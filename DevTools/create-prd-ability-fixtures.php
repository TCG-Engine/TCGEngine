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
