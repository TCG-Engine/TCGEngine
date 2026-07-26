<?php
// Test: the card-file loader includes every cards/<set>/*.php and their
// registrations land in the global ability arrays.
$cardsDir = __DIR__ . '/../../../Custom/cards';
$dir = "$cardsDir/zzz_test";
@mkdir($dir, 0777, true);
file_put_contents("$dir/Dummy.php", "<?php\n\$whenPlayedAbilities['ZZ_999'] = function(){ return 42; };\n");
// A `_`-prefixed file in a set dir must be skipped by the loader.
file_put_contents("$dir/_skipme.php", "<?php\nthrow new Exception('should not be included');\n");

$whenPlayedAbilities = [];
require "$cardsDir/_loader.php";

assert(isset($whenPlayedAbilities['ZZ_999']), 'ZZ_999 not registered by loader');
assert(($whenPlayedAbilities['ZZ_999'])() === 42, 'ZZ_999 closure wrong');

unlink("$dir/Dummy.php");
unlink("$dir/_skipme.php");
rmdir($dir);
echo "OK\n";
