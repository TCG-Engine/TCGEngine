<?php
// DTR Alter cases use database-generated abilities and the normal action runner.
gaScenario('dtr-annealing-flame-removes-one-durability')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Annealing Flame')
    ->seed('myField','Training Sword',player:2)->seed('myHand','Annealing Flame')
    ->castSpell('Annealing Flame',pay:2,target:'theirField-1')
    ->expectProperty('p2Field-1','Counters','{"durability":1}')
    ->expectDqEmpty()->tested('Annealing Flame')->covers(['counter','optional'],'Remove one durability from up to one weapon.')->register();
gaScenario('dtr-auric-persecution-destroys-and-compensates-opponent')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Auric Persecution')
    ->seed('myField','Giant Tortoise',player:2)->seed('myHand','Auric Persecution')
    ->castSpell('Auric Persecution',pay:2,target:'theirField-1')
    ->expectCardExists('theirGraveyard','Giant Tortoise')->expectZoneCount('theirMemory',1)
    ->expectDqEmpty()->tested('Auric Persecution')->covers(['destroy','draw'],'Destroy a non-champion; its controller draws memory.')->register();
gaScenario('dtr-clover-treehouse-enter-draws-memory')
    ->deckTemplate('quiet','Dungeon Guide')->seed('myField','Clover Treehouse')->observe()
    ->expectZoneCount('myMemory',1)->expectDqEmpty()->tested('Clover Treehouse')->covers(['enter','draw'],'On Enter draw memory.')->register();
gaScenario('dtr-hunters-mark-marks-unit')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor("Hunter's Mark")->seed('myHand',"Hunter's Mark")
    ->castSpell("Hunter's Mark",pay:1,target:'theirField-0')
    ->expectProperty('p2Field-0','Counters','{"dtrMarked":1}')
    ->expectDqEmpty()->tested("Hunter's Mark")->covers(['enter','marked'],'On Enter target unit becomes marked.')->register();
gaScenario('dtr-astute-tracker-class-bonus-marks-unit')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Astute Tracker',classBonus:true)
    ->seed('myField','Astute Tracker')->answer(1,'theirField-0')
    ->expectProperty('p2Field-0','Counters','{"dtrMarked":1}')
    ->expectDqEmpty()->tested('Astute Tracker')->covers(['enter','marked','class-bonus'],'Class bonus On Enter marks a unit as a Spell.')->register();
gaScenario('dtr-uplifting-rogation-draws-memory')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Uplifting Rogation',classBonus:true)
    ->seed('myHand','Uplifting Rogation')->castSpell('Uplifting Rogation',pay:2)
    ->expectZoneCount('myMemory',3)->expectDqEmpty()->tested('Uplifting Rogation')
    ->covers(['empower','draw'],'Empower and class-bonus draw memory.')->register();
gaScenario('dtr-silent-trade-recovers-without-apple')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Silent Trade')->champion(damage:5)
    ->seed('myHand','Silent Trade')->castSpell('Silent Trade',pay:2)
    ->expectDamage('p1Field-0',3)->expectDqEmpty()->tested('Silent Trade')->covers(['recover'],'Recover two when no Apple materializes.')->register();
gaScenario('dtr-silence-the-soul-removes-omen')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Silence the Soul')
    ->seed('myBanish','Dungeon Guide',player:2,properties:['Counters'=>['omen'=>3]])
    ->seed('myHand','Silence the Soul')->castSpell('Silence the Soul',pay:2,target:'theirBanish-0')
    ->expectProperty('p2Banish-0','Counters','[]')->expectGlobalEffect(1,'DTR_NO_OMEN')
    ->expectDqEmpty()->tested('Silence the Soul')->covers(['counter','restriction'],'Remove all omen counters and prevent more this turn.')->register();
gaScenario('dtr-render-turbid-opponent-selects-memory')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Render Turbid')
    ->seed('myMemory','Dungeon Guide',player:2)->seed('myHand','Render Turbid')
    ->castSpell('Render Turbid',pay:2)->answer(2,'myMemory-0')
    ->expectCardExists('theirBanish','Dungeon Guide')->expectDqEmpty()->tested('Render Turbid')
    ->covers(['opponent-choice','banish'],'Opponent chooses the memory card to banish.')->register();
gaScenario('dtr-macabre-subornation-declined-discard')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Macabre Subornation')
    ->seed('myHand','Macabre Subornation')->castSpell('Macabre Subornation',pay:3)->answer(2,'-')
    ->expectDamage('p2Field-0',8)->expectZoneCount('myMemory',4)
    ->expectDqEmpty()->tested('Macabre Subornation')->covers(['optional','damage','draw'],'Declining discard deals eight and draws caster memory.')->register();
gaScenario('dtr-radiating-assault-unprepared-damage')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Radiating Assault')
    ->seed('myHand','Radiating Assault')->castSpell('Radiating Assault',pay:2,target:'theirField-0')
    ->expectDamage('p2Field-0',2)->expectDqEmpty()->tested('Radiating Assault')->covers(['damage'],'Without preparing deal two to champion.')->register();
gaScenario('dtr-pallid-arsonist-damages-siegeable-domain')
    ->deckTemplate('quiet','Dungeon Guide')->seed('myField','Club House',player:2)
    ->seed('myField','Pallid Arsonist')->observe()
    ->expectProperty('p2Field-1','Counters','{"durability":4}')
    ->expectDqEmpty()->tested('Pallid Arsonist')->covers(['enter','damage','siegeable'],'Deal two to each Siegeable domain.')->register();
gaScenario('dtr-club-house-buffs-and-loses-durability')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Club House',classBonus:true)
    ->seed('myField','Club House')->seed('myField','Two of Clubs')
    ->action(1,10001,'myField-1!CustomInput!Activate:0')->target('myField-2')
    ->expectPower('p1Field-2',2)->expectProperty('p1Field-1','Counters','{"durability":4}')
    ->expectProperty('p1Field-1','Status','1')->tested('Club House')->covers(['activated','rest','counter'],'REST buffs a Suited ally and removes two durability.')->register();
gaScenario('dtr-trefoil-tower-makes-ally-distant')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Trefoil Tower',classBonus:true)
    ->seed('myField','Trefoil Tower')->seed('myField','Two of Clubs')
    ->action(1,10001,'myField-1!CustomInput!Activate:0')->target('myField-2')
    ->expectProperty('p1Field-2','TurnEffects','["ENTERED_THIS_TURN","DISTANT"]')->expectProperty('p1Field-1','Counters','{"durability":4}')
    ->expectProperty('p1Field-1','Status','1')->tested('Trefoil Tower')->covers(['activated','rest','distant'],'REST makes Suited ally distant and removes two durability.')->register();
gaScenario('dtr-five-clubs-cardistry-once')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Five of Clubs')
    ->seed('myField','Five of Clubs')->action(1,10001,'myField-1!CustomInput!Activate:0')->pay(4)
    ->expectPower('p1Field-1',3)->expectLife('p1Field-1',7)
    ->expectProperty('p1Field-1','Status','2')->expectZoneCount('myMemory',4)
    ->tested('Five of Clubs')->covers(['cardistry','buff','cost'],'Cardistry costs four with its own distinct cost, puts two buffs, and does not rest.')->register();
gaScenario('dtr-four-clubs-cardistry-makes-all-units-distant')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Four of Clubs')
    ->seed('myField','Four of Clubs')->seed('myField','Dungeon Guide')
    ->action(1,10001,'myField-1!CustomInput!Activate:0')->pay(3)
    ->expectProperty('p1Field-0','TurnEffects','["DISTANT"]')->expectProperty('p1Field-1','TurnEffects','["ENTERED_THIS_TURN","DISTANT"]')->expectProperty('p1Field-2','TurnEffects','["ENTERED_THIS_TURN","DISTANT"]')
    ->tested('Four of Clubs')->covers(['cardistry','distant'],'Every controlled unit becomes distant.')->register();
gaScenario('dtr-six-clubs-cardistry-scavenges-suited')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Six of Clubs')
    ->seed('myField','Six of Clubs')->deckTop(['Dungeon Guide','Two of Clubs','Dungeon Guide'])
    ->action(1,10001,'myField-1!CustomInput!Activate:0')->pay(5)
    ->expectCardExists('myHand','Two of Clubs')->tested('Six of Clubs')->covers(['cardistry','scavenge'],'Scavenge six for a Suited card.')->register();
gaScenario('dtr-two-clubs-discounts-next-domain')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Two of Clubs')
    ->seed('myField','Two of Clubs')->seed('myHand','Club House')
    ->action(1,10001,'myField-1!CustomInput!Activate:0')->pay(1)
    ->castSpell('Club House',pay:2)->expectCardExists('myField','Club House')->expectZoneCount('myMemory',3)
    ->tested('Two of Clubs')->covers(['cardistry','cost-reduction'],'Next domain costs two less.')->register();
gaScenario('dtr-three-clubs-cardistry-next-attack')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Three of Clubs')
    ->seed('myField','Three of Clubs')->action(1,10001,'myField-1!CustomInput!Activate:0')->pay(2)
    ->expectProperty('p1Field-1','TurnEffects','["ENTERED_THIS_TURN","DTR_NEXT_ATTACK_POWER_2"]')
    ->tested('Three of Clubs')->covers(['cardistry','attack-buff'],'Next attack gets two power without buff or distant.')->register();
gaScenario('dtr-misty-pays-x-and-sets-reserve-cost')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Misty, the Joker')->seed('myHand','Misty, the Joker')
    ->play('Misty, the Joker')->answer(1,'3')->pay(3)
    ->expectCardExists('myField','Misty, the Joker')->expectZoneCount('myMemory',3)
    ->expectProperty('p1Field-1','Counters','{"dtrX":3,"dtrReserveCost":3}')
    ->tested('Misty, the Joker')->covers(['variable-cost','enter'],'Chosen X is paid and becomes base reserve cost.')->register();
gaScenario('dtr-coruscation-pays-x-and-places-sheen')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Coruscation')->seed('myHand','Coruscation')
    ->play('Coruscation')->answer(1,'3')->pay(3)->target('theirField-0')
    ->expectProperty('p2Field-0','Counters','{"sheen":3}')->expectZoneCount('myMemory',3)
    ->tested('Coruscation')->covers(['variable-cost','enter','counter'],'Chosen X is paid and puts X sheen on target unit.')->register();
gaScenario('dtr-trefoliate-anointment-adds-durability')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Trefoliate Anointment')
    ->seed('myField','Club House')->seed('myHand','Trefoliate Anointment')
    ->castSpell('Trefoliate Anointment',pay:2)->answer(1,'1')->target('myField-1')
    ->expectProperty('p1Field-1','Counters','{"durability":9}')
    ->tested('Trefoliate Anointment')->covers(['modal','counter'],'Domain mode adds three durability.')->register();
gaScenario('dtr-escort-pawns-grants-power')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Escort the Pawns')
    ->seed('myField','Weiss Pawn')->seed('myHand','Escort the Pawns')
    ->castSpell('Escort the Pawns',pay:2)->answer(1,'myField-1')
    ->expectPower('p1Field-1',1)->tested('Escort the Pawns')->covers(['multi-target','power'],'Selected Pawn gets one power.')->register();
gaScenario('dtr-scalding-aethercharge-first-hit')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Scalding Aethercharge')
    ->seed('myHand','Scalding Aethercharge')->castSpell('Scalding Aethercharge',pay:2,target:'theirField-0')
    ->expectDamage('p2Field-0',2)->tested('Scalding Aethercharge')->covers(['damage'],'Champion that has not leveled takes one instance of two.')->register();
gaScenario('dtr-trefle-alternative-domain-payment')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Trefle, Queen of Clubs')
    ->seed('myGraveyard','Club House')->seed('myGraveyard','Shamrock Forest')
    ->deckTop(['Two of Clubs','Dungeon Guide'])->seed('myHand','Trefle, Queen of Clubs')
    ->play('Trefle, Queen of Clubs')->answer(1,'myGraveyard-0&myGraveyard-1')
    ->expectCardExists('myField','Trefle, Queen of Clubs')->expectZoneCount('myBanish',2)->expectZoneCount('myMemory',0)
    ->expectCardExists('myHand','Two of Clubs')->tested('Trefle, Queen of Clubs')->covers(['alternative-cost','enter','scavenge'],'Banish domains totaling ten, then scavenge for an ally.')->register();
gaScenario('dtr-valet-alternative-hand-payment')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Valet, Jack of Hearts')
    ->seed('myHand','Valet, Jack of Hearts')->seed('myHand','Three of Hearts')
    ->play('Valet, Jack of Hearts')->answer(1,'myHand-7')->pay(2)
    ->expectCardExists('myField','Valet, Jack of Hearts')->expectCardExists('myBanish','Three of Hearts')->expectZoneCount('myMemory',2)
    ->tested('Valet, Jack of Hearts')->covers(['alternative-cost'],'Pay two and banish a fire Suited card from hand.')->register();
gaScenario('dtr-abiding-figures-damage-and-glimpse')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Abiding Figures')->seed('myHand','Abiding Figures')
    ->castSpell('Abiding Figures',pay:2)->expectDamage('p1Field-0',2)
    ->tested('Abiding Figures')->covers(['damage','glimpse'],'Self damage followed by glimpse six.')->register();
gaScenario('dtr-briny-polliwog-fostered-class-bonus')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Briny Polliwog',classBonus:true)
    ->seed('myField','Briny Polliwog',properties:['TurnEffects'=>['FOSTERED']])->observe()
    ->expectPower('p1Field-1',3)->tested('Briny Polliwog')->covers(['foster','class-bonus'],'Fostered class bonus adds three power.')->register();
gaScenario('dtr-ciel-projection-printed-stats')
    ->deckTemplate('quiet','Dungeon Guide')->seed('myField','Ciel, Astral Projection')->observe()
    ->expectPower('p1Field-1',1)->expectLife('p1Field-1',4)->tested('Ciel, Astral Projection')->covers(['stats'],'Projection retains printed stats without distorted time.')->register();
gaScenario('dtr-countercurrent-ephemeral-stat-swap')
    ->deckTemplate('quiet','Dungeon Guide')->seed('myField','Countercurrent Raider',properties:['TurnEffects'=>['EPHEMERAL']])->observe()
    ->expectPower('p1Field-1',4)->expectLife('p1Field-1',1)->tested('Countercurrent Raider')->covers(['ephemeral','stats'],'Ephemeral swaps power and life.')->register();
gaScenario('dtr-diligent-garde-counts-cheap-omens')
    ->deckTemplate('quiet','Dungeon Guide')->champion(cardName:"Ciel, Mirage's Grave")
    ->seed('myBanish','Two of Clubs',properties:['Counters'=>['omen'=>1]])
    ->seed('myBanish',"Hunter's Mark",properties:['Counters'=>['omen'=>1]])
    ->seed('myField','Diligent Garde')->observe()->expectPower('p1Field-1',2)
    ->tested('Diligent Garde')->covers(['named-bonus','omens'],'Ciel bonus counts only omens costing one or less.')->register();
gaScenario('dtr-ghastfire-sacrifice-grants-ephemerate')
    ->deckTemplate('quiet','Dungeon Guide')->seed('myField','Ghastfire Tormentor')->seed('myGraveyard','Shatter the Brittle')
    ->action(1,10001,'myField-1!CustomInput!Activate:0')->target('myGraveyard-0')
    ->expectCardExists('myGraveyard','Ghastfire Tormentor')->expectZoneCount('myField',1)
    ->tested('Ghastfire Tormentor')->covers(['sacrifice','ephemerate'],'Sacrifice resolves a temporary graveyard grant.')->register();
gaScenario('dtr-poitier-ephemeral-haunt-bonus')
    ->deckTemplate('quiet','Dungeon Guide')->seed('myMastery','Phantasmagoria',properties:['Counters'=>['haunt'=>3]])
    ->seed('myField',"Poitier, Afterlife's Conductor",properties:['TurnEffects'=>['EPHEMERAL']])->observe()
    ->expectPower('p1Field-1',5)->expectLife('p1Field-1',6)->tested("Poitier, Afterlife's Conductor")
    ->covers(['ephemeral','haunt'],'Three haunt counters grant three power and life.')->register();
gaScenario('dtr-scholarly-nettle-buffs-other-floating-ally')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Scholarly Nettle',classBonus:true)
    ->seed('myField','Scholarly Nettle')->seed('myField','Scholarly Nettle')->observe()
    ->expectPower('p1Field-1',2)->expectLife('p1Field-2',3)->tested('Scholarly Nettle')
    ->covers(['static','floating-memory'],'Each Nettle buffs the other.')->register();
gaScenario('dtr-shamrock-end-phase-scavenge')
    ->deckTemplate('quiet','Dungeon Guide')->seed('myField','Shamrock Forest')->deckTop(['Two of Clubs','Dungeon Guide'])
    ->endTurn()->expectCardExists('myHand','Two of Clubs')->expectProperty('p1Field-1','Counters','{"durability":4}')
    ->tested('Shamrock Forest')->covers(['end-phase','scavenge'],'End phase scavenges Suited ally and removes two durability.')->register();
gaScenario('dtr-time-dilation-sacrifice-and-banish')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Time Dilation')
    ->seed('myField','Unwound Timepiece',properties:['Counters'=>['time'=>1]])->seed('myHand','Time Dilation')
    ->play('Time Dilation')->target('myField-1')->pay(5)
    ->expectCardExists('myGraveyard','Unwound Timepiece')->expectCardExists('myBanish','Time Dilation')
    ->tested('Time Dilation')->covers(['additional-cost','extra-turn','banish'],'Sacrifice a time-counter object and banish resolved spell.')->register();
gaScenario('dtr-unwound-timepiece-first-cascade')
    ->deckTemplate('quiet','Dungeon Guide')->seed('myField','Unwound Timepiece')
    ->action(1,10001,'myField-1!CustomInput!Activate:0')->pay(2)
    ->expectProperty('p1Field-1','Counters','{"cascade":1}')->expectProperty('p1Field-1','Status','1')
    ->tested('Unwound Timepiece')->covers(['cascade','cost','rest'],'First cascade costs two and rests.')->register();
gaScenario('dtr-verte-six-cost-ranged')
    ->deckTemplate('quiet','Dungeon Guide')->seed('myField','Five of Clubs')->seed('myField','Verte, Ace of Clubs')->observe()
    ->expectProperty('p1Field-2','TurnEffects','["ENTERED_THIS_TURN","RANGED_3"]')
    ->tested('Verte, Ace of Clubs')->covers(['enter','ranged'],'Total Suited cost six grants ranged three.')->register();
gaScenario('dtr-weiss-death-buffs-other-chessman')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Auric Persecution')
    ->seed('myField','Weiss Pawn')->seed('myField','Weiss Pawn')->seed('myHand','Auric Persecution')
    ->castSpell('Auric Persecution',pay:2,target:'myField-1')->expectLife('p1Field-1',4)
    ->tested('Weiss Pawn')->covers(['death','life'],'Dying Pawn grants other Chessman two life.')->register();
gaScenario('dtr-yonder-distant-scavenges-aethercharge')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Four of Clubs')
    ->seed('myField','Four of Clubs')->seed('myField','Yonder Aetherseeker')->deckTop(['Scalding Aethercharge','Dungeon Guide'])
    ->action(1,10001,'myField-1!CustomInput!Activate:0')->pay(3)
    ->expectCardExists('myHand','Scalding Aethercharge')->tested('Yonder Aetherseeker')
    ->covers(['distant','scavenge'],'Becoming distant scavenges an Aethercharge.')->register();
gaScenario('dtr-ghastfire-granted-spell-pays-reserve-and-resolves')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Scalding Aethercharge')
    ->seed('myField','Ghastfire Tormentor')->seed('myGraveyard','Scalding Aethercharge')
    ->action(1,10001,'myField-1!CustomInput!Activate:0')->target('myGraveyard-0')
    ->passBoth()->action(1,10002,'myGraveyard-0!FSM!')->pay(2)->target('theirField-0')
    ->expectDamage('p2Field-0',2)->expectZoneCount('myMemory',2)
    ->expectCardExists('myBanish','Scalding Aethercharge')->expectDqEmpty()
    ->tested('Ghastfire Tormentor')->covers(['ephemerate','cost','damage'],'Granted ephemerate pays two after leaving graveyard and resolves its spell.')->register();
gaScenario('dtr-coruscation-hand-ability-discards-pays-and-adds-sheen')
    ->deckTemplate('quiet','Dungeon Guide')->champion(cardName:'Merlin, Kingslayer')
    ->seed('myMastery','Fractured Memories')->seed('myHand','Coruscation')
    ->action(1,10001,'myHand-7!CustomInput!Activate:0')->pay(2)
    ->expectCardExists('myGraveyard','Coruscation')->expectZoneCount('myMemory',3)
    ->expectProperty('p1Mastery-0','Counters','{"sheen":2}')
    ->tested('Coruscation')->covers(['hand-ability','named-bonus','cost','counter'],'Merlin discards Coruscation, pays two, adds sheen and draws memory.')->register();
gaScenario('dtr-radiating-prepared-uses-base-level')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Radiating Assault')
    ->patch('myField-0',['Counters'=>['preparation'=>3]])->champion(player:2,cardName:'Lorraine, Blademaster')
    ->seed('myHand','Radiating Assault')->play('Radiating Assault')->answer(1,'YES')->pay(2)->target('theirField-0')
    ->expectDamage('p2Field-0',7)->expectProperty('p1Field-0','Counters','[]')
    ->tested('Radiating Assault')->covers(['prepare','cost','damage'],'Preparing pays three counters and deals three plus twice base level.')->register();
gaScenario('dtr-misty-death-puts-cheap-suited-ally')
    ->deckTemplate('quiet','Dungeon Guide')->readyFor('Auric Persecution')
    ->seed('myField','Misty, the Joker',properties:['Counters'=>['dtrX'=>3,'dtrReserveCost'=>3]])
    ->seed('myHand','Two of Clubs')->seed('myHand','Auric Persecution')
    ->castSpell('Auric Persecution',pay:2,target:'myField-1')->target('myHand-5')
    ->expectCardExists('myField','Two of Clubs')->expectZoneCount('myMemory',4)
    ->tested('Misty, the Joker')->covers(['death','variable-cost','put-onto-field','draw'],'Death uses declared X to admit a cheaper Suited ally and draw memory.')->register();
