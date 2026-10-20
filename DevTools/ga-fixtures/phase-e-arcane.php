<?php
/**
 * Phase E (Arcane): rules clauses recorded as unexercised: the arcane-Shenju element waiver on Harbinger of Lightning and
 * Seiryuu's Command, and the Staves X Ultra banishment end-phase effect.
 */

const GA_SHENJU_WAIVER_TEXT = 'As long as you control an arcane element Shenju ally, ignore this card\'s elemental requirements as you activate it.';

// Default champion has no arcane lineage, so the card is only playable through the waiver.
gaScenario('harbinger-of-lightning-without-an-arcane-shenju-ally-needs-the-arcane-element')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myHand', 'Harbinger of Lightning')
    ->action(1, 10002, 'myHand-10!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 8, 1, 'Without the element or a Shenju the Harbinger stays in hand')
    ->tested('Harbinger of Lightning')
    ->covers(['element-waiver', 'condition'], GA_SHENJU_WAIVER_TEXT)
    ->register();

gaScenario('harbinger-of-lightning-with-an-arcane-shenju-ally-ignores-the-element-requirement')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Seiryuu, Azure Dragon')
    ->seed('myHand', 'Harbinger of Lightning')
    ->play('Harbinger of Lightning')->pay(2)
    ->expectCardExists('myField', 'Harbinger of Lightning', 'The Shenju ally waived the arcane requirement')
    ->tested('Harbinger of Lightning')
    ->covers(['element-waiver', 'ally'], GA_SHENJU_WAIVER_TEXT)
    ->register();

gaScenario('harbinger-of-lightning-a-non-arcane-shenju-does-not-waive-the-element')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Genbu, Black Tortoise')
    ->seed('myHand', 'Harbinger of Lightning')
    ->action(1, 10002, 'myHand-10!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 8, 1, 'Genbu is a water Shenju: no waiver')
    ->tested('Harbinger of Lightning')
    ->covers(['element-waiver', 'condition'], GA_SHENJU_WAIVER_TEXT)
    ->register();

gaScenario('harbinger-of-lightning-the-waiver-ignores-an-opponents-arcane-shenju')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('theirField', 'Seiryuu, Azure Dragon')
    ->seed('myHand', 'Harbinger of Lightning')
    ->action(1, 10002, 'myHand-10!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 8, 1, 'Only a Shenju you control waives the requirement')
    ->tested('Harbinger of Lightning')
    ->covers(['element-waiver', 'condition', 'controller'], GA_SHENJU_WAIVER_TEXT)
    ->register();

gaScenario('seiryuus-command-with-an-arcane-shenju-ally-ignores-the-element-requirement')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Seiryuu, Azure Dragon')
    ->seed('myField', 'Firebird Trailblazer')
    ->seed('myHand', 'Seiryuu\'s Command')
    ->castSpell('Seiryuu\'s Command', pay: 2, target: 'myField-2')
    ->expectZoneCount('myGraveyard', 1, 1, 'The Command resolved without an arcane element')
    ->tested('Seiryuu\'s Command')
    ->covers(['element-waiver', 'ally'], GA_SHENJU_WAIVER_TEXT)
    ->register();

gaScenario('seiryuus-command-without-an-arcane-shenju-ally-needs-the-arcane-element')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Firebird Trailblazer')
    ->seed('myHand', 'Seiryuu\'s Command')
    ->action(1, 10002, 'myHand-10!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 8, 1, 'No element and no Shenju: stays in hand')
    ->tested('Seiryuu\'s Command')
    ->covers(['element-waiver', 'condition'], GA_SHENJU_WAIVER_TEXT)
    ->register();

// ---- Staves X Ultra (banishment end-phase effect) ----

const GA_STAVES_END_TEXT = '[Lorraine Bonus] [Element Bonus] At the beginning of your end phase, if this card is in your banishment, put a **static** counter on a Sword weapon you control and each object linked to it.';

gaScenario('staves-x-ultra-in-banishment-puts-a-static-counter-on-the-sword-at-end-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Arclight Saber', lineage: ['Lorraine, Wandering Warrior'])
    ->seed('myField', 'Training Sword')
    ->seed('myBanish', 'Staves X Ultra')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2,"static":1}', 'The Sword got a static counter')
    ->tested('Staves X Ultra')
    ->covers(['lorraine-bonus', 'element-bonus', 'end-phase', 'counter', 'banishment'], GA_STAVES_END_TEXT)
    ->register();

gaScenario('staves-x-ultra-in-banishment-also-puts-a-static-counter-on-the-object-linked-to-the-sword')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Arclight Saber', lineage: ['Lorraine, Wandering Warrior'])
    ->seed('myField', 'Training Sword', properties: ['Subcards' => [GaScenario::id('Staves X Ultra')]])
    ->seed('myField', 'Staves X Ultra', properties: ['Counters' => ['linkedToWeapon' => GaScenario::id('Training Sword')]])
    ->seed('myBanish', 'Staves X Ultra')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2,"static":1}', 'The Sword got a static counter')
    ->expectProperty('p1Field-2', 'Counters', '{"linkedToWeapon":"' . GaScenario::id('Training Sword') . '","static":1}', 'And so did the object linked to it')
    ->tested('Staves X Ultra')
    ->covers(['end-phase', 'counter', 'weapon-link', 'banishment'], GA_STAVES_END_TEXT)
    ->register();

gaScenario('staves-x-ultra-on-the-field-puts-no-counter-at-end-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Arclight Saber', lineage: ['Lorraine, Wandering Warrior'])
    ->seed('myField', 'Training Sword')
    ->seed('myField', 'Staves X Ultra')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}', 'Staves is not in banishment: no counter')
    ->tested('Staves X Ultra')
    ->covers(['end-phase', 'counter', 'condition', 'banishment'], GA_STAVES_END_TEXT)
    ->register();

gaScenario('staves-x-ultra-in-banishment-without-the-element-bonus-puts-no-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Lorraine, Arclight Saber'])
    ->seed('myField', 'Training Sword')
    ->seed('myBanish', 'Staves X Ultra')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}', 'A NORM champion: Element Bonus inactive, no counter')
    ->tested('Staves X Ultra')
    ->covers(['element-bonus', 'end-phase', 'condition'], GA_STAVES_END_TEXT)
    ->register();
