<?php
/**
 * Phase 3d (Norm): unique domains and the remaining domains. 'quiet' deck.
 */

// ---- Shilowen, Peaceful Beginnings ----

gaScenario('shilowen-an-ally-entering-may-remove-two-durability-counters-to-get-a-bulwark-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Shilowen, Peaceful Beginnings')
    ->seed('myField', 'Shilowen, Peaceful Beginnings', properties: ['Counters' => ['durability' => 4]])
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->pay(3)->answer(1, 'YES')
    ->expectProperty('p1Field-2', 'Counters', '{"bulwark":1}', 'The entering ally got a bulwark counter')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}', 'Two durability counters were removed from Shilowen')
    ->tested('Shilowen, Peaceful Beginnings')
    ->covers(['trigger', 'optional-cost', 'counter', 'bulwark'], 'Whenever an ally enters the field, you may remove two **durability** counters from CARDNAME. If you do, put a **bulwark** counter on that ally.')
    ->register();

// ---- Changban, Heroic Impasse ----

gaScenario('changban-enter-puts-a-buff-counter-on-a-unique-ally-and-buffed-allies-have-ambush')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Changban, Heroic Impasse')
    ->seed('myField', 'Camil, Basked Abundance')
    ->seed('myField', 'Changban, Heroic Impasse')
    ->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'On Enter: the unique ally got a buff counter')
    ->tested('Changban, Heroic Impasse')
    ->covers(['on-enter', 'counter', 'targeting'], '**On Enter**: Put a **buff** counter on a unique ally you control.')
    ->register();

gaScenario('changban-upkeep-without-a-unique-ally-sacrifices-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Changban, Heroic Impasse')
    ->seed('myField', 'Changban, Heroic Impasse')
    ->reachTurnThree()
    ->expectCardExists('myGraveyard', 'Changban, Heroic Impasse', 'Upkeep: no unique ally, so Changban was sacrificed')
    ->tested('Changban, Heroic Impasse')
    ->covers(['upkeep', 'sacrifice', 'condition'], '***Upkeep*** — At the beginning of your recollection phase, if you don\'t control a unique ally, sacrifice Changban.')
    ->register();

// ---- Chateau de Coeurs ----

gaScenario('chateau-de-coeurs-enter-removes-buff-counters-from-objects-you-do-not-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Chateau de Coeurs')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Counters' => ['buff' => 2]])
    ->seed('myField', 'Chateau de Coeurs')
    ->observe()
    ->expectProperty('p2Field-1', 'Counters', '[]', 'On Enter: the opposing buff counters were removed')
    ->tested('Chateau de Coeurs')
    ->covers(['on-enter', 'counter'], '**On Enter**: Remove all **buff** counters from all objects you don\'t control.')
    ->register();

// ---- Keep of the Golden Sashes ----

gaScenario('keep-of-the-golden-sashes-the-first-card-an-opponent-activates-each-turn-costs-one-more')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Keep of the Golden Sashes')
    ->seed('myField', 'Keep of the Golden Sashes')
    ->seed('myHand', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->play('Dungeon Guide', 2)->pay(3, 2)
    ->expectZoneCount('myField', 1, 2, 'Paying the printed 3 is one short: the first card an opponent activates costs 1 more')
    ->tested('Keep of the Golden Sashes')
    ->covers(['static', 'cost-increase'], 'The first card your opponents activate each turn costs 1 more to activate.')
    ->register();

// ---- Dawn of Ashes ----

gaScenario('dawn-of-ashes-non-norm-element-cards-cost-one-more')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Spark Fairy')
    ->seed('myField', 'Dawn of Ashes')
    ->seed('myHand', 'Spark Fairy')
    ->play('Spark Fairy')->pay(3)
    ->expectZoneCount('myField', 2, 1, 'A fire card costs 1 more: paying the printed 3 is one short')
    ->tested('Dawn of Ashes')
    ->covers(['static', 'cost-increase'], 'Non-norm element cards cost 1 more to activate.')
    ->register();

// ---- Field of Ranks and Files ----

gaScenario('field-of-ranks-and-files-the-first-chessman-ally-each-turn-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Field of Ranks and Files')
    ->seed('myField', 'Field of Ranks and Files')
    ->seed('myHand', 'Weiss Bishop')
    ->play('Weiss Bishop')->pay(2)
    ->tested('Field of Ranks and Files')
    ->covers(['trigger', 'stat-change', 'chessman'], 'The first time a Chessman ally enters the field under your control during each of your turns, that ally gets +2 [POWER] until end of turn.')
    ->register();

// ---- Bedlam Borough ----

gaScenario('bedlam-borough-two-rest-cascades-for-a-powercell-or-a-sacrifice')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bedlam Borough')
    ->seed('myField', 'Bedlam Borough')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)
    ->expectProperty('p1Field-1', 'Status', '1', 'The domain rested')
    ->tested('Bedlam Borough')
    ->covers(['activated-ability', 'rest', 'cascade'], '(2), [REST]: **Cascade**—')
    ->register();

// ---- Dorumegian Foundry ----

gaScenario('dorumegian-foundry-enter-summons-an-automaton-drone-with-a-buff-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dorumegian Foundry')
    ->seed('myField', 'Dorumegian Foundry')
    ->observe()
    ->expectCardExists('myField', 'Automaton Drone', 'On Enter: an Automaton Drone token was summoned')
    ->tested('Dorumegian Foundry')
    ->covers(['on-enter', 'summon', 'counter'], '**On Enter:** **Summon** an Automaton Drone token with a **buff** counter on it.')
    ->register();

gaScenario('dorumegian-foundry-class-bonus-costs-two-less-for-each-of-up-to-three-domains')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dorumegian Foundry', classBonus: true)
    ->seed('myField', 'Hub of Innovation')
    ->seed('myHand', 'Dorumegian Foundry')
    ->play('Dorumegian Foundry')->pay(6)
    ->expectCardExists('myField', 'Dorumegian Foundry', '[Class Bonus] One domain controlled: 8 - 2 = 6')
    ->tested('Dorumegian Foundry')
    ->covers(['class-bonus', 'cost-reduction', 'condition'], '[Class Bonus] This card costs 2 less to activate for each of up to three domains you control.')
    ->register();

// ---- The Eternal Kingdom ----

gaScenario('the-eternal-kingdom-class-bonus-domains-you-control-have-reservable')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('The Eternal Kingdom', classBonus: true)
    ->seed('myField', 'The Eternal Kingdom')
    ->seed('myField', 'Hub of Innovation')
    ->seed('myHand', 'Cell Production')
    ->play('Cell Production')->pay(2)->answer(1, 'myField-2')
    ->expectProperty('p1Field-2', 'Status', '1', 'The domain was rested to pay for 1 of the reserve cost')
    ->tested('The Eternal Kingdom')
    ->covers(['class-bonus', 'reservable', 'static'], '[Class Bonus] Domains you control have **reservable**.')
    ->register();

// ---- Guandu, Theater of War ----

gaScenario('guandu-theater-of-war-an-ally-attack-puts-a-battle-counter-on-it-and-glimpses-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Guandu, Theater of War')
    ->seed('myField', 'Guandu, Theater of War', properties: ['Counters' => ['battle' => 2]])
    ->seed('myField', 'Dungeon Guide')
    ->deckTop(['Dungeon Guide'])
    ->reachTurnThree()
    ->attack('myField-2', 'theirField-0')->answer(1, 'Top=px60u5n1do;Bottom=')->pass(2)
    ->expectProperty('p1Field-1', 'Counters', '{"battle":1}', 'Upkeep removed the two seeded battle counters; the attack put one back')
    ->tested('Guandu, Theater of War')
    ->covers(['trigger', 'counter', 'glimpse'], 'Whenever you declare an attack with an ally, put a **battle** counter on CARDNAME and **glimpse 1**.')
    ->register();
