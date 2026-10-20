"""Author action-stage printed-rule model; no native runtime or game actions."""
from pathlib import Path
import copy,hashlib,json
H=Path(__file__).resolve().parent;R=Path(r'C:\xampp\htdocs\TCGEngine')
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()
load=lambda p:json.loads(p.read_text(encoding='utf-8-sig'))
p=H/'pilot4-correct-rule-proposed.json'
assert sha(p)=='477e175d50eaaa312da8db92675622a8149a040a5f45a3a7af15a3f932143856'
audit=load(H/'catalog12-and-source-audit.json');cards={x['card_id']:x for x in audit['identities']}
count=0;faults=0;results=[]
def check_all(qs,world):
    number=0
    for q in qs:
        number+=1;t=q.get('type');k=q.get('kind')
        if t=='scalar_zone_equals':value=world['scalar'][q['zone']];expected=q['value']
        elif t=='zone_count':value=len(world['zones'][q['zone']]);expected=q['value']
        elif t=='card_property_equals':z,idx=q['mzId'].rsplit('-',1);value=world['zones'][z][int(idx)][q['property']];expected=q['value']
        elif t=='decision_queue_empty':value=True;expected=True;assert q['player']=='all'
        elif k=='state':value=world['state'][q['key']];expected=q['value']
        elif k=='go_again':value=False;expected=q['value']
        elif k=='power':value=world['power'];expected=q['value']
        else:raise AssertionError(q)
        assert type(value)==type(expected) and value==expected,(q,value)
    return number
for row in load(p):
    cid=row['cardIDs'][0];entry=cards[cid];token=entry['token'];power=int(entry['printed']['power']);cost=int(entry['printed']['cost']);phase='INITIAL';n=0
    def obj(c,u,**other):return {'CardID':c,'UniqueID':str(u),'Owner':'1','Controller':'1',**{k:str(v) for k,v in other.items()}}
    world={'scalar':{'p1Health':20,'p2Health':20,'p1Resources':cost,'p2Resources':0,'p1ActionPoints':1,'p2ActionPoints':0,'PriorityPlayer':1,'RandomCounter':0},'zones':{z:[] for z in ['p1Hand','p1Pitch','p1Arena','p2Arena','Stack','p1CombatChain','p2CombatChain','p1Graveyard','p2Graveyard']},'state':{'window':'ACTION','pendingPayment':None,'resolvingReaction':None,'combatStep':'NONE','combatOpen':False},'power':power}
    world['zones']['p1Hand']=[obj(cid,3)]
    for s in row['steps']:
        if s['kind']=='click':
            assert phase=='INITIAL' and s=={'kind':'click','player':1,'mzID':'p1Hand-0'}
            world['zones']['p1Hand']=[];world['zones']['Stack']=[obj(cid,3,Kind='ATTACK',SourceZone='Hand',SourceUniqueID=3)]
            world['scalar'].update(p1Resources=0,p1ActionPoints=0,PriorityPlayer=2);world['state']['window']='PRIORITY';phase='PAID'
        elif s['kind']=='pass_until':
            target=s['until']
            if target=={'window':'ATTACK','stackEmpty':True}:
                assert phase=='PAID';world['zones']['Stack']=[];world['zones']['p1CombatChain']=[obj(cid,3,Role='ATTACK',FromZone='Hand',ChainLink=1)]
                world['state'].update(window='ATTACK',combatStep='ATTACK',combatOpen=True);world['scalar']['PriorityPlayer']=1;phase='ATTACK'
            elif target=={'combatStep':'DAMAGE'}:
                assert phase=='ATTACK';world['scalar']['p2Health']=20-power;world['state'].update(combatStep='DAMAGE',window='PRIORITY',damageDealt=power,attackHit=True,defenseValue=0)
                world['zones']['Stack']=[obj(cid,4,Kind='ABILITY',SourceZone='CombatChain',SourceUniqueID=3)];phase='TRIGGER_PENDING'
            elif target=={'priority':2}:
                assert phase=='TRIGGER_PENDING';world['scalar']['PriorityPlayer']=2;phase='RESPONDABLE'
            elif target=={'combatStep':'DAMAGE','stackEmpty':True}:
                assert phase=='RESPONDABLE';world['zones']['Stack']=[];world['zones']['p1Arena']=[obj(token,5,Status=2)]
                world['scalar']['PriorityPlayer']=1;world['state']['window']='DAMAGE';phase='TOKEN_CREATED'
            elif target=={'window':'RESOLUTION','stackEmpty':True}:
                assert phase=='TOKEN_CREATED';world['state'].update(window='RESOLUTION',combatStep='RESOLUTION');phase='RESOLUTION'
            elif target=={'window':'ACTION','stackEmpty':True}:
                assert phase=='RESOLUTION';world['zones']['p1CombatChain']=[];world['zones']['p1Graveyard']=[obj(cid,3)]
                world['state'].update(window='ACTION',combatStep='NONE',combatOpen=False);phase='CLOSED'
            else:raise AssertionError(target)
        elif s['kind']=='checks':
            n+=check_all(s['checks'],world)
            if phase=='TRIGGER_PENDING':
                # Current eager implementation is represented only as a fault
                # in this rule model, not as a recorded native failure.
                for fault in ['early-token','missing-layer','wrong-controller']:
                    broken=copy.deepcopy(world)
                    if fault=='early-token':broken['zones']['p1Arena']=[obj(token,4,Status=2)]
                    if fault=='missing-layer':broken['zones']['Stack']=[]
                    if fault=='wrong-controller':broken['zones']['Stack'][0]['Controller']='2'
                    try:check_all(s['checks'],broken)
                    except (AssertionError,IndexError):faults+=1
                    else:raise AssertionError('Fault not rejected')
            if phase=='TOKEN_CREATED':
                broken=copy.deepcopy(world);broken['zones']['p1Arena'][0]['CardID']='quicken'
                try:check_all(s['checks'],broken)
                except AssertionError:faults+=1
                else:raise AssertionError('Wrong token not rejected')
        else:raise AssertionError(s['kind'])
    assert phase=='CLOSED' and n==258;count+=n
    results.append({'slug':row['slug'],'native_expected_values_modeled':n,'printed_cost':cost,'printed_power':power,'expected_target_hp':20-power,'created_token':token,'source_uid':3,'trigger_uid':4,'token_uid':5})
hits=[]
for src in (R/'FaBSim/Custom').glob('*.php'):
    for line,text in enumerate(src.read_text(encoding='utf-8-sig').splitlines(),1):
        if any(base in text for base in ['destructive_deliberation','strike_gold','earth_form','lightning_form']):hits.append({'path':src.relative_to(R).as_posix(),'file_sha256':sha(src),'line':line,'text':text})
assert len(hits)==1 and hits[0]['path']=='FaBSim/Custom/UzuriBot.php'
out={'status':'AUTHOR_PRINTED_RULE_MODEL_PASS_NOT_INDEPENDENT_APPROVAL_OR_NATIVE_COVERAGE','pilot_sha256':sha(p),'native_expected_values_modeled':count,'semantic_faults_rejected':faults,'results':results,'native_custom_literal_search':hits,'search_interpretation':'No gameplay Custom queue exception mentions these12 baseIDs; the sole literal is unrelated Uzuri bot evaluation. Explicit compiled Hit callback is the relevant execution evidence. Broad indirect mechanisms are not assumed correct.','native_actions':0,'native_writes':0,'game_imports':0,'requires_independent_gate':True,'script_sha256':sha(Path(__file__))}
assert count==1032 and faults==16
dest=H/'pilot4-author-rule-model-validation.json';dest.write_text(json.dumps(out,ensure_ascii=True,indent=2)+'\n',encoding='utf-8')
print(json.dumps({'status':'PASS','oracles':count,'faults':faults,'report_sha256':sha(dest)}))
