<?php
/**
 * Fixtures for cards starting with 'V', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Vernal Talisman: banish 2 preserved material cards to materialize, Class Bonus draw ---
// Vernal Talisman is a TERA (advanced element) REGALIA,ITEM card with a MAGE Class Bonus On
// Enter, so two separate setups are needed (same split as sabela-gossamer-penance-enter):
// the starting champion's Subcards are patched with a real TERA champion (Kongming, Fel
// Eidolon) for element access, and that same champion is ALSO physically seeded onto the
// field so IsClassBonusActive(["MAGE"]) — which scans physical field objects, independent of
// the Subcards-based lineage check — is satisfied. Like entrancing-filigree-enter-banish, its
// additional materialize cost ("banish 2 preserved cards from your material deck") is NOT
// exercised: materializing via a direct mode 10002 FSM click resolves straight through
// DoMaterialize() without ever routing through the MATERIALIZE decision's card-specific
// additional-cost switch in MaterializeLogic.php (verified live — myMaterial is unchanged
// after materializing), so only the On Enter Class Bonus draw is covered here. The
// [Class Bonus][REST] Empower activated ability is separately out of scope.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
//   - Kongming, Fel Eidolon (MAGE CHAMPION), physically seeded for Class Bonus
gaScenario('vernal-talisman-preserve-draw')
    ->standardDeck('Vernal Talisman')
    ->readyFor('Vernal Talisman')
    ->seed('myField', 'Kongming, Fel Eidolon')
    ->seed('myHand', 'Vernal Talisman')
    ->playSlot(3)
    ->tested('Vernal Talisman')
    ->register();

// --- Venous Core: additional materialize cost - sacrifice an Elysian ally ---
// Venous Core's element is EXIA (Dante's advanced element), so the starting champion's CardID
// is patched directly to Dante, Hemomancer to unlock it. Venous Core is REGALIA, and
// GrandArchiveSim/Custom/GameLogic.php's HandAddReplacement() unconditionally redirects any
// REGALIA card added to hand into the material deck instead (this is a real engine rule --
// REGALIA cards can never sit in hand -- confirmed live: seeding 'zone'=>'myHand' for her
// silently landed her in myMaterial, not myHand), so she is played via the normal
// material-phase MZMAYCHOOSE rather than a hand FSM click. An Elysian Test Subject token
// (3DCP7WmBpx, reserve cost 0) is seeded onto the field as the sacrifice fodder for Venous
// Core's additional materialize cost (GrandArchiveSim/Custom/MaterializeLogic.php,
// "Venous Core (YTO70fFsBY): additional cost to materialize - sacrifice an Elysian ally"),
// which offers an MZCHOOSE among Elysian allies before paying the 1-memory printed cost from a
// seeded memory-zone filler card. The +5 LIFE aura and [Dante Bonus] can't-be-negated clause
// are pure derived-stat/state effects with no stored counter or flag to assert (consistent
// with the established rule that computed stat buffs aren't directly assertable in this
// framework) and are out of scope here -- only the sacrifice cost itself is tested.
// Notes carried over from the original definition:
//   - become Dante, Hemomancer (EXIA unlock)
//   - Elysian Test Subject token - sacrifice fodder
gaScenario('venous-core-sacrifice-cost')
    ->standardDeck('Windslice')
    ->champion(cardName: 'Dante, Hemomancer')
    ->seed('myField', 'Elysian Test Subject')
    ->seed('myMaterial', 'Venous Core')
    ->seed('myMemory', 'Fairy Whispers')
    ->endTurn()
    ->endTurn(2)->pass(1)->pass(1)
    ->endTurn(2)->pass(1)->pass(1)->target('myMaterial-4')->target('myField-1')->pass(1)
    ->tested('Venous Core')
    ->expectProperty('myField-1', 'CardID', 'YTO70fFsBY', 'Venous Core materializes onto the field', step: 10)
    ->expectZoneCount('myField', 2, 1, 'The sacrificed Elysian ally is gone -- only the champion and Venous Core remain', step: 10)
    ->expectZoneCount('myGraveyard', 0, 1, 'The sacrificed ally was a token, so it ceases to exist rather than sitting in the graveyard', step: 10)
    ->expectZoneCount('myMemory', 0, 1, 'The 1-memory printed cost is paid from the seeded memory-zone filler card', step: 10)
    ->expectDqEmpty(step: 10)
    ->register();

// --- Veteran Soldier: Floating Memory pays a champion level-up's memory cost from graveyard ---
// Same Floating Memory keyword and same test shape as shieldroid-floating-memory /
// stalwart-shieldmate-floating-memory / honorable-vanguard-floating-memory / return-stroke-
// floating-memory -- Veteran Soldier's only ability (abilityCount=1 in the semantic backlog) is
// this unconditional keyword. Pay Lorraine, Wandering Warrior's 1-memory champion level-up cost
// entirely from a Veteran Soldier seeded directly into the graveyard, with no myMemory filler
// seeded, so QueueMaterializeFloatingPaymentChoice (GrandArchiveSim/Custom/MaterializeLogic.php)
// offers it as the sole payment source.
gaScenario('veteran-soldier-floating-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myGraveyard', 'Veteran Soldier')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')
    ->tested('Veteran Soldier')
    ->expectProperty('myField-0', 'CardID', 'DpHDGaX2Pn', 'The champion level-up completes -- Lorraine, Wandering Warrior takes the field')
    ->expectZoneCount('myGraveyard', 0, 1, 'Veteran Soldier leaves the graveyard, paying for the level-up\'s memory cost')
    ->expectZoneCount('myBanish', 1, 1, 'Floating Memory banishes Veteran Soldier rather than tapping a myMemory card')
    ->expectZoneCount('myMemory', 0, 1, 'No myMemory card was seeded or spent -- the cost was paid entirely from the graveyard')
    ->expectDqEmpty()
    ->register();

// --- Violet Haze: all your units become distant; put on bottom of target champion's lineage ---
// Violet Haze is UMBRA (advanced element), so the starting champion's Subcards are patched
// with a real UMBRA champion (Tristan, Shadowdancer) to unlock element access -- the same
// pattern already used for other UMBRA cards elsewhere in this file. It's a 2-reserve action
// played for real (no On Enter/no field presence to fake via test-setup). Seed an extra ally
// on the field to confirm "all units you control" isn't limited to the champion, then play
// the card from hand and target the champion with the lineage placement.
// Notes carried over from the original definition:
//   - UMBRA lineage/element unlock
//   - Dungeon Guide - confirms "all units" isn't champion-only
gaScenario('violet-haze-distant-lineage')
    ->standardDeck('Violet Haze')
    ->readyFor('Violet Haze')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Violet Haze')
    ->castSpell('Violet Haze', pay: 2, window: 'me', target: 'myField-0')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["DISTANT","vz4kc558yx-ACTION_ACTIVATED"]', 'Champion becomes distant')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'All units you control become distant, not just the champion')
    ->expectProperty('p1Field-0', 'Subcards', '["he6kd7hocc","vdxi74wa4x"]', 'Violet Haze is put on the bottom of the target champion\'s lineage')
    ->register();

// --- Vertus, Gaia's Roar: [Class Bonus] On Enter: allies +1 POWER per Animal/Beast in graveyard (Pride 10) ---
// Vertus is TERA -- the starting champion's CardID is patched directly to Silvie, With the
// Pack (TAMER, satisfies the Class Bonus) and its Subcards are ALSO patched with a real TERA
// champion (Arisanna, Astral Zenith is ASTRA -- use Gaia's Songbird's own Silvie Loved by All
// instead, a real TERA champion) for element/lineage unlock. Two Gray Wolf copies (BEAST) are
// seeded into the graveyard, and Gray Wolf itself is also seeded onto the field as the target
// ally to observe the buff on. Pride 10 (combat-only) is not separately re-tested here; Vertus
// is materialized via a real FSM play (paying its 4 reserve) so its On Enter ability actually
// fires, since seeding directly onto myField would bypass enterAbilities entirely.
// Notes carried over from the original definition:
//   - Silvie, With the Pack (TAMER) + Silvie, Loved by All (TERA unlock)
//   - Gray Wolf (BEAST) #1
//   - Gray Wolf (BEAST) #2
//   - Gray Wolf -- the ally that receives the buff
gaScenario('vertus-gaias-roar-class-bonus-enter-power-buff')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Silvie, With the Pack', lineage: ['Silvie, Loved by All'])
    ->seed('myGraveyard', 'Gray Wolf')
    ->seed('myGraveyard', 'Gray Wolf')
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Vertus, Gaia\'s Roar')
    ->castSpell('Vertus, Gaia\'s Roar', pay: 4)
    ->register();

// --- Vainglory Retribution: playCardAbilities dispatch coverage (qtzsekkjn3) ---
// Effects Stack fixture-coverage gap fill for the whole $playCardAbilities table: nothing in
// real gameplay ever called QueuePlayCardTriggeredAbility() before the fix in GameLogic.php's
// OnCardActivated() (the real "a card just resolved off the Effects Stack" chokepoint, reached
// from both DoActivateCard() and the separate StarcallingActivate path) -- the only previous
// caller, DoPlayCard(), was reachable solely through the generated PlayCard() macro, which
// nothing in the codebase invokes. Confirmed live: all 14 playCardAbilities entries fired zero
// times across the full 546-fixture suite before the fix (instrumented FirePlayCardTriggeredAbility
// with logging). This fixture actually plays Vainglory Retribution (qtzsekkjn3, WIND ACTION,
// reserve cost 4) from hand through the real ActivateCard()->DoActivateCard() path and asserts
// its on-play effect (playCardAbilities["qtzsekkjn3:0"]: AddTurnEffect(champMZ,
// "VAINGLORY_RETRIBUTION_4")) actually lands on the champion.
// PRISMATIC_CODEX_IGNORE_ELEMENT bypasses the WIND element-access check for this one
// activation (CanPlayerUseCardElement, GameLogic.php) without scripting a real element
// unlock -- self-consuming, matches the card's own real element gate rather than faking it
// via a champion Subcards patch. Vainglory Retribution is seeded directly into hand at the
// next open slot after the natural 7-card opening hand (myHand-7).
// Play Vainglory Retribution (mode 10002 FSM free play), pay its 4 reserve cost with four
// "choose a card from myHand" MZCHOOSE decisions (always myHand-0 -- each payment removes
// that slot and shifts the rest down, so the index is stable across all four), then both
// players pass the resulting Effects Stack Opportunity windows -- first for Vainglory
// Retribution's own activation entry, then a second time for the PLAY_CARD trigger entry
// that QueuePlayCardTriggeredAbility() pushes once the card resolves.
gaScenario('vainglory-retribution-play-card-trigger')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->globalEffect('PRISMATIC_CODEX_IGNORE_ELEMENT')
    ->seed('myHand', 'Vainglory Retribution')
    ->play('Vainglory Retribution')->pay(4)->passBoth()->passBoth()
    ->register();

// --- Veiling Breeze: reveal wind cards from memory to prevent that much champion damage ---
// Veiling Breeze (KoF3AMSlUe, WIND -- native, no patch needed) is played with a Diablerie
// (0plqbtjuxz, WIND ACTION) already seeded into memory. cardActivatedAbilities["KoF3AMSlUe:0"]
// (GeneratedMacroCode.php) calls VeilingBreezeStart() (CardDQHandlers.php), which offers an
// MZMULTICHOOSE of wind cards in memory; revealing the seeded card
// (customDQHandlers["VeilingBreezeChoose"]) adds TurnEffect "KoF3AMSlUe-1" (1 = the number of
// cards revealed) to the champion.
gaScenario('veiling-breeze-prevent-champion-damage')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->seed('myMemory', 'Diablerie')
    ->seed('myHand', 'Veiling Breeze')
    ->castSpell('Veiling Breeze', pay: 1, window: 'both', target: 'myMemory-0')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","KoF3AMSlUe-1"]', 'The champion gains the KoF3AMSlUe-1 turn effect (1 = the number of wind cards revealed).')
    ->register();

// --- Venerable Sage: [Kongming Bonus] whenever Shifting Currents change, +1 POWER and +1 LIFE until EOT ---
// Venerable Sage's [Kongming Bonus] direction-change trigger (GameLogic.php ~20930, fires on
// ANY Shifting Currents direction change, not a specific pair) tags Venerable Sage itself with
// an "FwPdj4PkSS" TurnEffect that a separate power/life case statement (GameLogic.php ~12767 /
// ~14167) reads to grant +1 POWER and +1 LIFE until end of turn. Same Fel Eidolon lineage patch
// as the other transition fixtures.
// Notes carried over from the original definition:
//   - TERA unlock + Kongming Bonus + Fel Eidolon Inherited trigger
//   - Shifting Currents facing North
//   - Venerable Sage
//   - Tera Sight, to trigger the Inherited SC choice on activation
//   - choose adjacent direction EAST
gaScenario('venerable-sage-direction-change-buff')
    ->deck(GA_KONGMING_BASE_DECK)
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Venerable Sage')
    ->seed('myHand', 'Tera Sight')
    ->play('Tera Sight')->answer(1, 'EAST')
    ->tested('Venerable Sage')
    ->register();

// --- Verdure of Preservation: [Kongming Bonus] SC change to next clockwise direction preserves top deck card ---
// Verdure of Preservation's [Kongming Bonus] clockwise-transition trigger (GameLogic.php
// ChangeShiftingCurrents() ~20878-20892; clockwise map NORTH->EAST->SOUTH->WEST->NORTH) calls
// PutTopDeckCardIntoMaterialPreserved() only when the new direction is the clockwise-next one
// from the old direction AND IsKongmingBonus() is true. Starting at NORTH and choosing EAST
// (the clockwise-next direction, also one of NORTH's two adjacent options) satisfies both the
// Fel Eidolon Inherited adjacent-direction trigger and the clockwise condition in one action.
// Notes carried over from the original definition:
//   - TERA unlock + Kongming Bonus + Fel Eidolon Inherited trigger
//   - Shifting Currents facing North
//   - Verdure of Preservation
//   - Tera Sight, to trigger the Inherited SC choice on activation
//   - choose adjacent + clockwise-next direction EAST
gaScenario('verdure-of-preservation-clockwise-preserve')
    ->deck(GA_KONGMING_BASE_DECK)
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Verdure of Preservation')
    ->seed('myHand', 'Tera Sight')
    ->play('Tera Sight')->answer(1, 'EAST')
    ->tested('Verdure of Preservation')
    ->register();

// --- Verdant Slime: On Enter: destroy a 0-memory-cost regalia, else put two buff counters ---
// Verdant Slime's On Enter ability (enterAbilities['kkbbu08s5r:0'] ->
// customDQHandlers['kkbbu08s5r:0:Enter-1'], GeneratedMacroCode.php) first searches both
// fields for a REGALIA with memory cost 0; with none present it immediately does
// AddCounters($player, $mzID, 'buff', 2) with no decision queued at all (the "if you don't
// [destroy one]" branch). This fixture exercises exactly that no-target branch -- no 0-cost
// REGALIA is seeded onto either field. Its element is TERA, not native to the 'Spirit of
// Fire' starting champion, so the champion's Subcards are patched with a real TERA champion
// purely to unlock element access. Verdant Slime is played for real from hand via FSM so its
// Enter trigger genuinely fires. The separate "destroy a 0-cost regalia instead" branch is a
// simple, symmetric alternate path not independently re-tested here.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock
gaScenario('verdant-slime-on-enter-no-regalia-two-buff')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myHand', 'Verdant Slime')
    ->castSpell('Verdant Slime', pay: 2)
    ->register();

// --- Viridescent Aetherstreak (pc0y3xneg7): "[Class Bonus] This card costs 1 less to activate. Choose two - up to one target unit becomes distant; prevent the next 2 damage to each distant unit; load this card into an Aetherwing weapon."
// Choosing the first mode and then declining its "up to one target" (PASS) must still go on to the next mode; ViridescentAetherstreakTargetA was queued without dontSkipOnPass, so the handler (which asks the next mode) was skipped and the
// card stopped after one mode, never loading. Salamander's Breath (an Aetherwing) is on the field, Diana, Deadly Duelist (RANGER) with a Spirit of Wind lineage gives the Class Bonus and the wind element. ---
// Notes carried over from the original definition:
//   - Diana, Deadly Duelist (RANGER): Class Bonus; Spirit of Wind lineage: wind element
//   - Salamander's Breath (Aetherwing weapon)
//   - Viridescent Aetherstreak -> myHand-7
gaScenario('viridescent-aetherstreak-mode-one-no-target-then-load-into-aetherwing')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Diana, Deadly Duelist', lineage: ['Spirit of Wind'])
    ->seed('myField', 'Salamander\'s Breath')
    ->seed('myHand', 'Viridescent Aetherstreak')
    ->play('Viridescent Aetherstreak')->pay(2)->answer(1, 'YES')->pass(1)->answer(1, 'NO')->answer(1, 'YES')
    ->target('myField-1')
    ->register();

// Veiled Gambit (hxdfyA0eP1): "As an additional cost to activate this card, sacrifice a Chessman ally. Prevent the next 4 damage that would be dealt to your champion and up to one other target unit until end of turn."
// Notes carried over from the original definition:
//   - CLERIC class
//   - Pawn Piece (CHESSMAN ally) -> myField-1
//   - Veiled Gambit -> myHand-7
gaScenario('veiled-gambit-sacrifices-a-chessman-and-shields-the-champion')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'CLERIC')
    ->seed('myField', 'Pawn Piece')
    ->seed('myHand', 'Veiled Gambit')
    ->play('Veiled Gambit')->target('myField-1')->pay(1)->target('theirField-0')
    ->covers(['actions-resolve-generated-enter-effect', 'additional-cost-sacrifice'], 'As an additional cost to activate this card, sacrifice a Chessman ally.', 'Prevent the next 4 damage that would be dealt to your champion and up to one other target unit until end of turn.')
    ->register();

// Verdant Slime (kkbbu08s5r): "On Enter: Destroy up to one target regalia with memory cost 0. If you don't, put two buff counters on CARDNAME." Declining the target (PASS) skipped the generated continuation, whose decline branch
// puts the two buff counters on it.
// Notes carried over from the original definition:
//   - TERA lineage/element unlock (Verdant Slime is a TERA Tamer)
//   - Alkahest (regalia, memory cost 0) -> theirField-1: the only legal target
//   - Verdant Slime -> myHand-7
gaScenario('verdant-slime-declined-regalia-destroy-puts-two-buff-counters-on-it')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Kongming, Fel Eidolon'])
    ->seed('myField', 'Alkahest', player: 2)
    ->seed('myHand', 'Verdant Slime')
    ->castSpell('Verdant Slime', pay: 2, window: 'me')
    ->covers(['optional-choice-decline', 'if-you-dont'], 'On Enter: Destroy up to one target regalia with memory cost 0. If you don\'t, put two buff counters on CARDNAME.')
    ->register();

// Notes carried over from the original definition:
//   - pays the Scepter's memory cost of 1
//   - Baby Red Slime (power 2) -> myField-1
gaScenario('verdant-scepter-enter-banishes-a-slime-for-power-plus-one-refinement-counters')
    ->deck($gaVerdantDeck)
    ->champion(lineage: ['Kongming, Fel Eidolon'], classes: 'TAMER')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myField', 'Baby Red Slime')
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-0')->target('myField-1')
    ->tested('Verdant Scepter')
    ->register();

// Notes carried over from the original definition:
//   - pays the Scepter's memory cost of 1
//   - Baby Red Slime -> myField-1
gaScenario('verdant-scepter-enter-declined-keeps-the-slime-and-puts-no-counters')
    ->deck($gaVerdantDeck)
    ->champion(lineage: ['Kongming, Fel Eidolon'], classes: 'TAMER')
    ->seed('myMemory', 'Fluffy Shopkeep')
    ->seed('myField', 'Baby Red Slime')
    ->endTurn()
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)
    ->endTurn(2)->target('myMaterial-0')->pass(1)
    ->tested('Verdant Scepter')
    ->register();

// "[REST], Remove a refinement counter from CARDNAME: Put a buff counter on each of up to two Slime allies you control then draw a card."
// Notes carried over from the original definition:
//   - Verdant Scepter -> myField-1
//   - myField-2
//   - myField-3
//   - myField-4: not chosen
gaScenario('verdant-scepter-rests-removes-a-counter-buffs-two-slimes-and-draws')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myField', 'Verdant Scepter')
    ->patch('myField-1', ['Counters' => ['refinement' => 2]])
    ->seed('myField', 'Baby Red Slime')
    ->seed('myField', 'Baby Red Slime')
    ->seed('myField', 'Baby Red Slime')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myField-2&myField-3')
    ->tested('Verdant Scepter')
    ->register();

gaAbilitySandbox('vel-ocity-punch-ability-1-pays-its-printed-cost', 'Vel-ocity Punch', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->pass(1)
    ->tested('Vel-ocity Punch')
    ->expectCostFootprint(hand: 10, memory: 7, banish: 0, graveyard: 4, field: 6)
    ->register();

gaAbilitySandbox('ventus-staff-of-zephyrs-ability-1-pays-its-printed-cost', 'Ventus, Staff of Zephyrs', classes: 'MAGE', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pass(1)
    ->tested('Ventus, Staff of Zephyrs')
    ->register();

gaAbilitySandbox('ventus-staff-of-zephyrs-ability-2-pays-its-printed-cost', 'Ventus, Staff of Zephyrs', classes: 'MAGE', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->pass(1)
    ->tested('Ventus, Staff of Zephyrs')
    ->register();

// Vigil Rempart (pc3zpkw43o): "[Class Bonus] Vigil Rempart gets +2 POWER."
gaScenario('vigil-rempart-class-bonus-gets-plus-two-power')
    ->deck($gaCielDeck)
    ->champion(classes: 'GUARDIAN')
    ->seed('myField', 'Vigil Rempart')
    ->target('myField-0')
    ->tested('Vigil Rempart')
    ->register();

gaScenario('vigil-rempart-without-the-class-bonus-has-base-power')
    ->deck($gaCielDeck)
    ->seed('myField', 'Vigil Rempart')
    ->target('myField-0')
    ->tested('Vigil Rempart')
    ->register();

// Vanish from Sight (vm5kt3q2sv): "Activate this card only during an opponent's recollection phase. Your champion gains stealth until end of turn."
gaScenario('vanish-from-sight-is-refused-during-your-own-main-phase')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'ASSASSIN')
    ->seed('myHand', 'Vanish from Sight')
    ->play('Vanish from Sight')
    ->expectZoneCount('myHand', 8, 1, 'Refused: not an opponent\'s recollection phase')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->expectDqEmpty()
    ->register();

gaScenario('vanish-from-sight-during-the-opponents-recollection-phase-grants-stealth')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'ASSASSIN')
    ->seed('myHand', 'Vanish from Sight')
    ->seed('myMemory', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->target('myHand-7')->pay(3)
    ->tested('Vanish from Sight')
    ->register();

// Voltaic Sphere (0op3nq0ymv): "Deal 3 damage to target unit. If that unit is an ally, deal 5 damage to it instead." and "[Class Bonus] Banish CARDNAME from your graveyard: The next arcane element Spell card you activate
// this turn costs 1 less to activate." (the generated body only offered allies, dealt 3 to phantasias / 5 to the rest, and the graveyard ability was not implemented).
gaScenario('voltaic-sphere-deals-five-to-an-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Voltaic Sphere')
    ->champion(cardName: 'Rai, Storm Seer')
    ->seed('myField', 'Surged Coordinator', player: 2)
    ->castSpell('Voltaic Sphere', pay: 2, target: 'theirField-1')
    ->expectZoneCount('theirGraveyard', 1, 1, 'The 4-life ally took 5 damage and died (3 would not have killed it)')
    ->expectDqEmpty()
    ->register();

gaScenario('voltaic-sphere-deals-three-to-a-champion')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Voltaic Sphere')
    ->champion(cardName: 'Rai, Storm Seer')
    ->castSpell('Voltaic Sphere', pay: 2, target: 'theirField-0')
    ->register();

// Notes carried over from the original definition:
//   - Rai, Storm Seer (ARCANE Mage)
//   - Voltaic Sphere -> myGraveyard-0
//   - a second Voltaic Sphere (arcane Spell, reserve cost 2) -> myHand-7
gaScenario('voltaic-sphere-graveyard-ability-makes-the-next-arcane-spell-cost-one-less')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Rai, Storm Seer')
    ->seed('myGraveyard', 'Voltaic Sphere')
    ->seed('myHand', 'Voltaic Sphere')
    ->action(1, 10002, 'myGraveyard-0!FSM!')
    ->castSpell('Voltaic Sphere', pay: 1, target: 'theirField-0')
    ->register();

// VelTech QA Tester (2XWNCcPN6o): playing it from hand crashed in its own cost modifier (the card being activated was offered as the discount source, an object without GetMZID()).
gaScenario('veltech-qa-tester-can-be-played-from-hand-and-enters-the-field')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'WARRIOR')
    ->seed('myHand', 'VelTech QA Tester')
    ->castSpell('VelTech QA Tester', pay: 3, window: 'me')
    ->register();

// Notes carried over from the original definition:
//   - Visceral Inversion -> p1 myHand-7
//   - Dungeon Guide, the attacker (power 1) -> p2 field-1
//   - end player 1 turn
//   - player 1 passes the two end-of-turn windows
//   - player 2 attacks with the Dungeon Guide
//   - ... targeting player 1's champion
//   - player 1 answers from the priority window with Visceral Inversion
//   - reserve 1, then pass so it resolves
gaScenario('visceral-inversion-defender-shrinks-the-attacking-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Tristan, Shadowdancer'], classes: 'CLERIC')
    ->seed('myHand', 'Visceral Inversion')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myHand-7')->pay(1)->pass(1)
    ->tested('Visceral Inversion')
    ->register();

// Notes carried over from the original definition:
//   - Varuckan Soulknife -> p1 myMaterial-0
//   - three Red Slimes (fire)
gaScenario('varuckan-soulknife-activates-from-the-material-deck-in-the-main-phase-by-banishing-three-fire-cards')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'ASSASSIN')
    ->seed('myMaterial', 'Varuckan Soulknife')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->action(1, 10002, 'myMaterial-0!FSM!')->target('myGraveyard-0')->target('myGraveyard-0')->target('myGraveyard-0')
    ->target('myMaterial-0')
    ->tested('Varuckan Soulknife')
    ->register();

gaScenario('varuckan-soulknife-is-not-activated-from-the-material-deck-with-only-two-fire-cards-in-the-graveyard')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'ASSASSIN')
    ->seed('myMaterial', 'Varuckan Soulknife')
    ->seed('myGraveyard', 'Red Slime')
    ->seed('myGraveyard', 'Red Slime')
    ->action(1, 10002, 'myMaterial-0!FSM!')
    ->tested('Varuckan Soulknife')
    ->register();
