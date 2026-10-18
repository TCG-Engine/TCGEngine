<?php
// LEVER 'vanecheapupgrade' (theme 'engines', ASH lever hunt G4-24). Off by default: '@try-vanecheapupgrade' / '@try-engines'.
// ASH_012 Vane: front "Action [Exhaust, defeat a friendly upgrade]: Deal 2 damage to a base"; deployed "On Attack: You may defeat a
// friendly upgrade. If you do, deal 2 damage to the defending unit or a base." Defeated so far: Shield 55, Advantage 27, Boba Fett
// PILOT 11, Sudden Ferocity 6, Kill Switch 2, Condemn 1 — a 5-cost pilot went while a token was usually there ('upgradecost' prices
// attachments for removal, not this cost prompt).
// The cost is paid in this order: a downgrade on my unit (good riddance) < an Advantage on an exhausted unit < a Shield on a unit
// nothing is about to hit (non-Sentinel) < an Experience < a spent Plan (Preparation) < a real upgrade by printed cost (a pilot is its
// card). Both prompts are ordered so: the host pick (each host by its cheapest upgrade) and the staged upgrade pick. The deployed
// On Attack is declined when only a pilot or a 3+ cost upgrade could pay, unless the 2 damage defeats the defender or the base.

function _SWUBotLvVanecheapupgradeRank(string $cid, ?array $host): float {
    if (SWUBotIsDowngrade($cid)) return -1.0;
    $ready = $host !== null && !empty($host['ready']);
    if ($cid === 'ASH_T02') return $ready ? 0.6 : 0.0;                                       // Advantage
    if ($cid === 'SOR_T02') return ($host !== null && !empty($host['sentinel'])) ? 1.2 : 0.3; // Shield
    if ($cid === 'SOR_T01') return 1.5;                                                      // Experience
    if ($cid === 'ASH_228') return 1.0;                                                      // Preparation: its job is done
    return 2.0 + max(1, intval(CardCost($cid)));
}
function _SWUBotLvVanecheapupgradeHostRank(int $seat, string $mz): ?float {
    $v = SWUBotViewForMz($seat, $mz);
    if ($v === null || !isset($v['obj'])) return null;
    $best = null;
    foreach (GetUpgradesOnUnit($v['obj']) as $u) {
        $r = _SWUBotLvVanecheapupgradeRank(strval($u->CardID ?? ''), $v);
        $best = min($best ?? $r, $r);
    }
    return $best;
}
function _SWUBotLvVanecheapupgradeThen(): string {
    return class_exists('DecisionQueueController') ? strval(DecisionQueueController::GetVariable('DefeatUpgThen') ?? '') : '';
}
function _SWUBotLvVanecheapupgradeIsVane(int $seat): bool {
    foreach (GetLeader($seat) as $l) if ($l !== null && empty($l->removed) && strval($l->CardID ?? '') === 'ASH_012') return true;
    return false;
}

SWUBotLeverRegister('engines', 'vanecheapupgrade', [
    'decision' => function (array $ctx, int $seat, string $tip, string $type, string $head, string $c, array $W): ?float {
        if (!_SWUBotLvVanecheapupgradeIsVane($seat)) return null;
        // The host pick (front: "(cost)"; On Attack: plain), only inside Vane's own flow.
        if (in_array($tip, ['Defeat_a_friendly_upgrade_(cost)', 'Defeat_a_friendly_upgrade'], true) && str_starts_with(_SWUBotLvVanecheapupgradeThen(), 'ASH_012#')) {
            $r = _SWUBotLvVanecheapupgradeHostRank($seat, $c);
            return $r === null ? null : 10.0 - $r;
        }
        // The staged pick among one host's upgrades.
        if ($tip === 'Choose_an_upgrade_to_defeat' && str_starts_with(_SWUBotLvVanecheapupgradeThen(), 'ASH_012#') && preg_match('/^myTempZone-(\d+)$/', $c, $m)) {
            $o = GetTempZone($seat)[intval($m[1])] ?? null;
            if ($o === null) return null;
            $hostMz = class_exists('DecisionQueueController') ? strval(DecisionQueueController::GetVariable('DefeatUpgHost') ?? '') : '';
            return 10.0 - _SWUBotLvVanecheapupgradeRank(strval($o->CardID ?? ''), $hostMz !== '' ? SWUBotViewForMz($seat, $hostMz) : null);
        }
        // The deployed On Attack: not for a pilot or a 3+ cost upgrade, unless the 2 kills.
        if ($type === 'YESNO' && $tip === 'Defeat_a_friendly_upgrade_to_deal_2_damage?') {
            $cheapest = null;
            foreach (SWUBotUnits($seat) as $u) {
                foreach (GetUpgradesOnUnit($u['obj']) as $up) {
                    $r = _SWUBotLvVanecheapupgradeRank(strval($up->CardID ?? ''), $u);
                    $cheapest = min($cheapest ?? $r, $r);
                }
            }
            if ($cheapest === null || $cheapest < 5.0) return null;   // a token / Plan / 1-2 cost upgrade pays
            $kills = false;
            foreach (SWUBotOpponents($seat) as $o) if (SWUBaseRemainingHp(intval($o)) <= 2) $kills = true;
            $def = function_exists('GetSWUVar') ? strval(GetSWUVar('SWU_CURRENT_DEFENDER') ?? '') : '';
            if ($def !== '' && str_contains($def, 'Arena') && ($dv = SWUBotViewForMz($seat, $def)) !== null
                && intval($dv['shields']) === 0 && intval($dv['remaining']) <= 2) $kills = true;
            if ($kills) return null;
            return $c === 'YES' ? -0.5 : 0.5;
        }
        return null;
    },
]);
