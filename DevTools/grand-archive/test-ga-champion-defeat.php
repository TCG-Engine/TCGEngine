<?php
// Both lethal-damage paths must publish the winner consumed by the match UI.
chdir(dirname(__DIR__, 2));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$checks = 0;
foreach (['OnDealDamage', 'DealUnpreventableDamage'] as $damage) {
    foreach ([1, 2] as $attacker) {
        InitializeGamestate();
        $playerID = $attacker;
        $defender = 3 - $attacker;
        // Champion entry abilities can draw; keep both decks nonempty.
        foreach ([1, 2] as $seat) for ($i = 0; $i < 10; ++$i) AddDeck($seat, 'LMyKyVC2O9');
        AddField($attacker, 'LMyKyVC2O9', Owner: $attacker, Controller: $attacker);
        $champion = AddField($defender, 'LMyKyVC2O9', Owner: $defender, Controller: $defender);
        $life = ObjectCurrentHP($champion);
        $damage($attacker, 'myField-0', 'theirField-0', $life - 1);
        if (intval(DecisionQueueController::GetVariable('GAMEOVER_WINNER')) !== 0) throw new RuntimeException('Nonlethal damage ended the game: ' . $damage . ' seat ' . $attacker . ' hp ' . $life . ' dmg ' . $champion->Damage);
        ++$checks;
        $damage($attacker, 'myField-0', 'theirField-0', 1);
        if (intval(DecisionQueueController::GetVariable('GAMEOVER_WINNER')) !== $attacker) throw new RuntimeException('Lethal damage did not declare the winner');
        ++$checks;
        DecisionQueueController::CleanupRemovedCards();
        if (intval(DecisionQueueController::GetVariable('GAMEOVER_WINNER')) !== $attacker) throw new RuntimeException('Cleanup lost the winner');
        ++$checks;
    }
}
echo "OK $checks champion defeat checks\n";
