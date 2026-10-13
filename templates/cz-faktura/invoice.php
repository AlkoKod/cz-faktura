<?php
/**
 * Faktura – daňový doklad.
 *
 * @package CzFaktura
 * @var \WPO\IPS\Documents\OrderDocument $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cz       = cz_faktura( $this );
$type     = $this->get_type();
$payer    = $cz->is_vat_payer();
$totals   = $cz->totals();
$recap    = $payer ? $cz->vat_recap() : array();
$supplier = $cz->supplier_ids();
$customer = $cz->customer_ids();
$accounts = $cz->bank_accounts();
$title    = $payer ? __( 'Faktura – daňový doklad', 'cz-faktura' ) : __( 'Faktura', 'cz-faktura' );
?>

<?php do_action( 'wpo_wcpdf_before_document', $type, $this->order ); ?>

<table class="head container">
	<tr>
		<td class="header">
			<?php if ( $this->has_header_logo() ) : ?>
				<?php do_action( 'wpo_wcpdf_before_shop_logo', $type, $this->order ); ?>
				<?php $this->header_logo(); ?>
				<?php do_action( 'wpo_wcpdf_after_shop_logo', $type, $this->order ); ?>
			<?php endif; ?>
		</td>
		<td class="shop-info">
			<p class="party-label"><?php esc_html_e( 'Dodavatel', 'cz-faktura' ); ?></p>
			<?php do_action( 'wpo_wcpdf_before_shop_name', $type, $this->order ); ?>
			<div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
			<?php do_action( 'wpo_wcpdf_after_shop_name', $type, $this->order ); ?>
			<?php do_action( 'wpo_wcpdf_before_shop_address', $type, $this->order ); ?>
			<div class="shop-address"><?php $this->shop_address(); ?></div>
			<?php do_action( 'wpo_wcpdf_after_shop_address', $type, $this->order ); ?>
			<?php foreach ( $supplier as $label => $value ) : ?>
				<div class="shop-id"><?php echo esc_html( $label ); ?>: <?php echo esc_html( $value ); ?></div>
			<?php endforeach; ?>
			<?php if ( ! $payer ) : ?>
				<div class="shop-id"><?php esc_html_e( 'Nejsme plátci DPH.', 'cz-faktura' ); ?></div>
			<?php endif; ?>
			<?php do_action( 'wpo_wcpdf_before_shop_phone_number', $type, $this->order ); ?>
			<?php if ( ! empty( $this->get_shop_phone_number() ) ) : ?>
				<div class="shop-phone-number"><?php $this->shop_phone_number(); ?></div>
			<?php endif; ?>
			<?php do_action( 'wpo_wcpdf_after_shop_phone_number', $type, $this->order ); ?>
			<?php if ( ! empty( $this->get_shop_email_address() ) ) : ?>
				<div class="shop-email-address"><?php $this->shop_email_address(); ?></div>
			<?php endif; ?>
			<?php do_action( 'wpo_wcpdf_after_shop_email_address', $type, $this->order ); ?>
		</td>
	</tr>
</table>

<?php do_action( 'wpo_wcpdf_before_document_label', $type, $this->order ); ?>

<h1 class="document-type-label">
	<?php echo esc_html( $title ); ?>
	<?php if ( $this->get_number( $type ) ) : ?>
		<span class="document-number"><?php $this->number( $type ); ?></span>
	<?php endif; ?>
</h1>

<?php do_action( 'wpo_wcpdf_after_document_label', $type, $this->order ); ?>

<table class="order-data-addresses">
	<tr>
		<td class="address billing-address">
			<p class="party-label"><?php esc_html_e( 'Odběratel', 'cz-faktura' ); ?></p>
			<?php do_action( 'wpo_wcpdf_before_billing_address', $type, $this->order ); ?>
			<p><?php $this->billing_address(); ?></p>
			<?php do_action( 'wpo_wcpdf_after_billing_address', $type, $this->order ); ?>
			<?php foreach ( $customer as $label => $value ) : ?>
				<div class="customer-id"><?php echo esc_html( $label ); ?>: <?php echo esc_html( $value ); ?></div>
			<?php endforeach; ?>
			<?php if ( isset( $this->settings['display_email'] ) ) : ?>
				<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php endif; ?>
			<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
				<div class="billing-phone"><?php $this->billing_phone(); ?></div>
			<?php endif; ?>
		</td>
		<td class="address shipping-address">
			<?php if ( $this->show_shipping_address() ) : ?>
				<p class="party-label"><?php $this->shipping_address_title(); ?></p>
				<?php do_action( 'wpo_wcpdf_before_shipping_address', $type, $this->order ); ?>
				<p><?php $this->shipping_address(); ?></p>
				<?php do_action( 'wpo_wcpdf_after_shipping_address', $type, $this->order ); ?>
				<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
					<div class="shipping-phone"><?php $this->shipping_phone(); ?></div>
				<?php endif; ?>
			<?php endif; ?>
		</td>
		<td class="order-data">
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $type, $this->order ); ?>
				<?php if ( isset( $this->settings['display_number'] ) ) : ?>
					<tr class="invoice-number">
						<th><?php esc_html_e( 'Číslo faktury:', 'cz-faktura' ); ?></th>
						<td><?php $this->number( $type ); ?></td>
					</tr>
				<?php endif; ?>
				<tr class="invoice-date">
					<th><?php esc_html_e( 'Datum vystavení:', 'cz-faktura' ); ?></th>
					<td><?php echo cz_faktura_date( $this, $cz->issue_date() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped ?></td>
				</tr>
				<?php if ( $payer ) : ?>
					<tr class="invoice-duzp">
						<th><?php esc_html_e( 'Datum zdan. plnění:', 'cz-faktura' ); ?></th>
						<td><?php echo cz_faktura_date( $this, $cz->duzp() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $this->show_due_date() ) : ?>
					<tr class="due-date">
						<th><?php esc_html_e( 'Datum splatnosti:', 'cz-faktura' ); ?></th>
						<td><?php $this->due_date(); ?></td>
					</tr>
				<?php endif; ?>
				<tr class="order-number">
					<th><?php esc_html_e( 'Číslo objednávky:', 'cz-faktura' ); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<tr class="order-date">
					<th><?php esc_html_e( 'Datum objednávky:', 'cz-faktura' ); ?></th>
					<td><?php $this->order_date(); ?></td>
				</tr>
				<?php if ( '' !== $cz->variable_symbol() ) : ?>
					<tr class="variable-symbol">
						<th><?php esc_html_e( 'Variabilní symbol:', 'cz-faktura' ); ?></th>
						<td><?php echo esc_html( $cz->variable_symbol() ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $this->get_payment_method() ) : ?>
					<tr class="payment-method">
						<th><?php esc_html_e( 'Způsob platby:', 'cz-faktura' ); ?></th>
						<td><?php echo esc_html( cz_faktura_text( $this->get_payment_method() ) ); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ( $this->get_shipping_method() ) : ?>
					<tr class="shipping-method">
						<th><?php esc_html_e( 'Způsob dopravy:', 'cz-faktura' ); ?></th>
						<td><?php $this->shipping_method(); ?></td>
					</tr>
				<?php endif; ?>
				<?php do_action( 'wpo_wcpdf_after_order_data', $type, $this->order ); ?>
			</table>
		</td>
	</tr>
</table>

<?php do_action( 'wpo_wcpdf_before_order_details', $type, $this->order ); ?>

<table class="order-details<?php echo $payer ? ' with-vat' : ' without-vat'; ?>">
	<thead>
		<tr>
			<th class="product"><?php esc_html_e( 'Položka', 'cz-faktura' ); ?></th>
			<th class="quantity"><?php esc_html_e( 'Množ.', 'cz-faktura' ); ?></th>
			<?php if ( $payer ) : ?>
				<th class="price"><?php esc_html_e( 'Cena/ks bez DPH', 'cz-faktura' ); ?></th>
				<th class="rate"><?php esc_html_e( 'Sazba', 'cz-faktura' ); ?></th>
				<th class="price"><?php esc_html_e( 'Základ', 'cz-faktura' ); ?></th>
				<th class="price"><?php esc_html_e( 'DPH', 'cz-faktura' ); ?></th>
				<th class="price total"><?php esc_html_e( 'Celkem s DPH', 'cz-faktura' ); ?></th>
			<?php else : ?>
				<th class="price"><?php esc_html_e( 'Cena/ks', 'cz-faktura' ); ?></th>
				<th class="price total"><?php esc_html_e( 'Celkem', 'cz-faktura' ); ?></th>
			<?php endif; ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $cz->rows() as $row ) : ?>
			<tr class="<?php echo esc_attr( $row['row_class'] ); ?>">
				<td class="product">
					<p class="item-name"><?php echo esc_html( $row['name'] ); ?></p>
					<?php if ( 'product' === $row['type'] ) : ?>
						<?php $item = $row['data']; ?>
						<?php do_action( 'wpo_wcpdf_before_item_meta', $type, $item, $this->order ); ?>
						<div class="item-meta">
							<?php if ( ! empty( $item['sku'] ) ) : ?>
								<p class="sku"><span class="label"><?php esc_html_e( 'Kód:', 'cz-faktura' ); ?></span> <?php echo esc_html( $item['sku'] ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $item['meta'] ) ) : ?>
								<?php echo wp_kses_post( $item['meta'] ); ?>
							<?php endif; ?>
						</div>
						<?php do_action( 'wpo_wcpdf_after_item_meta', $type, $item, $this->order ); ?>
					<?php endif; ?>
				</td>
				<td class="quantity"><?php echo cz_faktura_qty( $row['quantity'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped ?></td>
				<?php if ( $payer ) : ?>
					<td class="price"><?php echo cz_faktura_price( $this, $row['unit_net'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
					<td class="rate"><?php echo esc_html( cz_faktura_rate( $row['rate'] ) ); ?></td>
					<td class="price"><?php echo cz_faktura_price( $this, $row['net'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
					<td class="price"><?php echo cz_faktura_price( $this, $row['tax'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
				<?php else : ?>
					<td class="price"><?php echo cz_faktura_price( $this, $row['unit_gross'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
				<?php endif; ?>
				<td class="price total"><?php echo cz_faktura_price( $this, $row['gross'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<table class="notes-totals">
	<tbody>
		<tr class="no-borders">
			<td class="no-borders notes-cell">
				<?php if ( $recap ) : ?>
					<h3 class="recap-title"><?php esc_html_e( 'Rekapitulace DPH', 'cz-faktura' ); ?></h3>
					<table class="vat-recap">
						<thead>
							<tr>
								<th class="rate"><?php esc_html_e( 'Sazba', 'cz-faktura' ); ?></th>
								<th class="price"><?php esc_html_e( 'Základ', 'cz-faktura' ); ?></th>
								<th class="price"><?php esc_html_e( 'DPH', 'cz-faktura' ); ?></th>
								<th class="price"><?php esc_html_e( 'Celkem', 'cz-faktura' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recap as $bucket ) : ?>
								<tr>
									<td class="rate"><?php echo esc_html( cz_faktura_rate( $bucket['rate'] ) ); ?></td>
									<td class="price"><?php echo cz_faktura_price( $this, $bucket['net'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
									<td class="price"><?php echo cz_faktura_price( $this, $bucket['tax'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
									<td class="price"><?php echo cz_faktura_price( $this, $bucket['gross'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<?php if ( $totals['discount'] > 0 && $totals['coupons'] ) : ?>
					<p class="discount-note">
						<?php
						/* translators: 1: kódy kupónů, 2: výše slevy */
						echo wp_kses_post( sprintf( __( 'Uplatněná sleva (%1$s): %2$s vč. DPH, ceny položek jsou uvedeny po slevě.', 'cz-faktura' ), esc_html( implode( ', ', $totals['coupons'] ) ), cz_faktura_price( $this, $totals['discount'] ) ) );
						?>
					</p>
				<?php endif; ?>

				<?php do_action( 'wpo_wcpdf_before_document_notes', $type, $this->order ); ?>
				<?php if ( $this->get_document_notes() ) : ?>
					<div class="document-notes">
						<h3><?php esc_html_e( 'Poznámka', 'cz-faktura' ); ?></h3>
						<?php $this->document_notes(); ?>
					</div>
				<?php endif; ?>
				<?php do_action( 'wpo_wcpdf_after_document_notes', $type, $this->order ); ?>
				<?php do_action( 'wpo_wcpdf_before_customer_notes', $type, $this->order ); ?>
				<?php if ( $this->get_shipping_notes() ) : ?>
					<div class="customer-notes">
						<h3><?php esc_html_e( 'Poznámka zákazníka', 'cz-faktura' ); ?></h3>
						<?php $this->shipping_notes(); ?>
					</div>
				<?php endif; ?>
				<?php do_action( 'wpo_wcpdf_after_customer_notes', $type, $this->order ); ?>
			</td>
			<td class="no-borders totals-cell">
				<table class="totals">
					<tbody>
						<?php if ( $payer ) : ?>
							<tr class="total-net">
								<th class="description"><?php esc_html_e( 'Celkem bez DPH', 'cz-faktura' ); ?></th>
								<td class="price"><?php echo cz_faktura_price( $this, $totals['net'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
							</tr>
							<tr class="total-tax">
								<th class="description"><?php esc_html_e( 'DPH celkem', 'cz-faktura' ); ?></th>
								<td class="price"><?php echo cz_faktura_price( $this, $totals['tax'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
							</tr>
						<?php endif; ?>
						<?php if ( $totals['rounding'] ) : ?>
							<tr class="total-rounding">
								<th class="description"><?php esc_html_e( 'Zaokrouhlení', 'cz-faktura' ); ?></th>
								<td class="price"><?php echo cz_faktura_price( $this, $totals['rounding'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
							</tr>
						<?php endif; ?>
						<tr class="order_total">
							<th class="description"><?php esc_html_e( 'Celkem k úhradě', 'cz-faktura' ); ?></th>
							<td class="price"><?php echo cz_faktura_price( $this, $totals['total'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
						</tr>
						<?php if ( $totals['refunded'] > 0 ) : ?>
							<tr class="total-refunded">
								<th class="description"><?php esc_html_e( 'Vráceno zákazníkovi', 'cz-faktura' ); ?></th>
								<td class="price"><?php echo cz_faktura_price( $this, -$totals['refunded'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<?php if ( $cz->is_paid() ) : ?>
					<p class="payment-status paid">
						<?php
						/* translators: %s: datum zaplacení */
						echo esc_html( sprintf( __( 'Uhrazeno %s – neplaťte.', 'cz-faktura' ), wp_strip_all_tags( cz_faktura_date( $this, $cz->order()->get_date_paid() ) ) ) );
						?>
					</p>
				<?php elseif ( $accounts ) : ?>
					<div class="payment-status bank">
						<h3><?php esc_html_e( 'Platební údaje', 'cz-faktura' ); ?></h3>
						<?php foreach ( $accounts as $account ) : ?>
							<?php if ( ! empty( $account['account_number'] ) ) : ?>
								<div><?php esc_html_e( 'Číslo účtu:', 'cz-faktura' ); ?> <strong><?php echo esc_html( $account['account_number'] ); ?></strong></div>
							<?php endif; ?>
							<?php if ( ! empty( $account['iban'] ) ) : ?>
								<div>IBAN: <?php echo esc_html( $account['iban'] ); ?></div>
							<?php endif; ?>
							<?php if ( ! empty( $account['bic'] ) ) : ?>
								<div>BIC/SWIFT: <?php echo esc_html( $account['bic'] ); ?></div>
							<?php endif; ?>
						<?php endforeach; ?>
						<div><?php esc_html_e( 'Variabilní symbol:', 'cz-faktura' ); ?> <strong><?php echo esc_html( $cz->variable_symbol() ); ?></strong></div>
					</div>
				<?php endif; ?>
			</td>
		</tr>
	</tbody>
</table>

<?php do_action( 'wpo_wcpdf_after_order_details', $type, $this->order ); ?>

<div class="bottom-spacer"></div>

<?php if ( $this->get_footer() ) : ?>
	<htmlpagefooter name="docFooter"><!-- required for mPDF engine -->
		<div id="footer">
			<?php $this->footer(); ?>
		</div>
	</htmlpagefooter><!-- required for mPDF engine -->
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_document', $type, $this->order ); ?>
