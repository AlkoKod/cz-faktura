# CZ Faktura

Česká šablona **faktury – daňového dokladu** a **dodacího listu** pro plugin
[PDF Invoices & Packing Slips for WooCommerce](https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/) od WP Overnight.

Šablona je samostatný plugin, takže nepotřebuje *Custom PDF Template extension*, nenahrává se do šablony motivu a aktualizace pluginu WP Overnight ji nepřepíšou.

## Co umí

- **Rekapitulace DPH podle sazeb** ze všech řádků objednávky: zboží, doprava i poplatky (např. přirážka za dobírku nebo sleva na dopravu).
- Položky s **cenou za kus bez DPH, sazbou, základem, DPH a cenou s DPH**. Všechny sloupce počítají s cenou po slevě a součet sedí na korunu s celkovou částkou objednávky. Případný rozdíl se vypíše jako *Zaokrouhlení*.
- Sazby se berou **z objednávky**, ne z aktuálního nastavení obchodu, takže pozdější změna sazeb staré doklady nezmění.
- **IČO, DIČ a IČ DPH odběratele** z polí objednávky. Podporuje WPify Woo a běžné české pluginy, další klíče lze doplnit filtrem.
- **IČO a DIČ dodavatele** z nastavení pluginu. Nevypisují se dvakrát, pokud už jsou v adrese obchodu.
- **DUZP**, datum vystavení, datum splatnosti (je-li zapnuté), **variabilní symbol**, způsob platby a dopravy.
- U zaplacených objednávek text **„Uhrazeno – neplaťte“**. U nezaplaceného bankovního převodu **číslo účtu, IBAN a VS** z nastavení WooCommerce.
- **Neplátce DPH**: když v nastavení není DIČ a objednávka neobsahuje daň, vytvoří se faktura bez sloupců DPH s poznámkou „Nejsme plátci DPH“.
- Česká data (`5. 10. 2025`), čísla a měna podle WooCommerce.
- **Dodací list** v češtině, s adresou doručení (včetně výdejních míst).
- Kompatibilní s **HPOS** (objednávky čte jen přes `WC_Order` API), s PDF Invoices & Packing Slips **5.x i 6.x**, s režimem šetření inkoustem (v6) a s rozšířením mPDF.
- Všechny výstupy jsou escapované a zůstaly všechny háčky (`wpo_wcpdf_*`), na které se napojují jiné pluginy (Zásilkovna, balíčky produktů…).

## Instalace

1. Stáhněte `cz-faktura.zip` z [Releases](https://github.com/AlkoKod/cz-faktura/releases).
2. *Pluginy → Přidat nový → Nahrát plugin* a aktivujte ho.
3. *WooCommerce → PDF Invoices → Obecné → Vyberte šablonu*: **cz-faktura (Extension) [cz-faktura]**.
4. Do adresy obchodu vyplňte sídlo. IČO zadejte do pole pro registrační číslo firmy (CoC) a DIČ do pole pro DIČ (VAT), nebo je napište přímo do adresy.

Požadavky: WordPress 6.5+, PHP 7.4+, WooCommerce 8.0+, PDF Invoices & Packing Slips 5.0+.

## Nastavení filtry

Do `functions.php` motivu nebo do vlastního pluginu:

```php
// DUZP: 'issue_date' (výchozí, = datum vystavení), 'order_date', 'paid_or_completed'
add_filter( 'cz_faktura_duzp_source', fn() => 'paid_or_completed' );

// Další meta klíče pro IČO / DIČ odběratele
add_filter( 'cz_faktura_customer_id_keys', function ( $keys ) {
	$keys['IČO'][] = '_billing_company_ico';
	return $keys;
} );

// Formát data
add_filter( 'cz_faktura_date_format', fn() => 'd.m.Y' );

// Vynutit režim neplátce / plátce DPH
add_filter( 'cz_faktura_is_vat_payer', '__return_false' );
```

Další filtry: `cz_faktura_rows`, `cz_faktura_vat_recap`, `cz_faktura_totals`, `cz_faktura_line_rate`, `cz_faktura_supplier_ids`, `cz_faktura_customer_ids`, `cz_faktura_variable_symbol`, `cz_faktura_is_paid`, `cz_faktura_bank_accounts`, `cz_faktura_duzp`.

Vzhled lze upravit v *PDF Invoices → Obecné → Vlastní styly* nebo akcí `wpo_wcpdf_custom_styles`.

## Přechod ze staré šablony v *Custom PDF Template extension*

- Vybraná šablona se mění jen v nastavení. Už vystavené faktury (čísla, data) zůstávají, po stažení se jen vykreslí novým vzhledem.
- Stará šablona počítala rekapitulaci DPH bez poplatků (dobírka, slevy na dopravu) a ve sloupci „Cena s DPH“ ukazovala cenu před slevou. To je opravené.
- *Custom PDF Template extension* lze po přepnutí deaktivovat, pokud v něm nemáte jiné šablony.

## Vývoj

```
cz-faktura.php                 registrace šablony, deklarace HPOS
includes/class-invoice-data.php  výpočty: řádky, rekapitulace DPH, součty, IČO/DIČ, data, platba
includes/functions.php         pomocné funkce pro šablony
templates/cz-faktura/          invoice.php, packing-slip.php, style.css, html-document-wrapper.php, template-functions.php
```

Vydání: přidejte tag `vX.Y.Z` a GitHub Actions sestaví `cz-faktura.zip` do Releases.

## Licence

GPL-2.0-or-later
