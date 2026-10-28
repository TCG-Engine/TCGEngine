<?php
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
require 'GrandArchiveSim/Custom/UIProjection.php';
$state = ['updateNumber' => 7, 'gTurnNumber' => 2, 'gTurnPlayer' => 1, 'gDecisionQueueVariables' => 'SECRET_INTERNAL'];
foreach ([1, 2] as $seat) {
    foreach (['Field', 'Intent', 'Graveyard', 'Banish', 'Hand', 'Memory', 'Material', 'Deck'] as $zone) {
        $state["p{$seat}{$zone}"] = [(object)['CardID' => "SECRET_{$seat}_{$zone}", 'Counters' => ['private' => 'SECRET_COUNTER'], 'Subcards' => ['SECRET_SUBCARD'], 'Status' => 1, 'Damage' => 3]];
    }
    $state["p{$seat}Banish"][] = (object)['Location' => 'Banish', 'CardID' => "SECRET_SEALED_$seat", 'Counters' => ['_sealFaceDown' => 1]];
    $state["p{$seat}Banish"][] = (object)['Location' => 'Banish', 'CardID' => "SECRET_SHADOW_$seat", 'Counters' => ['_shadowreaverFaceDown' => 1]];
    $state["p{$seat}Hand"][] = (object)['CardID' => 'SECRET_REMOVED', 'removed' => true];
}
$checks = 0;
function checkProjection($ok, $label) {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    ++$checks;
}
foreach ([1, 2, null] as $viewer) {
    $projection = GAUIProjectState($state, $viewer);
    $json = json_encode($projection);
    foreach ([1, 2] as $owner) {
        foreach (['Hand', 'Memory', 'Material', 'Deck'] as $zone) {
            $expected = $owner === $viewer && $zone !== 'Deck';
            checkProjection(str_contains($json, "SECRET_{$owner}_{$zone}") === $expected, 'Private visibility');
        }
        foreach (['Field', 'Intent', 'Graveyard', 'Banish'] as $zone) checkProjection(str_contains($json, "SECRET_{$owner}_{$zone}"), 'Public visibility');
        checkProjection($projection['players'][$owner - 1]['zones']['Hand']['count'] === 1, 'Removed cards excluded from count');
        checkProjection($projection['players'][$owner - 1]['zones']['Banish']['count'] === 3, 'Concealed banish counted');
    }
    foreach (['SECRET_INTERNAL', 'SECRET_COUNTER', 'SECRET_SUBCARD', 'SECRET_SEALED', 'SECRET_SHADOW', 'SECRET_REMOVED'] as $secret) checkProjection(!str_contains($json, $secret), 'Internal or concealed data omitted');
    checkProjection($projection['commands'] === [] && $projection['revision'] === 7, 'Read-only revision contract');
}
try { GAUIProjectState($state, 3); throw new RuntimeException('Invalid viewer accepted'); }
catch (InvalidArgumentException $e) { ++$checks; }
$state['currentPlayer'] = 1;
$state['p1DecisionQueue'] = [(object)['Type' => 'MZMAYCHOOSE', 'Tooltip' => 'Take_a_fast_action?', 'Param' => 'SECRET_OPTIONS']];
checkProjection(GAUIProjectState($state, 1)['attention']['kind'] === 'opportunity', 'Fast response window distinguished');
checkProjection(GAUICommands($state, 1)[0]['label'] === 'Pass priority', 'Optional opportunity has distinct pass label');
checkProjection(GAUICommands($state, 2) === [] && GAUICommands($state, null) === [], 'Only active seat gets commands');
checkProjection(GAUIProjectState($state, 2)['attention']['kind'] === 'waiting', 'Opponent receives no decision context');
checkProjection(GAUIProjectState($state, null)['attention']['kind'] === 'spectating', 'Spectator receives no decision context');
foreach (['MZCHOOSE', 'MZMAYCHOOSE', 'MZMULTICHOOSE', 'MZREARRANGE', 'CUSTOM', 'SECRET_UNKNOWN_TYPE'] as $type) {
    $state['p1DecisionQueue'] = [(object)['Type' => $type, 'Tooltip' => 'SECRET_TOOLTIP', 'Param' => 'SECRET_OPTIONS']];
    $projection = GAUIProjectState($state, 1);
    checkProjection($projection['attention']['kind'] === 'decision', 'Pending choice described');
    checkProjection(count($projection['commands']) === ($type === 'MZMAYCHOOSE' ? 1 : 0), 'Only optional choices may be declined');
    foreach (['SECRET_TOOLTIP', 'SECRET_OPTIONS', 'SECRET_UNKNOWN_TYPE'] as $secret) checkProjection(!str_contains(json_encode($projection), $secret), 'Decision internals omitted');
}
$state['p1DecisionQueue'][0]->removed = true;
checkProjection(GAUIProjectState($state, 1)['attention']['kind'] === 'active', 'Removed decisions ignored');
$state['p1DecisionQueue'][] = (object)['Type' => 'MZCHOOSE'];
checkProjection(GAUIProjectState($state, 1)['attention']['kind'] === 'decision', 'First live decision selected');
checkProjection(GAUICommands($state, 1) === [], 'Removed queue head cannot produce executable command');

foreach ([1, 2] as $seat) {
    $state['currentPlayer'] = $seat;
    $state['p' . $seat . 'DecisionQueue'] = [(object)['Type' => 'MZCHOOSE', 'Param' => 'myHand&theirField-0&myHand-0']];
    $choices = GAUIProjectState($state, $seat)['choices'];
    checkProjection(count(GAUIProjectState($state, $seat)['commands']) === 2, 'Visible mandatory options become commands');
    checkProjection(count($choices) === 2, 'Visible ordinary pool deduplicated; removed cards omitted');
    checkProjection($choices[0]['owner'] === $seat && $choices[1]['owner'] === 3 - $seat, 'Selectors relative to either viewer');
    checkProjection(GAUIProjectState($state, null)['choices'] === [] && GAUIProjectState($state, 3 - $seat)['choices'] === [], 'Only chooser sees pool');
    foreach (['theirHand', 'theirMemory', 'theirMaterial', 'myDeck', 'myBanish', 'myBanish-1', 'myHand-1', 'myHand-99', 'myField-0@Activate-0', 'myField-0:filter', 'myTempZone', 'myField-0.u1', ''] as $unsupported) {
        $state['p' . $seat . 'DecisionQueue'][0]->Param = 'myHand-0&' . $unsupported;
        checkProjection(GAUIProjectState($state, $seat)['choices'] === [], 'Unsupported or concealed pool suppressed completely');
    }
    $state['p' . $seat . 'DecisionQueue'][0]->Param = 'myHand';
    $state['p' . $seat . 'DecisionQueue'][0]->Type = 'MZMAYCHOOSE';
    $state['p' . $seat . 'DecisionQueue'][0]->Tooltip = 'Take_a_fast_action?';
    checkProjection(GAUIProjectState($state, $seat)['choices'] === [], 'Fast actions need their own ability context');
    $state['p' . $seat . 'DecisionQueue'][0]->removed = true;
    checkProjection(GAUIProjectState($state, $seat)['choices'] === [], 'Removed head has no pool');
}
$state['gEffectStack'] = [
    (object)['CardID' => 'PUBLIC_ACTIVATION', 'Controller' => 1, 'TriggerType' => '-'],
    (object)['CardID' => 'SECRET_REMOVED_EFFECT', 'removed' => true],
    (object)['CardID' => 'PUBLIC_TRIGGER', 'Controller' => 2, 'TriggerType' => 'SECRET_TRIGGER_TYPE',
        'Counters' => ['captured' => 'SECRET_CAPTURED'], 'TriggerCopiedCardID' => 'SECRET_COPY',
        'TriggerSourceUniqueID' => 'SECRET_SOURCE', 'TurnEffects' => ['SECRET_EFFECT']],
];
foreach ([1, 2, null] as $viewer) {
    $stack = GAUIProjectState($state, $viewer)['effectStack'];
    checkProjection(array_column($stack, 'id') === ['EffectStack-2', 'EffectStack-0'], 'Live stack in resolution order, preserving indices');
    checkProjection(array_column($stack, 'controller') === [2, 1], 'Public controllers');
    checkProjection(array_column($stack, 'kind') === ['Triggered ability', 'Activation'], 'Fixed stack categories');
    checkProjection(array_column($stack, 'cardID') === ['PUBLIC_TRIGGER', 'PUBLIC_ACTIVATION'], 'Public stack identity visible to every viewer');
    checkProjection(!str_contains(json_encode($stack), 'SECRET_'), 'Stack internals never serialized');
}
checkProjection(GAUIProjectEffectStack([]) === [], 'Missing stack is empty');
$state['gEffectStack'] = [(object)['CardID' => 'PUBLIC_UNKNOWN', 'Controller' => 99]];
checkProjection(GAUIProjectEffectStack($state)[0]['controller'] === null, 'Unknown controller is not misattributed');
$effectData['PUBLIC_REFERENCE'] = '**On Enter**<br />Draw a card.<br>&lt;reference&gt;';
$state['gEffectStack'] = [(object)['CardID' => 'PUBLIC_REFERENCE', 'TriggerCopiedCardID' => 'SECRET_COPY', 'Counters' => ['text' => 'SECRET_CAPTURED']]];
foreach ([1, 2, null] as $viewer) {
    $entry = GAUIProjectState($state, $viewer)['effectStack'][0];
    checkProjection($entry['printedText'] === "**On Enter**\nDraw a card.\n<reference>", 'Static printed text normalized for every viewer');
    checkProjection(!str_contains(json_encode($entry), 'SECRET'), 'Printed reference never reads copied identity or captured text');
}
checkProjection(GAUIPrintedCardText('UNKNOWN_REFERENCE') === '', 'Missing reference has an explicit empty fallback');
foreach (['ENTER' => 'On Enter', 'ON_ATTACK' => 'On Attack', 'ON_HIT' => 'On Hit', 'ON_KILL' => 'On Kill',
    'BREW' => 'Brew', 'WITHER' => 'Wither', 'ALLY_DESTROYED' => 'Ally destroyed', 'LEAVE_FIELD' => 'Leave field',
    'ON_BANISH' => 'Card banished', 'DISCARD_CARD' => 'Card discarded', 'REVEAL_CARD' => 'Card revealed',
    'PLAY_CARD' => 'Card played', 'REST_CARD' => 'Card rested', 'DEAL_DAMAGE' => 'Damage dealt'] as $type => $label) {
    $state['gEffectStack'][0]->TriggerType = $type;
    foreach ([1, 2, null] as $viewer) {
        $entry = GAUIProjectState($state, $viewer)['effectStack'][0];
        checkProjection($entry['triggerLabel'] === $label, 'Public trigger event described for every viewer');
        checkProjection(!str_contains(json_encode($entry), 'SECRET'), 'Trigger description never includes captured or copied details');
    }
}
foreach (['', '-', 'DTR_EVENT', 'SECRET_UNKNOWN', null, ['SECRET_BAD_TAG']] as $type) {
    $state['gEffectStack'][0]->TriggerType = $type;
    $entry = GAUIProjectEffectStack($state)[0];
    checkProjection($entry['triggerLabel'] === null, 'Unknown, activation and compound event tags have no inferred label');
    checkProjection(!str_contains(json_encode($entry), 'SECRET'), 'Unknown tag remains private');
}
$effectData['SECRET_1_Hand'] = 'PRIVATE_PRINTED_REFERENCE';
foreach ([1, 2, null] as $viewer) {
    $projection = GAUIProjectState($state, $viewer);
    checkProjection(str_contains(json_encode($projection), 'PRIVATE_PRINTED_REFERENCE') === ($viewer === 1), 'Printed text follows card visibility');
}
echo "$checks projection checks passed\n";
