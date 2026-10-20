<?php
/**
 * Phase 3e (Luxem), the cards left over after luxem.php: Auspicious Manifestation.
 * Guo Jia, Chosen Disciple is the champion (Guo Jia Bonus); Zander, Blinding Steel in the lineage enables luxem.
 */

// ---- Auspicious Manifestation ----

gaScenario('auspicious-manifestation-banishes-a-shenju-ally-and-returns-it-at-the-next-end-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Guo Jia, Chosen Disciple', lineage: ['Zander, Blinding Steel'])
    ->seed('myField', 'Suzaku, Vermillion Phoenix')
    ->seed('myHand', 'Auspicious Manifestation')
    ->play('Auspicious Manifestation')->pay(2)->answer(1, 'myField-1')->passBoth()
    ->expectZoneCount('myBanish', 1, 1, 'Suzaku was banished', step: 4)
    ->expectZoneCount('myField', 1, 1, 'Only the champion is left on the field until the end phase', step: 4)
    ->endTurn(1)
    ->expectCardExists('myField', 'Fabled Ruby Fatestone', 'Suzaku returned transformed at the end phase')
    ->tested('Auspicious Manifestation')
    ->covers(['zone-movement', 'transform', 'guo-jia-bonus'], '[Guo Jia Bonus] Banish a Shenju ally you control. Return that ally to the field under its owner\'s control transformed at the beginning of the next end phase.')
    ->register();

gaScenario('auspicious-manifestation-discarded-for-two-draws-a-card-and-adds-a-quest-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Guo Jia, Chosen Disciple', lineage: ['Zander, Blinding Steel'])
    ->seed('myHand', 'Auspicious Manifestation')
    ->action(1, 10001, 'myHand-7!CustomInput!Activate:0')->pay(2)
    ->expectZoneCount('myHand', 6, 1, 'Discarded the card, paid two, drew one: 8 - 1 - 2 + 1')
    ->expectProperty('p1Field-0', 'Counters', '{"quest":1}', 'A quest counter on the champion')
    ->tested('Auspicious Manifestation')
    ->covers(['discard', 'draw-discard', 'quest', 'guo-jia-bonus'], '[Guo Jia Bonus] (2), **Discard this card from your hand:** Draw a card. Then if your champion has five or less **quest** counters on them, put a **quest** counter on them.')
    ->register();
