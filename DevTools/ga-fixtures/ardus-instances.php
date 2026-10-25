<?php
/**
 * Ardus, Floodborne Deacon: "Cards you activate, objects you control, and intents you control have an additional instance of each deluge ability and effect they have."
 * The threshold is the real water count in the graveyard; each deluge effect applies once per instance (DelugeInstances), so Ardus doubles the effect, he does not lower the threshold.
 */

const GA_ARDUS_TEXT = 'Cards you activate, objects you control, and intents you control have an additional instance of each **deluge** ability and effect they have.';

gaScenario('ardus-doubles-a-deluge-stat-bonus')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Floodborne Warrior')
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->seed('myField', 'Ardus, Floodborne Deacon')
    ->seed('myField', 'Floodborne Warrior')
    ->observe()
    ->expectPower('p1Field-2', 3, 'Deluge 2: +1 POWER twice on top of the printed 1')
    ->expectLife('p1Field-2', 5, 'Deluge 2: +1 LIFE twice on top of the printed 3')
    ->tested('Ardus, Floodborne Deacon')
    ->covers(['static', 'deluge', 'stat-change'], GA_ARDUS_TEXT)
    ->register();

gaScenario('ardus-does-not-lower-the-deluge-threshold')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Floodborne Warrior')
    ->seed('myGraveyard', 'Giant Tortoise')
    ->seed('myField', 'Ardus, Floodborne Deacon')
    ->seed('myField', 'Floodborne Warrior')
    ->observe()
    ->expectPower('p1Field-2', 1, 'One water card is below Deluge 2: no bonus even with Ardus')
    ->expectLife('p1Field-2', 3, 'One water card is below Deluge 2: no bonus even with Ardus')
    ->tested('Ardus, Floodborne Deacon')
    ->covers(['static', 'deluge', 'condition'], GA_ARDUS_TEXT)
    ->register();

gaScenario('ardus-doubles-the-deluge-cost-reduction')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Disciple of the Waves')
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->seed('myField', 'Ardus, Floodborne Deacon')
    ->seed('myHand', 'Disciple of the Waves')
    ->castSpell('Disciple of the Waves', pay: 1, window: 'opp')
    ->expectCardExists('myField', 'Disciple of the Waves', 'Deluge 3 twice: cost 3 - 2 = 1')
    ->tested('Ardus, Floodborne Deacon')
    ->covers(['static', 'deluge', 'cost-reduction'], GA_ARDUS_TEXT)
    ->register();

gaScenario('ardus-doubles-the-deluge-half-of-conniving-plans')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Conniving Plans', classBonus: true)
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->seed('myField', 'Ardus, Floodborne Deacon')
    ->seed('myHand', 'Conniving Plans')
    ->castSpell('Conniving Plans', pay: 2, window: 'both')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"ASSASSIN"},"preparation":2}', 'Two instances: two preparation counters')
    ->expectZoneCount('myMemory', 4, 1, '2 paid + 2 drawn into memory')
    ->tested('Ardus, Floodborne Deacon')
    ->covers(['static', 'deluge', 'preparation', 'draw-into-memory'], GA_ARDUS_TEXT)
    ->register();

gaScenario('ardus-doubles-treasure-of-the-depths-end-phase-trigger')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Ardus, Floodborne Deacon')
    ->seed('myField', 'Treasure of the Depths')
    ->seed('myGraveyard', 'Baby Blue Slime')->seed('myGraveyard', 'Baby Blue Slime')->seed('myGraveyard', 'Baby Blue Slime')
    ->endTurn()
    ->expectProperty('p1Field-2', 'Counters', '{"refinement":2}', 'Two instances: two refinement counters in one end phase')
    ->tested('Ardus, Floodborne Deacon')
    ->covers(['static', 'deluge', 'trigger'], GA_ARDUS_TEXT)
    ->register();

gaScenario('ardus-liquidation-deals-seven-damage-twice')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Liquidation')
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->seed('myField', 'Ardus, Floodborne Deacon')
    ->seed('myHand', 'Liquidation')
    ->castSpell('Liquidation', pay: 2, target: 'theirField-0')->answer(1, 'theirField-0')
    ->expectDamage('p2Field-0', 14, 'Two instances: 7 damage twice')
    ->tested('Ardus, Floodborne Deacon')
    ->covers(['static', 'deluge', 'damage'], GA_ARDUS_TEXT)
    ->register();

// Kill each ally through normal spell resolution; its own water card counts toward deluge.
foreach ([
    ['dynasty-chancellor', 'Dynasty Chancellor', 2, 0, 7],
    ['drowned-exorcist', 'Drowned Exorcist', 7, 3, 9],
    ['mistsworn-magister', 'Mistsworn Magister', 2, 2, null],
] as [$slug, $ally, $water, $damage, $hand]) {
    $s = gaScenario('ardus-doubles-' . $slug . '-on-death')
        ->standardDeck('Aenean Spark Alight')->readyFor('Aenean Spark Alight')
        ->seed('myField', 'Ardus, Floodborne Deacon');
    for ($i = 0; $i < $water; ++$i) $s->seed('myGraveyard', 'Giant Tortoise');
    $s->seed('myField', $ally, properties: ['Damage' => $damage])
      ->seed('myHand', 'Aenean Spark Alight')
      ->castSpell('Aenean Spark Alight', pay: 2, window: 'both', target: 'myField-2')
      ->expectCardExists('myGraveyard', $ally, 'The ally died through spell damage');
    if ($hand !== null) $s->expectZoneCount('myHand', $hand, 1, 'Both deluge On Death draws resolved');
    else $s->expectProperty('p1Field-0', 'Counters', '{"enlighten":4}', 'Two instances put four enlighten counters on the champion');
    $s->expectDqEmpty()->tested('Ardus, Floodborne Deacon')->covers(['deluge', 'on-death'], GA_ARDUS_TEXT)->register();
}

foreach ([1, 2] as $weapons) {
    $s = gaScenario('ardus-brookside-scout-loads-twice-with-' . $weapons . '-weapons')
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Brookside Scout')
        ->seed('myField', 'Ardus, Floodborne Deacon')
        ->seed('myField', "Seeker's Aetherwing");
    if ($weapons === 2) $s->seed('myField', "Legion's Wingspan");
    $s->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
      ->seed('myGraveyard', 'Stifling Aethercharge')->seed('myGraveyard', 'Recurring Aethercharge')
      ->seed('myField', 'Brookside Scout')
      ->answer(1, 'myGraveyard-6');
    if ($weapons === 2) $s->answer(1, 'myField-2')
        ->expectProperty('p1DecisionQueue-0', 'Param', 'myGraveyard-6', 'Only the remaining charge is offered after the weapon choice', step: 2)
        ->expectProperty('p1DecisionQueue-2', 'Type', 'CUSTOM', 'The next instance waits for the weapon choice instead of offering another charge early', step: 1);
    $s->answer(1, 'myGraveyard-6'); // the first load compacted the graveyard
    if ($weapons === 2) $s->answer(1, 'myField-3');
    $s->expectProperty('p1Field-2', 'Subcards', $weapons === 1 ? '["bYrLVjKSCL","MG8QoeZBXY"]' : '["bYrLVjKSCL"]', 'The first weapon received the selected charge(s)');
    if ($weapons === 2) $s->expectProperty('p1Field-3', 'Subcards', '["MG8QoeZBXY"]', 'The second charge was selected after the first load completed');
    $s->expectZoneCount('myGraveyard', 6)->expectDqEmpty()
      ->tested('Ardus, Floodborne Deacon')->covers(['deluge', 'on-enter', 'load'], GA_ARDUS_TEXT)->register();
}
