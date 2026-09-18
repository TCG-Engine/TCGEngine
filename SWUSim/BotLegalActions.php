<?php
// Legal-action enumeration for the SWUSim bot. Two paths (spec Section 2):
//   1. A seat owes a DecisionQueue response -> delegate to DevTools/TestAutomationBridge.php,
//      which already encodes concrete answers for MZCHOOSE / MZMULTICHOOSE / MZSPLITASSIGN /
//      MZREARRANGE / modal / slider. SWUSim has no goldfish decision encoder of its own, which is
//      exactly why this delegates rather than reimplementing.
//   2. Free play -> enumerated natively below (Task 6), because the action grammar is SWU-specific.
//
// Returns raw ProcessInput.php-shaped {mode, cardID, playerID} pairs, matching
// AzukiRlBotLegalActions and GABotLegalActions, so one chooser format serves all three.

// Mirrors AzukiRlBotEnsureBridgeLoaded — the live in-game bot loading the bridge is established
// practice, not a test-only shortcut.
function SWUBotEnsureBridgeLoaded() {
    if (function_exists('BridgeEnumerateLegalActionsLoaded')) return true;
    if (!defined('TCGENGINE_BRIDGE_LIBRARY_ONLY')) define('TCGENGINE_BRIDGE_LIBRARY_ONLY', true);
    $bridgePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'DevTools' . DIRECTORY_SEPARATOR . 'TestAutomationBridge.php';
    if (!is_file($bridgePath)) return false;
    include_once $bridgePath;
    return function_exists('BridgeEnumerateLegalActionsLoaded');
}

// Whichever seat owes a non-empty DecisionQueue must respond before anything else can happen.
//
// Seat-generic on purpose. GA hardcodes foreach([1,2]); SWUSim's own comment at
// Custom/GameLogic.php:24140 records that this exact hardcode caused a glow/gate drift bug where
// a decision pending on seat 3 or 4 was invisible. Phase 1 is 2-player, but the loop is correct
// for any seat count so the bug family cannot reappear here.
function SWUBotPendingDecisionSeat() {
    if (!function_exists('GetDecisionQueue')) return 0;
    $seatCount = function_exists('GetSeatOrderArray') ? count(GetSeatOrderArray()) : 2;
    for ($seat = 1; $seat <= max(2, $seatCount); $seat++) {
        foreach (GetDecisionQueue($seat) as $entry) {
            if ($entry !== null && empty($entry->removed)) return $seat;
        }
    }
    return 0;
}

// Structured mirror of the error_log below. Lets the self-play harness assert "no unhandled
// decision gap occurred" without scraping log text — which is how we discover, empirically,
// which SWUSim decision types the bridge does not cover. The alternative is auditing 1,608 cards.
function SWUBotRecordUnrecognizedDecision($type, $param, $seat) {
    if (!isset($GLOBALS['SWUBotUnrecognizedDecisions'])) $GLOBALS['SWUBotUnrecognizedDecisions'] = [];
    $GLOBALS['SWUBotUnrecognizedDecisions'][] = [
        'type' => strval($type), 'param' => strval($param), 'seat' => intval($seat),
    ];
    error_log("SWUBot: unrecognized decision type '$type' (param='$param') for seat $seat.");
}

// Capped, flag-less split prompts — "pool|mz:cap&mz:cap&…" — are INDIRECT DAMAGE (SWUDealIndirectDamage,
// GameLogic.php): the damaged side's units capped at their remaining HP, the base LAST with cap = pool, and the
// pool is assigned in full (CR 35.3). The bridge reads ":cap" as a zone filter and drops every target, leaving
// "-" as the only candidate — so in self-play, indirect damage with 2+ targets never landed (diagnosis 2026-09-14:
// 186 prompts in 39 Boba games, 0 damage). Enumerate full assignments instead. Each target but the last takes
// 0, cap-1 (survives), cap (defeated) or all that is left — the amounts that change what happens — and the last
// target takes the remainder. Deterministic; at most SWU_BOT_SPLIT_MAX candidates.
const SWU_BOT_SPLIT_MAX = 300;
function _SWUBotCappedSplits(array $targets, int $pool): array {
    $out = [];
    $n = count($targets);
    $walk = function (int $i, int $left, array $picked) use (&$walk, &$out, $targets, $n) {
        if (count($out) >= SWU_BOT_SPLIT_MAX) return;
        [$mz, $cap] = $targets[$i];
        if ($i === $n - 1) {
            if ($left > $cap) return;
            if ($left > 0) $picked[] = "$mz:$left";
            if (!empty($picked)) $out[] = implode(',', $picked);
            return;
        }
        $amounts = array_values(array_unique(array_filter([0, $cap - 1, $cap, $left], fn($a) => $a >= 0 && $a <= min($cap, $left))));
        sort($amounts);
        foreach ($amounts as $a) $walk($i + 1, $left - $a, $a > 0 ? array_merge($picked, ["$mz:$a"]) : $picked);
    };
    if ($n > 0 && $pool > 0) $walk(0, $pool, []);
    return array_values(array_unique($out));
}

// SWUSim-side corrections to the shared bridge's answer encoding. DevTools/TestAutomationBridge.php is shared
// with the other sims and stays untouched by bot work (RL bots spec, Section 1), so its SWUSim-specific slips
// are corrected here, after it returns. Both were found by the real-deck self-play sweep (2026-09-13), where
// EVERY candidate the bridge built was refused by the engine:
//  1. MZSPLITASSIGN "total|spec&spec|FLAG" (FLAG = UPTO, "assign fewer than the total"). The bridge splits only
//     the FIRST '|', so the flag stuck to the last target ("mySpaceArena-0|UPTO") and every assignment naming
//     that target was invalid. 34 of 18,200 games stalled (Boba's Pilot split, Talzin's Advantage spread, …).
//  2. Subcard targets ("theirGroundArena-1.u0" — an upgrade on its host, see the subcard mzID addressing). The
//     bridge expands them like zone specs into "theirGroundArena-1.u0-0 … -N", none of which exist, so System
//     Shock-style prompts stalled or burned retries (~420 games: Boba, Piett, Luke, Ahsoka). Keep them verbatim.
//  3. Capped, flag-less splits (indirect damage) — see _SWUBotCappedSplits.
// If the bridge ever fixes these, they become no-ops.
function _SWUBotCorrectBridgeAnswers(string $type, string $param, array $actions, int $seat): array {
    if ($type === 'MZSPLITASSIGN' && preg_match('/^(\d+)\|([^|]*:\d+[^|]*)$/', $param, $m)) {
        $targets = [];
        foreach (array_filter(explode('&', $m[2]), fn($v) => $v !== '') as $spec) {
            $bits = explode(':', $spec);
            if (count($bits) === 2 && ctype_digit($bits[1])) $targets[] = [$bits[0], intval($bits[1])];
        }
        $splits = count($targets) >= 2 ? _SWUBotCappedSplits($targets, intval($m[1])) : [];
        if (!empty($splits)) {
            return array_map(fn($r) => ['playerID' => $seat, 'mode' => 100, 'buttonInput' => '', 'cardID' => $r, 'chkInput' => [], 'inputText' => ''], $splits);
        }
    }
    // A STEPPED split ("total|targets|MODE|STEP" — HMW_036 Kelnacca's strikes of its power, 2026-09-14): every
    // amount must be a multiple of STEP or the engine refuses it. Enumerate in STEPS (total/step points) through
    // the bridge's helpers, then scale each amount back up.
    if ($type === 'MZSPLITASSIGN' && preg_match('/^(\d+)\|(.*)\|([A-Za-z_]*)\|(\d+)$/', $param, $m) && intval($m[4]) > 1) {
        if (!function_exists('BridgeEnumerateSplitAssignResults') || !function_exists('BridgeExpandDecisionSpecChoices')) return $actions;
        $step = intval($m[4]);
        $build = function () use ($m, $step) {
            $choices = [];
            foreach (array_filter(explode('&', $m[2]), fn($v) => $v !== '') as $spec) {
                foreach (BridgeExpandDecisionSpecChoices(explode(':', $spec)[0]) as $c) $choices[] = $c;
            }
            return BridgeEnumerateSplitAssignResults(array_values(array_unique($choices)), intdiv(intval($m[1]), $step));
        };
        $results = function_exists('BridgeWithPlayerPerspective') ? BridgeWithPlayerPerspective($seat, $build) : $build();
        $scaled = [];
        foreach ((array)$results as $r) {
            $parts = [];
            foreach (explode(',', strval($r)) as $pair) {
                $b = explode(':', $pair);
                if (count($b) === 2) $parts[] = $b[0] . ':' . (intval($b[1]) * $step);
            }
            if (!empty($parts)) $scaled[] = implode(',', $parts);
        }
        return array_map(fn($r) => ['playerID' => $seat, 'mode' => 100, 'buttonInput' => '', 'cardID' => $r, 'chkInput' => [], 'inputText' => ''], $scaled);
    }
    if ($type === 'MZSPLITASSIGN') {
        // Rebuild from the prompt WITHOUT the flag, through the bridge's own public helpers, so only the input is
        // corrected and the enumeration stays the bridge's. (Patching the answers would not do: the bridge also
        // treats "spec|UPTO" as a zone and appends an index, yielding "theirSpaceArena-0|UPTO-0".)
        if (!preg_match('/^(\d+)\|(.*)\|([A-Za-z_]+)$/', $param, $m)) return $actions;
        if (!function_exists('BridgeEnumerateSplitAssignResults') || !function_exists('BridgeExpandDecisionSpecChoices')) return $actions;
        $build = function () use ($m) {
            $choices = [];
            foreach (array_filter(explode('&', $m[2]), fn($v) => $v !== '') as $spec) {
                foreach (BridgeExpandDecisionSpecChoices($spec) as $c) $choices[] = $c;
            }
            return BridgeEnumerateSplitAssignResults(array_values(array_unique($choices)), intval($m[1]));
        };
        $results = function_exists('BridgeWithPlayerPerspective') ? BridgeWithPlayerPerspective($seat, $build) : $build();
        return array_map(fn($r) => ['playerID' => $seat, 'mode' => 100, 'buttonInput' => '', 'cardID' => $r, 'chkInput' => [], 'inputText' => ''], (array)$results);
    }
    if ($type !== 'MZCHOOSE' && $type !== 'MZMAYCHOOSE') return $actions;
    $subcards = [];
    foreach (explode('&', $param) as $spec) {
        $spec = explode(':', $spec)[0];
        if (preg_match('/^(my|their)[A-Za-z]+-\d+\.u\d+$/', $spec)) $subcards[] = $spec;
    }
    if (empty($subcards)) return $actions;
    $out = []; $pass = null; $have = [];
    foreach ($actions as $a) {
        $c = strval($a['cardID'] ?? '');
        if (preg_match('/^(.+\.u\d+)-\d+$/', $c, $m) && in_array($m[1], $subcards, true)) continue;   // an invented id
        if ($c === 'PASS') { $pass = $a; continue; }
        $have[$c] = true; $out[] = $a;
    }
    foreach ($subcards as $sc) {
        if (!isset($have[$sc])) $out[] = ['playerID' => $seat, 'mode' => 100, 'buttonInput' => '', 'cardID' => $sc, 'chkInput' => [], 'inputText' => ''];
    }
    if ($pass !== null) $out[] = $pass;   // PASS stays last, as the bridge orders it
    return $out;
}

function SWUBotLegalActions($gameName, $seat) {
    $seat = intval($seat);
    $decisionSeat = SWUBotPendingDecisionSeat();

    if ($decisionSeat !== 0) {
        if ($decisionSeat !== $seat) {
            // Another seat owes the answer; this bot has nothing to do this poll.
            return ['success' => true, 'kind' => 'waiting-on-other-seat', 'playerID' => 0, 'actions' => []];
        }
        if (!SWUBotEnsureBridgeLoaded()) {
            return ['success' => false, 'kind' => 'bridge-unavailable', 'playerID' => 0, 'actions' => []];
        }
        $legal = BridgeEnumerateLegalActionsLoaded('SWUSim', strval($gameName));
        $actions = is_array($legal['actions'] ?? null) ? $legal['actions'] : [];
        $actions = _SWUBotCorrectBridgeAnswers(strval($legal['decisionType'] ?? ''), strval($legal['decisionParam'] ?? ''), $actions, $seat);
        if (empty($actions)) {
            $front = null;
            foreach (GetDecisionQueue($seat) as $entry) {
                if ($entry !== null && empty($entry->removed)) { $front = $entry; break; }
            }
            SWUBotRecordUnrecognizedDecision($front->Type ?? '', $front->Param ?? '', $seat);
        }
        // Decision CONTEXT for the choosers (RL bots Phase 1a, Task 2). Without it a chooser sees only
        // bare answers and cannot tell an attack-target prompt from an Ambush YES/NO, or know which unit
        // is attacking. decisionTooltip is the RAW underscored form ("Choose_an_attack_target") so rules
        // can match it exactly. 'following' = the params of up to 3 live entries queued BEHIND the head —
        // the continuation usually carries the context the prompt itself lacks
        // ("SWUResolveAttack|<attackerMz>", "SWUAmbushAnswer|<mz>|<targets>", "CREDIT_PAY|<max>|<cost>|…").
        $following = [];
        $seenHead = false;
        foreach (GetDecisionQueue($seat) as $entry) {
            if ($entry === null || !empty($entry->removed)) continue;
            if (!$seenHead) { $seenHead = true; continue; }        // the head is the decision itself
            $following[] = strval($entry->Param ?? '');
            if (count($following) >= 3) break;
        }
        return [
            'success'         => true,
            'kind'            => 'decision',
            'playerID'        => $seat,
            'actions'         => $actions,
            'decisionType'    => strval($legal['decisionType'] ?? ''),
            'decisionParam'   => strval($legal['decisionParam'] ?? ''),
            'decisionTooltip' => strval($legal['decisionTooltipRaw'] ?? ''),
            'following'       => $following,
        ];
    }

    return SWUBotFreePlayActions($gameName, $seat);
}

// Free play: everything the seat may legally do when no decision is pending.
//
// ⚠ Deliberately NOT using CanActivateCardForSelection(). Its first line is
//   if(!function_exists("CanActivateCard")) return true;
// and CanActivateCard() is defined NOWHERE in this repo — verified at runtime:
//   CanActivateCard => false, CanActivateCardForSelection => true.
// So it returns true unconditionally and its body is dead code. GA's bot filters its hand with
// it and consequently offers every card regardless of cost, leaning on the engine to reject —
// which is a large part of why GA needed its no-op retry loop. CanAffordActivationReserve() is
// SWUSim's real, maintained affordability gate: cost halving (JTL_105), Exploit fodder, HMW_125,
// play-blocked cards (SOR_062), can't-play-from-hand (SEC_053), and TOTAL payment capacity
// including Credits and SEC_122 Droids rather than a bare ready-resource count.
//
// ⚠ VERIFIED DEVIATIONS FROM THE TASK 6 BRIEF (see task-5-6-report.md for the full evidence
// trail — grep sources, line numbers, confidence):
//
// 1. cardID MUST end in "!FSM!", not just be the bare mzID. mode 10002's handler
//    (Core/EngineActionRunner.php case 10002) does `$inpArr = explode('!', $cardID)` and
//    switches on $inpArr[1] to decide what to do; only the literal 'FSM' branch calls
//    ActionMap($actionCard), which is what actually plays a card ($cardZone=="myHand") or
//    attacks (($cardZone=="myGroundArena"/"mySpaceArena"). A bare "myHand-3" cardID has no '!' at
//    all, so $actionValue is '', which hits the switch's default (no-op) — the action would be
//    SILENTLY SWALLOWED, never reaching ActionMap. Confirmed against SWUSim's own generated
//    client dispatch (GeneratedUI_20260903180200.js GetZoneClickActions() returns
//    [{"Action":"FSM","Parameters":[]}] for every zone, and Core/UILibraries20260918.js'
//    CardClick() builds the cardID as `cardId + "!" + Action + "!" + Parameters.join(",")`) and
//    against GrandArchiveSim/BotLegalActions.php, the sibling this file mirrors, which already
//    does exactly this ("myHand-$i!FSM!", "myField-$i!FSM!"). Fixed below.
//
// 2. The "resource a card" free-play action the brief specifies (mode 10003, cardID "myHand-$i")
//    does not exist as a free-play action AT ALL, and 10003 is not a resourcing mode — it is the
//    generic engine's version-save/load action (Core/EngineActionRunner.php case 10003:
//    SaveVersion/LoadVersion). Grepping SWUSim's own game logic shows resourcing is never reached
//    via ActionMap (its "myHand" case unconditionally calls SWUBeginPlayCard — i.e. every hand
//    click is a PLAY attempt, never a resource) or via any other mode/cardID dispatch. Instead,
//    per Schemas/SWUSim/TurnSchema.txt ("ResourcePhase: starting with the initiative holder, each
//    player may place 1 card from their hand into their resource zone... Players may decline.")
//    and SWUSim/Custom/GameLogic.php's ResourcePhase(), resourcing is offered EXACTLY ONCE per
//    player per round as an MZMAYCHOOSE DecisionQueue prompt during the Regroup Phase's Resource
//    step (continuation "SWUApplyRegroupResource") — never as a discretionary Action-Phase move.
//    That means resourcing is a DECISION, already fully handled by SWUBotLegalActions' decision
//    branch above (which delegates to the bridge's MZMAYCHOOSE encoder) — there is nothing left
//    for the free-play enumerator to do here. Emitting the brief's literal mode-10003 action would
//    not resource anything; it would silently hit the version-load branch instead. Omitted.
//
// 3. $obj->Exhausted does not exist on GroundArena/SpaceArena zone objects. The real property is
//    Status (Schemas/SWUSim/GameSchema.txt: "Status: 1 = ready, 0 = exhausted", also asserted by
//    ActionMap's own attack gate: `if (intval($obj->Status) !== 1) { ...break; }`). Fixed below to
//    check Status === 1 (ready) rather than a nonexistent Exhausted flag.
//
// ── Fix-round-1 additions (review findings, both independently verified) ───────────────────────
//
// 4. TURN-PLAYER / PHASE GATE. Every action ActionMap() and CustomWidgetInput() accept is itself
//    gated on `GetTurnPlayer() == $playerID` AND (for most zones) `GetCurrentPhase() == "MAIN"` —
//    see GameLogic.php's ActionMap() at :20147 (myHand) and :20166-20169 (arenas), and
//    CustomInput.php's "myHealth" Pass case at :8 (turn-player check) and :25 (MAIN-phase check).
//    Off-turn or off-phase, EVERY entry this function used to emit — pass included — was a no-op,
//    which is exactly the situation a retry loop must not spin in. Fixed: the function now checks
//    turn player + MAIN phase FIRST and returns the same 'waiting-on-other-seat' shape used by the
//    decision branch (empty actions) when either is false, instead of a list of guaranteed-rejected
//    actions.
//
// 5. LEADER DEPLOY / ABILITY, BASE ACTION, UNIT ACTIVATED ABILITY were missing from free play
//    entirely. Without leader deploy in particular, the bot can never get its leader onto the
//    board, making the deployed-leader attack the arena loop already supports unreachable. These
//    go through mode 10001 (CustomInput), not mode 10002 (FSM) — a different dispatch from the
//    play/attack actions above. Verb strings verified against the REAL CLIENT's SubmitInput calls
//    in SWUSim/Custom/GameLayoutShared.php (not guessed from CustomInput.php's case bodies, which
//    for myBase/myGroundArena/mySpaceArena ignore the verb text entirely and dispatch purely on
//    zone name — so the wire-format verb must come from what the client actually sends, since that
//    is the thing "matches how SWUSim's own client submits these actions"):
//      - GameLayoutShared.php:1958 `window.swuDoLeaderAction('DeployLeader:Unit', idx)` →
//        "myLeader-{i}!CustomInput!DeployLeader:Unit" (CustomInput.php's myLeader case at line 141
//        parses the ':Unit' suffix as $deployMode, defaulting to 'Unit' anyway if omitted — the
//        colon form is kept here to match the client's actual wire bytes exactly).
//      - GameLayoutShared.php:1956 `window.swuDoLeaderAction('LeaderAbility', idx)` →
//        "myLeader-{i}!CustomInput!LeaderAbility".
//      - GameLayoutShared.php:1975 `SubmitInput('10001', '...myBase-0!CustomInput!EpicAction')` →
//        the real verb is "EpicAction", NOT "SWUBaseAction" (that is the PHP function name
//        CustomInput.php's myBase case calls internally — CustomWidgetInput ignores the passed
//        $action string for this zone and calls SWUBaseAction() unconditionally, but the verb on
//        the wire, which is what a submitted action must reproduce, is "EpicAction").
//      - GameLayoutShared.php:2061 `SubmitInput('10001', '...!CustomInput!Activate')` (the
//        "Ability" button in showUnitActionMenu, for myGroundArena/mySpaceArena) → the real verb is
//        "Activate", NOT "SWUUnitAction" (again the PHP function name, not the wire verb;
//        CustomWidgetInput's arena case likewise ignores $action and calls SWUUnitAction()
//        unconditionally, but "Activate" is what actually gets sent).
//    All four use mode 10001, matching BridgePassActionForRoot's own mode for the same
//    "<mzID>!CustomInput!<verb>" shape.
//    Deliberately OUT of scope (Phase 2): Smuggle-from-resources (myResources-N), play-from-
//    discard, hand-activated abilities (myHand-N!CustomInput!Activate:K), and the blast / plan
//    counters (Twin Suns). The initiative counter was added by RL bots Phase 1a (see "Take the
//    initiative" below).
// An UPGRADE with no legal host is affordable but unplayable, and — uniquely among the doomed plays
// the enumerator can emit — attempting it is NOT a no-op.
//
// Found by DevTools/SWUSimBotSelfPlayTest.php (seed s02, first player 2): the bot played
// "myHand-3!FSM!" (SOR_136 Vader's Lightsaber) 596 times in a row on turn 1 with an empty board.
// ActivateCard's upgrade branch (Custom/GameLogic.php:15866) refuses with "No valid targets for
// upgrade." — but only AFTER the shared prologue at :15544-15550 has already written the 'played'
// game-log line, bumped telemetry and appended a SWU_CARDS_PLAYED GlobalEffect. That last one is
// real, unbounded gameplay state, so the comparable hash legitimately MOVES on every attempt: the
// no-op detector cannot fire, the exclude-and-retry loop never excludes the candidate, and
// 'first-legal' re-picks it forever. (ActivateCard's own comment at :15540 already records this
// deferred-payment residue as known and deliberate; correcting it is an engine change with card-level
// consequences and is NOT in this task's scope — it is written up in the Task 8 report instead.)
//
// CanAffordActivationReserve() cannot cover this: it is purely an affordability/permission gate (it
// checks the Piloting alternate cost precisely because that is a COST question), and SWUSim's own
// client hand-glow uses it unchanged — so a human sees this card lit too. The bot needs the stronger
// "can this play actually resolve" question, and SWUGetUpgradeValidTargets() is the maintained
// helper the play path itself calls one line before the refusal, so offer and outcome cannot drift.
//
// $discount is deliberately 0 here, matching the default play. ActivateCard passes a nonzero discount
// only for the aspect-penalty waivers (LOF_018 Anakin / TWI_040), where a host can be affordable
// post-waiver and not before — so in exactly that case this gate is slightly STRICTER than the play
// path and may skip an offer that would have worked. Being narrower is the safe direction (a missed
// option, never a stall), and it is confined to those two cards.
function SWUBotHandCardHasSomewhereToGo($seat, $obj) {
    if (!function_exists('CardType') || !function_exists('SWUGetUpgradeValidTargets')) return true;
    $cardID = strval($obj->CardID ?? '');
    if ($cardID === '') return true;
    if (stripos(strval(CardType($cardID)), 'upgrade') === false) return true;
    return !empty(SWUGetUpgradeValidTargets(intval($seat), $cardID, $obj, 0));
}

function SWUBotFreePlayActions($gameName, $seat) {
    $seat = intval($seat);
    $actions = [];

    // Everything below is gated on this being $seat's turn during the MAIN (action) phase — see
    // deviation #4 above. Every free-play action the engine accepts shares this gate, so failing it
    // means every candidate would be a guaranteed no-op; return the same empty-actions shape the
    // decision branch uses for "not my turn" rather than a list this seat cannot legally act on.
    if (!function_exists('GetTurnPlayer') || !function_exists('GetCurrentPhase')) {
        return ['success' => false, 'kind' => 'bridge-unavailable', 'playerID' => 0, 'actions' => []];
    }
    if (intval(GetTurnPlayer()) !== $seat || GetCurrentPhase() !== 'MAIN') {
        return ['success' => true, 'kind' => 'waiting-on-other-seat', 'playerID' => 0, 'actions' => []];
    }

    global $playerID;
    $savedPlayerID = $playerID ?? 0;
    $playerID = $seat; // mzID derivation is perspective-dependent

    // ── Plays from hand ──────────────────────────────────────────────────────
    $hand = GetHand($seat);
    if (function_exists('CanAffordActivationReserve')) {
        foreach ($hand as $i => $obj) {
            if ($obj === null || !empty($obj->removed)) continue;
            if (!CanAffordActivationReserve($seat, $obj)) continue;
            if (!SWUBotHandCardHasSomewhereToGo($seat, $obj)) continue;
            $actions[] = ['playerID' => $seat, 'mode' => 10002, 'cardID' => "myHand-$i!FSM!"];
        }
    }
    // else: fail closed — offer no hand plays rather than guess at affordability.

    // ── Resource a card ──────────────────────────────────────────────────────
    // Deliberately NOT enumerated here. See deviation #2 above: resourcing is offered only as an
    // MZMAYCHOOSE DecisionQueue prompt during the Regroup Phase's Resource step, so it is already
    // covered by SWUBotLegalActions()'s decision branch. There is no free-play "resource" action
    // in SWUSim's action grammar for this enumerator to add.

    // ── Everything else free play offers comes from SWUComputeActionsData() ───
    // Fix-round-2 (Task 8 self-play harness). The four families below — attacks, leader deploy,
    // leader ability, base Epic Action, unit activated abilities — were previously emitted
    // UNCONDITIONALLY, one entry per slot, and the engine was left to reject them. That is what
    // GA's bot does, and it is why GA needs a large no-op retry budget: a board with a leader, a
    // base and four units offered seven guaranteed-rejected candidates ahead of Pass, so a single
    // poll burned seven exclude-and-retry iterations before it did anything (measured: 7, against
    // the harness's budget of 5).
    //
    // SWUComputeActionsData(int $player) is SWUSim's own maintained answer to "what can this seat
    // do right now" — it is what the real client's glow/affordance layer renders from, so using it
    // here makes the bot's candidate set exactly the set of controls a human would see lit. It
    // already applies the same MAIN + turn-player + empty-DecisionQueue gate this function checked
    // above, and every per-family predicate behind it is the maintained one:
    //   leaderDeployByIndex  — per-leader (Twin Suns dual leaders), incl. the non-generic leaders
    //                          (SOR_094 Bail Organa's resource+hand requirement, TWI_017's no-deploy)
    //   leaderAbilityByIndex — per-leader epic/ability availability
    //   baseEpic             — _SWUBaseActionProviders(), the same list SWUBaseAction dispatches from
    //   unitActions          — SWUGetUnitActionProvider() + cost-kind Status gate +
    //                          SWUUnitActionAffordable() (a unit with no "Action [...]" at all is
    //                          simply absent, which is most units)
    //   attackers            — _SWUUnitCanAttackNow(): ready AND at least one legal target, so a
    //                          ready unit facing a board it cannot legally attack is not offered
    //
    // Hand plays deliberately keep their own CanAffordActivationReserve() gate above rather than
    // moving here: SWUComputeActionsData does not enumerate the hand (the client's hand glow is a
    // separate P#HANDGLOW channel), so this is not a duplicate path.
    //
    // Fail-open fallback: if SWUComputeActionsData() is unavailable, keep the previous
    // offer-everything behaviour rather than silently emitting nothing but Pass — a bot that can
    // only pass is a worse failure than a bot that retries.
    $haveActionsData = function_exists('SWUComputeActionsData');
    $actionsData = $haveActionsData ? SWUComputeActionsData($seat) : [];

    // ── Attacks: both arenas ──────────────────────────────────────────────────
    // Ground and space are separate spaces; a unit may only attack within its own arena. The mzIDs
    // in $actionsData['attackers'] are already player-relative ("myGroundArena-N"/"mySpaceArena-N").
    if ($haveActionsData) {
        foreach ((array)($actionsData['attackers'] ?? []) as $mz) {
            $actions[] = ['playerID' => $seat, 'mode' => 10002, 'cardID' => "$mz!FSM!"];
        }
    } else {
        foreach (['myGroundArena', 'mySpaceArena'] as $zoneName) {
            $zone = GetZone($zoneName);
            foreach ((array)$zone as $i => $obj) {
                if ($obj === null || !empty($obj->removed)) continue;
                if (intval($obj->Status ?? 0) !== 1) continue; // must be ready (Status 1) to attack
                $actions[] = ['playerID' => $seat, 'mode' => 10002, 'cardID' => "$zoneName-$i!FSM!"];
            }
        }
    }

    // ── Leader: deploy + leader ability ──────────────────────────────────────
    // Real client verbs (see deviation #5 above): "DeployLeader:Unit" and "LeaderAbility".
    //
    // ⚠ TWO INDEX FRAMES, latent divergence. $li below is the RAW array index into the myLeader zone,
    // and it is also what the wire form "myLeader-{i}" means (CustomInput.php's myLeader case reads
    // $cardArr[1] and hands it to SWUGetLeaderByIndex). But SWUComputeActionsData keys
    // leaderDeployByIndex / leaderAbilityByIndex by a COMPACTED LIVE index — its own loop
    // (Custom/GameLogic.php:19271-19303) carries a separate $liveIdx that only advances for leaders
    // it did not skip. The two frames agree exactly while no leader in the zone is skipped, which is
    // the case today: nothing in SWUSim sets ->removed on a leader (a defeated leader returns to the
    // zone rather than being tombstoned — see the leader-defeat paths), and a two-leader Twin Suns
    // seat holds both live. They would diverge the moment leader index 0 is skipped and index 1 is
    // not: this loop would then read index 1's raw slot against index 0's live entry and offer the
    // wrong leader's deploy. Left as a comment rather than a rewrite because the divergence
    // condition does not exist yet and inventing a mapping for it would be untestable — but if a
    // "leader removed from the zone" state is ever introduced, or SWUComputeActionsData's skip list
    // grows, this read site must be converted to the live frame at the same time.
    $leaders = GetZone('myLeader');
    foreach ((array)$leaders as $li => $lObj) {
        if ($lObj === null || !empty($lObj->removed)) continue;
        $canDeploy  = $haveActionsData ? !empty(($actionsData['leaderDeployByIndex']  ?? [])[$li]) : true;
        $canAbility = $haveActionsData ? !empty(($actionsData['leaderAbilityByIndex'] ?? [])[$li]) : true;
        if ($canDeploy)  $actions[] = ['playerID' => $seat, 'mode' => 10001, 'cardID' => "myLeader-$li!CustomInput!DeployLeader:Unit"];
        if ($canAbility) $actions[] = ['playerID' => $seat, 'mode' => 10001, 'cardID' => "myLeader-$li!CustomInput!LeaderAbility"];
    }

    // ── Base epic action ──────────────────────────────────────────────────────
    // Real client verb (see deviation #5 above): "EpicAction".
    $bases = GetZone('myBase');
    foreach ((array)$bases as $bi => $bObj) {
        if ($bObj === null || !empty($bObj->removed)) continue;
        if ($haveActionsData && empty($actionsData['baseEpic'])) continue;
        $actions[] = ['playerID' => $seat, 'mode' => 10001, 'cardID' => "myBase-$bi!CustomInput!EpicAction"];
    }

    // ── Unit activated ability ("Action [...]") ───────────────────────────────
    // Real client verb (see deviation #5 above): "Activate". Note this deliberately does NOT share
    // the attack loop's ready gate — a 'defeat'-cost Action (SOR_110 Frontline Shuttle) may be used
    // while exhausted, and SWUComputeActionsData applies the correct per-cost-kind rule already.
    //
    // Restricted to this seat's OWN units. SWUComputeActionsData also lists opponents' units that
    // carry an any-player Action (LAW_156 Hunter For Hire, SHD_256) as "their…"/"p{n}…" mzIDs; those
    // are a real, legal move the engine accepts, but offering them is a WIDENING of the Phase 1
    // action space, so it is recorded for Phase 2 rather than added here off the back of a
    // termination fix.
    if ($haveActionsData) {
        foreach ((array)($actionsData['unitActions'] ?? []) as $mz) {
            if (strpos(strval($mz), 'my') !== 0) continue;
            $actions[] = ['playerID' => $seat, 'mode' => 10001, 'cardID' => "$mz!CustomInput!Activate"];
        }
    } else {
        foreach (['myGroundArena', 'mySpaceArena'] as $zoneName) {
            $zone = GetZone($zoneName);
            foreach ((array)$zone as $i => $obj) {
                if ($obj === null || !empty($obj->removed)) continue;
                $actions[] = ['playerID' => $seat, 'mode' => 10001, 'cardID' => "$zoneName-$i!CustomInput!Activate"];
            }
        }
    }

    // ── Take the initiative ──────────────────────────────────────────────────
    // Deferred by Phase 1 (header note above); added for the RL bots spec's layer-2 rules 3 ("take the
    // initiative for guaranteed lethal next round") and 9 ("nothing left to do → take the initiative"),
    // which cannot fire while the bot has no way to take it. Wire form = the client's own
    // (SWUSim/Custom/GameLayoutShared.php:1418); handler = CustomWidgetInput's "InitiativeCounter" case
    // (SWUSim/Custom/CustomInput.php:28), which re-checks turn player, empty queues and MAIN. Only one
    // player may take it per round, recorded as GetInitiativeCounter() ending in "_CLAIMED"
    // (SWUTakeInitiative). Placed immediately BEFORE Pass so Pass stays the last, always-legal candidate
    // the retry loop relies on — and so 'first-legal' still prefers every real action to it.
    $initiativeCounter = strval(GetInitiativeCounter() ?? '');
    if (!str_ends_with($initiativeCounter, '_CLAIMED')) {
        $actions[] = ['playerID' => $seat, 'mode' => 10001, 'cardID' => 'InitiativeCounter-0!CustomInput!TakeInitiative'];
    }

    // ── Pass / end action ────────────────────────────────────────────────────
    // Inlined rather than gated on SWUBotEnsureBridgeLoaded(): BridgePassActionForRoot('SWUSim', …)
    // is a fixed 6-key constant (DevTools/TestAutomationBridge.php:951-966;
    // 'myHealth-0!CustomInput!Pass', mode 10001), so requiring the bridge to be loaded just to
    // build it made a bridge-load failure silently return an empty action list with
    // success => true. By the point this line runs we have already confirmed $seat is the turn
    // player and the phase is MAIN — the two gates CustomInput.php's "myHealth" case itself checks
    // (:8, :25) — so, absent a still-open effect stack (a rare case the retry loop can still
    // absorb), Pass is genuinely legal and unconditional here, which is what guarantees
    // BotController's retry loop terminates.
    $actions[] = [
        'playerID'    => $seat,
        'mode'        => 10001,
        'buttonInput' => '',
        'cardID'      => 'myHealth-0!CustomInput!Pass',
        'chkInput'    => [],
        'inputText'   => '',
    ];

    $playerID = $savedPlayerID;

    return [
        'success'  => true,
        'kind'     => 'free-play',
        'playerID' => $seat,
        'actions'  => $actions,
    ];
}
