"""Prepare scratch probe staging, never execute a native action."""
from pathlib import Path
import copy,difflib,hashlib,json
H=Path(__file__).resolve().parent;R=Path(r'C:\xampp\htdocs\TCGEngine')
read=lambda p:json.loads(p.read_text(encoding='utf-8-sig'))
sha=lambda p:hashlib.sha256(p.read_bytes()).hexdigest()
def write(name,obj):(H/name).write_text(json.dumps(obj,ensure_ascii=True,indent=2)+'\n',encoding='utf-8')
pilots=H/'pilot4-correct-rule-proposed.json';matrix=H/'ordinary-branch-matrix-proposed.json';audit=H/'catalog12-and-source-audit.json'
assert sha(pilots)=='477e175d50eaaa312da8db92675622a8149a040a5f45a3a7af15a3f932143856'
assert sha(matrix)=='ddcccdd8d516ee7b873fac646ebe557af7e1d37310d179e9fa42873d61a95cc4'
assert sha(audit)=='07f5234d265146ab008efe67395e51f760609a7a219c7f6b334de4d7433079d2'
rows=read(pilots);old=read(audit);first=next(c for c in rows if c['cardIDs']==['strike_gold_red'])
# Exact full-object subset, with no oracle, name, action, setup or status edits.
write('first-strike-gold-probe-exact-subset.json',[first])
current=[];diffs=[]
for f in old['native_functions']:
    p=R/f['path'];text=p.read_text(encoding='utf-8-sig');start=text.index('function '+f['function']+'(');end=text.find('\nfunction ',start+1);body=text[start:end if end>=0 else None]
    digest=hashlib.sha256(body.encode()).hexdigest()
    row={'path':f['path'],'file_sha256':sha(p),'function':f['function'],'body_sha256':digest,'matches_frozen8ec_body':digest==f['body_sha256']};current.append(row)
    if not row['matches_frozen8ec_body']:diffs.append({'function':f['function'],'before_sha256':f['body_sha256'],'after_sha256':digest,'diff':''.join(difflib.unified_diff(f['body'].splitlines(True),body.splitlines(True),fromfile='historical8ec',tofile='currentCNC'))})
assert len(diffs)==1 and diffs[0]['function']=='OnHit'
assert 'command_and_conquer_red' in diffs[0]['diff']
assert all(base not in diffs[0]['diff'] for base in ['destructive_deliberation','strike_gold','earth_form','lightning_form'])
compiled=R/'FaBSim/GeneratedCode/GeneratedMacroCode.php'
assert sha(compiled)==old['compiled_macro_file_sha256']
source_files=['FaBSim/Custom/GameLogic.php','FaBSim/Custom/WTRCards.php','FaBSim/Custom/WTRAbilities.php','FaBSim/Custom/HVYCards.php','FaBSim/Custom/OUTRuntime.php','FaBSim/GeneratedCode/GeneratedMacroCode.php','DevTools/FaB/CardRegression/record_card_regressions.php']
rebase={'status':'READ_ONLY_CURRENT_SOURCE_CONTEXT_NOT_RUNTIME_QUALIFICATION',
 'historical8ec_audit':{'path':str(audit),'sha256':sha(audit)},'current_raw_sources':{rel:sha(R/rel) for rel in source_files},
 'function_comparisons':current,'changed_function_details':diffs,
 'conclusion':'The compiled12 Hit callbacks and nine audited helper function bodies remain byte-equivalent after text decoding. OnHit differs only by the bounded CnC inline-skip condition, retaining generic direct Hit dispatch for these12. This supplements the historical8ec source context; it does not establish clean runtime qualification for the currentCNC build.',
 'native_actions':0,'native_writes':0}
write('current-source-probe-context.json',rebase)
full=str(pilots.resolve());filter_text=first['slug'];subset=H/'first-strike-gold-probe-exact-subset.json'
plan={'status':'PENDING_INDEPENDENT_EXACT_SPEC_GATE_PARENT_ONLY_NATIVE_PROBES',
 'research_status':{'identities':12,'printing_records':33,'planned_ordinary_requirements':219,'concrete_pilot_recipes':4,'full219_recipes_authored':False,'native_recorded_or_verified_for_this_family':0},
 'inputs':{'pilot4':{'path':full,'sha256':sha(pilots)},'first_exact_subset':{'path':str(subset.resolve()),'sha256':sha(subset)},'branch_matrix':{'path':str(matrix.resolve()),'sha256':sha(matrix)},'author_model':{'path':str((H/'pilot4-author-rule-model-validation.json').resolve()),'sha256':sha(H/'pilot4-author-rule-model-validation.json')},'current_source_context':{'path':str((H/'current-source-probe-context.json').resolve()),'sha256':sha(H/'current-source-probe-context.json')}},
 'required_independent_gate':['Review exact477e pilot4 and source context supplement, printed12 faces/text, legal Dorinthea/Oldhim/Lexi hero pools, supported native FSM/pass-until and persisted assertions.','Confirm damage then queued hit ability before next priority, no token until ability resolution, correct source/controller/owner/UID allocation, no retroactive self-goagain. Do not approve native eager token creation simply because final token/damage is correct.','Any required recipe correction must be additive and re-frozen. Do not silently modify477e,219matrix or existing saved coverage.'],
 'serial_probe_order':[{'order':1,'card_id':'strike_gold_red','reason':'Generic cost0, no hero-only qualifier, direct shared FaBHVYToken path; sole ordinary source UID3 means unambiguous first new trigger4/token5.'},{'order':2,'card_id':'destructive_deliberation_red','reason':'Cost2 and explicit hero-hit guard plus FaBOUTToken wrapper; confirms the Hero-only family while main target is hero.'},{'order':3,'card_id':'earth_form_red','reason':'Earth talent and cost3 under Oldhim; same token creator with Elemental aura output.'},{'order':4,'card_id':'lightning_form_red','reason':'Lightning talent under Lexi and cost1; token created after play must not give current attack goagain.'}],
 'first_probe_parent_command':{'cwd':str(R),'powershell':"& 'C:\\xampp\\php\\php.exe' 'DevTools/FaB/CardRegression/record_card_regressions.php' '"+full+"' '"+filter_text+"'",'run_by_this_lane':False,'precondition':'Parent confirms no conflicting native run and exact independent gate approval. The existing recorder creates only isolated owned regression game and saves only after every correct oracle passes. No rerecord flag is proposed.'},
 'expected_discriminating_boundary':{'checkpoint':'damage_dealt_hit_trigger_pending_token_not_created','printed_rule':{'target_hp':16,'source_cc_uid':3,'pending_ability_count':1,'pending_ability_uid':4,'pending_ability_controller':1,'pending_ability_source_zone':'CombatChain','pending_ability_source_uid':3,'attacker_arena_count':0,'attacker_ap':0,'attacker_resources':0},'source_predicted_mismatch':'Generic compiled Hit immediately invokes token creator. Current source is expected to show Arena1/tokenUID4 and Stack0, so earliest correct oracle may fail p1Arena expected0 got1. No actual runtime failure is claimed by this plan.'},
 'failure_handling':['Retain immutable recorder run/input exact hashes, attempted native actions, first incorrect expected/actual boundary and actual source context. A failed correct-rule attempt must remain unsaved; check fixture absence and owned-game cleanup.','Do not proceed to broad219 recording, relax pending-layer/token/priority oracles, or apply any source repair under this staging plan. Parent may collect the other three independently approved bounded before-proofs serially if they add source-path evidence.','Any engine repair requires its own bounded proposal, independent gate and actual validation; generated macros remain untouched by this authoring lane.'],
 'passing_handling':['If all correct pilot oracles pass, independently review actual saved raw5/behavior4/actions/checkpoints against477e and printed rules.','Run fresh isolated clean replay2x and meaningful disposable semantic controls, then author all219 requirements in manageable source-equivalent batches, resolving planned token dependency contracts explicitly. A pilot pass does not close a card/family.'],
 'source_scope_notes':['Current-CNC raw sources differ from historical8ec as documented; recompute parent runtime stamp at actual execution. Do not inherit historical2743 qualification automatically.','Artifacts are workspace-only; no copy into native batch paths is performed here. Parent chooses repository-native batch numbers if adopting the input.','Three held Fertile/ArtOfWar drafts remain outside approvedheal187; declaration-before-payment and ordinary lifecycle are unresolved. They are not correct-rule probes or passing coverage.'],
 'native_game_actions':0,'native_source_writes':0,'fixture_writes':0,'script_sha256':sha(Path(__file__))}
write('minimal-native-probe-staging-plan.json',plan)
print(json.dumps({'status':'READY_FOR_INDEPENDENT_GATE','subset_sha256':sha(subset),'source_context_sha256':sha(H/'current-source-probe-context.json'),'plan_sha256':sha(H/'minimal-native-probe-staging-plan.json')}))
