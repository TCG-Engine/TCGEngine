<?php
// LEVER 'voltronkeep' (theme 'resourcing'; ASH lever hunt G1-27) — a Saboteur-leader deck keeps one Mastery and one Knight's Saber for its leader.
// Evidence (Ezra Blue: Mastery, Knight's Saber, Whistling Birds onto Saboteur Ezra): Mastery resourced in 52 of 109 games where drawn, cast 23;
// Knight's Saber went on Ezra 36 times, Mastery only 5 — 'upgradepicks' / 'hostpolicy' choose the host, but the upgrades are resource fodder by
// cost. Change (rank <= 2): while my leader's deployed side has Saboteur and it is deployed or deployable within 2 resources, the first Mastery and
// the first Knight's Saber in hand keep +10 on the aggro wing's '-$cost' scale (+100 on midrange's castable scale) — the leader itself is the
// unique / Jedi host. Hook: 'keep'.

const SWU_BOT_LV_VOLTRONKEEP_TITLES = ['Mastery', "Knight's Saber"];
const SWU_BOT_LV_VOLTRONKEEP_WITHIN = 2;

function _SWUBotLvVoltronkeepOn(int $seat): bool {
    foreach (GetLeader($seat) as $l) {
        if ($l === null || !empty($l->removed)) continue;
        if (!preg_match('/(^|\n)\s*Saboteur\b/', strval(CardDeployText(strval($l->CardID ?? ''))))) continue;
        if (in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) return true;
        if (_SWUBotLeaderThreshold($seat, $l) - SWUResourceCount($seat) <= SWU_BOT_LV_VOLTRONKEEP_WITHIN) return true;
    }
    return false;
}

SWUBotLeverRegister('resourcing', 'voltronkeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        $rank = SWUBotStyleRank(strval($ctx['style'] ?? ''));
        $title = strval(CardTitle($cid));
        if ($rank > 2 || !in_array($title, SWU_BOT_LV_VOLTRONKEEP_TITLES, true) || !str_contains(strval(CardType($cid)), 'Upgrade')) return null;
        foreach (_SWUBotHandCards($seat) as $j => $h) {   // the first copy only
            if ($j >= $i) break;
            if (strval(CardTitle($h)) === $title) return null;
        }
        if (!_SWUBotLvVoltronkeepOn($seat)) return null;
        return $keep + ($rank <= 1 ? 10.0 : 100.0);
    },
]);
