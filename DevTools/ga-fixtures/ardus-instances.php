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
