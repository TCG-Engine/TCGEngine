<?php
// Previously blank hosted definitions; exercise the normal generated dispatch.
gaScenario('dinah-lost-spirit-enter-draws-seven')
    ->standardDeck('Dungeon Guide')->seed('myField', 'Dinah, Lost Spirit')->observe()
    ->expectZoneCount('myHand', 14)->expectDqEmpty()->tested('Dinah, Lost Spirit')
    ->covers(['on-enter', 'draw'], 'On Enter: Draw seven cards.')->register();

gaScenario('lesser-boon-virelai-scavenges-harmony')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Lesser Boon of Virelai')
    ->deckTop(['Dungeon Guide', 'Empowering Harmony', 'Dungeon Guide'])
    ->seed('myHand', 'Lesser Boon of Virelai')
    ->castSpell('Lesser Boon of Virelai', pay: 3, window: '')
    ->expectCardExists('myHand', 'Empowering Harmony')->expectDqEmpty()
    ->tested('Lesser Boon of Virelai')->covers(['scavenge'], 'Scavenge 10 for a Harmony or Melody card.')->register();
