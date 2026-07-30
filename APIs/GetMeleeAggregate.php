<?php
/**
 * API: pooled aggregate across several imported melee tournaments.
 *
 * Required parameters:
 * - ids: comma-separated tournament IDs (e.g. ?ids=67,69,70)
 *
 * Why this exists rather than merging raw decks client-side: GetMeleeTournament.php serialises
 * ~7.7 MB for a single 1485-deck event, so the full 68k-matchup archive would be ~63 MB to the
 * browser. This returns only render-shaped aggregates — hundreds of KB regardless of selection.
 *
 * PARITY CONTRACT: every structure below must match, field for field AND in field order, what the
 * corresponding calculate*() function in Stats/MeleeTournamentResults.php returns, because both
 * feed the same renderers in Stats/MeleeCharts.js. DevTools/ui-harness/aggregate-parity.mjs
 * enforces this by diffing the two for a single-tournament selection. Note in particular:
 *   - rate fields are STRINGS fixed to 1dp ('54.0'), not numbers;
 *   - leaderPerformance win rate INCLUDES draws in its denominator;
 *   - archetype win rate EXCLUDES draws, and a match is won when wins > losses;
 *   - leaderMetaShare/leaderPerformance are keyed by leader NAME, not uuid.
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../Database/ConnectionManager.php";
require_once "../SWUDeck/Custom/CardIdentifiers.php";
include_once "../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php";
// After the dictionaries: ResolveOpponentBase()'s fallback calls CardAspect()/CardTitle().
require_once "../Core/StatsBaseRegistry.php";

set_time_limit(300);

function AggCardName($uuid) {
    global $titleData, $subtitleData;
    if (empty($uuid)) return null;
    $title = isset($titleData[$uuid]) ? $titleData[$uuid] : null;
    $subtitle = isset($subtitleData[$uuid]) ? $subtitleData[$uuid] : null;
    if ($title) return $subtitle ? $title . ", " . $subtitle : $title;
    return null;
}

// Mirrors archetypeIdentity() in MeleeTournamentResults.php, including its fallbacks.
function AggIdentity($leaderUuid, $baseUuid) {
    $leaderName = AggCardName($leaderUuid);
    if ($leaderName === null || $leaderName === '') $leaderName = ($leaderUuid !== null && $leaderUuid !== '') ? $leaderUuid : 'Unknown';
    $lu = ($leaderUuid !== null && $leaderUuid !== '') ? $leaderUuid : null;

    $baseName = AggCardName($baseUuid);
    if ($baseUuid === null || $baseUuid === '') {
        $baseKey = 'Unknown'; $baseLabel = 'Unknown'; $bu = null;
    } else {
        $bucket = StatsBaseBucket($baseUuid);
        $r = ResolveOpponentBase($baseUuid);
        $groupKey = $bucket['key'];
        $groupLabel = ($r && $r['kind'] === 'common') ? BaseGroupDisplayLabel($r['type'], $r['color']) : $baseName;
        $baseKey = ($groupKey !== null && $groupKey !== '') ? $groupKey : $baseUuid;
        $baseLabel = ($groupLabel !== null && $groupLabel !== '') ? $groupLabel : $baseUuid;
        $bu = ($bucket['displayBase'] !== null && $bucket['displayBase'] !== '') ? $bucket['displayBase'] : $baseUuid;
    }

    return [
        'key' => ($lu !== null ? $lu : $leaderName) . '||' . $baseKey,
        'leaderName' => $leaderName,
        'leaderUuid' => $lu,
        'baseLabel' => $baseLabel,
        'baseUuid' => $bu,
    ];
}

function AggFail($message) {
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

try {
    $raw = isset($_GET['ids']) ? $_GET['ids'] : '';
    $ids = array_values(array_unique(array_filter(
        array_map('intval', explode(',', $raw)),
        function ($v) { return $v > 0; }
    )));
    if (empty($ids)) AggFail('No tournament ids supplied.');
    sort($ids);
    $in = implode(',', $ids);   // intval'd above, safe to interpolate

    $conn = GetLocalMySQLConnection();
    if ($conn === false) AggFail('Error connecting to the database.');

    // ---- tournaments -------------------------------------------------------------------
    $tournaments = [];
    $res = mysqli_query($conn, "
        SELECT mt.tournamentID, mt.tournamentName, mt.tournamentDate, mt.tournamentLink,
               COUNT(d.deckID) AS players
        FROM meleetournament mt
        LEFT JOIN meleetournamentdeck d ON d.tournamentId = mt.tournamentID
        WHERE mt.tournamentID IN ($in)
        GROUP BY mt.tournamentID, mt.tournamentName, mt.tournamentDate, mt.tournamentLink
        ORDER BY mt.tournamentDate DESC, mt.tournamentID DESC");
    if ($res === false) AggFail('SQL error (tournaments): ' . mysqli_error($conn));
    while ($r = mysqli_fetch_assoc($res)) {
        $tournaments[] = [
            'id' => (int)$r['tournamentID'],
            'name' => $r['tournamentName'],
            'date' => $r['tournamentDate'],
            'meleeLink' => (int)$r['tournamentLink'],
            'players' => (int)$r['players'],
        ];
    }
    if (empty($tournaments)) AggFail('None of the supplied tournament ids exist.');

    // ---- decks -------------------------------------------------------------------------
    $res = mysqli_query($conn, "
        SELECT deckID, tournamentId, leader, base, `rank`,
               matchWins, matchLosses, matchDraws, gameWins, gameLosses, gameDraws
        FROM meleetournamentdeck WHERE tournamentId IN ($in)");
    if ($res === false) AggFail('SQL error (decks): ' . mysqli_error($conn));

    $deckArch = [];          // deckID => identity
    $leaderCounts = [];      // leaderName => n
    $leaderUuids = [];       // leaderName => first seen uuid (for card art)
    $comboCounts = [];       // archKey => identity + count
    $leaderStats = [];       // leaderName => performance accumulator
    $archetypes = [];        // archKey => aggregate
    $totalDecks = 0;

    while ($d = mysqli_fetch_assoc($res)) {
        $totalDecks++;
        $idn = AggIdentity($d['leader'], $d['base']);
        $deckArch[(int)$d['deckID']] = $idn;
        $ln = $idn['leaderName'];

        if (!isset($leaderCounts[$ln])) $leaderCounts[$ln] = 0;
        $leaderCounts[$ln]++;
        if ($idn['leaderUuid'] !== null && !isset($leaderUuids[$ln])) $leaderUuids[$ln] = $idn['leaderUuid'];

        if (!isset($comboCounts[$idn['key']])) {
            $comboCounts[$idn['key']] = ['idn' => $idn, 'count' => 0];
        }
        $comboCounts[$idn['key']]['count']++;

        if (!isset($leaderStats[$ln])) {
            $leaderStats[$ln] = ['name' => $ln, 'matchWins' => 0, 'matchLosses' => 0, 'matchDraws' => 0,
                                 'gameWins' => 0, 'gameLosses' => 0, 'gameDraws' => 0, 'count' => 0, 'topCut' => 0];
        }
        $s = &$leaderStats[$ln];
        $s['count']++;
        $s['matchWins']   += (int)$d['matchWins'];
        $s['matchLosses'] += (int)$d['matchLosses'];
        $s['matchDraws']  += (int)$d['matchDraws'];
        $s['gameWins']    += (int)$d['gameWins'];
        $s['gameLosses']  += (int)$d['gameLosses'];
        $s['gameDraws']   += (int)$d['gameDraws'];
        if ((int)$d['rank'] > 0 && (int)$d['rank'] <= 8) $s['topCut']++;
        unset($s);

        if (!isset($archetypes[$idn['key']])) {
            $archetypes[$idn['key']] = array_merge($idn, ['deckCount' => 0, 'totalMatches' => 0, 'opponentMap' => []]);
        }
        $archetypes[$idn['key']]['deckCount']++;
    }

    // ---- matchups ----------------------------------------------------------------------
    // Own-perspective only: one row per (deck, its match). Writing the opponent's side too
    // would double-count every match.
    $res = mysqli_query($conn, "
        SELECT m.player, m.opponent, m.wins, m.losses, m.draws
        FROM meleetournamentmatchup m
        JOIN meleetournamentdeck d ON d.deckID = m.player
        WHERE d.tournamentId IN ($in)");
    if ($res === false) AggFail('SQL error (matchups): ' . mysqli_error($conn));

    $totalMatchups = 0;
    while ($m = mysqli_fetch_assoc($res)) {
        $p = (int)$m['player'];
        $o = (int)$m['opponent'];
        if (!isset($deckArch[$p]) || !isset($deckArch[$o])) continue;   // opponent outside selection
        $totalMatchups++;

        $me = $deckArch[$p];
        $opp = $deckArch[$o];
        $a = &$archetypes[$me['key']];
        if (!isset($a['opponentMap'][$opp['key']])) {
            $a['opponentMap'][$opp['key']] = array_merge($opp, [
                'matchWins' => 0, 'matchLosses' => 0, 'matchDraws' => 0, 'matches' => 0,
                'isMirror' => ($opp['key'] === $me['key']),
            ]);
        }
        $e = &$a['opponentMap'][$opp['key']];
        $w = (int)$m['wins']; $l = (int)$m['losses'];
        if ($w > $l) $e['matchWins']++;
        else if ($w < $l) $e['matchLosses']++;
        else $e['matchDraws']++;
        $e['matches']++;
        $a['totalMatches']++;
        unset($e, $a);
    }

    // ---- shape the payload --------------------------------------------------------------
    // Field ORDER below is load-bearing: aggregate-parity.mjs compares with JSON.stringify.

    $leaderMetaShare = [];
    foreach ($leaderCounts as $name => $count) {
        $leaderMetaShare[] = [
            'name' => $name,
            'uuid' => isset($leaderUuids[$name]) ? $leaderUuids[$name] : null,
            'count' => $count,
            'percentage' => $totalDecks > 0 ? number_format($count / $totalDecks * 100, 1, '.', '') : '0.0',
        ];
    }
    usort($leaderMetaShare, function ($a, $b) { return $b['count'] - $a['count']; });

    $comboMetaShare = [];
    foreach ($comboCounts as $key => $c) {
        $i = $c['idn'];
        $comboMetaShare[] = [
            'key' => $key,
            'name' => $i['leaderName'] . ' / ' . $i['baseLabel'],
            'leaderName' => $i['leaderName'],
            'leaderUuid' => $i['leaderUuid'],
            'baseLabel' => $i['baseLabel'],
            'baseUuid' => $i['baseUuid'],
            'count' => $c['count'],
            'percentage' => $totalDecks > 0 ? number_format($c['count'] / $totalDecks * 100, 1, '.', '') : '0.0',
        ];
    }
    usort($comboMetaShare, function ($a, $b) { return $b['count'] - $a['count']; });
    $comboMetaShare = array_slice($comboMetaShare, 0, 10);   // page slices to top 10

    $leaderPerformance = [];
    foreach ($leaderStats as $s) {
        $totalMatches = $s['matchWins'] + $s['matchLosses'] + $s['matchDraws'];   // draws INCLUDED
        $totalGames   = $s['gameWins'] + $s['gameLosses'] + $s['gameDraws'];
        $leaderPerformance[] = [
            'name' => $s['name'],
            'matchWins' => $s['matchWins'], 'matchLosses' => $s['matchLosses'], 'matchDraws' => $s['matchDraws'],
            'gameWins' => $s['gameWins'], 'gameLosses' => $s['gameLosses'], 'gameDraws' => $s['gameDraws'],
            'count' => $s['count'], 'topCut' => $s['topCut'],
            'matchWinRate' => $totalMatches > 0 ? number_format($s['matchWins'] / $totalMatches * 100, 1, '.', '') : '0.0',
            'gameWinRate'  => $totalGames   > 0 ? number_format($s['gameWins']  / $totalGames   * 100, 1, '.', '') : '0.0',
            'topCutRate'   => number_format($s['topCut'] / $s['count'] * 100, 1, '.', ''),
        ];
    }
    usort($leaderPerformance, function ($a, $b) {
        $d = (float)$b['matchWinRate'] - (float)$a['matchWinRate'];
        if ($d > 0) return 1;
        if ($d < 0) return -1;
        return $b['count'] - $a['count'];
    });

    $archOut = [];
    foreach ($archetypes as $a) {
        $opps = array_values($a['opponentMap']);
        usort($opps, function ($x, $y) {
            if ($y['matches'] !== $x['matches']) return $y['matches'] - $x['matches'];
            return strcmp($x['leaderName'], $y['leaderName']);
        });
        $archOut[] = [
            'key' => $a['key'],
            'leaderName' => $a['leaderName'],
            'leaderUuid' => $a['leaderUuid'],
            'baseLabel' => $a['baseLabel'],
            'baseUuid' => $a['baseUuid'],
            'deckCount' => $a['deckCount'],
            'totalMatches' => $a['totalMatches'],
            'opponents' => $opps,
        ];
    }
    usort($archOut, function ($x, $y) {
        if ($y['deckCount'] !== $x['deckCount']) return $y['deckCount'] - $x['deckCount'];
        return strcmp($x['leaderName'], $y['leaderName']);
    });

    echo json_encode([
        'success' => true,
        'tournaments' => $tournaments,
        'totals' => [
            'tournaments' => count($tournaments),
            'players' => $totalDecks,
            'matchups' => $totalMatchups,
        ],
        'leaderMetaShare' => $leaderMetaShare,
        'comboMetaShare' => $comboMetaShare,
        'leaderPerformance' => $leaderPerformance,
        'archetypes' => $archOut,
    ], JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'where' => basename($e->getFile()) . ':' . $e->getLine(),
    ]);
}
