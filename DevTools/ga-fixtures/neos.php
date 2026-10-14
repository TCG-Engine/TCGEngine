<?php
/**
 * Phase 3e (Neos). readyFor() enables the neos element (and the class bonus where asked) through the champion lineage. 'quiet' deck.
 */

// ---- Production Crawldroid ----

gaScenario('production-crawldroid-enter-summons-two-powercell-tokens')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Production Crawldroid')
    ->seed('myHand', 'Production Crawldroid')
    ->play('Production Crawldroid')->pay(3)
    ->expectZoneCount('myField', 4, 1, 'The champion, the ally and two Powercell tokens')
    ->tested('Production Crawldroid')
    ->covers(['on-enter', 'summon', 'token'], '**On Enter:** **Summon** two Powercell tokens.')
    ->register();

// ---- Automaton Forgewarden ----

gaScenario('automaton-forgewarden-class-bonus-gets-plus-one-power-and-life-per-token-up-to-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Automaton Forgewarden', classBonus: true)
    ->seed('myField', 'Automaton Forgewarden')
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->observe()
    ->expectPower('p1Field-1', 5, '2 power + 3 (up to three of the four tokens)')
    ->expectLife('p1Field-1', 5, '2 life + 3')
    ->tested('Automaton Forgewarden')
    ->covers(['class-bonus', 'stat-change', 'tokens'], '[Class Bonus] CARDNAME gets +1[POWER] and +1[LIFE] for each of up to three tokens you control.')
    ->register();

// ---- Fabricator Slime ----

gaScenario('fabricator-slime-enters-with-a-buff-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fabricator Slime', classBonus: true, level: 3)
    ->seed('myHand', 'Fabricator Slime')
    ->play('Fabricator Slime')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Entered with a buff counter')
    ->tested('Fabricator Slime')
    ->covers(['enters-with-counter', 'buff'], 'CARDNAME enters the field with a **buff** counter on it.')
    ->register();

gaScenario('fabricator-slime-class-bonus-rest-summons-a-baby-slime-per-buff-counter-up-to-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fabricator Slime', classBonus: true, level: 3)
    ->seed('myField', 'Fabricator Slime', properties: ['Counters' => ['buff' => 2]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myField', 4, 1, 'Two buff counters: two Baby Slime tokens beside the champion and the Slime')
    ->tested('Fabricator Slime')
    ->covers(['class-bonus', 'activated-ability', 'rest', 'summon', 'token'], '[Class Bonus] [REST]: For each of up to three **buff** counters on CARDNAME, **summon** a Baby Slime token.')
    ->register();

// ---- Triumphant Mechanic ----

gaScenario('triumphant-mechanic-enter-with-three-or-more-tokens-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Triumphant Mechanic')
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->seed('myHand', 'Triumphant Mechanic')
    ->play('Triumphant Mechanic')->pay(3)
    ->expectZoneCount('myMemory', 4, 1, 'Three payments plus the card drawn into memory')
    ->tested('Triumphant Mechanic')
    ->covers(['on-enter', 'tokens', 'draw-into-memory', 'condition'], '**On Enter:** If you control three or more tokens, draw a card into your memory.')
    ->register();

gaScenario('triumphant-mechanic-enter-with-fewer-than-three-tokens-draws-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Triumphant Mechanic')
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->seed('myHand', 'Triumphant Mechanic')
    ->play('Triumphant Mechanic')->pay(3)
    ->expectZoneCount('myMemory', 3, 1, 'Only the three payments')
    ->tested('Triumphant Mechanic')
    ->covers(['on-enter', 'tokens', 'condition'], 'If you control three or more tokens')
    ->register();

gaScenario('triumphant-mechanic-on-attack-with-three-or-more-tokens-recovers-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Triumphant Mechanic', damage: 5)
    ->seed('myField', 'Triumphant Mechanic')
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->pass(2)
    ->expectDamage('p1Field-0', 2, 'Recover 3: 5 down to 2')
    ->tested('Triumphant Mechanic')
    ->covers(['on-attack', 'tokens', 'recover', 'condition'], '**On Attack:** If you control three or more tokens, **recover 3**.')
    ->register();

// ---- Neos Elemental ----

gaScenario('neos-elemental-class-bonus-costs-one-less-per-token-and-gets-plus-one-power-and-life-per-token')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Neos Elemental', classBonus: true)
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->seed('myHand', 'Neos Elemental')
    ->play('Neos Elemental')->pay(6)
    ->expectPower('p1Field-5', 5, '1 power + 4 tokens')
    ->expectLife('p1Field-5', 5, '1 life + 4 tokens')
    ->tested('Neos Elemental')
    ->covers(['class-bonus', 'cost-reduction', 'stat-change', 'tokens'], '[Class Bonus] This card costs 1 less to activate for each token object you control.', 'CARDNAME gets +1 [POWER] and +1 [LIFE] for each token object you control.')
    ->register();

// ---- Reactivate Drone ----

gaScenario('reactivate-drone-summons-a-rested-automaton-drone-with-a-buff-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Reactivate Drone', classBonus: true)
    ->seed('myHand', 'Reactivate Drone')
    ->play('Reactivate Drone')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'A buff counter')
    ->expectProperty('p1Field-1', 'Status', '1', 'Rested')
    ->tested('Reactivate Drone')
    ->covers(['summon', 'token', 'counter', 'rested'], '**Summon** an Automaton Drone token rested with a **buff** counter on it.')
    ->register();

gaScenario('reactivate-drone-ephemerate-two-activates-it-from-the-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Reactivate Drone', classBonus: true)
    ->seed('myGraveyard', 'Reactivate Drone')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(2)
    ->expectCardExists('myField', 'Automaton Drone', 'Activated from the graveyard for (2): the Drone was summoned')
    ->tested('Reactivate Drone')
    ->covers(['ephemerate', 'graveyard-activation'], '**Ephemerate —** (2)')
    ->register();

// ---- Refabrication ----

gaScenario('refabrication-influence-five-or-less-draws-two-cards')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Refabrication')
    ->emptyZone('myHand')
    ->seed('myHand', 'Refabrication')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->action(1, 10002, 'myHand-0!FSM!')->pay(3)
    ->expectZoneCount('myHand', 2, 1, 'Four in hand minus the spell and three payments leaves none, plus the two cards drawn')
    ->tested('Refabrication')
    ->covers(['influence', 'draw', 'condition'], 'If your **influence** is five or less, draw two cards.')
    ->register();

gaScenario('refabrication-may-sacrifice-two-tokens-rather-than-pay-its-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Refabrication')
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->seed('myHand', 'Refabrication')
    ->play('Refabrication')->answer(1, 'YES')->answer(1, 'myField-1')->answer(1, 'myField-1')
    ->expectZoneCount('myField', 1, 1, 'Both Automaton Drone tokens were sacrificed')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid from hand')
    ->tested('Refabrication')
    ->covers(['alternative-cost', 'sacrifice', 'token'], 'You may sacrifice two tokens rather than pay this card\'s reserve cost.')
    ->register();

// ---- Powerforged Burst ----

gaScenario('powerforged-burst-sacrifices-three-powercells-to-deal-two-damage-three-times-and-draw-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Powerforged Burst', classBonus: true)
    ->seed('myField', 'Powercell')->seed('myField', 'Powercell')->seed('myField', 'Powercell')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Powerforged Burst')
    ->play('Powerforged Burst')->answer(1, 'myField-1&myField-2&myField-3')->target('theirField-1')
    ->expectCardExists('theirGraveyard', 'Giant Tortoise', 'Two damage three times killed the 6-life Giant Tortoise')
    ->expectZoneCount('myField', 1, 1, 'All three Powercells were sacrificed')
    ->expectZoneCount('myMemory', 1, 1, 'X = 3: a card was drawn into memory (the card costs 0)')
    ->tested('Powerforged Burst')
    ->covers(['additional-cost', 'sacrifice', 'powercell', 'damage', 'draw-into-memory'], 'As an additional cost to activate this card, sacrifice **X** Powercell items. **X** can\'t be more than 3.', 'Deal 2 damage to target unit **X** times. If **X** is 3, draw a card into your memory.')
    ->register();

// ---- Obelisks ----

gaScenario('obelisk-of-armaments-five-rest-summons-an-aurousteel-greatsword-costing-one-less-per-domain')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Obelisk of Armaments', classBonus: true)
    ->seed('myField', 'Obelisk of Armaments', properties: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)
    ->expectCardExists('myField', 'Aurousteel Greatsword', 'One domain controlled: (5) - 1 = 4 payments')
    ->tested('Obelisk of Armaments')
    ->covers(['activated-ability', 'rest', 'summon', 'token', 'cost-reduction'], '(5), [REST]: **Summon** an Aurousteel Greatsword token. This ability costs (1) less to activate for each domain you control.')
    ->register();

gaScenario('obelisk-of-fabrication-six-rest-summons-a-buffed-automaton-drone-costing-one-less-per-domain')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Obelisk of Fabrication', classBonus: true)
    ->seed('myField', 'Obelisk of Fabrication', properties: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(5)
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'A buffed Automaton Drone: (6) - 1 = 5 payments')
    ->tested('Obelisk of Fabrication')
    ->covers(['activated-ability', 'rest', 'summon', 'token', 'cost-reduction'], '(6), [REST]: **Summon** an Automaton Drone token with a **buff** counter on it. This ability costs (1) less to activate for each domain you control.')
    ->register();

gaScenario('obelisk-of-protection-four-rest-prevents-two-damage-costing-one-less-per-domain')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Obelisk of Protection', classBonus: true)
    ->seed('myField', 'Obelisk of Protection', properties: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->target('myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["PREVENT_ALL_2"]', 'Prevent the next 2 damage: (4) - 1 = 3 payments')
    ->tested('Obelisk of Protection')
    ->covers(['activated-ability', 'rest', 'prevention', 'cost-reduction'], '(4), [REST]**:** Prevent the next 2 damage that would be dealt to target unit this turn. This ability costs (1) less to activate for each domain you control.')
    ->register();

// ---- Atmos Armor Type-Ares / Atmos Shield ----

gaScenario('atmos-armor-type-ares-enter-summons-two-atmos-shield-tokens-and-class-bonus-gains-power-per-shield')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Atmos Armor Type-Ares', classBonus: true)
    ->seed('myHand', 'Atmos Armor Type-Ares')
    ->play('Atmos Armor Type-Ares')->pay(3)
    ->expectZoneCount('myField', 4, 1, 'The ally and two Atmos Shield tokens beside the champion')
    ->expectPower('p1Field-1', 3, '1 power + 1 for each of the two Atmos Shield allies')
    ->tested('Atmos Armor Type-Ares')
    ->covers(['on-enter', 'summon', 'token', 'class-bonus', 'stat-change'], '**On Enter:** **Summon** two Atmos Shield tokens.', '[Class Bonus] CARDNAME gets +1 [POWER] for each ally named Atmos Shield you control.')
    ->register();

gaScenario('atmos-shield-rest-prevents-two-non-combat-damage-to-a-neos-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Atmos Shield')
    ->seed('myField', 'Atmos Shield')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_NONCOMBAT_2"]', 'The next non-combat damage will be reduced by 2')
    ->tested('Atmos Shield')
    ->covers(['activated-ability', 'rest', 'prevention'], '[REST]**:** The next time target neos element unit would take non-combat damage this turn, prevent 2 of that damage.')
    ->register();

gaScenario('atmos-shield-may-take-the-attack-aimed-at-another-neos-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Atmos Shield', classBonus: true)
    ->seed('myField', 'Atmos Shield')
    ->seed('myField', 'Automaton Forgewarden')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-2', player: 2)->answer(1, 'YES')->pass(1)
    ->expectDamage('p1Field-2', 0, 'The Forgewarden was no longer the target')
    ->tested('Atmos Shield')
    ->covers(['redirect', 'attack', 'neos'], 'Whenever another neos element unit you control is targeted for an attack, you may change the target of that attack to this ally.')
    ->register();

// ---- Alchemical Scripture ----

gaScenario('alchemical-scripture-end-phase-with-four-or-more-tokens-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Alchemical Scripture')
    ->seed('myField', 'Alchemical Scripture')
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->endTurn()
    ->expectZoneCount('myMemory', 1, 1, 'Four tokens at the beginning of the end phase: a card drawn into memory')
    ->tested('Alchemical Scripture')
    ->covers(['end-phase', 'tokens', 'draw-into-memory', 'condition'], 'At the beginning of your end phase, if you control four or more tokens, draw a card into your memory.')
    ->register();

gaScenario('alchemical-scripture-end-phase-with-fewer-than-four-tokens-draws-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Alchemical Scripture')
    ->seed('myField', 'Alchemical Scripture')
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->endTurn()
    ->expectZoneCount('myMemory', 0, 1, 'Three tokens: nothing drawn')
    ->tested('Alchemical Scripture')
    ->covers(['end-phase', 'tokens', 'condition'], 'if you control four or more tokens')
    ->register();

// ---- Aegis of Dawn ----

gaScenario('aegis-of-dawn-champion-dealt-four-or-more-damage-summons-an-automaton-drone')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Aegis of Dawn')
    ->seed('myField', 'Aegis of Dawn')
    ->seed('myHand', 'Vanguard\'s Reversal', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->play('Vanguard\'s Reversal', 2)->pay(5, 2)->target('theirField-0', 2)->passBoth()
    ->expectDamage('p1Field-0', 5, 'The attack dealt 5')
    ->expectCardExists('myField', 'Automaton Drone', 'Four or more damage: an Automaton Drone token was summoned')
    ->tested('Aegis of Dawn')
    ->covers(['trigger', 'damage', 'summon', 'token'], 'Whenever your champion is dealt four or more damage, **summon** an Automaton Drone Drone token.')
    ->register();

// ---- Automata Genesis ([Tonoris Bonus]) ----

gaScenario('automata-genesis-summons-three-titan-mk-ii-tokens-and-the-tonoris-bonus-buffs-each-by-the-token-count')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tonoris, Lone Mercenary', lineage: ['Tonoris, Creation\'s Will'])
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Automata Genesis')
    ->play('Automata Genesis')->pay(12)
    ->expectZoneCount('myField', 4, 1, 'The champion and three Titan Mk II tokens')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":3}', 'Three tokens: three buff counters on each')
    ->expectProperty('p1Field-3', 'Counters', '{"buff":3}', 'Including the third token')
    ->tested('Automata Genesis')
    ->covers(['summon', 'token', 'tonoris-bonus', 'counter'], '**Summon** three Titan Mk II tokens.', '[Tonoris Bonus] Put **X** **buff** counters on each token ally you control, where **X** is the amount of tokens you control.')
    ->register();

// ---- Titan Mk II ----

gaWindTaunt('titan-mk-ii-token-taunt-must-be-attacked-first', 'Titan Mk II', '**Taunt**, **Vigor**');

// ---- Aurousteel Greatsword ----

gaScenario('aurousteel-greatsword-token-weapon-attacks-for-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Aurousteel Greatsword')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->passBoth()
    ->expectDamage('p2Field-0', 3, 'The token weapon\'s 3 power')
    ->tested('Aurousteel Greatsword')
    ->covers(['token', 'weapon', 'attack'], 'Token weapon with 3 power')
    ->register();

// ---- Cordelia, Aurous Kaiser ----

gaScenario('cordelia-aurous-kaiser-enter-summons-two-automaton-drones')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cordelia, Aurous Kaiser', classBonus: true)
    ->seed('myHand', 'Cordelia, Aurous Kaiser')
    ->play('Cordelia, Aurous Kaiser')->pay(5)
    ->expectZoneCount('myField', 4, 1, 'The champion, Cordelia and two Automaton Drone tokens')
    ->tested('Cordelia, Aurous Kaiser')
    ->covers(['on-enter', 'summon', 'token'], '**On Enter:** **Summon** two Automaton Drone tokens.')
    ->register();

gaScenario('cordelia-aurous-kaiser-class-bonus-tokens-are-reservable')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cordelia, Aurous Kaiser', classBonus: true)
    ->seed('myField', 'Cordelia, Aurous Kaiser')
    ->seed('myField', 'Automaton Drone')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->answer(1, 'myField-2')->answer(1, 'myField-3')->answer(1, 'myField-4')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid from hand: the three tokens were rested instead')
    ->expectZoneCount('myHand', 7, 1, 'Only the played card left the hand')
    ->tested('Cordelia, Aurous Kaiser')
    ->covers(['class-bonus', 'reservable', 'token'], '[Class Bonus] Token objects you control have **reservable**.')
    ->register();

// ---- Blade of Creation ----

gaScenario('blade-of-creation-class-bonus-costs-one-less-per-token-and-cleaves')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Blade of Creation', classBonus: true)
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->seed('myField', 'Dungeon Guide', player: 2)->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Blade of Creation')
    ->reachTurnThree()
    ->play('Blade of Creation')->pay(4)
    ->expectZoneCount('theirField', 1, 1, 'Cleave: both opposing Dungeon Guides took 4 and died')
    ->expectDamage('p2Field-0', 4, 'And the opposing champion was attacked too')
    ->tested('Blade of Creation')
    ->covers(['class-bonus', 'cost-reduction', 'tokens', 'cleave'], '[Class Bonus] This card costs 1 less to activate for each token object you control.', '**Cleave**')
    ->register();

// ---- Radiant Repudiation ----

gaScenario('radiant-repudiation-prevents-four-to-a-neos-unit-and-the-prevention-deals-two-damage-to-a-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Radiant Repudiation', classBonus: true)
    ->seed('myField', 'Automaton Forgewarden')
    ->seed('myHand', 'Radiant Repudiation')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myHand-7')->pay(2)->target('myField-1')->target('theirField-1')->passBoth()
    ->expectDamage('p1Field-1', 0, 'The Dungeon Guide\'s damage was prevented')
    ->expectDamage('p2Field-1', 2, 'And the prevention dealt 2 damage to the Dungeon Guide')
    ->tested('Radiant Repudiation')
    ->covers(['reaction', 'prevention', 'neos', 'damage'], 'Prevent the next 4 damage that would be dealt to target neos element unit this turn. Whenever damage is prevented this way, deal 2 damage to target unit.')
    ->register();

// ---- Provoke Obstinance ----

gaScenario('provoke-obstinance-class-bonus-single-target-draws-into-memory-and-protects-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Provoke Obstinance', classBonus: true)
    ->seed('myHand', 'Provoke Obstinance')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(3)->answer(1, 'myField-0')->passBoth()
    ->expectZoneCount('myMemory', 4, 1, 'Three payments plus the card drawn into memory (exactly one target)')
    ->expectDamage('p1Field-0', 0, 'The champion got spellshroud and 2 damage prevention: the attack dealt nothing')
    ->tested('Provoke Obstinance')
    ->covers(['reaction', 'class-bonus', 'draw-into-memory', 'spellshroud', 'prevention'], '[Class Bonus] Whenever you activate this card, if there\'s exactly one target for its activation, draw a card into your memory.', 'Up to five target objects you control gain **spellshroud** until end of turn. Prevent the next 2 damage that would be dealt to each of those targets this turn if they\'re units.')
    ->register();

// ---- Amorphous Strike ----

gaScenario('amorphous-strike-class-bonus-on-attack-banishes-a-graveyard-attack-card-for-plus-its-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Amorphous Strike', classBonus: true)
    ->seed('myGraveyard', 'Off With Her Head')
    ->seed('myHand', 'Amorphous Strike')
    ->reachTurnThree()
    ->play('Amorphous Strike')->pay(3)->target('theirField-0')->answer(1, 'myGraveyard-0')
    ->expectDamage('p2Field-0', 6, '4 power plus the banished Off With Her Head\'s 2')
    ->expectCardExists('myBanish', 'Off With Her Head', 'It was banished from the graveyard')
    ->tested('Amorphous Strike')
    ->covers(['class-bonus', 'on-attack', 'banish', 'stat-change'], '[Class Bonus] **On Attack:** You may banish an attack card from your graveyard. If you do, CARDNAME gets **+X** [POWER] where **X** is the banished card\'s power.')
    ->register();

// ---- Iron Halo, Forcefield Node ----

gaScenario('iron-halo-forcefield-node-prevents-two-damage-to-another-object-you-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Iron Halo, Forcefield Node', classBonus: true)
    ->seed('myField', 'Iron Halo, Forcefield Node')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-2', player: 2)->pass(1)
    ->expectDamage('p1Field-2', 0, 'The Dungeon Guide\'s 1 damage was prevented (2 prevented)')
    ->tested('Iron Halo, Forcefield Node')
    ->covers(['prevention', 'domain'], 'If damage would be dealt to another object you control, prevent 2 of that damage.')
    ->register();

gaScenario('iron-halo-forcefield-node-end-phase-adds-a-durability-counter-for-each-of-up-to-two-tokens')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Iron Halo, Forcefield Node', classBonus: true)
    ->seed('myField', 'Iron Halo, Forcefield Node')
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '{"durability":5}', 'Its 3 durability plus one for each of up to two tokens (three controlled)')
    ->tested('Iron Halo, Forcefield Node')
    ->covers(['end-phase', 'durability', 'tokens'], 'At the beginning of your end phase, put a **durability** counter on CARDNAME for each of up to two tokens you control.')
    ->register();

// ---- Hector, Praetorian Guard ----

gaScenario('hector-praetorian-guard-class-bonus-prevents-damage-equal-to-your-token-count-once-per-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hector, Praetorian Guard', classBonus: true)
    ->seed('myField', 'Hector, Praetorian Guard')->answer(1, '-')
    ->seed('myField', 'Automaton Drone')->seed('myField', 'Automaton Drone')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->pass(1)
    ->expectDamage('p1Field-1', 0, 'Two tokens: 2 of the Dungeon Guide\'s 1 damage was prevented')
    ->tested('Hector, Praetorian Guard')
    ->covers(['class-bonus', 'prevention', 'tokens'], '[Class Bonus] If damage would be dealt to one or more non-token neos element units you control, prevent X of that damage where **X** is the amount of tokens you control. Apply this replacement effect only once per turn.')
    ->register();

// ---- Fractal of Creation ----

gaScenario('fractal-of-creation-sacrifice-summons-a-token-copy-of-a-token-you-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fractal of Creation', classBonus: true)
    ->seed('myField', 'Fractal of Creation')
    ->seed('myField', 'Automaton Drone', properties: ['Counters' => ['buff' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-1')
    ->expectZoneCount('myField', 3, 1, 'The Fractal was sacrificed and a copy of the Drone token was summoned')
    ->tested('Fractal of Creation')
    ->covers(['activated-ability', 'sacrifice-self', 'summon', 'token-copy'], '**Sacrifice CARDNAME:** **Summon** a token copy of target token you control.')
    ->register();

gaScenario('fractal-of-creation-reservable-rests-to-pay-for-one-of-a-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fractal of Creation', classBonus: true)
    ->seed('myField', 'Fractal of Creation')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->answer(1, 'myField-1')->pay(2)
    ->expectProperty('p1Field-1', 'Status', '1', 'The Fractal rested to pay for 1')
    ->expectZoneCount('myMemory', 2, 1, 'Only two cards were paid from hand')
    ->tested('Fractal of Creation')
    ->covers(['reservable', 'rest'], '**Reservable**')
    ->register();

// ---- Fractal of Duplication ----

gaScenario('fractal-of-duplication-becoming-rested-summons-a-rested-token-copy-of-itself')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fractal of Duplication', classBonus: true)
    ->seed('myField', 'Fractal of Duplication')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->answer(1, 'myField-1')->pay(2)
    ->expectZoneCount('myField', 4, 1, 'The champion, the rested Fractal, its rested token copy and the played Dungeon Guide')
    ->tested('Fractal of Duplication')
    ->covers(['trigger', 'rest', 'summon', 'token-copy', 'reservable'], 'Whenever CARDNAME becomes rested, **summon** a token copy of it rested.')
    ->register();

// ---- Tonoris, Creation's Will ----

gaScenario('tonoris-creations-will-token-summons-may-become-aurousteel-greatswords')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tonoris, Creation\'s Will')
    ->seed('myHand', 'Production Crawldroid')
    ->play('Production Crawldroid')->pay(3)->answer(1, 'YES')->answer(1, 'YES')
    ->expectCardExists('myField', 'Aurousteel Greatsword', 'The summoned Powercell tokens became Aurousteel Greatswords')
    ->expectZoneCount('myField', 4, 1, 'The champion, the ally and two replaced tokens')
    ->tested('Tonoris, Creation\'s Will')
    ->covers(['replacement', 'summon', 'token', 'optional-choice'], 'If you would **summon** one or more tokens, you may **summon** that many Aurousteel Greatsword tokens instead.')
    ->register();

// ---- Tor, Realmwalker Colossus ----

gaScenario('tor-realmwalker-colossus-class-bonus-enter-sacrifices-a-domain-for-durability-counters-and-gains-power-per-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tor, Realmwalker Colossus', classBonus: true)
    ->seed('myField', 'Epicurean Institute')
    ->seed('myHand', 'Tor, Realmwalker Colossus')
    ->play('Tor, Realmwalker Colossus')->pay(3)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":7}', 'Its 3 durability plus 2 + 2 (the sacrificed domain\'s reserve cost) = 4')
    ->expectPower('p1Field-1', 1, '+1 power for every four durability counters (7 counters)')
    ->tested('Tor, Realmwalker Colossus')
    ->covers(['class-bonus', 'on-enter', 'sacrifice', 'durability', 'stat-change'], '[Class Bonus] **On Enter:** Sacrifice up to three domains. For each domain sacrificed this way, put 2+**X** **durability** counters on CARDNAME where **X** is that domain\'s reserve cost.', 'CARDNAME gets +1[POWER] for every four **durability** counters on it.')
    ->register();
