<?php
// LEVER 'upgradefuelkeep' (theme 'resourcing'; ASH lever hunt G4-23) — ASH_012 Vane keeps the cheap upgrades his Action burns.
// Vane: "Action [Exhaust, defeat a friendly upgrade]: Deal 2 damage to a base" (deployed: On Attack, defeat a friendly upgrade for 2 damage).
// Evidence (resourced when in hand): Preparation 31/32, Commence the Festivities 28/28, Battle Fury 28/35, Sudden Ferocity 12/14, Ascension Cable
// 10/18; played: Preparation 1, Battle Fury 2, Ascension Cable 0, Commence 0 in 70 games — the midrange keep (100 + cost) banks the cheapest
// castable card first. Change: while Vane (front or deployed) is my leader, a friendly (non-downgrade) upgrade of seat cost <= 2 keeps +100
// (it is 2 base damage for its cost through the Action). Hook: 'keep' (Vane plays midrange: the plain resourcer).

const SWU_BOT_LV_UPGRADEFUELKEEP_BONUS = 100.0;
const SWU_BOT_LV_UPGRADEFUELKEEP_MAX_COST = 2;

function _SWUBotLvUpgradefuelkeepLeader(int $seat): bool {
    foreach (GetLeader($seat) as $l) if ($l !== null && empty($l->removed) && strval($l->CardID ?? '') === 'ASH_012') return true;
    return false;
}

SWUBotLeverRegister('resourcing', 'upgradefuelkeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (!str_contains(strval(CardType($cid)), 'Upgrade') || SWUBotIsDowngrade($cid)) return null;
        if (_SWUBotSeatCost($seat, $cid) > SWU_BOT_LV_UPGRADEFUELKEEP_MAX_COST || !_SWUBotLvUpgradefuelkeepLeader($seat)) return null;
        return $keep + SWU_BOT_LV_UPGRADEFUELKEEP_BONUS;
    },
]);
