<?php
/**
 * Phase 3b (Water): negate family, second file. Player 1 activates a Dungeon Guide (cost 3); player 2 holds the negator in slot 7 of their hand.
 */

gaScenario('chained-charge-class-bonus-negates-unless-its-controller-pays-one')
    ->deckTemplate('spirit-dungeon-guide', 'Chained Charge')
    ->champion(classes: 'RANGER', player: 2)
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Chained Charge', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(1, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->answer(1, 'NO')
    ->expectZoneCount('myGraveyard', 1, 1, 'Declined to pay (1): the Dungeon Guide was negated')
    ->expectZoneCount('myField', 1, 1, 'It never entered the field')
    ->expectZoneCount('myMemory', 1, 2, 'Class Bonus: Chained Charge cost 2 - 1 = 1')
    ->tested('Chained Charge')
    ->covers(['negate', 'pay-or-negate', 'class-bonus', 'cost-reduction'], '**Negate** target card activation unless its controller pays (1).')
    ->register();

gaScenario('chained-charge-the-controller-pays-one-and-the-ally-resolves')
    ->deckTemplate('spirit-dungeon-guide', 'Chained Charge')
    ->champion(classes: 'RANGER', player: 2)
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Chained Charge', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(1, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->answer(1, 'YES')->answer(1, 'myHand-0')->pass(2)->pass(1)->pass(2)->answer(1, 'NO')
    ->expectCardExists('myField', 'Dungeon Guide', 'Paid (1): the Dungeon Guide was not negated and entered the field')
    ->expectZoneCount('myMemory', 4, 1, 'Three for the ally, then the one card paid to keep it')
    ->tested('Chained Charge')
    ->covers(['negate', 'pay-or-negate', 'condition'], '**Negate** target card activation unless its controller pays (1).')
    ->register();

gaScenario('surging-obstruction-an-odd-cost-card-must-pay-three')
    ->deckTemplate('spirit-dungeon-guide', 'Surging Obstruction')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Surging Obstruction', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(3, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->answer(1, 'NO')
    ->expectZoneCount('myGraveyard', 1, 1, 'Declined to pay (3): the Dungeon Guide (odd reserve cost 3) was negated')
    ->expectZoneCount('myField', 1, 1, 'It never entered the field')
    ->expectZoneCount('myMemory', 3, 2, 'No Chessman Bishop: Surging Obstruction cost its full 3')
    ->tested('Surging Obstruction')
    ->covers(['negate', 'pay-or-negate', 'odd-cost'], '**Negate** target card activation unless its controller pays (1). If that card has an odd reserve cost, **negate** its activation unless its controller pays (3) instead.')
    ->register();

gaScenario('surging-obstruction-an-even-cost-card-only-has-to-pay-one')
    ->deckTemplate('spirit-dungeon-guide', 'Surging Obstruction')
    ->seed('myHand', 'Giant Tortoise')
    ->seed('myHand', 'Surging Obstruction', player: 2)
    ->play('Giant Tortoise')->pay(4)->pass(1)->answer(2, 'myHand-7')->pay(3, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->answer(1, 'YES')->answer(1, 'myHand-0')->pass(2)->pass(1)->pass(2)
    ->expectCardExists('myField', 'Giant Tortoise', 'Even reserve cost (4): paying only (1) kept the Giant Tortoise')
    ->expectZoneCount('myMemory', 5, 1, 'Four for the ally, then the one card paid to keep it')
    ->tested('Surging Obstruction')
    ->covers(['negate', 'pay-or-negate', 'even-cost'], '**Negate** target card activation unless its controller pays (1). If that card has an odd reserve cost, **negate** its activation unless its controller pays (3) instead.')
    ->register();

gaScenario('surging-obstruction-with-a-chessman-bishop-ally-costs-one-less')
    ->deckTemplate('spirit-dungeon-guide', 'Surging Obstruction')
    ->seed('myField', 'Weiss Bishop', player: 2)
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Surging Obstruction', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(2, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->answer(1, 'NO')
    ->expectZoneCount('myMemory', 2, 2, 'A Chessman Bishop ally: Surging Obstruction cost 3 - 1 = 2')
    ->expectZoneCount('myGraveyard', 1, 1, 'The Dungeon Guide was negated')
    ->tested('Surging Obstruction')
    ->covers(['cost-reduction', 'chessman'], 'As long as you control a Chessman Bishop ally, this card costs 1 less to activate.')
    ->register();

gaScenario('regal-expulsion-does-not-negate-a-card-activated-from-the-hand')
    ->deckTemplate('spirit-dungeon-guide', 'Regal Expulsion')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Regal Expulsion', player: 2)
    ->play('Dungeon Guide')->pay(3)->pass(1)->answer(2, 'myHand-7')->pay(2, 2)->pass(2)->pass(1)->answer(2, 'EffectStack-0')->pass(1)->pass(2)->answer(1, 'NO')
    ->expectCardExists('myField', 'Dungeon Guide', 'Activated from the hand, not a material deck: it was not negated')
    ->tested('Regal Expulsion')
    ->covers(['negate', 'condition', 'material-deck'], '**Negate** target card activation if that card was activated from a material deck.')
    ->register();
