<?php
// LEVER 'capturewaits' (theme 'pricing', G3-26 capture-upgrade-waits-target). SEC_256 Moral Authority ("Attach to a friendly <uq> unit. When
// Played: Attached unit captures an enemy non-leader unit with less remaining HP than it") had no effect — nothing capturable — in 51% of Leia's
// plays (110/217), 30% Chewie, 21% Sabé, 33% Ahsoka. 'condetbvalue' zeroes an unmet When Played, but the +stats still carry the play.
// play — held while no legal host (a friendly unit; unique when the card says <uq>) has more remaining HP, the upgrade's HP included, than
//        some enemy non-leader unit; played as a bare stat upgrade only when no other hand play is on offer.
// (The analyst's "its stats decide an attack this round" exception is not modelled.)

const SWU_BOT_LV_CAPTUREWAITS_RE = '/When Played: Attached unit captures an enemy non-leader unit with less remaining HP than it/i';

SWUBotLeverRegister('pricing', 'capturewaits', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        $t = strval(CardText($cid));
        if (!preg_match(SWU_BOT_LV_CAPTUREWAITS_RE, $t)) return null;
        $unique = stripos($t, '<uq>') !== false || stripos($t, 'unique') !== false;
        $bonus = function_exists('CardUpgradeHp') ? intval(CardUpgradeHp($cid)) : 0;
        $hostHp = -1;
        foreach (SWUBotUnits($seat) as $u) {
            if ($unique && !CardUnique($u['cardID'])) continue;
            $hostHp = max($hostHp, intval($u['remaining']) + $bonus);
        }
        if ($hostHp > 0) {
            foreach (SWUBotEnemyUnits($seat) as $e) if (empty($e['isLeader']) && intval($e['remaining']) < $hostHp) return null;
        }
        $me = SWUBotActionMz($action);
        foreach ($ctx['actions'] ?? [] as $a) {
            if (SWUBotActionKind($a) === 'play' && SWUBotActionMz($a) !== $me) return min($v, -0.4);
        }
        return null;
    },
]);
