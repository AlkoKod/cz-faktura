<?php
/**
 * Plugin Name:          CZ Faktura
 * Plugin URI:           https://github.com/AlkoKod/cz-faktura
 * Description:          Česká šablona faktury (daňového dokladu) a dodacího listu pro PDF Invoices & Packing Slips for WooCommerce: rozpis DPH podle sazeb včetně dopravy a poplatků, IČO/DIČ dodavatele i odběratele, DUZP, variabilní symbol a platební údaje.
 * Version:              1.0.0
 * Author:               AlkoKod
 * Author URI:           https://github.com/AlkoKod
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          cz-faktura
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce, woocommerce-pdf-invoices-packing-slips
 * WC requires at least: 8.0
 * WC tested up to:      11.2
 */

defined( 'ABSPATH' ) || exit;

define( 'CZ_FAKTURA_VERSION', '1.0.0' );
define( 'CZ_FAKTURA_DIR', __DIR__ );

require_once __DIR__ . '/includes/class-invoice-data.php';
require_once __DIR__ . '/includes/functions.php';

// WooCommerce: šablona čte objednávky jen přes WC_Order API, takže funguje s HPOS i bez něj.
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

/*
 * Registrace šablony. Klíč „extension::cz-faktura“ dává stabilní ID šablony
 * „extension::cz-faktura/cz-faktura“ (nezávisí na pořadí ostatních pluginů se šablonami).
 */
add_filter( 'wpo_wcpdf_template_paths', function ( $paths ) {
	$paths['extension::cz-faktura'] = CZ_FAKTURA_DIR . '/templates/';
	return $paths;
} );
