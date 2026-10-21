<?php
// Targets are declared before reserve payment; Nimue resolves above the action.
gaScenario('nimue-destroys-declared-ally-before-action')
    ->deckTemplate('quiet','Dungeon Guide')->champion(cardName:'Rai, Spellcrafter')
    ->seed('myField','Nimue, Cursed Touch')->seed('myField','Esteemed Knight',player:2)
    ->seed('myHand','Reposition')->play('Reposition')->answer(1,'theirField-1')->pay(1)->passBoth()
    ->expectCardExists('theirGraveyard','Esteemed Knight','Nimue destroys the declared ally')
    ->expectCardExists('myGraveyard','Reposition','The action finishes without selecting a replacement')
    ->expectZoneCount('myMemory',1,label:'Reserve is paid before Nimue resolves')->expectDqEmpty()
    ->tested('Nimue, Cursed Touch')->covers(['targeting','cost','trigger'],'Activate an action targeting an ally; pay costs; destroy the ally before the action resolves.')->register();
gaScenario('nimue-without-mage-bonus-does-not-destroy')
    ->deckTemplate('quiet','Dungeon Guide')->seed('myField','Nimue, Cursed Touch')
    ->seed('myField','Esteemed Knight',player:2)->seed('myHand','Reposition')
    ->play('Reposition')->answer(1,'theirField-1')->pay(1)->passBoth()
    ->expectCardExists('theirField','Esteemed Knight','No Mage bonus means no destroy trigger')
    ->expectProperty('p2Field-1','TurnEffects','["ENTERED_THIS_TURN","DISTANT"]','The declared action still resolves')
    ->expectDqEmpty()->tested('Nimue, Cursed Touch')->covers(['class-bonus','targeting'],'An inactive class bonus leaves the ally alive and the action resolves on its declared target.')->register();
gaScenario('nimue-does-not-trigger-for-champion-target')
    ->deckTemplate('quiet','Dungeon Guide')->champion(cardName:'Rai, Spellcrafter')
    ->seed('myField','Nimue, Cursed Touch')->seed('myHand','Reposition')
    ->play('Reposition')->answer(1,'theirField-0')->pay(1)->passBoth()
    ->expectProperty('p2Field-0','TurnEffects','["DISTANT"]','Champion target receives the action effect')
    ->expectZoneCount('theirGraveyard',0,label:'Nimue does not destroy a champion')->expectDqEmpty()
    ->tested('Nimue, Cursed Touch')->covers(['targeting','trigger'],'Targeting a champion does not trigger Nimue.')->register();
gaScenario('nimue-kindling-pays-sacrifice-and-reserve')
    ->deckTemplate('quiet','Dungeon Guide')->champion(cardName:'Rai, Spellcrafter',lineage:['Spirit of Fire'])
    ->seed('myField','Nimue, Cursed Touch')->seed('myField','Manaroot')
    ->seed('myField','Esteemed Knight',player:2)->seed('myHand','Kindling Flare')
    ->play('Kindling Flare')->answer(1,'myField-2')->answer(1,'theirField-1:2')->pay(2)->passBoth()
    ->expectZoneCount('myField',2,label:'The chosen Herb is sacrificed as a cost')
    ->expectZoneCount('myMemory',2,label:'Both reserve payments finish')
    ->expectCardExists('theirGraveyard','Esteemed Knight','Nimue destroys the split-damage target')
    ->expectDqEmpty()->tested('Nimue, Cursed Touch')->covers(['cost','sacrifice','split-damage','trigger'],'Kindling declares allocation, sacrifices its Herb, pays reserve, then triggers Nimue.')->register();
