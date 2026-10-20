<?php
/**
 * Phase E (Umbra): Gloamspire Mantle ambush, Gloamspire Wraith's distant On Hit recovery, Soutirer Vortex's omen trigger, Mary Ann's shared keywords,
 * Nightmare Coil's positive path, Tristan's same-level level-up draw, Death Essence Amulet's hand/memory choice, Spirelle's negate.
 * Negative cases that a fixture cannot drive (prereqs are bypassed by direct actions) live in test-ga-phase-e-umbra.php.
 */

const GA_MANTLE_AMBUSH_TEXT = 'Umbra element phantasia allies you control have **ambush**.';
const GA_WRAITH_RECOVER_TEXT = 'As long as CARDNAME is **distant**, it has "**On Hit: Recover X**, where **X** is the amount of damage dealt by this hit."';

// ---- Gloamspire Mantle ----

// P2's Dungeon Guide attacks P1's Dungeon Guide; the Ominous Shadow (an Umbra phantasia ally) retaliates through the Mantle's ambush.
function gaMantleAmbush(string $slug, bool $withMantle, string $ally) {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide');
    if ($withMantle) $s->seed('myField', 'Gloamspire Mantle');
    $s->seed('myField', 'Dungeon Guide')->seed('myField', $ally)->seed('myField', 'Dungeon Guide', player: 2)->endTurn();
    $first = $withMantle ? 2 : 1;
    $s->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-' . $first);
    return $withMantle ? $s->target('myField-' . ($first + 1)) : $s->passBoth();
}

gaMantleAmbush('gloamspire-mantle-gives-umbra-phantasia-allies-ambush', true, 'Ominous Shadow')
    ->expectProperty('p1Field-3', 'Status', '1', 'The Ominous Shadow rested to retaliate through the Mantle\'s ambush')
    ->expectDamage('p2Field-1', 1, 'The attacker took the Shadow\'s 1 power')
    ->expectDqEmpty()
    ->tested('Gloamspire Mantle')
    ->covers(['static', 'ambush', 'phantasia'], GA_MANTLE_AMBUSH_TEXT)
    ->register();

gaMantleAmbush('gloamspire-mantle-absent-the-umbra-phantasia-ally-cannot-retaliate', false, 'Ominous Shadow')
    ->expectProperty('p1Field-2', 'Status', '2', 'Without the Mantle the Shadow has no ambush and stays awake')
    ->expectDamage('p1Field-1', 1, 'The defending Dungeon Guide took the attacker\'s 1 damage')
    ->expectDamage('p2Field-1', 0, 'Nothing retaliated against the attacker')
    ->expectDqEmpty()
    ->tested('Gloamspire Mantle')
    ->covers(['static', 'ambush', 'condition'], GA_MANTLE_AMBUSH_TEXT)
    ->register();

// ---- Gloamspire Wraith ----

foreach ([true, false] as $distant) {
    $s = gaScenario($distant ? 'gloamspire-wraith-distant-on-hit-recovers-the-damage-dealt' : 'gloamspire-wraith-not-distant-has-no-on-hit-recovery')
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->champion(classes: 'RANGER', damage: 6, player: 2)
        ->seed('myField', 'Gloamspire Wraith', player: 2, properties: $distant ? ['TurnEffects' => ['DISTANT']] : [])
        ->endTurn()
        ->attack('myField-1', 'theirField-0', player: 2);
    if ($distant) $s->expectDamage('p1Field-0', 5, 'Ranged 2 while distant: 3 + 2')->expectDamage('p2Field-0', 1, 'Recover 5 (the damage dealt) from 6');
    else $s->expectDamage('p1Field-0', 3, 'Not distant: plain 3 power')->expectDamage('p2Field-0', 6, 'No On Hit recovery');
    $s->expectDqEmpty()->tested('Gloamspire Wraith')
        ->covers(['on-hit', 'recover', 'distant', $distant ? 'granted-ability' : 'condition'], GA_WRAITH_RECOVER_TEXT)->register();
}

// ---- Soutirer Vortex ----

// Omens: Gray Wolf x2 (reserve 2) and Dungeon Guide (reserve 3). The opponent activates a reserve-2 Gray Wolf: the two matching omens each trigger.
function gaVortex(string $slug, string $theirCard, int $theirCost, bool $withVortex = true, array $omens = ['Gray Wolf', 'Gray Wolf', 'Dungeon Guide']) {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->champion(damage: 5);
    if ($withVortex) $s->seed('myField', 'Soutirer Vortex');
    foreach ($omens as $omen) $s->seed('myBanish', $omen, properties: ['Counters' => ['omen' => 1]]);
    return $s
        ->seed('myHand', $theirCard, player: 2)
        ->endTurn()
        ->play($theirCard, 2)->pay($theirCost, 2);
}

gaVortex('soutirer-vortex-each-matching-omen-recovers-one-and-damages-the-activators-champion', 'Gray Wolf', 2)
    ->expectDamage('p1Field-0', 3, 'Two reserve-2 omens: recover 1 twice from 5')
    ->expectDamage('p2Field-0', 2, 'Two reserve-2 omens: 1 damage each to the activating champion')
    ->expectDqEmpty()
    ->tested('Soutirer Vortex')
    ->covers(['omen', 'trigger', 'recover', 'damage'], 'Your omens have ”Whenever an opponent activates a card with the same reserve cost as this **omen**, **recover 1** and deal 1 damage to their champion.”')
    ->register();

gaVortex('soutirer-vortex-omen-trigger-ignores-a-different-reserve-cost', 'Gray Wolf', 2, true, ['Gray Wolf', 'Dungeon Guide', 'Giant Tortoise'])
    ->expectDamage('p1Field-0', 4, 'Only the single reserve-2 omen (Gray Wolf) matched; reserve 3 and 4 did not')
    ->expectDamage('p2Field-0', 1, 'One damage to the activator')
    ->expectDqEmpty()
    ->tested('Soutirer Vortex')
    ->covers(['omen', 'trigger', 'condition'], 'Your omens have ”Whenever an opponent activates a card with the same reserve cost as this **omen**, **recover 1** and deal 1 damage to their champion.”')
    ->register();

gaVortex('soutirer-vortex-omens-do-nothing-without-the-vortex', 'Gray Wolf', 2, false)
    ->expectDamage('p1Field-0', 5, 'No Vortex: omens have no trigger')
    ->expectDamage('p2Field-0', 0, 'No damage to the activator')
    ->tested('Soutirer Vortex')
    ->covers(['omen', 'trigger', 'condition'], 'Your omens have ”Whenever an opponent activates a card with the same reserve cost as this **omen**, **recover 1** and deal 1 damage to their champion.”')
    ->register();

// ---- Mary Ann, Maladroit Maid ----

const GA_MARY_ANN_SHARE_TEXT = 'Mary Ann has **vigor** as long as one of your omens has **vigor**. The same is true for **ambush**, **intercept**, **steadfast**, **stealth**, **spellshroud**, **taunt**, and **true sight**.';

function gaMaryAnn(string $slug, ?string $omen) {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Mary Ann, Maladroit Maid');
    if ($omen !== null) $s->seed('myBanish', $omen, properties: ['Counters' => ['omen' => 1]]);
    return $s;
}

// Mary Ann, rested, wakes up at end of turn only through an omen with vigor (Gildas has vigor).
foreach (['Gildas, Faesworn Monarch' => 'wakes-up-at-end-of-turn-with-a-vigor-omen', 'Dungeon Guide' => 'stays-rested-without-a-vigor-omen'] as $omen => $name) {
    $vigor = $omen !== 'Dungeon Guide';
    gaMaryAnn('mary-ann-' . $name, $omen)->patch('myField-1', ['Status' => 1])->endTurn()
        ->expectProperty('p1Field-1', 'Status', $vigor ? '2' : '1', $vigor ? 'The vigor omen gave Mary Ann vigor: she woke at end of turn' : 'No vigor omen: she stays rested')
        ->tested('Mary Ann, Maladroit Maid')->covers(['static', 'keyword-sharing', 'vigor', 'omen'], GA_MARY_ANN_SHARE_TEXT)->register();
}

// Cloaked Executioner (ambush) as an omen lets Mary Ann retaliate for another ally.
gaMaryAnn('mary-ann-gains-ambush-from-an-omen-with-ambush-and-retaliates', 'Cloaked Executioner')
    ->seed('myField', 'Dungeon Guide')->seed('myField', 'Dungeon Guide', player: 2)->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-2')->target('myField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'Mary Ann rested to retaliate through the ambush she borrowed from the omen')
    ->expectDamage('p1Field-2', 1, 'The defending Dungeon Guide still took the attacker\'s damage')
    ->expectDqEmpty()
    ->tested('Mary Ann, Maladroit Maid')->covers(['static', 'keyword-sharing', 'ambush', 'omen'], GA_MARY_ANN_SHARE_TEXT)->register();

gaMaryAnn('mary-ann-has-no-ambush-when-no-omen-has-it', 'Gildas, Faesworn Monarch')
    ->seed('myField', 'Dungeon Guide')->seed('myField', 'Dungeon Guide', player: 2)->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-2')->passBoth()
    ->expectProperty('p1Field-1', 'Status', '2', 'No ambush omen: Mary Ann could not retaliate and stays awake')
    ->expectDqEmpty()
    ->tested('Mary Ann, Maladroit Maid')->covers(['static', 'keyword-sharing', 'ambush', 'condition'], GA_MARY_ANN_SHARE_TEXT)->register();

// Fluffy Shopkeep (intercept) as an omen lets Mary Ann intercept an attack on the champion.
gaMaryAnn('mary-ann-gains-intercept-from-an-omen-with-intercept', 'Fluffy Shopkeep')
    ->seed('myField', 'Dungeon Guide', player: 2)->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')->target('myField-1')->answer(1, '-')
    ->expectDamage('p1Field-0', 0, 'The champion took nothing: the attack was intercepted')
    ->expectDamage('p1Field-1', 1, 'Mary Ann took the redirected attack')
    ->expectDqEmpty()
    ->tested('Mary Ann, Maladroit Maid')->covers(['static', 'keyword-sharing', 'intercept', 'omen'], GA_MARY_ANN_SHARE_TEXT)->register();

// ---- Nightmare Coil ----

const GA_COIL_PUNISH_TEXT = 'Draw a card. Until end of turn, whenever an opponent activates a card or ability, that opponent\'s champion deals 8 unpreventable damage to itself.';

// Player 1's recollection window (REC_START) with a distant Ranger champion, then player 2 answers in the second-response window of the same window.
// Player 2 holds a fast Backstep, so every earlier phase also opens a window for them: the extra passes are those windows.
function gaCoil(string $slug, bool $classBonus) {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide');
    if ($classBonus) $s->readyFor('Nightmare Coil', classBonus: true);
    else $s->readyFor('Nightmare Coil');
    return $s->champion(damage: 0)->globalEffect('FREYDIS_PERMANENT_DISTANT')
        ->seed('myHand', 'Nightmare Coil')->seed('myHand', 'Backstep', player: 2)
        ->endTurn()->pass(2)->endTurn(2)->endTurn(2)->pass(2)->pass(2)->pass(1)
        ->answer(1, 'myHand-7')->pay(2);
}

gaCoil('nightmare-coil-recollection-draws-and-punishes-an-opponent-activation-for-eight', true)
    ->pass(2)
    ->answer(2, 'myHand-7')->pay(2, 2)
    ->expectDamage('p2Field-0', 8, 'The opponent activated Backstep after the Coil resolved: their champion dealt 8 unpreventable damage to itself')
    ->expectCardExists('myGraveyard', 'Nightmare Coil', 'The Coil resolved')
    ->expectZoneCount('myHand', 6, 1, 'Eight cards, the Coil and two payments out, one card drawn')
    ->tested('Nightmare Coil')
    ->covers(['activated-spell', 'draw', 'delayed-trigger', 'unpreventable-damage', 'recollection'], GA_COIL_PUNISH_TEXT)
    ->register();

// Horticounter ("Negate target card activation unless its controller pays X+Y") is castable here (Abnegation's fixtures use the same setup),
// but with the Class Bonus the Coil's activation cannot be negated: player 2 is never offered the negate, the Coil resolves and Horticounter stays in hand.
gaScenario('nightmare-coil-class-bonus-activation-cannot-be-negated')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Nightmare Coil', classBonus: true)->readyFor('Horticounter', player: 2)->globalEffect('FREYDIS_PERMANENT_DISTANT')
    ->seed('myHand', 'Nightmare Coil')->seed('myHand', 'Horticounter', player: 2)
    ->endTurn()->pass(2)->endTurn(2)->endTurn(2)->pass(2)->pass(2)->pass(1)
    ->answer(1, 'myHand-7')->pay(2)
    ->expectCardExists('myGraveyard', 'Nightmare Coil', 'The Coil resolved instead of being negated')
    ->expectCardExists('myHand', 'Horticounter', 'Player 2 was never offered the negate', 2)
    ->expectZoneCount('myBanish', 0, 1, 'Nothing was banished by a negation')
    ->expectDqEmpty()
    ->tested('Nightmare Coil')->covers(['negate', 'class-bonus', 'protection'], '[Class Bonus] This card\'s activation can\'t be **negated**.')->register();

// ---- Tristan, Shadowreaver: Lineage ----

const GA_TRISTAN_LINEAGE_TEXT = 'Tristan can level up into champions of the same base level. When she does, draw two cards.';

// Level 3 Shadowreaver levels into the other level 3 champion (Shadowdancer): memory cost 3, then draw two. A normal level 2 -> 3 level-up draws nothing.
gaScenario('tristan-shadowreaver-lineage-same-level-up-draws-two-cards')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tristan, Shadowreaver')
    ->seed('myMaterial', 'Tristan, Shadowdancer')
    ->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')
    ->endTurn()->endTurn(2)->answer(1, 'myMaterial-4')
    ->expectProperty('p1Field-0', 'CardID', 'he6kd7hocc', 'Shadowreaver leveled into Shadowdancer, a champion of the same base level')
    ->expectZoneCount('myHand', 10, 1, 'Eight cards (seven plus the draw phase\'s card) and two more from the lineage; the ordinary level-up control ends on eight')
    ->tested('Tristan, Shadowreaver')
    ->covers(['lineage', 'level-up', 'draw'], GA_TRISTAN_LINEAGE_TEXT)
    ->register();

// Control: an ordinary level 2 -> level 3 level-up into the same Shadowreaver draws nothing.
gaScenario('tristan-shadowreaver-ordinary-level-up-draws-no-cards')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tristan, Hired Blade')
    ->seed('myMaterial', 'Tristan, Shadowreaver')
    ->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')
    ->endTurn()->endTurn(2)->answer(1, 'myMaterial-4')
    ->expectProperty('p1Field-0', 'CardID', '4upufooz13', 'The ordinary level-up completed')
    ->expectZoneCount('myHand', 8, 1, 'No lineage draw for a normal level-up: only the draw phase\'s card')
    ->tested('Tristan, Shadowreaver')
    ->covers(['lineage', 'level-up', 'condition'], GA_TRISTAN_LINEAGE_TEXT)
    ->register();

// ---- Death Essence Amulet ----

const GA_AMULET_TEXT = 'Whenever an ally you control dies while it\'s not your turn, you may banish Death Essence Amulet. When you do, look at target opponent\'s hand or memory and discard a card from it.';

// Player 2's Maiden of Waning Bloom kills the Powered Armsmaster on player 2's turn. Answers: PASS (retaliation), then the banish YES/NO, then hand (YES) or memory (NO).
function gaAmulet(string $slug, bool $banish, ?bool $hand = null) {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Death Essence Amulet')
        ->seed('myField', 'Death Essence Amulet')->seed('myField', 'Powered Armsmaster')
        ->seed('myField', 'Maiden of Waning Bloom', player: 2)
        ->seed('myMemory', 'Gray Wolf', player: 2)
        ->endTurn()->attack('myField-1', 'theirField-2', player: 2)->answer(1, 'PASS')->answer(1, $banish ? 'YES' : 'NO');
    if ($banish) $s->answer(1, $hand ? 'YES' : 'NO');
    return $s;
}

gaAmulet('death-essence-amulet-memory-choice-discards-from-the-opponents-memory', true, false)
    ->answer(1, 'myTempZone-0')
    ->expectCardExists('myBanish', 'Death Essence Amulet', 'The Amulet banished itself')
    ->expectCardExists('myGraveyard', 'Gray Wolf', 'The opponent\'s memory card was discarded', 2)
    ->expectZoneCount('myMemory', 0, 2, 'Their memory is empty')
    ->expectZoneCount('myHand', 8, 2, 'Their hand was not touched (8 cards)')
    ->expectDqEmpty()
    ->tested('Death Essence Amulet')
    ->covers(['trigger', 'banish-self', 'discard', 'memory'], GA_AMULET_TEXT)
    ->register();

gaAmulet('death-essence-amulet-declining-the-banish-keeps-the-amulet-and-discards-nothing', false)
    ->expectCardExists('myField', 'Death Essence Amulet', 'The Amulet stayed on the field')
    ->expectZoneCount('myMemory', 1, 2, 'Their memory card was not discarded')
    ->expectZoneCount('myGraveyard', 0, 2, 'Nothing was discarded')
    ->expectDqEmpty()
    ->tested('Death Essence Amulet')
    ->covers(['trigger', 'optional'], GA_AMULET_TEXT)
    ->register();

// An ally dying on its controller's own turn does not trigger the Amulet.
gaScenario('death-essence-amulet-does-not-trigger-when-the-ally-dies-on-your-own-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Death Essence Amulet')
    ->seed('myField', 'Death Essence Amulet')->seed('myField', 'Powered Armsmaster')
    ->seed('myField', 'Maiden of Waning Bloom', player: 2)
    ->endTurn()->endTurn(2)->pass(1)
    ->attack('myField-2', 'theirField-1')->answer(2, 'myField-1')
    ->expectCardExists('myGraveyard', 'Powered Armsmaster', 'The Armsmaster died on its controller\'s own turn')
    ->expectCardExists('myField', 'Death Essence Amulet', 'The Amulet was not offered on your own turn')
    ->expectDqEmpty()
    ->tested('Death Essence Amulet')
    ->covers(['trigger', 'condition'], GA_AMULET_TEXT)
    ->register();

// ---- Steel Slug ----

const GA_STEEL_SLUG_RENEWABLE_TEXT = '**Renewable** *(If this card would be banished from the field or an intent, put it into its owner\'s material deck instead.)*';
const GA_STEEL_SLUG_LOAD_TEXT = '[REST]**:** Load CARDNAME into target unloaded Gun weapon you control.';

// A memory cost 1 regalia in the material deck: materialized in the materialize phase by paying one memory card.
gaScenario('steel-slug-materialize-phase-pays-its-one-memory-cost')
    ->deck("# Material\n1 Spirit of Fire\n1 Steel Slug\n# Main\n16 Dungeon Guide")
    ->seed('myMemory', 'Dungeon Guide')
    ->endTurn()->endTurn(2)->answer(1, 'myMaterial-0')
    ->expectCardExists('myField', 'Steel Slug', 'Steel Slug was materialized')
    ->expectZoneCount('myMemory', 0, 1, 'The memory card was spent on its memory cost 1')
    ->expectZoneCount('myBanish', 1, 1, 'The memory card was banished as the payment')
    ->expectDqEmpty()
    ->tested('Steel Slug')
    ->covers(['materialize', 'memory-cost'], 'memory cost 1')
    ->register();

gaScenario('steel-slug-cannot-be-materialized-with-an-empty-memory')
    ->deck("# Material\n1 Spirit of Fire\n1 Steel Slug\n# Main\n16 Dungeon Guide")
    ->endTurn()->endTurn(2)
    ->expectZoneCount('myMaterial', 1, 1, 'Steel Slug is still in the material deck: nothing to pay its memory cost with')
    ->expectZoneCount('myField', 1, 1, 'Only the champion is on the field')
    ->tested('Steel Slug')
    ->covers(['materialize', 'memory-cost', 'condition'], 'memory cost 1')
    ->register();

gaScenario('steel-slug-rests-to-load-itself-into-an-unloaded-gun')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Emerald Pistol')
    ->seed('myField', 'Steel Slug')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Subcards', '["ao8bki6fxx"]', 'Steel Slug is loaded into the Gun')
    ->expectZoneCount('myField', 2, 1, 'The slug left the field')
    ->tested('Steel Slug')
    ->covers(['load', 'rest', 'targeting'], GA_STEEL_SLUG_LOAD_TEXT)
    ->register();

// Attack with the loaded Gun (turn three: the first turn cannot attack): the slug goes to the intent, deals its 3 power, and Renewable sends it back to the material deck.
gaScenario('steel-slug-renewable-returns-to-the-material-deck-after-the-loaded-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Emerald Pistol', properties: ['Subcards' => ['ao8bki6fxx']])
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')
    ->expectDamage('p2Field-0', 4, 'Emerald Pistol 1 + Steel Slug 3')
    ->expectZoneCount('myMaterial', 5, 1, 'Renewable: the slug returned to the material deck (4 + 1)')
    ->expectZoneCount('myBanish', 0, 1, 'It was not banished')
    ->tested('Steel Slug')
    ->covers(['renewable', 'load', 'zone-replacement'], GA_STEEL_SLUG_RENEWABLE_TEXT)
    ->register();

// ---- Spirelle, Schwartz Queen ----

const GA_SPIRELLE_NEGATE_TEXT = '(2), [REST],  **Reveal a card banished by Spirelle with reserve cost X**: **Negate** each activation of cards with reserve cost **X**. Put each card **negated** this way and each card banished by Spirelle into their owner\'s memories. Then trigger each of Spirelle\'s  **on enter** abilities.';

// Spirelle banishes Gray Wolf from hand with her On Enter; on the opponent's turn the opponent activates their own reserve-2 Gray Wolf and Spirelle's ability negates it.
// An unrelated omen sits first in the banish zone: it is not "banished by Spirelle", so it can neither be revealed nor is it moved.
gaScenario('spirelle-schwartz-queen-negates-activations-with-the-revealed-reserve-cost-and-moves-the-banished-cards-to-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Spirelle, Schwartz Queen')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myHand', 'Spirelle, Schwartz Queen')->seed('myHand', 'Gray Wolf')->seed('myHand', 'Gray Wolf', player: 2)
    ->play('Spirelle, Schwartz Queen')->pay(2)->target('theirField-0')->answer(1, 'myHand-5')
    ->endTurn()->pass(1)->pass(1)->play('Gray Wolf', 2)->pay(2, 2)
    ->answer(1, 'myField-1@Activate-0@Negate')->pay(2)
    ->rejects(1, 'myBanish-0', 'The omen was not banished by Spirelle, so it cannot be revealed')
    ->answer(1, 'myBanish-1')->answer(1, 'theirField-0')->answer(1, 'PASS')
    ->expectDamage('p2Field-0', 6, 'Her on enter triggered again: 3 more damage to their champion (3 + 3)')
    ->expectCardExists('myMemory', 'Gray Wolf', 'The card banished by Spirelle was put into its owner\'s memory')
    ->expectCardExists('myBanish', 'Dungeon Guide', 'The omen stayed banished')
    ->expectZoneCount('myBanish', 1, 1, 'Only the omen remains banished')
    ->expectCardExists('myMemory', 'Gray Wolf', 'The negated Gray Wolf went to its controller\'s memory', 2)
    ->expectZoneCount('myMemory', 3, 2, 'Their two payments plus the negated Gray Wolf')
    ->expectZoneCount('myField', 1, 2, 'Their Gray Wolf never entered the field: only the champion')
    ->expectProperty('p1Field-1', 'Status', '1', 'Spirelle rested for the cost')
    ->expectDqEmpty()
    ->tested('Spirelle, Schwartz Queen')
    ->covers(['activated-ability', 'negate', 'reveal', 'cost', 'zone-movement', 'on-enter-retrigger'], GA_SPIRELLE_NEGATE_TEXT)
    ->register();

// Control: the revealed card has reserve cost 3 (Dungeon Guide), so the opponent's reserve-2 Gray Wolf is not negated.
gaScenario('spirelle-schwartz-queen-ability-leaves-activations-with-a-different-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Spirelle, Schwartz Queen')
    ->seed('myHand', 'Spirelle, Schwartz Queen')->seed('myHand', 'Gray Wolf', player: 2)
    ->play('Spirelle, Schwartz Queen')->pay(2)->target('theirField-0')->answer(1, 'myHand-0')
    ->endTurn()->pass(1)->pass(1)->play('Gray Wolf', 2)->pay(2, 2)
    ->answer(1, 'myField-1@Activate-0@Negate')->pay(2)->answer(1, 'myBanish-0')->answer(1, 'theirField-0')->answer(1, 'PASS')
    ->expectCardExists('myMemory', 'Dungeon Guide', 'The revealed card still went to memory')
    ->expectZoneCount('myBanish', 0, 1, 'Nothing is left banished by Spirelle')
    ->expectCardExists('myField', 'Gray Wolf', 'The reserve-2 Gray Wolf was not negated by a reserve-3 reveal', 2)
    ->tested('Spirelle, Schwartz Queen')
    ->covers(['activated-ability', 'negate', 'condition'], GA_SPIRELLE_NEGATE_TEXT)
    ->register();
