<?php
/**
 * Phase 3e (Luxem). readyFor() enables the luxem element (and the class bonus where asked) through the champion lineage. 'quiet' deck.
 */

// ---- Illuminate Secrets ----

gaScenario('illuminate-secrets-deals-damage-equal-to-the-owners-influence-minus-yours')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Illuminate Secrets')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myMemory', 'Dungeon Guide', player: 2)->seed('myMemory', 'Dungeon Guide', player: 2)->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Illuminate Secrets')
    ->play('Illuminate Secrets')->pay(2)->target('theirField-1')->passBoth()
    ->expectDamage('p2Field-1', 3, 'Opponent influence 7 hand + 3 memory = 10; yours 5 hand + 2 memory = 7: 3 damage')
    ->tested('Illuminate Secrets')
    ->covers(['damage', 'influence', 'targeting'], 'Deal an amount of damage to target unit equal to its owner\'s **influence** minus your **influence**.')
    ->register();

// ---- Sunblessed Gazelle ----

gaScenario('sunblessed-gazelle-enter-with-low-influence-draws-and-recovers-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sunblessed Gazelle', damage: 5)
    ->emptyZone('myHand')
    ->seed('myHand', 'Sunblessed Gazelle')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->action(1, 10002, 'myHand-0!FSM!')->pay(2)
    ->expectDamage('p1Field-0', 2, 'Influence 2 (three or less): recover 3 (5 - 3)')
    ->expectZoneCount('myHand', 1, 1, 'And a card was drawn')
    ->expectLife('p1Field-1', 8, '1 life + the opponent\'s influence of 7')
    ->tested('Sunblessed Gazelle')
    ->covers(['on-enter', 'influence', 'draw', 'recover', 'stat-change'], '**On Enter**: If your **influence** is three or less, draw a card and **recover 3**.', 'Sunblessed Gazelle gets +**X** [LIFE] where **X** is the **highest** influence among your opponents.')
    ->register();

gaScenario('sunblessed-gazelle-enter-with-higher-influence-does-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sunblessed Gazelle', damage: 5)
    ->seed('myHand', 'Sunblessed Gazelle')
    ->play('Sunblessed Gazelle')->pay(2)
    ->expectDamage('p1Field-0', 5, 'Influence 7 (more than three): no recover')
    ->tested('Sunblessed Gazelle')
    ->covers(['on-enter', 'influence', 'condition'], '**On Enter**: If your **influence** is three or less, draw a card and **recover 3**.')
    ->register();

// ---- Zander, Corhazi's Chosen ----

gaScenario('zander-corhazis-chosen-enter-gains-stealth-and-spellshroud-and-a-preparation-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Zander, Corhazi\'s Chosen')
    ->observe()
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","STEALTH_NEXT_TURN","SPELLSHROUD_NEXT_TURN"]', 'Stealth and spellshroud until the beginning of the next turn')
    ->expectProperty('p1Field-1', 'Counters', '{"preparation":1}', 'One preparation counter')
    ->tested('Zander, Corhazi\'s Chosen')
    ->covers(['on-enter', 'stealth', 'spellshroud', 'counter'], '**On Enter:** CARDNAME gains **stealth** and **spellshroud** until the beginning of your next turn. Put a **preparation** counter on CARDNAME.')
    ->register();

// ---- Pang Tong, Young Phoenix (Balance) ----

if (!function_exists('gaPangTong')) {
    function gaPangTong(string $slug, int $memory): GaScenario {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Pang Tong, Young Phoenix');
        for ($i = 0; $i < $memory; ++$i) $s->seed('myMemory', 'Dungeon Guide');
        return $s->seed('myHand', 'Off With Her Head', player: 2)->endTurn()
            ->play('Off With Her Head', 2)->pay(2, 2)->target('theirField-1', 2)->passBoth();
    }
}

$gaBalanceClause = 'Balance — If damage would be dealt to a unit you control while the amount of cards in your hand and memory are equal, prevent all but 3 of that damage.';

gaPangTong('pang-tong-young-phoenix-balance-prevents-all-but-three-damage', 7)
    ->expectDamage('p1Field-1', 3, 'Hand 7 and memory 7 are equal: the 5 damage was reduced to 3')
    ->tested('Pang Tong, Young Phoenix')->covers(['balance', 'prevention', 'condition'], $gaBalanceClause)->register();

gaPangTong('pang-tong-young-phoenix-without-balance-takes-the-full-damage', 0)
    ->expectCardExists('myGraveyard', 'Pang Tong, Young Phoenix', 'Hand 7 and memory 0 are different: the 5 damage killed him (4 life)')
    ->tested('Pang Tong, Young Phoenix')->covers(['balance', 'condition'], $gaBalanceClause)->register();

// ---- Caliburn of Silencing ----

gaScenario('caliburn-of-silencing-class-bonus-champion-hit-removes-the-hit-champions-abilities')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Caliburn of Silencing', classBonus: true)
    ->seed('myField', 'Caliburn of Silencing')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->passBoth()
    ->expectDamage('p2Field-0', 1, 'The dagger\'s 1 damage')
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P1","NO_ABILITIES"]', 'The hit champion lost all abilities')
    ->tested('Caliburn of Silencing')
    ->covers(['class-bonus', 'on-champion-hit', 'no-abilities'], '[Class Bonus] **On Champion Hit:** The hit champion loses all abilities until the beginning of your next turn.')
    ->register();

// ---- Carnwennan, Shrouded Edge ----

if (!function_exists('gaCarnwennan')) {
    function gaCarnwennan(string $slug, bool $classBonus, int $retaliation, string $label): void {
        $s = gaScenario($slug)
            ->deckTemplate('quiet', 'Dungeon Guide')
            ->readyFor('Carnwennan, Shrouded Edge', classBonus: $classBonus)
            ->seed('myField', 'Carnwennan, Shrouded Edge')
            ->seed('myField', 'Giant Tortoise', player: 2)
            ->reachTurnThree()
            ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-1');
        if ($retaliation > 0) $s->answer(2, 'myField-1');
        $s->expectDqEmpty()
            ->expectDamage('p2Field-1', 3, 'Carnwennan dealt its 3 damage to the Giant Tortoise')
            ->expectDamage('p1Field-0', $retaliation, $label)
            ->tested('Carnwennan, Shrouded Edge')
            ->covers(['class-bonus', 'retaliate', 'condition'], '[Class Bonus] Attacks using CARDNAME can\'t be retaliated.')
            ->register();
    }
}

gaCarnwennan('carnwennan-shrouded-edge-class-bonus-attacks-cannot-be-retaliated', true, 0, 'The Giant Tortoise did not retaliate');
gaCarnwennan('carnwennan-shrouded-edge-without-the-class-bonus-the-target-retaliates', false, 1, 'No class bonus: the Giant Tortoise retaliated for 1');

gaScenario('caliburn-of-silencing-without-the-class-bonus-the-hit-champion-keeps-its-abilities')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Caliburn of Silencing')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->passBoth()
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN","CHAMP_DMG_BY_P1"]', 'No class bonus: no NO_ABILITIES')
    ->tested('Caliburn of Silencing')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] **On Champion Hit:**')
    ->register();

// ---- Uther, Illustrious King ----

gaScenario('uther-illustrious-king-enter-rests-him-to-banish-another-non-champion-object')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Uther, Illustrious King')
    ->answer(1, 'YES')->target('theirField-1')
    ->expectProperty('p1Field-1', 'Status', '1', 'Uther rested to banish the object')
    ->expectCardExists('theirBanish', 'Dungeon Guide', 'The opposing Dungeon Guide was banished')
    ->expectZoneCount('theirField', 1, 1, 'Only the champion remains on the opposing field')
    ->tested('Uther, Illustrious King')
    ->covers(['on-enter', 'rest', 'banish', 'targeting'], '**On Enter:** You may rest Uther. When you do, banish another target non-champion object.')
    ->register();

gaScenario('uther-illustrious-king-on-leave-returns-the-banished-object-rested')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Uther, Illustrious King')
    ->answer(1, 'YES')->target('theirField-1')
    ->seed('myHand', 'Off With Her Head', player: 2)
    ->endTurn()
    ->play('Off With Her Head', 2)->pay(2, 2)->target('theirField-1', 2)->passBoth()
    ->expectCardExists('myGraveyard', 'Uther, Illustrious King', 'Uther took 5 damage (a unique ally) and was destroyed')
    ->expectCardExists('theirField', 'Dungeon Guide', 'On Leave: the banished Dungeon Guide returned to its owner\'s field')
    ->tested('Uther, Illustrious King')
    ->covers(['on-leave', 'return'], '**On Leave:** Return the banished object to the field under its owner\'s control rested.')
    ->register();

// ---- Excalibur, Cleansing Light ----

gaScenario('excalibur-cleansing-light-destroys-a-non-champion-object')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Excalibur, Cleansing Light')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Excalibur, Cleansing Light')
    ->play('Excalibur, Cleansing Light')->pay(2)->target('theirField-1')->passBoth()
    ->expectCardExists('theirGraveyard', 'Giant Tortoise', 'The non-champion object was destroyed')
    ->tested('Excalibur, Cleansing Light')
    ->covers(['destroy', 'targeting'], 'Destroy target non-champion object.')
    ->register();

gaScenario('excalibur-cleansing-light-class-bonus-chosen-elements-cannot-be-activated-until-your-next-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Excalibur, Cleansing Light', classBonus: true)
    ->readyFor('Dissuading Halt', player: 2)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Excalibur, Cleansing Light')
    ->seed('myHand', 'Dissuading Halt', player: 2)
    ->play('Excalibur, Cleansing Light')->pay(2)->pass(2)->target('theirField-1')->answer(1, '0')
    ->expectGlobalEffect(1, 'qtRBz9azeZ-P1-WATER', 'The water element is locked until the beginning of your next turn')
    ->endTurn()->pass(2)->pass(2)
    ->play('Dissuading Halt', 2)
    ->expectDqEmpty()
    ->expectZoneCount('myHand', 9, 2, 'The water spell cannot be activated: nothing was started (7 + the spell + the turn draw)')
    ->expectZoneCount('myMemory', 0, 2, 'No payment was made')
    ->tested('Excalibur, Cleansing Light')
    ->covers(['class-bonus', 'element-lock'], '[Class Bonus] Choose up to two non-norm elements. Players can\'t materialize or activate cards of those elements until the beginning of your next turn.')
    ->register();

// ---- Illuminating Charge ----

gaScenario('illuminating-charge-reveals-memory-and-puts-up-to-two-animal-cards-onto-the-field')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Illuminating Charge')
    ->seed('myMemory', 'Giant Tortoise')->seed('myMemory', 'Giant Tortoise')->seed('myMemory', 'Giant Tortoise')->seed('myMemory', 'Dungeon Guide')
    ->seed('myHand', 'Illuminating Charge')
    ->play('Illuminating Charge')->pay(3)->answer(1, 'myMemory-0')->answer(1, 'myMemory-0')
    ->expectZoneCount('myField', 3, 1, 'Two Animal cards from memory entered the field beside the champion')
    ->expectZoneCount('myMemory', 5, 1, 'Seven in memory (4 seeded + 3 paid) minus the two')
    ->tested('Illuminating Charge')
    ->covers(['reveal', 'zone-movement', 'animal'], 'Reveal all cards in your memory. You may put up to two Animal cards from among them onto the field.')
    ->register();

// ---- Lumen Borealis / Felicitous Flock ----

gaScenario('lumen-borealis-class-bonus-animal-allies-get-plus-one-power-and-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lumen Borealis', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myField', 'Lumen Borealis')
    ->answer(1, '-')
    ->observe()->expectPower('p1Field-1', 2, 'Giant Tortoise 1 power + 1')
    ->expectLife('p1Field-1', 7, 'Giant Tortoise 6 life + 1')
    ->tested('Lumen Borealis')
    ->covers(['class-bonus', 'static', 'stat-change', 'animal'], '[Class Bonus] Animal allies you control get +1 [POWER] and +1 [LIFE].')
    ->register();

gaScenario('felicitous-flock-element-and-class-bonus-revealing-it-from-memory-summons-a-buffed-intercepting-fledgling')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Corhazi\'s Chosen', classes: 'TAMER')
    ->seed('myMemory', 'Felicitous Flock')
    ->seed('myHand', 'Lumen Borealis')
    ->play('Lumen Borealis')->pay(3)->answer(1, 'myMemory-0')
    ->expectCardExists('myField', 'Fledgling', 'A Fledgling token was summoned')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'With a buff counter')
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN","INTERCEPT_EOT"]', 'It has intercept until end of turn')
    ->tested('Felicitous Flock')
    ->covers(['element-bonus', 'class-bonus', 'trigger', 'reveal', 'summon', 'intercept'], '[Element Bonus] [Class Bonus] Whenever you reveal CARDNAME from your memory, **summon** a Fledgling token with a **buff** counter on it. It gains **intercept** until end of turn.')
    ->register();

gaScenario('felicitous-flock-without-the-bonuses-revealing-it-summons-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lumen Borealis')
    ->seed('myMemory', 'Felicitous Flock')
    ->seed('myHand', 'Lumen Borealis')
    ->play('Lumen Borealis')->pay(3)->answer(1, 'myMemory-0')
    ->expectZoneCount('myField', 2, 1, 'No element/class bonus: only the champion and the phantasia')
    ->tested('Felicitous Flock')
    ->covers(['element-bonus', 'class-bonus', 'condition'], '[Element Bonus] [Class Bonus] Whenever you reveal CARDNAME from your memory')
    ->register();

// ---- Fractured Crown (Champion Link) ----

if (!function_exists('gaCrown')) {
    function gaCrown(string $slug, bool $classBonus): GaScenario {
        return gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')
            ->readyFor('Fractured Crown', classBonus: $classBonus)
            ->patch('myField-0', ['Subcards' => [GaScenario::id('Lorraine, Arclight Saber'), GaScenario::id('Fractured Crown')], 'Counters' => $classBonus ? ['_overrides' => ['classes' => 'WARRIOR']] : []])
            ->seed('myField', 'Fractured Crown', properties: ['Counters' => ['linkedToChampion' => GaScenario::id('Spirit of Fire')]])
            ->seed('myField', 'Training Sword')
            ->seed('myGraveyard', 'Camil, Basked Abundance')->seed('myGraveyard', 'Noire, Ace of Spades')->seed('myGraveyard', 'Dungeon Guide');
    }
}

$gaCrownClause = 'Linked champion\'s first attack each turn gets +2 [POWER]. **Class Bonus:** That champion also gets +2 [LIFE] for each unique ally card in your graveyard.';

gaCrown('fractured-crown-class-bonus-linked-champion-gets-plus-two-life-per-unique-ally-in-the-graveyard', true)
    ->observe()->expectLife('p1Field-0', 19, 'Spirit of Fire 15 + 2 for each of the two unique allies (the Dungeon Guide is not unique)')
    ->tested('Fractured Crown')->covers(['champion-link', 'class-bonus', 'stat-change', 'graveyard'], $gaCrownClause)->register();

gaCrown('fractured-crown-without-the-class-bonus-the-linked-champion-has-its-base-life', false)
    ->observe()->expectLife('p1Field-0', 15, 'No class bonus: the champion\'s printed 15')
    ->tested('Fractured Crown')->covers(['champion-link', 'class-bonus', 'condition'], $gaCrownClause)->register();

gaCrown('fractured-crown-linked-champions-first-attack-gets-plus-two-power', false)
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-2')->answer(1, 'theirField-0')->passBoth()
    ->expectDamage('p2Field-0', 3, 'Training Sword\'s 1 plus 2 for the first attack')
    ->tested('Fractured Crown')->covers(['champion-link', 'stat-change', 'first-attack'], $gaCrownClause)->register();

// ---- Luxera's Map ----

gaScenario('luxeras-map-rest-banish-searches-the-deck-for-a-card-and-puts-it-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Luxera\'s Map', properties: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myTempZone-0')
    ->expectZoneCount('myMemory', 1, 1, 'The chosen card was put into memory')
    ->expectCardExists('myBanish', 'Luxera\'s Map', 'The Map banished itself')
    ->expectZoneCount('myDeck', 8, 1, 'One card left the deck (the rest was shuffled)')
    ->tested('Luxera\'s Map')
    ->covers(['activated-ability', 'rest', 'banish-self', 'search', 'shuffle'], '[REST]**, Banish CARDNAME:** Search your deck for a card and put it into your memory. Then shuffle your deck.')
    ->register();

gaScenario('luxeras-map-ability-cannot-be-activated-at-fast-speed')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Luxera\'s Map', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectDqEmpty()
    ->expectCardExists('myField', 'Luxera\'s Map', 'On the opponent\'s turn (not slow speed) the ability was refused and the Map stays')
    ->tested('Luxera\'s Map')
    ->covers(['activated-ability', 'slow-speed', 'condition'], 'Activate this ability only at slow speed.')
    ->register();

// ---- Glorious Presence ----

if (!function_exists('gaGloriousPresence')) {
    // Player 2 (a luxem-enabled champion) answers player 1's end-of-turn window with the fast luxem spell Illuminate Secrets (2).
    function gaGloriousPresence(string $slug, bool $shenju): GaScenario {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Glorious Presence')->readyFor('Illuminate Secrets', player: 2)
            ->seed('myField', 'Glorious Presence');
        if ($shenju) $s->seed('myField', 'Suzaku, Vermillion Phoenix');
        return $s->seed('myHand', 'Illuminate Secrets', player: 2)->endTurn()->answer(2, 'myHand-7');
    }
}

$gaGloriousClause = 'As long as you control a Shenju ally, advanced element cards your opponents activate cost 2 more to activate.';

gaGloriousPresence('glorious-presence-with-a-shenju-ally-opposing-advanced-element-cards-cost-two-more', true)
    ->pay(4, 2)->target('theirField-0', 2)
    ->expectCardExists('theirGraveyard', 'Illuminate Secrets', 'Paid 2 + 2 and the luxem spell resolved')
    ->tested('Glorious Presence')->covers(['static', 'cost-increase', 'shenju'], $gaGloriousClause)->register();

gaGloriousPresence('glorious-presence-paying-only-the-printed-cost-is-not-enough', true)
    ->pay(2, 2)->rejects(2, 'theirField-0', 'The luxem spell still needs two more payments')
    ->tested('Glorious Presence')->covers(['static', 'cost-increase', 'shenju'], $gaGloriousClause)->register();

gaGloriousPresence('without-a-shenju-ally-glorious-presence-adds-no-cost', false)
    ->pay(2, 2)->target('theirField-0', 2)
    ->expectCardExists('theirGraveyard', 'Illuminate Secrets', 'Paid only 2 and the luxem spell resolved')
    ->tested('Glorious Presence')->covers(['static', 'condition'], $gaGloriousClause)->register();

gaScenario('lumen-borealis-enter-may-reveal-a-card-from-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lumen Borealis')
    ->seed('myMemory', 'Giant Tortoise')
    ->seed('myHand', 'Lumen Borealis')
    ->play('Lumen Borealis')->pay(3)->answer(1, 'myMemory-0')
    ->expectDqEmpty()
    ->expectZoneCount('myMemory', 4, 1, 'Revealing does not move the card: the seeded card and three payments are all still in memory')
    ->tested('Lumen Borealis')
    ->covers(['on-enter', 'reveal', 'optional-choice'], '**On Enter**: You may reveal a card from your memory.')
    ->register();
