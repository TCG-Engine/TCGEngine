"""Read-only FaBSim inventory; write analysis only into this task workspace."""
import collections, datetime, hashlib, json, pathlib, re

REPO = pathlib.Path(r'C:\xampp\htdocs\TCGEngine')
OUT = pathlib.Path(__file__).resolve().parent
cards = json.loads((REPO/'FaBSim/GeneratedCode/cardArrayCache.json').read_text())['cardArray']
card_by_id = {c['id']: c for c in cards}
snapshot = collections.defaultdict(list)
ability_signatures = collections.defaultdict(list)
for p in sorted((REPO/'DevTools/FaB').glob('*abilities.json')):
    for row in json.loads(p.read_text()):
        cid = row['cardId']
        snapshot[cid].append(str(p.relative_to(REPO)).replace('\\','/'))
        for a in row.get('abilities', []):
            code = a.get('abilityCode', '')
            ability_signatures[cid].append({
                'source': str(p.relative_to(REPO)).replace('\\','/'),
                'macro': a.get('macroName'), 'implemented_flag': a.get('isImplemented'),
                'code_sha256': hashlib.sha256(code.encode()).hexdigest(),
                'helpers': sorted(set(re.findall(r'\b(FaB\w+)\s*\(', code))),
            })
compiled = (REPO/'FaBSim/GeneratedCode/GeneratedMacroCode.php').read_text()
compiled_ids = set(re.findall(r'^\$\w+\["([^":]+)(?::\d+)?"\]\s*=', compiled, re.M))
native_tokens = {}
for p in sorted((REPO/'FaBSim/Custom').glob('*.php')):
    native_tokens[str(p.relative_to(REPO)).replace('\\','/')] = set(re.findall(r"['\"]([a-z0-9_]+)['\"]", p.read_text(encoding='utf-8-sig')))
cases = {}
spec_dir = REPO/'DevTools/FaB/CardRegression'
for p in sorted(spec_dir.glob('*.json')):
    if not (p.name in ['card-regression-scenarios.json','card-regression-rule-probes.json'] or re.fullmatch(r'card-regression-batch\d+\.json',p.name)):
        continue
    for case in json.loads(p.read_text()):
        cases[case['slug']] = dict(case, spec=p.name)
scenarios = collections.defaultdict(list)
for slug, case in cases.items():
    fixture = REPO/'Tests/Integration/FaBSim'/slug
    recorded = all((fixture/p).is_file() for p in ['initial_gamestate.txt','actions.json','assertions.json','expected_final_gamestate.txt','meta.json'])
    for cid in case['cardIDs']:
        scenarios[cid].append({'slug':slug, 'spec':case['spec'], 'branches':case['branches'], 'recorded':recorded, 'coverage_kind':case.get('coverage_kind','effect_branch')})

CLASSES = {'Adjudicator','Assassin','Bard','Brute','Guardian','Illusionist','Mechanologist','Merchant','Necromancer','Ninja','Pirate','Ranger','Runeblade','Shapeshifter','Warrior','Wizard','Generic'}
TALENTS = {'Chaos','Draconic','Earth','Elemental','Ice','Light','Lightning','Mystic','Revered','Reviled','Royal','Shadow'}
NUMBERS = {'zero':'0','one':'1','two':'2','three':'3','four':'4','five':'5','six':'6','seven':'7','eight':'8','nine':'9','ten':'10'}
def normalized(c, parameters=False):
    text = re.sub(r'\s+', ' ', c['functional_text_plain']).strip().lower()
    text = text.replace(c['name'].lower(), '<self>')
    if parameters:
        text = re.sub(r'\b('+'|'.join(NUMBERS)+r')\b', lambda m:NUMBERS[m[0]], text)
        text = re.sub(r'\d+(?:,\d+)*(?:\.\d+)?', '<n>', text)
    return text
def play_kind(c):
    t = set(c['types'])
    for kind in ['Hero','Weapon','Equipment','Defense Reaction','Attack Reaction','Instant','Block','Resource','Event','Landmark','Ally']:
        if kind in t:return kind
    if 'Arrow' in t:return 'Arrow attack'
    if 'Attack' in t:return 'Attack action'
    if 'Aura' in t:return 'Aura action/token'
    if 'Item' in t:return 'Item action/token'
    if 'Action' in t:return 'Nonattack action'
    return 'Other'
def category(c):
    t = set(c['types']); text = c['functional_text_plain'].strip()
    if not text:
        if t & {'Attack','Defense Reaction','Attack Reaction','Block'}:return 'printed_stats_without_rules_text'
        return 'no_rules_text_special_type_review'
    if re.fullmatch(r'(?:Go again|Boost|Blade Break|Battleworn|Temper|Arcane Barrier \d+|Spellvoid \d+|Ward \d+|Blood Debt|Phantasm)',text,re.I):
        return 'single_keyword_only'
    if 'Hero' in t:return 'hero_rules'
    if 'Equipment' in t:return 'equipment_rules'
    if 'Weapon' in t:return 'weapon_rules'
    if 'Token' in t:return 'token_with_rules_text'
    return 'other_effect_text'
def flags(c):
    text = normalized(c)
    patterns = {
      'event_trigger_layer_timing':r'\bwhen(?:ever)?\b',
      'delayed_trigger_or_duration':r'\b(?:beginning|end of|next turn|next end|this turn|this combat chain)\b',
      'optional_choice':r'\bmay\b', 'target_or_multiplayer':r'\b(?:target|opponent|hero|all heroes|each player)\b',
      'cost_additional_alternative':r'\b(?:additional cost|rather than|costs? .*less|costs? .*more|pay .*instead)\b',
      'randomness_or_deck_order':r'\b(?:random|shuffle|top|bottom|reveal|opt|look at)\b',
      'zone_permission_or_identity':r'\b(?:banished zone|graveyard|soul|transform|copy|face.down|face.up)\b',
      'mechanic_requires_nontrivial_state':r'\b(?:boost|fusion|charge|combo|crank|scrap|galvanize|clash|wager|beat chest|rune gate|decompose|meld|marked|watery grave|usurp|ward|phantasm)\b',
      'numeric_effect_parameter':r'\d',
    }
    return [name for name,pat in patterns.items() if re.search(pat,text)]

rows=[]; exact=collections.defaultdict(list); parameterized=collections.defaultdict(list)
for c in cards:
    cid=c['id']; base=re.sub(r'_(red|yellow|blue)$','',cid)
    refs=[p for p,tokens in native_tokens.items() if cid in tokens or (base!=cid and base in tokens)]
    kind=play_kind(c)
    ek=hashlib.sha256((kind+'\n'+normalized(c)).encode()).hexdigest()[:16]
    pk=hashlib.sha256((kind+'\n'+normalized(c,True)).encode()).hexdigest()[:16]
    recorded=[s for s in scenarios[cid] if s['recorded']]
    rows.append({
      'card_id':cid,'name':c['name'],'pitch':c['pitch'],'cost':c['cost'],'power':c['power'],'defense':c['defense'],
      'types':c['types'],'classes':sorted(set(c['types'])&CLASSES),'talents':sorted(set(c['types'])&TALENTS),
      'traits':c.get('traits',[]),'printed_keywords':c.get('card_keywords',[]),
      'ability_and_effect_keywords':c.get('ability_and_effect_keywords',[]),
      'play_kind':kind,'classification':category(c),'functional_text':c['functional_text_plain'],
      'exact_self_normalized_family':ek,'numeric_parameter_family':pk,'review_flags':flags(c),
      'printing_records':len(c.get('printings',[])),'set_ids':sorted(set(p['set_id'] for p in c.get('printings',[]))),
      'implementation_evidence':{'snapshots':snapshot.get(cid,[]),'compiled_macro':cid in compiled_ids,'native_literal_or_base_references':refs,'snapshot_macros':ability_signatures.get(cid,[])},
      'implementation_status':'candidate_evidence_present' if snapshot.get(cid) or cid in compiled_ids or refs else 'uncertain_retained',
      'scenarios':scenarios[cid], 'coverage_status':'partial_recorded_branches' if recorded else 'proposed_only' if scenarios[cid] else 'no_recording_scenario_yet',
      'ability_complete':False,
    })
    exact[ek].append(cid); parameterized[pk].append(cid)
by_id={r['card_id']:r for r in rows}
def family_record(key,ids,param):
    members=[by_id[cid] for cid in ids]; sample=card_by_id[ids[0]]
    return {'family_id':key,'play_kind':members[0]['play_kind'],
      'text_pattern':normalized(sample,param), 'grouping_is_semantic_equivalence_proof':False,
      'identities':ids, 'identity_count':len(ids),
      'without_recordings':[r['card_id'] for r in members if r['coverage_status']!='partial_recorded_branches'],
      'remaining_count':sum(r['coverage_status']!='partial_recorded_branches' for r in members),
      'class_requirements':sorted({x for r in members for x in r['classes']}),
      'talent_requirements':sorted({x for r in members for x in r['talents']}),
      'review_flags':sorted({f for r in members for f in r['review_flags']}),
      'implementation_uncertain':[r['card_id'] for r in members if r['implementation_status']=='uncertain_retained'],
      'snapshot_helper_names':sorted({h for r in members for a in r['implementation_evidence']['snapshot_macros'] for h in a['helpers']}),
      'variant_parameters':[{'card_id':r['card_id'],'pitch':r['pitch'],'cost':r['cost'],'power':r['power'],'defense':r['defense']} for r in members],
    }
exact_rows=sorted([family_record(k,v,False) for k,v in exact.items()],key=lambda f:(-f['remaining_count'],-f['identity_count'],f['family_id']))
parameter_rows=sorted([family_record(k,v,True) for k,v in parameterized.items()],key=lambda f:(-f['remaining_count'],-f['identity_count'],f['family_id']))
summary={
 'functional_identities':len(cards),'printing_records':sum(len(c.get('printings',[])) for c in cards),
 'snapshot_identities':len(snapshot),'compiled_macro_identities':len(compiled_ids),
 'identities_with_implementation_candidate_evidence':sum(r['implementation_status']=='candidate_evidence_present' for r in rows),
 'uncertain_identities_retained':sum(r['implementation_status']=='uncertain_retained' for r in rows),
 'identities_with_recorded_branches':sum(r['coverage_status']=='partial_recorded_branches' for r in rows),
 'identities_without_recordings':sum(r['coverage_status']!='partial_recorded_branches' for r in rows),
 'unique_complete_fixture_folders':sum(all((p/x).is_file() for x in ['initial_gamestate.txt','actions.json','assertions.json','expected_final_gamestate.txt','meta.json']) for p in (REPO/'Tests/Integration/FaBSim').iterdir() if p.is_dir()),
 'scenarios_in_specs':len(cases),'classifications':dict(collections.Counter(r['classification'] for r in rows)),
 'play_kind_counts':dict(collections.Counter(r['play_kind'] for r in rows)),
 'self_normalized_exact_text_type_families':len(exact_rows),'numeric_parameter_text_type_families':len(parameter_rows),
 'parameter_families_with_at_least_three_untested_identities':sum(f['remaining_count']>=3 for f in parameter_rows),
 'fully_covered_identities':0,
}
summary['remaining_by_play_kind']=dict(collections.Counter(r['play_kind'] for r in rows if r['coverage_status']!='partial_recorded_branches'))
summary['remaining_by_classification']=dict(collections.Counter(r['classification'] for r in rows if r['coverage_status']!='partial_recorded_branches'))
summary['token_type_identities']=sum('Token' in r['types'] for r in rows)
summary['token_type_without_primary_card_scenarios']=sum('Token' in r['types'] and r['coverage_status']!='partial_recorded_branches' for r in rows)
summary['no_text_identities']=sum(not r['functional_text'].strip() for r in rows)
summary['no_text_without_recordings']=sum(not r['functional_text'].strip() and r['coverage_status']!='partial_recorded_branches' for r in rows)
summary['uncertain_ids_by_play_kind']=dict(collections.Counter(r['play_kind'] for r in rows if r['implementation_status']=='uncertain_retained'))

def recommendation(fid, priority, title, pilot, branches, negative, evidence, risks):
    f=next(f for f in parameter_rows if f['family_id']==fid)
    return {'priority':priority,'title':title,'numeric_parameter_family':fid,'card_ids':f['identities'],
      'remaining_card_ids':f['without_recordings'],'pilot_ids':pilot,
      'proposed_semantic_branches':branches,'negative_control':negative,
      'runtime_evidence':evidence,'pilot_gates_or_risks':risks,
      'status':'proposed_for_independent_review_not_authored_or_verified'}
recommendations=[
 recommendation('eabbfe48f7138cc3',1,'Unrestricted next attack-action buff',
 ['come_to_fight_red','earthlore_surge_red'],
 ['Resolve buff and assert next attack-action power/damage increase at all pitch values',
  'Weapon and nonattack actions do not consume effect; first qualifying attack consumes it; second receives no bonus',
  'Turn expiry without consuming effect, resource/AP payment, insufficient resources and AP-zero rejection'],
 'Remove precisely ARC_NEXT_AA or ELE_NEXT_AA from an isolated prefix; fail explicit first eligible attack damage.',
 ['DevTools/FaB/arc_abilities.json:come_to_fight_* ResolveCard',
  'FaBSim/Custom/ARCCards.php:259-277 FaBARCCardPlayed ARC_NEXT_AA',
  'DevTools/FaB/ele_abilities.json:earthlore_surge_* ResolveCard',
  'FaBSim/Custom/ELECards.php:66-85 FaBELEPlayed ELE_NEXT_AA'],
 ['Two native effect consumers share printed pattern; pilot both separately.',
  'Choose a valid Earth hero whose passives do not alter first-attack results.']),
 recommendation('af0914588d4e5a00',2,'Arsenal-origin attack power',
 ['burgeoning_red','power_play_red'],
 ['Arsenal play receives printed bonus; hand play does not',
  'Face-up and face-down arsenal origin, costs/AP and actual damage',
  'Attack source retains bonus through stack/combat transition and removal from Arsenal; off-zone permissions separately reviewed'],
 'Change only initial origin Arsenal to Hand and adjust click; fail the damage assertion.',
 ['FaBSim/Custom/ELECards.php:87 FaBELEPlayed Burgeoning +1',
  'FaBSim/Custom/SUPCards.php:79 FaBSUPPlayed Power Play +5'],
 ['Parameter normalization hides different +1/+5 values; every card keeps actual printed numbers.',
  'Valid Earth and Guardian hero fixtures required; these are attacks, not arrows.']),
 recommendation('f9f93ca96e4c9413',3,'Plain Boost attacks (24 identities)',
 ['throttle_red','full_tilt_red','zero_to_sixty_red'],
 ['Accept boost with singleton Mechanologist deck: banish exact top card, gain go again and AP on resolution',
  'Accept with singleton non-Mechanologist deck: still boosted/banished but no go again',
  'Decline preserves deck/Banish; empty deck cannot pay boost; printed cost and AP rejection',
  'Blocked boosted attack still obtains go again; no automatic draw; exact source zone and owner'],
 'Swap only top card between Mechanologist and non-Mechanologist with matched pitch; fail AP/go-again assertion, not a snapshot identity check.',
 ['FaBSim/Custom/ARCAbilities.php:167-179 FaBARCBoost',
  'DevTools/FaB/arc_abilities.json:throttle_* PrepareCard',
  'DevTools/FaB/evo_abilities.json:full_tilt_* PrepareCard'],
 ['ARC and EVO PrepareCard generated continuations differ in loop structure; pilot both.',
  'No Hyper Driver, High Octane, banish triggers or modifying hero initially. Additional-cost event hooks call CRU/EVR/AMX/DYN/EVO; extend later with explicit trigger-layer tests.']),
 recommendation('3618b563b01341a6',4,'UPR plain arcane spells (12 additional identities)',
 ['aether_dart_red','ice_bolt_red'],
 ['Own/opposing hero targets and own/opposing Ally targets; actual printed damage and cost',
  'Preparation target UID survives priority response; target removed before resolution deals no damage',
  'Arcane Barrier accept/decline, resource pitch, no affordable barrier; prevention order/Spellvoid/Quell separate branches',
  'Damage bonus/prevention zero/excess boundaries; action AP and payment rejection'],
 'Change base damage or remove chosen barrier from an isolated initial state; require semantic health/resource assertion failure.',
 ['DevTools/FaB/upr_abilities.json:aether_dart_*,aether_hail_*,frosting_*,ice_bolt_*',
  'FaBSim/Custom/UPRCards.php:21-31 FaBUPRAnyTargets/FaBUPRStoreTarget/FaBUPRDeal',
  'FaBSim/Custom/UPRCards.php:159-160 FaBUPRBarrierValue/Choices'],
 ['Same printed text as recorded ARC spells uses different target/prevention pipeline: fresh pilot required.',
  'Three Ice Wizard families need valid Ice Wizard hero; avoid off-turn Iyslander frostbite interaction during initial on-turn pilot.',
  'Any target includes Allies; hero-only coverage would be partial.']),
 recommendation('02c39025d5160c60',5,'Plain Blade Break equipment lifecycle',
 ['ironrot_gauntlet','teklo_base_arms','rotten_old_buckler'],
 ['Defend with printed equipment defense; remains until chain closes then destroyed',
  'No defend => no destruction; multiple links before close; invalid noncontroller defend/removed equipment',
  'Destruction timing and response opportunities tested before treating lifecycle as verified'],
 'Remove Blade Break from isolated object; explicit post-close graveyard/equipment membership must fail.',
 ['FaBSim/Custom/GameLogic.php:1356-1367 defense degradation',
  'FaBSim/Custom/GameLogic.php:1370-1394 FaBCloseCombatChain'],
 ['Native destruction is queued as a tag then synchronous close cleanup. Official keyword is triggered-static: investigate whether a trigger-layer response window is required before saving as full timing coverage.',
  'Off-Hand and Mechanologist equipment have different setup/type requirements.']),
 recommendation('dabb91e13736cc3b',6,'Plain Arcane Barrier equipment',
 ['nullrune_gloves','aetherstorm_wellingtons'],
 ['Pay exactly N to prevent N; decline; insufficient available pitch; damage less/equal/greater than N',
  'Multiple distinct barrier sources; same object not reusable on same damage event; later separate event reusable',
  'Source equipment on combat chain still applies; suppressed/removed source excluded; unpreventable damage bypass'],
 'Replace AB equipment with same-slot non-AB equipment; fail damage/resources checkpoint.',
 ['FaBSim/Custom/UPRCards.php:159-160 barrier values and used UID exclusion',
  'FaBSim/Custom/UPRCards.php:165+ legacy combined prevention plans'],
 ['AB1 and AB2 parameter boundaries differ; run current UPR pipeline and older ARC combined-modal pipeline separately.',
  'Aether Ashwing Ally and Rusted Relic item share keyword but belong to separate play-kind families; neither is covered by equipment results.']),
 recommendation('55dfa32909fafe0c',7,'Plain Spellvoid equipment',
 ['spell_fray_gloves'],
 ['Accept destroys exact source to prevent one arcane damage; decline preserves source',
  'Only arcane, not combat damage; second packet cannot reuse destroyed source',
  'Suppressed/removed/undestroyable source and unpreventable arcane damage'],
 'Replace source with same-slot non-Spellvoid card; fail explicit health and graveyard membership.',
 ['FaBSim/Custom/MONCards.php:286-292 FaBMONSpellvoidRefs/FaBMONSpellvoid'],
 ['Spellbane Aegis token is separate family and must prove token creation/removal semantics.',
  'Keep AB/Quell absent for initial pilot, then test prevention ordering explicitly.']),
 recommendation('51ce6b5328b93844',8,'Singleton deterministic random-discard additional cost',
 ['savage_swing_red','wrecker_romp_red'],
 ['Exactly one other hand card makes random discard deterministic; discard before play completes',
  'Empty remaining hand rejects unpaid additional cost; pay printed resources separately; cost/AP unchanged on rejected play',
  'Multiple candidates need controlled randomness evidence, not blessing one random current result'],
 'Remove required discard card from isolated initial hand; play must reject rather than apply recorded damage.',
 ['DevTools/FaB/wtr_abilities.json: Savage Swing/Wrecker Romp have empty abilities; shared runtime supplies cost',
  'FaBSim/Custom/WTRCards.php:71-73 legality and :141-147 FaBWTRPayAdditionalCosts'],
 ['Avoid Rhinar intimidate triggers or Kayo power modifications in initial fixtures; choose class-valid hero with no relevant passive.',
  'No claim that singleton costs test uniform randomness.']),
 recommendation('ca47a0faccddee62',9,'Aura-play/create condition power (timing investigation)',
 ['shrill_of_skullform_red','hit_the_high_notes_red'],
 ['No prior aura; aura played; aura token created; preexisting aura alone must not imply this-turn event',
  'Create/play first aura after attack declaration before damage; compare dynamic current power',
  'Aura subsequently removed still satisfies this-turn history; turn reset clears history'],
 'Remove only this-turn aura history from an isolated state; fail explicit power/damage.',
 ['FaBSim/Custom/EVRCards.php:76-82 FaBEVRAttack one-time WTR_POWER:3 tagging',
  'FaBSim/Custom/ROSRuntime.php:22 FaBROSPower dynamic AURA check'],
 ['Current implementations evaluate same printed condition at different times. Potential incorrect behavior requires independent rule review and reproducer before saving any passing regression for changing-condition branch.']),
 recommendation('7a23a1ac3421b588',10,'No-rules-text defense reactions',
 ['evasive_leap_red','dodge_blue','toughen_up_blue'],
 ['Pay cost in reaction window; card joins defense and reduces damage by printed defense',
  'Reject on own attack, outside reaction step, from unsupported zone or without resources',
  'Hand restriction from dominate vs arsenal-origin, and end-chain destination'],
 'Swap to lower-defense reaction with matched cost; fail explicit life total rather than snapshot.',
 ['FaBSim/GeneratedCode/cardArrayCache.json printed costs/types/defense',
  'Core/EngineActionRunner.php supported card click and decision modes'],
 ['Dodge/Toughen Up lack implementation-evidence candidates and remain uncertain; do not assume no text means currently supported.',
  'No-rules-text Resource/Token/Evo Base identities are separately classified; generic play is inappropriate.']),
]
timing_risks=[
 {'kind':'event_triggers','scope':'All When/Whenever families, including hits/attacks/defends/start/end/leave-arena',
  'gate':'Assert trigger layer before effect, response window, correct controller/target, source independence, effect-condition evaluation timing and once-per-event multiplicity. Terminal damage/token/draw alone is not enough.'},
 {'kind':'continuous_conditions','scope':'If/while history/current-state conditions',
  'gate':'Change relevant state between play, attack declaration and damage; distinguish monotonic this-turn history from current board count. Shrill/High Notes is a concrete split implementation.'},
 {'kind':'cost_and_prepare','scope':'Boost/Fusion/random discard/charge/banish and alternative costs',
  'gate':'Pin declaration, payment, preparation choice and completion separately; rejected/unaffordable cost cannot partially mutate. Deterministic singleton tests do not establish random distribution.'},
 {'kind':'delayed_cleanup','scope':'End-phase draws, chain-close equipment, Blood Debt, Might/Agility/Vigor/Ponder and duration expiry',
  'gate':'Pin before/after event and priority layers, explicit ordering choices, AP cleanup before refill, all relevant seats and source expiry.'},
 {'kind':'shared_text_different_runtime','scope':'ARC/ELE next-AA buffs, ARC/EVO Boost, ARC/UPR arcane, EVR/ROS aura history',
  'gate':'Text-family members with different macro/helper paths need separate representatives before parameter expansion.'},
]
workflow=[
 'Choose family + concrete runtime subgroup, then enumerate printed values, valid class/talent setup, branches and timing gates.',
 'Independent semantic review of representative scenario before persistence; author via existing recorder with explicit intermediate checkpoints.',
 'Run semantic negative control at intended oracle; clean current-runtime replay; then parameter-expand only reviewed branch equivalence.',
 'Retain per-card identity/scenario/fixture/replay/review status. Expand new mechanics to shared token/keyword sources and relevant seat interactions.',
 'Handle uncertain386 through metadata/base/helper/import review and supported negative/positive probes; retain unsupported/incorrect behavior explicitly, never filter them from catalog.',
 'Do not call identities complete until a per-card full-text branch audit passes. No-rules-text cards still need rules for their types; tokens must be created/used/removed through game actions.'
]
special_type_audit={
 'no_text_special_type_ids':[r['card_id'] for r in rows if r['classification']=='no_rules_text_special_type_review'],
 'guidance':[
  'Inner Chi and Titanium Bauble are resources, so test pitching and resource/Chi accounting plus play rejection, not generic card play.',
  'Proto Base equipment is part of Evo material/transformation state; empty text does not remove type, slot, owner or sub-card requirements.',
  'Lightning Flow is a token aura with context-dependent Spellvoid override in FaBSim/Custom/GameLogic.php:305; author the creation/context/removal behavior explicitly.',
  'All 43 Token-typed identities lack a primary cardIDs scenario at this checkpoint. They may occur as supporting objects in other fixtures; this count does not claim they never appeared.',
  'Block cards cannot be played; tokens are created and removed according to their type rules. Heroes/equipment/weapons begin in arena zones and need activation/passive/lifecycle scenarios.',
  'Class/talent lists are fixture constraints, not ability equivalence. Multi-class/talent heroes and specializations need their own deck/setup restrictions audit.',
 ]}
assert len({r['card_id'] for r in rows})==len(cards)==5196
assert sum(f['identity_count'] for f in exact_rows)==len(cards)
assert sum(f['identity_count'] for f in parameter_rows)==len(cards)
assert all(x in card_by_id for f in parameter_rows for x in f['identities'])
result={'schema_version':1,'generated_at_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),
 'repository':str(REPO),'scope':'All local functional identities retained. Pitch variants separate; printings metadata. Filesystem evidence is a candidate for authoring, never verified card semantics.',
 'normalization':'Whitespace/lowercase and this card name -> <self>; numeric family additionally replaces spelled zero..ten and numeric literals with <n>. Play kind stays separate. Reminders, conditions, timing and durations stay in the pattern. Numeric normalization does not prove equivalent effects; review every value and bounds.',
 'freshness':'Live fixture existence and specs collected during a running authoring task; this is a checkpoint, not replay qualification. Recorded identities count only declared case.cardIDs, not every supporting card. No game state mutations or repository edits performed.',
 'summary':summary,'recommended_next_families':recommendations,'runtime_timing_risks':timing_risks,
 'scalable_workflow':workflow,'special_type_audit':special_type_audit,
 'primary_rules_references':[
  {'url':'https://rules.fabtcg.com/en/cr/08-keywords/','checked_utc':'2026-10-19','sections':['8.1.7 Resource','8.1.8 Token','8.1.12 Block','8.3.2 Battleworn','8.3.3 Blade Break','8.3.8 Arcane Barrier','8.3.9 Boost','8.3.10 Temper','8.3.15 Spellvoid']},
  {'url':'https://rules.fabtcg.com/en/cr/07-combat/','checked_utc':'2026-10-19','use':'Combat-step and chain-close pilot audit'}],
 'cards':rows,'exact_text_families':exact_rows,'numeric_parameter_families':parameter_rows}
(OUT/'catalog-effect-map.json').write_text(json.dumps(result,indent=2)+'\n',encoding='utf-8')
print(json.dumps(summary,indent=2))
print('Validation: all 5196 identities occur exactly once in each family partition; no identities excluded.')
