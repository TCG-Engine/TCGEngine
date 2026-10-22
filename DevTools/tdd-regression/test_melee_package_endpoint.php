<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_melee_package_endpoint.php
//
// Tournament imports are moderator-only (2026-10-22): APIs/ImportMeleeTournamentPackage.php takes up to five
// packages (DevTools/melee-package.php) from the mods-only picker on Stats/MeleeTournaments.php, and the old
// paste-a-link importer (APIs/FindOrImportMeleeTournament.php) — which melee 403s on the droplet anyway —
// is closed to everyone else.
// A request with a non-loopback Host is "someone on the internet": no session, not strict loopback.
// A loopback request counts as a moderator (CheckLoggedInUserModStrict). Nothing is written: the only package
// uploaded is one the local database already has, which answers already_imported.
header('Content-Type: text/plain');
require_once __DIR__ . '/../../Database/ConnectionManager.php';
require_once __DIR__ . '/../../Stats/MeleePackage.php';

function post(string $path, array|string $fields, bool $asMod, string $method = 'POST'): array {
    $ch = curl_init('http://localhost/TCGEngine/' . $path);
    $opts = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 120, CURLOPT_CUSTOMREQUEST => $method];
    if ($method === 'POST') $opts[CURLOPT_POSTFIELDS] = $fields;
    if (!$asMod) $opts[CURLOPT_HTTPHEADER] = ['Host: swustats.invalid'];
    curl_setopt_array($ch, $opts);
    $raw = (string)curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, json_decode($raw, true), $raw];
}
$fixture = __DIR__ . '/fixtures/melee-437850.json.gz';
$file = fn($path, $name = null) => new CURLFile($path, 'application/gzip', $name ?? basename($path));
$junk = tempnam(sys_get_temp_dir(), 'pkg');
file_put_contents($junk, 'this is not a package');

$checks = [];
$EP = 'APIs/ImportMeleeTournamentPackage.php';

// ── not a moderator ───────────────────────────────────────────────────────────────────────────────
[$code, $j] = post($EP, ['packages[0]' => $file($fixture)], false);
$checks['package import: non-moderator gets 403 JSON'] = $code === 403 && is_array($j) && ($j['success'] ?? null) === false;
[$code, $j] = post('APIs/FindOrImportMeleeTournament.php', '{"melee_url":"https://melee.gg/Tournament/View/437850"}', false);
$checks['link import: non-moderator gets 403 JSON'] = $code === 403 && is_array($j) && ($j['success'] ?? null) === false;

// ── moderator ─────────────────────────────────────────────────────────────────────────────────────
[$code, $j] = post($EP, [], true, 'GET');
$checks['package import: GET is 405'] = $code === 405;
[$code, $j] = post($EP, ['nothing' => '1'], true);
$checks['package import: no files is 400'] = $code === 400 && is_array($j) && isset($j['message']);
$six = [];
for ($i = 0; $i < SWU_MELEE_PACKAGE_MAX_FILES + 1; $i++) $six["packages[$i]"] = $file($junk, "junk$i.json.gz");
[$code, $j] = post($EP, $six, true);
$checks['package import: more than five files is 400, nothing imported'] = $code === 400 && !isset($j['results']);

// The link importer still answers a moderator exactly as before (bad input → its old message).
[$code, $j] = post('APIs/FindOrImportMeleeTournament.php', '{"melee_url":"not a url"}', true);
$checks['link import: a moderator still reaches it'] = ($j['message'] ?? null) === 'Invalid or missing melee.gg URL.';

// One bad file and one good one: each answers for itself.
$conn = GetLocalMySQLConnection();
$local = SWUMeleeFindTournament($conn, '437850');
$conn->close();
$checks['precondition: the local database already has melee 437850'] = $local !== null && $local['deckCount'] > 0;
if ($local !== null && $local['deckCount'] > 0) {
    [$code, $j] = post($EP, ['packages[0]' => $file($junk, 'junk.json.gz'), 'packages[1]' => $file($fixture)], true);
    $res = $j['results'] ?? [];
    $checks['package import: 200 with one result per file'] = $code === 200 && count($res) === 2;
    $checks['package import: the junk file failed, named'] = ($res[0]['status'] ?? null) === 'failed' && ($res[0]['file'] ?? null) === 'junk.json.gz';
    $checks['package import: the package is already_imported → its tournament'] = ($res[1]['status'] ?? null) === 'already_imported'
        && (int)($res[1]['tournament_id'] ?? 0) === $local['tournamentID'];
    $checks['package import: the result carries name and counts'] = ($res[1]['name'] ?? '') !== '' && ($res[1]['decks'] ?? null) === $local['deckCount'];
    $checks['package import: success is false when any file failed'] = ($j['success'] ?? null) === false;
}
unlink($junk);

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
foreach ($checks as $name => $ok) echo ($ok ? '  ok  ' : '  FAIL ') . $name . "\n";
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL (" . count($fails) . " of " . count($checks) . ")\n";
