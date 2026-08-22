<?php
// Core/DecisionQueueController.php
// Helper class for managing player decision queues in the game engine.
// ASSUMES: You have a per-player zone named "DecisionQueue"

// MZResolveObject() (subcard-aware mzID resolution) lives here and is called by MZCountChoices /
// MZFirstChoiceMzID below. Declare the dependency rather than relying on the caller's include order:
// this controller is reached from BOTH entry paths, and only one of them loads CoreZoneModifiers.
// ProcessInput.php includes it explicitly, but NextTurn.php does NOT — it reaches this class
// transitively (GamestateParser.php -> TurnController.php -> here), so in the render path
// GetZoneObject() is defined and MZResolveObject() would not be. include_once is idempotent, and
// CoreZoneModifiers' only top-level statement is its own include of DeterministicRNG.php (pure
// function definitions), so pulling it in early has no side effects for any of the 8 apps.
include_once __DIR__ . '/CoreZoneModifiers.php';

class DecisionQueueController {
    // ⚠ WAS the literal 2, declared here and assigned NOWHERE — so AllQueuesEmpty() could not see a
    // pending decision on seat 3 or 4. Every action/phase gate in the engine asks that one question
    // (TurnController's PENDING_DECISION, ~10 sites in CustomInput.php, SimHistory), so in a Twin Suns
    // game the whole "wait for everyone" interlock degraded to "wait for seats 1 and 2": the regroup
    // advanced to the next action phase while a later seat still owed a resource, and seat 1 got to
    // act first. Bug report #978 / game 3497.
    // Derived per call rather than stored: seat count is gamestate, and a controller instance can
    // outlive a parse. GetSeatOrderArray() lives in SWUSim's GameLogic and is NOT loaded by every app
    // that uses this Core class, hence the function_exists guard — a root without it keeps the old
    // 2-seat answer, which is correct for every non-Twin-Suns app.
    private function SeatCount(): int {
        if (function_exists('GetLiveSeatsArray')) {
            $n = count(GetLiveSeatsArray());
            if ($n >= 2) return $n;
        }
        if (function_exists('SeatCountForGame')) {
            $n = SeatCountForGame();
            if ($n >= 2) return $n;
        }
        return 2;
    }
    private static $debugMode = false;
    private static $executeDepth = 0;
    private static $executePlayerStack = [];
    private static $suspendAutoAdvanceDepth = 0;
    // Optional per-game predicate. While it returns true, AddDecision is a no-op — used by a game to
    // stop work that is still unwinding from the effect which ENDED the game from queuing anything
    // further. Default null, so a game that never registers one is completely unaffected.
    //
    // ⚠ A PREDICATE, not a sticky flag, and deliberately so: it must be re-evaluated per game state.
    // A static bool would leak across the many games a single process handles (the test harness runs
    // an entire suite in one process — one game ending would silence every game after it).
    // SWUSim registers `fn() => SWUGetGameWinner() !== 0`, which reads the SERIALIZED winner, so it is
    // automatically correct for each parsed gamestate with nothing to reset.
    private static $suppressNewDecisionsCheck = null;

    public function __construct() {

    }

    public static function SetSuppressNewDecisionsCheck(?callable $fn) { self::$suppressNewDecisionsCheck = $fn; }

    // Returns true if EVERY seat's queue is empty (2 in a normal game, 3-4 in Twin Suns).
    public function AllQueuesEmpty() {
        $seats = $this->SeatCount();
        for($i=1; $i<=$seats; ++$i) {
            $playerQueue = &GetDecisionQueue($i);
            if(!empty($playerQueue)) {
                return false;
            }
        }
        return true;
    }

    // Returns true if ANY seat has a pending decision.
    // ⚠ This called AllQueuesEmpty() as a bare FUNCTION rather than $this->AllQueuesEmpty() — an
    // instant fatal for any caller. There are none today (grepped), which is why it never surfaced;
    // fixed in passing so it is not a trap for the next person who reaches for it.
    public function AnyQueuePending() {
        return !$this->AllQueuesEmpty();
    }

    // Get the next decision for a player (returns null if none)
    public function NextDecision($player) {
        $playerQueue = &GetDecisionQueue($player);
        if (!empty($playerQueue)) {
            return $playerQueue[0];
        }
        return null;
    }

    // Remove the first decision for a player (after processing)
    public function PopDecision($player) {
        $playerQueue = &GetDecisionQueue($player);
        if (!empty($playerQueue)) {
            return array_shift($playerQueue);
        }
        return null;
    }

    function ExecuteStaticMethods($player, $lastDecision = null) {
        $activePlayer = end(self::$executePlayerStack);
        if($activePlayer !== false && intval($activePlayer) === intval($player)) {
            return;
        }

        self::$executeDepth++;
        self::$executePlayerStack[] = intval($player);
        $shouldAutoAdvance = true;
        try {
            while($decision = $this->NextDecision($player)) {
                if(self::$debugMode) echo("Processing decision for player " . $player . ": " . $decision->Type . " " . $decision->Param . " Last decision: " . $lastDecision . "<BR>");
                $this->PopDecision($player);
                switch($decision->Type) {
                    case "PASSPARAMETER":
                        $lastDecision = $decision->Param;
                        break;
                    case "MZMOVE":
                        if($lastDecision == "PASS" && !$decision->DontSkipOnPass) break;
                        $resolvedParam = str_replace("{<-}", $lastDecision, $decision->Param);
                        $parts = explode("->", $resolvedParam);
                        $source = $parts[0];
                        $destination = explode("-", $parts[1])[0];
                        MZMove($player, $source, $destination);
                        break;
                    case "CUSTOM":
                        if($lastDecision == "PASS" && !$decision->DontSkipOnPass) break;
                        global $customDQHandlers;
                        $parts = explode("|", $decision->Param);
                        $handlerName = array_shift($parts);
                        $customDQHandlers[$handlerName]($player, $parts, $lastDecision);
                        break;
                    case "SYSTEM":
                        if($lastDecision == "PASS" && !$decision->DontSkipOnPass) break;
                        global $systemDQHandlers;
                        $parts = explode("|", $decision->Param);
                        $handlerName = array_shift($parts);
                        $systemDQHandlers[$handlerName]($player, $parts, $lastDecision);
                        break;
                    default:
                        // Not static, return
                        if($decision->Type == "MZCHOOSE") { //We need to validate every decision type separately
                            // Use the new counting method that handles both zones and specific cards
                            $numChoices = $this->MZCountChoices($decision->Param);
                            if($numChoices === 0) {
                                // No valid choices, auto-PASS
                                $lastDecision = "PASS";
                                break;
                            }
                        } else if($decision->Type == "MZMULTICHOOSE") {
                            $paramParts = explode("|", strval($decision->Param), 3);
                            $choiceSpecs = count($paramParts) >= 3 ? $paramParts[2] : "";
                            $numChoices = $this->MZCountChoices($choiceSpecs);
                            if($numChoices === 0) {
                                $lastDecision = "-";
                                break;
                            }
                        }
                        // Put it back at the front
                        $playerQueue = &GetDecisionQueue($player);
                        array_unshift($playerQueue, $decision);
                        if(self::$debugMode) echo("Re-adding decision to player " . $player . " queue: " . $decision->Type . " " . $decision->Param . "<BR>");
                        $shouldAutoAdvance = false;
                        return;
                }
            }
        } finally {
            array_pop(self::$executePlayerStack);
            self::$executeDepth--;
            // Only the outermost execution frame may auto-advance phases.
            if($shouldAutoAdvance && self::$executeDepth === 0 && self::$suspendAutoAdvanceDepth === 0) {
                AutoAdvance();
            }
        }
    }

    public static function SuspendAutoAdvance() {
        self::$suspendAutoAdvanceDepth++;
    }

    public static function ResumeAutoAdvance() {
        if(self::$suspendAutoAdvanceDepth > 0) {
            self::$suspendAutoAdvanceDepth--;
        }
    }

    // Add a decision to a player's queue
    public static function AddDecision($player, $type, $param = '', $block = 0, $tooltip = '', $dontSkipOnPass = 0) {
        if(self::$suppressNewDecisionsCheck !== null
            && (self::$suppressNewDecisionsCheck)()) return;   // result is final — queue nothing further
        // Per-SEAT gate (multiplayer): never queue onto a seat that can no longer answer. A decision on
        // an eliminated seat is not merely a lost trigger — nothing drains it, so any flow that waits on
        // that queue SOFT-LOCKS the table. Resolved by function_exists rather than a registered callback
        // on purpose: the setter pattern above only re-registers in the request that declares a winner,
        // whereas this must hold in EVERY request, and a plain function is automatically a no-op for
        // sims that define none.
        if(function_exists('SWUSeatAcceptsDecisions') && !SWUSeatAcceptsDecisions($player)) return;
        // A DecisionQueue row is SPACE-DELIMITED — ZoneClasses' constructor does explode(" ", $line) with
        // Tooltip at index 3 and DontSkipOnPass at 4 — so a raw space in the tooltip would truncate it to
        // its first word and shift every field after it. Normalising here is what lets card files write
        // prompts as ordinary prose; the client turns the underscores back into spaces at render
        // (Tooltip.replace(/_/g,' ') at every prompt site).
        // ⚠ This guard covers the TOOLTIP ONLY. $param is written into the same space-delimited row and is
        // NOT sanitised, because a space there is unrecoverable rather than cosmetic — it cannot be told
        // apart from the field separator. Anything living in the param (OPTIONCHOOSE / YESNO option
        // labels, the ~BUDGET~ and ~REQ~ side channels) must still be underscored by its caller.
        $tooltip = str_replace(' ', '_', $tooltip);
        $playerQueue = &GetDecisionQueue($player);
        $insertIndex = 0;
        for($i = 0; $i < count($playerQueue); $i++) {
            if($playerQueue[$i]->Block > $block) {
                break;
            }
            $insertIndex = $i + 1;
        }
        if(self::$debugMode) echo("Adding decision to player " . $player . " queue: " . $type . " " . $param . " Block: " . $block . " at index " . $insertIndex . "<BR>");
        array_splice($playerQueue, $insertIndex, 0, [new DecisionQueue($type . " " . $param . " " . $block . " " . $tooltip . " " . $dontSkipOnPass)]);
    }

    private function MZZoneArray($zoneStr) {
        $zones = explode("&", $zoneStr);
        $output = [];
        for($i=0; $i<count($zones); ++$i) {
            $zone = explode(":", $zones[$i]);
            $zoneOrCard = $zone[0];
            // Check if this is a specific card reference (zoneName-index, or the subcard form
            // zoneName-index.uSub — see MZParseSubcardID). Both live in the HOST's zone.
            if (preg_match('/^(.+)-(\d+)(?:\.u(\d+))?$/', $zoneOrCard, $matches)) {
                // It's a specific card reference - extract zone name
                $output[] = $matches[1];
            } else {
                // It's a zone reference
                $output[] = $zoneOrCard;
            }
        }
        return $output;
    }
    
    // Parse zone string into array of specs with zone names and optional specific indices
    // Returns array of ['zone' => 'zoneName', 'specificIndex' => int|null]
    private function MZParseSpecs($zoneStr) {
        $zones = explode("&", $zoneStr);
        $output = [];
        for($i=0; $i<count($zones); ++$i) {
            $zone = explode(":", $zones[$i]);
            $zoneOrCard = $zone[0];
            // Check if this is a specific card reference (zoneName-index), optionally addressing a
            // SUBCARD of it (zoneName-index.uSub — an upgrade/token/captive; see MZParseSubcardID).
            // A subcard spec keeps its host's zone + index so every zone-level consumer (which zone
            // to light up, which host card the choice sits on) keeps working unchanged; `subIndex`
            // is the extra addressing the renderer and the answer validator use.
            if (preg_match('/^(.+)-(\d+)(?:\.u(\d+))?$/', $zoneOrCard, $matches)) {
                // It's a specific card reference
                $output[] = [
                    'zone' => $matches[1],
                    'specificIndex' => intval($matches[2]),
                    'subIndex' => (isset($matches[3]) && $matches[3] !== '') ? intval($matches[3]) : null,
                    'original' => $zoneOrCard
                ];
            } else {
                // It's a zone reference
                $output[] = [
                    'zone' => $zoneOrCard,
                    'specificIndex' => null,
                    'subIndex' => null,
                    'original' => $zoneOrCard
                ];
            }
        }
        return $output;
    }
    
    // Count available choices from a zone string (handles both zones and specific cards)
    private function MZCountChoices($zoneStr) {
        $specs = $this->MZParseSpecs($zoneStr);
        $numChoices = 0;
        foreach($specs as $spec) {
            if ($spec['specificIndex'] !== null) {
                // Specific card - validate the actual slot. Live-zone counts cannot
                // validate sparse arrays because a removed earlier slot shifts the
                // relationship between an index and the number of live objects.
                // MZResolveObject (not GetZoneObject) so a subcard spec validates the
                // SUBCARD's liveness, not merely its host's.
                $obj = MZResolveObject($spec['original']);
                if ($obj !== null && !(isset($obj->removed) && $obj->removed)) {
                    $numChoices += 1;
                }
            } else {
                // Whole zone - count all cards
                $numChoices += MZZoneCount($spec['zone']);
            }
        }
        return $numChoices;
    }
    
    // Returns the first valid mzID from an MZCHOOSE param string, or null if none.
    private function MZFirstChoiceMzID($zoneStr) {
        foreach ($this->MZParseSpecs($zoneStr) as $spec) {
            if ($spec['specificIndex'] !== null) {
                $obj = MZResolveObject($spec['original']);
                if ($obj !== null && !(isset($obj->removed) && $obj->removed)) return $spec['original'];
            } else {
                if (MZZoneCount($spec['zone']) > 0) return $spec['zone'] . '-0';
            }
        }
        return null;
    }

    /**
     * Auto-resolve any MZCHOOSE decision that has exactly one valid choice.
     * Iterates both players' queues until no single-choice decisions remain.
     * Returns the number of decisions auto-resolved.
     * Called by the Test Schema Editor after each step execution.
     */
    public function AutoResolveSingleChoiceDecisions(int $maxIterations = 30): int {
        $totalResolved = 0;
        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $anyResolved = false;
            for ($p = 1; $p <= 2; $p++) {
                $queue = &GetDecisionQueue($p);
                if (empty($queue)) continue;
                $decision = $queue[0];
                if ($decision->Type !== 'MZCHOOSE') continue;
                if ($this->MZCountChoices($decision->Param) !== 1) continue;
                $choice = $this->MZFirstChoiceMzID($decision->Param);
                if ($choice === null) continue;
                $this->PopDecision($p);
                $this->ExecuteStaticMethods($p, $choice);
                $anyResolved = true;
                $totalResolved++;
            }
            if (!$anyResolved) break;
        }
        return $totalResolved;
    }

    // Variable storage for await syntax using DecisionQueueVariables zone
    /**
     * Decode the decision-queue variable store.
     *
     * ⚠ ONE slot, historically TWO encodings. This JSON map is the canonical format, but SWUSim's
     * SetSWUVar/GetSWUVar used to write the same slot as pipe text ("PASS=0|UNDO_DENY_COUNT_4=0").
     * Each writer destroyed the other's keys: json_decode on a pipe string fails and restarted from
     * [], while the pipe parser found no "|" in JSON and rewrote the slot as pipe. A value written
     * ONCE and read later — the game-over winner — could silently vanish between the two (this is
     * what made every seat in a finished Twin Suns game see "You Lost").
     * Both writers now emit JSON; this decoder still accepts the legacy pipe form so games saved
     * before the unification keep loading.
     */
    private static function DecodeVariables($raw) {
        $raw = (string)$raw;
        if (trim($raw) === '') return [];
        $json = json_decode($raw, true);
        if (is_array($json)) return $json;
        // ── legacy pipe form ──
        if (preg_match('/^\d+$/', trim($raw))) return ['PASS' => trim($raw)]; // oldest form: bare PASS count
        $out = [];
        foreach (explode('|', $raw) as $pair) {
            $kv = explode('=', $pair, 2);
            if (count($kv) === 2) $out[$kv[0]] = $kv[1];
        }
        return $out;
    }

    public static function StoreVariable($name, $value) {
        $vars = self::DecodeVariables(GetDecisionQueueVariables());
        $vars[$name] = $value;
        SetDecisionQueueVariables(json_encode($vars));
    }

    public static function GetVariable($name) {
        $vars = self::DecodeVariables(GetDecisionQueueVariables());
        return $vars[$name] ?? null;
    }

    /** Shared decoder for callers that keep their own accessor pair (SWUSim's GetSWUVar/SetSWUVar). */
    public static function DecodeVariablesPublic($raw) { return self::DecodeVariables($raw); }

    public static function ClearVariable($name) {
        $vars = json_decode(GetDecisionQueueVariables(), true);
        if (!is_array($vars)) return;
        unset($vars[$name]);
        SetDecisionQueueVariables(json_encode($vars));
    }
    
    public static function ClearVariables() {
        SetDecisionQueueVariables('{}');
    }

    private static function SanitizeAwaitFrameValue($value) {
        if ($value === null || is_scalar($value)) return $value;
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $child) {
                if (is_object($child) || is_resource($child)) continue;
                $out[$key] = self::SanitizeAwaitFrameValue($child);
            }
            return $out;
        }
        return null;
    }

    private static function SanitizeAwaitFrameLocals($locals) {
        $out = [];
        if (!is_array($locals)) return $out;
        foreach ($locals as $name => $value) {
            $name = strval($name);
            if ($name === '') continue;
            $out[$name] = self::SanitizeAwaitFrameValue($value);
        }
        return $out;
    }

    public static function BeginAwaitFrame($prefix, $locals = []) {
        $vars = json_decode(GetDecisionQueueVariables(), true);
        if (!is_array($vars)) $vars = [];
        if (!isset($vars['__awaitFrames']) || !is_array($vars['__awaitFrames'])) {
            $vars['__awaitFrames'] = [];
        }
        $nextId = intval($vars['__awaitNextID'] ?? 1);
        $safePrefix = preg_replace('/[^A-Za-z0-9_.:-]/', '_', strval($prefix));
        $frameKey = $safePrefix . '#' . $nextId;
        $vars['__awaitNextID'] = $nextId + 1;
        $vars['__awaitFrames'][$frameKey] = [
            'locals' => self::SanitizeAwaitFrameLocals($locals),
        ];
        SetDecisionQueueVariables(json_encode($vars));
        return $frameKey;
    }

    public static function GetAwaitFrame($frameKey) {
        $vars = json_decode(GetDecisionQueueVariables(), true);
        if (!is_array($vars)) return null;
        $frames = $vars['__awaitFrames'] ?? null;
        if (!is_array($frames)) return null;
        $frame = $frames[strval($frameKey)] ?? null;
        return is_array($frame) ? $frame : null;
    }

    public static function UpdateAwaitFrame($frameKey, $locals = []) {
        $vars = json_decode(GetDecisionQueueVariables(), true);
        if (!is_array($vars)) $vars = [];
        if (!isset($vars['__awaitFrames']) || !is_array($vars['__awaitFrames'])) {
            $vars['__awaitFrames'] = [];
        }
        $frameKey = strval($frameKey);
        $existing = $vars['__awaitFrames'][$frameKey]['locals'] ?? [];
        if (!is_array($existing)) $existing = [];
        $vars['__awaitFrames'][$frameKey] = [
            'locals' => array_merge($existing, self::SanitizeAwaitFrameLocals($locals)),
        ];
        SetDecisionQueueVariables(json_encode($vars));
    }

    public static function SetAwaitFrameLocal($frameKey, $name, $value) {
        self::UpdateAwaitFrame($frameKey, [strval($name) => $value]);
    }

    public static function FinishAwaitFrame($frameKey) {
        $vars = json_decode(GetDecisionQueueVariables(), true);
        if (!is_array($vars)) return;
        if (isset($vars['__awaitFrames']) && is_array($vars['__awaitFrames'])) {
            unset($vars['__awaitFrames'][strval($frameKey)]);
            if (empty($vars['__awaitFrames'])) {
                unset($vars['__awaitFrames']);
                unset($vars['__awaitNextID']);
            }
        }
        SetDecisionQueueVariables(json_encode($vars));
    }
    
    public static function CleanupRemovedCards() {
        $allZones = GetAllZones();
        foreach ($allZones as $zoneName) {
            $zone = &GetZone($zoneName);
            if (!is_array($zone)) continue;
            
            // Physically remove cards marked as removed (reverse iteration to safely splice)
            for ($i = count($zone) - 1; $i >= 0; $i--) {
                if (isset($zone[$i]) && method_exists($zone[$i], 'Removed') && $zone[$i]->Removed()) {
                    array_splice($zone, $i, 1);
                }
            }
            
            // Rebuild mzIndex values and indexed properties for remaining cards
            for ($i = 0; $i < count($zone); $i++) {
                if (isset($zone[$i])) {
                    $zone[$i]->mzIndex = $i;
                    $zone[$i]->BuildIndex();
                }
            }
        }
    }
}
