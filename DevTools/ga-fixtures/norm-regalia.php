<?php
/**
 * Phase 3d (Norm): regalia leftovers. 'quiet' deck unless noted; regalia are seeded onto the field (never the deck's {MAIN} card).
 */

// ---- Censer of Restful Peace ----

gaScenario('censer-of-restful-peace-graveyard-cards-lose-floating-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(classes: 'GUARDIAN')
    ->seed('myField', 'Censer of Restful Peace')
    ->seed('myGraveyard', 'Savage Smash')
    ->endTurn()->endTurn(2)->target('myMaterial-0')
    ->expectZoneCount('myGraveyard', 1, 1, 'Savage Smash lost its Floating Memory in the graveyard: it stays there')
    ->expectZoneCount('myMaterial', 4, 1, 'No memory could be paid, so no champion was materialized')
    ->tested('Censer of Restful Peace')
    ->covers(['static', 'no-abilities', 'floating-memory'], 'Cards in graveyards lose all abilities.')
    ->register();

// ---- Memento Pocketwatch ----

gaScenario('memento-pocketwatch-third-charge-counter-banishes-it-draws-and-buffs-the-next-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Memento Pocketwatch', properties: ['Counters' => ['charge' => 2]])
    ->seed('myField', 'Dungeon Guide')
    ->reachTurnThree()
    ->expectCardExists('myBanish', 'Memento Pocketwatch', 'The third charge counter triggered On Charge 3: it banished itself')
    ->expectZoneCount('myHand', 9, 1, 'Seven opening cards, the turn-three draw and the Pocketwatch\'s draw')
    ->attack('myField-1', 'theirField-0')->pass(2)
    ->expectDamage('p2Field-0', 4, 'The next attack declared with a unit this turn: 1 + 3 power')
    ->tested('Memento Pocketwatch')
    ->covers(['on-charge', 'banish-self', 'draw', 'stat-change'], '**On Charge 3**: Banish Memento Pocketwatch and draw a card. The next attack you declare with a unit this turn gets +3[POWER].')
    ->register();

// ---- Endura, Reimagined ([Rai Bonus]) ----

gaScenario('endura-reimagined-rai-bonus-removes-three-enlighten-counters-to-deal-two-damage-and-draw')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Rai, Spellcrafter')
    ->patch('myField-0', ['Counters' => ['enlighten' => 3]])
    ->seed('myField', 'Endura, Reimagined')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'theirField-0')
    ->expectDamage('p2Field-0', 2, 'Two damage to the targeted unit')
    ->expectZoneCount('myHand', 8, 1, 'Drew a card')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'The three enlighten counters were removed from the champion')
    ->tested('Endura, Reimagined')
    ->covers(['rai-bonus', 'activated-ability', 'counter-cost', 'rest', 'damage', 'draw'], '[Rai Bonus] **Remove three enlighten counters from your champion,  [REST]:** As a Spell, deal 2 damage to up to one target unit. Draw a card.')
    ->register();

gaScenario('endura-reimagined-without-the-rai-bonus-cannot-be-activated')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['enlighten' => 3]])
    ->seed('myField', 'Endura, Reimagined')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-0', 'Counters', '{"enlighten":3}', 'No Rai champion: the enlighten counters were not removed')
    ->tested('Endura, Reimagined')
    ->covers(['rai-bonus', 'condition'], '[Rai Bonus] **Remove three enlighten counters from your champion,  [REST]:**')
    ->register();

// ---- Amorphous Missile ----

gaScenario('amorphous-missile-loads-itself-into-an-unloaded-ranger-weapon')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Seeker\'s Aetherwing')
    ->seed('myField', 'Amorphous Missile')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->pay(1)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Subcards', '["782mm2tq5l"]', 'The Missile is loaded into the Aetherwing')
    ->expectZoneCount('myField', 2, 1, 'The Missile left the field')
    ->tested('Amorphous Missile')
    ->covers(['activated-ability', 'rest', 'load', 'renewable'], '(1), [REST]**:** Load CARDNAME into target unloaded Ranger weapon you control.')
    ->register();

// ---- Viridian Protective Trinket ----

if (!function_exists('gaViridian')) {
    // Player 2 answers player 1's end-of-turn window with the fast water spell Dissuading Halt (2).
    function gaViridian(string $slug, bool $trinket): GaScenario {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Dissuading Halt', player: 2);
        if ($trinket) $s->seed('myField', 'Viridian Protective Trinket');
        return $s->seed('myHand', 'Dissuading Halt', player: 2)->endTurn()->answer(2, 'myHand-7');
    }
}

$gaViridianClause = 'During your turn, water element cards your opponents activate cost 2 more to activate.';

gaViridian('viridian-protective-trinket-opposing-water-cards-cost-two-more-during-your-turn', true)
    ->pay(4, 2)->target('theirField-0', 2)
    ->expectCardExists('theirGraveyard', 'Dissuading Halt', 'Paid 2 + 2 and the water spell resolved')
    ->tested('Viridian Protective Trinket')->covers(['static', 'cost-increase', 'element'], $gaViridianClause)->register();

gaViridian('viridian-protective-trinket-paying-only-the-printed-cost-is-not-enough', true)
    ->pay(2, 2)->rejects(2, 'theirField-0', 'The water spell still needs two more payments')
    ->tested('Viridian Protective Trinket')->covers(['static', 'cost-increase', 'element'], $gaViridianClause)->register();

gaViridian('without-viridian-protective-trinket-the-water-spell-costs-its-printed-two', false)
    ->pay(2, 2)->target('theirField-0', 2)
    ->expectCardExists('theirGraveyard', 'Dissuading Halt', 'Paid only 2 and the water spell resolved')
    ->tested('Viridian Protective Trinket')->covers(['static', 'condition'], $gaViridianClause)->register();

// ---- Alchemist's Cauldron ([Arisanna Bonus]) ----

gaScenario('alchemists-cauldron-arisanna-bonus-banishes-itself-to-gather-twice')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Arisanna, Herbalist Prodigy')
    ->seed('myField', 'Alchemist\'s Cauldron')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)
    ->expectCardExists('myBanish', 'Alchemist\'s Cauldron', 'The Cauldron was banished as a cost')
    ->expectZoneCount('myField', 3, 1, 'Gathered twice: two plant tokens beside the champion')
    ->tested('Alchemist\'s Cauldron')
    ->covers(['arisanna-bonus', 'activated-ability', 'banish-self', 'gather'], '[Arisanna Bonus] (3)**, Banish CARDNAME: ****Gather** twice.')
    ->register();

gaScenario('alchemists-cauldron-without-the-arisanna-bonus-cannot-be-activated')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Alchemist\'s Cauldron')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectCardExists('myField', 'Alchemist\'s Cauldron', 'No Arisanna champion: the ability was refused')
    ->tested('Alchemist\'s Cauldron')
    ->covers(['arisanna-bonus', 'condition'], '[Arisanna Bonus] (3)**, Banish CARDNAME:')
    ->register();

// ---- Huaji of Heaven's Rise ----

gaScenario('huaji-of-heavens-rise-class-bonus-a-unique-warrior-ally-attacks-using-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Huaji of Heaven\'s Rise', classBonus: true)
    ->seed('myField', 'Huaji of Heaven\'s Rise')
    ->seed('myField', 'Virgil, Altered Future')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-2!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->pass(2)
    ->expectDamage('p2Field-0', 3, 'Virgil\'s 2 power plus the polearm\'s 1')
    ->tested('Huaji of Heaven\'s Rise')
    ->covers(['class-bonus', 'weapon-attack', 'allies'], '[Class Bonus] Unique Warrior allies you control can attack using this weapon.')
    ->register();

// ---- Reciprocity, Dorumegia's Call ([Tonoris Bonus]) ----

if (!function_exists('gaReciprocity')) {
    function gaReciprocity(string $slug): GaScenario {
        return gaScenario($slug)->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Reciprocity, Dorumegia's Call
# Main
20 Dungeon Guide
DECK
        )->champion(cardName: 'Tonoris, Lone Mercenary');
    }
}

$gaReciprocityClause = '[Tonoris Bonus] You may pay (6) to activate this card from your material deck. It costs (3) less to activate this way for each non-token domain you control.';

gaReciprocity('reciprocity-dorumegias-call-tonoris-bonus-pay-six-to-activate-it-from-the-material-deck')
    ->endTurn()->endTurn(2)->pass(1)->answer(1, 'myMaterial-0')->pay(6)
    ->expectCardExists('myField', 'Reciprocity, Dorumegia\'s Call', 'Activated from the material deck for (6): it is on the field')
    ->expectZoneCount('myMaterial', 0, 1, 'It left the material deck')
    ->tested('Reciprocity, Dorumegia\'s Call')->covers(['tonoris-bonus', 'activate-from-material', 'cost'], $gaReciprocityClause)->register();

gaReciprocity('reciprocity-dorumegias-call-each-non-token-domain-makes-the-material-activation-three-cheaper')
    ->seed('myField', 'Sanctum of Esoteric Truth')
    ->endTurn()->endTurn(2)->pass(1)->answer(1, 'myMaterial-0')->pay(3)
    ->expectCardExists('myField', 'Reciprocity, Dorumegia\'s Call', 'One domain: (6) - 3 = 3 payments were enough')
    ->tested('Reciprocity, Dorumegia\'s Call')->covers(['tonoris-bonus', 'activate-from-material', 'cost-reduction'], $gaReciprocityClause)->register();

gaScenario('reciprocity-dorumegias-call-tonoris-bonus-level-two-rest-summons-an-automaton-drone')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tonoris, Might of Humanity')
    ->seed('myField', 'Reciprocity, Dorumegia\'s Call')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectCardExists('myField', 'Automaton Drone', 'A level 2 Tonoris champion: [REST] summoned an Automaton Drone token')
    ->tested('Reciprocity, Dorumegia\'s Call')
    ->covers(['tonoris-bonus', 'level-gate', 'activated-ability', 'rest', 'summon'], '[Tonoris Bonus] [Level 2+] **[REST]:** **Negate** up to one target **upkeep** trigger. Then **summon** an Automaton Drone token.')
    ->register();

gaScenario('reciprocity-dorumegias-call-tonoris-bonus-level-one-cannot-use-its-rest-ability')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tonoris, Lone Mercenary')
    ->seed('myField', 'Reciprocity, Dorumegia\'s Call')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myField', 2, 1, 'Level 1: no Automaton Drone token')
    ->tested('Reciprocity, Dorumegia\'s Call')
    ->covers(['tonoris-bonus', 'level-gate'], '[Tonoris Bonus] [Level 2+] **[REST]:**')
    ->register();

// ---- Pendant of Apsis Restraint ----

if (!function_exists('gaPendant')) {
    function gaPendant(string $slug, bool $pendant): GaScenario {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Auspicious Manifestation');
        if ($pendant) $s->seed('myField', 'Pendant of Apsis Restraint');
        return $s->seed('myHand', 'Auspicious Manifestation');
    }
}

$gaPendantClause = 'Ultimate cards cost (3) more to activate.';

gaPendant('pendant-of-apsis-restraint-ultimate-cards-cost-three-more', true)
    ->play('Auspicious Manifestation')->pay(5)
    ->expectCardExists('myGraveyard', 'Auspicious Manifestation', 'Paid 2 + 3 and the Ultimate action resolved')
    ->tested('Pendant of Apsis Restraint')->covers(['static', 'cost-increase', 'ultimate'], $gaPendantClause)->register();

gaPendant('pendant-of-apsis-restraint-paying-only-the-printed-cost-is-not-enough', true)
    ->play('Auspicious Manifestation')->pay(2)
    ->expectZoneCount('myGraveyard', 0, 1, 'Two payments are not enough: the Ultimate action is still being paid for')
    ->tested('Pendant of Apsis Restraint')->covers(['static', 'cost-increase', 'ultimate'], $gaPendantClause)->register();

gaPendant('without-pendant-of-apsis-restraint-the-ultimate-card-costs-its-printed-two', false)
    ->play('Auspicious Manifestation')->pay(2)
    ->expectCardExists('myGraveyard', 'Auspicious Manifestation', 'Paid only 2 and the Ultimate action resolved')
    ->tested('Pendant of Apsis Restraint')->covers(['static', 'condition'], $gaPendantClause)->register();

// ---- Reliable Blade ----

if (!function_exists('gaReliableBlade')) {
    function gaReliableBlade(string $slug, int $advancedBanished): GaScenario {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Reliable Blade', classBonus: true)->seed('myField', 'Reliable Blade');
        for ($i = 0; $i < $advancedBanished; ++$i) $s->seed('myBanish', 'Sidereal Spellshot');
        return $s->seed('myHand', 'Break Apart', player: 2)->endTurn()->play('Break Apart', 2)->pay(5, 2);
    }
}

$gaBladeClause = '[Class Bonus] As long as you have three or more advanced element cards in your banishment, CARDNAME has **spellshroud**.';

gaReliableBlade('reliable-blade-class-bonus-three-advanced-banished-cards-give-it-spellshroud', 3)
    ->expectCardExists('myField', 'Reliable Blade', 'Break Apart could not target the spellshrouded sword: it is still on the field')
    ->tested('Reliable Blade')->covers(['class-bonus', 'spellshroud', 'condition'], $gaBladeClause)->register();

gaReliableBlade('reliable-blade-class-bonus-two-advanced-banished-cards-leave-it-targetable', 2)
    ->target('theirField-1', 2)->passBoth()
    ->expectZoneCount('myField', 1, 1, 'Only two advanced cards banished: Break Apart destroyed the sword')
    ->tested('Reliable Blade')->covers(['class-bonus', 'spellshroud', 'condition'], $gaBladeClause)->register();

// ---- Framework Sidearm ([Class Bonus]) ----

gaScenario('framework-sidearm-class-bonus-pay-three-to-activate-it-from-the-material-deck')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Framework Sidearm
# Main
20 Dungeon Guide
DECK
    )
    ->readyFor('Framework Sidearm', classBonus: true)
    ->endTurn()->endTurn(2)->pass(1)->answer(1, 'myMaterial-0')->pay(3)
    ->expectCardExists('myField', 'Framework Sidearm', 'Activated from the material deck for (3): it is on the field')
    ->expectZoneCount('myMaterial', 0, 1, 'It left the material deck')
    ->tested('Framework Sidearm')
    ->covers(['class-bonus', 'activate-from-material', 'cost'], '[Class Bonus] You may pay (3) to activate this card from your material deck.')
    ->register();

gaScenario('framework-sidearm-without-the-class-bonus-cannot-be-activated-from-the-material-deck')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Framework Sidearm
# Main
20 Dungeon Guide
DECK
    )
    ->endTurn()->endTurn(2)->pass(1)
    ->expectZoneCount('myMaterial', 1, 1, 'No class bonus: no offer and the sidearm stays in the material deck')
    ->tested('Framework Sidearm')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] You may pay (3) to activate this card from your material deck.')
    ->register();

// ---- Trivariate Dream ----

if (!function_exists('gaTrivariate')) {
    function gaTrivariate(string $slug, int $loaded): GaScenario {
        return gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Trivariate Dream', classBonus: true)
            ->seed('myField', 'Trivariate Dream', properties: ['Subcards' => array_fill(0, $loaded, GaScenario::id('Resonant Aether'))])
            ->reachTurnThree()->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->passBoth();
    }
}

$gaTrivariateClause = '[Class Bonus] **On Attack**: If there are exactly three Aethercharge cards in the attacker\'s intent, this attack gets +3[POWER].';

gaTrivariate('trivariate-dream-class-bonus-exactly-three-aethercharge-cards-gets-plus-three-power', 3)
    ->expectDamage('p2Field-0', 7, 'The weapon\'s 1, three loaded 1-power Resonant Aethers and +3 for exactly three Aethercharge cards')
    ->tested('Trivariate Dream')->covers(['class-bonus', 'on-attack', 'stat-change', 'condition'], $gaTrivariateClause)->register();

gaTrivariate('trivariate-dream-class-bonus-two-aethercharge-cards-get-no-bonus', 2)
    ->expectDamage('p2Field-0', 3, 'The weapon\'s 1 plus two loaded Aethers: no bonus')
    ->tested('Trivariate Dream')->covers(['class-bonus', 'on-attack', 'condition'], $gaTrivariateClause)->register();

gaTrivariate('trivariate-dream-class-bonus-four-aethercharge-cards-get-no-bonus', 4)
    ->expectDamage('p2Field-0', 5, 'The weapon\'s 1 plus four loaded Aethers: not exactly three, no bonus')
    ->tested('Trivariate Dream')->covers(['class-bonus', 'on-attack', 'condition'], $gaTrivariateClause)->register();
