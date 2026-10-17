<?php
foreach([['dawn-dual-norm', 'Dawn of Ashes', 'Mordred, Fated Luminary'], ['sanctuary-dual-water', 'Prismatic Sanctuary', 'Eminent Lethargy']] as [$suffix, $domain, $memory]) {
    gaScenario('domain-upkeep-' . $suffix)
        ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', $domain)->seed('myMemory', $memory)
        ->endTurn()->endTurn(player: 2)->answer(1, 'PASS')
        ->expectCardExists('myField', $domain, 'The revealed dual element satisfies the upkeep membership check')
        ->tested($domain)->covers(['upkeep', 'reveal', 'element'], 'A matching basic or Norm element remains present on a dual-element card.')->register();
}
