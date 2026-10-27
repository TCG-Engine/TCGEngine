<?php
// LEVER 'deadremoval' (theme 'attrition'). Ninin vs Boba Fett R6: the only enemy unit was a TIE Bomber carrying Boba as a Pilot — a LEADER unit
// ("Attached unit is a leader unit"). The replay bot scored ASH_052 Chimaera ("When Played: You may choose a friendly unit and an enemy
// non-leader unit…") at 10.59 and it then "had no effect", while Hyperspace Disaster sat at 2.80. A unit play keeps its flat 'removal' tag
// value with nothing its When Played may take (the dud gate judges only events). When the removal names a NON-LEADER enemy unit and no
// enemy non-leader unit is in play, that tag value goes.
SWUBotLeverRegister('attrition', 'deadremoval', [
    'play' => function (float $v, array $ctx, int $seat, string $cid, array $action, array $W): ?float {
        if (!str_contains(strval(CardType($cid)), 'Unit') || !in_array('removal', SWUBotCardTags($cid), true)) return null;
        if (!preg_match('/When Played[^.]*\b(?:an? )?enemy non-leader unit/i', strval(CardText($cid)))) return null;
        foreach (SWUBotEnemyUnits($seat) as $e) if (empty($e['isLeader'])) return null;
        return $v - floatval($W['removal'] ?? 0.0);
    },
]);
