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
                             'WithP1BaseUpgrade',         'WithP2BaseUpgrade',
                             'WithP3BaseUpgrade',         'WithP4BaseUpgrade',
                             'WithP1BaseCaptive',         'WithP2BaseCaptive',
                             'WithP3BaseCaptive',         'WithP4BaseCaptive',
                             'WithP1GroundArenaUpgrade',  'WithP2GroundArenaUpgrade',
                             'WithP3GroundArenaUpgrade',  'WithP4GroundArenaUpgrade',
                             'WithP3SpaceArenaUpgrade',   'WithP4SpaceArenaUpgrade',
                             'WithP3GroundArenaPilot',    'WithP4GroundArenaPilot',
                             'WithP3SpaceArenaPilot',     'WithP4SpaceArenaPilot',
                             'WithP3GroundArenaCaptive',  'WithP4GroundArenaCaptive',
                             'WithP3SpaceArenaCaptive',   'WithP4SpaceArenaCaptive',
                             'WithP1SpaceArenaUpgrade',   'WithP2SpaceArenaUpgrade',
                             'WithP1GroundArenaPilot',    'WithP2GroundArenaPilot',
                             'WithP1SpaceArenaPilot',     'WithP2SpaceArenaPilot',
                             'WithP1GroundArenaCaptive',  'WithP2GroundArenaCaptive',
                             'WithP1SpaceArenaCaptive',   'WithP2SpaceArenaCaptive',
                             'WithP1GroundArenaControlled', 'WithP2GroundArenaControlled',
                             'WithP3GroundArenaControlled', 'WithP4GroundArenaControlled',
                             'WithP1SpaceArenaControlled',  'WithP2SpaceArenaControlled',
                             'WithP3SpaceArenaControlled',  'WithP4SpaceArenaControlled',
                             'WithP1ResourceControlled',    'WithP2ResourceControlled',
                             'WithP3ResourceControlled',    'WithP4ResourceControlled',
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
                            'WithP1BaseUpgrade', 'WithP2BaseUpgrade',
                            'WithP3BaseUpgrade', 'WithP4BaseUpgrade',
                            'WithP1BaseCaptive', 'WithP2BaseCaptive',
                            'WithP3BaseCaptive', 'WithP4BaseCaptive',
                            'WithP1GroundArenaUpgrade', 'WithP2GroundArenaUpgrade',
                            'WithP3GroundArenaUpgrade', 'WithP4GroundArenaUpgrade',
                            'WithP1SpaceArenaUpgrade', 'WithP2SpaceArenaUpgrade',
                            'WithP3SpaceArenaUpgrade', 'WithP4SpaceArenaUpgrade',
                            'WithP1GroundArenaPilot', 'WithP2GroundArenaPilot',
                            'WithP3GroundArenaPilot', 'WithP4GroundArenaPilot',
                            'WithP1SpaceArenaPilot', 'WithP2SpaceArenaPilot',
                            'WithP3SpaceArenaPilot', 'WithP4SpaceArenaPilot',
                            'WithP1GroundArenaCaptive', 'WithP2GroundArenaCaptive',
                            'WithP3GroundArenaCaptive', 'WithP4GroundArenaCaptive',
                            'WithP1SpaceArenaCaptive', 'WithP2SpaceArenaCaptive',
                            'WithP3SpaceArenaCaptive', 'WithP4SpaceArenaCaptive',
                            'WithP1GroundArenaControlled', 'WithP2GroundArenaControlled',
                            'WithP3GroundArenaControlled', 'WithP4GroundArenaControlled',
                            'WithP1SpaceArenaControlled', 'WithP2SpaceArenaControlled',
                            'WithP3SpaceArenaControlled', 'WithP4SpaceArenaControlled',
                            'WithP1ResourceControlled', 'WithP2ResourceControlled',
                            'WithP3ResourceControlled', 'WithP4ResourceControlled'];
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
        // Split owner/controller seats (the end state after a control-take: NGOR / Change of Heart).
        // "WithP{n}{Ground|Space}ArenaControlled: CARD:ownerSeat" — CARD sits in P{n}'s arena, CONTROLLED
        // by P{n} but OWNED by ownerSeat, so a return-to-hand sends it to the owner's hand.
        // Seats 3/4 supported since 2026-08-24: a four-seat owner/controller split could not be EXPRESSED
        // before, so any card whose rule turns on controller-vs-owner (TS26_15 C-3P0, the Bounty
        // collector, SHD_161) had no far-seat fixture — the gap was invisible by construction.
        // ⚠ The implicit owner default was `3 - $seat`, a two-seat identity (1↔2) that yields 0 and -1 for
        // seats 3/4. It is now "the lowest OTHER seat", which is byte-identical at two seats and sane
        // above; far-seat tests should still name the owner explicitly rather than lean on it.
        foreach ([1, 2, 3, 4] as $seat) {
            $defOwner = ($seat === 1) ? 2 : 1;
            // Optional 3rd field = status (1 ready / 0 exhausted), defaulting to ready — needed for any
            // "when this unit readies" effect on a unit whose control has changed (JTL_192 In Debt).
            foreach ($given["WithP{$seat}GroundArenaControlled"] ?? [] as $spec) {
                [$cid, $owner, $st] = array_pad(explode(':', $spec), 3, '');
                $b->WithControlledGroundUnitForPlayer($seat, $cid, intval($owner) ?: $defOwner,
                    $st === '' ? true : (intval($st) === 1));
            }
            foreach ($given["WithP{$seat}SpaceArenaControlled"] ?? [] as $spec) {
                [$cid, $owner] = array_pad(explode(':', $spec), 2, '');
                $b->WithControlledSpaceUnitForPlayer($seat, $cid, intval($owner) ?: $defOwner);
            }
            // A resource in P{seat}'s zone OWNED by another seat (e.g. after SHD_122 Arquitens resources an
            // enemy card): "WithP{seat}ResourceControlled: CARD:ownerSeat".
            foreach ($given["WithP{$seat}ResourceControlled"] ?? [] as $spec) {
                [$cid, $owner] = array_pad(explode(':', $spec), 2, '');
                $b->WithControlledResourceForPlayer($seat, $cid, intval($owner) ?: $defOwner);
            }
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
        // — seats 1/2 come from CommonSetup. DEPLOYED far-seat leaders are supported since 2026-08-24:
        // GameStateBuilder now splices a real arena unit and links DeployedUniqueID, mirroring the
        // seats-1/2 path (before that, `deployed` set the flag and created no unit at all).
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

        // Units ARRESTED and held under a base (WithP{n}BaseCaptive: CARD_ID[:owner], owner defaults to
        // the other seat). ⚠ A base captive is NOT a subcard — it is a "SWU_BASECAPTIVE|CardID|owner"
        // flag in the CAPTURING player's GlobalEffects (see _SWUBaseCaptureUnit), drained at
        // RegroupPhaseStart. Seeding it directly exists because you cannot otherwise build a
        // multi-arrest board in Twin Suns: a seat gets one action per turn, and letting the other
        // seats pass to come back round reaches the regroup phase, which rescues every captive.
        foreach ([1, 2, 3, 4] as $pn) {
            foreach ($given["WithP{$pn}BaseCaptive"] ?? [] as $spec) {
                foreach (explode(',', trim($spec)) as $one) {
                    $one = trim($one); if ($one === '') continue;
                    $bits   = explode(':', $one);
                    $cid    = trim($bits[0]);
                    $owner  = isset($bits[1]) ? intval($bits[1]) : ($pn === 1 ? 2 : 1);
                    // ⚠ Through the BUILDER, not a direct AddGlobalEffects: the game is not
                    // materialised at this point, so writing straight to the zone is a silent no-op.
                    if ($cid !== '') $b->WithGlobalEffectForPlayer($pn, 'SWU_BASECAPTIVE|' . $cid . '|' . $owner);
                }
            }
        }
        // FORTIFY upgrades attached to a BASE (WithP{n}BaseUpgrade: CARD_ID). No index — a base is a
        // single host, unlike an arena which needs idx:CARD_ID.
        // Seats 3/4 supported since 2026-08-24 (Twin Suns harness sweep).
        foreach ([1, 2, 3, 4] as $pn) {
            foreach ($given["WithP{$pn}BaseUpgrade"] ?? [] as $spec) {
                foreach (explode(',', trim($spec)) as $cid) {
                    $cid = trim($cid);
                    if ($cid !== '') $b->WithUpgradeOnBaseForPlayer($pn, $cid);
                }
            }
        }

        // Initial upgrades on arena units (multi-value: WithP{n}{Ground|Space}ArenaUpgrade: idx:CARD_ID).
        // ⚠ Seats 3/4 supported since 2026-08-24. Until then a FAR-SEAT unit could not carry an upgrade at
        // all — no Experience token, no Shield, no attachment — so any four-seat assertion about a
        // buffed/upgraded far-seat unit was unwritable by construction.
        foreach ([1, 2, 3, 4] as $pn) {
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
        foreach ([1, 2, 3, 4] as $pn) {
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

        // Initial CAPTIVE units held by an arena unit (WithP{n}{Ground|Space}ArenaCaptive: idx:CARD_ID).
        // Seeds a captured-unit subcard (IsCaptive=true) on the captor at $unitIdx, OWNED by the OTHER
        // player (captives are enemy units) — so a rescue (CR 8.34.4, e.g. JTL_050 Phantom II leaving play
        // as a unit) returns it to that owner's arena. The only harness way to pre-seat a captive.
        // Seats 3/4 supported since 2026-08-24. ⚠ The implicit owner is "the lowest OTHER seat", which is
        // byte-identical at two seats; a far-seat captive test should name the owner explicitly if it
        // matters, since "captives are enemy units" does not identify WHICH enemy above two seats.
        foreach ([1, 2, 3, 4] as $pn) {
            $owner = ($pn === 1) ? 2 : 1;
            foreach (['Ground', 'Space'] as $arenaType) {
                $key    = "WithP{$pn}{$arenaType}ArenaCaptive";
                $byUnit = [];
                foreach ($given[$key] ?? [] as $spec) {
                    [$idxStr, $cardID] = array_pad(explode(':', trim($spec), 2), 2, '');
                    $byUnit[intval($idxStr)][] = trim($cardID);
                }
                $method = "WithUpgradesOn{$arenaType}UnitForPlayer";
                foreach ($byUnit as $unitIdx => $cardIDs) {
                    $captives = array_map(function($cid) use ($pn, $owner) {
                        $c = GameStateBuilder::Upgrade($cid, $owner);   // Owner = the captive's owner (opponent)
                        $c['Controller'] = $pn;                          // controlled/guarded by the captor
                        $c['IsCaptive']  = true;
                        return $c;
                    }, $cardIDs);
                    $b->$method($pn, $unitIdx, $captives);
                }
            }
        }

        // Defeated players (sets gWinner + max base damage).
        foreach (explode(',', $given['WithDefeatedPlayer'] ?? '') as $dpStr) {
            $dp = intval(trim($dpStr));
            if ($dp > 0) $b->WithDefeatedPlayer($dp);
        }

        // The Force token (CR §37): WithP{n}Force: true → that player controls their Force token.
        // Seats 3/4 supported since 2026-08-24.
        foreach ([1, 2, 3, 4] as $pn) {
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
        // Twin Suns: seats 3/4 need credits too, or a four-seat "an enemy Credit token" assertion cannot
        // be WRITTEN — the fifth two-seat limit this sweep found in the harness itself, after
        // P#DISCARDUNIT, WithP3/P4Base, PlayFromOpponentDiscard's missing seat, and the absent
        // unit-action offer assertion.
        foreach ([1, 2, 3, 4] as $pn) {
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
                // The BASE's once-per-game Epic Action. CommonSetup already forwards
                // baseEpicActionUsed to MyBase()/TheirBase(); only the option to set it was missing.
                case 'myBaseEpicUsed':
                    $myOpts['baseEpicActionUsed'] = $val === '1' || $val === 'true';
                    break;
                case 'theirBaseEpicUsed':
                    $theirOpts['baseEpicActionUsed'] = $val === '1' || $val === 'true';
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

    // The exact legal-target set of a pending target-choice decision, as an array of candidate mzIDs.
    // A target choice stores its candidates in Param as an '&'-joined mzID list; MZMULTICHOOSE prefixes
    // it with "min|max|" (e.g. "1|2|theirGroundArena-0&theirSpaceArena-1"). Non-target decisions
    // (OPTIONCHOOSE labels, YESNO, TOPDECKSEARCH) return [] — SELECTABLE asserts don't apply to them.
    private static function _selectableTargets(object $pending): array {
        $type  = strtoupper((string)($pending->Type ?? ''));
        if (!in_array($type, ['MZCHOOSE', 'MZMAYCHOOSE', 'MZMULTICHOOSE'], true)) return [];
        $param = (string)($pending->Param ?? '');
        if ($type === 'MZMULTICHOOSE' && preg_match('/^\d+\|\d+\|(.*)$/s', $param, $mm)) $param = $mm[1];
        return array_values(array_filter(array_map('trim', explode('&', $param)), fn($s) => $s !== '' && $s !== '-'));
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
                // Else 'BASE' → the one opponent's base; a full mzID ('theirSpaceArena-2') is taken as-is;
                // 'S<idx>' → cross-arena space (JTL_259); a bare number → this arena; empty → index 0.
                if (($pt = _twSchemaSeatTarget($target)) !== null) $def = $pt;
                elseif ($target === 'BASE')              $def = 'theirBase-0';
                elseif (_schemaIsFullMzID($target))      $def = $target;
                elseif (str_starts_with($target, 'S'))   $def = 'theirSpaceArena-' . intval(substr($target, 1));
                else                                     $def = 'theirGroundArena-' . _schemaAttackIdx($target);
                $g->declareAttack($player, $atk, $def);
                break;
            }

            case 'AttackSpaceArena': {
                [$unitIdx, $target] = array_pad(explode(':', $args, 2), 2, '');
                $atk = "mySpaceArena-" . intval($unitIdx);
                // Twin Suns 'P<seat>...' seat-specific target (see AttackGroundArena).
                // Else 'BASE' → the one opponent's base; a full mzID is taken as-is; 'G<idx>' →
                // cross-arena ground (Strafing Gunship); a bare number → this arena; empty → index 0.
                if (($pt = _twSchemaSeatTarget($target)) !== null) $def = $pt;
                elseif ($target === 'BASE')             $def = 'theirBase-0';
                elseif (_schemaIsFullMzID($target))     $def = $target;
                elseif (str_starts_with($target, 'G'))   $def = 'theirGroundArena-' . intval(substr($target, 1));
                else                                     $def = 'theirSpaceArena-' . _schemaAttackIdx($target);
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
            case 'PlayFromOpponentDiscard': {
                // "PlayFromOpponentDiscard: <idx>"        → the one opponent (2-player, unchanged)
                // "PlayFromOpponentDiscard: P<seat>:<idx>" → that seat's pile (Twin Suns)
                if (preg_match('/^P(\d+):(\d+)$/', trim((string)$args), $m)) {
                    $g->playFromOpponentDiscard($player, intval($m[2]), intval($m[1]));
                } else {
                    $g->playFromOpponentDiscard($player, intval($args));
                }
                break;
            }

            case 'AnswerDecision':
                $g->answerDecision($player, $args);
                break;

            case 'Drain':
                // Run pending STATIC entries on $player's queue (cross-player reaction drain —
                // mirrors production's post-action ProcessGoldfishAutomation). No args.
                $g->drainQueue($player);
                break;

            case 'SimulateRequestBoundary':
                // Model the fresh-process boundary a real interactive decision creates: transient
                // in-memory continuation globals reset while serialized gamestate persists. Catches
                // bugs that park cross-decision state in a transient global. No args (player ignored).
                $g->simulateRequestBoundary();
                break;

            case 'Undo':        $g->undo($player); break;         // multi-step: revert one action
            case 'UndoPhase':   $g->undoPhase($player); break;    // jump to start of current phase
            case 'ApproveUndo': $g->approveUndo($player); break;  // opponent approves a pending request
            case 'DenyUndo':    $g->denyUndo($player); break;     // opponent denies a pending request

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
     *   P{n}OnlyActions: true     — the same shorthand for ANY seat (added 2026-08-24). Only P1 existed
     *                               before, so a four-seat section in which a FAR seat is the sole actor
     *                               could not be written — the gap that left SHD_161's bounty defect
     *                               unassertable. The claimed initiative goes to the lowest OTHER seat,
     *                               so P1OnlyActions stays byte-identical (initiative → P2).
     */
    public static function applyPostSetupDirectives(array $givenLines): void {
        $given = self::_parseGiven($givenLines);
        // WithPrivateGame: true -> SimGameIsPrivateGame returns true, so undo is always free (no consent).
        // Default public (false). Reset every test so it never leaks across cases in one process.
        $GLOBALS['SWU_TEST_FORCE_PRIVATE'] = strtolower($given['WithPrivateGame'] ?? 'false') === 'true';
        for ($oaSeat = 1; $oaSeat <= 4; ++$oaSeat) {
            if (strtolower($given["P{$oaSeat}OnlyActions"] ?? '') !== 'true') continue;
            $oaHolder = ($oaSeat === 1) ? 2 : 1;   // any OTHER seat; 2 for seat 1 keeps P1OnlyActions identical
            SetInitiativeCounter("P{$oaHolder}_CLAIMED");
            SetTurnPlayer($oaSeat);
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

            } elseif (preg_match('/^SWUVAR:([A-Z0-9_]+):(.*)$/', $line, $m)) {
                // A raw SWU game variable (SetSWUVar/GetSWUVar), e.g. SWU_TS_GAME_ENDING.
                // Needed to assert the MECHANISM rather than an outcome two rules happen to share:
                // in Team Suns an elimination must not set SWU_TS_GAME_ENDING at all, and asserting
                // "no winner yet" cannot tell that apart from Twin Suns' deferred scoring.
                $expected = $m[2];
                $actual   = (string)GetSWUVar($m[1]);
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected '{$expected}', got '{$actual}'";

            } elseif ($line === 'NOGAMEWINNER') {
                // No winner has been declared yet. The counterpart to GAMEWINNERS:, which matches
                // [0-9,]+ and so cannot express an empty set. Team Suns needs this: an elimination
                // must NOT end the game until a whole team is gone.
                $actual = SWUGetGameWinners();
                if (!empty($actual))
                    $failures[] = "NOGAMEWINNER: expected no winner, got [" . implode(',', $actual) . "]";

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

            } elseif (preg_match('/^P(\d+)BASE(UPGRADE|CAPTIVE)COUNT:(\d+)$/', $line, $m)) {
                // The base's Subcards array holds Fortify upgrades AND arrest captives together,
                // separated by IsCaptive. These are the two halves — assert them independently, or a
                // change that mixed the two up would keep the TOTAL right and still be wrong.
                $p = intval($m[1]); $expected = intval($m[3]);
                $bases = GetBase($p);
                $obj   = $bases[0] ?? null;
                $actual = ($obj === null) ? 0
                    : ($m[2] === 'UPGRADE' ? BaseUpgradeCount($obj) : BaseCaptiveCount($obj));
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected {$expected} base "
                        . ($m[2] === 'UPGRADE' ? 'Fortify upgrade(s)' : 'captive(s)') . ", got {$actual}";

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

            } elseif (preg_match('/^P(\d+)HANDGLOW(NOT)?:(\d+)$/', $line, $m)) {
                // Does the hand card at index N light up as playable? Asserts the REAL transport value
                // — SelectionMetadata() is what GetNextTurn emits per card — rather than the
                // affordability predicate alone, so the whole glow chain is covered.
                // Note SelectionMetadata only highlights during MAIN with BOTH decision queues empty
                // and only for the turn player, so leave no decision pending in a section using this.
                $p = intval($m[1]); $wantGlow = ($m[2] !== 'NOT'); $idx = intval($m[3]);
                $hand = GetHand($p);
                if (!isset($hand[$idx]) || !empty($hand[$idx]->removed)) {
                    $failures[] = "{$line}: no hand card at index {$idx} for player {$p}";
                } else {
                    $meta = json_decode(SelectionMetadata($hand[$idx]), true);
                    $glows = is_array($meta) && isset($meta['color']) && $meta['color'] === 'rgba(0, 255, 0, 0.95)';
                    if ($glows !== $wantGlow)
                        $failures[] = "{$line}: expected hand card {$idx} (" . ($hand[$idx]->CardID ?? '?')
                            . ") to " . ($wantGlow ? 'GLOW' : 'NOT glow') . ", got " . json_encode($meta);
                }

            } elseif (preg_match('/^P(\d+)TEMPZONECOUNT:(\d+)$/', $line, $m)) {
                // TempZone is the scratch staging zone (SWUQueueDefeatUpgrade, the Credit-payment
                // picker, Law119Trigger …). It has no board slot, so nothing else can observe a leak:
                // an effect that stages into it and forgets to drain leaves phantom cards that only
                // show up in the NEXT popup. Assert 0 after any staged effect resolves.
                $p = intval($m[1]); $expected = intval($m[2]);
                $actual = count(array_filter(GetTempZone($p), fn($o) => empty($o->removed)));
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected {$expected} staged TempZone cards, got {$actual}";

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
                //
                // Compared on the RENDERED text: underscores are normalised to spaces on BOTH sides, so a
                // test asserts what the player reads, not the transport encoding. The DecisionQueue row is
                // space-delimited, so AddDecision stores every tooltip underscored (see the note there) —
                // whether the source literal was written with underscores or with spaces. Without this,
                // every tooltip assertion is coupled to which style its card file happens to use, and
                // rewording a prompt from underscores to spaces breaks a test about behaviour it did not
                // change. Existing underscored expectations keep passing: both sides normalise identically.
                $p       = intval($m[1]);
                $pending = $g->state->pendingDecision($p);
                $norm    = fn($t) => str_replace('_', ' ', (string)$t);
                if ($pending === null)
                    $failures[] = "{$line}: expected a pending decision, but none found";
                elseif ($norm($pending->Tooltip ?? '') !== $norm($m[2]))
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

            } elseif (preg_match('/^P(\d+)SELECTABLE(HAS|NOT):(.+)$/', $line, $m)) {
                // Membership of a target mzID in a pending target-choice's exact legal-target set (the
                // SWUSim exact-legal-target assertion (membership form). Reads the
                // pending decision's Param — the '&'-joined candidate mzIDs (MZCHOOSE / MZMAYCHOOSE /
                // MZMULTICHOOSE, the latter prefixed "min|max|"). Leave the decision pending (don't answer
                // it) so it can be inspected. mzIDs are in the DECIDING player's frame (my*/their*).
                $p        = intval($m[1]);
                $wantHas  = ($m[2] === 'HAS');
                $mzWanted = $m[3];
                $pending  = $g->state->pendingDecision($p);
                if ($pending === null) {
                    $failures[] = "{$line}: expected a pending target-choice decision, but none found";
                } else {
                    $cands   = self::_selectableTargets($pending);
                    $present = in_array($mzWanted, $cands, true);
                    if ($wantHas && !$present)
                        $failures[] = "{$line}: '{$mzWanted}' not selectable [" . implode(',', $cands) . "]";
                    if (!$wantHas && $present)
                        $failures[] = "{$line}: '{$mzWanted}' unexpectedly selectable [" . implode(',', $cands) . "]";
                }

            } elseif (preg_match('/^P(\d+)UNITACTIONS(EXACT|HAS|NOT):(.*)$/', $line, $m)) {
                // ── THE UNIT-ACTION OFFER LIST ────────────────────────────────────────────────────────
                // Which units $player is actually OFFERED an "Action:" on, as mzIDs in their own frame —
                // i.e. $data['unitActions'] from SWUComputeActionsData, the same list the client uses to
                // decide what is clickable.
                //
                // ⚠ ADDED 2026-08-24 to close a real blind spot. The harness's `UseUnitAbility` command
                // calls the action DIRECTLY and never consults the offer list, so a card could be perfectly
                // gated and still be unreachable in a live game — invisible and unclickable — with every
                // test green. Three cards depended on that unverified path:
                //   • LAW_156 Hunter For Hire and SHD_256 Mercenary Gunship ("Any player may use this
                //     ability"), and
                //   • TS26_15 C-3P0 ("Only opponents may use this ability"),
                // all of which must be surfaced on a board the actor does NOT control ($anyPlayerUnitActions).
                // Before this assertion existed, unregistering a card from that list failed nothing.
                //
                // ⚠ Like P#HANDGLOW, this reads a value that only exists while the seat is ACTIVE:
                // SWUComputeActionsData gates on MAIN + it being $player's turn + BOTH decision queues
                // empty. Leave no decision pending in a section that uses it, or the list is empty by
                // construction rather than by fault.
                $p    = intval($m[1]);
                $kind = $m[2];
                $want = array_values(array_filter(explode('&', $m[3]), fn($x) => $x !== ''));
                $acts = [];
                if (function_exists('SWUComputeActionsData')) {
                    $d = SWUComputeActionsData($p);
                    $acts = array_values($d['unitActions'] ?? []);
                }
                if ($kind === 'EXACT') {
                    $a = $acts; $b = $want; sort($a); sort($b);
                    if ($a !== $b)
                        $failures[] = "{$line}: expected exactly [" . implode('&', $b) . "], got [" . implode('&', $a) . "]";
                } elseif ($kind === 'HAS') {
                    foreach ($want as $w)
                        if (!in_array($w, $acts, true))
                            $failures[] = "{$line}: unit action '{$w}' was NOT offered to P{$p} [" . implode('&', $acts) . "]";
                } else { // NOT
                    foreach ($want as $w)
                        if (in_array($w, $acts, true))
                            $failures[] = "{$line}: unit action '{$w}' was unexpectedly offered to P{$p} [" . implode('&', $acts) . "]";
                }

            } elseif (preg_match('/^P(\d+)SELECTABLEEXACT:(.*)$/', $line, $m)) {
                // The FULL exact legal-target set of a pending target-choice, order-insensitive ('&'-joined
                // mzIDs, deciding player's frame). Asserts the exact legal-target set of a choice.
                $p       = intval($m[1]);
                $want    = array_values(array_filter(explode('&', $m[2]), fn($s) => $s !== ''));
                $pending = $g->state->pendingDecision($p);
                if ($pending === null) {
                    $failures[] = "{$line}: expected a pending target-choice decision, but none found";
                } else {
                    $cands = self::_selectableTargets($pending);
                    $a = $cands; $b = $want; sort($a); sort($b);
                    if ($a !== $b)
                        $failures[] = "{$line}: expected exactly [" . implode('&', $b) . "], got [" . implode('&', $a) . "]";
                }

            } elseif (preg_match('/^P(\d+)(NOT)?SEESTOPCARD$/', $line, $m)) {
                // Does player N currently have the "you may look at the top card of your deck at any
                // time" permission (LAW_094 Hondo / HMW_205 Intelligence Agency)? This is the harness's
                // FIRST visibility assertion — that gap is exactly why the clause was previously
                // recorded as untestable. It asserts the server-side PERMISSION, which is the half that
                // is game logic; the per-viewer PAYLOAD (that the entitled seat receives the card and
                // the opponent does not) is verified separately against a live GetNextTurn, since the
                // in-process runner renders no transport.
                $p    = intval($m[1]);
                $want = ($m[2] ?? '') === '';
                $has  = function_exists('_SWUCanSeeOwnTopCard') && _SWUCanSeeOwnTopCard($p);
                if ($has !== $want) {
                    $failures[] = "{$line}: expected player {$p} " . ($want ? 'to' : 'NOT to')
                        . ' have the look-at-top-card permission, but they ' . ($has ? 'do' : 'do not');
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

            // ⚠ Widened from P[12] to any seat (2026-08-21): the Twin Suns sweep needs to assert WHICH
            // far seat's discard a card landed in — with P1/P2 only, a four-seat "who took from whom"
            // section cannot be written at all, and counts alone do not distinguish direction.
            } elseif (preg_match('/^P(\d+)DISCARDUNIT:(\d+):(CARDID|MODIFIER|FROM):(.*)$/', $line, $m)) {
                $p       = intval($m[1]);
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

                // Traits may be multi-word ("Capital Ship", "Force Wielder"), so spaces are allowed.
                } elseif (preg_match('/^HASTRAIT:([\w ]+)$/', $assert, $am)) {
                    if (!$unit->hasTrait(trim($am[1])))
                        $failures[] = "{$line}: expected unit to have trait " . trim($am[1]);

                } elseif (preg_match('/^NOTTRAIT:([\w ]+)$/', $assert, $am)) {
                    if ($unit->hasTrait(trim($am[1])))
                        $failures[] = "{$line}: expected unit to NOT have trait " . trim($am[1]);

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

            } elseif (preg_match('/^P(\d+)(NO)?GLOBALEFFECT:(\S+)$/', $line, $m)) {
                // GlobalEffects phase/round flags (SWU_FRIENDLY_UPGRADE_DEFEATED, SWU_PLAYED_*, …).
                // There was a GIVEN seeder (WithP{n}GlobalEffect) but no way to OBSERVE one.
                $has = GlobalEffectCount(intval($m[1]), $m[3]) > 0;
                $want = ($m[2] ?? '') !== 'NO';
                if ($has !== $want)
                    $failures[] = "{$line}: expected flag {$m[3]} " . ($want ? "SET" : "ABSENT")
                                . " for P{$m[1]}, it was " . ($has ? "set" : "absent");

            } elseif (preg_match('/^P(\d+)BASE:UPGRADECOUNT:(\d+)$/', $line, $m)) {
                // Attached FORTIFY upgrades on that player's base.
                try {
                    $got = $g->state->player(intval($m[1]))->base->upgradeCount;
                } catch (RuntimeException $e) { $failures[] = "{$line}: " . $e->getMessage(); continue; }
                if ($got !== intval($m[2]))
                    $failures[] = "{$line}: expected {$m[2]} base upgrade(s), got {$got}";

            } elseif (preg_match('/^P(\d+)BASE:UPGRADE:(\d+):CARDID:(\S+)$/', $line, $m)) {
                try {
                    $ups = $g->state->player(intval($m[1]))->base->upgrades;
                } catch (RuntimeException $e) { $failures[] = "{$line}: " . $e->getMessage(); continue; }
                $idx = intval($m[2]);
                $got = isset($ups[$idx]) ? ($ups[$idx]->CardID ?? '') : '(none)';
                if ($got !== $m[3])
                    $failures[] = "{$line}: expected {$m[3]} at base upgrade index {$idx}, got {$got}";

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
// True if an attack target is already a full zone mzID ("theirGroundArena-2", "p3SpaceArena-0",
// "theirBase-0"). Those are passed through verbatim.
//
// ⚠ WHY THIS EXISTS: the attack steps used to do `intval($target)` on whatever was after the second
// colon. `intval("theirGroundArena-1")` is **0**, so writing the full mzID — the natural thing to do,
// since every AnswerDecision uses that form — silently attacked index 0 instead. Tests written that
// way passed against the WRONG defender, and it manufactured at least one phantom "engine bug".
function _schemaIsFullMzID(string $target): bool {
    return (bool)preg_match('/^(their|my|p\d+)[A-Za-z]+-\d+$/', $target);
}

// Parse the bare-number form of an attack target index. Anything that is neither empty nor a plain
// integer is a typo or an unsupported spelling — fail LOUDLY instead of silently resolving to 0.
function _schemaAttackIdx(string $target): int {
    if ($target === '') return 0;                       // "AttackGroundArena:0" → first enemy unit
    if (preg_match('/^\d+$/', $target)) return intval($target);
    throw new RuntimeException("unrecognised attack target '{$target}' "
        . "(use a bare index like '1', a full mzID like 'theirGroundArena-1', 'BASE', "
        . "'S<idx>'/'G<idx>' for cross-arena, or a Twin Suns 'P<seat>G<idx>')");
}

function _twSchemaSeatTarget(string $target): ?string {
    if (!preg_match('/^P(\d+)([GSB])(\d*)$/', $target, $m)) return null;
    $seat = intval($m[1]);
    $idx  = $m[3] === '' ? 0 : intval($m[3]);
    if ($m[2] === 'B') return "p{$seat}Base-0";
    return $m[2] === 'G' ? "p{$seat}GroundArena-{$idx}" : "p{$seat}SpaceArena-{$idx}";
}
