<?php
// LEVER 'actionspareres' (theme 'order', ASH lever hunt G2-04). Off by default: '@try-actionspareres' / '@try-order'.
// SEC_004 Leia's "Action [1 resource, Exhaust]" was taken FIRST in the round, before the unit plays (traced R1/R2: Action, then
// Mina). A 1-resource leader Action whose effect does not scale with timing is a spare-resource play: while a hand card the
// fallback wants (score > 0) is still castable, the Action waits just below it — spend first, then the Action with the leftover.
// Exempt: an Action that enables this round's plays (a Credit, a cost reduction, "play a …"), one that attacks ("Attack with"),
// and an Action that GROWS a unit while a ready unit of mine can still attack (it goes before that attack, as the fallback has it).

const SWU_BOT_LV_ACTIONSPARERES_RE = '/^\s*Action \[1 resource, Exhaust\]:\s*(.*)$/mi';

SWUBotLeverRegister('order', 'actionspareres', [
    'ability' => function (array $ctx, int $seat, array $action, array $W): ?float {
        if (!empty($GLOBALS['SWUBotLvActionspareresDepth']) || SWUBotActionKind($action) !== 'leader-ability') return null;
        $l = _SWUBotActionLeader($seat, $action);
        if ($l === null || in_array(strval($l->Deployed ?? 'false'), ['true', '1'], true)) return null;
        if (!preg_match(SWU_BOT_LV_ACTIONSPARERES_RE, strval(CardText(strval($l->CardID ?? ''))), $m)) return null;
        $effect = $m[1];
        if (preg_match('/Credit|costs? \d+ resources? less|Attack with|\bplay (?:a|an|any)\b/i', $effect)) return null;
        $grows = (bool)preg_match('/Experience token|\+\d+\/\+\d+/i', $effect);
        if ($grows) {
            foreach ((array)($ctx['actions'] ?? []) as $a) {
                if (SWUBotActionKind($a) !== 'attack') continue;
                $v = SWUBotViewForMz($seat, SWUBotActionMz($a));
                if ($v !== null && !empty($v['ready'])) return null;
            }
        }
        $GLOBALS['SWUBotLvActionspareresDepth'] = 1;
        try {
            $bestPlay = null;
            foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
                if (SWUBotActionKind($a) !== 'play') continue;
                $s = SWUBotScoreAction($ctx, $a, intval($i));
                if ($s > 0.0) $bestPlay = max($bestPlay ?? $s, $s);
            }
            if ($bestPlay === null) return null;
            $base = _SWUBotAbilityValue($ctx, $action, $W);
        } finally { $GLOBALS['SWUBotLvActionspareresDepth'] = 0; }
        if ($base <= 0.0 || $base < $bestPlay - 0.01) return null;
        return $bestPlay - 0.01;
    },
]);
