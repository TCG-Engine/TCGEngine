<?php
/**
 * Phase E (Neos): Hector's imbued On Enter, Tonoris's token-weapon sacrifice buff, domain-count cost reductions (Iron Halo, Tor, Obelisks).
 */

const GA_HECTOR_ENTER_TEXT = '[Class Bonus] **On Enter:** If Hector is **imbued**, **summon** two Automaton Drone tokens.';

// Hector (Imbue 3: three neos cards among the reserve payments) is played from a hand of $neos Production Crawldroids; the payments take $payFrom.
function gaHector(string $slug, bool $classBonus, bool $imbued): GaScenario {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Hector, Praetorian Guard', classBonus: $classBonus)
        ->seed('myHand', 'Production Crawldroid')->seed('myHand', 'Production Crawldroid')->seed('myHand', 'Production Crawldroid')
        ->seed('myHand', 'Hector, Praetorian Guard')
        ->play('Hector, Praetorian Guard')->answer(1, $imbued ? 'YES' : 'NO');
    $slot = $imbued ? 'myHand-7' : 'myHand-0';
    return $s->answer(1, $slot)->answer(1, $slot)->answer(1, $slot);
}

gaHector('hector-praetorian-guard-imbued-class-bonus-enter-summons-two-automaton-drones', true, true)
    ->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Three neos cards were paid into memory: imbued')
    ->expectZoneCount('myField', 4, 1, 'The champion, Hector and two Automaton Drone tokens')
    ->expectCardExists('myField', 'Automaton Drone', 'A drone token was summoned')
    ->expectDqEmpty()
    ->tested('Hector, Praetorian Guard')
    ->covers(['class-bonus', 'on-enter', 'imbue', 'summon', 'token'], GA_HECTOR_ENTER_TEXT)
    ->register();

gaHector('hector-praetorian-guard-not-imbued-enter-summons-no-drones', true, false)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'Imbue declined: not imbued')
    ->expectZoneCount('myField', 2, 1, 'Only the champion and Hector')
    ->expectDqEmpty()
    ->tested('Hector, Praetorian Guard')
    ->covers(['on-enter', 'imbue', 'condition'], GA_HECTOR_ENTER_TEXT)
    ->register();

gaHector('hector-praetorian-guard-imbued-without-the-class-bonus-summons-no-drones', false, true)
    ->expectProperty('p1Field-1', 'TurnEffects', '["IMBUED","ENTERED_THIS_TURN"]', 'Imbued, but the champion is not a Guardian')
    ->expectZoneCount('myField', 2, 1, 'No Class Bonus: no drones')
    ->expectDqEmpty()
    ->tested('Hector, Praetorian Guard')
    ->covers(['class-bonus', 'on-enter', 'condition'], GA_HECTOR_ENTER_TEXT)
    ->register();

const GA_TONORIS_BUFF_TEXT = 'Token weapons you control have "**Sacrifice this object:** Target weapon gets +**X** [POWER] until end of turn where **X** is this object\'s power."';

// A Greatsword token (power 3) and a Training Sword (power 1) on the field; the token's sacrifice ability is activated at its slot (myField-1).
function gaTonorisSacrifice(string $slug, bool $tonoris, ?string $target): GaScenario {
    $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide');
    if ($tonoris) $s->champion(cardName: 'Tonoris, Creation\'s Will');
    $s->seed('myField', 'Aurousteel Greatsword')->seed('myField', 'Training Sword')->seed('myField', 'Training Sword', player: 2)
      ->action(1, 10001, 'myField-1!CustomInput!Activate:0');
    return $target === null ? $s : $s->answer(1, $target);
}

gaTonorisSacrifice('tonoris-creations-will-token-weapon-sacrifice-gives-another-weapon-plus-its-power', true, 'myField-1')
    ->expectZoneCount('myField', 2, 1, 'The token weapon was sacrificed (tokens cease to exist): the champion and the Training Sword remain')
    ->expectPower('p1Field-1', 4, 'Training Sword 1 + the sacrificed token\'s power 3')
    ->expectDqEmpty()
    ->tested('Tonoris, Creation\'s Will')
    ->covers(['granted-ability', 'sacrifice', 'power-buff', 'token-weapon'], GA_TONORIS_BUFF_TEXT)
    ->register();

gaTonorisSacrifice('tonoris-creations-will-token-weapon-sacrifice-can-target-an-opposing-weapon', true, 'theirField-1')
    ->expectPower('p2Field-1', 4, 'Target weapon: the opposing Training Sword gets +3 too')
    ->expectZoneCount('myField', 2, 1, 'The token weapon was sacrificed')
    ->expectPower('p1Field-1', 1, 'My own Training Sword is untouched')
    ->expectDqEmpty()
    ->tested('Tonoris, Creation\'s Will')
    ->covers(['granted-ability', 'sacrifice', 'power-buff', 'targeting'], GA_TONORIS_BUFF_TEXT)
    ->register();

gaTonorisSacrifice('token-weapons-have-no-sacrifice-buff-without-tonoris-creations-will', false, null)
    ->expectCardExists('myField', 'Aurousteel Greatsword', 'Without Tonoris the token has no such ability: it was not sacrificed')
    ->expectPower('p1Field-2', 1, 'Nothing was buffed')
    ->expectDqEmpty()
    ->tested('Tonoris, Creation\'s Will')
    ->covers(['granted-ability', 'condition'], GA_TONORIS_BUFF_TEXT)
    ->register();

const GA_OBELISK_ARMS_TEXT = '(5), [REST]: **Summon** an Aurousteel Greatsword token. This ability costs (1) less to activate for each domain you control.';

gaScenario('obelisk-of-armaments-costs-one-less-for-each-of-two-domains-you-control')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Obelisk of Armaments', classBonus: true)
    ->seed('myField', 'Obelisk of Armaments', properties: ['Status' => 2])
    ->seed('myField', 'Iron Halo, Forcefield Node')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)
    ->expectCardExists('myField', 'Aurousteel Greatsword', 'Two domains: (5) - 2 = 3 payments')
    ->expectZoneCount('myMemory', 3, 1, 'Exactly three cards were paid')
    ->tested('Obelisk of Armaments')
    ->covers(['activated-ability', 'summon', 'cost-reduction'], GA_OBELISK_ARMS_TEXT)
    ->register();

gaScenario('obelisk-of-armaments-does-not-count-an-opponents-domain')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Obelisk of Armaments', classBonus: true)
    ->seed('myField', 'Obelisk of Armaments', properties: ['Status' => 2])
    ->seed('myField', 'Iron Halo, Forcefield Node', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)
    ->expectCardExists('myField', 'Aurousteel Greatsword', 'Only my own domain counts: (5) - 1 = 4 payments')
    ->expectZoneCount('myMemory', 4, 1, 'Exactly four cards were paid')
    ->tested('Obelisk of Armaments')
    ->covers(['activated-ability', 'cost-reduction', 'controller'], GA_OBELISK_ARMS_TEXT)
    ->register();

const GA_TOR_ENTER_TEXT = '[Class Bonus] **On Enter:** Sacrifice up to three domains. For each domain sacrificed this way, put 2+**X** **durability** counters on CARDNAME where **X** is that domain\'s reserve cost.';

function gaTorEnter(string $slug, string $sacrifice): GaScenario {
    return gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
        ->readyFor('Tor, Realmwalker Colossus', classBonus: true)
        ->seed('myField', 'Epicurean Institute')->seed('myField', 'Iron Halo, Forcefield Node')
        ->seed('myHand', 'Tor, Realmwalker Colossus')
        ->play('Tor, Realmwalker Colossus')->pay(3)->answer(1, $sacrifice);
}

gaTorEnter('tor-realmwalker-colossus-enter-sacrifices-two-domains-for-durability-per-domain', 'myField-1&myField-2')
    ->expectCardExists('myField', 'Tor, Realmwalker Colossus', 'Tor stays on the field')
    ->expectZoneCount('myField', 3, 1, 'Both other domains were sacrificed: the champion, Tor and the Elysian Test Subject Epicurean Institute leaves behind')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":12}', 'Printed 4 + (2 + 1) for Epicurean Institute + (2 + 3) for Iron Halo')
    ->expectPower('p1Field-1', 3, '+1 power for each four of the 12 durability counters')
    ->expectDqEmpty()
    ->tested('Tor, Realmwalker Colossus')
    ->covers(['class-bonus', 'on-enter', 'sacrifice', 'durability', 'multiple'], GA_TOR_ENTER_TEXT)
    ->register();

gaTorEnter('tor-realmwalker-colossus-enter-may-sacrifice-no-domains', '')
    ->expectZoneCount('myField', 4, 1, 'Nothing was sacrificed: the champion, both domains and Tor')
    ->expectProperty('p1Field-3', 'Counters', '{"durability":4}', 'Tor keeps only its printed 4 durability')
    ->expectDqEmpty()
    ->tested('Tor, Realmwalker Colossus')
    ->covers(['class-bonus', 'on-enter', 'optional-choice', 'sacrifice'], GA_TOR_ENTER_TEXT)
    ->register();
