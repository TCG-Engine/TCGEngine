<?php
/**
 * Phase 3b (Water): negate family. Player 1 activates a Dungeon Guide (cost 3); player 2 holds the negator in slot 7 of their hand.
 */

gaScenario('engulf-negates-a-non-attack-card-activation')
    ->deckTemplate('spirit-dungeon-guide', 'Engulf')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Engulf', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(6, 2)->answer(2, 'EffectStack-0')
    ->expectZoneCount('myGraveyard', 1, 1, 'The negated Dungeon Guide went to its owner\'s graveyard')
    ->expectZoneCount('myField', 1, 1, 'It never entered the field')
    ->expectZoneCount('myMemory', 6, 2, 'Engulf cost 6')
    ->tested('Engulf')
    ->covers(['negate', 'card-activation'], '**Negate** target non-attack card activation.')
    ->register();

gaScenario('frozen-dismissal-the-controller-declines-and-the-ally-is-negated-and-banished')
    ->deckTemplate('spirit-dungeon-guide', 'Frozen Dismissal')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Frozen Dismissal', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(3, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->answer(1, 'NO')
    ->expectZoneCount('myBanish', 1, 1, 'Declined: the negated ally card was banished, not put in the graveyard')
    ->expectZoneCount('myField', 1, 1, 'It never entered the field')
    ->tested('Frozen Dismissal')
    ->covers(['negate', 'pay-or-negate', 'banish'], '**Negate** target ally card activation unless its controller pays (4). Banish the card that had its activation **negated** this way.')
    ->register();

gaScenario('frozen-dismissal-the-controller-pays-four-and-the-ally-resolves')
    ->deckTemplate('spirit-dungeon-guide', 'Frozen Dismissal')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Frozen Dismissal', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(3, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->answer(1, 'YES')->answer(1, 'myHand-0&myHand-1&myHand-2&myHand-3')->pass(2)
    ->expectCardExists('myField', 'Dungeon Guide', 'Paid 4: the ally was not negated and entered the field')
    ->expectZoneCount('myMemory', 7, 1, 'Three for the ally, then the four cards paid to keep it')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished')
    ->tested('Frozen Dismissal')
    ->covers(['negate', 'pay-or-negate', 'condition'], '**Negate** target ally card activation unless its controller pays (4).')
    ->register();

gaScenario('frozen-dismissal-class-bonus-costs-one-less')
    ->deckTemplate('spirit-dungeon-guide', 'Frozen Dismissal')
    ->champion(classes: 'CLERIC', player: 2)
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Frozen Dismissal', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(2, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->answer(1, 'NO')
    ->expectZoneCount('myMemory', 2, 2, '[Class Bonus] Frozen Dismissal cost 3 - 1 = 2')
    ->expectZoneCount('myBanish', 1, 1, 'The ally was negated and banished')
    ->tested('Frozen Dismissal')
    ->covers(['cost-reduction', 'class-bonus'], '[Class Bonus] This card costs 1 less to activate.')
    ->register();

gaScenario('tidal-lock-the-controller-declines-and-the-activation-is-negated-and-banished')
    ->deckTemplate('spirit-dungeon-guide', 'Tidal Lock')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Tidal Lock', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(3, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->answer(1, 'NO')
    ->expectZoneCount('myBanish', 1, 1, 'Declined: the negated card was banished')
    ->expectZoneCount('myMemory', 3, 2, 'Tidal Lock cost its full 3')
    ->tested('Tidal Lock')
    ->covers(['negate', 'pay-or-negate', 'banish'], '**Negate** target card activation unless its controller pays (2). Banish the card that had its activation **negated** this way.')
    ->register();

gaScenario('tidal-lock-three-water-cards-in-the-graveyard-make-it-cost-two-less')
    ->deckTemplate('spirit-dungeon-guide', 'Tidal Lock')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Tidal Lock', player: 2)
    ->seed('myGraveyard', 'Giant Tortoise', player: 2)->seed('myGraveyard', 'Giant Tortoise', player: 2)->seed('myGraveyard', 'Giant Tortoise', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(1, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->answer(1, 'NO')
    ->expectZoneCount('myMemory', 1, 2, 'Three water cards in the graveyard: Tidal Lock cost 3 - 2 = 1')
    ->expectZoneCount('myBanish', 1, 1, 'The negated card was banished')
    ->tested('Tidal Lock')
    ->covers(['cost-reduction', 'graveyard-count'], 'As long as you have three or more water element cards in your graveyard, this card costs 2 less to activate.')
    ->register();
