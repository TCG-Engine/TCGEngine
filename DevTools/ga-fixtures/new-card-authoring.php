<?php
// Previously blank hosted definitions; exercise the normal generated dispatch.
gaScenario('dinah-lost-spirit-enter-draws-seven')
    ->standardDeck('Dungeon Guide')->seed('myField', 'Dinah, Lost Spirit')->observe()
    ->expectZoneCount('myHand', 14, 1, 'Dinah adds seven to the opening seven')->expectDqEmpty()->tested('Dinah, Lost Spirit')
    ->covers(['on-enter', 'draw'], 'On Enter: Draw seven cards.')->register();

gaScenario('lesser-boon-virelai-scavenges-harmony')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Lesser Boon of Virelai')
    ->deckTop(['Dungeon Guide', 'Empowering Harmony', 'Dungeon Guide'])
    ->seed('myHand', 'Lesser Boon of Virelai')
    ->castSpell('Lesser Boon of Virelai', pay: 3, window: '')
    ->expectCardExists('myHand', 'Empowering Harmony', 'Scavenge finds the first matching Harmony')->expectDqEmpty()
    ->tested('Lesser Boon of Virelai')->covers(['scavenge'], 'Scavenge 10 for a Harmony or Melody card.')->register();

gaScenario('lesser-boon-fractals-discount-and-rested-pair')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Lesser Boon of Fractals')
    ->seed('myField', 'Core Fractal')->seed('myField', 'Core Fractal')
    ->seed('myHand', 'Lesser Boon of Fractals')
    ->castSpell('Lesser Boon of Fractals', pay: 4, window: '')
    ->expectZoneCount('myMemory', 4, 1, 'Two Fractals reduce ten reserve to four')->expectZoneCount('myField', 6, 1, 'Two additional Fractals were summoned')
    ->expectProperty('p1Field-4', 'Status', '1')->expectProperty('p1Field-5', 'Status', '1')
    ->expectDqEmpty()->tested('Lesser Boon of Fractals')
    ->covers(['cost-reduction', 'summon'], 'Costs 3 less for each Fractal; summon two Core Fractals rested.')->register();

gaScenario('stormblade-squire-banishes-memory-and-scavenges-arcane')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Stormblade Squire')
    ->seed('myField', 'Stormblade Squire')->seed('myHand', 'Strategic Warfare')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide', 'Unstable Voltage', 'Dungeon Guide'])
    ->reachTurnThree()->castSpell('Strategic Warfare', pay: 2, window: 'both')->attack('myField-1', 'theirField-0')
    ->answer(1, 'YES')
    ->expectZoneCount('myBanish', 1, 1, 'One memory card was banished')->expectCardExists('myHand', 'Unstable Voltage', 'Scavenge finds the Arcane card')
    ->tested('Stormblade Squire')->covers(['on-attack', 'random', 'scavenge'], 'Banish random memory; scavenge 2+X for arcane.')->register();

gaScenario('unstable-voltage-rolls-two-dice-for-ally-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Unstable Voltage')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Counters' => ['_overrides' => ['life' => 30]]])
    ->seed('myHand', 'Unstable Voltage')->castSpell('Unstable Voltage', pay: 2, window: '', target: 'theirField-1')
    ->expectDamage('p2Field-1', 3, 'Two deterministic D6 rolls total three for this fixture seed')
    ->expectDqEmpty()->tested('Unstable Voltage')
    ->covers(['damage', 'random'], 'Deal D6+D6 damage to target ally.')->register();

gaScenario('lesser-boon-refuge-counts-wind-boons-and-lineage')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Lesser Boon of Refuge')
    ->seed('myHand', 'Lesser Boon of Refuge')
    ->castSpell('Lesser Boon of Refuge', pay: 2, window: '', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["PREVENT_ALL_4"]', 'Two base, one Wind boon, one Wind lineage')
    ->expectDqEmpty()->tested('Lesser Boon of Refuge')->covers(['prevention', 'targeting'], 'Prevent 2+X+Y damage to target object.')->register();

gaScenario('lesser-boon-proxia-copies-opposing-zero-cost-regalia')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Lesser Boon of Proxia')
    ->seed('myField', 'Backup Charger', player: 2)->seed('myHand', 'Lesser Boon of Proxia')
    ->castSpell('Lesser Boon of Proxia', pay: 3, window: 'opp', target: 'theirField-1')
    ->expectCardExists('myField', 'Backup Charger', 'The controller summons a copy')
    ->expectProperty('p1Field-2', 'Counters', '{"_overrides":{"type":"REGALIA,ITEM,TOKEN"}}', 'The copy is a token')
    ->expectDqEmpty()->tested('Lesser Boon of Proxia')->covers(['summon', 'copy', 'targeting'], 'Summon a token copy of opposing regalia with memory cost zero.')->register();

gaScenario('chronowarp-installs-permanent-distorted-time')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Chronowarp')
    ->seed('myHand', 'Chronowarp')->castSpell('Chronowarp', pay: 4, window: '')
    ->expectGlobalEffect(1, 'TIME_DISTORTED', 'Time is distorted for the rest of the game')
    ->expectDqEmpty()->tested('Chronowarp')->covers(['permanent-effect', 'phase'], 'For the rest of the game, time is distorted.')->register();

gaScenario('lesser-boon-flock-summons-fledgling')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Lesser Boon of Flock')
    ->seed('myHand', 'Lesser Boon of Flock')->castSpell('Lesser Boon of Flock', pay: 2)
    ->expectCardExists('myField', 'Fledgling', 'Gaining the boon summons a Fledgling')
    ->expectDqEmpty()->tested('Lesser Boon of Flock')->covers(['summon'], 'As you gain this boon, summon a Fledgling.')->register();

gaScenario('sword-saint-gives-sword-power-and-entry-durability')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Lesser Boon of Sword Saint')
    ->seed('myField', 'Training Sword')->observe()
    ->expectPower('p1Field-2', 2, 'Sword gains one power')
    ->expectProperty('p1Field-2', 'Counters', '{"durability":3}', 'Sword enters with one additional durability')
    ->tested('Lesser Boon of Sword Saint')->covers(['static', 'counter'], 'Swords get +1 power and enter with an extra durability counter.')->register();

gaScenario('prismatic-perseverance-enables-advanced-element-at-thresholds')
    ->deckTemplate('quiet', 'Dungeon Guide')->champion(level: 2, damage: 15)
    ->seed('myField', 'Prismatic Perseverance')->seed('myHand', 'Unstable Voltage')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Counters' => ['_overrides' => ['life' => 30]]])
    ->castSpell('Unstable Voltage', pay: 2, target: 'theirField-1')
    ->expectCardExists('myGraveyard', 'Unstable Voltage', 'Arcane card activated without Arcane lineage')
    ->tested('Prismatic Perseverance')->covers(['static', 'element', 'level-gate', 'damage-gate'], 'Level 2+, Damage 15+: all elements are enabled.')->register();

foreach(['YES','NO'] as $triskitAnswer) {
    $s = gaScenario('triskit-champion-transformation-' . strtolower($triskitAnswer))
        ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Triskit, Guidance Angel')
        ->answer(1, $triskitAnswer)->tested('Triskit, Guidance Angel')
        ->covers(['on-enter', 'transform', 'optional'], 'May banish your champion; Triskit becomes a unique champion with base level 3.');
    if($triskitAnswer === 'YES') $s->expectLevel('p1Field-0', 3, 'Triskit has base level three')->expectCardExists('myBanish', 'Spirit of Fire', 'The old champion was banished');
    else $s->expectZoneCount('myField', 2, 1, 'Declining preserves champion and ally')->expectZoneCount('myBanish', 0, 1, 'Declining banishes nothing');
    $s->expectDqEmpty()->register();
}

foreach(['YES','NO'] as $solomonAnswer) {
    $s = gaScenario('solomon-generates-sights-' . strtolower($solomonAnswer))
        ->deckTemplate('quiet', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')
        ->seed('myField', 'Solomon, Master of Elements')->answer(1, $solomonAnswer)
        ->tested('Solomon, Master of Elements')->covers(['on-enter', 'discard', 'generate', 'optional'], 'Optionally discard hand and memory and generate eight Sights into memory.');
    if($solomonAnswer === 'YES') {
        $s->expectZoneCount('myHand', 0, 1, 'The entire hand was discarded')
            ->expectZoneCount('myGraveyard', 8, 1, 'Seven hand cards and one memory card were discarded')
            ->expectZoneCount('myMemory', 8, 1, 'Exactly eight Sights were generated');
        foreach(['Arcane','Astra','Crux','Exia','Luxem','Neos','Tera','Umbra'] as $element) {
            $s->expectCardExists('myMemory', $element.' Sight', 'Generated '.$element.' Sight')
                ->expectGlobalEffect(1, 'IGNORE_ELEMENT_NAME:'.$element.'%20Sight', 'Permanent element bypass for '.$element.' Sight');
        }
    } else $s->expectZoneCount('myHand', 7, 1, 'Declining preserves hand')->expectZoneCount('myMemory', 1, 1, 'Declining preserves memory');
    $s->expectDqEmpty()->register();
}

gaScenario('harness-lightning-empower-and-self-banish')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Harness Lightning')
    ->seed('myHand', 'Harness Lightning')->castSpell('Harness Lightning', pay: 1)->answer(1, '0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","bzwj7ztr78","EMPOWERED","EMPOWER_PLUS_4"]', 'Empower four is installed')
    ->expectPower('p1Field-0', 0, 'Empower does not give champion power')
    ->expectCardExists('myBanish', 'Harness Lightning', 'Harness Lightning banishes itself')
    ->expectDqEmpty()->tested('Harness Lightning')->covers(['modal', 'empower', 'banish'], 'Empower four, then banish Harness Lightning.')->register();

gaScenario('harness-lightning-buffs-ally-and-self-banishes')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Harness Lightning')
    ->seed('myField', 'Dungeon Guide')->seed('myHand', 'Harness Lightning')
    ->castSpell('Harness Lightning', pay: 1)->answer(1, '1')->target('myField-1')
    ->expectPower('p1Field-1', 5, 'Dungeon Guide gets four additional power')
    ->expectCardExists('myBanish', 'Harness Lightning', 'Harness Lightning banishes itself')
    ->expectDqEmpty()->tested('Harness Lightning')->covers(['modal', 'stat-change', 'banish'], 'Target ally gets four power, then banish Harness Lightning.')->register();

gaScenario('spurred-gallop-discounts-next-horse-and-draws-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Spurred Gallop')
    ->seed('myHand', 'Spurred Gallop')->seed('myHand', 'Trusty Steed')
    ->castSpell('Spurred Gallop', pay: 2)->castSpell('Trusty Steed', pay: 2)
    ->expectCardExists('myField', 'Trusty Steed', 'Horse activated for two instead of four reserve')
    ->expectZoneCount('myMemory', 5, 1, 'Four payments plus the drawn memory card')
    ->expectGlobalEffect(1, '8iNWBAkqkS', 'Horse consumes the one-shot permission', count: 0)
    ->expectDqEmpty()->tested('Spurred Gallop')->covers(['cost-reduction', 'draw-into-memory'], 'Draw memory; next Horse costs two less.')->register();

gaScenario('nourishment-scavenges-a-food')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Lesser Boon of Nourishment')
    ->deckTop(['Dungeon Guide', 'Forest Cake', 'Dungeon Guide'])
    ->seed('myHand', 'Lesser Boon of Nourishment')->castSpell('Lesser Boon of Nourishment', pay: 4)
    ->expectCardExists('myHand', 'Forest Cake', 'Scavenge takes the first Food')
    ->expectDqEmpty()->tested('Lesser Boon of Nourishment')->covers(['scavenge'], 'Scavenge thirteen for Kitchen or Food.')->register();

gaScenario('nourishment-gives-food-and-draws-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Lesser Boon of Nourishment')
    ->seed('myField', 'Delicious Pastry', properties: ['Counters'=>['age'=>2]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myField-2')->pass(2)
    ->expectZoneCount('myField', 2, 1, 'Food left your control')
    ->expectCardExists('theirField', 'Delicious Pastry', 'Opponent now controls the Food')
    ->expectProperty('p2Field-1', 'Controller', '2', 'Controller changed')
    ->expectProperty('p2Field-1', 'Owner', '1', 'Ownership did not change')
    ->expectProperty('p2Field-1', 'Counters', '{"age":2}', 'Existing counters are preserved')
    ->expectZoneCount('myMemory', 3, 1, 'Two payments and one draw into memory')
    ->expectDqEmpty()->tested('Lesser Boon of Nourishment')->covers(['activated-ability', 'control', 'draw-into-memory'], 'Pay two: give Food to opponent and draw into memory.')->register();

gaScenario('banner-ares-fourth-wind-activation-buffs-entering-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Windrider Vanguard')
    ->seed('myField', 'Banner of Ares', properties: ['Counters'=>['refinement'=>3]])
    ->seed('myHand', 'Windrider Vanguard')->castSpell('Windrider Vanguard', pay: 3)
    ->expectProperty('p1Field-1', 'Counters', '{"refinement":4}', 'Activation brings Banner to four before resolution')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'That Wind ally enters with a buff counter')
    ->expectDqEmpty()->tested('Banner of Ares')->covers(['activation-trigger', 'counter', 'entry-replacement'], 'Wind activation adds refinement; at four, Wind allies enter with buff.')->register();

gaScenario('greater-boon-rosen-pays-two-powercells-before-reserve')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Greater Boon of Rosen')
    ->seed('myField', 'Powercell')->seed('myField', 'Powercell')
    ->seed('myHand', 'Greater Boon of Rosen')->play('Greater Boon of Rosen')
    ->answer(1, 'myField-1&myField-2')->pay(3)
    ->expectZoneCount('myField', 2, 1, 'Only champion and Boon remain after sacrificing both Powercells')
    ->expectCardExists('myField', 'Greater Boon of Rosen', 'Boon resolves after its complete cost')
    ->expectZoneCount('myMemory', 3, 1, 'Normal reserve cost is also paid')
    ->expectDqEmpty()->tested('Greater Boon of Rosen')->covers(['additional-cost', 'sacrifice'], 'Sacrifice two Powercell items as an additional cost.')->register();

gaScenario('greater-boon-rosen-cannot-bestow-with-one-powercell')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Greater Boon of Rosen')
    ->seed('myField', 'Powercell')->seed('myHand', 'Greater Boon of Rosen')->play('Greater Boon of Rosen')
    ->expectCardExists('myHand', 'Greater Boon of Rosen', 'Unpayable additional cost leaves the card in hand')
    ->expectCardExists('myField', 'Powercell', 'No partial sacrifice')
    ->expectZoneCount('myMemory', 0, 1, 'No reserve spent on an illegal activation')
    ->tested('Greater Boon of Rosen')->covers(['restriction', 'additional-cost'], 'Require both Powercells before activation.')->register();

gaScenario('duality-convergence-copies-both-players-regalia-once')
    ->deckTemplate('quiet', 'Dungeon Guide')->champion(lineage: ['Merlin, Brilliant Vestige'])
    ->seed('myField', 'Backup Charger')->seed('myField', 'Training Sword', player: 2)
    ->seed('myField', 'Backup Charger', properties: ['Counters'=>['_overrides'=>['subtypes'=>'CLERIC,ACCESSORY,DISTORTION']]])
    ->seed('myHand', "Duality's Convergence")->castSpell("Duality's Convergence", pay: 3, window: 'both')
    ->expectZoneCount('myField', 4, 1, 'Only the non-Distortion regalia is copied')
    ->expectZoneCount('theirField', 3, 1, 'Opponent also gets a copy')
    ->expectProperty('p1Field-3', 'Counters', '{"_overrides":{"subtypes":"CLERIC,ACCESSORY,DISTORTION","type":"REGALIA,ITEM,TOKEN"}}', 'Own copy is a token Distortion')
    ->expectProperty('p2Field-2', 'Controller', '2', 'Opponent controls their copy')
    ->expectDqEmpty()->tested("Duality's Convergence")->covers(['copy', 'summon', 'cost-reduction'], 'Merlin discount; each player copies non-Distortion regalia as Distortion tokens.')->register();

$s = gaScenario('arcane-renunciation-recovers-arcane-and-empowers')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Arcane Renunciation');
for($i = 0; $i < 5; ++$i) $s->seed('myHand', 'Dungeon Guide');
$s->seed('myHand', 'Arcane Sight')->seed('myHand', 'Arcane Renunciation')
    ->seed('myGraveyard', 'Unstable Voltage')->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myBanish', 'Harness Lightning')->castSpell('Arcane Renunciation', pay: 12, window: 'me')
    ->expectZoneCount('myMemory', 0, 1, 'All memory is banished')
    ->expectZoneCount('myHand', 3, 1, 'Only three eligible Arcane cards return')
    ->expectCardExists('myHand', 'Arcane Sight', 'Arcane hand card is banished and recovered')
    ->expectCardExists('myHand', 'Unstable Voltage', 'Arcane graveyard card returns')
    ->expectCardExists('myHand', 'Harness Lightning', 'Arcane banishment card returns')
    ->expectCardExists('myGraveyard', 'Arcane Renunciation', 'Resolving action cannot return itself')
    ->expectCardExists('myGraveyard', 'Dungeon Guide', 'Non-Arcane graveyard card stays')
    ->expectProperty('p1Field-0', 'TurnEffects', '["qK7Et5ZTeT","EMPOWERED","EMPOWER_PLUS_10","vz4kc558yx-ACTION_ACTIVATED"]', 'Champion is empowered ten')
    ->expectDqEmpty()->tested('Arcane Renunciation')->covers(['banish', 'return-to-hand', 'empower'], 'Banish hand and memory, recover eligible Arcane cards, empower ten.')->register();

$s = gaScenario('constellary-predomination-diana-glimpses-eight')
    ->deckTemplate('quiet', 'Dungeon Guide')->champion(lineage: ['Diana, Moonpiercer'])
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Constellary Predomination')
    ->deckTop(['Unstable Voltage','Dungeon Guide','Dungeon Guide','Dungeon Guide','Dungeon Guide','Dungeon Guide','Dungeon Guide','Dungeon Guide'])
    ->castSpell('Constellary Predomination', pay: 8)
    ->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,em6eEh9q8y;Bottom=hiN2g99FLF')
    ->expectProperty('p1Deck-0','CardID','em6eEh9q8y','Diana bonus rearranges all eight cards')
    ->expectGlobalEffect(1, 'Z1L77xhMcC', 'Permanent champion attack grant installed')
    ->expectDqEmpty()->tested('Constellary Predomination')->covers(['glimpse','persistent-effect'], 'Diana glimpses eight and champion gains permanent attack trigger.')->register();

gaScenario('seize-fate-jin-discount-installs-damage-replacement')
    ->deckTemplate('quiet', 'Dungeon Guide')->champion(cardName: 'Jin, Undying Resolve')
    ->seed('myHand', 'Seize Fate')->castSpell('Seize Fate', pay: 4)
    ->expectZoneCount('myMemory', 4, 1, 'Jin reduces six reserve to four')
    ->expectGlobalEffect(1, 'l61ubi93jx', 'Permanent damage replacement is installed')
    ->expectDqEmpty()->tested('Seize Fate')->covers(['cost-reduction','persistent-effect'], 'Jin discount and permanent champion damage replacement.')->register();

gaScenario('mistbound-cutthroat-returns-rested-on-another-milled-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')->champion(level: 3)
    ->seed('myGraveyard', 'Mistbound Cutthroat')
    ->seed('myField', 'Ardus, Floodborne Deacon')->observe()
    ->expectCardExists('myField', 'Mistbound Cutthroat', 'Milling another ally returns Cutthroat')
    ->expectProperty('p1Field-2', 'Status', '1', 'Cutthroat returns rested')
    ->expectDqEmpty()->tested('Mistbound Cutthroat')->covers(['mill','graveyard-trigger','return-to-field'], 'At level three, another ally milled returns Cutthroat rested.')->register();

gaScenario('auravolt-current-exchanges-both-players-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Auravolt Current')
    ->seed('myMemory', 'Dungeon Guide', player: 2)->seed('myMemory', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Auravolt Current')->castSpell('Auravolt Current', pay: 2)->answer(1, '3')
    ->answer(1, 'p1Memory-0&p1Memory-1')->answer(2, 'p2Memory-0&p2Memory-1')
    ->expectZoneCount('myMemory', 2, 1, 'Player one replaces two banished memory cards')
    ->expectZoneCount('myMemory', 2, 2, 'Player two replaces two banished memory cards')
    ->expectZoneCount('myBanish', 3, 1, 'Own banishment has two payments and resolved Auravolt')
    ->expectZoneCount('myBanish', 2, 2, 'Opponent banishes their two memory cards')
    ->expectCardExists('myBanish', 'Auravolt Current', 'Auravolt banishes itself')
    ->expectDqEmpty()->tested('Auravolt Current')->covers(['banish','draw-into-memory','multiple-players'], 'Each chosen player selects two memory cards to exchange.')->register();

gaScenario('auravolt-current-activates-from-charged-banishment')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Auravolt Current')
    ->seed('myBanish', 'Auravolt Current', properties: ['Counters'=>['charge'=>1]])
    ->action(1, 10002, 'myBanish-0!FSM!')->pay(2)->answer(1, '0')
    ->expectZoneCount('myMemory', 2, 1, 'Activation pays its normal reserve cost')
    ->expectCardExists('myBanish', 'Auravolt Current', 'Resolved card banishes itself again')
    ->expectProperty('p1Banish-0', 'Counters', '[]', 'The old charge does not follow through new zones')
    ->expectDqEmpty()->tested('Auravolt Current')->covers(['banishment-activation','restriction'], 'A charged copy activates from banishment, with its normal costs.')->register();

gaScenario('cyclonic-strike-suppresses-once-and-loses-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')->champion(classes: 'GUARDIAN')
    ->seed('myIntent', 'Cyclonic Strike')->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myIntent-0!CustomInput!Activate:0')->target('theirField-1')
    ->expectCardExists('theirBanish', 'Dungeon Guide', 'Opposing ally was suppressed')
    ->expectPower('p1Intent-0', 2, 'Successful suppression reduces attack power by two')
    ->expectProperty('p1Intent-0', 'TurnEffects', '["CYCLONIC_STRIKE_USED","3ir1o0qtb3_POWER"]', 'Ability is marked used for this intent object')
    ->expectDqEmpty()->tested('Cyclonic Strike')->covers(['activated-ability','suppress','stat-change'], 'Activate from intent once to suppress and lose two power.')->register();

gaScenario('greater-boon-connection-linked-power-and-sacrifice-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Greater Boon of Connection')
    ->seed('myField', 'Dungeon Guide', properties: ['Subcards'=>['c8ljyevpmu']])
    ->seed('myField', 'Alliance Gearshield', properties: ['Counters'=>['linkedToAlly'=>'em6eEh9q8y']])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myField-3')
    ->expectZoneCount('myMemory', 3, 1, 'Two reserve payments and one memory draw')
    ->expectCardExists('myBanish', 'Alliance Gearshield', 'Linked regalia was sacrificed as a cost')
    ->expectPower('p1Field-2', 1, 'Sacrificing the link removes its power bonus')
    ->expectDqEmpty()->tested('Greater Boon of Connection')->covers(['continuous-power','sacrifice','draw-into-memory'], 'Linked ally gets power, sacrifice ally-link object and pay two to draw memory.')->register();

gaScenario('greater-boon-flock-summons-without-triggering-itself')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Greater Boon of Flock')
    ->seed('myField', 'Fledgling')->observe()
    ->expectZoneCount('myField', 4, 1, 'Exactly one extra Fledgling is summoned')
    ->expectProperty('p1Field-1', 'Counters', '{"cascade":1}', 'Own summoned Bird does not advance cascade again')
    ->expectDqEmpty()->tested('Greater Boon of Flock')->covers(['cascade','summon','trigger-filter'], 'Cascade one summons one Fledgling without recursion.')->register();

gaScenario('greater-boon-flock-fifth-cascade-buffs-bird')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Greater Boon of Flock', properties: ['Counters'=>['cascade'=>4]])
    ->seed('myField', 'Fledgling')->target('myField-2')
    ->expectProperty('p1Field-1', 'Counters', '{"cascade":5}', 'Fifth cascade uses the buff mode')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'Chosen Bird receives one buff counter')
    ->expectDqEmpty()->tested('Greater Boon of Flock')->covers(['cascade','counter'], 'Cascade five buffs a Bird.')->register();

gaScenario('greater-boon-flock-tenth-cascade-grants-power-and-vigor')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Greater Boon of Flock', properties: ['Counters'=>['cascade'=>9]])
    ->seed('myField', 'Fledgling')->observe()
    ->expectProperty('p1Field-1', 'Counters', '{"cascade":10,"flockAscended":1}', 'Tenth cascade installs permanent bonus')
    ->expectPower('p1Field-2', 1, 'Bird gains one power')
    ->expectDqEmpty()->tested('Greater Boon of Flock')->covers(['cascade','continuous-power'], 'Cascade ten grants Bird power and vigor.')->register();

gaScenario('birefringence-hit-declares-additional-copy-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Birefringence', classBonus: true, player: 2)
    ->seed('myIntent', 'Clean Cut', player: 2)->seed('myHand', 'Birefringence', player: 2)
    ->endTurn()->pass(2)->pass(2)->castSpell('Birefringence', pay: 2, player: 2)->target('myIntent-0', player: 2)
    ->attack('myField-0', 'theirField-0', player: 2)->answer(1, '-')
    ->answer(2, 'YES')->answer(2, 'theirField-0')->answer(1, '-')
    ->expectDamage('p1Field-0', 4, 'Original attack and additional copy each deal two')
    ->expectZoneCount('myGraveyard', 2, 2, 'Only original attack and Birefringence enter graveyard; copy ceases')
    ->expectDqEmpty()->tested('Birefringence')->covers(['granted-on-hit','additional-attack','copy'], 'Grant On Hit and attack again with a copy.')->register();

gaScenario('lakereaving-chill-links-an-opposing-weapon')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Lakereaving Chill')
    ->seed('myField', 'Training Sword', player: 2)->seed('myHand', 'Lakereaving Chill')
    ->play('Lakereaving Chill')->target('theirField-1')->pay(3)
    ->expectCardExists('myField', 'Lakereaving Chill', 'Chill resolves linked to opposing weapon')
    ->expectProperty('p2Field-1', 'Subcards', '["7wX0tZmhYb"]', 'Weapon records its linked Chill')
    ->expectDqEmpty()->tested('Lakereaving Chill')->covers(['link','opposing-target'], 'Link to an opposing weapon.')->register();

gaScenario('nox-curse-discount-and-enter-load')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Diana, Cursebreaker', lineage: ['Creeping Torment','Creeping Torment'])
    ->seed('myField', 'Framework Sidearm')->seed('myHand', 'Nox, Final Release')
    ->castSpell('Nox, Final Release', pay: 6)->target('myField-1')
    ->expectZoneCount('myMemory', 6, 1, 'Two lineage Curses reduce ten reserve to six')
    ->expectProperty('p1Field-1', 'Subcards', '["Gf33wXPzcg"]', 'Nox loads into the chosen Gun')
    ->expectZoneCount('myField', 2, 1, 'Loaded Nox is no longer a separate field object')
    ->expectDqEmpty()->tested('Nox, Final Release')->covers(['cost-reduction','load','on-enter'], 'Curse discount and mandatory Gun loading.')->register();

gaScenario('nox-diana-hit-discards-four-and-destroys-low-influence-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')->champion(lineage: ['Diana, Cursebreaker'], player: 2)
    ->seed('myField', 'Framework Sidearm', player: 2, properties: ['Subcards'=>['Gf33wXPzcg']])
    ->endTurn()->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-0')
    ->answer(1, 'p1Hand-0&p1Hand-1&p1Hand-2&p1Hand-3')
    ->expectZoneCount('myHand', 3, 1, 'Hit player discards four of seven hand cards')
    ->expectZoneCount('myField', 0, 1, 'Champion is destroyed after influence falls to three')
    ->tested('Nox, Final Release')->covers(['on-hit','discard','destroy'], 'Diana hit discards four, then destroys champion at influence four or less.')->register();

gaScenario('tricastles-opponent-can-banish-to-disable-domain')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Tricastles of Lucenia', player: 2)
    ->action(1, 10001, 'theirField-1!CustomInput!Activate:0')->target('myHand-0')
    ->expectZoneCount('myBanish', 1, 1, 'Activating opponent pays from their own hand')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","TRICASTLES_USED","NO_ABILITIES"]', 'Domain loses abilities for the turn')
    ->expectDqEmpty()->tested('Tricastles of Lucenia')->covers(['public-ability','banish','ability-suppression'], 'Opponent can pay a norm card to disable the domain.')->register();

gaScenario('lesser-boon-elysian-blood-grants-rest-empower')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Lesser Boon of Elysian Blood')
    ->seed('myField', 'Elysian Orphan', properties: ['Status'=>2])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')
    ->expectProperty('p1Field-2', 'Status', '1', 'Granted ability rests its Elysian source')
    ->expectProperty('p1Field-0', 'TurnEffects', '["Ss991TWKR9","EMPOWERED","EMPOWER_PLUS_1"]', 'Granted database ability empowers once')
    ->expectDqEmpty()->tested('Lesser Boon of Elysian Blood')->covers(['granted-ability','empower'], 'Elysian objects gain REST: Empower 1.')->register();

gaScenario('lesser-boon-elysian-blood-recovers-to-deck-top')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Lesser Boon of Elysian Blood')
    ->seed('myGraveyard', 'Elysian Orphan')->seed('myGraveyard', 'Elysian Orphan')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->target('myGraveyard-0')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->target('myGraveyard-0')
    ->expectProperty('p1Deck-0', 'CardID', 'RN7ueRDijA', 'Second recovered Elysian is on top')
    ->expectZoneCount('myMemory', 5, 1, 'Consecutive uses cost two then three')
    ->expectZoneCount('myGraveyard', 0, 1, 'Both Elysian cards left the graveyard')
    ->expectDqEmpty()->tested('Lesser Boon of Elysian Blood')->covers(['recover','increasing-cost'], 'Recover Elysian; reserve cost increases each use.')->register();
