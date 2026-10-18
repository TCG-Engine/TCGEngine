<?php
// LEVER 'arenastrikekeep' (theme 'pricing', G4-19 salvo-keep-with-a-big-ship). Turbolaser Salvo JTL_131 was resourced 102 of 167 (Piett Blue,
// 61%) and 28 of 76 (Colossus), cast in 3-6% of games at median R8-9 — and won 78% / 100% when cast. Piett's ships hit for 6-12, so Salvo is a
// one-sided arena wipe; it fell to the castable-soon rule at 7 cost, as 'wipekeep' / 'gloom' read printed wipes and -N/-N only.
// keep — an "a friendly unit deals damage equal to its power to each enemy unit in that arena" card is kept (+150, a wipe-tier keep) while a
//        5+ power friendly space unit is in play (or in hand, castable within 2 rounds) and an enemy has 2+ units in one arena.
// The control wing's resourcing3 tiers sort before the keep value, so a control-styled Piett is reached only within its tier.

const SWU_BOT_LV_ARENASTRIKEKEEP_RE = '/friendly (space )?unit deals damage equal to its power to each enemy unit in that arena/i';
const SWU_BOT_LV_ARENASTRIKEKEEP_POWER = 5;

SWUBotLeverRegister('pricing', 'arenastrikekeep', [
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (!preg_match(SWU_BOT_LV_ARENASTRIKEKEEP_RE, strval(CardText($cid)))) return null;
        $per = ['Ground' => 0, 'Space' => 0];
        foreach (SWUBotEnemyUnits($seat) as $u) $per[$u['arena']] = ($per[$u['arena']] ?? 0) + 1;
        if (max($per) < 2) return null;
        $big = false;
        foreach (SWUBotUnits($seat) as $u) if ($u['arena'] === 'Space' && intval($u['power']) >= SWU_BOT_LV_ARENASTRIKEKEEP_POWER) { $big = true; break; }
        if (!$big) {
            $soon = SWUResourceCount($seat) + 2;
            foreach (GetHand($seat) as $j => $o) {
                if ($j === $i || $o === null || !empty($o->removed)) continue;
                $h = strval($o->CardID ?? '');
                if (str_contains(strval(CardType($h)), 'Unit') && _SWUBotPlayArena($h) === 'Space' && intval(CardPower($h)) >= SWU_BOT_LV_ARENASTRIKEKEEP_POWER
                    && _SWUBotSeatCost($seat, $h) <= $soon) { $big = true; break; }
            }
        }
        return $big ? $keep + 150.0 : null;
    },
]);
