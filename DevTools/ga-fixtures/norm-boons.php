<?php
/**
 * Phase 3d (Norm): lesser boons. A boon is played from hand (castSpell, its "As you gain this boon" clauses resolve on entry) or seeded onto the field for its static effects. 'quiet' deck.
 */

// ---- Class-granting boons: the champion gains the class, observed through that class's Class Bonus ----

if (!function_exists('gaNormClassBoon')) {
    function gaNormClassBoon(string $slug, string $boon, callable $observe, string $clause): void {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor($boon)->seed('myField', $boon);
        $observe($s);
        $s->tested($boon)->covers(['static', 'class-grant', 'class-bonus'], $clause)->register();
    }
}

gaNormClassBoon('lesser-boon-of-veilara-the-champion-is-a-cleric-so-novice-healer-recovers', 'Lesser Boon of Veilara',
    fn($s) => $s->patch('myField-0', ['Damage' => 5])->seed('myHand', 'Novice Healer')->play('Novice Healer')->pay(3)->expectDamage('p1Field-0', 2, 'The Cleric class bonus applied: recover 3 (5 - 3)'),
    'Your champion is a Cleric class in addition to their other types.');

gaNormClassBoon('lesser-boon-of-artemis-the-champion-is-a-tamer-so-mechanical-hare-gets-a-buff-counter', 'Lesser Boon of Artemis',
    fn($s) => $s->seed('myHand', 'Mechanical Hare')->play('Mechanical Hare')->pay(2)->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'The Tamer class bonus applied'),
    'Your champion is a Tamer class in addition to their other types.');

gaNormClassBoon('lesser-boon-of-etherealys-the-champion-is-a-mage-so-tome-of-knowledge-grants-a-level', 'Lesser Boon of Etherealys',
    fn($s) => $s->seed('myField', 'Tome of Knowledge')->observe()->expectLevel('p1Field-0', 1, 'The Mage class bonus applied: +1 level'),
    'Your champion is a Mage class in addition to their other types.');

gaNormClassBoon('lesser-boon-of-odysseus-the-champion-is-a-warrior-so-sealed-blade-gets-plus-one-power', 'Lesser Boon of Odysseus',
    fn($s) => $s->seed('myField', 'Sealed Blade')->observe()->expectPower('p1Field-2', 3, 'The Warrior class bonus applied: 2 + 1'),
    'Your champion is a Warrior class in addition to their other types.');

gaNormClassBoon('lesser-boon-of-ozymandias-the-champion-is-a-guardian-so-gearshift-block-costs-one-less', 'Lesser Boon of Ozymandias',
    fn($s) => $s->seed('myField', 'Dungeon Guide')->seed('myHand', 'Gearshift Block')->castSpell('Gearshift Block', pay: 2, window: '', target: 'myField-2')->expectZoneCount('myMemory', 2, 1, 'The Guardian class bonus applied: cost 3 - 1'),
    'Your champion is a Guardian class in addition to their other types.');

gaNormClassBoon('lesser-boon-of-pulousa-the-champion-is-an-assassin-so-merciless-toss-costs-one-less', 'Lesser Boon of Pulousa',
    fn($s) => $s->seed('myField', 'Giant Tortoise', player: 2)->seed('myHand', 'Merciless Toss')->castSpell('Merciless Toss', pay: 2, window: '', target: 'theirField-1')->expectDamage('p2Field-1', 2, 'The Assassin class bonus applied: cost 3 - 1, and 2 damage'),
    'Your champion is an Assassin class in addition to their other types.');

gaNormClassBoon('lesser-boon-of-apollo-the-champion-is-a-ranger-so-mob-vantage-costs-two-less', 'Lesser Boon of Apollo',
    fn($s) => $s->seed('myField', 'Dungeon Guide', player: 2)->seed('myField', 'Dungeon Guide', player: 2)->seed('myHand', 'Mob Vantage')->castSpell('Mob Vantage', pay: 1, window: '')->expectZoneCount('myMemory', 2, 1, 'The Ranger class bonus applied: cost 3 - 2, plus a card drawn into memory'),
    'Your champion is a Ranger class in addition to their other types.');

// ---- Lesser Boon of Fauna ----

gaScenario('lesser-boon-of-fauna-gives-plus-one-level-for-each-of-up-to-two-animal-allies')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Fauna')
    ->seed('myField', 'Lesser Boon of Fauna')
    ->seed('myField', 'Gray Wolf')
    ->seed('myField', 'Gray Wolf')
    ->seed('myField', 'Gray Wolf')
    ->observe()
    ->expectLevel('p1Field-0', 2, 'Three Beast allies, counted up to two: +2 level')
    ->tested('Lesser Boon of Fauna')
    ->covers(['level', 'stat-change', 'condition'], 'For each of up to two Animal and/or Beast allies you control, your champion gets +1 level.')
    ->register();

// ---- Lesser Boon of Regret ----

gaScenario('lesser-boon-of-regret-puts-hand-cards-on-the-bottom-and-draws-that-many')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Regret')
    ->seed('myHand', 'Lesser Boon of Regret')
    ->play('Lesser Boon of Regret')->answer(1, 'myHand-0&myHand-1&myHand-2')
    ->expectZoneCount('myHand', 7, 1, 'Three cards went to the bottom and three were drawn (8 - 1 played, unchanged by the swap)')
    ->expectZoneCount('myDeck', 9, 1, 'The deck is the same size: three on the bottom, three drawn')
    ->tested('Lesser Boon of Regret')
    ->covers(['zone-movement', 'draw'], 'As you gain this boon, put up to seven cards from your hand on the bottom of your deck in any order. Then draw that many cards.')
    ->register();

// ---- Lesser Boon of Time's Passage ----

gaScenario('lesser-boon-of-times-passage-draws-into-memory-at-the-recollection-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Time\'s Passage')
    ->seed('myField', 'Lesser Boon of Time\'s Passage')
    ->reachTurnThree()
    ->expectZoneCount('myHand', 9, 1, 'A card was drawn into memory at the beginning of the recollection phase and returned to hand with the rest of memory (7 + the turn draw + 1)')
    ->tested('Lesser Boon of Time\'s Passage')
    ->covers(['trigger', 'draw-into-memory'], 'At the beginning of your recollection phase, draw a card into your memory. Trigger this ability only twice.')
    ->register();

// ---- Lesser Boon of Dux ----

gaScenario('lesser-boon-of-dux-class-locked-makes-an-ally-fostered')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Dux', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Lesser Boon of Dux')
    ->castSpell('Lesser Boon of Dux', pay: 2, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","FOSTERED"]', 'As you gain this boon the chosen ally became fostered')
    ->tested('Lesser Boon of Dux')
    ->covers(['foster', 'targeting'], 'As you gain this boon, choose an ally and it becomes **fostered**.')
    ->register();

// ---- Scavenge boons ----

gaScenario('lesser-boon-of-viscosity-scavenges-ten-for-a-slime-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Viscosity')
    ->deckTop(['Dungeon Guide', 'Baby Gray Slime'])
    ->seed('myHand', 'Lesser Boon of Viscosity')
    ->castSpell('Lesser Boon of Viscosity', pay: 3, window: '')->answer(1, 'myTempZone-8')
    ->expectCardExists('myHand', 'Baby Gray Slime', 'The Slime card within the top ten was put into the hand')
    ->tested('Lesser Boon of Viscosity')
    ->covers(['scavenge'], 'As you gain this boon, **scavenge 10** for a Slime card.')
    ->register();

gaScenario('lesser-boon-of-enchantment-scavenges-ten-for-a-phantasia-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Enchantment')
    ->deckTop(['Dungeon Guide', 'Bygone Days'])
    ->seed('myHand', 'Lesser Boon of Enchantment')
    ->castSpell('Lesser Boon of Enchantment', pay: 3, window: '')->answer(1, 'myTempZone-8')
    ->expectCardExists('myHand', 'Bygone Days', 'The phantasia card within the top ten was put into the hand')
    ->tested('Lesser Boon of Enchantment')
    ->covers(['scavenge'], 'As you gain this boon, **scavenge 10** for a phantasia card.')
    ->register();

gaScenario('lesser-boon-of-scriveners-scavenges-ten-for-a-spell-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Scriveners')
    ->deckTop(['Dungeon Guide', 'Purification'])
    ->seed('myHand', 'Lesser Boon of Scriveners')
    ->castSpell('Lesser Boon of Scriveners', pay: 3, window: '')->answer(1, 'myTempZone-8')
    ->expectCardExists('myHand', 'Purification', 'The Spell card within the top ten was put into the hand')
    ->tested('Lesser Boon of Scriveners')
    ->covers(['scavenge'], 'As you gain this boon, **scavenge 10** for a Spell card.')
    ->register();

// ---- Element-enabling boons (Agni / Notus / Poseidon): the 'spirit-dungeon-guide' deck's champion is a Spirit of Water, so only water is enabled without a boon ----

if (!function_exists('gaNormElementBoon')) {
    function gaNormElementBoon(string $slug, string $boon, string $card, int $pay, string $element, string $deck = 'spirit-dungeon-guide'): void {
        gaScenario($slug . '-lets-the-champion-activate-a-' . strtolower($element) . '-card')
            ->deckTemplate($deck, 'Dungeon Guide')
            ->seed('myField', $boon)
            ->seed('myHand', $card)
            ->play($card)->pay($pay)
            ->expectCardExists('myField', $card, "The boon enables $element: the card was played")
            ->tested($boon)
            ->covers(['static', 'element'], "$element element is enabled for you.")
            ->register();
        gaScenario($slug . '-without-the-boon-the-' . strtolower($element) . '-card-cannot-be-activated')
            ->deckTemplate($deck, 'Dungeon Guide')
            ->seed('myHand', $card)
            ->play($card)
            ->expectZoneCount('myField', 1, 1, "Without the boon $element is not enabled: the card was not played")
            ->tested($boon)
            ->covers(['static', 'element', 'condition'], "$element element is enabled for you.")
            ->register();
    }
}
gaNormElementBoon('lesser-boon-of-agni', 'Lesser Boon of Agni', 'Spark Fairy', 3, 'FIRE');
gaNormElementBoon('lesser-boon-of-notus', 'Lesser Boon of Notus', 'Longtail Grovesward', 3, 'WIND');
gaNormElementBoon('lesser-boon-of-poseidon', 'Lesser Boon of Poseidon', 'Giant Tortoise', 4, 'WATER', 'quiet');

// ---- Lesser Boon of Awilix ----

gaScenario('lesser-boon-of-awilix-returns-regalia-from-a-banishment-to-their-owners-material-deck')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Awilix')
    ->seed('myBanish', 'Prototype Pistol')
    ->seed('myBanish', 'Gem of Sorority', player: 2)
    ->seed('myHand', 'Lesser Boon of Awilix')
    ->play('Lesser Boon of Awilix')->answer(1, 'myBanish-0&theirBanish-0')
    ->expectCardExists('myMaterial', 'Prototype Pistol', 'Your regalia returned to your material deck')
    ->expectCardExists('myMaterial', 'Gem of Sorority', 'The opponent\'s regalia returned to the opponent\'s material deck', 2)
    ->tested('Lesser Boon of Awilix')
    ->covers(['zone-movement', 'targeting'], 'As you gain this boon, put up to four target regalia cards from any banishment into their owner’s material deck.')
    ->register();
