<?php
// Fire damage uses element membership, including Exalted Fire.
gaScenario('fire-damage-elements-synchron-dual')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Flared Iridescence')
    ->seed('myField', 'Extinguishing Synchron', player: 2)
    ->seed('myHand', 'Flared Iridescence')
    ->castSpell('Flared Iridescence', pay: 2, target: 'theirField-0', window: 'both')
    ->expectDamage('p2Field-0', 4, 'Dual Fire deals its printed damage')
    ->expectProperty('p2Field-1', 'Counters', '{"refinement":1}', 'Dual Fire damage gives one refinement counter')
    ->tested('Extinguishing Synchron')->covers(['damage', 'element', 'trigger'], 'Whenever your champion is dealt non-combat damage by a fire element source, put a refinement counter on this object.')->register();

gaScenario('fire-damage-elements-varuck-dual')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Flared Iridescence')
    ->seed('myField', 'Varuck, Smoldering Spire')
    ->patch('theirField-0', ['TurnEffects' => ['RIGHTEOUS_RETRIBUTION_5']])
    ->seed('myHand', 'Flared Iridescence')
    ->castSpell('Flared Iridescence', pay: 2, target: 'theirField-0', window: '')
    ->expectDamage('p2Field-0', 4, 'Dual Fire bypasses prevention under Varuck')
    ->tested('Varuck, Smoldering Spire')->covers(['damage', 'element', 'prevention'], 'Damage dealt by fire element sources you control cannot be prevented.')->register();

gaScenario('fire-damage-elements-without-varuck')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Flared Iridescence')
    ->patch('theirField-0', ['TurnEffects' => ['RIGHTEOUS_RETRIBUTION_5']])
    ->seed('myHand', 'Flared Iridescence')
    ->castSpell('Flared Iridescence', pay: 2, target: 'theirField-0', window: '')
    ->expectDamage('p2Field-0', 0, 'Without Varuck the same dual Fire damage is prevented')
    ->tested('Varuck, Smoldering Spire')->covers(['damage', 'element', 'prevention'], 'Damage dealt by fire element sources you control cannot be prevented.')->register();

gaScenario('fire-damage-elements-jianye-dual')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Flared Iridescence', classBonus: true)
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'WARRIOR']]])
    ->seed('myField', "Jianye, Dawn's Keep")
    ->seed('myField', 'Alizarin Longbowman')
    ->emptyZone('myHand', destZone: 'myDeck')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Flared Iridescence')
    ->action(1, 10002, 'myHand-2!FSM!')->pay(2)->target('myField-2')
    ->expectCardExists('myGraveyard', 'Alizarin Longbowman', 'The dual Fire ally dies')
    ->expectZoneCount('myHand', 1, 1, 'Jianye draws one after the dual Fire ally dies')
    ->tested("Jianye, Dawn's Keep")->covers(['death', 'element', 'draw'], 'Fire element allies have On Death: if influence is six or less, draw a card.')->register();

gaScenario('fire-damage-elements-ruby-dual')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Flared Iridescence')
    ->patch('myField-0', ['CardID' => GaScenario::id('Guo Jia, Chosen Disciple'), 'Subcards' => [GaScenario::id('Spirit of Fire'), GaScenario::id('Kongming, Fel Eidolon')]])
    ->seed('myField', 'Fabled Ruby Fatestone')
    ->seed('myHand', 'Flared Iridescence')
    ->castSpell('Flared Iridescence', pay: 2, target: 'theirField-0', window: '')
    ->expectDamage('p2Field-0', 4, 'The dual Fire source damages a unit')
    ->expectProperty('p1Field-0', 'Counters', '{"quest":1}', 'Guo Jia gains one quest counter for dual Fire damage')
    ->tested('Fabled Ruby Fatestone')->covers(['damage', 'element', 'quest'], 'Whenever non-combat damage is dealt to one or more units by a fire element source you control, put a quest counter on your champion.')->register();

gaScenario('fire-damage-elements-varuck-suppressed')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Flared Iridescence')
    ->seed('myField', 'Varuck, Smoldering Spire', properties: ['TurnEffects' => ['NO_ABILITIES']])
    ->patch('theirField-0', ['TurnEffects' => ['RIGHTEOUS_RETRIBUTION_5']])
    ->seed('myHand', 'Flared Iridescence')
    ->castSpell('Flared Iridescence', pay: 2, target: 'theirField-0', window: '')
    ->expectDamage('p2Field-0', 0, 'Suppressed Varuck does not bypass prevention')
    ->tested('Varuck, Smoldering Spire')->covers(['damage', 'element', 'prevention'], 'Damage dealt by fire element sources you control cannot be prevented.')->register();

