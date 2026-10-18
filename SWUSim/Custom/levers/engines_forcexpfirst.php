<?php
// LEVER 'forcexpfirst' (theme 'engines', ASH lever hunt G3-36). Off by default: '@try-forcexpfirst' / '@try-engines'.
// LOF_008 Obi-Wan Kenobi's front Action ("Exhaust, use the Force: give an Experience token to a unit without one") ran in 33% of
// pre-deploy rounds (Yellow Force), and claims came without it 52% of the time; 'forcebuff' fixed only the empty-board gate. The
// Experience belongs on a unit BEFORE it attacks (then The Holy City refills the Force on a Force-unit attack): while the Action is
// worth using now, a ready unit of mine without an Experience token holds its attack just below the Action. The claim side is
// 'actionbeforeclaim' (p51).

SWUBotLeverRegister('engines', 'forcexpfirst', [
    'attack' => function (float $s, array $ctx, int $seat, array $att, array $action, array $W): ?float {
        if (!empty($GLOBALS['SWUBotLvForcexpfirstDepth']) || !empty($att['isLeader']) || empty($att['ready']) || !PlayerHasTheForce($seat)) return null;
        if (_SWUBotLeaderFront($seat, 'LOF_008') === null || !isset($att['obj']) || _CountExperienceSubcards($att['obj']) > 0) return null;
        $ability = null;
        foreach ((array)($ctx['actions'] ?? []) as $i => $a) {
            if (SWUBotActionKind($a) !== 'leader-ability' || ($l = _SWUBotActionLeader($seat, $a)) === null || strval($l->CardID ?? '') !== 'LOF_008') continue;
            $GLOBALS['SWUBotLvForcexpfirstDepth'] = 1;
            try { $ability = SWUBotScoreAction($ctx, $a, intval($i)); } finally { $GLOBALS['SWUBotLvForcexpfirstDepth'] = 0; }
            break;
        }
        if ($ability === null || $ability <= 0.0 || $s < $ability - 0.01) return null;
        return $ability - 0.01;
    },
]);
