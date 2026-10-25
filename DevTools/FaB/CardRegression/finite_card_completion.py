"""Pure finite-baseline completion assessment; no game execution or writes.

Completion is scoped to the explicitly reviewed baseline, never every possible
cross-card interaction. Missing declarations are unknown/partial, not complete.
"""
import hashlib
import json


def metadata_hash(card):
    keys=["id","types","pitch","cost","power","defense","functional_text_plain"]
    return hashlib.sha256(json.dumps({k:card.get(k) for k in keys},sort_keys=True,separators=(",",":"),ensure_ascii=True).encode()).hexdigest()


def subset(expected,actual):
    if isinstance(expected,dict):
        return isinstance(actual,dict) and all(k in actual and subset(v,actual[k]) for k,v in expected.items())
    return type(expected) is type(actual) and expected==actual


def assess(card,scope,context):
    if scope is None:
        return {"status":"no_reviewed_finite_scope","complete":False,"requirements_met":0,"requirements_total":None,"reasons":["Declare and independently review the finite printed-behavior baseline before claiming completion."]}
    reasons=[]; rows=[]
    if scope.get("metadata_sha256")!=metadata_hash(card): reasons.append("Card metadata changed since scope declaration.")
    if scope.get("scope_review",{}).get("verdict")!="approved_complete_finite_baseline": reasons.append("Finite baseline has not received independent completion-scope approval.")
    if scope.get("remaining_baseline_obligations"): reasons.append("Declared baseline still has unresolved obligations.")
    if not context.get("current_full_clean_replay"): reasons.append("No current full clean replay of exact inventory.")
    if not context.get("current_controls_pass"): reasons.append("Current semantic controls are not qualified.")
    if not context.get("current_native_suites_pass"): reasons.append("Current required native suites are not qualified.")
    for required in scope.get("required_semantic_controls",[]):
        expected={k:v for k,v in required.items() if k!="output_contains"}
        matches=[r for r in context.get("semantic_control_results",[]) if subset(expected,r) and required.get("output_contains","") in r.get("output","")]
        if len(matches)!=1:reasons.append("Required exact semantic control missing or ambiguous: "+required.get("control_id","unnamed"))
    for requirement in scope.get("requirements",[]):
        failures=[]
        if requirement.get("not_applicable"):
            if not requirement.get("rationale"): failures.append("Non-applicability requires an explicit reviewed rationale.")
        else:
            slugs=requirement.get("scenario_ids",[])
            if not slugs: failures.append("No recorded scenario mapping.")
            for slug in slugs:
                fact=context.get("scenarios",{}).get(slug,{})
                if not fact.get("recorded"): failures.append(slug+": not recorded")
                if not fact.get("exact_semantic_review"): failures.append(slug+": missing exact semantic review")
                wanted=requirement.get("required_fixture_sha256",{}).get(slug)
                if wanted and fact.get("fixture_sha256")!=wanted: failures.append(slug+": changed from independently reviewed baseline bytes")
                if len(fact.get("clean_runs",[]))<scope.get("minimum_clean_passes",1): failures.append(slug+": insufficient exact current clean replays")
                for oracle in requirement.get("required_native_oracles",[]):
                    if not any(subset(oracle,a) for a in fact.get("native_assertions",[])): failures.append(slug+": required native oracle absent")
        rows.append({"id":requirement["id"],"met":not failures,"reasons":failures})
    if not rows: reasons.append("Finite baseline has no requirements.")
    if any(not r["met"] for r in rows): reasons.append("One or more finite baseline requirements remain uncovered.")
    complete=not reasons
    return {"status":"complete_declared_baseline" if complete else "partial_declared_baseline","complete":complete,
            "scope_id":scope["scope_id"],"completion_meaning":scope.get("completion_meaning"),
            "requirements_met":sum(r["met"] for r in rows),"requirements_total":len(rows),"requirements":rows,"reasons":reasons,
            "separate_interaction_holds":scope.get("separate_interaction_holds",[])}


def self_check():
    card={"id":"__metadata_unit_only"}
    scope={"scope_id":"unit","metadata_sha256":metadata_hash(card),"scope_review":{"verdict":"approved_complete_finite_baseline"},"requirements":[{"id":"power","scenario_ids":["unit"],"required_native_oracles":[{"type":"computed_power_equals","value":6}]}]}
    context={"current_full_clean_replay":True,"current_controls_pass":True,"current_native_suites_pass":True,"scenarios":{"unit":{"recorded":True,"exact_semantic_review":True,"clean_runs":["unit"],"native_assertions":[{"type":"computed_power_equals","value":6}]}}}
    assert assess(card,scope,context)["complete"]
    for key in ["current_full_clean_replay","current_controls_pass","current_native_suites_pass"]:
        altered=dict(context,**{key:False}); assert not assess(card,scope,altered)["complete"]
    altered=json.loads(json.dumps(context)); altered["scenarios"]["unit"]["native_assertions"][0]["value"]=7
    assert not assess(card,scope,altered)["complete"]
    assert not assess(card,dict(scope,remaining_baseline_obligations=["exact-defense"]),context)["complete"]
    assert not assess(card,dict(scope,scope_review={}),context)["complete"]
    assert not assess(card,None,context)["complete"]
    controlled=dict(scope,required_semantic_controls=[{"control_id":"unit_exact_control","prefix_actions":6,"output_contains":"native intended failure"}])
    controlled_context=dict(context,semantic_control_results=[{"control_id":"unit_exact_control","prefix_actions":6,"output":"native intended failure"}])
    assert assess(card,controlled,controlled_context)["complete"]
    for change in [{"control_id":"unrelated"},{"prefix_actions":7},{"output":"snapshot mismatch"}]:
        altered=dict(controlled_context,semantic_control_results=[dict(controlled_context["semantic_control_results"][0],**change)])
        assert not assess(card,controlled,altered)["complete"]
    return {"metadata_only_guards_passed":12,"not_card_coverage":True}


if __name__=="__main__": print(json.dumps(self_check()))
