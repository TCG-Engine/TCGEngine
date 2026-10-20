"""Pure exact CnC pending-hit-layer disposable-control planner; no engine imports."""
import copy,hashlib,json,pathlib,re
SOURCE_SLUG = 'probe-command-and-conquer-red-mandatory-hit-trigger-arsenal-present'
SOURCE_FILES = {'initial_gamestate.txt': '5fb449c3126d84949347052689f5c43486e60b491879701463158d446945ad94',
 'actions.json': '2bcedee0bb80d40da3960acb47f4e26a5feebfb9fd44631bcef566e6b4c0eff6',
 'assertions.json': '24e76b848deb92d9931d0370790ec5af6adbbabfdcafe4d214c41b443d494d93',
 'expected_final_gamestate.txt': 'bdb9dfecadb5bab6ad4fa58d711e382204aebde0d187e39e992bc711b33377af',
 'meta.json': '1257eab23687176445922d0bd4e2957394f8b05a6aaa12e04161f7ce84ea6657'}
SOURCE_BEHAVIOR = 'bed06a4057ac2db1b7c3a10ebe62f5002d753fe6d85213e6f3fa7ea8dc4fa7a2'
DEFINITIONS = {'cnc_missing_hit_layer': {'reason': 'Expected Stack count 1, got 0.',
                           'step': 0,
                           'target': {'step': 0,
                                      'type': 'zone_count',
                                      'viewerPlayerID': 1,
                                      'zone': 'Stack',
                                      'value': 1}},
 'cnc_premature_arsenal_destruction': {'reason': 'Expected p2Arsenal count 1, '
                                                 'got 0.',
                                       'step': 0,
                                       'target': {'step': 0,
                                                  'type': 'zone_count',
                                                  'viewerPlayerID': 1,
                                                  'zone': 'p2Arsenal',
                                                  'value': 1}},
 'cnc_wrong_captured_victim': {'reason': 'Expected p2Arsenal count 0, got 1.',
                               'step': 2,
                               'target': {'step': 2,
                                          'type': 'zone_count',
                                          'viewerPlayerID': 1,
                                          'zone': 'p2Arsenal',
                                          'value': 0}}}
CONTROL_KEYS = {(SOURCE_SLUG,d['reason']) for d in DEFINITIONS.values()}
def build_cnc_hit_plan(folder,original,reason,actions,assertions):
    matches=[(k,d) for k,d in DEFINITIONS.items() if original==SOURCE_SLUG and d['reason']==reason]
    if len(matches)!=1:raise ValueError('Unknown CnC control source/key')
    kind,d=matches[0];folder=pathlib.Path(folder)
    for name,h in SOURCE_FILES.items():
        if hashlib.sha256((folder/name).read_bytes()).hexdigest()!=h:raise ValueError('CnC raw source changed: '+name)
    bh=hashlib.sha256()
    for name in ['initial_gamestate.txt','actions.json','assertions.json','expected_final_gamestate.txt']:
        bh.update(name.encode());bh.update((folder/name).read_bytes())
    if bh.hexdigest()!=SOURCE_BEHAVIOR:raise ValueError('CnC behavior changed')
    if actions!=json.loads((folder/'actions.json').read_text(encoding='utf-8-sig')) or assertions!=json.loads((folder/'assertions.json').read_text(encoding='utf-8-sig')):raise ValueError('Supplied CnC source differs')
    if len(actions)!=16 or actions[0]['mode']!=10002 or actions[0]['playerID']!=1 or actions[0]['cardID']!='p1Hand-0!FSM':raise ValueError('Expected real original CnC source action')
    if [(a['playerID'],a['mode'],a['cardID']) for a in actions[1:]]!=[(p,10001,'!CustomInput!Pass') for p in [2,1,1,2,2,1,2,1,2,1,2,1,2,1,2]]:raise ValueError('Expected exact real CnC passes')
    retained=[dict(a,step=a['step']-10) for a in assertions if a['step']>=10]
    if retained[0]!={'step':0,'type':'zone_count','viewerPlayerID':1,'zone':'Stack','value':1} or d['target'] not in retained:raise ValueError('First meaningful CnC native oracle differs')
    return dict(control_id=kind,prefix=10,actions=copy.deepcopy(actions[10:]),assertions=retained,expected_failure_step=d['step'],target_assertion=copy.deepcopy(d['target']),original_initial_sha256=SOURCE_FILES['initial_gamestate.txt'],behavior_sha256=SOURCE_BEHAVIOR,omitted_assertions=[])
def intended_failure(returncode,stdout,plan):
    first=re.search(r'(Initial|Step [0-9]+) assertion failed:([^\r\n]*)',stdout)
    d=DEFINITIONS.get(plan.get('control_id'))
    return d is not None and returncode==1 and first is not None and first.group(1)==('Initial' if d['step']==0 else 'Step '+str(d['step'])) and d['reason'] in first.group(2) and 'snapshot mismatch' not in stdout
