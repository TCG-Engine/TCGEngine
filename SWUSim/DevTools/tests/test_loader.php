<?php
// Test: the card-file loader includes every cards/<set>/*.php and their registrations
// land in the global ability arrays, and skips `_`-prefixed files.
//
// Runs against an ISOLATED temp cards dir (a copy of the real _loader.php, whose glob is
// relative to its own __DIR__, plus dummy card files) — so it exercises the real loader
// logic without pulling in the whole card corpus (which depends on engine-defined factory
// closures at include time, e.g. $swuAttackWithTraitWhenPlayed).
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

$tmp = sys_get_temp_dir() . '/loadertest_' . getmypid();
@mkdir("$tmp/zz", 0777, true);
file_put_contents("$tmp/zz/Dummy.php", "<?php\n\$whenPlayedAbilities['ZZ_999'] = function(){ return 42; };\n");
// A `_`-prefixed file in a set dir must be skipped by the loader (would throw if included).
file_put_contents("$tmp/zz/_skipme.php", "<?php\nthrow new Exception('should not be included');\n");
copy(__DIR__ . '/../../Custom/cards/_loader.php', "$tmp/_loader.php");   // its __DIR__ glob now targets $tmp

$whenPlayedAbilities = [];
require "$tmp/_loader.php";

check(isset($whenPlayedAbilities['ZZ_999']), 'ZZ_999 not registered by loader');
check(($whenPlayedAbilities['ZZ_999'])() === 42, 'ZZ_999 closure wrong');
// Reaching here proves _skipme.php was NOT included (else its throw would have aborted).

unlink("$tmp/zz/Dummy.php");
unlink("$tmp/zz/_skipme.php");
unlink("$tmp/_loader.php");
rmdir("$tmp/zz");
rmdir($tmp);
echo "OK\n";
