<?php

// One registry decides BOTH which database a request connects to and which site renders
// (SharedUI/ActiveSite.php). These tests pin the two directions against each other so the pair
// can never drift: a root that connects to `hellbreaksim` must be the same root ActiveSite
// renders for `hellbreaksim`.
//
// The other half of the file guards the fallback chain. Apache SetEnv only exists for HTTP
// requests, so anything run from a shell -- the generator, migrations, DevTools, cron -- used to
// get NO env and silently connect to a hardcoded `swuonline` default instead of failing. These
// tests fix the replacement behaviour: env always wins, a CLI run may fall back to its rootName,
// and anything unresolved THROWS.

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '1');
chdir(dirname(__DIR__, 2));

include_once './Database/DatabaseResolution.php';

$failures = 0;
$checks = 0;
$check = function($condition, string $message) use (&$failures, &$checks): void {
    ++$checks;
    $ok = boolval($condition);
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $message . PHP_EOL;
    if(!$ok) ++$failures;
};

// Returns the thrown message, or '' when the callable returned normally.
$throws = function(callable $fn): string {
    try { $fn(); return ''; }
    catch (Throwable $e) { return $e->getMessage(); }
};

$withEnvAndRoot = function($db, $rootName, callable $fn) {
    $priorEnv  = getenv('MYSQL_DATABASE_NAME');
    $priorRoot = $GLOBALS['rootName'] ?? null;
    if($db === null) putenv('MYSQL_DATABASE_NAME');
    else putenv('MYSQL_DATABASE_NAME=' . $db);
    if($rootName === null) unset($GLOBALS['rootName']);
    else $GLOBALS['rootName'] = $rootName;
    try { return $fn(); }
    finally {
        if($priorEnv === false) putenv('MYSQL_DATABASE_NAME');
        else putenv('MYSQL_DATABASE_NAME=' . $priorEnv);
        if($priorRoot === null) unset($GLOBALS['rootName']);
        else $GLOBALS['rootName'] = $priorRoot;
    }
};

// ---------------------------------------------------------------------------
// Resolution order: the env is the authority, and nothing may override it.
// ---------------------------------------------------------------------------
// This is what keeps the change invisible to every HTTP request: Apache always sets the var, so
// the vhost SetEnv decides the database exactly as it did before, whatever rootName is in play.
$check($withEnvAndRoot('hellbreaksim', 'SWUSim', fn() => ResolveDatabaseName()) === 'hellbreaksim',
    'MYSQL_DATABASE_NAME wins over a conflicting rootName');

// ---------------------------------------------------------------------------
// CLI fallback: rootName -> database, only when the env is absent.
// ---------------------------------------------------------------------------
$check($withEnvAndRoot(null, 'HellbreakDeck', fn() => ResolveDatabaseName()) === 'hellbreaksim',
    'HellbreakDeck falls back to the hellbreaksim database');
$check($withEnvAndRoot(null, 'HellbreakSim', fn() => ResolveDatabaseName()) === 'hellbreaksim',
    'HellbreakSim falls back to the hellbreaksim database');
$check($withEnvAndRoot(null, 'AzukiDeck', fn() => ResolveDatabaseName()) === 'azukisim',
    'AzukiDeck falls back to its sim database, per AssetReflection');
$check($withEnvAndRoot(null, 'SWUDeck', fn() => ResolveDatabaseName()) === 'swudeck',
    'SWUDeck falls back to the swudeck database');

// An empty env var is as absent as an unset one -- Apache can hand over "" for a SetEnv with no
// value, and `?: ` treated that as missing before. Keep that.
$check($withEnvAndRoot('', 'HellbreakDeck', fn() => ResolveDatabaseName()) === 'hellbreaksim',
    'an EMPTY MYSQL_DATABASE_NAME is treated as absent, not as a database named ""');

// ---------------------------------------------------------------------------
// Unresolvable => throw. This is the whole point: no silent `swuonline`.
// ---------------------------------------------------------------------------
// FaBSim has no database of its own -- no docker-compose service, no ActiveSite entry. Inventing
// one would be exactly the silent-wrong-database bug we are removing.
$msg = $withEnvAndRoot(null, 'FaBSim', fn() => $throws(fn() => ResolveDatabaseName()));
$check($msg !== '', 'a rootName with no database throws instead of guessing');
$check(stripos($msg, 'FaBSim') !== false, 'the throw names the unresolved rootName');

$msg = $withEnvAndRoot(null, null, fn() => $throws(fn() => ResolveDatabaseName()));
$check($msg !== '', 'no env and no resolvable rootName throws');
$check(stripos($msg, 'MYSQL_DATABASE_NAME') !== false,
    'the throw names the env var an operator has to set');

$check(strpos(file_get_contents('./Database/ConnectionManager.php'), 'swuonline') === false,
    'ConnectionManager no longer carries a hardcoded database fallback');

// ---------------------------------------------------------------------------
// The rootName fallback is for the COMMAND LINE only.
// ---------------------------------------------------------------------------
// Apache always sets the var, so a web request arriving without it means the vhost is broken. If
// the fallback ran there too, HellbreakDeck would quietly resolve to hellbreaksim and keep working
// -- masking exactly the misconfiguration that took northbeach.gg down. A web request with no env
// must fail loudly instead, the same way SharedUI/ActiveSite.php does.
$msg = $withEnvAndRoot(null, 'HellbreakDeck',
    fn() => $throws(fn() => ResolveDatabaseName('apache2handler')));
$check($msg !== '', 'a WEB request with no env throws instead of falling back to rootName');
$check(stripos($msg, 'MYSQL_DATABASE_NAME') !== false,
    'the web-request throw names the env var the vhost is missing');
$check($withEnvAndRoot(null, 'HellbreakDeck', fn() => ResolveDatabaseName('cli')) === 'hellbreaksim',
    'the same rootName still resolves under the CLI SAPI');

// ---------------------------------------------------------------------------
// Registry integrity.
// ---------------------------------------------------------------------------
$registry = SiteRegistry();
$check(count($registry) > 0, 'the registry is non-empty');

$siteRootsPerDb = [];
foreach($registry as $rootName => $entry) {
    $wellFormed = is_array($entry) && isset($entry['db']) && array_key_exists('site', $entry)
        && is_string($entry['db']) && $entry['db'] !== '' && is_bool($entry['site']);
    $check($wellFormed, "registry entry '$rootName' has a string db and a boolean site flag");
    if(!$wellFormed) continue;
    if($entry['site']) $siteRootsPerDb[$entry['db']][] = $rootName;
}

// The reverse lookup (db -> site) has to be unambiguous: HellbreakSim and HellbreakDeck share a
// database, but only ONE of them may render as the site. Without this, ActiveSite's answer would
// depend on array order.
foreach($registry as $rootName => $entry) {
    if(!is_array($entry) || !isset($entry['db'])) continue;
    $db = $entry['db'];
    $check(count($siteRootsPerDb[$db] ?? []) === 1,
        "database '$db' has exactly one site root (got: " . implode(', ', $siteRootsPerDb[$db] ?? []) . ')');
}

// A root folder must exist for every registry entry -- a typo'd rootName would otherwise resolve
// happily and fail much later, at an include.
foreach(array_keys($registry) as $rootName) {
    $check(is_dir('./' . $rootName), "registry root '$rootName' is a real app folder");
}

// ---------------------------------------------------------------------------
// ActiveSite reads the SAME registry, and its contract is unchanged.
// ---------------------------------------------------------------------------
$activeSite = function($db) use ($withEnvAndRoot) {
    return $withEnvAndRoot($db, null, fn() => require './SharedUI/ActiveSite.php');
};

// Pinned to what ActiveSite's own $dbToSite map returned before the registry existed. If a
// refactor changes any of these, a live site starts rendering as a different site.
$historicalPairs = [
    'swudeck'         => 'SWUDeck',
    'grandarchivesim' => 'GrandArchiveSim',
    'azukisim'        => 'AzukiSim',
    'swusim'          => 'SWUSim',
    'hellbreaksim'    => 'HellbreakSim',
];
foreach($historicalPairs as $db => $expectedSite) {
    $check($activeSite($db) === $expectedSite, "ActiveSite still resolves '$db' to $expectedSite");
}

// ActiveSite must keep throwing rather than falling back -- serving the wrong site silently is
// worse than a 500, and a deck-shaped root must never render as a site.
$msg = $throws(fn() => $activeSite(null));
$check($msg !== '', 'ActiveSite throws when MYSQL_DATABASE_NAME is unset');
$check(stripos($msg, 'MYSQL_DATABASE_NAME') !== false,
    'the unset-env throw still names MYSQL_DATABASE_NAME');

$msg = $throws(fn() => $activeSite('not_a_real_database'));
$check($msg !== '', 'ActiveSite throws for a database with no site');
$check(stripos($msg, 'not_a_real_database') !== false, 'the unmapped throw names the database');

if($failures > 0) {
    fwrite(STDERR, PHP_EOL . "FAILED: {$failures} of {$checks} checks." . PHP_EOL);
    exit(1);
}
echo PHP_EOL . "ALL PASS ({$checks} checks)" . PHP_EOL;

?>
