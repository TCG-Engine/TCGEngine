<?php
// Glicko-2 (Mark Glickman, "Example of the Glicko-2 system", glicko2.pdf) — pure functions, no DB.
// Used by SWUSim's metapremier rated queue (docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §3.2).
// Each rated match is rated as its own one-game rating period (Lichess style); idle time before it inflates RD.

const GLICKO2_DEFAULT_RATING = 1500.0;
const GLICKO2_DEFAULT_RD     = 350.0;
const GLICKO2_DEFAULT_VOL    = 0.06;
const GLICKO2_SCALE          = 173.7178;
const GLICKO2_RD_MAX         = 350.0;
const GLICKO2_EPSILON        = 0.000001;

// RD after $idlePeriods rating periods with no games: phi' = sqrt(phi^2 + t*sigma^2), capped.
function Glicko2InflateRd(float $rd, float $volatility, float $idlePeriods): float {
    if ($idlePeriods <= 0) return min($rd, GLICKO2_RD_MAX);
    $phi = $rd / GLICKO2_SCALE;
    $phi = sqrt($phi * $phi + $idlePeriods * $volatility * $volatility);
    return min($phi * GLICKO2_SCALE, GLICKO2_RD_MAX);
}

function _Glicko2G(float $phi): float { return 1.0 / sqrt(1.0 + 3.0 * $phi * $phi / (M_PI * M_PI)); }
function _Glicko2E(float $mu, float $muJ, float $phiJ): float { return 1.0 / (1.0 + exp(-_Glicko2G($phiJ) * ($mu - $muJ))); }

// $player = ['rating','rd','volatility']; $results = [['rating','rd','score'], ...] (score 1 / 0.5 / 0).
function Glicko2Rate(array $player, array $results, float $idlePeriods = 0.0, float $tau = 0.5): array {
    $sigma = floatval($player['volatility']);
    $rd    = Glicko2InflateRd(floatval($player['rd']), $sigma, $idlePeriods);
    $mu    = (floatval($player['rating']) - GLICKO2_DEFAULT_RATING) / GLICKO2_SCALE;
    $phi   = $rd / GLICKO2_SCALE;
    if (empty($results)) return ['rating' => floatval($player['rating']), 'rd' => $rd, 'volatility' => $sigma];

    // Steps 3-4: estimated variance v and improvement delta.
    $vInv = 0.0; $deltaSum = 0.0;
    foreach ($results as $r) {
        $muJ  = (floatval($r['rating']) - GLICKO2_DEFAULT_RATING) / GLICKO2_SCALE;
        $phiJ = floatval($r['rd']) / GLICKO2_SCALE;
        $g = _Glicko2G($phiJ);
        $E = _Glicko2E($mu, $muJ, $phiJ);
        $vInv     += $g * $g * $E * (1.0 - $E);
        $deltaSum += $g * (floatval($r['score']) - $E);
    }
    $v = 1.0 / $vInv;
    $delta = $v * $deltaSum;

    // Step 5: new volatility via the Illinois algorithm.
    $a = log($sigma * $sigma);
    $f = function (float $x) use ($delta, $phi, $v, $a, $tau): float {
        $ex = exp($x);
        $num = $ex * ($delta * $delta - $phi * $phi - $v - $ex);
        $den = 2.0 * pow($phi * $phi + $v + $ex, 2);
        return $num / $den - ($x - $a) / ($tau * $tau);
    };
    $A = $a;
    if ($delta * $delta > $phi * $phi + $v) {
        $B = log($delta * $delta - $phi * $phi - $v);
    } else {
        $k = 1;
        while ($f($a - $k * $tau) < 0) $k++;
        $B = $a - $k * $tau;
    }
    $fA = $f($A); $fB = $f($B);
    while (abs($B - $A) > GLICKO2_EPSILON) {
        $C = $A + ($A - $B) * $fA / ($fB - $fA);
        $fC = $f($C);
        if ($fC * $fB <= 0) { $A = $B; $fA = $fB; } else { $fA = $fA / 2.0; }
        $B = $C; $fB = $fC;
    }
    $sigmaNew = exp($A / 2.0);

    // Steps 6-7: new RD and rating.
    $phiStar = sqrt($phi * $phi + $sigmaNew * $sigmaNew);
    $phiNew  = 1.0 / sqrt(1.0 / ($phiStar * $phiStar) + 1.0 / $v);
    $muNew   = $mu + $phiNew * $phiNew * $deltaSum;

    return [
        'rating'     => $muNew * GLICKO2_SCALE + GLICKO2_DEFAULT_RATING,
        'rd'         => min($phiNew * GLICKO2_SCALE, GLICKO2_RD_MAX),
        'volatility' => $sigmaNew,
    ];
}
