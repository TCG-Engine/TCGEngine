<?php
// Which database does this process connect to?
//
// Apache hands every HTTP request its database through `SetEnv MYSQL_DATABASE_NAME` in the
// matching vhost, so the Host header decides the database (see newhost/provision-vhost.sh). The
// env is therefore the AUTHORITY here and nothing may override it: a request that reaches PHP with
// the var set behaves exactly as it always has.
//
// `SetEnv` does not exist outside a request. Anything run from a shell -- the code generator,
// migrations, DevTools, cron -- used to get no env at all and silently fall through to a hardcoded
// `swuonline` default, connecting to whatever that name happened to mean on the box. That is the
// hole this file closes: a CLI run may name its app instead, and anything unresolved THROWS.

require_once __DIR__ . '/SiteRegistry.php';   // presence check; the array comes from SiteRegistry()

// The registry, read once per process.
function SiteRegistry()
{
    static $registry = null;
    if ($registry === null) $registry = require __DIR__ . '/SiteRegistry.php';
    return $registry;
}

// db name -> the ONE root that renders as its site, or null when nothing maps.
// SharedUI/ActiveSite.php is the caller; it is deliberately the reverse of ResolveDatabaseName().
function SiteForDatabase($database)
{
    foreach (SiteRegistry() as $rootName => $entry) {
        if ($entry['site'] && $entry['db'] === $database) return $rootName;
    }
    return null;
}

// Which app is this CLI process running as? Explicit wins over inferred:
//   1. $GLOBALS['rootName'] -- set by zzGameCodeGenerator.php and friends
//   2. a `rootName=VALUE` argument, the convention those scripts already accept
//   3. the app folder the running script lives in (HellbreakDeck/CreateDeck.php -> HellbreakDeck)
// Returns '' when none applies -- e.g. a root-level script, which must set the env itself.
function ResolveRootNameFromProcess()
{
    if (isset($GLOBALS['rootName']) && is_string($GLOBALS['rootName']) && $GLOBALS['rootName'] !== '') {
        return $GLOBALS['rootName'];
    }
    foreach (($GLOBALS['argv'] ?? []) as $arg) {
        if (is_string($arg) && strncmp($arg, 'rootName=', 9) === 0) {
            $value = substr($arg, 9);
            if ($value !== '') return $value;
        }
    }
    $script = realpath($GLOBALS['argv'][0] ?? '');
    $repoRoot = realpath(__DIR__ . '/..');
    if ($script === false || $repoRoot === false) return '';
    $prefix = $repoRoot . DIRECTORY_SEPARATOR;
    if (strncmp($script, $prefix, strlen($prefix)) !== 0) return '';
    $segments = explode(DIRECTORY_SEPARATOR, substr($script, strlen($prefix)));
    // A script sitting directly in the repo root has no app folder above it, only a filename.
    return count($segments) > 1 ? $segments[0] : '';
}

// The database for this process. Throws rather than guessing: connecting to the wrong database
// silently is worse than a hard failure, and it is the failure mode this replaced.
//
// $sapi is a seam for the tests; every caller uses the default.
function ResolveDatabaseName($sapi = PHP_SAPI)
{
    // An empty value counts as absent, matching the `?:` this replaced -- Apache can hand over ''
    // for a SetEnv with no value, and a database named '' does not exist.
    $fromEnv = getenv('MYSQL_DATABASE_NAME');
    if ($fromEnv !== false && $fromEnv !== '') return $fromEnv;

    // The rootName fallback exists for the command line ONLY. Apache always sets the var, so a web
    // request that arrives without it means the vhost is broken -- and falling back would let, say,
    // HellbreakDeck quietly resolve to hellbreaksim and keep serving, hiding the misconfiguration
    // instead of surfacing it. Fail the same way SharedUI/ActiveSite.php does.
    if ($sapi !== 'cli') {
        throw new RuntimeException(
            'DatabaseResolution: MYSQL_DATABASE_NAME is not set for this request; the serving vhost '
            . 'is missing its SetEnv (see newhost/provision-vhost.sh). Refusing to guess a database.'
        );
    }

    $rootName = ResolveRootNameFromProcess();
    if ($rootName === '') {
        throw new RuntimeException(
            'DatabaseResolution: MYSQL_DATABASE_NAME is not set and no rootName could be derived '
            . 'from this process; set MYSQL_DATABASE_NAME, or pass rootName=<App>.'
        );
    }

    $registry = SiteRegistry();
    if (!isset($registry[$rootName])) {
        throw new RuntimeException(
            "DatabaseResolution: MYSQL_DATABASE_NAME is not set and rootName '$rootName' has no "
            . 'database in Database/SiteRegistry.php; set MYSQL_DATABASE_NAME for this run, or add '
            . 'the root to the registry if it owns a database.'
        );
    }
    return $registry[$rootName]['db'];
}
