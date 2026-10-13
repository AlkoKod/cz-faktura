<?php
/**
 * Filtry a akce šablony CZ Faktura. Logika dokladu je v includes/ pluginu,
 * tady jen nastavení, která plugin načítá pro vybranou šablonu.
 *
 * @package CzFaktura
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Česká data (5. 10. 2025) i u data objednávky a splatnosti, které vypisuje plugin.
add_filter( 'wpo_wcpdf_date_format', function () {
	return cz_faktura_date_format();
} );

// Režim „šetření inkoustem“ (v6): černobílá hlavička tabulky a součtů.
add_filter( 'wpo_ips_ink_saving_supported_templates', function ( $templates ) {
	$templates[] = 'extension::cz-faktura/cz-faktura';
	return $templates;
} );

add_filter( 'wpo_ips_ink_saving_css', function ( $css, $document, $current_template ) {
	if ( 'extension::cz-faktura/cz-faktura' !== $current_template ) {
		return $css;
	}
	return $css . '
		.order-details thead th, .vat-recap thead th { color: #000; background: #fff; border-bottom: .8pt solid #000; }
		.totals tr.order_total th, .totals tr.order_total td { background: #fff; color: #000; border-top: .8pt solid #000; border-bottom: .8pt solid #000; }
	';
}, 10, 3 );
