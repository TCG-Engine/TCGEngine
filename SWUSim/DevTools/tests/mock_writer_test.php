<?php
// Create -> edit -> delete round-trip against temp files. Never touches the real registry.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

require __DIR__ . '/../MockCardWriter.php';

$mockFile = sys_get_temp_dir() . '/writer_mocks_' . getmypid() . '.php';
file_put_contents($mockFile, "<?php\nreturn [\n];\n");

$entry = [
    'title' => 'Carbonite Chamber', 'type' => 'Upgrade', 'set' => 'HMW', 'cost' => 1,
    'aspect' => ['Vigilance'], 'trait' => ['Fortification'],
    'text' => "Fortify (Attach this to your base, not a unit.)",
    'imageUrl' => 'https://example.invalid/095.png',
];

// --- create ---
check(SWUSimWriteMockCard('HMW_095', $entry, $mockFile), 'write returns true');
$loaded = SWUSimLoadMockCards($mockFile);
check(isset($loaded['HMW_095']), 'entry present after write');
check($loaded['HMW_095']['title'] === 'Carbonite Chamber', 'title round-trips');
check($loaded['HMW_095']['trait'] === ['Fortification'], 'array field round-trips');
check(strpos($loaded['HMW_095']['text'], "\n") === false, 'single-line text preserved');

// --- text with newlines and apostrophes survives ---
$tricky = $entry;
$tricky['title'] = "Obi-Wan's Lightsaber";
$tricky['text'] = "Line one\nLine two";
check(SWUSimWriteMockCard('HMW_096', $tricky, $mockFile), 'write tricky entry');
$loaded = SWUSimLoadMockCards($mockFile);
check($loaded['HMW_096']['title'] === "Obi-Wan's Lightsaber", 'apostrophe survives');
check($loaded['HMW_096']['text'] === "Line one\nLine two", 'newline survives');

// --- edit is idempotent, not duplicative ---
$edited = $entry;
$edited['cost'] = 2;
check(SWUSimWriteMockCard('HMW_095', $edited, $mockFile), 'overwrite existing');
$loaded = SWUSimLoadMockCards($mockFile);
check(count($loaded) === 2, 'still two entries after edit');
check($loaded['HMW_095']['cost'] === 2, 'edit applied');

// --- a token CardID is accepted; junk is refused ---
check(SWUSimWriteMockCard('HMW_T01', ['title' => 'Weakness', 'type' => 'Token Upgrade', 'set' => 'HMW'], $mockFile),
      'token SET_T## id accepted');
check(SWUSimWriteMockCard('not-a-card-id', $entry, $mockFile) === false, 'junk id refused');

// --- delete ---
check(SWUSimDeleteMockCard('HMW_095', $mockFile), 'delete returns true');
$loaded = SWUSimLoadMockCards($mockFile);
check(!isset($loaded['HMW_095']), 'entry gone');
check(isset($loaded['HMW_096']), 'sibling untouched');
check(SWUSimDeleteMockCard('HMW_000', $mockFile) === false, 'deleting a missing entry is false');

// --- override writer ---
$ovFile = sys_get_temp_dir() . '/writer_overrides_' . getmypid() . '.php';
file_put_contents($ovFile, "<?php\nfunction CardIDOverrideFixture(\$cardID) {\n  switch(\$cardID) {\n    case \"SHD_030\": return \"SOR_033\"; //Death Trooper\n    default: return \$cardID;\n  }\n}\n");
check(SWUSimWriteReprintOverride('IC27_097', 'SOR_128', 'Death Star Stormtrooper', $ovFile),
      'override written');
$src = file_get_contents($ovFile);
check(strpos($src, 'case "IC27_097": return "SOR_128"; //Death Star Stormtrooper') !== false,
      'override matches the file\'s existing form');
check(strpos($src, 'case "SHD_030"') !== false, 'existing overrides preserved');
check(substr_count($src, 'default: return $cardID;') === 1, 'default arm still last and single');
// idempotent
check(SWUSimWriteReprintOverride('IC27_097', 'SOR_128', 'Death Star Stormtrooper', $ovFile) === false,
      'writing the same override twice is a no-op');

unlink($mockFile);
unlink($ovFile);
echo "OK\n";
