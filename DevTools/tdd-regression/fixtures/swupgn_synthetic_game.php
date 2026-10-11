<?php
// Builds a deterministic synthetic SWU-PGN game of roughly $target events for the scrubbing and
// performance tests: draws, resourcing, plays (MOVE + STATS + PLAY + entering EXHAUST), attacks
// with damage and defeats, tokens gained and removed, upgrades, credits, a leader deploy, a capture
// and rescue, regroups — with keyframes at round boundaries that are exactly the fold so far
// (self-consistent, like a conformant writer's), and one deliberately damaged keyframe.
// Requires swupgn_test_helpers.php to be loaded first.

function SwuPgnSyntheticGame(int $target, int $seed = 7): array
{
    mt_srand($seed);
    $ev = [];
    $step = 0;
    $round = 1;
    $add = function (array $e, string $phase) use (&$ev, &$step, &$round) {
        $step++;
        $ev[] = ['seq' => "R$round.$phase.$step"] + $e;
    };
    $next = [1 => 1, 2 => 1];
    $inPlay = [1 => [], 2 => []];
    $hand = [1 => [], 2 => []];
    $draw = function (int $p, string $phase) use (&$next, &$hand, $add) {
        $id = sprintf('SYN#%03d', $p * 100 + ($next[$p] % 90)) . ($next[$p] >= 90 ? ':' . (intdiv($next[$p], 90) + 1) : '');
        $next[$p]++;
        $add(['t' => 'MOVE', 'card' => $id, 'from' => 'deck', 'to' => 'hand', 'p' => $p, 'kind' => 'unit'], $phase);
        $add(['t' => 'DRAW', 'p' => $p, 'count' => 1, 'cards' => [$id]], $phase);
        $hand[$p][] = $id;
    };
    $keyframe = function (array $root = []) use (&$ev) {
        $doc = SwuPgnParse(SwuPgnTestFile([['seq' => 'R1.A.1', 't' => 'X', 'v' => json_decode(SwuPgnStateToJson(SwuPgnFold($ev)), true)]]));
        $kf = $doc['events'][0]['v'];
        foreach ([1, 2] as $s) foreach ($kf['players'][$s]['cards'] as $i => $c) {
            if (($c['statusTokens'] ?? null) === []) $kf['players'][$s]['cards'][$i]['statusTokens'] = new stdClass();
        }
        foreach ($root as $k => $v) $kf[$k] = $v;
        return $kf;
    };

    $add(['t' => 'PHASE_START', 'phase' => 'setup'], 'S');
    for ($i = 0; $i < 6; $i++) { $draw(1, 'S'); $draw(2, 'S'); }
    while (count($ev) < $target) {
        $step = 0;
        $ev[] = ['seq' => "R$round.start", 't' => 'ROUND_START', 'round' => $round, 'keyframe' => $round === 3 ? ['players' => []] : $keyframe(['round' => $round, 'phase' => 'action', 'initiativeTaken' => false])];
        $add(['t' => 'PHASE_START', 'phase' => 'action'], 'A');
        for ($turn = 0; $turn < 24 && count($ev) < $target; $turn++) {
            $p = 1 + $turn % 2;
            $o = 3 - $p;
            $roll = mt_rand(0, 9);
            if ($roll < 4 && $hand[$p]) {
                $card = array_shift($hand[$p]);
                $add(['t' => 'EXHAUST_RESOURCES', 'p' => $p, 'amount' => mt_rand(1, 3)], 'A');
                $add(['t' => 'MOVE', 'card' => $card, 'from' => 'hand', 'to' => mt_rand(0, 1) ? 'ground' : 'space', 'p' => $p, 'kind' => 'unit'], 'A');
                $add(['t' => 'STATS', 'card' => $card, 'power' => mt_rand(1, 6), 'hp' => mt_rand(2, 7), 'keywords' => mt_rand(0, 1) ? ['sentinel'] : []], 'A');
                $add(['t' => 'PLAY', 'p' => $p, 'card' => $card, 'cost' => 3], 'A');
                $add(['t' => 'EXHAUST', 'card' => $card], 'A');
                $inPlay[$p][] = $card;
            } elseif ($roll < 7 && $inPlay[$p]) {
                $atk = $inPlay[$p][mt_rand(0, count($inPlay[$p]) - 1)];
                $add(['t' => 'EXHAUST', 'card' => $atk], 'A');
                if ($inPlay[$o] && mt_rand(0, 1)) {
                    $def = $inPlay[$o][mt_rand(0, count($inPlay[$o]) - 1)];
                    $add(['t' => 'ATTACK', 'p' => $p, 'atk' => $atk, 'def' => $def, 'defenderType' => 'unit'], 'A');
                    $add(['t' => 'DAMAGE', 'src' => $atk, 'tgt' => $def, 'amt' => 2, 'damageType' => 'combat', 'hp' => 1], 'A');
                    if (mt_rand(0, 2) === 0) {
                        $add(['t' => 'MOVE', 'card' => $def, 'from' => 'ground', 'to' => 'discard', 'p' => $o, 'kind' => 'unit'], 'A');
                        $add(['t' => 'DEFEAT', 'card' => $def, 'reason' => 'attack', 'defeatedBy' => $atk], 'A');
                        $inPlay[$o] = array_values(array_diff($inPlay[$o], [$def]));
                    }
                } else {
                    $add(['t' => 'ATTACK', 'p' => $p, 'atk' => $atk, 'def' => "base@$o", 'defenderType' => 'base'], 'A');
                    $add(['t' => 'DAMAGE', 'src' => $atk, 'tgt' => "base@$o", 'amt' => 3, 'damageType' => 'combat', 'hp' => mt_rand(1, 29)], 'A');
                }
            } elseif ($roll === 7 && $inPlay[$p]) {
                $host = $inPlay[$p][0];
                $add(['t' => 'MOVE', 'card' => "TOKEN:advantage#5844562972:$step$round", 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => $p, 'kind' => 'upgrade', 'attachedTo' => $host], 'A');
                $add(['t' => 'STATUS_TOKEN', 'card' => $host, 'token' => 'advantage', 'count' => 1], 'A');
                $add(['t' => 'SHIELD_GAIN', 'card' => $host], 'A');
                $add(['t' => 'PLAY_UPGRADE', 'p' => $p, 'card' => "UPG#$round$step", 'target' => $host], 'A');
                $add(['t' => 'STATUS_TOKEN', 'card' => $host, 'token' => 'advantage', 'count' => -1], 'A');
            } elseif ($roll === 8) {
                $add(['t' => 'MOVE', 'card' => "TOKEN:credit#1:$round$step", 'from' => 'outsideTheGame', 'to' => 'base', 'p' => $p], 'A');
                $add(['t' => 'PASS', 'p' => $p], 'A');
            } else {
                $add(['t' => 'PASS', 'p' => $p], 'A');
            }
        }
        $ev[] = ['seq' => "R$round.A.end", 't' => 'PHASE_END', 'phase' => 'action'];
        $step = 0;
        $add(['t' => 'PHASE_START', 'phase' => 'regroup'], 'G');
        foreach ([1, 2] as $p) {
            $draw($p, 'G');
            $draw($p, 'G');
            if ($hand[$p]) {
                $r = array_shift($hand[$p]);
                $add(['t' => 'MOVE', 'card' => $r, 'from' => 'hand', 'to' => 'resource', 'p' => $p, 'kind' => 'unit'], 'G');
                $add(['t' => 'RESOURCE', 'p' => $p, 'card' => $r], 'G');
            }
            foreach ($inPlay[$p] as $c) $add(['t' => 'READY', 'card' => $c], 'G');
            $add(['t' => 'READY_RESOURCES', 'p' => $p, 'amount' => 9], 'G');
        }
        $ev[] = ['seq' => "R$round.end", 't' => 'ROUND_END', 'round' => $round, 'keyframe' => $keyframe()];
        $round++;
    }
    return $ev;
}
