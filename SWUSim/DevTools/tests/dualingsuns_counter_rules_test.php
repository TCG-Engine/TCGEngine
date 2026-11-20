<?php
// Dualing Suns — the bot's legal-action enumeration (infrastructure, not a heuristic). At two seats with the dualingsuns
// pool, a seat ends its round by taking Initiative or Plan: both are offered, Pass and the Blast are not. Without the pool the
// same board is Premier: Pass is offered and Plan is not. Pass being refused is what makes the counters load-bearing for
// termination (see BotLegalActions.php's note on the Twin Suns counters), so a bot that saw only Pass would stall the game.
// docs/superpowers/specs/2026-11-12-swusim-dualing-suns-design.md
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 SWUSim/DevTools/tests/dualingsuns_counter_rules_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
$ids = fn($legal) => array_map(fn($a) => strval($a['cardID'] ?? ''), (array)($legal['actions'] ?? []));
$PLAN  = 'PlanCounter-0!CustomInput!TakeCounter';
$INIT  = 'InitiativeCounter-0!CustomInput!TakeInitiative';
$PASS  = 'myHealth-0!CustomInput!Pass';
$hasBlast = fn($cards) => (bool)array_filter($cards, fn($c) => str_starts_with($c, 'BlastCounter-'));

// The start of a round: P1 holds the initiative unclaimed, Blast and Plan in the centre, nobody has taken a counter.
$round = function (?string $pool) use ($build) {
    $build(function ($b) { $b->WithGroundUnitForPlayer(2, 'SOR_095'); });
    SetInitiativeCounter('P1_UNCLAIMED'); SetBlastCounter('AVAILABLE'); SetPlanCounter('AVAILABLE');
    SetSWUVar('SWU_COUNTER_TAKEN', '');
    if ($pool !== null) DecisionQueueController::StoreVariable('SWUCardPool', $pool);
};

echo "DualingSuns:\n";
$round('dualingsuns');
$cards = $ids(SWUBotLegalActions($gameName, 1));
$check(in_array($PLAN, $cards, true), 'Plan is a legal action');
$check(in_array($INIT, $cards, true), 'Initiative is a legal action');
$check(!$hasBlast($cards), 'the Blast is never offered');
$check(!in_array($PASS, $cards, true), 'Pass is refused while a counter is there');

// The board's toll hint (actions data 'initiativeToll'): what taking the initiative costs THIS seat right now.
echo "TollInActionsData:\n";
$check((SWUComputeActionsData(1)['initiativeToll'] ?? null) === 0, 'P1 holds it: keeping is free (0)');
$check((SWUComputeActionsData(2)['initiativeToll'] ?? null) === 1, 'P2 would steal it: 1');

echo "PremierUnchanged:\n";
$round(null);
$cards = $ids(SWUBotLegalActions($gameName, 1));
$check(in_array($PASS, $cards, true), 'Pass is offered');
$check(in_array($INIT, $cards, true), 'Initiative is offered');
$check(!in_array($PLAN, $cards, true) && !$hasBlast($cards), 'no Plan, no Blast');
$check((SWUComputeActionsData(2)['initiativeToll'] ?? null) === 0, 'no toll outside Dualing Suns (the key is always present)');

bot_test_finish();
