<?php
gaScenario('duplicitous-replication-copies-an-opposing-regalia-returning-from-suppression')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Spirit of Wind', 'Lorraine, Crux Knight'])
    ->seed('theirField', 'Training Sword')
    ->seed('myHand', 'Duplicitous Replication')->seed('myHand', 'Zephyr')
    ->play('Duplicitous Replication')->pay(2)->pass(1)
    ->play('Zephyr')->pay(2)->answer(1, 'theirField-1')->endTurn()
    ->expectCardExists('myField', 'Training Sword', 'An opposing end-phase return triggers the delayed copy')
    ->expectCardExists('theirField', 'Training Sword', 'The original returns under its owner control')
    ->expectZoneCount('myField', 2, 1, 'Exactly one token copy was summoned')
    ->tested('Duplicitous Replication')->covers(['delayed-trigger', 'regalia', 'token-copy', 'zone-movement'], 'The next regalia entry is covered even when it is not materialization.')->register();
