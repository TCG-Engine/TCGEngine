"""Author printed-rule/action model. Does not load game runtime or builder."""
from pathlib import Path
import copy,hashlib,json
D=Path(__file__).resolve().parent
load=lambda p:json.loads(p.read_text(encoding='utf-8-sig'))
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()
P=D/'scenarios12-proposed.json'
assert sha(P)=='c37e689e8c6e2649f13628ee9ed2d59bc4a4f0ad22140de1d7d01d2b2eb44562'
stats={'yinti_yanti_red':(0,3,2),'yinti_yanti_yellow':(0,2,2),'yinti_yanti_blue':(0,1,2),'wounding_blow_red':(0,4,3)}
zones=['Hand','Arsenal','Pitch','Graveyard','CombatChain','Arena','Equipment','Weapons','Banish','Deck','Temp','Soul']
def card(cid,uid,p,**kw):return dict(CardID=cid,UniqueID=str(uid),Owner=str(p),Controller=str(p),**{k:str(v) for k,v in kw.items()})
def bare(o):return {k:o[k] for k in ['CardID','UniqueID','Owner','Controller']}
def simulate(case,fault=None):
 w={'z':{f'p{p}{z}':[] for p in [1,2] for z in zones}|{'Stack':[]},'s':{'p1Health':20,'p2Health':20,'p1Resources':case['resources'][0],'p2Resources':case['resources'][1],'p1ActionPoints':1,'p2ActionPoints':0,'TurnPlayer':1,'TurnNumber':2,'RandomCounter':0,'PriorityPlayer':1},'state':{'window':'ACTION','pendingPayment':None,'resolvingReaction':None},'q':{1:[],2:[]},'arcCards':{}}
 uid=3
 for z in case['zones']:
  assert z['properties']=={} and z['zone'] in ['Hand','Arena','Deck']
  w['z'][f'p{z["player"]}{z["zone"]}'].append(card(z['cardID'],uid,z['player']));uid+=1
 initialAura={p:bool(w['z'][f'p{p}Arena']) for p in [1,2]}
 def aura(p):
  if fault=='freeze-condition-at-start':return initialAura[p]
  if fault=='bonus-absent':return False
  if fault=='bonus-always':return True
  return any(o['CardID'] in ['ponder','spectral_shield','merciful_retribution_yellow'] for o in w['z'][f'p{p}Arena'])
 def power():
  a=w['z']['p1CombatChain'][0];v=stats[a['CardID']][1]
  return v+int(a['CardID'].startswith('yinti_yanti') and aura(1))
 def defense():return sum(stats[o['CardID']][2]+int(o['CardID'].startswith('yinti_yanti') and aura(2)) for o in w['z']['p2CombatChain'])
 def verify(step):
  n=0
  for q in step['checks']:
   n+=1;t=q.get('type');k=q.get('kind')
   if t=='scalar_zone_equals':actual=w['s'][q['zone']];want=q['value']
   elif t=='zone_count':actual=len(w['z'][q['zone']]);want=q['value']
   elif t=='card_property_equals':
    z,i=q['mzId'].rsplit('-',1);p=int(z[1]) if z.endswith('DecisionQueue') else None
    actual=(w['q'][p] if p else w['z'][z])[int(i)][q['property']];want=q['value']
   elif t=='decision_queue_empty':actual=all(not w['q'][p] for p in ([1,2] if q['player']=='all' else [q['player']]));want=True
   elif t=='game_state_field':
    actual=w['arcCards'] if q['path'][0]=='arcCards' else w['state']
    path=q['path'][1:] if q['path'][0]=='arcCards' else q['path']
    for part in path:actual=actual[part]
    want=json.loads(q['valueJson'])
   elif k=='state':actual=w['state'][q['key']];want=q['value']
   elif k=='power':actual=power();want=q['value']
   elif k=='go_again':actual=False;want=q['value']
   else:raise AssertionError(q)
   assert type(actual)==type(want) and actual==want,(case['slug'],step['checkpoint'],q,actual,fault)
  return n
 phase='INITIAL';oracles=0;actions=0;target=None
 for step in case['steps']:
  kind=step['kind']
  if kind=='checks':oracles+=verify(step);continue
  actions+=1
  if kind=='click':
   p=step['player'];mz=step['mzID'];assert w['s']['PriorityPlayer']==p
   if phase=='INITIAL':
    assert p==1 and mz=='p1Hand-0';o=w['z']['p1Hand'].pop(0)
    assert stats[o['CardID']][0]==0;w['s']['p1ActionPoints']-=1
    o.update(Kind='ATTACK',SourceZone='Hand',SourceUniqueID=o['UniqueID']);w['z']['Stack']=[o]
    w['state']['window']='PRIORITY';w['s']['PriorityPlayer']=2;phase='ATTACK_PAID'
   elif phase=='DEFEND':
    assert p==2 and mz=='p2Hand-0';o=w['z']['p2Hand'].pop(0)
    assert o['CardID'].startswith('yinti_yanti');o.update(Role='DEFENSE',FromZone='Hand',ChainLink='1',TurnEffects='[]')
    w['z']['p2CombatChain']=[o];w['state']['defenseValue']=defense();phase='BLOCKED'
   elif phase=='REACTION':
    if mz==f'p{p}Hero-0':
     assert case['heroes'][p-1]=='kano' and w['s'][f'p{p}Resources']>=3
     w['s'][f'p{p}Resources']-=3;w['z']['Stack']=[card('kano',uid,p,Kind='ABILITY',SourceZone='Hero',SourceUniqueID=p)];uid+=1;phase='KANO_PAID'
    elif mz==f'p{p}Banish-0':
     o=w['z'][f'p{p}Banish'].pop(0);assert o['CardID']=='aether_dart_blue' and w['arcCards'][o['UniqueID']]['instantTurn']==2
     o=bare(o)|dict(Kind='INSTANT',SourceZone='Banish',SourceUniqueID=o['UniqueID']);w['z']['Stack']=[o]
     w['state'].update(window='PITCH',pendingPayment={'player':p,'cost':0})
     w['q'][p]=[dict(Type='MZCHOOSE',Param='p1Hero-0&p2Hero-0')];phase='DART_TARGET'
    else:
     assert mz==f'p{p}Hand-0';o=w['z'][f'p{p}Hand'].pop(0)
     cost={'peace_of_mind_red':2,'merciful_retribution_yellow':4}[o['CardID']]
     if o['CardID']=='merciful_retribution_yellow':assert case['heroes'][p-1]=='prism'
     w['s'][f'p{p}Resources']-=cost;assert w['s'][f'p{p}Resources']>=0
     o.update(Kind='INSTANT',SourceZone='Hand',SourceUniqueID=o['UniqueID']);w['z']['Stack']=[o];phase='AURA_INSTANT_PAID'
    w['s']['PriorityPlayer']=3-p
   else:raise AssertionError((phase,step))
  elif kind=='answer':
   p=step['player'];q=w['q'][p][0];assert q['Type']==step['type'];assert step['value'] in q['Param'].split('&')
   if phase=='KANO_LOOK':
    o=w['z'][f'p{p}Temp'].pop();o['PlayableFromBanish']='1';w['z'][f'p{p}Banish']=[o]
    w['arcCards'][o['UniqueID']]={'instantTurn':2};phase='KANO_GRANTED'
   elif phase=='DART_TARGET':
    target=int(step['value'][1]);w['state'].update(window='REACTION',pendingPayment=None);w['s']['PriorityPlayer']=3-p;phase='DART_COMMITTED'
   elif phase=='WARD':
    assert p==target and w['z'][f'p{p}Arena'][0]['CardID']=='spectral_shield'
    if fault!='ward-not-destroyed':w['z'][f'p{p}Arena']=[]
    o=w['z']['Stack'].pop();owner=int(o['Owner']);w['z'][f'p{owner}Graveyard'].append(bare(o))
    w['state'].update(window='REACTION',resolvingReaction=None);w['s']['PriorityPlayer']=1;phase='REACTION'
   else:raise AssertionError((phase,step))
   w['q'][p]=[]
  elif kind=='pass_until':
   u=step['until']
   if 'decision' in u:
    p=u['decision']['player'];typ=u['decision']['type']
    if phase=='KANO_PAID':
     assert typ=='MZMAYCHOOSE';o=w['z'][f'p{p}Deck'].pop(0);assert o['CardID']=='aether_dart_blue'
     w['z'][f'p{p}Temp']=[o];w['q'][p]=[dict(Type=typ,Param=f'p{p}Temp-0')];phase='KANO_LOOK'
    elif phase=='DART_COMMITTED':
     assert typ=='MZCHOOSE' and p==target and w['z'][f'p{p}Arena'][0]['CardID']=='spectral_shield'
     w['q'][p]=[dict(Type=typ,Param=f'p{p}Arena-0')];o=w['z']['Stack'][0];controller=int(o['Controller'])
     w['state']['resolvingReaction']={'uid':int(o['UniqueID']),'owner':controller,'controller':controller,'kind':'INSTANT','resolutionClass':'PLAIN_SPELL','cardID':'aether_dart_blue','sourceMZ':'Stack-0'};phase='WARD'
    else:raise AssertionError((phase,u))
   elif u['window']=='ATTACK':
    assert phase=='ATTACK_PAID';o=bare(w['z']['Stack'].pop())|dict(Role='ATTACK',FromZone='Hand',ChainLink='1',TurnEffects='[]')
    w['z']['p1CombatChain']=[o];w['state']['window']='ATTACK';w['s']['PriorityPlayer']=1;phase='ATTACK'
   elif u['window']=='DEFEND_DECLARE':
    assert phase=='ATTACK';w['state']['window']='DEFEND_DECLARE';w['s']['PriorityPlayer']=2;phase='DEFEND'
   elif u['window']=='REACTION':
    if phase=='AURA_INSTANT_PAID':
     o=w['z']['Stack'].pop();p=int(o['Controller'])
     if o['CardID']=='peace_of_mind_red':w['z'][f'p{p}Graveyard'].append(bare(o));w['z'][f'p{p}Arena'].append(card('ponder',uid,p));uid+=1
     else:w['z'][f'p{p}Arena'].append(bare(o))
    elif phase=='KANO_GRANTED':w['z']['Stack']=[]
    else:assert phase in ['ATTACK','BLOCKED','REACTION'],phase
    w['state']['window']='REACTION';w['s']['PriorityPlayer']=u.get('priority',1);phase='REACTION'
   elif u['window']=='DAMAGE':
    assert phase=='REACTION';d=defense();damage=max(0,power()-d);w['s']['p2Health']-=damage;w['s']['PriorityPlayer']=1
    w['state'].update(window='DAMAGE',defenseValue=d,damageDealt=damage,attackHit=damage>0);phase='DAMAGE'
   elif u['window']=='RESOLUTION':assert phase=='DAMAGE';w['state']['window']='RESOLUTION';phase='RESOLUTION'
   elif u['window']=='ACTION':
    assert phase=='RESOLUTION'
    for p in [1,2]:w['z'][f'p{p}Graveyard'] += [bare(o) for o in w['z'][f'p{p}CombatChain']];w['z'][f'p{p}CombatChain']=[]
    w['state']={'window':'ACTION','pendingPayment':None,'resolvingReaction':None,'combatOpen':False};phase='CLOSED'
   else:raise AssertionError(u)
  else:raise AssertionError(kind)
 assert phase=='CLOSED' and not any(w['q'].values())
 assert w['s']['p1ActionPoints']==w['s']['p2ActionPoints']==0
 return {'slug':case['slug'],'intended_native_oracles':oracles,'supported_action_descriptors':actions,'terminal_hp':w['s']['p2Health'],'native_recorded':False}

cases=load(P);rows=[simulate(c) for c in cases];faults=0
for c in cases:
 for fault in ['freeze-condition-at-start','bonus-absent','bonus-always']+(['ward-not-destroyed'] if any(z['cardID']=='spectral_shield' for z in c['zones']) else []):
  try:simulate(c,fault)
  except AssertionError:faults+=1
  else:raise AssertionError(('Missed semantic fault',c['slug'],fault))
assert sum(x['intended_native_oracles'] for x in rows)==8892
assert sum(x['supported_action_descriptors'] for x in rows)==156
assert faults==42
report={'status':'AUTHOR_ACTION_DRIVEN_PRINTED_RULE_MODEL_PASS_NOT_INDEPENDENT_REVIEW','scenarios_sha256':sha(P),'pilot_subset_sha256':sha(D/'pilots4-exact-subset-proposed.json'),'native_intended_values_modeled':8892,'supported_action_descriptors_modeled':156,'semantic_faults_rejected':faults,'faults':['Frozen Aura condition at initial state','Never apply conditional bonus','Unconditionally apply bonus','Ward token not really destroyed (six cases)'],'results':rows,'model_bounds':['Independent of author builder and checkpoint names: only supported action descriptors drive transitions. No engine output/runtime is loaded.','Printed fixed stats and static Aura predicate set primary expectations. Known support card costs, source ownership and effect resolution set support protocol.','GenericKano look-await source lifecycle is not certified; managedDart source lifetime and mandatoryWard are explicit assertions.','This remains author-only proof. Independent exact spec review, serial native execution, actual saved semantics and current replay/control gates are pending.'],'native_runtime_imports':0,'native_actions':0,'native_writes':0,'script_sha256':sha(Path(__file__))}
out=D/'author-rule-model-validation.json';out.write_text(json.dumps(report,indent=2)+'\n',encoding='utf-8');print(json.dumps({'status':'PASS','values':8892,'actions':156,'faults':faults,'report_sha256':sha(out)}))
