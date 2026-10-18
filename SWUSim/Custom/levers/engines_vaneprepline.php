<?php
// LEVER 'vaneprepline' (theme 'engines', ASH lever hunt G4-25). Off by default: '@try-vaneprepline' / '@try-engines'.
// ASH_228 Preparation ("When Played: Exhaust attached unit", +2/+1) is fuel for ASH_012 Vane ("defeat a friendly upgrade: deal 2 damage
// to a base"), and 'hostpolicy' already sends it onto a unit that just entered play or already attacked — but its play value is ~0
// (exhausting my own unit), so it was cast once in 70 games against 31 resourcings. LOF_139 Battle Fury (+3/+3) is the same fuel.
// With Vane able to eat it (front Action ready, or deployed Vane ready to attack): Preparation is worth the 2 base damage it becomes
// plus its stats on an exhausted host (a body's worth, W['develop'] per point pair); Battle Fury the 2 damage plus its power on a
// ready host's swing (its stats otherwise), less half a card for its "discard a card" On Attack.

function _SWUBotLvVaneprepVaneReady(int $seat): bool {
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed) || strval($l->CardID ?? '') !== 'ASH_012') continue;
        if (!in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) return _SWUBotLeaderReady($l);
        foreach (SWUBotUnits($seat) as $u) if (!empty($u['isLeader']) && $u['cardID'] === 'ASH_012' && !empty($u['ready'])) return true;
    }
    return false;
}

SWUBotLeverRegister('engines', 'vaneprepline', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (($cid !== 'ASH_228' && $cid !== 'LOF_139') || !_SWUBotLvVaneprepVaneReady($seat)) return null;
        $p = intval(CardUpgradePower($cid) ?? 0); $h = intval(CardUpgradeHp($cid) ?? 0);
        $fuel = 2.0 * floatval($W['base']);
        $units = SWUBotUnits($seat);
        if ($cid === 'ASH_228') {
            if (!array_filter($units, fn($u) => empty($u['ready']))) return null;   // nothing already spent to put it on
            $nv = $fuel + floatval($W['develop']) * ($p + $h) / 2.0;
        } else {
            if (empty($units)) return null;
            $readyHost = (bool)array_filter($units, fn($u) => !empty($u['ready']));
            $nv = $fuel + ($readyHost ? floatval($W['base']) * $p : floatval($W['develop']) * ($p + $h) / 2.0) - 0.5 * floatval($W['draw'] ?? 0.0);
        }
        return $nv > $v ? $nv : null;
    },
]);
