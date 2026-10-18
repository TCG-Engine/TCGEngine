<?php
// LEVER 'shipbeforebombard' (theme 'pricing', G1-32 bombardment-after-capital-ship). JTL_181 Planetary Bombardment reads "Deal 8 indirect
// damage to a player. If you control a Capital Ship unit, deal 12 indirect damage instead." Held in 219 of 233 Piett games and 54 of 79 Aurra
// games; the value model is blind to the clause and nothing plays a Capital Ship first.
// play — Planetary Bombardment with a Capital Ship in play carries the 4 extra points (4 x W['base']); without one, while a Capital Ship in
//        hand is castable now, it waits for the ship (an enabler order) and the ship gets +0.5 for going first.
// Stands alone: the 8 points themselves stay priced as today (the held 'indirectprice' proposal is NOT switched on).

const SWU_BOT_LV_SHIPBEFOREBOMBARD_RE = '/If you control a Capital Ship unit, deal (\d+) indirect damage instead/i';
const SWU_BOT_LV_SHIPBEFOREBOMBARD_SHIP_BONUS = 0.5;

function _SWUBotLvShipbeforebombardIsShip(string $cid): bool {
    return str_contains(strval(CardType($cid)), 'Unit') && str_contains(strval(CardTrait($cid) ?? ''), 'Capital Ship');
}
function _SWUBotLvShipbeforebombardControls(int $seat): bool {
    foreach (SWUBotUnits($seat) as $u) if (_SWUBotLvShipbeforebombardIsShip($u['cardID'])) return true;
    return false;
}
// The bonus the Capital Ship clause adds over the base amount ("Deal 8 … deal 12 instead" → 4), 0 for any other card.
function _SWUBotLvShipbeforebombardExtra(string $cid): int {
    $t = strval(CardText($cid));
    if (!preg_match(SWU_BOT_LV_SHIPBEFOREBOMBARD_RE, $t, $m) || !preg_match('/Deal (\d+) indirect damage/i', $t, $b)) return 0;
    return max(0, intval($m[1]) - intval($b[1]));
}

SWUBotLeverRegister('pricing', 'shipbeforebombard', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        $i = intval(substr(SWUBotActionMz($action), strlen('myHand-')));
        if (($extra = _SWUBotLvShipbeforebombardExtra($cid)) > 0) {
            if (_SWUBotLvShipbeforebombardControls($seat)) return $v <= -0.4 ? null : $v + $extra * floatval($W['base']);
            $cap = SWUTotalPaymentCapacity($seat);
            foreach (GetHand($seat) as $j => $o) {
                if ($j === $i || $o === null || !empty($o->removed) || !_SWUBotLvShipbeforebombardIsShip(strval($o->CardID ?? ''))) continue;
                if (intval(SWUComputePlayCost($seat, $o)) <= $cap) return min($v, -0.4);
            }
            return null;
        }
        if ($v <= -0.4 || !_SWUBotLvShipbeforebombardIsShip($cid) || _SWUBotLvShipbeforebombardControls($seat)) return null;
        foreach (GetHand($seat) as $j => $o) {
            if ($j !== $i && $o !== null && empty($o->removed) && _SWUBotLvShipbeforebombardExtra(strval($o->CardID ?? '')) > 0)
                return $v + SWU_BOT_LV_SHIPBEFOREBOMBARD_SHIP_BONUS;
        }
        return null;
    },
]);
