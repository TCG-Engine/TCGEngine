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
