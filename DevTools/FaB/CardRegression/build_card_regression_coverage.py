"""Inventory every functional identity; never infer effect coverage from a smoke run."""

import pathlib,json,re,hashlib,collections,os,argparse

from runtime_fingerprint import runtime_fingerprint

from qualify_card_regression_coverage import qualify_manifest

here=pathlib.Path(__file__).resolve().parent

repo=pathlib.Path(os.environ.get('FAB_REGRESSION_REPO',next((p for p in here.parents if (p/'Core').is_dir()),pathlib.Path.cwd())))

parser=argparse.ArgumentParser(description=__doc__)
parser.add_argument('--output',type=pathlib.Path,help='Write a reviewed scratch inventory instead of replacing the current manifest')
args=parser.parse_args()
review_path=here/'card-regression-review.json'
review_document=json.loads(review_path.read_text(encoding='utf-8-sig')) if review_path.exists() else {}
registered_reviews=review_document.get('scenarios',{})
cards=json.loads((repo/'FaBSim/GeneratedCode/cardArrayCache.json').read_text(encoding='utf-8-sig'))['cardArray']

snapshots=collections.defaultdict(list)

for p in sorted((repo/'DevTools/FaB').glob('*abilities.json')):

 for card in json.loads(p.read_text(encoding='utf-8-sig')):snapshots[card['cardId']].append(str(p.relative_to(repo)).replace('\\','/'))

compiled=(repo/'FaBSim/GeneratedCode/GeneratedMacroCode.php').read_text(encoding='utf-8-sig')

macro_ids=set(re.findall(r'^\$\w+\["([^":]+)(?::\d+)?"\]\s*=',compiled,re.M))

native={str(p.relative_to(repo)).replace('\\','/'):p.read_text(encoding='utf-8-sig') for p in (repo/'FaBSim/Custom').glob('*.php')}

legacy={str(p.relative_to(repo)).replace('\\','/'):p.read_text(encoding='utf-8-sig') for p in (repo/'DevTools/FaB').glob('*test.php')}

native_tokens={p:set(re.findall(r"['\"]([a-z0-9_]+)['\"]",text)) for p,text in native.items()}

legacy_tokens={p:set(re.findall(r"['\"]([a-z0-9_]+)['\"]",text)) for p,text in legacy.items()}

cases={}
spec_names=['card-regression-scenarios.json','card-regression-rule-probes.json']+[p.name for p in sorted(here.glob('card-regression-batch*.json')) if re.fullmatch(r'card-regression-batch\d+\.json',p.name)]
spec_cache={}
def read_spec(name):
 if pathlib.PurePath(name).name!=name or not name.endswith('.json') or not name.startswith('card-regression-'):
  raise RuntimeError('Unsafe registered source specification: '+name)
 if name not in spec_cache:
  source=here/name
  if not source.is_file():raise RuntimeError('Missing source specification: '+name)
  spec_cache[name]=json.loads(source.read_text(encoding='utf-8-sig'))
 return spec_cache[name]
for name in spec_names:
 for case in read_spec(name):cases[case['slug']]=dict(case,source_spec=name)
# An existing saved review selects its own exact source and scenario only.
# Never wholesale import an old pilot file or let another input replace it.
for slug,review in registered_reviews.items():
 source=review.get('source_spec')
 if not source:raise RuntimeError('Registered review has no source specification: '+slug)
 matches=[case for case in read_spec(source) if case.get('slug')==slug]
 if len(matches)!=1:raise RuntimeError('Registered source must contain exactly one scenario: '+source+' / '+slug)
 cases[slug]=dict(matches[0],source_spec=source)
replays={}

qualified_replay_reports=set()

runtime_sha256=runtime_fingerprint(repo)

for p in sorted(here.glob('card-regression-replay-*.json')):

 report=json.loads(p.read_text(encoding='utf-8-sig'))

 if report.get('returncode')==0 and report.get('fixtures') and all(r.get('passed') for r in report['fixtures']):qualified_replay_reports.add(p.name)

 for result in report.get('fixtures',[]):

  replays.setdefault(result['slug'],[]).append(dict(run=p.name,runtime_sha256=report.get('runtime_sha256'),**result))

negative_path=here/'card-regression-negative-control-results.json'

negative=json.loads(negative_path.read_text(encoding='utf-8-sig')) if negative_path.exists() else []

review_path=here/'card-regression-review.json'

reviews=registered_reviews

issue_path=here/'card-regression-issues.json'

issues=json.loads(issue_path.read_text(encoding='utf-8-sig')) if issue_path.exists() else []

blocked_path=here/'blocked-rule-probe-results.json'

blocked={r['slug']:r for r in json.loads(blocked_path.read_text(encoding='utf-8-sig'))} if blocked_path.exists() else {}

for name in ['ap-cleanup-before-fix-results.json','snatch-trigger-before-fix-results.json','life-trigger-before-fix-results.json','activation-and-battleworn-before-fix-results.json']:

 p=here/name

 if p.exists():

  for result in json.loads(p.read_text(encoding='utf-8-sig')):

   if result.get('status')=='blocked_or_incorrect':blocked[result['slug']]=dict(result,evidence=name)

# Issue proofs use immutable archives; latest-attempt views can be refreshed by a retry.

for issue in issues:

 name=issue.get('evidence')

 if not isinstance(name,str):continue

 p=here/name

 if not p.is_file():continue

 evidence=json.loads(p.read_text(encoding='utf-8-sig'))

 attempts=evidence if isinstance(evidence,list)else evidence.get('results',[])

 for result in attempts:

  if result.get('status')=='blocked_or_incorrect':blocked[result['slug']]=dict(result,evidence=name)

for p in sorted(here.glob('card-regression-batch*.recording-results.json')):

 for result in json.loads(p.read_text(encoding='utf-8-sig')):

  if result.get('status')=='blocked_or_incorrect':blocked[result['slug']]=dict(result,evidence=p.name)

rows=[]

for c in cards:

 id=c['id'];base=re.sub(r'_(red|yellow|blue)$','',id)

 refs=[path for path,tokens in native_tokens.items() if id in tokens]

 family_refs=[path for path,tokens in native_tokens.items() if base!=id and base in tokens]

 tests=[path for path,tokens in legacy_tokens.items() if id in tokens]

 candidates=[]

 if id in snapshots:candidates.append('ability_snapshot')

 if id in macro_ids:candidates.append('compiled_macro')

 if refs or family_refs:candidates.append('native_literal_or_family_reference')

 scenarios=[]

 for slug,case in cases.items():

  if id not in case['cardIDs']:continue

  folder=repo/'Tests/Integration/FaBSim'/slug

  recorded=all((folder/n).is_file() for n in ['initial_gamestate.txt','actions.json','assertions.json','expected_final_gamestate.txt','meta.json'])

  digest=hashlib.sha256()

  if recorded:

   for name in ['initial_gamestate.txt','actions.json','assertions.json','expected_final_gamestate.txt']:

    digest.update(name.encode());digest.update((folder/name).read_bytes())

  current_replays=[r for r in replays.get(slug,[]) if r['run'] in qualified_replay_reports and recorded and r.get('fixture_sha256')==digest.hexdigest() and r.get('runtime_sha256')==runtime_sha256]

  review=reviews.get(slug)

  if review and review.get('fixture_sha256')!=digest.hexdigest():review=None

  scenarios.append(dict(id=slug,source_spec=case['source_spec'],effect_branches=case['branches'],coverage_kind=case.get('coverage_kind','effect_branch'),status='recorded_semantically_checked' if recorded else 'blocked_incorrect_behavior' if slug in blocked else 'planned_not_recorded',recording_test_path='Tests/Integration/FaBSim/'+slug if recorded else None,fixture_sha256=digest.hexdigest() if recorded else None,authoring_oracle=case['oracle'],independent_review=review,replays=current_replays,historical_replay_runs=len(replays.get(slug,[])),negative_controls=[r for r in negative if r['fixture']==slug],blocked_attempt=blocked.get(slug)))

 rows.append(dict(card_id=id,name=c['name'],pitch=c['pitch'],types=c['types'],functional_text=c['functional_text_plain'],functional_text_sha256=hashlib.sha256(c['functional_text_plain'].encode()).hexdigest(),printings=[dict(set_id=p.get('set_id'),printing_id=p.get('id'),unique_id=p.get('unique_id')) for p in c.get('printings',[])],implementation_candidates=candidates,ability_snapshots=snapshots.get(id,[]),compiled_macro_present=id in macro_ids,native_reference_evidence=refs,native_family_reference_evidence=family_refs,legacy_test_mentions=tests,legacy_mentions_are_not_verified_effect_coverage=True,effect_branches=scenarios,coverage_status='partial' if any(s['status']=='recorded_semantically_checked' for s in scenarios) else 'pending_semantic_scenario_review',remaining_branches='Review full printed text, choices, targets, costs, trigger conditions, restrictions, timing, source zones, turn reset/expiry, failure paths, and relevant seat interactions. Existing scenarios cover only their named branches.',blocked_behaviors=[issue for issue in issues if id in issue['card_ids']]))

branch_entries=[s for r in rows for s in r['effect_branches']]

# A stacking fixture may cover two card IDs; count its saved scenario only once.

sc=list({s['id']:s for s in branch_entries}.values())

summary=dict(identities_inventoried=len(rows),printing_records=sum(len(r['printings']) for r in rows),ability_snapshot_identities=len(snapshots),compiled_macro_identities=len(macro_ids),implemented_or_shared_hook_candidates=sum(bool(r['implementation_candidates']) for r in rows),identities_with_recorded_branches=sum(any(s['status']=='recorded_semantically_checked' for s in r['effect_branches']) for r in rows),scenarios_recorded=sum(s['status']=='recorded_semantically_checked' for s in sc),scenarios_independently_semantically_reviewed=sum(bool(s['independent_review']) for s in sc),scenarios_with_clean_replay_pass=sum(any(p['passed'] for p in s['replays']) for s in sc),scenarios_with_two_clean_replay_passes=sum(sum(p['passed'] for p in s['replays'])>=2 for s in sc),fully_covered_identities=sum(r.get('completion_assessment',{}).get('complete') is True for r in rows),negative_controls_detected=sum(r['detected'] for r in negative),negative_controls_total=len(negative))

summary['recorded_terminal_state_compatibility_scenarios']=sum(s['status']=='recorded_semantically_checked' and s['coverage_kind']=='terminal_state_compatibility' for s in sc)

summary['blocked_incorrect_behavior_scenarios']=sum(s['status']=='blocked_incorrect_behavior' for s in sc)

summary['identities_without_recorded_branches']=sum(not any(s['status']=='recorded_semantically_checked' for s in r['effect_branches']) for r in rows)

verified=lambda s:bool(s['independent_review']) and any(p['passed'] for p in s['replays'])

summary['identities_with_semantically_verified_branches']=sum(any(verified(s) for s in r['effect_branches']) for r in rows)

summary['identities_with_partial_verified_coverage']=summary['identities_with_semantically_verified_branches']

summary['identities_without_semantically_verified_branches']=len(rows)-summary['identities_with_semantically_verified_branches']

summary['scenarios_semantically_reviewed_and_current_replay_passed']=sum(verified(s) for s in sc)

summary['card_scenario_branch_entries']=len(branch_entries)

canonical={p.name for p in (repo/'Tests/Integration/FaBSim').iterdir() if p.is_dir() and (p/'meta.json').exists() and not p.name.startswith('negative-control-')}

summary['canonical_saved_fixture_folders']=len(canonical)

summary['unmapped_canonical_fixture_folders']=sorted(canonical-{s['id'] for s in sc if s['status']=='recorded_semantically_checked'})

manifest=dict(schema_version=1,runtime_sha256=runtime_sha256,scope='All local FaBSim functional identities; pitch variants separate, printing records metadata. Implementation candidates include snapshots, compiled macros, native literal/family references; uncertain identities are retained for review.',summary=summary,recording_framework='Core/RegressionTestFramework.php via Core/EngineActionRunner.php modes 11000/11002/11001/11003',runner='php DevTools/RunIntegrationTests.php --root=FaBSim',classification='Named branches require exact semantic review and current replay; completion requires an explicit reviewed finite baseline.',cards=rows)

manifest=qualify_manifest(manifest,cards,here,repo,runtime_sha256,reviews=reviews,issues=issues)

if manifest['summary']['unmapped_canonical_fixture_folders']:
 raise RuntimeError('Saved fixtures lack a canonical source mapping: '+', '.join(manifest['summary']['unmapped_canonical_fixture_folders']))
previous_path=here/'card-regression-coverage.json'
if previous_path.is_file():
 previous=json.loads(previous_path.read_text(encoding='utf-8-sig'))
 if 'historical_source_review_archive' in previous:manifest['historical_source_review_archive']=previous['historical_source_review_archive']
output=args.output if args.output is not None else here/'card-regression-coverage.json'
output.write_text(json.dumps(manifest,indent=2,ensure_ascii=True)+'\n',encoding='utf-8')

print(json.dumps(summary,indent=2))

