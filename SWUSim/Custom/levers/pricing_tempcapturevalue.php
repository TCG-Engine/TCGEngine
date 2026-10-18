<?php
// LEVER 'tempcapturevalue' (theme 'pricing', G4-26 arrest-phase-capture-value). SEC_195 Arrest ("Your base captures an enemy non-leader unit. At
// the start of the regroup phase, its owner rescues it"): 21 casts in Vane games, 1 win; 4 casts in round 1, e.g. on an exhausted, just-played
// Pirate Snub Fighter rescued the same regroup. The dud gate counts the capture as removal and misses the rescue.
// play — worth, for its best enemy non-leader target, the damage it would still deal this phase (W['base'] x attack power when READY) + the
//        base damage it unlocks (a lone Sentinel in its arena: W['base'] x my ready non-Saboteur attackers' power there). Replaces the flat
//        removal / capture weights; an exhausted non-Sentinel target is worth 0, so with nothing better the card is held.

const SWU_BOT_LV_TEMPCAPTUREVALUE_RE = '/captures an enemy non-leader unit\. At the start of the regroup phase, its owner rescues it/i';

SWUBotLeverRegister('pricing', 'tempcapturevalue', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!preg_match(SWU_BOT_LV_TEMPCAPTUREVALUE_RE, strval(CardText($cid)))) return null;
        $enemies = SWUBotEnemyUnits($seat);
        $mine = SWUBotUnits($seat);
        $best = 0.0;
        foreach ($enemies as $e) {
            if (!empty($e['isLeader'])) continue;
            $val = !empty($e['ready']) ? floatval($W['base']) * intval($e['attackPower']) : 0.0;
            if (!empty($e['sentinel'])) {
                $others = 0;
                foreach ($enemies as $o) if ($o['uid'] !== $e['uid'] && $o['controller'] === $e['controller'] && $o['arena'] === $e['arena'] && !empty($o['sentinel'])) $others++;
                if ($others === 0) {
                    foreach ($mine as $u) if ($u['arena'] === $e['arena'] && !empty($u['ready']) && empty($u['saboteur'])) $val += floatval($W['base']) * intval($u['attackPower']);
                }
            }
            $best = max($best, $val);
        }
        if ($best <= 0.0) return min($v, -0.5);
        if ($v <= -0.4) return null;
        $tags = SWUBotCardTags($cid);
        $flat = (in_array('removal', $tags, true) ? floatval($W['removal']) : 0.0) + (in_array('capture', $tags, true) ? floatval($W['capture'] ?? 0.0) : 0.0);
        return $v - $flat + $best;
    },
]);
