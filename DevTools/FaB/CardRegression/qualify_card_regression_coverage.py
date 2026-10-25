"""Metadata qualification for the existing FaBSim regression inventory.

This module reads saved fixtures and evidence. It never executes games, blesses
snapshots, writes fixtures, or changes an issue's resolution. Completion means
only an explicitly reviewed finite baseline; all other interactions remain held.
"""
import collections
import hashlib
import json
import pathlib
import re

from finite_card_completion import assess
from runtime_fingerprint import runtime_fingerprint

PARTS = ("initial_gamestate.txt", "actions.json", "assertions.json", "expected_final_gamestate.txt")
REQUIRED = PARTS + ("meta.json",)
SEMANTIC_VERDICT = "semantically_valid_partial_branch_coverage"
# Three already-approved immutable documents predate the standard saved-row
# schema. Preserve their original review basis with exact, bounded adapters;
# all future fixtures tables require the standard explicit affirmative row.
LEGACY_REVIEW_SCHEMAS = {
    "0c6364a47cc25a85e2a64ba1fdc9481bd406256c9d06b71b7b897a3bd6d66b2b": "b13_behavior_sha256",
    "2a1d3e55572fc205f8579f4362fe513e3efee127b410ee575a2b9f556dc6ecc8": "battleworn_four_raw_hashes",
    "a6255bccaf09c4ab79959bf8774fe979ab00081c60c393abb01844fc7fd1655f": "approved_four_raw_hashes",
}


def load(path):
    return json.loads(path.read_text(encoding="utf-8-sig"))


def fixture_inventory(root):
    facts = {}
    for folder in sorted(root.iterdir()):
        if not folder.is_dir() or not (folder / "meta.json").is_file():
            continue
        complete = all((folder / name).is_file() for name in REQUIRED)
        digest = hashlib.sha256()
        if complete:
            for name in PARTS:
                digest.update(name.encode())
                digest.update((folder / name).read_bytes())
        facts[folder.name] = {
            "recorded": complete,
            "fixture_sha256": digest.hexdigest() if complete else None,
            "disposable_control": folder.name.startswith("negative-control-"),
        }
    return facts


def approved_review(review, fixture_sha256, slug, proof_verifier):
    if not isinstance(review, dict) or review.get("verdict") != SEMANTIC_VERDICT:
        return False
    if "semantically_approved" in review and review["semantically_approved"] is not True:
        return False
    if not fixture_sha256 or review.get("fixture_sha256") != fixture_sha256:
        return False
    return proof_verifier(review, slug, fixture_sha256)


def clean_report(report, runtime_sha256):
    rows = report.get("fixtures", [])
    return (
        type(report.get("returncode")) is int and report["returncode"] == 0
        and report.get("runtime_sha256") == runtime_sha256
        and isinstance(rows, list) and bool(rows)
        and all(isinstance(row, dict) and row.get("passed") is True and row.get("fixture_sha256") for row in rows)
        and len({row["slug"] for row in rows}) == len(rows)
    )


def qualified_control(row, runtime_sha256):
    output = row.get("output", "")
    if "expected_failure_step" in row:
        step = row["expected_failure_step"]
        if type(step) is not int or step < 0:
            return False
        first = re.search(r"\b(?:Step\s+(\d+)|Initial)\s+assertion failed:\s*([^\r\n]*)", output, re.I)
        if not first or (int(first.group(1)) if first.group(1) is not None else 0) != step:
            return False
        if row.get("expected_failure") and row["expected_failure"] not in first.group(2):
            return False
    return (
        row.get("runtime_sha256") == runtime_sha256
        and row.get("detected") is True
        and type(row.get("returncode")) is int and row["returncode"] == 1
        and "assertion failed:" in output.lower()
        and "snapshot mismatch" not in output.lower()
        and (not row.get("expected_failure") or row["expected_failure"] in output)
    )


def qualified_suite(report, runtime_sha256):
    runs = report.get("runs", [])
    return (
        report.get("runtime_sha256") == runtime_sha256 and bool(runs)
        and all(type(run.get("exit_code")) is int and run["exit_code"] == 0 for run in runs)
        and all(any(name in run.get("command", "").replace("\\", "/") for run in runs)
                for name in ("DevTools/FaB/wtr_test.php", "DevTools/FaB/arc_test.php"))
    )


def hold_matches(hold, fixture_sha256):
    return isinstance(hold, dict) and (not hold.get("fixture_sha256") or hold["fixture_sha256"] == fixture_sha256)


def qualify_metadata(manifest, catalog, review_document, issues, fixtures, reports,
                     controls, suites, scopes, proof_verifier, stable=True):
    """Pure qualification. Supplied facts/evidence must come from one read snapshot.

    Captured historical facts can be used by metadata-only tests. Production
    callers use qualify_manifest, which obtains actual current facts itself.
    """
    runtime = manifest["runtime_sha256"]
    current = {slug: fact for slug, fact in fixtures.items() if not fact.get("disposable_control")}
    inventory_valid = bool(current) and all(fact.get("recorded") for fact in current.values()) and len(current) == len(fixtures)
    exact_inventory = {slug: fact["fixture_sha256"] for slug, fact in current.items()}
    clean = {name: report for name, report in reports.items() if stable and clean_report(report, runtime)}
    full = sorted(name for name, report in clean.items()
                  if inventory_valid and {row["slug"]: row["fixture_sha256"] for row in report["fixtures"]} == exact_inventory)
    passed = collections.defaultdict(list)
    for name, report in clean.items():
        for row in report["fixtures"]:
            fact = current.get(row["slug"])
            if fact and fact.get("recorded") and row["fixture_sha256"] == fact["fixture_sha256"]:
                passed[row["slug"]].append(dict(row, run=name, runtime_sha256=runtime))
    current_controls = [row for row in controls if row.get("runtime_sha256") == runtime]
    controls_pass = bool(controls) and all(qualified_control(row, runtime) for row in controls)
    suite_names = sorted(name for name, report in suites.items() if qualified_suite(report, runtime))
    holds = review_document.get("semantically_held_saved_fixtures", {})
    old_holds = review_document.get("held_noncanonical_attempts", {})
    reviews = review_document.get("scenarios", {})
    pending = {}
    held = set()
    facts = {}
    for card in manifest["cards"]:
        for branch in card["effect_branches"]:
            slug = branch["id"]
            fact = current.get(slug)
            digest = fact.get("fixture_sha256") if fact else None
            base_matches = bool(fact and fact.get("recorded") and branch.get("fixture_sha256") == digest)
            review = reviews.get(slug)
            valid_review = base_matches and approved_review(review, digest, slug, proof_verifier)
            historical = old_holds.get(slug)
            active_hold = holds.get(slug) if hold_matches(holds.get(slug), digest) else None
            if historical:
                branch["held_outside_passing_inventory"] = historical
                branch["historical_hold_qualification"] = "Rejected historical bytes remain separate; this canonical version requires its own exact review and replay."
                if digest and hold_matches(historical, digest):
                    active_hold = historical
            if active_hold and base_matches:
                held.add(slug)
                branch["status"] = "recorded_incorrect_behavior_held_not_passing"
                branch["semantic_hold"] = active_hold
                valid_review = False
            elif base_matches:
                branch["status"] = "recorded_semantically_checked"
            elif fact:
                branch["status"] = "recorded_incomplete_or_changed_during_inventory"
                pending[slug] = "Saved fixture incomplete or changed while the inventory was being built."
            branch["independent_review"] = review if valid_review else None
            branch["replays"] = passed.get(slug, []) if base_matches and not active_hold else []
            if base_matches and not valid_review and not active_hold:
                pending[slug] = "No affirmative exact-hash semantic review with valid retained proof."
            if historical and fact:
                branch["current_replacement_exact_review_and_clean_replay"] = bool(valid_review and branch["replays"])
            facts[slug] = {"recorded": base_matches and not active_hold,
                           "fixture_sha256": digest, "exact_semantic_review": bool(valid_review),
                           "clean_runs": sorted(row["run"] for row in branch["replays"]),
                           "native_assertions": fact.get("native_assertions", []) if fact else []}

    # Preserved before-fix failures do not establish failure of a changed engine.
    # Require an issue explicitly marked fixed and an exact-reviewed saved pilot;
    # this merely qualifies the old failure's age and never verifies this probe.
    for card in manifest["cards"]:
        for branch in card["effect_branches"]:
            if branch["status"] != "blocked_incorrect_behavior" or branch["id"] in current:
                continue
            attempt = branch.get("blocked_attempt") or {}
            for issue in issues:
                if not issue.get("status", "").startswith("fixed_") or card["card_id"] not in issue.get("card_ids", []):
                    continue
                if issue.get("reproducer_scenario") != branch["id"] and issue.get("evidence") != attempt.get("evidence"):
                    continue
                pilot_slugs = [pathlib.PurePosixPath(pilot.get("path", "").replace("\\", "/")).name
                               for pilot in issue.get("pilot_checked_fixtures", [])]
                if issue.get("passing_fixture"):
                    pilot_slugs.append(pathlib.PurePosixPath(issue["passing_fixture"].replace("\\", "/")).name)
                if any(facts.get(slug, {}).get("exact_semantic_review") for slug in pilot_slugs):
                    branch["status"] = "historical_failure_not_rerun_after_fix"
                    branch["blocked_attempt_qualification"] = "Preserved original before-fix probe. Separate bounded fixed-issue pilots are saved and reviewed; this original probe has not been rerun and has no current pass."
                    break

    context = {"current_full_clean_replay": stable and len(full) >= 2,
               "current_controls_pass": stable and controls_pass,
               "current_native_suites_pass": stable and bool(suite_names),
               "semantic_control_results": current_controls, "scenarios": facts}
    declared = scopes.get("cards", {})
    for card in manifest["cards"]:
        scope = declared.get(card["card_id"])
        assessment = assess(catalog[card["card_id"]], scope, context)
        if scope and not proof_verifier(scope.get("scope_review", {}), None, None):
            assessment["complete"] = False
            assessment["status"] = "partial_declared_baseline"
            assessment["reasons"].append("Finite-scope review evidence is missing, stale or outside the metadata evidence root.")
        card["completion_assessment"] = assessment
        recorded = any(branch["status"] == "recorded_semantically_checked" for branch in card["effect_branches"])
        card["coverage_status"] = "complete_declared_baseline" if assessment["complete"] else "partial" if recorded else "pending_semantic_scenario_review"

    branches = [branch for card in manifest["cards"] for branch in card["effect_branches"]]
    unique = {branch["id"]: branch for branch in branches}
    recorded = lambda branch: branch["status"] == "recorded_semantically_checked"
    reviewed = lambda branch: recorded(branch) and bool(branch.get("independent_review"))
    verified = lambda branch: reviewed(branch) and bool(branch["replays"])
    summary = manifest["summary"]
    summary.update(
        identities_with_recorded_branches=sum(any(recorded(branch) for branch in card["effect_branches"]) for card in manifest["cards"]),
        scenarios_recorded=sum(recorded(branch) for branch in unique.values()),
        scenarios_independently_semantically_reviewed=sum(reviewed(branch) for branch in unique.values()),
        scenarios_with_clean_replay_pass=sum(recorded(branch) and bool(branch["replays"]) for branch in unique.values()),
        scenarios_with_two_clean_replay_passes=sum(recorded(branch) and len(branch["replays"]) >= 2 for branch in unique.values()),
        fully_covered_identities=sum(card["completion_assessment"]["complete"] for card in manifest["cards"]),
        negative_controls_detected=sum(row.get("detected") is True for row in controls), negative_controls_total=len(controls),
        recorded_terminal_state_compatibility_scenarios=sum(recorded(branch) and branch["coverage_kind"] == "terminal_state_compatibility" for branch in unique.values()),
        blocked_incorrect_behavior_scenarios=sum(branch["status"] == "blocked_incorrect_behavior" for branch in unique.values()),
        identities_with_semantically_verified_branches=sum(any(verified(branch) for branch in card["effect_branches"]) for card in manifest["cards"]),
        scenarios_semantically_reviewed_and_current_replay_passed=sum(verified(branch) for branch in unique.values()),
        card_scenario_branch_entries=len(branches), canonical_saved_fixture_folders=len(current),
        unmapped_canonical_fixture_folders=sorted(set(current) - set(unique)),
        semantically_held_saved_fixture_folders=sorted(held),
        historical_failure_scenarios_not_rerun_after_fix=sum(branch["status"] == "historical_failure_not_rerun_after_fix" for branch in unique.values()),
        negative_controls_current_runtime_total=len(current_controls),
        negative_controls_current_runtime_detected=sum(qualified_control(row, runtime) for row in current_controls),
        finite_completion_scopes_declared=len(declared),
    )
    summary["identities_without_recorded_branches"] = len(manifest["cards"]) - summary["identities_with_recorded_branches"]
    summary["identities_without_semantically_verified_branches"] = len(manifest["cards"]) - summary["identities_with_semantically_verified_branches"]
    summary["identities_with_partial_verified_coverage"] = summary["identities_with_semantically_verified_branches"] - summary["fully_covered_identities"]
    summary["identities_with_partial_recorded_branches"] = summary["identities_with_recorded_branches"] - summary["fully_covered_identities"]
    manifest["classification"] = "Named verified branches remain partial unless the card has an explicit complete_declared_baseline assessment; separate interaction holds remain outside finite baseline completion."
    manifest["completion_definition"] = scopes.get("completion_meaning", "Only an independently reviewed finite baseline can be complete; arbitrary cross-card interactions are excluded.")
    manifest["evidence_qualification"] = {
        "read_snapshot_stable": stable,
        "current_runtime_clean_replay_qualified_fixtures": len(passed),
        "full_current_inventory_clean_reports": full,
        "qualified_current_native_suite_reports": suite_names,
        "current_controls_report_qualified": controls_pass,
        "historical_controls_by_runtime": dict(collections.Counter(row.get("runtime_sha256") for row in controls)),
        "pending_saved_fixture_reviews": pending,
        "semantically_held_saved_fixtures": {slug: holds.get(slug, old_holds.get(slug)) for slug in held},
        "held_noncanonical_attempts": old_holds,
        "qualification": "A saved recording, a semantic review and a current clean replay are separate facts. Finite completion additionally requires two full exact-inventory current reports, intended semantic controls and required native suites. This builder does not update the captured historical checkpoint or issue resolutions.",
    }
    return manifest


def make_proof_verifier(here, fixture_root=None):
    """Resolve linked immutable review evidence inside the metadata root only."""
    proof_cache = {}

    def proof_verifier(review, slug, fixture_sha256):
        name = review.get("review_evidence", review.get("evidence_file"))
        wanted = review.get("review_evidence_sha256", review.get("evidence_sha256"))
        if not name and not wanted:
            return slug is not None  # retained exact-hash legacy review; no invented saved-inspection claim
        if not isinstance(name, str) or not isinstance(wanted, str):
            return False
        path = (here / name).resolve()
        if not path.is_relative_to(here.resolve()) or not path.is_file():
            return False
        if name not in proof_cache:
            raw = path.read_bytes()
            proof_cache[name] = (hashlib.sha256(raw).hexdigest(), json.loads(raw))
        actual, evidence = proof_cache[name]
        if actual != wanted:
            return False
        if slug is not None and isinstance(evidence, dict) and "fixtures" in evidence:
            rows = evidence["fixtures"]
            if isinstance(rows, dict):
                rows = [dict(row, slug=key) for key, row in rows.items() if isinstance(row, dict)]
            if not isinstance(rows, list) or not all(isinstance(row, dict) for row in rows):
                return False
            matches = [row for row in rows if row.get("slug") == slug]
            if len(matches) != 1:
                return False
            row = matches[0]
            dialect = LEGACY_REVIEW_SCHEMAS.get(actual)
            if dialect == "b13_behavior_sha256":
                return row.get("behaviorSHA256") == fixture_sha256 and row.get("fullNativeAssertionSequenceAndActionStepsMatch") is True
            if dialect in {"battleworn_four_raw_hashes", "approved_four_raw_hashes"}:
                positive = row.get("actual_final_pass") is True if dialect == "battleworn_four_raw_hashes" else row.get("semantically_approved") is True
                if not positive or fixture_root is None:
                    return False
                folder = (fixture_root / slug).resolve()
                if not folder.is_relative_to(fixture_root.resolve()):
                    return False
                digest = hashlib.sha256()
                for name in PARTS:
                    path = folder / name
                    if not path.is_file():
                        return False
                    raw = path.read_bytes()
                    if hashlib.sha256(raw).hexdigest() != row.get("file_sha256", {}).get(name):
                        return False
                    digest.update(name.encode()); digest.update(raw)
                return digest.hexdigest() == fixture_sha256
            return row.get("fixture_sha256") == fixture_sha256 and row.get("semantically_approved") is True
        return True

    return proof_verifier


def qualify_manifest(manifest, cards, here, repo, runtime_sha256, reviews, issues):
    """Read current native evidence and qualify the existing inventory in place."""
    root = repo / "Tests/Integration/FaBSim"
    before = fixture_inventory(root)
    proof_verifier = make_proof_verifier(here, root)
    previous = here / "card-regression-coverage.json"
    if previous.is_file():
        historical = load(previous).get("historical_checkpoint_reproducibility")
        if historical is not None:
            manifest["historical_checkpoint_reproducibility"] = historical

    review_path = here / "card-regression-review.json"
    review_document = load(review_path) if review_path.is_file() else {}
    review_document["scenarios"] = reviews
    scope_path = here / "card-regression-completion-scopes.json"
    scopes = load(scope_path) if scope_path.is_file() else {"cards": {}}
    # Assertions are needed only for finite requirements; reading is sufficient.
    required = {slug for scope in scopes.get("cards", {}).values() for requirement in scope.get("requirements", []) for slug in requirement.get("scenario_ids", [])}
    for slug in required:
        if slug in before and before[slug]["recorded"]:
            before[slug]["native_assertions"] = load(root / slug / "assertions.json")
    reports = {p.name: load(p) for p in sorted(here.glob("card-regression-replay-*.json"))}
    suite_paths=set(here.glob("card-regression-native-suite-*-results.json"))
    suite_paths.update(here.glob("card-regression-native-four-suite-*.json"))
    suites = {p.name: load(p) for p in sorted(suite_paths)}
    negative = here / "card-regression-negative-control-results.json"
    controls = load(negative) if negative.is_file() else []
    after = fixture_inventory(root)
    comparable = {slug: {key: value for key, value in fact.items() if key != "native_assertions"} for slug, fact in before.items()}
    stable = comparable == after and runtime_fingerprint(repo) == runtime_sha256
    return qualify_metadata(manifest, {card["id"]: card for card in cards}, review_document, issues,
                            before, reports, controls, suites, scopes, proof_verifier, stable=stable)
