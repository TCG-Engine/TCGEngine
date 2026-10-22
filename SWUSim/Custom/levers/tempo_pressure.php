<?php
// LEVER 'pressure' (theme 'tempo', shipped in p58). Spec 2026-10-20-swusim-tempo-flavour-design.md §2. Owner: midrange "against control
// styles, they want to be more aggressive to put pressure on early"; "soft control does want to put pressure into hard control … bait out
// removal while saving their own removal for their big threats" (the saving is 'threathold', p5); against another soft control "play almost
// midrangey. put pressure, value trades, then hit hard with late-game bombs".
// Gate: my deck is tempo, my archetype rank is 2 (midrange) or 3 (soft control), and the opponent's meta rank >= 2.5 (a control deck).
// target   — a base hit is worth 20% more.
// decision — a bounce / capture pick, or a shrink that kills it, takes an enemy SENTINEL in an arena where I have a ready attacker. (An
//            exhausted Sentinel still guards — final review #6 — so exhaust and non-lethal debuffs are left to the scorer.)

function _SWUBotTempoPresses(int $seat, int $rank): bool {
    return ($rank === 2 || $rank === 3) && _SWUBotTempoOn($seat) && _SWUBotTempoOppRank($seat) >= 2.5;
}

SWUBotLeverRegister('tempo', 'pressure', [
    'target' => function (float $tv, array $att, ?array $def, array $W): ?float {
        if ($def !== null || $tv <= 0) return null;
        $seat = intval($att['controller'] ?? 0);
        if ($seat <= 0 || !_SWUBotTempoPresses($seat, intval($W['archetypeRank'] ?? 2))) return null;
        return $tv * SWUBotLeverNum('tempo.pressureface', 1.2);
    },
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!preg_match('/return|capture|-\d+\/-\d+/i', $tip) || preg_match('/deal/i', $tip) || _SWUBotTooltipEffect($tip) !== 'hostile') return null;
        if (!SWUBotIsEnemyMz($seat, $c) || !_SWUBotTempoPresses($seat, intval($W['archetypeRank'] ?? 2))) return null;
        $v = SWUBotViewForMz($seat, $c);
        if ($v === null || empty($v['sentinel'])) return null;
        if (preg_match('/-\d+\/-(\d+)/', $tip, $m) && intval($m[1]) < intval($v['remaining'])) return null;   // a shrink must kill it
        foreach (SWUBotUnits($seat) as $u) {
            if (!empty($u['ready']) && $u['arena'] === $v['arena'] && intval($u['attackPower']) > 0) return SWUBotLeverNum('tempo.clearsentinel', 5.0);
        }
        return null;
    },
]);
