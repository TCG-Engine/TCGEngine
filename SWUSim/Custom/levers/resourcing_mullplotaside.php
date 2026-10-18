<?php
// LEVER 'mullplotaside' (theme 'resourcing'; ASH lever hunt G3-40, the PLOT half) — a Plot card the curve mulligan sets aside counts as castable.
// Evidence: Greef Red's Sudden Ferocity is resourced 60% then played via Plot 78% (the intended line), but the mulligan aside treats Plot cards
// at printed cost — and the opening resourcer banks Plot cards first (the Plot budget), so a Plot card always sits in the aside and is counted as
// nothing. Change: when the curve mulligan ('curvemull') would throw a hand back, it is re-judged exactly as _SWUBotCurveMulligan does but with
// each Plot card in the aside counted as one more card castable (played at the deploy), adding no surplus; a keep stands. NOT built: the PILOT
// half ("a Pilot card by its piloting cost") — that is the measured-and-dropped 'pilotcost' proposal (it widened the aggro gap). Hook: 'mulligan'.

// ── SHARED mulligan helpers (the SAME block in every resourcing mulligan lever; the first file loaded defines it) ──────────────────────
// Does the curve-value mulligan ('curvemull', _SWUBotCurveMulligan) decide this hand? Mirrors _SWUBotShouldMulligan's order: the Credit
// 'foddermull' and any explicit '@try-' mulligan proposal answer before it.
if (!function_exists('_SWUBotLvMullCurveDecides')) {
    function _SWUBotLvMullCurveDecides(int $seat, string $style): bool {
        if (!SWUBotFeatureOn('curvemull') || empty(_SWUBotHandCards($seat))) return false;
        if (function_exists('SWUBotFodderMulligan') && SWUBotFodderMulligan($seat)) return false;
        if (SWUBotProposalOn('mullnocast') || SWUBotProposalOn('mullcurve') || SWUBotProposalOn('mullstyle')) return false;
        if (SWUBotProposalOn('mgmull') && SWUBotStyleRank($style) === 2) return false;
        return true;
    }
    // The hand indices the curve mulligan sets aside: the two cards the opening pick would resource.
    function _SWUBotLvMullAside(int $seat, string $style): array {
        return array_map(fn($mz) => intval(substr($mz, strlen('myHand-'))), SWUBotChooseResourceCards(['seat' => $seat, 'style' => $style], 2));
    }
    function _SWUBotLvMullIsPlot(string $cid): bool {
        return in_array('plot', SWUBotCardTags($cid), true) || (bool)preg_match('/(^|\n)\s*Plot\b/', strval(CardText($cid)));
    }
}

SWUBotLeverRegister('resourcing', 'mullplotaside', [
    'mulligan' => function (?bool $mull, int $seat, string $style): ?bool {
        if ($mull !== true || !_SWUBotLvMullCurveDecides($seat, $style)) return null;
        $aside = _SWUBotLvMullAside($seat, $style);
        $H = SWUBotHorizon($style, $seat);
        $clamp = SWUBotProposalOn('mulleventclamp');
        $castable = 0; $surplus = 0.0; $cheap = false; $size = 0; $plots = 0;
        foreach (GetHand($seat) as $i => $o) {
            if ($o === null || !empty($o->removed)) continue;
            $size++;
            $cid = strval($o->CardID ?? '');
            if (in_array($i, $aside, true)) { if (_SWUBotLvMullIsPlot($cid)) $plots++; continue; }
            $c = SWUBotProposalOn('pilotcost') ? _SWUBotSeatCost($seat, $cid) : intval(CardCost($cid));
            if ($c <= 2) $cheap = true;
            if ($c <= 4) {
                $castable++;
                $s = SWUBotCurveSurplus($seat, $cid, $H, false) ?? 0.0;
                if ($clamp && str_contains(strval(CardType($cid)), 'Event')) $s = max(0.0, $s);
                $surplus += $s;
            }
        }
        if ($plots === 0) return null;
        $need = SWUBotProposalOn('mullhandsize') ? max(1, $size - 4) : 2;
        $keep = $castable + $plots >= $need && $surplus >= 0.0 && (SWUBotStyleRank($style) > 1 || $cheap);
        return $keep ? false : null;
    },
]);
