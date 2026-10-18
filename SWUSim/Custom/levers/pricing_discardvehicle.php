<?php
// LEVER 'discardvehicle' (theme 'pricing', G2-11 salvage-replays-the-best-vehicle). JTL_121 Salvage ("Play a Vehicle unit from your discard pile
// (paying its cost). Then, deal 1 damage to it") — Piett 88% of lists — was played in 0% of Piett games and resourced 92 of 96 sightings; trace
// poe_krennic R4 offered it with Pegasus in the discard, not played. Its only tag, 'recursion', weighs 0.
// play — worth the best Vehicle in my discard that I can pay for after this card, as a play from the discard (_SWUBotPlayValue 'discard', its
//        real play cost incl. Piett's Capital Ship discount), less W['chip'] per point of the damage it takes.
// keep — +100 while a 4+ cost Vehicle is in my discard, or (a capital-ship deck) a Capital Ship of mine is in play to die.

const SWU_BOT_LV_DISCARDVEHICLE_RE = '/Play a Vehicle unit from your discard pile \(paying its cost\)\. Then, deal (\d+) damage to it/i';

function _SWUBotLvDiscardvehicleIsVehicle(string $c): bool {
    return str_contains(strval(CardType($c)), 'Unit') && stripos(strval(CardTrait($c) ?? ''), 'Vehicle') !== false;
}

SWUBotLeverRegister('pricing', 'discardvehicle', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!preg_match(SWU_BOT_LV_DISCARDVEHICLE_RE, strval(CardText($cid)), $m)) return null;
        $i = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
        $self = GetHand($seat)[$i] ?? null;
        $left = SWUTotalPaymentCapacity($seat) - ($self !== null ? intval(SWUComputePlayCost($seat, $self)) : intval(CardCost($cid)));
        $best = null;
        foreach (GetDiscard($seat) as $o) {
            if ($o === null || !empty($o->removed)) continue;
            $vc = strval($o->CardID ?? '');
            if (!_SWUBotLvDiscardvehicleIsVehicle($vc) || intval(SWUComputePlayCost($seat, $o)) > $left) continue;
            $val = _SWUBotPlayValue($seat, $vc, $W, 'discard') - floatval($W['chip']) * intval($m[1]);
            $best = $best === null ? $val : max($best, $val);
        }
        return ($best === null || $best <= $v) ? null : $best;
    },
    'keep' => function (float $keep, array $ctx, int $seat, string $cid, int $i): ?float {
        if (!preg_match(SWU_BOT_LV_DISCARDVEHICLE_RE, strval(CardText($cid)))) return null;
        foreach (GetDiscard($seat) as $o) {
            if ($o !== null && empty($o->removed) && _SWUBotLvDiscardvehicleIsVehicle(strval($o->CardID ?? '')) && intval(CardCost(strval($o->CardID ?? ''))) >= 4) return $keep + 100.0;
        }
        if (in_array('capital-ship', SWUBotDeckFlavours($seat), true)) {
            foreach (SWUBotUnits($seat) as $u) if (str_contains(strval(CardTrait($u['cardID']) ?? ''), 'Capital Ship')) return $keep + 100.0;
        }
        return null;
    },
]);
