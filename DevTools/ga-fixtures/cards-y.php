<?php
/**
 * Fixtures for cards starting with 'Y', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Young Peacekeeper: Foster; gets +1 POWER and +1 LIFE as long as it's fostered ---
// Same reasoning as imperial-recruit-fostered-power: "Fostered" is a plain persistent
// TurnEffect, so the static +1 POWER/+1 LIFE while fostered can be tested directly by
// patching TurnEffects at setup.
// Notes carried over from the original definition:
//   - Young Peacekeeper, fostered
//   - harmless no-op click
gaScenario('young-peacekeeper-fostered-power-life')
    ->standardDeck('Young Peacekeeper')
    ->seed('myField', 'Young Peacekeeper', properties: ['TurnEffects' => ['FOSTERED']])
    ->target('myField-0')
    ->tested('Young Peacekeeper')
    ->register();

// Yuan Shao, Crown General (x8o84m37ti): "[Level 2+] (3), [REST]: As a Spell, gain control of target unique ally. Activate this ability only if there are three or more unique allies on the field you don't control."
// Notes carried over from the original definition:
//   - Yuan Shao -> myField-1
//   - three unique opposing allies -> theirField-1..3
gaScenario('yuan-shao-pays-three-rests-and-gains-control-of-a-unique-ally')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(level: 2)
    ->seed('myField', 'Yuan Shao, Crown General')
    ->seed('myField', 'Gawain, Chivalrous Thief', player: 2)
    ->seed('myField', 'Morgan, Soul Guide', player: 2)
    ->seed('myField', 'Parcenet, Royal Maid', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->target('theirField-1')
    ->tested('Yuan Shao, Crown General', 'Gawain, Chivalrous Thief', 'Morgan, Soul Guide', 'Parcenet, Royal Maid')
    ->expectProperty('p2Field-1', 'Controller', '1', 'Gawain, Chivalrous Thief is now controlled by player 1')
    ->expectProperty('p1Field-1', 'Status', '1', '[REST] was paid: Yuan Shao is rested')
    ->expectZoneCount('myMemory', 3, 1, '(3) was paid')
    ->covers(['activated-ability', 'gain-control', 'cost', 'condition'], '[Level 2+] (3), [REST]: As a Spell, gain control of target unique ally. Activate this ability only if there are three or more unique allies on the field you don\'t control.')
    ->register();

gaScenario('yuan-shao-refused-with-only-two-unique-opposing-allies')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(level: 2)
    ->seed('myField', 'Yuan Shao, Crown General')
    ->seed('myField', 'Gawain, Chivalrous Thief', player: 2)
    ->seed('myField', 'Morgan, Soul Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->tested('Yuan Shao, Crown General', 'Gawain, Chivalrous Thief', 'Morgan, Soul Guide')
    ->expectProperty('p1Field-1', 'Status', '2', 'Yuan Shao stays awake: the ability was refused before any cost')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->expectProperty('p2Field-1', 'Controller', '2', 'The opposing Gawain keeps its owner as controller')
    ->covers(['activated-ability', 'condition', 'cost-validation'], 'Activate this ability only if there are three or more unique allies on the field you don\'t control.')
    ->register();
