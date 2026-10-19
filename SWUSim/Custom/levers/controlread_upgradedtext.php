<?php
// LEVER 'upgradedtext' (theme 'controlread', report H2). A Weakness token is an upgrade: on an enemy whose text reads "if/while this unit is
// upgraded" it SWITCHES ON that bonus. Migs Mayfeld ASH_168 backfired 3 times in the 2 Wicket losses (7a R8; c56 R4 -> pinged Gideon 2, then
// Hemlock 2) — the only 2 'B' codes in 88 Weakness uses. A give that kills is unchanged; one that only softens such a unit is below a pass.
SWUBotLeverRegister('controlread', 'upgradedtext', [
    'weakness' => function (float $score, int $seat, array $v, bool $enemy, int $weak, array $W, int $loss): ?float {
        if (!$enemy || $loss >= intval($v['remaining'])) return null;                                   // mine, or a kill (damage counted)
        if (intval($v['upgrades']) + intval($v['downgrades']) > 0) return null;                         // already upgraded: nothing new switches on
        if (!preg_match('/\b(?:if|while) this unit is upgraded\b/i', strval(CardText(strval($v['cardID']))))) return null;
        return min($score, -0.5);
    },
]);
