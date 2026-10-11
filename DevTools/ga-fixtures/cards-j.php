<?php
/**
 * Fixtures for cards starting with 'J', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Jovian Hilt X Ultra: enters linked to target Sword weapon, buffs it ---
// Notes carried over from the original definition:
//   - ARCANE lineage/element unlock
//   - Clarent, Sword of Peace (Sword weapon) - link target
gaScenario('jovian-hilt-link-buff')
    ->standardDeck('Jovian Hilt X Ultra')
    ->champion(lineage: ['Lorraine, Arclight Saber'])
    ->seed('myField', 'Clarent, Sword of Peace')
    ->seed('myHand', 'Jovian Hilt X Ultra')
    ->playSlot(4)->target('myField-1')
    ->tested('Jovian Hilt X Ultra')
    ->register();

// --- Jin, Fate Defiant: Inherited Effect - when Jin attacks with a Polearm weapon or Polearm
// attack card, target Horse or Human ally gets +1 POWER until end of turn ---
// Real attack-declaration sequence, same shape as ardent-cloudstriker-west-attack-champion-buff,
// but the CHAMPION itself attacks (myField-0), patched directly to Jin, Fate Defiant (a static
// CardID patch suffices here since ChampionHasInLineage only checks lineage, not a real Enter).
// Steel Halberd (WARRIOR/POLEARM) is seeded on the field as the equipped weapon -- champions
// offer a weapon-choice decision before the attack target when a weapon is available
// (CombatLogic.php:1176-1180). Dungeon Guide (HUMAN) is the buff target.
// Notes carried over from the original definition:
//   - Jin, Fate Defiant, awake
//   - Dungeon Guide (HUMAN), buff target
//   - Steel Halberd (WARRIOR/POLEARM), equipped weapon
//   - decline materialize offer
//   - declare attack with Jin
//   - choose Steel Halberd as the weapon
//   - target opponent's champion
//   - choose Dungeon Guide for +1 POWER
gaScenario('jin-fate-defiant-polearm-attack-ally-buff')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['CardID' => 'zd8l14052j', 'Status' => 2])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Steel Halberd')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-2')->target('theirField-0')->target('myField-1')
    ->tested('Jin, Fate Defiant')
    ->register();

// --- Jin, Zealous Maverick: On Enter, this champion's next attack gets +1 POWER and wakes it up ---
// A real level-up event is required to fire a champion's own On Enter (a static CardID patch
// does not fire triggers, per kongming-wayward-maven-enter-shifting-currents). The starting
// champion is patched to Jin, Fate Defiant (level 1) so the real level-up reaches Jin, Zealous
// Maverick (level 2, memory cost 2). On Enter tags TurnEffects with '5ramr16052'
// (GeneratedMacroCode.php:9805-9809); on the champion's next attack, CombatLogic.php:1796-1801
// consumes that flag into '5ramr16052_POWER' (+1 POWER, GameLogic.php:12244) and calls
// WakeupCard -- both halves are exercised end to end in a single real attack declaration.
// Resolving the MAT-phase choice by leveling up (unlike declining) lands directly in MAIN phase
// with an empty decision queue -- no extra pass is needed, confirmed via a throwaway debug
// harness (DevTools/debug_zealous.php, now deleted). That harness also found Zealous Maverick has
// 0 base POWER, so the attack is illegal without a weapon (BeginCombatPhase's power>0 gate is
// checked before the On Attack trigger fires and can't retroactively legalize it) -- Steel
// Halberd is seeded on the field as the equipped weapon, same as jin-fate-defiant-polearm-attack-
// ally-buff, purely to give the attack positive power. The SAME harness also found that a real
// level-up re-adds the champion object at the END of the field array rather than replacing it
// in place -- Steel Halberd (originally myField-1) becomes myField-0 and the leveled champion
// becomes myField-1 after the level-up action.
// Notes carried over from the original definition:
//   - Jin, Fate Defiant (level 1)
//   - pays the 2-memory level-up cost, card 1/2
//   - Steel Halberd (WEAPON), gives the attack positive power
//   - level up to Zealous Maverick
//   - declare attack with Jin (now at index 1)
//   - choose Steel Halberd (now at index 0) as the weapon
//   - target opponent's champion
gaScenario('jin-zealous-maverick-enter-next-attack-power-wake')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Jin, Zealous Maverick
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(cardName: 'Jin, Fate Defiant')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myField', 'Steel Halberd')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-0')
    ->action(1, 10002, 'myField-1!FSM!')->target('myField-0')->target('theirField-0')
    ->tested('Jin, Zealous Maverick')
    ->register();

// --- Jin, Undying Resolve: immortality as long as it's not your end phase ---
// Jin, Undying Resolve's immortality (HasImmortality, GrandArchiveSim/Custom/GameLogic.php
// ~23146-23157: "as long as it's not your end phase, Jin has immortality" for the card itself
// or ChampionHasInLineage matches) is checked inside DoAllyDestroyed
// (GameLogic.php:7282-7296), the general destroy handler every lethal-damage path funnels
// through -- confirmed by tracing OnDealDamage's non-domain path (CombatLogic.php, ends with
// "$targetObj->Damage += $amount; ... AllyDestroyed($player, $target);"). The starting
// champion's CardID is patched directly to Jin, Undying Resolve (life 28), with Damage
// pre-patched to 27 (one below lethal). Nascent Blast (vajycopxgf, NORM element, always
// castable regardless of champion element -- see safeguard-paladin-class-bonus-noncombat-
// prevent's note on this) deals 3 non-combat damage to target unit; targeting the champion
// itself (myField-0, a legal self-target since Nascent Blast's target pool is any unit on
// either field) pushes Damage to 30, past the 28 life threshold. Since this is MAIN phase (not
// the controller's end phase), immortality suppresses the destroy check and the champion stays
// on the field with the damage counters intact (per the card's own reminder text: "won't die
// for having more damage counters than their life stat"). The reciprocal half -- immortality
// ending and the champion actually dying at the controller's own end phase (GameLogic.php
// EndPhase():10069-10082, a separate state-based re-check) -- needs a full phase advance to
// END and is out of scope for this fixture.
// Notes carried over from the original definition:
//   - Nascent Blast
gaScenario('jin-undying-resolve-immortality-survives-lethal')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Jin, Undying Resolve
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK
    )
    ->champion(cardName: 'Jin, Undying Resolve', damage: 27)
    ->seed('myHand', 'Nascent Blast')
    ->play('Nascent Blast')->pay(3)->target('myField-0')
    ->tested('Jin, Undying Resolve')
    ->expectProperty('myField-0', 'CardID', 'c4yrrtv7o1', 'Jin, Undying Resolve stays on the field despite lethal damage')
    ->expectDamage('myField-0', 30, 'Damage (30) exceeds life (28) but immortality prevents destruction')
    ->expectDqEmpty()
    ->register();

// Journey's Beginning: "Draw a card." + [Guo Jia Bonus] put a quest counter on your champion.
// Notes carried over from the original definition:
//   - Patch the starting champion directly into Guo Jia, Chosen Disciple (not testing HER own
//   - On Enter here) so IsGuoJiaBonus() reads true for the [Guo Jia Bonus] clause below.
//   - Play Journey's Beginning (verified live at myHand-1 with this deck/seed) and pay its
//   - 3-reserve cost from hand (moves 3 Dungeon Guide into memory). Then pass the resulting
//   - EffectStack opportunity window for both players so the activated ability actually
//   - resolves (GrandArchiveSim/Custom/OpportunityLogic.php) instead of leaving Journey's
//   - Beginning stuck on the stack.
gaScenario('journeys-beginning-draw-and-quest-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
4 Journey's Beginning
16 Dungeon Guide
DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->playSlot(1)->pay(3)->passBoth()
    ->tested('Journey\'s Beginning')
    ->expectProperty('myField-0', 'Counters', '{"quest":1}', '[Guo Jia Bonus] Journey\'s Beginning puts a quest counter on the Guo Jia champion')
    ->expectZoneCount('myDeck', 12, 1, 'Journey\'s Beginning drew a card, reducing the deck from 13 to 12')
    ->register();

// Judas, Claret Intercessor (AbjQkcN57S): "[Class Bonus] Sacrifice another ally: CARDNAME gains spellshroud until end of turn." The printed cost has no [REST], and the sacrifice is the whole cost.
// Notes carried over from the original definition:
//   - MAGE class: the Class Bonus is active
//   - Judas, Claret Intercessor -> myField-1
//   - Dungeon Guide (the other ally) -> myField-2
gaScenario('judas-sacrifice-ability-sacrifices-another-ally-and-does-not-rest-judas')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(classes: 'MAGE')
    ->seed('myField', 'Judas, Claret Intercessor')
    ->seed('myField', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-2')
    ->tested('Judas, Claret Intercessor')
    ->register();

gaScenario('jinzhuo-scavenges-a-beast-ally-and-puts-power-quest-counters-on-the-champion')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myField', 'Fabled Emerald Fatestone')
    ->seed('myField', 'Jinzhuo, Bands of Virtue')
    ->deckTop(['Capricious Lynx'])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myTempZone-5')
    ->answer(1, 'Top=;Bottom=em6eEh9q8y,em6eEh9q8y,px60u5n1do,em6eEh9q8y,em6eEh9q8y')
    ->tested('Jinzhuo, Bands of Virtue')
    ->register();

gaScenario('jinzhuo-finding-no-beast-ally-returns-itself-to-the-field-rested')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myField', 'Fabled Emerald Fatestone')
    ->seed('myField', 'Jinzhuo, Bands of Virtue')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')
    ->answer(1, 'Top=;Bottom=em6eEh9q8y,em6eEh9q8y,px60u5n1do,em6eEh9q8y,em6eEh9q8y,px60u5n1do')
    ->tested('Jinzhuo, Bands of Virtue')
    ->register();

// Notes carried over from the original definition:
//   - Guo Jia, Chosen Disciple
//   - Jinzhuo -> myField-1, no Fatestone
gaScenario('jinzhuo-cannot-activate-without-fabled-emerald-fatestone')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(cardName: 'Guo Jia, Chosen Disciple')
    ->seed('myField', 'Jinzhuo, Bands of Virtue')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Jinzhuo, Bands of Virtue')
    ->register();

// Juggle Knives (7VxRE6HgZC): "Deal 1 damage to target champion. [Class Bonus] Draw a card." The handler dealt damage from an undefined source ($obj).
// Notes carried over from the original definition:
//   - Assassin champion: the Class Bonus applies
gaScenario('juggle-knives-deals-one-damage-to-champion-and-class-bonus-draws')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Juggle Knives')
    ->champion(classes: 'ASSASSIN')
    ->play('Juggle Knives')->pay(2)->target('theirField-0')
    ->register();
