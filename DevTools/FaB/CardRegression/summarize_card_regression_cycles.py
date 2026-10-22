"""Derive sparse cycle presence from existing reviewed FaBSim regression evidence.

This is a read-only metadata summary. It executes no game, records no fixture,
and never changes card completion or inherits evidence across pitch variants.
"""
import argparse
import collections
import datetime
import hashlib
import json
import pathlib
import re
import sys

sys.dont_write_bytecode = True
PARTS = ('initial_gamestate.txt', 'actions.json', 'assertions.json', 'expected_final_gamestate.txt')


def load(path):
    return json.loads(path.read_text(encoding='utf-8-sig'))


def sha(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def require(value, message):
    if not value:
        raise ValueError(message)


def direct_file(root, name):
    require(isinstance(name, str) and pathlib.PurePath(name).name == name, 'Unsafe metadata filename')
    path = (root / name).resolve()
    require(path.is_relative_to(root.resolve()) and path.is_file(), 'Missing or out-of-root metadata input: ' + name)
    return path


def partition(document, cards):
    rows = document.get('groups')
    require(isinstance(rows, list) and bool(rows), 'Missing reviewed group partition')
    members = {}
    by_id = {}
    for row in rows:
        require(isinstance(row, list) and len(row) == 2, 'Invalid compact group row')
        group, ids = row
        require(isinstance(group, str) and group not in members and isinstance(ids, list) and bool(ids), 'Duplicate or empty group')
        require(all(isinstance(cid, str) and cid in cards and cid not in by_id for cid in ids) and len(set(ids)) == len(ids), 'Duplicate or unknown group member')
        members[group] = sorted(ids)
        by_id.update({cid: group for cid in ids})
    require(set(by_id) == set(cards), 'Group partition does not cover the exact current catalog')
    require(document.get('counts') == {'groups': len(members), 'functional_ids': len(cards)}, 'Group partition count declaration differs')
    return members, by_id


def snapshot(text, schema_text):
    """Read the existing schema-ordered snapshot format without engine imports."""
    layout = []
    for line in schema_text.splitlines():
        match = re.match(r'^([A-Za-z][A-Za-z0-9_]*)\s+-\s+(.+)$', line)
        if match:
            layout.append({'name': match[1], 'object': not match[2].startswith('Value:'), 'global': False,
                           'fields': [field.strip().split(':', 1)[0] for field in match[2].split(',')]})
        elif layout and 'Scope=Global' in line:
            layout[-1]['global'] = True
    require(bool(layout), 'Empty game schema')
    lines = text.splitlines()
    cursor = 2
    result = {}
    for zone in layout:
        for seat in ([0] if zone['global'] else [1, 2, 3, 4]):
            name = zone['name'] if seat == 0 else f'p{seat}' + zone['name']
            require(cursor < len(lines), 'Truncated saved initial snapshot')
            if zone['object']:
                count = int(lines[cursor])
                cursor += 1
                require(count >= 0 and cursor + count <= len(lines), 'Invalid saved zone count')
                objects = []
                for line in lines[cursor:cursor + count]:
                    values = line.split(' ')
                    require(len(values) == len(zone['fields']), 'Saved object/schema field mismatch')
                    objects.append(dict(zip(zone['fields'], values)))
                result[name] = objects
                cursor += count
            else:
                result[name] = lines[cursor]
                cursor += 1
    require(cursor == len(lines), 'Trailing saved snapshot fields')
    return result


def four_live_profile(case, initial, assertions, actions):
    """Require both actual live players and persisted assertions/actions for them."""
    reasons = []

    def gate(value, reason):
        if not value:
            reasons.append(reason)

    def oracle(kind, **fields):
        return any(row.get('step') == 0 and row.get('type') == kind and all(row.get(k) == v for k, v in fields.items()) for row in assertions)

    gate(case.get('activeSeats') == [1, 2, 3, 4], 'source_does_not_declare_four_active_seats')
    gate(initial.get('SeatOrder') == initial.get('LiveSeats') == '1234', 'actual_seat_order_or_live_seats_not_1234')
    for zone in ['SeatOrder', 'LiveSeats']:
        gate(oracle('scalar_zone_equals', zone=zone, value=1234), 'missing_initial_' + zone + '_assertion')
    state = json.loads(initial.get('GameState') or '{}')
    gate(state.get('gameMode') == 'UPF' and state.get('passiveSeats') == [], 'actual_mode_or_passive_seats_wrong')
    for field, expected in [('gameMode', 'UPF'), ('passiveSeats', [])]:
        gate(any(row.get('step') == 0 and row.get('type') == 'game_state_field' and row.get('rootName') == 'FaBSim' and row.get('path') == [field] and row.get('comparison') == 'equals' and json.loads(row.get('valueJson', 'null')) == expected for row in assertions), 'missing_initial_' + field + '_assertion')
    for seat in [1, 2, 3, 4]:
        hero_zone = f'p{seat}Hero'
        heroes = initial.get(hero_zone, [])
        health = initial.get(f'p{seat}Health', '0')
        living = len(heroes) == 1 and int(health) > 0
        gate(living, f'actual_seat{seat}_has_no_living_hero')
        gate(oracle('scalar_zone_equals', zone=f'p{seat}Health', value=int(health)) and int(health) > 0, f'missing_initial_seat{seat}_positive_health_assertion')
        if len(heroes) == 1:
            hero = heroes[0]
            gate(hero.get('Owner') == hero.get('Controller') == str(seat), f'actual_seat{seat}_hero_ownership_wrong')
            gate(len(case.get('heroes', [])) == 4 and hero.get('CardID') == case['heroes'][seat - 1], f'actual_seat{seat}_hero_differs_from_source')
            for field in ['CardID', 'Owner', 'Controller']:
                gate(oracle('card_property_equals', mzId=hero_zone + '-0', property=field, value=hero.get(field)), f'missing_initial_seat{seat}_hero_{field}_assertion')
    pass_actors = sorted({row.get('playerID') for row in actions if row.get('mode') == 10001 and row.get('cardID') == '!CustomInput!Pass'})
    gate(pass_actors == [1, 2, 3, 4], 'saved_real_pass_actors_not_all_four')
    return {'verified': not reasons, 'reasons': reasons, 'saved_pass_actors': pass_actors}


def coverage_inventory(coverage, fixture_facts):
    """Reject an older saved-fixture inventory even when gameplay is unchanged."""
    require(all(fact.get('recorded') and not fact.get('disposable_control') for fact in fixture_facts.values()), 'Canonical saved fixture inventory is incomplete or includes disposable controls')
    actual = {slug: fact['fixture_sha256'] for slug, fact in fixture_facts.items()}
    mapped = {}
    for card in coverage['cards']:
        for branch in card['effect_branches']:
            if branch.get('recording_test_path') or branch.get('fixture_sha256') or branch.get('status', '').startswith('recorded_'):
                slug = branch['id']
                digest = branch.get('fixture_sha256')
                require(slug not in mapped or mapped[slug] == digest, 'Coverage repeats a saved slug with different hashes: ' + slug)
                mapped[slug] = digest
    missing = sorted(set(actual) - set(mapped))
    removed = sorted(set(mapped) - set(actual))
    changed = sorted(slug for slug in set(actual) & set(mapped) if actual[slug] != mapped[slug])
    require(not (missing or removed or changed), 'Coverage manifest is stale for canonical saved fixtures; rebuild it first: ' + json.dumps({'unmapped': missing, 'no_longer_saved': removed, 'changed_hash': changed}, separators=(',', ':')))
    summary = coverage.get('summary', {})
    require(summary.get('canonical_saved_fixture_folders') == len(actual) and summary.get('unmapped_canonical_fixture_folders') == [], 'Coverage manifest saved-fixture inventory count or mapping declaration is stale; rebuild it first')
    digest = hashlib.sha256(json.dumps(sorted(actual.items()), ensure_ascii=True, separators=(',', ':')).encode('utf-8')).hexdigest()
    return {'canonical_fixture_count': len(actual), 'canonical_fixture_digest': digest, 'digest_definition': 'SHA256 of compact UTF-8 JSON sorted [slug, existing four-file behavior SHA256] pairs; all five fixture files must exist'}


def representative(rows, minimum_rank=0):
    eligible = [row for row in rows if row['rank'] >= minimum_rank]
    if not eligible:
        return None
    chosen = sorted(eligible, key=lambda row: (-row['rank'], row['slug'], row['card_id']))[0]
    return {key: chosen[key] for key in ['card_id', 'slug', 'source_spec', 'fixture_sha256', 'branches', 'status', 'current_replay_reports']} | ({'four_live_profile': chosen['four_live_profile']} if chosen.get('four_live_profile') else {})


def summarize(coverage, group_document, reviews, fixture_facts, reports, proof_verifier, case_for, profile_for, active_holds=None, historical_holds=None):
    """Pure evidence projection; callers provide one stable read snapshot."""
    from qualify_card_regression_coverage import approved_review, clean_report, hold_matches
    cards = {card['card_id']: card for card in coverage['cards']}
    members, group_for = partition(group_document, cards)
    runtime = coverage['runtime_sha256']
    clean = {name: report for name, report in reports.items() if clean_report(report, runtime)}
    replay_rows = {name: {row['slug']: row for row in report['fixtures']} for name, report in clean.items()}
    candidate_ids = {cid for cid, card in cards.items() if card.get('implementation_candidates')}
    candidate_groups = {group_for[cid] for cid in candidate_ids}
    rows = collections.defaultdict(list)
    blocked = collections.defaultdict(list)
    seen = set()
    for cid, card in cards.items():
        for branch in card['effect_branches']:
            slug = branch['id']
            require((cid, slug) not in seen, 'Duplicate primary card/scenario entry')
            seen.add((cid, slug))
            case = case_for(branch['source_spec'], slug)
            require(cid in case.get('cardIDs', []), 'Coverage subject is not a primary source card: ' + cid + '/' + slug)
            group = group_for[cid]
            fact = fixture_facts.get(slug, {})
            digest = fact.get('fixture_sha256')
            saved = bool(fact.get('recorded') and branch.get('fixture_sha256') == digest)
            review = reviews.get(slug)
            held = any(hold_matches(holds.get(slug), digest) for holds in [active_holds or {}, historical_holds or {}])
            reviewed = saved and not held and branch.get('status') == 'recorded_semantically_checked' and approved_review(review, digest, slug, proof_verifier) and branch.get('independent_review') == review
            runs = sorted({entry['run'] for entry in branch.get('replays', []) if entry.get('passed') is True and entry.get('runtime_sha256') == runtime and entry.get('fixture_sha256') == digest and entry.get('run') in replay_rows and replay_rows[entry['run']].get(slug, {}).get('fixture_sha256') == digest}) if reviewed else []
            rank = 3 if reviewed and len(runs) >= 2 else 2 if reviewed and runs else 1 if reviewed else 0
            status = ['recorded_pending_exact_semantic_review', 'saved_reviewed_pending_current_replay', 'saved_reviewed_one_current_replay', 'qualified_two_current_replays'][rank]
            if not saved or held or branch.get('status') != 'recorded_semantically_checked':
                if held or 'held' in branch.get('status', '') or 'blocked' in branch.get('status', '') or 'incomplete' in branch.get('status', ''):
                    blocked[group].append({'card_id': cid, 'slug': slug, 'status': 'current_semantic_hold' if held else branch['status']})
                continue
            profile = profile_for(case, slug, digest) if case.get('activeSeats') == [1, 2, 3, 4] else None
            rows[group].append({'card_id': cid, 'slug': slug, 'source_spec': branch['source_spec'], 'fixture_sha256': digest, 'branches': branch['effect_branches'], 'rank': rank, 'status': status, 'current_replay_reports': runs, 'four_live_profile': profile})

    counts = {'all_profiles': collections.Counter(), 'actual_four_live': collections.Counter()}
    output_rows = []
    for group in sorted(set(rows) | set(blocked)):
        items = rows[group]
        four = [row for row in items if (row.get('four_live_profile') or {}).get('verified')]
        for label, subset in [('all_profiles', items), ('actual_four_live', four)]:
            for name, minimum in [('recorded', 0), ('saved_reviewed', 1), ('one_current_replay_or_more', 2), ('two_current_replays', 3)]:
                counts[label][name] += any(row['rank'] >= minimum for row in subset)
        tested = sorted({row['card_id'] for row in items if row['rank'] >= 1})
        group_row = {'group_id': group, 'implementation_candidate': group in candidate_groups, 'primary_reviewed_member_ids': tested, 'members_without_own_primary_reviewed_scenario': sorted(set(members[group]) - set(tested)), 'saved_primary_scenario_count': len({row['slug'] for row in items}), 'all_profiles_representative': representative(items), 'four_live_representative': representative(four), 'sparse_four_live_first_pass': bool(any(row['rank'] >= 2 for row in four))}
        failed_profiles = [{'card_id': row['card_id'], 'slug': row['slug'], 'reasons': row['four_live_profile']['reasons']} for row in items if row.get('four_live_profile') and not row['four_live_profile']['verified']]
        if failed_profiles:
            group_row['unverified_four_live_profiles'] = failed_profiles
        if blocked[group]:
            group_row['blocked_or_held_primary_scenarios'] = sorted(blocked[group], key=lambda row: (row['slug'], row['card_id']))
        output_rows.append(group_row)
    four_groups = {row['group_id'] for row in output_rows if row['sparse_four_live_first_pass']}
    summary = {'catalog_functional_ids': len(cards), 'catalog_groups': len(members), 'implementation_candidate_ids': len(candidate_ids), 'implementation_candidate_groups': len(candidate_groups), 'groups_with_any_saved_primary_scenario': counts['all_profiles']['recorded'], 'all_profiles': dict(counts['all_profiles']), 'actual_four_live': dict(counts['actual_four_live']), 'sparse_four_live_first_pass_groups': len(four_groups), 'catalog_groups_remaining_without_qualified_four_live_representative': len(members) - len(four_groups), 'implementation_candidate_groups_with_qualified_four_live_representative': len(four_groups & candidate_groups), 'implementation_candidate_groups_remaining_without_qualified_four_live_representative': len(candidate_groups - four_groups), 'represented_or_blocked_group_rows': len(output_rows)}
    return {'schema_version': 2, 'status': 'CURRENT_SPARSE_CYCLE_PRIMARY_EVIDENCE_ONLY', 'runtime_sha256': runtime, 'summary': summary, 'policy': ['One exact reviewed primary-member fixture with a verified four-live profile and at least one current clean replay report represents its own group for sparse four-live first-pass breadth. New fixtures retain two clean replays at creation; after a shared-source change, one current compatibility pass can revalidate unchanged reviewed baseline fixtures. Two-current-replay diagnostics and finite completion gates remain separate and unchanged.', 'Sibling pitch variants inherit no tested branch or complete-card status. Groups absent from these rows have no saved primary scenario and remain uncovered.', 'Implementation flags identify candidates for investigation, not validated implemented behavior.', 'Secondary/support cards are excluded from primary counts; their separate original effect reviews remain available in the original metadata.', 'Recorded, reviewed, one-current-replay and two-current-replay facts remain separate. Global native control/suite/full-inventory readiness and finite completion remain separate existing gates.'], 'existing_global_qualification': {key: coverage.get('evidence_qualification', {}).get(key) for key in ['read_snapshot_stable', 'full_current_inventory_clean_reports', 'qualified_current_native_suite_reports', 'current_controls_report_qualified']}, 'finite_card_coverage_separate': {'declared_scopes': coverage['summary'].get('finite_completion_scopes_declared'), 'currently_complete_declared_baselines': coverage['summary'].get('fully_covered_identities')}, 'groups': output_rows}


def build(metadata, repo, coverage_path, group_path):
    sys.path.insert(0, str(metadata))
    from qualify_card_regression_coverage import fixture_inventory, make_proof_verifier
    from runtime_fingerprint import runtime_fingerprint
    watched = {}

    def read(path):
        raw = path.read_bytes()
        watched[path.resolve()] = hashlib.sha256(raw).hexdigest()
        return json.loads(raw)

    coverage = read(coverage_path)
    group_document = read(group_path)
    review_document = read(metadata / 'card-regression-review.json')
    runtime = runtime_fingerprint(repo)
    require(runtime == coverage.get('runtime_sha256'), 'Coverage manifest is stale for current runtime; rebuild it first')
    require(coverage.get('evidence_qualification', {}).get('read_snapshot_stable') is True, 'Coverage manifest lacks a stable qualification read')
    root = repo / 'Tests/Integration/FaBSim'
    fixtures = fixture_inventory(root)
    require(all(not fact.get('disposable_control') for fact in fixtures.values()), 'Disposable native controls are active; wait for cleanup')
    inventory = coverage_inventory(coverage, fixtures)
    spec_cache = {}
    report_names = {entry['run'] for card in coverage['cards'] for branch in card['effect_branches'] for entry in branch.get('replays', [])}
    reports = {name: read(direct_file(metadata, name)) for name in sorted(report_names)}
    schema = repo / 'Schemas/FaBSim/GameSchema.txt'
    schema_text = schema.read_text(encoding='utf-8-sig')
    watched[schema.resolve()] = sha(schema)
    profiles = {}

    def case_for(name, slug):
        if name not in spec_cache:
            spec_cache[name] = read(direct_file(metadata, name))
        matches = [case for case in spec_cache[name] if case.get('slug') == slug]
        require(len(matches) == 1, 'Scenario source must contain exactly one primary slug: ' + slug)
        return matches[0]

    def profile_for(case, slug, digest):
        if slug not in profiles:
            folder = (root / slug).resolve()
            require(folder.is_relative_to(root.resolve()), 'Unsafe fixture slug')
            initial_path = folder / PARTS[0]
            initial = snapshot(initial_path.read_text(encoding='utf-8-sig'), schema_text)
            assertions = read(folder / 'assertions.json')
            actions = read(folder / 'actions.json')
            watched[initial_path] = sha(initial_path)
            profile = four_live_profile(case, initial, assertions, actions)
            profile['initial_sha256'] = watched[initial_path]
            profile['fixture_sha256'] = digest
            profiles[slug] = profile
        return profiles[slug]

    native_proof_verifier = make_proof_verifier(metadata, root)

    def proof_verifier(review, slug, digest):
        valid = native_proof_verifier(review, slug, digest)
        name = review.get('review_evidence', review.get('evidence_file'))
        if valid and name:
            path = (metadata / name).resolve()
            watched[path] = review.get('review_evidence_sha256', review.get('evidence_sha256'))
        return valid

    result = summarize(coverage, group_document, review_document.get('scenarios', {}), fixtures, reports, proof_verifier, case_for, profile_for, review_document.get('semantically_held_saved_fixtures'), review_document.get('held_noncanonical_attempts'))
    require(all(sha(path) == digest for path, digest in watched.items()), 'Metadata inputs changed during cycle summary')
    require(fixtures == fixture_inventory(root) and runtime_fingerprint(repo) == runtime, 'Fixture inventory or runtime changed during cycle summary')
    result['generated_utc'] = datetime.datetime.now(datetime.timezone.utc).isoformat(timespec='seconds').replace('+00:00', 'Z')
    result['inventory'] = inventory
    result['inputs'] = {'coverage': {'path': str(coverage_path), 'sha256': watched[coverage_path.resolve()]}, 'group_partition': {'path': str(group_path), 'sha256': watched[group_path.resolve()]}, 'review_manifest_sha256': watched[(metadata / 'card-regression-review.json').resolve()], 'source_specifications_read': len(spec_cache), 'verified_four_live_profile_candidates': len(profiles), 'all_inputs_stable': True}
    return result


def main():
    here = pathlib.Path(__file__).resolve().parent
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--metadata-root', type=pathlib.Path, default=here)
    parser.add_argument('--repo', type=pathlib.Path)
    parser.add_argument('--coverage', type=pathlib.Path)
    parser.add_argument('--groups', type=pathlib.Path)
    parser.add_argument('--output', type=pathlib.Path)
    args = parser.parse_args()
    metadata = args.metadata_root.resolve()
    repo = args.repo.resolve() if args.repo else next((parent for parent in metadata.parents if (parent / 'Core').is_dir()), None)
    require(repo is not None, 'Cannot locate TCGEngine repository')
    output = args.output or metadata / 'cycle-coverage-current.json'
    result = build(metadata, repo, args.coverage or metadata / 'card-regression-coverage.json', args.groups or metadata / 'cycle-group-map.compact.json')
    output.write_text(json.dumps(result, indent=2, ensure_ascii=True) + '\n', encoding='utf-8')
    print(json.dumps(result['summary'], indent=2))


if __name__ == '__main__':
    main()
