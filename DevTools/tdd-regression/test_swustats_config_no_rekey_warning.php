<?php
// SWUStatsOAuthConfig() inside a request that ALREADY loaded the keys file (the game's ProcessInput.php loads
// APIKeys/APIKeys.php at global scope) must not raise a warning re-reading it: the file declares a const (see
// APIKeys.php.template: OPENAI_API_KEY), so a second plain require warns "Constant already defined" — inside the
// player's last action of a match, where stray output can corrupt the response. (spec 2026-10-10 §1/§3)
// Uses a TEMP keys file shaped like the template; the real APIKeys.php is never touched.
//
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swustats_config_no_rekey_warning.php
header('Content-Type: text/plain');
error_reporting(E_ALL);
chdir(dirname(__DIR__, 2));
require_once './SWUSim/SWUStatsLink.php';

$keys = sys_get_temp_dir() . '/swustats-keys-' . getmypid() . '.php';
file_put_contents($keys, "<?php\n\$swustatsClientID = 'from-keys-file';\n\$swustatsClientSecret = 'secret-from-keys-file';\nconst SWUSTATS_TEST_KEYS_CONST = 1;\n");
require $keys;                          // as ProcessInput.php does with the real file, at global scope

$warnings = [];
set_error_handler(function ($no, $str) use (&$warnings) { $warnings[] = $str; return true; });
$cfg = SWUStatsOAuthConfig($keys);
restore_error_handler();
unlink($keys);

$fails = [];
if ($warnings) $fails[] = 'warnings raised: ' . implode(' | ', $warnings);
if (($cfg['clientId'] ?? '') !== 'from-keys-file' || ($cfg['clientSecret'] ?? '') !== 'secret-from-keys-file')
    $fails[] = 'keys not read: ' . json_encode($cfg);
echo $fails ? "FAIL: " . implode('; ', $fails) . "\n" : "PASS (2 checks)\n";
exit($fails ? 1 : 0);
