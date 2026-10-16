<?php
/**
 * Phase 3e (Fire), the cards the earlier fire-*.php files left uncovered. Fire is a basic element: no lineage needed. 'quiet' deck.
 */

// ---- Actions ----

gaScenario('feu-awakening-ciel-bonus-discards-everything-and-banishes-two-graveyard-cards-with-omens')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Ciel, Omenbringer', lineage: ['Spirit of Fire'])
    ->seed('myMemory', 'Giant Tortoise')
    ->seed('myHand', 'Feu Awakening')
    ->play('Feu Awakening')
    ->answer(1, 'myGraveyard-1')->answer(1, 'myGraveyard-1')
    ->expectZoneCount('myHand', 0, 1, 'Everything in hand was discarded')
    ->expectZoneCount('myMemory', 0, 1, 'And everything in memory')
    ->expectZoneCount('myBanish', 2, 1, 'Two graveyard cards banished')
    ->expectProperty('p1Banish-0', 'Counters', '{"omen":1}', 'With an omen counter')
    ->expectProperty('p1Banish-1', 'Counters', '{"omen":1}', 'On each')
    ->tested('Feu Awakening')
    ->covers(['ciel-bonus', 'discard', 'banish', 'omen'], '[Ciel Bonus] Discard all cards from your hand and memory. Then banish up to two cards from your graveyard. Put an **omen** counter on each card banished this way.')
    ->register();

gaScenario('feu-awakening-interdiction-the-opponent-gets-no-opportunity-to-respond')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myHand', 'Flashfire Horse', player: 2)
    ->seed('myHand', 'Feu Awakening')
    ->play('Feu Awakening')
    ->expectDqEmpty()
    ->tested('Feu Awakening')
    ->covers(['keyword', 'interdiction'], '**Interdiction** *(As long as this card\'s activation is on the effects stack, players can\'t gain opportunity.)*')
    ->register();

gaScenario('molten-echo-deals-one-damage-to-a-unit-and-adds-a-sheen-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myMastery', 'Fractured Memories')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Molten Echo')
    ->castSpell('Molten Echo', pay: 2)->target('theirField-1')
    ->expectDamage('p2Field-1', 1, 'One damage to the target unit')
    ->expectProperty('p1Mastery-0', 'Counters', '{"sheen":1}', 'A sheen counter on Fractured Memories')
    ->tested('Molten Echo')
    ->covers(['damage', 'targeting', 'sheen'], 'Deal 1 damage to target unit. Then put a **sheen** counter on your Fractured Memories.')
    ->register();

gaScenario('molten-echo-merlin-bonus-kindle-two-pays-with-graveyard-fire-cards')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Merlin, Memorite Vassal', lineage: ['Spirit of Fire'])
    ->seed('myMastery', 'Fractured Memories')
    ->seed('myGraveyard', 'Red Slime')->seed('myGraveyard', 'Red Slime')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Molten Echo')
    ->play('Molten Echo')->answer(1, 'myGraveyard-0')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 2, 1, 'Kindle 2: both graveyard fire cards were banished')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid from hand')
    ->tested('Molten Echo')
    ->covers(['merlin-bonus', 'kindle'], '[Merlin Bonus] **Kindle 2**')
    ->register();

gaScenario('molten-echo-merlin-bonus-ephemerate-activates-it-from-the-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Merlin, Memorite Vassal', lineage: ['Spirit of Fire'])
    ->seed('myMastery', 'Fractured Memories')
    ->seed('myGraveyard', 'Molten Echo')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(2)->target('theirField-1')
    ->expectDamage('p2Field-1', 1, 'The graveyard copy resolved')
    ->expectZoneCount('myGraveyard', 0, 1, 'And it did not return to the graveyard (ephemeral)')
    ->tested('Molten Echo')
    ->covers(['merlin-bonus', 'ephemerate', 'cost'], '[Merlin Bonus] **Ephemerate** — (2) *(You may activate this card from your graveyard by paying this cost. Action cards played this way become **ephemeral** on the effects stack.)*')
    ->register();

// ---- Allies ----

gaScenario('cauterizing-seraphim-imbued-enter-deals-damage-to-all-other-norm-and-basic-allies')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Clarent, Sword of Peace
1 Backup Charger
1 Purifying Thurible
# Main
20 Foretold Bloom
DECK
    )
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myField', 'Red Slime', player: 2)
    ->seed('myField', 'Gemini Starbearer', player: 2)
    ->seed('myHand', 'Cauterizing Seraphim')
    ->play('Cauterizing Seraphim')->answer(1, 'YES')->pay(4)->pass(2)
    ->expectDamage('p2Field-1', 2, 'A norm ally takes X = 2')
    ->expectZoneCount('theirField', 4, 1, 'The 1-life basic element Red Slime took X = 2 and died')
    ->expectDamage('p2Field-2', 0, 'The advanced element ally is untouched')
    ->tested('Cauterizing Seraphim')
    ->covers(['imbue', 'on-enter', 'damage', 'condition'], '**On Enter:** If CARDNAME was **imbued**, deal X damage to all other norm and basic element allies.')
    ->register();

// ---- Attacks ----

gaScenario('conflagrative-trounce-ciel-bonus-two-omens-with-the-same-reserve-cost-get-plus-two-power')
    ->standardDeck('Conflagrative Trounce')
    ->champion(cardName: 'Ciel, Omenbringer', lineage: ['Spirit of Fire'])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Conflagrative Trounce')
    ->reachTurnThree()
    ->playSlot(7)->pay(1)->target('theirField-0')
    ->expectDamage('p2Field-0', 4, 'Printed 2 + 2: two omens share a reserve cost')
    ->tested('Conflagrative Trounce')
    ->covers(['ciel-bonus', 'omen', 'stat-change', 'condition'], '[Ciel Bonus] As long as you have two or more omens with the same reserve cost, Conflagrative Trounce gets +2[POWER].')
    ->register();

gaScenario('conflagrative-trounce-omens-with-different-reserve-costs-do-not-count')
    ->standardDeck('Conflagrative Trounce')
    ->champion(cardName: 'Ciel, Omenbringer', lineage: ['Spirit of Fire'])
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Giant Tortoise', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Conflagrative Trounce')
    ->reachTurnThree()
    ->playSlot(7)->pay(1)->target('theirField-0')
    ->expectDamage('p2Field-0', 2, 'Different reserve costs: the printed 2')
    ->tested('Conflagrative Trounce')
    ->covers(['ciel-bonus', 'omen', 'condition'], '[Ciel Bonus] As long as you have two or more omens with the same reserve cost, Conflagrative Trounce gets +2[POWER].')
    ->register();

gaScenario('flickering-afterglow-merlin-bonus-costs-one-less-per-sheen-counter')
    ->standardDeck('Flickering Afterglow')
    ->champion(cardName: 'Merlin, Memorite Vassal', lineage: ['Spirit of Fire'])
    ->seed('myMastery', 'Fractured Memories', properties: ['Counters' => ['sheen' => 2]])
    ->seed('myHand', 'Flickering Afterglow')
    ->reachTurnThree()
    ->playSlot(7)->pay(1)->target('theirField-0')
    ->expectDamage('p2Field-0', 4, 'Cost 3 - 2 sheen = 1, and the printed 4 power landed')
    ->tested('Flickering Afterglow')
    ->covers(['merlin-bonus', 'sheen', 'cost-reduction'], '[Merlin Bonus] This card costs 1 less to activate for each **sheen** counter on your Fractured Memories.')
    ->register();

// ---- Champions ----

gaScenario('polkhawk-bombastic-shot-ranger-reaction-cards-cost-one-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Polkhawk, Bombastic Shot', lineage: ['Spirit of Fire'])
    ->seed('myHand', 'Bombastic Sprint')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)
    ->answer(1, 'myHand-7')->pay(1)
    ->expectProperty('p1Field-0', 'TurnEffects', '["WAS_ATTACKED","DISTANT","vz4kc558yx-ACTION_ACTIVATED","DAMAGED_SINCE_LAST_TURN"]', 'The reaction was paid with a single reserve card and resolved: the champion is distant')
    ->tested('Polkhawk, Bombastic Shot')
    ->covers(['static', 'cost-reduction'], 'Ranger Reaction cards you activate cost 1 less to activate.')
    ->register();

gaScenario('polkhawk-bombastic-shot-ranged-two-while-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Polkhawk, Bombastic Shot', lineage: ['Spirit of Fire'])
    ->seed('myField', 'Executioner\'s Spear')
    ->seed('myHand', 'Bombastic Sprint')
    ->reachTurnThree(fastCardInHand: true)
    ->play('Bombastic Sprint')->pay(1)
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->expectDamage('p2Field-0', 3, 'The Spear\'s 1 power + 2 from Ranged 2 while distant')
    ->tested('Polkhawk, Bombastic Shot')
    ->covers(['keyword', 'ranged'], '**Ranged 2** *(As long as this unit is **distant**, its attacks get +2 [POWER].)*')
    ->register();

// ---- Weapons ----

gaScenario('contraband-revolver-class-bonus-attacking-a-champion-gets-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Contraband Revolver', classBonus: true)
    ->seed('myField', 'Contraband Revolver', properties: ['Subcards' => [GaScenario::id('Plated Bullet')]])
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->pass(2)->pass(1)
    ->expectDamage('p2Field-0', 5, 'The Revolver\'s 1 + the Plated Bullet\'s 2 + 2 for attacking a champion')
    ->tested('Contraband Revolver')
    ->covers(['class-bonus', 'on-attack', 'stat-change'], '[Class Bonus] **On Attack:** If the attacker is attacking a champion, CARDNAME gets +2 [POWER] for this attack.')
    ->register();

gaScenario('contraband-revolver-without-the-class-bonus-has-no-extra-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Contraband Revolver')
    ->seed('myField', 'Contraband Revolver', properties: ['Subcards' => [GaScenario::id('Plated Bullet')]])
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->pass(2)->pass(1)
    ->expectDamage('p2Field-0', 3, 'No Class Bonus: the Revolver\'s 1 + the Bullet\'s 2')
    ->tested('Contraband Revolver')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] **On Attack:** If the attacker is attacking a champion, CARDNAME gets +2 [POWER] for this attack.')
    ->register();

gaScenario('contraband-revolver-attacking-an-ally-gets-no-extra-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Contraband Revolver', classBonus: true)
    ->seed('myField', 'Contraband Revolver', properties: ['Subcards' => [GaScenario::id('Plated Bullet')]])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-1')->pass(2)->pass(1)
    ->expectDamage('p2Field-1', 3, 'Attacking an ally: no +2')
    ->tested('Contraband Revolver')
    ->covers(['condition', 'on-attack'], '[Class Bonus] **On Attack:** If the attacker is attacking a champion, CARDNAME gets +2 [POWER] for this attack.')
    ->register();

gaScenario('bombard-flarecannon-polkhawk-bonus-a-fire-card-in-the-intent-deals-two-damage-to-each-defending-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Polkhawk, Bombastic Shot', lineage: ['Spirit of Fire'])
    ->seed('myField', 'Bombard Flarecannon', properties: ['Subcards' => [GaScenario::id('Penetrator Round')]])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(1, 'myField-1')
    ->expectDqEmpty()
    ->expectDamage('p2Field-1', 2, 'Each unit the defending player controls takes 2')
    ->expectDamage('p2Field-2', 2, 'Including every ally')
    ->tested('Bombard Flarecannon')
    ->covers(['polkhawk-bonus', 'on-attack', 'damage', 'condition'], '[Polkhawk Bonus] **On Attack:** If there is a fire element card in this attacker\'s intent, deal 2 damage to each unit the defending player controls. *(Apply this effect only if your champion is Polkhawk.)*')
    ->register();

gaScenario('bombard-flarecannon-without-a-fire-card-in-the-intent-deals-no-splash')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Polkhawk, Bombastic Shot', lineage: ['Spirit of Fire'])
    ->seed('myField', 'Bombard Flarecannon', properties: ['Subcards' => [GaScenario::id('Plated Bullet')]])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->pass(2)->pass(1)
    ->expectDamage('p2Field-1', 0, 'A norm Bullet is not a fire element card: the Tortoise is untouched')
    ->tested('Bombard Flarecannon')
    ->covers(['polkhawk-bonus', 'condition'], '[Polkhawk Bonus] **On Attack:** If there is a fire element card in this attacker\'s intent, deal 2 damage to each unit the defending player controls. *(Apply this effect only if your champion is Polkhawk.)*')
    ->register();

gaScenario('lacunas-grasp-ciel-bonus-pay-x-up-to-your-omens-for-plus-x-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Ciel, Omenbringer', lineage: ['Spirit of Fire'])
    ->seed('myField', 'Lacuna\'s Grasp')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myBanish', 'Giant Tortoise', properties: ['Counters' => ['omen' => 1]])
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(1, '2')->pay(2)
    ->expectDamage('p2Field-0', 3, 'The Grasp\'s 1 + X = 2 (two omens, two reserve cards paid)')
    ->expectZoneCount('myMemory', 2, 1, 'X = 2 was paid')
    ->tested("Lacuna's Grasp")
    ->covers(['ciel-bonus', 'omen', 'on-attack', 'stat-change'], '[Ciel Bonus] **On Attack**: If there are no cards in the attacker\'s intent, you may pay (X). **X** can\'t be more than the amount of omens you have. If you do, Lacuna\'s Grasp gets +**X**[POWER].')
    ->register();

// ---- Items ----

gaScenario('mantle-of-the-abyss-alice-bonus-pay-three-and-banish-generates-rile-the-abyss-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Alice, Golden Queen', lineage: ['Spirit of Fire'])
    ->seed('myField', 'Mantle of the Abyss')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)
    ->expectCardExists('myBanish', 'Mantle of the Abyss', 'The Mantle banished itself')
    ->expectCardExists('myMemory', 'Rile the Abyss', 'A generated Rile the Abyss went into memory')
    ->tested('Mantle of the Abyss')
    ->covers(['alice-bonus', 'activated-ability', 'banish-self', 'generate'], '[Alice Bonus] (3), **Banish CARDNAME:** **Generate** a Rile the Abyss card and put it into your memory.')
    ->register();

gaScenario('mantle-of-the-abyss-alice-bonus-sacrifice-a-specter-ally-to-activate-it-from-the-material-deck')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Alice, Golden Queen', lineage: ['Spirit of Fire'])
    ->seed('myMaterial', 'Mantle of the Abyss')
    ->seed('myField', 'Mourning Veilbound')
    ->action(1, 10002, 'myMaterial-4!FSM!')->answer(1, 'myField-1')
    ->expectCardExists('myField', 'Mantle of the Abyss', 'The Mantle entered the field')
    ->expectZoneCount('myField', 2, 1, 'The Specter ally was sacrificed')
    ->tested('Mantle of the Abyss')
    ->covers(['alice-bonus', 'material-deck-activation', 'sacrifice'], '[Alice Bonus] You may sacrifice a Specter ally to activate this card from your material deck.')
    ->register();

gaScenario('sigil-of-budding-embers-diao-chan-bonus-removes-x-glimmer-counters-to-deal-x-damage-to-a-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diao Chan, Idyll Corsage', lineage: ['Spirit of Fire'])
    ->patch('myField-0', ['Counters' => ['glimmer' => 3]])
    ->seed('myField', 'Sigil of Budding Embers', properties: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, '2')->target('theirField-0')
    ->expectDamage('p2Field-0', 2, 'X = 2 damage to the target champion')
    ->expectProperty('p1Field-0', 'Counters', '{"glimmer":1}', 'Two of the three glimmer counters were removed')
    ->expectCardExists('myBanish', 'Sigil of Budding Embers', 'The Sigil banished itself')
    ->tested('Sigil of Budding Embers')
    ->covers(['diao-chan-bonus', 'activated-ability', 'rest', 'remove-counters', 'banish-self', 'damage', 'targeting'], '[Diao Chan Bonus] [REST]**, Remove X glimmer counters from your champion, Banish CARDNAME:** As a Spell, deal **X** damage to target champion.')
    ->register();

gaScenario('sigil-of-budding-embers-hindered-enters-the-field-rested')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myMaterial', 'Sigil of Budding Embers')
    ->endTurn()->endTurn(2)->target('myMaterial-4')
    ->expectProperty('p1Field-1', 'Status', '1', 'Hindered: it entered rested')
    ->tested('Sigil of Budding Embers')
    ->covers(['keyword', 'hindered'], '**Hindered** *(This object enters the field rested.)*')
    ->register();

// ---- Boons ----

gaScenario('greater-boon-of-vritra-end-phase-deals-damage-to-a-random-opposing-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Vritra', level: 1)
    ->seed('myHand', 'Greater Boon of Vritra')
    ->castSpell('Greater Boon of Vritra', pay: 3)
    ->endTurn()
    ->expectDamage('p2Field-0', 2, 'X = 1 fire boon (itself) + Y = 1 fire card in the lineage (Spirit of Fire)')
    ->tested('Greater Boon of Vritra')
    ->covers(['end-phase', 'damage', 'random'], 'At the beginning of your end phase, deal **X**+**Y** damage to a random target champion you don’t control, where **X** is the amount of fire element boons in your pantheon, and **Y** is the amount of fire element cards in your champion’s lineage.')
    ->register();

// ---- Uniques ----

gaScenario('bill-chimney-sweep-enter-sacrifices-a-specter-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Mourning Veilbound')
    ->seed('myHand', 'Bill, Chimney Sweep')
    ->play('Bill, Chimney Sweep')->pay(3)->answer(1, 'myField-1')
    ->expectCardExists('myField', 'Bill, Chimney Sweep', 'Bill stays')
    ->expectCardExists('myGraveyard', 'Mourning Veilbound', 'The other Specter ally was sacrificed')
    ->tested('Bill, Chimney Sweep')
    ->covers(['on-enter', 'sacrifice', 'condition'], '**On Enter:** If CARDNAME is not **ephemeral**, sacrifice a Specter ally.')
    ->register();

gaScenario('bill-chimney-sweep-alice-bonus-ephemerate-skips-the-sacrifice')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Alice, Golden Queen', lineage: ['Spirit of Fire'])
    ->seed('myField', 'Mourning Veilbound')
    ->seed('myGraveyard', 'Bill, Chimney Sweep')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->answer(1, 'myHand-0')->pay(5)
    ->expectCardExists('myField', 'Bill, Chimney Sweep', 'Bill came back from the graveyard')
    ->expectCardExists('myField', 'Mourning Veilbound', 'And it was ephemeral: no Specter ally was sacrificed')
    ->tested('Bill, Chimney Sweep')
    ->covers(['alice-bonus', 'ephemerate', 'cost'], '[Alice Bonus] **Ephemerate — **(5), Discard a card. *(You may activate this card from your graveyard by paying this cost. Ally cards played this way become **ephemeral** as they enter the field.)*')
    ->register();

gaScenario('crimson-tear-level-one-gets-plus-one-power-attacking-a-human')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Crimson Tear', level: 1)
    ->seed('myField', 'Crimson Tear')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->answer(2, 'myField-1')
    ->expectZoneCount('theirField', 1, 1, 'The 3-life Human Dungeon Guide took 2 + 1 and died')
    ->tested('Crimson Tear')
    ->covers(['level-gate', 'stat-change', 'condition'], '[Level 1+] As long as CARDNAME is attacking or retaliating against a Human, CARDNAME gets +1 [POWER]. ')
    ->register();

gaScenario('crimson-tear-level-zero-has-its-printed-power-against-a-human')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Crimson Tear')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->answer(2, 'myField-1')
    ->expectDamage('p2Field-1', 2, 'Below level 1: the printed 2')
    ->tested('Crimson Tear')
    ->covers(['level-gate', 'condition'], '[Level 1+] As long as CARDNAME is attacking or retaliating against a Human, CARDNAME gets +1 [POWER]. ')
    ->register();

gaScenario('crimson-tear-level-two-reaction-that-targets-it-draws-into-memory-and-wakes-it-on-the-opponents-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Evasive Positioning', level: 2)
    ->seed('myField', 'Crimson Tear', properties: ['Status' => 1])
    ->seed('myHand', 'Evasive Positioning')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)
    ->answer(1, 'myHand-7')->pay(1)->target('myField-1')
    ->expectProperty('p1Field-1', 'Status', '2', 'Level 2: not your turn, so Crimson Tear woke up')
    ->expectZoneCount('myMemory', 2, 1, 'The reserved card plus the card drawn into memory')
    ->tested('Crimson Tear')
    ->covers(['level-gate', 'trigger', 'draw', 'memory', 'wake-up'], '[Level 2+] Whenever you activate a Reaction card that targets CARDNAME, draw a card into your memory. Then if it\'s not your turn, wake up CARDNAME.')
    ->register();

gaScenario('zinn-volnia-abbess-arisanna-bonus-summons-volnia-tokens-instead-of-silvershine-and-fraysia')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Astral Zenith', lineage: ['Spirit of Wind', 'Lorraine, Arclight Saber', 'Spirit of Fire'])
    ->seed('myField', 'Zinn, Volnia Abbess')
    ->seed('myHand', 'Apothecary\'s Harvest')
    ->castSpell('Apothecary\'s Harvest', pay: 3)
    ->expectCardExists('myField', 'Volnia', 'A Volnia token arrived')
    ->expectZoneCount('myField', 8, 1, 'Champion, Zinn, four herbs and two Volnia tokens (no Silvershine or Fraysia)')
    ->tested('Zinn, Volnia Abbess')
    ->covers(['arisanna-bonus', 'replacement', 'summon', 'token'], '[Arisanna Bonus] If you would **summon** one or more Silvershine and/or Fraysia tokens, **summon** that many Volnia tokens instead.')
    ->register();
