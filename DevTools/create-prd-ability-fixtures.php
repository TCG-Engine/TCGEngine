<?php
/**
 * Batch-create PRD regression fixtures programmatically.
 *
 * Usage: php DevTools/create-prd-ability-fixtures.php [--seed=N] [--dry-run] [--fixture=SLUG] [--families]
 *
 * Creates selfplay games from curated decks, replays actions to exercise
 * specific card abilities, and saves regression fixtures.
 */
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

$repoRoot = dirname(__DIR__);
$rootName = 'GrandArchiveSim';
chdir($repoRoot);
$seed = 42;
$dryRun = false;
$onlyFixture = null;
$resetMeta = false;
$auditActions = false;
$familiesOnly = in_array('--families', $argv, true);

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--seed=')) $seed = intval(substr($arg, 7));
    elseif ($arg === '--dry-run') $dryRun = true;
    elseif ($arg === '--reset-meta') $resetMeta = true;
    elseif ($arg === '--audit-actions') $auditActions = true;
    elseif (str_starts_with($arg, '--fixture=')) $onlyFixture = substr($arg, 10);
}
// Regenerating a fixture used to wipe its directory, including the hand-written assertions.json and the meta.json notes / semanticCoverage / engineBug records. They are now carried over unless
// --reset-meta is passed: the snapshot (initial/expected gamestate, actions.json) is always rebuilt from the definition below, and the verification run at the end tells you when the kept
// assertions no longer match the rebuilt fixture.

require_once $repoRoot . '/Core/EngineActionRunner.php';
define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
require_once $repoRoot . '/DevTools/TestAutomationBridge.php';

// ---------------------------------------------------------------------------
// Fixture definitions: slug => [deck, actions, testedCards]
// ---------------------------------------------------------------------------
$fixtures = [];


// (The old 'recruitment-officer-class-bonus-on-foster-look-top-5' fixture was replaced by the deterministic
// 'recruitment-officer-foster-look-five-*' fixtures below: it relied on shuffle seed 1 putting an ally on top and so pinned the
// single-card look bug.)

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

// =============================================================================
// Zander Pantheon Starter deck semantic fixtures
// =============================================================================

// ===========================================================================
// Lorraine Pantheon Starter deck: semantic coverage fixtures
// ===========================================================================
const GA_LORRAINE_PANTHEON_DECK = <<<'DECK'
# Material
1 Spirit of Wind
1 Lorraine, Wandering Warrior
1 Lorraine, Blademaster
1 Lorraine, Spirit Ruler
1 Charm of Anticipation
1 Drawn Blade
1 Equinox Hour
1 Safeguard Amulet
1 Sword of Seeking
1 Tariff Ring
1 Prismatic Edge
1 Quietus Blade
# Main
4 Dungeon Guide
4 Fairy Whispers
4 Fluffy Shopkeep
DECK;

// ===========================================================================
// Kongming Pantheon Starter deck: semantic coverage fixtures
// ===========================================================================
const GA_KONGMING_BASE_DECK = <<<'DECK'
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
DECK;

// NOTE: Sanctified Paladin (ioLmt0S7op, printed "Foster") is intentionally NOT covered.
// HasFoster() (GrandArchiveSim/Custom/CardLogic.php ~188) hardcodes the small set of CardIDs that
// actually receive Foster processing at recollection (unconditional list plus a [Class Bonus]
// list) -- Sanctified Paladin's CardID (ioLmt0S7op) is present in neither, so it can never become
// fostered, its own +3/+3/vigor-while-fostered clause never applies, and its On Foster "draw two
// cards" trigger never fires (confirmed by static inspection of the whitelist; consistent with
// the sibling Pantheon-deck task's earlier finding). This is the same class of hardcoded-whitelist
// gap already flagged there as a real, separately-tracked engine bug (out of scope to fix here).

// ---------------------------------------------------------------------------
// Arisanna Pantheon Starter: remaining semantic-coverage fixtures
// ---------------------------------------------------------------------------
const GA_ARISANNA_BASE_DECK = <<<'DECK'
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
DECK;






// NOTE: An additional fixture proving [Diao Chan Bonus]'s "activated from memory" draw was
// attempted (activating Shimmering Refraction directly via mode 10002 FSM on "myMemory-0!FSM!")
// but the click silently no-ops in this engine -- no state changes at all, no error. The only
// documented memory-activation path found (TryGlimmerCast, Custom/GameLogic.php ~line 20180)
// requires spending glimmer counters via a champion's Inherited Effect and is architecturally
// unrelated to Shimmering Refraction. Left uncovered rather than shipping a fixture that would
// silently pass without proving anything; see the final report for details.


// ---------------------------------------------------------------------------
// Diao Chan Re:Collection, Idyll Corsage -- wither-counter mechanic fixtures
// ---------------------------------------------------------------------------
// Shared deck for the wither-counter batch: Spirit of Water starting champion
// (patched per-fixture to Diao Chan/Diao Chan Idyll Corsage for [Diao Chan Bonus]/
// [Class Bonus] CLERIC checks and TERA element access), plus non-FAST-speed filler
// (Dungeon Guide / Fluffy Shopkeep -- Fairy Whispers is FAST speed and would trigger
// a recurring Opportunity prompt that must be declined before every action).
const GA_DIAOCHAN_WITHER_DECK = <<<'DECK'
# Material
1 Spirit of Water
1 Diao Chan, Enchantress
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK;


// =============================================================================
// Diao Chan Re:Collection, Idyll Corsage starter deck semantic fixtures (batch 5)
// =============================================================================
//
// Shared minimal deck for all fixtures below: Diao Chan, Enchantress (00xbh8oc00,
// CLERIC, NORM) as the starting champion via the Material section (same convention as
// "1 Lorraine, Wandering Warrior" elsewhere in this file), Spirit of Water for theme,
// plus non-FAST-speed-only filler (Dungeon Guide, Fluffy Shopkeep -- Fairy Whispers is
// avoided because its FAST speed triggers a recurring Opportunity-window MZMAYCHOOSE
// that would have to be declined before every single subsequent action). Diao Chan,
// Enchantress is CLERIC, so IsClassBonusActive($player, ["CLERIC"]) is true for all five
// of these CLERIC cards without any extra setup.
$diaoChanDeck = <<<'DECK'
# Material
1 Spirit of Water
1 Diao Chan, Enchantress
1 Backup Charger
1 Purifying Thurible
# Main
6 Dungeon Guide
6 Fluffy Shopkeep
DECK;

// NOTE on the negative (negate+banish) path for Acquiescing Rejection: intentionally NOT
// implemented as a fixture. cardActivatedAbilities["qwtprd5b5r:0"] always defers to
// customDQHandlers["NegateActivationDrawChoice"] (OpportunityLogic.php ~538), which only
// stores pendingNegateTarget and queues a YESNO for the target's controller -- it never calls
// NegateCardActivation synchronously (unlike NegateActivationResolve's payAmount<=0 shortcut).
// Genuine engine bug found (not fixed -- a happy-path fixture already exercises this card, so
// fixing was not required to exercise it): customDQHandlers["PostResolutionCheck"]
// (OpportunityLogic.php ~1212), running synchronously right after Acquiescing Rejection's own
// resolution in the SAME action-processing chain, sees the target (e.g. Escharotomy) still live
// on the EffectStack (untouched by the deferred choice) and immediately calls
// ResolveTopOfEffectStack() (~1520) to resolve it normally -- this happens before the controller's
// YES/NO answer can possibly be submitted as a separate action. By the time
// customDQHandlers["NegateActivationDrawResolve"] (~548) later runs (on a NO answer) and calls
// NegateCardActivation($negatingPlayer, $targetMZ, "banish"), GetZoneObject($targetMZ) already
// points at an entry ResolveTopOfEffectStack() marked removed, so NegateCardActivation's own
// null/removed guard makes the call a no-op -- the target always resolves normally and is never
// actually negated or banished, regardless of the controller's answer. Confirmed by direct replay
// (dump-zones2.php-style trace): Escharotomy landed in Graveyard (not Banish) and its own "Target
// player cannot recover" ability's MZMODAL was already queued before the YES/NO was even
// answered. The same race affects the deferred (afford-to-pay) branch of the default
// NegateActivationResolve handler used by Glimmering Refusal / Blossoming Denial -- their
// fixtures below instead exercise the handler's payAmount-insufficient-funds branch, which DOES
// call NegateCardActivation synchronously (avoiding the race) and is a fully legitimate real-world
// "they couldn't pay" proof of the same "negate unless pay X" clause.




// =============================================================================
// Diao Chan Re:Collection, Idyll Corsage -- batch 5 (Claude Sonnet 5)
// Shared setup notes: the Material section is just "1 Spirit of Water"
// (tafqldAGRF, a Level 0 CHAMPION), which the pregame auto-places at myField-0
// as the sole legal starting-champion choice -- this alone grants natural WATER
// element access (GetPlayerEnabledElements() walks GetChampionLineage(), which
// starts from myField-0's own CardID). When a fixture needs TERA element access
// and/or the "[Diao Chan Bonus]" check (IsDiaoChanBonus() -> ChampionHasInLineage
// for any of the 3 Diao Chan card IDs), myField-0's Subcards are patched to add
// Diao Chan, Idyll Corsage (d7l6i5thdy, TERA element, satisfies IsDiaoChanBonus)
// -- GetChampionLineage() merges Subcards into the lineage array without ever
// making that card the physical on-field object, so its own "Whenever a
// non-token object an opponent controls is destroyed..." passive (keyed on
// literal on-field CardID === "d7l6i5thdy", GrandArchiveSim/Custom/GameLogic.php
// ~7985) never fires. When a fixture needs "[Class Bonus]" (IsClassBonusActive,
// which scans literal on-field CHAMPION objects for a class match -- independent
// of the Subcards-based lineage check), Diao Chan, Enchantress (00xbh8oc00,
// CLERIC) is physically seeded onto the field as a second CHAMPION object,
// exactly like the vernal-talisman-preserve-draw precedent.
// =============================================================================


// --- Guo Jia Re:Collection, Heaven's Favored starter deck semantic coverage ---

// =============================================================================
// Silvie Re:Collection, Slime Sovereign starter-deck semantic coverage batch
// =============================================================================

// NOTE on Baby Blue Slime (9ggfiy38t2, REST: Prevent the next 2 damage to another target Slime
// ally): this card used to be SKIPPED here because its REST ability was registered in
// $cardActivatedAbilities["9ggfiy38t2:0"] (the play-effect / reserve-cost dictionary) with a 0
// $CardActivateAbilityCountData row instead of $activateAbilityAbilities, so playing it auto-fired the
// target prompt without paying REST and the ability could never be activated from the field (field
// clicks were routed into DoActivateCard(), which treats the on-field ally as a card being played).
// Fixed (real $activateAbilityAbilities/$activateAbilityPrereqs entries, ability count/name rows, and the
// misfiled play-table body neutralized -- see Custom/GameLogic.php, Custom/GeneratedAbilityOverrides.php);
// its full semantic coverage now lives in the baby-blue-slime-* fixtures further below (defined next to
// the key-slime-pudding-* fixtures, the other card with the same categorization defect).

// --- Key Slime Pudding: Banish CARDNAME: Slime allies entering under your control this turn get an additional buff counter ---
// Key Slime Pudding (4wuq20gvcg, REGALIA/ITEM, memory 0): "Banish CARDNAME: Until end of turn, Slime
// allies that enter the field under your control enter with an additional buff counter on them."
// History: the generator registered this FIELD ability in $cardActivatedAbilities (the play-effect
// dictionary) with a 0 $CardActivateAbilityCountData row, so GetPlayableFastAbilities() never offered it
// and a field click silently no-opped (the former fixture key-slime-pudding-banish-ability-never-fires
// pinned that). It is now a real $activateAbilityAbilities["4wuq20gvcg:0"] ability whose Banish cost is
// paid by ActivatedAbilityCost(). In every fixture below Key Slime Pudding is MATERIALIZED FOR REAL from
// the material deck at the start of player 1's turn 3 (the seed only adds it to myMaterial), the Slime
// allies are played from hand with real FSM plays + reserve payments, and the ability is activated with
// real actions (the BREC opportunity window listing, or the main-phase Activate button).
$kspDeck = <<<'DECK'
# Material
1 Spirit of Fire
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Fluffy Shopkeep
4 Dungeon Guide
4 Fluffy Shopkeep
DECK;

// --- Baby Blue Slime: [Class Bonus] +1 LIFE; [REST]: Prevent the next 2 damage that would be dealt to another target Slime ally you control this turn ---
// Baby Blue Slime (9ggfiy38t2, WATER ALLY, TAMER/ANIMAL/SLIME, reserve 2, POWER 1 / LIFE 2): "[Class
// Bonus] CARDNAME gets +1 LIFE. [REST]: Prevent the next 2 damage that would be dealt to another target
// Slime ally you control this turn."
// History: the generator registered the REST body in $cardActivatedAbilities (the play-effect
// dictionary) with a 0 ability count, so playing the ally queued a free target prompt on enter (no REST
// paid) and the real REST ability could never be activated afterwards (the NOTE that used to stand here
// SKIPPED the card for that reason). It is now a real $activateAbilityAbilities["9ggfiy38t2:0"] with a
// prereq (ready + another Slime ally) and the ally auto-rest of DoActivatedAbility() as the [REST] cost.
// Every fixture below plays Baby Blue Slime for real from hand (WATER unlocked by patching Nico into the
// champion lineage; the colorless deck keeps fast-spell windows out of the flow). Seeded cards are only
// the targets/attackers/preconditions.

// --- Tristan Re:Collection, Shadowdancer starter deck: shared Material/Main skeleton ---
$tristanDeck = <<<'DECK'
# Material
1 Spirit of Wind
1 Tristan, Underhanded
1 Tristan, Hired Blade
1 Tristan, Shadowdancer
1 Gearstride Gloves
1 Dusksoul Stone
1 Malignant Athame
1 Shadeblood Coating
# Main
9 Dungeon Guide
9 Fluffy Shopkeep
DECK;

// --- Stifling Trap: [Class Bonus] alternate cost -- activate from memory by removing two preparation counters instead of paying reserve ---
// Stifling Trap (z5exbwdp7q): "[Class Bonus] If it's not your turn, you may remove two preparation
// counters from your champion to activate this card from your memory without paying its reserve
// cost." Previously never offered (the buggy pin was stifling-trap-memory-alternate-cost-never-offered).
// GetPlayableFastCards() now lists a memory Stifling Trap as a priority option when it is NOT the
// player's turn, the class bonus is active (the champion is an ASSASSIN) and the champion has >= 2
// preparation counters; TryStiflingTrapMemory() (Custom/OpportunityLogic.php) removes the counters,
// moves the card to hand and activates it with ignoreCost (activation source zone forced to memory).
// The alternate cost only waives the RESERVE cost (element, lockdown etc. still apply).
// Shared opening for the Stifling Trap memory fixtures: player 1 ends its turn, player 2 plays a
// Dungeon Guide on its own turn (3 reserve payments) which opens a priority window for player 1.
$stiflingTrapOpening = [
    ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // end player 1 turn
];
$stiflingTrapOpponentPlaysDungeonGuide = [
    ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-0!FSM!', 'chkInput' => [], 'inputText' => ''], // player 2 plays Dungeon Guide (opens a window for player 1)
    ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // reserve 1/3
    ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // reserve 2/3
    ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // reserve 3/3
];
$stiflingTrapTristan2Prep = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => 2]]]; // Tristan, Hired Blade (ASSASSIN) with 2 preparation counters (the alternate cost)
$stiflingTrapSeedTrapInMemory = ['player' => 1, 'zone' => 'myMemory', 'cardID' => 'z5exbwdp7q']; // Stifling Trap in MEMORY -> p1 myMemory-0
$stiflingTrapSeedTargetAlly = ['player' => 2, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y']; // Dungeon Guide, the Stifling Trap target -> p2 field-1
$stiflingTrapSeedFairyWhispers = ['player' => 1, 'zone' => 'myHand', 'cardID' => 'n8wyfG9hbY']; // Fairy Whispers (fast) keeps a priority window open at player 1 -> p1 myHand-7
// With a fast card in hand player 1 gets windows at its own end of turn: end turn, then pass the two windows.
$stiflingTrapOpeningWithFastCard = [
    ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // end player 1 turn
    ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // p1 passes a window
    ['playerID' => 1, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'PASS', 'chkInput' => [], 'inputText' => ''], // p1 passes a window
];

// --- Shadowstrike: Prepare X (optional, X >= 1), +X POWER, [Class Bonus] prepared => unblockable ---
// Shadowstrike (o191zv86la, UMBRA ATTACK, reserve 2, printed power 4): "Prepare X. X can't be 0.
// Shadowstrike gets +X POWER. [Class Bonus] If Shadowstrike was prepared, it has unblockable."
// Player 2's champion is Tristan, Shadowdancer (ASSASSIN, UMBRA); Player 1 controls a Swift
// Recruit (Intercept ally) so unblockability is observable (an interceptor prompt is queued for
// player 1 exactly when the attack is blockable). Shadowstrike is always played for real (two
// reserve payments) and attacks player 1's champion.
// Engine bug fixed here (was pinned as 'shadowstrike-prepare-x-never-wired-base-power-only'): the
// generated ability set had only onAttackAbilities['o191zv86la:0'] and no
// $cardActivatedAbilities['o191zv86la:0'], so no Prepare prompt was ever queued, the attack always
// dealt only its printed 4 and never consumed a preparation counter, and the unblockable branch was
// unreachable. Now Custom/GeneratedAbilityOverrides.php queues YES/NO "Pay Prepare X?" and then a
// NUMBERCHOOSE for X (1..counters), removes X counters and adds the o191zv86la_POWER_<X> effect.
$shadowstrikeActions = [
    ['playerID' => 1, 'mode' => 10001, 'buttonInput' => '', 'cardID' => 'myHealth-0!CustomInput!Pass', 'chkInput' => [], 'inputText' => ''], // end player 1 turn 1
    ['playerID' => 2, 'mode' => 10002, 'buttonInput' => '', 'cardID' => 'myHand-7!FSM!', 'chkInput' => [], 'inputText' => ''], // player 2 plays Shadowstrike
    ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // reserve payment 1/2
    ['playerID' => 2, 'mode' => 100, 'buttonInput' => '', 'cardID' => 'myHand-0', 'chkInput' => [], 'inputText' => ''], // reserve payment 2/2
];
$shadowstrikeSetup = function(int $counters) {
    return [
        ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], 'Counters' => ['preparation' => $counters]]], // Tristan, Shadowdancer (ASSASSIN, UMBRA) with $counters preparation counters
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'o191zv86la'], // Shadowstrike -> p2 myHand-7
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'mHd6LLyMyF'], // Swift Recruit (Intercept ally) -> p1 myField-1
    ];
};
// --- Mordred Re:Collection, Aurelian Regent starter deck: shared Material/Main skeleton ---

// Compact action builders for the Mordred Re:Collection fixtures (each returns one action row).
function mrdAct($p, $mode, $card, $extra = []) {
    return array_merge(['playerID' => $p, 'mode' => $mode, 'buttonInput' => '', 'cardID' => $card, 'chkInput' => [], 'inputText' => ''], $extra);
}
function mrdPlay($p, $card) { return mrdAct($p, 10002, $card . '!FSM!'); }       // FSM click: play a card / start an attack / materialize
function mrdAns($p, $card, $extra = []) { return mrdAct($p, 100, $card, $extra); } // answer the pending decision
function mrdEnd($p) { return mrdAct($p, 10001, 'myHealth-0!CustomInput!Pass'); }  // pass the turn
function mrdPass($p) { return mrdAns($p, 'PASS'); }
function mrdPay($p, $n) { $rows = []; for ($i = 0; $i < $n; ++$i) $rows[] = mrdAns($p, 'myHand-0'); return $rows; } // n reserve payments (each banishes the first hand card into memory)

$mordredDeck = <<<'DECK'
# Material
1 Spirit of Water
1 Mordred, Burnished Avenger
1 Mordred, Flawless Blade
1 Mordred, Fated Luminary
1 Mordred, Aurelian Regent
1 Mirrordepth's Blade
1 Dummy Trainer
1 Rhongomiant, Grove's Spire
# Main
9 Dungeon Guide
9 Fluffy Shopkeep
DECK;

// ===========================================================================
// Hand activation that cannot begin or be paid for is refused BEFORE anything moves
// ===========================================================================
// ENGINE BUG (fixed, same class as the banishment fix 67eceea9): clicking a hand card whose reserve
// cost the player cannot pay was never gated by affordability. DoActivateCard() moved the card onto
// the effect stack and queued one payment prompt per reserve point; once the hand ran out of
// payers the decision queue emptied and the card sat on the effect stack forever (never resolving,
// never reaching the graveyard). Mandatory additional costs whose own check lived AFTER the card was
// announced (Memory Invocation, Broken Promises, Devotion's Price, ...) stranded the card the same way
// when refused. The hand click now refuses first: card still in hand, hand/memory unchanged, nothing
// on the effect stack, no payment prompt, no undo snapshot overwritten, flash message gives the reason.
// Reserve payment sources are the hand (minus the activated card) plus ready reservable field objects;
// memory cards are not a payment source.

// ===========================================================================
// Printed-Spellshroud sweep: cards whose printed text gives them Spellshroud (or Omnishroud) but which
// HasSpellshroud() (Custom/GameLogic.php) did not recognise. Every fixture below has an opposing (player 1)
// targeted Spell aimed at a field of player 2 that holds the card under test plus one ordinary legal
// target, so a refused answer on the holder is meaningful. Spells used: Liturgy of Corruption (ally),
// Fracturize (item or weapon), Disenchant (phantasia).
// ===========================================================================

// --- Flawless Spirit of Mordred starter-deck skeleton (the real level 0 champion, whose On Enter fires at game start) ---
$mordredSpiritDeck = <<<'DECK'
# Material
1 Flawless Spirit of Mordred
1 Mordred, Burnished Avenger
1 Tristan, Underhanded
# Main
6 Dungeon Guide
6 Fluffy Shopkeep
6 Fairy Whispers
DECK;



// =============================================================================
// Fixed-cost "Prepare N" wiring audit (ATTACK / ACTION cards whose Prepare cost was never offered)
// =============================================================================
// Shared scaffolding. Player 2 is the Prepare player: its champion is patched to Tristan,
// Shadowdancer (ASSASSIN, UMBRA, so [Class Bonus] [ASSASSIN] and UMBRA cards are playable) carrying
// $counters preparation counters, plus $lineage extra champion cards in its lineage (Subcards)
// to unlock FIRE/WATER/LUXEM/CRUX/EXALTED cards. ATTACK cards cannot be activated by the first
// player on turn 1, so player 1 ends turn 1 and player 2 plays the card on its own turn.
$gaPrepareChamp = function(int $counters, array $lineage = []) {
    return ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'he6kd7hocc', 'Subcards' => array_merge(['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7'], $lineage), 'Counters' => ['preparation' => $counters]]];
};
$gaPrepareDeck = $tristanDeck;
$gaAct = function(int $playerID, string $cardID, int $mode = 100) {
    return ['playerID' => $playerID, 'mode' => $mode, 'buttonInput' => '', 'cardID' => $cardID, 'chkInput' => [], 'inputText' => ''];
};
// player 1 ends turn 1; player 2 plays the seeded hand card (myHand-7) and pays $reserve reserve cards
$gaPrepareP2Play = function(int $reserve) use ($gaAct) {
    $a = [$gaAct(1, 'myHealth-0!CustomInput!Pass', 10001), $gaAct(2, 'myHand-7!FSM!', 10002)];
    for($i = 0; $i < $reserve; ++$i) $a[] = $gaAct(2, 'myHand-0');
    return $a;
};
// same, for FAST cards: activating a fast card from hand first opens a "Take a fast action?" confirmation (answered with the card itself)
$gaFastP2Play = function(int $reserve) use ($gaAct) {
    $a = [$gaAct(1, 'myHealth-0!CustomInput!Pass', 10001), $gaAct(2, 'myHand-7!FSM!', 10002), $gaAct(2, 'myHand-7')];
    for($i = 0; $i < $reserve; ++$i) $a[] = $gaAct(2, 'myHand-0');
    return $a;
};
$gaLuxemLineage = ['UAF6Nr7GUE']; // Zander, Blinding Steel (LUXEM); EXALTED unlocks automatically alongside an advanced element
$gaFireLineage = ['LMyKyVC2O9'];   // Spirit of Fire
$gaWaterLineage = ['tafqldAGRF'];  // Spirit of Water
$gaCruxLineage = ['2TCyILvBYa'];   // Merlin, Brilliant Vestige (CRUX)

// Same non-Assassin control for the other two cards whose generated rider gated "[Class Bonus]" with
// IsClassBonusActive($player) and no class list (always true): player 2 keeps the deck's default Spirit
// of Fire champion (class SPIRIT) with the counters needed to pay Prepare.
$gaNonAssassinDeck = <<<'DECK'
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
DECK;

// --- Final Stroke (ekkjn37cx6): Prepare 3; [Class Bonus] On Champion Hit: if prepared and the hit champion has 20+ damage counters, destroy it ---
// Player 1's champion is patched to Ciel, Mirage's Grave (30 life) so 5 power + the seeded damage
// cannot kill it by damage alone: the destruction is Final Stroke's own effect.
$gaFinalStrokeVictim = function(int $damage) {
    return ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zhh43i1eaa', 'Damage' => $damage]];
};
// --- Condemning Evisceration (r84E55KBLM): [Class Bonus] Prepare 1; On Champion Hit: if prepared, may banish a floating-memory card from your graveyard; if you do, 4 damage to the hit champion ---
// Return to the Archive (aIbBhTilEN, Floating Memory) is seeded in player 2's graveyard.
$gaCondemningSetup = function() use ($gaPrepareChamp, $gaWaterLineage) {
    return [
        $gaPrepareChamp(1, array_merge($gaWaterLineage, ['UAF6Nr7GUE'])), // WATER + LUXEM (advanced) lineage unlocks EXALTED
        ['player' => 2, 'zone' => 'myGraveyard', 'cardID' => 'aIbBhTilEN'], // Return to the Archive (Floating Memory)
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'r84E55KBLM'],
    ];
};
// --- Stillshard Strike (TDI5DOrWB5): Prepare 1; On Attack: if prepared, recover 1+X (X = sheen on each defending unit) ---
// Player 2's champion starts with 6 damage; player 1's champion carries 2 sheen counters, so a
// prepared attack recovers 1+2 = 3.
$gaStillshardSetup = function() use ($gaPrepareChamp, $gaWaterLineage) {
    $champ = $gaPrepareChamp(1, $gaWaterLineage);
    $champ['setProperties']['Damage'] = 6;
    return [
        $champ,
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['sheen' => 2]]],
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'TDI5DOrWB5'],
    ];
};
// --- Strike from the Mist (DHn9J7gX6g): Prepare 2; [Class Bonus] as long as prepared, it can't be intercepted ---
$gaMistSetup = function() use ($gaPrepareChamp, $gaWaterLineage) {
    return [
        $gaPrepareChamp(2, $gaWaterLineage),
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'mHd6LLyMyF'], // Swift Recruit (Intercept ally) -> p1 myField-1
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'DHn9J7gX6g'],
    ];
};

// --- Soultrace Tessellation (7ePq6I4uZ8, ACTION): Prepare 1; put 3 sheen counters on target unit; if prepared, +1 sheen per three cards in your banishment ---
$gaSoultraceSetup = function() use ($gaPrepareChamp, $gaCruxLineage) {
    $s = [$gaPrepareChamp(1, $gaCruxLineage)];
    for($i = 0; $i < 6; ++$i) $s[] = ['player' => 2, 'zone' => 'myBanish', 'cardID' => 'em6eEh9q8y']; // six banished cards => +2 sheen when prepared
    $s[] = ['player' => 2, 'zone' => 'myHand', 'cardID' => '7ePq6I4uZ8'];
    return $s;
};

// --- Exploit Vulnerability (hy83sghwfi, ACTION, fast): Prepare 1; draw a card; if prepared, an Assassin unit you control gains "On Ally Hit: Destroy the hit ally" until end of turn ---
$gaExploitSetup = function() use ($gaPrepareChamp) {
    return [
        $gaPrepareChamp(1),
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'hy83sghwfi'],
    ];
};
// --- Fishing Accident (RRx0KK6g6D, ACTION, fast): Prepare 2; rest target ally; if prepared, put it on the bottom of its owner's deck instead ---
$gaFishingSetup = function() use ($gaPrepareChamp, $gaWaterLineage) {
    return [
        $gaPrepareChamp(2, $gaWaterLineage),
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'em6eEh9q8y'], // Dungeon Guide (ALLY) -> p1 myField-1
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'RRx0KK6g6D'],
    ];
};
// --- Silvergale Monstrosity's Call (lsLd8ADGAe, ACTION): Prepare 2; summon a Memorite Obelith; if prepared, move sheen from your Fractured Memories onto Memorite Obelith allies ---
$gaSilvergaleSetup = function() use ($gaPrepareChamp, $gaLuxemLineage) {
    return [
        $gaPrepareChamp(2, $gaLuxemLineage),
        ['player' => 2, 'zone' => 'myMastery', 'cardID' => 'UAJGQFbXjs', 'setProperties' => ['Counters' => ['sheen' => 5]]], // Fractured Memories with 5 sheen
        ['player' => 2, 'zone' => 'myHand', 'cardID' => 'lsLd8ADGAe'],
    ];
};
// --- Galestream Insight (usa6qyq3ka): "Look at the top six cards of your deck. You may reveal a Spell card from
// among them and put it into your memory. Put the rest on the bottom of your deck in any order." ---
// Pre-fix engine bug: GalestreamInsightResolve() pulled the six cards with a loop of MZMove($player, "myDeck-0",
// "myTempZone"). Remove() only flags the slot removed (no splice), so every iteration after the first resolved
// "myDeck-0" to the same already-removed slot and MZMove's own "already removed" guard silently no-oped: only the
// top card was ever looked at, and the spells deeper in the top six were never offered.
$gaGalestreamDeck = <<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK;
$gaGalestreamSetup = function(array $top6, int $player = 1, int $skip = 0) {
    $rows = [['player' => $player, 'zone' => 'myHand', 'cardID' => 'usa6qyq3ka']]; // Galestream Insight -> myHand-7
    // $skip: deck slots to leave alone (player 2's turn-start draw takes myDeck-0 before the activation)
    foreach ($top6 as $i => $cardID) $rows[] = ['player' => $player, 'patchMzId' => 'myDeck-' . ($i + $skip), 'setProperties' => ['CardID' => $cardID]];
    return $rows;
};
// --- Shackled Theurgist (vkqzk1jik7): "On Death: Target opponent may sacrifice an ally. If they don't, return Shackled
// Theurgist to the field. It gets +2 LIFE until end of turn and becomes ephemeral." ---
// The Theurgist is killed for real by Drenching Finish (2 power vs the Theurgist's 2 life); the On Death trigger goes on the
// effect stack and the opponent of its controller is asked "Sacrifice an ally?".
$gaTheurgistDeck = <<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK;

// --- Bygone Days (wXNDSNQ2x2): "At the beginning of your recollection phase, cascade -- 1: Recover 2. 2: Recover 1.
// 3: Each player chooses an ally they control and returns it to its owner's memory. Sacrifice CARDNAME." ---
// The phantasia is played for real; its cascade then triggers on the controller's 1st/2nd/3rd recollection phase.
$gaBygoneDeck = <<<'DECK'
# Material
1 Spirit of Water
# Main
10 Dungeon Guide
10 Fluffy Shopkeep
DECK;

// =====================================================================================================================
// Fixed-index move-loop sweep (Remove()/MZMove only FLAG a slot removed; the splice happens in CleanupRemovedCards()).
// A loop of MZMove($player, "myDeck-0", ...) therefore re-resolved the SAME removed slot after the first iteration and
// MZMove's "already removed" guard silently no-oped, so only the top card was ever moved (see 86027696, Galestream).
// ---------------------------------------------------------------------------------------------------------------------
$gaSweepDeck = function(string $champion) {
    return "# Material\n1 $champion\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n";
};
// Seed row helpers: deckTop places the listed card IDs on top of the player's deck (first listed = top card).
$gaTop = function(array $ids, int $player = 1) { return ['player' => $player, 'deckTop' => $ids]; };
$gaHand = function(string $cardID, int $player = 1) { return ['player' => $player, 'zone' => 'myHand', 'cardID' => $cardID]; };
$GA_DG = 'em6eEh9q8y';   // Dungeon Guide (ally)
$GA_FS = 'px60u5n1do';   // Fluffy Shopkeep (ally)

// --- Lure the Abyss (gqh3mw478q): "Reveal the top four cards of your deck. Put all Specter cards from among them into
// your graveyard and the rest on the bottom of your deck in any order." ---
$gaLureSpecterA = 'zyEMI5XNHt'; // Mourning Veilbound (SPECTER ally)
$gaLureSpecterB = 'PiZqM1q9ly'; // Night Barker (SPECTER ally)

// --- Ignition Draw (RhSPMn8Lix): "Look at the top six cards of your deck. Banish up to two Aethercharge cards from among
// them and put the rest on the bottom of your deck in any order. Until end of turn, you may activate the banished cards." ---
$gaIgnAeA = 'ra9950o14t'; // Charge the Soul (AETHERCHARGE spell)
$gaIgnAeB = '0w5bp5nuae'; // Prudent Nock (AETHERCHARGE spell)
$gaIgnAeC = '7l9th23niu'; // Aetheric Calibration (AETHERCHARGE spell)
$gaIgnSetup = function(array $top6, int $player = 1) use ($gaHand, $gaTop) { return [$gaHand('RhSPMn8Lix', $player), $gaTop($top6, $player)]; };

// --- Recruitment Officer (1x97n2jnlt): "[Class Bonus] Foster. On Foster: Look at the top five cards of your deck. You may
// reveal an ally card from among them and put it into your hand. Put the rest of the cards on the bottom of your deck in
// any order." ---
$GA_FW = 'n8wyfG9hbY'; // Fairy Whispers (SPELL, not an ally)
$gaRecruitSetup = function(array $top5, int $player = 1) use ($gaTop) {
    return [
        ['player' => $player, 'zone' => 'myField', 'cardID' => 'zb14m4c8lj'], // Tonoris, Lone Mercenary (GUARDIAN champion) -- Class Bonus source
        ['player' => $player, 'zone' => 'myField', 'cardID' => '1x97n2jnlt'], // Recruitment Officer
        $gaTop($top5, $player),
    ];
};
// player 1 ends turn 1, player 2 ends its turn -> player 1's recollection phase: Foster processing queues the On Foster prompt
// (the deck has no level-1 champion in its material, so there is no materialize offer to decline first)
$gaRecruitToFoster = [mrdEnd(1), mrdEnd(2)];

// --- Nico, Rapture's Embrace (29lqrve8fz): "On Enter: If there's another water element card in CARDNAME's lineage, look at the top
// two cards of your deck. Put one of those cards into your graveyard and the other on the bottom of your deck." ---
// Nico (level 1) is leveled up into from Spirit of Water (a WATER card, so the lineage condition holds): player 1's third global
// turn offers the materialize prompt, and the 1-memory level-up cost is paid from a memory card seeded as fuel.
$gaNicoDeck = "# Material\n1 Spirit of Water\n1 Nico, Rapture's Embrace\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n";
$gaNicoSetup = function(array $top, int $player = 1) use ($gaTop, $GA_FW) {
    return [['player' => $player, 'zone' => 'myMemory', 'cardID' => $GA_FW], $gaTop($top, $player)]; // memory card pays the level-up's 1-memory cost
};

// --- Divining Streams (TLqUZgBeg7): "Look at the top three cards of your deck. Put one of them into your graveyard, one on top
// of your deck, and one on the bottom of your deck." ---
$gaDivSetup = function(array $top3, int $player = 1) use ($gaHand, $gaTop) { return [$gaHand('TLqUZgBeg7', $player), $gaTop($top3, $player)]; };

// --- Ashen Riffle (fjpimrl974): "Reveal the top four cards of your deck. Banish up to two Suited non-action cards revealed this way
// and put the rest on the bottom of your deck in any order. For as long as those cards remain banished, you may activate them." ---
$gaRiffS2 = 'e8ygl32jef'; // Two of Spades (SUITED ally)
$gaRiffS3 = 'o09csnorqv'; // Three of Spades (SUITED ally)
$gaRiffS4 = '8bolq2y5qp'; // Four of Spades (SUITED ally)
$gaRiffAct = 'uxhmucm8si'; // Suited Trickery (SUITED ACTION: not eligible)
$gaRiffSetup = function(array $top4, int $player = 1) use ($gaHand, $gaTop) { return [$gaHand('fjpimrl974', $player), $gaTop($top4, $player)]; };

// --- Wuji of Lingering Fate (9cef7aknvn): "Whenever your Shifting Currents change from facing West to East, you may sacrifice CARDNAME.
// If you do, target player puts the top three cards of their deck into their graveyard." ---
// Shifting Currents faces West; the controller's champion is patched into Kongming's lineage (Fel Eidolon), whose Shifting Currents mastery
// lets the controller change it to ANY direction at the beginning of the end phase: passing the turn and choosing East fires W->E.
$gaWujiSetup = function(int $player = 1, array $topSelf = [], array $topOpp = []) use ($gaTop) {
    $rows = [
        ['player' => $player, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]], // Kongming lineage (Kongming Bonus: end-phase direction change)
        ['player' => $player, 'zone' => 'myMastery', 'cardID' => 'qh5mpkyl60', 'setProperties' => ['Direction' => 'WEST']],
        ['player' => $player, 'zone' => 'myField', 'cardID' => '9cef7aknvn'], // Wuji of Lingering Fate -> field-1
    ];
    if ($topSelf) $rows[] = $gaTop($topSelf, $player);
    if ($topOpp) $rows[] = $gaTop($topOpp, $player == 1 ? 2 : 1);
    return $rows;
};
$GA_MARK = 'ra9950o14t'; // Charge the Soul: a distinctive marker card placed just BELOW the cards a mill/look effect must take

// --- Peer the Depths (6JMwc6cpRm): "Activate this card only during your recollection phase. Target opponent skips their next draw
// phase. Look at the top four cards of that player's deck and banish a card from among them. Put the rest on the bottom of their
// deck in any order. Until the end of that player's next turn, they may activate the banished card, ignoring its elemental
// requirements." ---
$gaPeerSetup = function(array $oppTop4, int $player = 1) use ($gaHand, $gaTop) {
    return [$gaHand('6JMwc6cpRm', $player), $gaTop($oppTop4, $player == 1 ? 2 : 1)];
};

// --- Spellshield: Tera (yunjm0of8e): "The next time damage would be dealt to your champion this turn, prevent that damage. Reveal an
// amount of cards from the top of your deck equal to the amount of damage prevented this way and put them into your material deck
// preserved." ---
// The champion is patched into Kongming, Fel Eidolon (level 3, MAGE, TERA: unlocks the element and makes Leeching Bolt deal LV = 3).
// Player 1 first activates Spellshield: Tera, then Leeching Bolt (TERA spell: "Deal LV damage to target unit") aimed at its OWN champion,
// so exactly 3 damage is prevented and exactly 3 cards must be taken off the top of the deck.
$gaTeraShieldSetup = function(array $top, int $player = 1) use ($gaHand, $gaTop) {
    return [
        ['player' => $player, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '7x2v4tdop1']], // Kongming, Fel Eidolon (level 3 MAGE/TERA champion)
        $gaHand('yunjm0of8e', $player), // Spellshield: Tera -> hand-7
        $gaHand('hs1mzjzexc', $player), // Leeching Bolt -> hand-8
        $gaTop($top, $player),
    ];
};

// --- Paired Minds, Kindred Souls (7qjnqww067): "Look at the top ten cards of your deck. Reveal a Horse ally card from among them and put
// it into your hand. Put the rest on the bottom of your deck in any order." ---
$GA_HORSE = 'w6ax750524';  // Ordinary Horse (HORSE ally)
$GA_HORSE2 = 'r5uyjq37zh'; // Caretaker Horse (HORSE ally)
$gaPairedSetup = function(array $top, int $player = 1) use ($gaHand, $gaTop) { return [$gaHand('7qjnqww067', $player), $gaTop($top, $player)]; };

// --- SignalTech X Ultra (vFvhZeunOc): "[REST]: Look at the top three cards of your deck. You may reveal an ally card from among them and
// put it into your hand. Put the rest on the bottom of your deck in any order. If a card was put into your hand this way, sacrifice
// CARDNAME." ---
// The generated ability reaches ResolveTopDeckOptionalSelection() (GameLogic.php), which put the "rest" on the bottom with a loop of
// MZMove($player, "myDeck-0", "myDeck") (fixed-index: only one card moved) and, from the follow-up handler, was handed an UNDEFINED
// $topCount (so nothing at all went to the bottom after a reveal); the follow-up was also skipped on PASS (dontSkipOnPass missing).
$gaSignalSetup = function(array $top, int $player = 1) use ($gaTop) {
    return [['player' => $player, 'zone' => 'myField', 'cardID' => 'vFvhZeunOc'], $gaTop($top, $player)]; // SignalTech X Ultra -> field-1
};
$gaSignalAct = function(int $p) { return mrdAct($p, 10001, 'myField-1!CustomInput!Activate:0'); };

// --- Lucia, Reclaimed Blight (fIQR28QmYg): "[Class Bonus] On Enter: Look at the top six cards of your deck. You may reveal a Spell card from
// among them and put it into your memory. Put the rest on the bottom of your deck in any order." ---
// Same ResolveTopDeckOptionalSelection() path as SignalTech X Ultra. The champion is patched into Kongming, Fel Eidolon (MAGE for the Class
// Bonus, TERA to enable Lucia's EXALTED element).
$gaLuciaSetup = function(array $top6, int $player = 1) use ($gaHand, $gaTop) {
    return [
        ['player' => $player, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '7x2v4tdop1']], // Kongming, Fel Eidolon (MAGE, TERA)
        $gaHand('fIQR28QmYg', $player), // Lucia -> hand-7
        $gaTop($top6, $player),
    ];
};

// --- Storm Tyrant's Eye (EQZZsiUDyl): "Banish CARDNAME: Reveal cards from the top of your deck until you reveal an arcane element card. Deal
// unpreventable damage to your champion equal to the amount of cards revealed this way. Put one of those cards into your hand and the
// rest on the bottom of your deck in a random order." ---
$GA_ARCANE = '7aZwqrfbzO'; // Fulgurite Coordinator (ARCANE ally)
$gaStormEyeAct = function(int $p) { return mrdAct($p, 10001, 'myField-1!CustomInput!Activate:0'); };
$gaStormEyeSetup = function(array $top, int $player = 1) use ($gaTop) { return [['player' => $player, 'zone' => 'myField', 'cardID' => 'EQZZsiUDyl'], $gaTop($top, $player)]; };


// --- Icebound Slam (6fxxgmuesd): "On Attack: Put the top five cards of your deck into your graveyard. Then if there are five or more water element cards
// in your graveyard, Icebound Slam gets +5 POWER." Generated body milled with a loop of MZMove($player, "myDeck-0", "myGraveyard"): only the TOP card was ever
// milled (see 1676ae91 Pure Cytosynth). Top six of the deck: five Spirit of Water (WATER) then Charge the Soul (marker: must be the new top card). ---
$GA_SOW = 'tafqldAGRF'; // Spirit of Water (WATER)
// Attack cards can't be activated by the opening player on turn 1, so player 2 attacks (turn 2). Player 2's turn-start draw takes the top card of its
// deck, so a filler Dungeon Guide sits on top; the five milled cards and the marker follow.
$gaIceSlam = function(array $five) use ($gaHand, $gaTop, $GA_SOW, $GA_DG, $GA_MARK) {
    return [
        ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => [$GA_SOW]]], // WATER lineage/element unlock
        $gaHand('6fxxgmuesd', 2),
        $gaTop(array_merge([$GA_DG], $five, [$GA_MARK]), 2),
    ];
};
// ---------------------------------------------------------------------------

// --- Lena, Dorumegia's Herald (gwve1d47o7): "(4), [REST]: Look at the top four cards of your deck. You may reveal a Ranger ally card from among them and
// put it into your hand. Put the rest on the bottom of your deck in any order." and Enhance Hearing (edg616r0za): "Look at the top three cards of your
// deck. You may reveal a wind element card or a Reaction card from among them and put it into your hand. Put the rest on the bottom." The generated bodies
// looked with a fixed-index MZMove loop (only the top card was ever looked at) and the "may reveal" follow-up was skipped on PASS. ---
$GA_RANGER = 'bx4k3akqx7'; // Hidden Longbowman (RANGER ally)
$gaLenaSetup = function(array $top, int $player = 1) use ($gaTop) {
    return [
        ['player' => $player, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '7ozuj68m69', 'Subcards' => ['tafqldAGRF']]], // Diana, Deadly Duelist (level 2 RANGER): Class Bonus
        ['player' => $player, 'zone' => 'myField', 'cardID' => 'gwve1d47o7'], // Lena, Dorumegia's Herald -> field-1
        $gaTop($top, $player),
    ];
};
$gaLenaAct = function(int $p) { return mrdAct($p, 10001, 'myField-1!CustomInput!Activate:0'); };

// --- Captain Archer (disqw3d0o5): "Imbue 4. [Class Bonus] On Enter: If Captain Archer is imbued, look at the top six cards of your deck. You may put a wind
// element ally card with reserve cost 3 or less from among them onto the field distant. Put the rest on the bottom of your deck in any order." ---
$GA_WINDALLY = 'cqadnk9iz0'; // Baby Green Slime (WIND ally, reserve cost 2)
$gaArcherSetup = function(array $top, int $player = 1) use ($gaTop, $gaHand, $GA_FW) {
    return [
        ['player' => $player, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '7ozuj68m69', 'Subcards' => ['tafqldAGRF', 'pNiyaGlIe7']]], // Diana, Deadly Duelist (RANGER) with a Spirit of Wind lineage: Class Bonus + wind element
        ['player' => $player, 'emptyZone' => 'myHand', 'destZone' => 'myGraveyard'],
        $gaHand($GA_FW, $player), $gaHand($GA_FW, $player), $gaHand($GA_FW, $player), $gaHand($GA_FW, $player), // four wind cards to reserve: the Imbue 4 payment
        $gaHand('disqw3d0o5', $player), // Captain Archer -> myHand-4
        $gaTop($top, $player),
    ];
};

// --- Restoring Embers (FnTT1G4OQg): "Kindle 4. Recover 4. Then if your influence is four or less, draw a card into your memory." The generated body called DrawToMemory(),
// which does not exist (fatal); the engine function is DrawIntoMemory(). Influence = cards in hand + cards in memory. ---
$gaEmbersSetup = function(int $extraHand) use ($gaHand, $GA_DG) {
    $rows = [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Damage' => 6]], // champion with 6 damage: Recover 4 leaves 2
        ['player' => 1, 'emptyZone' => 'myHand', 'destZone' => 'myGraveyard'],
    ];
    for ($i = 0; $i < 4 + $extraHand; ++$i) $rows[] = $gaHand($GA_DG); // payers (hand-0..)
    $rows[] = $gaHand('FnTT1G4OQg'); // Restoring Embers -> last hand slot
    return $rows;
};

// --- Genuflecting Execution (iqzaNLhqk4): "Destroy up to two target rested allies." Both generated target handlers called DestroyAlly(), which does not exist (fatal);
// the engine function is DoAllyDestroyed(). Cost 7 at champion level 0 (Efficiency). Player 2 controls two RESTED allies and one awake ally. ---
$gaGenuflectSetup = function() use ($gaHand, $GA_DG, $GA_FS) {
    $rows = [['player' => 1, 'emptyZone' => 'myHand', 'destZone' => 'myGraveyard'],
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['he6kd7hocc']]]]; // any advanced element auto-enables EXALTED
    for ($i = 0; $i < 7; ++$i) $rows[] = $gaHand($GA_DG); // seven payers (hand-0..6)
    $rows[] = $gaHand('iqzaNLhqk4'); // Genuflecting Execution -> myHand-7
    $rows[] = ['player' => 2, 'zone' => 'myField', 'cardID' => $GA_DG]; // theirField-1 (rested below)
    $rows[] = ['player' => 2, 'zone' => 'myField', 'cardID' => $GA_FS]; // theirField-2 (rested below)
    $rows[] = ['player' => 2, 'zone' => 'myField', 'cardID' => $GA_DG]; // theirField-3 stays awake
    $rows[] = ['player' => 2, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 1]];
    $rows[] = ['player' => 2, 'patchMzId' => 'myField-2', 'setProperties' => ['Status' => 1]];
    return $rows;
};


// --- Rolling Chorus (IZVXDYjcSL): "Banish up to two target cards from a single graveyard. If the total reserve cost of the banished cards is 15 or greater, draw a card into your
// memory." The generated handler reached RollingChorusBanishAndDraw(), which called CardReserveCost(): that function does not exist (fatal). ---
$gaChorusSetup = function(string $gyA, string $gyB) use ($gaHand) {
    return [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['x9sSpjpP3G']]], // Lorraine, Arclight Saber (ARCANE): unlocks the ARCANE element
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => $gyA],
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => $gyB],
        $gaHand('IZVXDYjcSL'), // Rolling Chorus -> myHand-7
    ];
};


// --- Naia, Diviner of Fortunes (jdmthh88rx): "[Class Bonus] On Enter: Reveal the top three cards from your deck. Banish one of those cards and put the rest into your graveyard. If the
// banished card is a Spell card, you may activate it as long as you control CARDNAME." The generated body revealed with a fixed-index MZMove loop (only the top card was ever revealed). ---
$gaNaiaSetup = function(array $top, int $player = 1) use ($gaHand, $gaTop) {
    return [
        ['player' => $player, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '7x2v4tdop1', 'Subcards' => ['tafqldAGRF']]], // Kongming, Fel Eidolon (MAGE) with a Spirit of Water lineage: Class Bonus + water element
        $gaHand('jdmthh88rx', $player), // Naia -> myHand-7
        $gaTop($top, $player),
    ];
};

// --- Kind Beastcaller (k02kvfblwa): "[Class Bonus] On Enter: Look at the top five cards of your deck. You may reveal an Animal or Beast ally card from among them and put it into your hand. Put the
// rest on the bottom of your deck in any order." The generated body looked with a fixed-index MZMove loop (only the top card was ever looked at) and the follow-up was skipped on PASS. ---
$gaBeastSetup = function(array $top, int $player = 1) use ($gaHand, $gaTop) {
    return [
        ['player' => $player, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'j6dkdoxyqt']], // Guo Jia, Chosen Disciple (TAMER champion): Class Bonus
        $gaHand('k02kvfblwa', $player), // Kind Beastcaller -> myHand-7
        $gaTop($top, $player),
    ];
};






// --- Advent of the Stormcaller (ZSSegCjquB): "[Class Bonus] Efficiency. Reveal the top LV cards of your deck. You may banish any amount of arcane element cards from among them. For each card banished
// this way, choose a unit and deal 2 damage to it. Put the rest of the revealed cards on the top or on the bottom of your deck in any order." Defects: the generated body revealed with a fixed-index
// MZMove loop (only the top card); a card banished on one pass was offered again on the next (ZoneSearch does not skip removed slots) and still counted toward the damage; and declining the "may banish"
// prompt skipped the follow-up (dontSkipOnPass missing), stranding the revealed cards. The champion is level 3 (Efficiency: cost 15 - 3 = 12). Rolling Chorus is the arcane card. ---
$gaAdventSetup = function(array $top) use ($gaHand, $gaTop, $GA_DG) {
    $rows = [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '7x2v4tdop1', 'Subcards' => ['x9sSpjpP3G']]], // Kongming, Fel Eidolon (level 3, MAGE) with an ARCANE lineage (Lorraine, Arclight Saber)
        ['player' => 1, 'emptyZone' => 'myHand', 'destZone' => 'myGraveyard'],
    ];
    for ($i = 0; $i < 12; ++$i) $rows[] = $gaHand($GA_DG); // twelve payers (hand-0..11)
    $rows[] = $gaHand('ZSSegCjquB'); // Advent of the Stormcaller -> myHand-12
    $rows[] = $gaTop($top);
    return $rows;
};




// --- Blinding Lapse (rKKDhaLJ8w): "Target opponent with influence nine or more puts all cards from their hand into their memory. Then that player banishes three cards at random from their memory."
// The generated body was registered only in the dead $activateCardAbilities table, so playing it did nothing. Influence = hand + memory. ---
$gaLapseSetup = function(int $oppMemory) use ($gaHand) {
    $rows = [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'UAF6Nr7GUE']], // Zander, Blinding Steel (level 3, LUXEM): unlocks the LUXEM element
        $gaHand('rKKDhaLJ8w'), // Blinding Lapse -> myHand-7
    ];
    // every opponent card is the same Dungeon Guide so the printed "banishes three cards at random" lands in the same state whichever are picked (the fixture must replay identically)
    $rows[] = ['player' => 2, 'emptyZone' => 'myHand', 'destZone' => 'myGraveyard'];
    for ($i = 0; $i < 7; ++$i) $rows[] = ['player' => 2, 'zone' => 'myHand', 'cardID' => 'em6eEh9q8y'];
    for ($i = 0; $i < $oppMemory; ++$i) $rows[] = ['player' => 2, 'zone' => 'myMemory', 'cardID' => 'em6eEh9q8y'];
    return $rows;
};


// --- "Destroy target item or weapon with memory cost 0 or reserve cost N or less": the generated filter treated the data's -1 ("no reserve cost") as "<= N", so memory-only items were legal targets.
// The opponent controls Scepter of Lumina (memory cost 1, no reserve cost: NOT a legal target) and Forest Cake (reserve cost 2: legal). ---
$gaLowCostSetup = function(string $cardID) use ($gaHand) {
    return [
        $gaHand($cardID), // the destroyer -> myHand-7
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'e5o3cm9lbe'], // Scepter of Lumina (memory cost 1, no reserve cost) -> theirField-1
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'bjx6yo7mm5'], // Forest Cake (reserve cost 2) -> theirField-2
    ];
};

$gaShroudSetup = function(string $spell) use ($gaHand, $GA_DG) {
    return [
        $gaHand($spell), // the Spell -> myHand-7
        ['player' => 2, 'zone' => 'myField', 'cardID' => 'k8bwlx70qj'], // Genbu, Black Tortoise (spellshroud) -> theirField-1
        ['player' => 2, 'zone' => 'myField', 'cardID' => $GA_DG],       // Dungeon Guide -> theirField-2
    ];
};

// --- Bertha, Spry Howitzer (ki6fxxgmue): "[Class Bonus] [Level 2+] On Enter: Look at the top five cards of your deck. You may activate a Ranger action card with reserve cost 2 or less from among them without paying its costs. Put the rest on the
// bottom of your deck in any order." Declining the free activation PASSes the MZMAYCHOOSE; BerthaChooseAction was queued without dontSkipOnPass, so its "put the rest on the bottom" branch never ran and all five looked-at cards stayed in the
// temp zone. Hightail (Xy8loj55gJ, Ranger action, reserve 1) is the only legal pick. ---
$gaBerthaSetup = function(array $top) use ($gaTop, $gaHand) {
    return [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => '7ozuj68m69', 'Counters' => ['level' => 2]]], // Diana, Deadly Duelist (RANGER) at level 2: Class Bonus + Level 2+
        $gaHand('ki6fxxgmue'), // Bertha -> myHand-7
        $gaTop($top),
    ];
};


// --- Brewing Kit (dwmxz1vdxi): "[REST], Sacrifice three Herbs: Look at the top six cards of your deck. You may reveal a Potion item card from among them and put it into your hand. Put the rest on the bottom of your deck in any order."
// Declining the Potion PASSes the MZMAYCHOOSE; BrewingKitPotionPick was queued without dontSkipOnPass, so its cleanup (BrewingKitCleanup) never ran and all six looked-at cards stayed in the temp zone. Distilled Atrophy
// (h38lrj5221, a Potion item) is the only legal pick; three Blightroot herb tokens are sacrificed to pay. ---
$GA_BLIGHTROOT = 'i0a5uhjxhk'; // Blightroot (Herb token)

// --- Arondight, Azure Blade (29xxoo7dl5): "[Class Bonus] On Enter: You may banish any amount of cards with floating memory from your graveyard. For each card banished this way, put a refinement counter on Arondight." Declining a further
// banish (PASS) must still add one refinement counter per card already banished; ArondightBanishChoice was queued without dontSkipOnPass, so ExecuteStaticMethods() skipped the handler and the counters were lost. Two Fast Cure (3oda2ha4dk,
// floating memory) are seeded into the graveyard; one is banished and the next offer declined. ---
$gaWarriorWater = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'DpHDGaX2Pn', 'Subcards' => ['tafqldAGRF']]]; // Lorraine, Wandering Warrior (WARRIOR) + Spirit of Water lineage: Class Bonus + WATER element
$gaMaterializeTurnTwo = function() { // both players pass through to player 1's second turn, where a real Materialize opportunity is offered
    return array_merge([mrdEnd(1)], array_fill(0, 6, mrdEnd(2)));
};

// --- Stonescale Band (sphwpjsznn): "[Class Bonus] On Enter: Discard up to three ally cards from your hand and/or memory, then draw that many cards." Declining a further discard (PASS) must still draw one card per ally already
// discarded; StonescaleBandDiscard's loop re-queue was missing dontSkipOnPass, so the handler (and its draw) was skipped. The champion is patched to Guo Jia, Chosen Disciple (TAMER) with a Kongming (TERA) lineage for the Class Bonus
// and TERA element access; the seeded hand is all allies (Dungeon Guide / Fluffy Shopkeep). ---
$gaTameTera = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'j6dkdoxyqt', 'Subcards' => ['7x2v4tdop1']]]; // Guo Jia (TAMER) + Kongming (TERA) lineage





// --- Declined optional prompts (MZMAYCHOOSE answered PASS) must still run their continuation. A handler queued without
// dontSkipOnPass is skipped by ExecuteStaticMethods() on PASS, along with every unflagged CUSTOM queued behind it, so whatever the
// handler does on a decline (finalize the loop, deal the "if you don't" damage, sacrifice the card, ...) never happened. ---


// --- Actions whose effect was generated as an "On Enter" ability. An Action never enters the field, so the effect was never run and activating the card only paid for it and put it into the
// graveyard. OnCardActivated() now treats an Action's enter ability as its resolution effect when it has no activation ability of its own. One fixture per affected card. ---






// Sacrifice Play (1jmQ9XSLph): "As an additional cost to activate this card, sacrifice up to two awake Chessman allies. Command Chessman. CARDNAME enters the intent with +2 POWER for each ally sacrificed this way."
// The additional cost was never implemented (its handlers were never queued), so no ally was ever sacrificed and the power bonus never applied.
$gaSacrificePlaySetup = [
    ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['_overrides' => ['classes' => 'WARRIOR']]]], // WARRIOR class
    ['player' => 1, 'zone' => 'myField', 'cardID' => 'Rpr6yCQKU6'], // Pawn Piece (CHESSMAN ally) -> myField-1
    ['player' => 1, 'zone' => 'myField', 'cardID' => 'Rpr6yCQKU6'], // Pawn Piece (CHESSMAN ally) -> myField-2
    ['player' => 1, 'zone' => 'myField', 'cardID' => 'Rpr6yCQKU6'], // Pawn Piece (CHESSMAN ally) -> myField-3 (the Command needs an ally left to attack with)
    $gaHand('1jmQ9XSLph'), // Sacrifice Play -> myHand-7
];




// Mordred, Flawless Blade (WI2owxIw0z): "Attack cards in your graveyard have floating memory." That static ability is one of the champion's abilities: with abilities removed (NO_ABILITIES) an attack card in the graveyard has
// no floating memory, so it cannot pay for a materialize's memory cost. Arondight (memory 1) with no memory cards cannot be paid for: the materialization is refused (offered again) instead of offering the attack card.
$gaFlawlessSetup = function(bool $noAbilities) {
    return [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => array_merge(['CardID' => 'WI2owxIw0z', 'Subcards' => ['tafqldAGRF']], $noAbilities ? ['Counters' => ['_overrides' => ['NO_ABILITIES' => 1]]] : [])], // Mordred, Flawless Blade (+ Spirit of Water lineage for the WATER element); NO_ABILITIES as a persistent override (a turn effect would not survive the turn changes)
        ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => '0xylS3OcNa'], // Emberslash (ATTACK card) -> myGraveyard-0
    ]; // no memory cards: Arondight's 1 memory has to come from floating memory
};






// Smashing Force (88rx6p3p5i): "[Class Bonus] On Attack: You may banish two fire element cards from your graveyard. When you do, destroy target item or weapon with memory cost 0 or reserve cost 4 or less."
// The banish is optional ("you may"); it used to be forced whenever two fire cards were in the graveyard. Player 2 (a Guardian) attacks on their first turn with two fire cards in their graveyard while
// player 1 controls Scepter of Lumina (memory cost 1, not a legal target) and Forest Cake (reserve cost 2).
$gaSmashingSetup = [
    ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['_overrides' => ['classes' => 'GUARDIAN']]]], // GUARDIAN class: the Class Bonus is active
    ['player' => 2, 'zone' => 'myGraveyard', 'cardID' => 'r7oifozaog'], // Baby Red Slime (fire) -> p2 myGraveyard-0
    ['player' => 2, 'zone' => 'myGraveyard', 'cardID' => 'r7oifozaog'], // Baby Red Slime (fire) -> p2 myGraveyard-1
    ['player' => 1, 'zone' => 'myField', 'cardID' => 'e5o3cm9lbe'], // Scepter of Lumina (memory cost 1, no reserve cost) -> p1 myField-1
    ['player' => 1, 'zone' => 'myField', 'cardID' => 'bjx6yo7mm5'], // Forest Cake (reserve cost 2) -> p1 myField-2
    ['player' => 2, 'zone' => 'myHand', 'cardID' => '88rx6p3p5i'], // Smashing Force -> p2 myHand-7
];


// --- Declined optional choices whose handlers do the "decline" part themselves (GameCustomHandlerRunsOnPass): ExecuteStaticMethods() used to skip them on PASS. ---




// Clockwork Musicbox (q2svdv3zb9): "[REST]: You may activate a card banished by CARDNAME. (You still pay its costs.)" The card used to be moved to the hand before anything checked it could be activated.
$gaMusicboxSetup = function(bool $affordable) use ($GA_DG, $gaHand) {
    $rows = [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'q2svdv3zb9'], // Clockwork Musicbox -> myField-1 (Hindered: it enters rested)
        ['player' => 1, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]], // wake it up: its [REST] ability can be used
        ['player' => 1, 'zone' => 'myBanish', 'cardID' => $GA_DG, 'setProperties' => ['Counters' => ['_musicbox' => 1]]], // a Dungeon Guide (reserve cost 3) banished by the Musicbox -> myBanish-0
    ];
    if(!$affordable) array_unshift($rows, ['player' => 1, 'emptyZone' => 'myHand', 'destZone' => 'myGraveyard']); // no hand cards: the 3 reserve cost cannot be paid
    return $rows;
};


// Coronal of Rejuvenation (uvgflagxbb): "[REST]: You may play a card banished by CARDNAME. Activate this ability only at slow speed." The card used to be moved to the hand before anything checked it could be activated.
$gaCoronalSetup = function(bool $affordable) use ($GA_DG) {
    $rows = [
        ['player' => 1, 'zone' => 'myField', 'cardID' => 'uvgflagxbb'], // Coronal of Rejuvenation -> myField-1
        ['player' => 1, 'patchMzId' => 'myField-1', 'setProperties' => ['Status' => 2]],
        ['player' => 1, 'zone' => 'myBanish', 'cardID' => $GA_DG, 'setProperties' => ['Counters' => ['_coronal' => 1]]], // a Dungeon Guide (reserve cost 3) banished by Coronal -> myBanish-0
    ];
    if(!$affordable) array_unshift($rows, ['player' => 1, 'emptyZone' => 'myHand', 'destZone' => 'myGraveyard']); // no hand cards: the 3 reserve cost cannot be paid
    return $rows;
};





// --- Starter-deck coverage batch: Conflagrant Sentinel, Tempered Steel, Charge the Soul, Nocturne's Oblivion, Coy Bouclier ---



// --- Starter-deck coverage batch 2 (Ciel / Diana decks): omen cards ---
$gaOmen = function(string $cardID = 'em6eEh9q8y') { return ['player' => 1, 'zone' => 'myBanish', 'cardID' => $cardID, 'setProperties' => ['Counters' => ['omen' => 1]]]; }; // an omen: a banished card with an omen counter
$gaCiel = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'nn48ne8a05']]; // Ciel, Loyal Valet (GUARDIAN champion named Ciel: the Ciel Bonus)





// --- Starter-deck coverage batch 3: regalia items whose activated abilities were missing or misfiled ---

// --- Starter-deck coverage batch 3b: regalia items whose activated abilities were missing ---
$gaBlueSlime = 'r7oifozaog'; // Baby Red Slime: SLIME ally, power 2 (FIRE)
$gaWaterSlime = '9ggfiy38t2'; // Baby Blue Slime: SLIME ally, WATER element

// Verdant Scepter (7wsxirq146): "[Class Bonus] On Enter: You may banish a Slime ally you control. If you do, put an amount of refinement counters on CARDNAME equal to the banished ally's power plus 1."
$gaVerdantChamp = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1'], 'Counters' => ['_overrides' => ['classes' => 'TAMER']]]]; // TERA lineage + TAMER class bonus
$gaVerdantDeck = "# Material\n1 Spirit of Fire\n1 Verdant Scepter\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n";
// Jinzhuo, Bands of Virtue (m4MTDKWvyR): "[Guo Jia Bonus] [REST], Banish CARDNAME: Scavenge 6 for a Beast ally card. If a card was scavenged this way, put X quest counters on your champion, where X is that card's power stat (capped at 4).
// Otherwise, return CARDNAME to the field rested. Activate this ability only if you control an object named Fabled Emerald Fatestone."
$gaJinzhuoSetup = [
    ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'j6dkdoxyqt']], // Guo Jia, Chosen Disciple (Guo Jia Bonus)
    ['player' => 1, 'zone' => 'myField', 'cardID' => 'jz7odeqku4'], // Fabled Emerald Fatestone -> myField-1
    ['player' => 1, 'zone' => 'myField', 'cardID' => 'm4MTDKWvyR'], // Jinzhuo, Bands of Virtue -> myField-2
];
// Seraphic Legion's Descent (QX72P4Xx1A): "On Enter: Search your deck for any amount of Angel ally cards. Banish those cards along with any amount of Angel ally cards from your hand, memory, and/or graveyard. Shuffle your deck.
// Then draw a card into your memory for each card banished from your hand and memory this way."  (EXALTED + NORM: the champion's lineage includes a TERA card so the Exalted element is enabled.)
$gaLegionChamp = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['7x2v4tdop1']]]; // Spirit of Fire + Kongming (TERA): Exalted enabled
// "[Level 3+] (1), [REST]: Until end of turn, you may activate target card banished by CARDNAME."
$gaLegionLvl3 = [
    ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'enxi6tshtu']], // Guo Jia, Heaven's Favored (level 3)
    ['player' => 1, 'zone' => 'myField', 'cardID' => 'QX72P4Xx1A'], // Seraphic Legion's Descent -> myField-1
    ['player' => 1, 'zone' => 'myBanish', 'cardID' => 'aKjX6INGkV', 'setProperties' => ['Counters' => ['seraphicLegion' => 1]]], // Angelic Vanguard banished by the Legion -> myBanish-0
];
// --- Starter-deck coverage batch 4 (Ciel / Diana decks): allies, items and weapons ---
$gaNoop = function() { return mrdAns(1, 'myField-0'); }; // an answer with nothing pending is accepted as a no-op: gives the fixture a step to assert on

// --- Starter-deck coverage batch 5 (Ciel deck): omen-scaled statics ---
$gaCielDeck = $gaSweepDeck('Spirit of Fire');
// --- Starter-deck coverage batch 5b (Diana deck): Aethercharge spells and Aetherwings ---
$gaWing = ['player' => 1, 'zone' => 'myField', 'cardID' => 'mob9nu6lal']; // Salamander's Breath (Aetherwing weapon) -> myField-1
$gaRangerAstra = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba'], 'Counters' => ['_overrides' => ['classes' => 'RANGER']]]]; // ASTRA lineage + RANGER class
// --- Starter-deck coverage batch 5c (Ciel deck): champions ---
// Ciel, Loyal Valet (nn48ne8a05): "Inherited Effect -- At the beginning of your end phase, you may banish a card from your graveyard or hand and put an omen counter on it."
$gaCielDeckMat = "# Material\n1 Spirit of Fire\n1 Ciel, Omenbringer\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n";
// Ciel, Mirage's Grave (zhh43i1eaa): "Whenever an omen counter is put on a card in your banishment, as a Spell, deal 2 unpreventable damage to up to one target unit."
$gaCielGrave = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'zhh43i1eaa']];
// Diana, Moonpiercer (v3vfjtwm7g): "Whenever Diana becomes distant, choose one -- Negate each card activation that targets Diana unless its controller pays (2). Then if Diana is defending, end the combat phase unless the attacking player pays (2). / Glimpse 2."
$gaMoonpiercer = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'v3vfjtwm7g']]; // Diana, Moonpiercer (RANGER)
// Diana, Aether Dilettante (m7f6r8f3y8): "On Enter: If Diana is distant, materialize an Aetherwing card from your material deck. (You still pay its costs.)"
$gaDilettanteDeck = "# Material\n1 Spirit of Fire\n1 Diana, Aether Dilettante\n1 Salamander's Breath\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n";
// Pleiades, Celestial Genesis (rsps1qnzfl): "[Class Bonus] On Enter: Glimpse 3."
$gaPleiadesDeck = "# Material\n1 Spirit of Fire\n1 Pleiades, Celestial Genesis\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n";

// Slime Calling (dc8P58gmjR): "Activate this card only during an opponent's end phase. [Class Bonus] Look at the top 3+LV cards of your deck. You may activate up to two Slime ally cards from among them. Cards you activate this way cost 1 less to activate. Put the rest of the cards on the bottom of your deck in any order."
$gaTamer = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'pNiyaGlIe7', 'Counters' => ['_overrides' => ['classes' => 'TAMER']]]]; // Spirit of Wind (enables the WIND element) as a Tamer
// Meteoric Volley (10u5ldz371): "Activate this card only if your champion is attacking. For each Aethercharge card in the attacker's intent, create a copy of it in that attacker's intent."
$gaP2RangerAstra = ['player' => 2, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['q3huqj5bba'], 'Counters' => ['_overrides' => ['classes' => 'RANGER']]]];
$gaP2Wing = ['player' => 2, 'zone' => 'myField', 'cardID' => 'mob9nu6lal', 'setProperties' => ['Subcards' => ['wd7nuab7f3']]]; // Salamander's Breath loaded with Aether's Embrace -> p2 myField-1
// Lurid Dreaming (ps8unuy20m): "Activate this card only during an opponent's end phase. Wake up your champion. If you do, draw a card into your memory."
$gaCleric = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Status' => 1, 'Counters' => ['_overrides' => ['classes' => 'CLERIC']]]]; // rested Cleric champion

// --- Generated handlers that read a variable only defined in the ability body that queued them (closures do not share scope): the value was null at resolution. Each card below is
// played / activated end to end; pre-fix the effect did nothing (or the wrong amount). ---

// --- Graveyard-activated abilities ("(N), Banish this card from your graveyard: ..."): the engine had no route from a graveyard click to the generated ability bodies, so these were unusable (or, for Induction
// Strike / Proof of Life / Stellarion Shift, never paid). They now go through ActivateAbility with the (N) and the banish paid as the cost. ---

// Collapsing Trap (v2214upufo): "[Class Bonus] If it's not your turn, you may remove a preparation counter from your champion to activate this card from your memory without paying its reserve cost. The next time
// one or more allies would enter the field this turn, they enter the field rested instead." The memory alternate cost existed only for Stifling Trap (the other Traps had a TODO). Tristan (Assassin) has the
// counter and a Water lineage; player 2 plays a Dungeon Guide and player 1 answers from memory.
$gaCollapsingTristan = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['CardID' => 'gt7lh9v221', 'Subcards' => ['bjlwabipl6', 'tafqldAGRF'], 'Counters' => ['preparation' => 1]]];
// Seaside Rangefinder (5qyee9vkp8): "[Class Bonus] [Element Bonus] (2), Banish this card from your graveyard: Target unit becomes distant." A Ranger champion with a WATER element (override).
$gaRangerWater = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['_overrides' => ['classes' => 'RANGER', 'element' => 'WATER']]]];
// Visceral Inversion (tZtoAl4ojK): "Target attacking ally gets -5 POWER until end of turn." Used by the DEFENDING player: CombatAttacker is stored relative to the attacker, so the handler
// resolved the wrong object (or nothing) when player 1 answered player 2's attack and the Dungeon Guide never shrank.
$gaCombatClericUmbra = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['he6kd7hocc'], 'Counters' => ['_overrides' => ['classes' => 'CLERIC']]]]; // UMBRA lineage + CLERIC class
// Craggy Fatestone (h8n1520m2d): "[Guo Jia Bonus] [REST]: Transform CARDNAME. Activate this ability only if there are two or more buff counters on CARDNAME." With ONE buff counter the activation is refused
// (it neither rests nor transforms); with two it transforms and the rest cost is paid.
$gaCraggyDeck = "# Material\n1 Spirit of Fire\n# Main\n10 Dungeon Guide\n";
// Shadow's Claw (vm4kj3q2sv): "As long as you have four or more preparation counters on your champion, you may activate this card from your material deck." Mercenary's Blade (k0xhi5jnsl): "[Class Bonus] You may
// remove a preparation counter from your champion to activate this card from your material deck." Clicking the material card in the main phase puts it onto the field (GameLogic.php FSM, myMaterial case).
$gaPrepMaterialDeck = "# Material\n1 Spirit of Fire\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n";
$gaPrepChampion = function(int $prep) { return ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['he6kd7hocc'], 'Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'preparation' => $prep]]]; }; // UMBRA lineage, ASSASSIN, N preparation counters
// Mist Resonance (hw8dxKAnMX): "[Class Bonus] Harmonize -- If you've activated a Melody card this turn, allies you control assign damage with their life stat instead of power stat until end of turn." Harmonize was
// not implemented. A Dungeon Guide (printed power 1) attacks after Steady Verse (a Melody) and Mist Resonance: with +1 LIFE from Mist Resonance its life stat is the damage it assigns.
$gaMistTamer = ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['29lqrve8fz'], 'Counters' => ['_overrides' => ['classes' => 'TAMER']]]]; // Nico (WATER lineage), TAMER class
$gaMistTurnTwo = [mrdEnd(1), mrdPass(1), mrdPass(1), mrdEnd(2), mrdPass(1), mrdPass(1), mrdPass(1)]; // to player 1's second turn (a fast card in hand opens a window at every turn boundary; seeded units can attack from turn 2)
$gaMistDeck = "# Material\n1 Spirit of Fire\n# Main\n10 Dungeon Guide\n10 Fluffy Shopkeep\n";
// Manabolt Convergence (smse0zjalx): "Until end of turn, you may activate target Aethercharge card in your graveyard. (You still pay its costs.) You may load Manabolt Convergence into an Aetherwing weapon you control."
// The first sentence was a stub. Charge the Soul (ra9950o14t, an Aethercharge, reserve 1: deal 1 damage to target unit) sits in the graveyard; Manabolt Convergence targets it and it is then activated from there.
$gaManaboltSetup = [
    ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Counters' => ['_overrides' => ['classes' => 'RANGER']]]],
    ['player' => 1, 'zone' => 'myGraveyard', 'cardID' => 'ra9950o14t'], // Charge the Soul -> p1 graveyard-0
    $gaHand('smse0zjalx'), // Manabolt Convergence -> p1 myHand-7
];
$gaCapacitanceSetup = function(int $arcane) {
    $rows = [
        ['player' => 1, 'patchMzId' => 'myField-0', 'setProperties' => ['Subcards' => ['TJTeWcZnsQ', 'x9sSpjpP3G']]], // Lorraine, Blademaster (Lorraine Bonus) and Lorraine, Arclight Saber (ARCANE element access) in the lineage
        ['player' => 1, 'zone' => 'myMaterial', 'cardID' => 'FjklZcbjPV'], // Capacitance X Psycho -> p1 myMaterial-0
    ];
    for ($i = 0; $i < $arcane; ++$i) $rows[] = ['player' => 1, 'zone' => 'myBanish', 'cardID' => 'wFH1kBLrWh']; // Arcane Elemental (arcane)
    return $rows;
};
// Filter if --fixture specified
// ---------------------------------------------------------------------------
// Scenario-builder definitions (DevTools/ga-fixtures/): fluent descriptions in card names that expand to the same
// array shape as the inline definitions above. They register into $fixtures; see Scenario.php.
// Scenario-first rule: a fixture is described with the scenario builder (DevTools/ga-fixtures/*.php), not as an array in this file. The fixtures defined inline up to this point must all be
// listed in ga-fixtures/inline-allowlist.txt (the pre-builder legacy); a new inline definition is refused. GA_PRINT_INLINE=1 prints the current inline slugs (to refresh the list when one is converted).
$inlineSlugs = array_keys($fixtures);
if (getenv('GA_PRINT_INLINE')) { echo implode("\n", $inlineSlugs) . "\n"; exit(0); }
$inlineAllowFile = __DIR__ . '/ga-fixtures/inline-allowlist.txt';
$inlineAllowed = is_file($inlineAllowFile) ? array_values(array_filter(array_map('trim', file($inlineAllowFile)), fn($line) => $line !== '' && $line[0] !== '#')) : [];
$inlineViolations = array_values(array_diff($inlineSlugs, $inlineAllowed));
if (!empty($inlineViolations)) {
    fwrite(STDERR, "create-prd-ability-fixtures: inline fixture definitions are not allowed any more -- write these with the scenario builder (see DevTools/ga-fixtures/Scenario.php):\n  " . implode("\n  ", $inlineViolations) . "\n");
    exit(1);
}
require_once __DIR__ . '/ga-fixtures/Scenario.php';
require_once __DIR__ . '/ga-fixtures/helpers.php';
foreach (glob(__DIR__ . '/ga-fixtures/*.php') as $scenarioFile) {
    if (!in_array(basename($scenarioFile), ['Scenario.php', 'decks.php'], true)) require_once $scenarioFile;
}

// Read-only definition export for inventory tools and reviewed family discovery.
if (in_array('--export-shared-families', $argv, true)) {
    echo json_encode(array_filter($fixtures, fn($def) => isset($def['meta']['behaviorFamily'])), JSON_THROW_ON_ERROR);
    exit(0);
}

// Behavior families run from definitions and never record their own expected outcomes.
require_once __DIR__ . '/ga-fixtures/behavior-families/suites.php';
$behaviorCases = gaBehaviorCases();
foreach ($behaviorCases as $behaviorSlug => $row) {
    if ($row[1] === '@shared') {
        if (($fixtures[$behaviorSlug]['meta']['behaviorFamily'] ?? null) !== $row[0]) {
            throw new RuntimeException("Shared behavior definition missing or changed: $behaviorSlug");
        }
    } else gaBehaviorScenario($behaviorSlug, $row)->register();
}
foreach ($fixtures as $sharedSlug => $sharedDef) {
    if (isset($sharedDef['meta']['behaviorFamily']) && !isset($behaviorCases[$sharedSlug])) {
        throw new RuntimeException("Shared behavior missing from reviewed families.json: $sharedSlug");
    }
}
if ($familiesOnly) $fixtures = array_intersect_key($fixtures, $behaviorCases);

// --audit-actions: list saved fixtures whose actions.json has fewer actions than their definition (an older generator silently dropped every action after the first refused one).
if ($auditActions) {
    $checked = 0; $truncated = 0;
    foreach ($fixtures as $auditSlug => $auditDef) {
        if (isset($behaviorCases[$auditSlug])) continue;
        $auditFile = "$repoRoot/Tests/Integration/$rootName/$auditSlug/actions.json";
        if (!is_file($auditFile)) continue;
        ++$checked;
        $recorded = json_decode(file_get_contents($auditFile), true) ?: [];
        $want = count($auditDef['actions'] ?? []);
        if (count($recorded) !== $want) { ++$truncated; echo "TRUNCATED $auditSlug: definition has $want actions, saved fixture has " . count($recorded) . "\n"; }
    }
    echo "$checked fixtures checked, $truncated truncated\n";
    exit($truncated === 0 ? 0 : 1);
}

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
        echo isset($behaviorCases[$slug]) ? "[DRY-RUN] Would run behavior family $slug\n" : "[DRY-RUN] Would create $fixtureDir\n";
        echo "  Deck: " . substr_count($def['deck'], "\n") . " lines\n";
        echo "  Actions: " . count($def['actions']) . "\n";
        echo "  Tested cards: " . implode(', ', $def['testedCards']) . "\n";
        $created++;
        continue;
    }

    $behaviorOnly = isset($behaviorCases[$slug]);

    // Clean existing fixture (keeping its hand-written assertions and meta unless --reset-meta)
    $keptAssertionsJson = null;
    $keptMeta = null;
    if (!$behaviorOnly && is_dir($fixtureDir)) {
        if (!$resetMeta) {
            if (is_file($fixtureDir . '/assertions.json')) {
                $decodedAssertions = json_decode(file_get_contents($fixtureDir . '/assertions.json'), true);
                if (is_array($decodedAssertions) && !empty($decodedAssertions)) $keptAssertionsJson = $decodedAssertions;
            }
            if (is_file($fixtureDir . '/meta.json')) {
                $decodedMeta = json_decode(file_get_contents($fixtureDir . '/meta.json'), true);
                if (is_array($decodedMeta)) $keptMeta = $decodedMeta;
            }
        }
        RegressionDeleteDirRecursive($fixtureDir);
    }
    if (!$behaviorOnly) RegressionEnsureDir($fixtureDir);

    $gameName = 'prd_batch_' . $slug . '_' . $seed . '_' . bin2hex(random_bytes(4));
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
        WriteGamestate('./' . $rootName . '/');
        ParseGamestate('./' . $rootName . '/');
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
                // 'deckTop': place the listed card IDs on TOP of the player's deck (first listed
                // card becomes the top card), mirroring PutTempZoneOnTopOfDeck(). BridgeAddToZone
                // can only append to the BOTTOM of a zone, so this is the only way to make the
                // card an "On Enter: look at the top card of your deck" effect will reveal a
                // deliberately chosen one. Setup precondition only; nothing fires.
                if (isset($setupStep['deckTop'])) {
                    EngineLoadRootRuntime($rootName);
                    ParseGamestate('./' . $rootName . '/');
                    $GLOBALS['playerID'] = $setupStep['player'] ?? 1;
                    $deckTopZone = &GetDeck($setupStep['player'] ?? 1);
                    foreach (array_reverse((array)$setupStep['deckTop']) as $topCardID) {
                        array_unshift($deckTopZone, new Deck($topCardID, 'Deck', $setupStep['player'] ?? 1));
                    }
                    for ($di = 0; $di < count($deckTopZone); ++$di) $deckTopZone[$di]->mzIndex = $di;
                    unset($deckTopZone);
                    WriteGamestate('./' . $rootName . '/');
                    echo "  Setup: deckTop " . implode(',', (array)$setupStep['deckTop']) . " (player " . ($setupStep['player'] ?? 1) . ")\n";
                    continue;
                }
                // 'emptyZone': directly move every live object out of a zone (default destination
                // myBanish) via repeated MZMove of the zone's first live entry -- used to reach a
                // "player has 0 cards in hand" precondition (e.g. Strike of Singularity's "attacking
                // a unit controlled by a player with no cards in their hand" clause) without
                // scripting a real multi-action hand-depletion sequence. Not a real gameplay event
                // (no discard/hand-empty trigger fires), same spirit as patchMzId/dqVariables above.
                if (isset($setupStep['emptyZone'])) {
                    EngineLoadRootRuntime($rootName);
                    ParseGamestate('./' . $rootName . '/');
                    $GLOBALS['playerID'] = $setupStep['player'] ?? 1;
                    $destZone = $setupStep['destZone'] ?? 'myBanish';
                    $moved = 0;
                    while (true) {
                        $liveZone = GetZone($setupStep['emptyZone']);
                        $foundIdx = null;
                        foreach ($liveZone as $zi => $zObj) {
                            if ($zObj !== null && empty($zObj->removed)) { $foundIdx = $zi; break; }
                        }
                        if ($foundIdx === null) break;
                        MZMove($setupStep['player'] ?? 1, $setupStep['emptyZone'] . '-' . $foundIdx, $destZone);
                        $moved++;
                        if ($moved > 200) break; // safety valve
                    }
                    WriteGamestate('./' . $rootName . '/');
                    echo "  Setup: emptyZone {$setupStep['emptyZone']} (player " . ($setupStep['player'] ?? 1) . ") moved $moved card(s) to $destZone\n";
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
        if (!$behaviorOnly) file_put_contents($fixtureDir . '/initial_gamestate.txt', $initialGamestate);
        if ($behaviorOnly) gaCheckBehaviorStep($def, $behaviorCases[$slug][0], 0);
        echo "  Prepared initial gamestate\n";

        // 6. Replay actions
        $replayedActions = [];
        foreach ($def['actions'] as $i => $action) {
            $result = EngineRunAction($action, $rootName, $gameName, [
                'updateCache' => false,
                'disableRecording' => true,
            ]);

            if ($behaviorOnly) gaCheckBehaviorStep($def, $behaviorCases[$slug][0], $i + 1);

            if (!empty($action['expectFailure'])) {
                // A deliberately-illegal action (semantic negative path): the engine must reject
                // it. Keep it in actions.json (the integration runner re-checks the rejection)
                // and keep replaying the legal actions that follow.
                if ($result['success']) {
                    echo "  [WARN] Action $i was expected to be rejected but succeeded\n";
                    echo "  Action: " . json_encode($action) . "\n";
                    // A scenario (strictActions) that expects a refusal the engine does not give has either a wrong step order or found an engine bug: the remaining actions used to be dropped silently.
                    if (!empty($def['strictActions'])) {
                        throw new \RuntimeException("Scenario action $i (" . json_encode($action['cardID'] ?? '') . ") was expected to be rejected but the engine accepted it -- " . ($action['label'] ?? 'no label'));
                    }
                    break;
                }
                $replayedActions[] = $action;
                echo "  Action $i rejected as expected (mode={$action['mode']}, card={$action['cardID']})\n";
                continue;
            }
            if (!$result['success']) {
                echo "  [WARN] Action $i failed: " . ($result['message'] ?? 'unknown') . "\n";
                echo "  Action: " . json_encode($action) . "\n";
                if (!empty($def['strictActions'])) {
                    throw new \RuntimeException("Scenario action $i (" . json_encode($action['cardID'] ?? '') . ") was refused: " . ($result['message'] ?? 'unknown') . " -- the remaining actions would have been dropped silently; fix the step order (use rejects() for an intended refusal)");
                }
                // Continue anyway - some actions may not be legal in all seeds
                break;
            }
            $replayedActions[] = $action;
            echo "  Action $i OK (mode={$action['mode']}, card={$action['cardID']})\n";
        }

        if ($behaviorOnly) {
            echo "[PASS] $slug (behavior family)\n";
            ++$created;
            RegressionDeleteDirRecursive($gameDir);
            continue;
        }

        // 7. Save fixture files
        file_put_contents(
            $fixtureDir . '/actions.json',
            json_encode($replayedActions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        file_put_contents(
            $fixtureDir . '/assertions.json',
            json_encode($def['assertions'] ?? $keptAssertionsJson ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $generatedMeta = [
            'name' => $slug,
            'rootName' => $rootName,
            'createdAt' => date('c'),
            'createdBy' => 'batch-script',
            'testedCards' => $def['testedCards'],
        ];
        if ($keptMeta !== null || !empty($def['meta'])) {
            // The earlier record wins (createdAt/createdBy, notes, semanticCoverage, engineBug, ...), except that a scenario-builder
            // definition's own notes / semanticCoverage ($def['meta']) win over it; the identity and the tested cards follow the definition.
            $generatedMeta = array_merge($generatedMeta, $keptMeta ?? [], $def['meta'] ?? [], [
                'name' => $slug,
                'rootName' => $rootName,
                'testedCards' => $def['testedCards'],
            ]);
        }
        file_put_contents(
            $fixtureDir . '/meta.json',
            json_encode($generatedMeta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
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
            // The first failing assertion ("Step 5 assertion failed: Expected ...") is the useful line; the directory is left in place for dump-fixture-state.php.
            foreach (preg_split('/\R/', (string)$verifyResult) as $failLine) {
                if (strpos($failLine, '[FAIL]') !== false) { echo "  " . trim($failLine) . "\n"; break; }
            }
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

exit($failed > 0 ? 1 : 0);
