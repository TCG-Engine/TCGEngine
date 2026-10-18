<?php
// LEVER 'gifttokens' (theme 'pricing', G2-10 gift-tokens-cost-their-attacks). JTL_155 They Hate That Ship ("An opponent creates 2 TIE Fighter
// tokens and readies them. Then, play a Vehicle unit from your hand. It costs 3 resources less") was played in 29% of Poe games at 11% WR (deck
// 38%); trace poe_krennic R1: the 2 READY TIEs hit the base the same round. 'nogift' misses created tokens; the discount tag prices 0.
// play — charged the bodies the opponent gains: per token, W['loss'] x its unit value (a 1/1 token = 1) + W['base'] x its power when it comes
//        in ready; credited what it buys: the best Vehicle in hand it makes castable — W['develop'] x the resources saved net of this card,
//        plus that Vehicle's own play value when it could not be cast without the discount. No such Vehicle: the charge alone.

const SWU_BOT_LV_GIFTTOKENS_RE = '/An opponent creates (\d+) ([A-Za-z\'\- ]+?) tokens?( and readies them)?\./i';
const SWU_BOT_LV_GIFTTOKENS_TOKEN_STATS = ['TIE Fighter' => [1, 1], 'X-Wing' => [2, 2], 'Clone Trooper' => [2, 2], 'Battle Droid' => [1, 1], 'Spy' => [0, 2]];

SWUBotLeverRegister('pricing', 'gifttokens', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        $t = strval(CardText($cid));
        if (!preg_match(SWU_BOT_LV_GIFTTOKENS_RE, $t, $m)) return null;
        $n = intval($m[1]);
        [$p, $h] = SWU_BOT_LV_GIFTTOKENS_TOKEN_STATS[trim($m[2])] ?? [1, 1];
        $charge = $n * (floatval($W['loss']) * max(1.0, ($p + $h) / 2.0) + (($m[3] ?? '') !== '' ? floatval($W['base']) * $p : 0.0));
        $credit = 0.0;
        if (preg_match('/play a Vehicle unit from your hand\. It costs (\d+) resources? less/i', $t, $dm)) {
            $disc = intval($dm[1]);
            $i = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
            $self = GetHand($seat)[$i] ?? null;
            $cap = SWUTotalPaymentCapacity($seat);
            $left = $cap - ($self !== null ? intval(SWUComputePlayCost($seat, $self)) : intval(CardCost($cid)));
            foreach (GetHand($seat) as $j => $o) {
                if ($j === $i || $o === null || !empty($o->removed)) continue;
                $vc = strval($o->CardID ?? '');
                if (!str_contains(strval(CardType($vc)), 'Unit') || stripos(strval(CardTrait($vc) ?? ''), 'Vehicle') === false) continue;
                $full = intval(SWUComputePlayCost($seat, $o));
                if (max(0, $full - $disc) > $left) continue;
                $saved = min($disc, $full) - ($cap - $left);
                $c = floatval($W['develop']) * $saved + ($full > $cap ? _SWUBotPlayValue($seat, $vc, $W) : 0.0);
                $credit = max($credit, $c);
            }
        }
        return $v + $credit - $charge;
    },
]);
