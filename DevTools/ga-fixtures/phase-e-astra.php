<?php
/**
 * Phase E (Astra): Moonveil Android negate buff, Constellatory Spire negate trigger, the starcalling / aethercalling glimpse family,
 * Cometary Vantage, Stellarion Shift's graveyard ability, Prima Materia brewed, Astarte, Cataleptic Constellation, Reflect the Skies.
 */

const GA_MOONVEIL_NEGATE_TEXT = '[Class Bonus] Whenever you **negate** a card activation an opponent controls, put two **buff** counters on Moonveil Android.';

// P2 activates a Gray Wolf on their turn; P1 (holding $watcher, Poised Occlusion in hand) negates it.
function gaNegateTheirWolf(string $slug, string $watcher, bool $classBonus = true) {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide');
    $s = $classBonus ? $s->readyFor($watcher, classBonus: true) : $s->readyFor($watcher);
    return $s->seed('myField', $watcher)
        ->seed('myHand', 'Poised Occlusion')
        ->seed('myHand', 'Gray Wolf', player: 2)
        ->endTurn()->pass(1)->pass(1)
        ->play('Gray Wolf', 2)->pay(2, 2);
}

gaNegateTheirWolf('moonveil-android-class-bonus-negating-an-opposing-activation-puts-two-buff-counters-on-it', 'Moonveil Android')
    ->answer(1, 'myHand-7')->pay(2)->answer(1, 'EffectStack-0')
    ->expectCardExists('theirMemory', 'Gray Wolf', 'The negated Gray Wolf went to its owner\'s memory')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'Negating the opposing activation: two buff counters')
    ->tested('Moonveil Android')
    ->covers(['class-bonus', 'negate', 'counter'], GA_MOONVEIL_NEGATE_TEXT)
    ->register();

gaNegateTheirWolf('moonveil-android-without-the-class-bonus-negating-puts-no-buff-counters-on-it', 'Moonveil Android', classBonus: false)
    ->answer(1, 'myHand-7')->pay(2)->answer(1, 'EffectStack-0')
    ->expectCardExists('theirMemory', 'Gray Wolf', 'The Gray Wolf was negated')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No Class Bonus: no buff counters')
    ->tested('Moonveil Android')
    ->covers(['class-bonus', 'negate', 'condition'], GA_MOONVEIL_NEGATE_TEXT)
    ->register();

// The seeded Spire's On Enter drew a card, so Poised Occlusion sits one slot later (myHand-8).
const GA_SPIRE_NEGATE_TEXT = 'Whenever you **negate** a card activation, you may rest CARDNAME. When you do, deal 2 damage to target unit.';

gaNegateTheirWolf('the-constellatory-spire-negating-an-activation-lets-you-rest-it-to-deal-two-damage', 'The Constellatory Spire')
    ->answer(1, 'myHand-8')->pay(2)->answer(1, 'EffectStack-0')->answer(1, 'YES')->answer(1, 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'The Spire dealt 2 damage to the opposing champion')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Spire rested')
    ->expectDqEmpty()
    ->tested('The Constellatory Spire')
    ->covers(['trigger', 'negate', 'rest', 'damage'], GA_SPIRE_NEGATE_TEXT)
    ->register();

gaNegateTheirWolf('the-constellatory-spire-declining-the-rest-deals-no-damage', 'The Constellatory Spire')
    ->answer(1, 'myHand-8')->pay(2)->answer(1, 'EffectStack-0')->answer(1, 'NO')
    ->expectDamage('p2Field-0', 0, 'Declined: no damage')
    ->expectProperty('p1Field-1', 'Status', '2', 'The Spire stayed ready')
    ->expectDqEmpty()
    ->tested('The Constellatory Spire')
    ->covers(['trigger', 'negate', 'optional-choice'], GA_SPIRE_NEGATE_TEXT)
    ->register();

// ---- Starcalling (Scry the Skies glimpses; Stellaria Shower on top) ----

const GA_SHOWER_TEXT = '**On Enter**: If Stellaria Shower was **starcalled**, as a Spell, deal **X** damage to target unit, where **X** is the amount of phantasias you control.';

gaScenario('stellaria-shower-starcalled-deals-damage-equal-to-the-phantasias-you-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Lucent Arbiter', level: 3)
    ->seed('myField', 'Astral Shard')
    ->deckTop(['Stellaria Shower', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myHand', 'Scry the Skies')
    ->play('Scry the Skies')->pay(1)->passBoth()->answer(1, 'myTempZone-0')->pay(1)->passBoth()->answer(1, 'Bottom=em6eEh9q8y,em6eEh9q8y,px60u5n1do,em6eEh9q8y,em6eEh9q8y')->answer(1, 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'X = the two phantasias (Astral Shard, Stellaria Shower) you control')
    ->expectZoneCount('myMemory', 3, 1, 'Scry the Skies (1 paid, 1 drawn) and the starcalling (1)')
    ->expectDqEmpty()
    ->tested('Stellaria Shower')
    ->covers(['on-enter', 'starcalling', 'damage'], GA_SHOWER_TEXT)
    ->register();

const GA_SCRY_STARS_TEXT = 'Until end of turn, cards you look at while **glimpsing** have "**Starcalling** — (X)", where **X** is that card\'s reserve cost. **Glimpse 3**.';

// Dungeon Guide (reserve 3) has no starcalling of its own: Scry the Stars gives it Starcalling (3).
gaScenario('scry-the-stars-glimpsed-cards-gain-starcalling-equal-to-their-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Lucent Arbiter')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myHand', 'Scry the Stars')
    ->play('Scry the Stars')->pay(3)->passBoth()->answer(1, 'myTempZone-0')->pay(3)->passBoth()->answer(1, 'Bottom=em6eEh9q8y,em6eEh9q8y')
    ->expectCardExists('myField', 'Dungeon Guide', 'The glimpsed Dungeon Guide was starcalled onto the field for its reserve cost (3)')
    ->expectZoneCount('myMemory', 6, 1, 'Scry the Stars paid 3 and the starcalling 3')
    ->expectDqEmpty()
    ->tested('Scry the Stars')
    ->covers(['grant-ability', 'starcalling', 'glimpse'], GA_SCRY_STARS_TEXT)
    ->register();

const GA_PORTENT_COPY_TEXT = 'The next time you **starcall** a card this turn, copy that activation. You may choose new targets for the copy.';

// Stargazer's Portent (3 with the Class Bonus), then Scry the Skies glimpses a Cosmic Bolt (Starcalling (2): 4 damage to target unit).
gaScenario('stargazers-portent-copies-the-next-starcalled-activation-with-a-new-target')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Lucent Arbiter', level: 3)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->deckTop(['Cosmic Bolt', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myHand', 'Stargazer\'s Portent')->seed('myHand', 'Scry the Skies')
    ->play('Stargazer\'s Portent')->pay(3)->passBoth()
    ->play('Scry the Skies')->pay(1)->passBoth()->answer(1, 'myTempZone-0')->pay(2)->answer(1, 'theirField-0')->answer(1, 'Bottom=em6eEh9q8y,em6eEh9q8y,px60u5n1do,em6eEh9q8y,em6eEh9q8y')->answer(1, 'theirField-1')
    ->expectDamage('p2Field-0', 4, 'The starcalled Cosmic Bolt hit the champion for 4')
    ->expectCardExists('theirGraveyard', 'Giant Tortoise', 'The copy chose a new target: the Giant Tortoise (life 6) died to 4 plus 2 for the Cosmic Bolt already in the graveyard')
    ->expectZoneCount('myGraveyard', 3, 1, 'Portent, Scry the Skies and Cosmic Bolt (a copy is not a card)')
    ->expectDqEmpty()
    ->tested('Stargazer\'s Portent')
    ->covers(['starcalling', 'copy', 'targeting'], GA_PORTENT_COPY_TEXT)
    ->register();

// ---- Aethercalling (Vanishing Eclipse) ----

const GA_ECLIPSE_CALLING_TEXT = '[Element Bonus] **Aethercalling** *(As you\'re looking at this card while **glimpsing**, you may load it into an Aetherwing weapon you control.)*';

gaScenario('vanishing-eclipse-element-bonus-aethercalling-loads-it-into-an-aetherwing-while-glimpsing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Lucent Arbiter', level: 3)
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->deckTop(['Vanishing Eclipse', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myHand', 'Scry the Skies')
    ->play('Scry the Skies')->pay(1)->passBoth()->answer(1, 'myTempZone-0')->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,px60u5n1do,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectProperty('p1Field-1', 'Subcards', '["gamylrj1fc"]', 'Vanishing Eclipse is loaded into the Seeker\'s Aetherwing')
    ->expectDqEmpty()
    ->tested('Vanishing Eclipse')
    ->covers(['element-bonus', 'aethercalling', 'glimpse', 'load'], GA_ECLIPSE_CALLING_TEXT)
    ->register();

gaScenario('vanishing-eclipse-without-the-element-bonus-has-no-aethercalling')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vanishing Eclipse', level: 3)
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->deckTop(['Vanishing Eclipse', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myHand', 'Scry the Skies')
    ->play('Scry the Skies')->pay(1)->passBoth()->answer(1, 'Top=gamylrj1fc,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectProperty('p1Field-1', 'Subcards', '[]', 'A FIRE champion has no Element Bonus: nothing was offered or loaded')
    ->expectDqEmpty()
    ->tested('Vanishing Eclipse')
    ->covers(['element-bonus', 'aethercalling', 'condition'], GA_ECLIPSE_CALLING_TEXT)
    ->register();

// ---- Cometary Vantage ----

const GA_VANTAGE_TEXT = 'Choose one. If your champion is **distant**, choose two instead— • **Glimpse 4**. • Draw a card.';

gaScenario('cometary-vantage-distant-champion-chooses-two-modes-glimpse-four-and-draw')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cometary Vantage')
    ->patch('myField-0', ['TurnEffects' => ['DISTANT']])
    ->seed('myHand', 'Cometary Vantage')
    ->play('Cometary Vantage')->pay(1)->answer(1, '0,1')->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 7, 1, 'Eight in hand, minus Vantage and the card paid, plus the card drawn')
    ->expectDqEmpty()
    ->tested('Cometary Vantage')
    ->covers(['modal', 'distant', 'glimpse', 'draw'], GA_VANTAGE_TEXT)
    ->register();

const GA_VANTAGE_LOAD_TEXT = '• If a unit is attacking, each Aethercharge card in that attacker\'s intent gains "**On Hit**: Load this card into an Aetherwing weapon you control."';

function gaVantageAttack(string $slug) {
    return gaScenario($slug)
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->champion(lineage: ['Arisanna, Astral Zenith'], classes: 'RANGER')
        ->seed('myField', 'Salamander\'s Breath', properties: ['Subcards' => ['wd7nuab7f3']])
        ->seed('myHand', 'Cometary Vantage')
        ->reachTurnThree(fastCardInHand: true)
        ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0');
}

gaVantageAttack('cometary-vantage-attacker-aethercharge-intent-card-loads-back-into-the-aetherwing-on-hit')
    ->answer(1, 'myHand-7')->pay(1)->answer(1, '2')
    ->expectProperty('p1Field-1', 'Subcards', '["wd7nuab7f3"]', 'The Aether\'s Embrace was loaded back into the Salamander\'s Breath on the hit')
    ->expectZoneCount('myGraveyard', 1, 1, 'Only Cometary Vantage went to the graveyard: the Aethercharge card did not')
    ->tested('Cometary Vantage')
    ->covers(['modal', 'grant-ability', 'on-hit', 'load'], GA_VANTAGE_LOAD_TEXT)
    ->register();

gaScenario('cometary-vantage-aethercharge-mode-does-nothing-when-no-unit-is-attacking')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cometary Vantage')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myHand', 'Cometary Vantage')
    ->play('Cometary Vantage')->pay(1)->answer(1, '2')
    ->expectZoneCount('myHand', 6, 1, 'Eight in hand, minus Vantage and the card paid: nothing was drawn')
    ->expectDqEmpty()
    ->tested('Cometary Vantage')
    ->covers(['modal', 'condition'], GA_VANTAGE_LOAD_TEXT)
    ->register();

// ---- Stellarion Shift ----

const GA_SHIFT_GY_TEXT = '[Class Bonus] [Element Bonus] (2), **Banish this card from your graveyard**: Your champion gains **stealth** until end of turn. Prevent the next 4 non-combat damage that would be dealt to them this turn. Activate this ability only if your champion is **distant**.';

function gaShiftGraveyard(string $slug, bool $distant = true, ?string $handCard = null) {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->champion(cardName: 'Diana, Moonpiercer', level: 3);
    if ($distant) $s->patch('myField-0', ['TurnEffects' => ['DISTANT']]);
    if ($handCard !== null) $s->seed('myHand', $handCard);
    return $s->seed('myGraveyard', 'Stellarion Shift')
        ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(2);
}

gaShiftGraveyard('stellarion-shift-graveyard-ability-gives-a-distant-champion-stealth-and-prevents-four-non-combat-damage')
    ->expectCardExists('myBanish', 'Stellarion Shift', 'The card was banished from the graveyard as part of the cost')
    ->expectProperty('p1Field-0', 'TurnEffects', '["DISTANT","STEALTH","PREVENT_NONCOMBAT_4"]', 'Stealth and four points of non-combat prevention')
    ->tested('Stellarion Shift')
    ->covers(['graveyard-ability', 'banish', 'stealth', 'prevention', 'condition'], GA_SHIFT_GY_TEXT)
    ->register();

gaShiftGraveyard('stellarion-shift-graveyard-prevention-absorbs-the-next-non-combat-damage', handCard: 'Charge the Soul')
    ->passBoth()
    ->play('Charge the Soul')->pay(1)->answer(1, 'myField-0')->passBoth()
    ->expectDamage('p1Field-0', 0, 'The 1 damage from Charge the Soul was prevented')
    ->expectProperty('p1Field-0', 'TurnEffects', '["DISTANT","STEALTH","PREVENT_NONCOMBAT_3","vz4kc558yx-ACTION_ACTIVATED"]', 'Three points of prevention are left')
    ->expectDqEmpty()
    ->tested('Stellarion Shift')
    ->covers(['graveyard-ability', 'prevention'], GA_SHIFT_GY_TEXT)
    ->register();

// ---- Prima Materia ----

const GA_PRIMA_TEXT = '[Arisanna Bonus] **Brew** — Four Herbs with different names. [REST]: Draw a card into your memory. If CARDNAME was **brewed**, the next time an astra element source you control would deal damage to one or more units this turn, it deals that much damage plus 3 to those units instead.';

function gaPrimaMateria(string $slug, bool $brew) {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Prima Materia')
        ->seed('myHand', 'Prima Materia')->seed('myHand', 'Cosmic Bolt');
    if ($brew) foreach (['Manaroot', 'Blightroot', 'Fraysia', 'Razorvine'] as $herb) $s->seed('myField', $herb);
    return $s;
}

gaPrimaMateria('prima-materia-brewed-boosts-the-next-astra-damage-by-three', true)
    ->play('Prima Materia')->answer(1, 'YES')->answer(1, 'myField-1')->answer(1, 'myField-2')->answer(1, 'myField-3')->answer(1, 'myField-4')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->passBoth()
    ->play('Cosmic Bolt')->pay(3)->answer(1, 'theirField-0')->passBoth()
    ->expectDamage('p2Field-0', 7, 'Cosmic Bolt (an astra source) dealt 4 plus the 3 from the brewed Prima Materia')
    ->expectProperty('p1Field-1', 'Status', '1', 'Prima Materia rested to draw')
    ->expectDqEmpty()
    ->tested('Prima Materia')
    ->covers(['brew', 'rest', 'damage-boost'], GA_PRIMA_TEXT)
    ->register();

gaPrimaMateria('prima-materia-not-brewed-adds-nothing-to-astra-damage', false)
    ->play('Prima Materia')->pay(4)->passBoth()
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->passBoth()
    ->play('Cosmic Bolt')->pay(3)->answer(1, 'theirField-0')->passBoth()
    ->expectDamage('p2Field-0', 4, 'Played for its reserve cost: not brewed, so Cosmic Bolt deals its printed 4')
    ->expectDqEmpty()
    ->tested('Prima Materia')
    ->covers(['brew', 'condition', 'damage-boost'], GA_PRIMA_TEXT)
    ->register();

// ---- Astarte, Celestial Dawn ----

const GA_ASTARTE_FAST_TEXT = '[Class Bonus] **Fast Activation** *(You may activate this card at fast speed.)*';

// Astarte is a CLERIC: the Class Bonus champion fast-activates it in an opponent's turn window.
gaScenario('astarte-celestial-dawn-class-bonus-fast-activation-in-the-opponents-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Lucent Arbiter', level: 3)
    ->seed('myHand', 'Astarte, Celestial Dawn')
    ->endTurn()->answer(1, 'myHand-7')->pay(3)->passBoth()
    ->expectCardExists('myField', 'Astarte, Celestial Dawn', 'An ally was activated in a priority window: only possible at fast speed')
    ->tested('Astarte, Celestial Dawn')
    ->covers(['class-bonus', 'fast-activation'], GA_ASTARTE_FAST_TEXT)
    ->register();

// ---- Cataleptic Constellation ----

const GA_CATALEPTIC_TEXT = '**On Enter**: Rest target ally. That ally can\'t wake up for as long as you control Cataleptic Constellation.';

// The Constellation may target an ally you control. The rested Dungeon Guide is the control: it wakes in turn three's wake up phase, the targeted Giant Tortoise does not.
gaScenario('cataleptic-constellation-the-targeted-ally-stays-rested-through-the-wake-up-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cataleptic Constellation')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 1])
    ->seed('myHand', 'Cataleptic Constellation')
    ->play('Cataleptic Constellation')->answer(1, 'NO')->pay(2)->target('myField-1')
    ->reachTurnThree()
    ->expectProperty('p1Field-1', 'Status', '1', 'The locked Giant Tortoise did not wake up in the wake up phase')
    ->expectProperty('p1Field-2', 'Status', '2', 'The control Dungeon Guide woke up as usual')
    ->tested('Cataleptic Constellation')
    ->covers(['on-enter', 'rest', 'wake-up-lock'], GA_CATALEPTIC_TEXT)
    ->register();

// ---- Reflect the Skies ----

const GA_REFLECT_TEXT = 'Put the bottom four cards of your deck on top of your deck in a random order. **Glimpse 2**.';

// The four bottom cards are all Foretold Blooms, so the random order cannot matter: the two glimpsed cards must be Blooms (the Dungeon Guides that start on top would be refused).
gaScenario('reflect-the-skies-the-bottom-four-cards-are-the-ones-glimpsed-on-top')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Reflect the Skies', classBonus: true)
    ->patch('myDeck-5', ['CardID' => 'lnhzj43qiw'])->patch('myDeck-6', ['CardID' => 'lnhzj43qiw'])
    ->patch('myDeck-7', ['CardID' => 'lnhzj43qiw'])->patch('myDeck-8', ['CardID' => 'lnhzj43qiw'])
    ->seed('myHand', 'Reflect the Skies')
    ->play('Reflect the Skies')->pay(2)->answer(1, 'Top=lnhzj43qiw,lnhzj43qiw;Bottom=')
    ->expectZoneCount('myDeck', 9, 1, 'The glimpse keeps both cards in the deck')
    ->expectDqEmpty()
    ->tested('Reflect the Skies')
    ->covers(['zone-movement', 'random-order', 'glimpse'], GA_REFLECT_TEXT)
    ->register();
