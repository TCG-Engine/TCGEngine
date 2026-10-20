<?php
// Select the actual level-zero champion at startup; no champion is seeded.

gaScenario('pregame-fragmented-fire-opening')
    ->deck(<<<'DECK'
# Material
1 Fragmented Spirit of Fire
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
DECK
    )
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')->answer(2, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 6, 1, 'Player 1 receives only the printed opening hand')
    ->expectZoneCount('myMemory', 0, 1, 'Player 1 receives the printed opening memory')
    ->expectCardExists('myField', 'Spirit Shard', 'Player 1 summons the opening Spirit Shard', viewer: 1)
    ->expectZoneCount('myHand', 6, 2, 'Player 2 receives only the printed opening hand')
    ->expectZoneCount('myMemory', 0, 2, 'Player 2 receives the printed opening memory')
    ->expectCardExists('myField', 'Spirit Shard', 'Player 2 summons the opening Spirit Shard', viewer: 2)
    ->expectDqEmpty()->tested('Fragmented Spirit of Fire')->covers(['pregame', 'on-enter', 'opening-hand'], 'The starting champion resolves its own printed On Enter once.')->register();

gaScenario('pregame-fortuitous-fire-opening')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fortuitous Fire
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
DECK
    )
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')->answer(2, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 0, 1, 'Player 1 receives only the printed opening hand')
    ->expectZoneCount('myMemory', 7, 1, 'Player 1 receives the printed opening memory')
    ->expectZoneCount('myHand', 0, 2, 'Player 2 receives only the printed opening hand')
    ->expectZoneCount('myMemory', 7, 2, 'Player 2 receives the printed opening memory')
    ->expectDqEmpty()->tested('Spirit of Fortuitous Fire')->covers(['pregame', 'on-enter', 'opening-hand'], 'The starting champion resolves its own printed On Enter once.')->register();

gaScenario('pregame-serene-fire-opening')
    ->deck(<<<'DECK'
# Material
1 Spirit of Serene Fire
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
4 Dungeon Guide
DECK
    )
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')->answer(2, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 6, 1, 'Player 1 receives only the printed opening hand')
    ->expectZoneCount('myMemory', 0, 1, 'Player 1 receives the printed opening memory')
    ->expectZoneCount('myHand', 6, 2, 'Player 2 receives only the printed opening hand')
    ->expectZoneCount('myMemory', 0, 2, 'Player 2 receives the printed opening memory')
    ->expectDqEmpty()->tested('Spirit of Serene Fire')->covers(['pregame', 'on-enter', 'opening-hand'], 'The starting champion resolves its own printed On Enter once.')->register();
