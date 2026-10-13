<?php
/**
 * Data pro českou fakturu: řádky, rekapitulace DPH, součty, identifikace stran a data.
 *
 * Vše se čte přes WC_Order API (get_items, get_meta…), takže funguje s HPOS i s klasickým
 * ukládáním objednávek. Částky vychází z uložených hodnot objednávky (po slevách), sazby
 * z daňových řádků objednávky – změna sazeb v nastavení obchodu staré doklady nezmění.
 *
 * @package CzFaktura
 */

namespace AlkoKod\CzFaktura;

defined( 'ABSPATH' ) || exit;

final class Invoice_Data {

	/** @var \WC_Abstract_Order */
	private $order;

	/** @var object Dokument PDF Invoices & Packing Slips (WPO\IPS\Documents\OrderDocument). */
	private $document;

	/** @var array<int, float>|null Sazba v procentech podle ID daňové sazby. */
	private $rate_map = null;

	/** @var array<string, self> */
	private static $instances = array();

	private function __construct( $document ) {
		$this->document = $document;
		$this->order    = $document->order;
	}

	/** Jedna instance na dokument (šablona se ptá vícekrát). */
	public static function for_document( $document ): self {
		$key = spl_object_hash( $document );
		if ( ! isset( self::$instances[ $key ] ) ) {
			self::$instances[ $key ] = new self( $document );
		}
		return self::$instances[ $key ];
	}

	public function order() {
		return $this->order;
	}

	/* ------------------------------------------------------------------ */
	/* DPH                                                                 */
	/* ------------------------------------------------------------------ */

	/**
	 * Plátce DPH? Výchozí: v nastavení dokumentů je vyplněné DIČ, nebo objednávka obsahuje daň.
	 * Neplátci dostanou fakturu bez sloupců DPH a s poznámkou „Nejsme plátci DPH“.
	 */
	public function is_vat_payer(): bool {
		$payer = '' !== trim( (string) $this->document->get_shop_vat_number() ) || (float) $this->order->get_total_tax() > 0;
		return (bool) apply_filters( 'cz_faktura_is_vat_payer', $payer, $this->order, $this->document );
	}

	/** Sazby uložené v objednávce (WC 3.7+), jinak z tabulky sazeb. */
	private function rate_map(): array {
		if ( null !== $this->rate_map ) {
			return $this->rate_map;
		}
		$this->rate_map = array();
		foreach ( $this->order->get_items( 'tax' ) as $tax_item ) {
			$rate_id = (int) $tax_item->get_rate_id();
			$percent = is_callable( array( $tax_item, 'get_rate_percent' ) ) ? $tax_item->get_rate_percent() : null;
			if ( null === $percent || '' === $percent ) {
				$percent = \WC_Tax::get_rate_percent_value( $rate_id );
			}
			$this->rate_map[ $rate_id ] = (float) $percent;
		}
		return $this->rate_map;
	}

	/** Sazba DPH řádku v procentech (0 = bez daně). */
	public function line_rate( \WC_Order_Item $item ): float {
		$net   = (float) $item->get_total();
		$tax   = (float) $item->get_total_tax();
		$taxes = is_callable( array( $item, 'get_taxes' ) ) ? $item->get_taxes() : array();
		$ids   = array();
		foreach ( (array) ( $taxes['total'] ?? array() ) as $rate_id => $amount ) {
			if ( '' !== $amount && null !== $amount ) {
				$ids[] = (int) $rate_id;
			}
		}

		$map     = $this->rate_map();
		$percent = 0.0;
		$known   = (bool) $ids;
		foreach ( $ids as $rate_id ) {
			if ( isset( $map[ $rate_id ] ) ) {
				$percent += $map[ $rate_id ];
			} else {
				$known = false;
			}
		}
		// Sazbu nelze dohledat (smazaná sazba, import) – dopočítat z částek.
		if ( ! $known && abs( $net ) >= 0.01 && abs( $tax ) >= 0.01 ) {
			$percent = round( $tax / $net * 100, 1 );
		}

		return (float) apply_filters( 'cz_faktura_line_rate', $percent, $item, $this->order );
	}

	/**
	 * Řádky dokladu: produkty (v podobě, jak je připraví plugin – meta, SKU, balíčky), doprava a poplatky.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function rows(): array {
		$rows = array();
		foreach ( $this->document->get_order_items() as $item_id => $data ) {
			$item = $data['item'] ?? $this->order->get_item( $item_id );
			if ( $item instanceof \WC_Order_Item_Product ) {
				$rows[] = $this->row( 'product', $item, $data );
			}
		}
		foreach ( $this->order->get_items( 'shipping' ) as $item ) {
			$rows[] = $this->row( 'shipping', $item );
		}
		foreach ( $this->order->get_items( 'fee' ) as $item ) {
			$rows[] = $this->row( 'fee', $item );
		}
		return (array) apply_filters( 'cz_faktura_rows', $rows, $this->order, $this->document );
	}

	private function row( string $type, \WC_Order_Item $item, array $data = array() ): array {
		$net      = (float) $item->get_total();
		$tax      = (float) $item->get_total_tax();
		$quantity = 'product' === $type ? (float) $item->get_quantity() : 1.0;

		if ( 'product' === $type ) {
			$name = (string) ( $data['name'] ?? $item->get_name() );
		} elseif ( 'shipping' === $type ) {
			/* translators: %s: název způsobu dopravy */
			$name = sprintf( __( 'Doprava – %s', 'cz-faktura' ), self::clean_name( $item->get_name() ) );
		} else {
			$name = self::clean_name( $item->get_name() );
		}

		return array(
			'type'      => $type,
			'item'      => $item,
			'data'      => $data,
			'name'      => $name,
			'quantity'  => $data['quantity'] ?? $quantity,
			'unit_net'  => $quantity ? $net / $quantity : $net,
			'unit_gross' => $quantity ? ( $net + $tax ) / $quantity : $net + $tax,
			'net'       => $net,
			'tax'       => $tax,
			'gross'     => $net + $tax,
			// bez daně a s nulovou částkou (doprava zdarma) – sazba se nevypisuje
			'rate'      => ( abs( $net ) < 0.005 && abs( $tax ) < 0.005 && ! $this->line_rate( $item ) ) ? null : $this->line_rate( $item ),
			'row_class' => trim( (string) ( $data['row_class'] ?? '' ) . ' cz-row cz-row--' . $type ),
		);
	}

	/** Odstraní značky, které do názvů poplatků v minulosti vkládal TranslatePress. */
	public static function clean_name( string $name ): string {
		$name = preg_replace( '/#!trpst#.*?#!trpen#/s', '', $name );
		return trim( wp_strip_all_tags( (string) $name ) );
	}

	/**
	 * Rekapitulace DPH podle sazeb ze VŠECH řádků objednávky (produkty, doprava, poplatky),
	 * nezávisle na tom, které řádky šablona zobrazí.
	 *
	 * @return array<int, array{rate: float, net: float, tax: float, gross: float}>
	 */
	public function vat_recap(): array {
		$buckets = array();
		foreach ( $this->order->get_items( array( 'line_item', 'shipping', 'fee' ) ) as $item ) {
			$rate = $this->line_rate( $item );
			$key  = number_format( $rate, 2, '.', '' );
			if ( ! isset( $buckets[ $key ] ) ) {
				$buckets[ $key ] = array( 'rate' => $rate, 'net' => 0.0, 'tax' => 0.0, 'gross' => 0.0 );
			}
			$buckets[ $key ]['net'] += (float) $item->get_total();
			$buckets[ $key ]['tax'] += (float) $item->get_total_tax();
		}
		foreach ( $buckets as $key => &$bucket ) {
			$bucket['gross'] = $bucket['net'] + $bucket['tax'];
			if ( abs( $bucket['net'] ) < 0.005 && abs( $bucket['tax'] ) < 0.005 ) {
				unset( $buckets[ $key ] );
			}
		}
		unset( $bucket );
		uasort( $buckets, function ( $a, $b ) {
			return $b['rate'] <=> $a['rate'];
		} );
		return (array) apply_filters( 'cz_faktura_vat_recap', array_values( $buckets ), $this->order, $this->document );
	}

	/**
	 * Součty dokladu. „rounding“ je rozdíl mezi součtem řádků a celkovou částkou objednávky
	 * (zaokrouhlení na celé koruny apod.).
	 */
	public function totals(): array {
		$net = 0.0;
		$tax = 0.0;
		foreach ( $this->vat_recap() as $bucket ) {
			$net += $bucket['net'];
			$tax += $bucket['tax'];
		}
		$total    = (float) $this->order->get_total();
		$rounding = round( $total - ( $net + $tax ), wc_get_price_decimals() );

		return (array) apply_filters(
			'cz_faktura_totals',
			array(
				'net'      => $net,
				'tax'      => $tax,
				'rounding' => abs( $rounding ) >= 0.005 ? $rounding : 0.0,
				'total'    => $total,
				'discount' => (float) $this->order->get_discount_total() + (float) $this->order->get_discount_tax(),
				'coupons'  => $this->order->get_coupon_codes(),
				'refunded' => (float) $this->order->get_total_refunded(),
			),
			$this->order,
			$this->document
		);
	}

	/* ------------------------------------------------------------------ */
	/* Strany dokladu                                                      */
	/* ------------------------------------------------------------------ */

	/** Text bez HTML a mezer pro porovnání, zda už údaj v adrese je. */
	private static function squash( string $text ): string {
		return strtoupper( preg_replace( '/\s+/u', '', wp_strip_all_tags( html_entity_decode( $text ) ) ) );
	}

	/**
	 * IČO a DIČ dodavatele z nastavení dokumentů (pole „Číslo KvK/registrace“ a „DIČ“).
	 * Údaj, který už je ve volném textu adresy obchodu, se nevypisuje znovu.
	 *
	 * @return array<string, string> popisek => hodnota
	 */
	public function supplier_ids(): array {
		$address = self::squash( (string) $this->document->get_shop_address() );
		$ids     = array(
			__( 'IČO', 'cz-faktura' ) => trim( (string) $this->document->get_shop_coc_number() ),
			__( 'DIČ', 'cz-faktura' ) => trim( (string) $this->document->get_shop_vat_number() ),
		);
		foreach ( $ids as $label => $value ) {
			if ( '' === $value || false !== strpos( $address, self::squash( $value ) ) ) {
				unset( $ids[ $label ] );
			}
		}
		return (array) apply_filters( 'cz_faktura_supplier_ids', $ids, $this->order, $this->document );
	}

	/**
	 * IČO / DIČ / IČ DPH odběratele z meta objednávky. Výchozí klíče pokrývají WPify Woo
	 * a běžné české pluginy; doplnit lze filtrem `cz_faktura_customer_id_keys`.
	 *
	 * @return array<string, string> popisek => hodnota
	 */
	public function customer_ids(): array {
		$keys = (array) apply_filters(
			'cz_faktura_customer_id_keys',
			array(
				__( 'IČO', 'cz-faktura' )    => array( '_billing_ic', 'billing_ic', '_billing_ico', 'billing_ico', '_billing_company_id' ),
				__( 'DIČ', 'cz-faktura' )    => array( '_billing_dic', 'billing_dic', '_billing_vat_number', 'billing_vat_number', '_vat_number', 'vat_number' ),
				__( 'IČ DPH', 'cz-faktura' ) => array( '_billing_dic_dph', 'billing_dic_dph', '_billing_billing_dic_dph', '_billing_ic_dph', 'billing_ic_dph' ),
			),
			$this->order
		);
		$address = self::squash( (string) $this->order->get_formatted_billing_address() );
		$ids     = array();
		foreach ( $keys as $label => $meta_keys ) {
			foreach ( (array) $meta_keys as $meta_key ) {
				$value = trim( (string) $this->order->get_meta( $meta_key ) );
				if ( '' !== $value ) {
					if ( false === strpos( $address, self::squash( $value ) ) ) {
						$ids[ $label ] = $value;
					}
					break;
				}
			}
		}
		return (array) apply_filters( 'cz_faktura_customer_ids', $ids, $this->order, $this->document );
	}

	/* ------------------------------------------------------------------ */
	/* Data a platba                                                       */
	/* ------------------------------------------------------------------ */

	/** Datum vystavení dokladu (datum dokumentu podle nastavení pluginu). */
	public function issue_date(): ?\WC_DateTime {
		$date = $this->document->get_date();
		return $date instanceof \WC_DateTime ? $date : $this->order->get_date_created();
	}

	/**
	 * Datum uskutečnění zdanitelného plnění.
	 * Zdroj filtrem `cz_faktura_duzp_source`:
	 *  - 'issue_date' (výchozí) – shodné s datem vystavení,
	 *  - 'order_date' – datum objednávky,
	 *  - 'paid_or_completed' – dřívější z data zaplacení a dokončení objednávky (dodání).
	 */
	public function duzp(): ?\WC_DateTime {
		$source = (string) apply_filters( 'cz_faktura_duzp_source', 'issue_date', $this->order, $this->document );
		$date   = null;
		if ( 'order_date' === $source ) {
			$date = $this->order->get_date_created();
		} elseif ( 'paid_or_completed' === $source ) {
			$candidates = array_filter( array( $this->order->get_date_paid(), $this->order->get_date_completed() ) );
			if ( $candidates ) {
				usort( $candidates, function ( $a, $b ) {
					return $a->getTimestamp() <=> $b->getTimestamp();
				} );
				$date = $candidates[0];
			}
		}
		$date = $date ?: $this->issue_date();
		return apply_filters( 'cz_faktura_duzp', $date, $this->order, $this->document );
	}

	/** Variabilní symbol: číslice z čísla objednávky, nejvýše 10 znaků. */
	public function variable_symbol(): string {
		$vs = substr( preg_replace( '/\D+/', '', (string) $this->order->get_order_number() ), -10 );
		return (string) apply_filters( 'cz_faktura_variable_symbol', $vs, $this->order, $this->document );
	}

	/** Zaplaceno předem (kartou, převodem…)? Dobírka se nepočítá – hradí se při převzetí. */
	public function is_paid(): bool {
		$paid = null !== $this->order->get_date_paid() && 'cod' !== $this->order->get_payment_method();
		return (bool) apply_filters( 'cz_faktura_is_paid', $paid, $this->order, $this->document );
	}

	/**
	 * Bankovní účty pro platbu převodem (jen nezaplacené objednávky s platbou „Bankovní převod“).
	 *
	 * @return array<int, array<string, string>>
	 */
	public function bank_accounts(): array {
		$accounts = array();
		if ( 'bacs' === $this->order->get_payment_method() && ! $this->is_paid() ) {
			$accounts = array_filter( (array) get_option( 'woocommerce_bacs_accounts', array() ) );
		}
		return (array) apply_filters( 'cz_faktura_bank_accounts', $accounts, $this->order, $this->document );
	}
}
