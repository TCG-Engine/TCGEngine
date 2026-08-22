<?php

// ═══════════════════════════════════════════════════════════════════
// State Accessor classes — read live globals, never snapshot
// ═══════════════════════════════════════════════════════════════════

class UpgradeAccessor {
    private $obj;
    public function __construct($obj) { $this->obj = $obj; }

    public function __get(string $name) {
        switch ($name) {
            case 'cardID': return is_array($this->obj) ? $this->obj['CardID'] : $this->obj->CardID;
        }
        throw new RuntimeException("UpgradeAccessor: unknown property '$name'");
    }
}

class UnitAccessor {
    private $obj;
    public function __construct($obj) { $this->obj = $obj; }

    public function __get(string $name) {
        switch ($name) {
            case 'cardID': return $this->obj->CardID;
            case 'damage': return intval($this->obj->Damage);
        }
        throw new RuntimeException("UnitAccessor: unknown property '$name'");
    }

    public function isReady(): bool {
        return intval($this->obj->Status) === 1;
    }

    public function currentPower(): int {
        global $playerID;
        $saved = $playerID;
        $playerID = intval($this->obj->PlayerID);
        $v = ObjectCurrentPower($this->obj);
        $playerID = $saved;
        return intval($v);
    }

    public function currentHP(): int {
        global $playerID;
        $saved = $playerID;
        $playerID = intval($this->obj->PlayerID);
        $v = ObjectCurrentHP($this->obj);
        $playerID = $saved;
        return intval($v);
    }

    private function _upgrades(): array {
        return array_values(array_filter(
            (array)($this->obj->Subcards ?? []),
            fn($s) => is_array($s)
                ? (!isset($s['removed']) || !$s['removed'])
                : (!isset($s->removed) || !$s->removed)
        ));
    }

    public function upgradeCount(): int {
        return count($this->_upgrades());
    }

    public function upgrade(int $idx): UpgradeAccessor {
        $upgrades = $this->_upgrades();
        if (!isset($upgrades[$idx])) {
            throw new OutOfBoundsException(
                "No upgrade at index $idx (unit has " . count($upgrades) . " upgrades)"
            );
        }
        return new UpgradeAccessor($upgrades[$idx]);
    }

    // Dispatch to the generated HasKeyword_<Keyword>($obj) boolean (e.g. 'Sentinel', 'Raid').
    public function hasKeyword(string $keyword): bool {
        global $playerID;
        $fn = 'HasKeyword_' . $keyword;
        if (!function_exists($fn)) {
            throw new RuntimeException("UnitAccessor: no keyword function '$fn'");
        }
        $saved = $playerID;
        $playerID = intval($this->obj->PlayerID);
        $v = $fn($this->obj);
        $playerID = $saved;
        return (bool)$v;
    }

    // Object-aware trait check (dispatches to TraitContains so granted traits — e.g. the
    // Clone trait a TWI_116 copy gains via its IsClone flag — are honored, not just printed traits).
    public function hasTrait(string $trait): bool {
        global $playerID;
        $saved = $playerID;
        $playerID = intval($this->obj->PlayerID);
        $v = TraitContains($this->obj, $trait);
        $playerID = $saved;
        return (bool)$v;
    }

    // Returns true if this unit is a Leader Unit (deployed leader or host of a
    // leader-pilot that converts its host — see IsLeaderUnit in KeywordEffects.php).
    public function isLeaderUnit(): bool {
        global $playerID;
        $saved = $playerID;
        $playerID = intval($this->obj->PlayerID);
        $v = IsLeaderUnit($this->obj);
        $playerID = $saved;
        return (bool)$v;
    }
}

class LeaderAccessor {
    private $obj;
    public function __construct($obj) { $this->obj = $obj; }

    public function isReady(): bool        { return (bool)$this->obj->Ready; }
    public function isDeployed(): bool     { return (bool)$this->obj->Deployed; }
    public function epicActionUsed(): bool { return (bool)$this->obj->EpicActionUsed; }
}

class ArenaZoneAccessor {
    private array $items;

    public function __construct(array $zone) {
        $this->items = array_values(
            array_filter($zone, fn($o) => !isset($o->removed) || !$o->removed)
        );
    }

    public function count(): int { return count($this->items); }

    public function get(int $index): UnitAccessor {
        if (!isset($this->items[$index])) {
            throw new OutOfBoundsException(
                "No unit at index $index (arena has " . count($this->items) . " units)"
            );
        }
        return new UnitAccessor($this->items[$index]);
    }
}

class ZoneCountAccessor {
    protected array $items;

    public function __construct(array $zone) {
        $this->items = array_values(
            array_filter($zone, fn($o) => !isset($o->removed) || !$o->removed)
        );
    }

    public function count(): int { return count($this->items); }

    public function topCardID(): ?string {
        return isset($this->items[0]) ? $this->items[0]->CardID : null;
    }
}

class ResourceZoneAccessor extends ZoneCountAccessor {
    // Credit tokens (CR §3.13) live in the resource zone but are NOT resources, so they are
    // excluded from resource counts. _isCredit detects them by card type ("Credit Token").
    private static function _isCredit($o): bool {
        return CardType($o->CardID ?? '') === 'Credit Token';
    }
    public function count(): int {
        return count(array_filter($this->items, fn($o) => !self::_isCredit($o)));
    }
    public function readyCount(): int {
        return count(array_filter($this->items,
            fn($o) => !self::_isCredit($o) && intval($o->Status) === 1));
    }
    public function creditCount(): int {
        return count(array_filter($this->items, fn($o) => self::_isCredit($o)));
    }
}

class BaseAccessor {
    private $obj;
    public function __construct($obj) { $this->obj = $obj; }

    public function __get(string $name) {
        switch ($name) {
            case 'damage':        return intval($this->obj->Damage);
            case 'hp':            return intval(CardHp($this->obj->CardID));
            case 'epicActionUsed': return (bool)($this->obj->EpicActionUsed ?? false);
            // Remaining per-game uses of a repeatable base Action (e.g. LOF_022); 0 for non-action bases.
            case 'actionUsesLeft': return _SWUBaseActionUsesLeft($this->obj, $this->obj->CardID ?? '');
            // Attached FORTIFY upgrades. Absent Subcards (every base before Fortify) reads as none.
            // GetUpgradesOnUnit normalizes round-tripped ARRAY subcards to objects, so assertions
            // behave the same before and after a gamestate round-trip.
            case 'upgradeCount':   return count(GetUpgradesOnUnit($this->obj));
            case 'upgrades':       return GetUpgradesOnUnit($this->obj);
        }
        throw new RuntimeException("BaseAccessor: unknown property '$name'");
    }
}

class PlayerStateAccessor {
    private int $player;
    public function __construct(int $player) { $this->player = $player; }

    public function __get(string $name) {
        switch ($name) {
            case 'base': {
                $zone = GetBase($this->player);
                $live = array_values(array_filter($zone, fn($o) => !isset($o->removed) || !$o->removed));
                if (empty($live)) throw new RuntimeException("No base for player $this->player");
                return new BaseAccessor($live[0]);
            }
            case 'leader': {
                $obj = SWUGetLeader($this->player);
                if ($obj === null) throw new RuntimeException("No leader for player $this->player");
                return new LeaderAccessor($obj);
            }
            case 'hand':        return new ZoneCountAccessor(GetHand($this->player));
            case 'deck':        return new ZoneCountAccessor(GetDeck($this->player));
            case 'discard':     return new ZoneCountAccessor(GetDiscard($this->player));
            case 'resources':   return new ResourceZoneAccessor(GetResources($this->player));
            case 'groundArena': return new ArenaZoneAccessor(GetGroundArena($this->player));
            case 'spaceArena':  return new ArenaZoneAccessor(GetSpaceArena($this->player));
            case 'force':       return PlayerHasTheForce($this->player); // The Force (CR §37) player state
        }
        throw new RuntimeException("PlayerStateAccessor: unknown property '$name'");
    }
}

class GameStateAccessor {
    public function player(int $n): PlayerStateAccessor {
        return new PlayerStateAccessor($n);
    }

    public function pendingDecision(int $player): ?object {
        $queue = GetDecisionQueue($player);
        $live  = array_values(array_filter($queue, fn($o) => !isset($o->removed) || !$o->removed));
        return $live[0] ?? null; // DecisionQueue object with ->Type, ->Param, ->Tooltip
    }

    public function isGameOver(): bool {
        global $gWinner;
        return $gWinner !== null;
    }

    public function winner(): ?int {
        global $gWinner;
        return $gWinner;
    }

    public function currentPhase(): string {
        return strval(GetCurrentPhase());
    }

    public function initiativeCounter(): string {
        return strval(GetInitiativeCounter());
    }

    public function gameLog(): string {
        global $gGameLog;
        return $gGameLog ?? '';
    }
}

// ═══════════════════════════════════════════════════════════════════
// GameTestAdapter — action dispatch
// ═══════════════════════════════════════════════════════════════════

class GameTestAdapter {
    public GameStateAccessor $state;

    public function __construct() {
        $this->state = new GameStateAccessor();
    }

    /** Restore all globals from a builder snapshot and reset the accessor. */
    public function loadState(GameStateBuilder $state): void {
        $state->_applyToGlobals();
        // Start each test with a clean undo stack (now player 1's Versions zone, part of the gamestate).
        if (function_exists('UndoStackClear')) UndoStackClear();
        $this->state = new GameStateAccessor();
    }

    /**
     * Play the card at $handIndex from $player's hand.
     * Routes through ActionMap("myHand-{N}") — same path as a real click.
     */
    public function playCardFromHand(int $player, int $handIndex): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        ActionMap("myHand-{$handIndex}");
        $this->_drainDQ($player);
        ob_end_clean();
        $playerID = $saved;
    }

    /**
     * Declare an attack with $attackerMzID targeting $targetMzID.
     * Routes through ActionMap("{arena}-{N}") — the same FSM entry as a real click (mirrors
     * playCardFromHand). ActionMap enforces the server's action guards (turn player, MAIN phase,
     * unit ready, no pending decisions), so an out-of-turn or otherwise illegal attack no-ops
     * exactly as it would in a live game — and only THEN injects $targetMzID into the picker.
     *
     * $attackerMzID: relative mzID, e.g. "myGroundArena-0"
     * $targetMzID:   relative mzID, e.g. "theirGroundArena-0" or "theirBase-0"
     */
    public function declareAttack(int $player, string $attackerMzID, string $targetMzID): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        // ActionMap returns "ATTACK" only when the attack was actually declared (guards passed).
        // An illegal attack (e.g. not this player's turn) returns "" and is a no-op — leave the
        // gamestate untouched, exactly as the server would.
        if (ActionMap($attackerMzID) === "ATTACK") {
            $this->_drainDQ($player);
            // Inject the chosen defender ONLY into the attack-target picker — the MZCHOOSE BeginSWUAttack
            // queues when there are 2+ valid targets, tagged "Choose_an_attack_target". A single-valid-target
            // attack (e.g. only the base is attackable) runs ExecuteSWUAttack inline with NO picker, so the
            // pending MZCHOOSE here would be an ON-ATTACK ability's own choice (e.g. SOR_116's "+2/+2 to a
            // friendly unit"). Consuming THAT with the attack target silently mis-resolves the ability and
            // swallows the test's explicit AnswerDecision; leave it pending so the next WHEN line answers it.
            $pending = $this->state->pendingDecision($player);
            if ($pending !== null && $pending->Type === 'MZCHOOSE'
                && ($pending->Tooltip ?? '') === 'Choose_an_attack_target') {
                $dq = new DecisionQueueController();
                $dq->PopDecision($player);
                $dq->ExecuteStaticMethods($player, $targetMzID);
                $this->_drainDQ($player);
            }
        }
        ob_end_clean();
        $playerID = $saved;
    }

    /** Player passes their action (calls SWUPassAction directly). */
    public function passAction(int $player): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        SWUPassAction($player);
        $this->_drainDQ($player);
        ob_end_clean();
        $playerID = $saved;
    }

    /**
     * Simulate a mid-game undo: checkpoint the current state (SaveVersion) then immediately restore it
     * (LoadVersion) — the exact SaveVersion→LoadVersion round-trip a real undo performs. The board is
     * unchanged, but every zone object is now the one LoadVersion reconstructed. In a live game a disk
     * write + re-ParseGamestate follows every action and re-normalizes these objects, so this corruption
     * is invisible; an in-memory "Play All" replay skips that boundary and carries it into the next action.
     * Regression guard for the LoadVersion invariant that a unit keeps a RELATIVE Location ('GroundArena')
     * and its owner PlayerID — a bad LoadVersion leaves absolute 'p2GroundArena' / PlayerID 0, which later
     * makes SWUGetValidAttackTargets build a null zone and fatal on count(null).
     */
    public function undoCycle(int $player): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        SaveVersion($player);
        LoadVersion($player);
        ob_end_clean();
        $playerID = $saved;
    }

    /** Multi-step undo: revert one action (or, kind='phase', jump to the start of the current phase). */
    public function undo(int $player, string $kind = 'step'): void {
        global $playerID; $saved = $playerID; $playerID = $player;
        ob_start(); SWUDoUndo($player, $kind); $this->_drainDQ($player); ob_end_clean();
        $playerID = $saved;
    }
    /** Undo Phase — jump to the beginning of the current phase (post-resource first action). */
    public function undoPhase(int $player): void { $this->undo($player, 'phase'); }
    /** Opponent approves a pending undo request (public-queue consent flow). */
    public function approveUndo(int $player): void {
        global $playerID; $saved = $playerID; $playerID = $player;
        ob_start(); SWUApproveUndo(); $this->_drainDQ($player); ob_end_clean();
        $playerID = $saved;
    }
    /** Opponent denies a pending undo request. */
    public function denyUndo(int $player): void {
        ob_start(); SWUDenyUndo(); ob_end_clean();
    }

    /** Use a leader's action ability (exhausts the leader, fires its handler). */
    public function useLeaderAbility(int $player, int $leaderIndex = 0): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        $leaderArr = GetLeader($player);
        $live = array_values(array_filter($leaderArr, fn($o) => !isset($o->removed) || !$o->removed));
        if (isset($live[$leaderIndex])) {
            SWULeaderAction($player, $live[$leaderIndex]->CardID, $leaderIndex);
            $this->_drainDQ($player);
        }
        ob_end_clean();
        $playerID = $saved;
    }

    /** Use the base's Epic Action (calls SWUBaseAction). */
    public function useBaseAbility(int $player): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        SWUBaseAction($player);
        $this->_drainDQ($player);
        ob_end_clean();
        $playerID = $saved;
    }

    /** Use a unit's Action ability (calls SWUUnitAction). $mzID like "myGroundArena-0". */
    public function useUnitAbility(int $player, string $mzID): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        SWUUnitAction($player, $mzID);
        $this->_drainDQ($player);
        ob_end_clean();
        $playerID = $saved;
    }

    /** Deploy a leader to the ground arena via Epic Action. */
    public function deployLeader(int $player, int $leaderIndex = 0): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        SWUDeployLeader($player, 'Unit', '', $leaderIndex);
        $this->_drainDQ($player);
        ob_end_clean();
        $playerID = $saved;
    }

    /** Player claims initiative (calls SWUTakeInitiative directly). */
    public function takeInitiative(int $player): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        SWUTakeInitiative($player);
        $this->_drainDQ($player);
        ob_end_clean();
        $playerID = $saved;
    }

    /** Twin Suns: player takes a counter ('blast' or 'plan') via SWUTakeCounter. */
    public function takeCounter(int $player, string $which): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        SWUTakeCounter($player, $which);
        $this->_drainDQ($player);
        ob_end_clean();
        $playerID = $saved;
    }

    /** Twin Suns Phase 5 (test-only driver): directly eliminate a seat. $killer=null → no heal. */
    public function eliminateSeat(int $seat, ?int $killer = null): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $seat;
        ob_start();
        SWUEliminateSeat($seat, $killer);
        $this->_drainDQ($seat);
        ob_end_clean();
        $playerID = $saved;
    }

    /** Twin Suns Phase 5 (test-only driver): declare an explicit winner set. */
    public function declareWinners(array $seats): void {
        ob_start();
        SWUDeclareTwinSunsWinners($seats);
        ob_end_clean();
    }

    /** Twin Suns Phase 5 (test-only driver): run the deferred end-of-phase scoring pass. */
    public function scorePhaseEnd(): void {
        ob_start();
        _SWUScoreTwinSunsEndOfPhase();
        ob_end_clean();
    }

    /** Twin Suns Phase 5 (test-only driver): run RegroupPhaseStart (fires Final Showdown + scoring). */
    public function runRegroupStart(): void {
        ob_start();
        RegroupPhaseStart();
        ob_end_clean();
    }

    /** Play a card from $player's discard pile by index. */
    public function playFromDiscard(int $player, int $idx): void {
        CustomWidgetInput($player, "PlayFromDiscard-{$idx}");
        AutoAdvanceAndExecute();
    }

    /**
     * Play a card from an opponent's discard pile by index.
     * $ownerSeat (Twin Suns) names WHICH opponent's pile; null keeps the 2-player wire form byte-identical.
     * Without it a four-seat "the permission is on seat 3's pile" assertion could not be WRITTEN at all —
     * the third harness two-seat limit found by this sweep, after P#DISCARDUNIT and WithP3/P4Base.
     */
    public function playFromOpponentDiscard(int $player, int $idx, ?int $ownerSeat = null): void {
        $tok = ($ownerSeat !== null && $ownerSeat > 0)
             ? "PlayFromOpponentDiscard-{$ownerSeat}-{$idx}"
             : "PlayFromOpponentDiscard-{$idx}";
        CustomWidgetInput($player, $tok);
        AutoAdvanceAndExecute();
    }

    /** Play a card from $player's resource zone using Smuggle. $resourceIdx is 0-based. */
    public function smuggleResource(int $player, int $resourceIdx): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        SWUSmuggleResource($player, $resourceIdx);
        $this->_drainDQ($player);
        ob_end_clean();
        $playerID = $saved;
    }

    /** Resource the card at $handIndex from $player's hand. */
    public function resourceCard(int $player, int $handIndex): void {
        global $playerID;
        $saved = $playerID;
        $playerID = $player;
        ob_start();
        DoResourceCard($player, "myHand-{$handIndex}");
        $this->_drainDQ($player);
        ob_end_clean();
        $playerID = $saved;
    }

    /** Return the raw discard array for $player (including removed entries). */
    public function getDiscard(int $player): array {
        return GetDiscard($player);
    }

    public function getHand(int $player): array {
        return GetHand($player);
    }

    /** Return pilotPlayableHand indices for $player (no $active guard). */
    public function getPilotPlayableHand(int $player): array {
        return SWUComputePilotPlayableHand($player);
    }

    /**
     * Answer a pending interactive DQ decision.
     * Pops the front decision (MZCHOOSE, YESNO, etc.) and re-runs
     * ExecuteStaticMethods with $value as lastDecision — identical to mode=100.
     *
     * $value: mzID string for MZCHOOSE, 'YES'/'NO' for YESNO, number for NUMBERCHOOSE
     */
    public function answerDecision(int $player, string $value): void {
        // Mirror production's answer validation (EngineActionRunner mode=100): an answer outside the
        // pending choice's candidate pool is rejected there, so a test using one must fail LOUDLY here
        // rather than silently acting on an out-of-pool target (the hole that let a section "defeat"
        // a unit the offer never contained).
        if (function_exists('SWUValidateDecisionAnswer') && !SWUValidateDecisionAnswer($player, $value)) {
            $headDesc = '(empty queue)';
            foreach (GetDecisionQueue($player) as $d) {
                if (empty($d->removed)) {
                    $headDesc = ($d->Type ?? '?') . ' [' . substr((string)($d->Param ?? ''), 0, 160) . ']'
                              . ' tooltip=' . (string)($d->Tooltip ?? '');
                    break;
                }
            }
            throw new RuntimeException(
                "AnswerDecision '{$value}' is not a candidate of P{$player}'s pending decision: {$headDesc}");
        }
        ob_start();
        $dq = new DecisionQueueController();
        $dq->PopDecision($player);
        $dq->ExecuteStaticMethods($player, $value);
        $this->_mirrorProductionPostAction();
        ob_end_clean();
    }

    /**
     * Production runs ProcessGoldfishAutomation() after EVERY successful gamestate-writing engine action
     * — including answering a decision (Core/EngineActionRunner.php). Anything that function does after
     * the static drain must therefore also happen here, or the harness observes a state production never
     * reaches. Currently that is the deferred defeat-replacement flush: a "would be defeated → you may
     * instead …" replacement parked by a defeat that resolved inside a trigger/answer (rather than inside
     * the acting player's own SWUAfterAction) is only offered by this post-action flush. Without it the
     * unit is stranded in play, and a test would report that stranded state as correct.
     */
    private function _mirrorProductionPostAction(): void {
        SWUFlushDeferredReplacements();
    }

    private function _drainDQ(int $player): void {
        $dq = new DecisionQueueController();
        $dq->ExecuteStaticMethods($player, '-');
        $this->_mirrorProductionPostAction();
    }

    /**
     * Simulate an HTTP request boundary mid-decision. In production every interactive decision ends the
     * request; the next answer arrives in a genuinely fresh PHP process that (1) re-ParseGamestates the
     * serialized state (zones, decision queue, EffectStack, $gDecisionQueueVariables / SWUVars) from disk
     * and (2) starts every NON-serialized in-memory continuation global from scratch. A step-driven test
     * runs ONE process, so both effects are absent — masking two bug classes:
     *   • cross-decision state parked in a transient global instead of the serialized gamestate; and
     *   • state that survives serialization but is RECONSTRUCTED in a different order/shape on re-parse
     *     (e.g. combat/trigger orchestration whose after-action coordination is queue-ordering sensitive).
     *
     * Reproduce both: WriteGamestate → reset transient continuation globals → ParseGamestate — the exact
     * round-trip production performs at each boundary. A test that inserts this between an action and the
     * answer to its interactive decision exercises the real fresh-process boundary.
     */
    public function simulateRequestBoundary(): void {
        global $gameName, $gShootFirstPending, $gDeferredReplacements,
               $gSec035DefeatSnapshot, $gAsh195DefeatSnapshot, $gCombatDefeatByMz, $gPlayGrantedExploit,
               $gScryState, $gPendingEntryEffects, $gExploitDeferTriggers, $gExploitDeferredBag,
               $gLastPlayResourcesPaid, $gWDPowerSnapshot, $gLastIndirectUnitUIDs, $gLastIndirectBaseDmg,
               $gSec035AttackPower, $gLastExploitedPowers, $gCloneCopyCardID, $gGrantedBountySnapshot,
               $gPlayGrantTurnEffect, $gPlayGrantExp, $gPlayGrantShield, $gPlayGrantPrevent2,
               $gEntryPlayGrantTE, $gForceEnterReady, $gInCombatDamage;
        // Round-trip through a scratch dir under the system temp — never the repo working tree.
        // ⚠ The dir is scoped BY UID. The CLI runner runs as root (uid 0) while the zzRegressionSWUSim.php
        // endpoint runs as Apache's user (uid 33): whichever ran first owned a shared dir, and the other
        // then could not write to it. WriteGamestate's failure is invisible here (the ob_start/ob_end_clean
        // below swallows the warning), so ParseGamestate simply re-read stale state and EVERY
        // SimulateRequestBoundary test failed with "nothing happened" — 16 phantom product bugs that
        // reproduced only over HTTP. Per-uid paths mean the two runners can never collide.
        // ⚠ Scope by the PROCESS uid, not getmyuid() — getmyuid() is the uid of the currently executing
        // SCRIPT's OWNER, which is a property of the file on disk, not of who is running it. The two
        // runners therefore did NOT reliably get distinct paths: a root CLI run whose entry script was
        // owned by 33 created /tmp/swusim_request_boundary_33 as root:0755, and the Apache endpoint
        // (really uid 33) could then never write into its own directory — every boundary section failed
        // there while the CLI runner stayed green. posix_geteuid() is who we actually are.
        // ⚠ ALSO SCOPE BY PROCESS. uid alone is not enough: the CLI runner hardcodes
        // $gameName = 'test_runner', so EVERY concurrent runner under the same uid shared
        // .../swusim_request_boundary_<uid>/Games/test_runner and raced over one Gamestate.txt.
        // With parallel agents that means one agent's WriteGamestate is read back by another's
        // ParseGamestate — observed twice in one wave: a boundary section failed with a card
        // (SEC_081) that was never in its fixture, and another saw a buff silently not apply.
        // Both passed in isolation. A phantom product bug produced purely by test infrastructure,
        // which is the most expensive kind. The write and the read both happen inside THIS call,
        // so per-process scoping is safe and makes concurrent runs independent.
        $uid  = function_exists('posix_geteuid') ? posix_geteuid() : getmyuid();
        $pid  = function_exists('getmypid') ? intval(getmypid()) : 0;
        $base = rtrim(sys_get_temp_dir(), '/') . '/swusim_request_boundary_' . $uid . '_' . $pid . '/';
        $dir  = $base . "Games/{$gameName}";
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); @chmod($dir, 0777); }
        if (!is_dir($dir) || !is_writable($dir)) {
            // Fail LOUDLY rather than silently degrading into a fake state-loss bug.
            throw new RuntimeException("SimulateRequestBoundary: scratch dir '$dir' is not writable "
                . "(uid " . getmyuid() . ", sapi " . php_sapi_name() . ") — the boundary cannot be simulated.");
        }
        ob_start();
        WriteGamestate($base);                // 1) serialize (production writes on every pending-decision response)
        // 2) fresh process: EVERY non-serialized in-memory continuation global starts empty. This list must
        //    mirror the transient-global block in GameLogic.php (search `$gDeferredReplacements = $gDeferred`).
        //    Anything omitted here silently survives the boundary and hides a whole bug class — that is
        //    exactly how the JTL_094 pilot-replacement disappearance went unnoticed: only $gShootFirstPending
        //    was reset, so the pilot-replacement snapshot leaked across the boundary and every guard passed.
        // ── Added 2026-08-15 after an ORACLE AUDIT of the reset list (transient $gXxx globals that are
        //    neither reset here nor serialized in GamestateParser.php). Production loses ALL of these on
        //    every request; anything omitted here silently survives the boundary and hides a bug class.
        //    The three marked CROSSES are the JTL_094 shape — written, then read AFTER an interactive
        //    decision — so a boundary section written before this was hardened would pass VACUOUSLY.
        $GLOBALS['gSmuggleDeferred']        = null;   // CROSSES: written in SWUSmuggleResource, read by the deferHandler
        $GLOBALS['gShd001Pending']          = [];     // CROSSES: written in the defeat collector, read in a DQ handler
        $GLOBALS['gSWUWillrowPinnedCount']  = [];     // CROSSES: pinned count read across per-upgrade decisions
        $GLOBALS['gShd161DefeatOwner']      = [];     // defeat-time snapshot consumed at bounty-offer time
        $GLOBALS['gLastPlayedMzID']         = '';     // synchronous result channel; reset for faithfulness
        $GLOBALS['gPlayingEventCardID']     = '';
        $GLOBALS['gShd010Recollecting']     = false;
        $GLOBALS['gSimulDefeatWindow']      = false;
        // Its PAIR. The read in _SWUSimulObserverCount is gated on the window above, so leaving this set
        // is harmless TODAY — but production loses both at every boundary, and a reset window paired with
        // a stale snapshot is exactly the half-cleared shape the JTL_094 leak had. Reset for faithfulness.
        unset($GLOBALS['gSimulDefeatUnits']);
        $GLOBALS['gTwi040IgnoreAspect']     = false;
        unset($GLOBALS['gSimulDefeatSidious']);
        $gShootFirstPending    = null;
        $gDeferredReplacements = [];
        $gSec035DefeatSnapshot = [];
        $gAsh195DefeatSnapshot = [];
        $gCombatDefeatByMz     = [];
        $gPlayGrantedExploit   = 0;
        // Hardened 2026-08-14: the list above covered 6 of the ~20 transient continuation globals, so a
        // guard could sit on a card whose state crosses the boundary in one of the OTHERS and still pass —
        // vacuously. Everything below is in-memory only (absent from GamestateParser.php, which serializes
        // just $gPendingTriggers/$gTriggerDepth and resets both on parse), so production starts each one
        // empty at every boundary. Keep this in sync with the transient-global block in GameLogic.php.
        $gScryState             = null;   // peeked cards live HERE, spliced OUT of the deck — see DoScry
        $gPendingEntryEffects   = [];
        $gExploitDeferTriggers  = false;
        $gExploitDeferredBag    = [];
        $gLastPlayResourcesPaid = 0;
        $gWDPowerSnapshot       = [];
        $gLastIndirectUnitUIDs  = [];
        $gLastIndirectBaseDmg   = 0;
        $gSec035AttackPower     = [];
        $gLastExploitedPowers   = [];
        $gCloneCopyCardID       = null;
        $gGrantedBountySnapshot = [];
        $gPlayGrantTurnEffect   = null;
        $gPlayGrantExp          = null;
        $gPlayGrantShield       = null;
        $gPlayGrantPrevent2     = null;
        $gEntryPlayGrantTE      = '';
        $gForceEnterReady       = false;
        $gInCombatDamage        = false;
        ParseGamestate($base);                // 3) re-parse — repopulates ONLY the serialized state
        ob_end_clean();
    }

    /**
     * Public drain — run pending STATIC decisions (RESOLVE_TRIGGER / CUSTOM / SYSTEM) on $player's
     * queue without popping or answering anything, stopping at the first interactive decision.
     *
     * Needed for cross-player reactions: the harness only drains the ACTING player's queue after
     * each action, but a trigger belonging to the NON-acting player (e.g. a unit's When Defeated
     * whose controller is the opponent that just got its unit killed) is left as a static
     * RESOLVE_TRIGGER at the front of that player's queue. In production EngineActionRunner drains
     * both queues (ProcessGoldfishAutomation) after every action; a step-driven test mirrors that
     * one player at a time via the `Drain` WHEN verb, then answers the interactive follow-up.
     */
    public function drainQueue(int $player): void {
        ob_start();
        $this->_drainDQ($player);
        ob_end_clean();
    }
}
