"""Pure, fixed-scenario planning for six faults across five vanilla families.

Never executes actions or reads/writes files. Future actual saved pilots must
meet these guards; prospective unit inputs do not constitute recordings.
"""
import copy
import hashlib
import json
import re

COST1='card-critical-strike-red-baseline-hand-red-pitch-exact'
COST2='card-brutal-assault-red-baseline-hand-red-then-red-pitch'
DEF3='card-critical-strike-blue-baseline-defends-exactly-3-partial-damage'
ABSENT='card-smash-with-big-tree-blue-baseline-no-defense-value-cannot-declare-block'
ARROW='card-rusty-harpoon-blue-baseline-arsenal-facedown-floating'
CONTROL_KEYS={
 (COST1,'p1Resources to equal 0, got 1'):'vanilla_cost_one_pitch_overfund',
 (COST2,'p1Resources to equal 0, got 2'):'vanilla_cost_two_second_pitch_overfund',
 (DEF3,'Expected FaBSim state ["damageDealt"] to equal JSON 1, got 0.'):'vanilla_defense_three_plus_one',
 (DEF3,'Expected FaBSim state ["damageDealt"] to equal JSON 1, got 2.'):'vanilla_defense_three_minus_one',
 (ABSENT,'Expected FaBSim state ["defenseValue"] to equal JSON 0, got 3.'):'vanilla_absent_defense_becomes_legal',
 (ARROW,'Expected FaBSim state ["window"] to equal JSON "PRIORITY", got "ACTION".'):'vanilla_zero_cost_arrow_without_bow',
}

def parse_initial(text,schema):
 if not isinstance(text,str)or not isinstance(schema,str)or schema.splitlines()[0]!='FaBSim':raise RuntimeError('Invalid FaBSim initial/schema input')
 layout=[]
 for line in schema.splitlines():
  m=re.match(r'^([A-Za-z][A-Za-z0-9_]*)\s+-\s+(.+)$',line)
  if m:layout.append(dict(name=m[1],object=not m[2].startswith('Value:'),global_zone=False,fields=[s.strip().split(':',1)[0]for s in m[2].split(',')]))
  elif layout and'Scope=Global'in line:layout[-1]['global_zone']=True
 if not layout:raise RuntimeError('Empty schema layout')
 lines=text.splitlines();cursor=2;out={};positions={}
 try:
  if len(lines)<2 or int(lines[0])!=1:raise ValueError('Unexpected initial viewer')
  for zone in layout:
   for seat in ([0]if zone['global_zone']else[1,2,3,4]):
    name=zone['name']if seat==0 else f'p{seat}'+zone['name']
    if zone['object']:
     n=int(lines[cursor]);cursor+=1
     if n<0 or cursor+n>len(lines):raise ValueError('Invalid source count')
     positions[name]=list(range(cursor,cursor+n));out[name]=[dict(zip(zone['fields'],line.split(' ')))for line in lines[cursor:cursor+n]];cursor+=n
    else:out[name]=lines[cursor];cursor+=1
 except(IndexError,ValueError,TypeError)as error:raise RuntimeError('Malformed initial fixture')from error
 if cursor!=len(lines):raise RuntimeError('Initial snapshot not fully consumed')
 return out,positions,lines

def build_vanilla_control_plan(original,reason,actions,assertions,initial_text,schema_text):
 if(original,reason)not in CONTROL_KEYS:raise RuntimeError('Unregistered vanilla control')
 if not isinstance(actions,list)or not isinstance(assertions,list)or not actions:raise RuntimeError('Missing actual saved actions/assertions')
 if any(not isinstance(a,dict)or type(a.get('step'))is not int or not 0<=a['step']<=len(actions)for a in assertions):raise RuntimeError('Invalid native assertion steps')
 kind=CONTROL_KEYS[(original,reason)]
 def match(a,**fields):return all(a.get(k)==v for k,v in fields.items())
 def steps(**fields):return {a['step']for a in assertions if match(a,**fields)}
 def require(ok,message):
  if not ok:raise RuntimeError(message)
 def state_steps(key,value):return steps(type='game_state_field',rootName='FaBSim',path=[key],comparison='equals',valueJson=json.dumps(value,separators=(',',':')))
 def one(values,label):require(len(values)==1,'Need one '+label);return next(iter(values))
 def action_tuple(a):return(a.get('playerID'),a.get('mode'),a.get('cardID'))
 def only_passes(rows):return all(a.get('mode')==10001 and a.get('playerID')in[1,2]and a.get('cardID')=='!CustomInput!Pass'for a in rows)
 initial,positions,lines=parse_initial(initial_text,schema_text)
 expected_zones={
 COST1:{'p1Hand':['critical_strike_red','wounding_blow_red']},
 COST2:{'p1Hand':['brutal_assault_red','wounding_blow_red','critical_strike_red']},
 DEF3:{'p1Hand':['wounding_blow_red'],'p2Hand':['critical_strike_blue']},
 ABSENT:{'p1Hand':['wounding_blow_red'],'p2Hand':['smash_with_big_tree_blue']},
 ARROW:{'p1Arsenal':['rusty_harpoon_blue'],'p1Weapons':['death_dealer']},
 }[original]
 wanted_heroes=['marlynn','dorinthea']if original==ARROW else['dorinthea','rhinar']if original==ABSENT else['dorinthea','dorinthea']
 uids=[]
 for seat in [1,2]:
  require(len(initial.get(f'p{seat}Hero',[]))==1 and initial[f'p{seat}Hero'][0]['CardID']==wanted_heroes[seat-1],'Unexpected legal fixture hero')
  for zone in ['Hero','Hand','Arsenal','Weapons','Equipment','Arena','Deck','Pitch','Graveyard','Banish','Soul','Temp','Inventory','CombatChain']:
   name=f'p{seat}'+zone;rows=initial.get(name)
   require(isinstance(rows,list),'Missing initial zone '+name)
   if zone!='Hero':require([o['CardID']for o in rows]==expected_zones.get(name,[]),'Unexpected initial source/order '+name)
   for o in rows:
    require(o.get('Owner',str(seat))==str(seat)and o.get('Controller',str(seat))==str(seat),'Unexpected initial ownership')
    require(o.get('TurnEffects','-')=='-'and o.get('Counters','-')=='-','Initial modifiers are not allowed')
    require(o.get('UniqueID','').isdigit()and int(o['UniqueID'])>0,'Physical source UID must be positive')
    uids.append(int(o['UniqueID']))
  require(initial[f'p{seat}Health']=='20'and initial[f'p{seat}Resources']=='0'and initial[f'p{seat}ActionPoints']==('1'if seat==1 else'0'),'Unexpected initial assets')
 require(len(set(uids))==len(uids),'Initial physical UID collision')
 require(initial.get('UniqueIDCounter','').isdigit()and int(initial['UniqueIDCounter'])>=max(uids),'Initial UID allocator must cover live objects')
 require(initial.get('LiveSeats')=='12'and initial.get('SeatOrder')=='12','Expected ordinary two-seat order')
 for name,value in [('PriorityPlayer','1'),('ConsecutivePasses','0'),('TurnPlayer','1'),('TurnNumber','2'),('CurrentPhase','MAIN'),('Winner','0')]:require(initial.get(name)==value,'Unexpected initial '+name)
 require(not initial.get('Stack')and all(not initial.get(f'p{p}DecisionQueue')for p in[1,2,3,4]),'Unexpected initial stack/choice')
 require(all(not initial.get(f'p{p}Hero')for p in[3,4]),'Only the two expected heroes may be present')
 try:s=json.loads(initial['GameState']);v=json.loads(initial['DecisionQueueVariables']or'{}')
 except(ValueError,TypeError,KeyError)as error:raise RuntimeError('Malformed initial semantic state')from error
 require(isinstance(s,dict)and s.get('window')=='ACTION'and s.get('combatOpen')is False and s.get('pendingPayment')is None and s.get('resolvingReaction')is None,'Unexpected initial semantic context')
 require(not v or isinstance(v,dict)and not v.get('__awaitFrames'),'Unexpected initial await work')
 for key in ['turnEffects','nextTurnEffects','intimidated','hitsThisTurn','cardsPlayedThisTurn']:require(not s.get(key),'Initial external effects/history are not allowed')
 require(all(a.get('mode')in[10001,10002]and a.get('playerID')in[1,2]for a in actions),'Unexpected interactive action kind')
 direct=[(i,a)for i,a in enumerate(actions)if a['mode']==10002]
 wanted_direct=[(1,10002,'p1Arsenal-0!FSM')]if original==ARROW else[(1,10002,'p1Hand-0!FSM'),(2,10002,'p2Hand-0!FSM')]if original in[DEF3,ABSENT]else[(1,10002,'p1Hand-0!FSM')]*(2 if original==COST1 else 3)
 require([action_tuple(a)for _,a in direct]==wanted_direct,'Unexpected actual play/pitch/block clicks')
 require(only_passes([a for a in actions if a['mode']!=10002]),'Require only actual priority passes outside direct clicks')

 plan=dict(mutation=kind,prefix=0,actions=copy.deepcopy(actions),assertions=copy.deepcopy(assertions),original_initial_sha256=hashlib.sha256(initial_text.encode()).hexdigest())
 if original in[COST1,COST2,ARROW]:
  cid='critical_strike_red'if original==COST1 else'brutal_assault_red'if original==COST2 else'rusty_harpoon_blue'
  origin='Arsenal'if original==ARROW else'Hand'
  paid=one(state_steps('window','PRIORITY')&steps(type='zone_count',zone='Stack',value=1)&steps(type='card_exists',zone='Stack',cardID=cid)&steps(type='card_property_equals',mzId='Stack-0',property='SourceZone',value=origin)&steps(type='card_property_equals',mzId='Stack-0',property='Kind',value='ATTACK')&steps(type='card_property_equals',mzId='Stack-0',property='Owner',value='1')&steps(type='card_property_equals',mzId='Stack-0',property='Controller',value='1'),'native paid source boundary')
  require(paid==(2 if original==COST1 else 3 if original==COST2 else 1),'Payment must directly follow known clicks')
  require([i for i,_ in direct]==list(range(paid)),'No response/pass before costs finish')
  require(paid in steps(type='scalar_zone_equals',zone='p1Resources',value=0)and paid in steps(type='scalar_zone_equals',zone='p1ActionPoints',value=0),'Native exact asset charge missing')
  for name in ['p2Resources','p2ActionPoints']:require(paid in steps(type='scalar_zone_equals',zone=name,value=0),'Native opposing asset checkpoint missing')
  for field in ['pendingPayment','resolvingReaction']:require(paid in steps(type='game_state_field',rootName='FaBSim',path=[field],comparison='missing_or_null'),'Native paid cleanup missing')
  if original in[COST1,COST2]:
   for step in range(1,paid):
    require(step in state_steps('window','PITCH')and step in steps(type='scalar_zone_equals',zone='p1ActionPoints',value=1)and step in steps(type='scalar_zone_equals',zone='p1Resources',value=step-1),'Partial funding/AP invariant missing')
  else:require(initial['p1Arsenal'][0].get('FaceDown')=='1','Arrow pilot must start facedown')
  plan['expected_failure_step']=paid
 elif original==ABSENT:
  boundary=direct[1][0]+1
  require(boundary in steps(type='computed_power_equals',mzId='p1CombatChain-0',value=4)and boundary in state_steps('window','DEFEND_DECLARE')and boundary in state_steps('defenseValue',0),'Absent-defense rejection boundary missing')
  require(boundary in steps(type='card_exists',zone='p2Hand',cardID='smash_with_big_tree_blue')and boundary in steps(type='zone_count',zone='p2Hand',value=1)and boundary in steps(type='zone_count',zone='p2CombatChain',value=0),'Absent-defense source invariant missing')
  for name in ['p1Resources','p2Resources','p1ActionPoints','p2ActionPoints']:require(boundary in steps(type='scalar_zone_equals',zone=name,value=0),'Native absent-defense free assets missing')
  require(boundary in steps(type='zone_count',zone='Stack',value=0),'Native absent-defense empty Stack missing')
  for field in ['pendingPayment','resolvingReaction']:require(boundary in steps(type='game_state_field',rootName='FaBSim',path=[field],comparison='missing_or_null'),'Native absent-defense cleanup missing')
  plan['expected_failure_step']=boundary
 else:
  boundary=one(steps(type='card_property_equals',mzId='p2CombatChain-0',property='Role',value='DEFENSE')&steps(type='card_property_equals',mzId='p2CombatChain-0',property='FromZone',value='Hand')&steps(type='card_property_equals',mzId='p2CombatChain-0',property='Owner',value='2')&steps(type='card_property_equals',mzId='p2CombatChain-0',property='Controller',value='2')&steps(type='card_exists',zone='p2CombatChain',cardID='critical_strike_blue')&steps(type='computed_power_equals',mzId='p1CombatChain-0',value=4)&state_steps('window','DEFEND_DECLARE')&state_steps('defenseValue',3),'native exact defense3 source boundary')
  require(boundary==direct[1][0]+1,'Defense prefix must end with actual declared block')
  for name in ['p1Resources','p2Resources','p1ActionPoints','p2ActionPoints']:require(boundary in steps(type='scalar_zone_equals',zone=name,value=0),'Native free-defense asset missing')
  for key in ['pendingPayment','resolvingReaction']:require(boundary in steps(type='game_state_field',rootName='FaBSim',path=[key],comparison='missing_or_null'),'Native defense cleanup missing')
  require(boundary in steps(type='zone_count',zone='Stack',value=0),'Native empty Stack missing')
  damaged=one(state_steps('window','RESOLUTION')&state_steps('damageDealt',1)&state_steps('attackHit',True)&steps(type='scalar_zone_equals',zone='p2Health',value=19)&steps(type='card_exists',zone='p2CombatChain',cardID='critical_strike_blue')&steps(type='zone_count',zone='p2Graveyard',value=0),'native exact defense damage boundary')
  require(boundary<damaged<len(actions)and only_passes(actions[boundary:]),'Only real passes may follow the defense fault')
  plan.update(prefix=boundary,actions=copy.deepcopy(actions[boundary:]),assertions=[dict(a,step=a['step']-boundary)for a in assertions if a['step']>=boundary],expected_failure_step=damaged-boundary,
   object_effect=[dict(player=2,zone='CombatChain',cardID='critical_strike_blue',tag='WTR_DEFENSE:1'if kind.endswith('plus_one')else'WTR_DEFENSE:-1',mode='add',vanilla_defense_source=dict(sourceUID=int(initial['p2Hand'][0]['UniqueID']),attackUID=int(initial['p1Hand'][0]['UniqueID'])))])

 if original!=DEF3:
  old,new,zone,index,allowed=(
   ('wounding_blow_red','wounding_blow_yellow','p1Hand',1,{'p1Pitch'})if original==COST1 else
   ('critical_strike_red','critical_strike_blue','p1Hand',2,{'p1Pitch'})if original==COST2 else
   ('smash_with_big_tree_blue','brutal_assault_blue','p2Hand',0,{'p2Hand'})if original==ABSENT else
   ('death_dealer','talishar_the_lost_prince','p1Weapons',0,{'p1Weapons'}))
  rows=initial[zone];source=rows[index]
  require(source['CardID']==old,'Unexpected exact initial fault source')
  require(sum(o.get('CardID')==old for value in initial.values()if isinstance(value,list)for o in value)==1,'Fault identity must occur in exactly one initial object')
  line=positions[zone][index];raw_lines=initial_text.splitlines(keepends=True)
  first,sep,rest=raw_lines[line].partition(' ');require(first==old and bool(sep),'Exact CardID row token missing')
  raw_lines[line]=new+sep+rest
  modified=''.join(raw_lines)
  after,_,_=parse_initial(modified,schema_text);expected=copy.deepcopy(initial);expected[zone][index]['CardID']=new
  require(after==expected,'Identity fault changed another initial property/UID')
  rewrites=0
  for a in plan['assertions']:
   if a.get('cardID')==old:
    require(a.get('type')=='card_exists'and a.get('zone')in allowed,'Unexpected identity oracle requiring rewrite')
    a['cardID']=new;rewrites+=1
   if a.get('property')=='CardID'and a.get('value')==old:raise RuntimeError('New identity property oracle requires independent planner review')
  require(rewrites>0 or original==ARROW,'Expected membership references for mutated source')
  plan.update(initial_text=modified,identity_replacement=dict(old=old,new=new,zone=zone,index=index,sourceUID=int(source['UniqueID']),rewritten_memberships=rewrites))
 # The exact first intended assertion remains unmodified in the retained tail.
 target=(dict(type='scalar_zone_equals',zone='p1Resources',value=0)if original in[COST1,COST2]else
         dict(type='game_state_field',rootName='FaBSim',path=['damageDealt'],comparison='equals',valueJson='1')if original==DEF3 else
         dict(type='game_state_field',rootName='FaBSim',path=['defenseValue'],comparison='equals',valueJson='0')if original==ABSENT else
         dict(type='game_state_field',rootName='FaBSim',path=['window'],comparison='equals',valueJson='"PRIORITY"'))
 require(any(a['step']==plan['expected_failure_step']and match(a,**target)for a in plan['assertions']),'Intended native assertion was not retained')
 plan['target_assertion']=target
 return plan
