<?php
/**
 * Phase 3d (Norm): actions, fifth batch (glimpse, gather, regalia-dependent and choice actions). 'quiet' deck.
 */

// ---- Armed and Dangerous ----

gaScenario('armed-and-dangerous-draws-a-card-for-each-gun-weapon')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Armed and Dangerous')
    ->seed('myField', 'Prototype Pistol')
    ->seed('myField', 'Blastshot Pump')
    ->seed('myHand', 'Armed and Dangerous')
    ->castSpell('Armed and Dangerous', pay: 3, window: '')
    ->expectZoneCount('myHand', 6, 1, 'Two Gun weapons: two cards drawn (8 - 1 - 3 + 2)')
    ->tested('Armed and Dangerous')
    ->covers(['draw', 'condition'], 'Draw a card for each of up to two Gun weapons you control.')
    ->register();

// ---- Clear Pastures ----

gaScenario('clear-pastures-equestrian-with-a-horse-glimpses-three-then-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Clear Pastures')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myField', 'Ordinary Horse')
    ->seed('myHand', 'Clear Pastures')
    ->castSpell('Clear Pastures', pay: 1, window: '')->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myMemory', 2, 1, 'One paid plus one drawn into memory')
    ->tested('Clear Pastures')
    ->covers(['equestrian', 'glimpse', 'draw-into-memory', 'condition'], '***Equestrian*** — If you control a Horse ally, **glimpse 3**.', 'Draw a card into your memory.')
    ->register();

gaScenario('clear-pastures-without-a-horse-only-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Clear Pastures')
    ->seed('myHand', 'Clear Pastures')
    ->castSpell('Clear Pastures', pay: 1, window: '')
    ->expectZoneCount('myMemory', 2, 1, 'One paid plus one drawn into memory, with no glimpse prompt')
    ->tested('Clear Pastures')
    ->covers(['equestrian', 'condition', 'draw-into-memory'], '***Equestrian*** — If you control a Horse ally, **glimpse 3**.')
    ->register();

// ---- Forbidden Teachings ----

gaScenario('forbidden-teachings-with-a-book-glimpses-three-then-draws')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Forbidden Teachings')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myField', 'Worn Diary')
    ->seed('myHand', 'Forbidden Teachings')
    ->castSpell('Forbidden Teachings', pay: 2, window: 'me')->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 6, 1, 'A card was drawn (8 - 1 - 2 + 1)')
    ->tested('Forbidden Teachings')
    ->covers(['glimpse', 'draw', 'condition'], 'If you control a Book object, **glimpse 3**.', 'Draw a card.')
    ->register();

// ---- Scientific Discoveries ----

gaScenario('scientific-discoveries-glimpses-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Scientific Discoveries')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myHand', 'Scientific Discoveries')
    ->castSpell('Scientific Discoveries', pay: 1, window: '')
    ->rejects(1, 'Top=em6eEh9q8y,em6eEh9q8y;Bottom=', 'Glimpse 3: arranging only two cards is refused', 100)
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->tested('Scientific Discoveries')
    ->covers(['glimpse'], '**Glimpse 3**.')
    ->register();

// ---- Nurture Crops ----

gaScenario('nurture-crops-glimpses-two-then-gathers-twice')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Nurture Crops')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide'])
    ->seed('myHand', 'Nurture Crops')
    ->castSpell('Nurture Crops', pay: 2, window: '')->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myField', 3, 1, 'Gather twice: two plant tokens beside the champion')
    ->tested('Nurture Crops')
    ->covers(['glimpse', 'gather', 'summon'], '**Glimpse 2**, then **gather** twice.')
    ->register();

// ---- Rictus Tiding ----

gaScenario('rictus-tiding-two-cards-totalling-eight-both-go-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Rictus Tiding')
    ->deckTop(['Eight of Spades', 'Woodland Squirrels'])
    ->seed('myHand', 'Rictus Tiding')
    ->castSpell('Rictus Tiding', pay: 2, window: '')
    ->expectZoneCount('myMemory', 4, 1, 'Reserve costs 8 + 0 = 8: both revealed cards went into memory (2 paid + 2)')
    ->tested('Rictus Tiding')
    ->covers(['reveal', 'zone-movement', 'condition'], 'Reveal the top two cards of your deck. If the total reserve cost of the revealed cards is 8, put both of them into your memory.')
    ->register();

gaScenario('rictus-tiding-any-other-total-puts-one-into-memory-and-one-on-the-bottom')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Rictus Tiding')
    ->deckTop(['Eight of Spades', 'Dungeon Guide'])
    ->seed('myHand', 'Rictus Tiding')
    ->castSpell('Rictus Tiding', pay: 2, window: '')->answer(1, 'myTempZone-0')
    ->expectZoneCount('myMemory', 3, 1, 'Total 9: only one revealed card went into memory (2 paid + 1)')
    ->tested('Rictus Tiding')
    ->covers(['reveal', 'zone-movement', 'condition'], 'Otherwise put one of them into your memory and the other on the bottom of your deck.')
    ->register();

// ---- Hidden Secrets ----

gaScenario('hidden-secrets-without-a-domain-choosing-stealth-gives-an-ally-stealth')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hidden Secrets')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Hidden Secrets')
    ->castSpell('Hidden Secrets', pay: 2, window: '')->answer(1, 'YES')->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","STEALTH"]', 'The ally gained stealth until end of turn')
    ->expectZoneCount('myMemory', 2, 1, 'Only one mode: no card drawn into memory')
    ->tested('Hidden Secrets')
    ->covers(['modal', 'stealth', 'targeting'], 'Up to one target ally gains **stealth** until end of turn.')
    ->register();

gaScenario('hidden-secrets-without-a-domain-choosing-to-draw-puts-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hidden Secrets')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Hidden Secrets')
    ->castSpell('Hidden Secrets', pay: 2, window: '')->answer(1, 'NO')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'No stealth')
    ->expectZoneCount('myMemory', 3, 1, 'Two paid plus one drawn into memory')
    ->tested('Hidden Secrets')
    ->covers(['modal', 'draw-into-memory'], 'Choose one. If you control one or more domains, choose up to two instead—')
    ->register();

// ---- Study the Fables ----

gaScenario('study-the-fables-targeting-yourself-reveals-your-material-cards-and-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Study the Fables')
    ->seed('myHand', 'Study the Fables')
    ->castSpell('Study the Fables', pay: 2, window: '')->answer(1, 'YES')
    ->expectZoneCount('myMemory', 3, 1, 'Two paid plus one drawn into memory')
    ->tested('Study the Fables')
    ->covers(['reveal', 'draw-into-memory', 'targeting'], 'Target player reveals six cards from their material deck. Draw a card into your memory.')
    ->register();

gaScenario('study-the-fables-targeting-the-opponent-still-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Study the Fables')
    ->seed('myHand', 'Study the Fables')
    ->castSpell('Study the Fables', pay: 2, window: '')->answer(1, 'NO')
    ->expectZoneCount('myMemory', 3, 1, 'Two paid plus one drawn into memory')
    ->tested('Study the Fables')
    ->covers(['reveal', 'draw-into-memory', 'targeting'], 'Target player reveals six cards from their material deck.')
    ->register();

// ---- Table Straight ----

gaScenario('table-straight-without-suited-cards-draws-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Table Straight')
    ->seed('myHand', 'Table Straight')
    ->castSpell('Table Straight', pay: 3, window: '')
    ->expectZoneCount('myHand', 4, 1, 'No Suited cards: nothing drawn (8 - 1 - 3)')
    ->tested('Table Straight')
    ->covers(['reveal', 'draw', 'condition'], 'Reveal any amount of Suited cards from your memory.')
    ->register();

// ---- Unforgotten Will ----

gaScenario('unforgotten-will-banishes-itself-as-an-omen-and-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Unforgotten Will')
    ->seed('myHand', 'Unforgotten Will')
    ->castSpell('Unforgotten Will', pay: 3, window: '')
    ->expectCardExists('myBanish', 'Unforgotten Will', 'The card banished itself and became an omen')
    ->expectZoneCount('myMemory', 4, 1, 'Three paid plus one drawn into memory')
    ->tested('Unforgotten Will')
    ->covers(['banish-self', 'counter', 'draw-into-memory'], 'Banish CARDNAME and put an **omen** counter on it. If you do, draw a card into your memory.')
    ->register();

// ---- Unmake Duality ----

gaScenario('unmake-duality-sacrificing-a-divine-relic-regalia-draws-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Unmake Duality')
    ->seed('myField', 'Art of War')
    ->seed('myHand', 'Unmake Duality')
    ->play('Unmake Duality')->answer(1, 'myField-1')->pay(3)
    ->expectZoneCount('myHand', 6, 1, 'Two cards drawn (8 - 1 - 3 + 2)')
    ->tested('Unmake Duality')
    ->covers(['additional-cost', 'sacrifice', 'draw'], 'As an additional cost to activate this card, sacrifice a regalia with **divine relic**.', 'Draw two cards.')
    ->register();

// ---- Exorcism ----

gaScenario('exorcism-sacrifices-an-ephemeral-object-unless-its-controller-pays-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Exorcism')
    ->seed('myField', 'Haunting Apparition', properties: ['TurnEffects' => ['EPHEMERAL']])
    ->seed('myHand', 'Exorcism')
    ->castSpell('Exorcism', pay: 3, window: '')->answer(1, 'NO')
    ->expectCardExists('myBanish', 'Haunting Apparition', 'Not paid: the ephemeral Apparition was sacrificed and, being ephemeral, banished')
    ->tested('Exorcism')
    ->covers(['ephemeral', 'sacrifice', 'optional-cost'], 'For each **ephemeral** object each player controls, its controller sacrifices it unless they pay (1).')
    ->register();

gaScenario('exorcism-paying-one-keeps-the-ephemeral-object')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Exorcism')
    ->seed('myField', 'Haunting Apparition', properties: ['TurnEffects' => ['EPHEMERAL']])
    ->seed('myHand', 'Exorcism')
    ->castSpell('Exorcism', pay: 3, window: '')->answer(1, 'YES')
    ->expectCardExists('myField', 'Haunting Apparition', 'Paid (1): the Apparition stays')
    ->expectZoneCount('myMemory', 4, 1, 'Three for the spell and one for Exorcism')
    ->tested('Exorcism')
    ->covers(['ephemeral', 'optional-cost'], 'its controller sacrifices it unless they pay (1)')
    ->register();

gaScenario('exorcism-reaches-the-opponents-ephemeral-objects-too')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Exorcism')
    ->seed('myField', 'Haunting Apparition', player: 2, properties: ['TurnEffects' => ['EPHEMERAL']])
    ->seed('myHand', 'Exorcism')
    ->castSpell('Exorcism', pay: 3, window: '')->answer(2, 'NO')
    ->expectCardExists('myBanish', 'Haunting Apparition', 'The opponent declined to pay: their ephemeral Apparition was sacrificed and banished', 2)
    ->tested('Exorcism')
    ->covers(['ephemeral', 'sacrifice', 'optional-cost'], 'For each **ephemeral** object each player controls')
    ->register();
