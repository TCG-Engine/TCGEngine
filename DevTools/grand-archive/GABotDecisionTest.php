<?php
// Focused tests against the actual GA runtime and card data; no generated files are changed.
// Run: php -d xdebug.mode=off DevTools/grand-archive/GABotDecisionTest.php
require_once __DIR__ . '/../../Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$failures = 0; $checks = 0;
function check($condition, $message) {
    global $failures, $checks;
    ++$checks;
    if (!$condition) ++$failures;
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $message . "\n";
}
function resetBoard($seat = 2) {
    global $playerID;
    InitializeGamestate();
    $playerID = $seat;
    SetTurnPlayer($seat);
    SetCurrentPhase('MAT');
    AddField($seat, 'LMyKyVC2O9', Owner:$seat, Controller:$seat);
}
function offer($type, $param, $handler = '', $tooltip = '', $seat = 2) {
    $queue = &GetDecisionQueue($seat); $queue = [];
    DecisionQueueController::AddDecision($seat, $type, $param, 1, $tooltip);
    if ($handler !== '') DecisionQueueController::AddDecision($seat, 'CUSTOM', $handler, 1);
    $legal = GABotLegalActions('decision-test', $seat);
    $action = GABotChooseAction($legal['actions'], $legal);
    if (!GameValidateDecisionAnswer($seat, $action['cardID'])) throw new RuntimeException('Bot returned an invalid decision: ' . json_encode($action));
    return [$legal, $action];
}
resetBoard();
AddMemory(2, 'rWhFC8XBaH');
AddMaterial(2, 'e3z4pyx8bd');
AddMaterial(2, 'frzrplywc0');
[$legal, $action] = offer('MZMAYCHOOSE', 'myMaterial', 'MATERIALIZE');
check(count($legal['actions']) === 3, 'enumerate every bare-zone candidate plus pass');
check($action['cardID'] === 'myMaterial-0', 'prefer affordable Diana level-up to equipment');
// Execute the actual continuation to prove the choice progresses the champion.
$dq = new DecisionQueueController();
$dq->PopDecision(2);
$dq->ExecuteStaticMethods(2, $action['cardID']);
check(CardName(GABotOwnChampion(2)->CardID) === 'Diana, Keen Huntress', 'selected level-up resolves through engine');
resetBoard();
$field = &GetField(2); $field = [];
AddField(2, 'e3z4pyx8bd', Owner:2, Controller:2);
AddMaterial(2, '7ozuj68m69');
[$legal, $action] = offer('MZMAYCHOOSE', 'myMaterial-0', 'MATERIALIZE');
check($action['cardID'] === 'PASS', 'decline unaffordable level-up');
for ($i = 0; $i < 3; ++$i) AddMemory(2, 'rWhFC8XBaH');
[$legal, $action] = offer('MZMAYCHOOSE', 'myMaterial-0', 'MATERIALIZE');
check($action['cardID'] === 'myMaterial-0', 'take level-up once memory is available');
$playerID = 1;
$action = GABotChooseAction($legal['actions'], $legal);
check($action['cardID'] === 'myMaterial-0' && $playerID === 1, 'score bot seat correctly and restore viewer perspective');
$playerID = 2;
$memory = &GetMemory(2); $memory = [];
[$legal, $action] = offer('MZMAYCHOOSE', 'myMaterial-0', 'MATERIALIZE|NOCOST');
check($action['cardID'] === 'myMaterial-0', 'free materialization does not require memory');
GetMaterial(2)[0]->removed = true;
[$legal, $action] = offer('MZMAYCHOOSE', 'myMaterial-0', 'MATERIALIZE');
check($action['cardID'] === 'PASS', 'removed candidates cannot be selected');
resetBoard();
for ($i = 0; $i < 3; ++$i) AddGraveyard(2, 'rWhFC8XBaH');
[$legal, $action] = offer('MZMULTICHOOSE', '0|2|myGraveyard', 'PAYFLOATING|2');
check($action['cardID'] === 'myGraveyard-0&myGraveyard-1', 'use up to owed floating-memory payments');
[$legal, $action] = offer('MZMULTICHOOSE', '0|3|myGraveyard', 'PAYFLOATING|1');
check($action['cardID'] === 'myGraveyard-0', 'never spend more floating memory than owed');
[$legal, $action] = offer('MZMULTICHOOSE', '0|3|myGraveyard', 'UnknownSacrifice');
check($action['cardID'] === '-', 'unknown optional multichoice remains conservative');
[$legal, $action] = offer('MZMULTICHOOSE', '2|3|myGraveyard', 'UnknownRequired');
check(count(explode('&', $action['cardID'])) === 2, 'required multichoice still satisfies minimum');
resetBoard();
foreach (['9owQoFU1Tz', 'x5FICqmSNN', 'AACH0EPqqL'] as $id) AddGraveyard(2, $id);
[$legal, $action] = offer('MZMULTICHOOSE', '2|3|myGraveyard', 'OKkRsNIzM8:0:AlternativeCost-await|frame|3|chosen');
check($action['cardID'] === 'myGraveyard-0&myGraveyard-2', 'Trefle skips the first two domains costing only eight');
require_once __DIR__ . '/rl/ga_policy.php';
$expanded = GaRlExpandActions($legal);
check(count($expanded['actions']) === 1 && $expanded['actions'][0]['cardID'] === $action['cardID'], 'RL catalog preserves exact-cost domain constraint');
check(GADTRPayTrefleDomains(2, $action['cardID']), 'Trefle payment passes the actual engine check');
check(count(GetBanish(2)) === 2, 'Trefle banishes exactly the payable pair');
check(GABotTreflePayment(['myGraveyard-0'], 2, 3) === null, 'Trefle does not invent a payment when no subset exists');
resetBoard();
for ($i = 0; $i < 5; ++$i) AddDeck(2, 'rWhFC8XBaH');
[$legal, $action] = offer('YESNO', '-', 'f15joh300z:0:CardActivated-1', 'Draw_into_memory_and_curse_lineage?');
check($action['cardID'] === 'YES' && count($legal['actions']) === 2, 'take Umbra Sight extra draw with safe life margin');
GABotOwnChampion(2)->Damage = ObjectCurrentHP(GABotOwnChampion(2)) - 2;
[$legal, $action] = offer('YESNO', '-', 'f15joh300z:0:CardActivated-1', 'Draw_into_memory_and_curse_lineage?');
check($action['cardID'] === 'NO', 'decline lethal Umbra Sight self-damage');
[$legal, $action] = offer('YESNO', '-', 'DanteHemomancerRecover', 'Recover_2?');
check($action['cardID'] === 'YES', 'take free recovery while damaged');
GABotOwnChampion(2)->Damage = 0;
[$legal, $action] = offer('YESNO', '-', 'DanteHemomancerRecover', 'Recover_2?');
check($action['cardID'] === 'NO', 'skip recovery at full life');
$deck = &GetDeck(2); $deck = [];
[$legal, $action] = offer('YESNO', '-', 'f15joh300z:0:CardActivated-1', 'Draw_into_memory_and_curse_lineage?');
check($action['cardID'] === 'NO', 'decline extra draw from an empty deck');
AddHand(2, 'rWhFC8XBaH');
[$legal, $action] = offer('MZMAYCHOOSE', 'myHand-0', 'UnknownSacrifice', 'Sacrifice_something?');
check($action['cardID'] === 'PASS' && !empty($GLOBALS['GABotUnsupportedOptionalDecisions']), 'unsupported optional effects pass and emit coverage diagnostics');
[$legal, $action] = offer('MZCHOOSE', 'myHand-0');
check($action['cardID'] === 'myHand-0', 'mandatory choice does not gain a pass option');
resetBoard();
SetCurrentPhase('MAIN');
SetTurnPlayer(1);
AddField(1, '5n874ubgai', Owner:1, Controller:1);
AddHand(2, '1n3gygojwk');
for ($i = 0; $i < 3; ++$i) AddHand(2, 'rWhFC8XBaH');
StoreCombatAttackerState('myField-0', 1);
StoreCombatTargetState('theirField-0', 1);
GABotOwnChampion(2)->Damage = ObjectCurrentHP(GABotOwnChampion(2)) - 2;
[$legal, $action] = offer('MZMAYCHOOSE', 'myHand-0', 'OpportunityWindowSecondResponse', 'Take_a_fast_action?');
check($action['cardID'] === 'myHand-0', 'use Evasive Maneuvers to survive lethal incoming damage');
[$legal, $action] = offer('MZCHOOSE', 'theirField-0&myField-0', '1n3gygojwk:0:CardActivated-1');
check($action['cardID'] === 'myField-0', 'defensive response targets threatened friendly unit, not first offered enemy');
EvasiveManeuversResolve(2, $action['cardID']);
[$legal, $action] = offer('MZMAYCHOOSE', 'myHand-0', 'OpportunityWindowSecondResponse', 'Take_a_fast_action?');
check($action['cardID'] === 'PASS', 'do not waste another prevention card once existing prevention suffices');
GABotOwnChampion(2)->TurnEffects = [];
$hand = &GetHand(2); $hand = [$hand[0]];
[$legal, $action] = offer('MZMAYCHOOSE', 'myHand-0', 'OpportunityWindowSecondResponse', 'Take_a_fast_action?');
check($action['cardID'] === 'PASS', 'decline unaffordable combat response');
resetBoard();
AddField(2, 'frzrplywc0', Owner:2, Controller:2);
AddField(2, 'l75tlzsmw3', Owner:2, Controller:2);
$load = EncodeOpportunityAbilityChoice('myField-2', 0, 'Load');
[$legal, $action] = offer('MZMAYCHOOSE', $load, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?');
check($action['cardID'] === $load, 'preserve encoded optional abilities and choose starter bullet loading');
LoadBulletIntoGun(2, 'myField-2', 'myField-1');
check(IsGunLoaded(GetZoneObject('myField-1')), 'chosen bullet loading resolves to a loaded gun');
GetZoneObject('myField-1')->Counters['durability'] = 3;
[$legal, $action] = offer('MZMAYCHOOSE', 'myField-1', 'WeaponSelected', 'Choose_weapon?');
check($action['cardID'] === 'myField-1', 'choose loaded weapon rather than automatically attack unarmed');
[$legal, $action] = offer('MZMAYCHOOSE', $load, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?');
check($action['cardID'] === 'PASS', 'removed loaded bullet cannot be selected again');
resetBoard();
SetCurrentPhase('MAIN');
AddHand(2, 'y5koddlyv8'); // Undying Dreams requires an ally.
for ($i = 0; $i < 5; ++$i) AddHand(2, 'rWhFC8XBaH');
SetFlashMessage('existing message');
$legal = GABotLegalActions('decision-test', 2);
check(!in_array('myHand-0!FSM!', array_column($legal['actions'], 'cardID')), 'do not offer Undying Dreams without an ally target');
check(GetFlashMessage() === 'existing message', 'target availability check does not display a rejection');
AddField(1, CardNameToID('Dungeon Guide'), Owner:1, Controller:1);
$legal = GABotLegalActions('decision-test', 2);
check(in_array('myHand-0!FSM!', array_column($legal['actions'], 'cardID')), 'offer Undying Dreams when a legal ally exists');
AddTurnEffect('theirField-0', 'SPELLSHROUD');
$legal = GABotLegalActions('decision-test', 2);
check(!in_array('myHand-0!FSM!', array_column($legal['actions'], 'cardID')), 'protected ally does not make Undying Dreams legal');
// Genbu's quest engine must work from either seat, without inspecting the opponent's hand.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    $field = &GetField($seat); $field = [];
    AddField($seat, '59ipqa91r2', Owner:$seat, Controller:$seat);
    AddField($seat, 'vzmnt0orxj', Owner:$seat, Controller:$seat);
    GetField($seat)[1]->Status = 2;
    for ($i = 0; $i < 15; ++$i) AddDeck($seat, 'rWhFC8XBaH');
    AddQuestCounters($seat, 7);
    [$legal, $action] = offer('YESNO', '-', '59ipqa91r2:0:Enter-1', '', $seat);
    check($action['cardID'] === 'YES', "P$seat builds quest counters instead of defaulting to draw");
    DecisionQueueController::StoreVariable('mzID', 'myField-0');
    $dq = new DecisionQueueController(); $dq->PopDecision($seat);
    $dq->ExecuteStaticMethods($seat, $action['cardID']);
    check(GetQuestCounterCount($seat) === 9, "P$seat quest choice resolves to nine counters");
    $transform = EncodeOpportunityAbilityChoice('myField-1', 0, 'Transform');
    [$legal, $action] = offer('MZMAYCHOOSE', $transform, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $transform, "P$seat chooses Sapphire transformation");
    $viewer = $playerID; $playerID = 3 - $seat;
    check(GABotChooseAction($legal['actions'], $legal)['cardID'] === $transform && $playerID === 3 - $seat, 'Genbu policy restores viewer perspective');
    $playerID = $viewer;
    GetField($seat)[1]->Status = 1;
    check(GABotOptionalScore($seat, $transform, $legal) < 0, 'rested Sapphire cannot transform');
    GetField($seat)[1]->Status = 2;
    RemoveQuestCounters($seat, 1);
    check(GABotOptionalScore($seat, $transform, $legal) < 0, 'eight counters are insufficient');
    AddQuestCounters($seat, 1);
    // Resolve the same generated effect the selected ability invokes.
    DecisionQueueController::StoreVariable('mzID', 'myField-1');
    DecisionQueueController::StoreVariable('abilityIndex', 0);
    $activateAbilityAbilities['vzmnt0orxj:0']($seat);
    check(GetField($seat)[1]->CardID === 'k8bwlx70qj' && GetQuestCounterCount($seat) === 0, 'transformation creates Genbu and spends nine counters');
    [$legal, $action] = offer('YESNO', '-', '59ipqa91r2:0:Enter-1', '', $seat);
    check($action['cardID'] === 'NO', 'after transformation prefer draw over unused quest counters');
}
resetBoard();
$field = &GetField(2); $field = [];
AddField(2, 'enxi6tshtu', Owner:2, Controller:2);
AddField(2, 'vzmnt0orxj', Owner:2, Controller:2);
GABotOwnChampion(2)->Damage = ObjectCurrentHP(GABotOwnChampion(2)) - 3;
[$legal, $action] = offer('YESNO', '-', 'enxi6tshtu:0:Enter-1');
check($action['cardID'] === 'NO', 'prefer recovery to quest counters at critical life');
GABotOwnChampion(2)->Damage = 0;
[$legal, $action] = offer('YESNO', '-', 'enxi6tshtu:0:Enter-1');
check($action['cardID'] === 'YES', 'healthy champion accelerates Genbu');
for ($i = 0; $i < 9; ++$i) AddDeck(2, 'rWhFC8XBaH');
[$legal, $action] = offer('YESNO', '-', 'thxvlynvy5:0:OnAttack-1');
check($action['cardID'] === 'YES', 'Bovine builds its own graveyard toward deluge');
for ($i = 0; $i < 12; ++$i) AddGraveyard(2, '3zb9p4lgdl');
[$legal, $action] = offer('YESNO', '-', 'thxvlynvy5:0:OnAttack-1');
check($action['cardID'] === 'NO', 'stop feeding our graveyard once deluge twelve is reached');
$graveyard = &GetGraveyard(2); $graveyard = [];

GetDeck(2)[0]->removed = true;
[$legal, $action] = offer('YESNO', '-', 'thxvlynvy5:0:OnAttack-1');
check($action['cardID'] === 'NO', 'stop self-milling near deck exhaustion');
resetBoard();
$field = &GetField(2); $field = [];
AddField(2, '59ipqa91r2', Owner:2, Controller:2);
AddField(2, 'rpOaAjgtue', Owner:2, Controller:2);
AddGraveyard(2, 'thxvlynvy5');
for ($i = 0; $i < 3; ++$i) AddDeck(2, 'rWhFC8XBaH');
[$legal, $action] = offer('MZMAYCHOOSE', 'myGraveyard-0', 'rpOaAjgtue:0:Enter-1');
check($action['cardID'] === 'myGraveyard-0', 'Paladin spends floating memory for draw and buff');
DecisionQueueController::StoreVariable('mzID', 'myField-1');
$dq = new DecisionQueueController(); $dq->PopDecision(2); $dq->ExecuteStaticMethods(2, $action['cardID']);
check(GABotLiveCount(GetHand(2)) === 1 && GABotLiveCount(GetBanish(2)) === 1
    && intval(GetField(2)[1]->Counters['buff'] ?? 0) === 1, 'Paladin choice resolves draw, banish, and buff');
AddGraveyard(2, 'thxvlynvy5');
$deck = &GetDeck(2); $deck = []; AddDeck(2, 'rWhFC8XBaH');
[$legal, $action] = offer('MZMAYCHOOSE', 'myGraveyard', 'rpOaAjgtue:0:Enter-1');
check($action['cardID'] === 'PASS', 'Paladin avoids drawing away the last deck card');
// Exercise combat targeting through the real legal decision path, from both seats.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    $opponent = 3 - $seat;
    AddField($seat, 'thxvlynvy5', Owner:$seat, Controller:$seat);
    AddField($opponent, 'LMyKyVC2O9', Owner:$opponent, Controller:$opponent);
    AddField($opponent, 'thxvlynvy5', Owner:$opponent, Controller:$opponent);
    $attacker = GetField($seat)[1];
    $victim = GetField($opponent)[1];
    $champion = GetField($opponent)[0];
    $victim->Damage = ObjectCurrentHP($victim) - 1;
    $victim->Status = 1;
    StoreCombatAttackerState('myField-1', $seat);
    [$legal, $action] = offer('MZCHOOSE', 'theirField-0&theirField-1', '', 'Choose_attack_target', $seat);
    check($action['cardID'] === 'theirField-1', "seat $seat removes a vulnerable ally over incidental face damage");
    $attacker->Damage = ObjectCurrentHP($attacker) - 1;
    $victim->Status = 2;
    [$legal, $action] = offer('MZCHOOSE', 'theirField-0&theirField-1', '', 'Choose_attack_target', $seat);
    check($action['cardID'] === 'theirField-0', "seat $seat avoids losing its attacker in an unnecessary trade");
    $victim->Status = 1;
    [$legal, $action] = offer('MZCHOOSE', 'theirField-0&theirField-1', '', 'Choose_attack_target', $seat);
    check($action['cardID'] === 'theirField-1', "seat $seat recognizes a rested defender cannot normally retaliate");
    $champion->Damage = ObjectCurrentHP($champion) - 1;
    [$legal, $action] = offer('MZCHOOSE', 'theirField-0&theirField-1', '', 'Choose_attack_target', $seat);
    check($action['cardID'] === 'theirField-0', "seat $seat prioritizes lethal champion damage");
    $champion->Damage = 0;
    $victim->Damage = 0;
    AddCounters($opponent, 'theirField-1', 'buff', 10);
    [$legal, $action] = offer('MZCHOOSE', 'theirField-0&theirField-1', '', 'Choose_attack_target', $seat);
    check($action['cardID'] === 'theirField-0', "seat $seat avoids ineffective chip damage into a large ally");
    $playerID = $opponent;
    GABotChooseAction($legal['actions'], $legal);
    check($playerID === $opponent, "combat targeting restores seat $seat viewer perspective");
}
// Damage spells share public-board scoring but retain their exact damage amounts.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    $opponent = 3 - $seat;
    AddField($opponent, 'LMyKyVC2O9', Owner:$opponent, Controller:$opponent);
    AddField($opponent, 'thxvlynvy5', Owner:$opponent, Controller:$opponent);
    $victim = GetField($opponent)[1];
    $champion = GetField($opponent)[0];
    $victim->Damage = ObjectCurrentHP($victim) - 2;
    foreach (['0G32Pp5atK', '0op3nq0ymv', '0xm513tj3j', 'iohZMWh5v5'] as $card) {
        $handler = 'GADeclareTarget|' . base64_encode(json_encode(['cardID'=>$card])) . '|' . base64_encode('primary');
        $targets = $card === '0xm513tj3j' ? 'theirField-1' : 'myField-0&theirField-0&theirField-1';
        [$legal, $action] = offer('MZCHOOSE', $targets, $handler, 'Declare_target', $seat);
        check($action['cardID'] === 'theirField-1', "P$seat $card removes vulnerable opposing ally");
        $playerID = $opponent;
        GABotChooseAction($legal['actions'], $legal);
        check($playerID === $opponent, 'spell targeting restores perspective');
        $playerID = $seat;
        if ($card !== '0xm513tj3j') {
            $champion->Damage = ObjectCurrentHP($champion) - 2;
            [$legal, $action] = offer('MZCHOOSE', $targets, $handler, 'Declare_target', $seat);
            check($action['cardID'] === 'theirField-0', 'damage spell prioritizes champion lethal');
            $champion->Damage = 0;
        }
    }
    [$legal, $action] = offer('MZCHOOSE', 'myField-0&theirField-1', '0op3nq0ymv:0:CardActivated-1', '', $seat);
    check($action['cardID'] === 'theirField-1', 'legacy damage continuation avoids friendly fire');
    AddCounters($seat, 'theirField-1', 'buff', 5);
    $victim->Damage = ObjectCurrentHP($victim) - 5;
    [$legal, $action] = offer('MZCHOOSE', 'theirField-0&theirField-1', '0op3nq0ymv:0:CardActivated-1', '', $seat);
    check($action['cardID'] === 'theirField-1', 'Voltaic Sphere accounts for five damage against allies');
    [$legal, $action] = offer('MZCHOOSE', 'theirField-0&theirField-1', '0G32Pp5atK:0:CardActivated-1', '', $seat);
    check($action['cardID'] === 'theirField-0', 'Mote Sear does not assume optional extra damage');
    foreach (['iohZMWh5v5:0:CardActivated-1', 'hd0sxpu7cp:0:CardActivated-1', 'm6h38lrj52:0:Enter-1'] as $damageHandler) {
        [$legal, $action] = offer('MZCHOOSE', 'myField-0&theirField-0', $damageHandler, '', $seat);
        check($action['cardID'] === 'theirField-0', "P$seat $damageHandler avoids damaging own champion");
    }
    $pyreHandler = 'hd0sxpu7cp:0:CardActivated-1';
    $before = GABotDamageTargetScore($seat, 'theirField-0', $pyreHandler);
    for ($i = 0; $i < 7; ++$i) AddGraveyard($opponent, 'iohZMWh5v5');
    check(GABotDamageTargetScore($seat, 'theirField-0', $pyreHandler) === $before, 'Pyre stays at two below eight graveyard cards');
    AddGraveyard($opponent, 'iohZMWh5v5');
    check(GABotDamageTargetScore($seat, 'theirField-0', $pyreHandler) > $before, 'Pyre counts the target controller graveyard for six damage');
    $handler = 'GADeclareTarget|' . base64_encode(json_encode(['cardID'=>'unknown'])) . '|' . base64_encode('primary');
    check(GABotDamageTargetScore($seat, 'theirField-1', $handler) === null, 'unknown spells keep existing fallback');
}
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    SetCurrentPhase('MAIN');
    SetTurnPlayer(3 - $seat);
    AddField($seat, 'Tx8noEw78s', Owner:$seat, Controller:$seat);
    $source = GetField($seat)[1];
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Recover');
    $champion = GABotOwnChampion($seat);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', 'hold Synchron at full health');
    $champion->Damage = 1;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', 'hold Synchron rather than waste recovery');
    $champion->Damage = 4;
    AddCounters($seat, 'myField-1', 'refinement', 2);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, 'use refined Synchron for full healing');
    $playerID = 3 - $seat;
    GABotChooseAction($legal['actions'], $legal);
    check($playerID === 3 - $seat, 'recovery policy restores viewer perspective');
    $playerID = $seat;
    AddGlobalEffects($seat, 'CANT_RECOVER');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', 'do not sacrifice Synchron while recovery is blocked');
    $effects = &GetGlobalEffects($seat); $effects = [];
    AddField(3 - $seat, 'koyegh1snb', Owner:3-$seat, Controller:3-$seat);
    check(GABotSynchronScore($seat, $source) < 0, 'Judicator prevents wasteful recovery');
    GetField(3 - $seat)[0]->removed = true;
    AddField(3 - $seat, 'vgWgu1DUYv', Owner:3-$seat, Controller:3-$seat);
    check(GABotRecoveryAmount($seat, 4) === 1 && GABotRecoveryAmount($seat, 2) === 0, 'Vessel reduces healing before scoring');
    GetField(3 - $seat)[1]->removed = true;
    $enemyField = &GetField(3 - $seat); $enemyField = [];
    AddField(3 - $seat, '5n874ubgai', Owner:3-$seat, Controller:3-$seat);
    DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval(3 - $seat));
    StoreCombatAttackerState('myField-0', 3 - $seat);
    StoreCombatTargetState('theirField-0', 3 - $seat);
    $champion->Damage = ObjectCurrentHP($champion) - 2;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, 'heal in response to a survivable lethal attack');
    AddCounters($seat, 'theirField-0', 'buff', 20);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', 'hold healing when it cannot stop lethal by itself');
    $champion->Damage = 4;
    // Compare the estimate to actual engine recovery, including saved refinement.
    DecisionQueueController::StoreVariable('gaLKI_refinement', 2);
    $activateAbilityAbilities['Tx8noEw78s:0']($seat);
    check($champion->Damage === 0, 'Synchron effect heals the estimated amount');
}
// Free-play sequencing uses real declaration targets from both seat perspectives.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddField(3 - $seat, 'LMyKyVC2O9', Owner:3-$seat, Controller:3-$seat);
    AddHand($seat, '1n3gygojwk');
    $pass = ['mode'=>10001, 'cardID'=>'myHealth-0!CustomInput!Pass', 'playerID'=>$seat];
    $defense = ['mode'=>10002, 'cardID'=>'myHand-0!FSM!', 'playerID'=>$seat];
    $context = ['kind'=>'free-play', 'playerID'=>$seat];
    check(GABotChooseAction([$defense, $pass], $context) === $pass, "P$seat saves prevention for a response");
    AddHand($seat, '0op3nq0ymv');
    $spell = ['mode'=>10002, 'cardID'=>'myHand-1!FSM!', 'playerID'=>$seat];
    $opponent = GetField(3 - $seat)[0];
    $opponent->Damage = ObjectCurrentHP($opponent) - 3;
    $playerID = 3 - $seat;
    check(GABotScoreHandPlay($seat, $spell) >= 10000, "P$seat recognizes lethal spell from engine targets");
    check($playerID === 3 - $seat, "P$seat hand scoring restores perspective");
    check(GABotChooseAction([$defense, $pass, $spell], $context) === $spell, "P$seat chooses lethal spell over prevention");
    $playerID = $seat;
    $opponent->removed = true;
    check(GABotScoreHandPlay($seat, $spell) < 0, "P$seat holds damage when only friendly targets remain");
    $enemyField = &GetField(3 - $seat); $enemyField = [];
    AddField(3 - $seat, '5n874ubgai', Owner:3-$seat, Controller:3-$seat);
    $enemy = GetField(3 - $seat)[0];
    $enemy->Damage = max(0, ObjectCurrentHP($enemy) - 1);
    check(GABotScoreHandPlay($seat, $spell) >= 65, "P$seat values removal of a vulnerable ally");
    AddField($seat, '5n874ubgai', Owner:$seat, Controller:$seat);
    $attack = ['mode'=>10002, 'cardID'=>'myField-1!FSM!', 'playerID'=>$seat];
    check(GABotChooseAction([$attack, $spell, $pass], $context) === $spell, "P$seat removes a threat before routine attacking");
    AddField(3 - $seat, 'LMyKyVC2O9', Owner:3-$seat, Controller:3-$seat);
    $enemyChampion = GetField(3 - $seat)[1];
    $enemyChampion->Damage = ObjectCurrentHP($enemyChampion) - 1;
    check(GABotChooseAction([$attack, $spell, $pass], $context) === $spell, "P$seat prioritizes a lethal spell over an attack");

}
// Target previews share engine retaliation eligibility without changing combat.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    $opponent = 3 - $seat;
    AddField($seat, 'thxvlynvy5', Owner:$seat, Controller:$seat);
    AddField($opponent, 'thxvlynvy5', Owner:$opponent, Controller:$opponent);
    AddField($opponent, '5n874ubgai', Owner:$opponent, Controller:$opponent);
    $target = GetField($opponent)[0];
    $ambusher = GetField($opponent)[1];
    $target->Status = 1;
    $ambusher->Status = 2;
    $ambusher->TurnEffects[] = 'AMBUSH';
    StoreCombatAttackerState('myField-1', $seat);
    StoreCombatTargetState('theirField-1', $seat);
    check(GABotRetaliationPower($seat, 'theirField-0') === intval(ObjectCurrentPower($ambusher)), "P$seat accounts for off-target Ambush retaliation");
    check(DecisionQueueController::GetVariable('CombatTarget') === 'theirField-1', "P$seat target preview preserves committed target");
    $ambusher->Status = 1;
    check(GABotRetaliationPower($seat, 'theirField-0') === 0, "P$seat rested Ambush cannot retaliate");
    $target->Status = 2;
    check(GABotRetaliationPower($seat, 'theirField-0') === intval(ObjectCurrentPower($target)), "P$seat ready target can retaliate");
    GetField($seat)[1]->CardID = 'qry41lw9n0';
    check(GABotRetaliationPower($seat, 'theirField-0') === 0, "P$seat Blazing Bowman cannot be retaliated");
    check(GetRetaliatorOptions($seat) === null, "P$seat existing engine caller retains retaliation restriction");
}
// Ring's optional draw should produce real card advantage without drawing into deck-out.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddField($seat, '2gv7DC0KID', Owner:$seat, Controller:$seat);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Draw');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat holds Ring with an empty deck");
    AddDeck($seat, 'rWhFC8XBaH');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat preserves the last card for a mandatory draw");
    AddDeck($seat, 'rWhFC8XBaH');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat uses Ring when drawing is safe");
    AddGlobalEffects($seat, 'ri955ygd5v_NO_DRAW');
    [$blockedLegal, $blockedAction] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($blockedAction['cardID'] === 'PASS', "P$seat preserves Ring when drawing is prohibited");
    $effects = &GetGlobalEffects($seat); $effects = [];
    // Public draw limits apply regardless of which player controls the permanent.
    AddField(3 - $seat, 'q8sdbzr5zs', Owner:3-$seat, Controller:3-$seat);
    SetTurnNumber(2);
    $turnIndex = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    $turnIndex['Draw'][$seat] = 3;
    SetMacroTurnIndex(json_encode($turnIndex));
    check(!GABotCanBenefitFromDraw($seat), "P$seat respects Tithe's draw cap");
    $turnIndex['Draw'][$seat] = 2;
    SetMacroTurnIndex(json_encode($turnIndex));
    check(GABotCanBenefitFromDraw($seat), "P$seat can draw below Tithe's cap");
    $enemyField = &GetField(3 - $seat); $enemyField = [];
    AddField(3 - $seat, '5ckzgqa186', Owner:3-$seat, Controller:3-$seat);
    AddField(3 - $seat, 'ka5av43ehj', Owner:3-$seat, Controller:3-$seat);
    for ($i = 0; $i < 8; ++$i) AddMemory($seat, 'rWhFC8XBaH');
    check(!GABotCanBenefitFromDraw($seat), "P$seat respects Mandate at eight influence");
    GetField(3 - $seat)[0]->TurnEffects[] = 'NO_ABILITIES';
    check(GABotCanBenefitFromDraw($seat), "P$seat ignores suppressed Mandate");
    $enemyField = []; $memory = &GetMemory($seat); $memory = [];
    $playerID = 3 - $seat;
    check(GABotChooseAction($legal['actions'], $legal)['cardID'] === $choice && $playerID === 3 - $seat,
        "P$seat Ring decision restores viewer perspective");
    $playerID = $seat;
    AddField($seat, 'Tx8noEw78s', Owner:$seat, Controller:$seat);
    GABotOwnChampion($seat)->Damage = 2;
    $heal = EncodeOpportunityAbilityChoice('myField-2', 0, 'Recover');
    [$legal, $action] = offer('MZMAYCHOOSE', "$choice&$heal", 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $heal, "P$seat prioritizes useful recovery over Ring draw");
    DecisionQueueController::StoreVariable('mzID', 'myField-1');
    $activateAbilityAbilities['2gv7DC0KID:0']($seat);
    check(GABotLiveCount(GetHand($seat)) === 1 && GABotLiveCount(GetDeck($seat)) === 1
        && GABotLiveCount(GetBanish($seat)) === 1 && GetBanish($seat)[0]->CardID === '2gv7DC0KID'
        && GABotLiveCount(GetField($seat)) === 2,
        "P$seat Ring actually banishes itself and draws one card");
}
// Genbu spends reserve only to survive an otherwise lethal incoming attack.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    GetField($seat)[0]->CardID = '59ipqa91r2';
    AddField($seat, 'k8bwlx70qj', Owner:$seat, Controller:$seat);
    $genbu = GetField($seat)[1];
    AddField(3 - $seat, '5n874ubgai', Owner:3-$seat, Controller:3-$seat);
    for ($i = 0; $i < 3; ++$i) AddHand($seat, 'rWhFC8XBaH');
    for ($i = 0; $i < 4; ++$i) AddGraveyard($seat, 'thxvlynvy5');
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Buff');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat saves Genbu reserve outside combat");
    DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval(3 - $seat));
    StoreCombatAttackerState('myField-0', 3 - $seat);
    StoreCombatTargetState('theirField-1', 3 - $seat);
    $genbu->Damage = ObjectCurrentHP($genbu) - 1;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat buffs Genbu to survive lethal combat");
    $playerID = 3 - $seat;
    check(GABotChooseAction($legal['actions'], $legal)['cardID'] === $choice && $playerID === 3 - $seat,
        "P$seat Genbu scoring preserves viewer perspective");
    $playerID = $seat;
    $genbu->Damage = 0;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat saves reserve against a nonlethal attack");
    $genbu->Damage = ObjectCurrentHP($genbu) - 1;
    AddCounters($seat, 'theirField-0', 'buff', 20);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat holds Genbu buff when it cannot save the unit");
    RemoveCounters($seat, 'theirField-0', 'buff', 20);
    check(GABotGenbuDefenseScore($seat, $genbu) > 0, "P$seat recognizes a survivable threat again after power falls");
    StoreCombatTargetState('theirField-0', 3 - $seat);
    check(GABotGenbuDefenseScore($seat, $genbu) < 0, "P$seat does not buff Genbu to defend a different unit");
    StoreCombatTargetState('theirField-1', 3 - $seat);
    DecisionQueueController::StoreVariable('CombatDamageAmount', 2);
    check(GABotGenbuDefenseScore($seat, $genbu) < 0, "P$seat does not buff after damage");
    DecisionQueueController::StoreVariable('CombatDamageAmount', null);
    $hand = &GetHand($seat); $hand = [];
    check(!CanActivateAbility($seat, 'myField-1', 0), "P$seat respects Genbu reserve cost");
    for ($i = 0; $i < 3; ++$i) AddHand($seat, 'rWhFC8XBaH');
    $hp = ObjectCurrentHP($genbu);
    DecisionQueueController::StoreVariable('mzID', 'myField-1');
    $activateAbilityAbilities['k8bwlx70qj:0']($seat);
    check(ObjectCurrentHP($genbu) === $hp + 4, "P$seat Genbu effect adds estimated life");
    check(!CanActivateAbility($seat, 'myField-1', 0), "P$seat cannot reuse Genbu buff this turn");
}
// Coverage policies use public counts and own cards; exercise both seat perspectives.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    SetCurrentPhase('MAIN');
    AddField($seat, 'T3cx65VM3D', Owner:$seat, Controller:$seat);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Banish');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat preserves Orb against an empty hand");
    AddHand(3-$seat, 'rWhFC8XBaH'); AddHand(3-$seat, 'rWhFC8XBaH');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat uses Orb to deny two opposing hand cards");
    SetCurrentPhase('MAT');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat holds Orb before recollection");
    SetCurrentPhase('MAIN'); SetTurnPlayer(3-$seat);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat conserves Orb outside its offensive turn");
    $playerID = $seat;
    $queue = &GetDecisionQueue($seat); $queue = [];
    $activateAbilityAbilities['T3cx65VM3D:0']($seat);
    $playerID = 3-$seat;
    $orbLegal = GABotLegalActions('decision-test', 3-$seat);
    $orbAction = GABotChooseAction($orbLegal['actions'], $orbLegal);
    check(GameValidateDecisionAnswer(3-$seat, $orbAction['cardID']), "P$seat Orb victim selects a legal pair");
    $dq = new DecisionQueueController(); $dq->PopDecision(3-$seat);
    $dq->ExecuteStaticMethods(3-$seat, $orbAction['cardID']);
    check(GABotLiveCount(GetHand(3-$seat)) === 0 && GABotLiveCount(GetMemory(3-$seat)) === 2,
        "P$seat Orb continuation moves chosen hand cards into memory");

    resetBoard($seat);
    GetField($seat)[0]->CardID = 'iq4d5vettc';
    AddCounters($seat, 'myField-0', 'enlighten', 3);
    for ($j=0; $j<3; ++$j) AddDeck($seat, 'rWhFC8XBaH');
    $dynamic = json_decode(GetDynamicAbilities(GetField($seat)[0]), true);
    $choice = EncodeOpportunityAbilityChoice('myField-0', $dynamic[0]['index'], 'Enlighten');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat Diana uses her available Enlighten ability");
    AddGlobalEffects($seat, 'ri955ygd5v_NO_DRAW');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Diana preserves counters when draw is blocked");
    $effects = &GetGlobalEffects($seat); $effects = [];
    $deck = &GetDeck($seat); $savedDeck = $deck; $deck = array_slice($deck, 0, 1);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Enlighten preserves the last deck card");
    $deck = $savedDeck;
    GetField($seat)[0]->CardID = 'LMyKyVC2O9';
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat Enlighten coverage applies to other champions");
    AddField($seat, 'IC3OU6vCnF', Owner:$seat, Controller:$seat);
    check(GetDynamicAbilities(GetField($seat)[0]) === '', "P$seat Mana Limiter removes the Enlighten offer");
    GetField($seat)[1]->removed = true;
    $queue = &GetDecisionQueue($seat); $queue = [];
    DoActivatedAbility($seat, 'myField-0', $dynamic[0]['index']);
    check(GetCounterCount(GetField($seat)[0], 'enlighten') === 0 && GABotLiveCount(GetHand($seat)) === 1,
        "P$seat Enlighten spends three counters and draws through the engine");

    resetBoard($seat);
    GetField($seat)[0]->CardID = 'iq4d5vettc';
    GetField($seat)[0]->Subcards = ['e3z4pyx8bd'];
    $dynamic = json_decode(GetDynamicAbilities(GetField($seat)[0]), true);
    $choice = EncodeOpportunityAbilityChoice('myField-0', $dynamic[0]['index'], 'LR');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Diana preserves lineage without a gun to materialize");
    AddMaterial($seat, 'frzrplywc0');
    for ($j=0; $j<5; ++$j) AddMemory($seat, 'rWhFC8XBaH');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat Diana releases lineage for an affordable gun");
    AddCounters($seat, 'myField-0', 'enlighten', 3);
    $shifted = EncodeOpportunityAbilityChoice('myField-0', $dynamic[0]['index'] + 1, 'LR');
    [$legal, $action] = offer('MZMAYCHOOSE', $shifted, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $shifted, "P$seat identifies lineage release after Enlighten shifts its slot");
    RemoveCounters($seat, 'myField-0', 'enlighten', 3);

    AddField($seat, 'frzrplywc0', Owner:$seat, Controller:$seat);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Diana preserves lineage when already armed");
    GetField($seat)[1]->removed = true;
    $memory = &GetMemory($seat); $memory = [];
    GetMaterial($seat)[0]->CardID = 'gmnmp5af09';
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Diana will not release for an unaffordable gun");
    GetMaterial($seat)[0]->CardID = 'frzrplywc0';
    for ($j=0; $j<5; ++$j) AddMemory($seat, 'rWhFC8XBaH');
    $queue = &GetDecisionQueue($seat); $queue = [];
    DoActivatedAbility($seat, 'myField-0', $dynamic[0]['index']);
    $legal = GABotLegalActions('decision-test', $seat);
    $action = GABotChooseAction($legal['actions'], $legal);
    check($action['cardID'] === 'myMaterial-0' && !in_array('e3z4pyx8bd', GetField($seat)[0]->Subcards),
        "P$seat lineage release banishes Keen Huntress and selects the gun");
    $dq = new DecisionQueueController(); $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    check(count(array_filter(GetField($seat), fn($obj) => !$obj->removed && $obj->CardID === 'frzrplywc0')) === 1,
        "P$seat released gun materializes through engine");

    resetBoard($seat);
    GetField($seat)[0]->CardID = '59ipqa91r2';
    AddField($seat, 'vzmnt0orxj', Owner:$seat, Controller:$seat);
    AddField($seat, '5fnmnpavo4', Owner:$seat, Controller:$seat);
    AddGraveyard($seat, 'thxvlynvy5');
    for ($j=0; $j<20; ++$j) { AddDeck($seat, 'rWhFC8XBaH'); AddDeck(3-$seat, 'rWhFC8XBaH'); }
    check(GABotPolarDepthsTarget($seat, true) === 'YES', "P$seat Fractal builds a live Sapphire quest safely");
    $choice = EncodeOpportunityAbilityChoice('myField-2', 0, 'Sacrifice');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat activates Fractal for its graveyard plan");
    AddGlobalEffects($seat, 'yuo7dbge3b');
    check(GABotPolarDepthsTarget($seat, true) === null, "P$seat Fractal avoids redirected self mill");
    $effects = &GetGlobalEffects($seat); $effects = [];
    GetField($seat)[1]->TurnEffects[] = 'NO_ABILITIES';
    check(GABotPolarDepthsTarget($seat, true) === null, "P$seat Fractal holds without an active quest plan");
    GetField($seat)[1]->TurnEffects = [];
    $deck = &GetDeck($seat); $deck = array_slice($deck, 0, 3);
    check(GABotPolarDepthsTarget($seat, true) === null, "P$seat Fractal avoids unsafe self mill");
    $enemyDeck = &GetDeck(3-$seat); $enemyDeck = array_slice($enemyDeck, 0, 2);
    check(GABotPolarDepthsTarget($seat, true) === 'NO', "P$seat Fractal includes its own sacrifice in mill amount");
    // Resolve cost then generated prompt; opponent is now exactly within mill range.
    ActivatedAbilityCost($seat, 'myField-2', '5fnmnpavo4', 0);
    $queue = &GetDecisionQueue($seat); $queue = [];
    $activateAbilityAbilities['5fnmnpavo4:0']($seat);
    $legal = GABotLegalActions('decision-test', $seat);
    $action = GABotChooseAction($legal['actions'], $legal);
    check($action['cardID'] === 'NO', "P$seat selects opponent for finishing mill");
    $dq = new DecisionQueueController(); $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    check(GABotLiveCount(GetDeck(3-$seat)) === 0 && GABotLiveCount(GetDeck($seat)) === 3,
        "P$seat Fractal mills the opponent without touching own deck");
}
// Resource activations and noncombat protection, exercised from both seat perspectives.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddField($seat, 'DNbIpzVgde', Owner:$seat, Controller:$seat);
    GetField($seat)[1]->Status = 2;
    for ($j=0; $j<3; ++$j) AddDeck($seat, 'rWhFC8XBaH');
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Banish');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat uses ready Lost Providence for a safe draw");
    GetField($seat)[1]->Status = 1;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat holds hindered Lost Providence");
    GetField($seat)[1]->Status = 2;
    AddGlobalEffects($seat, 'ri955ygd5v_NO_DRAW');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat preserves Providence when draw is blocked");
    $effects = &GetGlobalEffects($seat); $effects = [];
    $deck = &GetDeck($seat); $deck = array_slice($deck, 0, 1);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat preserves last deck card with Providence");
    AddDeck($seat, 'rWhFC8XBaH');
    $queue = &GetDecisionQueue($seat); $queue = [];
    ActivatedAbilityCost($seat, 'myField-1', 'DNbIpzVgde', 0);
    $activateAbilityAbilities['DNbIpzVgde:0']($seat);
    check(GABotLiveCount(GetBanish($seat)) === 1 && GABotLiveCount(GetHand($seat)) === 1,
        "P$seat Providence pays banish cost and draws");

    resetBoard($seat);
    AddField($seat, '3qu7d6sopo', Owner:$seat, Controller:$seat);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Load');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat holds Incendiary Shot without a gun");
    AddField($seat, 'frzrplywc0', Owner:$seat, Controller:$seat);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat loads Incendiary Shot into an unloaded gun");
    GetField($seat)[2]->Subcards = ['l75tlzsmw3'];
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat does not load into an already loaded gun");
    GetField($seat)[2]->Subcards = [];
    GetField($seat)[1]->Status = 1;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat does not reuse a rested bullet");
    GetField($seat)[1]->Status = 2;
    $queue = &GetDecisionQueue($seat); $queue = [];
    DecisionQueueController::StoreVariable('mzID', 'myField-1');
    $activateAbilityAbilities['3qu7d6sopo:0']($seat);
    $legal = GABotLegalActions('decision-test', $seat); $action = GABotChooseAction($legal['actions'], $legal);
    check(GameValidateDecisionAnswer($seat, $action['cardID']), "P$seat chooses a legal loading target");
    $dq = new DecisionQueueController(); $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    check(in_array('3qu7d6sopo', GetField($seat)[1]->Subcards), "P$seat Shot becomes a loaded gun subcard");

    resetBoard($seat);
    AddField($seat, 'LeyUk5auEP', Owner:$seat, Controller:$seat);
    for ($j=0; $j<5; ++$j) AddHand($seat, 'rWhFC8XBaH');
    for ($j=0; $j<3; ++$j) AddDeck($seat, 'rWhFC8XBaH');
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Banish');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat holds Thurible against an empty graveyard");
    for ($j=0; $j<3; ++$j) AddGraveyard(3-$seat, 'rWhFC8XBaH');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat uses Thurible for three banishes and memory draw");
    [$legal, $action] = offer('NUMBERCHOOSE', '0|5', 'PurifyingThuriblePay', '', $seat);
    check($action['cardID'] === '3', "P$seat Thurible chooses three instead of default zero");
    $hand = &GetHand($seat); $hand = array_slice($hand, 0, 3);
    check(GABotThuriblePayment($seat) === 0, "P$seat Thurible keeps two hand cards in reserve");
    AddHand($seat, 'rWhFC8XBaH'); AddHand($seat, 'rWhFC8XBaH');
    AddGlobalEffects($seat, 'ri955ygd5v_NO_DRAW');
    check(GABotThuriblePayment($seat) === 0, "P$seat Thurible avoids paying three for a blocked draw");
    $effects = &GetGlobalEffects($seat); $effects = [];
    $grave = &GetGraveyard(3-$seat); $savedGrave = $grave; $grave = [];
    AddGraveyard(3-$seat, 'aIbBhTilEN');
    check(GABotThuriblePayment($seat) === 1, "P$seat Thurible cheaply clears floating memory");
    $grave = $savedGrave;
    AddGraveyard($seat, 'aIbBhTilEN'); AddGraveyard($seat, '0G32Pp5atK');
    [$legal, $action] = offer('MZCHOOSE', 'myGraveyard', 'PurifyingThuribleBanish|' . (3-$seat) . '|1', '', $seat);
    check($action['cardID'] === 'myGraveyard-1', "P$seat Thurible victim preserves floating memory");
    // Resolve real payment prompts and the opposing banish decisions through the engine.
    $queue = &GetDecisionQueue($seat); $queue = [];
    ActivatedAbilityCost($seat, 'myField-1', 'LeyUk5auEP', 0);
    $activateAbilityAbilities['LeyUk5auEP:0']($seat);
    for ($step=0; $step<20; ++$step) {
        $legal = GABotLegalActions('decision-test', $seat);
        if (($legal['kind'] ?? '') !== 'decision') break;
        $pending = $legal['playerID']; $playerID = $pending;
        $action = GABotChooseAction($legal['actions'], $legal);
        check(GameValidateDecisionAnswer($pending, $action['cardID']), "P$seat Thurible continuation $step is legal");
        $dq = new DecisionQueueController(); $dq->PopDecision($pending); $dq->ExecuteStaticMethods($pending, $action['cardID']);
    }
    check(GABotLiveCount(GetGraveyard(3-$seat)) === 0 && GABotLiveCount(GetBanish(3-$seat)) === 3
        && GABotLiveCount(GetMemory($seat)) === 4 && GABotLiveCount(GetHand($seat)) === 2,
        "P$seat Thurible pays three, draws into memory and banishes three opposing cards");

    resetBoard($seat);
    GetField($seat)[0]->CardID = 'TJTeWcZnsQ';
    AddField($seat, 'm31WVJ9F04', Owner:$seat, Controller:$seat);
    AddCounters($seat, 'myField-1', 'durability', 2);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Prevent');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Clarent preserves durability without spell damage");
    AddEffectStack('0G32Pp5atK', 3-$seat);
    GetEffectStack()[0]->Counters['activationTargets'] = json_encode(['primary'=>[GATargetIdentity('myField-0')]]);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat Clarent responds to publicly targeted spell damage");
    foreach (['RIVahUIQVD'=>1 + PlayerLevel(3-$seat), '50m1nBduUZ'=>2,
        'vjonmi9jjq'=>1, 'ZIU4bH6D9q'=>2, '145y6KBhxe'=>4] as $spell=>$expected) {
        GetEffectStack()[0]->CardID = $spell;
        check(GABotDeclaredDamageEstimate(GetEffectStack()[0], GetField($seat)[0]) === $expected,
            "P$seat public damage estimate for $spell");
        [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
        check($action['cardID'] === $choice, "P$seat Clarent recognizes $spell");
    }
    GetEffectStack()[0]->CardID = 'ZIU4bH6D9q';
    GetField($seat)[0]->Damage = 1;
    check(GABotDeclaredDamageEstimate(GetEffectStack()[0], GetField($seat)[0]) === 6,
        "P$seat wounded target changes damage estimate");
    GetField($seat)[0]->Damage = 0;
    GetEffectStack()[0]->CardID = 'RIVahUIQVD';
    GetField($seat)[1]->Counters['durability'] = 1;
    GetField($seat)[0]->Damage = ObjectCurrentHP(GetField($seat)[0]) - (1 + PlayerLevel(3-$seat));
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat Clarent saves a unit from level-scaled Fireball");
    GetField($seat)[0]->Damage = 0;
    GetField($seat)[1]->Counters['durability'] = 2;
    GetEffectStack()[0]->CardID = '0G32Pp5atK';
    GetEffectStack()[0]->Controller = $seat;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Clarent ignores own spell activations");
    GetEffectStack()[0]->Controller = 3-$seat;
    GetEffectStack()[0]->CardID = 'rWhFC8XBaH';
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Clarent does not guess unknown spell damage");
    GetEffectStack()[0]->CardID = '0G32Pp5atK';
    GetField($seat)[1]->Counters['durability'] = 1;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Clarent preserves last durability against nonlethal damage");
    GetField($seat)[0]->Damage = ObjectCurrentHP(GetField($seat)[0]) - 2;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat Clarent spends last durability to survive lethal spell");
    AddTurnEffect('myField-0', 'PREVENT_NONCOMBAT_1');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Clarent does not stack existing protection");
    GetField($seat)[0]->TurnEffects = [];
    $queue = &GetDecisionQueue($seat); $queue = [];
    ActivatedAbilityCost($seat, 'myField-1', 'm31WVJ9F04', 0);
    $activateAbilityAbilities['m31WVJ9F04:0']($seat);
    $before = GetField($seat)[0]->Damage;
    OnDealDamage(3-$seat, 'EffectStack-0', 'myField-0', 2);
    check(GetField($seat)[0]->Damage === $before + 1 && GetCounterCount(GetField($seat)[1], 'durability') === 0,
        "P$seat Clarent spends durability and prevents one actual noncombat damage");
}
// Tanggu pays for delayed card advantage only with spare resources and no pending threat.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddField($seat, 'mb3iqw3kc6', Owner:$seat, Controller:$seat);
    for ($i=0; $i<5; ++$i) AddHand($seat, 'rWhFC8XBaH');
    for ($i=0; $i<3; ++$i) AddDeck($seat, 'rWhFC8XBaH');
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Banish');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Tanggu preserves own-turn resources");
    SetTurnPlayer(3-$seat);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat Tanggu uses spare resources in a quiet opposing turn");
    AddEffectStack('RIVahUIQVD', 3-$seat);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Tanggu holds during a pending effect");
    $stack = &GetEffectStack(); $stack = [];
    $hand = &GetHand($seat); $savedHand = $hand; array_pop($hand);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Tanggu keeps two response cards");
    $hand = $savedHand;
    AddGlobalEffects($seat, 'ri955ygd5v_NO_DRAW');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Tanggu avoids a blocked draw");
    $effects = &GetGlobalEffects($seat); $effects = [];
    $deck = &GetDeck($seat); $savedDeck = $deck; $deck = array_slice($deck, 0, 1);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Tanggu preserves the last deck card");
    $deck = $savedDeck;
    $queue = &GetDecisionQueue($seat); $queue = [];
    ActivatedAbilityCost($seat, 'myField-1', 'mb3iqw3kc6', 0);
    for ($step=0; $step<15; ++$step) {
        $dq = new DecisionQueueController(); $dq->ExecuteStaticMethods($seat);
        $legal = GABotLegalActions('decision-test', $seat);
        if (($legal['kind'] ?? '') !== 'decision') break;
        $action = GABotChooseAction($legal['actions'], $legal);
        check(GameValidateDecisionAnswer($seat, $action['cardID']), "P$seat Tanggu payment $step is legal");
        $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    }
    $activateAbilityAbilities['mb3iqw3kc6:0']($seat);
    check(GABotLiveCount(GetHand($seat)) === 2 && GABotLiveCount(GetMemory($seat)) === 4
        && GABotLiveCount(GetBanish($seat)) === 1 && GABotLiveCount(GetDeck($seat)) === 2,
        "P$seat Tanggu pays three, banishes itself and draws into memory");
}
// Ready non-unit reservable sources unlock Tanggu without spending response cards.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddField($seat, 'mb3iqw3kc6', Owner:$seat, Controller:$seat);
    AddField($seat, 'fm894uc4ij', Owner:$seat, Controller:$seat);
    $resource = GetField($seat)[2];
    $resource->Counters['_overrides']['granted_keywords'] = ['Reservable'];
    $resource->Status = 2;
    for ($i=0; $i<4; ++$i) AddHand($seat, 'rWhFC8XBaH');
    for ($i=0; $i<3; ++$i) AddDeck($seat, 'rWhFC8XBaH');
    SetTurnPlayer(3-$seat);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Banish');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat Tanggu counts a ready non-unit reserve source");
    $resource->Status = 1;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Tanggu excludes rested reserve sources");
    $resource->Status = 2;
    $resource->Counters['_overrides']['type'] = 'ALLY';
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Tanggu preserves reservable combat units");
    unset($resource->Counters['_overrides']['type']);
    $queue = &GetDecisionQueue($seat); $queue = [];
    ActivatedAbilityCost($seat, 'myField-1', 'mb3iqw3kc6', 0);
    for ($step=0; $step<15; ++$step) {
        $dq = new DecisionQueueController(); $dq->ExecuteStaticMethods($seat);
        $legal = GABotLegalActions('decision-test', $seat);
        if (($legal['kind'] ?? '') !== 'decision') break;
        $action = GABotChooseAction($legal['actions'], $legal);
        check(GameValidateDecisionAnswer($seat, $action['cardID']), "P$seat reservable Tanggu payment $step is legal");
        $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    }
    $activateAbilityAbilities['mb3iqw3kc6:0']($seat);
    check($resource->Status === 1 && GABotLiveCount(GetHand($seat)) === 2
        && GABotLiveCount(GetMemory($seat)) === 3 && GABotLiveCount(GetDeck($seat)) === 2,
        "P$seat Tanggu rests its reserve source and retains two hand cards");
}
// Armoire preserves omen cost bonuses while trading an expensive hand card for a cheap one.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    GetField($seat)[0]->CardID = 'nn48ne8a05';
    AddField($seat, 'fm894uc4ij', Owner:$seat, Controller:$seat);
    foreach (['rWhFC8XBaH', 'rWhFC8XBaH', 'szene5o32m'] as $id) {
        AddBanish($seat, $id);
        AddCounters($seat, 'myBanish-' . (count(GetBanish($seat))-1), 'omen', 1);
    }
    AddHand($seat, 'szene5o32m');
    AddHand($seat, 'rWhFC8XBaH');
    AddHand($seat, 'rWhFC8XBaH');
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Banish');
    SetCurrentPhase('MAT');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Armoire waits for main phase");
    SetCurrentPhase('MAIN');
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat Armoire takes a useful exchange");
    $hand = &GetHand($seat); $fullHand = $hand; $hand = array_slice($hand, 0, 1);
    check(GABotArmoirePair($seat) === null, "P$seat Armoire rejects a card with no reserve to play it");
    AddField($seat, 'mb3iqw3kc6', Owner:$seat, Controller:$seat);
    $resource = GetField($seat)[2]; $resource->Status = 2;
    $resource->Counters['_overrides']['granted_keywords'] = ['Reservable'];
    check(GABotArmoirePair($seat) !== null, "P$seat Armoire counts ready non-unit reserve toward the acquired card");
    $resource->Status = 1;
    check(GABotArmoirePair($seat) === null, "P$seat Armoire excludes a rested reserve source");
    $resource->removed = true; $hand = $fullHand;
    AddEffectStack('RIVahUIQVD', 3-$seat);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Armoire holds during a pending effect");
    $stack = &GetEffectStack(); $stack = [];
    $hand = &GetHand($seat); $savedHand = $hand; $hand = [];
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Armoire avoids an empty-hand exchange");
    $hand = $savedHand;
    GetBanish($seat)[1]->removed = true;
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Armoire preserves a unique omen cost");
    GetBanish($seat)[1]->removed = false;
    SetTurnPlayer(3-$seat);
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Armoire holds on opposing turn");
    SetTurnPlayer($seat);
    GetField($seat)[0]->CardID = 'LMyKyVC2O9';
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, '', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat Armoire requires Ciel");
    GetField($seat)[0]->CardID = 'nn48ne8a05';
    $queue = &GetDecisionQueue($seat); $queue = [];
    ActivatedAbilityCost($seat, 'myField-1', 'fm894uc4ij', 0);
    $activateAbilityAbilities['fm894uc4ij:0']($seat);
    for ($step=0; $step<2; ++$step) {
        $legal = GABotLegalActions('decision-test', $seat);
        $action = GABotChooseAction($legal['actions'], $legal);
        check(GameValidateDecisionAnswer($seat, $action['cardID']), "P$seat Armoire choice $step is legal");
        check(GetZoneObject($action['cardID'])->CardID === ($step === 0 ? 'rWhFC8XBaH' : 'szene5o32m'),
            "P$seat Armoire chooses the planned swap card $step");
        $dq = new DecisionQueueController(); $dq->PopDecision($seat);
        $dq->ExecuteStaticMethods($seat, $action['cardID']);
    }
    check(GABotLiveCount(GetHand($seat)) === 3 && GetOmenCount($seat) === 3
        && GetTotalOmenCost($seat) === 9 && HasOmensWithSameCost($seat)
        && GetOmenDistinctCostCount($seat) === 2,
        "P$seat Armoire swap resolves and preserves omen bonuses");
}
// Flash Grenade uses public spell targets and only protects distant units.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddField($seat, 'isxy5lh23q', Owner:$seat, Controller:$seat);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Prevent');
    $chooseGrenade = function() use ($choice, $seat) {
        return GABotOptionalScore($seat, $choice, ['tooltip' => 'Take_a_fast_action?']) > 0 ? $choice : 'PASS';
    };
    check($chooseGrenade() === 'PASS', "P$seat Grenade holds without a threat");
    AddEffectStack('0G32Pp5atK', 3-$seat);
    GetEffectStack()[0]->Counters['activationTargets'] = json_encode(['primary'=>[GATargetIdentity('myField-0')]]);
    check($chooseGrenade() === 'PASS', "P$seat Grenade requires distant");
    AddTurnEffect('myField-0', 'DISTANT');
    check($chooseGrenade() === $choice, "P$seat Grenade prevents recognized spell damage");
    GetEffectStack()[0]->Controller = $seat;
    check($chooseGrenade() === 'PASS', "P$seat Grenade ignores own spells");
    GetEffectStack()[0]->Controller = 3-$seat;
    GetEffectStack()[0]->CardID = 'rWhFC8XBaH';
    check($chooseGrenade() === 'PASS', "P$seat Grenade does not guess unknown damage");
    GetEffectStack()[0]->CardID = 'vjonmi9jjq';
    check($chooseGrenade() === 'PASS', "P$seat Grenade preserves consumable against one damage");
    GetField($seat)[0]->Damage = ObjectCurrentHP(GetField($seat)[0]) - 1;
    check($chooseGrenade() === $choice, "P$seat Grenade saves a unit from one lethal damage");
    GetField($seat)[0]->Damage = 0;
    GetEffectStack()[0]->CardID = '0G32Pp5atK';
    AddTurnEffect('myField-0', 'PREVENT_NONCOMBAT_1');
    check($chooseGrenade() === 'PASS', "P$seat Grenade conservatively avoids existing protection");
    GetField($seat)[0]->TurnEffects = ['DISTANT'];
    AddEffectStack('isxy5lh23q', $seat);
    check($chooseGrenade() === 'PASS', "P$seat Grenade avoids a duplicate pending activation");
    GetEffectStack()[1]->removed = true;
    $queue = &GetDecisionQueue($seat); $queue = [];
    DecisionQueueController::StoreVariable('mzID', 'myField-1');
    DecisionQueueController::StoreVariable('abilityIndex', '0');
    $activateAbilityAbilities['isxy5lh23q:0']($seat);
    check(GABotLiveCount(GetBanish($seat)) === 1 && GlobalEffectCount($seat, 'isxy5lh23q') === 1,
        "P$seat Grenade banishes itself and installs protection");
    OnDealDamage(3-$seat, 'EffectStack-0', 'myField-0', 4);
    check(GetField($seat)[0]->Damage === 1, "P$seat Grenade actually prevents three damage");
    check(GABotFlashGrenadeScore($seat) === -1, "P$seat Grenade avoids active protection");
}
// Combat protection is evaluated before damage, after pending effects settle.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddField($seat, 'isxy5lh23q', Owner:$seat, Controller:$seat);
    AddField(3-$seat, '5n874ubgai', Owner:3-$seat, Controller:3-$seat);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Prevent');
    $chooseGrenade = function() use ($choice, $seat) {
        return GABotOptionalScore($seat, $choice, ['tooltip' => 'Take_a_fast_action?']) > 0 ? $choice : 'PASS';
    };
    DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval(3-$seat));
    StoreCombatAttackerState('myField-0', 3-$seat);
    StoreCombatTargetState('theirField-0', 3-$seat);
    check($chooseGrenade() === 'PASS', "P$seat combat Grenade requires distant");
    AddTurnEffect('myField-0', 'DISTANT');
    $threat = GABotCombatContext($seat)['threat'];
    check($threat >= 2 && $chooseGrenade() === $choice, "P$seat Grenade prevents nonlethal combat damage");
    AddEffectStack('rWhFC8XBaH', 3-$seat);
    check($chooseGrenade() === 'PASS', "P$seat combat Grenade waits for unresolved effects");
    GetEffectStack()[0]->removed = true;
    DecisionQueueController::StoreVariable('CombatDamageAmount', 0);
    check($chooseGrenade() === 'PASS', "P$seat combat Grenade holds after damage even if zero");
    DecisionQueueController::StoreVariable('CombatDamageAmount', null);
    AddTurnEffect('myField-0', 'PREVENT_COMBAT_3');
    check($chooseGrenade() === 'PASS', "P$seat combat Grenade avoids existing combat protection");
    GetField($seat)[0]->TurnEffects = ['DISTANT', 'PREVENT_NONCOMBAT_1'];
    check($chooseGrenade() === $choice, "P$seat noncombat protection does not block combat Grenade");
    GetField($seat)[0]->Damage = ObjectCurrentHP(GetField($seat)[0]) - $threat;
    check($chooseGrenade() === $choice, "P$seat Grenade saves a unit from lethal combat");
    AddCounters($seat, 'theirField-0', 'buff', 20);
    check($chooseGrenade() === 'PASS', "P$seat Grenade holds when combat remains lethal");
    RemoveCounters($seat, 'theirField-0', 'buff', 20);
    GetField(3-$seat)[0]->Controller = $seat;
    check($chooseGrenade() === 'PASS', "P$seat Grenade ignores friendly attacks");
    GetField(3-$seat)[0]->Controller = 3-$seat;
    $queue = &GetDecisionQueue($seat); $queue = [];
    DecisionQueueController::StoreVariable('mzID', 'myField-1');
    DecisionQueueController::StoreVariable('abilityIndex', '0');
    $activateAbilityAbilities['isxy5lh23q:0']($seat);
    $before = GetField($seat)[0]->Damage;
    check(IsCombatDamageFrom('theirField-0'), "P$seat Grenade fixture uses actual combat damage");
    OnDealDamage(3-$seat, 'theirField-0', 'myField-0', $threat);
    check(GetField($seat)[0]->Damage === $before + max(0, $threat - 3),
        "P$seat Grenade resolves combat prevention");
    check(GABotFlashGrenadeScore($seat) === -1, "P$seat combat Grenade avoids active protection");
}
// Safeguard protects only the champion from recognized noncombat damage.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddField($seat, 'yj2rJBREH8', Owner:$seat, Controller:$seat);
    AddField($seat, '5n874ubgai', Owner:$seat, Controller:$seat);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Banish');
    $chooseAmulet = function() use ($choice, $seat) {
        return GABotOptionalScore($seat, $choice, ['tooltip' => 'Take_a_fast_action?']) > 0 ? $choice : 'PASS';
    };
    check($chooseAmulet() === 'PASS', "P$seat Safeguard holds without a spell");
    AddEffectStack('0G32Pp5atK', 3-$seat);
    $spell = GetEffectStack()[0];
    $spell->Counters['activationTargets'] = json_encode(['primary'=>[GATargetIdentity('myField-2')]]);
    check($chooseAmulet() === 'PASS', "P$seat Safeguard does not protect allies");
    $spell->Counters['activationTargets'] = json_encode(['primary'=>[GATargetIdentity('myField-0')]]);
    check($chooseAmulet() === $choice, "P$seat Safeguard responds to champion damage");
    $spell->Controller = $seat;
    check($chooseAmulet() === 'PASS', "P$seat Safeguard ignores friendly spells");
    $spell->Controller = 3-$seat;
    $spell->CardID = 'rWhFC8XBaH';
    check($chooseAmulet() === 'PASS', "P$seat Safeguard holds for unknown effects");
    $spell->CardID = 'vjonmi9jjq';
    check($chooseAmulet() === 'PASS', "P$seat Safeguard holds against one nonlethal damage");
    GetField($seat)[0]->Damage = ObjectCurrentHP(GetField($seat)[0]) - 1;
    check($chooseAmulet() === $choice, "P$seat Safeguard prevents one lethal damage");
    $spell->CardID = '0op3nq0ymv';
    check($chooseAmulet() === $choice, "P$seat Safeguard saves champion from three damage");
    $spell->CardID = 'ZIU4bH6D9q';
    check($chooseAmulet() === 'PASS', "P$seat Safeguard holds when six damage remains lethal");
    GetField($seat)[0]->Damage = 0;
    $spell->CardID = '0G32Pp5atK';
    AddTurnEffect('myField-0', 'PREVENT_NONCOMBAT_1');
    check($chooseAmulet() === 'PASS', "P$seat Safeguard avoids existing prevention");
    GetField($seat)[0]->TurnEffects = [];
    AddEffectStack('yj2rJBREH8', $seat);
    check($chooseAmulet() === 'PASS', "P$seat Safeguard avoids pending duplicate");
    GetEffectStack()[1]->removed = true;
    AddGlobalEffects($seat, 'isxy5lh23q');
    AddTurnEffect('myField-0', 'DISTANT');
    check($chooseAmulet() === 'PASS', "P$seat Safeguard avoids active distant protection");
    GetField($seat)[0]->TurnEffects = [];
    check($chooseAmulet() === $choice, "P$seat distant protection does not cover a non-distant champion");
    $queue = &GetDecisionQueue($seat); $queue = [];
    DecisionQueueController::StoreVariable('mzID', 'myField-1');
    DecisionQueueController::StoreVariable('abilityIndex', '0');
    $activateAbilityAbilities['yj2rJBREH8:0']($seat);
    check(GABotLiveCount(GetBanish($seat)) === 1 && GABotSafeguardScore($seat) === -1,
        "P$seat Safeguard banishes itself and avoids duplicate protection");
    OnDealDamage(3-$seat, 'EffectStack-0', 'myField-0', 5);
    check(GetField($seat)[0]->Damage === 1, "P$seat Safeguard actually prevents four damage");
    OnDealDamage(3-$seat, 'EffectStack-0', 'myField-0', 2);
    check(GetField($seat)[0]->Damage === 3, "P$seat Safeguard protection is consumed");
}
// Nullifying Mirror uses public memory counts, not hidden identities.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddField($seat, 'pol1nz0j1n', Owner:$seat, Controller:$seat);
    GetField($seat)[1]->Status = 2;
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Norm memory');
    $chooseMirror = function() use ($choice, $seat) {
        return GABotOptionalScore($seat, $choice, ['tooltip' => 'Take_a_fast_action?']) > 0 ? $choice : 'PASS';
    };
    SetTurnPlayer(3-$seat);
    check($chooseMirror() === 'PASS', "P$seat Mirror holds against empty memory");
    AddMemory(3-$seat, '0G32Pp5atK');
    check($chooseMirror() === 'PASS', "P$seat Mirror holds with no public matchup evidence");
    AddField(3-$seat, 'LMyKyVC2O9', Owner:3-$seat, Controller:3-$seat);
    check($chooseMirror() === 'PASS', "P$seat Mirror holds against Fire spirit");
    GetField(3-$seat)[0]->CardID = 'pNiyaGlIe7';
    check($chooseMirror() === $choice, "P$seat Mirror suppresses opposing-turn memory");
    GetField(3-$seat)[0]->CardID = 'e3z4pyx8bd';
    GetField(3-$seat)[0]->Subcards = ['pNiyaGlIe7'];
    check($chooseMirror() === $choice, "P$seat Mirror remembers Wind spirit under later champion");
    GetField(3-$seat)[0]->Subcards = ['LMyKyVC2O9'];
    check($chooseMirror() === 'PASS', "P$seat Mirror does not infer Luxem from Diana");
    GetField(3-$seat)[0]->CardID = '59ipqa91r2';
    check($chooseMirror() === $choice, "P$seat Mirror infers possible Luxem from Guo Jia");
    GetField(3-$seat)[0]->CardID = 'UAF6Nr7GUE';
    check($chooseMirror() === $choice, "P$seat Mirror recognizes confirmed Luxem champion");

    SetTurnPlayer($seat);
    check($chooseMirror() === 'PASS', "P$seat Mirror preserves own-turn readiness");
    SetTurnPlayer(3-$seat);
    GetMemory(3-$seat)[0]->CardID = 'rWhFC8XBaH';
    check($chooseMirror() === $choice, "P$seat Mirror choice is unchanged by hidden Norm card");
    GetMemory(3-$seat)[0]->CardID = 'hidden-card-sentinel';
    check($chooseMirror() === $choice, "P$seat Mirror never needs hidden card metadata");
    GetMemory(3-$seat)[0]->CardID = '0G32Pp5atK';
    GetField($seat)[1]->Status = 1;
    check($chooseMirror() === 'PASS', "P$seat Mirror respects resting cost");
    GetField($seat)[1]->Status = 2;
    AddEffectStack('pol1nz0j1n', $seat);
    check($chooseMirror() === 'PASS', "P$seat Mirror avoids a pending duplicate");
    GetEffectStack()[0]->removed = true;
    AddGlobalEffects($seat, '0sVdvpQKXq_MEMORY');
    check($chooseMirror() === 'PASS', "P$seat Mirror avoids equivalent active suppression");
    $effects = &GetGlobalEffects($seat); $effects = [];
    AddMemory($seat, '0G32Pp5atK');
    $before = EffectiveCardElement(GetMemory(3-$seat)[0]);
    $queue = &GetDecisionQueue($seat); $queue = [];
    ActivatedAbilityCost($seat, 'myField-1', 'pol1nz0j1n', 0);
    $activateAbilityAbilities['pol1nz0j1n:0']($seat);
    check(GetField($seat)[1]->Status === 1 && GlobalEffectCount($seat, 'pol1nz0j1n') === 1,
        "P$seat Mirror rests and resolves suppression");
    check($before !== 'NORM' && EffectiveCardElement(GetMemory(3-$seat)[0]) === 'NORM',
        "P$seat Mirror changes opposing memory element");
    check(EffectiveCardElement(GetMemory($seat)[0]) === $before,
        "P$seat Mirror leaves own memory unchanged");
    GetField($seat)[1]->Status = 2;
    check($chooseMirror() === 'PASS', "P$seat Mirror avoids redundant suppression after readying");
}
// Opening-turn attacks must not enter the chooser's action set.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    SetCurrentPhase('MAIN');
    SetTurnNumber(1);
    SetFirstPlayer($seat);
    AddField($seat, CardNameToID('Dungeon Guide'), Owner:$seat, Controller:$seat);
    GetField($seat)[1]->Status = 2;
    $hasAttack = function() use ($seat) {
        $legal = GABotLegalActions('decision-test', $seat);
        return in_array('myField-1!FSM!', array_column($legal['actions'], 'cardID'));
    };
    SetFlashMessage('unchanged');
    check(!$hasAttack(), "P$seat excludes opening-turn attacks");
    check(GetFlashMessage() === 'unchanged', "P$seat opening-turn filter has no rejection side effect");
    SetFirstPlayer(3 - $seat);
    check($hasAttack(), "P$seat may attack when not the opening player");
    SetFirstPlayer($seat);
    SetTurnNumber(2);
    check($hasAttack(), "P$seat may attack on later turns");
    SetTurnNumber(1);
    SetMacroGameIndex(json_encode(['dtrExtraTurnActive' => true]));
    check($hasAttack(), "P$seat preserves the extra-turn exception");
}
// Pride uses the live requirement, including temporary ability suppression.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    SetCurrentPhase('MAIN');
    SetTurnNumber(2);
    $field = &GetField($seat); $field = [];
    AddField($seat, 'TJTeWcZnsQ', Owner:$seat, Controller:$seat); // Lorraine: level 2
    AddField($seat, 'c3plbuv3fr', Owner:$seat, Controller:$seat); // Jueying: pride 3
    GetField($seat)[1]->Status = 2;
    $hasAttack = function() use ($seat) {
        $legal = GABotLegalActions('decision-test', $seat);
        return in_array('myField-1!FSM!', array_column($legal['actions'], 'cardID'));
    };
    SetFlashMessage('unchanged');
    check(intval(PrideAmount(GetField($seat)[1])) === 3 && intval(PlayerLevel($seat)) === 2,
        "P$seat Pride fixture is below the required level (pride=" . PrideAmount(GetField($seat)[1]) . ", level=" . PlayerLevel($seat) . ")");
    check(!$hasAttack(), "P$seat excludes attacks below Pride level");
    check(GetFlashMessage() === 'unchanged', "P$seat Pride filter has no rejection side effect");
    GetField($seat)[0]->Counters['level'] = 1;
    check($hasAttack(), "P$seat permits attacks at Pride level");
    GetField($seat)[0]->Counters['level'] = 0;
    AddTurnEffect('myField-1', 'NO_ABILITIES');
    check($hasAttack(), "P$seat permits attacks when Pride is suppressed");
    GetField($seat)[1]->TurnEffects = [];
    AddTurnEffect('myField-1', 'pk9xycwz9g'); // Cell Handler removes Pride this turn
    check($hasAttack(), "P$seat respects a temporary Pride removal");
}
// Tournament regression: Ominous Shadows must wait for champion combat damage.
foreach ([1, 2] as $seat) {
    resetBoard($seat); SetCurrentPhase('MAIN'); SetTurnNumber(2);
    AddField($seat, 'gveirpdm44', Owner:$seat, Controller:$seat);
    GetField($seat)[1]->Status = 2;
    AddField(3-$seat, 'LMyKyVC2O9', Owner:3-$seat, Controller:3-$seat);
    $hasAttack = function() use ($seat) {
        return in_array('myField-1!FSM!', array_column(GABotLegalActions('decision-test', $seat)['actions'], 'cardID'), true);
    };
    check(!$hasAttack(), "P$seat Shadow waits for champion damage");
    TrackChampionCombatDamage($seat, 'theirField-0', 1);
    check($hasAttack(), "P$seat Shadow may attack a damaged surviving unit");
    GetField(3-$seat)[0]->removed = true;
    check(!$hasAttack(), "P$seat Shadow excludes a departed damaged target");
    GetField(3-$seat)[0]->removed = false;
    GetField(3-$seat)[0]->TurnEffects = [];
    AddTurnEffect('myField-1', 'NO_ABILITIES');
    check($hasAttack(), "P$seat suppressed Shadow can attack without champion damage");
}
// Tristan support: spend resources only for understood value, from either seat.
foreach ([1, 2] as $seat) {
    resetBoard($seat); SetCurrentPhase('MAIN'); SetTurnNumber(2);
    GetField($seat)[0]->CardID = 'he6kd7hocc';
    AddField($seat, '8yzADlgx4R', Owner:$seat, Controller:$seat);
    GetField($seat)[1]->Status = 2;
    GetField($seat)[0]->Counters['preparation'] = 2;
    AddField(3-$seat, 'LMyKyVC2O9', Owner:3-$seat, Controller:3-$seat);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Buff');
    $score = function() use ($seat, &$choice) { return GABotOptionalScore($seat, $choice, ['tooltip'=>'Take_a_fast_action?']); };
    check($score() < 0, "P$seat preserves Ripper preparation outside combat");
    DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval($seat));
    StoreCombatAttackerState('myField-0', $seat);
    StoreCombatTargetState('theirField-0', $seat);
    DecisionQueueController::StoreVariable('CombatWeapon', 'myField-1');
    $threat = GABotCombatContext($seat)['threat'];
    GetField(3-$seat)[0]->Damage = ObjectCurrentHP(GetField(3-$seat)[0]) - $threat - 1;
    check($score() > 0, "P$seat boosts Ripper to cross lethal threshold");
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat chooses the lethal Ripper boost");
    GetField(3-$seat)[0]->Damage += 1;
    check($score() < 0, "P$seat saves Ripper preparation when attack is already lethal");
    GetField(3-$seat)[0]->Damage -= 1;
    GetField($seat)[1]->Status = 1;
    check($score() < 0, "P$seat cannot rest an exhausted Ripper for its boost");
    GetField($seat)[1]->Status = 2;
    DecisionQueueController::StoreVariable('CombatWeapon', 'myField-0');
    check($score() < 0, "P$seat does not boost a weapon outside the current attack");
    DecisionQueueController::StoreVariable('CombatWeapon', 'myField-1');
    GetField($seat)[0]->Counters['preparation'] = 0;
    check($score() < 0, "P$seat cannot boost Ripper without preparation");
    GetField($seat)[0]->Counters['preparation'] = 2;
    GetField(3-$seat)[0]->Damage -= 4;
    check($score() < 0, "P$seat saves preparation when Ripper cannot finish target");

    resetBoard($seat); SetCurrentPhase('MAIN');
    AddField($seat, 'ugly4wiffe', Owner:$seat, Controller:$seat);
    for ($i=0; $i<3; ++$i) AddHand($seat, 'rWhFC8XBaH');
    AddField(3-$seat, '5n874ubgai', Owner:3-$seat, Controller:3-$seat);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Return');
    check($score() < 0, "P$seat keeps Sadi on an unthreatened board");
    DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval(3-$seat));
    StoreCombatAttackerState('myField-0', 3-$seat);
    StoreCombatTargetState('theirField-1', 3-$seat);
    GetField($seat)[1]->Damage = ObjectCurrentHP(GetField($seat)[1])-1;
    check($score() > 0, "P$seat returns Sadi before lethal damage");
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat chooses Sadi rescue");
    StoreCombatTargetState('theirField-0', 3-$seat);
    check($score() < 0, "P$seat does not return Sadi when another unit is attacked");
    StoreCombatTargetState('theirField-1', 3-$seat);
    $savedDamage = GetField($seat)[1]->Damage;
    GetField($seat)[1]->Damage = 0;
    GetField(3-$seat)[0]->CardID = 'LMyKyVC2O9';
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === 'PASS', "P$seat keeps Sadi for a nonlethal attack");
    GetField($seat)[1]->Damage = $savedDamage;
    GetField(3-$seat)[0]->CardID = '5n874ubgai';
    $hand = &GetHand($seat); $savedHand = $hand; $hand = [];
    check($score() < 0, "P$seat cannot rescue Sadi without reserve payment");
    $hand = $savedHand;
    DecisionQueueController::StoreVariable('CombatDamageAmount', 2);
    check($score() < 0, "P$seat does not return Sadi after damage");

    resetBoard($seat);
    AddField($seat, 'ScGcOmkoQt', Owner:$seat, Controller:$seat);
    AddDeck($seat, 'rWhFC8XBaH'); AddDeck($seat, 'rWhFC8XBaH');
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, '');
    check($score() > 0, "P$seat cycles Smoke Bombs safely on an empty board");
    AddField(3-$seat, 'ugly4wiffe', Owner:3-$seat, Controller:3-$seat);
    check($score() < 0, "P$seat avoids granting only enemy allies stealth");
    AddField($seat, 'ugly4wiffe', Owner:$seat, Controller:$seat);
    check($score() > 0, "P$seat uses Smoke Bombs with a friendly ally");
    [$legal, $action] = offer('MZCHOOSE', 'theirField-0&myField-2', 'ScGcOmkoQt:0:ActivateAbility-1', '', $seat);
    check($action['cardID'] === 'myField-2', "P$seat grants Smoke Bombs stealth to a friendly ally");
    GetDeck($seat)[1]->removed = true;
    check($score() < 0, "P$seat preserves last deck card instead of cycling Smoke Bombs");
}
// Guo Jia response coverage uses public targets, costs and visible Fractals only.
foreach ([1, 2] as $seat) {
    resetBoard($seat); SetCurrentPhase('MAIN');
    GetField($seat)[0]->CardID = '59ipqa91r2';
    GetField($seat)[0]->Subcards = ['tafqldAGRF'];
    AddHand($seat, '6ffqsuo6gb');
    for ($i=0; $i<6; ++$i) AddHand($seat, 'rWhFC8XBaH');
    AddField(3-$seat, 'LMyKyVC2O9', Owner:3-$seat, Controller:3-$seat);
    $chooseSpell = function() use ($seat) {
        return offer('MZMAYCHOOSE', 'myHand-0', 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat)[1]['cardID'];
    };
    check($chooseSpell() === 'PASS', "P$seat saves Missile instead of incidental chip damage");
    GetField(3-$seat)[0]->Damage = ObjectCurrentHP(GetField(3-$seat)[0]) - 2;
    check($chooseSpell() === 'PASS', "P$seat Missile waits when base damage cannot finish target");
    AddField($seat, '5fnmnpavo4', Owner:$seat, Controller:$seat);
    check(GABotRefractingDamage($seat) === 2, "P$seat Missile counts a visible Fractal");
    check($chooseSpell() === 'myHand-0', "P$seat casts Missile for lethal with a Fractal");
    AddTurnEffect('theirField-0', 'SPELLSHROUD');
    check($chooseSpell() === 'PASS', "P$seat Missile respects protected lethal targets");
    GetField(3-$seat)[0]->TurnEffects = [];
    $hand = &GetHand($seat); $savedHand = $hand; $hand = [$hand[0]];
    check($chooseSpell() === 'PASS', "P$seat holds unaffordable Missile");
    $hand = $savedHand;

    $payload = base64_encode(json_encode(['cardID'=>'6ffqsuo6gb']));
    [$legal, $action] = offer('MZCHOOSE', 'myField-0&theirField-0', 'GADeclareTarget|'.$payload.'|'.base64_encode('primary'), '', $seat);
    check($action['cardID'] === 'theirField-0', "P$seat Missile declaration targets the opposing champion");
    GetField($seat)[1]->removed = true;
    check($chooseSpell() === 'PASS', "P$seat Missile excludes departed Fractals");
    GetField($seat)[1]->removed = false;
    AddEffectStack('6ffqsuo6gb', $seat);
    check($chooseSpell() === 'PASS', "P$seat waits for pending Missile before spending another");
    $stack = &GetEffectStack(); $stack = [];
    GetHand($seat)[0]->CardID = 'cpvn96659y';
    check($chooseSpell() === 'PASS', "P$seat holds Fracturize without opposing equipment");
    AddField($seat, '8yzADlgx4R', Owner:$seat, Controller:$seat);
    AddField(3-$seat, '8yzADlgx4R', Owner:3-$seat, Controller:3-$seat);
    check($chooseSpell() === 'myHand-0', "P$seat uses Fracturize against opposing weapon");
    AddTurnEffect('theirField-1', 'SPELLSHROUD');
    check($chooseSpell() === 'PASS', "P$seat Fracturize respects protected equipment");
    GetField(3-$seat)[1]->TurnEffects = [];
    AddEffectStack('cpvn96659y', $seat);
    check($chooseSpell() === 'PASS', "P$seat waits for pending Fracturize");
    $stack = &GetEffectStack(); $stack = [];

    [$legal, $action] = offer('MZCHOOSE', 'myField-2&theirField-1', 'cpvn96659y:0:CardActivated-1', '', $seat);
    check($action['cardID'] === 'theirField-1', "P$seat Fracturize preserves own weapon");
    GetField(3-$seat)[1]->removed = true;
    check($chooseSpell() === 'PASS', "P$seat does not Fracturize friendly-only equipment");
}
// Cerulean chooses modes and corresponding targets from the same public plan.
foreach ([1, 2] as $seat) {
    resetBoard($seat); SetCurrentPhase('MAIN'); SetTurnPlayer(3-$seat);
    GetField($seat)[0]->Subcards = ['tafqldAGRF'];
    AddHand($seat, 'ipl6gt7lh9');
    for ($i=0; $i<6; ++$i) AddHand($seat, 'rWhFC8XBaH');
    for ($i=0; $i<3; ++$i) AddDeck($seat, 'rWhFC8XBaH');
    $frame = 'GADeclareTarget|'.base64_encode(json_encode(['cardID'=>'ipl6gt7lh9'])).'|';
    $chooseDecree = function() use ($seat) {
        return offer('MZMAYCHOOSE', 'myHand-0', 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat)[1]['cardID'];
    };
    check($chooseDecree() === 'myHand-0', "P$seat Decree uses spare resources for delayed draw");
    [$legal, $action] = offer('MZMODAL', '1|1|Negate_non_attack&Unit_attack_power&Draw_into_memory', $frame.base64_encode('mode'), '', $seat);
    check($action['cardID'] === '2', "P$seat Decree chooses memory draw in a quiet window");
    SetTurnPlayer($seat);
    check($chooseDecree() === 'PASS', "P$seat Decree preserves own-turn resources");
    SetTurnPlayer(3-$seat);
    AddField(3-$seat, '5n874ubgai', Owner:3-$seat, Controller:3-$seat);
    DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval(3-$seat));
    StoreCombatAttackerState('myField-0', 3-$seat);
    StoreCombatTargetState('theirField-0', 3-$seat);
    GetField($seat)[0]->Damage = ObjectCurrentHP(GetField($seat)[0]) - 2;
    check($chooseDecree() === 'myHand-0', "P$seat Decree responds to survivable lethal combat");
    [$legal, $action] = offer('MZMODAL', '1|1|Negate_non_attack&Unit_attack_power&Draw_into_memory', $frame.base64_encode('mode'), '', $seat);
    check($action['cardID'] === '1', "P$seat Decree chooses defensive power mode");
    [$legal, $action] = offer('MZMAYCHOOSE', 'myField-0&theirField-0', $frame.base64_encode('power'), '', $seat);
    check($action['cardID'] === 'theirField-0', "P$seat Decree debuffs the opposing attacker");
    AddTurnEffect('theirField-0', 'SPELLSHROUD');
    check($chooseDecree() === 'PASS', "P$seat Decree respects attacker protection");
    GetField(3-$seat)[0]->TurnEffects = [];
    AddEffectStack('6ffqsuo6gb', 3-$seat);
    AddEffectStack('6ffqsuo6gb', $seat);
    [$legal, $action] = offer('MZMODAL', '2|2|Negate_non_attack&Unit_attack_power&Draw_into_memory', $frame.base64_encode('mode'), '', $seat);
    check($action['cardID'] === '1,0', "P$seat imbued Decree combines defense and opposing activation tax");
    [$legal, $action] = offer('MZMAYCHOOSE', 'EffectStack-1&EffectStack-0', $frame.base64_encode('negate'), '', $seat);
    check($action['cardID'] === 'EffectStack-0', "P$seat Decree never taxes own activation");
    GetEffectStack()[0]->TurnEffects[] = 'CANT_BE_NEGATED';
    [$legal, $action] = offer('MZMAYCHOOSE', 'EffectStack-1&EffectStack-0', $frame.base64_encode('negate'), '', $seat);
    check($action['cardID'] === 'PASS', "P$seat Decree declines unnegatable and friendly activations");
    [$legal, $action] = offer('MZMODAL', '1|1|Unit_attack_power&Draw_into_memory', $frame.base64_encode('mode'), '', $seat);
    check($action['cardID'] === '0', "P$seat Decree maps a reduced mode list to displayed indices");
    $stack = &GetEffectStack(); $stack = [];
    // Execute the generated declared resolver, rather than applying its effect by hand.
    $before = ObjectCurrentPower(GetField(3-$seat)[0]);
    DecisionQueueController::StoreVariable('activationTargets', json_encode([
        'mode'=>['mode'=>'1'], 'power'=>[GATargetIdentity('theirField-0')]]));
    $declaredCardResolutionAbilities['ipl6gt7lh9:0']($seat);
    check(ObjectCurrentPower(GetField(3-$seat)[0]) === $before - 3, "P$seat declared Decree actually reduces power by three");
    check($chooseDecree() === 'PASS', "P$seat Decree does not repeat a resolved sufficient debuff");
}
// Kindle is payment for an already chosen activation, not an optional extra spell.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddGraveyard($seat, 'ek7r2d7uz4');
    AddGraveyard($seat, '7w4v1hgl3e');
    DecisionQueueController::StoreVariable('kindleMax', '2');
    DecisionQueueController::StoreVariable('kindleBanished', '0');
    DecisionQueueController::StoreVariable('kindleReserveCost', '3');
    [$legal, $action] = offer('MZMAYCHOOSE', 'myGraveyard-0&myGraveyard-1', 'KindleProcess', '', $seat);
    check($action['cardID'] === 'myGraveyard-1', "P$seat Kindle prioritizes March Hare's return");
    $dq = new DecisionQueueController(); $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    check(intval(DecisionQueueController::GetVariable('kindleBanished')) === 1,
        "P$seat chosen Kindle reduces remaining payment");
    check(count(array_filter(GetField($seat), fn($o) => !$o->removed && $o->CardID === '7w4v1hgl3e')) === 1,
        "P$seat Kindle actually returns March Hare to the field");
    [$legal, $action] = offer('MZMAYCHOOSE', 'myGraveyard-0', 'KindleProcess', '', $seat);
    check($action['cardID'] === 'myGraveyard-0', "P$seat spends a fire graveyard card for remaining cost");
    DecisionQueueController::StoreVariable('kindleBanished', '2');
    [$legal, $action] = offer('MZMAYCHOOSE', 'myGraveyard-0', 'KindleProcess', '', $seat);
    check($action['cardID'] === 'PASS', "P$seat stops at the Kindle limit");
    DecisionQueueController::StoreVariable('kindleBanished', '1');
    DecisionQueueController::StoreVariable('kindleReserveCost', '1');
    [$legal, $action] = offer('MZMAYCHOOSE', 'myGraveyard-0', 'KindleProcess', '', $seat);
    check($action['cardID'] === 'PASS', "P$seat does not Kindle beyond the remaining reserve cost");
}
// Lorraine conserves useful hand/graveyard resources rather than cycling blindly.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddHand($seat, '5du8f077ua');
    for ($i=0; $i<3; ++$i) AddDeck($seat, 'rWhFC8XBaH');
    [$legal, $action] = offer('YESNO', '-', 'RedHareDiscardDraw', '', $seat);
    check($action['cardID'] === 'NO', "P$seat Red Hare preserves a hand with no known discard payoff");
    AddHand($seat, '7w4v1hgl3e');
    [$legal, $action] = offer('YESNO', '-', 'RedHareDiscardDraw', '', $seat);
    check($action['cardID'] === 'YES', "P$seat Red Hare cycles March Hare toward its graveyard plan");
    $dq = new DecisionQueueController(); $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    $legal = GABotLegalActions('decision-test', $seat);
    $action = GABotChooseAction($legal['actions'], $legal);
    check($action['cardID'] === 'myHand-1', "P$seat Red Hare discards March Hare instead of the first card");
    $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    check(GABotLiveCount(GetHand($seat)) === 2 && GABotLiveCount(GetDeck($seat)) === 2
        && GetGraveyard($seat)[0]->CardID === '7w4v1hgl3e', "P$seat Red Hare discard and draw resolve through engine");
    AddHand($seat, '7w4v1hgl3e');
    AddGlobalEffects($seat, 'ri955ygd5v_NO_DRAW');
    [$legal, $action] = offer('YESNO', '-', 'RedHareDiscardDraw', '', $seat);
    check($action['cardID'] === 'NO', "P$seat Red Hare avoids discarding into a blocked draw");

    resetBoard($seat); SetCurrentPhase('MAIN');
    AddHand($seat, 'ek7r2d7uz4');
    for ($i=0; $i<5; ++$i) AddHand($seat, 'rWhFC8XBaH');
    AddGraveyard($seat, '7w4v1hgl3e');
    AddGraveyard($seat, 'ek7r2d7uz4');
    $chooseFlame = function() use ($seat) {
        return offer('MZMAYCHOOSE', 'myHand-0', '', 'Take_a_fast_action?', $seat)[1]['cardID'];
    };
    check($chooseFlame() === 'PASS', "P$seat does not cast Restorative Flame at full life");
    GABotOwnChampion($seat)->Damage = 4;
    check($chooseFlame() === 'myHand-0', "P$seat casts Restorative Flame with useful recovery and spare graveyard fuel");
    [$legal, $action] = offer('MZMAYCHOOSE', 'myGraveyard-0&myGraveyard-1', 'ek7r2d7uz4:0:CardActivated-1', '', $seat);
    check($action['cardID'] === 'myGraveyard-1', "P$seat Restorative Flame preserves March Hare");
    $dq = new DecisionQueueController(); $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    check(GABotOwnChampion($seat)->Damage === 2, "P$seat selected Restorative Flame payment recovers two");
    $legal = GABotLegalActions('decision-test', $seat);
    $action = GABotChooseAction($legal['actions'], $legal);
    check($action['cardID'] === 'PASS', "P$seat stops recovery rather than consuming the remaining March Hare");
    GABotOwnChampion($seat)->Damage = ObjectCurrentHP(GABotOwnChampion($seat)) - 3;
    check(GABotRestorativePaymentScore($seat, 'myGraveyard-0') > 0, "P$seat spends preserved fuel when life is critical");
    AddEffectStack('ek7r2d7uz4', $seat);
    check($chooseFlame() === 'PASS', "P$seat waits for pending Restorative Flame before spending another");
    $stack = &GetEffectStack(); $stack = [];
    AddGlobalEffects($seat, 'CANT_RECOVER');
    check($chooseFlame() === 'PASS', "P$seat holds Restorative Flame when recovery is blocked");
}
// Hasty Messenger cycles known graveyard value, not guessed future draws.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddHand($seat, '5du8f077ua');
    AddHand($seat, '7w4v1hgl3e');
    for ($i=0; $i<3; ++$i) AddDeck($seat, 'rWhFC8XBaH');
    [$legal, $action] = offer('MZMAYCHOOSE', 'myHand-0&myHand-1', 'DsiRzt0trX:0:OnAttack-1', '', $seat);
    check($action['cardID'] === 'myHand-1', "P$seat Messenger cycles March Hare over an ordinary card");
    $dq = new DecisionQueueController(); $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $action['cardID']);
    check(GABotLiveCount(GetHand($seat)) === 2 && GABotLiveCount(GetDeck($seat)) === 2
        && GetGraveyard($seat)[0]->CardID === '7w4v1hgl3e', "P$seat Messenger discard and draw resolve through engine");
    [$legal, $action] = offer('MZMAYCHOOSE', 'myHand-0', 'DsiRzt0trX:0:OnAttack-1', '', $seat);
    check($action['cardID'] === 'PASS', "P$seat Messenger preserves a card without known graveyard payoff");
    AddHand($seat, '7w4v1hgl3e');
    AddGlobalEffects($seat, 'ri955ygd5v_NO_DRAW');
    [$legal, $action] = offer('MZMAYCHOOSE', 'myHand-2', 'DsiRzt0trX:0:OnAttack-1', '', $seat);
    check($action['cardID'] === 'PASS', "P$seat Messenger avoids blocked draws");
    resetBoard($seat);
    AddHand($seat, '7w4v1hgl3e'); AddDeck($seat, 'rWhFC8XBaH');
    [$legal, $action] = offer('MZMAYCHOOSE', 'myHand-0', 'DsiRzt0trX:0:OnAttack-1', '', $seat);
    check($action['cardID'] === 'PASS', "P$seat Messenger preserves the last deck card");
}
// Tariff Ring uses visible upcoming attack pressure in either seat.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    SetTurnPlayer(3-$seat); SetCurrentPhase('BREC');
    AddField($seat, 'xnrw8qq1uw', Owner:$seat, Controller:$seat);
    $choice = EncodeOpportunityAbilityChoice('myField-1', 0, 'Banish');
    $chooseRing = function() use ($seat, $choice) {
        return GABotOptionalScore($seat, $choice, ['tooltip' => 'Take_a_fast_action?']) > 0 ? $choice : 'PASS';
    };
    check($chooseRing() === 'PASS', "P$seat preserves Ring without visible attack pressure");
    AddField(3-$seat, 'ugly4wiffe', Owner:3-$seat, Controller:3-$seat);
    $playerID = $seat; SetCurrentPhase('BREC');
    check($chooseRing() === 'PASS', "P$seat preserves Ring against one nonlethal ally");
    GABotOwnChampion($seat)->Damage = ObjectCurrentHP(GABotOwnChampion($seat)) - 1;
    check($chooseRing() === $choice, "P$seat taxes a single threatening ally at critical life");
    GABotOwnChampion($seat)->Damage = 0;
    AddField(3-$seat, 'ugly4wiffe', Owner:3-$seat, Controller:3-$seat);
    $playerID = $seat; SetCurrentPhase('BREC');
    GetField(3-$seat)[0]->Status = 1; GetField(3-$seat)[1]->Status = 1;
    check($chooseRing() === $choice, "P$seat taxes multiple allies before they wake");
    $otherQueue = &GetDecisionQueue(3-$seat); $otherQueue = [];
    [$legal, $action] = offer('MZMAYCHOOSE', $choice, 'OpportunityWindowSecondResponse', 'Take_a_fast_action?', $seat);
    check($action['cardID'] === $choice, "P$seat selects Ring through the legal response chooser");

    GetField(3-$seat)[1]->removed = true;
    check($chooseRing() === 'PASS', "P$seat excludes removed attackers");
    GetField(3-$seat)[1]->removed = false;
    AddEffectStack('xnrw8qq1uw', $seat);
    check($chooseRing() === 'PASS', "P$seat waits for pending Ring");
    $stack = &GetEffectStack(); $stack = [];
    AddGlobalEffects(3-$seat, 'xnrw8qq1uw');
    check($chooseRing() === 'PASS', "P$seat preserves Ring when a tax already applies");
    $effects = &GetGlobalEffects(3-$seat); $effects = [];
    SetTurnPlayer($seat);
    check($chooseRing() === 'PASS', "P$seat cannot use Ring in own recollection");
    SetTurnPlayer(3-$seat); SetCurrentPhase('MAIN');
    check($chooseRing() === 'PASS', "P$seat cannot use Ring after recollection");
}
// Rending Flames: threshold decision and legal three-card payment in both seats.
foreach ([1, 2] as $seat) {
    resetBoard($seat);
    AddField(3-$seat, 'LMyKyVC2O9', Owner:3-$seat, Controller:3-$seat);
    AddIntent($seat, 'soO3hjaVfN');
    AddGraveyard($seat, '7w4v1hgl3e');
    for ($i=0; $i<3; ++$i) AddGraveyard($seat, 'iohZMWh5v5');
    $playerID = $seat;
    DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval($seat));
    StoreCombatAttackerState('myField-0', $seat);
    StoreCombatTargetState('theirField-0', $seat);
    DecisionQueueController::StoreVariable('RendingFlamesSource', 'myField-0');
    $threat = GABotCombatContext($seat)['threat'];
    $target = GetField(3-$seat)[0];
    $target->Damage = ObjectCurrentHP($target) - $threat - 1;
    [$legal, $action] = offer('YESNO', '-', 'RendingFlamesConfirm', '', $seat);
    check($threat > 0 && $action['cardID'] === 'YES', "P$seat doubles Rending Flames across knockout threshold");
    $dq = new DecisionQueueController(); $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, 'YES');
    $legal = GABotLegalActions('decision-test', $seat);
    $payment = GABotChooseAction($legal['actions'], $legal);
    check(GameValidateDecisionAnswer($seat, $payment['cardID'])
        && $payment['cardID'] === 'myGraveyard-1&myGraveyard-2&myGraveyard-3', "P$seat pays three ordinary fire cards and retains March Hare");
    $dq->PopDecision($seat); $dq->ExecuteStaticMethods($seat, $payment['cardID']);
    check(GABotLiveCount(GetBanish($seat)) === 3 && AttackHasDamageDoubleEffect($seat), "P$seat payment banishes three cards and enables combat doubling");
    check(ApplyCombatDamageReplacements($seat, 'myField-0', $threat) === 2*$threat, "P$seat engine applies doubled combat damage");
    GetIntent($seat)[0]->TurnEffects = [];
    for ($i=0; $i<3; ++$i) AddGraveyard($seat, 'iohZMWh5v5');
    $target->Damage = ObjectCurrentHP($target) - $threat;
    check(GABotRendingFlamesScore($seat) < 0, "P$seat saves fuel when already lethal");
    $target->Damage = ObjectCurrentHP($target) - 2*$threat - 1;
    check(GABotRendingFlamesScore($seat) < 0, "P$seat saves fuel when doubling cannot cross threshold");
    $target->Damage = ObjectCurrentHP($target) - $threat - 1;
    $target->TurnEffects = ['BARRIER_PREVENT_DAMAGE'];
    check(GABotRendingFlamesScore($seat) < 0 && $target->TurnEffects === ['BARRIER_PREVENT_DAMAGE'], "P$seat declines barrier without consuming protection during scoring");
    $target->TurnEffects = [];
    DecisionQueueController::StoreVariable('CombatDamageAmount', '1');
    check(GABotRendingFlamesScore($seat) < 0, "P$seat does not boost damage already dealt");
}
echo "Checks: $checks; failures: $failures\n";
exit($failures ? 1 : 0);
