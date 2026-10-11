<?php
// The Rematch inputs an Arenabot game persists at creation and hands back at game end.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/botdata_rematch_test.php
chdir('/var/www/html/TCGEngine');
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
require_once './SWUSim/BotDataRematch.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$dir = sys_get_temp_dir() . '/botdata_rematch_' . getmypid();
@mkdir($dir, 0777, true);

$check(SWUBotDataReadRematch($dir) === null, 'a game with no Rematch.json offers no rematch');

SWUBotDataWriteRematch($dir, ['deckLink' => 'LEADER-A', 'deckLink2' => 'LEADER-B',
                              'botStyle' => 'softaggro', 'cardPool' => 'premier']);
$r = SWUBotDataReadRematch($dir);
$check(is_array($r), 'a botpractice game offers a rematch');
$check(($r['deckLink'] ?? '') === 'LEADER-A' && ($r['deckLink2'] ?? '') === 'LEADER-B', 'both decks carry over');
$check(($r['botStyle'] ?? '') === 'softaggro', 'the bot style carries over');
$check(($r['cardPool'] ?? '') === 'premier', 'the card pool carries over');
$check(($r['format'] ?? '') === 'botpractice', 'the format is botpractice');
$blob = strval(file_get_contents($dir . '/Rematch.json'));
$check(stripos($blob, 'authKey') === false && stripos($blob, 'userId') === false,
    'no identity is persisted — a rematch mints fresh credentials through JoinQueue.php');

// A half-known game must offer NO rematch rather than create a broken one.
SWUBotDataWriteRematch($dir, ['deckLink' => 'LEADER-A', 'deckLink2' => '',
                              'botStyle' => 'softaggro', 'cardPool' => 'premier']);
$check(SWUBotDataReadRematch($dir) === null, 'a missing second deck offers no rematch');

// A corrupt file is "no rematch", not a fatal.
file_put_contents($dir . '/Rematch.json', 'not json at all');
$check(SWUBotDataReadRematch($dir) === null, 'a corrupt Rematch.json offers no rematch rather than throwing');

array_map('unlink', glob($dir . '/*') ?: []); @rmdir($dir);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
