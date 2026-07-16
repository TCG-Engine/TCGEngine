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

    // Object-aware trait check (dispatches to _SWUUnitHasTrait so granted traits — e.g. the
    // Clone trait a TWI_116 copy gains via its IsClone flag — are honored, not just printed traits).
    public function hasTrait(string $trait): bool {
        global $playerID;
        $saved = $playerID;
        $playerID = intval($this->obj->PlayerID);
        $v = _SWUUnitHasTrait($this->obj, $trait);
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

    /** Play a card from the opponent's discard pile by index. */
    public function playFromOpponentDiscard(int $player, int $idx): void {
        CustomWidgetInput($player, "PlayFromOpponentDiscard-{$idx}");
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
        ob_start();
        $dq = new DecisionQueueController();
        $dq->PopDecision($player);
        $dq->ExecuteStaticMethods($player, $value);
        ob_end_clean();
    }

    private function _drainDQ(int $player): void {
        $dq = new DecisionQueueController();
        $dq->ExecuteStaticMethods($player, '-');
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
