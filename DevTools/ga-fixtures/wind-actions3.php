<?php
/**
 * Phase 3b (Wind): actions, third file -- banish-and-return, wake-up, buff and suppress actions.
 */

// ---- Sylph's Envelopment ----

gaScenario('sylphs-envelopment-returns-the-ally-rested')
    ->standardDeck('Sylph\'s Envelopment')
    ->readyFor('Sylph\'s Envelopment')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Sylph\'s Envelopment')
    ->castSpell('Sylph\'s Envelopment', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The ally came back rested')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Not a phantasia: no extra buff counter')
    ->tested('Sylph\'s Envelopment')
    ->covers(['zone-movement', 'condition'], 'Banish target ally you control, then return it to the field under its owner\'s control rested. If that ally is a phantasia, it enters the field with an additional **buff** counter on it.')
    ->register();

gaScenario('sylphs-envelopment-a-phantasia-enters-with-a-buff-counter')
    ->standardDeck('Sylph\'s Envelopment')
    ->readyFor('Sylph\'s Envelopment')
    ->seed('myField', 'Maiden of Glimmer\'s Dusk')
    ->seed('myHand', 'Sylph\'s Envelopment')
    ->castSpell('Sylph\'s Envelopment', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The phantasia came back rested')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'It entered with an additional buff counter')
    ->tested('Sylph\'s Envelopment')
    ->covers(['counter', 'condition'], 'If that ally is a phantasia, it enters the field with an additional **buff** counter on it.')
    ->register();

// ---- Windy Leap ----

gaScenario('windy-leap-a-ranger-ally-returns-rested-and-distant')
    ->standardDeck('Windy Leap')
    ->readyFor('Windy Leap')
    ->seed('myField', 'Lone Gunslinger')
    ->seed('myHand', 'Windy Leap')
    ->castSpell('Windy Leap', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The ally came back rested')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","ENTERED_THIS_TURN","DISTANT"]', 'A Ranger ally becomes distant')
    ->tested('Windy Leap')
    ->covers(['zone-movement', 'distant', 'condition'], 'Banish target ally you control, then return it to the field under its owner’s control rested. If it\'s a Ranger ally, it becomes **distant**. *(Units stay **distant** until the end of their controller\'s turn.)*')
    ->register();

gaScenario('windy-leap-a-non-ranger-ally-is-not-made-distant')
    ->standardDeck('Windy Leap')
    ->readyFor('Windy Leap')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Windy Leap')
    ->castSpell('Windy Leap', pay: 2, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'The ally came back rested')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","ENTERED_THIS_TURN"]', 'Not a Ranger: not distant')
    ->tested('Windy Leap')
    ->covers(['condition'], 'If it\'s a Ranger ally, it becomes **distant**.')
    ->register();

// ---- Riveting Winds ----

gaScenario('riveting-winds-class-bonus-costs-two-less-and-wakes-the-champion')
    ->standardDeck('Riveting Winds')
    ->readyFor('Riveting Winds', classBonus: true)
    ->patch('myField-0', ['Status' => 1])
    ->seed('myHand', 'Riveting Winds')
    ->castSpell('Riveting Winds', pay: 2, window: 'me')
    ->expectProperty('p1Field-0', 'Status', '2', 'The rested champion woke up')
    ->expectZoneCount('myMemory', 2, 1, 'Class Bonus: cost 4 - 2 = 2 was paid')
    ->tested('Riveting Winds')
    ->covers(['status', 'class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 2 less to activate. *(Apply this effect only if your champion\'s class matches this card\'s class.)*', 'Wake up your champion.')
    ->register();

gaScenario('riveting-winds-without-the-class-bonus-costs-the-full-four')
    ->standardDeck('Riveting Winds')
    ->readyFor('Riveting Winds')
    ->patch('myField-0', ['Status' => 1])
    ->seed('myHand', 'Riveting Winds')
    ->castSpell('Riveting Winds', pay: 4, window: 'me')
    ->expectProperty('p1Field-0', 'Status', '2', 'The champion still woke up')
    ->expectZoneCount('myMemory', 4, 1, 'No Class Bonus: the printed cost 4 was paid')
    ->tested('Riveting Winds')
    ->covers(['class-bonus'], '[Class Bonus] This card costs 2 less to activate.')
    ->register();

// ---- Song of Return ----

gaScenario('song-of-return-class-bonus-costs-one-less-and-returns-an-ally-to-hand')
    ->standardDeck('Song of Return')
    ->readyFor('Song of Return', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Song of Return')
    ->castSpell('Song of Return', pay: 2, window: 'me', target: 'myField-1')
    ->expectCardExists('myHand', 'Giant Tortoise', 'The ally returned to hand')
    ->expectZoneCount('myMemory', 2, 1, 'Class Bonus: cost 3 - 1 = 2 was paid')
    ->tested('Song of Return')
    ->covers(['zone-movement', 'class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 1 less to activate. *(Apply this effect only if your champion\'s class matches this card\'s class.)*', 'Return up to two target allies you control to their owner\'s hands.')
    ->register();

// ---- Second Wind ----

gaScenario('second-wind-class-bonus-wakes-the-ally-and-gives-it-plus-one-power')
    ->standardDeck('Second Wind')
    ->readyFor('Second Wind', classBonus: true)
    ->seed('myField', 'Giant Tortoise', properties: ['Status' => 1])
    ->seed('myHand', 'Second Wind')
    ->castSpell('Second Wind', pay: 3, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '2', 'The rested ally woke up')
    ->expectPower('p1Field-1', 2, 'Class Bonus: +1 POWER until end of turn (1 + 1)')
    ->tested('Second Wind')
    ->covers(['status', 'stat-change', 'class-bonus'], 'Wake up target ally. **Class Bonus:** That ally gets +1 [POWER] until end of turn. *(Apply the additional effect only if your champion\'s class matches this card\'s class.)*')
    ->register();

gaScenario('second-wind-without-the-class-bonus-only-wakes-the-ally')
    ->standardDeck('Second Wind')
    ->readyFor('Second Wind')
    ->seed('myField', 'Giant Tortoise', properties: ['Status' => 1])
    ->seed('myHand', 'Second Wind')
    ->castSpell('Second Wind', pay: 3, window: 'me', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Status', '2', 'The rested ally woke up')
    ->expectPower('p1Field-1', 1, 'No Class Bonus: no extra POWER')
    ->tested('Second Wind')
    ->covers(['class-bonus'], '**Class Bonus:** That ally gets +1 [POWER] until end of turn.')
    ->register();

// ---- Usurp the Winds ----

gaScenario('usurp-the-winds-gives-an-ally-plus-one-power-and-plus-one-life')
    ->standardDeck('Usurp the Winds')
    ->readyFor('Usurp the Winds')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Usurp the Winds')
    ->castSpell('Usurp the Winds', pay: 1, window: 'me', target: 'myField-1')
    ->expectPower('p1Field-1', 2, '+1 POWER until end of turn')
    ->expectLife('p1Field-1', 7, '+1 LIFE until end of turn')
    ->tested('Usurp the Winds')
    ->covers(['stat-change', 'targeting'], 'Up to one target ally gets +1 [POWER] and +1 [LIFE] until end of turn.')
    ->register();

// ---- Sage Protection ----

gaScenario('sage-protection-class-bonus-gives-an-ally-plus-one-life-and-enlightens')
    ->standardDeck('Sage Protection')
    ->readyFor('Sage Protection', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Sage Protection')
    ->castSpell('Sage Protection', pay: 2, window: 'me', target: 'myField-1')
    ->expectLife('p1Field-1', 7, '+1 LIFE until end of turn')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"MAGE"},"enlighten":1}', 'Class Bonus: an enlighten counter on the champion')
    ->tested('Sage Protection')
    ->covers(['stat-change', 'class-bonus', 'counter'], 'Up to three target allies get +1 [LIFE] until end of turn.', '[Class Bonus] Put an **enlighten** counter on your champion. *(You may remove three **enlighten** counters from your champion to draw a card.)*')
    ->register();

gaScenario('sage-protection-without-the-class-bonus-adds-no-enlighten-counter')
    ->standardDeck('Sage Protection')
    ->readyFor('Sage Protection')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Sage Protection')
    ->castSpell('Sage Protection', pay: 2, window: 'me', target: 'myField-1')
    ->expectLife('p1Field-1', 7, 'The life bonus still applies')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'No Class Bonus: no enlighten counter')
    ->tested('Sage Protection')
    ->covers(['class-bonus'], '[Class Bonus] Put an **enlighten** counter on your champion.')
    ->register();

// ---- Ravaging Tempest ----

gaScenario('ravaging-tempest-banishes-all-allies-and-their-controllers-draw')
    ->standardDeck('Ravaging Tempest')
    ->readyFor('Ravaging Tempest', level: 3)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Ravaging Tempest')
    ->castSpell('Ravaging Tempest', pay: 5, window: 'me')
    ->expectZoneCount('myField', 1, 1, 'Our ally was banished')
    ->expectZoneCount('theirField', 1, 1, 'Their ally was banished')
    ->expectZoneCount('myHand', 3, 1, 'Efficiency 3: cost 8 - 3 = 5 paid; drew one card for our banished ally (8 - 1 - 5 + 1)')
    ->expectZoneCount('theirHand', 8, 1, 'The opponent drew a card for their banished ally')
    ->tested('Ravaging Tempest')
    ->covers(['banish', 'draw', 'efficiency'], '**Efficiency** *(This card costs **LV** less to activate. **LV** refers to your champion\'s level.)*', 'Banish all allies. For each ally banished this way, its controller draws a card.')
    ->register();
