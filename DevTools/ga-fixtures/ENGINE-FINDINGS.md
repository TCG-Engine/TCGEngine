# GrandArchiveSim engine findings (as of the end of Phase 3a Fire)

Everything here was found by writing a fixture that plays a card the way a player would and asserting the printed text.
Section 1 is fixed and covered by a fixture. Sections 2 and 3 are **open**; section 2 are behaviour-wide changes that need a decision
before they are made, section 3 are cards whose text is not implemented at all.

Fixes live in `GrandArchiveSim/Custom/*` (mostly `GeneratedAbilityOverrides.php`, `GameLogic.php`, `CombatLogic.php`); no generated file
was edited by hand.

## 1. Fixed

### Printed keywords that did nothing (hand-kept registries missing cards)
`$rangedCardValues`, `$ephemerateCards`, `$Kindle_Cards`, `$Cardistry_Cards`, `$Imbue_Cards` and the `*Link_Cards` sets list cards by id, so a card
missing from its list silently lost the keyword. Added: Ranged (Ashfletched Bowman, VelTech Armiger, Nimble Longbowman, Huang Zhong, Acheron
Express Officer, Rampart Defender), Ephemerate (Emberwrath Witch, Peer Beyond, Classical Opening, Distort Reality), Kindle (Silent Firebrand),
Cardistry (Eight of Hearts), Imbue (Tempestuous Seraphim). `DevTools/audit-ga-keyword-registries.php` now cross-checks every printed keyword
against these lists and lists the known gaps.

### Costs and restrictions that were printed but never enforced
| Card(s) | What was wrong |
|---|---|
| Firetuned Automaton, Dichroic Scorch, Plutus | "As an additional cost, discard a (fire) card" was never paid (the generated body only said it was "handled in DoActivateCard"). New `GenericDiscardCost`; refusal gate when nothing can be discarded; the activated card cannot discard itself. |
| Innervate Fury | "Delevel your champion and recover 5" was never paid (its sibling Innervate Agility had it). |
| Oppressive Presence | Generated prereq was inverted, so the card could never be activated in the opponent's recollection phase. |
| Duchess, Six of Hearts; Two of Spades, Three of Spades, Two of Diamonds, Four of Hearts, Five of Spades, Five of Hearts, Five of Diamonds | Cardistry "Activate this ability only once" was never recorded, so it could be repeated. |
| Eight of Hearts | Cardistry draw was filed as a play effect (cast drew two cards for free; the field ability did not exist). |
| Emberwrath Witch | Nothing sacrificed it at the end phase. |

### Wrong target / wrong object
| Card(s) | What was wrong |
|---|---|
| Firetongue, Dragon's Dawn | Weapon On Attack closures receive the **attacker's** mz as `$mzID`, so "CARDNAME" meant the champion: the durability counter, the +2 POWER and the On Champion Hit draw never went on the weapon. |
| Potion Infusion: Blaze | Read the raw `CombatAttacker` mz (attacker's perspective) from the defender's side, so the 4 damage never hit the attacking ally. |
| Inflamed Bladehand, Brooch X Ultra, AquaTech Shell | Generated `OnAttack` macro listeners were never dispatched. |
| Innervate Fury, Spirit Blade: Dispersion (all split damage) | A target that died shifted the zone indexes, leaving a later target damaged but alive. Damage is now dealt highest index first. |

### Crashes and wrong arithmetic
| Card | What was wrong |
|---|---|
| Duchess, Six of Hearts | Using Cardistry fataled the engine (`stdClass::Remove()`), and Exalted fire actions were never offered. |
| Gloamspire, Black Market | "Influence" was counted as memory only, in both the draw ability (every player drew) and the Upkeep (never sacrificed). |

## 2. Open: behaviour-wide, needs a decision

### A. Exalted cards are not "fire/water/wind/norm element" to exact-match checks
80 cards have a two-element value (`EXALTED,FIRE` 14, `EXALTED,WIND` 17, `EXALTED,WATER` 15, `EXALTED,NORM` 34). `ZoneSearch(cardElements:)` handles that
correctly, but about 64 hand-written and generated sites compare `CardElement($id) === "FIRE"` (FIRE 33, WIND 16, WATER 12, NORM 3), so those 80 cards fall
outside Kindle payments, "fire element card" discards and banishes, fire-source unpreventable damage, fire-ally death triggers and the like.
Affected fire cards include Blaze Alight, Suffocating Ash, Tyrannical Denigration, Lavastorm, Alizarin Longbowman and Queen's Cinderhog.
Proposed fix: one `CardHasElement($id, $el)` helper (`PropertyContains`) and replace the comparisons that mean "is an X element card".
Risk: changes the outcome of any game that plays those 80 cards; each site needs a look because a few `=== "NORM"` tests are deliberate.

### B. "Activate this ability only at slow speed" is not enforced in fast windows
14 cards print it (Lesser Boon of Nourishment, Greater Boon of Inari, Ninja Tabi, Luminous Quartz, Endura, Ghostsight Glass, Coronal of Rejuvenation,
Ebbing Tide, Fatestone of Heaven, Memento Mori, Divine Comedy, Minister of Ceremony, Luxera's Map, Gloamspire). Gloamspire's draw resolved from the
end-of-turn response window. `GetPlayableFastAbilities` offers them and the per-card prereqs (`!HasOpportunity($player)`) do not stop it.

### C. Raw `GetVariable("CombatAttacker")` readers
The variable is stored from the attacker's perspective (`myField-N`). 75 places read it directly (GameLogic 26, CombatLogic 22, generated 24, other 3).
Reads by the attacking player are right; reads by the defending player (reactions, "target attacking ally", defender-side triggers) resolve against the
wrong zone. Potion Infusion: Blaze was one; `GetCombatAttackerMZ()` is the viewer-relative accessor. Needs an audit of the defender-side sites only.

### D. `[Class Bonus] Kindle` is not gated on the class bonus
Silent Firebrand, Burning Aethercharge, Glowering Conflagration (and Molten Echo's Merlin Bonus Kindle) offer Kindle whether or not the bonus applies.
The kindle block's own comment says it checks the class bonus; it does not.

### E. A starting champion's printed On Enter never runs
`PlacePregameStartingChampion` sets `SuppressNextEnter`, so the opening hand is always seven cards. Fragmented / Fortuitous / Serene Spirit of Fire
(Glimpse 6/7, draw six, summon a Spirit Shard, draw into memory) and any other starting champion with a different On Enter behave like a plain draw-seven.
The six Fire champion fixtures seed the champion onto the field to exercise the ability.

### F. Spellshroud also blocks your own spells
`FilterSpellshroudTargets` hides a Spellshroud object from its controller's spells. Every Shenju has Spellshroud, so Suzaku's Command's Shenju clause
(+2 more POWER, unpreventable) is unreachable. Is that the intended rule?

## 3. Open: card text not implemented
- Eight of Hearts: alternative payment (sacrifice up to two Suited allies for 3 each).
- Abnegation: conditional Ephemerate (no "activation negated this turn" tracker).
- Steady Sharpshooter (aim-counter Ranged), Calculated Foresight (champion Ranged 3), Reconnaissance Field (REST ability), Ritai Stablemaster ("Horse cards you activate have Kindle 3").
- Molten Impact: sacrifices at resolution rather than as a cost.
- Generated listeners for `AllyDestroyed` (Turbulent Bounty Hunter), `ActivateAbility` (Trained Birdroid) and `CardBanish` (Evaporation Synchron) are never dispatched.
- Spirit of Fortuitous / Serene / Fragmented Fire on a real game start (see E).

## 4. Fire cards still without a fixture (15)
Need a bonus champion or a harder setup: Feu Awakening (Ciel), Conflagrative Trounce (Ciel), Molten Echo and Flickering Afterglow (Merlin + Fractured Memories),
Bill and Mantle of the Abyss (Alice), Sigil of Budding Embers (Diao Chan), Zinn (Arisanna), Crimson Tear (level 1/2), Cauterizing Seraphim (Advanced Imbue),
Greater Boon of Vritra (pantheon), Bombard Flarecannon and Polkhawk, Bombastic Shot (Polkhawk), Contraband Revolver (loaded Gun), Lacuna's Grasp (Ciel omens).

## Tools
`audit-ga-keyword-registries.php` (keyword lists), `mutation-spot-check.php` (do assertions fail when the engine is broken), `lint-fixture-coverage.php`,
`audit-ga-semantic-coverage.php`, `build-ga-semantic-backlog.php`. The pattern audits above are plain greps; they are worth turning into a script
before Phase 5 guard rails.
