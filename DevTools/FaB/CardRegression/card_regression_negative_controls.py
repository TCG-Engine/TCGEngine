"""Mutate only disposable fixture copies and demand semantic assertion failures."""
import json,pathlib,subprocess,shutil,uuid,os
repo=pathlib.Path(os.environ.get('FAB_REGRESSION_REPO',next((p for p in pathlib.Path(__file__).resolve().parents if (p/'Core').is_dir()),pathlib.Path.cwd())))
fixtures=repo/'Tests/Integration/FaBSim'
php=shutil.which('php')
specs=[
 ('card-sigil-of-solace-red-gain','sigil_of_solace_red','sigil_of_solace_blue','p1Health to equal 13, got 11'),
 ('card-wounding-blow-red-unblocked','wounding_blow_red','wounding_blow_yellow','p2Health to equal 16, got 17'),
 ('card-head-jab-red-unblocked','head_jab_red','wounding_blow_yellow','p1ActionPoints to equal 1, got 0'),
 ('card-harmonized-kodachi-zero-cost-pitch','wounding_blow_blue','raging_onslaught_blue','p1ActionPoints to equal 1, got 0'),
 ('card-sink-below-red-exchange',None,None,'Expected card wounding_blow_blue to exist in p2Hand'),
 ('card-goliath-gauntlet-destroy-buff',None,None,'p2Health to equal 11, got 13'),
 ('card-enlightened-strike-red-mode-draw',None,None,'p1Hand count 1, got 0'),
 ('card-enlightened-strike-red-mode-power',None,None,'p2Health to equal 13, got 15'),
 ('card-enlightened-strike-red-mode-go-again',None,None,'p1ActionPoints to equal 1, got 0'),
 ('card-enlightened-strike-red-missing-additional-cost',None,None,'Expected card enlightened_strike_red to exist in p1Hand'),
 ('card-bravo-cost-three-dominate',None,None,'p2CombatChain count 1, got 2'),
 ('card-scalding-rain-red-target-2','scalding_rain_red','scalding_rain_yellow','p2Health to equal 16, got 17'),
 ('card-scalding-rain-red-target-2','wounding_blow_blue','wounding_blow_red','p1Resources to equal 2, got 0'),
 ('card-scalding-rain-red-target-2',None,None,'p2Health to equal 16, got 20'),
 ('card-scalding-rain-red-barrier-pay',None,None,'p2Health to equal 17, got 16'),
 ('card-scalding-rain-red-barrier-decline',None,None,'p2Health to equal 16, got 17'),
 ('card-nimblism-red-cost-two-excluded-preserves-buff',None,None,'p2Health to equal 8, got 11'),
 ('card-sloggism-red-cost-two-boundary-next-only',None,None,'p2Health to equal 9, got 15'),
 ('card-potion-of-strength-blue-weapon-included-next-only',None,None,'p2Health to equal 17, got 19'),
 ('card-unmovable-red-arsenal-paid-blue-three',None,None,'p2Health to equal 19, got 18'),
 ('card-unmovable-red-hand-paid-blue-three',None,None,'p2Health to equal 18, got 19'),
 ('card-fate-foreseen-red-hand-opt-one-top-then-draw',None,None,'Expected card wounding_blow_red to exist in p2Hand'),
 ('card-sic-em-shot-red-equipped-bow-facedown-allowed','death_dealer','talishar_the_lost_prince','p1Arsenal count 0, got 1'),
 ('card-snatch-red-hit-trigger-response-window',None,None,'p1Hand count 1, got 0'),
 ('card-scar-for-a-scar-red-lower-own-heal-removes-condition',None,None,"TurnEffects to equal '[]', got '[\"GO_AGAIN\"]'"),
 ('card-scar-for-a-scar-red-equal-opponent-heal-enables-condition',None,None,"TurnEffects to equal '[\"GO_AGAIN\"]', got '[]'"),
 ('card-wounded-bull-red-lower-own-heal-removes-condition',None,None,"TurnEffects to equal '[]', got '[\"WTR_WOUNDED_BULL\"]'"),
 ('card-wounded-bull-red-equal-opponent-heal-enables-condition',None,None,"TurnEffects to equal '[\"WTR_WOUNDED_BULL\"]', got '[]'"),
 ('card-energy-potion-blue-activation-deploy-then-instant-at-zero-ap',None,None,'p1Resources to equal 2, got 0'),
 ('card-timesnap-potion-blue-activation-pay-one-gain-two-two-attacks',None,None,'p1ActionPoints to equal 2, got 0'),
 ('card-heartened-cross-strap-activation-cost-three-discount-two',None,None,'p1Resources to equal 2, got 0'),
 ('card-snapdragon-scalers-activation-cost-one-boundary-grants-go-again',None,None,'p1ActionPoints to equal 1, got 0'),
 ('card-breaking-scales-activation-combo-label-without-active-condition',None,None,'p2Health to equal 16, got 17'),
 ('card-snapdragon-scalers-activation-cost-one-boundary-grants-go-again',None,None,'computed go again to equal 0, got 1'),
 ('card-come-to-fight-red-cost-3-attack-no-granted-go-again',None,None,'p2Health to equal 10, got 13'),
 ('card-earthlore-surge-red-weapon-excluded-preserves-buff',None,None,'p2Health to equal 5, got 10'),
]
specs.extend([
 ('compat-reaction-rout-red-reprise-return-defender',None,None,'Expected card rout_red to exist in Stack'),
 ('compat-reaction-stroke-of-foresight-red-reprise-draw-put-top',None,None,"CardID to equal 'wounding_blow_yellow', got 'raging_onslaught_blue'"),
 ('compat-reaction-roaring-beam-yellow-empty-soul-charge-self',None,None,'p1Graveyard count 0, got 1'),
 ('card-quicken-attack-action-trigger-timing','quicken','copper','Expected Stack count 2, got 1'),
 ('card-quicken-attack-action-trigger-timing',None,None,'computed go again to equal 1, got 0'),
 ('card-quicken-attack-action-trigger-timing',None,None,"TurnEffects to equal '[]', got '[\"GO_AGAIN\"]'"),
 ('card-flock-of-the-feather-walkers-red-opponent-response-before-quicken-created',None,None,'p1Arena count 1, got 0'),
 ('card-burgeoning-red-arsenal-face-down-unblocked-float',None,None,'p2Health to equal 13, got 14'),
 ('card-power-play-red-arsenal-face-down-unblocked-float',None,None,'p2Health to equal 12, got 17'),
])
specs.extend([
 ('card-pummel-red-two-reactions-two-hit-triggers-two-discards',None,None,'Expected Stack count 2, got 1'),
 ('card-pummel-red-hand-cost-two-hit-defender-chooses-0',None,None,"Expected p2DecisionQueue-0.Type to equal 'MZCHOOSE', got ''"),
 ('card-razor-reflex-red-hand-cost-zero-hit-trigger-go-again',None,None,'computed go again to equal 0, got 1'),
 ('card-razor-reflex-red-hand-cost-zero-hit-trigger-go-again',None,None,'computed go again to equal 1, got 0'),
])

def granted_hit_control_plan(original,reason,actions,assertions):
 from granted_hit_control_plan import build_granted_hit_plan
 return build_granted_hit_plan(original,reason,actions,assertions)

specs.extend([
 ('card-ice-bolt-red-opponent-barrier-pay-floating',None,None,'Expected FaBSim state ["arcaneDealt"] to equal JSON {"1":{"2":4}}, got {"2":{"2":4}}'),
 ('card-ice-bolt-red-opponent-barrier-pay-floating',None,None,'Expected FaBSim state ["resolvingReaction"] to be missing or null.'),
 ('card-ice-bolt-red-opponent-barrier-pay-floating',None,None,'Expected Stack count 1, got 0'),
])

specs.extend([
 ('card-muscle-mutt-yellow-defends-exactly-two-partial-damage',None,None,'p2Health to equal 18, got 19'),
 ('card-muscle-mutt-yellow-defends-exactly-two-partial-damage',None,None,'p2Health to equal 18, got 17'),
])

specs.extend([
 ('card-ironrot-gauntlet-blade-break-fully-blocked-no-hit-still-destroys',None,None,'Expected card ironrot_gauntlet to exist in p2Graveyard'),
 ('card-ironrot-gauntlet-blade-break-fully-blocked-no-hit-still-destroys',None,None,'p2Health to equal 20, got 19'),
 ('card-ironrot-gauntlet-blade-break-fully-blocked-no-hit-still-destroys',None,None,'Expected card ironrot_gauntlet to exist in p2CombatChain'),
])

specs.extend([
 ('card-critical-strike-red-baseline-hand-red-pitch-exact', None, None, 'p1Resources to equal 0, got 1'),
 ('card-brutal-assault-red-baseline-hand-red-then-red-pitch', None, None, 'p1Resources to equal 0, got 2'),
 ('card-critical-strike-blue-baseline-defends-exactly-3-partial-damage', None, None, 'Expected FaBSim state ["damageDealt"] to equal JSON 1, got 0.'),
 ('card-critical-strike-blue-baseline-defends-exactly-3-partial-damage', None, None, 'Expected FaBSim state ["damageDealt"] to equal JSON 1, got 2.'),
 ('card-smash-with-big-tree-blue-baseline-no-defense-value-cannot-declare-block', None, None, 'Expected FaBSim state ["defenseValue"] to equal JSON 0, got 3.'),
 ('card-rusty-harpoon-blue-baseline-arsenal-facedown-floating', None, None, 'Expected FaBSim state ["window"] to equal JSON "PRIORITY", got "ACTION".'),
])

specs.extend([
 ('card-aetherstorm-wellingtons-pay-floating-exact', None, None, 'state ["arcaneDealt"] to equal JSON {"1":{"2":1}}, got {"1":{"2":3}}'),
 ('card-aetherstorm-wellingtons-pay-blue-pitch', None, None, 'p2Resources to equal 1, got 0'),
 ('card-aetherstorm-wellingtons-intrinsic-barrier-while-defending-real-kano-instant', None, None, "p2DecisionQueue-0.Param to equal 'p2CombatChain-0', got ''"),
])

specs.extend([
 ('card-surging-strike-red-innate-ga-hand-fully-blocked-go-again-real-followup', None, None, 'computed go again to equal 1, got 0'),
 ('card-fry-blue-innate-ga-zero-cost-keeps-spare-blue', None, None, 'computed power to equal 1, got 2'),
 ('card-surging-strike-red-innate-ga-hand-fully-blocked-go-again-real-followup', None, None, 'p2Health to equal 20, got 19'),
 ('card-sic-em-shot-red-innate-ga-arsenal-blue-pitch', None, None, 'p1Resources to equal 2, got 1'),
])

specs.append(('card-savage-swing-red-mandatory-discard-hand-no-spare-despite-funded-assets', None, None, 'Expected p1Hand count 1, got 0.'))

specs.extend([('diagnostic-en-garde-red-hand-floating-printed-power', None, None, 'computed power to equal 5, got 2'),
 ('card-cut-deep-red-hand-floating', None, None, 'computed power to equal 5, got 1'),
 ('card-felling-swing-red-hand-floating', None, None, 'computed power to equal 8, got 2')])

specs.extend([('probe-command-and-conquer-red-mandatory-hit-trigger-arsenal-present',
  None,
  None,
  'Expected Stack count 1, got 0.'),
 ('probe-command-and-conquer-red-mandatory-hit-trigger-arsenal-present',
  None,
  None,
  'Expected p2Arsenal count 1, got 0.'),
 ('probe-command-and-conquer-red-mandatory-hit-trigger-arsenal-present',
  None,
  None,
  'Expected p2Arsenal count 0, got 1.')])

from runtime_fingerprint import runtime_fingerprint
four_scope=os.environ.get('FAB_FOUR_LIVE_CONTROLS','')
four_module=None
if four_scope:
 import four_live_control_plan as four_module
 four_ids=four_scope.split(',')
 if len(four_ids)!=4 or len(set(four_ids))!=4 or set(four_ids)!=set(four_module.DEFINITIONS):raise ValueError('Select exactly the four reviewed four-live control IDs')
 specs=[(four_module.DEFINITIONS[k]['source'],None,None,four_module.DEFINITIONS[k]['reason'])for k in four_ids]
four_helpers={str(pathlib.Path(__file__).with_name(n)):__import__('hashlib').sha256(pathlib.Path(__file__).with_name(n).read_bytes()).hexdigest()for n in ['card_regression_negative_controls.py','negative_control_state.php','four_live_control_plan.py']}if four_module else{}
if four_module is not None:
 for guarded_slug in four_module.SOURCE_FILES:four_module.verify_source_fixture(fixtures/guarded_slug,guarded_slug)
runtime_before=runtime_fingerprint(repo)
results=[]
for original,old,new,reason in specs:
 target=fixtures/('negative-control-'+uuid.uuid4().hex)
 assert target.parent.resolve()==fixtures.resolve() and target.name.startswith('negative-control-')
 try:
  granted_control=None
  state_control=None
  muscle_control=None
  blade_control=None
  vanilla_control=None
  ab_control=None
  ga_control=None
  discard_control=None
  weapon_next_control=None
  cnc_hit_control=None
  four_live_control=None
  shutil.copytree(fixtures/original,target)
  assertions=json.loads((target/'assertions.json').read_text())
  actions=json.loads((target/'actions.json').read_text())
  if four_module is not None:
   four_live_control=four_module.build_four_live_plan(target,original,reason,actions,assertions)
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=four_live_control['prefix'],four_live_control=four_live_control['control_id'])))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=four_live_control['actions'];assertions=four_live_control['assertions']
  elif (original,reason) in __import__('cnc_hit_control_plan').CONTROL_KEYS:
   cnc_module=__import__('cnc_hit_control_plan');cnc_hit_control=cnc_module.build_cnc_hit_plan(target,original,reason,actions,assertions)
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=cnc_hit_control['prefix'],cnc_hit_control=cnc_hit_control['control_id'])))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=cnc_hit_control['actions'];assertions=cnc_hit_control['assertions']
  elif (original,reason) in __import__('weapon_next_control_plan').CONTROL_KEYS:
   weapon_module=__import__('weapon_next_control_plan')
   weapon_next_control=weapon_module.build_weapon_next_plan(target,original,reason,actions,assertions)
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=weapon_next_control['prefix'],remove_effect=weapon_next_control['remove_effect'],weapon_next_control=weapon_next_control['control_id'])))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=weapon_next_control['actions'];assertions=weapon_next_control['assertions']
  elif (original,reason) == __import__('mandatory_discard_control_plan').CONTROL_KEY:
   discard_module=__import__('mandatory_discard_control_plan')
   discard_control=discard_module.verify_source_fixture(target,original,actions,assertions)
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),**discard_control)))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
  elif (original,reason) in __import__('innate_ga_control_plan').CONTROL_KEYS:
   ga_module=__import__('innate_ga_control_plan');ga_module.verify_ga_source_fixture(target,original)
   with (target/'initial_gamestate.txt').open('r',encoding='utf-8',newline='') as initial_file:ga_initial=initial_file.read()
   ga_schema=(repo/'Schemas/FaBSim/GameSchema.txt').read_text(encoding='utf-8-sig')
   ga_control=ga_module.build_innate_ga_control_plan(original,reason,actions,assertions,ga_initial,ga_schema)
   if 'initial_text' in ga_control:
    with (target/'initial_gamestate.txt').open('w',encoding='utf-8',newline='') as initial_file:initial_file.write(ga_control['initial_text'])
   else:
    spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=ga_control['prefix'],object_effect=ga_control['object_effect'])))
    state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
    if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=ga_control['actions'];assertions=ga_control['assertions']
  elif (original,reason) in __import__('ab_control_plan').CONTROL_KEYS:
   with (target/'initial_gamestate.txt').open('r',encoding='utf-8',newline='') as initial_file:ab_initial=initial_file.read()
   ab_schema=(repo/'Schemas/FaBSim/GameSchema.txt').read_text(encoding='utf-8-sig')
   ab_control=__import__('ab_control_plan').build_ab_control_plan(original,reason,actions,assertions,ab_initial,ab_schema)
   if 'initial_text' in ab_control:
    with (target/'initial_gamestate.txt').open('w',encoding='utf-8',newline='') as initial_file:initial_file.write(ab_control['initial_text'])
   if 'ab_barrier_fault' in ab_control:
    spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=ab_control['prefix'],ab_barrier_fault=ab_control['ab_barrier_fault'])))
    state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
    if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=ab_control['actions'];assertions=ab_control['assertions']
  elif (original,reason) in __import__('vanilla_control_plan').CONTROL_KEYS:
   with (target/'initial_gamestate.txt').open('r',encoding='utf-8',newline='') as initial_file:vanilla_initial=initial_file.read()
   vanilla_schema=(repo/'Schemas/FaBSim/GameSchema.txt').read_text(encoding='utf-8-sig')
   vanilla_control=__import__('vanilla_control_plan').build_vanilla_control_plan(original,reason,actions,assertions,vanilla_initial,vanilla_schema)
   if 'initial_text' in vanilla_control:
    with (target/'initial_gamestate.txt').open('w',encoding='utf-8',newline='') as initial_file:initial_file.write(vanilla_control['initial_text'])
   else:
    spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=vanilla_control['prefix'],object_effect=vanilla_control['object_effect'])))
    state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
    if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=vanilla_control['actions'];assertions=vanilla_control['assertions']
  elif (original,reason) in __import__('blade_break_control_plan').KEYS:
   blade_control=__import__('blade_break_control_plan').build_blade_break_plan(original,reason,actions,assertions)
   spec=dict(fixture=str(target),prefix=blade_control['prefix'])
   if 'object_effect' in blade_control:spec['object_effect']=blade_control['object_effect']
   else:spec['blade_break_clear']=True
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(spec))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=blade_control['actions'];assertions=blade_control['assertions']
  elif (original,reason) in __import__('muscle_control_plan').CONTROL_KEYS:
   muscle_control=__import__('muscle_control_plan').build_muscle_control_plan(original,reason,actions,assertions)
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=muscle_control['prefix'],object_effect=muscle_control['object_effect'])))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=muscle_control['actions'];assertions=muscle_control['assertions']
  elif (original,reason) in __import__('state_control_plan').CONTROL_KEYS:
   state_control=__import__('state_control_plan').build_state_control_plan(original,reason,actions,assertions)
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=state_control['prefix'],state_assertion_mutation=state_control['mutation'])))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=state_control['actions'];assertions=state_control['assertions']
  elif (original,reason) in __import__('granted_hit_control_plan').CONTROL_KEYS:
   granted_control=granted_hit_control_plan(original,reason,actions,assertions)
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=granted_control['prefix'],granted_hit_mutation=granted_control['mutation'])))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=granted_control['actions'];assertions=granted_control['assertions']
  elif original.startswith(('card-burgeoning-','card-power-play-')):
   card='burgeoning_red'if 'burgeoning'in original else'power_play_red'
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),move_card=[dict(player=1,cardID=card,**{'from':'Arsenal','to':'Hand'})])))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   plays=[a for a in actions if a['mode']==10002 and a['cardID']=='p1Arsenal-0!FSM']
   if len(plays)!=1:raise RuntimeError('Expected one Arsenal-origin play')
   plays[0]['cardID']='p1Hand-0!FSM'
   for a in assertions:
    if a.get('type')=='card_exists'and a.get('step')==0 and a.get('zone')=='p1Arsenal':a['zone']='p1Hand'
    if a.get('property')in['SourceZone','FromZone']and a.get('value')=='Arsenal':a['value']='Hand'
  elif original.startswith('card-flock-'):
   prefix=4
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=prefix,remove_stack_param='wtrFlockTokenTrigger')))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=actions[prefix:];assertions=[dict(a,step=a['step']-prefix)for a in assertions if a['step']>prefix]
  elif original.startswith('compat-reaction-'):
   if 'stroke-of-foresight' in original:
    choices=[a for a in actions if a['mode']==100 and a['cardID']=='0']
    if len(choices)!=1:raise RuntimeError('Expected one Stroke top placement choice')
    choices[0]['cardID']='1'
   else:
    if 'rout-' in original:
     prefix=13;card='rout_red';source='Stack'
    else:
     prefix=12;card='roaring_beam_yellow';source='Soul'
    spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=prefix,move_card=[dict(player=1,cardID=card,**{'from':source,'to':'Graveyard'})])))
    state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
    if state.returncode:raise RuntimeError(state.stdout+state.stderr)
    actions=actions[prefix:];assertions=[dict(a,step=a['step']-prefix) for a in assertions if a['step']>=prefix]
  elif original.startswith('card-quicken-') and not old:
   early='computed go again' not in reason
   prefix=1 if early else min(a['step'] for a in assertions if a.get('property')=='TurnEffects' and a.get('value')=='["GO_AGAIN"]')
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=prefix,object_effect=[dict(player=1,zone='Stack',cardID='wounding_blow_red',tag='GO_AGAIN',mode='add'if early else'remove')])))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=actions[prefix:];assertions=[dict(a,step=a['step']-prefix) for a in assertions if a['step']>=prefix]if early else[dict(a,step=a['step']-prefix) for a in assertions if a['step']>prefix]
  elif 'come-to-fight' in original or 'earthlore-surge' in original:
   source='come_to_fight_red' if 'come-to-fight' in original else 'earthlore_surge_red'
   if 'weapon-excluded' in original:prefix=min(a['step'] for a in assertions if a.get('type')=='computed_go_again_equals' and a.get('value')==0)
   else:prefix=min(a['step'] for a in assertions if a.get('zone')=='p1Graveyard' and a.get('cardID')==source)
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=prefix,remove_effect='ARC_NEXT_AA' if 'come-to-fight' in original else 'ELE_NEXT_AA')))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=actions[prefix:];assertions=[dict(a,step=a['step']-prefix) for a in assertions if a['step']>prefix]
  elif '-activation-' in original:
   spec=dict(fixture=str(target))
   if 'energy-potion' in original or 'timesnap-potion' in original:
    prefix=min(a['step'] for a in assertions if a.get('zone')=='Stack' and a.get('value')==1)
    source='energy_potion_blue' if 'energy-potion' in original else 'timesnap_potion_blue'
    spec['replace_stack_card']=dict(**{'from':source,'to':'timesnap_potion_blue' if source=='energy_potion_blue' else 'energy_potion_blue'})
   elif 'heartened' in original:
    prefix=min(a['step'] for a in assertions if a.get('zone')=='p1ActionPoints' and a.get('value')==1)
    spec['remove_effect']='NEXT_COST'
   else:
    card='blackout_kick_red' if 'snapdragon' in original else 'rising_knee_thrust_red'
    tag='GO_AGAIN' if 'snapdragon' in original else 'WTR_POWER:1'
    if 'computed go again' in reason:
     prefix=max(a['step'] for a in assertions if a.get('zone')=='Stack' and a.get('value')==1)
     spec['object_effect']=[dict(player=1,zone='CombatChain',cardID=card,tag=tag,mode='add')]
    else:
     prefix=min(a['step'] for a in assertions if a.get('type')=='card_property_equals' and a.get('property')=='TurnEffects' and a.get('value')=='["'+tag+'"]')
     spec['object_effect']=[dict(player=1,zone='CombatChain',cardID=card,tag=tag,mode='remove')]
   spec['prefix']=prefix
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(spec))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=actions[prefix:]
   if 'computed go again' in reason:assertions=[dict(a,step=0) for a in assertions if a['step']==prefix and a.get('type')=='computed_go_again_equals' and a.get('value')==0]
   else:assertions=[dict(a,step=a['step']-prefix) for a in assertions if a['step']>prefix]
  elif 'hit-trigger-response-window' in original or 'heal-' in original:
   spec=dict(fixture=str(target))
   if 'hit-trigger-response-window' in original:
    prefix=max(a['step'] for a in assertions if a.get('zone')=='Stack' and a.get('value')==1)
    spec.update(prefix=prefix,remove_stack_param='wtrDrawTrigger')
    actions=actions[prefix:];assertions=[dict(a,step=a['step']-prefix) for a in assertions if a['step']>prefix]
   else:
    # Make the same legal Sigil response cross the opposite life-condition boundary.
    spec['set_health']=[dict(player=2 if 'lower-own' in original else 1,value=22)]
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(spec))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
  elif old:
   initial=(target/'initial_gamestate.txt').read_text()
   if initial.count(old)!=1:raise RuntimeError('Initial source identity must occur once')
   (target/'initial_gamestate.txt').write_text(initial.replace(old,new))
   for a in assertions:
    if a.get('cardID')==old:a['cardID']=new
  elif 'sink-below' in original:
   answers=[a for a in actions if a['mode']==100 and a['cardID']=='p2Hand-0']
   if len(answers)!=1:raise RuntimeError('Expected one exchange choice')
   answers[0]['cardID']='-'
  elif 'goliath' in original:
   if [a['mode'] for a in actions[:3]]!=[10002,10001,10001]:raise RuntimeError('Unexpected activation action sequence')
   actions=actions[3:]
   assertions=[dict(a,step=a['step']-3) for a in assertions if a['step']>3]
  elif 'mode-' in original:
   choices=[a for a in actions if a['mode']==100 and a['cardID'] in ['0','1','2']]
   if len(choices)!=1:raise RuntimeError('Expected exactly one mode choice')
   choices[0]['cardID']={'0':'1','1':'2','2':'1'}[choices[0]['cardID']]
   if original.endswith('go-again'):assertions=[a for a in assertions if a.get('zone')!='p2Health']
  elif 'missing-additional-cost' in original or 'bravo' in original:
   spec=dict(fixture=str(target))
   if 'bravo' in original:spec.update(prefix=4,remove_effect='BRAVO_DOMINATE')
   else:spec.update(add_hand=[dict(player=1,cardID='wounding_blow_blue')])
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(spec))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   if 'bravo' in original:
    actions=actions[4:]
    assertions=[dict(a,step=a['step']-4) for a in assertions if a['step']>4]
  elif 'scalding-rain' in original:
   if 'barrier' in original:
    choices=[a for a in actions if a['mode']==100 and a['cardID'] in ['0','1']]
    if len(choices)!=1:raise RuntimeError('Expected one prevention choice')
    choices[0]['cardID']='0' if choices[0]['cardID']=='1' else '1'
   else:
    choices=[a for a in actions if a['mode']==100 and a['cardID']=='p2Hero-0']
    if len(choices)!=1:raise RuntimeError('Expected one target choice')
    choices[0]['cardID']='p1Hero-0'
  elif any(name in original for name in ['nimblism','sloggism','potion-of-strength']):
   effect='NEXT_AA_LOW' if 'nimblism' in original else 'NEXT_AA_HIGH' if 'sloggism' in original else 'NEXT_ATTACK'
   if 'nimblism' in original:prefix=min(a['step'] for a in assertions if a.get('zone')=='p2Health' and int(a['value'])==15)
   else:prefix=min(a['step'] for a in assertions if a['step']>0 and a.get('zone')=='p1ActionPoints' and int(a['value'])==1)
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=prefix,remove_effect=effect)))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=actions[prefix:];assertions=[dict(a,step=a['step']-prefix) for a in assertions if a['step']>prefix]
  elif 'unmovable' in original:
   source='Arsenal' if 'arsenal' in original else 'Hand';destination='Hand' if source=='Arsenal' else 'Arsenal'
   indexes=[i for i,a in enumerate(actions) if a['mode']==10002 and a['playerID']==2]
   if not indexes:raise RuntimeError('Expected a reaction play')
   # The first defender card click announces the reaction; later clicks pitch.
   prefix=indexes[0]
   spec_path=target/'negative-state.json';spec_path.write_text(json.dumps(dict(fixture=str(target),prefix=prefix,move_card=[dict(player=2,cardID='unmovable_red',**{'from':source,'to':destination})])))
   state=subprocess.run([php,str(pathlib.Path(__file__).with_name('negative_control_state.php')),str(spec_path)],cwd=repo,text=True,capture_output=True)
   if state.returncode:raise RuntimeError(state.stdout+state.stderr)
   actions=actions[prefix:];actions[0]['cardID']='p2'+destination+('-1!FSM' if destination=='Hand' else '-0!FSM')
   assertions=[dict(a,step=a['step']-prefix) for a in assertions if a['step']>prefix]
  elif 'fate-foreseen' in original:
   choices=[a for a in actions if a['mode']==100 and a['cardID'].startswith('Top=')]
   if len(choices)!=1:raise RuntimeError('Expected one opt rearrangement')
   top=choices[0]['cardID'].split(';')[0].split('=',1)[1]
   if not top:raise RuntimeError('Expected keep-top choice')
   choices[0]['cardID']='Top=;Bottom='+top
  if discard_control is None:
   (target/'assertions.json').write_text(json.dumps(assertions,indent=2))
   (target/'actions.json').write_text(json.dumps(actions,indent=2))
  run=subprocess.run([php,'DevTools/RunIntegrationTests.php','--root=FaBSim','--test='+target.name],cwd=repo,text=True,capture_output=True)
  ok=run.returncode==1 and reason in run.stdout and 'assertion failed:' in run.stdout and 'snapshot mismatch' not in run.stdout
  if vanilla_control is not None:ok=ok and (('Initial' if vanilla_control['expected_failure_step']==0 else 'Step '+str(vanilla_control['expected_failure_step']))+' assertion failed:') in run.stdout
  if ab_control is not None:ok=ok and (('Initial' if ab_control['expected_failure_step']==0 else 'Step '+str(ab_control['expected_failure_step']))+' assertion failed:') in run.stdout
  if ga_control is not None:
   ga_first_failure=__import__('re').search(r'(Initial|Step [0-9]+) assertion failed:([^\r\n]*)',run.stdout)
   ok=ok and ga_first_failure is not None and ga_first_failure.group(1)==('Initial' if ga_control['expected_failure_step']==0 else 'Step '+str(ga_control['expected_failure_step'])) and reason in ga_first_failure.group(2)
  if discard_control is not None:ok=ok and discard_module.intended_failure(run.returncode,run.stdout)
  if weapon_next_control is not None:ok=ok and weapon_module.intended_failure(run.returncode,run.stdout,weapon_next_control)
  if cnc_hit_control is not None:ok=ok and cnc_module.intended_failure(run.returncode,run.stdout,cnc_hit_control)
  if four_live_control is not None:ok=ok and four_module.intended_failure(run.returncode,run.stdout,four_live_control)
  results.append(dict(runtime_sha256=runtime_before,fixture=original,mutation=(old+' -> '+new if old else 'fixture-only effect/choice/cost perturbation'),expected_failure=reason,detected=ok,output=run.stdout.strip(),returncode=run.returncode))
  if discard_control is not None:results[-1].update(control_id=discard_module.CONTROL_ID,prefix_actions=0,expected_failure_step=1,expected_failure_assertion=assertions[0],fault_target_binding=discard_control,original_initial_sha256=discard_module.SOURCE_FILES['initial_gamestate.txt'],fault_semantics='one_discard_candidate_enables_otherwise_funded_legal_play',evidence_kind='disposable_fixture_semantic_assertion')
  if weapon_next_control is not None:results[-1].update(control_id=weapon_next_control['control_id'],prefix_actions=weapon_next_control['prefix'],expected_failure_step=weapon_next_control['expected_failure_step'],expected_failure_assertion=weapon_next_control['target_assertion'],fault_target_binding=dict(remove_effect=weapon_next_control['remove_effect']),omitted_assertions=weapon_next_control['omitted_assertions'],original_initial_sha256=weapon_next_control['original_initial_sha256'],source_behavior_sha256=weapon_next_control['behavior_sha256'],fault_semantics='remove_one_exact_precombat_next_grant_then_unchanged_real_weapon_action',evidence_kind='disposable_fixture_semantic_assertion')
  if cnc_hit_control is not None:results[-1].update(control_id=cnc_hit_control['control_id'],prefix_actions=10,expected_failure_step=cnc_hit_control['expected_failure_step'],expected_failure_assertion=cnc_hit_control['target_assertion'],fault_target_binding=dict(attackUID=3,victim=2,arsenalUID=4),original_initial_sha256=cnc_hit_control['original_initial_sha256'],source_behavior_sha256=cnc_hit_control['behavior_sha256'],omitted_assertions=[],fault_semantics='disposable_exact_pending_hit_layer_or_effect_timing_fault',evidence_kind='disposable_fixture_semantic_assertion')
  if granted_control is not None:results[-1].update(control_id=granted_control['mutation'],prefix_actions=granted_control['prefix'],expected_failure_step=granted_control['expected_failure_step'],evidence_kind='disposable_fixture_semantic_assertion')
  if state_control is not None:results[-1].update(control_id=state_control['mutation'],prefix_actions=state_control['prefix'],expected_failure_step=state_control['expected_failure_step'],evidence_kind='disposable_fixture_semantic_assertion')
  if muscle_control is not None:results[-1].update(control_id=muscle_control['mutation'],prefix_actions=muscle_control['prefix'],expected_failure_step=muscle_control['expected_failure_step'],evidence_kind='disposable_fixture_semantic_assertion')
  if blade_control is not None:results[-1].update(control_id=blade_control['mutation'],prefix_actions=blade_control['prefix'],expected_failure_step=blade_control['expected_failure_step'],evidence_kind='disposable_fixture_semantic_assertion')
  if vanilla_control is not None:results[-1].update(control_id=vanilla_control['mutation'],prefix_actions=vanilla_control['prefix'],expected_failure_step=vanilla_control['expected_failure_step'],expected_failure_assertion=vanilla_control['target_assertion'],fault_target_binding=vanilla_control.get('identity_replacement',vanilla_control.get('object_effect')),original_initial_sha256=vanilla_control['original_initial_sha256'],evidence_kind='disposable_fixture_semantic_assertion')
  if ab_control is not None:results[-1].update(control_id=ab_control['mutation'],prefix_actions=ab_control['prefix'],expected_failure_step=ab_control['expected_failure_step'],expected_failure_assertion=ab_control['target_assertion'],fault_semantics=('component_backed_eligibility_enumeration_injection_not_dynamic_revalidation' if 'ab_barrier_fault' in ab_control else 'supported_choice_or_pitch_value_perturbation'),fault_target_binding=ab_control.get('identity_replacement',ab_control.get('ab_barrier_fault',ab_control.get('action_replacement'))),original_initial_sha256=ab_control['original_initial_sha256'],evidence_kind='disposable_fixture_semantic_assertion')
  if ga_control is not None:results[-1].update(control_id=ga_control['mutation'],prefix_actions=ga_control['prefix'],expected_failure_step=ga_control['expected_failure_step'],expected_failure_assertion=ga_control['target_assertion'],fault_target_binding=ga_control.get('identity_replacement',ga_control.get('object_effect')),original_initial_sha256=ga_control['original_initial_sha256'],fault_semantics='disposable_exact_printed_pilot_fault_not_legal_suppression_generation',evidence_kind='disposable_fixture_semantic_assertion')
  if four_live_control is not None:results[-1].update(control_id=four_live_control['control_id'],prefix_actions=four_live_control['prefix'],expected_failure_step=four_live_control['expected_failure_step'],expected_failure_assertion=four_live_control['target_assertion'],source_files=four_live_control['source_files'],omitted_assertions=[],rewritten_assertions=[],fault_semantics=four_live_control['fault_semantics'],evidence_kind='disposable_fixture_semantic_assertion')
  print(('[DETECTED] ' if ok else '[UNDETECTED] ')+original+' '+run.stdout.strip())
 finally:
  if target.exists():shutil.rmtree(target)
  if four_module is not None:
   for guarded_slug in four_module.SOURCE_FILES:four_module.verify_source_fixture(fixtures/guarded_slug,guarded_slug)
   if target.exists():raise RuntimeError('Owned four-live disposable target remains')
   if results and results[-1].get('control_id')==(four_live_control or{}).get('control_id'):results[-1].update(canonical_raw5_preserved=True,disposable_fixture_removed=True,guarded_source_files=four_module.SOURCE_FILES)
if runtime_before!=runtime_fingerprint(repo):raise RuntimeError('Runtime changed during controls; no qualified report saved')
if four_module is not None:
 for p,h in four_helpers.items():
  if __import__('hashlib').sha256(pathlib.Path(p).read_bytes()).hexdigest()!=h:raise RuntimeError('Four-live DevTools source changed during controls; no report saved')
 for row in results:row.update(runtime_preserved=True,native_sources_preserved=True)
pathlib.Path(__file__).with_name('card-regression-four-live-negative-control-results.json'if four_module else'card-regression-negative-control-results.json').write_text(json.dumps(results,indent=2)+'\n')
raise SystemExit(0 if all(r['detected'] for r in results) else 1)
