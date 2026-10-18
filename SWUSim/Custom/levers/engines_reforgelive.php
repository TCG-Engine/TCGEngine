<?php
// LEVER 'reforgelive' (theme 'engines', G1-25 reforge-live-target). ASH_090 Reforge ("Defeat an upgrade on a friendly unit. If you do, search
// the top 8 cards of your deck for an upgrade that can attach to that unit … play it on that unit. It costs 4 resources less."): Ezra Blue's
// casts fetched nothing 19 of 66 times; traced ezraB-vader-1 R2 destroyed Flarestar's Advantage token to fetch a 1-cost Nimble Prowess.
// 'searchvalue' (p42) prices a search flat (half a draw).
// play — Reforge is priced by its expected hit: P(an upgrade that fits the best friendly host is in the top 8 — 16 when the host wears Arcana
// Star Map) from my deck's CONTENTS (never its order), times the 4-resource saving plus the mean play value of those upgrades, less what the
// defeated upgrade was worth (a token ~nothing, a card its cost). It waits while that chance is under one half.
// (The Reforge HOST pick is not changed: its prompt is the upgrade-to-defeat, read by the generic scorer.)
const SWU_BOT_LV_REFORGELIVE_MIN_P = 0.5;

// Can upgrade $u attach to unit view $v? Its printed "Attach to a … unit" restriction, read loosely (ground / space / non-Vehicle / Vehicle /
// a trait), plus "unique" and a trait word.
function _SWUBotLvReforgeliveFits(string $u, array $v): bool {
    $t = strval(CardText($u));
    if (!preg_match('/Attach to an? ([^.]*?)unit/i', $t, $m)) return true;
    $req = strtolower($m[1]);
    $traits = strtolower(strval(CardTrait(strval($v['cardID'])) ?? ''));
    if (str_contains($req, 'non-vehicle') && str_contains($traits, 'vehicle')) return false;
    if (preg_match('/\bvehicle\b/', $req) && !str_contains($req, 'non-vehicle') && !str_contains($traits, 'vehicle')) return false;
    if (str_contains($req, 'ground') && $v['arena'] !== 'Ground') return false;
    if (str_contains($req, 'space') && $v['arena'] !== 'Space') return false;
    if (str_contains($req, 'unique') && !CardUnique(strval($v['cardID']))) return false;
    foreach (['jedi', 'sith', 'force', 'mandalorian', 'trooper', 'droid', 'bounty hunter', 'fighter', 'transport'] as $tr) {
        if (str_contains($req, $tr) && !str_contains($traits, $tr)) return false;
    }
    return true;
}
// [P(hit), mean value of the fitting upgrades] for host $v, over the top $n of my deck.
function _SWUBotLvReforgeliveOdds(int $seat, array $v, int $n, array $W): array {
    $deck = array_values(array_filter(GetDeck($seat), fn($o) => $o !== null && empty($o->removed)));
    $d = count($deck);
    if ($d === 0) return [0.0, 0.0];
    $k = 0; $sum = 0.0;
    foreach ($deck as $o) {
        $u = strval($o->CardID ?? '');
        if (!str_contains(strval(CardType($u)), 'Upgrade') || SWUBotIsDowngrade($u) || !_SWUBotLvReforgeliveFits($u, $v)) continue;
        $k++; $sum += _SWUBotPlayValue($seat, $u, $W, 'deck', 'free');
    }
    if ($k === 0) return [0.0, 0.0];
    $n = min($n, $d);
    $miss = 1.0;   // hypergeometric: no fitting upgrade among the top $n
    for ($i = 0; $i < $n; $i++) $miss *= max(0.0, ($d - $k - $i) / ($d - $i));
    return [1.0 - $miss, $sum / $k];
}

SWUBotLeverRegister('engines', 'reforgelive', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!preg_match('/Defeat an upgrade on a friendly unit\. If you do, search the top (\d+) cards of your deck for an upgrade that can attach to that unit[^.]*\.\s*It costs (\d+) resources? less/i', strval(CardText($cid)), $m)) return null;
        $best = null; $bestP = 0.0;
        foreach (SWUBotUnits($seat) as $u) {
            if (!isset($u['obj']) || intval($u['upgrades']) <= 0) continue;
            $lost = PHP_FLOAT_MAX;   // the cheapest upgrade on it goes
            foreach (GetUpgradesOnUnit($u['obj']) as $s) {
                $sid = strval($s->CardID ?? '');
                if (SWUBotIsDowngrade($sid)) continue;
                $lost = min($lost, floatval($W['develop']) * max(0, intval(CardCost($sid))));
            }
            if ($lost === PHP_FLOAT_MAX) continue;
            $arcana = function_exists('_SWUUnitHasUpgrade') && _SWUUnitHasUpgrade($u['obj'], 'ASH_084');
            [$p, $mean] = _SWUBotLvReforgeliveOdds($seat, $u, intval($m[1]) * ($arcana ? 2 : 1), $W);
            $val = $p * (floatval($W['develop']) * intval($m[2]) + $mean) - $lost;
            if ($best === null || $val > $best) { $best = $val; $bestP = $p; }
        }
        if ($best === null) return null;   // no friendly upgrade: 'noeffect' already holds it
        if ($bestP < SWU_BOT_LV_REFORGELIVE_MIN_P) return min($v, -0.4);
        return floatval($W['develop']) * intval(CardCost($cid)) + $best;
    },
]);
