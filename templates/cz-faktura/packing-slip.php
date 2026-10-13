<?php
/**
 * Dodací list.
 *
 * @package CzFaktura
 * @var \WPO\IPS\Documents\OrderDocument $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$type = $this->get_type();
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
			<?php do_action( 'wpo_wcpdf_before_shop_name', $type, $this->order ); ?>
			<div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
			<?php do_action( 'wpo_wcpdf_after_shop_name', $type, $this->order ); ?>
			<?php do_action( 'wpo_wcpdf_before_shop_address', $type, $this->order ); ?>
			<div class="shop-address"><?php $this->shop_address(); ?></div>
			<?php do_action( 'wpo_wcpdf_after_shop_address', $type, $this->order ); ?>
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

<h1 class="document-type-label"><?php esc_html_e( 'Dodací list', 'cz-faktura' ); ?></h1>

<?php do_action( 'wpo_wcpdf_after_document_label', $type, $this->order ); ?>

<table class="order-data-addresses">
	<tr>
		<td class="address shipping-address">
			<p class="party-label"><?php esc_html_e( 'Doručit na', 'cz-faktura' ); ?></p>
			<?php do_action( 'wpo_wcpdf_before_shipping_address', $type, $this->order ); ?>
			<p><?php $this->shipping_address(); ?></p>
			<?php do_action( 'wpo_wcpdf_after_shipping_address', $type, $this->order ); ?>
			<?php if ( isset( $this->settings['display_email'] ) ) : ?>
				<div class="billing-email"><?php $this->billing_email(); ?></div>
			<?php endif; ?>
			<?php if ( isset( $this->settings['display_phone'] ) ) : ?>
				<div class="shipping-phone"><?php $this->shipping_phone( ! $this->show_billing_address() ); ?></div>
			<?php endif; ?>
		</td>
		<td class="address billing-address">
			<?php if ( $this->show_billing_address() ) : ?>
				<p class="party-label"><?php esc_html_e( 'Fakturační adresa', 'cz-faktura' ); ?></p>
				<?php do_action( 'wpo_wcpdf_before_billing_address', $type, $this->order ); ?>
				<p><?php $this->billing_address(); ?></p>
				<?php do_action( 'wpo_wcpdf_after_billing_address', $type, $this->order ); ?>
				<?php if ( isset( $this->settings['display_phone'] ) && ! empty( $this->get_billing_phone() ) ) : ?>
					<div class="billing-phone"><?php $this->billing_phone(); ?></div>
				<?php endif; ?>
			<?php endif; ?>
		</td>
		<td class="order-data">
			<table>
				<?php do_action( 'wpo_wcpdf_before_order_data', $type, $this->order ); ?>
				<tr class="order-number">
					<th><?php esc_html_e( 'Číslo objednávky:', 'cz-faktura' ); ?></th>
					<td><?php $this->order_number(); ?></td>
				</tr>
				<tr class="order-date">
					<th><?php esc_html_e( 'Datum objednávky:', 'cz-faktura' ); ?></th>
					<td><?php $this->order_date(); ?></td>
				</tr>
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

<table class="order-details">
	<thead>
		<tr>
			<th class="product"><?php esc_html_e( 'Položka', 'cz-faktura' ); ?></th>
			<th class="quantity"><?php esc_html_e( 'Množství', 'cz-faktura' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $this->get_order_items() as $item_id => $item ) : ?>
			<tr class="<?php echo esc_attr( $item['row_class'] ); ?>">
				<td class="product">
					<p class="item-name"><?php echo esc_html( $item['name'] ); ?></p>
					<?php do_action( 'wpo_wcpdf_before_item_meta', $type, $item, $this->order ); ?>
					<div class="item-meta">
						<?php if ( ! empty( $item['sku'] ) ) : ?>
							<p class="sku"><span class="label"><?php esc_html_e( 'Kód:', 'cz-faktura' ); ?></span> <?php echo esc_html( $item['sku'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $item['weight'] ) ) : ?>
							<p class="weight"><span class="label"><?php esc_html_e( 'Hmotnost:', 'cz-faktura' ); ?></span> <?php echo esc_html( $item['weight'] ); ?> <?php echo esc_html( get_option( 'woocommerce_weight_unit' ) ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $item['meta'] ) ) : ?>
							<?php echo wp_kses_post( $item['meta'] ); ?>
						<?php endif; ?>
					</div>
					<?php do_action( 'wpo_wcpdf_after_item_meta', $type, $item, $this->order ); ?>
				</td>
				<td class="quantity"><?php echo cz_faktura_qty( $item['quantity'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<?php do_action( 'wpo_wcpdf_after_order_details', $type, $this->order ); ?>

<?php do_action( 'wpo_wcpdf_before_customer_notes', $type, $this->order ); ?>
<?php if ( $this->get_shipping_notes() ) : ?>
	<div class="customer-notes">
		<h3><?php esc_html_e( 'Poznámka zákazníka', 'cz-faktura' ); ?></h3>
		<?php $this->shipping_notes(); ?>
	</div>
<?php endif; ?>
<?php do_action( 'wpo_wcpdf_after_customer_notes', $type, $this->order ); ?>

<div class="bottom-spacer"></div>

<?php if ( $this->get_footer() ) : ?>
	<htmlpagefooter name="docFooter"><!-- required for mPDF engine -->
		<div id="footer">
			<?php $this->footer(); ?>
		</div>
	</htmlpagefooter><!-- required for mPDF engine -->
<?php endif; ?>

<?php do_action( 'wpo_wcpdf_after_document', $type, $this->order ); ?>
