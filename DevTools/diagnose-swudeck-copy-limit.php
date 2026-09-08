<?php
// Read-only diagnostic for "SWUDeck won't let me add a 4th Swarming Vulture Droid".
//
// Run it on the machine that shows the problem. It answers three questions in one shot:
//   1. Is the fixed code actually LIVE in this PHP process (vs. present on disk but stale in opcache)?
//   2. What copy limit does each format resolve to right now?
//   3. For one deck: what format is it tagged, and how many copies does the gate already count?
//
// ⚠ Run it through the WEB server, not just the CLI — the CLI has its own opcache, so a CLI run
//   cannot see stale bytecode in Apache. Both work; the SAPI is reported.
//     web: <site>/TCGEngine/DevTools/diagnose-swudeck-copy-limit.php?gameName=123456
//     cli: php DevTools/diagnose-swudeck-copy-limit.php 123456
// It writes nothing and prints no user data beyond the deck's format and card counts.

if (php_sapi_name() !== 'cli') header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED);

$gameName = $_GET['gameName'] ?? ($argv[1] ?? null);
$CARD     = 'JTL_256';   // Swarming Vulture Droid — "a deck can have up to 15 copies of this card"
$root     = dirname(__DIR__);

$line = fn($k, $v) => printf("%-38s %s\n", $k . ':', $v);
$copies = fn($n) => $n === PHP_INT_MAX ? 'unlimited' : $n;

echo "=== environment ===\n";
$line('sapi', php_sapi_name());
$line('php', PHP_VERSION);

echo "\n=== is the fix live? ===\n";
$validator = $root . '/SWUDeck/Custom/DeckValidation.php';
$onDisk = is_file($validator) ? file_get_contents($validator) : '';
$line('DeckValidation.php on disk', is_file($validator)
    ? date('Y-m-d H:i:s', filemtime($validator)) . '  sha1=' . substr(sha1($onDisk), 0, 12)
    : 'MISSING at ' . $validator);
$line('  contains SWUDeckMaxCopies', strpos($onDisk, 'function SWUDeckMaxCopies') !== false ? 'YES' : 'NO — the deploy did not land');

include_once $root . '/SWUDeck/Custom/CardIdentifiers.php';   // dictionary: SWUNormalizeDictionaryKey
include_once $validator;

$live = function_exists('SWUDeckMaxCopies');
$line('SWUDeckMaxCopies() loaded', $live ? 'YES' : 'NO');
if ($live) {
    $r = new ReflectionFunction('SWUDeckMaxCopies');
    $line('  declared in', $r->getFileName() . ':' . $r->getStartLine());
}
if (function_exists('opcache_get_status')) {
    $st = @opcache_get_status(true);
    $cfg = @opcache_get_configuration();
    $line('opcache enabled', !empty($st['opcache_enabled']) ? 'YES' : 'no');
    $line('  validate_timestamps', var_export($cfg['directives']['opcache.validate_timestamps'] ?? null, true)
        . ' (false = a git pull alone will NOT take effect; the web server must be reloaded)');
    $line('  revalidate_freq', var_export($cfg['directives']['opcache.revalidate_freq'] ?? null, true));
    foreach ([$validator, $root . '/AppCore/SWU/Formats.php'] as $f) {
        $entry = $st['scripts'][$f] ?? null;
        $line('  cached: ' . basename($f), $entry
            ? 'compiled ' . date('Y-m-d H:i:s', $entry['timestamp']) . ' vs file ' . date('Y-m-d H:i:s', filemtime($f))
              . ($entry['timestamp'] < filemtime($f) ? '   <-- STALE' : '')
            : 'not cached');
    }
} else {
    $line('opcache', 'not available in this SAPI');
}

echo "\n=== resolved copy limits for $CARD ===\n";
if ($live) {
    $line('canonical id', SWUDeckCanonicalCardID($CARD) . '   (must be JTL_256)');
    foreach (array_keys(SWUFormatDefinitions()) as $fid) {
        $line('  ' . $fid, $copies(SWUDeckMaxCopies($CARD, $fid)) . ' copies   (ordinary card: '
            . $copies(SWUDeckMaxCopies('JTL_033', $fid)) . ')');
    }
} else {
    echo "  (skipped — the function is not loaded)\n";
}

echo "\n=== deck ===\n";
if ($gameName === null) {
    echo "  pass ?gameName=<deck id> (or an argv) to check one deck\n";
} else {
    include_once $root . '/AccountFiles/AccountDatabaseAPI.php';
    include_once $root . '/Database/ConnectionManager.php';
    $asset = LoadAssetData(1, $gameName);
    if (!$asset) {
        echo "  no ownership row for deck $gameName\n";
    } else {
        $fmt = $asset['format'] ?? null;
        $line('gameName', $gameName);
        $line('format (raw)', var_export($fmt, true) . '  hex=' . bin2hex((string)$fmt));
        $line('format resolves', SWUGetFormat($fmt) === null ? 'NO — falls back to premier' : 'yes');
        if ($live) $line("limit for $CARD here", $copies(SWUDeckMaxCopies($CARD, $fmt)));
    }
    // Count the card in the saved deck file the same way the gate does, without booting the engine.
    $gs = $root . '/SWUDeck/Games/' . basename((string)$gameName) . '/Gamestate.txt';
    if (is_file($gs)) {
        $ids = preg_split('/\r\n|\r|\n/', file_get_contents($gs));
        $hits = 0;
        foreach ($ids as $id) {
            $id = trim($id);
            if ($id === '' || !$live) continue;
            if (SWUDeckCanonicalCardID($id) === $CARD) $hits++;
        }
        $line('copies of ' . $CARD . ' in the file', $hits . '   (whole file, all zones — indicative, not the gate\'s count)');
    } else {
        $line('Gamestate.txt', 'not found at ' . $gs);
    }
}
echo "\ndone\n";
