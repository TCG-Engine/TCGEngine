# GrandArchiveSim engine findings (as of the end of Phase 3a Fire)

Everything here was found by writing a fixture that plays a card the way a player would and asserting the printed text.
Section 1 is fixed and covered by a fixture. Sections 2 and 3 are **open**; section 2 are behaviour-wide changes that need a decision
before they are made (A is parked for its own Exalted batch), section 3 are cards whose text is not implemented at all.

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

### Found while covering Water
| Item | Change |
|---|---|
| Hidden-card choices (Extricating Touch, Death Essence Amulet, Pristine Scourge, Seep into the Mind, ...) | `StageHiddenMZChoicesToTemp` stored raw mzIDs, but the staging handler and the consuming handler run for different players, so Extricating Touch discarded the caster's own card. The map now records the staging perspective and `ResolveTempChoiceToSourceMZ` returns the mzID in the consumer's perspective. |
| On Enter conditions (`$enterPrereqs`) | Nine generated On Enter "If ..." guards were never consulted (Hydrocask Droid got +2 POWER with no floating-memory card). `FireEnterTriggeredAbility` now refuses on an explicit false. |
| Repeated `MZMove($player, "myDeck-0", ...)` | MZMove only flags a slot removed, so every pass of a loop after the first moved nothing (Breakwater Cadet milled 1 of 2; about 20 generated abilities: mill N, look at the top N, banish 4, draw to memory). New opt-in generator module `SkipRemovedMZMoves=myDeck,theirDeck` (zzGameCodeGenerator.php + GrandArchiveSim schema): a flagged deck slot is skipped to the next live one. Other schemas generate byte-identical code. Needs `php zzGameCodeGenerator.php rootName=GrandArchiveSim` after deploy. |

| Hailfinch | [Class Bonus] On Hit asked its YESNO and then read `CombatTarget` in the answer handler, but the combat variables are only restored while the trigger itself resolves. The buff counter was removed and the hit object was never rested. Override freezes Hailfinch and the hit object by unique id (`HailfinchHitChoice`); no other generated `:OnHit-N` answer handler reads the combat variables. |
| Innervate Knowledge | Same gap as Innervate Fury: "As an additional cost, delevel your champion and recover 5" was never paid (and a champion with no lineage could still cast it). Added to the shared cost block and prereq. |
| Surging Obstruction | Had no implementation at all (it resolved with no effect). Now negates unless the controller pays (1), or (3) when the target has an odd reserve cost; `GASurgingObstructionDiscount()` gives the Chessman Bishop discount (the card has no row in the generated cost-modifier tables). |
| Song of Frost | The [Class Bonus] alternate cost ("banish a floating memory card rather than pay") had its handlers but nothing ever queued the offer. Queued in the 1.3 cost block and listed in `ActivationHasAlternativeReserveCost`. |
| Aenean Cryosalvo | The [Class Bonus][Level 5+] "Fractal sources deal +1" was applied only inside the Spell-source damage block; Fractals are phantasias, so the bonus never applied. Now applied to any Fractal source (two sites in CombatLogic). |
| Wavekeeper's Bond, Hydrating Fractal | "Whenever your champion levels up, recover N" healed the flagged-removed old champion (first CHAMPION in the field array) instead of the new one, so it never healed. `RecoverChampion` skips removed objects. |
| Martial Flowstate | Its [Class Bonus] level-up trigger sacrificed by raw field index while the removed old champion still occupied slot 0; the sacrifice compacted the field and hit the NEW champion. It now purges first and finds the Flowstate by unique id. |
| Ravishing Finale, Intervention | The additional cost "banish two cards with floating memory from your graveyard" and the [Class Bonus] "rest your champion to pay for 2 of the cost" had their handlers (`RavishingFinaleBanish1/2`, `InterventionRestCost`) but nothing queued them, so both cards ignored the cost. Ravishing Finale now goes through `GenericGraveyardBanishCostSpec` (new `floating` spec); Intervention's offer is queued in the 1.3 cost block. |
| Frigid Bash | The [Class Bonus] On Hit ("doesn't wake up ... unless that player pays (2)") was never implemented; only the cost reduction existed. Override queues the controller's pay-or-skip choice; fixtures for paying and declining. |

### Decided and fixed after review
| Item | Change |
|---|---|
| "Activate this ability only at slow speed" (12 abilities on 14 cards) | Enforced centrally: `GAIsSlowSpeed()` (your own main phase, empty stack, no combat) wraps the prereq of each; `GAFastWindowAbilityContext` marks the window listing and window activations in `OpportunityLogic.php`. Fixtures: Gloamspire and Ninja Tabi (refused in a window, works in the main phase). The two boons (Lesser Boon of Nourishment, Greater Boon of Inari) have no ability implementation at all yet. |
| `[Class Bonus] Kindle` / `[Merlin Bonus] Kindle` | `KindleBonusApplies()` gates Glowering Conflagration, Burning Aethercharge, Silent Firebrand (class bonus) and Molten Echo (Merlin in the lineage). Fixture: Silent Firebrand with and without the bonus. |

### Decided: leave as is
| Item | Decision |
|---|---|
| Spellshroud also stops your own spells | Keep. Suzaku's Command's Shenju clause stays unreachable (every Shenju has Spellshroud). |

### Open: orphaned alternate-cost handlers
`$hasDominatingStrikeAltCost` is declared but never set, and `DominatingStrikeAltCost` is never queued: Dominating Strike's [Vanitas Bonus] "reveal three wind cards from memory rather than pay" is unreachable (Wind batch). Cards whose handlers exist but were never queued have now been fixed for Song of Frost, Ravishing Finale and Intervention.

### Open: target-dependent cost reductions
- Slipstream Vault is in the flat Class Bonus discount table (`GameLogic.php`, "costs 1 less (if targets unique ally)"), so it is discounted even when it targets a non-unique ally. Coronation Ceremony shows the right shape (CalculateActivationReserveCost + `CoronationCeremonyChoice`); Astral Seal ("targets an activation of a card with the same name as a banished card") and Spectral Haunting ("targets a Specter") need the same look when their elements are covered. The Slipstream Vault fixture uses a unique ally so it does not depend on the bug.
- Genbu's Command: the "If that ally is a Shenju, recover 6" clause is unreachable (every Shenju has Spellshroud and the action is a Spell), same as Suzaku's Command. No fixture for the clause.

### Decided: do as its own batch later
| Item | Decision |
|---|---|
| Exalted dual-element cards fail exact-match element checks (section 2A) | Will be an "Exalted" batch with its own fixtures, not folded into the element batches. |

### Harness: scenario fixtures can no longer be silently truncated
The generator stopped at the first action the engine refused and saved only the actions before it, so a wrong step order left a fixture whose later assertions passed vacuously (found in Burst Asunder, Conjure Downpour, Slipstream Vault, Scars of Old, Rescue the Heir, Seeker's Aetherwing, Misteye Archer). `GaScenario::build()` now sets `strictActions`; the generator throws on a refused scenario action unless it was declared with `rejects()`. `php DevTools/create-prd-ability-fixtures.php --audit-actions` lists saved fixtures whose `actions.json` is shorter than their definition (0 now). Note: a failed strict regeneration wipes the fixture directory, restore it with `git checkout` if needed.
New assertion type `global_effect_present` (`expectGlobalEffect(player, effect, label, count, step)`) for per-player until-end-of-turn effects that leave no per-object trace (Shroud in Mist, Freezing Steel, Sudden Snow, Conjure Downpour, Unmoored Call ...). An `expect*` without a step is evaluated after the LAST action: pin early checks with the step argument.

### Confirmed intended: player 2 skips recollection on their first turn
`RecollectionPhase()` returns when `GetTurnNumber() === 1`, which skips the recollection phase for both players' first turns (memory is not returned to hand, recollection triggers do not fire). Confirmed as the rule by the owner (2026-10-12): player 2 does not recollect on their first turn. No change needed; fixtures that reach player 2's first turn must not expect recollection effects.

## 2. Open: behaviour-wide, needs a decision

### A. Exalted cards are not "fire/water/wind/norm element" to exact-match checks
80 cards have a two-element value (`EXALTED,FIRE` 14, `EXALTED,WIND` 17, `EXALTED,WATER` 15, `EXALTED,NORM` 34). `ZoneSearch(cardElements:)` handles that
correctly, but about 64 hand-written and generated sites compare `CardElement($id) === "FIRE"` (FIRE 33, WIND 16, WATER 12, NORM 3), so those 80 cards fall
outside Kindle payments, "fire element card" discards and banishes, fire-source unpreventable damage, fire-ally death triggers and the like.
Affected fire cards include Blaze Alight, Suffocating Ash, Tyrannical Denigration, Lavastorm, Alizarin Longbowman and Queen's Cinderhog.
Proposed fix: one `CardHasElement($id, $el)` helper (`PropertyContains`) and replace the comparisons that mean "is an X element card".
Risk: changes the outcome of any game that plays those 80 cards; each site needs a look because a few `=== "NORM"` tests are deliberate.

### B. Raw `GetVariable("CombatAttacker")` readers
The variable is stored from the attacker's perspective (`myField-N`). 75 places read it directly (GameLogic 26, CombatLogic 22, generated 24, other 3).
Reads by the attacking player are right; reads by the defending player (reactions, "target attacking ally", defender-side triggers) resolve against the
wrong zone. Potion Infusion: Blaze was one; `GetCombatAttackerMZ()` is the viewer-relative accessor. Needs an audit of the defender-side sites only.

### C. A starting champion's printed On Enter never runs
`PlacePregameStartingChampion` sets `SuppressNextEnter`, so the opening hand is always seven cards. Fragmented / Fortuitous / Serene Spirit of Fire
(Glimpse 6/7, draw six, summon a Spirit Shard, draw into memory) and any other starting champion with a different On Enter behave like a plain draw-seven.
The six Fire champion fixtures seed the champion onto the field to exercise the ability.

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

## 5. Wind (Phase 3c): fixed, and still open
Fixed (each has a fixture): Byakko's Command (searched only `myField`), Primeval Ritual (sacrifice cost never paid; now in `GenericSacrificeCostCandidates`), Ensnaring Fumes
(remove-three-preparation-counters alternate cost never offered), Ravaging Tempest (opposing allies never banished: MZMove frame), Purging Tempest / Sasha (a *resolved action* entered
the graveyard; only discard and mill were redirected), Bagua of Cardinal Fate (West drew before the glimpse prompt; East woke and pumped every ally instead of the defending ones).
Ranger-unit searches (Squallbind Pounce, Sleety Retreat, Tinderflare Pivot) and Exploit Vulnerability's Assassin search used `cardSubtypes`, which a CHAMPION does not carry its class in: a Ranger/Assassin champion was never offered. They now use `cardClasses` (card-local overrides; `ZoneSearch` itself is unchanged). Sleety Retreat and Tinderflare Pivot have no Ranger-champion fixture yet.
Open: additional-cost cards the engine never mentions (Molten Impact, Powerforged Burst, Cremation Ritual, Umbilical Ritual, Penumbral Waltz, Sparkling Adornment, Horticounter),
alternate-cost cards without wiring (Combustible Potion, Glacial Binding), Veiled Dash (parked), Calming Breeze's `[Level 1+]` Floating Memory twin, Diablerie's Vanitas floating memory.
Purging Tempest's own card is already in the graveyard when its effect begins (the engine moves the action before running its ability); attack cards still go to the graveyard under the
effect because only the action path was changed.

## Tools
`audit-ga-keyword-registries.php` (keyword lists), `mutation-spot-check.php` (do assertions fail when the engine is broken), `lint-fixture-coverage.php`,
`audit-ga-semantic-coverage.php`, `build-ga-semantic-backlog.php`. The pattern audits above are plain greps; they are worth turning into a script
before Phase 5 guard rails.
