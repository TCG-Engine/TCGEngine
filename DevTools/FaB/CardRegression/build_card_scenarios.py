import json,pathlib,os
repo=pathlib.Path(os.environ.get('FAB_REGRESSION_REPO',next((p for p in pathlib.Path(__file__).resolve().parents if (p/'Core').is_dir()),pathlib.Path.cwd())))
dest=pathlib.Path(__file__).parent
cards={c['id']:c for c in json.loads((repo/'FaBSim/GeneratedCode/cardArrayCache.json').read_text())['cardArray']}
cases=[]
def zone(card,player=1,z='Hand',**props):return dict(cardID=card,player=player,zone=z,properties=props)
def click(mz,player=1):return dict(kind='click',mzID=mz,player=player)
def answer(value,player=1,typ=None):
 d=dict(kind='answer',value=value,player=player)
 if typ:d['type']=typ
 return d
def until(**kw):return dict(kind='pass_until',until=kw)
def checks(*args):return dict(kind='checks',checks=list(args))
def scalar(zone,value):return dict(type='scalar_zone_equals',zone=zone,value=value)
def count(zone,value):return dict(type='zone_count',zone=zone,value=value)
def exists(zone,card):return dict(type='card_exists',zone=zone,cardID=card)
def semantic(kind,value,**kw):return dict(kind=kind,value=value,**kw)
def case(id,branch,zones,steps,oracle,**kw):
 cases.append(dict(slug='card-'+id.replace('_','-')+'-'+branch,name=cards[id]['name']+' / '+branch,cardIDs=[id],branches=[branch],zones=zones,steps=steps,oracle=oracle,sources=['FaBSim/GeneratedCode/cardArrayCache.json#'+id,'https://rules.fabtcg.com/en/cr/07-combat/'],**kw))
for color,gain in [('red',3),('yellow',2),('blue',1)]:
 id='sigil_of_solace_'+color
 case(id,'gain',[zone(id)],[click('p1Hand-0'),until(stackEmpty=True,window='ACTION'),checks(scalar('p1Health',10+gain),scalar('p1ActionPoints',1),count('p1Hand',0),exists('p1Graveyard',id),count('Stack',0))],f'Printed text gains {gain} life. Injured controller starts at 10 life; instant consumes no action point.',health=[10,20])
for id,damage,go in [('wounding_blow_red',4,False),('head_jab_red',3,True)]:
 case(id,'unblocked',[zone(id)],[click('p1Hand-0'),answer('p2Hero-0'),until(window='ATTACK',stackEmpty=True),checks(semantic('power',damage),semantic('go_again',go)),until(window='RESOLUTION'),checks(scalar('p2Health',20-damage),scalar('p1ActionPoints',int(go)),exists('p1CombatChain',id))],f'First unblocked attack deals printed {damage} physical damage; go again is {go}. The first attack does not receive Ira second-attack bonus.')
for branch,choice in [('exchange','p2Hand-0'),('decline','-')]:
 id='sink_below_red'
 case(id,branch,[zone('wounding_blow_red'),zone(id,2),zone('head_jab_blue',2),zone('wounding_blow_blue',2,'Deck')],[click('p1Hand-0'),answer('p2Hero-0'),until(window='REACTION',priority=2),click('p2Hand-0',2),until(decision=dict(player=2,type='MZMAYCHOOSE')),answer(choice,2,'MZMAYCHOOSE'),checks(semantic('hand_ids',['wounding_blow_blue'] if branch=='exchange' else ['head_jab_blue'],player=2),semantic('deck_ids',['head_jab_blue'] if branch=='exchange' else ['wounding_blow_blue'],player=2),scalar('p2Health',20)),until(window='REACTION',stackEmpty=True),checks(semantic('defense',4)),until(window='RESOLUTION'),checks(scalar('p2Health',20),exists('p2CombatChain',id))],f'Sink Below defends for 4. Optional exchange {branch}: only accepting bottoms the chosen card and draws original deck top. Wounding Blow 4 is fully defended.')
for branch,pitch,go in [('zero-cost-pitch','wounding_blow_blue',True),('nonzero-cost-pitch','raging_onslaught_blue',False),('no-cost-pitch','heart_of_fyendal_blue',False)]:
 id='harmonized_kodachi'
 case(id,branch,[zone(id,1,'Weapons'),zone(pitch)],[click('p1Weapons-0'),answer('p2Hero-0'),checks(semantic('state','PITCH',key='window')),click('p1Hand-0'),until(window='ATTACK',stackEmpty=True),checks(scalar('p1Resources',2),exists('p1Pitch',pitch),semantic('power',1),semantic('go_again',go),semantic('can_activate',False,player=1,mzID='p1Weapons-0')),until(window='RESOLUTION'),checks(scalar('p2Health',19),scalar('p1ActionPoints',int(go)),exists('p1Weapons',id))],f'Pay one resource by pitching a blue card for three; keep two. Kodachi attacks for one and persists. Go again requires an actual cost of zero, not an absent cost; expected {go}. Once-per-turn activation unavailable after attack.')
# A hero activation and equipment activation exercise stack, resources and consumption.
case('bravo','paid-dominate',[zone('wounding_blow_blue'),zone('raging_onslaught_red')],[click('p1Hero-0'),click('p1Hand-0'),until(stackEmpty=True,window='ACTION'),checks(scalar('p1Resources',1),scalar('p1ActionPoints',1)),click('p1Hand-0'),answer('p2Hero-0'),click('p1Hand-0'),until(window='ATTACK',stackEmpty=True)],'Bravo activation costs 2 resources and has go again. This setup will be extended with explicit dominate and cost-threshold checks.',heroes=['bravo','ira_crimson_haze'])
# Keep hero prototype separate until its additional attack payment is fully specified.
cases=[c for c in cases if c['cardIDs']!=['bravo']]
case('goliath_gauntlet','destroy-buff',[zone('goliath_gauntlet',1,'Equipment'),zone('raging_onslaught_red'),zone('wounding_blow_blue')],[click('p1Equipment-0'),until(stackEmpty=True,window='ACTION'),checks(count('p1Equipment',0),exists('p1Graveyard','goliath_gauntlet'),scalar('p1ActionPoints',1)),click('p1Hand-0'),answer('p2Hero-0'),click('p1Hand-0'),until(window='ATTACK',stackEmpty=True),checks(semantic('power',9)),until(window='RESOLUTION'),checks(scalar('p2Health',11),scalar('p1ActionPoints',0))],'Destroy equipment as cost; activation has go again. Next cost-3 Raging Onslaught gets +2 power (7 to 9); blue pitch pays cost 3.')
for c in cases:
 c['steps']=[s for s in c['steps'] if not(s['kind']=='answer' and s['value']=='p2Hero-0')]
 if c['cardIDs']==['goliath_gauntlet']:
  c['steps'].insert(1,checks(count('p1Equipment',0),exists('p1Graveyard','goliath_gauntlet'),count('Stack',1)))
 if c['slug']=='card-harmonized-kodachi-zero-cost-pitch':
  c['steps'] += [until(window='ACTION',stackEmpty=True),checks(scalar('p1ActionPoints',1),scalar('p1Resources',2),semantic('can_activate',False,player=1,mzID='p1Weapons-0')),click('p1Weapons-0'),checks(count('Stack',0),scalar('p1ActionPoints',1),scalar('p1Resources',2))]
 elif c['cardIDs']==['harmonized_kodachi']:
  c['oracle']=c['oracle'].replace('Once-per-turn activation unavailable after attack.','Once-per-turn limit not independently covered in this branch.')
(dest/'card-regression-scenarios.json').write_text(json.dumps(cases,indent=2)+'\n')
print(len(cases),'pilot scenarios')
implemented=set()
for p in (repo/'DevTools/FaB').glob('*abilities.json'):
 implemented.update(c['cardId'] for c in json.loads(p.read_text()))
cases=[]
for id,c in cards.items():
 if id not in implemented or 'Action' not in c['types'] or 'Attack' not in c['types'] or c['functional_text_plain'].strip() not in ('','Go again'):continue
 power=int(c['power']);cost=int(c['cost']);go=c['functional_text_plain'].strip()=='Go again'
 typ=c['types'];hero='ira_crimson_haze'
 if 'Draconic' in typ:hero='fai'
 elif 'Lightning' in typ:hero='lexi'
 elif 'Earth' in typ or 'Ice' in typ:hero='oldhim'
 elif 'Pirate' in typ:hero='marlynn'
 elif 'Ranger' in typ:hero='azalea'
 elif 'Brute' in typ:hero='rhinar'
 source='Arsenal' if 'Arrow' in typ else 'Hand'
 n=(cost+2)//3
 zones=[zone(id,1,source)]+[zone('wounding_blow_blue') for _ in range(n)]
 if 'Arrow' in typ:zones.append(zone('death_dealer',1,'Weapons'))
 steps=[click('p1'+source+'-0')]
 for _ in range(n):steps.append(click('p1Hand-0'))
 steps += [until(window='ATTACK',stackEmpty=True),checks(semantic('power',power),semantic('go_again',go),scalar('p1Resources',3*n-cost),count('p1Pitch',n),scalar('p1ActionPoints',0))]
 for branch,block in [('unblocked',0),('blocked',3)]:
  z=zones.copy();s=steps.copy()
  if block:
   z.append(zone('wounding_blow_blue',2))
   s += [until(window='DEFEND_DECLARE'),click('p2Hand-0',2),checks(semantic('defense',3),exists('p2CombatChain','wounding_blow_blue'))]
  s += [until(window='RESOLUTION'),checks(scalar('p2Health',20-max(0,power-block)),scalar('p1ActionPoints',int(go)),exists('p1CombatChain',id))]
  case(id,branch,z,s,f'Printed attack {power}, cost {cost}, go again {go}; pay using {n} blue pitch card(s). Defender has {block} defense; expected damage {max(0,power-block)}. Hero {hero} has no modifying effect in this first-attack state.',heroes=[hero,'ira_crimson_haze'])
 if cost>0:
  failzones=[zone(id,1,source)]
  if 'Arrow' in typ:failzones.append(zone('death_dealer',1,'Weapons'))
  case(id,'insufficient-resources',failzones,[checks(semantic('can_play',False,player=1,mzID='p1'+source+'-0')),click('p1'+source+'-0'),checks(exists('p1'+source,id),count('Stack',0),scalar('p1ActionPoints',1),scalar('p1Resources',0))],f'Printed cost {cost} cannot be paid with zero floating resources and no pitchable hand card; clicking must leave card, stack, action points and resources unchanged.',heroes=[hero,'ira_crimson_haze'])
(dest/'card-regression-batch01.json').write_text(json.dumps(cases,indent=2)+'\n')
print(len(cases),'batch 01 scenarios')
cases=[]
for mode,label in [(0,'draw'),(1,'power'),(2,'go-again')]:
 id='enlightened_strike_red';power=7 if mode==1 else 5;go=mode==2
 case(id,'mode-'+label,[zone(id),zone('wounding_blow_blue'),zone('head_jab_blue',1,'Deck')],[click('p1Hand-0'),answer('p1Hand-0',typ='MZCHOOSE'),answer(str(mode),typ='MZMODAL'),checks(semantic('deck_ids',['head_jab_blue','wounding_blow_blue'],player=1),count('p1Hand',0)),until(window='ATTACK',stackEmpty=True),checks(semantic('power',power),semantic('go_again',go),semantic('hand_ids',['head_jab_blue'] if mode==0 else [],player=1),semantic('deck_ids',['wounding_blow_blue'] if mode==0 else ['head_jab_blue','wounding_blow_blue'],player=1)),until(window='RESOLUTION'),checks(scalar('p2Health',20-power),scalar('p1ActionPoints',int(go)))],f'Pay mandatory additional cost by bottoming Wounding Blow blue, then choose mode {mode} ({label}). Attack for {power}; only draw mode draws original top Head Jab blue, only go-again mode returns an action point.')
case('enlightened_strike_red','missing-additional-cost',[zone('enlightened_strike_red')],[checks(semantic('can_play',False,player=1,mzID='p1Hand-0')),click('p1Hand-0'),checks(exists('p1Hand','enlightened_strike_red'),count('Stack',0),scalar('p1ActionPoints',1))],'The mandatory extra hand card is absent; cannot announce Enlightened Strike even though printed resource cost is zero.')
for branch,attack,power,nblocks in [('cost-three-dominate','crush_confidence_blue',5,1),('cost-zero-no-dominate','wounding_blow_red',4,2)]:
 zones=[zone('wounding_blow_blue'),zone(attack),zone('wounding_blow_blue'),zone('wounding_blow_blue',2),zone('wounding_blow_blue',2)]
 steps=[click('p1Hero-0'),click('p1Hand-0'),until(stackEmpty=True,window='ACTION'),checks(scalar('p1Resources',1),scalar('p1ActionPoints',1)),click('p1Hand-0')]
 if branch=='cost-three-dominate':steps +=[click('p1Hand-0')]
 steps += [until(window='DEFEND_DECLARE'),click('p2Hand-0',2),click('p2Hand-0',2),checks(count('p2CombatChain',nblocks),count('p2Hand',2-nblocks),semantic('defense',3*nblocks)),until(window='RESOLUTION'),checks(scalar('p2Health',20-max(0,power-3*nblocks)))]
 case('bravo',branch,zones,steps,f'Pay 2 to activate Bravo (blue3 leaves1) and recover action point. {attack} costs '+('3 and must get dominate: second hand block rejected; one block3 permits2 damage.' if branch=='cost-three-dominate' else '0 and must not get dominate: both hand blocks legal and prevent all4 damage.'),heroes=['bravo','ira_crimson_haze'])
(dest/'card-regression-batch02.json').write_text(json.dumps(cases,indent=2)+'\n')
print(len(cases),'batch 02 family pilots')
cases=[]
for color,gain in [('red',3),('yellow',2),('blue',1)]:
 id='sigil_of_solace_'+color
 case(id,'opponent-turn-instant',[zone('wounding_blow_red'),zone(id,2)],[click('p1Hand-0'),until(window='ATTACK',priority=2),click('p2Hand-0',2),until(stackEmpty=True,window='ATTACK'),checks(scalar('p2Health',10+gain),scalar('p2ActionPoints',0),exists('p2Graveyard',id)),until(window='RESOLUTION'),checks(scalar('p2Health',6+gain))],f'Non-turn player plays the instant before blocks. Gain exactly {gain} with zero action points, then take 4 from Wounding Blow.',health=[20,10])
for color,defense in [('red',4),('yellow',3),('blue',2)]:
 id='sink_below_'+color
 for source in ['Hand','Arsenal']:
  if color=='red' and source=='Hand':continue # already covered by the original pilot
  for exchange in [True,False]:
   z=[zone('wounding_blow_red'),zone(id,2,source,**({'FaceDown':1} if source=='Arsenal' else {})),zone('head_jab_blue',2),zone('wounding_blow_blue',2,'Deck')]
   s=[click('p1Hand-0'),until(window='REACTION',priority=2),click('p2'+source+'-0',2),until(decision=dict(player=2,type='MZMAYCHOOSE')),answer('p2Hand-0' if exchange else '-',2,'MZMAYCHOOSE'),checks(semantic('hand_ids',['wounding_blow_blue'] if exchange else ['head_jab_blue'],player=2),semantic('deck_ids',['head_jab_blue'] if exchange else ['wounding_blow_blue'],player=2)),until(window='RESOLUTION'),checks(scalar('p2Health',20-(4-defense)),exists('p2CombatChain',id))]
   case(id,source.lower()+'-'+('exchange' if exchange else 'decline'),z,s,f'Play defense reaction from {source}. Printed defense {defense} prevents {defense} of incoming4. Optional exchange is {exchange}; only acceptance bottoms chosen card and draws original deck top.')
 case(id,'empty-hand',[zone('wounding_blow_red'),zone(id,2),zone('wounding_blow_blue',2,'Deck')],[click('p1Hand-0'),until(window='REACTION',priority=2),click('p2Hand-0',2),until(stackEmpty=True,window='REACTION'),checks(count('p2Hand',0),exists('p2Deck','wounding_blow_blue'),count('p2Deck',1)),until(window='RESOLUTION'),checks(scalar('p2Health',20-(4-defense)))],f'With no card left in hand after playing Sink, no exchange is possible and no card is drawn. Printed defense{defense} still applies.')
 case(id,'empty-deck-exchange',[zone('wounding_blow_red'),zone(id,2),zone('head_jab_blue',2)],[click('p1Hand-0'),until(window='REACTION',priority=2),click('p2Hand-0',2),until(decision=dict(player=2,type='MZMAYCHOOSE')),answer('p2Hand-0',2,'MZMAYCHOOSE'),checks(count('p2Deck',0),exists('p2Hand','head_jab_blue'),count('p2Hand',1)),until(window='RESOLUTION'),checks(scalar('p2Health',20-(4-defense)))],f'Bottoming into an empty deck makes the chosen card the sole deck card; the following draw returns that card. Printed defense{defense} still applies. Terminal-state compatibility only: membership alone does not distinguish an omitted exchange.',coverage_kind='terminal_state_compatibility')
case('bravo','cost-two-no-dominate',[zone('wounding_blow_blue'),zone('brutal_assault_blue'),zone('wounding_blow_blue'),zone('wounding_blow_blue',2),zone('wounding_blow_blue',2)],[click('p1Hero-0'),click('p1Hand-0'),until(stackEmpty=True,window='ACTION'),checks(scalar('p1Resources',1),scalar('p1ActionPoints',1)),click('p1Hand-0'),click('p1Hand-0'),until(window='DEFEND_DECLARE'),click('p2Hand-0',2),click('p2Hand-0',2),checks(count('p2CombatChain',2),count('p2Hand',0)),until(window='RESOLUTION'),checks(scalar('p2Health',20))],'Exact negative boundary: Brutal Assault blue costs2, below Bravo threshold3, so both hand blockers must be legal.',heroes=['bravo','ira_crimson_haze'])
case('goliath_gauntlet','cost-two-boundary',[zone('goliath_gauntlet',1,'Equipment'),zone('surging_strike_red'),zone('wounding_blow_blue')],[click('p1Equipment-0'),until(stackEmpty=True,window='ACTION'),click('p1Hand-0'),click('p1Hand-0'),until(window='ATTACK',stackEmpty=True),checks(semantic('power',7)),until(window='RESOLUTION'),checks(scalar('p2Health',13),scalar('p1ActionPoints',1))],'Cost2 is included: first Surging Strike red attacks for printed5+2=7 with go again.')
case('goliath_gauntlet','cost-one-exclusion',[zone('goliath_gauntlet',1,'Equipment'),zone('leg_tap_red'),zone('wounding_blow_blue')],[click('p1Equipment-0'),until(stackEmpty=True,window='ACTION'),click('p1Hand-0'),click('p1Hand-0'),until(window='ATTACK',stackEmpty=True),checks(semantic('power',4)),until(window='RESOLUTION'),checks(scalar('p2Health',16),scalar('p1ActionPoints',1))],'Exact negative boundary: Leg Tap red costs1, so it attacks for printed4 without the +2 Goliath buff.')
case('goliath_gauntlet','filter-and-consumption',[zone('goliath_gauntlet',1,'Equipment'),zone('head_jab_red'),zone('surging_strike_red'),zone('surging_strike_blue'),zone('wounding_blow_blue'),zone('wounding_blow_blue')],[click('p1Equipment-0'),until(stackEmpty=True,window='ACTION'),click('p1Hand-0'),until(window='RESOLUTION'),checks(scalar('p2Health',17),scalar('p1ActionPoints',1)),click('p1Hand-0'),click('p1Hand-1'),until(window='ATTACK',stackEmpty=True),checks(semantic('power',8)),until(window='RESOLUTION'),checks(scalar('p2Health',9),scalar('p1ActionPoints',1)),click('p1Hand-0'),click('p1Hand-0'),until(window='ATTACK',stackEmpty=True),checks(semantic('power',3)),until(window='RESOLUTION'),checks(scalar('p2Health',6),scalar('p1ActionPoints',1))],'Cost0 Head Jab does not consume buff. Second attack Surging red costs2 and gets printed5+Goliath2+Ira1=8. Third Surging blue3 receives neither consumed Goliath nor second-attack Ira bonus.')
# One representative arcane family pilot; expansion waits for independent review.
for target in [1,2]:
 id='scalding_rain_red'
 case(id,'target-'+str(target),[zone(id),zone('wounding_blow_blue')],[click('p1Hand-0'),answer('p'+str(target)+'Hero-0',typ='MZCHOOSE'),click('p1Hand-0'),until(window='ACTION',stackEmpty=True),checks(scalar('p'+str(target)+'Health',16),scalar('p'+str(3-target)+'Health',20),scalar('p1Resources',2),scalar('p1ActionPoints',0),exists('p1Graveyard',id))],'Target hero may be own or opposing. Pay printed1 by pitching blue3; deal exactly4 arcane to selected hero, leave2resources, no go again.',heroes=['kano_dracai_of_aether','ira_crimson_haze'])
for accept in [True,False]:
 case('scalding_rain_red','barrier-'+('pay' if accept else 'decline'),[zone('scalding_rain_red'),zone('wounding_blow_blue'),zone('nullrune_gloves',2,'Equipment')],[click('p1Hand-0'),answer('p2Hero-0',typ='MZCHOOSE'),click('p1Hand-0'),until(decision=dict(player=2,type='MZMODAL')),answer('1' if accept else '0',2,'MZMODAL'),until(window='ACTION',stackEmpty=True),checks(scalar('p2Health',17 if accept else 16),scalar('p2Resources',0 if accept else 1),scalar('p1Resources',2),scalar('p1ActionPoints',0),exists('p2Equipment','nullrune_gloves'))],'Arcane Barrier1 may pay1 to prevent1 of the 4 arcane damage, or decline. Equipment persists; payer is target seat2, not caster.',heroes=['kano_dracai_of_aether','ira_crimson_haze'],resources=[0,1])
(dest/'card-regression-batch03.json').write_text(json.dumps(cases,indent=2)+'\n')
print(len(cases),'batch 03 scenarios')
cases=[]
case('sic_em_shot_red','no-bow-rejected',[zone('sic_em_shot_red',1,'Arsenal',FaceDown=1)],[click('p1Arsenal-0'),checks(exists('p1Arsenal','sic_em_shot_red'),count('Stack',0),scalar('p1ActionPoints',1))],'CR8.2.6a: playing an arrow requires both arsenal origin and control of a bow. With no bow, clicking must not announce or pay for the arrow.',heroes=['azalea','ira_crimson_haze'],resources=[1,0])
(dest/'card-regression-rule-probes.json').write_text(json.dumps(cases,indent=2)+'\n')
cases=[]
for id,c in cards.items():
 if id.split('_red')[0].split('_yellow')[0].split('_blue')[0] not in ['scalding_rain','voltic_bolt','zap'] or id=='scalding_rain_red':continue
 import re
 m=re.fullmatch(r'Deal (\d+) arcane damage to target hero\.?',c['functional_text_plain'].strip())
 if not m:raise ValueError('Plain arcane family text changed: '+id)
 damage=int(m[1]);cost=int(c['cost']);n=(cost+2)//3
 basezones=[zone(id)]+[zone('wounding_blow_blue') for _ in range(n)]
 for target in [1,2]:
  steps=[click('p1Hand-0'),answer('p'+str(target)+'Hero-0',typ='MZCHOOSE')]+[click('p1Hand-0') for _ in range(n)]+[until(window='ACTION',stackEmpty=True),checks(scalar('p'+str(target)+'Health',20-damage),scalar('p'+str(3-target)+'Health',20),scalar('p1Resources',3*n-cost),scalar('p1ActionPoints',0),exists('p1Graveyard',id))]
  case(id,'target-'+str(target),basezones,steps,f'Exact plain spell text: deal {damage} arcane to chosen own/opposing hero. Printed cost{cost}; {n} blue pitches leaves{3*n-cost}. Action consumes one AP.',heroes=['kano_dracai_of_aether','ira_crimson_haze'])
 for accept in [True,False]:
  steps=[click('p1Hand-0'),answer('p2Hero-0',typ='MZCHOOSE')]+[click('p1Hand-0') for _ in range(n)]+[until(decision=dict(player=2,type='MZMODAL')),answer('1' if accept else '0',2,'MZMODAL'),until(window='ACTION',stackEmpty=True),checks(scalar('p2Health',20-damage+int(accept)),scalar('p2Resources',0 if accept else 1),scalar('p1Resources',3*n-cost),scalar('p1ActionPoints',0),exists('p2Equipment','nullrune_gloves'))]
  case(id,'barrier-'+('pay' if accept else 'decline'),basezones+[zone('nullrune_gloves',2,'Equipment')],steps,f'Arcane packet{damage}; target seat2 elects to '+('pay1 to prevent1' if accept else 'decline prevention')+f'. Caster pays cost{cost}; equipment persists.',heroes=['kano_dracai_of_aether','ira_crimson_haze'],resources=[0,1])
 if cost:
  case(id,'unaffordable',[zone(id)],[click('p1Hand-0'),checks(exists('p1Hand',id),count('Stack',0),scalar('p1Resources',0),scalar('p1ActionPoints',1))],f'Action spell cost{cost} cannot be paid with no floating resource or other hand card to pitch.',heroes=['kano_dracai_of_aether','ira_crimson_haze'])
 case(id,'no-action-point',[zone(id)],[click('p1Hand-0'),checks(exists('p1Hand',id),count('Stack',0),scalar('p1Resources',cost),scalar('p1ActionPoints',0))],f'Action card requires an AP even with printed resource cost{cost} available; announcement must be rejected.',heroes=['kano_dracai_of_aether','ira_crimson_haze'],resources=[cost,0],actionPoints=[0,0])
(dest/'card-regression-batch04.json').write_text(json.dumps(cases,indent=2)+'\n')
print(len(cases),'reviewed plain arcane batch scenarios')
