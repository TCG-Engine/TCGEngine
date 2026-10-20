"""Pure, exact raw5-bound planning for four UPF disposable fault copies."""
import copy,hashlib,json,pathlib,re
SOURCE_FILES={'card-sigil-of-solace-red-four-live-hold-priority-correct-rule-before-probe': {'initial_gamestate.txt': 'd09fc863fc6e810df6dc49c7aceccdff835b56f72b96a8c2a1e3fd31f9d8336d', 'actions.json': '46f52a911b52e5dd9ba5001f62869e6a7ede2618717f4117f01c2681f229811e', 'assertions.json': '5d234e6c97a5a80a0c18e9f867c90ef396d40a9671653a365a80bf05dcb95470', 'expected_final_gamestate.txt': '6d6cf85e59fb4a721651f90b2c64a57e482f1ab390b3aeaab274eb5f012468de', 'meta.json': '2d8fde6036184540e0075402ff2d5dc14cbb26579af7e9e2beef5dc305be1e86'}, 'card-aether-dart-red-four-live-hand-caster1-sigil3-spellvoid4': {'initial_gamestate.txt': 'ac60286980012708a041bd926fa6ab7622c74309eb8a40bd14c7fb8b2b910a02', 'actions.json': 'f3af111339ea2cc984793321dd7b91d5f03c81cad31164befdb24cda346a5701', 'assertions.json': '4631353746996bbab3c6c436b25ecbac4520a8329cc6bdff713085892cf6d6e2', 'expected_final_gamestate.txt': '459bcd968f16853b210f97f15a7bd4f87dfe0ec644657053178e262e782a0c27', 'meta.json': '21c5f2f578cb90efc80d264721a695b7767f600d84fc378ae6d9025caf2626c9'}, 'card-strike-gold-red-upf-four-live-adjacent-four-all-seat-sigil-responses': {'initial_gamestate.txt': '8b7bcd8981b60d2e7d779982ebecbcce01e27f9ea1d95afd37f8897b3a3c0167', 'actions.json': '602bb44013ed20ff7eeba0e86821d7a7a36f2bf2706720d9758141e1e19d55ee', 'assertions.json': '17036116e71ca6bded70d64fcc1b611d674baff97929262bdfe221799c60839e', 'expected_final_gamestate.txt': 'f53ca1bfe632d9030b2749f8005e8e18a33e525659c0fbce7e376dee6b2b5112', 'meta.json': 'dc0ca2c00f5c6962ff75f1ffa8eb2cd4ee0226100f70c603ce6d98489045845f'}}
DEFINITIONS={'four_sigil_hold_false': {'tier': 'core', 'source': 'card-sigil-of-solace-red-four-live-hold-priority-correct-rule-before-probe', 'prefix': 3, 'original_failure_step': 4, 'expected_failure_step': 1, 'mutation': {'P4ShortcutPreferences.holdPriority': [True, False]}, 'target': {'type': 'scalar_zone_equals', 'zone': 'PriorityPlayer', 'value': 4}, 'reason': 'Expected PriorityPlayer to equal 4, got 1.', 'semantics': 'Existing payment policy chooses next live seat if actor holdPriority is false. All shortcut windows remain false; no additional automatic pass. Only preference changes, and actual P4cast must succeed.', 'control_id': 'four_sigil_hold_false', 'keep_boundary_assertions': True, 'native_status': 'NOT_RUN', 'source_raw5_bound': True}, 'four_dart_source_lost_during_victim4_choice': {'tier': 'core', 'source': 'card-aether-dart-red-four-live-hand-caster1-sigil3-spellvoid4', 'prefix': 13, 'original_failure_step': 13, 'expected_failure_step': 0, 'mutation': {'physical_Dart_UID5.removed': [False, True]}, 'target': {'type': 'zone_count', 'zone': 'Stack', 'value': 1}, 'reason': 'Expected Stack count 1, got 0.', 'semantics': 'One source visibility fault while actual P4 MZMAYCHOOSE/frame remain unchanged. Deliberate lost-source injection, not a legal move or callback-cleanup simulation.', 'control_id': 'four_dart_source_lost_during_victim4_choice', 'keep_boundary_assertions': True, 'native_status': 'NOT_RUN', 'source_raw5_bound': True}, 'four_dart_wrong_arcane_actor': {'tier': 'core', 'source': 'card-aether-dart-red-four-live-hand-caster1-sigil3-spellvoid4', 'prefix': 14, 'original_failure_step': 14, 'expected_failure_step': 0, 'mutation': {'FaBSim.arcaneDealt': [{'1': {'4': 2}}, {'4': {'4': 2}}]}, 'target': {'type': 'game_state_field', 'rootName': 'FaBSim', 'path': ['arcaneDealt'], 'comparison': 'equals', 'valueJson': '{"1":{"4":2}}'}, 'reason': 'Expected FaBSim state ["arcaneDealt"] to equal JSON {"1":{"4":2}}, got {"4":{"4":2}}.', 'semantics': 'Only recorded arcane attribution changes after actual P4answer and damage; all physical cards, HP and source ownership stay unchanged. Bookkeeping control, not alternate legal play.', 'control_id': 'four_dart_wrong_arcane_actor', 'keep_boundary_assertions': True, 'native_status': 'NOT_RUN', 'source_raw5_bound': True}, 'four_dart_nonadjacent_offer': {'tier': 'core', 'source': 'card-aether-dart-red-four-live-hand-caster1-sigil3-spellvoid4', 'prefix': 1, 'original_failure_step': 1, 'expected_failure_step': 0, 'mutation': {'P1MZCHOOSE.Param': ['p2Hero-0&p4Hero-0', 'p2Hero-0&p3Hero-0&p4Hero-0']}, 'target': {'type': 'card_property_equals', 'mzId': 'p1DecisionQueue-0', 'property': 'Param', 'value': 'p2Hero-0&p4Hero-0'}, 'reason': "Expected p1DecisionQueue-0.Param to equal 'p2Hero-0&p4Hero-0', got 'p2Hero-0&p3Hero-0&p4Hero-0'.", 'semantics': 'Only actual target-offer enumeration widens to nonadjacent P3; actor, UID5, pending payment, other queues and await frame unchanged. Does not claim illegal-answer revalidation coverage.', 'control_id': 'four_dart_nonadjacent_offer', 'keep_boundary_assertions': True, 'native_status': 'NOT_RUN', 'source_raw5_bound': True}}
CONTROL_KEYS={(d['source'],d['reason'])for d in DEFINITIONS.values()}
def require(ok,message):
 if not ok:raise ValueError(message)
def verify_source_fixture(folder,original):
 folder=pathlib.Path(folder);binding=SOURCE_FILES.get(original)
 require(isinstance(binding,dict)and len(binding)==5,'Actual four-live raw5 binding pending')
 for name,digest in binding.items():require(hashlib.sha256((folder/name).read_bytes()).hexdigest()==digest,'Four-live raw source changed: '+name)
 return binding
def build_four_live_plan(folder,original,reason,actions,assertions):
 choices=[(kind,d)for kind,d in DEFINITIONS.items()if (original,reason)==(d['source'],d['reason'])]
 require(len(choices)==1,'Unknown four-live control source/key');kind,d=choices[0]
 binding=verify_source_fixture(folder,original);folder=pathlib.Path(folder)
 require(actions==json.loads((folder/'actions.json').read_text(encoding='utf-8-sig'))and assertions==json.loads((folder/'assertions.json').read_text(encoding='utf-8-sig')),'Supplied four-live source differs from actual saved raw5')
 require(all(type(a.get('step'))is int and 0<=a['step']<=len(actions)for a in assertions),'Malformed native assertion steps')
 tuple_=lambda a:(a.get('playerID'),a.get('mode'),a.get('cardID'))
 if kind=='four_sigil_hold_false':
  expected=[(p,10001,'!CustomInput!Pass')for p in [1,2,3]]+[(4,10002,'p4Hand-0!FSM')]+[(p,10001,'!CustomInput!Pass')for p in [4,1,2,3]]
 else:
  expected=[(1,10002,'p1Hand-0!FSM'),(1,100,'p4Hero-0')]+[(p,10001,'!CustomInput!Pass')for p in [1,2]]+[(3,10002,'p3Hand-0!FSM')]+[(p,10001,'!CustomInput!Pass')for p in [3,4,1,2,1,2,3,4]]+[(4,100,'p4Equipment-0')]
 require([tuple_(a)for a in actions]==expected,'Actual four-live play/pass/answer ledger differs')
 prefix=d['prefix'];retained=[dict(a,step=a['step']-prefix)for a in assertions if a['step']>=prefix]
 target=dict(d['target'],step=d['expected_failure_step'])
 matches=lambda a:all(a.get(k)==v for k,v in target.items())
 require(sum(matches(a)for a in retained)==1,'Intended exact first semantic oracle missing/duplicated')
 # Prove no other assertion on the changed semantic component precedes this one.
 if kind=='four_sigil_hold_false':
  affected=lambda a:a['step']>=1 and a.get('type')=='scalar_zone_equals'and a.get('zone')=='PriorityPlayer'
 elif kind=='four_dart_source_lost_during_victim4_choice':
  affected=lambda a:(a.get('zone')=='Stack'and a.get('type')in['zone_count','card_exists'])or(a.get('type')=='card_property_equals'and str(a.get('mzId','')).startswith('Stack-'))
 elif kind=='four_dart_nonadjacent_offer':
  affected=lambda a:a.get('type')=='card_property_equals'and a.get('mzId')=='p1DecisionQueue-0'and a.get('property')=='Param'
 else:
  affected=lambda a:a.get('type')=='game_state_field'and a.get('rootName')=='FaBSim'and a.get('path')==['arcaneDealt']
 first=next((a for a in retained if affected(a)),None)
 require(first is not None and matches(first),'Earlier changed-component assertion would shadow intended failure')
 require(not any(a.get('type')=='decision_queue_empty'and a.get('player')=='all'for a in retained),'False all-seat queue assertion in four-live source')
 return dict(control_id=kind,prefix=prefix,expected_failure_step=d['expected_failure_step'],target_assertion=copy.deepcopy(first),actions=copy.deepcopy(actions[prefix:]),assertions=retained,omitted_assertions=[],rewritten_assertions=[],source_files=copy.deepcopy(binding),fault_semantics=d['semantics'])
def intended_failure(returncode,stdout,plan):
 d=DEFINITIONS.get(plan.get('control_id'));rows=re.findall(r'^\[FAIL\][^\r\n]*',stdout,re.M)
 if d is None or returncode!=1 or len(rows)!=1 or 'snapshot mismatch'in stdout:return False
 first=re.search(r'(Initial|Step [0-9]+) assertion failed:([^\r\n]*)',rows[0])
 return first is not None and first.group(1)==('Initial'if d['expected_failure_step']==0 else'Step '+str(d['expected_failure_step']))and first.group(2).strip()==d['reason']
