<?php
// SWU-PGN reader board → SWUSim gamestate, for the read-only replay viewer. The caller runs
// InitializeGamestate() first and writes the file after. A hidden card is the CardID 'CardBack';
// a card SWUSim does not know is a placeholder CardID whose name the client draws.
require_once __DIR__ . '/SwuPgnCardMap.php';

function SwuPgnPlaceholderId(string $pgnBaseId): string { return 'SWUPGNX_' . substr(md5($pgnBaseId), 0, 10); }

function SwuPgnBoardPhase($phase): string { return ['setup' => 'APS', 'action' => 'MAIN', 'regroup' => 'RGS'][$phase] ?? 'MAIN'; }

// The ReducedState keys this converter reads (test_swupgn_board.php fails when the reader grows a new one).
function SwuPgnBoardHandledFields(): array
{
    return ['state.round', 'state.phase', 'state.initiative', 'state.initiativeTaken', 'state.active', 'state.players',
        'player.seat', 'player.baseHp', 'player.baseMaxHp', 'player.handSize', 'player.hand', 'player.deckSize',
        'player.resourcesReady', 'player.resourcesExhausted', 'player.resources', 'player.baseEpicActionUsed',
        'player.credits', 'player.hasForce', 'player.discard', 'player.cards', 'player.leader',
        'card.id', 'card.zone', 'card.damage', 'card.exhausted', 'card.upgrades', 'card.shields', 'card.experience',
        'card.statusTokens', 'card.captured', 'card.power', 'card.hp', 'card.keywords'];
}

// $view: 'both' shows both hands; 'p1' / 'p2' shows only that seat's hand face up.
function SwuPgnBoardWrite(array $state, array $doc, string $view): array
{
    global $gUniqueIDCounter;
    $index = SwuPgnCardIndex($doc);
    $names = [];
    $map = function ($pgnId) use (&$names, $index): string {
        $id = SwuPgnMapCardId($pgnId);
        if ($id !== null) return $id;
        $base = SwuPgnBaseId(is_string($pgnId) ? $pgnId : '?');
        $ph = SwuPgnPlaceholderId($base);
        $names[$ph] = SwuPgnName($index, $base);
        return $ph;
    };
    $headers = is_array($doc['headers'] ?? null) ? $doc['headers'] : [];
    $round = intval($state['round'] ?? 0);
    $init = in_array($state['initiative'] ?? null, [1, 2], true) ? $state['initiative'] : 1;
    $active = in_array($state['active'] ?? null, [1, 2], true) ? $state['active'] : $init;
    AddTurnNumber(max(1, $round)); AddFirstPlayer($active); AddTurnPlayer($active);
    AddCurrentPhase(SwuPgnBoardPhase($state['phase'] ?? 'action'));
    AddInitiativeCounter('P' . $init . '_' . (!empty($state['initiativeTaken']) ? 'CLAIMED' : 'UNCLAIMED'));

    $uid = 0;
    $stats = [];
    foreach ([1, 2] as $p) {
        $ps = is_array($state['players'][$p] ?? null) ? $state['players'][$p] : [];
        $other = $p === 1 ? 2 : 1;
        $faceUp = $view === 'both' || $view === "p$p";

        // The state's HP is the printed HP until a keyframe or base DAMAGE/HEAL says otherwise (SwuPgnBoardStartState).
        $baseMax = intval($ps['baseMaxHp'] ?? 30);
        AddBase($p, $map($headers["P{$p}Base"] ?? ''), max(0, $baseMax - intval($ps['baseHp'] ?? $baseMax)), !empty($ps['baseEpicActionUsed']));

        $leader = is_array($ps['leader'] ?? null) ? $ps['leader'] : ['id' => $headers["P{$p}Leader"] ?? ''];
        $leaderPgn = is_string($leader['id'] ?? null) ? $leader['id'] : ($headers["P{$p}Leader"] ?? '');
        $leaderUid = 0;
        $leaderIsPilot = false;

        foreach (is_array($ps['cards'] ?? null) ? $ps['cards'] : [] as $c) {
            if (!is_array($c) || !is_string($c['id'] ?? null)) continue;
            $uid++;
            $sub = [];
            foreach (is_array($c['upgrades'] ?? null) ? $c['upgrades'] : [] as $u) {
                if (!is_string($u)) continue;
                $uId = $map($u);
                $isLeaderPilot = $u === $leaderPgn;
                if ($isLeaderPilot) $leaderIsPilot = true;
                $type = (string)(CardType($uId) ?? '');
                $sub[] = ['CardID' => $uId, 'Owner' => $p, 'Controller' => $p, 'TurnEffects' => [],
                    'IsPilot' => $isLeaderPilot || $type === 'Unit' || $type === 'Leader'];
            }
            foreach (['shields' => 'SOR_T02', 'experience' => 'SOR_T01'] as $k => $tok) {
                for ($i = 0; $i < min(32, intval($c[$k] ?? 0)); $i++) $sub[] = ['CardID' => $tok, 'Owner' => $p, 'Controller' => $p, 'TurnEffects' => [], 'IsPilot' => false];
            }
            foreach ((array)($c['statusTokens'] ?? []) as $tokName => $count) {
                $tok = ['advantage' => 'ASH_T02', 'weakness' => 'HMW_T02'][$tokName] ?? null;
                if ($tok === null) continue;
                for ($i = 0; $i < min(32, intval($count)); $i++) $sub[] = ['CardID' => $tok, 'Owner' => $p, 'Controller' => $p, 'TurnEffects' => [], 'IsPilot' => false];
            }
            foreach (is_array($c['captured'] ?? null) ? $c['captured'] : [] as $held) {
                if (is_string($held)) $sub[] = ['CardID' => $map($held), 'Owner' => $other, 'Controller' => $p, 'TurnEffects' => [], 'IsPilot' => false, 'IsCaptive' => true];
            }
            $add = ($c['zone'] ?? 'ground') === 'space' ? 'AddSpaceArena' : 'AddGroundArena';
            $add($p, $map($c['id']), !empty($c['exhausted']) ? 0 : 1, $p, max(0, intval($c['damage'] ?? 0)), $p, '-', $sub ?: '-', $uid);
            if ($c['id'] === $leaderPgn) $leaderUid = $uid;
            $st = [];
            if (is_int($c['power'] ?? null)) $st['p'] = $c['power'];
            if (is_int($c['hp'] ?? null)) $st['h'] = $c['hp'];
            // File text in the gamestate: keep plain "word" / "word N" keywords only (the poll payload is delimiter-split).
            if (is_array($c['keywords'] ?? null)) $st['k'] = array_values(array_map('strtolower', array_filter($c['keywords'],
                fn($k) => is_string($k) && preg_match('/^[A-Za-z]{1,20}( \d{1,3})?$/', $k) === 1)));
            if ($st) $stats[$uid] = $st;
        }

        // A double-sided leader (e.g. TWI_017) reuses Deployed as its face bit, with no arena unit.
        $flipped = ($leader['onStartingSide'] ?? null) === false;
        AddLeader($p, $map($leaderPgn), !empty($leader['epicActionUsed']), empty($leader['exhausted']),
            !empty($leader['deployed']) || $flipped, ($leaderIsPilot || $flipped) ? 0 : $leaderUid);

        $handSize = max(0, intval($ps['handSize'] ?? 0));
        $known = $faceUp ? array_values(array_filter(is_array($ps['hand'] ?? null) ? $ps['hand'] : [], 'is_string')) : [];
        for ($i = 0; $i < min(200, $handSize); $i++) AddHand($p, isset($known[$i]) ? $map($known[$i]) : 'CardBack');

        $ready = max(0, intval($ps['resourcesReady'] ?? 0));
        $exhausted = max(0, intval($ps['resourcesExhausted'] ?? 0));
        $named = array_values(array_filter(is_array($ps['resources'] ?? null) ? $ps['resources'] : [], 'is_string'));
        for ($i = 0; $i < min(200, $ready + $exhausted); $i++) {
            $rid = isset($named[$i]) ? (SwuPgnMapCardId($named[$i]) ?? 'CardBack') : 'CardBack';
            AddResources($p, $rid, $i < $exhausted ? 0 : 1, $p, $p);
        }
        for ($i = 0; $i < min(64, max(0, intval($ps['credits'] ?? 0))); $i++) AddResources($p, 'LAW_T01', 1, $p, $p);

        $discard = array_values(array_filter(is_array($ps['discard'] ?? null) ? $ps['discard'] : [], 'is_string'));
        foreach (array_slice($discard, 0, 200) as $d) AddDiscard($p, $map($d), 'PLAY');
        // Known from the DECKS total (SwuPgnBoardStartState) or a keyframe; unknown → empty.
        $deckCount = is_int($ps['deckSize'] ?? null) ? $ps['deckSize'] : 0;
        for ($i = 0; $i < max(0, min(200, $deckCount)); $i++) AddDeck($p, 'CardBack');

        if (!empty($ps['hasForce'])) AddGlobalEffects($p, 'SWU_HAS_FORCE');
    }
    $gUniqueIDCounter = $uid;
    SetSWUVar('SWUPGN_VIEWER', '1');
    SetSWUVar('SWUPGN_STATS', json_encode((object)$stats));
    // Placeholder names go to the client in the viewer's meta, never into the gamestate.
    return ['placeholders' => $names];
}

// The board before the first event, from what SWUSim knows and no event carries (SWU-PGN §11): each base
// starts at its printed HP, each deck at its DECKS total. Keyframes and base DAMAGE/HEAL still overwrite
// both. A base SWUSim has no card data for keeps the reader's placeholder.
function SwuPgnBoardStartState(array $doc): array
{
    $state = SwuPgnEmptyState();
    $headers = is_array($doc['headers'] ?? null) ? $doc['headers'] : [];
    foreach ([1, 2] as $p) {
        $id = SwuPgnMapCardId($headers["P{$p}Base"] ?? '');
        $hp = $id !== null ? CardHp($id) : null;
        if (is_numeric($hp) && intval($hp) > 0) $state['players'][$p]['baseHp'] = $state['players'][$p]['baseMaxHp'] = intval($hp);
    }
    foreach (is_array($doc['decks'] ?? null) ? $doc['decks'] : [] as $d) {
        if (!is_array($d) || !in_array($d['p'] ?? null, [1, 2], true)) continue;
        $n = 0;
        foreach (is_array($d['deck'] ?? null) ? $d['deck'] : [] as $pair) $n += is_array($pair) ? max(0, intval($pair[1] ?? 0)) : 0;
        $state['players'][$d['p']]['deckSize'] = $n;
    }
    return $state;
}

// The viewer's scrubbing timeline: the reader's, folded from SwuPgnBoardStartState.
function SwuPgnBoardTimeline(array $doc): array
{
    return SwuPgnTimeline(is_array($doc['events'] ?? null) ? $doc['events'] : [], SwuPgnBoardStartState($doc));
}
