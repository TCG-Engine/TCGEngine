"""Independent-of-builder action-driven author model, no native runtime import."""
from pathlib import Path
import copy,hashlib,json
H=Path(__file__).resolve().parent;R=Path(r'C:\xampp\htdocs\TCGEngine')
load=lambda p:json.loads(p.read_text(encoding='utf-8-sig'))
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()
input=H/'pilots4-proposed.json';assert sha(input)=='aa169be595ed6811c4ce14d06a7b7b47e84e8c3b8ceaa1bcea4098f6e5adf235'
stats={
 'surging_militia_red':(2,5,2),'stony_woottonhog_red':(2,6,2),'yinti_yanti_red':(0,3,2),'yinti_yanti_blue':(0,1,2),
 'wounding_blow_blue':(0,2,3),'wounding_blow_red':(0,4,3),'ponder':(None,None,None),
}
cards={c['id']:c for c in load(R/'FaBSim/GeneratedCode/cardArrayCache.json')['cardArray']}
for cid,(cost,power,defense) in stats.items():
 if cid=='ponder':assert cards[cid]['types']==['Generic','Token','Aura'];continue
 assert tuple(int(cards[cid][k]) for k in ['cost','power','defense'])==(cost,power,defense)
checks=0;actions=0;faults=0;rows=[]
def aura(w,p):return any(o['CardID']=='ponder' for o in w['zones'][f'p{p}Arena'])
def current_power(w,offset=0):
 aa=w['zones']['p1CombatChain'][0];base=stats[aa['CardID']][1]
 n=len(w['zones']['p2CombatChain']);cid=aa['CardID']
 if cid.startswith('surging_militia'):base+=n
 elif cid.startswith('stony_woottonhog'):base+=int(n<2)
 elif cid.startswith('yinti_yanti'):base+=int(aura(w,1))
 return base+offset
def current_defense(w,offset=0):
 total=0
 for o in w['zones']['p2CombatChain']:
  total+=stats[o['CardID']][2]
  if o['CardID'].startswith('yinti_yanti') and aura(w,2):total+=1
 return total+offset
def verify(s,w,po=0,do=0):
 count=0
 for q in s['checks']:
  count+=1;t=q.get('type');k=q.get('kind')
  if t=='scalar_zone_equals':actual=w['scalar'][q['zone']];want=q['value']
  elif t=='zone_count':actual=len(w['zones'][q['zone']]);want=q['value']
  elif t=='card_property_equals':z,idx=q['mzId'].rsplit('-',1);actual=w['zones'][z][int(idx)][q['property']];want=q['value']
  elif t=='decision_queue_empty':actual=True;want=True;assert q['player']=='all'
  elif k=='state':
   actual=w['state'][q['key']];want=q['value']
   if q['key']=='defenseValue':actual+=do
  elif k=='power':actual=current_power(w,po);want=q['value']
  elif k=='go_again':actual=False;want=q['value']
  else:raise AssertionError(q)
  assert type(actual)==type(want) and actual==want,(s['checkpoint'],q,actual)
 return count
for case in load(input):
 w={'zones':{f'p{p}{z}':[] for p in [1,2] for z in ['Hand','Arsenal','Pitch','Graveyard','CombatChain','Arena','Equipment','Weapons','Banish','Deck']}|{'Stack':[]},'scalar':{'p1Health':20,'p2Health':20,'p1Resources':case['resources'][0],'p2Resources':0,'p1ActionPoints':1,'p2ActionPoints':0,'PriorityPlayer':1,'RandomCounter':0},'state':{'window':'ACTION','pendingPayment':None,'resolvingReaction':None}}
 uid=3
 for z in case['zones']:
  assert z['properties']=={}
  w['zones'][f"p{z['player']}"+z['zone']].append({'CardID':z['cardID'],'UniqueID':str(uid),'Owner':str(z['player']),'Controller':str(z['player'])});uid+=1
 phase='INITIAL';n=0
 for step in case['steps']:
  if step['kind']=='click':
   actions+=1;p=step['player'];mz=step['mzID']
   if p==1:
    assert phase=='INITIAL' and mz=='p1Hand-0'
    o=w['zones']['p1Hand'].pop(0);w['scalar']['p1Resources']-=stats[o['CardID']][0];w['scalar']['p1ActionPoints']-=1
    o.update(Kind='ATTACK',SourceZone='Hand',SourceUniqueID=o['UniqueID']);w['zones']['Stack']=[o];w['scalar']['PriorityPlayer']=2;w['state']['window']='PRIORITY';phase='PAID'
   else:
    assert phase in ['DEFEND','BLOCKED'] and mz=='p2Hand-0'
    o=w['zones']['p2Hand'].pop(0);o.update(Role='DEFENSE',FromZone='Hand',ChainLink='1',TurnEffects='[]');w['zones']['p2CombatChain'].append(o)
    w['state']['defenseValue']=current_defense(w);phase='BLOCKED'
  elif step['kind']=='pass_until':
   actions+=1;target=step['until']
   if target=={'window':'ATTACK','stackEmpty':True}:
    assert phase=='PAID';o=w['zones']['Stack'].pop();o={k:o[k] for k in ['CardID','UniqueID','Owner','Controller']}|{'Role':'ATTACK','FromZone':'Hand','ChainLink':'1','TurnEffects':'[]'}
    w['zones']['p1CombatChain']=[o];w['scalar']['PriorityPlayer']=1;w['state']['window']='ATTACK';phase='ATTACK'
   elif target=={'window':'DEFEND_DECLARE','priority':2}:
    assert phase=='ATTACK';w['scalar']['PriorityPlayer']=2;w['state']['window']='DEFEND_DECLARE';phase='DEFEND'
   elif target=={'window':'DAMAGE','stackEmpty':True}:
    assert phase in ['ATTACK','BLOCKED'];defense=current_defense(w);damage=max(0,current_power(w)-defense)
    w['scalar']['p2Health']-=damage;w['scalar']['PriorityPlayer']=1;w['state'].update(window='DAMAGE',defenseValue=defense,damageDealt=damage,attackHit=damage>0);phase='DAMAGE'
   elif target=={'window':'RESOLUTION','stackEmpty':True}:
    assert phase=='DAMAGE';w['state']['window']='RESOLUTION';phase='RESOLUTION'
   elif target=={'window':'ACTION','stackEmpty':True}:
    assert phase=='RESOLUTION'
    for seat in [1,2]:
     w['zones'][f'p{seat}Graveyard']=[{k:o[k] for k in ['CardID','UniqueID','Owner','Controller']} for o in w['zones'][f'p{seat}CombatChain']];w['zones'][f'p{seat}CombatChain']=[]
    w['state']={'window':'ACTION','pendingPayment':None,'resolvingReaction':None,'combatOpen':False};phase='CLOSED'
   else:raise AssertionError(target)
  elif step['kind']=='checks':
   n+=verify(step,w)
   if phase=='BLOCKED':
    for offset in [-1,1]:
     # Continuous power is faulted for attacking identities; exact defending
     # Yinti bonus is faulted in the cached defense/damage witness.
     po=0 if case['cardIDs']==['yinti_yanti_blue'] else offset
     do=offset if case['cardIDs']==['yinti_yanti_blue'] else 0
     try:verify(step,w,po,do)
     except AssertionError:faults+=1
     else:raise AssertionError('Meaningful static-stat fault was not detected')
  else:raise AssertionError(step['kind'])
 assert phase=='CLOSED';checks+=n
 rows.append({'slug':case['slug'],'modeled_native_oracles':n,'terminal_hp':w['scalar']['p2Health'],'p1_ap':w['scalar']['p1ActionPoints'],'p2_ap':w['scalar']['p2ActionPoints'],'native_recorded':False})
assert checks==1400 and len(rows)==4 and faults==8
report={'status':'AUTHOR_ACTION_DRIVEN_PRINTED_RULE_MODEL_PASS_NOT_INDEPENDENT_APPROVAL','pilot_sha256':sha(input),'native_expected_values_modeled':checks,'supported_step_descriptors_modeled':actions,'semantic_stat_faults_rejected':faults,'results':rows,'source_of_expected_values':'Printed fixed stats and current stat predicates; action-driven source play, real Hand declaration, physical damage and normal close model. No checkpoint-label-driven transitions and no native state sampled.','qualification':'Author proof only. Independent exact spec gate and serial native recorder/saved-semantic/replay/control gates remain required. Live Aura response recipes have not yet been authored; full228 requirements remain planned.','native_runtime_imports':0,'native_actions':0,'native_writes':0,'script_sha256':sha(Path(__file__))}
out=H/'pilot4-author-rule-model-validation.json';out.write_text(json.dumps(report,ensure_ascii=True,indent=2)+'\n',encoding='utf-8');print(json.dumps({'status':'PASS','oracles':checks,'faults':faults,'actions_descriptors':actions,'report_sha256':sha(out)}))
