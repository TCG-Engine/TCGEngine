<?php
// The learned layer (spec Section 2, layer 3) — RL Phase 3 MVP. Plan:
// docs/superpowers/plans/2026-09-14-swusim-rl-phase3-trainer-mvp.md.
//
// On only for the '@rl' chooser variant (heuristic-<style>@rl), and only at the FALLBACK: the style filter, the
// resourcing floor and every layer-2 rule still decide what they cover (covered decisions are never learned).
// Among the fallback's candidates it picks by the table of mean returns per (style, swu-v1 state, move).
//
// Environment (all optional; with none of them set '@rl' plays exactly like the heuristic):
//   SWU_RL_POLICY      checkpoint JSON {"version","batches","games","table":{style:{state:{move:[visits,mean]}}}}
//                      — or swu-v2 (rl_ab.py) with "ab":{style:{state:{move:[tw,twg,tw2,nc,cg]}}}, which then decides
//                      every override (the swu-v1 means are biased by the heuristic's own choices: RL run 3)
//   SWU_RL_MODE        'play' (default: greedy, abstains to the fallback on thin evidence) | 'train' (also explores)
//   SWU_RL_EPSILON     exploration rate in train mode (default 0.1)
//   SWU_RL_MIN_VISITS  visits a move needs before its mean is trusted (default 8)
//   SWU_RL_Z           > 0: the significance rule — override only a MEASURED fallback move, by > Z standard errors
//   SWU_RL_EPISODE     train mode: append-only JSONL, one {"seat","style","s","m"} per learned decision
//   SWU_RL_SEED        the game's seed, for exploration hashing
// Deterministic: exploration hashes (seed, seat, decision #) — the engine's RNG stream is never touched.

const SWU_RL_OVERRIDE_MARGIN = 0.02;   // a trusted move must beat a trusted fallback move by this much

function SWURlPolicy(): array {
    static $cache = [];
    $path = strval(getenv('SWU_RL_POLICY') ?: '');
    if ($path === '' || !is_file($path)) return ['table' => []];
    $k = $path . '|' . @filemtime($path) . '|' . @filesize($path);
    if (!isset($cache[$k])) {
        $j = json_decode(strval(@file_get_contents($path)), true);
        $cache = [$k => is_array($j) ? $j : ['table' => []]];   // one checkpoint per process
    }
    return $cache[$k];
}

// One swu-v2 entry [tw, twg, tw2, nc, cg] → the treatment and control means, their (effective) sizes, the effect and
// its standard error. Mirrors ab_effect() in SWUSim/DevTools/rl/rl_ab.py; null without both arms.
function SWURlAbEffect($e): ?array {
    if (!is_array($e) || count($e) < 5) return null;
    [$tw, $twg, $tw2, $nc, $cg] = array_map('floatval', $e);
    if ($tw <= 0 || $nc <= 0 || $tw2 <= 0) return null;
    $qT = $twg / $tw; $qC = $cg / $nc; $nT = $tw * $tw / $tw2;
    $se = sqrt(max(1e-9, 1 - $qT * $qT) / $nT + max(1e-9, 1 - $qC * $qC) / $nc);
    return ['qT' => $qT, 'qC' => $qC, 'nT' => $nT, 'nC' => $nc, 'effect' => $qT - $qC, 'se' => $se];
}

function SWURlChoose(array $ctx, ?array $fallbackPick): ?array {
    if ($fallbackPick === null || empty($ctx['actions'])) return $fallbackPick;
    $seat = intval($ctx['seat']); $style = strval($ctx['style']);
    $mode = strval(getenv('SWU_RL_MODE') ?: 'play');
    $byMove = [];
    foreach ($ctx['actions'] as $a) { $m = SWURlMoveKey($ctx, $a); if (!isset($byMove[$m])) $byMove[$m] = $a; }
    if (count($byMove) < 2) return $fallbackPick;   // no choice to learn

    $s = SWURlStateKey($ctx);
    $policy = SWURlPolicy();
    $row = $policy['table'][$style][$s] ?? [];
    $minV = intval(getenv('SWU_RL_MIN_VISITS') ?: 8);
    $fbMove = SWURlMoveKey($ctx, $fallbackPick);
    $choice = $fallbackPick; $chosen = $fbMove; $how = 'keep';

    $z = floatval(getenv('SWU_RL_Z') ?: 0);
    if (isset($policy['ab'])) {
        // swu-v2 (SWUSim/DevTools/rl/rl_ab.py): override only where games in which EXPLORATION forced a move beat the
        // heuristic's own games in the same state — the swu-v1 means were biased by the heuristic's choices (RL run 3
        // lost at 46.8%). Both arms need the sample floor, and the effect must clear z SE (z defaults to 3 here: a
        // bare positive effect is never enough). Among the qualifying moves, the largest effect.
        $abRow = $policy['ab'][$style][$s] ?? [];
        $zAb = $z > 0 ? $z : 3.0; $bestE = -INF;
        foreach ($byMove as $m => $a) {
            if ($m === $fbMove) continue;
            $e = SWURlAbEffect($abRow[$m] ?? null);
            if ($e === null || $e['nT'] < $minV || $e['nC'] < $minV) continue;
            if ($e['effect'] > $zAb * $e['se'] && $e['effect'] > $bestE) { $bestE = $e['effect']; $choice = $a; $chosen = $m; $how = 'override'; }
        }
    } elseif ($z > 0) {
        // The significance rule (RL run 1: the margin rule below chased noise — winner's curse). Only when the
        // fallback's OWN move is measured, and an alternative beats it by more than z standard errors of the
        // difference (a ±1 return has variance ≈ 1 − mean²). Among those, the highest mean.
        $fb = $row[$fbMove] ?? null;
        if (is_array($fb) && intval($fb[0]) >= $minV) {
            $nF = intval($fb[0]); $qF = floatval($fb[1]); $bestQ = -INF;
            foreach ($byMove as $m => $a) {
                $e = $row[$m] ?? null;
                if ($m === $fbMove || !is_array($e) || intval($e[0]) < $minV) continue;
                $nA = intval($e[0]); $qA = floatval($e[1]);
                $se = sqrt(max(1e-9, 1 - $qF * $qF) / $nF + max(1e-9, 1 - $qA * $qA) / $nA);
                if ($qA - $qF > $z * $se && $qA > $bestQ) { $bestQ = $qA; $choice = $a; $chosen = $m; $how = 'override'; }
            }
        }
    } else {
        $best = null; $bestQ = -INF;
        foreach ($byMove as $m => $a) {
            $e = $row[$m] ?? null;
            if (is_array($e) && intval($e[0]) >= $minV && floatval($e[1]) > $bestQ) { $best = $m; $bestQ = floatval($e[1]); }
        }
        if ($best !== null && $best !== $fbMove) {
            $fb = $row[$fbMove] ?? null;
            $fbTrusted = is_array($fb) && intval($fb[0]) >= $minV;
            if (!$fbTrusted || $bestQ > floatval($fb[1]) + SWU_RL_OVERRIDE_MARGIN) { $choice = $byMove[$best]; $chosen = $best; $how = 'override'; }
        }
    }
    if ($mode === 'train') {
        $eps = getenv('SWU_RL_EPSILON');
        $eps = ($eps === false || $eps === '') ? 0.1 : floatval($eps);
        $n = $GLOBALS['SWURlDecisionN'][$seat] = ($GLOBALS['SWURlDecisionN'][$seat] ?? 0) + 1;
        $h = crc32(strval(getenv('SWU_RL_SEED')) . "|$seat|$n");
        if (($h % 1000000) / 1000000 < $eps) {
            $keys = array_keys($byMove);
            $chosen = $keys[intdiv($h, 1000000) % count($keys)];
            $choice = $byMove[$chosen]; $how = 'explore';
        }
        // how / fb / L feed the randomised comparison (rl_ab.py): 'explore' decisions are the treatment arm, 'keep'
        // decisions the control arm for every legal move, and the forced move is weighted by the legal-move count.
        $ep = strval(getenv('SWU_RL_EPISODE') ?: '');
        $legal = array_keys($byMove); sort($legal);
        if ($ep !== '') @file_put_contents($ep, json_encode(['seat' => $seat, 'style' => $style, 's' => $s, 'm' => $chosen,
            'how' => $how, 'fb' => $fbMove, 'L' => $legal], JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
    }
    SWUBotRecordCoverage($seat, "rl:$how");
    return $choice;
}
