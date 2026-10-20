<?php
/**
 * Phase E (Tera): Bloom: Autumn's Fall hand ability, Flourishing Restoration element waiver, Lu Bu, Indomitable Titan death replacement.
 * (Hymn of Gaia's Grace's attack retarget is covered by combat-perspective.php.)
 */

const GA_AUTUMN_DISCARD_TEXT = '[Element Bonus] (2), **Discard this card from your hand**: **Empower 2**. Draw a card.';
const GA_RESTORATION_WAIVER_TEXT = '[Diao Chan Bonus] You may ignore this card\'s elemental requirements as you activate it. If you do, it costs 2 more to activate.';
const GA_LU_BU_DEATH_TEXT = '[Diao Chan Bonus] If your champion would die with exactly thirty-two damage counters on them, banish them, transform CARDNAME, and destroy all other objects you control instead.';

// ---- Bloom: Autumn's Fall ----

gaScenario('bloom-autumns-fall-element-bonus-discard-pays-two-empowers-and-draws')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage')
    ->seed('myHand', 'Bloom: Autumn\'s Fall')
    ->action(1, 10001, 'myHand-7!CustomInput!Activate:0')->pay(2)
    ->expectZoneCount('myHand', 6, 1, 'Discarded the card, paid two, drew one: 8 - 1 - 2 + 1')
    ->expectCardExists('myGraveyard', 'Bloom: Autumn\'s Fall', 'The discarded card is in the graveyard')
    ->expectProperty('p1Field-0', 'TurnEffects', '["pebu7agtcd","EMPOWERED","EMPOWER_PLUS_2"]', 'Empower 2 on the champion')
    ->tested('Bloom: Autumn\'s Fall')
    ->covers(['element-bonus', 'discard', 'empower', 'draw'], GA_AUTUMN_DISCARD_TEXT)
    ->register();

// ---- Flourishing Restoration ----

// Diao Chan, Enchantress is a NORM champion: without the waiver she cannot activate a TERA spell at all.
gaScenario('flourishing-restoration-diao-chan-bonus-waives-the-element-for-two-more')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Enchantress', damage: 10)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Flourishing Restoration')
    ->play('Flourishing Restoration')->pay(4)
    ->expectDamage('p1Field-0', 4, 'Recover 3 and 3 more for the one opposing ally: 10 down to 4')
    ->expectDqEmpty()
    ->tested('Flourishing Restoration')
    ->covers(['diao-chan-bonus', 'element-waiver', 'cost-increase'], GA_RESTORATION_WAIVER_TEXT)
    ->register();

// ---- Lu Bu, Indomitable Titan ----

// Charge the Soul (1 damage) lands the 32nd damage counter on a champion with 31 (life 25); the controller casts it on their own champion.
function gaLuBuDeath(string $slug) {
    return gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
        ->champion(cardName: 'Diao Chan, Idyll Corsage', damage: 31)
        ->seed('myField', 'Lu Bu, Indomitable Titan')
        ->seed('myField', 'Dungeon Guide')
        ->seed('myHand', 'Charge the Soul')
        ->play('Charge the Soul')->pay(1)->answer(1, 'myField-0');
}

gaLuBuDeath('lu-bu-indomitable-titan-diao-chan-bonus-banishes-the-dying-champion-and-transforms')
    ->expectCardExists('myBanish', 'Diao Chan, Idyll Corsage', 'The champion with exactly 32 damage was banished instead of dying')
    ->expectProperty('p1Field-0', 'CardID', 'l5izukgdmh', 'Lu Bu transformed into Lu Bu, Wrath Incarnate')
    ->expectZoneCount('myField', 1, 1, 'All other objects (the Dungeon Guide) were destroyed')
    ->expectDqEmpty()
    ->tested('Lu Bu, Indomitable Titan')
    ->covers(['diao-chan-bonus', 'replacement', 'transform', 'banish'], GA_LU_BU_DEATH_TEXT)
    ->register();
