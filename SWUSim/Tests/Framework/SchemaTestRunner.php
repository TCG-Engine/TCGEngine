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

    // ── Parsing ──────────────────────────────────────────────────────

    private static function _parse(string $content): array {
        $sections = ['given' => [], 'when' => [], 'expect' => []];
        $current  = null;

        foreach (explode("\n", $content) as $raw) {
            $line = trim($raw);
            if ($line === '## GIVEN')  { $current = 'given';  continue; }
            if ($line === '## WHEN')   { $current = 'when';   continue; }
            if ($line === '## EXPECT') { $current = 'expect'; continue; }
            if ($current === null || $line === '') continue;
            // Strip inline comments, skip blank-after-strip lines.
            $clean = trim(preg_replace('/#.*$/', '', $line));
            if ($clean !== '') $sections[$current][] = $clean;
        }

        return ['ok' => true] + $sections;
    }

    private static function _parseGiven(array $lines): array {
        // These keys can appear more than once; their values accumulate as arrays.
        static $multiKeys = ['WithP1GroundArena',        'WithP2GroundArena',
                             'WithP1SpaceArena',          'WithP2SpaceArena',
                             'WithP1Hand',                'WithP2Hand',
                             'WithP1GroundArenaUpgrade',  'WithP2GroundArenaUpgrade',
                             'WithP1SpaceArenaUpgrade',   'WithP2SpaceArenaUpgrade',
                             'WithP1Deck',                'WithP2Deck'];
        // List-valued keys accept either one card ID per line OR a whitespace-separated
        // array on a single line, e.g. "WithP2Deck: [SOR_225 SEC_080 SOR_128]". Each token
        // becomes its own accumulated entry, so both forms (and a mix) interoperate.
        static $listKeys = ['WithP1Hand', 'WithP2Hand', 'WithP1Deck', 'WithP2Deck'];
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
        [$p1BaseID, $p1BaseDmg] = self::_parseBaseSpec($p1BaseSpec);
        [$p2BaseID, $p2BaseDmg] = self::_parseBaseSpec($p2BaseSpec);

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
            $b->MyBase($p1BaseID, $p1BaseDmg)
              ->MyLeader($p1Leader, $p1LeaderReady, $p1LeaderDeployed, $p1LeaderEpic)
              ->TheirBase($p2BaseID, $p2BaseDmg)
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
            [$cid, $ready, $dmg] = self::_parseUnitSpec($spec);
            $b->WithGroundUnitForPlayer(1, $cid, $ready, $dmg);
        }
        foreach ($given['WithP2GroundArena'] ?? [] as $spec) {
            [$cid, $ready, $dmg] = self::_parseUnitSpec($spec);
            $b->WithGroundUnitForPlayer(2, $cid, $ready, $dmg);
        }
        foreach ($given['WithP1SpaceArena'] ?? [] as $spec) {
            [$cid, $ready, $dmg] = self::_parseUnitSpec($spec);
            $b->WithSpaceUnitForPlayer(1, $cid, $ready, $dmg);
        }
        foreach ($given['WithP2SpaceArena'] ?? [] as $spec) {
            [$cid, $ready, $dmg] = self::_parseUnitSpec($spec);
            $b->WithSpaceUnitForPlayer(2, $cid, $ready, $dmg);
        }

        // Explicit hand cards (multi-value: WithP1Hand / WithP2Hand).
        foreach ($given['WithP1Hand'] ?? [] as $cid) $b->WithCardInHandForPlayer(1, trim($cid));
        foreach ($given['WithP2Hand'] ?? [] as $cid) $b->WithCardInHandForPlayer(2, trim($cid));

        // Individual deck cards (multi-value: WithP1Deck / WithP2Deck).
        foreach ($given['WithP1Deck'] ?? [] as $cid) $b->WithCardInDeckForPlayer(1, trim($cid));
        foreach ($given['WithP2Deck'] ?? [] as $cid) $b->WithCardInDeckForPlayer(2, trim($cid));

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
        foreach ([1, 2] as $pn) {
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
                case 'handCardIds':
                    $myOpts['handCardIds'] = array_map('trim', explode(',', $val));
                    break;
                case 'theirHandCardIds':
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
                case 'myLeaderDeployed':
                    $myOpts['leaderDeployed'] = $val === '1' || $val === 'true';
                    break;
                case 'theirLeaderDeployed':
                    $theirOpts['leaderDeployed'] = $val === '1' || $val === 'true';
                    break;
                case 'myLeaderReady':
                    $myOpts['leaderReady'] = $val === '1' || $val === 'true';
                    break;
                case 'theirLeaderReady':
                    $theirOpts['leaderReady'] = $val === '1' || $val === 'true';
                    break;
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

    // "SOR_024"    → ['SOR_024', 0]
    // "SOR_024:27" → ['SOR_024', 27]
    private static function _parseBaseSpec(string $spec): array {
        $parts = explode(':', trim($spec), 2);
        return [trim($parts[0]), isset($parts[1]) ? intval($parts[1]) : 0];
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
    private static function _parseUnitSpec(string $spec): array {
        $parts  = explode(':', trim($spec));
        $cardId = trim($parts[0]);
        $ready  = isset($parts[1]) ? (intval($parts[1]) === 1) : true;
        $damage = isset($parts[2]) ? intval($parts[2]) : 0;
        return [$cardId, $ready, $damage];
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
                // 'BASE' → enemy base; 'S<idx>' → cross-arena space target (JTL_259 Airspeeder); else ground.
                if ($target === 'BASE')                  $def = 'theirBase-0';
                elseif (str_starts_with($target, 'S'))   $def = 'theirSpaceArena-' . intval(substr($target, 1));
                else                                     $def = 'theirGroundArena-' . intval($target);
                $g->declareAttack($player, $atk, $def);
                break;
            }

            case 'AttackSpaceArena': {
                [$unitIdx, $target] = array_pad(explode(':', $args, 2), 2, '');
                $atk = "mySpaceArena-" . intval($unitIdx);
                // 'BASE' → enemy base; 'G<idx>' → cross-arena ground target (Strafing Gunship); else space.
                if ($target === 'BASE')                 $def = 'theirBase-0';
                elseif (str_starts_with($target, 'G'))   $def = 'theirGroundArena-' . intval(substr($target, 1));
                else                                     $def = 'theirSpaceArena-' . intval($target);
                $g->declareAttack($player, $atk, $def);
                break;
            }

            case 'Pass':
                $g->passAction($player);
                break;

            case 'Claim':
                $g->takeInitiative($player);
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
        ];
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

            } elseif (preg_match('/^P(\d+)BASEDMG:(\d+)$/', $line, $m)) {
                $p        = intval($m[1]);
                $expected = intval($m[2]);
                $actual   = $g->state->player($p)->base->damage;
                if ($actual !== $expected)
                    $failures[] = "{$line}: expected base damage {$expected}, got {$actual}";

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
