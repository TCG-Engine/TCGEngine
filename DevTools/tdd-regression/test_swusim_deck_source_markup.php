<?php
// Deck-source toggle markup (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §2).
// Unlinked must be BYTE-IDENTICAL to the pre-toggle markup; linked must carry the contract the client relies on.
//
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_deck_source_markup.php
header('Content-Type: text/plain');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Custom/SetupPanels.php';

$PASS = 0; $FAIL = 0; $MSGS = [];
function check($name, $cond, $detail = '') {
    global $PASS, $FAIL, $MSGS;
    if ($cond) { $PASS++; return; }
    $FAIL++; $MSGS[] = "FAIL: $name" . ($detail !== '' ? "  [$detail]" : '');
}

$decks = [
    ['key' => 'aaaaaaaaaaaa', 'name' => 'Deck A', 'leaders' => ['SOR_005'], 'base' => 'SOR_027', 'count' => 0, 'input' => 'https://swudb.com/deck/A'],
    ['key' => 'bbbbbbbbbbbb', 'name' => 'Deck B', 'leaders' => ['ASH_009'], 'base' => 'ASH_025', 'count' => 0, 'input' => 'https://swudb.com/deck/B'],
];
// The six MainMenu call sites, with the args they pass today.
$sites = [
    ['pvp-saved',    'No saved decks yet', '', ''],
    ['ts-saved',     'No saved decks yet', '', ''],
    ['ab-saved',     'No saved decks yet', '', ''],
    ['ab-bot-saved', 'No saved decks yet — use a pre-con below', '— Use one of your saved decks or use a pre-con below —', ''],
    ['sp-saved',     'No saved decks yet', '', ''],
    ['sp-saved-2',   'No saved decks yet', '', 'bot'],
];
// The six label lines exactly as they are in MainMenu.php today.
$labels = [
    ['pvp-saved', '', '<label class="flabel" for="pvp-saved">Saved Decks</label>'],
    ['ts-saved', '', '<label class="flabel" for="ts-saved">Saved Decks</label>'],
    ['ab-saved', ' for your deck', '<label class="flabel" for="ab-saved">Saved Decks<span class="u-vh"> for your deck</span></label>'],
    ['ab-bot-saved', " for the bot's deck", '<label class="flabel" for="ab-bot-saved">Saved Decks<span class="u-vh"> for the bot\'s deck</span></label>'],
    ['sp-saved', '', '<label class="flabel" for="sp-saved">Saved Decks</label>'],
    ['sp-saved-2', ' for the second seat', '<label class="flabel" for="sp-saved-2">Saved Decks<span class="u-vh"> for the second seat</span></label>'],
];
$dom = function (string $html) {
    $d = new DOMDocument();
    libxml_use_internal_errors(true);
    $d->loadHTML('<?xml encoding="utf-8"?><div id="root">' . $html . '</div>');
    libxml_clear_errors();
    return new DOMXPath($d);
};
$attr = fn($n, $a) => $n ? $n->getAttribute($a) : null;

// ── unlinked: byte-identical ─────────────────────────────────────────────────
foreach ([$decks, []] as $list) {
    foreach ($sites as [$id, $empty, $none, $slot]) {
        $was = SWUSetupDeckPicker($id, $list, $empty, $none, $slot);
        $now = SWUSetupDeckSourcePicker($id, $list, false, $empty, $none, $slot);
        check("unlinked $id (" . count($list) . " decks) is byte-identical", $now === $was);
    }
}
foreach ($labels as [$id, $vh, $orig]) {
    check("unlinked label $id is byte-identical", SWUSetupDeckLabel($id, false, $vh) === $orig, SWUSetupDeckLabel($id, false, $vh));
}
check('withMsg=false drops ONLY the pickmsg', SWUSetupDeckPicker('pvp-saved', $decks, 'No saved decks yet', '', '', false) . SWUSetupPickMsg('own')
    === SWUSetupDeckPicker('pvp-saved', $decks));

// ── linked: the contract ─────────────────────────────────────────────────────
foreach ([$decks, []] as $list) {
    foreach ($sites as [$id, $empty, $none, $slot]) {
        $wantSlot = $slot !== '' ? $slot : _SWUSetupSlot($id);
        $x = $dom(SWUSetupDeckLabel($id, true, '') . SWUSetupDeckSourcePicker($id, $list, true, $empty, $none, $slot));
        $tag = "linked $id (" . count($list) . ")";
        check("$tag: one toggle", $x->query('//*[@data-decksrc]')->length === 1);
        $ss = $x->query("//input[@id='$id-src-ss']")->item(0);
        $sv = $x->query("//input[@id='$id-src-saved']")->item(0);
        check("$tag: radios named {id}-src, SWUStats checked", $ss && $sv && $attr($ss, 'name') === "$id-src" && $attr($sv, 'name') === "$id-src"
            && $ss->hasAttribute('checked') && !$sv->hasAttribute('checked') && $attr($ss, 'value') === 'swustats' && $attr($sv, 'value') === 'saved');
        $savedPanel = $x->query("//div[contains(@class,'decksrc__panel') and @data-src='saved']")->item(0);
        check("$tag: saved panel hidden", $savedPanel && $savedPanel->hasAttribute('hidden'));
        if ($list) check("$tag: saved panel holds the original select", $x->query("//div[@data-src='saved']//select[@id='$id']")->length === 1);
        $host = $x->query("//div[@data-src='swustats']/div[@class='decksrc__host']/div[@data-source='swustats']")->item(0);
        check("$tag: SWUStats host carries what it is built from", $host && $host->hasAttribute('data-empty')
            && $attr($host, 'data-select-id') === "$id-ss" && $attr($host, 'data-slot') === $wantSlot && $attr($host, 'data-none-label') === $none,
            $host ? $x->document->saveHTML($host) : 'no host');
        check("$tag: a refresh button", $x->query("//div[@data-src='swustats']//button[@data-decksrc-refresh]")->length === 1);
        check("$tag: exactly one pickmsg, OUTSIDE the toggle", $x->query("//*[@data-pickmsg='$wantSlot']")->length === 1
            && $x->query("//*[@data-decksrc]//*[@data-pickmsg]")->length === 0);
        $lbl = $x->query("//span[@id='$id-lbl']")->item(0);
        $seg = $x->query("//*[@data-decksrc]/div[@role='radiogroup']")->item(0);
        check("$tag: the label names the toggle", $lbl && $seg && $attr($seg, 'aria-labelledby') === "$id-lbl");
    }
}
echo ($FAIL === 0 ? "PASS ($PASS checks)" : "FAIL ($FAIL of " . ($PASS + $FAIL) . ")\n" . implode("\n", $MSGS)) . "\n";
exit($FAIL === 0 ? 0 : 1);
