<?php
// LEVER 'hpstrike' (theme 'pricing', G3-28 protect-the-pod-hp-strike). LOF_128 Protect the Pod ("A friendly non-Vehicle unit deals damage equal
// to its remaining HP to an enemy unit") was played 4/47 (Qui-Gon), 8/16 (Avar), in decks running Gungi, Max Rebo, Typho and Consular bodies.
// It carries a flat damage-enemy-unit weight whatever the board.
// play — priced like the power-strike block, by REMAINING HP: no friendly non-Vehicle unit takes the damage weight back; a striker whose
//        remaining HP defeats an unshielded enemy unit adds W['kill'].

const SWU_BOT_LV_HPSTRIKE_RE = '/friendly non-Vehicle unit deals damage equal to its remaining HP to an enemy unit/i';

SWUBotLeverRegister('pricing', 'hpstrike', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!preg_match(SWU_BOT_LV_HPSTRIKE_RE, strval(CardText($cid)))) return null;
        $r = 0;
        foreach (SWUBotUnits($seat) as $u) {
            if (stripos(strval(CardTrait($u['cardID']) ?? ''), 'Vehicle') !== false) continue;
            $r = max($r, intval($u['remaining']));
        }
        if ($r <= 0) return in_array('damage-enemy-unit', SWUBotCardTags($cid), true) ? $v - floatval($W['damage-enemy-unit'] ?? 0.0) : null;
        if ($v <= -0.4) return null;
        foreach (SWUBotEnemyUnits($seat) as $e) {
            if (intval($e['shields']) === 0 && $r >= intval($e['remaining'])) return $v + floatval($W['kill']);
        }
        return null;
    },
]);
