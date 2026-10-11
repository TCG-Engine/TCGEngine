<?php
// Offline self-test for apply-card-ability-patch.php: runs the ops against an in-memory stand-in for the
// ability repository (no database needed). Run: php DevTools/card-db-patches/selftest.php

require_once __DIR__ . '/apply-card-ability-patch.php';

final class FakeAbilityRepo
{
    public array $rows = [];       // cardId => list of row arrays
    public int $nextId = 1;
    public bool $conflictOnReplace = false;
    public int $writes = 0;

    public function seed(string $cardId, string $macro, string $code, ?string $prereq = null, ?string $name = null): void
    {
        $this->rows[$cardId][] = ['id' => $this->nextId++, 'macro_name' => $macro, 'ability_code' => $code, 'prereq_code' => $prereq,
            'ability_name' => $name, 'is_implemented' => 1, 'ability_type' => 'macro', 'listener_zones' => null];
    }
    public function loadCardAbilities($root, $cardId): array { return $this->rows[$cardId] ?? []; }
    public function revisionForCard($root, $cardId): string { return md5(json_encode($this->rows[$cardId] ?? [])); }
    public function saveAbility($id, $root, $cardId, $macro, $code, $prereq = null, $name = null, $impl = 0, $type = 'macro', $zones = null)
    {
        ++$this->writes;
        foreach ($this->rows[$cardId] as &$row) {
            if ($row['id'] === $id) { $row['macro_name'] = $macro; $row['ability_code'] = $code; return $id; }
        }
        return false;
    }
    public function replaceCardAbilities($root, $cardId, array $abilities, bool $impl, ?string $baseRevision): array
    {
        if ($this->conflictOnReplace) throw new RuntimeException('Card abilities changed since they were loaded', 409);
        ++$this->writes;
        $this->rows[$cardId] = [];
        foreach ($abilities as $a) $this->seed($cardId, $a['macroName'], $a['abilityCode'], $a['prereqCode'] ?? null, $a['abilityName'] ?? null);
        return ['abilities' => $this->rows[$cardId], 'revision' => $this->revisionForCard($root, $cardId)];
    }
}

$failures = 0;
function Check(string $label, bool $condition): void
{
    global $failures;
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $label . "\n";
    if (!$condition) ++$failures;
}
function States(array $results): array { return array_map(fn($r) => $r[2], $results); }

$verified = json_decode(file_get_contents(__DIR__ . '/ga-card-ability-fixes-verified.json'), true);
$lorraine = json_decode(file_get_contents(__DIR__ . '/ga-lorraine-pantheon-abilities-unverified.json'), true);

// --- verified patch: database in the OLD state
$repo = new FakeAbilityRepo();
$repo->seed('xpnjvt9y59', 'Enter', 'BanishFromGraveyard();');
$repo->seed('AMv1u54B2s', 'OnAttack', 'AddTurnEffect($mzID, "soO3hjaVfN_DOUBLE");');
[$res, $ok] = RunCardAbilityPatch($repo, $verified, false);
Check('dry run reports both ops PENDING', States($res) === ['PENDING', 'PENDING'] && $ok);
Check('dry run writes nothing', $repo->writes === 0 && $repo->rows['xpnjvt9y59'][0]['macro_name'] === 'Enter');

[$res, $ok] = RunCardAbilityPatch($repo, $verified, true);
Check('apply reports both ops FIXED', States($res) === ['FIXED', 'FIXED'] && $ok);
Check('set-macro moved Enter -> CardActivated', $repo->rows['xpnjvt9y59'][0]['macro_name'] === 'CardActivated');
Check('replace-text corrected the tag', $repo->rows['AMv1u54B2s'][0]['ability_code'] === 'AddTurnEffect($mzID, "AMv1u54B2s_DOUBLE");');

$writes = $repo->writes;
[$res, $ok] = RunCardAbilityPatch($repo, $verified, true);
Check('second apply is a no-op (APPLIED, zero writes)', States($res) === ['APPLIED', 'APPLIED'] && $repo->writes === $writes && $ok);

// --- verified patch: rows edited by someone else / card missing -> BLOCKED, never written
$repo = new FakeAbilityRepo();
$repo->seed('xpnjvt9y59', 'OnAttack', 'x');
[$res, $ok] = RunCardAbilityPatch($repo, $verified, true);
Check('unknown state is BLOCKED (and exit not ok)', States($res) === ['BLOCKED', 'BLOCKED'] && !$ok && $repo->writes === 0);

// --- replace-text only touches the matching row of a multi-row card
$repo = new FakeAbilityRepo();
$repo->seed('AMv1u54B2s', 'OnAttack', 'a soO3hjaVfN_DOUBLE b');
$repo->seed('AMv1u54B2s', 'Enter', 'untouched');
RunCardAbilityPatch($repo, ['root' => 'GrandArchiveSim', 'ops' => [$verified['ops'][1]]], true);
Check('replace-text leaves other rows alone', $repo->rows['AMv1u54B2s'][1]['ability_code'] === 'untouched' && str_contains($repo->rows['AMv1u54B2s'][0]['ability_code'], 'AMv1u54B2s_DOUBLE'));

// --- Lorraine patch: author-if-absent
$repo = new FakeAbilityRepo();
$repo->seed('vkL2RFh0yM', '', '', null, '[Card Implemented]'); // empty marker row only
[$res, $ok] = RunCardAbilityPatch($repo, $lorraine, false);
Check('marker-only / missing cards are PENDING', States($res) === ['PENDING', 'PENDING'] && $ok);
[$res, $ok] = RunCardAbilityPatch($repo, $lorraine, true);
Check('apply authors both cards', States($res) === ['FIXED', 'FIXED'] && $ok);
Check('Charm gets ActivateAbility + prereq', $repo->rows['vkL2RFh0yM'][0]['macro_name'] === 'ActivateAbility' && str_contains($repo->rows['vkL2RFh0yM'][0]['prereq_code'], 'gpmJdGYqoC'));
Check('Gale gets CardActivated', $repo->rows['uUWsgLmyTk'][0]['macro_name'] === 'CardActivated');
[$res] = RunCardAbilityPatch($repo, $lorraine, true);
Check('Lorraine rerun is APPLIED', States($res) === ['APPLIED', 'APPLIED']);

$repo = new FakeAbilityRepo();
$repo->seed('vkL2RFh0yM', 'ActivateAbility', 'something else');
[$res, $ok] = RunCardAbilityPatch($repo, ['root' => 'GrandArchiveSim', 'ops' => [$lorraine['ops'][0]]], true);
Check('author-if-absent never overwrites different rows', States($res) === ['BLOCKED'] && !$ok && $repo->rows['vkL2RFh0yM'][0]['ability_code'] === 'something else');

// --- concurrent edit (revision conflict) is reported, not fatal
$repo = new FakeAbilityRepo();
$repo->conflictOnReplace = true;
[$res, $ok] = RunCardAbilityPatch($repo, ['root' => 'GrandArchiveSim', 'ops' => [$lorraine['ops'][0]]], true);
Check('revision conflict -> FAILED with a rerun hint', States($res) === ['FAILED'] && str_contains($res[0][3], 'conflict') && !$ok);

echo $failures === 0 ? "\nAll checks passed.\n" : "\n$failures check(s) FAILED.\n";
exit($failures === 0 ? 0 : 1);
