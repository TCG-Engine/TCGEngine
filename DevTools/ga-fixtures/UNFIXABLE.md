# Cards without a complete semantic fixture

The roadmap's exit condition is "every implemented card has at least one semantic fixture or is listed here with the reason". `php DevTools/build-ga-semantic-backlog.php` lists the cards that still have none.
Cards that are fixtured except for one clause are listed too, so the gap is written down.

## No fixture at all

| Card | Why |
| --- | --- |
| Duplicitous Replication | "The next time one or more regalia enter the field under an opponent's control this turn" lasts one turn. A regalia enters under the opponent's control in their materialize phase, which no response window precedes, and only the materialize path checks the effect (regalia put onto the field by other effects are not checked). |
| Lorraine, Ascendant Wings | Its (2024) lineage ability costs 2024 reserve and its text is open-ended ("generate any amount of Sword regalia"); the ability is not implemented. |

## Fixtured except for one clause

| Card | Clause that is not covered | Why |
| --- | --- | --- |
| Greater Boon of Luxera | "You can't generate cards with the chosen name." | The restriction is not implemented. |
| Veiled Dash | "Reveal **any amount** of wind element cards" | The engine reveals every wind card in memory instead of asking. |
| Heavenly Guide | "A card on top of its lineage that wasn't played" | Nothing tracks whether a lineage card was played; the engine treats any lineage card as unplayed. |
| Greater Boon of Enki | "One or more tokens ... for the first time each turn" | Tokens are seen one at a time, so the first qualifying token is the one copied. |

## Resolved exceptions (2026-10-16)

Frostbitten Etui and Stifling Aethercharge now have real pending-On-Enter negation fixtures in `enter-response.php`. The response window already existed; its default auto-pass preference hid it in previous fixtures. Etui exposed a cross-controller payment race, now fixed so the trigger waits for payment or decline and resumes once. Negation filters the ENTER source by UniqueID.
