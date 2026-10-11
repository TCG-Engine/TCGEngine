<?php
/**
 * Cross-checks the printed keywords on every GrandArchiveSim card against the hand-maintained keyword registries in
 * Custom/GameLogic.php. Several keywords are not derived from the card data but listed per card ($rangedCardValues,
 * $ephemerateCards, $Kindle_Cards, $Cardistry_Cards, $Imbue_Cards and the *Link_Cards sets), so a card missing from its list
 * silently loses the keyword (Ashfletched Bowman's Ranged 3, Silent Firebrand's Kindle 2 and Eight of Hearts' Cardistry were all
 * found this way).
 *
 *   php DevTools/audit-ga-keyword-registries.php            # list cards whose printed keyword is not registered
 *   php DevTools/audit-ga-keyword-registries.php --all      # also list the registered ones
 *
 * Exit code 1 when anything is missing and not in the KNOWN_GAPS list below (so it can be wired into CI later).
 */
chdir(dirname(__DIR__));
require 'GrandArchiveSim/GeneratedCode/GeneratedCardDictionaries.php';
global $nameData;

$showAll = in_array('--all', $argv, true);
$src = file_get_contents('GrandArchiveSim/Custom/GameLogic.php');

// Cards whose printed keyword is deliberately not in the registry, with the reason. Keep this short and honest.
const KNOWN_GAPS = [
    'ranged' => [
        'm7f6r8f3y8' => 'champion Ranged, read from the champion card elsewhere',
        '7ozuj68m69' => 'champion Ranged, read from the champion card elsewhere',
        'wiztyu6o24' => 'champion Ranged, read from the champion card elsewhere',
        'pvxb5hrfsu' => 'Ranger Strides: grants RANGED_n turn effects',
        'yakyp6odw3' => 'OPEN: Steady Sharpshooter\'s aim-counter ranged is not implemented (no engine reference to the card)',
        'PAymR7JsNp' => 'PlasmaTech Blaster: handled as a linked-object bonus',
        'fsudPn5GQH' => 'Signal Gunner: handled in GetRangedValue',
        'vnta6qsesw' => 'Take Aim: grants a turn effect',
        's3bqtjayfn' => 'Tinderflare Pivot: grants a turn effect',
        'sl7dedg616' => 'OPEN: Calculated Foresight\'s champion ranged 3 is not implemented (no engine reference to the card)',
        '7xgwve1d47' => 'Dahlia: dynamic value in GetRangedValue',
        '2rz308kuz0' => 'OPEN: Reconnaissance Field\'s REST ability (ranged 1 + true sight) is not implemented (no engine reference to the card)',
        '43rtqovkti' => 'Baidi: field-presence grant in GetRangedValue',
        '44vm5kt3q2' => 'Battlefield Spotter: field-presence grant in GetRangedValue',
        'dWgPzoEbIE' => 'Cosmic Focus: grants a turn effect',
        'qp2r93Bgpj' => 'Diana, Haunt Reminiscence: dynamic value in GetRangedValue',
    ],
    'ephemerate' => [
        '7JjxD8xRVq' => 'Abnegation: conditional on a negated activation this turn; no tracker exists yet',
        'KqBosnU7pU' => 'Mordred, Fated Luminary: granted dynamically (MordredFatedEphemerateApplies)',
        'MKiuesDVdW' => 'Thanatotic Hemosynth: granted dynamically (THANATOTIC_EPHEMERATE_*)',
    ],
    'link' => [
        'q5CXVeHGIf' => 'Camil, Basked Abundance only mentions items with ally link; it is not one',
    ],
    'kindle' => [
        'ba0tqvwlp1' => 'OPEN: Ritai Stablemaster\'s "Horse cards you activate have kindle 3" is not implemented',
    ],
];

$checks = [];
$missing = 0;
$report = function (string $kind, string $id, string $why, bool $registered) use (&$missing, $showAll) {
    $known = KNOWN_GAPS[$kind][$id] ?? null;
    if ($registered && !$showAll) return;
    $open = $known !== null && str_starts_with($known, 'OPEN:');
    $tag = $registered ? 'ok     ' : ($open ? 'open   ' : ($known !== null ? 'known  ' : 'MISSING'));
    if (!$registered && $known === null) ++$missing;
    echo str_pad($tag, 8), str_pad($kind, 11), CardName($id), " ($id) ", CardType($id), ' | ', $why, $known !== null && !$registered ? "  [$known]" : '', "\n";
};

$plain = fn (string $id): string => trim(strip_tags(str_replace('<br>', ' ', strval(CardEffect($id)))));
$has = fn (string $table, string $id): bool => preg_match('/\$' . preg_quote($table, '/') . '\["' . preg_quote($id, '/') . '"\]/', $src) === 1;

$ids = array_keys($nameData);
sort($ids);
foreach ($ids as $id) {
    $text = $plain($id);
    $raw = strval(CardEffect($id));
    if ($text === '') continue;

    if (preg_match('/\branged (\d+)/i', $text, $m)) {
        // $rangedCardValues is a literal array: ["id" => [n, bool, [classes]], ...]
        $registered = preg_match('/"' . preg_quote($id, '/') . '"\s*=>\s*\[\d+,\s*(true|false)/', $src) === 1;
        $report('ranged', $id, 'Ranged ' . $m[1], $registered);
    }
    if (preg_match('/Ephemerate\s*[—-]+\s*\*?\*?\s*\((\w+)\)/u', $raw, $m) || preg_match('/\*\*Ephemerate\*\*\s*[—-]\s*\((\w+)\)/u', $raw, $m)) {
        $report('ephemerate', $id, 'Ephemerate (' . $m[1] . ')', $has('ephemerateCards', $id));
    }
    if (preg_match('/\*\*Kindle (\d+)\*\*/i', $raw, $m)) {
        $report('kindle', $id, 'Kindle ' . $m[1], $has('Kindle_Cards', $id));
    }
    if (preg_match('/Cardistry\*{0,3}\s*[—-]*\s*\*{0,3}\s*\((\d+)\)/u', $raw, $m)) {
        $report('cardistry', $id, 'Cardistry (' . $m[1] . ')', $has('Cardistry_Cards', $id));
    }
    if (preg_match('/\*\*(Advanced )?Imbue (\w+)\*\*/', $raw, $m)) {
        $report('imbue', $id, trim($m[1] . 'Imbue ' . $m[2]), $has('Imbue_Cards', $id));
    }
    foreach (['Ally Link' => 'AllyLink_Cards', 'Weapon Link' => 'WeaponLink_Cards', 'Unit Link' => 'UnitLink_Cards', 'Champion Link' => 'ChampionLink_Cards'] as $keyword => $table) {
        if (preg_match('/\*\*' . preg_quote($keyword, '/') . '\*\*/i', $raw) || preg_match('/enters the field linked to target ' . strtolower(explode(' ', $keyword)[0]) . '/i', $text)) {
            if ($keyword === 'Weapon Link' && !preg_match('/Weapon Link/i', $text)) continue;
            $report('link', $id, $keyword, $has($table, $id));
        }
    }
}
echo $missing === 0 ? "\nAll printed keywords are registered (known gaps aside).\n" : "\n$missing card(s) have a printed keyword the engine does not know about.\n";
exit($missing === 0 ? 0 : 1);
