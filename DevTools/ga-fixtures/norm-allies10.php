<?php
/**
 * Phase 3d (Norm): allies, tenth batch (bulwark, ephemeral, steadfast and loose ends). 'quiet' deck.
 */

// ---- Fatebound Caracal (third printing) ----

gaNormIntercept('fatebound-caracal-third-printing-intercept-redirects-the-attack-on-the-champion', '#r1nS4nDTyM', '**Intercept** *(Whenever your champion is attacked while this ally is awake, you may redirect that attack to this ally.)*');
gaNormRetort('fatebound-caracal-third-printing-retort-two-retaliates-for-two-extra-power', '#r1nS4nDTyM', 3, 'Retaliating with 1 + 2 power', '**Retort 2** *(As long as this ally is retaliating, it gets +2 [POWER].)*');

// ---- Airborne Squirrel ----

gaScenario('airborne-squirrel-enters-with-its-printed-stats')
    ->deckTemplate('quiet', 'Airborne Squirrel')
    ->readyFor('Airborne Squirrel')
    ->seed('myHand', 'Airborne Squirrel')
    ->play('Airborne Squirrel')->pay(3)
    ->expectCardExists('myField', 'Airborne Squirrel', 'Paying the printed reserve 3 played it')
    ->expectPower('p1Field-1', 2, 'Printed 2 power')
    ->expectLife('p1Field-1', 3, 'Printed 3 life')
    ->tested('Airborne Squirrel')
    ->covers(['vanilla'], 'Airborne Squirrel (no rules text)')
    ->register();

// ---- Valiant Protector ----

gaScenario('valiant-protector-class-bonus-enters-with-a-bulwark-counter')
    ->deckTemplate('quiet', 'Valiant Protector')
    ->readyFor('Valiant Protector', classBonus: true)
    ->seed('myHand', 'Valiant Protector')
    ->play('Valiant Protector')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '{"bulwark":1}', '[Class Bonus] Bulwark: the Protector entered with a bulwark counter')
    ->tested('Valiant Protector')
    ->covers(['class-bonus', 'keyword', 'bulwark'], '[Class Bonus] **Bulwark**')
    ->register();

gaScenario('valiant-protector-without-the-class-bonus-enters-without-a-bulwark-counter')
    ->deckTemplate('quiet', 'Valiant Protector')
    ->readyFor('Valiant Protector')
    ->seed('myHand', 'Valiant Protector')
    ->play('Valiant Protector')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No class bonus: no bulwark counter')
    ->tested('Valiant Protector')
    ->covers(['class-bonus', 'keyword', 'bulwark', 'condition'], '[Class Bonus] **Bulwark**')
    ->register();

gaScenario('rampart-defender-bulwark-enters-with-a-bulwark-counter')
    ->deckTemplate('quiet', 'Rampart Defender')
    ->readyFor('Rampart Defender')
    ->seed('myHand', 'Rampart Defender')
    ->play('Rampart Defender')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '{"bulwark":1}', 'Bulwark: the Defender entered with a bulwark counter')
    ->tested('Rampart Defender')
    ->covers(['keyword', 'bulwark'], '**Bulwark** *(This ally enters the field with a **bulwark** counter on it. If combat damage would be dealt to an ally with any **bulwark** counters on it, remove one and prevent that damage instead.)*')
    ->register();

gaScenario('valiant-protector-a-bulwark-counter-prevents-the-combat-damage-and-is-removed')
    ->deckTemplate('quiet', 'Valiant Protector')
    ->readyFor('Valiant Protector')
    ->seed('myField', 'Valiant Protector', properties: ['Counters' => ['bulwark' => 1]])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectDamage('p1Field-1', 0, 'The bulwark counter prevented the combat damage')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The bulwark counter was removed')
    ->tested('Valiant Protector')
    ->covers(['keyword', 'bulwark', 'prevention', 'combat'], '**Bulwark**')
    ->register();

// ---- War Marshal ----

gaNormRetort('war-marshal-class-bonus-steadfast-retaliates-while-rested', 'War Marshal', 2, '[Class Bonus] A rested steadfast ally retaliated with its 2 power', '[Class Bonus] **Steadfast** *(This ally can retaliate while rested and doesn\'t rest to do so.)*', true, ['class-bonus', 'keyword', 'steadfast', 'retaliate'], ['Status' => 1]);

gaScenario('war-marshal-class-bonus-equestrian-with-a-horse-gets-plus-one-power-and-life')
    ->deckTemplate('quiet', 'War Marshal')
    ->readyFor('War Marshal', classBonus: true)
    ->seed('myField', 'War Marshal')
    ->seed('myField', 'Ordinary Horse')
    ->observe()
    ->expectPower('p1Field-1', 3, '[Class Bonus] Equestrian: 2 + 1 power')
    ->expectLife('p1Field-1', 4, '[Class Bonus] Equestrian: 3 + 1 life')
    ->tested('War Marshal')
    ->covers(['class-bonus', 'equestrian', 'stat-change'], '[Class Bonus] ***Equestrian*** — As long as you control a Horse ally, CARDNAME gets +1 [POWER] and +1 [LIFE].')
    ->register();

gaScenario('war-marshal-without-the-class-bonus-has-its-printed-stats-with-a-horse')
    ->deckTemplate('quiet', 'War Marshal')
    ->readyFor('War Marshal')
    ->seed('myField', 'War Marshal')
    ->seed('myField', 'Ordinary Horse')
    ->observe()
    ->expectPower('p1Field-1', 2, 'No class bonus: 2 power')
    ->expectLife('p1Field-1', 3, 'No class bonus: 3 life')
    ->tested('War Marshal')
    ->covers(['class-bonus', 'equestrian', 'condition'], '[Class Bonus] ***Equestrian*** — As long as you control a Horse ally, CARDNAME gets +1 [POWER] and +1 [LIFE].')
    ->register();

// ---- Night Barker ----

gaScenario('night-barker-on-death-returns-to-the-field-ephemeral')
    ->deckTemplate('quiet', 'Night Barker')
    ->readyFor('Night Barker')
    ->seed('myField', 'Night Barker')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectCardExists('myField', 'Night Barker', 'On Death: the Barker came back to the field')
    ->expectProperty('p1Field-1', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN","ENTERED_THIS_TURN","EPHEMERAL"]', 'It returned ephemeral (carrying the damaged-since-last-turn marker of the object that died)')
    ->tested('Night Barker')
    ->covers(['on-death', 'ephemeral', 'zone-movement'], '**On Death: **Return CARDNAME from your graveyard to the field. It becomes **ephemeral**.')
    ->register();

gaScenario('unyielding-wraithguard-on-death-returns-to-the-field-ephemeral')
    ->deckTemplate('quiet', 'Unyielding Wraithguard')
    ->readyFor('Unyielding Wraithguard')
    ->seed('myField', 'Unyielding Wraithguard')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectCardExists('myField', 'Unyielding Wraithguard', 'On Death: the Wraithguard came back to the field')
    ->expectProperty('p1Field-1', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN","ENTERED_THIS_TURN","EPHEMERAL"]', 'It returned ephemeral (carrying the damaged-since-last-turn marker of the object that died)')
    ->tested('Unyielding Wraithguard')
    ->covers(['on-death', 'ephemeral', 'zone-movement'], '**On Death**: Return Unyielding Wraithguard from your graveyard to the field. It becomes **ephemeral**.')
    ->register();

// ---- Veiled Oracle ----

gaScenario('veiled-oracle-enter-becomes-ephemeral-and-glimpses-three')
    ->deckTemplate('quiet', 'Veiled Oracle')
    ->readyFor('Veiled Oracle')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myHand', 'Veiled Oracle')
    ->play('Veiled Oracle')->pay(3)->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","EPHEMERAL"]', 'On Enter: the Oracle became ephemeral')
    ->tested('Veiled Oracle')
    ->covers(['on-enter', 'ephemeral', 'glimpse'], '**On Enter**: Veiled Oracle becomes **ephemeral**. **Glimpse 3**.')
    ->register();
