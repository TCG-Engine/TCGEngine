"""One additional-cost semantic fault; pure planning and exact source guards."""
import hashlib, json, pathlib, re
CONTROL_ID = 'mandatory_discard_missing_cost_becomes_payable'
FIXTURE = 'card-savage-swing-red-mandatory-discard-hand-no-spare-despite-funded-assets'
REASON = 'Expected p1Hand count 1, got 0.'
CONTROL_KEY = (FIXTURE, REASON)
SOURCE_FILES = {'initial_gamestate.txt': 'a3bc6ff863d3e5b6a378acfba155fddc2666d82e2439f56508656cb49de28406', 'actions.json': '552f566dbb3f92cd5ba073367ebc69489d64068f7398d09f17f8cf93f0e2025d', 'assertions.json': '03418b4e563e6758155f9c264978a5f77f778b0906923272560b99408a827dfe', 'expected_final_gamestate.txt': '947f93cafe4c6f085d8d6dee59d752a5493737fb21b8cf87ec495d811b188f13', 'meta.json': '57d633b14fe8221fa72cbae1f04efd958f58fdc1de19f59ca59ff6a0d72cfbd6'}
BEHAVIOR_SHA256 = '76f449b2216d01f3b6041096ca3dc91c988793e3784939c1f9f8533a68cab42d'

def verify_source_fixture(folder, original, actions, assertions):
    if original != FIXTURE:
        raise ValueError('Unexpected mandatory-discard source fixture')
    folder=pathlib.Path(folder)
    for name, expected in SOURCE_FILES.items():
        if hashlib.sha256((folder/name).read_bytes()).hexdigest()!=expected:
            raise ValueError('Mandatory-discard source file differs: '+name)
    digest=hashlib.sha256()
    for name in list(SOURCE_FILES)[:4]:
        digest.update(name.encode());digest.update((folder/name).read_bytes())
    if digest.hexdigest()!=BEHAVIOR_SHA256:
        raise ValueError('Mandatory-discard behavior differs')
    if actions!=json.loads((folder/'actions.json').read_text(encoding='utf-8-sig')):
        raise ValueError('Mandatory-discard actions were changed')
    if assertions!=json.loads((folder/'assertions.json').read_text(encoding='utf-8-sig')):
        raise ValueError('Mandatory-discard oracles were changed')
    if len(actions)!=1 or len(assertions)!=19 or assertions[0]!={'step':1,'type':'zone_count','viewerPlayerID':1,'zone':'p1Hand','value':1}:
        raise ValueError('Mandatory-discard first native oracle differs')
    return {'add_hand':[{'player':1,'cardID':'wounding_blow_blue'}]}

def intended_failure(returncode, stdout):
    first=re.search(r'(Initial|Step [0-9]+) assertion failed:([^\r\n]*)',stdout)
    return (returncode==1 and first is not None and first.group(1)=='Step 1'
            and REASON in first.group(2) and 'snapshot mismatch' not in stdout)
