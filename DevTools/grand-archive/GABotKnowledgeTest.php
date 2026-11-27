<?php
require_once __DIR__ . '/../../Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$checks = 0; $failures = 0;
function knowledgeCheck($condition, $label) {
    global $checks, $failures;
    ++$checks; if (!$condition) ++$failures;
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $label . "\n";
}
function closeOdds($a, $b) { return abs($a - $b) < 0.00000001; }
#[\AllowDynamicProperties]
class HiddenKnowledgeCard {
    public $removed = false;
    function __get($name) { throw new RuntimeException('Hidden information read: ' . $name); }
}
knowledgeCheck(closeOdds(GABotHypergeometricHit(60, 4, 7), 1 - (56/60)*(55/59)*(54/58)*(53/57)*(52/56)*(51/55)*(50/54)), 'opening-hand probability uses sampling without replacement');
knowledgeCheck(GABotHypergeometricHit(10, 0, 3) === 0.0, 'no remaining copies gives zero chance');
knowledgeCheck(GABotHypergeometricHit(4, 1, 4) === 1.0, 'drawing the population guarantees a remaining copy');
foreach ([1, 2] as $seat) {
    InitializeGamestate(); $playerID = $seat;
    AddField($seat, 'LMyKyVC2O9', Owner:$seat, Controller:$seat);
    $target = '1n3gygojwk'; $other = '5n874ubgai';
    GABotRememberDecklist($seat, array_merge(array_fill(0, 4, $target), array_fill(0, 6, $other)));
    $deck = &GetDeck($seat); $deck = array_fill(0, 7, new HiddenKnowledgeCard());
    AddHand($seat, $target); AddHand($seat, $other); AddHand($seat, $other);
    $odds = GABotDrawOdds($seat, [$target]);
    knowledgeCheck($odds['exact'] && closeOdds($odds['lower'], 3/7), "P$seat subtracts its opening hand without reading deck identities");
    knowledgeCheck(closeOdds(GABotDrawOdds($seat, [$target], 2)['lower'], 1 - (4/7)*(3/6)), "P$seat updates multi-draw odds after opening hand");
    // Opponent private zones are inaccessible sentinels, not dummy public identities.
    $hand = &GetHand(3-$seat); $hand = [new HiddenKnowledgeCard()];
    $memory = &GetMemory(3-$seat); $memory = [new HiddenKnowledgeCard()];
    $opponentDeck = &GetDeck(3-$seat); $opponentDeck = [new HiddenKnowledgeCard()];
    knowledgeCheck(GABotDrawOdds($seat, [$target]) === $odds, "P$seat ignores opponent private information");
    DecisionQueueController::AddDecision($seat, 'NAMECARD', '', 1);
    $nameLegal = GABotLegalActions('knowledge-test', $seat);
    knowledgeCheck($nameLegal['actions'][0]['cardID'] === CardName($other), "P$seat names the most likely known card without reading hidden deck");
    $queue = &GetDecisionQueue($seat); $queue = [];
    array_pop($deck); AddGraveyard($seat, $target);
    knowledgeCheck(closeOdds(GABotDrawOdds($seat, [$target])['lower'], 2/6), "P$seat accounts for publicly milled copy");
    $grave = &GetGraveyard($seat); $grave = []; $deck[] = new HiddenKnowledgeCard();
    knowledgeCheck(GABotDrawOdds($seat, [$target]) === $odds, "P$seat accounts for a known card returned to deck");
    array_pop($deck); // A card disappears without its identity being observable.
    $uncertain = GABotDrawOdds($seat, [$target]);
    knowledgeCheck(!$uncertain['exact'] && closeOdds($uncertain['lower'], 2/6) && closeOdds($uncertain['upper'], 3/6), "P$seat bounds opaque removals instead of peeking");
    $banish = &GetBanish($seat);
    $opaque = new HiddenKnowledgeCard(); $opaque->Location = 'Banish'; $opaque->Counters = ['_sealFaceDown' => true];
    $banish[] = $opaque;
    knowledgeCheck(GABotDrawOdds($seat, [$target]) === $uncertain, "P$seat does not inspect face-down banished identities");
    $banish = [];
    $deck = [new Deck($target), new Deck($other), new Deck($other), new Deck($target), new Deck($other), new Deck($target)];
    $before = GABotDrawOdds($seat, [$target]);
    $deck = array_reverse($deck);
    knowledgeCheck(GABotDrawOdds($seat, [$target]) === $before, "P$seat probabilities are invariant under deck permutation");
    $saved = $playerID; $playerID = 3-$seat;
    knowledgeCheck(GABotDrawOdds($seat, [$target]) === $before && $playerID === 3-$seat, "P$seat knowledge is independent of ambient viewer");
    $playerID = $saved;
    // Use an actual authorized glimpse, preserving duplicate card identities.
    $deck = [new Deck($target), new Deck($other), new Deck($other)];
    Glimpse($seat, 3, false);
    $legal = GABotLegalActions('knowledge-test', $seat);
    $action = GABotChooseAction($legal['actions'], $legal);
    knowledgeCheck(GameValidateDecisionAnswer($seat, $action['cardID']), "P$seat glimpse ordering is a legal permutation");
    knowledgeCheck(str_starts_with($action['cardID'], 'Top=' . $other . ',' . $other), "P$seat uses only shown glimpse cards to prioritize allies");
    $dq = new DecisionQueueController(); $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    knowledgeCheck(GetDeck($seat)[0]->CardID === $other && count(GetDeck($seat)) === 3, "P$seat chosen glimpse order resolves through the engine");
}
InitializeGamestate(); $playerID = 1;
AddField(1, 'LMyKyVC2O9', Owner:1, Controller:1);
AddField(2, '5n874ubgai', Owner:2, Controller:2);
AddHand(1, '5n874ubgai');
$champ = GetField(1)[0]; $champ->Damage = ObjectCurrentHP($champ) - 1;
DecisionQueueController::StoreVariable('CombatAttackerPlayer', '2');
StoreCombatAttackerState('myField-0', 2); StoreCombatTargetState('theirField-0', 2);
$deck = &GetDeck(1); $deck = array_fill(0, 4, new HiddenKnowledgeCard());
GABotRememberDecklist(1, ['1n3gygojwk', '1n3gygojwk', '5n874ubgai', '5n874ubgai', '5n874ubgai']);
$withOut = GABotDrawPriority(1);
GABotRememberDecklist(1, array_fill(0, 5, '5n874ubgai'));
knowledgeCheck($withOut > GABotDrawPriority(1), 'drawing a possible lifesaving answer raises draw priority without seeing the top card');
InitializeGamestate();
$deck = &GetDeck(1); $deck = [new HiddenKnowledgeCard()];
knowledgeCheck(GABotDrawOdds(1, ['1n3gygojwk']) === ['lower'=>0.0, 'upper'=>1.0, 'exact'=>false], 'older saves without a known decklist do not inspect hidden cards');
echo "Checks: $checks; failures: $failures\n";
exit($failures ? 1 : 0);
