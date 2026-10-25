<?php
// Twin Suns house rules — the fixed menu (spec: docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md).
// The waiting room renders these rows (via SWULobbyAdapter::houseRuleDefs) and the engine reads the chosen ones back
// through SWUHouseRule(). ORDER MATTERS: it is the display order AND the tie-break when both rules of an exclusive
// group qualify at the same moment (LobbyHouseRulesResolve).
//
// 'value' is null for an on/off rule, else the host-adjustable number's default and range.
// 'group' names an exclusive group: at most one of its rules may be active.

if (!function_exists('SWUHouseRuleDefs')) {
function SWUHouseRuleDefs(): array {
    return [
        ['id' => 'extraBaseHp', 'label' => 'Extra Base HP',
         'desc' => 'Every base gets this much extra HP.',
         'value' => ['default' => 10, 'min' => 0, 'max' => 30, 'step' => 5], 'group' => null],
        ['id' => 'takeCredit', 'label' => 'Take Credit 4th Action',
         'desc' => 'Adds a Credit counter to the round: taking it gives you a Credit token and ends your round, like Blast or Plan.',
         'value' => null, 'group' => 'fourthAction'],
        ['id' => 'takeRestore', 'label' => 'Take Restore 4th Action',
         'desc' => 'Adds a Restore counter to the round: taking it heals your base by this much and ends your round, like Blast or Plan.',
         'value' => ['default' => 1, 'min' => 1, 'max' => 5, 'step' => 1], 'group' => 'fourthAction'],
        ['id' => 'planScry', 'label' => 'Plan Scry',
         'desc' => 'Taking Plan looks at this many cards from the top of your deck instead: put each on the top or bottom. Nothing is drawn.',
         'value' => ['default' => 2, 'min' => 1, 'max' => 5, 'step' => 1], 'group' => null],
    ];
}
}
