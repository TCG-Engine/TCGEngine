<?php
/**
 * Phase 3b (Wind): Spirit champions. Same shapes as the Fire spirits in fire-allies-d.php: a champion seeded onto the field runs its On Enter, and the
 * glimpse answer lists the top cards of the standard deck (Fairy Whispers are the fast cards, hence the n8wyfG9hbY ids).
 */

foreach (['Brissa, Spirit of Wind', 'Gwendolyn, Spirit of Wind', 'Kaze, Spirit of Wind'] as $gaSpirit) {
    $gaSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($gaSpirit)), '-');
    gaScenario("$gaSlug-enter-draws-seven-cards")
        ->standardDeck('Dungeon Guide')
        ->seed('myField', $gaSpirit)
        ->observe()
        ->expectZoneCount('myHand', 14, 1, 'On Enter: drew seven cards on top of the opening seven')
        ->tested($gaSpirit)
        ->covers(['on-enter', 'draw', 'champion'], '**On Enter:** Draw seven cards.')
        ->register();
}

gaScenario('fragmented-spirit-of-wind-enter-glimpses-draws-six-and-summons-a-spirit-shard')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Fragmented Spirit of Wind')
    ->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,px60u5n1do,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 13, 1, 'Drew six cards on top of the opening seven')
    ->expectCardExists('myField', 'Spirit Shard', 'Summoned a Spirit Shard token')
    ->tested('Fragmented Spirit of Wind')
    ->covers(['on-enter', 'glimpse', 'draw', 'summon', 'champion'], '**On Enter:** **Glimpse 6**. Draw six cards. Then **summon** a Spirit Shard token.')
    ->register();

gaScenario('spirit-of-fortuitous-wind-enter-glimpses-and-draws-seven-into-memory')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Spirit of Fortuitous Wind')
    ->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,px60u5n1do,em6eEh9q8y,n8wyfG9hbY;Bottom=')
    ->expectZoneCount('myMemory', 7, 1, 'Drew seven cards into memory')
    ->expectZoneCount('myHand', 7, 1, 'The hand is untouched: the cards went to memory')
    ->tested('Spirit of Fortuitous Wind')
    ->covers(['on-enter', 'glimpse', 'draw-into-memory', 'champion'], '**On Enter**: **Glimpse 7**. Draw seven cards into your memory.')
    ->register();

gaScenario('spirit-of-serene-wind-enter-glimpses-and-draws-six')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Spirit of Serene Wind')
    ->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,px60u5n1do,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 13, 1, 'Drew six cards on top of the opening seven')
    ->tested('Spirit of Serene Wind')
    ->covers(['on-enter', 'glimpse', 'draw', 'champion'], '**On Enter:** **Glimpse 6**. Draw six cards.')
    ->register();

gaScenario('spirit-of-serene-wind-lineage-release-recovers-six')
    ->standardDeck('Dungeon Guide')
    ->champion(lineage: ['Spirit of Serene Wind'], damage: 8)
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')
    ->expectDamage('p1Field-0', 2, 'Lineage Release -- Recover 6: 8 damage down to 2')
    ->tested('Spirit of Serene Wind')
    ->covers(['lineage-release', 'recover'], '**Lineage Release** -- **Recover 6**.')
    ->register();
