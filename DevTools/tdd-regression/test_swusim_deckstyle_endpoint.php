<?php
// APIs/SWUBotDeckStyle.php — the Main Menu's deck-style lookup. POSTs to the REAL endpoint over the container's
// loopback, like test_swusim_botpractice_style.php. ⚠ CONTROLS: the endpoint must create NO game and NO lobby, and
// junk input must return ok:false rather than a style.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_swusim_deckstyle_endpoint.php
$ROOT = dirname(__DIR__, 2);
$URL = 'http://localhost/TCGEngine/APIs/SWUBotDeckStyle.php';
$fails = 0;
$check = function ($ok, $msg, $detail = '') use (&$fails) {
    echo ($ok ? 'PASS' : 'FAIL') . ": $msg" . (!$ok && $detail !== '' ? "  [got: $detail]" : '') . "\n";
    if (!$ok) $fails++;
};
$post = function (array $fields) use ($URL) {
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($fields), 'timeout' => 60, 'ignore_errors' => true]]);
    $raw = @file_get_contents($URL, false, $ctx);
    return is_string($raw) ? json_decode($raw, true) : null;
};
$gamesBefore = count(glob($ROOT . '/SWUSim/Games/*') ?: []);

// A) A pasted list (the fixture format the free-text importer accepts).
$list = (string)file_get_contents($ROOT . '/SWUSim/Tests/BotFixtures/meta-2026-09/aggro_vader_yellow.txt');
$a = $post(['rootName' => 'SWUSim', 'deckLink' => $list]);
$check(is_array($a) && ($a['ok'] ?? false) === true, 'a pasted list is classified', json_encode($a));
$check(in_array($a['style'] ?? '', ['hyperaggro', 'softaggro', 'midrange', 'softcontrol', 'hardcontrol'], true),
    'the style is one of the five archetypes', strval($a['style'] ?? ''));
$check(($a['source'] ?? '') === 'label' && ($a['style'] ?? '') === 'hyperaggro',
    'the stock Vader list takes its label', strval($a['source'] ?? '') . '/' . strval($a['style'] ?? ''));
$check(isset($a['reasons']) && is_array($a['reasons']), 'the response carries its reasons');

// A2) deckText is accepted as well as deckLink (the menu's Free Text tab).
$a2 = $post(['rootName' => 'SWUSim', 'deckText' => $list]);
$check(is_array($a2) && ($a2['ok'] ?? false) === true && ($a2['style'] ?? '') === ($a['style'] ?? ''),
    'deckText gives the same answer as deckLink', json_encode($a2));

// B) Junk input → ok:false, never a style.
foreach ([['deckLink' => 'not a deck'], ['deckLink' => ''], ['deckLink' => '{"broken":'], []] as $i => $fields) {
    $b = $post(array_merge(['rootName' => 'SWUSim'], $fields));
    $check(is_array($b) && ($b['ok'] ?? true) === false && !isset($b['style']), "junk input #$i returns ok:false", json_encode($b));
}
// B2) The IMPORTER's own message reaches the caller, rather than the generic "no deck" the empty-deck guard gives.
// Without this the two guards are indistinguishable, and dropping the importer check passes unnoticed.
$b2 = $post(['rootName' => 'SWUSim', 'deckLink' => 'not a deck']);
$check(is_array($b2) && stripos(strval($b2['error'] ?? ''), 'deck') !== false && strval($b2['error'] ?? '') !== 'no deck',
    'an unreadable deck answers with the importer\'s reason', json_encode($b2['error'] ?? null));
// C) The wrong root is refused.
$c = $post(['rootName' => 'AzukiSim', 'deckLink' => $list]);
$check(is_array($c) && ($c['ok'] ?? true) === false, 'another sim is refused', json_encode($c));
// D) It writes nothing.
$check(count(glob($ROOT . '/SWUSim/Games/*') ?: []) === $gamesBefore, 'no game directory was created');

echo $fails === 0 ? "\nPASS\n" : "\nFAIL ($fails)\n";
exit($fails === 0 ? 0 : 1);
