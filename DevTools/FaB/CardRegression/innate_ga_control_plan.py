"""Pure, exact saved-pilot planning; no engine imports or fixture writes."""
import copy,hashlib,json
from pathlib import Path
from vanilla_control_plan import parse_initial
GO='card-surging-strike-red-innate-ga-hand-fully-blocked-go-again-real-followup'
POWER='card-fry-blue-innate-ga-zero-cost-keeps-spare-blue'
PITCH='card-sic-em-shot-red-innate-ga-arsenal-blue-pitch'
GO_REASON='computed go again to equal 1, got 0'
POWER_REASON='computed power to equal 1, got 2'
DEF_REASON='p2Health to equal 20, got 19'
PITCH_REASON='p1Resources to equal 2, got 1'
CONTROL_KEYS={(GO,GO_REASON):'innate_ga_missing_refund',(POWER,POWER_REASON):'innate_ga_printed_power',(GO,DEF_REASON):'innate_ga_full_block_defense_loss',(PITCH,PITCH_REASON):'innate_ga_asset_pitch_surplus'}
APPROVED_CORE_HASHES = {'card-fry-blue-innate-ga-zero-cost-keeps-spare-blue': {'initial_gamestate.txt': '5f5559ea6594a3b660916f85d6899d848d5567f6aab0a6fd080107e4ae97edbc', 'actions.json': '7eebce0730347849658fe72f36bdcda24dd9649c4e2713013c60bb2b65e8113b', 'assertions.json': '2182c94db0d6b633fa1afe8b59fbc2c6b9bf9d668e895be15f9abeb1ad38d915', 'expected_final_gamestate.txt': 'c2e3c6c1c9aac3f4d253096b3e482415487827dc3a29435c0eb9ff78d35c1650'}, 'card-surging-strike-red-innate-ga-hand-fully-blocked-go-again-real-followup': {'initial_gamestate.txt': 'e1f7745d98c038d437bab9429a3f84dbe1d86ee2803f763a06a773ed7ca997d1', 'actions.json': '5385c5c70590cdbc8ae6f3f005eced3087d19391873c04dbc78cb43f3246cde9', 'assertions.json': 'e419ac3cb75ea60029e6b5997b191c4e7bccf1c3448e5f0e8df1b1e42250da99', 'expected_final_gamestate.txt': '10270cfd3af007ca4141205fb82d26a404f6568cf0994d2e098a8291d0398852'}, 'card-dust-runner-outlaw-yellow-innate-ga-printed-pitch-funds-generic-cost-three': {'initial_gamestate.txt': '07dec1ad75027b208b4008f8a0f47cdd5da59e142ffbd4572afe86b18baf42e2', 'actions.json': 'a5527b44e1419ef78f27212f020666139abf2833f6221775456d91bea3ed7eae', 'assertions.json': '797672f2910f070f5fdabde8ad0d7a19d313f7c0223bf95160020a01887435f5', 'expected_final_gamestate.txt': 'ba88207244f9dc2af0e3c7e756da76c98486bce326cf3482ba845e7564499c08'}, 'card-sic-em-shot-red-innate-ga-arsenal-blue-pitch': {'initial_gamestate.txt': '56d17f7e663a2ead3773f902a13ff70e4168029b2a281ff40f9fa3093560c5d2', 'actions.json': 'd98747c78d75e3d8301c51bf8eb791de324a04ce4feb64b6bc1439fa315cca80', 'assertions.json': '1ccc217d0433c6a4d1797863c0cfdde1692639ffd6b7a3b3da5d535e9f14783e', 'expected_final_gamestate.txt': '9a6e50ffea3078df5f59977365e509bda9457944d18e3c01059da6e496e6cccc'}}
def require(v,m):
 if not v:raise RuntimeError(m)
def verify_ga_source_fixture(folder,original):
 require(original in APPROVED_CORE_HASHES,'Unknown exact GA source fixture')
 for name,want in APPROVED_CORE_HASHES[original].items():require(hashlib.sha256((Path(folder)/name).read_bytes()).hexdigest()==want,'Reviewed GA behavior file drift: '+name)
 meta=json.loads((Path(folder)/'meta.json').read_text(encoding='utf-8-sig'))
 require(meta.get('rootName')=='FaBSim'and meta.get('createdBy')=='FaBSim card regression authoring'and isinstance(meta.get('notes'),str),'Unexpected GA metadata identity')
def build_innate_ga_control_plan(original,reason,actions,assertions,initial_text,schema_text):
 require((original,reason)in CONTROL_KEYS,'Unknown isolated GA control')
 require(all(type(a.get('step'))is int and 0<=a['step']<=len(actions)for a in assertions),'Malformed GA native assertion steps')
 before=copy.deepcopy((actions,assertions));initial,pos,_=parse_initial(initial_text,schema_text)
 def has(step,**kv):return any(a['step']==step and all(a.get(k)==v for k,v in kv.items())for a in assertions)
 def prop(step,ref,k,v):return has(step,type='card_property_equals',mzId=ref,property=k,value=str(v))
 def state(step,k,v):return has(step,type='game_state_field',rootName='FaBSim',path=[k],comparison='equals',valueJson=json.dumps(v,separators=(',',':')))
 def scalar(step,z,v):return has(step,type='scalar_zone_equals',zone=z,value=v)
 def one(values,label):require(len(values)==1,'Expected one '+label);return next(iter(values))
 def steps(**kv):return {a['step']for a in assertions if all(a.get(k)==v for k,v in kv.items())}
 kind=CONTROL_KEYS[(original,reason)]
 heroes=['fai','dorinthea']if original==GO else['aurora','dorinthea']if original==POWER else['azalea','dorinthea']
 zones={'p1Hand':['surging_strike_red','energy_potion_blue'],'p2Hand':['wounding_blow_blue','wounding_blow_blue']}if original==GO else{'p1Hand':['fry_blue','wounding_blow_blue']}if original==POWER else{'p1Hand':['wounding_blow_blue'],'p1Arsenal':['sic_em_shot_red'],'p1Weapons':['death_dealer']}
 require(initial.get('LiveSeats')=='12'and initial.get('SeatOrder')=='12','Unexpected GA live seats')
 for k,v in [('TurnPlayer','1'),('TurnNumber','2'),('CurrentPhase','MAIN'),('PriorityPlayer','1'),('ConsecutivePasses','0'),('Winner','0')]:require(initial.get(k)==v,'Unexpected initial GA '+k)
 for p in [1,2]:
  require(len(initial[f'p{p}Hero'])==1 and initial[f'p{p}Hero'][0]['CardID']==heroes[p-1],'Wrong legal GA hero')
  for zone in ['Hand','Arsenal','Weapons','Equipment','Arena','Deck','Pitch','Graveyard','CombatChain','Banish','Soul','Temp','Inventory']:
   rows=initial[f'p{p}'+zone];require([o['CardID']for o in rows]==zones.get(f'p{p}'+zone,[]),'Unexpected GA source/order '+zone)
   for o in rows:require(o['Owner']==o['Controller']==str(p)and o.get('TurnEffects','-')=='-'and o.get('Counters','-')=='-','Unexpected seeded GA modifier/ownership')
  require(initial[f'p{p}Health']=='20'and initial[f'p{p}ActionPoints']==('1'if p==1 else'0')and initial[f'p{p}Resources']==('2'if p==1 and original==GO else'0'),'Unexpected initial GA assets')
 require(not initial['Stack']and all(not initial[f'p{p}DecisionQueue']for p in [1,2,3,4]),'Unexpected initial GA work')
 s=json.loads(initial['GameState']);v=json.loads(initial['DecisionQueueVariables']or'{}')
 require(s.get('window')=='ACTION'and s.get('combatOpen')is False and s.get('pendingPayment')is None and s.get('resolvingReaction')is None and not(v and v.get('__awaitFrames')),'Unexpected initial GA frame/context')
 require(all(not s.get(k)for k in ['turnEffects','nextTurnEffects','cardsPlayedThisTurn','hitsThisTurn']),'Initial GA effect/history not permitted')
 require(all(a.get('mode')in [10001,10002]and a.get('playerID')in [1,2]for a in actions),'Unexpected GA action kind')
 direct=[(a['playerID'],a['cardID'])for a in actions if a['mode']==10002]
 expected=[(1,'p1Hand-0!FSM'),(2,'p2Hand-0!FSM'),(2,'p2Hand-0!FSM'),(1,'p1Hand-0!FSM')]if original==GO else[(1,'p1Hand-0!FSM')]if original==POWER else[(1,'p1Arsenal-0!FSM'),(1,'p1Hand-0!FSM')]
 require(direct==expected and all(a['cardID']=='!CustomInput!Pass'for a in actions if a['mode']==10001),'Unexpected real GA play/block/pitch/followup path')
 plan=dict(mutation=kind,prefix=0,actions=copy.deepcopy(actions),assertions=copy.deepcopy(assertions),original_initial_sha256=hashlib.sha256(initial_text.encode()).hexdigest())
 if original==PITCH:
  require(len(actions)==15,'Unexpected exact Arrow pilot length')
  require(initial['p1Arsenal'][0]['UniqueID']=='3'and initial['p1Arsenal'][0]['FaceDown']=='1'and initial['p1Hand'][0]['UniqueID']=='4'and initial['p1Weapons'][0]['UniqueID']=='5','Actual legal Arrow source/funding/Bow UIDs')
  require(state(1,'window','PITCH')and scalar(1,'p1Resources',0)and scalar(1,'p1ActionPoints',1),'Arrow actual pending resource/AP checkpoint')
  require(state(2,'window','PRIORITY')and scalar(2,'p1Resources',2)and scalar(2,'p1ActionPoints',0),'Arrow actual fully paid once economy')
  for key,value in [('CardID','sic_em_shot_red'),('SourceUniqueID',3),('Owner',1),('Controller',1),('SourceZone','Arsenal'),('Kind','ATTACK')]:require(prop(2,'Stack-0',key,value),'Paid Arrow source '+key)
  require(prop(2,'p1Pitch-0','UniqueID',4)and prop(2,'p1Pitch-0','CardID','wounding_blow_blue'),'Actual pitched blueUID4')
  raw=initial_text.splitlines(keepends=True);line=pos['p1Hand'][0];first,sep,rest=raw[line].partition(' ');require(first=='wounding_blow_blue'and sep,'Exact blue funding token')
  raw[line]='wounding_blow_yellow'+sep+rest;changed=''.join(raw);after,_,_=parse_initial(changed,schema_text);wanted=copy.deepcopy(initial);wanted['p1Hand'][0]['CardID']='wounding_blow_yellow';require(after==wanted,'Pitch mutant changed another initial field/UID')
  rewrites=0
  for a in plan['assertions']:
   if a.get('property')=='CardID'and a.get('value')=='wounding_blow_blue':
    require(a.get('type')=='card_property_equals'and a.get('mzId')=='p1Pitch-0'and a['step']>=2 and prop(a['step'],'p1Pitch-0','UniqueID',4),'Unexpected changed identity oracle')
    a['value']='wounding_blow_yellow';rewrites+=1
   require(a.get('cardID')!='wounding_blow_blue','Unexpected unreviewed membership rewrite')
  require(rewrites==2,'Expected exact paid/terminal pitch identity rewrites')
  target=dict(type='scalar_zone_equals',zone='p1Resources',value=2)
  plan.update(initial_text=changed,expected_failure_step=2,identity_replacement=dict(zone='p1Hand',index=0,sourceUID=4,old='wounding_blow_blue',new='wounding_blow_yellow',rewritten_identity_properties=rewrites))
 else:
  prefix=one(steps(type='computed_power_equals',mzId='p1CombatChain-0',value=5 if original==GO else 1)&steps(type='computed_go_again_equals',mzId='p1CombatChain-0',value=1)&steps(type='game_state_field',rootName='FaBSim',path=['window'],comparison='equals',valueJson='"REACTION"'),'real unmodified Reaction boundary')
  require(prefix==(10 if original==GO else 8),'Exact native Reaction prefix position')
  for key,value in [('CardID','surging_strike_red'if original==GO else'fry_blue'),('UniqueID',3),('Owner',1),('Controller',1),('Role','ATTACK'),('FromZone','Hand')]:require(any(prop(step,'p1CombatChain-0',key,value)for step in [3,prefix]),'Native actual attack identity '+key)
  require(state(prefix,'defenseValue',6 if original==GO else 0),'Retained native baseline defense')
  for key in ['p1Resources','p2Resources','p1ActionPoints','p2ActionPoints']:require(scalar(prefix,key,0),'Actual empty GA assets')
  require(scalar(prefix,'p1Health',20)and scalar(prefix,'p2Health',20),'Actual pre-damage GA health')
  for field in ['pendingPayment','resolvingReaction']:require(has(prefix,type='game_state_field',rootName='FaBSim',path=[field],comparison='missing_or_null'),'Actual no pending GA source work')
  require(has(prefix,type='decision_queue_empty',player='all')and has(prefix,type='zone_count',zone='Stack',value=0),'Actual GA empty stack/queues')
  tag='PEN_BLANK_ATTACK'if kind=='innate_ga_missing_refund'else'WTR_POWER:1'if kind=='innate_ga_printed_power'else'WTR_DEFENSE:-2'
  player=2 if kind=='innate_ga_full_block_defense_loss'else 1;uid=4 if player==2 else 3;cid='wounding_blow_blue'if player==2 else'surging_strike_red'if original==GO else'fry_blue'
  if original==GO:
   for i,u in enumerate([4,5]):
    for key,value in [('CardID','wounding_blow_blue'),('UniqueID',u),('Owner',2),('Controller',2)]:require(prop(7,'p2CombatChain-'+str(i),key,value),'Actual named Hand blocker identity')
   require(has(7,type='zone_count',zone='p2Hand',value=0)and state(7,'defenseValue',6),'Both real Hand declarations complete')
  if kind=='innate_ga_missing_refund':target=dict(type='computed_go_again_equals',mzId='p1CombatChain-0',value=1);failure=0
  elif kind=='innate_ga_printed_power':target=dict(type='computed_power_equals',mzId='p1CombatChain-0',value=1);failure=0
  else:
   require(state(14,'damageDealt',0)and state(14,'attackHit',False)and scalar(14,'p2Health',20),'Retained actual nohit witness')
   require(not any(prefix<a['step']<14 for a in assertions),'No omitted/intervening native assertions before first altered HP')
   target=dict(type='scalar_zone_equals',zone='p2Health',value=20);failure=14-prefix
  plan.update(prefix=prefix,actions=copy.deepcopy(actions[prefix:]),assertions=[dict(a,step=a['step']-prefix)for a in assertions if a['step']>=prefix],expected_failure_step=failure,
      object_effect=[dict(player=player,zone='CombatChain',cardID=cid,tag=tag,mode='add',innate_ga_source=dict(control=kind,sourceUID=uid,attackUID=3))])
 require(any(a['step']==plan['expected_failure_step']and all(a.get(k)==v for k,v in target.items())for a in plan['assertions']),'Intended retained native failure oracle absent')
 plan['target_assertion']=target
 require((actions,assertions)==before,'Planner mutated caller source inputs')
 return plan
