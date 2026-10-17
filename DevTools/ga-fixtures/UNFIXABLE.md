# Cards without a complete semantic fixture

The roadmap's exit condition is "every implemented card has at least one semantic fixture or is listed here with the reason". `php DevTools/build-ga-semantic-backlog.php` lists the cards that still have none.
Cards that are fixtured except for one clause are listed too, so the gap is written down.

## Current semantic coverage exceptions

None. The baseline is empty after the Phase D Duplicitous Replication and Lorraine fixtures. This means every card in the coverage audit has a semantic fixture; it does not prove every printed clause or wider engine mechanic is complete. Remaining implementation work stays unchecked in ROADMAP.md.

## Phase D resolved clauses

- Greater Boon of Luxera: generation restrictions now gate the implemented memory, deck, banishment and lineage generation routes. Mixed-effect instructions retain their other effects.
- Veiled Dash: explicit zero-to-two target and zero-to-all Wind reveal choices.
- Heavenly Guide: played versus effect-driven top-champion provenance, including inherited history through level-up and delevel.
- Greater Boon of Enki: explicit simultaneous field-entry groups, including the different tokens in Floral Arrangement, with a copy choice.
- Duplicitous Replication: field entries outside materialization now trigger; simultaneous groups copy every regalia. A real activation followed by Zephyr's opposing end-phase return is fixtured.
- Lorraine, Ascendant Wings: the user selected full printed implementation. Actual 2024-reserve payment is fixtured on the card and inherited from lineage; generation permits zero or repeated Sword selections and installs permanent independent Sword-attack permission.

## Resolved exceptions (2026-10-16)

Frostbitten Etui and Stifling Aethercharge now have real pending-On-Enter negation fixtures in `enter-response.php`. The response window already existed; its default auto-pass preference hid it in previous fixtures. Etui exposed a cross-controller payment race, now fixed so the trigger waits for payment or decline and resumes once. Negation filters the ENTER source by UniqueID.
