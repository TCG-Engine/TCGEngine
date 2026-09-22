<?php
/**
 * Batch-create PRD regression fixtures programmatically.
 *
 * Usage: php DevTools/create-prd-ability-fixtures.php [--seed=N] [--dry-run] [--fixture=SLUG]
 *
 * Creates selfplay games from curated decks, replays actions to exercise
 * specific card abilities, and saves regression fixtures.
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$repoRoot = dirname(__DIR__);
$rootName = 'GrandArchiveSim';
$seed = 42;
$dryRun = false;
$onlyFixture = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--seed=')) $seed = intval(substr($arg, 7));
    elseif ($arg === '--dry-run') $dryRun = true;
    elseif (str_starts_with($arg, '--fixture=')) $onlyFixture = substr($arg, 10);
}

require_once $repoRoot . '/Core/EngineActionRunner.php';
define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
require_once $repoRoot . '/DevTools/TestAutomationBridge.php';

// ---------------------------------------------------------------------------
// Fixture definitions: slug => [deck, actions, testedCards]
// ---------------------------------------------------------------------------
$fixtures = [];

// --- Recover: Escharotomy prevents target player from recovering ---
$fixtures['escharotomy-prevents-recover'] = [
    'testedCards' => ['CIU4gT14EE'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Escharotomy
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    // Play Escharotomy (myHand-1 with this deck/seed — verified live, see DevTools notes below)
    // and choose "B: Opponent" on its modal target, which sets a CANT_RECOVER global effect on
    // the opponent (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php,
    // CIU4gT14EE:0:CardActivated-1).
    'actions' => [
        // Free play: play Escharotomy (mode 10002 FSM)
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-1!FSM!', 'chkInput' => [], 'inputText' => ''],
        // Pay reserve cost: this is a "choose a card from myHand" MZCHOOSE, not a myField pick.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Pass fast action opportunities
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Modal target choice: "A: Yourself" / "B: Opponent" — pick B (opponent) so the
        // CANT_RECOVER global effect lands on the opponent, matching the fixture's premise.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'B', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Buff Counters: Tindered Soldier gets buff counter on discard ---
$fixtures['tindered-soldier-discard-buff'] = [
    'testedCards' => ['KEhmWGivJp'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Tindered Soldier
4 Scars of Old
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    // Play Tindered Soldier (myHand-1 with this deck/seed — verified live, see DevTools notes
    // below), then Scars of Old (myHand-0 after Tindered Soldier leaves hand). Reserve costs are
    // "choose a card from myHand" MZCHOOSEs, not myField picks — paying Scars of Old's second
    // reserve point with the only FIRE card left in hand (myHand-0) is what actually discards a
    // FIRE card and fires Tindered Soldier's discard-triggered buff counter
    // (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, discardCardAbilities["KEhmWGivJp:0"]
    // requires CardElement($discardedCardID) === "FIRE"). Verified: final Counters.buff = 1.
    'actions' => [
        // Play Tindered Soldier (2 reserve)
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-1!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-3', 'chkInput' => [], 'inputText' => ''],
        // Pass fast actions
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Play Scars of Old (2 reserve) - the 2nd reserve payment discards the last FIRE card in
        // hand, which is what actually triggers Tindered Soldier's buff counter.
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Pass remaining prompts
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Scars of Old's own draw+discard: discard from the (now NORM-only) remaining hand.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Cascade: FlameTech Manual activates Cascade ---
$fixtures['flametech-manual-cascade-activate'] = [
    'testedCards' => ['WZJxZMBAir'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 FlameTech Manual
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Escharotomy
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    // Cascade's activation prereq (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php,
    // activateAbilityPrereqs["WZJxZMBAir:0"]) requires (a) a MAGE Class Bonus — no level 0
    // starting champion has a class other than SPIRIT, so a MAGE champion is seeded directly
    // onto the field — and (b) a FIRE card already in the graveyard, also seeded directly.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'gPKTJKqvOI'], // Rai, Spellcrafter (MAGE champion) - Class Bonus source
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'CIU4gT14EE'], // Escharotomy (FIRE) - graveyard prereq
    ],
    // Play FlameTech Manual (0 cost regalia; lands on myField-2 behind the two seeded objects),
    // then activate its Cascade ability via mode 10001 CustomInput (field abilities are NOT
    // reachable via a plain mode 10002 FSM click — that only covers materialize/attack).
    'actions' => [
        // Play FlameTech Manual (0 cost, goes to field)
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-3!FSM!', 'chkInput' => [], 'inputText' => ''],
        // Pass fast actions
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Activate Cascade on FlameTech Manual (now at myField-2)
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        // Cascade deals 2 damage to a target champion — hit the opponent's.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        // Pass remaining fast action opportunities
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Static Counter: Fulgurite Coordinator banished from graveyard adds a static
// counter to an arcane object you control ---
//
// NOTE on the fixture name/original premise: Fulgurite Coordinator (7aZwqrfbzO) IS ARCANE
// element and DOES have a real "enters the field with a static counter on itself" ability
// (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, $enterAbilities["7aZwqrfbzO:0"]),
// dispatched from FireEnterTriggeredAbility/QueueEnterTriggeredAbility in
// GrandArchiveSim/Custom/GameLogic.php (~line 7108) as part of the materialize effect-stack
// resolution path. The problem is reachability, not existence: ARCANE is an advanced element
// (GetAdvancedElementNames()) that no level-0 starting champion carries, so
// CanPlayerUseCardElement genuinely blocks materializing this card from hand for real in a
// fresh fixture — independent of the pregame bug. And BridgeAddToZone (the test-setup helper
// used for 'setup' below) adds objects via the raw MZAddZone/AddField/FieldAfterAdd path, which
// does NOT invoke FireEnterTriggeredAbility — so seeding the card directly onto myField would
// not exercise the enter ability either. Given that, this rebuild instead exercises the card's
// other real, implemented ability — the graveyard activation at the "Fulgurite Coordinator:
// Banish self from graveyard to add static counters." comment (~line 1365): banish this card
// from the graveyard (1 reserve) to add a static counter to an arcane object you control. It
// uses BridgeAddToZone test-setup helpers (the same primitive the MCP fixture tooling uses) to
// seed the precondition state — a second arcane ally already on the field (the target) and a
// copy of Fulgurite Coordinator already in the graveyard (the activation source) — then replays
// the real activate-from-graveyard decision sequence (FSM click -> choose target -> pay reserve
// -> pass) and confirms the target's Counters.static becomes 1. (Note: the sibling
// fulgurite-coordinator-static-counter fixture, created separately via MCP tooling, was fixed
// to exercise this same graveyard-activation ability — the two fixtures are intentionally
// similar since it's the only ability on this card reachable by current test tooling.)
$fixtures['fulgurite-enters-static-counter'] = [
    'testedCards' => ['7aZwqrfbzO'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Fulgurite Coordinator
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    // Test-setup preconditions applied via BridgeAddToZone (after pregame startup, before the
    // initial gamestate is captured): an arcane ally already on the field to serve as the
    // static-counter target, and a copy of Fulgurite Coordinator already in the graveyard to
    // serve as the graveyard-activation source.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'blqryebvwj'], // Storm Slime (ARCANE ally) - counter target
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => '7aZwqrfbzO'], // Fulgurite Coordinator - GY activation source
    ],
    'actions' => [
        // Activate Fulgurite Coordinator from the graveyard (banish self, 1 reserve) — myGraveyard-0
        // is the copy seeded by 'setup' above.
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myGraveyard-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        // Choose the static-counter target: myField-1 is the Storm Slime seeded by 'setup' above
        // (myField-0 is the starting champion).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        // Pay the 1-reserve activation cost by reserving a hand card.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Pass fast action opportunities
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Elysian Aura: Elysian Aspirant enters, passive should be active ---
$fixtures['elysian-aspirant-aura-passive'] = [
    'testedCards' => ['HHtlkEeyQR'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Elysian Aspirant
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    // Elysian Aspirant's element is EXIA (an advanced element, GrandArchiveSim/Custom/GameLogic.php
    // GetAdvancedElementNames()), and no level 0 starting champion has an advanced element (only
    // NORM/FIRE/WATER/WIND "Spirit of X" cards exist at level 0) — so it can never legally
    // materialize from hand at game start regardless of the pregame fix. Seed it directly onto
    // the field instead (the same BridgeAddToZone test-setup primitive used for the fulgurite
    // fixtures) so the fixture actually tests what the card does once in play: its passive
    // "Elysian Aura" (GrandArchiveSim/Custom/GameLogic.php, HasElysianAura/
    // PlayerControlsElysianAura — grants +1 damage from Aenean Spell sources) is a pure presence
    // check with no per-turn setup, so having it on the field IS the effect being active.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'HHtlkEeyQR'], // Elysian Aspirant - Elysian Aura source
    ],
    'actions' => [
        // Pass fast action opportunities so the fixture demonstrates a normal turn continuing
        // with the passive active (no separate activation is needed for a static aura).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Scars of Old: Draw + discard + buff counters on damaged allies ---
$fixtures['scars-of-old-draw-discard'] = [
    'testedCards' => ['lD0sK81PZT'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Scars of Old
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
4 Windslice
4 Windslice
4 Windslice
4 Windslice
DECK,
    // Scars of Old's post-discard buff (ScarsOfOldBuffDamagedAllies in
    // GrandArchiveSim/Custom/GameLogic.php) requires (a) a WARRIOR Class Bonus — no level 0
    // starting champion has a class other than SPIRIT — and (b) an already-damaged ally on the
    // field to actually receive the buff counter. Both are seeded directly since neither is
    // reachable through a fresh pregame-only board.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'LahboNoSRx'], // Nameless Champion (WARRIOR) - Class Bonus source
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['Damage' => 2]], // Dungeon Guide, pre-damaged - buff target
    ],
    'actions' => [
        // Play Scars of Old (2 reserve — reserve cost is a "choose a card from myHand"
        // MZCHOOSE, not a myField pick)
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-5!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Pass remaining prompts
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Scars of Old's draw+discard: discard a card, which then triggers the buff-damaged-
        // allies step.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Deflecting Edge: Sword-control activation discount + prevent 3 combat damage ---
$fixtures['deflecting-edge-sword-discount'] = [
    'testedCards' => ['g7uDOmUf2u'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Deflecting Edge
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Deflecting Edge's discount (activationCostModifierAbilities["g7uDOmUf2u:0"] in
    // GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php) requires a Sword weapon on the field
    // to reduce its 1-reserve activation cost to 0 — seed Clarent, Sword of Peace (a real
    // WEAPON,SWORD card) directly onto the field since no level 0 starting champion carries a
    // weapon at game start.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'm31WVJ9F04'], // Clarent, Sword of Peace (WEAPON,SWORD) - discount source
    ],
    // Play Deflecting Edge (myHand-4 with this deck/seed — verified live via
    // DevTools discovery harness). With the Sword discount active, the activation costs 0
    // reserve, so play goes straight from the FSM click to the opponent's fast-action response
    // window (no reserve MZCHOOSE appears) and then to the target choice.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-4!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Target choice: prevent the next 3 combat damage to your own champion (myField-0).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Fortified Mana Shield: Taunt-based class bonus discount + prevent 4 non-combat damage ---
$fixtures['fortified-mana-shield-taunt-discount'] = [
    'testedCards' => ['5lh23qu7d6'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Fortified Mana Shield
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Fortified Mana Shield's Class Bonus discount (activationCostModifierAbilities
    // ["5lh23qu7d6:0"]) requires (a) a GUARDIAN Class Bonus — no level 0 starting champion has a
    // class other than SPIRIT — and (b) a unit with taunt anywhere on the field, to reduce its
    // 2-reserve activation cost to 0. Seed Ciel, Loyal Valet (a real GUARDIAN champion) for the
    // class bonus, and give Dungeon Guide the TAUNT turn effect directly (no printed-Taunt ally
    // is in this filler deck) to satisfy the taunt-unit condition.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'nn48ne8a05'], // Ciel, Loyal Valet (GUARDIAN champion) - Class Bonus source
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['TurnEffects' => ['TAUNT']]], // Dungeon Guide w/ Taunt - discount condition + effect target
    ],
    // Play Fortified Mana Shield (myHand-0 with this deck/seed — verified live via DevTools
    // discovery harness). With the discount active, activation costs 0 reserve: FSM play ->
    // player's own fast-action response window -> opponent's response window -> target choice.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Target choice: prevent the next 4 non-combat damage to the taunt unit (myField-2).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Luxem Sight: Draw a card (LUXEM element access + free activation) ---
$fixtures['luxem-sight-draw'] = [
    'testedCards' => ['uwnHTLG3fL'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Luxem Sight
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Luxem Sight is a LUXEM (advanced element) card, and no level 0 starting champion has an
    // advanced element (CanPlayerMeetCardElementRequirements checks GetChampionLineage(), which
    // walks the on-field champion's Subcards) — so it's illegal to play from hand on a fresh
    // board. Patch the starting champion's Subcards directly to include a real LUXEM champion
    // (Zander, Blinding Steel), which is what a genuine level-up into that lineage would leave
    // behind, without scripting the full level-up sequence. This also grants access to its
    // "[Element Bonus] whenever you reveal this card from your memory, recover 3" reveal trigger,
    // but that trigger is not exercised here — reveals-from-memory are tied to the separate Imbue
    // system and are out of scope for this fixture, which only covers the base "Draw a card"
    // effect.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['UAF6Nr7GUE']]], // Zander, Blinding Steel (LUXEM CHAMPION) - lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'uwnHTLG3fL'], // Luxem Sight, seeded to a known hand slot
    ],
    // Play Luxem Sight (myHand-7 with this deck/seed — verified live via DevTools discovery
    // harness). Reserve cost is 0 and the effect needs no target, so it resolves immediately: no
    // reserve MZCHOOSE and no opponent response window appear.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Sabela, Gossamer Penance: WARRIOR Class Bonus On Enter — recur a banished Sword regalia ---
$fixtures['sabela-gossamer-penance-enter'] = [
    'testedCards' => ['pOJ4uRuyMK'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Sabela, Gossamer Penance
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Sabela is a CRUX (advanced element) UNIQUE ALLY, so — like Luxem Sight above — the starting
    // champion's Subcards must be patched to unlock element access. Lorraine, Crux Knight (a real
    // WARRIOR/CRUX champion) is used for the patch AND seeded directly onto the field, so the
    // same card also satisfies the WARRIOR Class Bonus that Sabela's On Enter ability requires
    // (IsClassBonusActive checks CHAMPION-type objects physically on the field, not lineage).
    // Clarent, Sword of Peace (a real REGALIA,WEAPON with the SWORD subtype and memory cost 1) is
    // seeded into the banishment as the card On Enter recurs onto the field. The "On Leave:
    // sacrifice each regalia with a bond counter" half of the card is not exercised here — it
    // requires removing Sabela from the field, which needs its own removal scaffolding — so this
    // fixture only covers the On Enter half.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'NfbZ0nouSQ'], // Lorraine, Crux Knight (WARRIOR/CRUX champion) - Class Bonus source
        ['player' => 1, 'zone' => 'myBanish', 'cardID' => 'm31WVJ9F04'], // Clarent, Sword of Peace (REGALIA,WEAPON,SWORD, memory cost 1) - On Enter target
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['NfbZ0nouSQ']]], // CRUX lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'pOJ4uRuyMK'], // Sabela, seeded to a known hand slot
    ],
    // Play Sabela (myHand-7 with this deck/seed — verified live via DevTools discovery harness),
    // pay her 3-reserve cost (no discount applies), then choose the banished Clarent for the On
    // Enter ability.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myBanish-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Shizun of the Ash: On Enter — optional discard to draw ---
$fixtures['shizun-of-the-ash-discard-draw'] = [
    'testedCards' => ['pnDUy9jUbo'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Shizun of the Ash
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Shizun's element is FIRE (a basic element, matching the "Spirit of Fire" starting
    // champion), so no lineage patch is needed here. Only the always-available On Enter "you may
    // discard a card, if you do draw a card" half is covered — the [Kongming Bonus] REST ability
    // is gated behind a specific champion identity plus the separate "Shifting Currents" facing
    // mechanic, which is out of scope for this fixture.
    // Play Shizun (myHand-0 with this deck/seed — verified live via DevTools discovery harness),
    // pay her 2-reserve cost, answer YES to the optional discard, then choose a card to discard.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Spirit Blade: Infusion: combat-damage discount + grant power/on-hit-draw to a Sword weapon ---
$fixtures['spirit-blade-infusion-combat-discount'] = [
    'testedCards' => ['CgyJxpEgzk'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Spirit Blade: Infusion
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Spirit Blade: Infusion is a CRUX (advanced element) ACTION card, so — like Luxem Sight and
    // Sabela above — the starting champion's Subcards are patched with a real WARRIOR/CRUX
    // champion (Lorraine, Crux Knight) to unlock element access; the same card's WARRIOR class
    // isn't needed here (this card's discount is combat-based, not class-based), it's reused
    // purely for the CRUX unlock. The discount (activationCostModifierAbilities
    // ["CgyJxpEgzk:0"]) requires GlobalEffectCount($player, "CHAMP_DEALT_COMBAT_DMG") > 0, which
    // is normally set by TrackChampionCombatDamage() when a champion deals real combat damage —
    // set it directly via AddGlobalEffects() rather than scripting a full attack sequence, since
    // this fixture is about the card's own cost/effect logic, not the combat subsystem. Clarent,
    // Sword of Peace is seeded onto the field as the only legal Sword weapon target.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'm31WVJ9F04'], // Clarent, Sword of Peace (WEAPON,SWORD) - effect target
        ['player' => 1, 'globalEffect' => 'CHAMP_DEALT_COMBAT_DMG'], // Discount condition
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['NfbZ0nouSQ']]], // CRUX lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'CgyJxpEgzk'], // Spirit Blade: Infusion, seeded to a known hand slot
    ],
    // Play Spirit Blade: Infusion (myHand-7 with this deck/seed — verified live via DevTools
    // discovery harness). With the discount active, activation costs 0 reserve: FSM play -> the
    // player's own fast-action response window -> target choice (Clarent is the only legal Sword
    // weapon, so no opponent response window appears).
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Stocked Outpost: On Enter — draw a card into memory ---
$fixtures['stocked-outpost-enter-draw-memory'] = [
    'testedCards' => ['AOMXEGeSQk'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Stocked Outpost
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Stocked Outpost is a NORM DOMAIN card (a siegeable permanent that materializes onto the
    // field like any other permanent type — there's no separate domain zone), so no element
    // unlock is needed. Only the always-available On Enter "draw a card into your memory" half is
    // covered — "On Destroy: if it's an opponent's turn, that opponent draws a card into their
    // memory" requires reducing its durability to 0 via siege combat damage, which is out of
    // scope for this fixture.
    // Play Stocked Outpost (myHand-2 with this deck/seed — verified live via DevTools discovery
    // harness), pay its 2-reserve cost (each reserve payment itself moves a hand card into
    // memory), then confirm the On Enter draw adds one more.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Meltdown: Level 2+ activation discount + destroy target domain/item/weapon ---
$fixtures['meltdown-level2-destroy-item'] = [
    'testedCards' => ['ht2tsn0ye3'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Meltdown
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Meltdown's element is FIRE (matching the "Spirit of Fire" starting champion), so no
    // lineage patch is needed. The starting champion's Counters are patched directly with 2
    // "level" counters (ObjectCurrentLevel() = CardLevel + level-counter count) to reach the
    // [Level 2+] discount condition without scripting a real level-up sequence. Clarent, Sword of
    // Peace is seeded onto the opponent's field as the destroy target (a legal WEAPON).
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'm31WVJ9F04'], // Clarent, Sword of Peace (WEAPON) - destroy target
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['level' => 2]]], // Level 2+ discount condition
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ht2tsn0ye3'], // Meltdown, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Luminous Surge: buff target unit's next attack, recover 3 champion damage ---
$fixtures['luminous-surge-buff-recover'] = [
    'testedCards' => ['KOqdA7G6by'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Luminous Surge
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Luminous Surge is a LUXEM (advanced element) ACTION card, so — like Luxem Sight and Sabela
    // above — the starting champion's Subcards are patched with a real LUXEM champion (Zander,
    // Blinding Steel) to unlock element access. The starting champion is also pre-damaged (5) so
    // the unconditional "Recover 3" half of the ability is observable as a Damage decrease, not
    // just a no-op against 0 damage. Only the base always-available effect is covered; the
    // [Class Bonus][Element Bonus] memory-reveal trigger is out of scope (tied to the separate
    // memory-reveal subsystem, already documented as out of scope in luxem-sight-draw).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['UAF6Nr7GUE'], 'Damage' => 5]], // LUXEM lineage/element unlock + pre-existing damage for the recover assertion
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'KOqdA7G6by'], // Luminous Surge, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Corhazi Arsonist: remove a preparation counter for stealth ---
$fixtures['corhazi-arsonist-prepare-stealth'] = [
    'testedCards' => ['0ejcyuvuxn'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Corhazi Arsonist
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Corhazi Arsonist's element is FIRE (matching the starting champion), so no lineage patch is
    // needed. The starting champion is pre-seeded with 1 preparation counter directly (normally
    // only reachable via a separate preparation-counter-granting effect) so the "Prepare"
    // activated ability's cost (remove 1 preparation counter from your champion, resolved by the
    // ActivatedAbilityCost() switch in GameLogic.php) can actually be paid. Only the always-
    // available "gain stealth" half is covered; the onHit "banish instead of die" replacement
    // requires a real combat hit and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['preparation' => 1]]], // Prepare-ability cost fuel
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '0ejcyuvuxn'], // Corhazi Arsonist, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Entrancing Filigree: On Enter banish target non-champion opponent object ---
$fixtures['entrancing-filigree-enter-banish'] = [
    'testedCards' => ['vrf9n24b5a'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Entrancing Filigree
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Entrancing Filigree is a TERA (advanced element) REGALIA,ITEM card, so the starting
    // champion's Subcards are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock
    // element access. Its memory cost (2) is NOT exercised here: materializing via a direct mode
    // 10002 FSM click on the hand card resolves straight through DoMaterialize() without ever
    // routing through the MATERIALIZE decision/QueueMaterializePayment cost flow (verified live —
    // myMemory contents are unchanged after materializing), so this fixture covers only the
    // targeting/zone-movement/On-Enter half, not the memory-cost payment mechanic. Dungeon Guide
    // is seeded onto the opponent's field as the On Enter banish target. The On Leave "return it
    // rested" trigger requires removing this card from the field afterward and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - On Enter banish target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vrf9n24b5a'], // Entrancing Filigree, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Vernal Talisman: banish 2 preserved material cards to materialize, Class Bonus draw ---
$fixtures['vernal-talisman-preserve-draw'] = [
    'testedCards' => ['dW5uyngvJW'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Vernal Talisman
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
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
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => '7x2v4tdop1'], // Kongming, Fel Eidolon (MAGE CHAMPION), physically seeded for Class Bonus
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'dW5uyngvJW'], // Vernal Talisman, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-3!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Mend Flesh: Damage 25+ discount + Recover 8 ---
$fixtures['mend-flesh-damage25-recover'] = [
    'testedCards' => ['ju2d98w3j0'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Mend Flesh
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Mend Flesh is an EXIA (advanced element) ACTION card, so the starting champion's Subcards
    // are patched with a real EXIA champion (Dante, Hemomancer) to unlock element access. The
    // starting champion's Damage is pre-set to 25 to both reach the [Damage 25+] discount
    // condition (activationCostModifierAbilities, GeneratedMacroCode.php) and make the
    // unconditional Recover 8 observable as a Damage decrease.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['4FtNBFaOJp'], 'Damage' => 25]], // EXIA lineage/element unlock + discount/recover precondition
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ju2d98w3j0'], // Mend Flesh, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Penetrator Round: load into an unloaded Gun weapon ---
$fixtures['penetrator-round-load-gun'] = [
    'testedCards' => ['97n2jnltv5'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Penetrator Round
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Penetrator Round's element is FIRE (matching the starting champion), so no lineage patch is
    // needed, and its memory cost is 0 so materializing via a direct FSM click is free. Framework
    // Sidearm (a REGALIA,WEAPON with the GUN subtype and no Subcards, i.e. unloaded) is seeded
    // onto the field as the [REST] Load ability's target. Only the always-available Load half is
    // covered; the [Class Bonus][Level 2+] On Attack "unpreventable" trigger requires a real
    // attack and is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'p4lgdlx7md'], // Framework Sidearm (unloaded GUN weapon) - Load target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '97n2jnltv5'], // Penetrator Round, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-6!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Deployment Beacon: On Enter summons an Automaton Drone token ---
// NOTE: Samaritan's Reach was attempted first but abandoned — its effect body
// (SamaritanReachResolve) reads the CombatAttacker/CombatAttackerPlayer/CombatTarget
// decision-queue variables, but by the time the ACTION card's effect stack finishes resolving
// (multiple EffectStackOpportunity/EffectStackActiveResponse/EffectStackOpponentResponse
// windows deep), those dqVariables-injected values had already been cleared (verified live: all
// three read back NULL from the final gamestate, and the target's Damage stayed 0), so the
// ability silently no-opped. Setting them via the setup primitive only works for effects that
// read the variable immediately upon resolution, not ones buried behind several priority
// windows — a real attack sequence would be needed to test this card properly. Deployment
// Beacon's WIND element also isn't native to the "Spirit of Fire" starting champion, so — same
// technique as the advanced-element cards above — the champion's Subcards are patched with a
// real WIND champion (Spirit of Wind) to unlock element access, even though WIND isn't in
// GetAdvancedElementNames(); CanPlayerMeetCardElementRequirements() gates any non-NORM element
// the same way, not just the nine "advanced" ones.
$fixtures['deployment-beacon-summon-drone'] = [
    'testedCards' => ['klryvfq3hu'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Deployment Beacon
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Only the On Enter summon is covered; the [Class Bonus] On Leave second summon requires
    // removing this card from the field afterward and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'klryvfq3hu'], // Deployment Beacon, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-6!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Worn Diary: page counters from memory, then banish for a draw at 10+ page counters ---
$fixtures['worn-diary-page-counters-draw'] = [
    'testedCards' => ['gmuesdu6o6'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Worn Diary
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Worn Diary's element is NORM and its memory cost is 0, so no lineage patch is needed and
    // materializing via a direct FSM click is free. Three filler cards are pre-seeded into
    // myMemory so ability 0 (put a page counter per card in memory) has a nonzero, predictable
    // result. Ability 1 (REST, banish self: draw a card, only at 10+ page counters) is reached by
    // patching the page counter directly to 10 after ability 0 resolves, rather than repeating
    // ability 0 ten times.
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'gmuesdu6o6'], // Worn Diary, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-5!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Elucidate Plans: put two preparation counters on your champion ---
$fixtures['elucidate-plans-prep-counters'] = [
    'testedCards' => ['GoC1YaaCUV'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Elucidate Plans
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Elucidate Plans is a LUXEM (advanced element) ACTION card, so the starting champion's
    // Subcards are patched with a real LUXEM champion (Zander, Blinding Steel) to unlock element
    // access. Only the base always-available effect is covered; the [Class Bonus][Element Bonus]
    // memory-reveal trigger is out of scope (tied to the separate memory-reveal subsystem,
    // already documented as out of scope in luxem-sight-draw).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['UAF6Nr7GUE']]], // LUXEM lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'GoC1YaaCUV'], // Elucidate Plans, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Anathema's End: load into an unloaded Gun weapon ---
$fixtures['anathemas-end-load-gun'] = [
    'testedCards' => ['ii17fzcyfr'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Anathema's End
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Anathema's End is an UMBRA (advanced element) ITEM card, so the starting champion's
    // Subcards are patched with a real UMBRA champion (Tristan, Shadowdancer) to unlock element
    // access. Framework Sidearm (a REGALIA,WEAPON with the GUN subtype and no Subcards, i.e.
    // unloaded) is seeded onto the field as the [REST] Load ability's target, same pattern as
    // penetrator-round-load-gun. Only the always-available Load half is covered; the [Class
    // Bonus] On Champion Hit curse-banishing trigger requires a real combat hit and is out of
    // scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['he6kd7hocc']]], // UMBRA lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'p4lgdlx7md'], // Framework Sidearm (unloaded GUN weapon) - Load target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ii17fzcyfr'], // Anathema's End, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Coronal of Rejuvenation: On Enter banish Spell cards from graveyard ---
$fixtures['coronal-of-rejuvenation-banish-spell'] = [
    'testedCards' => ['uvgflagxbb'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Coronal of Rejuvenation
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Coronal of Rejuvenation is a TERA (advanced element) REGALIA,ITEM card, so the starting
    // champion's Subcards are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock
    // element access. Like entrancing-filigree-enter-banish, its additional materialize cost
    // ("banish a preserved card from your material deck") is NOT exercised here for the same
    // FSM-click-bypass reason. Luminous Surge (a SPELL card) is seeded into the graveyard as the
    // On Enter banish target. Only the On Enter banish is covered; the [REST] "play a card
    // banished by CARDNAME" ability is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'KOqdA7G6by'], // Luminous Surge (SPELL) - On Enter banish target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'uvgflagxbb'], // Coronal of Rejuvenation, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-6!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Empowering Tincture: On Enter draws into memory if brewed ---
// NOTE: Empowering Tincture was attempted first but abandoned for the same reason as
// Samaritan's Reach above -- its On Enter reads the "wasBrewed" decision-queue variable
// (normally set by the separate Brew minigame), and injecting it via dqVariables in setup
// doesn't survive to Enter resolution (verified live: reads back NULL from the final gamestate,
// and no card was drawn into memory). Fan of Seven Debts is a much simpler substitute with the
// same "draw a card" shape but no hidden variable dependency.
$fixtures['fan-of-seven-debts-enter-draw'] = [
    'testedCards' => ['k9zhw0gbov'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Fan of Seven Debts
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Fan of Seven Debts' element is NORM, so no lineage patch is needed, and its memory cost (1)
    // is NOT exercised for the same FSM-click-bypass reason as entrancing-filigree-enter-banish.
    // Only the On Enter draw is covered; the [Kongming Bonus] "banish for Shifting Currents"
    // ability is tied to a separate facing-state subsystem and is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'k9zhw0gbov'], // Fan of Seven Debts, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-4!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Incendiary Shot: load into an unloaded Gun weapon ---
$fixtures['incendiary-shot-load-gun'] = [
    'testedCards' => ['3qu7d6sopo'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Incendiary Shot
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Incendiary Shot's element is FIRE (matching the starting champion), so no lineage patch is
    // needed. Framework Sidearm (a REGALIA,WEAPON with the GUN subtype and no Subcards, i.e.
    // unloaded) is seeded onto the field as the [REST] Load ability's target, same pattern as
    // penetrator-round-load-gun/anathemas-end-load-gun. Only the always-available Load half is
    // covered; the [Class Bonus] On Hit damage trigger requires a real combat hit and is out of
    // scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'p4lgdlx7md'], // Framework Sidearm (unloaded GUN weapon) - Load target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '3qu7d6sopo'], // Incendiary Shot, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Seed of Nature: enters rested, On Enter grants +2 level ---
$fixtures['seed-of-nature-enter-level-buff'] = [
    'testedCards' => ['ybdj1Db9jz'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Seed of Nature
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Seed of Nature is a TERA (advanced element) REGALIA,ITEM card, so the starting champion's
    // Subcards are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock element
    // access. Its memory cost (0) needs no floating-payment setup. On Enter sets the dmfoA7jOjy
    // global effect (the champion's +2 level until end of turn). Only the base On Enter is
    // covered; the [Class Bonus] REST+banish repeat of the same buff is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ybdj1Db9jz'], // Seed of Nature, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-5!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Summon Sentinels: summon two Automaton Drone tokens with buff counters ---
$fixtures['summon-sentinels-drone-tokens'] = [
    'testedCards' => ['5tlzsmw3rr'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Summon Sentinels
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Summon Sentinels is a NEOS (advanced element) ACTION card, so the starting champion's
    // Subcards are patched with a real NEOS champion. Only the base "summon two tokens" effect is
    // covered; the [Class Bonus] per-domain discount isn't exercised (no domains are seeded),
    // meaning the full 4-reserve cost is paid.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['n2jnltv5kl']]], // NEOS lineage/element unlock (Tonoris, Creation's Will)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '5tlzsmw3rr'], // Summon Sentinels, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Reclaim: return target friendly ally to hand ---
$fixtures['reclaim-return-ally'] = [
    'testedCards' => ['F2wp1v0Tyk'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Reclaim
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Reclaim's element is WIND, so the starting champion's Subcards are patched with a real WIND
    // champion (Spirit of Wind) to unlock element access, same as deployment-beacon-summon-drone.
    // Dungeon Guide is seeded onto our own field as the "target ally you control" target. The
    // Floating Memory clause (banishing this card from the graveyard to help pay a later memory
    // cost) is a passive/reusable-elsewhere property, not a triggered ability, and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - return-to-hand target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F2wp1v0Tyk'], // Reclaim, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Potion Infusion: Animate: Arisanna Bonus discount + turn a Potion into an ally ---
$fixtures['potion-infusion-animate-turn-ally'] = [
    'testedCards' => ['nDYInWoAnw'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Potion Infusion: Animate
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Potion Infusion: Animate's element is NORM, so no lineage patch is needed, but its discount
    // requires the Arisanna Bonus, which (unlike the physical-presence Class Bonus checks above)
    // is lineage-based (ChampionHasInLineage), so the starting champion's Subcards are patched
    // with a real Arisanna champion (Arisanna, Herbalist Prodigy). Distilled Water (an ITEM,
    // CLERIC,POTION with reserve cost 0) is seeded onto the field as the target; animating it sets
    // Counters potion_animate/potion_animate_power/potion_animate_life (all 0, since Distilled
    // Water's own reserve cost is 0) and adds ALLY to its effective type via
    // ApplyPersistentOverride.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['b31x97n2jn']]], // Arisanna Bonus lineage unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'O1OU62Zx2Y'], // Distilled Water (ITEM, CLERIC,POTION) - Animate target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'nDYInWoAnw'], // Potion Infusion: Animate, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Tasershot: load into an unloaded Gun weapon ---
$fixtures['tasershot-load-gun'] = [
    'testedCards' => ['4x7e22tk3i'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Tasershot
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Tasershot's element is NORM, so no lineage patch is needed, and its memory cost is 0 so
    // materializing via a direct FSM click is free. Framework Sidearm (a REGALIA,WEAPON with the
    // GUN subtype and no Subcards, i.e. unloaded) is seeded onto the field as the [REST] Load
    // ability's target, same pattern as penetrator-round-load-gun/anathemas-end-load-gun/
    // incendiary-shot-load-gun. Only the always-available Load half is covered; the [Class Bonus]
    // On Champion Hit level-up-punish trigger requires a real combat hit and is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'p4lgdlx7md'], // Framework Sidearm (unloaded GUN weapon) - Load target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4x7e22tk3i'], // Tasershot, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-1!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Battlefield Benediction: Class Bonus discount + Empower scaled by opponent's board ---
$fixtures['battlefield-benediction-empower4'] = [
    'testedCards' => ['HcR3O8vDps'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Battlefield Benediction
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Battlefield Benediction's element is NORM, so no lineage patch is needed, but its discount
    // requires a CLERIC or MAGE Class Bonus, which (like samaritans-reach's abandoned attempt but
    // Class-Bonus checks scan physical field objects) needs a physically-seeded champion, so
    // Kongming, Fel Eidolon (a MAGE CHAMPION) is seeded directly onto the field. Three Dungeon
    // Guide allies are seeded onto the opponent's field so "an opponent controls three or more
    // units" is true, reaching the empower-4 branch instead of the base empower-2.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => '7x2v4tdop1'], // Kongming, Fel Eidolon (MAGE CHAMPION) - Class Bonus source
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Opponent ally 1/3
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Opponent ally 2/3
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Opponent ally 3/3
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'HcR3O8vDps'], // Battlefield Benediction, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Guerrilla Advantage: put two preparation counters on your champion ---
$fixtures['guerrilla-advantage-prep-counters'] = [
    'testedCards' => ['JxCzS4XJ3V'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Guerrilla Advantage
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Guerrilla Advantage's element is NORM, so no lineage patch is needed. Only the base
    // always-available effect is covered; the discount condition (an opponent controlling 3+
    // units) isn't reached, so the full 4-reserve cost is paid.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'JxCzS4XJ3V'], // Guerrilla Advantage, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Mnemonic Charm: On Enter draws a card into memory ---
$fixtures['mnemonic-charm-enter-draw-memory'] = [
    'testedCards' => ['to1pmvo54d'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Mnemonic Charm
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Mnemonic Charm's element is NORM, so no lineage patch is needed. Only the always-available
    // On Enter draw-into-memory is covered; the [Class Bonus] Sacrifice-for-Empower ability is
    // out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'to1pmvo54d'], // Mnemonic Charm, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Mindbreak Bullet: load into an unloaded Gun weapon ---
$fixtures['mindbreak-bullet-load-gun'] = [
    'testedCards' => ['9htu9agwj4'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Mindbreak Bullet
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Mindbreak Bullet is an UMBRA (advanced element) ITEM card, so the starting champion's
    // Subcards are patched with a real UMBRA champion (Tristan, Shadowdancer), same as
    // anathemas-end-load-gun. Framework Sidearm is seeded onto the field as the [REST] Load
    // ability's target. Only the always-available Load half is covered; the [Class Bonus] On
    // Champion Hit memory-discard trigger requires a real combat hit and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['he6kd7hocc']]], // UMBRA lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'p4lgdlx7md'], // Framework Sidearm (unloaded GUN weapon) - Load target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '9htu9agwj4'], // Mindbreak Bullet, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Winds of Retribution: allies you control get +2 power ---
$fixtures['winds-of-retribution-ally-buff'] = [
    'testedCards' => ['huqj5bbae3'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Winds of Retribution
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Winds of Retribution's element is WIND, so the starting champion's Subcards are patched
    // with a real WIND champion (Spirit of Wind), same as deployment-beacon-summon-drone/
    // reclaim-return-ally. Only the base always-available effect is covered; the [Class
    // Bonus][Level 2+] discount condition isn't reached here, so the full 6-reserve cost is paid.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'huqj5bbae3'], // Winds of Retribution, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Flute of Taming: [REST] your champion gets +1 level ---
$fixtures['flute-of-taming-champion-level'] = [
    'testedCards' => ['y8fx8G64C9'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Flute of Taming
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Flute of Taming's element is NORM, so no lineage patch is needed, and its memory cost is
    // NOT exercised for the same FSM-click-bypass reason as entrancing-filigree-enter-banish.
    // Ability 1 ([REST]: champion +1 level) is covered via the same AddGlobalEffects()-flag
    // pattern used elsewhere; ability 0 (buff a target Animal/Beast ally) requires a subtyped
    // ally target and is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'y8fx8G64C9'], // Flute of Taming, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-5!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Tome of Sorcery: Class Bonus + Level 2+ On Enter draw into memory, REST Empower 1 ---
$fixtures['tome-of-sorcery-enter-draw-empower'] = [
    'testedCards' => ['sq0ou8vas3'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Tome of Sorcery
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Tome of Sorcery's element is NORM, so no lineage patch is needed, but its On Enter requires
    // both a MAGE Class Bonus (physical presence, so Kongming, Fel Eidolon is seeded onto the
    // field) AND champion Level 2+ (the starting champion's Counters are patched with 2 level
    // counters, same technique as meltdown-level2-destroy-item). Its memory cost is NOT exercised
    // for the same FSM-click-bypass reason as entrancing-filigree-enter-banish. Both the On Enter
    // draw and the [REST] Empower 1 activated ability are covered in sequence.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => '7x2v4tdop1'], // Kongming, Fel Eidolon (MAGE CHAMPION) - Class Bonus source
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['level' => 2]]], // Level 2+ condition
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'sq0ou8vas3'], // Tome of Sorcery, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-3!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Aenean Ward: prevent the next 2 damage to target unit ---
$fixtures['aenean-ward-prevent-2'] = [
    'testedCards' => ['gqyWZXpxl9'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Aenean Ward
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Aenean Ward's element is NORM, so no lineage patch is needed. Only the base always-available
    // prevention is covered; the [Class Bonus][Level 3+] bonus draw isn't reached (no class bonus
    // or level condition set up), and targets our own champion directly.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'gqyWZXpxl9'], // Aenean Ward, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Aesan Protector: On Enter return target friendly ally to hand ---
$fixtures['aesan-protector-return-ally'] = [
    'testedCards' => ['heq49UQGvQ'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Aesan Protector
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Aesan Protector's element is WIND, so the starting champion's Subcards are patched with a
    // real WIND champion (Spirit of Wind), same as deployment-beacon-summon-drone/
    // reclaim-return-ally/winds-of-retribution-ally-buff. A second Dungeon Guide is seeded onto
    // our own field as the On Enter return-to-hand target (so it's a different object from Aesan
    // Protector itself, which is also a legal-looking ally once it enters). Only the On Enter is
    // covered; Intercept is a combat-redirect passive that requires a real attack and is out of
    // scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - On Enter return target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'heq49UQGvQ'], // Aesan Protector, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Coriolis Ward: prevent the next 1+level damage to target unit ---
$fixtures['coriolis-ward-prevent-level'] = [
    'testedCards' => ['cagz0393zq'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Coriolis Ward
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Coriolis Ward's element is WATER, so the starting champion's Subcards are patched with a
    // real WATER champion (Spirit of Water) to unlock element access. The champion is level 0 by
    // default, so the prevention amount resolves to the base 1+0=1. The "Shifting Currents face
    // West" bonus draw is tied to a separate facing-state subsystem and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'cagz0393zq'], // Coriolis Ward, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Dwarf Star's Glow: deal 2 damage to target unit ---
$fixtures['dwarf-stars-glow-damage'] = [
    'testedCards' => ['zVubkJC3ce'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dwarf Star's Glow
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Dwarf Star's Glow is an ASTRA (advanced element) ACTION card, so the starting champion's
    // Subcards are patched with a real ASTRA champion (Arisanna, Astral Zenith) to unlock element
    // access. Targets the opponent's champion directly for a straightforward damage assertion.
    // The Starcalling alternate-cost clause and the "if starcalled, put into memory" clause are
    // tied to the separate glimpse subsystem and are out of scope (this fixture pays the normal
    // reserve cost).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba']]], // ASTRA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'zVubkJC3ce'], // Dwarf Star's Glow, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Exploit Vulnerability: draw a card (Prepare 1 optional cost not exercised) ---
$fixtures['exploit-vulnerability-draw'] = [
    'testedCards' => ['hy83sghwfi'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Exploit Vulnerability
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Exploit Vulnerability's element is NORM, so no lineage patch is needed. The always-available
    // "Draw a card" is covered regardless of the optional Prepare 1 cost; the Assassin On Ally Hit
    // buff (gated behind actually paying that optional cost) is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'hy83sghwfi'], // Exploit Vulnerability, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Cosmic Bolt: deal 4 damage to target unit ---
$fixtures['cosmic-bolt-damage'] = [
    'testedCards' => ['vpmu6gvnta'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Cosmic Bolt
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Cosmic Bolt is an ASTRA (advanced element) ACTION card, so the starting champion's Subcards
    // are patched with a real ASTRA champion (Arisanna, Astral Zenith), same as
    // dwarf-stars-glow-damage. Targets the opponent's champion directly. With no other copies of
    // Cosmic Bolt in the graveyard/banishment, the damage resolves to the base 4 (no +2 bonus).
    // The Starcalling alternate-cost clause is tied to the glimpse subsystem and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba']]], // ASTRA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vpmu6gvnta'], // Cosmic Bolt, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Dream Fairy: On Enter return target opposing ally to memory ---
$fixtures['dream-fairy-return-to-memory'] = [
    'testedCards' => ['UVAb8CmjtL'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dream Fairy
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Dream Fairy's element is WIND, so the starting champion's Subcards are patched with a real
    // WIND champion (Spirit of Wind), same as deployment-beacon-summon-drone/reclaim-return-ally/
    // aesan-protector-return-ally. Dungeon Guide is seeded onto the opponent's field as the On
    // Enter target -- unlike aesan-protector-return-ally (return to hand), this sends the target
    // to the OPPONENT's own memory zone, not banish or hand. The "opponents can't activate cards
    // with that ally's name" name-lock and the passive Stealth keyword are not independently
    // asserted here.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - On Enter return-to-memory target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'UVAb8CmjtL'], // Dream Fairy, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Essence Crucible: On Enter draws a card ---
$fixtures['essence-crucible-enter-draw'] = [
    'testedCards' => ['DF5Ffwv7DJ'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Essence Crucible
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Essence Crucible's element is NORM, so no lineage patch is needed, and its memory cost (1)
    // is NOT exercised for the same FSM-click-bypass reason as entrancing-filigree-enter-banish.
    // Only the On Enter draw is covered; the [Arisanna Bonus] refinement-counter trigger and the
    // Spell-damage-boost static ability are out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'DF5Ffwv7DJ'], // Essence Crucible, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-3!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Evasive Maneuvers: prevent the next 2 damage to target unit ---
$fixtures['evasive-maneuvers-prevent-2'] = [
    'testedCards' => ['1n3gygojwk'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Evasive Maneuvers
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Evasive Maneuvers' element is NORM, so no lineage patch is needed. Only the base prevention
    // is covered; the target (our own champion) isn't a Ranger, so the "becomes distant" branch
    // isn't reached.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '1n3gygojwk'], // Evasive Maneuvers, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Barrier Servant: remove 2 enlighten counters to prevent the next damage to itself ---
$fixtures['barrier-servant-enlighten-prevent'] = [
    'testedCards' => ['xW6SZSlJX6'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Barrier Servant
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Barrier Servant's element is NORM, so no lineage patch is needed. The starting champion's
    // Counters are patched with 2 enlighten counters directly (normally only reachable via a
    // separate enlighten-granting effect) so the ability's cost (remove 2 enlighten counters from
    // your champion) can actually be paid. Only the always-available "prevent next damage to
    // self" half is covered; Intercept is a combat-redirect passive and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['enlighten' => 2]]], // Ability cost fuel
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'xW6SZSlJX6'], // Barrier Servant, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Ignite the Soul: deal 1 damage to target unit ---
$fixtures['ignite-the-soul-damage'] = [
    'testedCards' => ['rXHo9fLU32'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Ignite the Soul
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Ignite the Soul's element is FIRE (matching the starting champion), so no lineage patch is
    // needed. Its target pool is any object on the opponent's field (not filtered to ALLY/
    // CHAMPION), so it targets the opponent's champion directly. The [Class Bonus] Floating
    // Memory clause is a passive/reusable-elsewhere property, not a triggered ability, and is out
    // of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'rXHo9fLU32'], // Ignite the Soul, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Imperial Countermeasure: prevent the next 4 damage to target unit + draw into memory ---
$fixtures['imperial-countermeasure-prevent-draw'] = [
    'testedCards' => ['HRPSt74B7g'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Imperial Countermeasure
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Imperial Countermeasure's element is EXALTED,NORM -- EXALTED auto-enables whenever any
    // OTHER advanced element is unlocked in lineage (GetPlayerEnabledElements(), GameLogic.php),
    // so the starting champion's Subcards are patched with a real UMBRA champion (Tristan,
    // Shadowdancer, reused from anathemas-end-load-gun) purely to trigger that auto-enable, not
    // because the card itself needs UMBRA. Both clauses (prevention + draw into memory) are
    // covered in one activation.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['he6kd7hocc']]], // Any advanced element auto-enables EXALTED
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'HRPSt74B7g'], // Imperial Countermeasure, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Leeching Bolt: deal champion-level damage to target unit + Recover 2 ---
$fixtures['leeching-bolt-damage-recover'] = [
    'testedCards' => ['hs1mzjzexc'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Leeching Bolt
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Leeching Bolt is a TERA (advanced element) ACTION card, so the starting champion's Subcards
    // are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock element access. The
    // champion's Counters are also patched with 2 level counters (so "deal LV damage" resolves to
    // a nonzero, observable 2, instead of the default level-0 no-op) and its Damage is pre-set to
    // 5 (so Recover 2 is observable as a decrease). The damage targets the opponent's champion
    // while Recover 2 heals our own, so the two effects are independently assertable. The [Class
    // Bonus] empowered/preserved clause is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1'], 'Counters' => ['level' => 2], 'Damage' => 5]], // TERA lineage/element unlock + LV damage scaling + recover precondition
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'hs1mzjzexc'], // Leeching Bolt, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Entrenched Fortress: On Enter deals 3 damage to target unit ---
$fixtures['entrenched-fortress-enter-damage'] = [
    'testedCards' => ['PWkXI6rMl3'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Entrenched Fortress
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Entrenched Fortress is a TERA (advanced element) DOMAIN card, so the starting champion's
    // Subcards are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock element
    // access. Like other DOMAIN cards (see stocked-outpost-enter-draw-memory), it materializes
    // onto the field like any other permanent -- there's no separate domain zone in this schema.
    // Only the On Enter damage is covered; Taunt is a combat-targeting-priority passive that
    // requires a real attack declaration and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'PWkXI6rMl3'], // Entrenched Fortress, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Intangible Geist: On Enter may return regalia from banishment to material deck ---
$fixtures['intangible-geist-banish-to-material'] = [
    'testedCards' => ['Zu53izIFTX'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Intangible Geist
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Intangible Geist is a CRUX (advanced element) ALLY card, so the starting champion's
    // Subcards are patched with a real CRUX champion (Lorraine, Crux Knight, reused from
    // sabela-gossamer-penance-enter/spirit-blade-infusion-combat-discount). Backup Charger (a
    // REGALIA,ITEM) is seeded directly into myBanish as the On Enter's optional target. Only the
    // On Enter is covered; the [Class Bonus] combat-damage-prevention static ability is out of
    // scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['NfbZ0nouSQ']]], // CRUX lineage/element unlock
        ['player' => 1, 'zone' => 'myBanish', 'cardID' => '9gv4vm4kj3'], // Backup Charger (REGALIA,ITEM) - On Enter optional target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'Zu53izIFTX'], // Intangible Geist, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myBanish-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Martial Guard: prevent the next 2 damage to target unit ---
$fixtures['martial-guard-prevent-2'] = [
    'testedCards' => ['nsdwmxz1vd'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Martial Guard
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Martial Guard's element is NORM, so no lineage patch is needed. Only the base always-
    // available prevention is covered; the [Class Bonus][Level 2+] Floating Memory clause is a
    // passive/reusable-elsewhere property, not a triggered ability, and is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'nsdwmxz1vd'], // Martial Guard, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Pendant of Accrual: REST, remove 2 debt counters: draw into memory ---
$fixtures['pendant-of-accrual-debt-draw'] = [
    'testedCards' => ['WUhbG91eRa'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Pendant of Accrual
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Pendant of Accrual's element is NORM, so no lineage patch is needed, and its memory cost is
    // NOT exercised for the same FSM-click-bypass reason as entrancing-filigree-enter-banish. It
    // is seeded directly onto the field with 2 debt counters already present (normally only
    // reachable via the opponent declining to pay at their recollection phase) so the ability's
    // cost can actually be paid.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'WUhbG91eRa', 'setProperties' => ['Counters' => ['debt' => 2]]], // Ability cost fuel
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Perfect Repulsion: prevent the next exact-X damage to a friendly unit ---
$fixtures['perfect-repulsion-prevent-x'] = [
    'testedCards' => ['gwj4f15joh'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Perfect Repulsion
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Perfect Repulsion's element is WATER, so the starting champion's Subcards are patched with
    // a real WATER champion (Spirit of Water, reused from coriolis-ward-prevent-level). X is the
    // number of cards in memory at resolution time, which after 2 reserve payments is 2, so the
    // shield prevents exactly 2 damage. Only the shield's creation is covered; the "draw a card if
    // damage was prevented this way" clause is a separate deferred trigger that requires an actual
    // damage event afterward and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'gwj4f15joh'], // Perfect Repulsion, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Cometfall: deal 3 damage to all non-Astra units ---
$fixtures['cometfall-sweep-damage'] = [
    'testedCards' => ['4d5vettczb'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Cometfall
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Cometfall is an ASTRA (advanced element) ACTION card, so the starting champion's Subcards
    // are patched with a real ASTRA champion (Arisanna, Astral Zenith) to unlock element access --
    // note this only grants element ACCESS, it doesn't change the champion's own element (both
    // starting champions remain FIRE, so both take the sweep damage). No targeting decision is
    // needed since the effect hits all non-Astra units unconditionally. Without the [Class Bonus]
    // (CLERIC), the damage is the base 3, not the boosted 4. The Starcalling alternate-cost clause
    // is tied to the glimpse subsystem and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba']]], // ASTRA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4d5vettczb'], // Cometfall, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Clarent, Sword of Peace: Class Bonus + remove durability counter to prevent noncombat damage ---
$fixtures['clarent-sword-of-peace-prevent'] = [
    'testedCards' => ['m31WVJ9F04'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Clarent, Sword of Peace's own activated ability requires a WARRIOR Class Bonus (physical
    // presence, so Lorraine, Wandering Warrior is seeded directly onto the field) and a durability
    // counter on Clarent itself (seeded directly, since it's normally only present via the card's
    // own materialize-time durability allotment). Clarent is seeded straight onto the field rather
    // than played from hand since this fixture is about its activated ability, not its
    // materialize flow.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'DpHDGaX2Pn'], // Lorraine, Wandering Warrior (WARRIOR CHAMPION) - Class Bonus source
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'm31WVJ9F04', 'setProperties' => ['Counters' => ['durability' => 1]]], // Clarent, Sword of Peace - ability cost fuel
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Potion Infusion: Volatility: rest target Potion and grant a Sacrifice trigger ---
$fixtures['potion-infusion-volatility-rest'] = [
    'testedCards' => ['ndnEl5mq7W'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Potion Infusion: Volatility
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Potion Infusion: Volatility's element is NORM, so no lineage patch is needed. Distilled
    // Water (an ITEM, CLERIC,POTION) is seeded onto the field as the target. Without the
    // [Arisanna Bonus] Efficiency discount, the full 7-reserve cost is paid. Only the immediate
    // "rest the target and grant it the On Sacrifice trigger" half is covered; the eventual
    // 4+D6 damage roll requires actually sacrificing the target and is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'O1OU62Zx2Y'], // Distilled Water (ITEM, CLERIC,POTION) - rest target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ndnEl5mq7W'], // Potion Infusion: Volatility, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// NOTE: Creeping Torment was attempted first but abandoned -- its On Enter handler
// (customDQHandlers["zrplywc08c:0:Enter-1"], GeneratedMacroCode.php) calls MZRemove(), a function
// that doesn't exist anywhere in the codebase (verified: 7 call sites across CardDQHandlers.php
// and GeneratedMacroCode.php, zero definitions), causing a PHP fatal "Call to undefined function"
// error rather than a clean no-op. Flagged separately as task_bdcead88. Swapped for Charged
// Manaplate, which has no such dependency.
// --- Charged Manaplate: Class Bonus banish for a draw, gated on this-turn champion damage ---
$fixtures['charged-manaplate-banish-draw'] = [
    'testedCards' => ['jxhkurfp66'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Charged Manaplate
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Charged Manaplate's element is NORM, so no lineage patch is needed, and its memory cost (0)
    // needs no floating-payment setup. The ability requires both a GUARDIAN Class Bonus (physical
    // presence, so Ciel, Loyal Valet is seeded directly onto the field) and the champion having
    // taken 4+ damage this turn, which GetChampionDamageTakenThisTurn() reads directly from a
    // Counters["_champDamageThisTurn"] entry on the champion object -- patched in directly rather
    // than scripting a real combat/damage sequence.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'nn48ne8a05'], // Ciel, Loyal Valet (GUARDIAN CHAMPION) - Class Bonus source
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['_champDamageThisTurn' => 4]]], // This-turn damage prereq
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'jxhkurfp66'], // Charged Manaplate, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-4!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Break Apart: destroy target non-regalia item or weapon ---
$fixtures['break-apart-destroy'] = [
    'testedCards' => ['4ns2jbt4hq'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Break Apart
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Break Apart's element is NORM, so no lineage patch is needed. Forest Cake (a plain ITEM,
    // not REGALIA) is seeded onto the opponent's field as the target, avoiding the "costs 2 more
    // if it targets a regalia" branch (which isn't exercised here). Destroy resolves the same way
    // as meltdown-level2-destroy-item: the target is banished, not moved to its graveyard.
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'bjx6yo7mm5'], // Forest Cake (plain ITEM) - destroy target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4ns2jbt4hq'], // Break Apart, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Ruinous Pillars of Qidao: Class Bonus On Enter Empower 2 + draw ---
$fixtures['ruinous-pillars-of-qidao-enter-empower'] = [
    'testedCards' => ['pmx99jrukm'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Ruinous Pillars of Qidao
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Ruinous Pillars of Qidao is a TERA (advanced element) DOMAIN card, so the starting
    // champion's Subcards are patched with a real TERA champion (Kongming, Fel Eidolon) to unlock
    // element access, and that same champion is physically seeded onto the field so the MAGE
    // Class Bonus (physical-presence check) is also satisfied. Only the On Enter Empower 2 + draw
    // is covered; the Shifting-Currents-triggered sacrifice/destroy is tied to the separate facing
    // subsystem and is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => '7x2v4tdop1'], // Kongming, Fel Eidolon (MAGE CHAMPION) - Class Bonus source
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'pmx99jrukm'], // Ruinous Pillars of Qidao, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Rocket Jump: target unit becomes distant ---
$fixtures['rocket-jump-distant'] = [
    'testedCards' => ['rhlq2kkvoq'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Rocket Jump
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Rocket Jump's element is FIRE (matching the starting champion), so no lineage patch is
    // needed. Only the base "becomes distant" status is covered; the [Class Bonus] discount isn't
    // reached (full 5-reserve cost paid), and the "deal 4 damage to its attacker" clause is
    // conditional on the target actually defending in combat, which is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'rhlq2kkvoq'], // Rocket Jump, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Swerving Spring: prevent the next 2 damage to target unit ---
$fixtures['swerving-spring-prevent-2'] = [
    'testedCards' => ['vj6vmuuldt'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Swerving Spring
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Swerving Spring's element is NORM, so no lineage patch is needed. Only the base always-
    // available prevention is covered; the [Class Bonus] preparation-counter clause isn't reached
    // (no matching class bonus set up).
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vj6vmuuldt'], // Swerving Spring, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Aenean Frostlance: deal 2 damage to target unit ---
$fixtures['aenean-frostlance-damage'] = [
    'testedCards' => ['NXGaB1dYwL'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Aenean Frostlance
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Aenean Frostlance's element is WATER, so the starting champion's Subcards are patched with
    // a real WATER champion (Spirit of Water, reused from coriolis-ward-prevent-level/
    // perfect-repulsion-prevent-x). Only the base 2-damage clause is covered; the [Class Bonus]
    // [Level 3+]/[Level 6+] scaled-damage-to-a-rested-unit branches aren't reached.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'NXGaB1dYwL'], // Aenean Frostlance, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Worn Gearblade: Class Bonus + remove durability counter to prevent 1 damage ---
$fixtures['worn-gearblade-durability-prevent'] = [
    'testedCards' => ['r1o0qtb31x'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Worn Gearblade's own activated ability requires a GUARDIAN Class Bonus (physical presence,
    // so Ciel, Loyal Valet is seeded directly onto the field, reused from
    // charged-manaplate-banish-draw) and a durability counter on Worn Gearblade itself (seeded
    // directly). Worn Gearblade is seeded straight onto the field rather than played from hand
    // since this fixture is about its activated ability, not its materialize flow. Only the
    // always-available prevention half is covered; the "durability counter whenever an Automaton
    // ally dies" trigger requires a real Automaton death and is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'nn48ne8a05'], // Ciel, Loyal Valet (GUARDIAN CHAMPION) - Class Bonus source
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'r1o0qtb31x', 'setProperties' => ['Counters' => ['durability' => 1]]], // Worn Gearblade - ability cost fuel
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Airship Engineer: On Enter draws into memory if you control a distant unit ---
$fixtures['airship-engineer-enter-draw-memory'] = [
    'testedCards' => ['66pv4n1n3g'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Airship Engineer
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Airship Engineer's element is FIRE (matching the starting champion), so no lineage patch is
    // needed. IsDistant() (CardLogic.php) reads the "DISTANT" TurnEffect directly, so the starting
    // champion's TurnEffects are patched with it directly (same tag rocket-jump-distant confirmed
    // is applied by a real "becomes distant" effect) rather than scripting one. Only the On Enter
    // draw-into-memory is covered; the [Class Bonus] Ranged 2 static combat bonus is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['TurnEffects' => ['DISTANT']]], // On Enter "control a distant unit" precondition
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '66pv4n1n3g'], // Airship Engineer, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Beastbond Paws: banish self to buff a target Animal/Beast ally ---
$fixtures['beastbond-paws-buff-truesight'] = [
    'testedCards' => ['F1t18omUlx'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Beastbond Paws
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Beastbond Paws' element is NORM, so no lineage patch is needed, and its memory cost (0)
    // needs no floating-payment setup. Cheerful Slime (an ALLY, TAMER,ANIMAL,SLIME) is seeded
    // onto the field as the required Animal/Beast target.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'OUqX2BBcGv'], // Cheerful Slime (ALLY, ANIMAL) - buff target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F1t18omUlx'], // Beastbond Paws, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-4!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Fireball: deal 1+level damage to target unit ---
$fixtures['fireball-scaling-damage'] = [
    'testedCards' => ['RIVahUIQVD'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Fireball
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Fireball's element is FIRE (matching the starting champion), so no lineage patch is needed.
    // The starting champion's Counters are patched with 2 level counters so "deal 1+LV damage"
    // resolves to a nonzero-interesting 3, instead of the default level-0 base of 1. Without the
    // [Class Bonus] discount, the full 4-reserve cost is paid.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['level' => 2]]], // LV damage scaling
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'RIVahUIQVD'], // Fireball, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Disintegrate: destroy target ally or regalia ---
$fixtures['disintegrate-destroy'] = [
    'testedCards' => ['FhbVHkHQRb'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Disintegrate
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Disintegrate's element is FIRE (matching the starting champion), so no lineage patch is
    // needed. Dungeon Guide (an ALLY) is a legal target already present via natural deck draw on
    // the opponent's side of a fresh game -- no additional target is seeded. Without the [Class
    // Bonus] Efficiency discount, the full 8-reserve cost is paid, which needs more fuel than a
    // natural hand provides, so 2 extra filler cards are seeded into hand alongside Disintegrate.
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - destroy target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 1/2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 2/2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'FhbVHkHQRb'], // Disintegrate, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-9!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Crux Sight: draw a card (additional cost not exercised) ---
$fixtures['crux-sight-draw'] = [
    'testedCards' => ['P9Y1Q5cQ0F'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Crux Sight
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Crux Sight is a CRUX (advanced element) ACTION card, so the starting champion's Subcards
    // are patched with a real CRUX champion (Lorraine, Crux Knight). Its reserve cost is 0, so no
    // reserve payment decisions are needed. Only the always-available "Draw a card" is covered;
    // the optional (2) additional cost (self-banish + return a crux card from graveyard) isn't
    // paid.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['NfbZ0nouSQ']]], // CRUX lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'P9Y1Q5cQ0F'], // Crux Sight, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Equinox Hour: banish self to draw a card ---
$fixtures['equinox-hour-banish-draw'] = [
    'testedCards' => ['UE6g95C1nZ'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Equinox Hour
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Equinox Hour's element is NORM, so no lineage patch is needed, and its memory cost (0)
    // needs no floating-payment setup. Only the "banish self, draw a card" half is covered; the
    // opponent's optional follow-up (may materialize a card from their material deck) is declined
    // via a trailing NO from player 2.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'UE6g95C1nZ'], // Equinox Hour, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-6!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Distilled Atrophy: sacrifice for a delevel-and-damage effect ---
$fixtures['distilled-atrophy-delevel-damage'] = [
    'testedCards' => ['h38lrj5221'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Distilled Atrophy's element is NORM, so no lineage patch is needed. It's seeded directly
    // onto the field with 1 age counter already present (normally only reachable via a real
    // recollection-phase trigger) so the sacrifice ability's -X-level/damage-if-level<=0 effect is
    // observable. The opponent's champion is level 0 by default, so -1 level brings it to -1
    // (<=0), triggering 1 unpreventable damage too.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'h38lrj5221', 'setProperties' => ['Counters' => ['age' => 1]]], // Distilled Atrophy - ability scaling fuel
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Creative Shock: draw two cards, then discard one ---
$fixtures['creative-shock-draw-discard'] = [
    'testedCards' => ['BqDw4Mei4C'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Creative Shock
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Creative Shock's element is FIRE (matching the starting champion), so no lineage patch is
    // needed. Only the base "draw 2, discard 1" is covered; the [Class Bonus] fire-discard damage
    // clause is out of scope (no class bonus set up, and the discarded card isn't guaranteed FIRE).
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'BqDw4Mei4C'], // Creative Shock, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Disorienting Winds: return target ally to hand, draw a card ---
$fixtures['disorienting-winds-return-draw'] = [
    'testedCards' => ['UfQh069mc3'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Disorienting Winds
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Disorienting Winds' element is WIND, so the starting champion's Subcards are patched with a
    // real WIND champion (Spirit of Wind), same as deployment-beacon-summon-drone/
    // reclaim-return-ally. Dungeon Guide is seeded onto our own field as the return-to-hand
    // target. Without the Efficiency discount (champion level 0), the full 5-reserve cost is paid.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - return-to-hand target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'UfQh069mc3'], // Disorienting Winds, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Exia Sight: draw a card ---
$fixtures['exia-sight-draw'] = [
    'testedCards' => ['1fy8l4pxs9'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Exia Sight
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Exia Sight is an EXIA (advanced element) ACTION card, so the starting champion's Subcards
    // are patched with a real EXIA champion (Dante, Hemomancer, reused from
    // mend-flesh-damage25-recover). Its reserve cost is 0, so no reserve payment decisions are
    // needed. Only the base "Draw a card" is covered; the [Damage 20+] discount-for-next-card
    // clause isn't reached (champion Damage is 0).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['4FtNBFaOJp']]], // EXIA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '1fy8l4pxs9'], // Exia Sight, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Focused Flames: deal 4 damage to target ally ---
$fixtures['focused-flames-damage'] = [
    'testedCards' => ['145y6KBhxe'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Focused Flames
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Focused Flames' element is FIRE (matching the starting champion), so no lineage patch is
    // needed. Dungeon Guide is seeded onto the opponent's field as the required ally target
    // (unlike most other damage cards in this backlog, this one can't target a champion). Without
    // the [Class Bonus] discount, the full 2-reserve cost is paid.
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - damage target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '145y6KBhxe'], // Focused Flames, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Forging Heat: put a durability counter and +1 power on target Sword weapon ---
$fixtures['forging-heat-sword-buff'] = [
    'testedCards' => ['tjmzM6t9R5'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Forging Heat
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Forging Heat's element is FIRE (matching the starting champion), so no lineage patch is
    // needed. Clarent, Sword of Peace is seeded onto the field as the required Sword weapon
    // target. The Floating Memory clause is a passive/reusable-elsewhere property, not a
    // triggered ability, and is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'm31WVJ9F04'], // Clarent, Sword of Peace (WEAPON, SWORD) - buff target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'tjmzM6t9R5'], // Forging Heat, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Foraging Servant: On Enter Gather a random resource token ---
$fixtures['foraging-servant-enter-gather'] = [
    'testedCards' => ['0pw0y6isxy'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Foraging Servant
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Foraging Servant's element is NORM, so no lineage patch is needed. Only the base "Gather" On
    // Enter is covered (which summons one of six possible tokens chosen at random); the [Class
    // Bonus] Floating Memory clause is a passive/reusable-elsewhere property and is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '0pw0y6isxy'], // Foraging Servant, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Give Bath: remove all temporary damage from target ally ---
$fixtures['give-bath-remove-damage'] = [
    'testedCards' => ['XeXek4dKav'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Give Bath
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Give Bath's element is WATER, so the starting champion's Subcards are patched with a real
    // WATER champion (Spirit of Water, reused from coriolis-ward-prevent-level/
    // perfect-repulsion-prevent-x/aenean-frostlance-damage). Dungeon Guide is seeded onto the
    // opponent's field with 1 pre-existing Damage so the "remove all damage" effect is observable
    // as a decrease to 0. The [Class Bonus] Floating Memory clause is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['Damage' => 2]], // Dungeon Guide, pre-damaged - remove-damage target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'XeXek4dKav'], // Give Bath, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Immolation Trap: destroy target damaged ally ---
$fixtures['immolation-trap-destroy-damaged'] = [
    'testedCards' => ['Uxn14UqyQg'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Immolation Trap
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Immolation Trap's element is FIRE (matching the starting champion), so no lineage patch is
    // needed. Dungeon Guide is seeded onto the opponent's field with 1 pre-existing Damage so it
    // qualifies as a legal "damaged ally" target. Without the [Class Bonus] discount, the full
    // 2-reserve cost is paid.
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['Damage' => 1]], // Dungeon Guide, pre-damaged - destroy target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'Uxn14UqyQg'], // Immolation Trap, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Gencode Womb: Dante Bonus (3), banish self: summon a token (no Elysian controlled) ---
$fixtures['gencode-womb-banish-summon'] = [
    'testedCards' => ['7IVQRtJFa8'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Gencode Womb
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Gencode Womb's element is NORM, so no lineage patch is needed, and its memory cost (0)
    // needs no floating-payment setup. Its ability requires IsDanteBonusActive() (a champion-name
    // check, not a class/lineage check), so Dante, Hemomancer (reused from
    // mend-flesh-damage25-recover/exia-sight-draw) is seeded directly onto the field. The
    // ActivatedAbilityCost() switch (GameLogic.php) auto-queues 3 reserve payments and banishes
    // Gencode Womb itself as the ability's own cost. We control no Elysian object, so the "else"
    // branch (summon an Elysian Test Subject token) resolves rather than the draw-into-memory
    // branch.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => '4FtNBFaOJp'], // Dante, Hemomancer (CHAMPION) - Dante Bonus source
        ['player' => 1, 'zone' => 'myField', 'cardID' => '7IVQRtJFa8'], // Gencode Womb, seeded straight onto the field (this fixture is about its activated ability, not materialize)
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Distilled Water: Brew (1 Herb), then Sacrifice draws a card since it was brewed ---
$fixtures['distilled-water-brew-sacrifice-draw'] = [
    'testedCards' => ['O1OU62Zx2Y'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Distilled Water
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Distilled Water's element is NORM, so no lineage patch is needed. Brewing is an alternate
    // cost (sacrifice herbs instead of paying reserve), declared via a YESNO decision at
    // materialize time. A single Herb-subtype token (Blightroot) is seeded onto the field to pay
    // the Brew - One Herb cost. Sacrificing Distilled Water afterward checks the
    // O1OU62Zx2Y_wasBrewed decision-queue variable set during that declare-brew flow.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB token) - brew ingredient
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'O1OU62Zx2Y'], // Distilled Water, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Empowering Tincture: Brew (Manaroot + Herb), On Enter draws into memory since brewed ---
$fixtures['empowering-tincture-brew-enter-draw'] = [
    'testedCards' => ['9g44vm5kt3'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Empowering Tincture
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Empowering Tincture's element is NORM, so no lineage patch is needed. Brewing is an
    // alternate cost (sacrifice herbs instead of paying reserve): its recipe requires exactly one
    // Manaroot plus one other Herb, so both tokens are seeded onto the field. Only the On Enter
    // "if brewed, draw into memory" half is covered; the unconditional Sacrifice: +2 level effect
    // is out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => '5joh300z2s'], // Manaroot (HERB token) - required brew ingredient
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB token) - second brew ingredient
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '9g44vm5kt3'], // Empowering Tincture, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Necklace of Foresight: Banish self, Glimpse 4 ---
$fixtures['necklace-of-foresight-banish-glimpse'] = [
    'testedCards' => ['lq2kkvoqk1'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Necklace of Foresight's element is NORM, so no lineage patch is needed. As a REGALIA,ITEM
    // (a Material-deck card type), it is seeded directly onto the field rather than played from
    // hand, same pattern as worn-gearblade-durability-prevent -- this fixture is about its
    // activated ability, not its materialize flow, so the memory-cost payment mechanic is out of
    // scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'lq2kkvoqk1'], // Necklace of Foresight, seeded straight onto the field
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY;Bottom=', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Astra Sight: Glimpse 1, then draw a card ---
$fixtures['astra-sight-glimpse-draw'] = [
    'testedCards' => ['zuj68m69iq'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Astra Sight
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Astra Sight is an ASTRA (advanced element) ACTION card, so the starting champion's Subcards
    // are patched with a real ASTRA champion (Arisanna, Astral Zenith), same as
    // dwarf-stars-glow-damage/cosmic-bolt-damage/cometfall-sweep-damage, to unlock element access.
    // Its reserve cost is 0, so no reserve payment is needed. Only the base Glimpse 1 + draw
    // effect is covered; the Starcalling (0) alternate-timing clause is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba']]], // ASTRA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'zuj68m69iq'], // Astra Sight, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=px60u5n1do;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Rally the Peasants: look at top 6, may reveal a Human into hand ---
$fixtures['rally-the-peasants-search-human'] = [
    'testedCards' => ['q1uwq8sdbz'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Rally the Peasants
8 Formidable Youxia
4 Dungeon Guide
4 Fairy Whispers
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'q1uwq8sdbz'], // Rally the Peasants, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'ToHand=acmde97dbu;Reveal=acmde97dbu,acmde97dbu,acmde97dbu,acmde97dbu;', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Juggle Knives: deal 1 damage to target champion ---
$fixtures['juggle-knives-damage-champion'] = [
    'testedCards' => ['7VxRE6HgZC'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Juggle Knives
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '7VxRE6HgZC'], // Juggle Knives, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Alpha Philterbeast: Brew - Three Herbs, ages if brewed ---
$fixtures['alpha-philterbeast-brew-age-counters'] = [
    'testedCards' => ['NwK5wge8wy'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Alpha Philterbeast
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['KqBosnU7pU']]], // EXALTED lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot herb 1/3
        ['player' => 1, 'zone' => 'myField', 'cardID' => '5joh300z2s'], // Manaroot herb 2/3
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'soporhlq2k'], // Fraysia herb 3/3
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'NwK5wge8wy'], // Alpha Philterbeast, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-3', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Otherworldly Possessions: enlighten counter per distinct reserve cost you control/graveyard ---
$fixtures['otherworldly-possessions-enlighten'] = [
    'testedCards' => ['MuCOJRgMdj'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Otherworldly Possessions
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (reserve cost 3)
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'n8wyfG9hbY'], // Fairy Whispers (reserve cost 1)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'MuCOJRgMdj'], // Otherworldly Possessions, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Lightweaver's Assault: reveal memory, split damage equal to cards revealed ---
$fixtures['lightweavers-assault-split-damage'] = [
    'testedCards' => ['zxB4tzy9iy'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Lightweaver's Assault
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['UAF6Nr7GUE']]], // LUXEM lineage/element unlock
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // memory fuel 1/2
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // memory fuel 2/2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'zxB4tzy9iy'], // Lightweaver's Assault, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0:6', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Mark the Target: deal 1 damage to target unit ---
$fixtures['mark-the-target-damage'] = [
    'testedCards' => ['LRsgl92Iqa'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Mark the Target
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - damage target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'LRsgl92Iqa'], // Mark the Target, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Meteor Strike: not starcalled, destroy target non-champion object ---
$fixtures['meteor-strike-destroy'] = [
    'testedCards' => ['dwavcoxpnj'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Meteor Strike
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba']]], // ASTRA lineage/element unlock
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - destroy target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'dwavcoxpnj'], // Meteor Strike, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Nascent Blast: deal 3 damage to target unit ---
$fixtures['nascent-blast-damage'] = [
    'testedCards' => ['vajycopxgf'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Nascent Blast
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - damage target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vajycopxgf'], // Nascent Blast, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Planted Explosive: deal 2 damage to target unit (not prepared) ---
$fixtures['planted-explosive-damage'] = [
    'testedCards' => ['5X5W2Uda5a'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Planted Explosive
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - damage target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '5X5W2Uda5a'], // Planted Explosive, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Reposition: target unit becomes distant ---
$fixtures['reposition-distant'] = [
    'testedCards' => ['vfq3huqj5b'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Reposition
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vfq3huqj5b'], // Reposition, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Potion Infusion: Seal: rest target Potion you control ---
$fixtures['potion-infusion-seal-rest'] = [
    'testedCards' => ['om2ry208kk'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Potion Infusion: Seal
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'O1OU62Zx2Y'], // Distilled Water (POTION) - rest target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'om2ry208kk'], // Potion Infusion: Seal, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Backstep: target unit becomes distant, then draw a card ---
$fixtures['backstep-distant-draw'] = [
    'testedCards' => ['sesw2ugmnm'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Backstep
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'sesw2ugmnm'], // Backstep, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Retold Fortune: discard up to one Spell, if you do draw a card ---
$fixtures['retold-fortune-discard-draw'] = [
    'testedCards' => ['ow8iopvc8s'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Retold Fortune
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '9wxcgpy069'], // Expel the Departed (SPELL) - discard fuel
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ow8iopvc8s'], // Retold Fortune, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-8!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-5', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Harvest Herbs: Gather (summon a random herb token) ---
$fixtures['harvest-herbs-gather'] = [
    'testedCards' => ['zadf9q1wl8'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Harvest Herbs
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'zadf9q1wl8'], // Harvest Herbs, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Harmonious Mantra: Recover 3 ---
$fixtures['harmonious-mantra-recover'] = [
    'testedCards' => ['gnth142db4'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Harmonious Mantra
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Damage' => 5]], // pre-damage champion so Recover 3 is observable
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'gnth142db4'], // Harmonious Mantra, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Everlonging Thorns: put 2 debuff counters on target ally ---
$fixtures['everlonging-thorns-debuff'] = [
    'testedCards' => ['C3YucOomEM'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Everlonging Thorns
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['mdwbkuhtjm']]], // TERA lineage/element unlock
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'C3YucOomEM'], // Everlonging Thorns, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Heighten Spellcraft: Empower 3 ---
$fixtures['heighten-spellcraft-empower'] = [
    'testedCards' => ['zejeq7rp5q'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Heighten Spellcraft
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'zejeq7rp5q'], // Heighten Spellcraft, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Inspiring Call: allies +1 power until end of turn, draw a card into memory ---
$fixtures['inspiring-call-buff-draw'] = [
    'testedCards' => ['k71PE3clOI'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Inspiring Call
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'k71PE3clOI'], // Inspiring Call, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Attune with the Winds: put a buff counter on each ally you control ---
$fixtures['attune-with-the-winds-buff'] = [
    'testedCards' => ['ify06tSEVC'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Attune with the Winds
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - buff recipient
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ify06tSEVC'], // Attune with the Winds, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Into the Fray: target ally gets +1 POWER per other ally until end of turn ---
$fixtures['into-the-fray-buff'] = [
    'testedCards' => ['tu9agwj4f1'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Into the Fray
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide 1/2 - buff target
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'acmde97dbu'], // Formidable Youxia - other ally, makes otherCount=1
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'tu9agwj4f1'], // Into the Fray, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Gentle Respite: if target opponent's influence is greater than yours, draw into memory ---
$fixtures['gentle-respite-draw'] = [
    'testedCards' => ['ddv1au7t9m'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Gentle Respite
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'theirHand', 'cardID' => 'em6eEh9q8y'], // extra opponent hand fuel 1/2, pushes their influence above ours
        ['player' => 1, 'zone' => 'theirHand', 'cardID' => 'em6eEh9q8y'], // extra opponent hand fuel 2/2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ddv1au7t9m'], // Gentle Respite, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Water Barrier: prevent all but 1 of the next damage to your champion this turn ---
$fixtures['water-barrier-prevent-tag'] = [
    'testedCards' => ['xWJND68I8X'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Water Barrier
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'xWJND68I8X'], // Water Barrier, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Tsunami of Nanyue: deal 2 damage to all rested allies (default Shifting Currents) ---
$fixtures['tsunami-of-nanyue-damage'] = [
    'testedCards' => ['pi9ftq3sul'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Tsunami of Nanyue
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['Status' => 1]], // rested Dungeon Guide - damage target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'pi9ftq3sul'], // Tsunami of Nanyue, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Flourishing Qi: deal LV (+charge) damage to target unit ---
$fixtures['flourishing-qi-damage'] = [
    'testedCards' => ['MDu0e3tib8'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Flourishing Qi
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['mdwbkuhtjm'], 'Counters' => ['level' => 2]]], // TERA lineage/element unlock + level 2 for nonzero LV damage
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - damage target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'MDu0e3tib8'], // Flourishing Qi, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Nascent Barrier: [Level 3+] Glimpse 3 ---
$fixtures['nascent-barrier-glimpse'] = [
    'testedCards' => ['6bc3ogf0o8'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Nascent Barrier
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['level' => 3]]], // level 3 for the Level 3+ Glimpse clause
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '6bc3ogf0o8'], // Nascent Barrier, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y,6bc3ogf0o8,em6eEh9q8y;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Potion Infusion: Frostbite: rest target Potion you control ---
$fixtures['potion-infusion-frostbite-rest'] = [
    'testedCards' => ['mxz1vdxi74'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Potion Infusion: Frostbite
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'O1OU62Zx2Y'], // Distilled Water (POTION) - rest target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'mxz1vdxi74'], // Potion Infusion: Frostbite, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Optical Control: choose a card type on enter ---
$fixtures['optical-control-choose-type'] = [
    'testedCards' => ['j4U5Tu76Lz'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Optical Control
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['UAF6Nr7GUE']]], // LUXEM lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'j4U5Tu76Lz'], // Optical Control, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '2', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Package Courier: On Enter, may discard a card to draw a card ---
$fixtures['package-courier-discard-draw'] = [
    'testedCards' => ['kjCKx4FVrM'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Package Courier
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'kjCKx4FVrM'], // Package Courier, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Clumsy Apprentice: On Enter, deal 2 damage to your champion, draw a card ---
$fixtures['clumsy-apprentice-enter-damage-draw'] = [
    'testedCards' => ['LZ8JpWj27h'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Clumsy Apprentice
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'LZ8JpWj27h'], // Clumsy Apprentice, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Solar Pinnacle: deal 2 damage to target unit, Empower 2 ---
$fixtures['solar-pinnacle-damage-empower'] = [
    'testedCards' => ['zeig1e49wb'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Solar Pinnacle
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - damage target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'zeig1e49wb'], // Solar Pinnacle, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Regenerate: draw a card into memory ---
$fixtures['regenerate-draw'] = [
    'testedCards' => ['v9ngjjadj4'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Regenerate
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'v9ngjjadj4'], // Regenerate, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Purge in Flames: deal 2 damage to all units except your champion ---
$fixtures['purge-in-flames-damage'] = [
    'testedCards' => ['uTBsOYf15p'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Purge in Flames
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - AoE target 1/2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 1/2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 2/2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'uTBsOYf15p'], // Purge in Flames, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-9!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Revitalizing Cleanse: recover X (water cards in memory), draw a card ---
$fixtures['revitalizing-cleanse-recover-draw'] = [
    'testedCards' => ['1BkfdFqCrG'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Revitalizing Cleanse
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF'], 'Damage' => 5]], // WATER lineage/element unlock + pre-damage so recovery is observable
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'xWJND68I8X'], // Water Barrier (WATER) - memory fuel 1/2
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'pi9ftq3sul'], // Tsunami of Nanyue (WATER) - memory fuel 2/2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '1BkfdFqCrG'], // Revitalizing Cleanse, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Solar Providence: draw a card, then discard a card ---
$fixtures['solar-providence-draw-discard'] = [
    'testedCards' => ['gnj9hi5ult'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Solar Providence
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'gnj9hi5ult'], // Solar Providence, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Spellshield: Arcane: prevent the next damage to your champion this turn ---
$fixtures['spellshield-arcane-prevent-tag'] = [
    'testedCards' => ['RUqtU0Lczf'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Spellshield: Arcane
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G']]], // ARCANE lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'RUqtU0Lczf'], // Spellshield: Arcane, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Spellshield: Astra: prevent the next damage to your champion this turn, then Glimpse X ---
$fixtures['spellshield-astra-prevent-tag'] = [
    'testedCards' => ['nmp5af098k'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Spellshield: Astra
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba']]], // ASTRA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'nmp5af098k'], // Spellshield: Astra, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Stellar Bloom: Gather four times ---
$fixtures['stellar-bloom-gather-four'] = [
    'testedCards' => ['JFdxtCqdeg'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Stellar Bloom
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba']]], // ASTRA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'JFdxtCqdeg'], // Stellar Bloom, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Take Cover: target unit you control gains stealth and becomes distant ---
$fixtures['take-cover-stealth-distant'] = [
    'testedCards' => ['2ugmnmp5af'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Take Cover
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '2ugmnmp5af'], // Take Cover, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Spirit's Blessing: return a regalia to material deck, wake up champion, draw a card ---
$fixtures['spirits-blessing-wake-draw'] = [
    'testedCards' => ['qaA3sXFRFY'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Spirit's Blessing
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['NfbZ0nouSQ'], 'Status' => 1]], // CRUX lineage/element unlock, rested so wake-up is observable
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'lq2kkvoqk1'], // Necklace of Foresight (REGALIA) - additional-cost fuel
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'qaA3sXFRFY'], // Spirit's Blessing, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Safeguard Amulet: banish self to prevent 4 non-combat damage to champion this turn ---
$fixtures['safeguard-amulet-prevent'] = [
    'testedCards' => ['yj2rJBREH8'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Nascent Blast
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Safeguard Amulet's element is NORM, so no lineage patch is needed. It is seeded directly
    // onto the field (REGALIA convention). Its ability is banish-self at zero reserve cost, so
    // one CustomInput Activate click both tags the champion with the prevent-4 turn effect and
    // moves the amulet to myBanish. Nascent Blast (also NORM) is then played and its own MZCHOOSE
    // target is pointed at our own champion (myField-0, "target unit" allows own side) instead of
    // an opponent's ally, dealing 3 non-combat damage that the prevention fully absorbs. The
    // prevention's exact 4-point ceiling isn't independently proven (3 < 4 either way), only that
    // the 3 damage dealt is fully prevented.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'yj2rJBREH8'], // Safeguard Amulet (REGALIA)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vajycopxgf'], // Nascent Blast, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Shard of Empowerment: banish self, Empower 2 ---
$fixtures['shard-of-empowerment-buff'] = [
    'testedCards' => ['qqq8j5fxym'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Windslice
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Shard of Empowerment's element is NORM. Seeded directly onto the field (REGALIA
    // convention), its ability is banish-self at zero reserve cost: one CustomInput Activate
    // click both empowers the champion by 2 and moves the shard to myBanish. Empower() tags the
    // champion's TurnEffects with the source card ID, EMPOWERED, and EMPOWER_PLUS_2.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'qqq8j5fxym'], // Shard of Empowerment (REGALIA)
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Bauble of Abundance: banish self, each player draws a card ---
$fixtures['bauble-of-abundance-draw'] = [
    'testedCards' => ['Z9TCpaMJTc'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Windslice
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Bauble of Abundance's element is NORM. Seeded directly onto the field (REGALIA
    // convention), its ability is banish-self at zero reserve cost: one CustomInput Activate
    // click draws a card for both the activating player and the opponent, then moves the
    // bauble to myBanish.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'Z9TCpaMJTc'], // Bauble of Abundance (REGALIA)
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Arcane Sight: champion gets +1 level until end of turn, draw a card ---
$fixtures['arcane-sight-level-draw'] = [
    'testedCards' => ['XLrHaYV9VB'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Arcane Sight
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Arcane Sight's element is ARCANE, so the starting champion's Subcards are patched with a
    // real ARCANE champion (Lorraine, Arclight Saber, this deck's own champion) to unlock element
    // access. Its reserve cost is 0, so no reserve payment step is needed. The ability tags the
    // champion's TurnEffects with its own card ID and draws a card.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G']]], // ARCANE lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'XLrHaYV9VB'], // Arcane Sight, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Creative Tinder: draw two cards, then discard a card ---
$fixtures['creative-tinder-draw-discard'] = [
    'testedCards' => ['KCXN59ldAi'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Creative Tinder
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Creative Tinder's element is FIRE, matched by the default starting champion (Spirit of
    // Fire), so no lineage patch is needed. Without a [Class Bonus] discount, the full 3-reserve
    // cost is paid; completing the 3rd reserve payment cascades directly into the ability itself
    // (no separate opportunity-window PASS is needed for this card), which draws two cards and
    // immediately opens its own MZCHOOSE asking which one to discard.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'KCXN59ldAi'], // Creative Tinder, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Crimson Vein: whenever you recover, put a blood counter on it ---
$fixtures['crimson-vein-recover-counter'] = [
    'testedCards' => ['QwF7kvdpFz'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Harmonious Mantra
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Crimson Vein's element is EXIA, so the starting champion's Subcards are patched with a real
    // EXIA champion (Dante, Hemomancer) to unlock element access. It is seeded directly onto the
    // field (REGALIA convention) with 0 blood counters; without the [Class Bonus] discount it does
    // not enter with any. The champion is pre-damaged so Harmonious Mantra's Recover 3 (NORM,
    // castable regardless of champion identity) is observable. Only the unconditional "whenever
    // you recover, put a blood counter on CARDNAME" trigger is covered by asserting Counters; the
    // resulting +X[LIFE] champion buff is a dynamically computed value with no stored property to
    // assert against, so it is not independently proven here.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['4FtNBFaOJp'], 'Damage' => 5]], // EXIA lineage/element unlock, pre-damaged so recover is observable
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'QwF7kvdpFz'], // Crimson Vein (REGALIA)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'gnth142db4'], // Harmonious Mantra, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Blood Surge: until end of turn, you can't draw cards ---
$fixtures['blood-surge-no-draw'] = [
    'testedCards' => ['yHIeIwxWde'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Blood Surge
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Blood Surge's element is EXIA, so the starting champion's Subcards are patched with a real
    // EXIA champion (Dante, Hemomancer) to unlock element access. Without the [Class Bonus]
    // discount and with the champion undamaged (below the [Level 5+][Damage 10+] threshold),
    // Blood Surge draws 0 cards itself but unconditionally applies a "can't draw cards until end
    // of turn" global effect (ri955ygd5v_NO_DRAW) to the activating player. Bauble of Abundance
    // (NORM, seeded directly onto the field per REGALIA convention) is then activated as a second,
    // independent draw source: its "each player draws a card" effect is blocked for the
    // NO_DRAW'd activating player but still resolves normally for the opponent, proving the
    // prevention is real (not just an absence of draw sources) via the asymmetric outcome.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['4FtNBFaOJp']]], // EXIA lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'Z9TCpaMJTc'], // Bauble of Abundance (REGALIA) - second draw source
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'yHIeIwxWde'], // Blood Surge, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Dante, Hemomancer: (X), [REST]: deal X unpreventable damage to Dante and empower X ---
$fixtures['dante-hemomancer-empower'] = [
    'testedCards' => ['4FtNBFaOJp'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Windslice
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Dante, Hemomancer's own activated ability is champion-identity-gated (a card-ID switch
    // case, not the generic element-lineage system), so the starting champion object's CardID
    // itself is patched directly to Dante, Hemomancer rather than only patching Subcards.
    // Activating it opens a NUMBERCHOOSE for X (1 to CountAvailableReservePayments), then pays X
    // reserve, then the ability deals X unpreventable damage to Dante and empowers him by X.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '4FtNBFaOJp']], // become Dante, Hemomancer
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-0!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Blistering Insurgent: whenever your champion attacks for the first time each turn, +1 POWER ---
$fixtures['blistering-insurgent-attack-buff'] = [
    'testedCards' => ['XgnBkRQgg1'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Blistering Insurgent
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['potion_animate_power' => 3]]], // give the champion positive power so it can attack without a weapon
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'XgnBkRQgg1'], // Blistering Insurgent, seeded to a known hand slot
    ],
    'actions' => [
        // Rule 1.h: the first player can't attack on turn 1, so end turn twice (P1->P2->P1) to
        // reach turn 3 before declaring an attack.
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Jovian Hilt X Ultra: enters linked to target Sword weapon, buffs it ---
$fixtures['jovian-hilt-link-buff'] = [
    'testedCards' => ['sZTH5LanyW'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Jovian Hilt X Ultra
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G']]], // ARCANE lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'm31WVJ9F04'], // Clarent, Sword of Peace (Sword weapon) - link target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'sZTH5LanyW'], // Jovian Hilt X Ultra, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-4!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Rumble Coordinator: Vigor -- wakes up at the beginning of your end phase ---
$fixtures['rumble-coordinator-vigor-wake'] = [
    'testedCards' => ['U5Fns5U7He'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Windslice
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Rumble Coordinator's element is ARCANE, so the starting champion's Subcards are patched
    // with a real ARCANE champion (Lorraine, Arclight Saber) to unlock element access. It is
    // seeded directly onto the field, pre-rested (Status 1), then a second setup step patches its
    // Status back down to confirm the rested precondition -- ending the turn once triggers Vigor's
    // unconditional "wakes up at the beginning of your end phase" keyword, so it ends the turn
    // already readied even though it's now the opponent's turn. Only the base Vigor keyword is
    // covered; both [Class Bonus] clauses (+1 power per static counter, On Death draw) are out of
    // scope for the default (non-matching-class) champion.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G']]], // ARCANE lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'U5Fns5U7He'], // Rumble Coordinator
        ['player' => 1, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 1]], // pre-rest it so the wake-up is observable
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Creeping Torment: On Enter, put itself on the bottom of target champion's lineage ---
$fixtures['creeping-torment-lineage'] = [
    'testedCards' => ['zrplywc08c'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Creeping Torment
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Creeping Torment's element is UMBRA, so the starting champion's Subcards are patched with a
    // real UMBRA champion (Tristan, Shadowreaver) to unlock element access. Without a [Class
    // Bonus] discount, the full 1-reserve cost is paid, then both players decline the
    // EffectStackActiveResponse opportunity window before the ability's own MZCHOOSE (which
    // champion to add to the lineage) resolves, targeting our own champion.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['4upufooz13']]], // UMBRA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'zrplywc08c'], // Creeping Torment, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Cone of Frost: level-conditional multi-target damage (Level 1+ step) ---
$fixtures['cone-of-frost-level1-damage'] = [
    'testedCards' => ['i7sbjy86ep'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Cone of Frost
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Regression test for a fixed engine crash: activating Cone of Frost at champion level 1+
    // called DecisionQueueController::AddDecision(..., lastDecision:"-"), a named argument that
    // didn't exist on that method's signature, which fatally crashed every PHP 8 request that hit
    // it. AddDecision() now accepts (and ignores) that argument. Cone of Frost's element is WATER,
    // so the starting champion's Subcards are patched with a real WATER champion (Spirit of Water)
    // to unlock element access, and its Counters are patched with a level-1 counter so the
    // [Level 1+] clause is active (only the base level-1 step is exercised here; [Level 3+] and
    // [Level 5+] add further MZMAYCHOOSE rounds out of scope for this fixture). Without a [Class
    // Bonus] discount, the full 3-reserve cost is paid; completing the 3rd reserve payment
    // cascades directly into the level-1 MZMAYCHOOSE (no separate opportunity-window PASS needed
    // for this card), which offers our own champion as an optional damage target.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF'], 'Counters' => ['level' => 1]]], // WATER lineage/element unlock, level 1
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'i7sbjy86ep'], // Cone of Frost, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Relentless Outburst: for every 6 damage counters on your champion, deal 1 damage to all other units ---
$fixtures['relentless-outburst-damage-burst'] = [
    'testedCards' => ['oobp8g4cpe'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Relentless Outburst
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Regression test for a fixed engine crash: activating Relentless Outburst called the
    // previously-undefined GetChampionDamage() function (see GrandArchiveSim/Custom/GameLogic.php),
    // which fatally crashed the game. GetChampionDamage() is now an alias for the existing
    // ChampionDamageCounters() helper, matching every other "[Damage N+]" card's convention.
    // Relentless Outburst's element is EXIA, so the starting champion's Subcards are patched with
    // a real EXIA champion (Dante, Hemomancer) to unlock element access, and the champion's
    // Damage is pre-patched to 6 so intdiv(6, 6) = exactly 1 damage burst. A Dungeon Guide (ALLY)
    // is seeded onto our own field so the "all other units" clause has a same-side target to
    // distinguish from our own excluded champion. Without a [Class Bonus] discount, the full
    // 3-reserve cost is paid; completing the 3rd reserve payment cascades directly into the
    // ability's own MZCHOOSE (which champion the [Class Bonus][Damage 35+] retaliation-buff
    // clause would apply to, out of scope here since Class Bonus is inactive) with no separate
    // opportunity-window PASS needed for this card.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['4FtNBFaOJp'], 'Damage' => 6]], // EXIA lineage/element unlock, 6 damage counters so 1 burst
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - same-side burst target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'oobp8g4cpe'], // Relentless Outburst, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Cryogenic Ritual: sacrifice an ally to summon a Core Fractal token ---
$fixtures['cryogenic-ritual-summon'] = [
    'testedCards' => ['FWinA77xF1'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Cryogenic Ritual
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Regression test for a fixed bug: activating Cryogenic Ritual never actually sacrificed the
    // ally its own additional cost requires (see GrandArchiveSim/Custom/GameLogic.php --
    // DoActivateCard now declares this cost the same way every other sacrifice-cost card does).
    // Cryogenic Ritual's element is WATER, so the starting champion's Subcards are patched with a
    // real WATER champion (Spirit of Water) to unlock element access. A Dungeon Guide (ALLY) is
    // seeded onto the field to serve as the sacrifice target. Materializing it opens a real
    // MZCHOOSE for the sacrifice target BEFORE reserve payment; choosing the Dungeon Guide pays
    // the full 2-reserve cost, then the ability itself (unaffected by this fix) summons a Core
    // Fractal token.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - sacrifice fuel
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'FWinA77xF1'], // Cryogenic Ritual, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Memory Invocation: banish a floating memory card from graveyard, draw two cards ---
$fixtures['memory-invocation-draw'] = [
    'testedCards' => ['io7maIjC4u'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Memory Invocation
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'ddv1au7t9m'], // Gentle Respite (unconditional Floating Memory) - cost fuel
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'io7maIjC4u'], // Memory Invocation, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Spurn to Ash: destroy target regalia with memory cost 1 or less (debug probe) ---
$fixtures['spurn-to-ash-destroy-regalia'] = [
    'testedCards' => ['ErH0lIBq4z'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Spurn to Ash
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Spurn to Ash's element is FIRE, matched by the default starting champion (Spirit of Fire),
    // so no lineage patch is needed. Necklace of Foresight (memory cost 1, REGALIA) is seeded onto
    // the opponent's field as the destroy target. Without a [Class Bonus] discount, the full
    // 3-reserve cost is paid; the target choice then opens a reactive opponent-response window
    // (declined via P2 PASS) before a second, real target MZCHOOSE resolves the destroy effect.
    // The generated ability code moves the target to "theirGraveyard" (relative to the activating
    // player), but since the target is REGALIA, GraveyardAddReplacement() transparently redirects
    // any REGALIA graveyard-add into that same player's banish zone instead -- and MZMove()
    // correctly resolves "that same player" as the object's actual owner (P2) throughout, not the
    // activating player (P1), so the destroyed regalia lands in its owner's banish zone as
    // expected. (A prior investigation reported this card's target vanishing entirely; that check
    // only looked at both players' graveyards and fields, not banish zones -- not an engine bug.)
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'lq2kkvoqk1'], // Necklace of Foresight (memory cost 1) - opponent's regalia target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ErH0lIBq4z'], // Spurn to Ash, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Life Essence Amulet: whenever an ally you control dies while it's not your turn, may banish to draw ---
$fixtures['life-essence-amulet-death-draw'] = [
    'testedCards' => ['1XegCUjBnY'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Windslice
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Regression test for a fixed bug: Life Essence Amulet's on-death offer was correctly queued
    // (DoAllyDestroyed adds a LifeEssenceAmuletOffer CUSTOM decision) but was silently popped and
    // discarded without ever running its handler. The decision is typically queued mid-cascade,
    // after the player has already answered an unrelated "PASS" earlier in the same combat-damage
    // resolution chain (e.g. declining the pre-damage Retaliate prompt); without dontSkipOnPass,
    // the decision queue's stale $lastDecision=="PASS" from that earlier, unrelated answer caused
    // this decision to be skipped too (see GrandArchiveSim/Custom/GameLogic.php -- the
    // AddDecision call for LifeEssenceAmuletOffer now passes dontSkipOnPass:1).
    // Life Essence Amulet's element is NORM, so no lineage patch is needed; it is seeded directly
    // onto P1's field (REGALIA convention) along with a Dungeon Guide (ALLY, 3 life) that will die
    // to trigger it. P2's champion Counters are patched with a 'potion_animate_power' override
    // (5, exceeding the ally's 3 life) so it can deal lethal combat damage without needing to
    // equip a weapon. P1 ends turn 1 so it becomes P2's turn (the "not your turn" condition holds
    // for P1); P2 declines the material-phase prompt, then declares an attack with their champion
    // against P1's Dungeon Guide (theirField-2 from P2's perspective) instead of P1's champion.
    // P1 declines the pre-damage Retaliate window; the lethal hit destroys the ally during P2's
    // turn, correctly triggering Life Essence Amulet's on-death offer for P1 (the non-active
    // player) despite the stale PASS earlier in the same cascade. Answering YES banishes the
    // amulet and draws a card.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => '1XegCUjBnY'], // Life Essence Amulet (REGALIA)
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY, 3 life) - death fodder
        ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['potion_animate_power' => 5]]], // give P2's champion lethal power
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Lorraine, Wandering Warrior: champion level-up debug probe ---
$fixtures['lorraine-wandering-warrior-levelup'] = [
    'testedCards' => ['DpHDGaX2Pn'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Windslice
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Investigated as a suspected engine bug (a prior session found champion level-up
    // materialization getting stuck in a loop, the champion left in a broken state) but turned
    // out to be correct behavior: that prior fixture never seeded any way to pay Lorraine's
    // 1-memory level-up cost (no memory-zone card, no floating-memory graveyard card), so
    // QueueMaterializePayment's own affordability check correctly failed and called
    // AutoUndoMaterializeCostFailure(), which calls LoadVersion() to roll the whole attempt back
    // to the pre-FSM snapshot -- not a bug, just an unpaid cost undoing the action, which then
    // looks like a stuck loop when the same now-still-legal material choice is attempted again
    // from the restored state. This fixture seeds a filler card directly into the memory zone as
    // real cost fuel. Lorraine, Wandering Warrior is level 1, one level above the default level-0
    // starting champion (Spirit of Fire), so she is a legal level-up target; champion-swap
    // materialization is only offered through the material-phase MZMAYCHOOSE at the start of a
    // turn, not via a generic FSM click at arbitrary times, so both players end their first turn
    // (P1 -> P2) to reach that prompt for P1's turn 3. Choosing her pays the 1-memory cost from
    // the seeded memory card and completes the swap in one step, preserving Spirit of Fire in her
    // lineage (Subcards).
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card in memory to pay Lorraine's 1-memory level-up cost
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Dante, Prodigal Swain: On Enter, summon an Elysian Test Subject token ---
$fixtures['dante-prodigal-swain-summon-token'] = [
    'testedCards' => ['apVtyt48u3'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Dante, Prodigal Swain
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Dante, Prodigal Swain's element is NORM (always playable, no lineage patch needed) and its
    // level (1) is exactly one above the default level-0 starting champion (Spirit of Fire), so
    // it's a legal level-up target with no element patching. Champion-swap materialization is only
    // offered through the material-phase MZMAYCHOOSE at the start of a turn, so both players end
    // their first turn (P1 -> P2) to reach that prompt on P1's next turn. Its printed cost is
    // 1 memory, so a filler card is seeded directly into myMemory as real cost fuel -- without it,
    // QueueMaterializePayment's own affordability check silently rolls the whole attempt back via
    // AutoUndoMaterializeCostFailure() (LoadVersion()), which looks like the choice was a no-op.
    // Choosing Dante, Prodigal Swain completes the swap and its On Enter ability
    // (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, enterAbilities["apVtyt48u3:0"])
    // unconditionally summons an Elysian Test Subject token (3DCP7WmBpx) onto the field.
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card in memory to pay Dante, Prodigal Swain's 1-memory level-up cost
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Pure Cytosynth: [Dante Bonus] On Enter, mill three then empower by water cards milled ---
$fixtures['pure-cytosynth-dante-bonus-empower'] = [
    'testedCards' => ['172utOanGk'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Pure Cytosynth
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Pure Cytosynth's On Enter (mill 3, then empower X = water element cards in graveyard) is
    // gated behind [Dante Bonus] (IsDanteBonusActive: champion name starts with "Dante") and its
    // element property is "EXALTED,WATER" -- CanPlayerMeetCardElementRequirements always requires
    // EXALTED specifically (auto-enabled only once another advanced element is enabled) plus at
    // least one of its other listed elements (WATER here). The starting champion's CardID is
    // patched directly to Dante, Hemomancer (element EXIA, an advanced element -- satisfies both
    // the Dante Bonus name check and unlocks EXALTED) and its Subcards are patched with Spirit of
    // Water (WATER) to unlock the card's other required element. X is computed from ALL water
    // element cards in the graveyard, not just the 3 milled this turn (verified live via a
    // temporary debug trace on the generated enterAbility closure -- with an empty graveyard, this
    // seed/shuffle happens to mill zero water cards, so the ability's own internal
    // Empower($player, 0, ...) call correctly no-ops per Empower()'s own `if($amount <= 0) return`
    // guard), so Spirit of Water (a real WATER card) is seeded directly into the graveyard as a
    // second, independent water source to make the empower amount deterministically non-zero
    // regardless of what the mill draws.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '4FtNBFaOJp', 'Subcards' => ['tafqldAGRF']]],
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'tafqldAGRF'], // Spirit of Water (WATER), guarantees empower X >= 1
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '172utOanGk'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Fulminator, Rising Storm: [Lorraine Bonus] enters with LV-2 static counters ---
$fixtures['fulminator-rising-storm-lorraine-bonus'] = [
    'testedCards' => ['F1JIgewvFI'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Fulminator, Rising Storm
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Fulminator's element is ARCANE and its Lorraine Bonus (IsLorraineBonusActive) requires
    // Lorraine, Wandering Warrior (DpHDGaX2Pn) specifically in lineage. The starting champion's
    // CardID is patched directly to Lorraine, Arclight Saber (level 3, ARCANE -- unlocks the
    // card's element) and its Subcards are patched with Lorraine, Wandering Warrior to satisfy the
    // Lorraine Bonus lineage check. On Enter puts max(0, LV-2) static counters on itself
    // (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, enterAbilities["F1JIgewvFI:0"]); at
    // champion level 3 that's 1 static counter.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'x9sSpjpP3G', 'Subcards' => ['DpHDGaX2Pn']]],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Surged Coordinator: [Class Bonus] On Enter static counters from LV + other statics - 3 ---
$fixtures['surged-coordinator-class-bonus-counters'] = [
    'testedCards' => ['6eWmfzAmWr'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Surged Coordinator
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Surged Coordinator's element is ARCANE and its [Class Bonus] requires a WARRIOR champion.
    // The starting champion's CardID is patched directly to Lorraine, Arclight Saber (level 3,
    // WARRIOR, ARCANE -- unlocks both the class bonus and the card's element) and is also given 1
    // static counter directly so it counts as "another object with a static counter" for the
    // ability's own count. On Enter puts max(0, LV + count - 3) static counters on itself
    // (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, enterAbilities["6eWmfzAmWr:0"]); at
    // champion level 3 with count=1 (the champion itself) that's 1 static counter.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'x9sSpjpP3G', 'Counters' => ['static' => 1]]],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '6eWmfzAmWr'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Honorable Vanguard: Floating Memory pays a champion level-up's memory cost from graveyard ---
$fixtures['honorable-vanguard-floating-memory'] = [
    'testedCards' => ['cWJqSwhKEQ'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Honorable Vanguard's only ability is the unconditional Floating Memory keyword (while
    // paying a memory cost, you may banish this card from your graveyard to pay for 1 of that
    // cost). This is tested by paying Lorraine, Wandering Warrior's 1-memory champion level-up
    // cost entirely from a Honorable Vanguard seeded directly into the graveyard, with no myMemory
    // filler seeded -- QueueMaterializeFloatingPaymentChoice
    // (GrandArchiveSim/Custom/MaterializeLogic.php) offers it as the sole payment source.
    'setup' => [
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'cWJqSwhKEQ'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Lorraine, Arclight Saber: On Enter LV static counters + 1 per banished arcane card ---
$fixtures['lorraine-arclight-saber-enter-counters'] = [
    'testedCards' => ['x9sSpjpP3G'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Arclight Saber
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Lorraine, Arclight Saber is level 3, one level above a level-2 champion. Rather than
    // grinding out two real prior level-ups, the starting champion's CardID is patched directly
    // to Lorraine, Blademaster (TJTeWcZnsQ, level 2) -- CanChampionLevelUpIntoCard only checks the
    // CURRENT champion's own printed CardLevel, not lineage, so this satisfies the "current+1"
    // legality gate for one real level-up into Lorraine, Arclight Saber. Her own On Enter ability
    // (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php, enterAbilities["x9sSpjpP3G:0"]) reads
    // PlayerLevel($player) *after* she is already the field champion, so it correctly returns her
    // own level (3) regardless of how she got there. Two ARCANE cards are seeded directly into
    // banishment so the ability's "for each of up to seven arcane element cards in your
    // banishment" clause adds 2 more, for 3+2=5 total static counters. Her 3-memory level-up cost
    // is paid from 3 filler memory-zone cards.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'TJTeWcZnsQ']], // Lorraine, Blademaster (level 2, WARRIOR) - level-up precondition
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myBanish', 'cardID' => 'F1JIgewvFI'], // Fulminator, Rising Storm (ARCANE) - banished arcane card #1
        ['player' => 1, 'zone' => 'myBanish', 'cardID' => '6eWmfzAmWr'], // Surged Coordinator (ARCANE) - banished arcane card #2
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Venous Core: additional materialize cost - sacrifice an Elysian ally ---
$fixtures['venous-core-sacrifice-cost'] = [
    'testedCards' => ['YTO70fFsBY'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Windslice
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
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
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '4FtNBFaOJp']], // become Dante, Hemomancer (EXIA unlock)
        ['player' => 1, 'zone' => 'myField', 'cardID' => '3DCP7WmBpx'], // Elysian Test Subject token - sacrifice fodder
        ['player' => 1, 'zone' => 'myMaterial', 'cardID' => 'YTO70fFsBY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-4', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Shieldroid: Floating Memory pays a champion level-up's memory cost from graveyard ---
$fixtures['shieldroid-floating-memory'] = [
    'testedCards' => ['qCTini03Bc'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Shieldroid's Floating Memory keyword (while paying a memory cost, you may banish this card
    // from your graveyard to pay for 1 of that cost) is tested the same way as
    // honorable-vanguard-floating-memory: pay Lorraine, Wandering Warrior's 1-memory champion
    // level-up cost entirely from a Shieldroid seeded directly into the graveyard, with no
    // myMemory filler seeded, so QueueMaterializeFloatingPaymentChoice
    // (GrandArchiveSim/Custom/MaterializeLogic.php) offers it as the sole payment source. Taunt
    // (the combat-targeting-priority half of Shieldroid's text) is a computed attack-declaration
    // restriction with no stored counter or flag to assert and is out of scope here.
    'setup' => [
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'qCTini03Bc'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Stalwart Shieldmate: Floating Memory pays a champion level-up's memory cost from graveyard ---
$fixtures['stalwart-shieldmate-floating-memory'] = [
    'testedCards' => ['eifnz0fgm3'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Same Floating Memory keyword and same test shape as shieldroid-floating-memory and
    // honorable-vanguard-floating-memory -- Stalwart Shieldmate shares identical printed text with
    // Shieldroid (Taunt + Floating Memory), so this is a second, independent card exercising the
    // same QueueMaterializeFloatingPaymentChoice payment path. Taunt is out of scope, same reason.
    'setup' => [
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'eifnz0fgm3'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Tariff Ring: Banish to tax attack declarations, activated during opponent's recollection ---
$fixtures['tariff-ring-attack-tax'] = [
    'testedCards' => ['xnrw8qq1uw'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Was previously abandoned after discovering that activateAbilityPrereqs["xnrw8qq1uw:0"]
    // (GrandArchiveSim/GeneratedCode/GeneratedMacroCode.php) compared GetCurrentPhase() against
    // the literal "RECOLLECTION", a string the engine's real phase codes (GrandArchiveSim/
    // TurnStates.php: WU->MAT->BREC->REC->DRAW->MAIN) never produce -- the REC_START opportunity
    // window (where this ability is actually offered) opens while the phase code is "BREC"
    // (GrandArchiveSim/Custom/GameLogic.php's BeforeRecollectionPhase()), not "REC" and never
    // "RECOLLECTION". Root-caused to 4 card_abilities DB rows authored with the wrong literal
    // (Database/migrations/12_grand_archive_recollection_phase_fix.sql) plus 3 matching
    // hand-authored checks in GrandArchiveSim/Custom/{GameLogic,OpportunityLogic}.php. Now that
    // both are fixed and GeneratedMacroCode.php regenerated, Tariff Ring's ability is reachable:
    // it's seeded directly onto P1's field (an Item ability, not a hand play), P1 passes through
    // turn 1's Main/BeforeEnd/BeforeEndOpportunity windows, and turn auto-advances into P2's turn
    // 2 BeforeRecollection phase, where GetPlayableOpportunityChoices offers Tariff Ring to P1
    // (the non-turn-player) as a MZMAYCHOOSE fast option. Selecting it resolves the "Banish"
    // macro: AddGlobalEffects($player, "xnrw8qq1uw") and Tariff Ring moves from field to banish
    // (its own activation cost, "Banish CARDNAME:"). The resulting attack-tax computation
    // (GrandArchiveSim/Custom/CombatLogic.php's $tariffRingTax) is a combat-declaration-time
    // computed cost with no independently stored counter beyond the global effect itself, so it's
    // out of scope here -- the global effect's presence is the assertable, semantic proof that the
    // ability actually fired.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'xnrw8qq1uw'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1@Activate-0@Banish', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Return Stroke: Floating Memory pays a champion level-up's memory cost from graveyard ---
$fixtures['return-stroke-floating-memory'] = [
    'testedCards' => ['TZym0IOInK'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Same Floating Memory keyword and same test shape as shieldroid-floating-memory /
    // stalwart-shieldmate-floating-memory / honorable-vanguard-floating-memory. Return Stroke is an
    // ATTACK card, but Floating Memory only cares about paying a memory cost from the graveyard --
    // unrelated to its own reserve cost or to it ever being played as an attack -- so the same
    // champion-level-up-from-graveyard shape applies unchanged. Its "[Class Bonus] +LVPOWER" static
    // buff is a computed combat-time value with no independently stored counter, so it's out of
    // scope here.
    'setup' => [
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'TZym0IOInK'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Emberslash: On Attack, may discard a card to draw a card ---
$fixtures['emberslash-discard-draw'] = [
    'testedCards' => ['0xylS3OcNa'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        // Emberslash's on-attack effect is gated behind [Class Bonus] (IsClassBonusActive), which
        // reads EffectiveCardClasses() -- CardClasses($obj->CardID) unless a Counters['_overrides']
        // ['classes'] override is present (GrandArchiveSim/Custom/GameLogic.php:21116-21121). The
        // fresh level-0 starting champion (Spirit of Fire) is class SPIRIT, not WARRIOR, so patch
        // the override directly (same mechanism a real in-game class-change effect would use)
        // rather than scripting a full champion level-up.
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['_overrides' => ['classes' => 'WARRIOR']]]],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '0xylS3OcNa'], // Emberslash, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Conductive Strike: Class Bonus On Hit, pay 2 to put static counters on arcane objects ---
$fixtures['conductive-strike-static-counters'] = [
    'testedCards' => ['dDOMoeCJyK'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        // Same WARRIOR class-bonus override as emberslash-discard-draw. Conductive Strike is also
        // ARCANE element, which CanPlayerMeetCardElementRequirements checks separately via
        // GetChampionLineage() (walks Subcards, not the Counters override) -- same technique as
        // luxem-sight-draw -- so patch Subcards to include an ARCANE champion in the lineage. Use
        // Lorraine, Arclight Saber (x9sSpjpP3G): WARRIOR class AND ARCANE element, the actual
        // champion this starter deck is themed around.
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['_overrides' => ['classes' => 'WARRIOR']], 'Subcards' => ['x9sSpjpP3G']]],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'blqryebvwj'], // Storm Slime (ARCANE ally) - static counter target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'dDOMoeCJyK'], // Conductive Strike, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Stoked Slice: Class Bonus On Attack, banish two fire cards from graveyard to buff allies ---
$fixtures['stoked-slice-rally-allies'] = [
    'testedCards' => ['6wiHKD52Lw'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        // Same WARRIOR class-bonus override as emberslash-discard-draw. Stoked Slice is FIRE
        // element, and the starting champion (Spirit of Fire) is already FIRE, so no lineage patch
        // is needed for the element gate here (unlike conductive-strike-static-counters' ARCANE).
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['_overrides' => ['classes' => 'WARRIOR']]]],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - +1 power buff target
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'pk9xycwz9g'], // Cell Handler (FIRE ally) #1 - banish fodder
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'pk9xycwz9g'], // Cell Handler (FIRE ally) #2 - banish fodder
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '6wiHKD52Lw'], // Stoked Slice, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0&myGraveyard-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Aenean Frozen Shunt: end the combat phase as a REACTION during the COMBAT_DAMAGE window ---
$fixtures['aenean-frozen-shunt-end-combat'] = [
    'testedCards' => ['Fkpr1hCUGF'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        // Aenean Frozen Shunt is WATER element (CanPlayerMeetCardElementRequirements ->
        // GetChampionLineage() -> Subcards, same mechanism as conductive-strike-static-counters'
        // ARCANE patch) and its [Class Bonus][Level 4+] draw needs both a CLERIC/MAGE class match
        // (Counters['_overrides']['classes'], same as the WARRIOR overrides elsewhere) and
        // PlayerLevel() >= 4, which ObjectCurrentLevel computes as CardLevel + a "level" counter
        // (GrandArchiveSim/Custom/GameLogic.php:12933-12936) -- so patch a level counter directly
        // instead of scripting real champion level-ups.
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['_overrides' => ['classes' => 'MAGE'], 'level' => 4], 'Subcards' => ['tafqldAGRF']]],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '0xylS3OcNa'], // Emberslash, seeded to a known hand slot (any attack works)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'Fkpr1hCUGF'], // Aenean Frozen Shunt, seeded to a known hand slot
    ],
    // Emberslash is played with no class-bonus override this time (only MAGE is granted), so its
    // own on-attack trigger stays inert and doesn't add an extra decision to the sequence -- it's
    // purely a vehicle to get combat active. Declaring the attack fires OnAttack, then
    // FinalizeAttackDeclaration grants the COMBAT_DAMAGE opportunity window to the turn player
    // (GrandArchiveSim/Custom/CombatLogic.php:2902) before damage resolves; Aenean Frozen Shunt
    // (REACTION speed) is offered there as a fast hand card and, once activated, ends combat --
    // asserted by damage never landing on the defending champion.
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-6', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- FlameTech BladeCore: Sword Weapon Link, entering the field linked to a target Sword weapon ---
$fixtures['flametech-bladecore-weapon-link'] = [
    'testedCards' => ['aAJliPQT3F'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // FlameTech BladeCore is EXALTED element -- CanPlayerMeetCardElementRequirements requires
    // EXALTED to be explicitly enabled, which GetPlayerEnabledElements does automatically once any
    // OTHER advanced element is enabled (GrandArchiveSim/Custom/GameLogic.php:19410-19419), so the
    // same ARCANE Subcards lineage patch used by conductive-strike-static-counters unlocks it too.
    // Its Weapon Link is generic engine machinery ($WeaponLink_Cards, GameLogic.php:914-916,
    // 2239-2248): activating it with a valid Sword weapon on the field queues an MZCHOOSE to pick
    // the link target, and CreateWeaponLink stores the link on BOTH sides -- the weapon's Subcards
    // gains the linking card's CardID, and the linking object's Counters gets
    // 'linkedToWeapon' => weapon's CardID (GameLogic.php:22718-22732) -- both are plain stored
    // properties, directly assertable. The "+2POWER" and "put a durability counter on discard"
    // clauses read that link at computed-value/listener-dispatch time with no additional stored
    // state of their own, so they're out of scope here. [Lorraine Bonus] Link Shield is a distinct,
    // narrower conditional (IsLorraineBonusActive) not exercised by this fixture.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G']]], // Lorraine, Arclight Saber (ARCANE) - unlocks EXALTED too
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'm31WVJ9F04'], // Clarent, Sword of Peace (WEAPON, SWORD) - link target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'aAJliPQT3F'], // FlameTech BladeCore, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Brooch X Ultra: Ally Link, entering the field linked to a target ally ---
$fixtures['brooch-x-ultra-ally-link'] = [
    'testedCards' => ['3Gx9ByIl9t'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Same ARCANE Subcards lineage patch as conductive-strike-static-counters. Ally Link is
    // generic engine machinery ($AllyLink_Cards, a plain boolean set at
    // GrandArchiveSim/Custom/GameLogic.php:864) mirroring Weapon Link's shape
    // (flametech-bladecore-weapon-link): activating with a valid ally on the field queues an
    // MZCHOOSE to pick the link target, and CreateAllyLink stores the link on both sides as plain
    // properties (GameLogic.php:22572-22587) -- the ally's Subcards gains the linking card's
    // CardID, and the linking object's Counters gets 'linkedToAlly' => the ally's CardID. The
    // "+2POWER" and "On Attack: banish a random memory card to draw" clauses read that link at
    // computed-value/listener-dispatch time with no additional stored state, so they're out of
    // scope here.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G']]], // Lorraine, Arclight Saber (ARCANE)
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - link target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '3Gx9ByIl9t'], // Brooch X Ultra, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Keen Tidebinder: Class Bonus, first empower each turn gets +2 POWER until end of turn ---
$fixtures['keen-tidebinder-first-empower'] = [
    'testedCards' => ['ZmBQAOb9gj'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Empower() (GrandArchiveSim/Custom/GameLogic.php:18438) is a shared engine primitive many
    // different cards' own abilities call into -- it's not a base action every deck can trigger,
    // so this borrows Dante, Hemomancer's own innate activated ability ("(X), REST: deal X
    // unpreventable damage to Dante and empower X") as the trigger, by patching the starting
    // champion's CardID directly to Dante (4FtNBFaOJp) rather than the Subcards-lineage trick used
    // elsewhere -- GetChampionLineage() always includes the champion object's own CardID first
    // (GameLogic.php:19351-19358), so a direct CardID swap satisfies element/class checks for
    // whatever the champion's OWN card needs, with no separate override required. Keen Tidebinder
    // only needs to be present on the field when Empower resolves (its trigger reads the field
    // directly, GameLogic.php:18478-18483, no card_activated macro of its own), so it's seeded
    // directly rather than played from hand -- sidestepping its own WATER element / reserve cost
    // requirements entirely. The ZmBQAOb9gj_POWER TurnEffect Empower() stamps is the assertable,
    // semantic proof; the "[Class Bonus] Floating Memory" clause is the same graveyard-payment
    // mechanic already proven repeatedly elsewhere (shieldroid-floating-memory etc.) and is out of
    // scope here to avoid duplicating that coverage.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '4FtNBFaOJp']], // Dante, Hemomancer
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'ZmBQAOb9gj'], // Keen Tidebinder (ALLY) - empower trigger target
    ],
    // Dante's ability has no phase restriction, so it's offered as a fast option at every
    // opportunity window (matching aenean-frozen-shunt-end-combat's discovery) -- it's declined at
    // turn 1's end-step windows and activated instead during P1's own turn-2 BeforeRecollection
    // window, so that after it resolves, auto-advance continues to a stable Main phase (which never
    // auto-advances without an explicit PASS) rather than cascading straight to end of turn, where
    // Keen Tidebinder's "until end of turn" TurnEffect would already have expired before the
    // fixture's final snapshot is captured.
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0@Activate-0@Empower', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Spirit of Fire: On Enter, draw seven cards ---
$fixtures['spirit-of-fire-on-enter-draw'] = [
    'testedCards' => ['LMyKyVC2O9'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Spirit of Fire is the default starting champion for every fixture in this file -- its "On
    // Enter: Draw seven cards" trigger fires during pregame setup, before any actions run, which
    // is exactly why every other fixture's initial gamestate already shows a 7-card starting hand.
    // This fixture makes that implicit, constantly-reconfirmed coverage explicit for the backlog
    // tool. The assertion framework can't evaluate step 0 (RegressionEvaluateAssertion needs the
    // full engine bootstrap an action triggers -- GetZoneObject() is undefined that early), so one
    // harmless P1 Main-phase pass is included purely to produce an assertable step; it doesn't
    // touch hand count.
    'setup' => [],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Spirit of Water: On Enter, draw seven cards ---
$fixtures['spirit-of-water-on-enter-draw'] = [
    'testedCards' => ['tafqldAGRF'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Water
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Same trigger and same reasoning as spirit-of-fire-on-enter-draw, swapping the deck's starting
    // champion to Spirit of Water instead of patching CardID post-hoc -- a raw property patch
    // wouldn't retroactively fire the On Enter trigger, since it only runs at actual placement time
    // during pregame setup, not whenever a card happens to carry that CardID.
    'setup' => [],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Banner Knight: [Class Bonus][Level 2+] Other allies and weapons get +1 POWER ---
$fixtures['banner-knight-class-bonus-power-buff'] = [
    'testedCards' => ['IAkuSSnzYB'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Banner Knight's field-presence buff (GameLogic.php:11856-11871) is purely computed at
    // ObjectCurrentPower() read time -- no flag or counter is ever stamped on the buffed object, so
    // card_property_equals has nothing to assert against. Proven instead via a new
    // computed_power_equals assertion type (Core/RegressionTestFramework.php) that calls
    // ObjectCurrentPower() directly, same as the engine's own combat/render code paths do. [Class
    // Bonus][Level 2+] requires a WARRIOR champion at level 2+; the champion's CardID is patched
    // directly to Lorraine, Arclight Saber (level 3, WARRIOR) -- the same technique used by
    // surged-coordinator-class-bonus-counters. Dungeon Guide (base POWER 1) is seeded onto the field
    // as the buffed ally; expected computed power is 1 + 1 = 2.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'x9sSpjpP3G']],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'IAkuSSnzYB'], // Banner Knight
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide, buffed ally
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Lorraine, Honed Operative: On Enter, banish up to 3 random memory cards and draw for each ---
// Previously abandoned: reaching her requires two sequential real champion level-ups (0 -> 1 -> 2),
// and investigating an "unexplained memory/hand discrepancy" after the first level-up was misread
// as cross-tool RNG nondeterminism. The real mechanism (GrandArchiveSim/Custom/GameLogic.php,
// RecollectionPhase(), comment "Must run BEFORE memory is returned to hand") is core game design:
// at the start of EVERY turn but each player's own turn 1, Recollection returns the ENTIRE memory
// zone to hand, before that turn's own Main phase. A one-time memory seed at pregame setup survives
// to fund the first level-up (turn 2's Materialize phase, which runs BEFORE turn 2's Recollection),
// but is fully returned to hand by turn 3's Recollection before the second level-up's Materialize
// phase can spend it -- memory has to be rebuilt during turn 2's Main phase (after turn 2's own
// Recollection already happened) to still be present for turn 3's Materialize phase. Harness Mana
// (G2XFRE8rFX, 0 reserve cost, "Put any amount of cards from your hand into your memory") is used
// as that refill: 0 reserve keeps hand math simple, and its MZMAYCHOOSE-loop shape (CardDQHandlers.php,
// customDQHandlers["HarnessManaLoop"]) is answered by repeating a myHand-0 choice, then declining.
// Separately, her own ability handler (CardDQHandlers.php,
// customDQHandlers["LorraineHonedOperativeBanishMemory"]) really was using plain PHP shuffle()
// instead of the engine's EngineShuffle() wrapper, bypassing deterministic RNG entirely -- fixed
// directly in that tracked, hand-maintained file (it is NOT one of the generated/gitignored
// per-app engine files) as part of landing this fixture.
$fixtures['lorraine-honed-operative-banish-draw'] = [
    'testedCards' => ['UsX7t4lXfX'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Lorraine, Honed Operative
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // 1 filler card seeded directly into myMemory pays Wandering Warrior's 1-memory level-up cost
    // on turn 2 (before turn 2's own Recollection has run). Harness Mana is seeded to a known hand
    // slot so it's available without depending on the shuffle: played during turn 2's Main phase, it
    // converts 3 more hand cards into memory, which -- unlike the pregame seed -- survive turn 3's
    // Recollection (already passed for turn 2) to fund Honed Operative's 2-memory level-up cost on
    // turn 3, with 1 left over for her own On Enter to actually banish and draw.
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'G2XFRE8rFX'], // Harness Mana
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], // level up to Wandering Warrior
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''], // activate Harness Mana
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // convert hand card 1/3 to memory
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // convert hand card 2/3 to memory
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // convert hand card 3/3 to memory
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // stop the Harness Mana loop
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // P2 declines their own turn-2 materialize choice
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], // level up to Honed Operative
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '1', 'chkInput' => [], 'inputText' => ''], // banish 1 (all that's available) from memory
    ],
];

// --- Rising Tides: [Class Bonus][Level 3+] Draw a card into your memory ---
$fixtures['rising-tides-class-bonus-level3-draw-memory'] = [
    'testedCards' => ['y6q4goxi8a'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // IsClassBonusActive(["MAGE"]) && PlayerLevel($player) >= 3 (GrandArchiveSim/GeneratedCode/
    // GeneratedMacroCode.php's cardActivatedAbilities["y6q4goxi8a:0"]) are independent checks: the
    // level check reads the main champion object (patched to Lorraine, Arclight Saber, level 3,
    // same as wisdoms-reprise-level3-draw-memory), and the class check scans ALL physical field
    // objects for a MAGE (same physically-seeded Kongming, Fel Eidolon as
    // devoted-bloomweaver-class-bonus-empower/vernal-talisman-preserve-draw) -- two separate
    // objects. Unlike NORM (always enabled, GameLogic.php's CanPlayerMeetCardElementRequirements /
    // IsNormOnlyElementProperty), Rising Tides' WATER element is NOT free: GetPlayerEnabledElements()
    // scans GetChampionLineage() = [main champion CardID] + its Subcards, and neither
    // x9sSpjpP3G (ARCANE) nor the physically-seeded Fel Eidolon (TERA, not in the main champion's
    // Subcards anyway) grants WATER -- discovered empirically after the FSM click on Rising Tides
    // silently no-opped (DoActivateCard's !CanPlayerUseCardElement(...) early-return,
    // GameLogic.php ~line 2093). Fixed by ALSO patching the main champion's Subcards with Spirit of
    // Water (a WATER-elemental champion), independent of the CardID patch that provides the level.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'x9sSpjpP3G', 'Subcards' => ['tafqldAGRF']]], // Lorraine, Arclight Saber (level 3) + Spirit of Water (WATER element access) in lineage
        ['player' => 1, 'zone' => 'myField', 'cardID' => '7x2v4tdop1'], // Kongming, Fel Eidolon (MAGE CHAMPION), physically seeded for Class Bonus
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'y6q4goxi8a'], // Rising Tides
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay 2-reserve cost, card 1/2
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay 2-reserve cost, card 2/2
    ],
];

// --- Tera Sight: Preserve. Draw a card. ---
$fixtures['tera-sight-preserve-draw'] = [
    'testedCards' => ['2Ojrn7buPe'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // TERA (advanced element), 0 reserve cost. Same Subcards element-access patch as
    // vernal-talisman-preserve-draw/devoted-bloomweaver-class-bonus-empower (Kongming, Fel Eidolon),
    // no physical champion needed this time since there's no Class Bonus gate. Preserve (put this
    // card into its owner's material deck preserved as it resolves, instead of the graveyard) is a
    // generic keyword layered on top of the card's own "Draw($player, 1)" macro.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '2Ojrn7buPe'], // Tera Sight
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Kongming, Fel Eidolon: On Enter, Recover X (X = TERA cards in banishment) ---
$fixtures['kongming-fel-eidolon-enter-recover-tera-banished'] = [
    'testedCards' => ['7x2v4tdop1'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Kongming, Fel Eidolon
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // A real level-up event is required to fire a champion's own On Enter (a static CardID patch
    // does not fire triggers, per kongming-wayward-maven-enter-shifting-currents). Fel Eidolon is
    // level 3 (3-memory cost), so the starting champion is patched to a level-2 champion first
    // (satisfies CanChampionLevelUpIntoCard's targetLevel===currentLevel+1 gate) with Fel Eidolon's
    // own CardID also placed in Subcards for TERA element access (GetLegalMaterializeChoices also
    // gates champion targets on CanPlayerUseCardElement, same technique as tera-sight-preserve-draw).
    // Pre-damaging the champion object lets Recover X be observed as a Damage reduction once the
    // level-up completes on the same field object.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '84YTQPTvar', 'Subcards' => ['7x2v4tdop1'], 'Damage' => 5]],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // pays the 3-memory level-up cost, card 1/3
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // card 2/3
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // card 3/3
        ['player' => 1, 'zone' => 'myBanish', 'cardID' => 'qktid6zlyt'], // TERA element card #1 in banishment
        ['player' => 1, 'zone' => 'myBanish', 'cardID' => 'jwanjcy453'], // TERA element card #2 in banishment
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], // level up to Fel Eidolon (probed below)
    ],
];

// --- Ardent Cloudstriker: +3 POWER while facing West and attacking a champion ---
// --- Planar Abyss: Delayed - at beginning of next recollection phase, destroy all non-champion
// objects, then if SC South, deal 10 damage to each opponent champion ---
$fixtures['planar-abyss-delayed-destroy-south-damage'] = [
    'testedCards' => ['qexcwmx2ug'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Planar Abyss's own activation just sets a delayed PLANAR_ABYSS_PENDING global effect
    // (cardActivatedAbilities['qexcwmx2ug:0'], GeneratedMacroCode.php ~20854-20860); the actual
    // destroy-all + conditional-damage resolution happens inside RecollectionPhase() (GameLogic.php
    // ~9676-9699) the next time this player's recollection phase begins. Setting the flag directly
    // via the 'globalEffect' setup step (same technique as blistering-insurgent's LEVELED_UP_THIS_TURN)
    // avoids scripting the card's own 12-reserve activation, which is out of scope for this fixture.
    // Dungeon Guide (an ALLY) is seeded onto both fields as the "non-champion object" target; SC
    // faces South so the champion-damage half is also exercised in the same fixture.
    'setup' => [
        ['player' => 1, 'globalEffect' => 'PLANAR_ABYSS_PENDING'],
        ['player' => 1, 'zone' => 'myMastery', 'cardID' => 'qh5mpkyl60', 'setProperties' => ['Direction' => 'SOUTH']], // Shifting Currents facing South
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY), P1's own non-champion object to be destroyed
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY), P2's non-champion object to be destroyed
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline materialize offer, continue phase advance through recollection
    ],
];

// --- Taiji of Crystal Strategems: South->East transition, may rest to deal 3 damage to opponent's champion ---
$fixtures['taiji-crystal-strategems-south-east-rest-damage'] = [
    'testedCards' => ['l17uc67eaq'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // South->East is adjacent (IsAdjacentDirection: SOUTH<->EAST/WEST), so this reuses the same
    // Fel Eidolon Inherited "adjacent direction on Spell activation" trigger mechanism as
    // hydroguard-retainer-north-west-draw, just starting from SOUTH and answering "EAST". The
    // transition callback (GameLogic.php ~20630-20636) then queues a YES/NO "rest to deal 3
    // damage?" decision, answered YES.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock + Fel Eidolon Inherited trigger
        ['player' => 1, 'zone' => 'myMastery', 'cardID' => 'qh5mpkyl60', 'setProperties' => ['Direction' => 'SOUTH']], // Shifting Currents facing South
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'l17uc67eaq'], // Taiji of Crystal Strategems
        ['player' => 1, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '2Ojrn7buPe'], // Tera Sight, to trigger the Inherited SC choice on activation
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'EAST', 'chkInput' => [], 'inputText' => ''], // choose adjacent direction EAST
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''], // rest Taiji to deal 3 damage
    ],
];

// --- Spirited Neophyte: On Attack, if facing North, Empower 2 ---
$fixtures['spirited-neophyte-north-attack-empower'] = [
    'testedCards' => ['ekplmih8ra'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Same real attack-declaration sequence as ardent-cloudstriker-west-attack-champion-buff. Unlike
    // Ardent Cloudstriker's transient computed-power bonus, Empower() tags the CHAMPION object with a
    // TurnEffects entry that persists after combat resolves (it isn't cleared by the CombatAttacker/
    // CombatTarget cleanup), so it's directly observable in the post-action snapshot.
    'setup' => [
        ['player' => 1, 'zone' => 'myMastery', 'cardID' => 'qh5mpkyl60', 'setProperties' => ['Direction' => 'NORTH']], // Shifting Currents facing North
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'ekplmih8ra'], // Spirited Neophyte
        ['player' => 1, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can attack
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline materialize offer
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Spirited Neophyte
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
    ],
];

$fixtures['ardent-cloudstriker-west-attack-champion-buff'] = [
    'testedCards' => ['4kpotk5hvr'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Real attack-declaration sequence, same shape as blistering-insurgent-attack-buff: the 2-pass
    // cycle (P1 pass, P2 pass) already reaches P1's global turn 3 (their own 2nd turn), past Rule
    // 1.h's turn-1 attack lock. Ardent Cloudstriker is seeded directly onto the field (bypassing its
    // 5-reserve cost) with Status patched awake (2) so it can declare an attack immediately.
    'setup' => [
        ['player' => 1, 'zone' => 'myMastery', 'cardID' => 'qh5mpkyl60', 'setProperties' => ['Direction' => 'WEST']], // Shifting Currents facing West
        ['player' => 1, 'zone' => 'myField', 'cardID' => '4kpotk5hvr'], // Ardent Cloudstriker
        ['player' => 1, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can attack
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline materialize offer
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Ardent Cloudstriker
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
    ],
];

// --- Kongming, Ascetic Vice: On Enter Empower 3 + Inherited N->S draw (Kongming Bonus, at end phase) ---
$fixtures['kongming-ascetic-vice-enter-empower-and-inherited-draw'] = [
    'testedCards' => ['a01pyxwo25'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Kongming, Ascetic Vice
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'apVtyt48u3']], // level-1 champion (Dante, Prodigal Swain), satisfies the level-1->2 gate
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // pays the 2-memory level-up cost, card 1/2
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // card 2/2
        ['player' => 1, 'zone' => 'myMastery', 'cardID' => 'qh5mpkyl60', 'setProperties' => ['Direction' => 'NORTH']], // Shifting Currents facing North
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], // level up to Ascetic Vice, fires On Enter Empower 3
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // reach BeforeEndPhase -> Kongming Bonus SC "any direction" offer
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'SOUTH', 'chkInput' => [], 'inputText' => ''], // choose SOUTH to fire the Inherited N->S transition (probed below)
    ],
];

// --- Hydroguard Retainer: Shifting Currents North->West transition draws a card ---
$fixtures['hydroguard-retainer-north-west-draw'] = [
    'testedCards' => ['0qm7n87o4s'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Direction-CHANGE triggers ($shiftingCurrentsTransitions in GameLogic.php, keyed "FROM->TO")
    // only fire through ChangeShiftingCurrents(), which is reached in a controlled way by activating
    // any Spell card while Kongming, Fel Eidolon is in the champion's lineage (its own Inherited
    // ability queues an ICONCHOICE "adjacent direction" decision on every Spell activation, GameLogic.php
    // ~5703-5708) -- same Subcards lineage-patch technique as tera-sight-preserve-draw, reused here
    // purely as a trigger mechanism rather than for its own sake. ICONCHOICE's response is just the
    // chosen direction string verbatim (goldfish default picks options[0], GameLogic.php:261-264).
    // Shifting Currents seeded at NORTH; Tera Sight (0 reserve TERA Spell) is activated to reach the
    // choice, which is answered "WEST" (adjacent to NORTH) to fire the NORTH->WEST transition.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock + Fel Eidolon Inherited trigger
        ['player' => 1, 'zone' => 'myMastery', 'cardID' => 'qh5mpkyl60', 'setProperties' => ['Direction' => 'NORTH']], // Shifting Currents facing North
        ['player' => 1, 'zone' => 'myField', 'cardID' => '0qm7n87o4s'], // Hydroguard Retainer
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '2Ojrn7buPe'], // Tera Sight, to trigger the Inherited SC choice on activation
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-8!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'WEST', 'chkInput' => [], 'inputText' => ''], // choose adjacent direction WEST
    ],
];

// --- Formidable Youxia: As long as Shifting Currents face East, +2 LIFE ---
$fixtures['formidable-youxia-east-life-buff'] = [
    'testedCards' => ['acmde97dbu'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Purely computed at ObjectCurrentHP() read time (GrandArchiveSim/Custom/GameLogic.php:13538-
    // 13542, ~13740), same shape as Banner Knight's power buff -- no stored flag to assert against,
    // so a new generic computed_life_equals assertion type (Core/RegressionTestFramework.php,
    // mirroring computed_power_equals) was added. Shifting Currents mastery is a myMastery zone card
    // (CardID qh5mpkyl60) with a Direction property (established by
    // kongming-wayward-maven-enter-shifting-currents), seeded directly facing EAST. Base LIFE 2 + 2
    // = 4.
    'setup' => [
        ['player' => 1, 'zone' => 'myMastery', 'cardID' => 'qh5mpkyl60', 'setProperties' => ['Direction' => 'EAST']],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'acmde97dbu'], // Formidable Youxia
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay 2-reserve cost, card 1/2
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay 2-reserve cost, card 2/2
    ],
];

// --- Wisdom's Reprise: Glimpse 3. [Level 3+] Draw a card into memory. ---
$fixtures['wisdoms-reprise-level3-draw-memory'] = [
    'testedCards' => ['lvmj48fn9p'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // [Level 3+] gate reads PlayerLevel() directly, satisfied by the same static champion CardID
    // patch technique as banner-knight-class-bonus-power-buff (Lorraine, Arclight Saber, level 3) --
    // no real level-up sequence or On Enter trigger needed since this reads the champion's level,
    // not anything about Wisdom's Reprise's own controller lineage. Glimpse 3's own MZREARRANGE
    // decision must be answered (submitting the same "Top=...;Bottom=" param verbatim, same
    // technique as idle-thoughts-glimpse-4) before QueueDrawIntoMemoryAfterGlimpse's queued draw can
    // resolve -- confirmed via direct probe against this exact deck/seed.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'x9sSpjpP3G']], // Lorraine, Arclight Saber, level 3
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'lvmj48fn9p'], // Wisdom's Reprise
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay 1-reserve cost
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Idle Thoughts: Glimpse 4, keep original order (shared with Jin Starter Deck) ---
$fixtures['idle-thoughts-glimpse-4'] = [
    'testedCards' => ['rWhFC8XBaH'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'rWhFC8XBaH'], // Idle Thoughts, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay 1-reserve cost
        // MZREARRANGE response: submit the same "Top=...;Bottom=" param verbatim to keep original
        // order (same no-op default GoldfishChooseAction uses for this decision type,
        // GrandArchiveSim/Custom/GameLogic.php:254-255) -- confirmed via direct probe against this
        // exact deck/seed since the glimpsed card IDs depend on the deterministic shuffle.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Devoted Bloomweaver: [Class Bonus] On Enter, Empower 2 ---
$fixtures['devoted-bloomweaver-class-bonus-empower'] = [
    'testedCards' => ['yqm3l6lbns'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Devoted Bloomweaver is a TERA (advanced element) ALLY with a MAGE Class Bonus On Enter, same
    // split as vernal-talisman-preserve-draw: the starting champion's Subcards are patched with a
    // real TERA champion (Kongming, Fel Eidolon) for element access, and that same champion is ALSO
    // physically seeded onto the field so IsClassBonusActive(["MAGE"]) is satisfied.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => '7x2v4tdop1'], // Kongming, Fel Eidolon (MAGE CHAMPION), physically seeded for Class Bonus
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'yqm3l6lbns'], // Devoted Bloomweaver, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay 2-reserve cost, card 1/2
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay 2-reserve cost, card 2/2
    ],
];

// --- Sweet Ambrosia: Banish self to recover 3 damage from the champion ---
$fixtures['sweet-ambrosia-banish-recover'] = [
    'testedCards' => ['dgyduwh84p'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Field items with an activated ability are not clickable via a plain myField-N!FSM! action
    // (ActionMap's "myField" case only handles attack declarations, GrandArchiveSim/Custom/
    // GameLogic.php:1289-1296) -- their ability is offered as a fast-action MZMAYCHOOSE opportunity
    // once the turn player attempts to pass (GetPlayableOpportunityChoices/GetPlayableFastAbilities,
    // OpportunityLogic.php), answered with the encoded "{mzID}@Activate-{abilityIndex}@{label}"
    // choice string (same shape as tariff-ring-attack-tax's response).
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'dgyduwh84p'], // Sweet Ambrosia
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Damage' => 5]], // pre-damage champion
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1@Activate-0@Banish', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Kongming, Wayward Maven: On Enter, gain the Shifting Currents mastery ---
$fixtures['kongming-wayward-maven-enter-shifting-currents'] = [
    'testedCards' => ['346vgwz3y4'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Kongming, Wayward Maven
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // pays the 1-memory level-up cost
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], // level up to Wayward Maven
    ],
];

// --- Steel Halberd: [Class Bonus] +1 POWER ---
$fixtures['steel-halberd-class-bonus-power'] = [
    'testedCards' => ['fvnvknj4dd'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // [Class Bonus] scans ALL physical field objects, independent of the main champion (established
    // technique, e.g. devoted-bloomweaver-class-bonus-empower): a physically-seeded WARRIOR champion
    // (Jin, Fate Defiant) satisfies it. Base power 1 + 1 from the bonus.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'zd8l14052j'], // Jin, Fate Defiant (WARRIOR), for Class Bonus
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'fvnvknj4dd'], // Steel Halberd
    ],
    // A step-0 assertion can't run (the engine's game state isn't parsed into memory until the
    // first action executes, DevTools/RunIntegrationTests.php:158 vs 150/167) -- a harmless Pass
    // forces that load so the computed-power assertion can run at step 1.
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Shuang Ji of Sacrifice: +1 POWER per five damage on your champion ---
// (Its own On Enter -- "may deal 5 unpreventable damage to your champion to draw a card" -- turned
// out to require a real hand-activation materialize sequence that vanishes the card instead of
// placing it on the field for this WEAPON regalia subtype, unlike ally/item materializes elsewhere
// in this file; that's a separate investigation and out of scope here. Only the always-on computed
// power bonus is covered, using the same direct-field-seed technique as steel-halberd-class-bonus-power.)
$fixtures['shuang-ji-sacrifice-champion-damage-power'] = [
    'testedCards' => ['y1tyo32voa'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Damage' => 12]], // 12 damage on champion
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'y1tyo32voa'], // Shuang Ji of Sacrifice
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Bloodbond Bladesworn: [Class Bonus] +1 POWER per 10 damage counters on champion ---
$fixtures['bloodbond-bladesworn-class-bonus-champion-damage-power'] = [
    'testedCards' => ['blyb6fd6vy'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'zd8l14052j'], // Jin, Fate Defiant (WARRIOR), for Class Bonus
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Damage' => 25]], // 25 damage on champion
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'blyb6fd6vy'], // Bloodbond Bladesworn
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Favorable Winds: Allies you control get +1 LIFE until end of turn ---
$fixtures['favorable-winds-ally-life-buff'] = [
    'testedCards' => ['dsAqxMezGb'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // WIND (basic element): Subcards lineage patch for element access, same technique as
    // rising-tides/tera-sight. AddGlobalEffects('dsAqxMezGb') then applies to any ALLY-type object
    // via $doesGlobalEffectApply['dsAqxMezGb'] (GameLogic.php:17660-17662), read back as +1 LIFE in
    // the LIFE computation switch (~13680-13682).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND element unlock (Spirit of Wind)
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY), base LIFE 3
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'dsAqxMezGb'], // Favorable Winds
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay 1-reserve cost
        // Reserve-paid SPELL activations resolve via an EffectStack, unlike the direct-resolution
        // memory-cost activations used elsewhere in this file; decline the "respond?" opportunity
        // ("-") to let it resolve (discovered via a throwaway debug harness, DevTools/debug_winds.php).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Slate Whetstone: Banish - up to one target Polearm weapon you control gets +1 POWER
// until end of turn, draw a card ---
$fixtures['slate-whetstone-banish-polearm-power-draw'] = [
    'testedCards' => ['a8a0v4njrt'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Field items with an activated ability are offered as a fast-action MZMAYCHOOSE opportunity
    // once the turn player attempts to pass (same technique as sweet-ambrosia-banish-recover),
    // answered with the encoded "{mzID}@Activate-{abilityIndex}@{label}" choice string. Steel
    // Halberd (WARRIOR/POLEARM) is seeded as the buff target.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'a8a0v4njrt'], // Slate Whetstone
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'fvnvknj4dd'], // Steel Halberd (WARRIOR/POLEARM)
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1@Activate-0@Banish', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''], // choose Steel Halberd as the buff target
    ],
];

// --- Eminent Commander: [Class Bonus] costs 3 less to activate as long as your champion has
// dealt 3+ combat damage this turn ---
$fixtures['eminent-commander-class-bonus-combat-damage-discount'] = [
    'testedCards' => ['iow4occyxi'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // CountChampionCombatDamageDealtThisTurn (GameLogic.php:23297) reads the champion object's own
    // Counters['_champCombatDamageDealtThisTurn'], directly seedable via patchMzId (no real combat
    // needed). [Class Bonus] WARRIOR is satisfied by a physically-seeded WARRIOR champion (Jin,
    // Fate Defiant), independent of the main champion. WIND element access via the established
    // Subcards lineage patch (Spirit of Wind). Base reserve cost 5, -3 discount = 2: the FSM click
    // plus 2 "myHand-0" reserve payments (each pick removes the new top-of-hand card, same as
    // summon-sentinels-drone-tokens' 4x myHand-0 pattern) pays the full discounted cost.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7'], 'Counters' => ['_champCombatDamageDealtThisTurn' => 3]]],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'zd8l14052j'], // Jin, Fate Defiant (WARRIOR), for Class Bonus
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'iow4occyxi'], // Eminent Commander
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // After the discounted 2-reserve cost is fully paid, a standard post-materialize opportunity
        // window offers remaining hand fast-actions; decline it to reach a clean end state.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Materialize Polearm: materialize a Polearm card from your material deck ---
$fixtures['materialize-polearm-from-material-deck'] = [
    'testedCards' => ['zc7wxgur23'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // WIND element access via the established Subcards lineage patch (Spirit of Wind). Steel
    // Halberd (WARRIOR/POLEARM) is seeded directly into the Material zone (on top of the 5 default
    // material cards) as the choosable target; the CUSTOM handler resolves via DoMaterialize
    // (GeneratedMacroCode.php:38036), bypassing the Polearm's own cost.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND element unlock (Spirit of Wind)
        ['player' => 1, 'zone' => 'myMaterial', 'cardID' => 'fvnvknj4dd'], // Steel Halberd (WARRIOR/POLEARM)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'zc7wxgur23'], // Materialize Polearm
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Reserve-paid activated abilities resolve through an EffectStack opportunity, same as
        // favorable-winds-ally-life-buff; decline it ('-') to let the target choice appear.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-4', 'chkInput' => [], 'inputText' => ''], // choose Steel Halberd
    ],
];

// --- Trusty Steed: On Enter, target ally you control gets +2 POWER until end of turn ---
$fixtures['trusty-steed-enter-ally-power-buff'] = [
    'testedCards' => ['FCbKYZcbNq'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // NORM (no advanced element) ALLY, so no Subcards lineage patch is needed. Dungeon Guide is
    // seeded on the field first as the only other ally, so ZoneSearch("myField", ["ALLY"]) with the
    // "pop self off the end" trick (GeneratedMacroCode.php:11162) leaves exactly one legal target.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide, buff target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'FCbKYZcbNq'], // Trusty Steed
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Unlike a SPELL activation (favorable-winds-ally-life-buff), an ALLY's On Enter target
        // choice is queued directly after the last reserve payment -- no EffectStack decline step
        // (confirmed via a throwaway debug harness, DevTools/debug_steed.php, now deleted; an extra
        // decline here gets consumed AS the target choice with chosen="-", silently no-opping the buff).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // choose Dungeon Guide
    ],
];

// --- Jin, Fate Defiant: Inherited Effect - when Jin attacks with a Polearm weapon or Polearm
// attack card, target Horse or Human ally gets +1 POWER until end of turn ---
$fixtures['jin-fate-defiant-polearm-attack-ally-buff'] = [
    'testedCards' => ['zd8l14052j'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Real attack-declaration sequence, same shape as ardent-cloudstriker-west-attack-champion-buff,
    // but the CHAMPION itself attacks (myField-0), patched directly to Jin, Fate Defiant (a static
    // CardID patch suffices here since ChampionHasInLineage only checks lineage, not a real Enter).
    // Steel Halberd (WARRIOR/POLEARM) is seeded on the field as the equipped weapon -- champions
    // offer a weapon-choice decision before the attack target when a weapon is available
    // (CombatLogic.php:1176-1180). Dungeon Guide (HUMAN) is the buff target.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zd8l14052j', 'Status' => 2]], // Jin, Fate Defiant, awake
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (HUMAN), buff target
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'fvnvknj4dd'], // Steel Halberd (WARRIOR/POLEARM), equipped weapon
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline materialize offer
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Jin
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''], // choose Steel Halberd as the weapon
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // choose Dungeon Guide for +1 POWER
    ],
];

// --- Jin, Zealous Maverick: On Enter, this champion's next attack gets +1 POWER and wakes it up ---
$fixtures['jin-zealous-maverick-enter-next-attack-power-wake'] = [
    'testedCards' => ['5ramr16052'],
    'deck' => <<<'DECK'
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
DECK,
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
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zd8l14052j']], // Jin, Fate Defiant (level 1)
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // pays the 2-memory level-up cost, card 1/2
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // card 2/2
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'fvnvknj4dd'], // Steel Halberd (WEAPON), gives the attack positive power
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], // level up to Zealous Maverick
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Jin (now at index 1)
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''], // choose Steel Halberd (now at index 0) as the weapon
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
    ],
];

// --- Veteran Soldier: Floating Memory pays a champion level-up's memory cost from graveyard ---
$fixtures['veteran-soldier-floating-memory'] = [
    'testedCards' => ['vefcX6tBeg'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Same Floating Memory keyword and same test shape as shieldroid-floating-memory /
    // stalwart-shieldmate-floating-memory / honorable-vanguard-floating-memory / return-stroke-
    // floating-memory -- Veteran Soldier's only ability (abilityCount=1 in the semantic backlog) is
    // this unconditional keyword. Pay Lorraine, Wandering Warrior's 1-memory champion level-up cost
    // entirely from a Veteran Soldier seeded directly into the graveyard, with no myMemory filler
    // seeded, so QueueMaterializeFloatingPaymentChoice (GrandArchiveSim/Custom/MaterializeLogic.php)
    // offers it as the sole payment source.
    'setup' => [
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'vefcX6tBeg'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Berserker Plate: recollection phase - deal 3 unpreventable to your champion, then draw ---
$fixtures['berserker-plate-recollection-damage-draw'] = [
    'testedCards' => ['ci00l7pqcx'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Berserker Plate's recollection-phase trigger (GameLogic.php ~9012-9022, inside the
    // unconditional per-field-card switch in ResolveBeforeRecollectionPhaseStart) deals 3
    // unpreventable damage to the turn player's champion, then draws a card. Reaching player 1's
    // own recollection phase from the initial gamestate needs the same P1->P2->P1 cycle as
    // planar-abyss-delayed-destroy-south-damage: P1 pass, P2 pass reaches P1's turn 2 MAT phase
    // (BeforeRecollectionPhase's own currentTurn===1 early-return requires turn>1), and declining
    // the MAT-phase materialize offer ('PASS') lets the phase engine auto-advance through
    // BREC->REC (firing this trigger)->DRAW->MAIN. The [Class Bonus] +7 LIFE clause is a computed
    // stat buff with no stored counter to assert and is out of scope here (consistent with the
    // established rule for computed buffs); the recollection trigger is unconditional regardless of
    // class bonus, so no champion class patch is needed.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'ci00l7pqcx'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Safeguard Paladin: [Class Bonus] prevent 2 non-combat damage to itself ---
$fixtures['safeguard-paladin-class-bonus-noncombat-prevent'] = [
    'testedCards' => ['ifmmvbm26h'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Jin, Fate Defiant
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Safeguard Paladin's Class Bonus (CombatLogic.php:4479-4484, inside OnDealDamage's non-combat
    // prevention chain: "if(!$isCombat && ... $targetObj->CardID === 'ifmmvbm26h' ...) $amount =
    // max(0, $amount - 2)") only fires when the champion's class matches (CLERIC or WARRIOR). The
    // starting champion's CardID is patched directly to Jin, Fate Defiant (WARRIOR) to satisfy that
    // condition -- but Jin's own element is NORM, not FIRE, so a FIRE-element damage spell (like
    // Focused Flames, tried first and rejected by CanPlayerUseCardElement -- confirmed via a
    // throwaway debug harness, DevTools/debug_safeguard.php/debug_safeguard2.php, now deleted --
    // since the champion patch drops the default Spirit of Fire lineage's FIRE unlock) can't be
    // activated. Nascent Blast (vajycopxgf, cardActivatedAbilities/CardActivated-1,
    // GeneratedMacroCode.php ~22398-22410/37158-37166) is NORM element instead (always usable
    // regardless of champion element) -- a 3-reserve ACTION spell that deals 3 non-combat damage to
    // target unit via DealDamage -> OnDealDamage, activated from hand and targeted at Safeguard
    // Paladin itself (same action shape as ignite-the-soul-damage: FSM click, pay reserve, both
    // players decline the EffectStack response window with PASS, then choose the target) --
    // confirmed WRONG via RunIntegrationTests --verbose: the target MZCHOOSE is queued directly
    // after the 3rd payment with no decline step (same as trusty-steed-enter-ally-power-buff's
    // ALLY On Enter target choice), so an extra PASS action gets consumed as the target choice
    // itself instead. 3 damage - 2 prevented = 1 damage actually applied.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zd8l14052j']], // Jin, Fate Defiant (WARRIOR) - Class Bonus precondition
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'ifmmvbm26h'], // Safeguard Paladin
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vajycopxgf'], // Nascent Blast
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Jin, Undying Resolve: immortality as long as it's not your end phase ---
$fixtures['jin-undying-resolve-immortality-survives-lethal'] = [
    'testedCards' => ['c4yrrtv7o1'],
    'deck' => <<<'DECK'
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
DECK,
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
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'c4yrrtv7o1', 'Damage' => 27]],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vajycopxgf'], // Nascent Blast
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Executioner's Spear: [Jin Bonus] On Kill, put a durability counter on itself ---
$fixtures['executioners-spear-jin-bonus-on-kill-durability'] = [
    'testedCards' => ['zv6yp6q7zw'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Jin, Fate Defiant
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Executioner's Spear's On Kill (GeneratedMacroCode.php ~26540-26547, onKillAbilities) only
    // fires when IsJinBonus($player) (GameLogic.php:25095-25100, champion name starts with "Jin")
    // is true, so the starting champion's CardID is patched directly to Jin, Fate Defiant. On Kill
    // dispatch (CombatLogic.php:2541-2578, OnKillTrigger) only fires for a real combat-damage kill,
    // so this needs a full real attack sequence (same P1 pass / P2 pass / P1 decline-MAT-PASS
    // shape as jin-fate-defiant-polearm-attack-ally-buff to clear the turn-1 attack lock and reach
    // turn 2 MAIN). A setup step targeting the OPPONENT's own field must use
    // {'player'=>2,'zone'=>'myField'}, NOT 'theirField' -- 'zone' names in a setup step are
    // relative to the acting player, so 'theirField' with player=2 seeds player 1's field instead
    // (hit and fixed live: Baby Gray Slime first landed on the attacker's own field). After
    // choosing the attack target, a "Retaliate?" MZMAYCHOOSE decision is queued for the DEFENDER
    // (player 2) that must be explicitly declined ('-') before CombatApplyAttackerDamage actually
    // lands the damage -- omitting it leaves the kill (and the durability counter) from ever
    // happening (confirmed via a throwaway debug harness, now deleted). Jin, Fate Defiant has no
    // printed base POWER (0), and Executioner's Spear has no Class Bonus power boost (unlike Steel
    // Halberd), so total attack power is exactly the weapon's printed 1 POWER -- Baby Gray Slime
    // (0hsncz1fz2, 1 life) is the kill target. Executioner's Spear enters the field with 2
    // durability counters; combat damage removes 1 (per its own reminder text), then On Kill adds 1
    // back, netting durability=2.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zd8l14052j']], // Jin, Fate Defiant (WARRIOR) - Jin Bonus precondition
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'zv6yp6q7zw'], // Executioner's Spear
        ['player' => 2, 'zone' => 'myField', 'cardID' => '0hsncz1fz2'], // Baby Gray Slime (1 life) - kill target, on player 2's OWN field
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // choose Executioner's Spear as the weapon
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''], // target Baby Gray Slime
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline Retaliate so CombatApplyAttackerDamage actually lands
    ],
];

// --- Hemorrhaging Rend: [Damage 20+] Cleave, attack all units a chosen opponent controls ---
$fixtures['hemorrhaging-rend-damage20-cleave'] = [
    'testedCards' => ['xiazfnm292'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Hemorrhaging Rend is an ATTACK card, played as an intent card during a real attack rather
    // than having its own cardActivatedAbilities entry (no per-card macro exists for it at all --
    // confirmed by grep). Seeding it directly into myIntent via setup does NOT survive to the
    // attack: the P1/P2 turn-1 pass needed to clear the turn-1 attack lock runs through EndPhase(),
    // which calls ClearIntent() and wipes any pre-seeded intent card before turn 2 begins
    // (confirmed via a throwaway debug harness, now deleted, showing GetIntentCards(1) === [] and
    // BeginCombatPhase failing with "0 or less power" right when the attack was attempted). So
    // instead it's played for real from hand during turn 2's MAIN phase, same FSM-click + reserve-
    // payment shape as any other hand card -- ActionMap's myHand case has no special handling for
    // ATTACK-type cards, it just calls the same ActivateCard() as a materialize/spell, whose result
    // is entering myIntent instead of the field. Hemorrhaging Rend's element is EXIA, not unlocked
    // by the default Spirit of Fire lineage. Patching the champion's own CardID to an EXIA champion
    // (Dante, Hemomancer) was tried first and rejected: GetPlayerEnabledElements reads
    // GetChampionLineage (the current champion's own elements plus its Subcards lineage), so a full
    // CardID patch works for the element unlock but ALSO inherits Dante's own printed abilities --
    // an Empower fast-action Opportunity window opened at the very first decision point and
    // silently absorbed the turn-1 pass action meant to decline the turn-2 MAT-phase materialize
    // offer, leaving turn 2 never properly reached (confirmed via a throwaway debug harness, now
    // deleted: BeginCombatPhase kept failing with "0 or less power" and GetIntentCards(1) stayed
    // empty even after the FSM click + reserve payments "succeeded"). Fix: unlock EXIA via Subcards
    // lineage only (same technique as Lorraine Arclight Saber's WIND unlock via
    // Subcards=[pNiyaGlIe7]) -- Dante's CardID goes into Subcards while Spirit of Fire (no
    // interfering abilities) stays the active champion. Damage is patched to 20 for the
    // [Damage 20+] condition. AttackerHasCleave (CombatLogic.php:433-443) grants Cleave once the
    // champion has 20+ damage counters and Hemorrhaging Rend is in intent -- Cleave with no weapon
    // available bypasses the normal target-choice MZCHOOSE entirely and queues a CleaveAttack
    // decision directly (CombatLogic.php:1187-1188/1575-1578), making ALL of the opponent's units
    // (their champion AND Dungeon Guide, seeded onto their own field) simultaneous defenders.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['4FtNBFaOJp'], 'Damage' => 20]], // Dante, Hemomancer in Subcards for EXIA unlock only
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'xiazfnm292'], // Hemorrhaging Rend
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide, second Cleave defender
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Savage Swing: [Class Bonus] Floating Memory ---
$fixtures['savage-swing-class-bonus-floating-memory'] = [
    'testedCards' => ['vk56lbihtc'],
    'deck' => <<<'DECK'
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
DECK,
    // Unlike Veteran Soldier's unconditional Floating Memory (veteran-soldier-floating-memory),
    // Savage Swing's is gated by [Class Bonus] (its own class is WARRIOR). Two things ruled out
    // Floating Memory's usual test shape: (1) Floating Memory only offers itself as a payment
    // source for a MEMORY cost (a champion level-up), NOT a reserve cost -- confirmed by trying to
    // pay 1 of Nascent Blast's reserve cast cost from the graveyard, which was rejected as "Invalid
    // selection."; (2) patching the starting champion directly to Jin, Fate Defiant (level 1)
    // to satisfy the Class Bonus makes leveling into another level-1 champion (Lorraine, Wandering
    // Warrior) illegal -- level-up requires a strictly higher level than the current champion. Fix:
    // patch the champion to Jin, Fate Defiant (level 1, WARRIOR) and level up from there into Jin,
    // Zealous Maverick (level 2, WARRIOR, 2-memory cost) instead -- the Class Bonus condition checks
    // the CURRENT champion (Jin, Fate Defiant, still WARRIOR) at payment time, before the level-up
    // completes. Savage Swing pays 1 of the 2 memory via Floating Memory from the graveyard; the
    // 2nd memory point is a filler card seeded directly into myMemory (no draw/recollection cycle
    // needed to populate it).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zd8l14052j']], // Jin, Fate Defiant (WARRIOR, level 1) - Class Bonus precondition + legal level-up base
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'vk56lbihtc'], // Savage Swing
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'], // filler memory card, 2nd point of Jin Zealous Maverick's 2-memory level-up cost
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], // select Jin, Zealous Maverick as the level-up target
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''], // pay 1 memory via Floating Memory
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMemory-0', 'chkInput' => [], 'inputText' => ''], // pay the 2nd memory point
    ],
];

// --- Wind Cutter: [Class Bonus] +1 POWER, real single-target (non-Cleave) ATTACK-card attack ---
$fixtures['wind-cutter-class-bonus-power-attack'] = [
    'testedCards' => ['TgYTZg6TaG'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
8 Dungeon Guide
4 Fluffy Shopkeep
4 Windslice
DECK,
    // First fixture to exercise the NORMAL (non-Cleave) single-target ATTACK-card flow, now that
    // hemorrhaging-rend-damage20-cleave proved ATTACK cards are played from hand into myIntent like
    // any other card. Champion is patched to Jin, Fate Defiant (WARRIOR/RANGER class match for
    // Wind Cutter's [Class Bonus] +1 POWER) with Spirit of Wind (pNiyaGlIe7) added via Subcards only
    // (not a full CardID patch) to unlock WIND without inheriting any interfering abilities -- same
    // technique as hemorrhaging-rend's Dante-via-Subcards fix. Unlocking WIND has a side effect
    // specific to the OTHER Jin fixtures' shared Main list: Fairy Whispers (n8wyfG9hbY) is itself a
    // WIND-element ACTION card, and once WIND is unlocked it becomes flash-playable, which
    // perpetually re-triggers a "Take a fast action?" Opportunity window (and a chained materialize
    // offer) at EVERY decision boundary -- declining it never actually clears it, and a single
    // decline was even observed to skip all the way to the opponent's turn (GA's phase auto-advance
    // treats an empty decision queue as "nothing left for this player," and CustomInput.php's
    // "myHealth" Pass button is in fact the same generic mid-game turn-pass action used for the
    // pregame mulligan, usable by whichever player's turn it currently is -- confirmed via a
    // throwaway debug harness, now deleted). Fix: this fixture's own deck heredoc drops Fairy
    // Whispers entirely (replaced with more Dungeon Guide to keep 16 Main cards), so unlocking WIND
    // doesn't expose any new flash-playable card, and the action sequence reduces to the same
    // minimal single-PASS turn-1 shape used by every other real-attack fixture.
    // GetAttackWeaponChoices($player, $obj)
    // (CombatLogic.php:110-148) reads GetAvailableWeapons($player), which only looks at field-based
    // WEAPON items -- with none on the field, $availableWeapons is empty, so BeginCombatPhase
    // (CombatLogic.php:1478-1580) skips the weapon-choice MZCHOOSE entirely (that step is
    // conditioned on `!empty($availableWeapons)`) and queues ChooseAttackTarget directly. Wind
    // Cutter's own printed POWER is 1; the intent card's computed power (base 1 + Class Bonus 1 = 2)
    // is asserted directly on myIntent-0 right after paying reserve, before the attack is declared.
    // GetTotalAttackPower then sums the champion's own power (0, Jin, Fate Defiant has none) plus
    // the intent card's positive power (2) with no weapon, so the opponent's champion (targeted
    // directly, theirField-0) takes exactly 2 combat damage. As with every other real-attack
    // fixture, the defender's "Retaliate?" MZMAYCHOOSE must be explicitly declined before
    // CombatApplyAttackerDamage actually lands the damage.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zd8l14052j', 'Subcards' => ['pNiyaGlIe7']]], // Jin, Fate Defiant (WARRIOR class bonus) + WIND unlock via Spirit of Wind lineage
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'TgYTZg6TaG'], // Wind Cutter
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline Retaliate
    ],
];

// --- Pierce the Heavens: [Jin Bonus] leveled up this turn -> +2 POWER, unblockable ---
$fixtures['pierce-the-heavens-jin-bonus-leveled-up-power-unblockable'] = [
    'testedCards' => ['yguf3aw2ct'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Jin, Fate Defiant
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Pierce the Heavens' onAttackAbilities (CombatLogic.php:592) checks
    // strpos(CardName($champ->CardID), "Jin") === 0 AND GlobalEffectCount($player,
    // "LEVELED_UP_THIS_TURN") > 0, adding +2 POWER and UNBLOCKABLE TurnEffects. Rather than
    // patching the champion directly (which would need a SEPARATE later level-up to set the
    // LEVELED_UP_THIS_TURN flag, and patching+leveling in the same turn risks the "can't level to a
    // card of equal/lower level" issue hit in savage-swing-class-bonus-floating-memory), the
    // champion is kept as the default Spirit of Fire (level 0) and leveled up NATURALLY into Jin,
    // Fate Defiant (level 1, 1-memory cost) during turn 1's MAIN phase -- this single real level-up
    // both satisfies the "champion is Jin" precondition AND sets LEVELED_UP_THIS_TURN, in the same
    // turn Pierce the Heavens is played and attacked with. Pierce the Heavens is NORM element
    // (always castable regardless of champion element, unlike Wind Cutter's WIND -- no Fairy
    // Whispers Opportunity-window cascade to work around here). Total attack power = champion's own
    // power (0, Jin Fate Defiant has none) + intent card's boosted power (base 3 + 2 = 5, no weapon).
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'], // filler memory card, Jin Fate Defiant's 1-memory level-up cost
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'yguf3aw2ct'], // Pierce the Heavens
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], // level up into Jin, Fate Defiant
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMemory-0', 'chkInput' => [], 'inputText' => ''], // pay the 1 memory
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline Retaliate
    ],
];

// --- Swift Recruit: Intercept, redirect an attack on your champion to this awake ally ---
$fixtures['swift-recruit-intercept-redirect'] = [
    'testedCards' => ['mHd6LLyMyF'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Intercept (GetAvailableInterceptRedirectTargets, CombatLogic.php:605-624) only offers a
    // redirect when the attack's TARGET is a CHAMPION (checked via PropertyContains(...,
    // "CHAMPION")), so this needs the OPPONENT attacking PLAYER 1's champion, not the usual
    // player-1-attacks pattern used by every other real-attack fixture so far. Reaching player 2's
    // own turn (rather than staying on player 1's turn 1, as every prior fixture did) uses the
    // CustomInput.php "myHealth" Pass button as a genuine mid-game end-turn action (not just the
    // pregame mulligan use) -- discovered while debugging wind-cutter-class-bonus-power-attack: it's
    // the SAME action as the pregame health-pass, gated only by "only the turn player can pass," and
    // reusable any time it's your turn with nothing left to do. After P1 formally ends turn 1 (with
    // nothing to do -- Swift Recruit is placed directly on the field, not played from hand), P2
    // declines their own MAT-phase materialize offer, then P2's champion (default Spirit of Fire)
    // attacks P1's champion directly. GetAvailableInterceptRedirectTargets then offers Swift Recruit
    // (awake, ALLY, HasIntercept -- all satisfied by a plain field seed with no extra patches) as a
    // redirect target, queued as a "Choose_an_interceptor" MZMAYCHOOSE for the DEFENDER (player 1,
    // in their own myField-1 perspective). Redirecting moves the attack's target to Swift Recruit
    // itself, so the subsequent "Retaliate?" decision (still queued for the defender, player 1) and
    // the resulting combat damage land on Swift Recruit (1 life... its actual life is 2, dealt 1
    // damage from the champion's own printed POWER) instead of the champion.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'mHd6LLyMyF'], // Swift Recruit, awake by default
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'zv6yp6q7zw'], // Executioner's Spear (1 POWER), P2's own field -- Spirit of Fire has no base POWER, so a weapon is needed for a legal attack
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // P1 declines their own MAT-phase materialize offer first (the mid-game end-turn Pass button refuses while a decision is pending)
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // P1 formally ends turn 1 (nothing to do)
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // P2 declines their MAT-phase materialize offer
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''], // P2's champion attacks
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // choose Executioner's Spear as the weapon
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target P1's champion
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // redirect to Swift Recruit
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline Retaliate
    ],
];

$fixtures['plated-bullet-rest-load-shadows-twin-power'] = [
    'testedCards' => ['l75tlzsmw3', '5vettczb14'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
8 Dungeon Guide
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => '5vettczb14'], // Shadow's Twin, unloaded Gun, myField-1
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'l75tlzsmw3'], // Plated Bullet, awake, myField-2
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline own MAT-phase materialize offer
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''], // Plated Bullet's [REST] ability
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // choose Shadow's Twin as the unloaded Gun to load into
    ],
];


// --- Automaton Bomber: Ranged 4 (unconditional -- no Class Bonus needed) ---
$fixtures['automaton-bomber-ranged-attack'] = [
    'testedCards' => ['ygojwk0pw0'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Automaton Bomber
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Seed Automaton Bomber directly onto player 2's field (awake, Distant) so player 2 can
    // legally attack on turn 2 (Rule 1.h only blocks the opening player's own turn 1). DISTANT
    // is on the persistent-turn-effects allowlist and is only cleared in EndPhase for the
    // *controller's own* ending turn, so seeding it before turn 1 ends (player 1's turn, not
    // player 2's) survives into player 2's turn untouched. Player 1 ends turn 1 with a single
    // CustomInput Pass (there's no pending decision to decline here, so this action itself ends
    // the turn -- confirmed by directly instrumenting the phase machine this session), then
    // player 2 attacks with Ranged 4 active (1 base + 4 = 5 POWER).
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'ygojwk0pw0', 'setProperties' => ['TurnEffects' => ['DISTANT'], 'Status' => 2]], // Automaton Bomber, awake and Distant
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (no pending decision to decline)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Trained Sharpshooter: [Class Bonus] Ranged 2 ---
$fixtures['trained-sharpshooter-class-bonus-ranged-attack'] = [
    'testedCards' => ['uhjxhkurfp'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Trained Sharpshooter
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Same turn-cycle approach as automaton-bomber-ranged-attack (Rule 1.h blocks player 1's own
    // turn-1 attack), but Trained Sharpshooter's Ranged 2 needs a RANGER Class Bonus
    // (IsClassBonusActive scans the whole field for any champion-type object of the right class),
    // so also seed a real RANGER champion (Diana, Keen Huntress) onto player 2's field.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'e3z4pyx8bd'], // Diana, Keen Huntress (RANGER champion) - Class Bonus source
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'uhjxhkurfp', 'setProperties' => ['TurnEffects' => ['DISTANT'], 'Status' => 2]], // Trained Sharpshooter, awake and Distant
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Imperial Rifleman: [Class Bonus] On Enter: becomes distant ---
$fixtures['imperial-rifleman-class-bonus-enter-distant'] = [
    'testedCards' => ['17fzcyfrzr'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Imperial Rifleman
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Imperial Rifleman's On Enter trigger only fires on a REAL materialize (BridgeAddToZone
    // seeds silently, no Enter trigger), so it must be played from hand while a RANGER Class
    // Bonus is already active. Seed the RANGER champion first, then Imperial Rifleman into a
    // known hand slot; its 3-reserve cost needs 3 reps of the myHand-0 reserve-payment decision.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'e3z4pyx8bd'], // Diana, Keen Huntress (RANGER champion) - Class Bonus source
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '17fzcyfrzr'], // Imperial Rifleman, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Prototype Pistol: [Class Bonus] On Enter: +1 POWER until end of turn ---
$fixtures['prototype-pistol-class-bonus-enter-power'] = [
    'testedCards' => ['frzrplywc0'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Prototype Pistol
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Prototype Pistol is a REGALIA card. HandAddReplacement (GameLogic.php) silently redirects
    // a REGALIA card into the material zone whenever it's added to hand via the BridgeAddToZone
    // test-setup primitive -- but that hook only fires for that kind of explicit single-card
    // add, not the normal bulk initial deal, so a REGALIA copy drawn into a real opening hand
    // stays there normally. Player 2's natural 7-card opening hand happens to include one
    // (confirmed at theirHand-0 from player 1's perspective, i.e. player 2's own myHand-0), so it
    // can be played for real via the ordinary hand FSM flow once player 2 reaches their turn.
    // Rule 1.h only blocks the opening player's own turn 1, so player 1 ends turn 1 with a single
    // CustomInput Pass (no decision is pending, so this action itself ends the turn) and player 2
    // plays it on turn 2, with a RANGER Class Bonus champion already seeded onto their field.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'e3z4pyx8bd'], // Diana, Keen Huntress (RANGER champion) - Class Bonus source
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-0!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Violet Haze: all your units become distant; put on bottom of target champion's lineage ---
$fixtures['violet-haze-distant-lineage'] = [
    'testedCards' => ['vdxi74wa4x'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Violet Haze
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Violet Haze is UMBRA (advanced element), so the starting champion's Subcards are patched
    // with a real UMBRA champion (Tristan, Shadowdancer) to unlock element access -- the same
    // pattern already used for other UMBRA cards elsewhere in this file. It's a 2-reserve action
    // played for real (no On Enter/no field presence to fake via test-setup). Seed an extra ally
    // on the field to confirm "all units you control" isn't limited to the champion, then play
    // the card from hand and target the champion with the lineage placement.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['he6kd7hocc']]], // UMBRA lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide - confirms "all units" isn't champion-only
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vdxi74wa4x'], // Violet Haze, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Umbra Sight: Draw a card; may draw into memory + curse lineage for damage ---
$fixtures['umbra-sight-curse-lineage-damage'] = [
    'testedCards' => ['f15joh300z'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Umbra Sight
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Umbra Sight is UMBRA (advanced element), so the starting champion's Subcards are patched
    // with a real UMBRA champion (Tristan, Shadowdancer) to unlock element access. Its reserve
    // cost is a real printed 0 (not the "-1 = no reserve type" sentinel), so no reserve-payment
    // decision is queued at all -- straight into the unconditional draw, then a YESNO for the
    // optional draw-into-memory + curse-lineage effect. Choosing YES adds it to the champion's
    // lineage and deals 2 unpreventable damage per curse in lineage (2, counting itself).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['he6kd7hocc']]], // UMBRA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'f15joh300z'], // Umbra Sight, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Umbral Tithe: each player draws 2 into memory, then damage to high-memory champions ---
$fixtures['umbral-tithe-memory-damage'] = [
    'testedCards' => ['2snsdwmxz1'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Umbral Tithe
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Umbral Tithe is UMBRA (advanced element); same lineage patch as Umbra Sight. Its 5-reserve
    // cost is discounted 1 per Curse in either champion's lineage -- with none seeded here the
    // full 5 reps of the myHand-0 reserve-payment decision are needed. Player 1's memory is
    // pre-seeded with 4 cards so the two drawn by this effect push them to 6+, triggering the
    // 4-damage clause; player 2 starts at 0 and stays under the threshold.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['he6kd7hocc']]], // UMBRA lineage/element unlock
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '2snsdwmxz1'], // Umbral Tithe, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Take Aim: target unit's next attack gets +2 POWER; [Class Bonus] also gains Ranged 2 ---
$fixtures['take-aim-class-bonus-ranged-attack'] = [
    'testedCards' => ['vnta6qsesw'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Take Aim
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Same turn-cycle approach as automaton-bomber-ranged-attack (Rule 1.h blocks player 1's own
    // turn-1 attack): player 1 ends turn 1 with a single CustomInput Pass, then player 2 plays
    // Take Aim targeting their own ally (already Distant and awake) with a RANGER Class Bonus
    // active, then attacks. AddTurnEffect("vnta6qsesw"/"RANGED_2") only turns into actual POWER
    // once the target's attack is declared (CombatLogic.php converts vnta6qsesw ->
    // vnta6qsesw_POWER at that point), and the CB-granted "RANGED_2" only contributes via
    // GetRangedValue while the unit is Distant on its controller's turn.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'e3z4pyx8bd'], // Diana, Keen Huntress (RANGER champion) - Class Bonus source
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['TurnEffects' => ['DISTANT'], 'Status' => 2]], // Dungeon Guide, awake and Distant - Take Aim's target
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'vnta6qsesw'], // Take Aim, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-2!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Force Load: choose a fire/norm 0-cost Bullet from material deck, load into target Gun ---
$fixtures['force-load-bullet-into-gun'] = [
    'testedCards' => ['y6isxy5lh2'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Force Load
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Force Load reads the material zone directly (not the standard "myMaterial" section of this
    // fixture's own deck list, which has no Bullet cards), so a real 0-memory-cost NORM Bullet
    // (Plated Bullet) is seeded there via test-setup. Shadow's Twin is seeded unloaded onto the
    // field as the target Gun -- LoadBulletIntoGun() is the same shared function used by Plated
    // Bullet's own [REST] ability, so loading via Force Load also triggers Shadow's Twin's
    // "whenever this becomes loaded, +2 POWER" trigger.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => '5vettczb14'], // Shadow's Twin, unloaded Gun - Force Load's target
        ['player' => 1, 'zone' => 'myMaterial', 'cardID' => 'l75tlzsmw3'], // Plated Bullet - 0-memory NORM Bullet source
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'y6isxy5lh2'], // Force Load, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-4', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Materialize Munitions: [Class Bonus] discount; materialize a Bullet from material deck ---
$fixtures['materialize-munitions-class-bonus-discount'] = [
    'testedCards' => ['xi74wa4x7e'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Materialize Munitions
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Seeds a RANGER champion for the Class Bonus (discounting the 3-reserve activation cost to
    // 2) and a real 0-memory-cost Bullet (Plated Bullet) into the material zone -- this fixture's
    // own deck's Material section has no Bullet cards. Materializing the chosen bullet still
    // routes through the normal CUSTOM "MATERIALIZE" handler and pays its own (0) memory cost.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'e3z4pyx8bd'], // Diana, Keen Huntress (RANGER champion) - Class Bonus source
        ['player' => 1, 'zone' => 'myMaterial', 'cardID' => 'l75tlzsmw3'], // Plated Bullet - 0-memory Bullet source
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'xi74wa4x7e'], // Materialize Munitions, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-4', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Diana, Keen Huntress: Lineage Release -- materialize a Gun from material deck ---
$fixtures['diana-keen-huntress-lineage-release-materialize-gun'] = [
    'testedCards' => ['e3z4pyx8bd'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Diana, Keen Huntress's Lineage Release ("LR: Materialize Gun") only becomes an activatable
    // dynamic ability when she sits as a subcard of the CURRENT champion's inner lineage (not
    // just present on the field), patched directly via the champion's Subcards. A real 0-memory
    // NORM Gun card (Framework Sidearm) is seeded into the material zone as the LR effect's
    // target -- MaterializeLogic.php's generic MATERIALIZE handler element-checks the chosen
    // card (CanPlayerUseCardElement) before placing it, so an UMBRA Gun like Shadow's Twin would
    // need its own lineage patch too; NORM needs none. Activating a champion's dynamic ability
    // uses the same CustomInput Activate:N pattern as a field object's own activated ability,
    // with N = staticAbilityCount (0, since the starting champion has no static abilities) for
    // the first eligible LR subcard.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['e3z4pyx8bd']]], // Diana, Keen Huntress in the champion's inner lineage
        ['player' => 1, 'zone' => 'myMaterial', 'cardID' => 'p4lgdlx7md'], // Framework Sidearm (NORM Gun, 0 memory) - LR materialize target
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-0!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-4', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Quickdraw Piercer: [Class Bonus] On Banish: Draw a card ---
$fixtures['quickdraw-piercer-class-bonus-on-banish-draw'] = [
    'testedCards' => ['j4f15joh30'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Quickdraw Piercer
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Quickdraw Piercer has no activated ability of its own -- its only ability is the generic
    // OnLeaveField hook (fired whenever it leaves the field for any reason, not just a genuine
    // rules "banish"). Blazing Throw's own mandatory "sacrifice a weapon" additional cost is the
    // simplest real in-game path to remove it from the field, so it's played (FIRE element, needs
    // the same lineage-patch pattern as UMBRA cards) targeting Quickdraw Piercer as the sacrifice.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['LMyKyVC2O9']]], // FIRE lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'e3z4pyx8bd'], // Diana, Keen Huntress (RANGER champion) - Class Bonus source
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'j4f15joh30'], // Quickdraw Piercer - sacrifice target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'iohZMWh5v5'], // Blazing Throw, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Blastshot Pump: [Class Bonus] redirect combat damage to an additional unit ---
$fixtures['blastshot-pump-class-bonus-redirect-damage'] = [
    'testedCards' => ['gmnmp5af09'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Blastshot Pump
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Weapons need a "durability" counter to even be considered available to attack with
    // (GetAvailableWeapons in CombatLogic.php), and functional weapons (GUN/BOW/AETHERWING) must
    // already be loaded (non-empty Subcards). Same turn-cycle approach as automaton-bomber-
    // ranged-attack (Rule 1.h blocks player 1's own turn-1 attack): player 1 ends turn 1, then
    // player 2's champion attacks using Blastshot Pump (with a RANGER Class Bonus champion also
    // on their field) against one of player 1's two units, redirecting some of the hit damage to
    // the other.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'e3z4pyx8bd'], // Diana, Keen Huntress (RANGER champion) - Class Bonus source
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'gmnmp5af09', 'setProperties' => ['Counters' => ['durability' => 1], 'Subcards' => ['l75tlzsmw3']]], // Blastshot Pump, loaded and usable
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide - primary attack target
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Supply Drone: [Class Bonus] at recollection phase, materialize a 0-cost Bullet ---
$fixtures['supply-drone-class-bonus-recollection-materialize'] = [
    'testedCards' => ['ljyevpmu6g'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Supply Drone
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // The global turn counter only increments when play cycles back to the first player (EndPhase:
    // "$turnPlayer = ($turnPlayer==1)?2:1; if($turnPlayer==$firstPlayer) ++$currentTurn;"), so
    // "$currentTurn===1" actually covers BOTH player 1's AND player 2's first turns -- player 2's
    // first turn does NOT unblock RecollectionPhase/BeforeRecollectionPhase (confirmed by directly
    // instrumenting the turn/phase state this session). Reaching a real recollection phase for
    // player 2 needs a full cycle back to player 2's own SECOND turn: P1 ends turn 1, P2 ends
    // their (still turn-1) turn, P1 declines their own MAT-phase materialize offer and ends their
    // turn 2 (this crosses back to player 1, incrementing the global counter to 2), then P2
    // declines their own materialize offer before BREC's per-card recollection check queues
    // Supply Drone's materialize choice.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'e3z4pyx8bd'], // Diana, Keen Huntress (RANGER champion) - Class Bonus source
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'ljyevpmu6g'], // Supply Drone
        ['player' => 2, 'zone' => 'myMaterial', 'cardID' => 'l75tlzsmw3'], // Plated Bullet - 0-cost Bullet source
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends player 2's turn (still global turn 1)
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline player 1's own MAT-phase materialize offer
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends player 1's turn 2 (crosses back to player 1 -> global turn increments to 2)
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline player 2's own MAT-phase materialize offer -> reaches BREC
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-4', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Diana, Deadly Duelist: On Enter -- materialize a Bullet from material deck (level-up) ---
$fixtures['diana-deadly-duelist-enter-materialize-bullet'] = [
    'testedCards' => ['7ozuj68m69'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Diana, Deadly Duelist requires Diana Lineage (leveled from a level-1 Diana champion), but
    // CanChampionLevelUpIntoCard only checks the CURRENT champion's own printed CardLevel, not
    // lineage (same pattern already used for lorraine-arclight-saber-static-counters), so the
    // starting champion's CardID is patched directly to Diana, Keen Huntress (level 1) as the
    // level-up precondition. Her On Enter ability filters the material zone specifically for the
    // BULLET subtype (GeneratedMacroCode.php enterAbilities["7ozuj68m69:0"]), so a real Bullet
    // (Plated Bullet, NORM/0-memory) -- not a Gun -- is seeded into the material zone as her
    // materialize target.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'e3z4pyx8bd']], // Diana, Keen Huntress (level 1) - level-up precondition
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMaterial', 'cardID' => '7ozuj68m69'], // Diana, Deadly Duelist (level 2) - level-up target
        ['player' => 1, 'zone' => 'myMaterial', 'cardID' => 'l75tlzsmw3'], // Plated Bullet (NORM Bullet, 0 memory) - On Enter materialize target
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-4', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-4', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Diana, Duskstalker: On Enter -- becomes distant (level-up) ---
$fixtures['diana-duskstalker-enter-distant'] = [
    'testedCards' => ['iq4d5vettc'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Same level-up-bypass approach as diana-deadly-duelist-enter-materialize-bullet:
    // CanChampionLevelUpIntoCard only checks the CURRENT champion's own printed CardLevel, not
    // lineage, so the starting champion's CardID is patched directly to Diana, Deadly Duelist
    // (level 2) as the level-up precondition, then a real level-up (via the standard
    // MaterializeChoice flow) into Diana, Duskstalker (level 3) fires her On Enter naturally.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '7ozuj68m69']], // Diana, Deadly Duelist (level 2) - level-up precondition
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMaterial', 'cardID' => 'iq4d5vettc'], // Diana, Duskstalker (level 3) - level-up target
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-4', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Novice Mechanist: Foster; On Foster: Summon an Automaton Drone token ---
$fixtures['novice-mechanist-on-foster-summon-drone'] = [
    'testedCards' => ['22tk3ir1o0'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Novice Mechanist
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Foster is checked at the beginning of the controller's recollection phase: "if this ally
    // hasn't been dealt damage since the end of your previous turn, it becomes fostered"
    // (GameLogic.php ~9501, inside ResolveBeforeRecollectionPhaseStart). A freshly-seeded object
    // has no DAMAGED_SINCE_LAST_TURN tag, so it qualifies immediately. Reaching player 1's own
    // recollection phase just needs: player 1 ends turn 1, player 2 ends their turn (still global
    // turn 1), then player 1 declines their own MAT-phase materialize offer -- that single Pass
    // auto-advances into player 1's own BREC, which is where Foster processing (and this card's
    // On Foster trigger) runs.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => '22tk3ir1o0'], // Novice Mechanist
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends player 2's turn (still global turn 1)
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline player 1's own MAT-phase materialize offer -> reaches BREC/Foster processing
    ],
];

// --- Recruitment Officer: [Class Bonus] Foster; On Foster: look top 5, may take an ally ---
$fixtures['recruitment-officer-class-bonus-on-foster-look-top-5'] = [
    'testedCards' => ['1x97n2jnlt'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Recruitment Officer
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Recruitment Officer's Foster is [Class Bonus]-gated (HasFoster's $fosterCBCards table,
    // CardLogic.php), so a GUARDIAN champion (Tonoris, Lone Mercenary) is seeded for the bonus.
    // Same 3-action approach as novice-mechanist-on-foster-summon-drone to reach player 1's own
    // recollection phase, where Foster processing (and this card's On Foster trigger) runs.
    // Deck-shuffle seed 1 (not the usual default 42) is used so the revealed top 5 of the deck
    // actually contains an ally card, exercising the "may reveal an ally" branch instead of the
    // no-op "no ally found" one.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'zb14m4c8lj'], // Tonoris, Lone Mercenary (GUARDIAN champion) - Class Bonus source
        ['player' => 1, 'zone' => 'myField', 'cardID' => '1x97n2jnlt'], // Recruitment Officer
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends player 2's turn (still global turn 1)
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline player 1's own MAT-phase materialize offer -> reaches BREC/Foster processing
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myTempZone-0', 'chkInput' => [], 'inputText' => ''], // reveal the ally found in the top 5 and put it into hand
    ],
];

// --- Imperial Recruit: Foster; gets +1 POWER as long as it's fostered ---
$fixtures['imperial-recruit-fostered-power'] = [
    'testedCards' => ['lzsmw3rrii'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Imperial Recruit
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // "Fostered" is a plain (persistent) TurnEffect (IsFostered() in CardLogic.php just checks
    // for the tag), so the static +1 POWER while fostered can be tested directly by patching
    // TurnEffects at setup, without needing a real recollection-phase Foster transition.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'lzsmw3rrii', 'setProperties' => ['TurnEffects' => ['FOSTERED']]], // Imperial Recruit, fostered
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''], // harmless no-op click
    ],
];

// --- Young Peacekeeper: Foster; gets +1 POWER and +1 LIFE as long as it's fostered ---
$fixtures['young-peacekeeper-fostered-power-life'] = [
    'testedCards' => ['z4pyx8bd7o'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Young Peacekeeper
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Same reasoning as imperial-recruit-fostered-power: "Fostered" is a plain persistent
    // TurnEffect, so the static +1 POWER/+1 LIFE while fostered can be tested directly by
    // patching TurnEffects at setup.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'z4pyx8bd7o', 'setProperties' => ['TurnEffects' => ['FOSTERED']]], // Young Peacekeeper, fostered
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''], // harmless no-op click
    ],
];

// --- Neos Sight: Draw a card; if you control 8+ objects, draw into memory too ---
$fixtures['neos-sight-eight-objects-memory-draw'] = [
    'testedCards' => ['4n1n3gygoj'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Neos Sight
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Neos Sight is NEOS (advanced element), so the starting champion's Subcards are patched
    // with a real NEOS champion (Tonoris, Creation's Will) to unlock element access. Its reserve
    // cost is a real printed 0, so no reserve-payment decision is ever queued. Seven allies are
    // seeded onto the field alongside the champion (8 objects total) to satisfy the "eight or
    // more objects" clause.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['n2jnltv5kl']]], // NEOS lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4n1n3gygoj'], // Neos Sight, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Take Point: your champion gains taunt until the beginning of your next turn ---
$fixtures['take-point-champion-taunt-next-turn'] = [
    'testedCards' => ['098kmoi0a5'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Take Point
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Take Point applies "TAUNT_NEXT_TURN" (a delayed grant, converted to real TAUNT at the
    // beginning of the caster's next turn), not TAUNT directly, so it's tested at the point the
    // tag is applied rather than trying to advance all the way to the conversion.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '098kmoi0a5'], // Take Point, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Powercharged Shield: Banish this -- target unit with taunt gains vigor ---
$fixtures['powercharged-shield-banish-taunt-vigor'] = [
    'testedCards' => ['rrii17fzcy'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Powercharged Shield
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Powercharged Shield's own activated ability requires a unit with taunt already on the
    // field (activateAbilityPrereqs["rrii17fzcy:0"]), so a Dungeon Guide is patched with TAUNT
    // directly via test-setup rather than scripting a real taunt-granting effect.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['TurnEffects' => ['TAUNT']]], // Dungeon Guide w/ Taunt - ability target
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'rrii17fzcy'], // Powercharged Shield
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Sentinel Fabricator: (3), REST: Summon an Automaton Drone token with a buff counter ---
$fixtures['sentinel-fabricator-summon-buffed-drone'] = [
    'testedCards' => ['j68m69iq4d'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Sentinel Fabricator
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // The activated ability's prereq requires Status==2 (awake), so it's patched explicitly.
    // The 3-reserve cost queues 3 reps of the myHand-0 reserve-payment decision before summoning
    // the token.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'j68m69iq4d', 'setProperties' => ['Status' => 2]], // Sentinel Fabricator, awake
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Crest of the Alliance: whenever a fostered ally dies, may banish this to draw ---
$fixtures['crest-of-the-alliance-fostered-ally-dies-draw'] = [
    'testedCards' => ['ojwk0pw0y6'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Crest of the Alliance
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Combat damage strips FOSTERED from the target immediately, in the same DealDamage call that
    // applies the lethal hit (CombatLogic.php ~4964-4968: "Foster tracking: mark that this unit
    // received damage and remove fostered state"), so by the time DoAllyDestroyed's IsFostered()
    // check runs, a combat-killed ally is never still fostered. Undeniable Truth's own "mandatory
    // sacrifice of an ally" additional cost (GameLogic.php's DoActivateCard cost-declaration
    // switch, $hasUndeniableTruthCost) removes the ally via DoSacrificeFighter -> DoAllyDestroyed
    // directly, with no intervening damage, so the FOSTERED tag is still present when the "does a
    // fostered ally you control die" check runs.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'ojwk0pw0y6'], // Crest of the Alliance (added first, so sacrificing the ally below it doesn't shift its own index)
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['TurnEffects' => ['FOSTERED']]], // Dungeon Guide, fostered
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'UaUfw7yFTW'], // Undeniable Truth, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Bulwark Sword: [Class Bonus] +1 POWER; additional attack cost pay (2) ---
$fixtures['bulwark-sword-class-bonus-attack-cost'] = [
    'testedCards' => ['8kmoi0a5uh'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Bulwark Sword
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Weapons need a "durability" counter to be considered available to attack with
    // (GetAvailableWeapons in CombatLogic.php); SWORD weapons (unlike GUN/BOW/AETHERWING) don't
    // need to be "loaded". Same turn-cycle approach as automaton-bomber-ranged-attack (Rule 1.h
    // blocks player 1's own turn-1 attack): player 1 ends turn 1, then player 2's champion
    // attacks using Bulwark Sword (with a GUARDIAN Class Bonus champion also on their field),
    // paying the weapon's own additional 2-reserve attack cost.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'zb14m4c8lj'], // Tonoris, Lone Mercenary (GUARDIAN champion) - Class Bonus source
        ['player' => 2, 'zone' => 'myField', 'cardID' => '8kmoi0a5uh', 'setProperties' => ['Counters' => ['durability' => 1]]], // Bulwark Sword, usable
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Archon Broadsword: additional attack cost pay (2); [Class Bonus] +1 POWER per token ---
$fixtures['archon-broadsword-class-bonus-token-power'] = [
    'testedCards' => ['pyx8bd7ozu'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Archon Broadsword
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Same turn-cycle/durability-counter approach as bulwark-sword-class-bonus-attack-cost.
    // Two Automaton Drone tokens are seeded onto player 2's field (alongside a GUARDIAN Class
    // Bonus champion) so the "+1 POWER for each token you control" clause has something to count.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'zb14m4c8lj'], // Tonoris, Lone Mercenary (GUARDIAN champion) - Class Bonus source
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'mu6gvnta6q'], // Automaton Drone token #1
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'mu6gvnta6q'], // Automaton Drone token #2
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'pyx8bd7ozu', 'setProperties' => ['Counters' => ['durability' => 1]]], // Archon Broadsword, usable
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-4', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Heavy Swing: [Class Bonus] costs 2 less to activate ---
$fixtures['heavy-swing-class-bonus-discount'] = [
    'testedCards' => ['kvoqk1l75t'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Heavy Swing
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Heavy Swing is an ATTACK card (its printed 6 POWER becomes the intent-loaded attack bonus,
    // out of scope here) with a straightforward [Class Bonus] reserve discount: 6 - 2 = 4.
    // CanActivateAttackCardNow (GameLogic.php ~1246) applies Rule 1.h to activating ANY ATTACK-
    // type card, not just declaring a real attack, so playing it also needs player 2's turn (Rule
    // 1.h only locks the opening player's own turn 1); the discount is confirmed by needing only
    // 4 reps of the myHand-0 reserve-payment decision, not 6.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'zb14m4c8lj'], // Tonoris, Lone Mercenary (GUARDIAN champion) - Class Bonus source
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'kvoqk1l75t'], // Heavy Swing, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Rousing Slam: [Class Bonus] [Level 2+] On Attack: attacker gains vigor and taunt ---
$fixtures['rousing-slam-class-bonus-level-2-on-attack-vigor-taunt'] = [
    'testedCards' => ['v5klryvfq3'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Rousing Slam
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // PlayerLevel($player) >= 2 is checked against the CHAMPION's own level, so patching the
    // champion directly to Tonoris, Might of Humanity (level 2, GUARDIAN) satisfies both the
    // [Class Bonus] and [Level 2+] gates in one step (same CardID-patch bypass used for the Diana
    // champion-lineage fixtures -- CanChampionLevelUpIntoCard-style level requirements only check
    // current state, not lineage). Rousing Slam is WIND (advanced element), so the champion's
    // Subcards also carry a WIND lineage unlock. Rule 1.h applies to activating any ATTACK-type
    // card, so it's played on player 2's turn (after player 1 ends turn 1), then the champion
    // declares a real attack for OnAttack to fire.
    'setup' => [
        ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'yevpmu6gvn', 'Subcards' => ['pNiyaGlIe7']]], // Tonoris, Might of Humanity (level 2, GUARDIAN) + WIND lineage unlock
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'v5klryvfq3'], // Rousing Slam, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline an ambient fast-opportunity window
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline the active-response opportunity for the spell on the stack
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target player 1's champion -- Rousing Slam's own effect IS the attack
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline a post-resolution opportunity window
    ],
];

// --- Spirit of Wind: On Enter -- Draw seven cards ---
$fixtures['spirit-of-wind-enter-draw-seven'] = [
    'testedCards' => ['pNiyaGlIe7'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Wind
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Spirit of Wind is a level-0 champion used as the pregame starting-champion material choice
    // in place of the usual Spirit of Fire. Its On Enter ability ("Draw seven cards") fires
    // synchronously during pregame resolution (PREGAME_CHOOSE_STARTING_CHAMPION's unconditional
    // Enter() call, GameLogic.php ~1190-1200) -- confirmed via a standalone debug script tracing
    // hand count through the exact pregame sequence: hand goes from 0 to 7 the instant Enter()
    // fires, BEFORE initial_gamestate.txt is even captured. Note Spirit of Fire (used by every
    // other fixture's Material section) has the IDENTICAL "On Enter: Draw seven cards" text, so
    // there is no separate/default opening-hand mechanic to diff against -- every single fixture
    // in this suite already exercises this exact ability text via Spirit of Fire; this fixture
    // exists purely to record dedicated coverage against pNiyaGlIe7's own card ID. A zone_count
    // assertion of 7 on myHand (the deck has 0 other draw effects before this point) is therefore
    // the correct and only meaningful check: if the On Enter ability failed to fire, hand would
    // be 0, not 7.
    'setup' => [],
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''], // harmless no-op click
    ],
];

// --- Tonoris, Lone Mercenary: On Enter, gain taunt until beginning of next turn ---
$fixtures['tonoris-lone-mercenary-on-enter-taunt'] = [
    'testedCards' => ['zb14m4c8lj'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Tonoris, Lone Mercenary
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Tonoris, Lone Mercenary is level 1, one level above the default level-0 starting champion
    // (Spirit of Fire), so no lineage/element patch is needed -- 0+1 is already a legal level-up
    // (same shape as dante-prodigal-swain-summon-token/lorraine-wandering-warrior-levelup). Its
    // NORM element is always playable. Champion-swap materialization is only offered through the
    // material-phase MZMAYCHOOSE at the start of a turn, so both players end their first turn
    // (P1 -> P2) to reach that prompt on P1's next turn. Its printed cost is 1 memory, paid from a
    // filler card seeded directly into myMemory. Choosing it completes the swap and its On Enter
    // ability (GeneratedMacroCode.php enterAbilities["zb14m4c8lj:0"]) calls
    // AddTurnEffect($mzID, "TAUNT_NEXT_TURN") on the champion itself.
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card in memory to pay Tonoris, Lone Mercenary's 1-memory level-up cost
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Tonoris, Might of Humanity: On Enter, next attack this turn gets +3 POWER ---
$fixtures['tonoris-might-of-humanity-on-enter-next-attack-power'] = [
    'testedCards' => ['yevpmu6gvn'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Tonoris, Might of Humanity
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Tonoris, Might of Humanity is level 2 (Tonoris Lineage), but CanChampionLevelUpIntoCard only
    // checks $targetLevel === $currentLevel + 1 (GameLogic.php ~19487-19501) -- lineage text is not
    // enforced by the level-up gate itself (same bypass used for rousing-slam-class-bonus and the
    // Diana champion-lineage fixtures this session). The starting champion is patched directly to
    // Tonoris, Lone Mercenary (zb14m4c8lj, level 1) so a REAL level-up into Might of Humanity is
    // legal; patching the CardID (rather than leveling up twice) is fine here since we don't care
    // about Lone Mercenary's own On Enter firing, only Might of Humanity's. NORM element needs no
    // lineage/Subcards patch to be playable. Champion-swap materialization is only offered through
    // the material-phase MZMAYCHOOSE at the start of a turn, so both players end their first turn
    // (P1 -> P2) to reach that prompt on P1's next turn; its 2-memory cost is paid from two filler
    // cards seeded directly into myMemory. Choosing it completes the swap and its On Enter ability
    // (GeneratedMacroCode.php enterAbilities["yevpmu6gvn:0"]) calls
    // AddTurnEffect($mzID, "yevpmu6gvn") on the champion itself -- CombatLogic.php ~1688 reads and
    // consumes that exact marker to grant +3 POWER on the champion's next attack, so asserting the
    // marker is present directly confirms the On Enter ability fired.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zb14m4c8lj']], // Tonoris, Lone Mercenary (level 1) - level-up precondition
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card 1/2 in memory to pay Might of Humanity's 2-memory level-up cost
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card 2/2
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Tonoris, Genesis Aegis: at recollection, choose an Obelisk token not yet chosen ---
$fixtures['tonoris-genesis-aegis-recollection-obelisk'] = [
    'testedCards' => ['ta6qsesw2u'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Genesis Aegis's recollection trigger is a plain CardID switch-case inside
    // ResolveBeforeRecollectionPhaseStart (GameLogic.php ~9089, same dispatcher used for Foster
    // processing) rather than a materialized-Enter/activateAbility macro, so the starting champion
    // is patched directly to ta6qsesw2u -- no real level-up/lineage/element unlock needed, since
    // this switch only reads the already-on-field object's CardID, not how it got there (same
    // reasoning as the Foster class-bonus champion patches this session). Reaching player 1's own
    // recollection phase just needs the established 3-action shortcut: player 1 ends turn 1,
    // player 2 ends their turn (still global turn 1), then player 1 declines their own MAT-phase
    // materialize offer, auto-advancing into player 1's own BREC. TonorisRecollection()
    // (CardDQHandlers.php ~2350) finds no obelisks in Counters['tonoris_chosen'] yet, so it queues
    // an MZCHOOSE over all three myTempZone Obelisk choices; choosing myTempZone-0 (Obelisk of
    // Armaments, wk0pw0y6is) summons it onto the field via TonorisChooseObelisk and records the
    // choice.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'ta6qsesw2u']], // Tonoris, Genesis Aegis
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends player 2's turn (still global turn 1)
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline player 1's own MAT-phase materialize offer -> reaches BREC/Tonoris recollection processing
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myTempZone-0', 'chkInput' => [], 'inputText' => ''], // choose Obelisk of Armaments
    ],
];

// --- Assemble the Ancients: sacrifice domains, summon that many buffed Automaton Drone tokens ---
$fixtures['assemble-the-ancients-domain-sacrifice-tokens'] = [
    'testedCards' => ['moi0a5uhjx'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Assemble the Ancients is NEOS (advanced element), so the starting champion's Subcards are
    // patched with a real NEOS champion (Tonoris, Creation's Will) to unlock element access. Two
    // Palatial Concourse domains (a plain DOMAIN with only an unrelated recollection-phase
    // trigger, chosen to avoid any On Enter interference from the summoned tokens) are seeded onto
    // the field so AssembleAncientsSacrifice (CardDQHandlers.php ~2026) has something to offer.
    // Its own MZMAYCHOOSE loop is repeated twice (sacrificing both domains via DoSacrificeFighter)
    // then declined, which calls AssembleAncientsFinalize(player, 2): summons 2 Automaton Drone
    // tokens, each entering rested (Status=1) with 2 buff counters and a VIGOR_EOT turn effect.
    // Its printed cost is 3 reserve, paid from three more hand cards.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['n2jnltv5kl']]], // NEOS lineage/element unlock (Tonoris, Creation's Will)
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'c7wklzjmwu'], // Palatial Concourse (DOMAIN) #1
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'c7wklzjmwu'], // Palatial Concourse (DOMAIN) #2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'moi0a5uhjx'], // Assemble the Ancients, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // sacrifice domain #1
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // sacrifice domain #2 (reindexed after #1's removal)
    ],
];

// --- Imperial Sentry: [Class Bonus] Intercept redirect ---
$fixtures['imperial-sentry-class-bonus-intercept'] = [
    'testedCards' => ['plywc08c9h'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Same shape as swift-recruit-intercept-redirect, except Imperial Sentry's Intercept is
    // [Class Bonus]-gated (HasKeyword_Intercept, GeneratedKeywordCode.php ~487, checks
    // IsClassBonusActive($player, CardClasses($cardId))), so player 1 (the defender)'s champion is
    // patched to a GUARDIAN champion (Tonoris, Lone Mercenary) to satisfy it -- unlike Swift
    // Recruit's unconditional Intercept, a plain field seed alone is not enough here.
    // GetAvailableInterceptRedirectTargets (CombatLogic.php:605-624) only offers a redirect when
    // the attack's TARGET is a CHAMPION, so this needs the opponent (P2) attacking P1's champion.
    // P1 ends turn 1 (nothing to do), P2 declines their MAT-phase offer, then P2's champion attacks
    // P1's champion using a weapon (Spirit of Fire has no base POWER). The resulting
    // "Choose_an_interceptor" MZMAYCHOOSE for P1 offers Imperial Sentry (awake, ALLY, HasIntercept
    // now satisfied); redirecting moves the attack's target to it, and the following "Retaliate?"
    // decision is declined so the resulting combat damage lands on Imperial Sentry instead of the
    // champion.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zb14m4c8lj']], // Tonoris, Lone Mercenary (GUARDIAN) - Class Bonus source
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'plywc08c9h'], // Imperial Sentry, awake by default
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'zv6yp6q7zw'], // Executioner's Spear (1 POWER), P2's own field -- Spirit of Fire has no base POWER, so a weapon is needed for a legal attack
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // P1 declines their own MAT-phase materialize offer first
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // P1 formally ends turn 1 (nothing to do)
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // P2 declines their MAT-phase materialize offer
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''], // P2's champion attacks
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // choose Executioner's Spear as the weapon
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target P1's champion
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // redirect to Imperial Sentry
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline Retaliate
    ],
];

// --- Smash with Obelisk (2kkvoqk1l7): mandatory domain sacrifice as additional cost; gets +X
// POWER where X is the sacrificed domain's reserve cost. Engine gap: the "Play prereq" (requires a
// domain on field, see activateCardPrereqs["2kkvoqk1l7:0"]) was wired via the schema/generator, but
// DoActivateCard's hand-written cost-declaration switch (GrandArchiveSim/Custom/GameLogic.php) never
// queued the pre-existing SmashWithObeliskSacrifice DQ handler, so playing the card skipped the
// sacrifice entirely and the ability macro's "smashObeliskBonus" read (GeneratedMacroCode.php) always
// saw an unset variable, resolving as +0 POWER. Fixed by wiring $hasSmashObeliskCost the same way as
// the other mandatory-sacrifice additional-cost cards (Undeniable Truth, Blazing Throw).
$fixtures['smash-with-obelisk-domain-sacrifice-bonus'] = [
    'testedCards' => ['2kkvoqk1l7'],
    'deck' => <<<'DECK'
# Main
4 Recruitment Officer
4 Recruitment Officer
4 Recruitment Officer
4 Recruitment Officer
4 Recruitment Officer
# Material
1 Spirit of Wind
DECK,
    // Grant NEOS element access (Smash with Obelisk's element) by patching the starting champion's
    // Subcards with a NEOS lineage card -- the harness's documented technique for reaching an
    // advanced element without scripting a real level-up sequence (GetChampionLineage() walks
    // $obj->Subcards; see GrandArchiveSim/Custom/GameLogic.php ~19370-19446). Then seed a plain,
    // rules-text-free DOMAIN (Wool Brook, reserve cost 4) onto the field to sacrifice, and seed
    // Smash with Obelisk plus 3 reserve-payment fodder cards directly into hand so the fixture only
    // has to replay the actual activation, not deck/draw setup. Everything is seeded for PLAYER 2:
    // CanActivateAttackCardNow()/IsFirstTurnAttackLocked() (GrandArchiveSim/Custom/GameLogic.php)
    // forbid the true first player from playing an ATTACK card on turn 1, so player 1's turn is
    // passed through first (below) and player 2 -- never "the first player" -- plays the card on
    // their own first turn instead. The starting champion is always the first (and here, only)
    // object placed on a fresh field, so it lands at myField-0.
    'setup' => [
        ['patchMzId' => 'myField-0', 'player' => 2, 'setProperties' => ['Subcards' => ['n2jnltv5kl']]], // Tonoris, Creation's Will (NEOS)
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'lcCGyyNGuM'], // Wool Brook (DOMAIN, reserve cost 4, no rules text)
        ['player' => 2, 'zone' => 'myHand', 'cardID' => '2kkvoqk1l7'], // Smash with Obelisk
        ['player' => 2, 'zone' => 'myHand', 'cardID' => '1x97n2jnlt'], // Recruitment Officer (reserve fodder)
        ['player' => 2, 'zone' => 'myHand', 'cardID' => '1x97n2jnlt'], // Recruitment Officer (reserve fodder)
        ['player' => 2, 'zone' => 'myHand', 'cardID' => '1x97n2jnlt'], // Recruitment Officer (reserve fodder)
    ],
    // myHand-7 confirmed from the "Setup: added ..." mzID log (opening hand under seed=42 is
    // myHand-0..6, so Smash with Obelisk lands at myHand-7 and the 3 fodder cards follow it).
    'actions' => [
        // Pass through player 1's turn 1 (nothing to do), matching the pattern verified in
        // wind-cutter-class-bonus-power-attack for reaching player 2's own turn.
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // P1 declines their own MAT-phase materialize offer
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // P1 formally ends turn 1
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // P2 declines their MAT-phase materialize offer
        // Free play: P2 plays Smash with Obelisk
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        // MZCHOOSE: sacrifice the domain (Wool Brook)
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        // Pay reserve cost (3x, one MZCHOOSE per reserve payment). Each payment removes a card and
        // reindexes the hand zone, so the next fodder card always shifts down into myHand-8.
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-8', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-8', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-8', 'chkInput' => [], 'inputText' => ''],
        // Pass fast action opportunities
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Academy Attendant: [Class Bonus][Memory 4+] +1 POWER ---
$fixtures['academy-attendant-class-bonus-memory-power'] = [
    'testedCards' => ['m4c8ljyevp'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Academy Attendant's static +1 POWER (GameLogic.php ~11255) is a plain
    // IsClassBonusActive(["CLERIC"]) + "4+ cards in memory" check with no turn-cycle needed at all
    // -- the champion is patched directly to Arisanna, Master Alchemist (CLERIC) and 4 filler cards
    // are seeded into myMemory. Verified via computed_power_equals (base 2 + 1 = 3) rather than a
    // real combat sequence.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'ltv5klryvf']], // Arisanna, Master Alchemist (CLERIC)
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'm4c8ljyevp'], // Academy Attendant
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''], // harmless no-op click
    ],
];

// --- Synth Disrupter: banish, Automaton allies enter the field rested until end of turn ---
$fixtures['synth-disrupter-banish-automaton-rested'] = [
    'testedCards' => ['z1vdxi74wa'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Synth Disrupter's banish ability just sets a global effect flag (z1vdxi74wa_RESTED,
    // GeneratedMacroCode.php); the actual effect (Automaton allies enter the field rested) is
    // checked in FieldAfterAdd (GameLogic.php ~8256). ENGINE BUG FOUND AND FIXED: Synth Disrupter's
    // "Banish [self]:" cost was missing from the hardcoded "always banish self" fallthrough switch
    // in ActivatedAbilityCost (GameLogic.php ~6142-6194, alongside ~50 other banish-self cards) --
    // selecting its fast-action opportunity choice fired the ability's effect but never actually
    // removed the card, so it stayed on the field and kept re-offering itself. Fixed by adding
    // z1vdxi74wa (and bHGUNMFLg9, Wind Resonance Bauble, found missing the same way) to that list.
    // Materializing Automaton Beastkeeper (a plain AUTOMATON ally with an optional Class Bonus On
    // Enter, out of scope) queues its own reserve payment then an EffectStackOpportunity BEFORE it
    // actually resolves onto the field -- answering that opportunity with Synth Disrupter's banish
    // choice (instead of declining) lets the global effect apply in time for Beastkeeper's own
    // FieldAfterAdd check, confirmed by its Status landing rested (1) instead of the default awake
    // (2). (Using "attempt to pass" to reach the opportunity instead would end player 1's turn
    // outright once nothing else responds -- it only works standalone, as in sweet-ambrosia-banish-
    // recover, not when a same-turn follow-up action is still needed.)
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'z1vdxi74wa'], // Synth Disrupter
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'i5jnsl7ddc'], // Automaton Beastkeeper, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''], // materialize Automaton Beastkeeper
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay reserve 1/4
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay reserve 2/4
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay reserve 3/4
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay reserve 4/4
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1@Activate-0@Banish', 'chkInput' => [], 'inputText' => ''], // banish Synth Disrupter during the pre-resolution opportunity
    ],
];

// --- Ingredient Pouch: (1), REST: Gather ---
$fixtures['ingredient-pouch-rest-gather'] = [
    'testedCards' => ['u7d6soporh'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Ingredient Pouch's REST ability pays 1 reserve then Gathers (summons one of six possible
    // resource tokens at random -- resolves to Blightroot with this fixture's seed, same as
    // foraging-servant-enter-gather). Field items with an activated ability are not clickable via a
    // plain myField-N!FSM! action -- their ability is offered as a fast-action MZMAYCHOOSE
    // opportunity once the turn player attempts to pass, answered with the encoded
    // "{mzID}@Activate-{abilityIndex}@{label}" choice string (same shape as sweet-ambrosia-banish-
    // recover). Blightroot itself carries a fast "Sacrifice:" ability, so the AbilityOpportunity
    // window that opens after Gather resolves re-offers it once more even after the first decline
    // (a fresh priority round re-checks the still-available fast ability) -- a second explicit
    // decline is needed to fully close out and leave the decision queue empty.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'u7d6soporh'], // Ingredient Pouch
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // attempt to pass -> offers the fast-action opportunity
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1@Activate-0@Gather', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline the gathered Blightroot's own fast Sacrifice ability (1/2)
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline again -- the same fast ability is re-offered in the next priority round (2/2)
    ],
];

// --- Cosmic Astroscope: REST: Glimpse 3 ---
$fixtures['cosmic-astroscope-rest-glimpse'] = [
    'testedCards' => ['qj5bbae3z4'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Cosmic Astroscope's REST ability is a plain, free (memory cost 0) Glimpse 3 -- no reserve
    // payment queued. Only the base Glimpse is covered; the [Class Bonus] "opponent glimpses 3
    // instead" replacement effect is a passive/reusable-elsewhere property and out of scope. Field
    // items with an activated ability are not clickable via a plain myField-N!FSM! action -- their
    // ability is offered as a fast-action MZMAYCHOOSE opportunity once the turn player attempts to
    // pass, answered with the encoded "{mzID}@Activate-{abilityIndex}@{label}" choice string.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'qj5bbae3z4'], // Cosmic Astroscope
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // attempt to pass -> offers the fast-action opportunity
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1@Activate-0@Glimpse', 'chkInput' => [], 'inputText' => ''],
        // MZREARRANGE response: submit the same "Top=...;Bottom=" param verbatim to keep original
        // order (same no-op default GoldfishChooseAction uses for this decision type) -- confirmed
        // via direct probe against this exact deck/seed since the glimpsed card IDs depend on the
        // deterministic shuffle.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Scale of Souls: (2), REST: Return a card from your memory to your hand ---
// ENGINE BUG FOUND AND FIXED: Scale of Souls' ability is registered in $cardActivatedAbilities
// (GeneratedMacroCode.php, the same dictionary used for reserve-cost cards played from
// hand/material via DoActivateCard/ActivateCard), NOT in $activateAbilityAbilities (the
// free/memory-cost-0 dictionary field items like Ingredient Pouch and Cosmic Astroscope use, invoked
// via ActivateAbility/DoActivatedAbility). But GrandArchiveSim/Custom/CustomInput.php's
// "myField"/"myIntent" case only ever called ActivateAbility() for a field-resident object's direct
// activate click -- DoActivatedAbility's $staticAbilityCount came back 0 for this card (it has no
// $activateAbilityAbilities entry), so ability index 0 was misclassified as a "dynamic" ability that
// matched nothing and the whole activation silently no-opped. Fixed by having CustomInput.php route
// to ActivateCard() instead whenever CardActivateAbilityCount() is 0 but CardCardActivatedCount() is
// nonzero for the target's CardID -- this affects every field-resident REGALIA/ITEM with a
// repeatable ability only reachable this way (not via the fast-opportunity MZMAYCHOOSE path the
// other myField@Activate fixtures in this suite use), so this fixture drives the direct,
// non-opportunity mode=10001 CustomInput click that was previously dead.
// SEPARATE, NOT-YET-FIXED GAP FOUND: the card's own "(2)" reserve cost is not wired up --
// CardCost_reserve('0z2snsdwmx') returns -1 (unset) in the CardEditor card-ability database, so
// CalculateActivationReserveCost() computes no reserve cost and DoActivateCard() charges nothing.
// This is a card-data gap in the CardEditor database (separate from the CustomInput.php routing
// fix), out of scope here -- this fixture reflects the ability's actual current (free) cost rather
// than fabricating a reserve payment that doesn't really happen; no myHand-N cost-payment action is
// needed before the MZCHOOSE.
$fixtures['scale-of-souls-rest-return-memory'] = [
    'testedCards' => ['0z2snsdwmx'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => '0z2snsdwmx'], // Scale of Souls
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // Fairy Whispers, filler card to return from memory
    ],
    'actions' => [
        // Direct field click via the normal client action path (not the fast-opportunity
        // workaround) -- only reachable now that CustomInput.php routes this to ActivateCard().
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMemory-0', 'chkInput' => [], 'inputText' => ''], // choose the filler card to return to hand
    ],
];

// --- Barter Herbs: sacrifice up to two Herbs, summon that many chosen replacement Herb tokens ---
$fixtures['barter-herbs-sacrifice-summon'] = [
    'testedCards' => ['p5af098kmo'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Barter Herbs (NORM, reserve 1) sacrifices up to two Herb-subtype objects, choosing a
    // replacement Herb token for each one sacrificed (BarterHerbsSacrificeLoop, PotionLogic.php
    // ~664). Two Blightroot HERB tokens are seeded onto the field to sacrifice; each is replaced
    // with Manaroot (chosen from the six-option temp-zone menu). Only the base sacrifice/summon
    // loop is covered; the [Class Bonus] Floating Memory clause is a passive/reusable-elsewhere
    // property and out of scope.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #1
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'p5af098kmo'], // Barter Herbs, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay reserve 1
        // Decline the ambient opportunity offering the seeded Blightroots' OWN fast Sacrifice
        // ability (a different mechanic than Barter Herbs' own sacrifice loop below).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // sacrifice Blightroot #1
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myTempZone-1', 'chkInput' => [], 'inputText' => ''], // choose Manaroot as replacement
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // sacrifice Blightroot #2
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myTempZone-1', 'chkInput' => [], 'inputText' => ''], // choose Manaroot as replacement
    ],
];

// --- Stream of Consciousness: Draw a card into memory ---
$fixtures['stream-of-consciousness-draw-into-memory'] = [
    'testedCards' => ['wa4x7e22tk'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Stream of Consciousness is WATER -- confirmed (via CanPlayerMeetCardElementRequirements /
    // GetPlayerEnabledElements, GameLogic.php ~19412-19442) that EVERY non-NORM element, not just
    // the "advanced" ones (NEOS/ASTRA/TERA), requires a lineage unlock; only NORM is free. The
    // starting champion's Subcards are patched with a real WATER champion (Nico, Rapture's
    // Embrace) to unlock element access. Base case only: without a CLERIC Class Bonus champion, it
    // unconditionally draws a card into memory. The [Class Bonus][Memory 4+] Glimpse 3 clause is
    // out of scope (a separate, richer effect requiring both a class-bonus champion patch and 4
    // memory cards seeded).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['29lqrve8fz']]], // WATER lineage/element unlock (Nico, Rapture's Embrace)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'wa4x7e22tk'], // Stream of Consciousness, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Potion of Healing: Brew (Two Herbs); Sacrifice: Recover 5 ---
$fixtures['potion-of-healing-brew-sacrifice-recover'] = [
    'testedCards' => ['qtb31x97n2'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Potion of Healing's element is NORM, so no lineage patch is needed. Brewing is an alternate
    // cost (sacrifice two Herb-subtype objects instead of paying reserve); two Blightroot tokens
    // are seeded onto the field to pay it. Sacrifice: Recover 5 is unconditional (no "if brewed"
    // gate, unlike Distilled Water), so it's testable via a direct follow-up Activate:0 click once
    // the brewed Potion has resolved onto the field (same shape as distilled-water-brew-sacrifice-
    // draw's own trailing step). The champion is pre-damaged by 6 so Recover 5 leaves 1 damage
    // remaining, distinguishing "recovered" from "already at full life."
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Damage' => 6]], // pre-damage champion
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #1 - brew ingredient
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #2 - brew ingredient
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'qtb31x97n2'], // Potion of Healing, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Serum of Wisdom: Brew (Three Herbs); Sacrifice: Glimpse 3, draw a card into memory ---
$fixtures['serum-of-wisdom-brew-sacrifice-glimpse-draw'] = [
    'testedCards' => ['bae3z4pyx8'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Serum of Wisdom's element is NORM. Brewing sacrifices three Herb-subtype objects instead of
    // paying reserve; three Blightroot tokens are seeded onto the field to pay it. Sacrifice:
    // Glimpse 3 (an MZREARRANGE decision, resolved keeping original order, same no-op default used
    // in idle-thoughts-glimpse-4) then queues a draw into memory.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #1 - brew ingredient
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #2 - brew ingredient
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #3 - brew ingredient
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'bae3z4pyx8'], // Serum of Wisdom, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-3', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        // MZREARRANGE response: keep original order (same no-op default as idle-thoughts-glimpse-4).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Essence of Blizzards: Brew (One Adjuvant, One Catalyst); Sacrifice: deal 1 damage ---
$fixtures['essence-of-blizzards-brew-sacrifice-damage'] = [
    'testedCards' => ['k1l75tlzsm'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Essence of Blizzards' element is WATER, so the starting champion's Subcards are patched with
    // a real WATER champion (Nico, Rapture's Embrace) for element access. Brewing sacrifices one
    // ADJUVANT-subtype and one CATALYST-subtype object instead of paying reserve; Manaroot
    // (ADJUVANT) and Blightroot (CATALYST) are seeded to pay it. Unlike the HERB-count brews, its
    // own self-move-to-graveyard is baked directly into the ability macro (CardDQHandlers-style
    // handler, GeneratedMacroCode.php) rather than the generic ActivatedAbilityCost switch, so no
    // engine-gap risk there. Only the base "deal 1 damage" case against an awake target is covered;
    // the "if rested, deal 1+LV instead" and "allies enter rested until EOT" clauses are out of
    // scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['29lqrve8fz']]], // WATER lineage/element unlock (Nico, Rapture's Embrace)
        ['player' => 1, 'zone' => 'myField', 'cardID' => '5joh300z2s'], // Manaroot (ADJUVANT) - brew ingredient
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (CATALYST) - brew ingredient
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'k1l75tlzsm'], // Essence of Blizzards, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
    ],
];

// --- Condensed Supernova: Brew (Silvershine + 2 Adjuvants + 2 Catalysts); Sacrifice: LV damage to all, Glimpse 4 ---
$fixtures['condensed-supernova-brew-sacrifice-damage-glimpse'] = [
    'testedCards' => ['14m4c8ljye'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Condensed Supernova's element is ASTRA (advanced), so the starting champion's Subcards are
    // patched with a real ASTRA champion (Arisanna, Astral Zenith) for element access -- her
    // patch also conveniently sets Counters.level = 3 so LV damage is directly observable (0 would
    // be invisible). Brewing sacrifices a specific Silvershine plus two more ADJUVANT-subtype and
    // two more CATALYST-subtype objects instead of paying reserve; Silvershine itself is also
    // CATALYST but the brew cost lists it as a separate named-card requirement from the generic
    // "2 Catalysts," so two additional Blightroot (CATALYST) plus two Manaroot (ADJUVANT) are
    // seeded alongside it (5 ingredients total). Sacrifice hits every non-ASTRA-element unit on
    // both fields automatically (no target choice) for LV damage, then Glimpses 4 (resolved
    // keeping original order, same no-op default used in idle-thoughts-glimpse-4).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba'], 'Counters' => ['level' => 3]]], // ASTRA lineage/element unlock (Arisanna, Astral Zenith) + LV damage
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'bd7ozuj68m'], // Silvershine - named brew ingredient
        ['player' => 1, 'zone' => 'myField', 'cardID' => '5joh300z2s'], // Manaroot (ADJUVANT) #1
        ['player' => 1, 'zone' => 'myField', 'cardID' => '5joh300z2s'], // Manaroot (ADJUVANT) #2
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (CATALYST) #1
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (CATALYST) #2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '14m4c8ljye'], // Condensed Supernova, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-3', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-4', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-5', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        // MZREARRANGE response: keep original order (same no-op default as idle-thoughts-glimpse-4).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Krustallan Distiller: [Class Bonus] On Enter, if brewed a Potion this turn, draw into memory ---
$fixtures['krustallan-distiller-class-bonus-brewed-draw'] = [
    'testedCards' => ['c08c9htu9a'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Krustallan Distiller is WATER, so the starting champion's Subcards are patched with a real
    // WATER champion (Nico, Rapture's Embrace) for element access; that same champion's CardID is
    // ALSO patched directly onto the field so IsClassBonusActive(["CLERIC"]) is NOT satisfied by
    // it -- Nico is GUARDIAN, so instead the champion is patched to Arisanna, Master Alchemist
    // (CLERIC) directly for the Class Bonus, with Subcards left carrying the WATER unlock. The
    // "brewed a Potion this turn" condition is reached via the BREWED_POTION global effect
    // (normally set by the real Brew-declaration flow) seeded directly at setup, matching the
    // harness's documented technique for reaching an otherwise-unreachable-at-game-start
    // precondition without scripting a full brew sequence.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'ltv5klryvf', 'Subcards' => ['29lqrve8fz']]], // Arisanna, Master Alchemist (CLERIC) + WATER lineage unlock
        ['player' => 1, 'globalEffect' => 'BREWED_POTION'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'c08c9htu9a'], // Krustallan Distiller, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Caretaker Drone: [Class Bonus] On Death: Glimpse 4 ---
$fixtures['caretaker-drone-class-bonus-death-glimpse'] = [
    'testedCards' => ['urfp66pv4n'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Caretaker Drone is NORM (no lineage patch needed). Its [Class Bonus] On Death is coded as
    // IsClassBonusActive($player) with NO class argument (GameLogic.php's IsClassBonusActive
    // treats a null $classes as "any champion present," GameLogic.php ~18079-18104), so it fires
    // unconditionally as long as a champion exists -- no champion patch needed at all. Only Intercept
    // (a shared static ability tested elsewhere) is out of scope. Undeniable Truth's own "mandatory
    // sacrifice of an ally" additional cost (GameLogic.php's DoActivateCard cost-declaration switch,
    // $hasUndeniableTruthCost) is borrowed to kill Caretaker Drone via a non-combat removal path
    // (same technique as crest-of-the-alliance-fostered-ally-dies-draw), which triggers On Death
    // reliably.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'urfp66pv4n'], // Caretaker Drone
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'UaUfw7yFTW'], // Undeniable Truth, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // sacrifice Caretaker Drone as the mandatory cost
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // MZREARRANGE response: keep original order (same no-op default as idle-thoughts-glimpse-4).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Scry the Skies: Glimpse LV. Draw a card into your memory ---
$fixtures['scry-the-skies-glimpse-lv-draw'] = [
    'testedCards' => ['F9POfB5Nah'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Scry the Skies is NORM, no lineage patch needed. The starting champion's level is patched to
    // 2 so Glimpse LV (0 by default, which would be an invisible no-op) is directly observable via
    // a real 2-card MZREARRANGE decision.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['level' => 2]]], // LV for Glimpse LV
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // MZREARRANGE response: keep original order (same no-op default as idle-thoughts-glimpse-4).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y,em6eEh9q8y;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Flash Freeze: Negate target card activation, banish the negated card ---
$fixtures['flash-freeze-negate-target-card-activation'] = [
    'testedCards' => ['w3rrii17fz'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['29lqrve8fz']]], // Nico, Rapture's Embrace -- unlocks WATER for Flash Freeze
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies -- the card whose activation gets negated
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'w3rrii17fz'], // Flash Freeze
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Fast-opportunity window offers Flash Freeze in response, before Scry the Skies resolves.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-6', 'chkInput' => [], 'inputText' => ''],
        // Pay Flash Freeze's 4-reserve cost (no Class Bonus -- default champion isn't CLERIC).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Choose Scry the Skies (EffectStack-0) as the activation to negate.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'EffectStack-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Potion Infusion: Clarity: Rest target Potion, grant "On Sacrifice: Draw two cards" ---
$fixtures['potion-infusion-clarity-rest-grant-draw-two'] = [
    'testedCards' => ['300z2snsdw'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['29lqrve8fz']]], // Nico, Rapture's Embrace -- unlocks WATER for Potion Infusion: Clarity
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Damage' => 6]], // pre-damage champion so Recover 5 (Potion of Healing's own Sacrifice) is observable
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #1 - brew ingredient
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #2 - brew ingredient
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'qtb31x97n2'], // Potion of Healing, seeded to a known hand slot -- brewed onto the field as the Infusion's target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '300z2snsdw'], // Potion Infusion: Clarity
    ],
    'actions' => [
        // Brew Potion of Healing (sacrifice both Blightroot tokens instead of paying reserve).
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Play Potion Infusion: Clarity, paying its full 7 reserve (no Class Bonus).
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Decline the standing fast-opportunity to Sacrifice the brewed Potion early -- Infusion
        // must resolve first so the granted "On Sacrifice: Draw two" bonus is in place.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Target the brewed Potion of Healing (myField-1) to rest it and grant the bonus.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        // Activate the Potion's own Sacrifice ability -- triggers the granted "draw two" before it.
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Potion Infusion: Starlight: Rest target Potion, grant "On Sacrifice: champion +4 level" ---
$fixtures['potion-infusion-starlight-rest-grant-champion-level'] = [
    'testedCards' => ['6qsesw2ugm'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Only the base ability (Rest target Potion, grant "On Sacrifice: champion +4 level") is
    // covered; [Class Bonus] Starcalling -- (1) needs a full starcalling reveal-and-choose flow
    // (a mechanic not otherwise scripted this session) and is out of scope. No "computed level"
    // assertion type exists in this harness (only computed_power_equals/computed_life_equals), so
    // the grant is confirmed via the champion's own TurnEffects list carrying "INFUSION_STARLIGHT"
    // after the Potion's Sacrifice ability resolves, rather than a numeric level readout.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba']]], // Arisanna, Astral Zenith -- unlocks ASTRA for Potion Infusion: Starlight
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #1 - brew ingredient
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'i0a5uhjxhk'], // Blightroot (HERB) #2 - brew ingredient
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'qtb31x97n2'], // Potion of Healing, seeded to a known hand slot -- brewed onto the field as the Infusion's target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '6qsesw2ugm'], // Potion Infusion: Starlight
    ],
    'actions' => [
        // Brew Potion of Healing (sacrifice both Blightroot tokens instead of paying reserve).
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Play Potion Infusion: Starlight, paying its full 3 reserve (no Class Bonus).
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Decline the standing fast-opportunity to Sacrifice the brewed Potion early -- Infusion
        // must resolve first so the granted "On Sacrifice: champion +4 level" bonus is in place.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Target the brewed Potion of Healing (myField-1) to rest it and grant the bonus.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        // Activate the Potion's own Sacrifice ability -- triggers the granted "+4 level" before it.
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Arisanna, Astral Zenith: Once per turn, pay (0) rather than a card's starcalling costs ---
$fixtures['arisanna-astral-zenith-free-starcalling'] = [
    'testedCards' => ['q3huqj5bba'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // The starting champion's CardID is patched directly to Arisanna, Astral Zenith (q3huqj5bba) --
    // ChampionHasInLineage() checks [champion's own CardID, ...Subcards], so this trivially
    // satisfies "Arisanna Lineage" and also unlocks ASTRA (its own element) for Cometfall's
    // Starcalling. Its level is patched to 1 so Scry the Skies' "Glimpse LV" (0 by default) reveals
    // exactly one card. The deck's top card (myDeck-0) is patched directly to Cometfall
    // (4d5vettczb, Starcalling -- (2)) so that single glimpsed card is a starcalling candidate.
    // GetStarcallingCost() internally calls EnsureArisannaFreeStarcallingEligibility() on every
    // call (including the candidate pre-check during Glimpse), so the cost is already forced to 0
    // before the player is even offered the choice -- no reserve payment is queued when starcalled.
    // Confirmed via Cometfall's own effect resolving (3 damage to all non-astra units) with zero
    // reserve spent, and the once-per-turn "ArisannaFreeStarcallingUsed" flag being set afterward
    // (GameLogic.php's MarkArisannaFreeStarcallingUsed/HasUsedArisannaFreeStarcalling) -- not
    // independently re-testable within one fixture, so only the single use is exercised.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'q3huqj5bba', 'Counters' => ['level' => 1]]],
        ['player' => 1, 'patchMzId' => 'myDeck-0', 'setProperties' => ['CardID' => '4d5vettczb']], // Cometfall on top of deck
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Starcall Cometfall from the glimpse popup instead of returning it to the deck.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myTempZone-0', 'chkInput' => [], 'inputText' => ''],
        // Scry the Skies' own "draw a card into memory" clause then runs its own glimpse-driven
        // draw (the same multi-card multiplier documented in stream-of-consciousness-draw-into-
        // memory / scry-the-skies-glimpse-lv-draw); accept the offered bottom order verbatim.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Bottom=em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Prototype Staff: [Level 4+] REST: put a hand card on bottom of deck, draw into memory ---
$fixtures['prototype-staff-rest-bottom-draw'] = [
    'testedCards' => ['8c9htu9agw'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Only the base REST ability (put a hand card on the deck's bottom, then draw into memory) is
    // covered; the separate [Class Bonus][Memory 4+] +1 level static clause needs an "effective
    // champion level" assertion type this harness doesn't have and is out of scope. The starting
    // champion's level is patched to 4 to match the ability's printed [Level 4+] gate, though the
    // ability's own macro body (GeneratedMacroCode.php) has no level check at all -- the gate
    // appears to be enforced only client-side/by schema metadata, not by game logic, so this
    // fixture exercises the effect unconditionally regardless. Field items with an activated
    // ability are not clickable via a plain myField-N!FSM! action -- offered as a fast-action
    // MZMAYCHOOSE opportunity once the turn player attempts to pass (same shape as sweet-ambrosia-
    // banish-recover).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['level' => 4]]], // matches the printed [Level 4+] gate
        ['player' => 1, 'zone' => 'myField', 'cardID' => '8c9htu9agw'], // Prototype Staff
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // attempt to pass -> offers the fast-action opportunity
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1@Activate-0@8c9htu9agw', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // put a hand card on the bottom of the deck
    ],
];

// --- Arisanna, Herbalist Prodigy: On Enter, Gather twice ---
$fixtures['arisanna-herbalist-prodigy-on-enter-gather-twice'] = [
    'testedCards' => ['b31x97n2jn'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Arisanna, Herbalist Prodigy
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Arisanna, Herbalist Prodigy is level 1, one level above the default level-0 starting
    // champion (Spirit of Fire), so no lineage/element patch is needed -- 0+1 is already a legal
    // level-up (same shape as dante-prodigal-swain-summon-token). Champion-swap materialization is
    // only offered through the material-phase MZMAYCHOOSE at the start of a turn, so both players
    // end their first turn (P1 -> P2) to reach that prompt on P1's next turn; its 1-memory cost is
    // paid from a filler card seeded into myMemory. Its On Enter ability Gathers twice (resolves
    // to Blightroot with this fixture's seed, same as foraging-servant-enter-gather).
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card in memory to pay the 1-memory level-up cost
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        // Decline the ambient opportunity offering the two gathered Herb tokens' own fast
        // Sacrifice abilities (a different mechanic than Gather itself).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Arisanna, Master Alchemist: On Enter, Gather twice ---
$fixtures['arisanna-master-alchemist-on-enter-gather-twice'] = [
    'testedCards' => ['ltv5klryvf'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Arisanna, Master Alchemist
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Arisanna, Master Alchemist is level 2 (Arisanna Lineage), but CanChampionLevelUpIntoCard only
    // checks targetLevel === currentLevel + 1 (GameLogic.php ~19487-19501) -- lineage text is not
    // enforced by the level-up gate itself. The starting champion is patched directly to Arisanna,
    // Herbalist Prodigy (b31x97n2jn, level 1) so a real level-up is legal; patching the CardID is
    // fine here since only Master Alchemist's own On Enter is under test. Champion-swap
    // materialization is only offered through the material-phase MZMAYCHOOSE at the start of a
    // turn, so both players end their first turn (P1 -> P2) to reach that prompt; its 2-memory cost
    // is paid from two filler cards seeded into myMemory. Its On Enter ability Gathers twice
    // (resolves to Silvershine and Blightroot with this fixture's seed, same as arisanna-herbalist-
    // prodigy-on-enter-gather-twice). Only the base On Enter is covered; the "Inherited Effect" end-
    // phase sacrifice-two-Herbs-draw clause is out of scope.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'b31x97n2jn']], // Arisanna, Herbalist Prodigy (level 1) - level-up precondition
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card 1/2 in memory to pay Master Alchemist's 2-memory level-up cost
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card 2/2
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        // Decline the ambient opportunity offering the two gathered Herb tokens' own fast
        // Sacrifice abilities (a different mechanic than Gather itself).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Hypothermia: target rested ally gets -4 LIFE until end of turn ---
$fixtures['hypothermia-target-rested-ally-life'] = [
    'testedCards' => ['cyfrzrplyw'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Hypothermia
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK,
    // Regression fixture for Database/migrations/14_grand_archive_hypothermia_rested_target_fix.sql:
    // Hypothermia's stored ability_code filtered its ally target list on $obj->Status == 2 (AWAKE,
    // GrandArchiveSim/Custom/GameLogic.php:10156) instead of == 1 (RESTED), the opposite of its
    // printed "Target rested ally gets -4 [LIFE] until end of turn." text -- a rested-only field
    // would silently no-op. Hypothermia's element is WATER (unlike the FIRE-aligned starting
    // champion used by most fixtures in this file), so the same WATER lineage/element-unlock patch
    // used by tsunami-of-nanyue-damage is reused. Dungeon Guide is seeded onto the opponent's field
    // and explicitly rested (Status=1) as the target; after the fix it's the only legal target.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['Status' => 1]], // rested Dungeon Guide - the only legal target after the fix
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'cyfrzrplyw'], // Hypothermia, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Giant Tortoise: vanilla stat check ---
$fixtures['giant-tortoise-vanilla-stats'] = [
    'testedCards' => ['L0RmNaDzhk'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Giant Tortoise is WATER and vanilla (no printed ability). The starting champion's Subcards
    // are patched with a real WATER champion (Nico, Rapture's Embrace) to unlock element access.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['29lqrve8fz']]], // Nico, Rapture's Embrace -- unlocks WATER
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'L0RmNaDzhk'], // Giant Tortoise, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Wind Resonance Bauble: Banish -- draw a card (only if opponent controls a wind champion) ---
$fixtures['wind-resonance-bauble-banish-draw'] = [
    'testedCards' => ['bHGUNMFLg9'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Wind Resonance Bauble is REGALIA -- adding it to 'myHand' via setup gets silently redirected
    // to the Material zone instead (HandAddReplacement, GameLogic.php ~17282: any REGALIA CardID
    // added to hand is rerouted to AddMaterial), matching every other REGALIA item fixture this
    // session (Synth Disrupter, Ingredient Pouch, etc.) -- it must be seeded directly onto myField.
    // Its prereq (IsPlayerElementEnabled) reads the OPPONENT's own element lineage, so P2's starting
    // champion's Subcards are patched with a real WIND champion. Field items with an activated
    // ability are not clickable via a plain myField-N!FSM! action as the very first action of a
    // fresh turn -- offered as a fast-action MZMAYCHOOSE opportunity once the turn player attempts
    // to pass, answered with the encoded "{mzID}@Activate-{abilityIndex}@{label}" choice string
    // (same shape as ingredient-pouch-rest-gather).
    'setup' => [
        ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock for P2
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'bHGUNMFLg9'], // Wind Resonance Bauble
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // attempt to pass -> offers the fast-action opportunity
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1@Activate-0@Banish', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline P2's own resulting fast-action opportunity (1/2)
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline again -- same fast opportunity re-offered in the next priority round (2/2)
    ],
];

// --- Beastbond Boots: Banish -- champion gains spellshroud (only if you control an Animal/Beast ally) ---
$fixtures['beastbond-boots-banish-spellshroud'] = [
    'testedCards' => ['xjuCkODVRx'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Beastbond Boots is REGALIA -- adding it to 'myHand' via setup gets silently redirected to the
    // Material zone instead (HandAddReplacement, GameLogic.php ~17282), matching every other
    // REGALIA item fixture this session -- it must be seeded directly onto myField. Gray Wolf (a
    // BEAST ally, also from this deck) is seeded alongside it so the prereq (control an Animal or
    // Beast ally) is already satisfied.
    // ENGINE BEHAVIOR CONFIRMED (traced via temporary error_log instrumentation, since reverted):
    // using the "attempt to pass" trick as the ONLY action to reach a field-resident item's
    // ability is NOT safe when the granted effect is "until end of turn" -- if nothing else
    // responds after the ability resolves, the original pass attempt itself completes and the turn
    // actually ends, running end-of-turn cleanup that clears the just-granted SPELLSHROUD before it
    // can ever be observed (confirmed: AddTurnEffect correctly applies it, and it is still present
    // through the entire AbilityOpportunity/GrantOpportunityWindow resolution chain, but is gone by
    // the time the action's final gamestate is written). This is the same class of issue documented
    // in synth-disrupter-banish-automaton-rested's notes ("attempt to pass ends the turn if nothing
    // else responds -- cannot be used mid-sequence"). Fixed the same way: play a real card first
    // (Scry the Skies, NORM, reserve 1) so a same-turn action has already happened, then reach the
    // Boots' ability via a direct Activate:0 click instead of the pass-trick.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf (BEAST) -- satisfies "control an Animal or Beast ally"
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'xjuCkODVRx'], // Beastbond Boots
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay Scry the Skies' 1 reserve
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-2!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Gray Wolf: Pride 2 (won't attack unless champion is level 2+) ---
$fixtures['gray-wolf-pride-2-attack-rejected'] = [
    'testedCards' => ['hJ2xh9lNMR'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Gray Wolf is NORM, vanilla besides Pride 2. Pride is enforced only in combat
    // (CombatLogic.php ~619/2836, GameLogic.php ~16695: PlayerLevel($controller) < PrideAmount($obj)),
    // there is no side-effect-free "can attack" predicate -- BeginCombatPhase() is simultaneously the
    // check and the action. Seeded directly onto the field (already awake, so no materialize/summoning
    // sickness concern). At the default champion level (0), attempting to declare it as an attacker is
    // rejected outright (expectFailure) since 0 < 2.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Rebellious Bull: enters the field rested (Pride 3) ---
$fixtures['rebellious-bull-enters-rested'] = [
    'testedCards' => ['GXeEa0pe3B'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Rebellious Bull is NORM. Its "enters the field rested" clause is directly observable via
    // Status after a normal FSM materialize -- Pride 3 (a combat-only restriction, same mechanic
    // as gray-wolf-pride-2-attack-rejected) is not separately re-tested here.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'GXeEa0pe3B'], // Rebellious Bull, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Freezing Hail + Blue Slime: deal 2 damage, [Class Bonus] Blue Slime gets a buff counter ---
$fixtures['freezing-hail-blue-slime-class-bonus-buff'] = [
    'testedCards' => ['SrBA7h2a1N', '1Sl4Gq2OuV'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Both cards are WATER -- the starting champion's CardID is patched directly to Silvie, With
    // the Pack (TAMER, also satisfies Blue Slime's [Class Bonus] class-match) and its Subcards are
    // ALSO patched with a real WATER champion (Nico, Rapture's Embrace) purely for the element/
    // lineage unlock (Silvie's own champions are NORM/TERA, not WATER). Blue Slime (Pride 4) is
    // seeded directly onto the field so its Pride doesn't need to be re-satisfied for a
    // materialize (Pride only gates attacking, already covered by gray-wolf-pride-2-attack-
    // rejected). Freezing Hail's dealDamageAbilities trigger for Blue Slime fires reactively off
    // DealDamage regardless of source, so targeting Blue Slime with our own Freezing Hail is a
    // valid, simple way to trigger both cards' effects in one fixture: Freezing Hail's own "deal 2
    // damage, skip next wake up" and Blue Slime's own "[Class Bonus] whenever dealt damage, buff
    // counter" trigger simultaneously.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'nllCALIXDT', 'Subcards' => ['29lqrve8fz']]], // Silvie, With the Pack (TAMER) + Nico (WATER unlock)
        ['player' => 1, 'zone' => 'myField', 'cardID' => '1Sl4Gq2OuV'], // Blue Slime
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'SrBA7h2a1N'], // Freezing Hail, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // target Blue Slime
    ],
];

// --- Lakeside Serpent: [Class Bonus] +1 POWER per water card in graveyard (Pride 6) ---
$fixtures['lakeside-serpent-class-bonus-water-graveyard-power'] = [
    'testedCards' => ['krgjMyVHRd'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Lakeside Serpent is WATER -- the starting champion's CardID is patched directly to Silvie,
    // With the Pack (TAMER, satisfies the Class Bonus) and its Subcards are ALSO patched with a
    // real WATER champion (Nico, Rapture's Embrace) for element/lineage unlock. Two Freezing Hail
    // copies (WATER) are seeded directly into the graveyard. Pride 6 (a combat-only restriction,
    // same mechanic already covered generically in gray-wolf-pride-2-attack-rejected) is not
    // separately re-tested here; Lakeside Serpent is seeded directly onto the field.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'nllCALIXDT', 'Subcards' => ['29lqrve8fz']]], // Silvie, With the Pack (TAMER) + Nico (WATER unlock)
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'SrBA7h2a1N'], // Freezing Hail (WATER) #1
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'SrBA7h2a1N'], // Freezing Hail (WATER) #2
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'krgjMyVHRd'], // Lakeside Serpent
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''], // harmless no-op click
    ],
];

// --- Vertus, Gaia's Roar: [Class Bonus] On Enter: allies +1 POWER per Animal/Beast in graveyard (Pride 10) ---
$fixtures['vertus-gaias-roar-class-bonus-enter-power-buff'] = [
    'testedCards' => ['dZ960Hnkzv'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Vertus is TERA -- the starting champion's CardID is patched directly to Silvie, With the
    // Pack (TAMER, satisfies the Class Bonus) and its Subcards are ALSO patched with a real TERA
    // champion (Arisanna, Astral Zenith is ASTRA -- use Gaia's Songbird's own Silvie Loved by All
    // instead, a real TERA champion) for element/lineage unlock. Two Gray Wolf copies (BEAST) are
    // seeded into the graveyard, and Gray Wolf itself is also seeded onto the field as the target
    // ally to observe the buff on. Pride 10 (combat-only) is not separately re-tested here; Vertus
    // is materialized via a real FSM play (paying its 4 reserve) so its On Enter ability actually
    // fires, since seeding directly onto myField would bypass enterAbilities entirely.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'nllCALIXDT', 'Subcards' => ['GKEpAulogu']]], // Silvie, With the Pack (TAMER) + Silvie, Loved by All (TERA unlock)
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf (BEAST) #1
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf (BEAST) #2
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf -- the ally that receives the buff
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'dZ960Hnkzv'], // Vertus, Gaia's Roar, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Song of Nurturing: allies +2 LIFE, [Class Bonus] also +1 POWER, until end of turn ---
$fixtures['song-of-nurturing-class-bonus-life-power'] = [
    'testedCards' => ['4hbA9FT56L'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Song of Nurturing is NORM. The starting champion's CardID is patched directly to Silvie,
    // With the Pack (TAMER) so the Class Bonus applies. Gray Wolf is seeded onto the field as the
    // ally that receives the buff. Confirmed via computed_life_equals (base 2 + 2 = 4) and
    // computed_power_equals (base 2 + 1 Class Bonus = 3).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'nllCALIXDT']], // Silvie, With the Pack (TAMER)
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf -- the ally that receives the buff
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4hbA9FT56L'], // Song of Nurturing, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Mist Resonance: allies +1 LIFE until end of turn ---
$fixtures['mist-resonance-allies-life'] = [
    'testedCards' => ['hw8dxKAnMX'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Mist Resonance is WATER -- the starting champion's Subcards are patched with a real WATER
    // champion (Nico, Rapture's Embrace) to unlock element access. Only the base ability (allies +1
    // LIFE) is covered; the [Class Bonus] Harmonize clause (allies assign damage with life instead
    // of power) is confirmed NOT IMPLEMENTED at all by the engine's own code comment
    // (GeneratedMacroCode.php: "Note: Harmonize class bonus ... is not implemented") and is out of
    // scope. Gray Wolf is seeded onto the field as the ally that receives the buff. Confirmed via
    // computed_life_equals (base 2 + 1 = 3).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['29lqrve8fz']]], // Nico, Rapture's Embrace -- unlocks WATER
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf -- the ally that receives the buff
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'hw8dxKAnMX'], // Mist Resonance, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Meadowbloom Dryad: [Class Bonus] whenever an ally enters, buff counter on target ally ---
$fixtures['meadowbloom-dryad-class-bonus-enter-buff'] = [
    'testedCards' => ['cVRIUJdTW5'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Meadowbloom Dryad is TERA. The starting champion's CardID is patched directly to Silvie,
    // Loved by All -- both TAMER (satisfies the Class Bonus) and TERA (unlocks element access) at
    // once. The trigger (FieldAfterAdd, GameLogic.php ~8126) fires for ANY ally entering, including
    // Meadowbloom Dryad itself: when it resolves as the very first ally on either field, it is the
    // only entry in the target-ally search, so the engine auto-applies the buff counter without a
    // choice decision (count($allyTargets) === 1 skips the MZCHOOSE branch) -- a simpler and
    // equally valid way to exercise the trigger than materializing a second ally afterward. Only
    // the Class Bonus trigger is covered; Preserve (returning from the material deck on
    // materialize) is a separate, out-of-scope mechanic.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'GKEpAulogu']], // Silvie, Loved by All (TAMER + TERA)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'cVRIUJdTW5'], // Meadowbloom Dryad, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Beastbond Ears: champion +1 level as long as you control an Animal or Beast ally ---
$fixtures['beastbond-ears-level-while-animal-beast'] = [
    'testedCards' => ['JPcFmCpdiF'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Beastbond Ears is NORM and unconditional (no Class Bonus gate). No "computed level" assertion
    // type exists in this harness (only computed_power_equals/computed_life_equals -- champion
    // level bonuses cannot be directly asserted), so the +1 level is confirmed indirectly: at the
    // default champion level (0), Scry the Skies' "Glimpse LV" would be an invisible Glimpse 0
    // no-op, but with Beastbond Ears' static bonus active (Gray Wolf, a BEAST ally, is seeded
    // alongside it), the effective level is 1, so Glimpse LV surfaces a real 1-card MZREARRANGE
    // decision that must be explicitly answered -- the fixture's own action sequence only completes
    // if that decision genuinely appears, which is itself the proof.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'JPcFmCpdiF'], // Beastbond Ears
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf (BEAST)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // MZREARRANGE response: keep original order (same no-op default as idle-thoughts-glimpse-4).
        // This decision only exists because the effective level is 1, not the default 0 -- proof
        // that Beastbond Ears' static bonus is applying.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Deep Sea Beastbonder: champion +1 level as long as you control an Animal or Beast ally ---
$fixtures['deep-sea-beastbonder-level-while-animal-beast'] = [
    'testedCards' => ['qxbdXU7H4Z'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Deep Sea Beastbonder is WATER -- the starting champion's Subcards are patched with a real
    // WATER champion (Nico, Rapture's Embrace) to unlock element access. Its static +1 level is
    // unconditional (no Class Bonus gate); only [Class Bonus] Floating Memory is out of scope. Same
    // indirect Glimpse-LV proof technique as beastbond-ears-level-while-animal-beast: Gray Wolf (a
    // BEAST ally) is seeded onto the field so the condition is already satisfied once Deep Sea
    // Beastbonder resolves, then Scry the Skies' Glimpse LV surfaces a real MZREARRANGE decision
    // (invisible at the default level 0) proving the effective level is 1.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['29lqrve8fz']]], // Nico, Rapture's Embrace -- unlocks WATER
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf (BEAST)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'qxbdXU7H4Z'], // Deep Sea Beastbonder, seeded to a known hand slot
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-3!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // MZREARRANGE response: keep original order -- only exists because the effective level is 1.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Melodious Flute: champion +1 level as long as you control an Animal or Beast ally ---
$fixtures['melodious-flute-level-while-animal-beast'] = [
    'testedCards' => ['WAFNy2lY5t'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Melodious Flute is NORM and REGALIA -- adding it to 'myHand' via setup gets silently
    // redirected to the Material zone instead (HandAddReplacement), matching every other REGALIA
    // item fixture this session, so it is seeded directly onto myField. Its static +1 level is
    // unconditional; [Class Bonus] Banish: next Harmony action is a Melody is a separate,
    // out-of-scope ability. Same indirect Glimpse-LV proof technique as beastbond-ears-level-
    // while-animal-beast: Gray Wolf (a BEAST ally) is seeded alongside it, then Scry the Skies'
    // Glimpse LV surfaces a real MZREARRANGE decision (invisible at the default level 0) proving
    // the effective level is 1.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'WAFNy2lY5t'], // Melodious Flute
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf (BEAST)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Decline the fast-opportunity offer of Melodious Flute's own [Class Bonus] Banish ability
        // (unrelated -- out of scope here) so Scry the Skies proceeds to its own resolution.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // MZREARRANGE response: keep original order -- only exists because the effective level is 1.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Silvie, Loved by All: Animal and Beast allies get +1 LIFE and have intercept ---
$fixtures['silvie-loved-by-all-animal-beast-life-intercept'] = [
    'testedCards' => ['GKEpAulogu'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // The starting champion's CardID is patched directly to Silvie, Loved by All itself (the card
    // under test), bypassing Silvie Lineage's level-up gate the same way established for other
    // champion fixtures this session. Gray Wolf (BEAST) is seeded onto the field. The +1 LIFE half
    // of the static ability is confirmed via computed_life_equals (base 2 + 1 = 3). The "has
    // intercept" half (HasIntercept(), GameLogic.php ~21762-21769, confirmed via direct code read
    // to share the same Animal-or-Beast-ally + Silvie-on-field check) has no dedicated assertion
    // type in this harness and would need a real combat interception sequence to observe -- out of
    // scope here, matching this session's treatment of other keyword-only clauses (e.g. Pride).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'GKEpAulogu']], // Silvie, Loved by All
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf (BEAST)
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-0', 'chkInput' => [], 'inputText' => ''], // harmless no-op click
    ],
];

// --- Silvie, With the Pack: On Enter: draw if Animal ally, draw if Beast ally ---
$fixtures['silvie-with-the-pack-on-enter-draw-animal-beast'] = [
    'testedCards' => ['nllCALIXDT'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Silvie, With the Pack
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Silvie, With the Pack is level 2 (Silvie Lineage), but CanChampionLevelUpIntoCard only checks
    // targetLevel === currentLevel + 1 -- lineage text is not enforced by the level-up gate itself
    // (same technique established for Arisanna's champions). The starting champion is patched
    // directly to Silvie, Wilds Whisperer (level 1) so a real level-up is legal. Champion-swap
    // materialization is only offered through the material-phase MZMAYCHOOSE at the start of a
    // turn, so both players end their first turn (P1 -> P2) to reach that prompt; its 2-memory cost
    // is paid from two filler cards seeded into myMemory. Giant Tortoise (ANIMAL) and Gray Wolf
    // (BEAST) are seeded onto the field beforehand so both On Enter draw clauses trigger.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'RfPP8h16Wv']], // Silvie, Wilds Whisperer (level 1) - level-up precondition
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'L0RmNaDzhk'], // Giant Tortoise (ANIMAL)
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf (BEAST)
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card 1/2 in memory to pay the 2-memory level-up cost
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card 2/2
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Silvie, Wilds Whisperer: On Enter: next Animal/Beast ally activated enters with a buff counter ---
$fixtures['silvie-wilds-whisperer-on-enter-buff-next-animal-beast'] = [
    'testedCards' => ['RfPP8h16Wv'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Silvie, Wilds Whisperer
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Silvie, Wilds Whisperer is level 1, one level above the default level-0 starting champion, so
    // no lineage/level patch is needed -- 0+1 is already a legal level-up (same as arisanna-
    // herbalist-prodigy-on-enter-gather-twice). Champion-swap materialization is only offered
    // through the material-phase MZMAYCHOOSE at the start of a turn, so both players end their
    // first turn (P1 -> P2) to reach that prompt; its 1-memory cost is paid from a filler card
    // seeded into myMemory. Her On Enter sets a "next Animal/Beast ally" flag (AddGlobalEffects);
    // Gray Wolf (BEAST) is then materialized from hand in the same turn to consume it, entering
    // with an extra buff counter (GameLogic.php ~8009-8025).
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card in memory to pay the 1-memory level-up cost
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Dewdrop Hares: [Class Bonus] Floating Memory ---
$fixtures['dewdrop-hares-class-bonus-floating-memory'] = [
    'testedCards' => ['fxwy3haEXU'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Silvie, With the Pack
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Dewdrop Hares' only ability is [Class Bonus] Floating Memory (its own class is TAMER),
    // same shape as savage-swing-class-bonus-floating-memory: Floating Memory only offers itself
    // as a payment source for a MEMORY cost (a champion level-up), and patching the champion
    // directly to the level-2 target would make leveling illegal (level-up requires strictly +1),
    // so the champion is patched to Silvie, Wilds Whisperer (level 1, TAMER) instead and leveled up
    // into Silvie, With the Pack (level 2, TAMER, 2-memory cost) -- the Class Bonus condition
    // checks the CURRENT champion (still TAMER) at payment time, before the level-up completes.
    // Dewdrop Hares pays 1 of the 2 memory via Floating Memory from the graveyard; the 2nd memory
    // point is a filler card seeded directly into myMemory.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'RfPP8h16Wv']], // Silvie, Wilds Whisperer (TAMER, level 1) - Class Bonus precondition + legal level-up base
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'fxwy3haEXU'], // Dewdrop Hares
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'], // filler memory card, 2nd point of Silvie, With the Pack's 2-memory level-up cost
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], // select Silvie, With the Pack as the level-up target
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''], // pay 1 memory via Floating Memory
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMemory-0', 'chkInput' => [], 'inputText' => ''], // pay the 2nd memory point
    ],
];

// --- Gaia's Songbird: [Class Bonus] On Enter: reveal until a Beast ally is found, put it in hand ---
$fixtures['gaias-songbird-class-bonus-enter-reveal-beast'] = [
    'testedCards' => ['sHzSmygjWY'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Gaia's Songbird is TERA -- the starting champion's Subcards are patched with a real TERA
    // champion (Silvie, Loved by All) to unlock element access. Its Class Bonus is checked via
    // IsClassBonusActive($player) with NO $classes argument, which (per this session's established
    // finding) degrades to "does any champion exist" -- always true -- so no class-match patch is
    // needed. The deck's top card (myDeck-0) is patched directly to Gray Wolf (a BEAST ally) so the
    // reveal finds it on the very first card, confirmed by Gray Wolf ending up in hand rather than
    // in the deck.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['GKEpAulogu']]], // Silvie, Loved by All -- unlocks TERA
        ['player' => 1, 'patchMzId' => 'myDeck-0', 'setProperties' => ['CardID' => 'hJ2xh9lNMR']], // Gray Wolf on top of deck
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'sHzSmygjWY'], // Gaia's Songbird, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Blissful Calling: look at top 5, may reveal an Animal or Beast card to hand ---
$fixtures['blissful-calling-look-5-reveal-animal-beast'] = [
    'testedCards' => ['YOjdZJpOO1'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Blissful Calling is NORM, no lineage patch needed. The deck's top card (myDeck-0) is patched
    // directly to Gray Wolf (a BEAST ally) so it is among the top 5 looked at and offered as the
    // sole qualifying candidate.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myDeck-0', 'setProperties' => ['CardID' => 'hJ2xh9lNMR']], // Gray Wolf on top of deck
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'YOjdZJpOO1'], // Blissful Calling, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myTempZone-0', 'chkInput' => [], 'inputText' => ''], // choose Gray Wolf
    ],
];

// --- Invoke Dominance: champion +3 level, can't activate non-ally cards this turn ---
$fixtures['invoke-dominance-level-lock-non-ally'] = [
    'testedCards' => ['PLljzdiMmq'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Invoke Dominance is TERA -- the starting champion's Subcards are patched with a real TERA
    // champion (Silvie, Loved by All) to unlock element access. Unlike the "+1 level while Animal/
    // Beast ally" static clauses tested indirectly via Glimpse LV elsewhere this session, this +3
    // level is applied via a direct AddTurnEffect("PLljzdiMmq") on the champion (GeneratedMacroCode.
    // php ~20641-20652), so it is directly observable via TurnEffects without needing the indirect
    // proof technique. The "can't activate non-ally cards this turn" restriction is a silent no-op
    // check embedded inside DoActivateCard itself (GameLogic.php ~2008-2014), not a legality
    // rejection CanActivateCard/expectFailure would catch -- confirmed instead via the global effect
    // flag it sets (AddGlobalEffects "PLljzdiMmq_NO_NONALLY") being present in myGlobalEffects.
    // Preserve (this card returning to the material deck as it resolves, rather than the graveyard)
    // is a separate, out-of-scope mechanic.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['GKEpAulogu']]], // Silvie, Loved by All -- unlocks TERA
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'PLljzdiMmq'], // Invoke Dominance, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Piper's Lullaby: champion +1 level while Animal/Beast ally, [Class Bonus] rest target ally ---
$fixtures['pipers-lullaby-class-bonus-level-rest'] = [
    'testedCards' => ['raG5r85ieO'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Piper's Lullaby is WATER -- the starting champion's CardID is patched directly to Silvie,
    // With the Pack (TAMER, satisfies the Class Bonus) and its Subcards are ALSO patched with a
    // real WATER champion (Nico, Rapture's Embrace) for element/lineage unlock. Unlike the "+1
    // level while Animal/Beast ally" STATIC clauses tested indirectly via Glimpse LV elsewhere this
    // session, this one is a one-shot AddTurnEffect("raG5r85ieO") applied when the card resolves
    // (GeneratedMacroCode.php ~21186-21203), so it is directly observable via TurnEffects. Gray
    // Wolf is seeded onto the field, satisfying the Animal/Beast condition and also serving as the
    // Class Bonus's rest target, confirmed via Status.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'nllCALIXDT', 'Subcards' => ['29lqrve8fz']]], // Silvie, With the Pack (TAMER) + Nico (WATER unlock)
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'hJ2xh9lNMR'], // Gray Wolf (BEAST) -- satisfies the level condition and is the rest target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'raG5r85ieO'], // Piper's Lullaby, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // [Class Bonus] rest Gray Wolf
    ],
];

// --- Empowering Harmony: champion +2 level until end of turn ---
$fixtures['empowering-harmony-level'] = [
    'testedCards' => ['Kc5Bktw0yK'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Empowering Harmony is NORM, no lineage patch needed. The +2 level is unconditional (no Class
    // Bonus gate); it is applied via AddGlobalEffects("Kc5Bktw0yK") (GeneratedMacroCode.php ~19347-
    // 19352), checked in the level-computation switch (GameLogic.php ~12964) -- directly observable
    // via card_exists in myGlobalEffects, without needing the indirect Glimpse-LV proof technique
    // used for the continuous "+1 level while Animal/Beast ally" static clauses elsewhere this
    // session. [Class Bonus] Harmonize -- draw a card (gated on both TAMER class match AND having
    // activated a Melody card this turn) is a separate, out-of-scope mechanic.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'Kc5Bktw0yK'], // Empowering Harmony, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Smack with Flute: On Attack: champion +1 level until end of turn ---
$fixtures['smack-with-flute-on-attack-level'] = [
    'testedCards' => ['zpkcFs72Ah'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Smack with Flute is NORM, no lineage patch needed; its On Attack level bonus is unconditional
    // ([Class Bonus] Floating Memory is a separate, unrelated ability on the same card, out of
    // scope). Same real single-target ATTACK-card flow as wind-cutter-class-bonus-power-attack: the
    // card is played from hand into myIntent, then the champion attacks with no weapon available
    // (GetAttackWeaponChoices returns empty, so BeginCombatPhase skips straight to
    // ChooseAttackTarget). On Attack fires as soon as the attack is declared, before damage
    // resolves, so its effect (AddGlobalEffects "zpkcFs72Ah") is asserted right after choosing the
    // target, before the defender's Retaliate response.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'zpkcFs72Ah'], // Smack with Flute
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
    ],
];

// --- Anger the Skies: deal 3 (4 with [Class Bonus]) damage to all allies ---
$fixtures['anger-the-skies-damage-all-allies'] = [
    'testedCards' => ['wOKw0q4SZR'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Anger the Skies is ARCANE, so the starting champion's Subcards are patched with a real ARCANE
    // champion (Lorraine, Arclight Saber) to unlock element access. Unconditional base deals 3
    // damage to all allies both sides (GeneratedMacroCode.php ~22858-22865); the [Class Bonus] bump
    // to 4 is out of scope. A Dungeon Guide is seeded onto the opponent's field as the only ally
    // target present.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G']]], // ARCANE lineage/element unlock
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - damage target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'wOKw0q4SZR'], // Anger the Skies, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // The Lorraine, Arclight Saber Subcards patch grants a lineage-inherited "Enlighten"
        // ability, offered as a fast-action opportunity to both players once reserve is paid
        // (same side effect as arcane-sight-level-draw) -- both decline.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Arcane Blast: deal 11 damage to target champion ---
$fixtures['arcane-blast-damage-target-champion'] = [
    'testedCards' => ['pn9gQjV3Rb'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Arcane Blast is ARCANE, so the starting champion's Subcards are patched with a real ARCANE
    // champion (Lorraine, Arclight Saber) to unlock element access. Its 11-reserve cost needs more
    // fuel than a natural 7-card hand provides (same shortfall pattern as disintegrate-destroy,
    // which needed 2 extra fillers for an 8-reserve cost); 5 extra filler cards are seeded into hand.
    // Unlike the smaller-reserve-cost ARCANE fixtures in this batch, paying all 11 reserve leaves
    // only 1 hand card -- too little to afford the Lorraine, Arclight Saber lineage-inherited
    // "Enlighten" ability, so GetPlayableOpportunityChoices offers nothing and EffectStackOpportunity
    // auto-resolves with no MZMAYCHOOSE at all (confirmed via direct probe: sending a stray PASS here
    // instead answers the ability's OWN target-choice MZCHOOSE with "PASS", which ArcaneBlastTarget's
    // handler treats as a decline and the 11 damage is never dealt) -- so no PASS step is needed
    // between paying reserve and choosing the damage target.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G']]], // ARCANE lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 1/5
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 2/5
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 3/5
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 4/5
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 5/5
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'pn9gQjV3Rb'], // Arcane Blast, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-12!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
    ],
];

// --- Arcane Disposition: draw 2 (3 with [Class Bonus]), discard hand at next end phase ---
$fixtures['arcane-disposition-draw-discard-flag'] = [
    'testedCards' => ['blq7qXGvWH'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Arcane Disposition is ARCANE, so the starting champion's Subcards are patched with a real
    // ARCANE champion (Lorraine, Arclight Saber) to unlock element access. Draws 2 (the [Class
    // Bonus] 3rd draw is out of scope), then sets a delayed-discard flag
    // (blq7qXGvWH_DISCARD_NEXT_END, GameLogic.php ~10454-10463) that fires at the next end phase --
    // directly observable via card_exists in myGlobalEffects without needing to reach that end phase.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G']]], // ARCANE lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'blq7qXGvWH'], // Arcane Disposition, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // The Lorraine, Arclight Saber Subcards patch grants a lineage-inherited "Enlighten"
        // ability, offered as a fast-action opportunity to both players once reserve is paid
        // (same side effect as arcane-sight-level-draw) -- both decline.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Arcanist's Prism: at the beginning of recollection phase, wheel memory into deck, draw that many ---
$fixtures['arcanists-prism-recollection-wheel-draw'] = [
    'testedCards' => ['dIEAN4J4YS'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Arcanist's Prism is REGALIA -- adding it to 'myHand' via setup gets silently redirected to the
    // Material zone instead (HandAddReplacement, GameLogic.php ~17282), matching every other REGALIA
    // item fixture this session -- it must be seeded directly onto myField. Its recollection-phase
    // trigger (GameLogic.php ~9044-9054, same unconditional per-field-card switch as Berserker
    // Plate) puts all cards from memory on the bottom of the deck, then draws that many -- a filler
    // card is seeded into myMemory so the effect has something to wheel. Same P1->P2->P1 cycle as
    // berserker-plate-recollection-damage-draw to reach player 1's own recollection phase.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'dIEAN4J4YS'], // Arcanist's Prism
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card to wheel into the deck
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Blitz Mage: vanilla stats (3 POWER / 1 LIFE, no ability) ---
$fixtures['blitz-mage-vanilla-stats'] = [
    'testedCards' => ['u8m6LuUSSu'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Blitz Mage is FIRE and vanilla (no printed ability). The default starting champion (Spirit of
    // Fire) is already FIRE, so no lineage/Subcards patch is needed to unlock element access.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'u8m6LuUSSu'], // Blitz Mage, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Careful Study: put five enlighten counters on your champion ---
$fixtures['careful-study-enlighten-counters'] = [
    'testedCards' => ['4NkVdSx9ed'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Careful Study is NORM, no lineage patch needed. Its 8-reserve cost needs 1 extra card beyond
    // a natural 7-card hand (2 extra fillers seeded for margin). The "Efficiency" keyword is a cost
    // discount (out of scope); the ability itself unconditionally puts 5 enlighten counters on the
    // champion (GeneratedMacroCode.php ~15634-15640).
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 1/2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'], // Extra reserve-payment fuel 2/2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4NkVdSx9ed'], // Careful Study, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-9!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Cremation Ritual: additional cost sacrifice an ally, draw 2 ---
$fixtures['cremation-ritual-sacrifice-draw'] = [
    'testedCards' => ['Pr48kXnasw'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Cremation Ritual is NORM, no lineage patch needed. A Dungeon Guide is seeded onto the field
    // as sacrifice fodder for the ability's own MZCHOOSE (GeneratedMacroCode.php ~20677-20684:
    // choose an ally, CUSTOM handler DoSacrificeFighter then Draw 2).
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - sacrifice fodder
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'Pr48kXnasw'], // Cremation Ritual, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // sacrifice Dungeon Guide
    ],
];

// --- Crystal of Empowerment: Banish -- champion gets +2 level until end of turn ---
$fixtures['crystal-of-empowerment-banish-level'] = [
    'testedCards' => ['dmfoA7jOjy'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Crystal of Empowerment is REGALIA -- seeded directly onto myField (hand-add redirect). Its
    // "until end of turn" TurnEffect is at risk of the same "attempt to pass ends the turn" bug
    // documented for Beastbond Boots, so Scry the Skies is played first (a same-turn warm-up action)
    // and the ability is reached via a direct Activate:0 click instead of the pass-trick (same fix
    // as beastbond-boots-banish-spellshroud). At the default champion level (0), Scry's own Glimpse
    // LV is an invisible no-op, so no further decision is needed after activating.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'dmfoA7jOjy'], // Crystal of Empowerment
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay Scry the Skies' 1 reserve
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Endura, Scepter of Ignition: [REST] remove an enlighten counter, deal 1 damage to target unit ---
$fixtures['endura-scepter-rest-damage'] = [
    'testedCards' => ['SGsDKB9CN5'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Endura is REGALIA -- seeded directly onto myField. Its prereq requires the champion to
    // already have an enlighten counter (GeneratedMacroCode.php ~6147-6172), patched directly via
    // Counters. Same Scry-the-Skies warm-up + direct Activate:0 click as crystal-of-empowerment-
    // banish-level (this ability's own effect isn't "until end of turn", but the same "field item
    // isn't clickable as the literal first action of a fresh turn via a plain FSM click" constraint
    // applies, so the same warm-up shape is reused for safety). A Dungeon Guide on the opponent's
    // field is the damage target.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['enlighten' => 1]]], // ability cost fuel
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'SGsDKB9CN5'], // Endura, Scepter of Ignition
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - damage target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay Scry the Skies' 1 reserve
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''], // target Dungeon Guide
    ],
];

// --- Flame-Rune Swordsman: [Class Bonus] Floating Memory ---
$fixtures['flame-rune-swordsman-class-bonus-floating-memory'] = [
    'testedCards' => ['VV6ADdMrr5'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Flame-Rune Swordsman's only ability is [Class Bonus] Floating Memory (its own classes are
    // MAGE,WARRIOR), same shape as dewdrop-hares-class-bonus-floating-memory: the champion is
    // patched to Rai, Spellcrafter (level 1, MAGE) so leveling into Rai, Archmage (level 2, MAGE,
    // 2-memory cost) is legal and the Class Bonus condition (checked on the CURRENT champion at
    // payment time) is satisfied. Flame-Rune Swordsman pays 1 of the 2 memory via Floating Memory
    // from the graveyard; the 2nd memory point is a filler card seeded directly into myMemory.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'gPKTJKqvOI']], // Rai, Spellcrafter (MAGE, level 1) - Class Bonus precondition + legal level-up base
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'VV6ADdMrr5'], // Flame-Rune Swordsman
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler memory card, 2nd point of Rai, Archmage's 2-memory level-up cost
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''], // select Rai, Archmage as the level-up target
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''], // pay 1 memory via Floating Memory
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMemory-0', 'chkInput' => [], 'inputText' => ''], // pay the 2nd memory point
    ],
];

// --- Impassioned Tutor: On Attack: champion +1 level until end of turn ---
$fixtures['impassioned-tutor-on-attack-level'] = [
    'testedCards' => ['MECS7RHRZ8'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Impassioned Tutor is NORM. Same real attack-declaration sequence as ardent-cloudstriker-west-
    // attack-champion-buff: seeded directly onto the field (bypassing its reserve cost) with Status
    // patched awake so it can declare an attack immediately. Its On Attack fires as soon as the
    // attack is declared (AddGlobalEffects "MECS7RHRZ8", GeneratedMacroCode.php ~24673-24677),
    // before damage resolves.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'MECS7RHRZ8'], // Impassioned Tutor
        ['player' => 1, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can attack
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline materialize offer
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Impassioned Tutor
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
    ],
];

// --- Library Witch: Intercept, On Death: draw a card ---
$fixtures['library-witch-on-death-draw'] = [
    'testedCards' => ['iD8qbpA8z5'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Library Witch is NORM. Only the unconditional On Death: draw a card is covered here (Intercept
    // is a shared static ability tested elsewhere, out of scope). Undeniable Truth's mandatory
    // sacrifice-an-ally additional cost is reused as the kill trigger: choosing the sac target
    // (GameLogic.php ~3218-3232, UndeniableTruthCost handler) directly sacrifices and queues its
    // 1-reserve cost -- no YES/NO or Glimpse decision of its own (confirmed via direct probe; the
    // extra YES/Glimpse steps in caretaker-drone-class-bonus-death-glimpse belong to THAT card's own
    // On Death "Glimpse 4" effect, not to Undeniable Truth's generic cost flow).
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'iD8qbpA8z5'], // Library Witch
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'UaUfw7yFTW'], // Undeniable Truth, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // sacrifice Library Witch as the mandatory cost
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay Undeniable Truth's 1-reserve cost
    ],
];

// --- Magus Disciple: [Class Bonus] On Death: draw a card ---
$fixtures['magus-disciple-class-bonus-on-death-draw'] = [
    'testedCards' => ['pnDhApDNvR'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Magus Disciple's [Class Bonus] On Death is IsClassBonusActive($player) with NO class argument
    // (degrades to "any champion present," same established finding as caretaker-drone-class-bonus-
    // death-glimpse and gaias-songbird-class-bonus-enter-reveal-beast), so it fires unconditionally.
    // Its separate unconditional static "+1 level while on the field" clause (GameLogic.php ~13185,
    // a continuous computed-level check with no stored counter to assert) is out of scope here.
    // Same Undeniable Truth sacrifice-kill technique as library-witch-on-death-draw (its cost
    // handler directly sacrifices and queues its own 1-reserve cost -- no YES/NO or Glimpse step).
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'pnDhApDNvR'], // Magus Disciple
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'UaUfw7yFTW'], // Undeniable Truth, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // sacrifice Magus Disciple as the mandatory cost
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay Undeniable Truth's 1-reserve cost
    ],
];

// --- Mana Limiter: Activate: draw a card ---
$fixtures['mana-limiter-activate-draw'] = [
    'testedCards' => ['IC3OU6vCnF'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Mana Limiter is REGALIA -- seeded directly onto myField. Same Scry-the-Skies warm-up + direct
    // Activate:0 click as crystal-of-empowerment-banish-level (field items aren't reliably
    // clickable as the literal first action of a fresh turn).
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'IC3OU6vCnF'], // Mana Limiter
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay Scry the Skies' 1 reserve
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Peer into Mana: put 2+LV enlighten counters on your champion ---
$fixtures['peer-into-mana-enlighten-counters'] = [
    'testedCards' => ['914hZjxDL0'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Peer into Mana is NORM, no lineage patch needed. At the default champion level (0), the
    // amount is 2 + 0 = 2 enlighten counters (GeneratedMacroCode.php ~16481-16491).
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '914hZjxDL0'], // Peer into Mana, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Power Overwhelming: remove X enlighten counters from your champion, it gets +X level until end of turn ---
$fixtures['power-overwhelming-remove-enlighten-level'] = [
    'testedCards' => ['AnEPyfFfHj'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Power Overwhelming is ARCANE (reserve cost 0), so the starting champion's Subcards are also
    // patched with a real ARCANE champion (Lorraine, Arclight Saber) to unlock element access,
    // alongside 3 enlighten counters so the NUMBERCHOOSE (0 to current enlighten count,
    // GeneratedMacroCode.php ~16844-16853) has real fuel; 2 is chosen. The resulting TurnEffect key
    // is dynamically suffixed with the removed amount ("AnEPyfFfHj-2", GameLogic.php ~13084-13087,
    // +N level per counter removed) and is directly observable in TurnEffects without needing the
    // indirect Glimpse-LV proof technique.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G'], 'Counters' => ['enlighten' => 3]]], // ARCANE lineage/element unlock + ability cost fuel
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'AnEPyfFfHj'], // Power Overwhelming, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        // The Lorraine, Arclight Saber Subcards patch grants a lineage-inherited "Enlighten"
        // ability, offered as a fast-action opportunity as soon as the card enters the effect
        // stack (reserve cost 0, so no reserve payment precedes it) -- both players decline.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '2', 'chkInput' => [], 'inputText' => ''], // remove 2 enlighten counters
    ],
];

// --- Rai, Archmage: Inherited Effect -- first Mage action card each turn, put an enlighten counter on champion ---
$fixtures['rai-archmage-inherited-first-mage-action-enlighten'] = [
    'testedCards' => ['zdIhSL5RhK'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Rai, Archmage's Inherited Effect (GameLogic.php ~5670-5680) checks ChampionHasInLineage,
    // which reads the champion object's own CardID plus Subcards -- a direct CardID patch is
    // sufficient (no natural level-up needed). Idle Thoughts (rWhFC8XBaH, NORM, MAGE, reserve 1,
    // already used in idle-thoughts-glimpse-4 with this exact generic deck/seed) is the first MAGE
    // ACTION card activated this turn, putting an enlighten counter on the champion and setting the
    // RAI_ARCHMAGE_TRIGGERED once-per-turn flag.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zdIhSL5RhK']], // become Rai, Archmage
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'rWhFC8XBaH'], // Idle Thoughts, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay 1-reserve cost
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Rai, Spellcrafter: On Enter: put two enlighten counters on CARDNAME ---
$fixtures['rai-spellcrafter-on-enter-enlighten'] = [
    'testedCards' => ['gPKTJKqvOI'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Spellcrafter
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Rai, Spellcrafter is level 1, one level above the default level-0 starting champion, so no
    // lineage/level patch is needed -- 0+1 is already a legal level-up (same as arisanna-herbalist-
    // prodigy-on-enter-gather-twice). Champion-swap materialization is only offered through the
    // material-phase MZMAYCHOOSE at the start of a turn, so both players end their first turn
    // (P1 -> P2) to reach that prompt; its 1-memory cost is paid from a filler card seeded into
    // myMemory. Her On Enter puts 2 enlighten counters on herself (GeneratedMacroCode.php
    // ~11447-11451).
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card in memory to pay the 1-memory level-up cost
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Rai, Storm Seer: +1 level for each arcane element Mage Spell card in your banishment ---
$fixtures['rai-storm-seer-level-per-arcane-mage-spell-banished'] = [
    'testedCards' => ['g92bHLtTNl'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Storm Seer
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Rai, Storm Seer's level bonus (GameLogic.php ~13279-13288) is a continuous computed check
    // (+1 level per qualifying banished card) with no stored counter to assert directly -- the
    // champion is patched directly to Rai, Storm Seer (the check reads $obj->CardID literally, not
    // lineage, so a direct patch is sufficient), and Shock Therapy (tyj2s3572j, an ARCANE MAGE
    // SPELL action card) is seeded into myBanish. Same indirect Glimpse-LV proof technique as
    // beastbond-ears-level-while-animal-beast: at the default level 0, Scry the Skies' Glimpse LV
    // would be an invisible no-op, but with the +1 level bonus active (1 qualifying banished card),
    // Glimpse LV surfaces a real 1-card MZREARRANGE decision that must be explicitly answered.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'g92bHLtTNl']], // Rai, Storm Seer
        ['player' => 1, 'zone' => 'myBanish', 'cardID' => 'tyj2s3572j'], // Shock Therapy (ARCANE, MAGE, SPELL)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // MZREARRANGE response: this decision only exists because the effective level is 1, not the
        // default 0 -- proof that Rai, Storm Seer's static bonus is applying.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=em6eEh9q8y;Bottom=', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Surveillance Stone: opponent's 3rd attack each turn, may banish to draw ---
$fixtures['surveillance-stone-third-attack-banish-draw'] = [
    'testedCards' => ['kk46Whz7CJ'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Surveillance Stone is REGALIA -- seeded directly onto myField (P1's side). Its trigger
    // (CombatLogic.php ~2742-2751) fires when the OPPONENT's OnAttackCallCount reaches exactly 3
    // for their turn, offering the owner (P1) a YESNO to banish it and draw. 3 separate Dungeon
    // Guide allies are seeded onto P2's field (each patched awake) so P2 can declare 3 real attacks
    // in a single turn, same real attack-declaration sequence (P1 pass, P2 pass, P1 formally ends
    // turn 1 via the mid-game Pass button, P2 declines their MAT offer) as swift-recruit-intercept-
    // redirect for reaching P2's own attacking turn.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'kk46Whz7CJ'], // Surveillance Stone
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide attacker 1
        ['player' => 2, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]],
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide attacker 2
        ['player' => 2, 'patchMzId' => 'myField-2', 'setProperties' => ['Status' => 2]],
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide attacker 3
        ['player' => 2, 'patchMzId' => 'myField-3', 'setProperties' => ['Status' => 2]],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // P1 declines their own MAT-phase materialize offer
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // P1 formally ends turn 1 (nothing to do)
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // P2 declines their MAT-phase materialize offer
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // attack 1/3
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-2!FSM!', 'chkInput' => [], 'inputText' => ''], // attack 2/3
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-3!FSM!', 'chkInput' => [], 'inputText' => ''], // attack 3/3
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''], // P1 banishes Surveillance Stone to draw
    ],
];

// --- Tome of Knowledge: Banish -- draw a card ---
$fixtures['tome-of-knowledge-banish-draw'] = [
    'testedCards' => ['yDARN8eV6B'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Tome of Knowledge is REGALIA -- seeded directly onto myField. Same Scry-the-Skies warm-up +
    // direct Activate:0 click as crystal-of-empowerment-banish-level.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'yDARN8eV6B'], // Tome of Knowledge
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay Scry the Skies' 1 reserve
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Water Resonance Bauble: Banish -- draw a card (if your opponent can access Water) ---
$fixtures['water-resonance-bauble-banish-draw'] = [
    'testedCards' => ['dSSRtNnPtw'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Rai, Archmage
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Water Resonance Bauble is REGALIA -- seeded directly onto myField. Its prereq (IsPlayerElementEnabled)
    // reads the OPPONENT's own element lineage, so P2's starting champion's Subcards are patched
    // with a real WATER champion (Spirit of Water), same technique as wind-resonance-bauble-banish-
    // draw. Same Scry-the-Skies warm-up + direct Activate:0 click as crystal-of-empowerment-banish-
    // level.
    'setup' => [
        ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock for P2
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'dSSRtNnPtw'], // Water Resonance Bauble
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'], // Scry the Skies -- a same-turn warm-up action so "attempt to pass" isn't needed
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // pay Scry the Skies' 1 reserve
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Crusader of Aesa: enters the field rested ---
$fixtures['crusader-of-aesa-enters-rested'] = [
    'testedCards' => ['2Q60hBYO3i'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Crusader of Aesa's "enters the field rested" is unconditional card text (GameLogic.php
    // ~7909: if($added->CardID == "2Q60hBYO3i") { $added->Status = 1; }), applied whenever the
    // card is added to a field via the generic field-add hook -- not gated on Class Bonus (that
    // gate only applies to its SEPARATE [Class Bonus] Intercept ability, tested separately by
    // esteemed-knight-class-bonus-intercept since both cards share the same Intercept mechanic).
    // Played from hand (myHand-7, verified via DevTools/probe-hand.php) paying its 3 reserve.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '2Q60hBYO3i'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Esteemed Knight: [Class Bonus] Intercept ---
$fixtures['esteemed-knight-class-bonus-intercept'] = [
    'testedCards' => ['iabqeB0I6t'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Esteemed Knight's only ability is [Class Bonus] Intercept (HasKeyword_Intercept,
    // GeneratedKeywordCode.php, gated on IsClassBonusActive($player, ["WARRIOR","HUMAN"])) --
    // unlike Swift Recruit's unconditional Intercept (swift-recruit-intercept-redirect), this
    // needs a real WARRIOR-or-HUMAN-class champion, so the starting champion is CardID-patched
    // directly to Lorraine, Blademaster (WARRIOR). Same real-attack/redirect sequence as
    // swift-recruit-intercept-redirect: P2's default champion (0 printed POWER) needs
    // Executioner's Spear seeded on its own field to have a legal attack; P1 ends turn 1 with
    // nothing to do, P2 attacks P1's champion directly, and P1 redirects to Esteemed Knight
    // (GetAvailableInterceptRedirectTargets only offers a redirect when the attack target is a
    // CHAMPION).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'TJTeWcZnsQ']],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'iabqeB0I6t'], // Esteemed Knight, awake
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'zv6yp6q7zw'], // Executioner's Spear (1 POWER) for a legal P2 attack
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Spirit Blade: Ghost Strike: On Attack, may banish a material card for +1 POWER ---
$fixtures['spirit-blade-ghost-strike-banish-material-power'] = [
    'testedCards' => ['vcZSHNHvKX'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Spirit Blade: Ghost Strike is CRUX element (verified via CardElement(), not NORM as
    // cardArrayCache.json's unreliable elements field would suggest) -- champion CardID-patched
    // directly to Lorraine, Crux Knight (WARRIOR + CRUX) to unlock it, same technique as the
    // Rai deck's ARCANE cards. 0 reserve cost, so played straight to attack (ATTACK cards go
    // into myIntent via FSM, not through the DoActivateCard fast-action opportunity flow --
    // confirmed via wind-cutter-class-bonus-power-attack). Its On Attack ability
    // (onAttackAbilities["vcZSHNHvKX:0"]) offers a MZMAYCHOOSE to banish a material-deck card for
    // +1 POWER on the champion's attacks (AddGlobalEffects "vcZSHNHvKX",
    // doesGlobalEffectApply["vcZSHNHvKX"]); the deck's own default Material leftovers (Lorraine
    // Wandering Warrior, Clarent, Backup Charger, Purifying Thurible) supply a real choice.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'NfbZ0nouSQ']],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vcZSHNHvKX'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Fire Resonance Bauble: Banish -- draw a card (if opponent can access Fire) ---
$fixtures['fire-resonance-bauble-banish-draw'] = [
    'testedCards' => ['LROrzTmh55'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Fire Resonance Bauble is REGALIA -- seeded directly onto myField (memory-cost REGALIA
    // items are Material-zone-only cards when played for real, same as Clarent/Backup
    // Charger/Purifying Thurible in this deck's own Material section; seeding directly bypasses
    // that entirely to test only the [Activate] ability itself). Its prereq
    // (activateAbilityPrereqs["LROrzTmh55:0"]) requires the OPPONENT to have Fire access --
    // P2's default starting champion is literally Spirit of Fire, so no patch is needed. Same
    // Scry the Skies warm-up + direct Activate:0 click as crystal-of-empowerment-banish-level.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'LROrzTmh55'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'F9POfB5Nah'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Hurricane Sweep: [Class Bonus] Efficiency + Cleave ---
$fixtures['hurricane-sweep-class-bonus-power-attack'] = [
    'testedCards' => ['4V6qKuM7xs'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
8 Dungeon Guide
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Hurricane Sweep is WIND element with [Class Bonus] Efficiency (reduce reserve cost by
    // champion's current level) and printed Cleave (already proven generically by the
    // pre-existing hemorrhaging-rend-damage20-cleave fixture, so not re-asserted here). Champion
    // CardID-patched directly to Lorraine, Blademaster (WARRIOR, level 2) with Spirit of Wind
    // added via Subcards for WIND access -- same technique as wind-cutter-class-bonus-power-attack,
    // including dropping Fairy Whispers from the Main list to avoid the WIND-unlock
    // Opportunity-window cascade that card triggers once WIND is enabled. With level 2 active,
    // Efficiency reduces the printed 5 reserve cost to 3 (verified: CalculateActivationReserveCost
    // applies the Efficiency registry's reduction unconditionally once IsGA's $Efficiency_Cards
    // registry contains the card, regardless of the printed "[Class Bonus]" qualifier -- an
    // engine-behavior detail worth noting, though moot here since a real WARRIOR Class Bonus is
    // also active).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'TJTeWcZnsQ', 'Subcards' => ['pNiyaGlIe7']]],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4V6qKuM7xs'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Ornamental Greatsword: [Class Bonus] On Enter target ally gets +1 POWER until EOT ---
$fixtures['ornamental-greatsword-enter-ally-power'] = [
    'testedCards' => ['qyQLlDYBlr'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Ornamental Greatsword
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Ornamental Greatsword is REGALIA with a 0-memory-cost On Enter ability, so (unlike the
    // other REGALIA items in this deck) it must actually MATERIALIZE for its enterAbilities to
    // fire -- direct field-seeding would skip the trigger entirely. Memory-cost REGALIA
    // materialize like champions, via the material-phase MZMAYCHOOSE, only offered on the turn
    // player's OWN turn after turn 1 (MaterializePhase() in MaterializeLogic.php), so both
    // players end turn 1/2 to reach P1's turn 3. Its enterAbilities call
    // IsClassBonusActive($player) with NO classes argument, which (per GameLogic.php's
    // IsClassBonusActive) requires only that SOME champion is on the field -- not actually gated
    // on this card's own GUARDIAN/WARRIOR classes, an engine-behavior detail noted here rather
    // than assumed away; the default Spirit of Fire champion already satisfies it. A Dungeon
    // Guide is seeded as the ally target.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide, the ally target
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Savage Slash: [Class Bonus] Floating Memory ---
$fixtures['savage-slash-class-bonus-floating-memory'] = [
    'testedCards' => ['4a7QLLouGk'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Lorraine, Blademaster
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Savage Slash's only ability is [Class Bonus] Floating Memory, gated on WARRIOR Class
    // Bonus (unlike honorable-vanguard-floating-memory's unconditional version). Floating Memory
    // is a MEMORY-cost payment source (champion leveling / memory-cost REGALIA materialize) --
    // NOT a reserve-cost payment source (that's the separate "Reservable" keyword, checked via
    // GetReservablePaymentSources against field objects only; an initial draft of this fixture
    // wrongly assumed Floating Memory applied to reserve costs too and failed with "Invalid
    // selection" on the ReserveCard MZCHOOSE, whose Param turned out to be the literal zone name
    // "myHand" with no graveyard entries). Champion CardID-patched to Lorraine, Wandering
    // Warrior (level 1, WARRIOR -- already gives the Class Bonus), then leveled up for real
    // (level 1 -> 2, legal since target == current + 1) into Lorraine, Blademaster on P1's turn 3
    // material phase, paying its 2-memory cost with Savage Slash from myGraveyard (Floating
    // Memory) plus 1 real myMemory filler card.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'DpHDGaX2Pn']],
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => '4a7QLLouGk'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Spirit Blade: Ascension: additional cost return Sword to material; fetch a Sword to field ---
$fixtures['spirit-blade-ascension-swap-sword'] = [
    'testedCards' => ['N0ipz8UWwf'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Spirit Blade: Ascension is CRUX element (champion CardID-patched to Lorraine, Crux Knight
    // to unlock it). Its ability (cardActivatedAbilities["N0ipz8UWwf:0"]) requires controlling a
    // REGALIA,SWORD on the field to return to material, then lets you fetch a REGALIA,SWORD from
    // material or banishment onto the field. Clarent, Sword of Peace is seeded directly onto the
    // field as the Sword to return; Warrior's Longsword is seeded into material as the Sword to
    // fetch back.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'NfbZ0nouSQ']],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'm31WVJ9F04'], // Clarent, Sword of Peace -- the Sword to return
        ['player' => 1, 'zone' => 'myMaterial', 'cardID' => 'jF1VuIR7a6'], // Warrior's Longsword -- the Sword to fetch back
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'N0ipz8UWwf'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-4', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Spirit Blade: Dispersion: strip durability from Swords, split damage among units ---
$fixtures['spirit-blade-dispersion-split-damage'] = [
    'testedCards' => ['7Rsid05Cf6'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Spirit Blade: Dispersion is CRUX element, 0 reserve (champion CardID-patched to Lorraine,
    // Crux Knight to unlock it). Its ability (SpiritBladeDispersion, CardLogic.php) removes
    // durability counters from a chosen Sword weapon, banishes it, and splits that much damage
    // among chosen units via an MZSPLITASSIGN decision. Clarent, Sword of Peace is seeded onto
    // the field with 2 durability counters; the answer format for MZSPLITASSIGN
    // (ProcessSplitDamage, GameLogic.php) is "targetMZ:amount" regardless of how the decision's
    // own Param field happens to be ordered (confirmed by reading ProcessSplitDamage directly --
    // this card's own DQ handler builds Param as "targets|amount|tooltip", the reverse of every
    // other MZSPLITASSIGN caller's "amount|targets" convention, which would even confuse
    // GoldfishResolveDecisionInput's generic auto-resolver; a genuine minor engine inconsistency
    // noted here but not fixed, since fixing engine behavior is out of scope for fixture
    // authoring). All 2 damage assigned to P2's Dungeon Guide.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'NfbZ0nouSQ']],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'm31WVJ9F04', 'setProperties' => ['Counters' => ['durability' => 2]]],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '7Rsid05Cf6'],
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1:2', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Sword of Seeking: [Class Bonus] True Sight ---
$fixtures['sword-of-seeking-class-bonus-true-sight'] = [
    'testedCards' => ['Dz8I0eJzaf'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Sword of Seeking's only ability is [Class Bonus] True Sight (HasKeyword_TrueSight, gated
    // on WARRIOR Class Bonus) -- champion CardID-patched directly to Lorraine, Blademaster
    // (WARRIOR), Sword of Seeking seeded directly onto the field (its ability is a static
    // wielded-weapon check, not an Enter trigger). Gildas, Faesworn Monarch (unconditional
    // Stealth) is seeded onto P2's field as the attack target: AttackerHasTrueSight
    // (CombatLogic.php) checks the wielded weapon for True Sight, which is what allows Stealth
    // units to be legally targeted at all (GetChooseAttackTargets filters them out otherwise) --
    // successfully targeting Gildas is itself the proof.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'TJTeWcZnsQ']],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'Dz8I0eJzaf'], // Sword of Seeking
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'g99PIuhU0O'], // Gildas, Faesworn Monarch (Stealth)
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Weaponsmith: [Class Bonus] put a durability counter on target weapon at recollection ---
$fixtures['weaponsmith-class-bonus-durability'] = [
    'testedCards' => ['6gN5KjqRW5'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Weaponsmith's ability fires from ResolveBeforeRecollectionPhaseStart (GameLogic.php ~9366),
    // once per turn at the start of the controller's own recollection phase -- champion
    // CardID-patched to Lorraine, Blademaster (WARRIOR) for the Class Bonus, with exactly one
    // WEAPON on the field (Warrior's Longsword) so the counter is applied automatically with no
    // extra MZCHOOSE. Both players end turn 1/2 (same pattern as
    // arcanists-prism-recollection-wheel-draw) to reach P1's own turn 3 recollection phase.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'TJTeWcZnsQ']],
        ['player' => 1, 'zone' => 'myField', 'cardID' => '6gN5KjqRW5'], // Weaponsmith
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'jF1VuIR7a6'], // Warrior's Longsword -- the only weapon on the field
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Lorraine, Blademaster: On Enter, attacks +2 POWER and gain On Kill: Draw a card ---
$fixtures['lorraine-blademaster-enter-attack-buff'] = [
    'testedCards' => ['TJTeWcZnsQ'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Lorraine, Blademaster
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Lorraine, Blademaster's ability is an On Enter trigger (enterAbilities["TJTeWcZnsQ:0"]),
    // so it must actually be leveled into via a real level-up event, not a direct CardID patch --
    // but CanChampionLevelUpIntoCard only allows target level == current level + 1, so a direct
    // 0->2 jump is illegal. The starting champion is CardID-patched to Lorraine, Wandering
    // Warrior (level 1) first, then leveled up for real (level 1 -> 2) on P1's turn 3 material
    // phase, paying the 2-memory cost from 2 seeded myMemory filler cards. The On Enter ability
    // tags the champion with the TJTeWcZnsQ TurnEffect, which CombatLogic.php reads for both the
    // +2 POWER on attacks and the On Kill: Draw grant -- asserted directly via TurnEffects
    // rather than scripting a full attack, matching the minimal-assertion philosophy established
    // for other "until end of turn" grants.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'DpHDGaX2Pn']],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'px60u5n1do'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Phalanx Captain: reserve cost reduced by 1 per HUMAN ally (Class Bonus) ---
$fixtures['phalanx-captain-class-bonus-human-discount'] = [
    'testedCards' => ['rPpLwLPGaL'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Phalanx Captain is WIND element with a self-referential activationCostModifierAbilities
    // entry (rPpLwLPGaL:0) that reduces its own reserve cost by 1 per HUMAN ally controlled,
    // gated on WARRIOR Class Bonus. Champion CardID-patched to Lorraine, Blademaster (WARRIOR)
    // with Spirit of Wind added via Subcards for WIND access; Dungeon Guide (a HUMAN ally) is
    // seeded onto the field. With 1 HUMAN ally, the printed 5 reserve cost is reduced to 4.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'TJTeWcZnsQ', 'Subcards' => ['pNiyaGlIe7']]],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (HUMAN)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'rPpLwLPGaL'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Sudden Steel: [Class Bonus] Efficiency ---
$fixtures['sudden-steel-class-bonus-efficiency'] = [
    'testedCards' => ['SSu2eQZFJV'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Sudden Steel's only ability is [Class Bonus] Efficiency (reduce reserve cost by champion's
    // current level). Champion CardID-patched directly to Lorraine, Blademaster (WARRIOR, level
    // 2), reducing the printed 6 reserve cost to 4. Played into myIntent via FSM (ATTACK cards
    // don't go through the fast-action opportunity flow until after the attack is declared, per
    // wind-cutter-class-bonus-power-attack), then attacks the opponent's champion directly.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'TJTeWcZnsQ']],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'SSu2eQZFJV'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Training Session: target ally gets a buff counter ---
$fixtures['training-session-buff-counter'] = [
    'testedCards' => ['G42RDwb3Ko'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Training Session (TAMER,WARRIOR, NORM, no Class Bonus gate on the macro itself) targets an
    // ally on the field and puts a buff counter on it. Dungeon Guide seeded as the target.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide, the target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'G42RDwb3Ko'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Lorraine, Crux Knight: attacks +1 POWER per regalia weapon in banishment ---
$fixtures['lorraine-crux-knight-banished-weapon-power'] = [
    'testedCards' => ['NfbZ0nouSQ'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Lorraine, Crux Knight's ability is a static computed-power case (GameLogic.php ~11019,
    // not an Enter trigger), so the champion is CardID-patched directly to it. NfbZ0nouSQ has no
    // printed POWER (CardPower returns -1) -- a champion attacking with 0 total power never
    // reaches the damage step at all (confirmed via a temporary error_log instrumentation of the
    // ObjectCurrentPower switch case, since reverted: with no weapon, the pre-declaration "does
    // this attacker have positive power" check runs while CombatAttacker is still unset, so the
    // bonus can't count toward it, and the attack silently resolves with no damage step ever
    // running -- the same "reports success but nothing happens" class of issue as the Rai-deck
    // Anger the Skies investigation). Rather than fight that pre-check by adding a real weapon
    // (which risks conflating the measurement with the weapon's own abilities, as a first draft
    // using Warrior's Longsword discovered -- its own [Class Bonus] +1 POWER stacked with this
    // card's bonus and killed the target outright), the CombatAttacker DecisionQueueController
    // variable is set directly via the 'dqVariables' setup primitive (same technique as
    // Samaritan's Reach's existing fixture) so ObjectCurrentPower's NfbZ0nouSQ case evaluates
    // exactly as it would mid-combat, with zero scripted combat actions. A REGALIA weapon
    // (Warrior's Longsword) is seeded into myBanish; computed_power_equals on the champion's own
    // mzId directly asserts the resulting +1 POWER.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'NfbZ0nouSQ']],
        ['player' => 1, 'zone' => 'myBanish', 'cardID' => 'jF1VuIR7a6'], // Warrior's Longsword (REGALIA,WEAPON) in banish -- the +1 POWER bonus source
        ['player' => 1, 'dqVariables' => ['CombatAttacker' => 'myField-0']],
    ],
    // A single harmless mode=100 PASS with no pending decision is included purely so the test
    // runner (RunIntegrationTests.php) loads the root runtime at all -- it only does so as a
    // side effect of replaying at least one EngineRunAction, and a zero-action fixture crashes
    // its own step-0 assertion check with "Call to undefined function GetZoneObject()" before
    // ever reaching the replay loop. Confirmed via direct probing that this PASS does not touch
    // the injected CombatAttacker variable or the champion's computed power.
    'actions' => [
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Opening Cut: [Class Bonus] +2 POWER while exactly 1 card in memory ---
$fixtures['opening-cut-class-bonus-memory-power'] = [
    'testedCards' => ['vBetRTn3eW'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Opening Cut's only ability is [Class Bonus] +2 POWER while its controller has exactly 1
    // card in Memory (GameLogic.php ~11053). Champion CardID-patched to Lorraine, Blademaster
    // (WARRIOR), with exactly 1 filler card seeded into myMemory. Reserve cost 1.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'TJTeWcZnsQ']],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'], // exactly 1 card in memory
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'vBetRTn3eW'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Warrior's Longsword: [Class Bonus] +1 POWER ---
$fixtures['warriors-longsword-class-bonus-power'] = [
    'testedCards' => ['jF1VuIR7a6'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Warrior's Longsword's only ability is [Class Bonus] +1 POWER, a static computed-power case
    // (GameLogic.php ~10998, not an Enter trigger) -- seeded directly onto the field. Champion
    // CardID-patched to Lorraine, Blademaster (WARRIOR). Champion attacks wielding the weapon;
    // computed power includes the printed 1 POWER of the weapon itself plus the +1 Class Bonus.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'TJTeWcZnsQ']],
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'jF1VuIR7a6'], // Warrior's Longsword
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Prismatic Edge: [Class Bonus] On Enter, reveal memory: Fire deals 3 damage ---
$fixtures['prismatic-edge-fire-damage'] = [
    'testedCards' => ['FxYwR2azTt'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Prismatic Edge
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Prismatic Edge is CRUX element (verified via CardElement(), not NORM) with a 2-memory
    // On Enter ability requiring [Class Bonus] WARRIOR -- champion CardID-patched to Lorraine,
    // Crux Knight (WARRIOR + CRUX unlock). It must actually materialize (memory-cost REGALIA are
    // Material-zone-only cards, like this deck's own Clarent/Backup Charger/Purifying Thurible)
    // for its enterAbilities to fire, so the deck's Material section is just the champion slot
    // plus Prismatic Edge itself, giving it myMaterial-0. 2 filler cards are seeded into myMemory
    // to pay the 2-memory cost -- FINISHPAYMATERIALIZE banishes the FIRST N memory cards as
    // payment, so a real FIRE-element card (Spirit of Fire itself) is seeded into the OPPONENT's
    // memory instead, untouched by payment, to trigger the "reveal memory for Fire" branch
    // (revealing either player's memory counts) without index-ordering risk. The Fire branch
    // deals 3 damage to a chosen unit (PrismaticEdgeFire, CardDQHandlers.php) -- P2's Dungeon
    // Guide is the target.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'NfbZ0nouSQ']],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'px60u5n1do'],
        ['player' => 2, 'zone' => 'myMemory', 'cardID' => 'LMyKyVC2O9'], // Spirit of Fire (FIRE element), untouched by payment
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide, damage target
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Powered Defender: dealDamageAbilities dispatch coverage (z07nau5sw9) ---
// Effects Stack fixture-coverage gap fill: dealDamageAbilities["z07nau5sw9:0"] summons a
// Powercell token (qzzadf9q1v) whenever Powered Defender is dealt damage. No prior fixture
// deals real (combat or non-combat) damage to this card. WATER element ally, cost 4 reserve,
// class bonus (Taunt) not needed for this test. Uses a cheap real damage spell -- Charge the
// Soul (ra9950o14t, NORM element, 1 reserve, "Deal 1 damage to target unit") -- targeting our
// own ally, which is the simplest real damage-dealing event available (no combat/attack
// sequence needed, since the dealDamageAbilities dispatch doesn't check isCombat for this
// card -- unlike pal7cpvn96/Intrepid Spearman's replacement effect).
$fixtures['powered-defender-dealt-damage-summons-powercell'] = [
    'testedCards' => ['z07nau5sw9'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Powered Defender is seeded directly onto the field (myField-1, awake by default) rather
    // than played from hand -- setup field-seeding bypasses cost/element checks entirely, so no
    // WATER element access is needed. Charge the Soul is seeded into hand (myHand-7), then
    // played by resolving the OpportunityWindowFirstResponse MZMAYCHOOSE with its own mzID
    // (mode=10002 FSM free-play is a no-op here -- verified live: reports success but never
    // removes the card from hand). Its 1-reserve cost is
    // paid with myHand-0, then the resulting MZCHOOSE:myField-0&myField-1&theirField-0 targets
    // myField-1 (Powered Defender itself). Verified live: this leaves Powered Defender with 1
    // damage and a genuine qzzadf9q1v Powercell token freshly created at myField-2 -- no
    // additional PASS actions are needed, the Effects Stack resolves immediately.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'z07nau5sw9'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ra9950o14t'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-7', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Intrepid Spearman: dealDamageAbilities dispatch coverage (pal7cpvn96) ---
// Effects Stack fixture-coverage gap fill: dealDamageAbilities["pal7cpvn96:0"] is itself an
// intentional no-op stub (its real logic is a synchronous replacement effect inlined in
// OnDealDamage(), GrandArchiveSim/Custom/CombatLogic.php ~line 4925, guarded by
// DecisionQueueController::GetVariable("CombatAttacker") !== null -- i.e. it only applies to
// REAL COMBAT damage, unlike Powered Defender's dispatch-table trigger above). No prior fixture
// deals real combat damage to this card, so this fixture exercises the replacement effect
// itself: [Level 1+] once per turn, reveal a random memory card when combat damage would be
// dealt; if wind element, prevent 3 of that damage.
//
// Requires: (1) champion at Level 1+ (PlayerLevel() reads ObjectCurrentLevel(), which adds a
// "level" counter on top of the card's printed level -- patched directly via setProperties
// rather than scripting a real level-up), (2) a real attack that deals combat damage to this
// ally specifically. GetValidAttackTargets() (CombatLogic.php) allows a champion to attack an
// opposing ALLY directly (ZoneSearch("theirField", ["ALLY","CHAMPION"])), so no
// intercept/redirect dance is needed -- P2's champion just targets theirField-1 (P1's Intrepid
// Spearman) directly. P2's champion (Spirit of Fire, 0 base power) needs a weapon to have any
// attack power; Executioner's Spear (zv6yp6q7zw, printed 1 POWER, no durability requirement --
// verified live, unlike Tideholder Claymore which requires durability counters to be a legal
// weapon choice per GetAvailableWeapons()) is seeded onto P2's field. (3) Exactly one memory
// card, and it must be WIND element, for a deterministic array_rand() reveal -- Vainglory
// Retribution (qtzsekkjn3, WIND) is reused as the memory card. With 1 combat damage and -3
// prevention, final Damage clamps to 0 -- verified live: TurnEffects gains the "pal7cpvn96"
// once-per-turn marker and FlashMessage is set to "REVEAL:qtzsekkjn3" regardless, which is
// exactly the code path that (per CombatLogic.php) sets TurnEffects *before* the amount<=0
// early-return, so both survive as independent proof the replacement effect actually ran.
$fixtures['intrepid-spearman-combat-damage-reveal-wind-prevent'] = [
    'testedCards' => ['pal7cpvn96'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'pal7cpvn96'],
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'qtzsekkjn3'], // sole memory card, WIND element
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['level' => 1]]], // champion Level 1+
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'zv6yp6q7zw'], // Executioner's Spear, 1 POWER
    ],
    'actions' => [
        // P1 has nothing playable this turn (no hand cards seeded) -- a single health-pass ends
        // the whole turn and hands priority straight to P2's MAIN phase (verified live).
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline P2's own MAT-phase materialize offer
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''], // P2's champion attacks
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // choose Executioner's Spear as the weapon
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''], // target P1's Intrepid Spearman directly (not the champion)
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline Retaliate
    ],
];

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
$fixtures['vainglory-retribution-play-card-trigger'] = [
    'testedCards' => ['qtzsekkjn3'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // PRISMATIC_CODEX_IGNORE_ELEMENT bypasses the WIND element-access check for this one
    // activation (CanPlayerUseCardElement, GameLogic.php) without scripting a real element
    // unlock -- self-consuming, matches the card's own real element gate rather than faking it
    // via a champion Subcards patch. Vainglory Retribution is seeded directly into hand at the
    // next open slot after the natural 7-card opening hand (myHand-7).
    'setup' => [
        ['player' => 1, 'globalEffect' => 'PRISMATIC_CODEX_IGNORE_ELEMENT'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'qtzsekkjn3'],
    ],
    // Play Vainglory Retribution (mode 10002 FSM free play), pay its 4 reserve cost with four
    // "choose a card from myHand" MZCHOOSE decisions (always myHand-0 -- each payment removes
    // that slot and shifts the rest down, so the index is stable across all four), then both
    // players pass the resulting Effects Stack Opportunity windows -- first for Vainglory
    // Retribution's own activation entry, then a second time for the PLAY_CARD trigger entry
    // that QueuePlayCardTriggeredAbility() pushes once the card resolves.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Red Slime stack-order choice: a generated closure's own synchronous multi-kill sweep
// (mttsvbgl6f:0 On Death, GeneratedCode/GeneratedMacroCode.php) chain-kills 2+ of its own
// controller's other allies with On Death triggers. ResolveTopOfEffectStack() (hand-written,
// OpportunityLogic.php) now wraps its whole trigger-resolution dispatch in
// BeginTriggeredAbilityBatch()/EndTriggeredAbilityBatch() so this case gets the same
// controller-chooses-stacking-order treatment as OnAttackTrigger/OnHitTrigger/OnKillTrigger.
$fixtures['redslime-ondeath-sweep-stack-order-choice'] = [
    'testedCards' => ['mttsvbgl6f'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Killed via two casts of Charge the Soul (ra9950o14t, NORM, 1 reserve, "Deal 1 damage to
    // target unit") targeting our own Red Slime, rather than combat -- no weapon/retaliate/
    // combat-cleanup timing to fight, and (per the powered-defender-dealt-damage-summons-
    // powercell precedent) a single cast resolves immediately with no extra PASS actions needed.
    'setup' => [
        // Guo Jia, Chosen Disciple: plain single-class TAMER champion with no special-cased
        // ObjectCurrentHP/FieldAfterAdd logic (unlike Silvie, Wilds Whisperer, whose "next
        // Animal/Beast ally enters with a buff counter" passive silently buffed Red Slime's
        // life from 2 to 3 -- Red Slime is subtyped BEAST -- and made it survive exactly-lethal
        // damage; verified live via a temporary debug print in OnDealDamage).
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'j6dkdoxyqt'], // Guo Jia, Chosen Disciple -- TAMER champion, satisfies Red Slime's Class Bonus
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'mttsvbgl6f'], // Red Slime, life 2
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'Lewf9sfv9m'], // Golden Pawn, life 1 -- On Death: Draw a card
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'loSCQzxqi1'], // Heavenly Drake, life 3 -- On Death: Recover 3
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ra9950o14t'], // Charge the Soul #1 -- myHand-7
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ra9950o14t'], // Charge the Soul #2 -- myHand-8
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        // Cast #1: myHand-7, pay 1 reserve with myHand-0, target Red Slime at myField-2
        // (champion=0, Silvie=1, Red Slime=2, Golden Pawn=3, Heavenly Drake=4).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-7', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Decline the Effect Stack Opportunity's "play a fast card in response" offer
        // (the second Charge the Soul copy, myHand-6) so the first cast resolves first.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        // Target Red Slime (myField-2) with the first Charge the Soul.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        // Cast #2: second Charge the Soul copy, now at myHand-6 after the first cast + its
        // reserve-cost payment each removed one card ahead of it. First cast's target-response
        // (mode=100, myHand-N) worked without FSM because it was answering the still-open
        // OpportunityWindowFirstResponse MZMAYCHOOSE left over from the P1/P2 health-passes --
        // once that resolves to a clean MAIN-phase state (decisionQueue=0), a fresh play needs
        // the normal FSM free-play click instead.
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-6!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-2', 'chkInput' => [], 'inputText' => ''],
        // The fix under test: Red Slime's death sweep just killed both Golden Pawn and Heavenly
        // Drake simultaneously, and the controller is now asked to choose their stacking order
        // (MZCHOOSE:myGraveyard-3&myGraveyard-4 / TriggerOrderPickResolve) instead of them
        // silently auto-resolving in a hardcoded order. Pick myGraveyard-3 (Golden Pawn) first.
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-3', 'chkInput' => [], 'inputText' => ''],
    ],
];

// ---------------------------------------------------------------------------
// playCardAbilities dispatch coverage batch: the remaining 13 entries besides
// qtzsekkjn3 (see vainglory-retribution-play-card-trigger above). Confirmed via
// FirePlayCardTriggeredAbility instrumentation that NONE of these 13 ever fired
// across the full pre-fix suite -- the earlier assumption that 12 of 14 already
// had fixture coverage was wrong; grep hits on these CardIDs in other fixtures'
// gamestate dumps were decklist/hand noise, not an actual play of the card.
// ---------------------------------------------------------------------------

$fillerDeck = <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK;

$bigFillerDeck = <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
10 Dungeon Guide
10 Fairy Whispers
10 Fluffy Shopkeep
10 Stocked Outpost
DECK;

// --- Lesser Boon of Shou: As gained, put an enlighten counter on your champion (rSIXf50oBc) ---
$fixtures['lesser-boon-of-shou-enlighten-on-play'] = [
    'testedCards' => ['rSIXf50oBc'],
    'deck' => $fillerDeck,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'rSIXf50oBc'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Greater Boon of Shou: As gained, draw a card (Zw0T2GmowK) ---
$fixtures['greater-boon-of-shou-draw-on-play'] = [
    'testedCards' => ['Zw0T2GmowK'],
    'deck' => $fillerDeck,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'Zw0T2GmowK'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Cavalier Rescue: target ally gains a turn effect (+3 LIFE until EOT) (75uhspxqme) ---
$fixtures['cavalier-rescue-target-turn-effect'] = [
    'testedCards' => ['75uhspxqme'],
    'deck' => $fillerDeck,
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['TurnEffects' => []]], // Dungeon Guide, target ally
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '75uhspxqme'],
    ],
    // The target MZCHOOSE fires immediately once reserve is paid (negating/targeting something
    // still mid-resolution doesn't open a fresh Opportunity window) -- see the note on
    // astral-seal-negate-activation-banish. Answer it as the very next action; a PASS submitted
    // first would be misapplied against that pending MZCHOOSE and the effect would silently never
    // land, even though replay still "verifies" against the (also-silently-wrong) recorded state.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Tempest Downfall: deal 3 damage to target ally/champion (4etkr73opc) ---
$fixtures['tempest-downfall-target-damage'] = [
    'testedCards' => ['4etkr73opc'],
    'deck' => $fillerDeck,
    'setup' => [
        // A permanent lineage unlock, not PRISMATIC_CODEX_IGNORE_ELEMENT: that self-consuming
        // bypass is checked (and consumed) TWICE for a single activation -- once by
        // CanActivateCard()'s own element gate, again by DoActivateCard()'s redundant
        // CanPlayerUseCardElement($player,$cardID,true,true) call -- so a 1-stack budget is
        // silently exhausted by the first check and the second always fails, aborting the whole
        // activation with no decision ever queued and no error.
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y', 'setProperties' => ['TurnEffects' => []]], // Dungeon Guide, target ally
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4etkr73opc'],
    ],
    // The lineage-unlocked activation opens a real Opportunity window (other WIND-eligible filler
    // copies remain in hand), unlike the "nothing left to offer" fixtures elsewhere in this file --
    // decline it ('-') so 4etkr73opc itself resolves and its on-play target MZCHOOSE is queued,
    // then answer that immediately (see astral-seal-negate-activation-banish's note).
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Rebounding Gust: move target ally to its controller's memory (9e0z7hb9id) ---
$fixtures['rebounding-gust-target-to-memory'] = [
    'testedCards' => ['9e0z7hb9id'],
    'deck' => $fillerDeck,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['pNiyaGlIe7']]], // WIND lineage/element unlock
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // opponent's Dungeon Guide, target ally
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '9e0z7hb9id'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Sleety Retreat: target Ranger ally/champion becomes distant (j9fkuzgg9i) ---
$fixtures['sleety-retreat-target-distant'] = [
    'testedCards' => ['j9fkuzgg9i'],
    'deck' => $fillerDeck,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'ki6fxxgmue', 'setProperties' => ['TurnEffects' => []]], // Bertha, Spry Howitzer (RANGER ally)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'j9fkuzgg9i'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Enervating Decay: destroy target opposing ally, recover champion its HP (jh9s424gjr) ---
$fixtures['enervating-decay-target-destroy-recover'] = [
    'testedCards' => ['jh9s424gjr'],
    'deck' => $fillerDeck,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1'], 'Damage' => 5]], // TERA lineage/element unlock + pre-existing damage for the recover assertion
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'em6eEh9q8y'], // opponent's Dungeon Guide, target ally
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'jh9s424gjr'],
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Pouvoir Absolu: banish top 10 own deck, add omen counters ([Ciel Bonus]) (OylAWd6Tew) ---
$fixtures['pouvoir-absolu-banish-top10-omen'] = [
    'testedCards' => ['OylAWd6Tew'],
    'deck' => $bigFillerDeck,
    'setup' => [
        ['player' => 1, 'globalEffect' => 'PRISMATIC_CODEX_IGNORE_ELEMENT'], // UMBRA unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'em6eEh9q8y'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'px60u5n1do'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'px60u5n1do'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'px60u5n1do'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'px60u5n1do'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'AOMXEGeSQk'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'AOMXEGeSQk'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'AOMXEGeSQk'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'AOMXEGeSQk'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY'],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'OylAWd6Tew'],
    ],
    'actions' => array_merge(
        [['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-21!FSM!', 'chkInput' => [], 'inputText' => '']],
        array_fill(0, 13, ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => '']),
        [
            ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
            ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
            ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
            ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ]
    ),
];

// --- Lesser Boon of Kanaloa: both players discard 3 (P8sbt2gXkn) ---
$fixtures['lesser-boon-of-kanaloa-both-discard-3'] = [
    'testedCards' => ['P8sbt2gXkn'],
    'deck' => $fillerDeck,
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['tafqldAGRF']]], // WATER lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'P8sbt2gXkn'],
    ],
    // The on-play trigger (both players discard 3) fires immediately once reserve is paid -- no
    // Opportunity-window passes needed or possible here. A PASS submitted against the pending
    // discard MZCHOOSE is treated by DiscardChosenCard as "discard nothing" (it explicitly no-ops
    // on "-"/""/"PASS"), silently eating one of the six discard rounds per stray PASS -- see the
    // note on astral-seal-negate-activation-banish for the general pattern.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Lesser Boon of Territories: scavenge 10 for a Domain card (ZpM7gliLxm) ---
$fixtures['lesser-boon-of-territories-scavenge-domain'] = [
    'testedCards' => ['ZpM7gliLxm'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Stocked Outpost
4 Stocked Outpost
4 Stocked Outpost
4 Stocked Outpost
DECK,
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ZpM7gliLxm'],
    ],
    // The scavenge choice fires immediately once reserve is paid (see the note on
    // astral-seal-negate-activation-banish); a PASS against that pending MZCHOOSE is treated by
    // ScavengeChoose as "decline" and silently skips the scavenge instead of answering it.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myTempZone-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Astral Seal: negate target activation, banish it (e3aebjvwbc) ---
$fixtures['astral-seal-negate-activation-banish'] = [
    'testedCards' => ['e3aebjvwbc'],
    'deck' => $fillerDeck,
    'setup' => [
        // A real lineage-based unlock (not the self-consuming PRISMATIC_CODEX_IGNORE_ELEMENT
        // bypass) -- that bypass is consumed once per non-NORM card *considered* while the engine
        // assembles the opportunity-window candidate list, not just once per card actually played,
        // so it silently starves out before reaching the real activation in a multi-candidate
        // window. A permanent lineage unlock has no such budget.
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba']]], // ASTRA lineage/element unlock
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (NORM ALLY, no element unlock needed), bait activation to negate
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'e3aebjvwbc'],
    ],
    // Play Dungeon Guide (materialize, 3 reserve), then in its Opportunity window the engine offers
    // every fast-eligible hand card at once (MZMAYCHOOSE lists them "&"-joined) -- pick Astral Seal
    // specifically among the offered candidates. Once Astral Seal's own 3 reserve is paid, its
    // on-play trigger fires *immediately* off the same call (no further Opportunity-window passes
    // needed -- negating something still mid-resolution doesn't open a new window) and queues the
    // MZCHOOSE target choice right there, so it must be answered as the very next action.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-4', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        // Choose EffectStack-0 (Dungeon Guide) to negate/banish, not EffectStack-1 (Astral Seal's
        // own still-resolving trigger entry, also offered as a technically-legal but wrong target).
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'EffectStack-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Annul Spell: negate target SPELL activation unless controller pays 3 (u817uqlk1j) ---
$fixtures['annul-spell-negate-spell-activation'] = [
    'testedCards' => ['u817uqlk1j'],
    'deck' => $fillerDeck,
    'setup' => [
        // Both u817uqlk1j (NORM) and its bait (Charge the Soul, also NORM) need no element unlock.
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'ra9950o14t'], // Charge the Soul (NORM SPELL), bait activation to negate
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'u817uqlk1j'],
    ],
    // Play Charge the Soul (materialize/activate, 1 reserve), then in its Opportunity window respond
    // with Annul Spell (myHand-6 at this point). Once Annul Spell's own 3 reserve is paid, its
    // on-play trigger fires immediately (no further Opportunity-window passes) and queues the
    // MZCHOOSE target choice, which must be answered as the very next action. Since self-controller
    // still has exactly 3 reserve-payable cards left (>= payAmount 3), the engine then asks Charge
    // the Soul's controller (also player 1) whether to pay 3 to prevent the negate -- answer NO so
    // the negate actually fires.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-6', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'EffectStack-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Imperial Accord: negate target advanced-element activation unless controller pays 6 (1S7Q5fqX5u) ---
$fixtures['imperial-accord-negate-advanced-element'] = [
    'testedCards' => ['1S7Q5fqX5u'],
    'deck' => $fillerDeck,
    'setup' => [
        // Permanent lineage unlocks (see astral-seal-negate-activation-banish's note on why not
        // PRISMATIC_CODEX_IGNORE_ELEMENT): NEOS for the bait, EXALTED + WATER for Imperial Accord
        // itself (a dual-element card).
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['n2jnltv5kl', 'KqBosnU7pU', 'tafqldAGRF']]],
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4n1n3gygoj'], // Neos Sight (NEOS, advanced element), bait activation to negate
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '1S7Q5fqX5u'],
    ],
    // Play Neos Sight (free, reserve 0) -- its Opportunity window offers Imperial Accord as a fast
    // response. Once Imperial Accord's own 2 reserve is paid, its on-play trigger fires immediately
    // and queues the MZCHOOSE target choice, answered as the very next action. Imperial Accord's
    // "pay 6 to prevent" check needs the target's controller (self, player 1) to have >= 6
    // reserve-payable cards; after paying for both cards, far fewer than 6 remain, so the engine
    // auto-negates with no further YES/NO prompt.
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-7', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'EffectStack-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// =============================================================================
// Zander Pantheon Starter deck semantic fixtures
// =============================================================================

// --- Thieving Cut: Prepare 1, On Hit draw a card if it was prepared ---
$fixtures['thieving-cut-prepare-onhit-draw'] = [
    'testedCards' => ['7t9m4muq2r'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Thieving Cut's element is NORM, so no lineage patch is needed. ATTACK cards can't be
    // activated by the game's first player on turn 1 (CanActivateAttackCardNow/
    // IsFirstTurnAttackLocked in GameLogic.php), so player 1 ends turn 1 first and player 2 plays
    // Thieving Cut on their own turn 1 instead (same turn-cycle shape as
    // bulwark-sword-class-bonus-attack-cost). Player 2's champion is pre-seeded with 1 preparation
    // counter directly (normally only reachable via a separate preparation-counter-granting
    // effect) so its "Prepare 1" additional cost (remove 1 preparation counter as you activate it,
    // CardActivated macro 7t9m4muq2r:0 in GeneratedMacroCode.php) can actually be paid. Answering
    // YES stores wasPrepared=YES via DecisionQueueController; the On Hit macro (7t9m4muq2r:0
    // onHitAbilities) reads that variable back and draws a card only when it is "YES" -- a card
    // that was never marked prepared would not draw.
    'setup' => [
        ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['preparation' => 1]]], // Prepare-ability cost fuel
        ['player' => 2, 'zone' => 'myHand', 'cardID' => '7t9m4muq2r'], // Thieving Cut, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Insignia of the Corhazi: (3), REST: put a preparation counter on your champion ---
$fixtures['insignia-of-corhazi-rest-prepare'] = [
    'testedCards' => ['52u81v4c0z'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Insignia of the Corhazi is seeded directly onto the field (same pattern as
    // necklace-of-foresight-banish-glimpse) rather than played from hand, so its materialize flow
    // is out of scope -- this fixture is only about the always-available "(3), [REST]: put a
    // preparation counter" activated ability (CardActivated macro 52u81v4c0z:0 in
    // GeneratedMacroCode.php). Its element is LUXEM, and DoActivateCard()'s
    // CanPlayerUseCardElement() gate applies to a field item's reserve-cost activated ability the
    // same as a hand play (verified live -- with the default FIRE/NORM starting champion, the
    // whole activation silently no-ops before ever reaching MZMove to the EffectStack), so the
    // starting champion's CardID is patched directly to Zander, Blinding Steel (LUXEM, ASSASSIN).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'UAF6Nr7GUE']], // Zander, Blinding Steel (LUXEM) - element-requirement precondition
        ['player' => 1, 'zone' => 'myField', 'cardID' => '52u81v4c0z'], // Insignia of the Corhazi, seeded straight onto the field
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Orb of Choking Fumes: Banish -- opponents' cards cost 1 more this turn; Class Bonus draw ---
$fixtures['orb-of-choking-fumes-banish-cost-cb-draw'] = [
    'testedCards' => ['llQe0cg4xJ'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Orb of Choking Fumes is seeded directly onto the field (materialize flow out of scope, same
    // as insignia-of-corhazi-rest-prepare). The starting champion's CardID is patched directly to
    // Zander, Deft Executor (ASSASSIN) so IsClassBonusActive($player, ["ASSASSIN"]) is true for the
    // ActivateAbility macro's Class Bonus draw clause.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'fc4ic5fmaa']], // Zander, Deft Executor (ASSASSIN) - Class Bonus precondition
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'llQe0cg4xJ'], // Orb of Choking Fumes, seeded straight onto the field
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Uncover the Plot: target player reveals memory, draw; Class Bonus +2 preparation ---
$fixtures['uncover-the-plot-reveal-draw-prepare'] = [
    'testedCards' => ['4zkTRt8qXn'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Uncover the Plot's element is LUXEM, so the starting champion's CardID is patched directly
    // to Zander, Blinding Steel (LUXEM, ASSASSIN) -- both the element-requirement precondition to
    // activate it at all and the Class Bonus preparation-counter clause. A filler card is seeded
    // into the OPPONENT's memory ('theirMemory' with player=>1, i.e. player 2's memory -- setup
    // zone names are relative to the acting player) so the "target player reveals all cards in
    // their memory" half has something to reveal, observable via the "REVEAL:<cardID>" flash
    // message DoRevealCard() sets (GameLogic.php).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'UAF6Nr7GUE']], // Zander, Blinding Steel (LUXEM, ASSASSIN)
        ['player' => 1, 'zone' => 'theirMemory', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide, seeded into the opponent's (player 2's) memory
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4zkTRt8qXn'], // Uncover the Plot, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'NO', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Bathe in Light: Recover 4; at the beginning of your next recollection phase, Recover 4 ---
$fixtures['bathe-in-light-recover-delayed'] = [
    'testedCards' => ['d9zax2g20h'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Bathe in Light's element is LUXEM; the starting champion's CardID is patched directly to
    // Zander, Blinding Steel (LUXEM) so it can be legally activated, and its Damage is pre-set to
    // 10 so the unconditional "Recover 4" is observable as a Damage decrease. The delayed "at your
    // next recollection phase, Recover 4" half is out of scope (would require advancing a full
    // turn cycle); the immediate Recover 4 and the BATHE_IN_LIGHT_RECOVER global effect it
    // schedules (GameLogic.php CardActivated macro) are both directly asserted.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'UAF6Nr7GUE', 'Damage' => 10]], // Zander, Blinding Steel (LUXEM) + damage precondition
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'd9zax2g20h'], // Bathe in Light, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Cunning Broker: [REST], remove two preparation counters from your champion: Draw a card ---
$fixtures['cunning-broker-rest-remove-prep-draw'] = [
    'testedCards' => ['oy34bro89w'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Cunning Broker is seeded directly onto the field (materialize flow out of scope). The
    // starting champion is pre-seeded with 2 preparation counters so the activated ability's cost
    // (remove 2 preparation counters from your champion, gated by ActivatedAbilityCost() in
    // GameLogic.php requiring >= 2) can actually be paid.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['preparation' => 2]]], // Ability cost fuel
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'oy34bro89w'], // Cunning Broker, seeded straight onto the field
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myField-1!CustomInput!Activate:0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Disenchant: Destroy target phantasia ---
$fixtures['disenchant-destroy-phantasia'] = [
    'testedCards' => ['zd83net7x0'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Disenchant's element is NORM, so no lineage patch is needed. Scorching Imperilment
    // (aj7pz79wsp, a PHANTASIA card) is seeded onto the opponent's field as the destroy target.
    'setup' => [
        ['player' => 1, 'zone' => 'theirField', 'cardID' => 'aj7pz79wsp'], // Scorching Imperilment (PHANTASIA), destroy target
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'zd83net7x0'], // Disenchant, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Nimble Court Assassin: Ambush, Vigor; On Enter you gain agility 3 for this turn ---
$fixtures['nimble-court-assassin-enter-agility'] = [
    'testedCards' => ['i2vPUpbPEl'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Nimble Court Assassin's element is EXALTED (an advanced element enabled only while another
    // advanced element is enabled), so the starting champion's Subcards are patched with a real
    // TERA champion to unlock element access generically the same way other advanced-element
    // fixtures do (Exalted enables off ANY other advanced element being enabled).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // TERA lineage/element unlock (enables Exalted too)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'i2vPUpbPEl'], // Nimble Court Assassin, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Sacred Barrier: the next non-combat damage to each ally this turn is prevented by 4 ---
$fixtures['sacred-barrier-prevent-noncombat'] = [
    'testedCards' => ['hYDqthNDpB'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Sacred Barrier's element is NORM, so no lineage patch is needed. Cunning Broker (an ALLY
    // with no On Enter trigger of its own, unlike Fluffy Shopkeep/Dungeon Guide -- verified live
    // that seeding either of those left a dangling MZMAYCHOOSE/CUSTOM Enter decision in the queue
    // that blocked every subsequent FSM action) is seeded onto the field as the beneficiary of the
    // "each ally" clause. Activating a card puts it on the EffectStack behind an Opportunity
    // window (verified live via a throwaway EffectStack/DQ dump) -- both players must pass their
    // response window (P1's own MZMAYCHOOSE offering to respond by activating Cunning Broker's
    // Rest ability, then P2's) before Sacred Barrier actually resolves.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'oy34bro89w'], // Cunning Broker (ALLY, no On Enter trigger)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'hYDqthNDpB'], // Sacred Barrier, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Accepted Contract: Put three preparation counters on your champion ---
$fixtures['accepted-contract-prepare-three'] = [
    'testedCards' => ['uZCyXDNJ6I'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Accepted Contract's element is NORM, so no lineage patch is needed.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'uZCyXDNJ6I'], // Accepted Contract, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Increasing Danger: Draw a card. Each player draws a card into their memory ---
$fixtures['increasing-danger-draw-memory'] = [
    'testedCards' => ['7tUvIHeo0i'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Increasing Danger's element is FIRE, matching the starting champion, so no lineage patch is
    // needed.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '7tUvIHeo0i'], // Increasing Danger, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Shared Fervor: Each player draws a card into their memory. You gain the Crowd's Favor status ---
$fixtures['shared-fervor-memory-crowds-favor'] = [
    'testedCards' => ['RnUpMoSb4w'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Shared Fervor's element is NORM, so no lineage patch is needed.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'RnUpMoSb4w'], // Shared Fervor, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Tenderheart Guard: On Enter, each player may discard to draw into memory; gain Crowd's Favor ---
$fixtures['tenderheart-guard-enter-discard-draw-crowds-favor'] = [
    'testedCards' => ['0ZWcrEsFHA'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Tenderheart Guard's element is FIRE, matching the starting champion, so no lineage patch is
    // needed. Both players answer YES to the discard-to-draw-into-memory offer so both halves of
    // the trigger (TenderheartGuardEnter -> TenderheartGuardDiscard -> TenderheartGuardDrawMemory,
    // CardDQHandlers.php) are exercised, plus the unconditional GainCrowdsFavor() call.
    'setup' => [
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '0ZWcrEsFHA'], // Tenderheart Guard, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Wandering Glaivier: On Death, each player draws a card ---
$fixtures['wandering-glaivier-on-death-draw'] = [
    'testedCards' => ['p6120p3f5d'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Wandering Glaivier's element is FIRE, matching the starting champion, so no lineage patch is
    // needed. Rule 1.h blocks the game's first player from attacking at all on turn 1 (any attack,
    // not just ATTACK-type cards -- BeginCombatPhase() in CombatLogic.php), so player 1 ends turn 1
    // first and player 2 attacks with Wandering Glaivier on their own turn 1 instead (same
    // turn-cycle shape as thieving-cut-prepare-onhit-draw). It is seeded directly onto player 2's
    // field, awake, so it can attack immediately. Its 1 Life means any retaliate damage kills it --
    // but the starting champion's printed POWER is blank/0 (CardPower() returns -1, verified live),
    // so it is NOT itself a legal retaliator (GetRetaliatorOptions requires POWER > 0). A Dungeon
    // Guide (1 POWER, no dangling On Enter decision when seeded this way -- verified live, unlike
    // Fluffy Shopkeep which leaves an unresolved MZMAYCHOOSE/CUSTOM Enter chain in the queue) is
    // seeded onto player 1's own field instead as the retaliator. Player 1 accepts the
    // "Retaliate?" MZMAYCHOOSE with it (myField-1 from their perspective), which deals 1 combat
    // damage back to Wandering Glaivier and destroys it, firing its On Death trigger. Only the
    // attack's actual TARGET is offered as a retaliator (GetRetaliatorOptions in CombatLogic.php
    // otherwise only allows specific hardcoded Ambush-style cards to retaliate without being
    // targeted -- a plain awake/positive-power ally that wasn't the target is NOT offered,
    // verified live), so Dungeon Guide itself -- not the champion -- is the attack's target.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'p6120p3f5d'], // Wandering Glaivier
        ['player' => 2, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can attack
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (1 POWER) - attack target and retaliator
        ['player' => 1, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can retaliate
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Wandering Glaivier
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''], // target Dungeon Guide
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // accept Retaliate with Dungeon Guide
    ],
];

// --- Sable Remnant: [Class Bonus] +1 POWER ---
$fixtures['sable-remnant-class-bonus-power'] = [
    'testedCards' => ['8n4zw4gq5w'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Sable Remnant's element is NORM, so only the Class Bonus condition needs a precondition:
    // the starting champion's CardID is patched directly to Zander, Deft Executor (ASSASSIN) so
    // IsClassBonusActive($player, ["ASSASSIN"]) is true. Sable Remnant's printed POWER is 1
    // (verified via CardPower()); the computed_power_equals assertion of 2 is only possible if the
    // static +1 POWER case in GameLogic.php's power-modifier switch actually applied. A single
    // harmless "end turn 1" action is included even though nothing about ending the turn matters
    // to this static ability -- RunIntegrationTests.php's step-0 (pre-action) assertion check
    // never calls ParseGamestate() itself, so with zero actions it reads stale runtime globals
    // left over from whichever fixture ran immediately before this one in the same process
    // (verified live: computed_power_equals came back as an unrelated leftover value), while the
    // step-1 (post-action) check is always evaluated against this fixture's own freshly-parsed
    // state.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'fc4ic5fmaa']], // Zander, Deft Executor (ASSASSIN)
        ['player' => 1, 'zone' => 'myField', 'cardID' => '8n4zw4gq5w'], // Sable Remnant, seeded straight onto the field
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // harmless: ends turn 1
    ],
];

// --- Photic Blade: gets +1 POWER for each refinement counter on it ---
$fixtures['photic-blade-refinement-power'] = [
    'testedCards' => ['NRBO0nVMdl'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Photic Blade's element is LUXEM, but it is seeded directly onto the field (materialize flow
    // out of scope, same pattern as necklace-of-foresight-banish-glimpse), so no lineage patch is
    // needed for this always-on static clause. It is pre-seeded with 2 refinement counters
    // directly (normally only reachable via its own [Class Bonus] recover-triggered listener,
    // which is out of scope here). Photic Blade's printed POWER is 3 (verified via CardPower());
    // the computed_power_equals assertion of 5 is only possible if the +1-per-refinement-counter
    // static case in GameLogic.php's power-modifier switch actually applied. A single harmless
    // "end turn 1" action is included so the assertion runs against step 1 (this fixture's own
    // freshly-parsed state) rather than step 0, which RunIntegrationTests.php evaluates before
    // ever calling ParseGamestate() for this fixture (verified live -- see
    // sable-remnant-class-bonus-power's note for the same gotcha).
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'NRBO0nVMdl', 'setProperties' => ['Counters' => ['refinement' => 2]]], // Photic Blade, 2 refinement counters
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // harmless: ends turn 1
    ],
];

// --- Elyan, Lustre Loyalty: [Class Bonus] whenever you recover, +X POWER (X = amount recovered) ---
$fixtures['elyan-lustre-loyalty-recover-power'] = [
    'testedCards' => ['2jgiM0p4dt'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Elyan's element is LUXEM; the starting champion's CardID is patched directly to Zander,
    // Blinding Steel (LUXEM, ASSASSIN) both for its own element-requirement precondition (Bathe in
    // Light, used to trigger a real recover event, is also LUXEM) and Elyan's own [Class Bonus]
    // condition. Elyan is seeded directly onto the field. The champion's Damage is pre-set to 10
    // so Bathe in Light's Recover 4 is a real, observable recover event that fires the recover
    // listener in GameLogic.php (~line 18437), which both tags Elyan with the
    // "2jgiM0p4dt_RECOVER_4" TurnEffects entry (+4 POWER, since 4 >= 4 also grants UNBLOCKABLE)
    // and -- because the recovered amount is >= 4 -- grants unblockable, both asserted below.
    // Elyan's printed POWER is 2 (verified via CardPower()); computed_power_equals of 6 is only
    // possible if the +X-per-recover-amount static case actually applied with X=4.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'UAF6Nr7GUE', 'Damage' => 10]], // Zander, Blinding Steel (LUXEM, ASSASSIN) + damage precondition
        ['player' => 1, 'zone' => 'myField', 'cardID' => '2jgiM0p4dt'], // Elyan, Lustre Loyalty
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'd9zax2g20h'], // Bathe in Light, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Lightveil Agent: whenever you recover, put a buff counter on CARDNAME ---
$fixtures['lightveil-agent-recover-buff-counter'] = [
    'testedCards' => ['jcaLgesx0e'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Lightveil Agent's element is LUXEM; the starting champion's CardID is patched directly to
    // Zander, Blinding Steel (LUXEM) so Bathe in Light (also LUXEM) can be legally activated. The
    // champion's Damage is pre-set to 4 so Bathe in Light's Recover 4 is a real, observable
    // recover event that fires the unconditional (no Class Bonus needed) recover listener in
    // GameLogic.php (~line 18447), which puts a buff counter on Lightveil Agent.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'UAF6Nr7GUE', 'Damage' => 4]], // Zander, Blinding Steel (LUXEM) + damage precondition
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'jcaLgesx0e'], // Lightveil Agent
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'd9zax2g20h'], // Bathe in Light, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
    ],
];

// --- Epochal Conqueror: as long as it's attacking a domain, it gets +3 POWER ---
$fixtures['epochal-conqueror-attack-domain-power'] = [
    'testedCards' => ['gR3LGjzKPS'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Epochal Conqueror's element is NORM, so no lineage patch is needed. Rule 1.h blocks the
    // game's first player from attacking on turn 1, so player 1 ends turn 1 and player 2 attacks
    // with Epochal Conqueror on their own turn 1 instead. Baidi, Oathsworn Palace (a plain DOMAIN
    // card with only a static ability and no activated ability of its own -- Bedlam Borough's own
    // "(2), REST: Cascade" activated ability was tried first but kept re-offering itself as a
    // perpetual Opportunity choice that never actually declined, verified live) is seeded onto the
    // opponent's field as the attack target; IsSiegeable() recognizes it via its SIEGEABLE
    // subtype, the actual condition GameLogic.php's power-modifier switch checks (not the DOMAIN
    // card type itself). Since the +3 POWER only applies transiently while CombatAttacker is set
    // (cleared again once combat fully resolves within the same action), it is proven indirectly:
    // the domain's durability drops by 4 (1 printed POWER + 3), not just 1.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'gR3LGjzKPS'], // Epochal Conqueror
        ['player' => 2, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can attack
        ['player' => 1, 'zone' => 'myField', 'cardID' => '43rtqovkti', 'setProperties' => ['Counters' => ['durability' => 5]]], // Baidi, Oathsworn Palace (DOMAIN/SIEGEABLE)
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Epochal Conqueror
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''], // target Baidi, Oathsworn Palace
    ],
];

// --- Curved Dagger: [Class Bonus] as long as your champion is attacking an ally, +1 POWER ---
$fixtures['curved-dagger-class-bonus-attack-ally-power'] = [
    'testedCards' => ['Q2ugqVm04E'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Curved Dagger's element is NORM. The starting champion's CardID is patched directly to
    // Zander, Deft Executor (ASSASSIN) for the Class Bonus condition. Rule 1.h blocks the game's
    // first player from attacking on turn 1, so player 1 ends turn 1 and player 2's champion
    // attacks (equipped with Curved Dagger) targeting a Dungeon Guide ally on their turn 1
    // instead. Curved Dagger's printed POWER is 1, and the champion's own printed POWER is blank
    // (-1, so it contributes nothing) -- since the +1 POWER is only active transiently while
    // CombatAttacker/CombatTarget are set (cleared again once combat fully resolves within the
    // same action, so a computed_power_equals snapshot after the fact can't observe it, verified
    // live), it is instead proven indirectly via the ally taking 2 damage, not just 1. The
    // defender's "Retaliate?" MZMAYCHOOSE (Dungeon Guide itself, being the target with positive
    // POWER, is eligible) must be explicitly declined so CombatApplyAttackerDamage actually lands.
    'setup' => [
        ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'fc4ic5fmaa']], // Zander, Deft Executor (ASSASSIN)
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'Q2ugqVm04E', 'setProperties' => ['Counters' => ['durability' => 1]]], // Curved Dagger, usable
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) - attack target
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-0!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with champion
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myField-1', 'chkInput' => [], 'inputText' => ''], // choose Curved Dagger as the weapon
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''], // target Dungeon Guide
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline Retaliate so CombatApplyAttackerDamage actually lands
    ],
];

// --- Piquant Shieldbearer: Taunt forces attackers to target it first while awake ---
$fixtures['piquant-shieldbearer-taunt-forces-target'] = [
    'testedCards' => ['Cvvvxlf0hi'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Piquant Shieldbearer's element is NORM, so no lineage patch is needed. Rule 1.h blocks the
    // game's first player from attacking on turn 1, so player 1 ends turn 1 and player 2 attacks
    // on their own turn 1 instead. The starting champion's printed POWER is blank/0
    // (CardPower() returns -1, verified live), so BeginCombatPhase() silently refuses to let it
    // attack at all with no weapon equipped -- a Dungeon Guide (1 POWER) is seeded onto player 2's
    // field as the actual attacker instead. Piquant Shieldbearer is seeded onto player 1's field,
    // awake, so its printed Taunt (parsed generically by HasKeyword_Taunt from the card text,
    // GeneratedCode/GeneratedKeywordCode.php) applies. GetLegalAttackTargets()/the Taunt filter in
    // CombatLogic.php (~line 512-525) restricts targeting to awake Taunt units when any exist, so
    // attempting to target the champion directly is an illegal selection -- rejected here as
    // negative-path semantic evidence -- and only the Taunt unit itself may then be legally
    // targeted.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'Cvvvxlf0hi'], // Piquant Shieldbearer (Taunt), awake
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (1 POWER) - actual attacker
        ['player' => 2, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can attack
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Dungeon Guide
        [
            'playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => '',
            'expectFailure' => true, 'semantic' => true, 'label' => 'Cannot target the champion directly while an awake Taunt unit is present',
        ],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''], // legal: target the Taunt unit itself
    ],
];

// --- Hasty Messenger: On Attack, you may discard a card to draw a card ---
$fixtures['hasty-messenger-on-attack-discard-draw'] = [
    'testedCards' => ['DsiRzt0trX'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Hasty Messenger's element is FIRE, matching the starting champion, so no lineage patch is
    // needed. Rule 1.h blocks the game's first player from attacking on turn 1, so player 1 ends
    // turn 1 and player 2 attacks with Hasty Messenger on their own turn 1 instead.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'DsiRzt0trX'], // Hasty Messenger
        ['player' => 2, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can attack
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Hasty Messenger
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // discard a card
    ],
];

// --- Zander, Prepared Scout: On Enter, Glimpse 2, put a preparation counter on itself ---
$fixtures['zander-prepared-scout-enter-glimpse-prepare'] = [
    'testedCards' => ['T3CIBknts0'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Zander, Prepared Scout
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Zander, Prepared Scout is level 1, one level above the default level-0 starting champion
    // (Spirit of Fire), so no lineage/element patch is needed -- 0->1 is a legal level-up
    // (CanChampionLevelUpIntoCard() in GameLogic.php requires targetLevel === currentLevel + 1;
    // same shape as tonoris-lone-mercenary-on-enter-taunt). Its NORM element is always playable.
    // Champion-swap materialization is only offered through the material-phase MZMAYCHOOSE at the
    // start of a turn, so both players end their first turn (P1 -> P2) to reach that prompt on
    // P1's next turn. Its printed cost is 1 memory, paid from a filler card seeded directly into
    // myMemory. Choosing it completes the swap and its On Enter ability (GeneratedMacroCode.php
    // enterAbilities["T3CIBknts0:0"]) both Glimpses 2 (resolved via the Top=/Bottom= deck-order
    // decision) and puts a preparation counter on itself.
    'setup' => [
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'n8wyfG9hbY'], // filler card in memory to pay the 1-memory level-up cost
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'Top=n8wyfG9hbY,em6eEh9q8y;Bottom=', 'chkInput' => [], 'inputText' => ''], // Glimpse 2: keep deck order unchanged
    ],
];

// --- Imperial Spy: On Kill, put a preparation counter on your champion ---
$fixtures['imperial-spy-onkill-prepare'] = [
    'testedCards' => ['l6gt7lh9v2'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Imperial Spy's element is NORM, so no lineage patch is needed. Rule 1.h blocks the game's
    // first player from attacking on turn 1, so player 1 ends turn 1 and player 2 attacks with
    // Imperial Spy on their own turn 1 instead. A directly-patched Damage precondition on a
    // freshly-seeded ally does NOT survive ending the turn -- GA's recollection/end-of-turn
    // processing clears Damage back to 0 for any object whose Damage was set outside a real
    // damage event (verified live: a Dungeon Guide seeded with Damage=2 read back as Damage=0
    // immediately after the very next "end turn" action, before combat ever ran) -- so instead of
    // pre-damaging a 3-Life ally, Wandering Glaivier (1 Life, from this same Pantheon pool) is
    // used as the kill target: Imperial Spy's printed 2 POWER is lethal on a single fresh hit with
    // no precondition needed. The defender's "Retaliate?" MZMAYCHOOSE (Wandering Glaivier, being
    // the target with positive POWER, is eligible) must be explicitly declined so
    // CombatApplyAttackerDamage actually lands and kills it, firing Imperial Spy's On Kill trigger.
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'l6gt7lh9v2'], // Imperial Spy
        ['player' => 2, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can attack
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'p6120p3f5d'], // Wandering Glaivier (1 Life) - kill target
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Imperial Spy
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''], // target Wandering Glaivier
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline Retaliate so CombatApplyAttackerDamage actually lands the kill
    ],
];

// --- Corhazi Infiltrator: Whenever you reveal it from memory, may put a copy from memory to field ---
$fixtures['corhazi-infiltrator-reveal-memory-to-field'] = [
    'testedCards' => ['VAFTR5taNG'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Corhazi Infiltrator's element is LUXEM; the starting champion's CardID is patched directly
    // to Zander, Blinding Steel (LUXEM, ASSASSIN) for both its own element requirement and the
    // Class Bonus condition (Element Bonus is unconditionally true -- IsElementBonusActive() is a
    // stubbed TODO returning true always, verified via GameLogic.php). Two copies of Corhazi
    // Infiltrator are seeded into memory; Uncover the Plot (targeting yourself) reveals all of
    // memory, firing the reveal-triggered ability once per copy revealed (QueueRevealTriggeredAbility
    // in GameLogic.php). Answering the resulting MZMayChoose moves the OTHER copy from memory onto
    // the field.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'UAF6Nr7GUE']], // Zander, Blinding Steel (LUXEM, ASSASSIN)
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'VAFTR5taNG'], // Corhazi Infiltrator copy #1
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'VAFTR5taNG'], // Corhazi Infiltrator copy #2
        ['player' => 1, 'zone' => 'myHand', 'cardID' => '4zkTRt8qXn'], // Uncover the Plot, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''], // target yourself
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMemory-0', 'chkInput' => [], 'inputText' => ''], // move a copy from memory to field
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline the second copy's own reveal-triggered offer
    ],
];

// --- Covert Manipulator: [Class Bonus] On Enter, reveal, optionally give opponent Crowd's Favor draw ---
$fixtures['covert-manipulator-enter-reveal-crowds-favor'] = [
    'testedCards' => ['A1jfgrWpiN'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Covert Manipulator's element is NORM; the starting champion's CardID is patched directly to
    // Zander, Deft Executor (ASSASSIN) for the Class Bonus condition.
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'fc4ic5fmaa']], // Zander, Deft Executor (ASSASSIN)
        ['player' => 1, 'zone' => 'myHand', 'cardID' => 'A1jfgrWpiN'], // Covert Manipulator, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''], // choose an opponent for Crowd's Favor
    ],
];

// --- Lurking Assailant: has stealth as long as it's awake ---
$fixtures['lurking-assailant-stealth-while-awake'] = [
    'testedCards' => ['uq2r6v374c'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Lurking Assailant's element is NORM, so no lineage patch is needed. Rule 1.h blocks the
    // game's first player from attacking on turn 1, so player 1 ends turn 1 and player 2 attacks
    // with a Dungeon Guide (1 POWER) on their own turn 1 instead. Lurking Assailant is seeded onto
    // player 1's field, awake (Status 2) -- its unconditional stealth-while-awake (HasStealth() in
    // GameLogic.php) is proven negatively: the attacker cannot target it directly (only the
    // champion is a legal target, since HasTrueSight is false), even though it would otherwise be
    // a legal attack target like any other awake unit.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'uq2r6v374c'], // Lurking Assailant (stealth while awake)
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (1 POWER) - actual attacker
        ['player' => 2, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can attack
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Dungeon Guide
        [
            'playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => '',
            'expectFailure' => true, 'semantic' => true, 'label' => 'Cannot target a stealthed, awake Lurking Assailant without True Sight',
        ],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // legal: target the champion instead
    ],
];

// --- Extraction Incision: True Sight; [Class Bonus] On Kill, put a preparation counter on your champion ---
$fixtures['extraction-incision-truesight-onkill-prepare'] = [
    'testedCards' => ['zthwm68lgo'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Extraction Incision's element is NORM. The starting champion's CardID is patched directly to
    // Zander, Deft Executor (ASSASSIN) for the Class Bonus condition. Rule 1.h blocks the game's
    // first player from attacking on turn 1, so player 1 ends turn 1 and player 2 plays Extraction
    // Incision on their own turn 1 instead. Wandering Glaivier (1 Life) is the kill target --
    // Extraction Incision's printed 3 POWER is lethal on a single fresh hit with no precondition
    // needed. The defender's "Retaliate?" MZMAYCHOOSE must be explicitly declined so
    // CombatApplyAttackerDamage actually lands and kills it, firing the Class Bonus On Kill
    // trigger.
    'setup' => [
        ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'fc4ic5fmaa']], // Zander, Deft Executor (ASSASSIN)
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'p6120p3f5d'], // Wandering Glaivier (1 Life) - kill target
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'zthwm68lgo'], // Extraction Incision, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-1', 'chkInput' => [], 'inputText' => ''], // target Wandering Glaivier
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline Retaliate so CombatApplyAttackerDamage actually lands the kill
    ],
];

// --- Corhazi Courier: Stealth; [Class Bonus] On Hit, draw+discard, deal 1 damage if fire discarded ---
$fixtures['corhazi-courier-onhit-draw-discard-damage'] = [
    'testedCards' => ['YqQsXwEvv5'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Corhazi Courier's [Class Bonus] On Hit ability calls IsClassBonusActive($player) with no
    // class filter, which is unconditionally true as long as any champion is on the field
    // (GameLogic.php: $requiredClasses stays null, so the check short-circuits true) -- no class
    // patch is needed. Rule 1.h blocks the game's first player from attacking on turn 1, so player
    // 1 ends turn 1 and player 2 attacks with Corhazi Courier on their own turn 1 instead. A second
    // copy of Corhazi Courier (FIRE element) is seeded into hand as discard fodder so the
    // "if a fire element card was discarded" branch is reachable, dealing 1 damage to the chosen
    // unit (the opponent's champion).
    'setup' => [
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'YqQsXwEvv5'], // Corhazi Courier
        ['player' => 2, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // awake, can attack
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'YqQsXwEvv5'], // second copy (FIRE), discard fodder, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myField-1!FSM!', 'chkInput' => [], 'inputText' => ''], // declare attack with Corhazi Courier
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-7', 'chkInput' => [], 'inputText' => ''], // discard the second (FIRE) copy
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // choose the opponent's champion to deal 1 damage to
    ],
];

// --- Scorching Imperilment: at each end phase, that player may discard a card to draw a card ---
$fixtures['scorching-imperilment-end-phase-discard-draw'] = [
    'testedCards' => ['aj7pz79wsp'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Scorching Imperilment's element is FIRE, matching the starting champion, so no lineage patch
    // is needed. It is seeded directly onto the field (materialize flow, including its own Class
    // Bonus cost discount, out of scope). Ending player 1's turn 1 (myHealth-0!CustomInput!Pass)
    // passes through player 1's own end phase, where the unconditional end-phase check in
    // GameLogic.php (~line 10817) queues the "discard a card to draw a card" MZMAYCHOOSE for the
    // turn player (player 1) as long as any Scorching Imperilment is on either player's field.
    'setup' => [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'aj7pz79wsp'], // Scorching Imperilment
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1, passing through end phase
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // discard a card
    ],
];

// --- Rending Flames: [Class Bonus] On Attack, may banish 3 fire cards from graveyard for double damage ---
$fixtures['rending-flames-onattack-banish-double-damage'] = [
    'testedCards' => ['soO3hjaVfN'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // Rending Flames's element is FIRE, but no Zander champion is itself FIRE (all are NORM or
    // LUXEM, verified via CardElement()), so patching the champion's CardID alone to an ASSASSIN
    // Zander champion for the Class Bonus condition would lose FIRE access entirely (verified
    // live: with CardID patched to Zander, Deft Executor alone, the FSM activation silently
    // no-oped). GetChampionLineage() (GameLogic.php) returns [CardID] merged with Subcards, so the
    // champion's CardID is patched to Zander, Deft Executor (ASSASSIN) for Class Bonus AND its
    // Subcards are separately patched to include Corhazi Courier (FIRE) for element access -- both
    // conditions are independent properties on the same object. Rule 1.h blocks the game's first
    // player from attacking on turn 1, so player 1 ends turn 1 and player 2 plays Rending Flames on
    // their own turn 1 instead. Three copies of Corhazi Courier (FIRE element) are seeded directly
    // into player 2's graveyard to satisfy the "banish three fire element cards from your
    // graveyard" cost. Rending Flames's printed POWER is 3; doubled damage (6) on the champion is
    // only possible if the ability's "deals double that damage instead" effect actually applied.
    'setup' => [
        ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'fc4ic5fmaa', 'Subcards' => ['YqQsXwEvv5']]], // Zander, Deft Executor (ASSASSIN) + FIRE lineage/element unlock
        ['player' => 2, 'zone' => 'myGraveyard', 'cardID' => 'YqQsXwEvv5'], // Corhazi Courier (FIRE) #1
        ['player' => 2, 'zone' => 'myGraveyard', 'cardID' => 'YqQsXwEvv5'], // Corhazi Courier (FIRE) #2
        ['player' => 2, 'zone' => 'myGraveyard', 'cardID' => 'YqQsXwEvv5'], // Corhazi Courier (FIRE) #3
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'soO3hjaVfN'], // Rending Flames, seeded to a known hand slot
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1 (first-player attack lock)
        ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''],
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'theirField-0', 'chkInput' => [], 'inputText' => ''], // target opponent's champion
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'YES', 'chkInput' => [], 'inputText' => ''], // banish 3 fire cards for double damage
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myGraveyard-0&myGraveyard-1&myGraveyard-2', 'chkInput' => [], 'inputText' => ''], // select all 3 Corhazi Couriers
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline Retaliate so CombatApplyAttackerDamage actually lands
    ],
];

// --- Zander, Blinding Steel: at your recollection phase, reveal memory; opponent puts hand cards into memory per luxem revealed ---
$fixtures['zander-blinding-steel-recollection-luxem-memory'] = [
    'testedCards' => ['UAF6Nr7GUE'],
    'deck' => <<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
4 Windslice
DECK,
    // The starting champion's CardID is patched directly to Zander, Blinding Steel so
    // ChampionHasInLineage($turnPlayer, "UAF6Nr7GUE") is true (GameLogic.php ~line 9962) --
    // testing only the passive recollection-phase trigger, not the real level-up flow. A LUXEM
    // card is seeded directly into player 1's own memory as the revealed card -- specifically
    // Corhazi Infiltrator (an ALLY), not a REGALIA card like Insignia of the Corhazi: AddMemory()'s
    // MemoryAddReplacement() hook (GameLogic.php) silently redirects any REGALIA card added to
    // memory into the Material zone instead (a real GA rule -- Regalia can't sit in memory), so a
    // REGALIA seed here would silently land in Material and never be revealed (verified live: the
    // seeded Insignia ended up counted in myMaterial, and GetMemory(1) read back empty). Both
    // players end their first two turns (P1 -> P2) to reach player 1's OWN next turn, whose
    // recollection phase reveals memory and, for each luxem card revealed (1 here), makes the
    // opponent (player 2) put a card from their hand into their memory
    // (ZanderBlindingSteelStep/ZanderBlindingSteelMemory in CardDQHandlers.php).
    'setup' => [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'UAF6Nr7GUE']], // Zander, Blinding Steel lineage
        ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'VAFTR5taNG'], // Corhazi Infiltrator (LUXEM ALLY, not REGALIA) - revealed card
    ],
    'actions' => [
        ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 1
        ['playerID' => 2, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // ends turn 2, reaching player 1's recollection phase
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // decline the material-phase champion swap offer
        ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // opponent puts a hand card into memory
        ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => '-', 'chkInput' => [], 'inputText' => ''], // decline Corhazi Infiltrator's own reveal-triggered offer (unrelated to Zander)
    ],
];

// ---------------------------------------------------------------------------
// Filter if --fixture specified
// ---------------------------------------------------------------------------
if ($onlyFixture) {
    if (!isset($fixtures[$onlyFixture])) {
        echo "Unknown fixture: $onlyFixture\n";
        echo "Available: " . implode(', ', array_keys($fixtures)) . "\n";
        exit(1);
    }
    $fixtures = [$onlyFixture => $fixtures[$onlyFixture]];
}

// ---------------------------------------------------------------------------
// Create each fixture
// ---------------------------------------------------------------------------
$created = 0;
$failed = 0;

foreach ($fixtures as $slug => $def) {
    echo "\n=== $slug ===\n";

    $fixtureDir = $repoRoot . '/Tests/Integration/' . $rootName . '/' . $slug;

    if ($dryRun) {
        echo "[DRY-RUN] Would create $fixtureDir\n";
        echo "  Deck: " . substr_count($def['deck'], "\n") . " lines\n";
        echo "  Actions: " . count($def['actions']) . "\n";
        echo "  Tested cards: " . implode(', ', $def['testedCards']) . "\n";
        $created++;
        continue;
    }

    // Clean existing fixture
    if (is_dir($fixtureDir)) {
        RegressionDeleteDirRecursive($fixtureDir);
    }
    RegressionEnsureDir($fixtureDir);

    $gameName = 'prd_batch_' . $slug . '_' . $seed;
    $gameDir = $repoRoot . '/' . $rootName . '/Games/' . $gameName;

    try {
        // 1. Initialize game
        echo "  Initializing game (seed=$seed)...\n";
        RegressionEnsureDir($gameDir);
        RegressionEnsureDir($repoRoot . '/' . $rootName . '/Games/' . $gameName);
        EngineLoadRootRuntime($rootName);
        $GLOBALS['gameName'] = $gameName;
        InitializeGamestate();
        SetDeterministicRandomCounter($seed);
        WriteGamestate('./');
        ParseGamestate('./');
        SetDeterministicRandomCounter($seed);

        // 2. Load decks
        $GLOBALS['bridgeDeterministicDeckShuffle'] = true;
        $GLOBALS['bridgeDeterministicDeckShuffleSeed'] = $seed;

        $deckSummary = [];
        BridgeLoadDeckForPlayer($rootName, 1, $def['deck'], $deckSummary);
        BridgeLoadDeckForPlayer($rootName, 2, $def['deck'], $deckSummary);
        echo "  P1 deck: " . ($deckSummary['mainDeckCount'] ?? '?') . " main, " . ($deckSummary['materialCount'] ?? '?') . " material\n";

        // 3. Set first player and turn
        $firstPlayer = &GetFirstPlayer();
        $firstPlayer = 1;
        $turnPlayer = &GetTurnPlayer();
        $turnPlayer = 1;
        $currentTurn = &GetTurnNumber();
        $currentTurn = 1;

        // 4. Run pregame startup
        BridgeRunRootSelfplayStartup($rootName);
        RegressionFlushCurrentGamestate($rootName);

        // 4a. Resolve the pregame starting-champion choice for both players. This is queued as
        // an MZCHOOSE expecting a real myMaterial-N mzID (see QueuePregameStartingChampionChoice
        // in GrandArchiveSim/Custom/GameLogic.php) — every fixture deck here has exactly one Lv 0
        // champion in its Material section ("Spirit of Fire"), so myMaterial-0 is always the
        // (only) legal choice for both players. Submitting "NO"/"PASS" here instead silently
        // no-ops via the PREGAME_CHOOSE_STARTING_CHAMPION early-return, leaving hands undealt
        // and every subsequent action operating on an empty board.
        foreach ([1, 2] as $pregamePlayer) {
            $pregameAction = ['playerID' => $pregamePlayer, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myMaterial-0', 'chkInput' => [], 'inputText' => ''];
            $pregameResult = EngineRunAction($pregameAction, $rootName, $gameName, [
                'updateCache' => false,
                'disableRecording' => true,
            ]);
            if (!$pregameResult['success']) {
                throw new \RuntimeException("Pregame starting-champion choice failed for player $pregamePlayer: " . ($pregameResult['message'] ?? 'unknown'));
            }
        }
        echo "  Resolved pregame starting champion for both players\n";

        // 4b. Apply any test-setup preconditions (e.g. seeding a graveyard/field card directly
        // via the same BridgeAddToZone primitive the MCP fixture tooling uses) before the
        // initial gamestate is captured, so the fixture's actions.json only has to replay the
        // actual ability activation, not an artificial way of reaching the precondition.
        if (!empty($def['setup'])) {
            foreach ($def['setup'] as $setupStep) {
                // 'globalEffect': directly set a per-player global effect flag that's normally
                // only reachable by playing out a real game event (e.g. CHAMP_DEALT_COMBAT_DMG,
                // set by TrackChampionCombatDamage() when a champion deals combat damage) — used
                // to reach a cost-discount condition without scripting a full combat sequence.
                if (isset($setupStep['globalEffect'])) {
                    AddGlobalEffects($setupStep['player'] ?? 1, $setupStep['globalEffect']);
                    WriteGamestate('./' . $rootName . '/');
                    echo "  Setup: AddGlobalEffects(player={$setupStep['player']}, {$setupStep['globalEffect']})\n";
                    continue;
                }
                // 'patchMzId': directly mutate an already-on-field object (e.g. the starting
                // champion's Subcards) rather than a freshly-seeded one. Used to grant access to
                // an advanced element (CanPlayerMeetCardElementRequirements reads
                // GetChampionLineage(), which walks $obj->Subcards on the champion already on the
                // field) without scripting a real level-up sequence.
                if (isset($setupStep['patchMzId'])) {
                    EngineLoadRootRuntime($rootName);
                    ParseGamestate('./' . $rootName . '/');
                    $GLOBALS['playerID'] = $setupStep['player'] ?? 1;
                    $patchObj = GetZoneObject($setupStep['patchMzId']);
                    if ($patchObj !== null) {
                        foreach ($setupStep['setProperties'] as $propName => $propValue) {
                            $patchObj->$propName = $propValue;
                        }
                        WriteGamestate('./' . $rootName . '/');
                        echo "  Setup: patched {$setupStep['patchMzId']} with " . json_encode($setupStep['setProperties']) . "\n";
                    }
                    continue;
                }
                // 'markPreserved': directly mark card IDs as "preserved" via the
                // DynamicPreserveCardIDs decision-queue variable that
                // GetPreservedMaterialChoices()/HydrateDynamicPreserveCards() read
                // (GrandArchiveSim/Custom/GameLogic.php) — normally only reachable via a real
                // "preserve" effect (e.g. PREVENT_CHAMP_TERA_PRESERVE). Used together with seeding
                // cards into myMaterial to satisfy "banish N preserved cards from your material
                // deck" additional materialize costs (e.g. Vernal Talisman) without scripting the
                // real preserve trigger.
                if (isset($setupStep['markPreserved'])) {
                    EngineLoadRootRuntime($rootName);
                    ParseGamestate('./' . $rootName . '/');
                    $GLOBALS['playerID'] = $setupStep['player'] ?? 1;
                    SetDynamicPreserveCardIDs(array_fill_keys($setupStep['markPreserved'], true));
                    WriteGamestate('./' . $rootName . '/');
                    echo "  Setup: markPreserved " . implode(',', $setupStep['markPreserved']) . "\n";
                    continue;
                }
                // 'dqVariables': directly store arbitrary DecisionQueueController variables (e.g.
                // CombatAttacker/CombatAttackerPlayer/CombatTarget, normally only set mid-combat by
                // CombatLogic.php) so an ability that reads combat state (e.g. Samaritan's Reach's
                // GetCombatAttackerMZ()) can be exercised without scripting a full attack sequence.
                if (isset($setupStep['dqVariables'])) {
                    EngineLoadRootRuntime($rootName);
                    ParseGamestate('./' . $rootName . '/');
                    $GLOBALS['playerID'] = $setupStep['player'] ?? 1;
                    foreach ($setupStep['dqVariables'] as $varName => $varValue) {
                        DecisionQueueController::StoreVariable($varName, $varValue);
                    }
                    WriteGamestate('./' . $rootName . '/');
                    echo "  Setup: dqVariables " . json_encode($setupStep['dqVariables']) . "\n";
                    continue;
                }
                $setupResult = BridgeAddToZone(
                    $rootName,
                    $gameName,
                    $setupStep['zone'],
                    $setupStep['cardID'],
                    $setupStep['player'] ?? 1
                );
                echo "  Setup: added {$setupStep['cardID']} to {$setupStep['zone']} (player {$setupStep['player']}) -> {$setupResult['mzID']}\n";
                // Optional: directly set a property (e.g. Damage) on the freshly-seeded object —
                // used to construct otherwise-unreachable-at-game-start preconditions like "an
                // already-damaged ally on the field".
                if (!empty($setupStep['setProperties'])) {
                    EngineLoadRootRuntime($rootName);
                    ParseGamestate('./' . $rootName . '/');
                    $GLOBALS['playerID'] = $setupStep['player'] ?? 1;
                    $seededObj = GetZoneObject($setupResult['mzID']);
                    if ($seededObj !== null) {
                        foreach ($setupStep['setProperties'] as $propName => $propValue) {
                            $seededObj->$propName = $propValue;
                        }
                        WriteGamestate('./' . $rootName . '/');
                        echo "  Setup: set " . json_encode($setupStep['setProperties']) . " on {$setupResult['mzID']}\n";
                    }
                }
            }
        }

        // 5. Save initial gamestate
        RegressionFlushCurrentGamestate($rootName);
        $initialGamestate = RegressionCurrentGamestateText($rootName, $gameName);
        file_put_contents($fixtureDir . '/initial_gamestate.txt', $initialGamestate);
        echo "  Saved initial gamestate\n";

        // 6. Replay actions
        $replayedActions = [];
        foreach ($def['actions'] as $i => $action) {
            $result = EngineRunAction($action, $rootName, $gameName, [
                'updateCache' => false,
                'disableRecording' => true,
            ]);

            if (!$result['success']) {
                echo "  [WARN] Action $i failed: " . ($result['message'] ?? 'unknown') . "\n";
                echo "  Action: " . json_encode($action) . "\n";
                // Continue anyway - some actions may not be legal in all seeds
                break;
            }
            $replayedActions[] = $action;
            echo "  Action $i OK (mode={$action['mode']}, card={$action['cardID']})\n";
        }

        // 7. Save fixture files
        file_put_contents(
            $fixtureDir . '/actions.json',
            json_encode($replayedActions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        file_put_contents(
            $fixtureDir . '/assertions.json',
            json_encode([], JSON_PRETTY_PRINT)
        );

        file_put_contents(
            $fixtureDir . '/meta.json',
            json_encode([
                'name' => $slug,
                'rootName' => $rootName,
                'createdAt' => date('c'),
                'createdBy' => 'batch-script',
                'testedCards' => $def['testedCards'],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // 8. Save expected final gamestate
        RegressionFlushCurrentGamestate($rootName);
        $finalGamestate = RegressionCurrentGamestateText($rootName, $gameName);
        $normalized = RegressionNormalizeGamestateTextForComparison($rootName, $finalGamestate);
        file_put_contents($fixtureDir . '/expected_final_gamestate.txt', $normalized);
        echo "  Saved expected final gamestate\n";

        // 9. Verify by replaying
        echo "  Verifying fixture...\n";
        $verifyResult = shell_exec(
            "cd {$repoRoot} && php DevTools/RunIntegrationTests.php --root={$rootName} --test={$slug} 2>&1"
        );
        if (strpos($verifyResult, '[PASS]') !== false) {
            echo "  [OK] Fixture passes verification\n";
            $created++;
        } else {
            echo "  [FAIL] Fixture failed verification\n";
            echo "  " . trim(substr($verifyResult, strrpos($verifyResult, "\n") + 1)) . "\n";
            $failed++;
        }

        // Cleanup temp game
        if (is_dir($gameDir)) {
            RegressionDeleteDirRecursive($gameDir);
        }

    } catch (\Throwable $e) {
        echo "  [ERROR] " . $e->getMessage() . "\n";
        echo "  " . $e->getTraceAsString() . "\n";
        $failed++;
        // Cleanup
        if (is_dir($gameDir)) {
            RegressionDeleteDirRecursive($gameDir);
        }
    }
}

echo "\n=== Summary ===\n";
echo "Created: $created | Failed: $failed | Total: " . count($fixtures) . "\n";
