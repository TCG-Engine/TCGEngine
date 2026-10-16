# Cards without a complete semantic fixture

The roadmap's exit condition is "every implemented card has at least one semantic fixture or is listed here with the reason". `php DevTools/build-ga-semantic-backlog.php` lists the cards that still have none.
Cards that are fixtured except for one clause are listed too, so the gap is written down.

## No fixture at all

| Card | Why |
| --- | --- |
| Frostbitten Etui | Its On Enter negation can only target an ally that is already on the field, and an ally's On Enter trigger is put on the effects stack and resolved in the same step, so no state offers a moment to use it. |
| Duplicitous Replication | "The next time one or more regalia enter the field under an opponent's control this turn" lasts one turn. A regalia enters under the opponent's control in their materialize phase, which no response window precedes, and only the materialize path checks the effect (regalia put onto the field by other effects are not checked). |
| Lorraine, Ascendant Wings | Its (2024) lineage ability costs 2024 reserve and its text is open-ended ("generate any amount of Sword regalia"); the ability is not implemented. |

## Fixtured except for one clause

| Card | Clause that is not covered | Why |
| --- | --- | --- |
| Stifling Aethercharge | "Negate all On Enter triggers from target ally." | Same limitation as Frostbitten Etui: there is never a moment to respond to an On Enter trigger. The engine's approximation negates card activations of that card name on the stack. The load clause is covered. |
| Greater Boon of Luxera | "You can't generate cards with the chosen name." | The restriction is not implemented. |
| Veiled Dash | "Reveal **any amount** of wind element cards" | The engine reveals every wind card in memory instead of asking. |
| Heavenly Guide | "A card on top of its lineage that wasn't played" | Nothing tracks whether a lineage card was played; the engine treats any lineage card as unplayed. |
| Greater Boon of Enki | "One or more tokens ... for the first time each turn" | Tokens are seen one at a time, so the first qualifying token is the one copied. |
