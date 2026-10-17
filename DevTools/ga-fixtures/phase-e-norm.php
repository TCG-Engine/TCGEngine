<?php
/**
 * Phase E (Norm): rules clauses recorded as unexercised: Crystallized Destiny prevention, Suited Trickery / Plea for Peace
 * restrictions, Allowance Race, Equivalent Exchange, Dodge Roll.
 */

const GA_DESTINY_TEXT = 'The next time damage would be dealt to your champion this turn, prevent that damage. When 7 or more damage is prevented this way, cards your opponents activate this turn cost (2) more to activate.';

gaScenario('crystallized-destiny-prevents-the-next-combat-damage-to-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Crystallized Destiny')
    ->seed('myField', 'Airborne Squirrel')->seed('myField', 'Airborne Squirrel')
    ->seed('myHand', 'Crystallized Destiny')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(1)->pass(1)->pass(2)
    ->expectDamage('p1Field-0', 0, 'The attack on the champion was prevented')
    ->expectDqEmpty()
    ->tested('Crystallized Destiny')
    ->covers(['prevention', 'champion', 'damage'], GA_DESTINY_TEXT)
    ->register();

const GA_TRICKERY_TEXT = 'Until the beginning of your next turn, players can\'t declare attacks with champions they control unless they pay (2) for each attack declaration. Then if you control a Suited ally, draw a card into your memory.';

gaScenario('suited-trickery-with-a-suited-ally-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Suited Trickery')
    ->seed('myField', 'Four of Hearts')
    ->seed('myHand', 'Suited Trickery')
    ->castSpell('Suited Trickery', pay: 1, window: 'me')
    ->expectZoneCount('myMemory', 2, 1, 'One card paid plus the draw into memory')
    ->expectDqEmpty()
    ->tested('Suited Trickery')
    ->covers(['restriction', 'draw-discard', 'condition'], GA_TRICKERY_TEXT)
    ->register();

gaScenario('suited-trickery-without-a-suited-ally-draws-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Suited Trickery')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Suited Trickery')
    ->castSpell('Suited Trickery', pay: 1, window: '')
    ->expectZoneCount('myMemory', 1, 1, 'No Suited ally: only the payment is in memory')
    ->expectDqEmpty()
    ->tested('Suited Trickery')
    ->covers(['restriction', 'draw-discard', 'condition'], GA_TRICKERY_TEXT)
    ->register();

gaScenario('suited-trickery-taxes-the-opposing-champion-attack-declaration')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Suited Trickery')
    ->seed('myField', 'Reliable Blade', player: 2, properties: ['Counters' => ['durability' => 1]])
    ->seed('myHand', 'Suited Trickery')
    ->castSpell('Suited Trickery', pay: 1, window: '')
    ->endTurn()
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->pay(2, 2)->answer(2, 'theirField-0')
    ->expectZoneCount('myMemory', 2, 2, 'The opposing champion paid (2) to declare the attack')
    ->tested('Suited Trickery')
    ->covers(['restriction', 'cost', 'champion'], GA_TRICKERY_TEXT)
    ->register();

gaScenario('suited-trickery-does-not-tax-an-ally-attack-declaration')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Suited Trickery')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->seed('myHand', 'Suited Trickery')
    ->castSpell('Suited Trickery', pay: 1, window: '')
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')
    ->expectZoneCount('myMemory', 0, 2, 'An ally attack declaration is free')
    ->expectDamage('p1Field-0', 1, 'The ally attack was declared and hit')
    ->tested('Suited Trickery')
    ->covers(['restriction', 'cost', 'ally'], GA_TRICKERY_TEXT)
    ->register();

const GA_ALLOWANCE_TEXT = 'Each player may rest up to two Horse allies they control. For each Horse ally rested this way, its controller draws a card.';

gaScenario('allowance-race-rests-two-horses-for-each-player-and-each-draws-per-horse')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Allowance Race')
    ->seed('myField', 'Ordinary Horse')->seed('myField', 'Ordinary Horse')
    ->seed('myField', 'Ordinary Horse', player: 2)->seed('myField', 'Ordinary Horse', player: 2)
    ->seed('myHand', 'Allowance Race')
    ->castSpell('Allowance Race', pay: 3, window: '')->answer(1, 'myField-1')->answer(1, 'myField-2')->answer(2, 'myField-1')->answer(2, 'myField-2')
    ->expectProperty('p1Field-1', 'Status', '1', 'First own Horse rested')
    ->expectProperty('p1Field-2', 'Status', '1', 'Second own Horse rested')
    ->expectProperty('p2Field-1', 'Status', '1', 'The opponent rested their first Horse')
    ->expectProperty('p2Field-2', 'Status', '1', 'The opponent rested their second Horse')
    ->expectZoneCount('myHand', 6, 1, 'Two Horses rested: two cards drawn (8 - 1 - 3 + 2)')
    ->expectZoneCount('myHand', 9, 2, 'The opponent drew one card per Horse they rested')
    ->expectDqEmpty()
    ->tested('Allowance Race')
    ->covers(['rest', 'draw', 'opponent'], GA_ALLOWANCE_TEXT)
    ->register();

gaScenario('allowance-race-declining-the-second-horse-draws-only-for-the-first')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Allowance Race')
    ->seed('myField', 'Ordinary Horse')->seed('myField', 'Ordinary Horse')
    ->seed('myHand', 'Allowance Race')
    ->castSpell('Allowance Race', pay: 3, window: '')->answer(1, 'myField-1')->answer(1, 'PASS')
    ->expectProperty('p1Field-1', 'Status', '1', 'The chosen Horse rested')
    ->expectProperty('p1Field-2', 'Status', '2', 'The declined Horse stayed awake')
    ->expectZoneCount('myHand', 5, 1, 'One Horse rested: one card drawn (8 - 1 - 3 + 1)')
    ->expectDqEmpty()
    ->tested('Allowance Race')
    ->covers(['rest', 'draw', 'optional-choice'], GA_ALLOWANCE_TEXT)
    ->register();

const GA_EXCHANGE_TEXT = '**Summon** a Lost Being token rested. Then you may banish two ally cards from a single graveyard. If you do, wake up that token and put a **buff** counter on it.';

gaScenario('equivalent-exchange-banishing-two-allies-wakes-the-token-and-buffs-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Equivalent Exchange')
    ->seed('myGraveyard', 'Dungeon Guide')->seed('myGraveyard', 'Gray Wolf')
    ->seed('myHand', 'Equivalent Exchange')
    ->castSpell('Equivalent Exchange', pay: 4, window: '')->answer(1, 'YES')->answer(1, 'myGraveyard-0')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 2, 1, 'Both ally cards were banished from the graveyard')
    ->expectProperty('p1Field-1', 'Status', '2', 'The Lost Being token woke up')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The token got a buff counter')
    ->expectDqEmpty()
    ->tested('Equivalent Exchange')
    ->covers(['summon', 'banish', 'wake', 'counter'], GA_EXCHANGE_TEXT)
    ->register();

gaScenario('equivalent-exchange-declining-the-banish-leaves-the-token-rested-and-unbuffed')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Equivalent Exchange')
    ->seed('myGraveyard', 'Dungeon Guide')->seed('myGraveyard', 'Gray Wolf')
    ->seed('myHand', 'Equivalent Exchange')
    ->castSpell('Equivalent Exchange', pay: 4, window: '')->answer(1, 'NO')
    ->expectZoneCount('myBanish', 0, 1, 'Declined: nothing banished')
    ->expectProperty('p1Field-1', 'Status', '1', 'The token stays rested')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No buff counter')
    ->expectDqEmpty()
    ->tested('Equivalent Exchange')
    ->covers(['summon', 'banish', 'optional-choice'], GA_EXCHANGE_TEXT)
    ->register();

gaScenario('equivalent-exchange-one-ally-in-each-graveyard-is-not-a-single-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Equivalent Exchange')
    ->seed('myGraveyard', 'Dungeon Guide')->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Equivalent Exchange')
    ->castSpell('Equivalent Exchange', pay: 4, window: '')
    ->expectZoneCount('myBanish', 0, 1, 'Only one ally card per graveyard: no banish offered')
    ->expectProperty('p1Field-1', 'Status', '1', 'The token stays rested')
    ->expectDqEmpty()
    ->tested('Equivalent Exchange')
    ->covers(['banish', 'single-graveyard', 'condition'], GA_EXCHANGE_TEXT)
    ->register();

gaScenario('equivalent-exchange-banishes-two-allies-from-the-opponents-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Equivalent Exchange')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Equivalent Exchange')
    ->castSpell('Equivalent Exchange', pay: 4, window: '')->answer(1, 'YES')->answer(1, 'theirGraveyard-0')->answer(1, 'theirGraveyard-0')
    ->expectZoneCount('myBanish', 2, 1, 'Two allies from the opposing graveyard were banished')
    ->expectProperty('p1Field-1', 'Status', '2', 'The token woke up')
    ->expectDqEmpty()
    ->tested('Equivalent Exchange')
    ->covers(['summon', 'banish', 'wake', 'opponent'], GA_EXCHANGE_TEXT)
    ->register();

const GA_DODGE_TEXT = 'Prevent the next 3 non-combat damage that would be dealt to target unit you control this turn. That unit becomes **distant**.';

gaScenario('dodge-roll-does-not-prevent-combat-damage-to-the-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dodge Roll')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Dodge Roll')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)->pass(1)
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myHand-7')->pay(1)->answer(1, 'myField-1')->pass(1)->pass(2)
    ->expectDamage('p1Field-1', 1, 'Dodge Roll prevents non-combat damage only: the combat damage still landed')
    ->expectProperty('p1Field-1', 'TurnEffects', '["PREVENT_NONCOMBAT_3","DISTANT","DAMAGED_SINCE_LAST_TURN"]', 'The unit became distant and still holds the unused prevention')
    ->tested('Dodge Roll')
    ->covers(['prevention', 'combat', 'condition'], GA_DODGE_TEXT)
    ->register();

gaScenario('crystallized-destiny-with-one-fatebound-object-has-no-discount')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Crystallized Destiny')
    ->seed('myField', 'Airborne Squirrel')
    ->seed('myHand', 'Crystallized Destiny')
    ->castSpell('Crystallized Destiny', pay: 3, window: '')
    ->expectCardExists('myGraveyard', 'Crystallized Destiny', 'One Fatebound object is not two: the spell cost the full 3')
    ->expectZoneCount('myMemory', 3, 1, 'Three cards paid')
    ->expectDqEmpty()
    ->tested('Crystallized Destiny')
    ->covers(['cost-reduction', 'condition'], 'As long as you control two or more Fatestone and/or Fatebound objects, this card costs 2 less to activate.')
    ->register();
