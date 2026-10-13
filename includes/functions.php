<?php
/**
 * Pomocné funkce pro šablony.
 *
 * @package CzFaktura
 */

defined( 'ABSPATH' ) || exit;

/** Data faktury pro dokument (v šabloně: cz_faktura( $this )). */
function cz_faktura( $document ): \AlkoKod\CzFaktura\Invoice_Data {
	return \AlkoKod\CzFaktura\Invoice_Data::for_document( $document );
}

/** Částka ve měně objednávky (HTML z WooCommerce). */
function cz_faktura_price( $document, $amount ): string {
	return wp_kses_post( $document->format_price( (float) $amount ) );
}

/** Sazba DPH: „21 %“, „12,5 %“. */
function cz_faktura_rate( $rate ): string {
	if ( null === $rate ) {
		return '–';
	}
	$rate     = (float) $rate;
	$decimals = abs( $rate - round( $rate ) ) > 0.001 ? 1 : 0;
	return number_format_i18n( $rate, $decimals ) . "\u{00A0}%";
}

/** Datum ve formátu dokumentů (filtr `wpo_wcpdf_date_format` respektuje nastavení pluginu). */
function cz_faktura_date( $document, $date ): string {
	if ( ! $date instanceof \WC_DateTime ) {
		return '';
	}
	$format = apply_filters( 'wpo_wcpdf_date_format', cz_faktura_date_format(), $document );
	return esc_html( date_i18n( $format, $date->getOffsetTimestamp() ) );
}

/** Formát data dokladů (5. 10. 2025). */
function cz_faktura_date_format(): string {
	return (string) apply_filters( 'cz_faktura_date_format', 'j. n. Y' );
}

/** Text pro PDF: znaky, které běžné fonty nemají (rámečkové čáry, emoji), nahradí nebo vypustí. */
function cz_faktura_text( $text ): string {
	$text = str_replace( array( "\u{2500}", "\u{2014}", "\u{2015}" ), '–', (string) $text );
	return (string) preg_replace( '/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{FE0F}]/u', '', $text );
}

/** Množství bez zbytečných desetinných míst. */
function cz_faktura_qty( $quantity ): string {
	$quantity = (float) $quantity;
	return esc_html( number_format_i18n( $quantity, abs( $quantity - round( $quantity ) ) > 0.0001 ? 2 : 0 ) );
}
