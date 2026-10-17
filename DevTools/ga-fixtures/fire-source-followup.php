<?php
gaScenario('everflame-staff-sees-exalted-fire-spell-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Flared Iridescence')
    ->seed('myField', 'Everflame Staff')->seed('myHand', 'Flared Iridescence')
    ->play('Flared Iridescence')->pay(2)->answer(1, 'theirField-0')
    ->expectDamage('p2Field-0', 4, 'The Exalted Fire spell deals damage')
    ->expectProperty('p1Field-1', 'Counters', '{"refinement":1}', 'The Fire spell gives the Staff a refinement counter')
    ->tested('Everflame Staff')->covers(['damage', 'trigger', 'element'], 'Exalted Fire Spell damage qualifies as Fire.')->register();

gaScenario('charged-gunslinger-exalted-fire-discard-after-powercell-compaction')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Charged Gunslinger', classBonus: true)
    ->seed('myField', 'Powercell')->seed('myField', 'Charged Gunslinger')->seed('myField', 'Delivery Droid')
    ->seed('myHand', 'Scorched Conquest')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)->answer(1, 'myHand-6')->answer(1, 'myField-2')
    ->expectCardExists('myGraveyard', 'Scorched Conquest', 'Exalted Fire is discarded')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The original Gunslinger becomes distant after the earlier slot disappears')
    ->tested('Charged Gunslinger')->covers(['trigger', 'discard', 'element', 'identity'], 'Fire membership and source identity survive Powercell departure.')->register();
