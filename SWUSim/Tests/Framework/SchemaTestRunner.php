<?php

// ═══════════════════════════════════════════════════════════════════
// SchemaTestRunner — parses GIVEN/WHEN/EXPECT .ref.md files and
// drives GameTestAdapter to run a full or partial SWUSim game.
//
// Pregame (MulliganNo/MulliganYes/ResourceHand commands before the
// first MAIN-phase action) is handled via Option B: the builder
// computes the post-pregame state directly, skipping the DQ chain.
// This requires homogeneous or positionally deterministic decks.
// ═══════════════════════════════════════════════════════════════════

class SchemaRunResult {
    public bool   $passed;
    public string $message;
    public array  $failedExpects;
    public int    $actionsExecuted;

    public function __construct(bool $passed, string $message, array $failedExpects = [], int $actionsExecuted = 0) {
        $this->passed          = $passed;
        $this->message         = $message;
        $this->failedExpects   = $failedExpects;
        $this->actionsExecuted = $actionsExecuted;
    }

    public static function failure(string $msg): self {
        return new self(false, $msg);
    }

    public static function success(int $actionCount): self {
        return new self(true, "All assertions passed ({$actionCount} actions executed).", [], $actionCount);
    }
}

class SchemaTestRunner {

    // ── Public API ───────────────────────────────────────────────────

    /**
     * Run a schema file given a path relative to the repo root.
     */
    public static function runFile(string $repoRelativePath): SchemaRunResult {
        $root     = dirname(__DIR__, 3); // Framework → Tests → SWUSim → repo root
        $fullPath = $root . DIRECTORY_SEPARATOR . ltrim($repoRelativePath, '/\\');
        if (!is_file($fullPath)) {
            return SchemaRunResult::failure("Schema file not found: {$repoRelativePath}");
        }
        return self::runString(file_get_contents($fullPath), basename($repoRelativePath));
    }

    /**
     * Run a schema given its raw markdown content.
     */
    public static function runString(string $content, string $label = 'schema'): SchemaRunResult {
        $parsed = self::_parse($content);
        if (!$parsed['ok']) return SchemaRunResult::failure($parsed['error']);

        ['given' => $givenLines, 'when' => $whenLines, 'expect' => $expectLines] = $parsed;

        // Classify WHEN actions: pregame (Mulligan*/ResourceHand before first MAIN action) vs main phase.
        ['pregame' => $pregame, 'main' => $mainActions] = self::_splitPregame($whenLines);

        // Build post-pregame state and load it.
        $builder = self::_buildInitialState($givenLines, $pregame);
        $g = new GameTestAdapter();
        $g->loadState($builder);

        // Advance to MAIN (AutoAdvance fires ActionPhaseStart before the APS→MAIN transition).
        if (!$g->state->isGameOver()) {
            ob_start(); AutoAdvanceAndExecute(); ob_end_clean();
        }

        // Apply post-setup state overrides (must run after AutoAdvanceAndExecute to avoid
        // being overwritten by phase transition logic).
        self::applyPostSetupDirectives($givenLines);

        // Execute main-phase actions.
        foreach ($mainActions as $idx => $action) {
            try {
                self::_execute($g, $action);
            } catch (Throwable $e) {
                $label = "Action " . ($idx + 1) . " [{$action['raw']}]";
                $trace = implode("\n  ", array_map(
                    fn($f) => ($f['file'] ?? '?') . ':' . ($f['line'] ?? '?') . ' → ' . ($f['function'] ?? '?'),
                    array_slice($e->getTrace(), 0, 8)
                ));
                $loc   = $e->getFile() . ':' . $e->getLine();
                return SchemaRunResult::failure("{$label} failed: " . $e->getMessage() . "\n  at {$loc}\n  " . $trace);
            }
        }

        // Evaluate EXPECT assertions.
        $failures = self::_evalExpect($g, $expectLines);
        if (!empty($failures)) {
            return new SchemaRunResult(false, implode("\n", $failures), $failures, count($mainActions));
        }

        return SchemaRunResult::success(count($mainActions));
    }

    /**
     * Split a schema file into one or more test segments.
     *
     * Segments are delimited by a markdown horizontal rule — a line that is
     * exactly "---". Each segment may begin with a single-"#" header giving the
     * test name (TitleCase); "##" section headers are NOT names. A file with no
     * "---" is a single segment (the legacy one-test-per-file case): its optional
     * "#" header is ignored by the parser and the name comes from the filename.
     *
     * @return array<int, array{name: ?string, content: string}>
     */
    public static function splitSegments(string $content): array {
        $chunks   = preg_split('/^\s*---\s*$/m', $content);
        $segments = [];
        foreach ($chunks as $chunk) {
            if (trim($chunk) === '') continue; // ignore empties from leading/trailing/doubled "---"
            // First single-"#" header anywhere in the segment is its name.
            // Excludes "##" section headers and "#//" comments.
            $name = null;
            if (preg_match('/^[ \t]*#(?!#|\/\/)[ \t]*(.+?)[ \t]*$/m', $chunk, $m)) {
                $name = trim($m[1]);
            }
            $segments[] = ['name' => $name, 'content' => $chunk];
        }
        if (empty($segments)) $segments[] = ['name' => null, 'content' => $content];
        return $segments;
    }

    // ── Parsing ──────────────────────────────────────────────────────

    private static function _parse(string $content): array {
        $sections = ['given' => [], 'when' => [], 'expect' => []];
        $current  = null;
        $braceBuf = null;   // non-null while accumulating a multi-line "{ ... }" block (e.g. CommonSetup opts)

        foreach (explode("\n", $content) as $raw) {
            $line = trim($raw);
            if ($braceBuf === null) {
                if ($line === '## GIVEN')  { $current = 'given';  continue; }
                if ($line === '## WHEN')   { $current = 'when';   continue; }
                if ($line === '## EXPECT') { $current = 'expect'; continue; }
            }
            if ($current === null) continue;
            // Strip inline comments.
            $clean = trim(preg_replace('/#.*$/', '', $line));

            // Mid-block: keep folding lines into one logical line until braces balance.
            if ($braceBuf !== null) {
                if ($clean !== '') $braceBuf .= ' ' . $clean;
                if (substr_count($braceBuf, '{') <= substr_count($braceBuf, '}')) {
                    $sections[$current][] = trim($braceBuf);
                    $braceBuf = null;
                }
                continue;
            }

            if ($clean === '') continue;
            // A line that opens more '{' than it closes starts a multi-line block.
            if (substr_count($clean, '{') > substr_count($clean, '}')) {
                $braceBuf = $clean;
                continue;
            }
            $sections[$current][] = $clean;
        }

        // Unterminated block: flush what we have so it surfaces downstream instead of vanishing.
        if ($braceBuf !== null && $current !== null) $sections[$current][] = trim($braceBuf);

        return ['ok' => true] + $sections;
    }

    private static function _parseGiven(array $lines): array {
        // These keys can appear more than once; their values accumulate as arrays.
        static $multiKeys = ['WithP1GroundArena',        'WithP2GroundArena',
                             'WithP3GroundArena',         'WithP4GroundArena',
                             'WithP1SpaceArena',          'WithP2SpaceArena',
                             'WithP3SpaceArena',          'WithP4SpaceArena',
                             'WithP1Hand',                'WithP2Hand',
                             'WithP3Hand',                'WithP4Hand',
                             'WithP1Discard',             'WithP2Discard',
                             'WithP3Discard',             'WithP4Discard',
                             'WithP1GroundArenaUpgrade',  'WithP2GroundArenaUpgrade',
                             'WithP1SpaceArenaUpgrade',   'WithP2SpaceArenaUpgrade',
                             'WithP1GroundArenaPilot',    'WithP2GroundArenaPilot',
                             'WithP1SpaceArenaPilot',     'WithP2SpaceArenaPilot',
                             'WithP1Deck',                'WithP2Deck',
                             'WithP3Deck',                'WithP4Deck'];
        // List-valued keys accept either one spec per line OR a bracketed, whitespace-separated
        // array on a single line — e.g. "WithP2Deck: [SOR_225 SEC_080 SOR_128]" or
        // "WithP1GroundArena: [ASH_048:1:0 SEC_098:0:3]". Each token becomes its own accumulated
        // entry, so both forms (and a mix) interoperate. Arena/upgrade specs never contain spaces,
        // so whitespace-splitting is safe for them too (a bare single spec splits to one token).
        static $listKeys = ['WithP1Hand', 'WithP2Hand', 'WithP3Hand', 'WithP4Hand',
                            'WithP1Discard', 'WithP2Discard', 'WithP3Discard', 'WithP4Discard',
                            'WithP1Deck', 'WithP2Deck', 'WithP3Deck', 'WithP4Deck',
                            'WithP1GroundArena', 'WithP2GroundArena', 'WithP1SpaceArena', 'WithP2SpaceArena',
                            'WithP3GroundArena', 'WithP4GroundArena', 'WithP3SpaceArena', 'WithP4SpaceArena',
                            'WithP1GroundArenaUpgrade', 'WithP2GroundArenaUpgrade',
                            'WithP1SpaceArenaUpgrade', 'WithP2SpaceArenaUpgrade',
                            'WithP1GroundArenaPilot', 'WithP2GroundArenaPilot',
                            'WithP1SpaceArenaPilot', 'WithP2SpaceArenaPilot'];
        $out = [];
        foreach ($lines as $line) {
            if (!str_contains($line, ':')) continue;
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            if (in_array($k, $listKeys, true)) {
                foreach (self::_parseDeckList($v) as $cid) $out[$k][] = $cid;
            } elseif (in_array($k, $multiKeys, true)) {
                $out[$k][] = $v;
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    private static function _parseDeckList(string $val): array {
        $val = trim($val, '[] ');
        return array_values(array_filter(preg_split('/\s+/', $val)));
    }

    private static function _parseWhenLine(string $line): ?array {
        // "- P{N}>{Command}" or "- P{N}>{Command}:{args}"
        $stripped = ltrim($line, '- ');
        if (!preg_match('/^P(\d+)>(\w+)(?::(.*))?$/', $stripped, $m)) return null;
        return ['raw' => $line, 'player' => intval($m[1]), 'cmd' => $m[2], 'args' => trim($m[3] ?? '')];
    }

    /**
     * Pregame = any leading Mulligan/ResourceHand actions before the first MAIN action.
     * In-game ResourceHand/ResourcePass (regroup phase) appear after at least one MAIN command
     * and are NOT classified as pregame.
     */
    private static function _splitPregame(array $whenLines): array {
        static $pregameCmds = ['MulliganNo', 'MulliganYes', 'ResourceHand'];
        $pregame = [];
        $main    = [];
        $inMain  = false;

        foreach ($whenLines as $line) {
            $action = self::_parseWhenLine($line);
            if ($action === null) continue;

            if (!$inMain && in_array($action['cmd'], $pregameCmds, true)) {
                $pregame[] = $action;
            } else {
                $inMain = true;
                $main[] = $action;
            }
        }
        return ['pregame' => $pregame, 'main' => $main];
    }

    // ── State Setup ──────────────────────────────────────────────────

    /**
     * SkipPreGame: true  — state is defined entirely by GIVEN directives; no drew-6 math.
     *                      P1Deck/P2Deck describe the literal current deck contents.
     * SkipPreGame: false — Option B: count pregame ResourceHand actions to derive
     *                      post-draw hand/resource/deck counts (homogeneous deck assumed).
     */
    private static function _buildInitialState(array $givenLines, array $pregameActions): GameStateBuilder {
        $given      = self::_parseGiven($givenLines);
        $skipPre    = strtolower($given['SkipPreGame'] ?? 'false') === 'true';
        if (isset($given['CommonSetup'])) $skipPre = true;

        [$p1LeaderSpec, $p1BaseSpec] = array_pad(explode('/', $given['P1LeaderBase'] ?? '/'), 2, '');
        [$p2LeaderSpec, $p2BaseSpec] = array_pad(explode('/', $given['P2LeaderBase'] ?? '/'), 2, '');
        [$p1Leader, $p1LeaderReady, $p1LeaderDeployed, $p1LeaderEpic] = self::_parseLeaderSpec($p1LeaderSpec);
        [$p2Leader, $p2LeaderReady, $p2LeaderDeployed, $p2LeaderEpic] = self::_parseLeaderSpec($p2LeaderSpec);
        [$p1BaseID, $p1BaseDmg, $p1BaseEpic] = self::_parseBaseSpec($p1BaseSpec);
        [$p2BaseID, $p2BaseDmg, $p2BaseEpic] = self::_parseBaseSpec($p2BaseSpec);

        $p1Deck     = self::_parseDeckList($given['P1Deck'] ?? '');
        $p2Deck     = self::_parseDeckList($given['P2Deck'] ?? '');
        $initPlayer = intval($given['WithInitiativePlayer'] ?? $given['InitChoice'] ?? 1);
        $initClaimed = strtolower($given['WithInitiativeClaimed'] ?? 'false') === 'true';

        $p1Card = $p1Deck[0] ?? '';
        $p2Card = $p2Deck[0] ?? '';

        if ($skipPre) {
            // Deck list is the literal current deck — no drew-6 adjustment.
            $p1HandCount = 0;
            $p2HandCount = 0;
            $p1DeckLeft  = count($p1Deck);
            $p2DeckLeft  = count($p2Deck);
            $resourced   = [1 => 0, 2 => 0];
        } else {
            // Post-pregame: drew D, resourced N → hand = D-N, resources = N, deck = total-D.
            // D defaults to 6 but a player's base may modify the opening draw (JTL_021/028);
            // SWUStartingHandModifier is the same helper production's QueuePregameSetup uses.
            $resourced = [1 => 0, 2 => 0];
            foreach ($pregameActions as $a) {
                if ($a['cmd'] === 'ResourceHand') $resourced[$a['player']]++;
            }
            $p1Drew = max(0, 6 + SWUStartingHandModifier($p1BaseID));
            $p2Drew = max(0, 6 + SWUStartingHandModifier($p2BaseID));
            $p1HandCount = max(0, $p1Drew - $resourced[1]);
            $p2HandCount = max(0, $p2Drew - $resourced[2]);
            $p1DeckLeft  = max(0, count($p1Deck) - $p1Drew);
            $p2DeckLeft  = max(0, count($p2Deck) - $p2Drew);
        }

        // Start in APS so AutoAdvanceAndExecute fires ActionPhaseStart before MAIN begins.
        $b = (new GameStateBuilder())
            ->WithActivePlayer($initPlayer)
            ->WithInitiativePlayerBeing($initPlayer)
            ->WithGamePhase('APS')
            ->WithCurrentRoundBeing(1);

        if (!isset($given['CommonSetup'])) {
            $b->MyBase($p1BaseID, $p1BaseDmg, $p1BaseEpic)
              ->MyLeader($p1Leader, $p1LeaderReady, $p1LeaderDeployed, $p1LeaderEpic)
              ->TheirBase($p2BaseID, $p2BaseDmg, $p2BaseEpic)
              ->TheirLeader($p2Leader, $p2LeaderReady, $p2LeaderDeployed, $p2LeaderEpic);
        }

        if ($initClaimed) $b->WithInitiativeClaimed();

        if (isset($given['CommonSetup'])) {
            $csParts     = explode('/', $given['CommonSetup'], 3);
            $csMyCode    = trim($csParts[0] ?? 'grw');
            $csTheirCode = trim($csParts[1] ?? 'grw');
            $csOptsRaw   = trim($csParts[2] ?? '');
            [$csMyOpts, $csTheirOpts] = self::_parseCommonSetupOpts($csOptsRaw);
            CommonSetup($b, $csMyCode, $csTheirCode, $csMyOpts, $csTheirOpts);
        }

        if ($resourced[1] > 0 && $p1Card !== '') $b->FillResourcesForPlayer(1, $p1Card, $resourced[1], true);
        if ($resourced[2] > 0 && $p2Card !== '') $b->FillResourcesForPlayer(2, $p2Card, $resourced[2], true);

        for ($i = 0; $i < $p1HandCount && $p1Card !== ''; $i++) $b->WithCardInHandForPlayer(1, $p1Card);
        for ($i = 0; $i < $p2HandCount && $p2Card !== ''; $i++) $b->WithCardInHandForPlayer(2, $p2Card);

        for ($i = 0; $i < $p1DeckLeft && $p1Card !== ''; $i++) $b->WithCardInDeckForPlayer(1, $p1Card);
        for ($i = 0; $i < $p2DeckLeft && $p2Card !== ''; $i++) $b->WithCardInDeckForPlayer(2, $p2Card);

        // Arena units from GIVEN directives.
        foreach ($given['WithP1GroundArena'] ?? [] as $spec) {
            [$cid, $ready, $dmg, $te] = self::_parseUnitSpec($spec);
            $b->WithGroundUnitForPlayer(1, $cid, $ready, $dmg, 0, $te);
        }
        foreach ($given['WithP2GroundArena'] ?? [] as $spec) {
            [$cid, $ready, $dmg, $te] = self::_parseUnitSpec($spec);
            $b->WithGroundUnitForPlayer(2, $cid, $ready, $dmg, 0, $te);
        }
        foreach ($given['WithP1SpaceArena'] ?? [] as $spec) {
            [$cid, $ready, $dmg, $te] = self::_parseUnitSpec($spec);
            $b->WithSpaceUnitForPlayer(1, $cid, $ready, $dmg, 0, $te);
        }
        foreach ($given['WithP2SpaceArena'] ?? [] as $spec) {
            [$cid, $ready, $dmg, $te] = self::_parseUnitSpec($spec);
            $b->WithSpaceUnitForPlayer(2, $cid, $ready, $dmg, 0, $te);
        }
        // Twin Suns seats 3/4 — plain arena units for storage-layer tests.
        foreach ([3, 4] as $seat) {
            foreach ($given["WithP{$seat}GroundArena"] ?? [] as $spec) {
                [$cid, $ready, $dmg, $te] = self::_parseUnitSpec($spec);
                $b->WithGroundUnitForPlayer($seat, $cid, $ready, $dmg, 0, $te);
            }
            foreach ($given["WithP{$seat}SpaceArena"] ?? [] as $spec) {
                [$cid, $ready, $dmg, $te] = self::_parseUnitSpec($spec);
                $b->WithSpaceUnitForPlayer($seat, $cid, $ready, $dmg, 0, $te);
            }
        }
        // Twin Suns seats 3/4 bases (WithP{n}Base: CARDID[:damage]) — seats 1/2 come from CommonSetup.
        foreach ([3, 4] as $seat) {
            if (!isset($given["WithP{$seat}Base"])) continue;
            [$bcid, $bdmg] = array_pad(explode(':', trim($given["WithP{$seat}Base"]), 2), 2, '0');
            $b->WithBaseForPlayer($seat, trim($bcid), intval($bdmg));
        }
        // Twin Suns seats 3/4 leaders (WithP{n}Leader / WithP{n}Leader2: CARDID[:ready[:deployed[:epicUsed]]])
        // — seats 1/2 come from CommonSetup. Undeployed leaders only (no deployed-unit splice for 3/4).
        foreach ([3, 4] as $seat) {
            if (isset($given["WithP{$seat}Leader"])) {
                $l = self::_parseSecondLeader(trim($given["WithP{$seat}Leader"]));
                $b->WithLeaderForSeat($seat, $l['cardID'], $l['ready'], $l['deployed'], $l['epicUsed']);
            }
            if (isset($given["WithP{$seat}Leader2"])) {
                $l = self::_parseSecondLeader(trim($given["WithP{$seat}Leader2"]));
                $b->WithLeader2ForSeat($seat, $l['cardID'], $l['ready'], $l['deployed'], $l['epicUsed']);
            }
        }
        // Twin Suns Phase 5: a ground unit a seat CONTROLS but does not OWN (mind-control), so
        // elimination-cleanup can be tested. WithP{n}ControlledUnit: CARDID:owner
        foreach ([1, 2, 3, 4] as $seat) {
            if (!isset($given["WithP{$seat}ControlledUnit"])) continue;
            [$ccid, $cown] = array_pad(explode(':', trim($given["WithP{$seat}ControlledUnit"]), 2), 2, '');
            $b->WithControlledGroundUnitForPlayer($seat, trim($ccid), intval($cown));
        }
        // Twin Suns Phase 5: seed a GlobalEffects flag on a seat. WithP{n}GlobalEffect: CARDID
        foreach ([1, 2, 3, 4] as $seat) {
            if (!isset($given["WithP{$seat}GlobalEffect"])) continue;
            $b->WithGlobalEffectForPlayer($seat, trim($given["WithP{$seat}GlobalEffect"]));
        }
        // Twin Suns seat lists (single-digit concatenations, e.g. "123"). SeatOrder = clockwise turn
        // order; LiveSeats = non-eliminated subset (defaults to SeatOrder).
        if (isset($given['WithSeatOrder'])) $b->WithSeatOrder(trim($given['WithSeatOrder']));
        if (isset($given['WithLiveSeats'])) $b->WithLiveSeats(trim($given['WithLiveSeats']));

        // Explicit hand cards (multi-value: WithP1Hand / WithP2Hand).
        // Explicit hand / discard / deck cards (multi-value). Seats 3/4 (Twin Suns) supported.
        foreach ([1, 2, 3, 4] as $pn) {
            foreach ($given["WithP{$pn}Hand"] ?? [] as $cid) $b->WithCardInHandForPlayer($pn, trim($cid));
            foreach ($given["WithP{$pn}Discard"] ?? [] as $cid) $b->WithCardInDiscardForPlayer($pn, trim($cid));
            foreach ($given["WithP{$pn}Deck"] ?? [] as $cid) $b->WithCardInDeckForPlayer($pn, trim($cid));
        }

        // Initial upgrades on arena units (multi-value: WithP{n}{Ground|Space}ArenaUpgrade: idx:CARD_ID).
        foreach ([1, 2] as $pn) {
            foreach (['Ground', 'Space'] as $arenaType) {
                $key    = "WithP{$pn}{$arenaType}ArenaUpgrade";
                $byUnit = [];
                foreach ($given[$key] ?? [] as $spec) {
                    [$idxStr, $cardID] = array_pad(explode(':', trim($spec), 2), 2, '');
                    $byUnit[intval($idxStr)][] = trim($cardID);
                }
                $method = "WithUpgradesOn{$arenaType}UnitForPlayer";
                foreach ($byUnit as $unitIdx => $cardIDs) {
                    $upgrades = array_map(fn($cid) => GameStateBuilder::Upgrade($cid, $pn), $cardIDs);
                    $b->$method($pn, $unitIdx, $upgrades);
                }
            }
        }

        // Initial PILOT upgrades on arena units (WithP{n}{Ground|Space}ArenaPilot: idx:CARD_ID).
        // Same wiring as ArenaUpgrade but flags IsPilot=true, so the host counts as occupied
        // (SWUVehiclePilotCount) — the honest way to pre-seat a piloted Vehicle.
        foreach ([1, 2] as $pn) {
            foreach (['Ground', 'Space'] as $arenaType) {
                $key    = "WithP{$pn}{$arenaType}ArenaPilot";
                $byUnit = [];
                foreach ($given[$key] ?? [] as $spec) {
                    [$idxStr, $cardID] = array_pad(explode(':', trim($spec), 2), 2, '');
                    $byUnit[intval($idxStr)][] = trim($cardID);
                }
                $method = "WithUpgradesOn{$arenaType}UnitForPlayer";
                foreach ($byUnit as $unitIdx => $cardIDs) {
                    $pilots = array_map(function($cid) use ($pn) {
                        $u = GameStateBuilder::Upgrade($cid, $pn);
                        $u['IsPilot'] = true;
                        return $u;
                    }, $cardIDs);
                    $b->$method($pn, $unitIdx, $pilots);
                }
            }
        }

        // Defeated players (sets gWinner + max base damage).
        foreach (explode(',', $given['WithDefeatedPlayer'] ?? '') as $dpStr) {
            $dp = intval(trim($dpStr));
            if ($dp > 0) $b->WithDefeatedPlayer($dp);
        }

        // The Force token (CR §37): WithP1Force / WithP2Force: true → player controls their Force token.
        foreach ([1, 2] as $pn) {
            if (strtolower($given["WithP{$pn}Force"] ?? 'false') === 'true') $b->WithForceForPlayer($pn);
        }

        // Explicit resource fill.
        // Single group:  "WithP1Resources: N:cardID"
        // Multi-group:   "WithP1Resources: 1:SHD_089:0,7:SOR_095"  (count:cardID[:status], status 0=exhausted 1=ready)
        // Seats 3/4 (Twin Suns) are supported for N-player tests.
        foreach ([1, 2, 3, 4] as $pn) {
            $key = "WithP{$pn}Resources";
            if (isset($given[$key])) {
                $groups = explode(',', trim($given[$key]));
                foreach ($groups as $group) {
                    $parts    = explode(':', trim($group));
                    $n        = max(0, intval($parts[0]));
                    $fillCard = trim($parts[1] ?? 'SOR_095') ?: 'SOR_095';
                    $allReady = isset($parts[2]) ? (intval($parts[2]) === 1) : true;
                    if ($n > 0) $b->FillResourcesForPlayer($pn, $fillCard, $n, $allReady);
                }
            }
        }

        // Credit tokens (CR §3.13): "WithP1Credits: N" creates N Credit tokens (LAW_T01) in the
        // player's resource zone. They are created via the resource fill but are NOT resources —
        // SWUResourceCount/SWUExhaustResources skip them, and they accumulate AFTER any real
        // resources filled above (so their mzID index = realResourceCount + offset).
        foreach ([1, 2] as $pn) {
            $key = "WithP{$pn}Credits";
            if (isset($given[$key])) {
                $n = max(0, intval(trim($given[$key])));
                if ($n > 0) $b->FillResourcesForPlayer($pn, 'LAW_T01', $n, true);
            }
        }

        return $b;
    }

    // Twin Suns: parse a second-leader spec "CARDID[:ready[:deployed[:epicUsed]]]".
    private static function _parseSecondLeader(string $val): array {
        $p = array_map('trim', explode(':', $val));
        return [
            'cardID'   => $p[0] ?? '',
            'ready'    => !isset($p[1]) || $p[1] === '1' || $p[1] === 'true',
            'deployed' => isset($p[2]) && ($p[2] === '1' || $p[2] === 'true'),
            'epicUsed' => isset($p[3]) && ($p[3] === '1' || $p[3] === 'true'),
        ];
    }

    // Parse "{key:val;key:val}" opts block from CommonSetup directive.
    // Returns [$myOpts, $theirOpts] arrays keyed for CommonSetup().
    private static function _parseCommonSetupOpts(string $raw): array {
        $raw = trim($raw, '{} ');
        if ($raw === '') return [[], []];

        $myOpts    = [];
        $theirOpts = [];

        foreach (explode(';', $raw) as $entry) {
            $entry = trim($entry);
            if ($entry === '') continue;
            $colonPos = strpos($entry, ':');
            if ($colonPos === false) continue;
            $key = trim(substr($entry, 0, $colonPos));
            $val = trim(substr($entry, $colonPos + 1));

            switch ($key) {
                case 'myResources':
                    $myOpts['resourceCount'] = intval($val);
                    break;
                case 'theirResources':
                    $theirOpts['resourceCount'] = intval($val);
                    break;
                // Leader override with optional inline params, mirroring the P1LeaderBase leader spec
                // plus a 4th damage field:  myLeader: CARDID[:ready[:deployed[:epicUsed[:damage]]]]
                //   ready    1=ready (default) / 0=exhausted
                //   deployed 1=deploy as a REAL linked ground-arena leader unit (deployMode='unit')
                //   epicUsed 1=Epic deploy already used
                //   damage   damage on the deployed leader UNIT (only meaningful when deployed=1)
                // Each field is optional; bare `myLeader: CARDID` is unchanged. Individual opts
                // (myLeaderReady/myLeaderDeployed/...) still work and override per-key if also present.
                case 'myLeader':
                    self::_applyLeaderParams($myOpts, $val);
                    break;
                case 'theirLeader':
                    self::_applyLeaderParams($theirOpts, $val);
                    break;
                // Twin Suns second leader:  myLeader2: CARDID[:ready[:deployed[:epicUsed]]]
                // (undeployed by default; tests usually deploy it live in WHEN). Stored for CommonSetup.
                case 'myLeader2':
                    $myOpts['leader2'] = self::_parseSecondLeader($val);
                    break;
                case 'theirLeader2':
                    $theirOpts['leader2'] = self::_parseSecondLeader($val);
                    break;
                case 'myBase':         // override the code-derived base with an explicit cardID
                    $myOpts['baseCardID'] = trim($val);
                    break;
                case 'theirBase':
                    $theirOpts['baseCardID'] = trim($val);
                    break;
                case 'handCardIds':     // legacy alias; prefer 'myhandCardIds' going forward
                case 'myhandCardIds':
                    $myOpts['handCardIds'] = array_map('trim', explode(',', $val));
                    break;
                case 'theirHandCardIds': // legacy alias; prefer 'theirhandCardIds' going forward
                case 'theirhandCardIds':
                    $theirOpts['handCardIds'] = array_map('trim', explode(',', $val));
                    break;
                case 'discardCardIds':
                    $myOpts['discardCardIds'] = array_map('trim', explode(',', $val));
                    break;
                case 'theirDiscardCardIds':
                    $theirOpts['discardCardIds'] = array_map('trim', explode(',', $val));
                    break;
                case 'myBaseDamage':
                    $myOpts['baseDamage'] = intval($val);
                    break;
                case 'theirBaseDamage':
                    $theirOpts['baseDamage'] = intval($val);
                    break;
                case 'myLeaderDeployed':       // deploy as a real ground-arena leader unit
                    $myOpts['leaderDeployed'] = $val === '1' || $val === 'true';
                    break;
                case 'theirLeaderDeployed':
                    $theirOpts['leaderDeployed'] = $val === '1' || $val === 'true';
                    break;
                case 'myLeaderDeployedPilot':  // deploy as a Pilot upgrade on the first friendly unit
                    $myOpts['leaderDeployedPilot'] = $val === '1' || $val === 'true';
                    break;
                case 'theirLeaderDeployedPilot':
                    $theirOpts['leaderDeployedPilot'] = $val === '1' || $val === 'true';
                    break;
                case 'myLeaderFlipped':        // TWI_017 "Flipatine": Deployed flag WITHOUT board presence
                    $myOpts['leaderFlipped'] = $val === '1' || $val === 'true';
                    break;
                case 'theirLeaderFlipped':
                    $theirOpts['leaderFlipped'] = $val === '1' || $val === 'true';
                    break;
                // NOTE: leader READY is set via the inline "myLeader: CID:ready:..." form (its 2nd
                // field), not a standalone opt. A normal deployed leader always has board presence
                // (use myLeaderDeployed / myLeaderDeployedPilot); the ONE exception is a double-leader-face
                // flip card (TWI_017), whose "Deployed" is just the flipped side with no arena unit —
                // seed that state with myLeaderFlipped.
                case 'myLeaderEpicUsed':
                    $myOpts['leaderEpicActionUsed'] = $val === '1' || $val === 'true';
                    break;
                case 'theirLeaderEpicUsed':
                    $theirOpts['leaderEpicActionUsed'] = $val === '1' || $val === 'true';
                    break;
            }
        }

        return [$myOpts, $theirOpts];
    }

    // Parse `CARDID[:ready[:deployed[:epicUsed[:damage[:indexOverride]]]]]` from a myLeader/theirLeader
    // opt into the side's opts array. Only fields actually present are written (so a bare CARDID leaves
    // ready/etc. at their CommonSetup defaults). The 6th field, indexOverride, is the ground-arena index
    // to insert a REGULAR-deploy (deployed=1) leader unit at, shifting the other WithP{n}GroundArena
    // units up; ignored unless deployed as a unit.
    private static function _applyLeaderParams(array &$opts, string $val): void {
        $p = array_map('trim', explode(':', $val));
        $opts['leaderCardID'] = $p[0];
        $truthy = fn($s) => $s === '1' || $s === 'true';
        if (isset($p[1]) && $p[1] !== '') $opts['leaderReady']          = $truthy($p[1]);
        if (isset($p[2]) && $truthy($p[2])) $opts['leaderDeployed']      = true;  // deployMode='unit'
        if (isset($p[3]) && $truthy($p[3])) $opts['leaderEpicActionUsed'] = true;
        if (isset($p[4]) && $p[4] !== '') $opts['leaderDamage']          = intval($p[4]);
        if (isset($p[5]) && $p[5] !== '') $opts['leaderIndexOverride']   = intval($p[5]);
    }

    // "SOR_024"     → ['SOR_024', 0,  epicActionUsed:false]
    // "SOR_024:27"  → ['SOR_024', 27, epicActionUsed:false]
    // "SOR_022:0:1" → ['SOR_022', 0,  epicActionUsed:true]   (3rd field: base Epic Action used)
    private static function _parseBaseSpec(string $spec): array {
        $parts        = explode(':', trim($spec));
        $cardId       = trim($parts[0]);
        $damage       = isset($parts[1]) ? intval($parts[1]) : 0;
        $epicUsed     = isset($parts[2]) ? (intval($parts[2]) === 1) : false;
        return [$cardId, $damage, $epicUsed];
    }

    // "SOR_014"       → ['SOR_014', ready:true,  deployed:false, epicActionUsed:false]
    // "SOR_014:0"     → ['SOR_014', ready:false, deployed:false, epicActionUsed:false]
    // "SOR_014:1:1"   → ['SOR_014', ready:true,  deployed:true,  epicActionUsed:false]
    // "SOR_014:0:0:1" → ['SOR_014', ready:false, deployed:false, epicActionUsed:true]
    private static function _parseLeaderSpec(string $spec): array {
        $parts        = explode(':', trim($spec));
        $cardId       = trim($parts[0]);
        $ready        = isset($parts[1]) ? (intval($parts[1]) === 1) : true;
        $deployed     = isset($parts[2]) ? (intval($parts[2]) === 1) : false;
        $epicActionUsed = isset($parts[3]) ? (intval($parts[3]) === 1) : false;
        return [$cardId, $ready, $deployed, $epicActionUsed];
    }

    // "SOR_095:1:3" → ['SOR_095', ready:true,  damage:3]
    // "SOR_095:0"   → ['SOR_095', ready:false, damage:0]
    // "SOR_095"     → ['SOR_095', ready:true,  damage:0]
    // "CID"  |  "CID:ready"  |  "CID:ready:dmg"  |  "CID:ready:dmg:eff1~eff2"
    // 4th field = active TurnEffects on the unit ('~'-delimited, e.g. a granted keyword like
    // LOF_045 / SENTINEL^SEC_041 / RESTORE-1@attack^JTL_097). Returns "-" (none) when absent.
    private static function _parseUnitSpec(string $spec): array {
        $parts  = explode(':', trim($spec));
        $cardId = trim($parts[0]);
        $ready  = isset($parts[1]) ? (intval($parts[1]) === 1) : true;
        $damage = isset($parts[2]) ? intval($parts[2]) : 0;
        $turnEffects = (isset($parts[3]) && $parts[3] !== '') ? trim($parts[3]) : '-';
        return [$cardId, $ready, $damage, $turnEffects];
    }

    // ── Execution ────────────────────────────────────────────────────

    private static function _execute(GameTestAdapter $g, array $action): void {
        $player = $action['player'];
        $cmd    = $action['cmd'];
        $args   = $action['args'];

        switch ($cmd) {
            case 'PlayHand':
                $g->playCardFromHand($player, intval($args));
                break;

            case 'AttackGroundArena': {
                [$unitIdx, $target] = array_pad(explode(':', $args, 2), 2, '');
                $atk = "myGroundArena-" . intval($unitIdx);
                // Twin Suns: 'P<seat>G<idx>' / 'P<seat>S<idx>' / 'P<seat>B' names a SPECIFIC opponent's
                // unit/base in an N-player game (union targets).
                // Else 'BASE' → the one opponent's base; 'S<idx>' → cross-arena space (JTL_259); else ground.
                if (($pt = _twSchemaSeatTarget($target)) !== null) $def = $pt;
                elseif ($target === 'BASE')              $def = 'theirBase-0';
                elseif (str_starts_with($target, 'S'))   $def = 'theirSpaceArena-' . intval(substr($target, 1));
                else                                     $def = 'theirGroundArena-' . intval($target);
                $g->declareAttack($player, $atk, $def);
                break;
            }

            case 'AttackSpaceArena': {
                [$unitIdx, $target] = array_pad(explode(':', $args, 2), 2, '');
                $atk = "mySpaceArena-" . intval($unitIdx);
                // Twin Suns 'P<seat>...' seat-specific target (see AttackGroundArena).
                // Else 'BASE' → the one opponent's base; 'G<idx>' → cross-arena ground (Strafing Gunship); else space.
                if (($pt = _twSchemaSeatTarget($target)) !== null) $def = $pt;
                elseif ($target === 'BASE')             $def = 'theirBase-0';
                elseif (str_starts_with($target, 'G'))   $def = 'theirGroundArena-' . intval(substr($target, 1));
                else                                     $def = 'theirSpaceArena-' . intval($target);
                $g->declareAttack($player, $atk, $def);
                break;
            }

            case 'Pass':
                $g->passAction($player);
                break;

            case 'UndoCycle':
                // SaveVersion→LoadVersion round-trip (a mid-game undo). Reconstructs every zone object
                // via LoadVersion — regression guard for the relative-Location / owner-PlayerID invariant.
                $g->undoCycle($player);
                break;

            case 'Claim':
                $g->takeInitiative($player);
                break;

            case 'TakeCounter':   // Twin Suns: PN>TakeCounter:blast | PN>TakeCounter:plan
                $g->takeCounter($player, trim((string)$args));
                break;

            case 'EliminateSeat': {
                // Twin Suns Phase 5 (test-only): eliminate a seat directly.
                //   - P{n}>EliminateSeat:S        (killer = null → no heal)
                //   - P{n}>EliminateSeat:S:K      (K = eliminating seat → heals 5)
                $ea     = explode(':', trim((string)$args));
                $seat   = intval($ea[0]);
                $killer = (isset($ea[1]) && $ea[1] !== '') ? intval($ea[1]) : null;
                $g->eliminateSeat($seat, $killer);
                break;
            }

            case 'DeclareWinners':   // Twin Suns Phase 5 (test-only): PN>DeclareWinners:2,4
                $g->declareWinners(array_map('intval', explode(',', trim((string)$args))));
                break;

            case 'ScorePhaseEnd':    // Twin Suns Phase 5 (test-only): run deferred end-of-phase scoring
                $g->scorePhaseEnd();
                break;

            case 'RunRegroupStart':  // Twin Suns Phase 5 (test-only): run RegroupPhaseStart directly
                $g->runRegroupStart();
                break;

            case 'ResourceHand':
                // Answer the pending MZCHOOSE from ResourcePhase for this player.
                $g->answerDecision($player, "myHand-" . intval($args));
                break;

            case 'ResourcePass':
                // Decline the optional resource (SWUApplyRegroupResource guard skips on "-").
                $g->answerDecision($player, "-");
                break;

            case 'UseBaseAbility':
                $g->useBaseAbility($player);
                break;

            case 'UseUnitAbility':
                $g->useUnitAbility($player, trim($args));
                break;
            case 'UseLeaderAbility':
                $g->useLeaderAbility($player, intval($args ?? 0));
                break;

            case 'DeployLeader':
                $g->deployLeader($player, intval($args ?? 0));
                break;

            case 'SmuggleResource':
                $g->smuggleResource($player, intval($args));
                break;

            case 'PlayFromDiscard':
                $g->playFromDiscard($player, intval($args));
                break;
            case 'PlayFromOpponentDiscard':
                $g->playFromOpponentDiscard($player, intval($args));
                break;

            case 'AnswerDecision':
                $g->answerDecision($player, $args);
                break;

            case 'Drain':
                // Run pending STATIC entries on $player's queue (cross-player reaction drain —
                // mirrors production's post-action ProcessGoldfishAutomation). No args.
                $g->drainQueue($player);
                break;

            case 'ChooseMyGroundUnit':
                $g->answerDecision($player, "myGroundArena-{$args}");
                break;
            case 'ChooseMySpaceUnit':
                $g->answerDecision($player, "mySpaceArena-{$args}");
                break;
            case 'ChooseTheirGroundUnit':
                $g->answerDecision($player, "theirGroundArena-{$args}");
                break;
            case 'ChooseTheirSpaceUnit':
                $g->answerDecision($player, "theirSpaceArena-{$args}");
                break;

            case 'ResolveTrigger':
                // ResolveTrigger:TriggerType or ResolveTrigger:TriggerType:CardID
                // Picks the matching EffectStack entry and answers the MZCHOOSE with its index.
                $tParts     = explode(':', $args ?? '', 2);
                $triggerType = $tParts[0];
                $filterCardID = $tParts[1] ?? null;
                $stack = GetEffectStack();
                $mzIdx = null;
                foreach ($stack as $idx => $e) {
                    if (!empty($e->removed ?? false)) continue;
                    if (($e->TriggerType ?? '') !== $triggerType) continue;
                    if ($filterCardID !== null && ($e->CardID ?? '') !== $filterCardID) continue;
                    $mzIdx = $idx;
                    break;
                }
                if ($mzIdx === null)
                    throw new RuntimeException("ResolveTrigger:{$args}: no matching EffectStack entry");
                $g->answerDecision($player, "EffectStack-{$mzIdx}");
                break;

            default:
                throw new RuntimeException("Unknown schema command: {$cmd}");
        }

        // Drive automatic phase transitions (RGS→DRAW→RES, READY→APS→MAIN, etc.).
        ob_start();
        AutoAdvanceAndExecute();
        ob_end_clean();
    }

    // ── Public API for UI tooling ────────────────────────────────────

    /**
     * Parse schema markdown into sections for use by the test schema UI.
     * Returns: ['ok'=>bool, 'given'=>[], 'pregame'=>[], 'main'=>[], 'error'=>?string]
     */
    public static function parseForUI(string $content): array {
        $parsed = self::_parse($content);
        if (!$parsed['ok']) return ['ok' => false, 'error' => $parsed['error'] ?? 'Parse failed'];
        ['when' => $whenLines] = $parsed;
        ['pregame' => $pregame, 'main' => $main] = self::_splitPregame($whenLines);
        return [
            'ok'      => true,
            'given'   => $parsed['given'],
            'pregame' => $pregame,
            'main'    => $main,
            'expect'  => $parsed['expect'],
        ];
    }

    /** Evaluate EXPECT assertion lines against a live GameTestAdapter (read-only). */
    public static function evalExpectLines(GameTestAdapter $g, array $expectLines): array {
        return self::_evalExpect($g, $expectLines);
    }

    /** Build a GameStateBuilder from parsed GIVEN lines + pregame actions. */
    public static function buildInitialStateForUI(array $givenLines, array $pregame): GameStateBuilder {
        return self::_buildInitialState($givenLines, $pregame);
    }

    /**
     * Apply post-AutoAdvance state overrides from GIVEN directives.
     * Must be called AFTER AutoAdvanceAndExecute() so phase-transition logic
     * cannot overwrite these values.
     *
     * Supported directives:
     *   WithInitiativePlayer: N   — which player holds the initiative token
     *   WithInitiativeClaimed: true/false — whether it has been claimed this round
     *   WithActivePlayer: N       — which player is the active (turn) player
     *   P1OnlyActions: true       — shorthand for WithInitiativePlayer:2 + WithInitiativeClaimed:true + WithActivePlayer:1
     *                               P2 auto-passes after every P1 action (P2 holds claimed initiative)
     */
    public static function applyPostSetupDirectives(array $givenLines): void {
        $given = self::_parseGiven($givenLines);
        if (strtolower($given['P1OnlyActions'] ?? '') === 'true') {
            SetInitiativeCounter('P2_CLAIMED');
            SetTurnPlayer(1);
            return;
        }
        if (isset($given['WithInitiativePlayer']) || isset($given['WithInitiativeClaimed'])) {
            $holder  = intval($given['WithInitiativePlayer'] ?? 1);
            $claimed = strtolower($given['WithInitiativeClaimed'] ?? 'false') === 'true';
            SetInitiativeCounter("P{$holder}_" . ($claimed ? 'CLAIMED' : 'UNCLAIMED'));
        }
        if (isset($given['WithActivePlayer'])) {
            SetTurnPlayer(intval($given['WithActivePlayer']));
        }
    }

    /** Parse a single raw WHEN line (e.g. "- P1>PlayHand:0") into an action array. */
    public static function parseSingleAction(string $line): ?array {
        return self::_parseWhenLine($line);
    }

    /** Execute a single parsed action against a live GameTestAdapter. */
    public static function executeSingleAction(GameTestAdapter $g, array $action): void {
        self::_execute($g, $action);
    }

    // ── Assertions ───────────────────────────────────────────────────

    private static function _evalExpect(GameTestAdapter $g, array $lines): array {
        $failures = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            if ($line === 'P1WIN') {
                if ($g->state->winner() !== 1)
                    $failures[] = "P1WIN: winner is " . var_export($g->state->winner(), true);

            } elseif ($line === 'P2WIN') {
                if ($g->state->winner() !== 2)
                    $failures[] = "P2WIN: winner is " . var_export($g->state->winner(), true);

            } elseif (preg_match('/^TURNPLAYER:(\d+)$/', $line, $m)) {
                // Whose action it is right now. Catches actions that fail to pass the turn —
                // e.g. a declined optional "may" follow-up that leaks a free action.
                $expected = intval($m[1]);
                $actual   = intval(GetTurnPlayer());
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected turn player {$expected}, got {$actual}";

            } elseif (preg_match('/^SEATCOUNT:(\d+)$/', $line, $m)) {
                // Twin Suns: number of seats in SeatOrder (the game's player count, 2..4).
                $expected = intval($m[1]);
                $actual   = SeatCountForGame();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected seat count {$expected}, got {$actual}";

            } elseif (preg_match('/^SEATLIVE:(\d+):(true|false)$/', $line, $m)) {
                // Twin Suns: whether a seat is in LiveSeats (non-eliminated).
                $seat     = intval($m[1]);
                $expected = ($m[2] === 'true');
                $actual   = IsSeatLive($seat);
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected seat {$seat} live=" . var_export($expected, true)
                                . ", got " . var_export($actual, true);

            } elseif (preg_match('/^GAMEWINNERS:([0-9,]+)$/', $line, $m)) {
                // Twin Suns Phase 5: the end-game winner set (sorted seats; ties share).
                $expected = array_map('intval', explode(',', $m[1]));
                sort($expected);
                $actual = SWUGetGameWinners();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected winners [" . implode(',', $expected)
                                . "], got [" . implode(',', $actual) . "]";

            } elseif (preg_match('/^OPPONENTSOF:(\d+):(.*)$/', $line, $m)) {
                // Twin Suns (Phase 3): the live opponents of a seat, as a comma-joined list.
                $p = intval($m[1]); $expected = trim($m[2]);
                $actual = implode(',', OpponentsOf($p));
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected opponents [{$expected}], got [{$actual}]";

            } elseif (preg_match('/^ZONESEARCH:(\d+):(\w+):(\d+)$/', $line, $m)) {
                // Twin Suns (Phase 3): number of results ZoneSearch($zone) returns from a seat's view —
                // for "their<Zone>" in an N-player game this unions all opponents' zones.
                global $playerID; $savedPID = $playerID; $playerID = intval($m[1]);
                $actual = count(ZoneSearch($m[2]));
                $playerID = $savedPID;
                if ($actual !== intval($m[3]))
                    $failures[] = "{$line}: expected {$m[3]} results, got {$actual}";

            } elseif (preg_match('/^ATTACKTARGETS:(\d+):([GS]):(\d+):(\d+)$/', $line, $m)) {
                // Twin Suns (Phase 3): the number of valid attack targets for seat $1's unit at index $3 in
                // its Ground/Space arena — UNIONED across all live opponents (per-opponent Sentinel/base).
                global $playerID; $savedPID = $playerID; $playerID = intval($m[1]);
                $arena  = $m[2] === 'G' ? 'GroundArena' : 'SpaceArena';
                $atkObj = GetZoneObject("p{$m[1]}{$arena}-{$m[3]}");
                $actual = $atkObj === null ? -1
                        : count(SWUGetAllValidAttackTargets(intval($m[1]), $atkObj, $arena));
                $playerID = $savedPID;
                if ($actual !== intval($m[4]))
                    $failures[] = "{$line}: expected {$m[4]} attack targets, got {$actual}";

            } elseif (preg_match('/^(BLAST|PLAN)COUNTER:(.+)$/', $line, $m)) {
                // Twin Suns (Phase 4): the blast/plan counter's state ("AVAILABLE" or "P{n}").
                $actual = ($m[1] === 'BLAST') ? GetBlastCounter() : GetPlanCounter();
                if ($actual !== $m[2])
                    $failures[] = "{$line}: expected {$m[2]}, got {$actual}";

            } elseif (preg_match('/^P(\d+)(BLAST|PLAN)AVAIL:(0|1)$/', $line, $m)) {
                // Twin Suns UI: does seat N's actions data report the counter as available to take?
                // Mirror SWUComputeActionsData: available iff the seat hasn't taken a counter this round
                // AND the counter is still AVAILABLE globally.
                $seat = intval($m[1]);
                $counter = ($m[2] === 'BLAST') ? GetBlastCounter() : GetPlanCounter();
                $avail = (!_SWUSeatTookCounterThisRound($seat) && $counter === 'AVAILABLE') ? '1' : '0';
                if ($avail !== $m[3])
                    $failures[] = "{$line}: expected {$m[3]}, got {$avail}";

            } elseif (preg_match('/^P(\d+)BASEDMG:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->base->damage;
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected base damage {$expected}, got {$actual}";

            } elseif (preg_match('/^P(\d+)GROUNDCOUNT:(\d+)$/', $line, $m)) {
                // Twin Suns Phase 5: non-removed unit count in seat N's ground arena.
                $p = intval($m[1]); $expected = intval($m[2]);
                $actual = count(array_filter(GetGroundArena($p), fn($o) => empty($o->removed)));
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected {$expected} ground units, got {$actual}";

            } elseif (preg_match('/^P(\d+)SPACECOUNT:(\d+)$/', $line, $m)) {
                $p = intval($m[1]); $expected = intval($m[2]);
                $actual = count(array_filter(GetSpaceArena($p), fn($o) => empty($o->removed)));
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected {$expected} space units, got {$actual}";

            } elseif (preg_match('/^P(\d+)DISCARDCOUNT:(\d+)$/', $line, $m)) {
                $p = intval($m[1]); $expected = intval($m[2]);
                $actual = count(array_filter(GetDiscard($p), fn($o) => empty($o->removed)));
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected {$expected} discard cards, got {$actual}";

            } elseif (preg_match('/^P(\d+)RESCOUNT:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->resources->count();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected resource count {$expected}, got {$actual}";

            } elseif (preg_match('/^P(\d+)CREDITCOUNT:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->resources->creditCount();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected credit token count {$expected}, got {$actual}";

            } elseif (preg_match('/^P(\d+)NODECISION$/', $line, $m)) {
                $p       = intval($m[1]);
                $pending = $g->state->pendingDecision($p);
                if ($pending !== null)
                    $failures[] = "{$line}: expected no pending decision, got type " . $pending->Type;

            } elseif (preg_match('/^P(\d+)HASDECISION$/', $line, $m)) {
                $p       = intval($m[1]);
                $pending = $g->state->pendingDecision($p);
                if ($pending === null)
                    $failures[] = "{$line}: expected a pending decision, but none found";

            } elseif (preg_match('/^P(\d+)DECISIONTOOLTIP:(.+)$/', $line, $m)) {
                // Exact-match the pending decision's tooltip — lets a test assert an offered pool/amount
                // that is embedded in the prompt (e.g. "Distribute_up_to_6_Advantage_among_friendly_units")
                // but never surfaced in the board state the other assertions read.
                $p       = intval($m[1]);
                $pending = $g->state->pendingDecision($p);
                if ($pending === null)
                    $failures[] = "{$line}: expected a pending decision, but none found";
                elseif (($pending->Tooltip ?? '') !== $m[2])
                    $failures[] = "{$line}: expected tooltip '{$m[2]}', got '" . ($pending->Tooltip ?? '') . "'";

            } elseif (preg_match('/^P(\d+)SEARCHPLAYABLE(HAS|NOT):(.+)$/', $line, $m)) {
                // Assert membership in a pending TOPDECKSEARCH's *playable* set (the matchIDs field —
                // the cards the UI lets you actually pick/play, distinct from the full revealed set).
                // Param format: allIDs|matchIDs|constraint|costMap. Leave the search decision pending
                // (don't answer it) so it can be read. Lets a test prove the offered pool is filtered
                // (e.g. affordability) — which the harness's answer path does NOT enforce on its own.
                $p        = intval($m[1]);
                $wantHas  = ($m[2] === 'HAS');
                $cardID   = $m[3];
                $pending  = $g->state->pendingDecision($p);
                if ($pending === null) {
                    $failures[] = "{$line}: expected a pending TOPDECKSEARCH decision, but none found";
                } elseif (($pending->Type ?? '') !== 'TOPDECKSEARCH') {
                    $failures[] = "{$line}: pending decision is '" . ($pending->Type ?? '') . "', not TOPDECKSEARCH";
                } else {
                    $fields   = explode('|', $pending->Param ?? '');
                    $playable = array_values(array_filter(explode(',', $fields[1] ?? '')));
                    $present  = in_array($cardID, $playable, true);
                    if ($wantHas && !$present)
                        $failures[] = "{$line}: '{$cardID}' not in playable set [" . implode(',', $playable) . "]";
                    if (!$wantHas && $present)
                        $failures[] = "{$line}: '{$cardID}' unexpectedly in playable set [" . implode(',', $playable) . "]";
                }

            } elseif (preg_match('/^P(\d+)OPTION(HAS|NOT):(.+)$/', $line, $m)) {
                // Membership of a label in a pending OPTIONCHOOSE's option list (Param, '&'-split). A
                // leading "@CardID" image ref is naturally excluded (it won't equal a label). Leave the
                // decision pending to read it — lets a test assert an option is offered/withheld, e.g. an
                // affordability-gated "Play" that the harness's answer path would not enforce on its own.
                $p       = intval($m[1]);
                $wantHas = ($m[2] === 'HAS');
                $label   = $m[3];
                $pending = $g->state->pendingDecision($p);
                if ($pending === null) {
                    $failures[] = "{$line}: expected a pending decision, but none found";
                } else {
                    $opts    = array_values(array_filter(explode('&', $pending->Param ?? '')));
                    $present = in_array($label, $opts, true);
                    if ($wantHas && !$present)
                        $failures[] = "{$line}: option '{$label}' not offered [" . implode(',', $opts) . "]";
                    if (!$wantHas && $present)
                        $failures[] = "{$line}: option '{$label}' unexpectedly offered [" . implode(',', $opts) . "]";
                }

            } elseif (preg_match('/^P(\d+)HASFORCE$/', $line, $m)) {
                $p = intval($m[1]);
                if (!$g->state->player($p)->force)
                    $failures[] = "{$line}: expected player $p to control the Force, but they do not";

            } elseif (preg_match('/^P(\d+)NOFORCE$/', $line, $m)) {
                $p = intval($m[1]);
                if ($g->state->player($p)->force)
                    $failures[] = "{$line}: expected player $p to NOT control the Force, but they do";

            } elseif (preg_match('/^P(\d+)BASEACTIONUSES:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->base->actionUsesLeft;
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected base repeatable-action uses-left {$expected} for player $p, got {$actual}";

            } elseif (preg_match('/^PHASE:(.+)$/', $line, $m)) {
                $expected = $m[1];
                $actual   = $g->state->currentPhase();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected phase {$expected}, got {$actual}";

            } elseif (preg_match('/^PHASEISNOT:(.+)$/', $line, $m)) {
                $notExpected = $m[1];
                $actual      = $g->state->currentPhase();
                if ($actual === $notExpected)
                    $failures[] = "{$line}: phase should not be {$notExpected}, but it is";

            } elseif (preg_match('/^INITIATIVECOUNTER:(.+)$/', $line, $m)) {
                $expected = $m[1];
                $actual   = $g->state->initiativeCounter();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected initiative counter {$expected}, got {$actual}";

            } elseif (preg_match('/^P(\d+)RESAVAILABLE:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->resources->readyCount();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected ready resources {$expected}, got {$actual}";

            } elseif (preg_match('/^P(\d+)HANDCOUNT:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->hand->count();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected hand count {$expected}, got {$actual}";

            } elseif (preg_match('/^P(\d+)DISCARDCOUNT:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->discard->count();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected discard count {$expected}, got {$actual}";

            } elseif (preg_match('/^(P[12])DISCARDUNIT:(\d+):(CARDID|MODIFIER|FROM):(.*)$/', $line, $m)) {
                $p       = $m[1] === 'P1' ? 1 : 2;
                $idx     = intval($m[2]);
                $field   = $m[3];
                $expected = $m[4];
                $discard = $g->getDiscard($p);
                $actual  = null;
                $count   = 0;
                foreach ($discard as $entry) {
                    if ($entry->removed ?? false) continue;
                    if ($count === $idx) { $actual = $entry; break; }
                    $count++;
                }
                if ($actual === null) {
                    $failures[] = "P{$p}DISCARDUNIT:{$idx} not found";
                } else {
                    $propMap = ['CARDID' => 'CardID', 'FROM' => 'From', 'MODIFIER' => 'Modifier'];
                    $prop = $propMap[$field];
                    $val = $actual->$prop ?? '';
                    if ($val !== $expected)
                        $failures[] = "{$line}: expected {$field} {$expected}, got {$val}";
                }

            } elseif (preg_match('/^P(\d+)HANDCARD:(\d+):(\S+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $idx      = intval($m[2]);
                $expected = $m[3];
                $hand     = $g->getHand($p);
                $actual   = null;
                $count    = 0;
                foreach ($hand as $entry) {
                    if ($entry->removed ?? false) continue;
                    if ($count === $idx) { $actual = $entry; break; }
                    $count++;
                }
                if ($actual === null) {
                    $failures[] = "P{$p}HANDCARD:{$idx} not found";
                } elseif (($actual->CardID ?? '') !== $expected) {
                    $failures[] = "{$line}: expected hand card {$expected}, got " . ($actual->CardID ?? '');
                }

            } elseif (preg_match('/^P(\d+)DECKCOUNT:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->deck->count();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected deck count {$expected}, got {$actual}";

            } elseif (preg_match('/^P(\d+)DECKTOPCARD:(\S+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = $m[2];
                $actual   = $g->state->player($p)->deck->topCardID();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected deck top card {$expected}, got " . ($actual ?? 'null');

            } elseif (preg_match('/^P(\d+)GROUNDARENACOUNT:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->groundArena->count();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected ground arena count {$expected}, got {$actual}";

            } elseif (preg_match('/^P(\d+)SPACEARENACOUNT:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->spaceArena->count();
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected space arena count {$expected}, got {$actual}";

            } elseif (preg_match('/^P(\d+)(GROUND|SPACE)ARENAUNIT:(\d+):(.+)$/', $line, $m)) {
                $p       = intval($m[1]);
                $arena   = strtolower($m[2]) . 'Arena';   // 'groundArena' or 'spaceArena'
                $idx     = intval($m[3]);
                $assert  = $m[4];

                try {
                    $unit = $g->state->player($p)->$arena->get($idx);
                } catch (OutOfBoundsException $e) {
                    $failures[] = "{$line}: " . $e->getMessage();
                    continue;
                }

                if ($assert === 'READY') {
                    if (!$unit->isReady())
                        $failures[] = "{$line}: unit is exhausted, expected ready";

                } elseif ($assert === 'EXHAUSTED') {
                    if ($unit->isReady())
                        $failures[] = "{$line}: unit is ready, expected exhausted";

                } elseif (preg_match('/^CARDID:(.+)$/', $assert, $am)) {
                    $expected = $am[1];
                    $actual   = $unit->cardID;
                    if ($actual !== $expected)
                        $failures[] = "{$line}: expected cardID {$expected}, got {$actual}";

                } elseif (preg_match('/^DAMAGE:(\d+)$/', $assert, $am)) {
                    $expected = intval($am[1]);
                    $actual   = $unit->damage;
                    if ($actual !== $expected)
                        $failures[] = "{$line}: expected damage {$expected}, got {$actual}";

                } elseif (preg_match('/^POWER:(\d+)$/', $assert, $am)) {
                    $expected = intval($am[1]);
                    $actual   = $unit->currentPower();
                    if ($actual !== $expected)
                        $failures[] = "{$line}: expected power {$expected}, got {$actual}";

                } elseif (preg_match('/^HP:(\d+)$/', $assert, $am)) {
                    $expected = intval($am[1]);
                    $actual   = $unit->currentHP();
                    if ($actual !== $expected)
                        $failures[] = "{$line}: expected hp {$expected}, got {$actual}";

                } elseif (preg_match('/^UPGRADECOUNT:(\d+)$/', $assert, $am)) {
                    $expected = intval($am[1]);
                    $actual   = $unit->upgradeCount();
                    if ($actual !== $expected)
                        $failures[] = "{$line}: expected upgrade count {$expected}, got {$actual}";

                } elseif (preg_match('/^SHIELDCOUNT:(\d+)$/', $assert, $am)) {
                    $expected = intval($am[1]);
                    $actual   = 0;
                    for ($i = 0; $i < $unit->upgradeCount(); $i++) {
                        if ($unit->upgrade($i)->cardID === 'SOR_T02') $actual++;
                    }
                    if ($actual !== $expected)
                        $failures[] = "{$line}: expected shield token count {$expected}, got {$actual}";

                } elseif (preg_match('/^ADVANTAGECOUNT:(\d+)$/', $assert, $am)) {
                    $expected = intval($am[1]);
                    $actual   = 0;
                    for ($i = 0; $i < $unit->upgradeCount(); $i++) {
                        if ($unit->upgrade($i)->cardID === 'ASH_T02') $actual++;
                    }
                    if ($actual !== $expected)
                        $failures[] = "{$line}: expected advantage token count {$expected}, got {$actual}";

                } elseif (preg_match('/^UPGRADE:(\d+):CARDID:(.+)$/', $assert, $am)) {
                    $upgradeIdx = intval($am[1]);
                    $expected   = $am[2];
                    try {
                        $actual = $unit->upgrade($upgradeIdx)->cardID;
                        if ($actual !== $expected)
                            $failures[] = "{$line}: expected upgrade cardID {$expected}, got {$actual}";
                    } catch (OutOfBoundsException $e) {
                        $failures[] = "{$line}: " . $e->getMessage();
                    }

                } elseif (preg_match('/^HASTRAIT:(\w+)$/', $assert, $am)) {
                    if (!$unit->hasTrait($am[1]))
                        $failures[] = "{$line}: expected unit to have trait {$am[1]}";

                } elseif (preg_match('/^NOTTRAIT:(\w+)$/', $assert, $am)) {
                    if ($unit->hasTrait($am[1]))
                        $failures[] = "{$line}: expected unit to NOT have trait {$am[1]}";

                } elseif (preg_match('/^HASKEYWORD:(\w+)$/', $assert, $am)) {
                    if (!$unit->hasKeyword($am[1]))
                        $failures[] = "{$line}: expected unit to have keyword {$am[1]}";

                } elseif (preg_match('/^NOTKEYWORD:(\w+)$/', $assert, $am)) {
                    if ($unit->hasKeyword($am[1]))
                        $failures[] = "{$line}: expected unit to NOT have keyword {$am[1]}";

                } elseif ($assert === 'ISLEADERUNIT') {
                    if (!$unit->isLeaderUnit())
                        $failures[] = "{$line}: expected unit to be a Leader Unit, but it is not";

                } elseif ($assert === 'NOTLEADERUNIT') {
                    if ($unit->isLeaderUnit())
                        $failures[] = "{$line}: expected unit to NOT be a Leader Unit, but it is";

                } else {
                    $failures[] = "Unknown unit assertion in: {$line}";
                }

            } elseif (preg_match('/^P(\d+)LEADERCOUNT:(\d+)$/', $line, $m)) {
                // Twin Suns: number of live leaders for a seat.
                $p = intval($m[1]); $expected = intval($m[2]);
                $arr = &GetLeader($p);
                $live = 0;
                for ($i = 0; $i < count($arr); $i++) {
                    if (!isset($arr[$i]->removed) || !$arr[$i]->removed) $live++;
                }
                if ($live !== $expected)
                    $failures[] = "{$line}: expected {$expected} live leaders, got {$live}";

            } elseif (preg_match('/^P(\d+)LEADER(\d+)DEPLOYED:(true|false)$/', $line, $m)) {
                // Twin Suns: deployed state of the $idx-th live leader (per-instance).
                $p = intval($m[1]); $idx = intval($m[2]); $expected = ($m[3] === 'true');
                $L = SWUGetLeaderByIndex($p, $idx);
                if ($L === null) $failures[] = "{$line}: no live leader at index {$idx}";
                elseif ((bool)($L->Deployed ?? false) !== $expected)
                    $failures[] = "{$line}: leader {$idx} deployed=" . var_export((bool)($L->Deployed ?? false), true)
                                . ", expected " . var_export($expected, true);

            } elseif (preg_match('/^P(\d+)LEADER(\d+):(READY|EXHAUSTED)$/', $line, $m)) {
                // Twin Suns: ready/exhausted state of the $idx-th live leader (per-instance).
                $p = intval($m[1]); $idx = intval($m[2]); $wantReady = ($m[3] === 'READY');
                $L = SWUGetLeaderByIndex($p, $idx);
                if ($L === null) $failures[] = "{$line}: no live leader at index {$idx}";
                elseif ((bool)($L->Ready ?? false) !== $wantReady)
                    $failures[] = "{$line}: leader {$idx} " . ((bool)($L->Ready ?? false) ? 'ready' : 'exhausted')
                                . ", expected " . ($wantReady ? 'ready' : 'exhausted');

            } elseif (preg_match('/^P(\d+)LEADER:(.+)$/', $line, $m)) {
                $p      = intval($m[1]);
                $assert = $m[2];
                try {
                    $leader = $g->state->player($p)->leader;
                } catch (RuntimeException $e) {
                    $failures[] = "{$line}: " . $e->getMessage();
                    continue;
                }

                if ($assert === 'READY') {
                    if (!$leader->isReady())
                        $failures[] = "{$line}: leader is exhausted, expected ready";

                } elseif ($assert === 'EXHAUSTED') {
                    if ($leader->isReady())
                        $failures[] = "{$line}: leader is ready, expected exhausted";

                } elseif ($assert === 'DEPLOYED') {
                    if (!$leader->isDeployed())
                        $failures[] = "{$line}: leader is not deployed, expected deployed";

                } elseif ($assert === 'NOTDEPLOYED') {
                    if ($leader->isDeployed())
                        $failures[] = "{$line}: leader is deployed, expected not deployed";

                } elseif ($assert === 'EPICUSED') {
                    if (!$leader->epicActionUsed())
                        $failures[] = "{$line}: epic action is available, expected used";

                } elseif ($assert === 'EPICAVAILABLE') {
                    if ($leader->epicActionUsed())
                        $failures[] = "{$line}: epic action is used, expected available";

                } else {
                    $failures[] = "Unknown leader assertion in: {$line}";
                }

            } elseif (preg_match('/^P(\d+)BASE:(EPICUSED|EPICAVAILABLE)$/', $line, $m)) {
                $p      = intval($m[1]);
                $assert = $m[2];
                try {
                    $base = $g->state->player($p)->base;
                } catch (RuntimeException $e) {
                    $failures[] = "{$line}: " . $e->getMessage();
                    continue;
                }

                if ($assert === 'EPICUSED') {
                    if (!$base->epicActionUsed)
                        $failures[] = "{$line}: base epic action is available, expected used";

                } elseif ($assert === 'EPICAVAILABLE') {
                    if ($base->epicActionUsed)
                        $failures[] = "{$line}: base epic action is used, expected available";
                }

            } elseif (preg_match('/^EFFECTSTACKCOUNT:(\d+)$/', $line, $m)) {
                $stack = GetEffectStack();
                $count = count(array_filter($stack, fn($e) => empty($e->removed ?? false)));
                if ($count !== intval($m[1]))
                    $failures[] = "{$line}: expected {$m[1]} EffectStack entries, got {$count}";

            } elseif (preg_match('/^EFFECTSTACKHAS:(\w+)$/', $line, $m)) {
                $stack = GetEffectStack();
                $found = false;
                foreach ($stack as $e) {
                    if (!empty($e->removed ?? false)) continue;
                    if (($e->TriggerType ?? '') === $m[1]) { $found = true; break; }
                }
                if (!$found)
                    $failures[] = "{$line}: no EffectStack entry with TriggerType={$m[1]}";

            } elseif (preg_match('/^LOGCONTAINS:(.+)$/', $line, $m)) {
                $needle  = trim($m[1]);
                $rawLog  = $g->state->gameLog();
                $entries = $rawLog !== '' ? explode('<NL>', $rawLog) : [];
                $found   = false;
                foreach ($entries as $entry) {
                    $parts = explode('|', $entry, 3);
                    $text  = $parts[2] ?? '';
                    if (str_contains($text, $needle)) { $found = true; break; }
                }
                if (!$found)
                    $failures[] = "{$line}: no log entry whose text contains '{$needle}'";

            } elseif (preg_match('/^LASTLOGCONTAINS:(.+)$/', $line, $m)) {
                $needle  = trim($m[1]);
                $rawLog  = $g->state->gameLog();
                $entries = $rawLog !== '' ? array_filter(explode('<NL>', $rawLog)) : [];
                $last    = end($entries);
                $parts   = $last !== false ? explode('|', $last, 3) : [];
                $text    = $parts[2] ?? '';
                if (!str_contains($text, $needle))
                    $failures[] = "{$line}: last log entry text '{$text}' does not contain '{$needle}'";

            } elseif (preg_match('/^P(\d+)HANDPILOTPLAYABLE:(\d+)$/', $line, $m)) {
                $p   = intval($m[1]);
                $idx = intval($m[2]);
                $list = $g->getPilotPlayableHand($p);
                if (!in_array($idx, $list, true))
                    $failures[] = "{$line}: hand index {$idx} not in pilotPlayableHand " . json_encode($list);

            } elseif (preg_match('/^P(\d+)HANDPILOTPLAYABLENOT:(\d+)$/', $line, $m)) {
                $p   = intval($m[1]);
                $idx = intval($m[2]);
                $list = $g->getPilotPlayableHand($p);
                if (in_array($idx, $list, true))
                    $failures[] = "{$line}: hand index {$idx} should NOT be in pilotPlayableHand " . json_encode($list);

            } else {
                $failures[] = "Unknown EXPECT assertion: {$line}";
            }
        }
        return $failures;
    }
}

// Twin Suns (Phase 3): decode a WHEN attack target that names a SPECIFIC opponent seat, for N-player
// combat tests. 'P<seat>G<idx>' → "p{seat}GroundArena-{idx}", 'P<seat>S<idx>' → "p{seat}SpaceArena-{idx}",
// 'P<seat>B' → "p{seat}Base-0". Returns null for any other form (the 2-player 'BASE'/'S<n>'/'G<n>'/index
// syntaxes are handled by the caller and stay byte-identical).
function _twSchemaSeatTarget(string $target): ?string {
    if (!preg_match('/^P(\d+)([GSB])(\d*)$/', $target, $m)) return null;
    $seat = intval($m[1]);
    $idx  = $m[3] === '' ? 0 : intval($m[3]);
    if ($m[2] === 'B') return "p{$seat}Base-0";
    return $m[2] === 'G' ? "p{$seat}GroundArena-{$idx}" : "p{$seat}SpaceArena-{$idx}";
}
