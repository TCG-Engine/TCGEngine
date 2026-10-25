"""Three exact disposable NEXT grant controls; pure file guards/planning only."""
import copy,hashlib,json,pathlib,re
DEFINITIONS = {'weapon_next_missing_grant': {'fixture': 'diagnostic-en-garde-red-hand-floating-printed-power',
                               'reason': 'computed power to equal 5, got 2',
                               'files': {'initial_gamestate.txt': '6f308023356340d6d03d853720e0016ce837163bf9b2cbfc284bb135929e5288',
                                         'actions.json': 'fbeb8abce8eb8cc16afbd2cf43b429b25e91676124d354da24b8f0154daff8bf',
                                         'assertions.json': 'ed8975d4dde9d2dd60acfc4187778a030932c07bbf3cd68060bc1deaa34c978c',
                                         'expected_final_gamestate.txt': '18e3a173515eb791fb726bc06f1ce25696652ea2580995225280d52137552d85',
                                         'meta.json': 'c530461fa9f3c07cf37774cef28e357b8a4b69e606aadf20a1e6c05fd41886d7'},
                               'behavior': '759d739b4175c7ae0bfaa6d17d550e6603f4cc4bfc172ffc2cad3f85536527e6',
                               'omissions': [],
                               'remove_effect': 'NEXT_WEAPON',
                               'wanted_power': 5,
                               'wrong_power': 2},
 'dagger_next_missing_grant': {'fixture': 'card-cut-deep-red-hand-floating',
                               'reason': 'computed power to equal 5, got 1',
                               'files': {'initial_gamestate.txt': '7e612220cdc2aa8a97832e14a36632da0c95f05134f768112e40dad6714785b5',
                                         'actions.json': 'fbeb8abce8eb8cc16afbd2cf43b429b25e91676124d354da24b8f0154daff8bf',
                                         'assertions.json': '9e5ff7d26d6d2e5b13cf25503ea2d54fc6c1b62eed96107489f97ac22c80c7a6',
                                         'expected_final_gamestate.txt': '4fd443cc517f4d6dafa07dfba348bee5acf14920a983917c1260bb3021befb6e',
                                         'meta.json': 'ac1bcc5cd54fc379b0d44b5bbd2708e871fb40645718541ce8cbdcd2e00be394'},
                               'behavior': 'f6f9a96d96319000100792072ede25bef2ef758178fdcd4c03cec6192e10d73b',
                               'omissions': [{'step': 4,
                                              'type': 'card_property_equals',
                                              'viewerPlayerID': 1,
                                              'mzId': 'Stack-0',
                                              'property': 'TurnEffects',
                                              'value': '["WTR_POWER:4"]'}],
                               'remove_effect': 'HNT_NEXT',
                               'wanted_power': 5,
                               'wrong_power': 1},
 'axe_next_missing_grant': {'fixture': 'card-felling-swing-red-hand-floating',
                            'reason': 'computed power to equal 8, got 2',
                            'files': {'initial_gamestate.txt': '7af040f435e31dcfcfe0bd58d86e3b4e9be81a6b0c3167d3f7390484cd27c399',
                                      'actions.json': 'fbeb8abce8eb8cc16afbd2cf43b429b25e91676124d354da24b8f0154daff8bf',
                                      'assertions.json': 'c7cef54abb641a95488233f203d5d18bbc1c8509b3531ef1bcf8188d80bb06ab',
                                      'expected_final_gamestate.txt': '49cb7ad138ffaef768ed33acd71ea934e30920ad0f9f625e3ed8795e800ce8e8',
                                      'meta.json': '9f6b23f6b376f246bb806abdc05821332c5b969f226648e10ee7a5c6384a15af'},
                            'behavior': 'cb31e64e7065490ad038b3ed0e5cb498824d953dda53a2d751c7c5a71c9ae95a',
                            'omissions': [{'step': 4,
                                           'type': 'card_property_equals',
                                           'viewerPlayerID': 1,
                                           'mzId': 'Stack-0',
                                           'property': 'TurnEffects',
                                           'value': '["WTR_POWER:6"]'}],
                            'remove_effect': 'DYN_NEXT',
                            'wanted_power': 8,
                            'wrong_power': 2}}
CONTROL_KEYS = {(v['fixture'],v['reason']) for v in DEFINITIONS.values()}

def build_weapon_next_plan(folder, original, reason, actions, assertions):
    matches=[(k,v) for k,v in DEFINITIONS.items() if (v['fixture'],v['reason'])==(original,reason)]
    if len(matches)!=1:raise ValueError('Unknown weapon-next control key')
    control,d=matches[0];folder=pathlib.Path(folder)
    for n,h in d['files'].items():
        if hashlib.sha256((folder/n).read_bytes()).hexdigest()!=h:raise ValueError('Weapon-next source differs: '+n)
    bh=hashlib.sha256()
    for n in ['initial_gamestate.txt','actions.json','assertions.json','expected_final_gamestate.txt']:
        bh.update(n.encode());bh.update((folder/n).read_bytes())
    if bh.hexdigest()!=d['behavior']:raise ValueError('Weapon-next behavior differs')
    if actions!=json.loads((folder/'actions.json').read_text(encoding='utf-8-sig')) or assertions!=json.loads((folder/'assertions.json').read_text(encoding='utf-8-sig')):raise ValueError('Weapon-next supplied source actions/oracles changed')
    if len(actions)!=15:raise ValueError('Expected exact fifteen-action weapon pilot')
    if [(a['playerID'],a['mode'],a['cardID']) for a in actions[:6]]!=[(1,10002,'p1Hand-0!FSM'),(2,10001,'!CustomInput!Pass'),(1,10001,'!CustomInput!Pass'),(1,10002,'p1Weapons-0!FSM'),(2,10001,'!CustomInput!Pass'),(1,10001,'!CustomInput!Pass')]:raise ValueError('Unexpected supported weapon action sequence')
    prefix=3;target={'step':6,'type':'computed_power_equals','viewerPlayerID':1,'mzId':'p1CombatChain-0','value':d['wanted_power']}
    if assertions.count(target)!=1:raise ValueError('Expected exact unique first printed-power witness')
    retained=[]
    for omission in d['omissions']:
        if assertions.count(omission)!=1:raise ValueError('Expected exact unique allowed proxy-tag omission')
    for a in assertions:
        if a['step']<prefix:continue
        if a in d['omissions']:continue
        retained.append(dict(a,step=a['step']-prefix))
    shifted=dict(target,step=3)
    if retained.count(shifted)!=1 or any(a.get('type')=='computed_power_equals' and a['step']<3 for a in retained):raise ValueError('Numerical first-failure boundary differs')
    return dict(control_id=control,prefix=3,remove_effect=d['remove_effect'],actions=copy.deepcopy(actions[3:]),assertions=retained,expected_failure_step=3,target_assertion=shifted,omitted_assertions=copy.deepcopy(d['omissions']),original_initial_sha256=d['files']['initial_gamestate.txt'],behavior_sha256=d['behavior'])

def intended_failure(returncode, stdout, plan):
    first=re.search(r'(Initial|Step [0-9]+) assertion failed:([^\r\n]*)',stdout)
    d=DEFINITIONS.get(plan.get('control_id'))
    return (d is not None and returncode==1 and first is not None and first.group(1)=='Step 3'
            and d['reason'] in first.group(2) and 'snapshot mismatch' not in stdout)
