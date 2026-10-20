"""Pure modern AB control planning; never imports an engine or writes fixtures."""
import copy
import hashlib
import json
from vanilla_control_plan import parse_initial

FLOAT = 'card-aetherstorm-wellingtons-pay-floating-exact'
PITCH = 'card-aetherstorm-wellingtons-pay-blue-pitch'
DEFEND = 'card-aetherstorm-wellingtons-intrinsic-barrier-while-defending-real-kano-instant'
REASONS = {
 FLOAT: 'state ["arcaneDealt"] to equal JSON {"1":{"2":1}}, got {"1":{"2":3}}',
 PITCH: 'p2Resources to equal 1, got 0',
 DEFEND: "p2DecisionQueue-0.Param to equal 'p2CombatChain-0', got ''",
}
CONTROL_KEYS = set(REASONS.items())


def require(value, message):
 if not value: raise RuntimeError(message)


def build_ab_control_plan(original, reason, actions, assertions, initial_text, schema_text):
 require((original, reason) in CONTROL_KEYS, 'Unknown isolated modern AB control')
 require(all(isinstance(a,dict) and isinstance(a.get('step'),int) and 0<=a['step']<=len(actions) for a in assertions), 'Malformed assertion step')
 initial, positions, _ = parse_initial(initial_text, schema_text)
 def has(step, **wanted): return any(a['step']==step and all(a.get(k)==v for k,v in wanted.items()) for a in assertions)
 def property_(step,ref,key,value): return has(step,type='card_property_equals',mzId=ref,property=key,value=str(value))
 def state(step,key,value): return has(step,type='game_state_field',rootName='FaBSim',path=[key],comparison='equals',valueJson=json.dumps(value,separators=(',',':')))
 def scalar(step,zone,value): return has(step,type='scalar_zone_equals',zone=zone,value=value)
 def live_source(step,zone='Equipment'):
  ref='p2'+zone+'-0'
  for key,value in [('CardID','aetherstorm_wellingtons'),('UniqueID',4),('Owner',2),('Controller',2),('Status',2),('Damage',0)]: require(property_(step,ref,key,value),'Missing exact native AB source '+key)
  require(has(step,type='zone_count',zone='p2'+zone,value=1),'Missing sole native AB source')
 def source_stack(step,uid,kind,origin):
  require(has(step,type='zone_count',zone='Stack',value=1),'Missing pending original spell layer')
  for key,value in [('CardID','aether_dart_red'),('SourceUniqueID',uid),('Owner',1),('Controller',1),('Kind',kind),('SourceZone',origin)]: require(property_(step,'Stack-0',key,value),'Missing exact native spell layer '+key)
 def action_tuple(a): return (a.get('playerID'),a.get('mode'),a.get('cardID'))
 require(initial.get('LiveSeats')=='12' and initial.get('SeatOrder')=='12','Expected two live ordinary seats')
 for key,value in [('TurnPlayer','1'),('PriorityPlayer','1'),('ConsecutivePasses','0'),('Winner','0')]: require(initial.get(key)==value,'Unexpected initial '+key)
 require(not initial.get('Stack') and all(not initial.get(f'p{p}DecisionQueue') for p in [1,2,3,4]),'Unexpected initial pending work')
 require(initial.get('DecisionQueueVariables') in ['', '{}','[]'],'Unexpected initial continuation variables')
 heroes=[o['CardID'] for p in [1,2] for o in initial.get(f'p{p}Hero',[])]
 require(heroes==(['kano','kano'] if original==DEFEND else ['iyslander','kano']),'Expected legal Wizard equipment owners')
 expected_zones={'p1Hand':['wounding_blow_blue' if original==DEFEND else 'aether_dart_red'],'p2Equipment':['aetherstorm_wellingtons']}
 if original==DEFEND:expected_zones['p1Deck']=['aether_dart_red']
 if original==PITCH:expected_zones['p2Hand']=['wounding_blow_blue']
 uids=[]
 require(len(initial['p2Equipment'])==1 and initial['p2Equipment'][0]['CardID']=='aetherstorm_wellingtons' and initial['p2Equipment'][0]['UniqueID']=='4','Expected exact initial equipment UID4')
 for p in [1,2]:
  require(initial[f'p{p}ActionPoints']==('1' if p==1 else '0'),'Unexpected initial AP')
  for zone in ['Hero','Hand','Deck','Arsenal','Equipment','Weapons','Arena','CombatChain','Pitch','Graveyard','Banish','Soul','Temp','Inventory']:
   if zone!='Hero':require([o['CardID']for o in initial.get(f'p{p}'+zone,[])]==expected_zones.get(f'p{p}'+zone,[]),'Unexpected initial sources/order')
   for obj in initial.get(f'p{p}'+zone,[]):
    require(obj.get('Owner',str(p))==str(p) and obj.get('Controller',str(p))==str(p),'Unexpected ownership')
    require(obj.get('TurnEffects','-')=='-' and obj.get('Counters','-')=='-','Unexpected initial source effects')
    require(obj.get('UniqueID','').isdigit() and int(obj['UniqueID'])>0,'Missing physical UID');uids.append(int(obj['UniqueID']))
 require(len(set(uids))==len(uids) and int(initial.get('UniqueIDCounter',0))>=max(uids),'Unexpected physical UID collision/allocator')
 require(all(not initial.get(f'p{p}Hero') for p in [3,4]),'Unexpected third or fourth hero')
 try:initial_state=json.loads(initial['GameState'])
 except(ValueError,KeyError)as error:raise RuntimeError('Malformed initial state')from error
 require(initial_state.get('window')=='ACTION' and initial_state.get('combatOpen')is False and initial_state.get('pendingPayment')is None and initial_state.get('resolvingReaction')is None,'Unexpected initial semantic boundary')
 require(all(not initial_state.get(k)for k in ['turnEffects','nextTurnEffects','hitsThisTurn','cardsPlayedThisTurn']),'Unexpected seeded effects/history')
 plan={'mutation':{FLOAT:'ab_fixed_two_decline',PITCH:'ab_blue_pitch_funding',DEFEND:'ab_defending_source_eligibility'}[original], 'prefix':0,
       'actions':copy.deepcopy(actions),'assertions':copy.deepcopy(assertions),'original_initial_sha256':hashlib.sha256(initial_text.encode()).hexdigest()}
 if original in [FLOAT,PITCH]:
  require(len(actions)==(5 if original==FLOAT else 6),'Unexpected modern AB action count')
  require([action_tuple(a) for a in actions[:5]]==[(1,10002,'p1Hand-0!FSM'),(1,100,'p2Hero-0'),(2,10001,'!CustomInput!Pass'),(1,10001,'!CustomInput!Pass'),(2,100,'p2Equipment-0')],'Unexpected actual AB target/pass/payment path')
  require(initial['p1Hand'][0]['CardID']=='aether_dart_red' and initial['p1Hand'][0]['UniqueID']=='3' and len(initial['p1Hand'])==1,'Expected exact ordinary Dart source')
  require(initial['p1Resources']=='0' and initial['p2Resources']==('2' if original==FLOAT else '0'),'Unexpected AB funding')
  require(initial['p1Health']=='20' and initial['p2Health']=='20','Unexpected known healed heroes')
  source_stack(4,3,'ACTION','Hand'); live_source(4)
  require(property_(4,'p2DecisionQueue-0','Type','MZMAYCHOOSE') and property_(4,'p2DecisionQueue-0','Param','p2Equipment-0'),'Expected exact offered AB2 source')
  require(scalar(4,'p2Resources',2 if original==FLOAT else 0) and scalar(4,'p2Health',20),'Expected unchanged prepayment assets')
  require(state(len(actions),'arcaneDealt',{'1':{'2':1}}),'Expected retained correct hero attribution')
  if original==FLOAT:
   require(not initial['p2Hand'],'Floating AB must have no pitch source')
   plan['actions'][4]['cardID']='PASS'
   target=dict(type='game_state_field',rootName='FaBSim',path=['arcaneDealt'],comparison='equals',valueJson='{"1":{"2":1}}')
   plan.update(expected_failure_step=5, action_replacement=dict(index=4,old=actions[4],new=plan['actions'][4]))
  else:
   require(action_tuple(actions[5])==(2,100,'p2Hand-0'),'Expected actual single effect-pitch choice')
   require(len(initial['p2Hand'])==1 and initial['p2Hand'][0]['CardID']=='wounding_blow_blue' and initial['p2Hand'][0]['UniqueID']=='5','Expected exact blue3 pitch UID5')
   source_stack(5,3,'ACTION','Hand');live_source(5)
   require(property_(5,'p2DecisionQueue-0','Type','MZCHOOSE') and property_(5,'p2DecisionQueue-0','Param','p2Hand-0'),'Expected live mandatory pitch choice')
   require(scalar(6,'p2Resources',1),'Expected unchanged blue-pitch remainder oracle')
   raw=initial_text.splitlines(keepends=True);line=positions['p2Hand'][0];old,sep,rest=raw[line].partition(' ')
   require(old=='wounding_blow_blue' and sep,'Missing exact blue row token')
   raw[line]='wounding_blow_yellow'+sep+rest;changed=''.join(raw)
   after,_,_=parse_initial(changed,schema_text);expected=copy.deepcopy(initial);expected['p2Hand'][0]['CardID']='wounding_blow_yellow'
   require(after==expected,'Pitch fault changed another field/UID')
   rewrites=0
   for a in plan['assertions']:
    if a.get('cardID')=='wounding_blow_blue':
     require(a.get('type')=='card_exists' and a.get('zone') in ['p2Hand','p2Pitch'],'Unexpected pitch identity membership');a['cardID']='wounding_blow_yellow';rewrites+=1
    require(not(a.get('property')=='CardID' and a.get('value')=='wounding_blow_blue'),'Explicit identity property needs independently reviewed rewrite')
   require(rewrites>0,'Missing retained pitch membership')
   target=dict(type='scalar_zone_equals',zone='p2Resources',value=1)
   plan.update(initial_text=changed,expected_failure_step=6,identity_replacement=dict(zone='p2Hand',index=0,sourceUID=5,old='wounding_blow_blue',new='wounding_blow_yellow',rewritten_memberships=rewrites))
 else:
  # Discover only an actual native offer boundary. No fabricated action history.
  direct=[action_tuple(a)for a in actions if a.get('mode')!=10001]
  require(direct==[(1,10002,'p1Hand-0!FSM'),(2,10002,'p2Equipment-0!FSM'),(1,10002,'p1Hero-0!FSM'),(1,100,'p1Temp-0'),(1,10002,'p1Banish-0!FSM'),(1,100,'p2Hero-0'),(2,100,'p2CombatChain-0')],'Expected actual block/Kano/grant/Dart/payment actions')
  require(all(a.get('playerID')in[1,2] and a.get('cardID')=='!CustomInput!Pass'for a in actions if a.get('mode')==10001),'Unexpected intervening response')
  offers=[a['step'] for a in assertions if a.get('type')=='card_property_equals' and a.get('mzId')=='p2DecisionQueue-0' and a.get('property')=='Param' and a.get('value')=='p2CombatChain-0']
  require(len(set(offers))==1,'Expected one exact defending-source offer checkpoint');prefix=offers[0]
  require(0<prefix<len(actions),'Invalid real defending-source prefix')
  require(action_tuple(actions[prefix])==(2,100,'p2CombatChain-0'),'Expected next actual payment reference')
  source_stack(prefix,5,'INSTANT','Banish');live_source(prefix,'CombatChain')
  for k,v in [('Role','DEFENSE'),('FromZone','Equipment'),('ChainLink',1)]: require(property_(prefix,'p2CombatChain-0',k,v),'Missing real defending-source '+k)
  require(has(prefix,type='zone_count',zone='p2Equipment',value=0),'Original Equipment must have moved')
  require(property_(prefix,'p2DecisionQueue-0','Type','MZMAYCHOOSE'),'Expected optional barrier offer')
  for zone,value in [('p1Resources',0),('p2Resources',2),('p1ActionPoints',0),('p2ActionPoints',0),('p1Health',15),('p2Health',15)]: require(scalar(prefix,zone,value),'Missing defending offer assets')
  require(initial['p1Resources']=='3' and initial['p2Resources']=='2' and initial['p1Health']=='15' and initial['p2Health']=='15','Unexpected legal Kano initial state')
  require(len(initial['p1Deck'])==1 and initial['p1Deck'][0]['CardID']=='aether_dart_red' and initial['p1Deck'][0]['UniqueID']=='5','Expected real Kano top DartUID5')
  require(len(initial['p1Hand'])==1 and initial['p1Hand'][0]['CardID']=='wounding_blow_blue' and initial['p1Hand'][0]['UniqueID']=='3','Expected real attackUID3')
  target=dict(type='card_property_equals',mzId='p2DecisionQueue-0',property='Param',value='p2CombatChain-0')
  plan.update(prefix=prefix,actions=copy.deepcopy(actions[prefix:]),assertions=[dict(a,step=a['step']-prefix) for a in assertions if a['step']>=prefix],expected_failure_step=0,
              ab_barrier_fault=dict(sourceUID=4,spellUID=5,attackUID=3,victim=2,sourceRef='p2CombatChain-0'))
 require(any(a['step']==plan['expected_failure_step'] and all(a.get(k)==v for k,v in target.items()) for a in plan['assertions']),'Intended earliest native oracle absent')
 plan['target_assertion']=target
 return plan
