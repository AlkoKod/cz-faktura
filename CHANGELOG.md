# Changelog

## 1.0.0

- První verze jako samostatný plugin. Nahrazuje starou šablonu `wellness2` v *Custom PDF Template extension*.
- Rekapitulace DPH zahrnuje dopravu i poplatky a sazby se berou z objednávky.
- Sloupce položek jsou konzistentně po slevě a součet sedí s objednávkou (případné zaokrouhlení se vypíše).
- IČO, DIČ a IČ DPH odběratele, IČO a DIČ dodavatele, DUZP, variabilní symbol, platební údaje a stav úhrady.
- Režim neplátce DPH.
- Kompatibilita s HPOS a s PDF Invoices & Packing Slips 5.x i 6.x. Odstraněné zastaralé volání `invoice_number()` a `invoice_date()`.
- Escapování všech výstupů, česká data a dodací list v češtině.
