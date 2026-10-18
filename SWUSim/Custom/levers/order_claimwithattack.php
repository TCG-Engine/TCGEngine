<?php
// LEVER 'claimwithattack' (theme 'order', ASH lever hunt G1-11). Off by default: '@try-claimwithattack' / '@try-order'.
// ASH_155 Grogu: "When you take the initiative: You may attack with a unit." The bot claimed with Grogu in play 39 times and an attack
// followed 2 times: rule 8 ('no-unused-attacks') sends every ready unit in first, then the claim finds nobody to attack with.
// With such a trigger on my board, the initiative claimable, and my round down to attacks / pass / claim with exactly ONE attack
// left, that attack leaves the list: rule 8 abstains, 'nothing-left' claims, and the trigger makes the attack — the claim and the
// swing in one action.

function _SWUBotLvClaimwithattackTrigger(int $seat): bool {
    foreach (SWUBotUnits($seat) as $u) {
        $t = !empty($u['isLeader']) ? CardDeployText($u['cardID']) : CardText($u['cardID']);
        if (preg_match('/When you take the initiative: You may attack with a unit/i', strval($t))) return true;
    }
    return false;
}

SWUBotLeverRegister('order', 'claimwithattack', [
    'filter' => function (array $actions, array $ctx): ?array {
        if (($ctx['kind'] ?? '') !== 'free-play') return null;
        $attacks = 0; $init = false;
        foreach ($actions as $a) {
            $k = SWUBotActionKind($a);
            if ($k === 'attack') $attacks++;
            elseif ($k === 'initiative') $init = true;
            elseif ($k !== 'pass') return null;
        }
        if ($attacks !== 1 || !$init || !str_contains(strval(GetInitiativeCounter() ?? ''), 'UNCLAIMED')) return null;
        if (!_SWUBotLvClaimwithattackTrigger(intval($ctx['seat']))) return null;
        return array_values(array_filter($actions, fn($a) => SWUBotActionKind($a) !== 'attack'));
    },
]);
