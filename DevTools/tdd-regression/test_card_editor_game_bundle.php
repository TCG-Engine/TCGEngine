<?php
// TDD guard for the Generator Workspace "CardEditor game" transfer.
//
// The point of this feature is moving ONE authored game (its 12 ce_* tables plus, optionally, its
// uploaded asset files) between machines. Unlike the card_abilities transfer, a per-game bundle
// cannot ship raw SQL: every ce_* table is keyed by an AUTOINCREMENT id and joined by that id, so
// the receiving database will assign different numbers. This layer's whole job is to make the
// bundle ID-FREE — every cross-table reference travels as the target row's UUID — so the importer
// can rebuild the id graph locally without ever trusting a foreign machine's numbering.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php DevTools/tdd-regression/test_card_editor_game_bundle.php
error_reporting(E_ALL & ~E_DEPRECATED); ini_set('display_errors', 1);
chdir(dirname(dirname(__DIR__)));
include_once './CardEditor/Database/CardEditorGameBundle.php';

$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// Rejections are the bulk of this suite; assert on the message so a wrong-but-passing guard
// (e.g. "dangling reference" caught by the JSON check) still fails the test.
$rejects = function (callable $run, string $expectFragment, string $msg) use ($check) {
    try {
        $run();
        $check(false, "$msg (no exception thrown)");
    } catch (Throwable $error) {
        $check(
            stripos($error->getMessage(), $expectFragment) !== false,
            "$msg — got: " . $error->getMessage()
        );
    }
};

$tempFiles = [];
$writeTemp = function (string $suffix, string $bytes) use (&$tempFiles): string {
    $path = sys_get_temp_dir() . '/ce-bundle-test-' . bin2hex(random_bytes(6)) . $suffix;
    file_put_contents($path, $bytes);
    $tempFiles[] = $path;
    return $path;
};

const GAME_UUID = '11111111-1111-4111-8111-111111111111';
const ASSET_PATH = 'Assets/' . GAME_UUID . '/images/aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa.webp';

// A complete one-game corpus, exactly as the endpoint reads it out of MySQL: local autoincrement
// `id` present, cross-table columns holding local numeric ids. $base shifts every id so the same
// logical game can be produced with a different numbering, which is what the ID-free assertions
// below compare against.
function sampleTables(int $base = 0): array
{
    $now = '2026-08-21 12:00:00';
    $id = function (int $n) use ($base) { return $base + $n; };
    return [
        'ce_games' => [[
            'id' => $id(1), 'game_uuid' => GAME_UUID, 'name' => 'Soul Masters', 'slug' => 'soul-masters',
            'description' => 'A game', 'created_at' => $now, 'updated_at' => $now,
        ]],
        'ce_assets' => [[
            'id' => $id(2), 'asset_uuid' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'game_id' => $id(1),
            'asset_kind' => 'image', 'original_filename' => 'frame.webp', 'mime_type' => 'image/webp',
            'extension' => 'webp', 'relative_path' => ASSET_PATH, 'width' => 750, 'height' => 1050,
            'file_size' => 4, 'created_at' => $now,
        ]],
        'ce_sets' => [[
            'id' => $id(3), 'set_uuid' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'game_id' => $id(1),
            'name' => 'Core Set', 'slug' => 'core', 'description' => null,
            'created_at' => $now, 'updated_at' => $now,
        ]],
        'ce_templates' => [[
            'id' => $id(4), 'template_uuid' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'game_id' => $id(1),
            'name' => 'Unit', 'slug' => 'unit', 'description' => null, 'canvas_width' => 750,
            'canvas_height' => 1050, 'canvas_background_color' => '#ffffff',
            'canvas_background_asset_id' => $id(2), 'safe_area_padding' => 40,
            'created_at' => $now, 'updated_at' => $now,
        ]],
        'ce_template_fields' => [[
            'id' => $id(5), 'field_uuid' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'template_id' => $id(4),
            'field_key' => 'power', 'label' => 'Power', 'field_type' => 'number', 'help_text' => null,
            'default_value' => '0', 'sort_order' => 1, 'settings_json' => '{"min":0}',
            'created_at' => $now, 'updated_at' => $now,
        ]],
        'ce_template_layout_elements' => [[
            'id' => $id(6), 'element_uuid' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'template_id' => $id(4),
            'element_type' => 'field', 'field_id' => $id(5), 'asset_id' => $id(2),
            'x' => '10.00', 'y' => '20.00', 'width' => '100.00', 'height' => '40.00',
            'z_index' => 2, 'rotation' => '0.00', 'is_visible' => 1, 'style_json' => null,
            'created_at' => $now, 'updated_at' => $now,
        ]],
        'ce_game_tags' => [[
            'id' => $id(7), 'tag_uuid' => 'ffffffff-ffff-4fff-8fff-ffffffffffff', 'game_id' => $id(1),
            'name' => 'Villain', 'slug' => 'villain', 'created_at' => $now, 'updated_at' => $now,
        ]],
        'ce_game_enums' => [[
            'id' => $id(8), 'enum_uuid' => '99999999-9999-4999-8999-999999999999', 'game_id' => $id(1),
            'name' => 'Aspect', 'slug' => 'aspect', 'created_at' => $now, 'updated_at' => $now,
        ]],
        'ce_game_enum_options' => [[
            'id' => $id(9), 'option_uuid' => '88888888-8888-4888-8888-888888888888', 'enum_id' => $id(8),
            'label' => 'Aggression', 'value' => 'aggression', 'asset_id' => $id(2), 'sort_order' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]],
        'ce_cards' => [[
            'id' => $id(10), 'card_uuid' => '77777777-7777-4777-8777-777777777777', 'game_id' => $id(1),
            'set_id' => $id(3), 'template_id' => $id(4), 'name' => 'Test Unit', 'slug' => 'test-unit',
            'created_at' => $now, 'updated_at' => $now,
        ]],
        'ce_card_field_values' => [[
            'id' => $id(11), 'card_id' => $id(10), 'field_id' => $id(5), 'value_text' => null,
            'value_number' => '3.0000', 'value_boolean' => null, 'value_json' => null, 'updated_at' => $now,
        ]],
        'ce_card_tags' => [[
            'card_id' => $id(10), 'tag_id' => $id(7), 'created_at' => $now,
        ]],
    ];
}

$exportedAt = '2026-08-21T12:00:00Z';

// ---------------------------------------------------------------- round trip
$archive = CardEditorGameBundle::export(sampleTables(100), $exportedAt);
$archivePath = $writeTemp('.zip', $archive);
$bundle = CardEditorGameBundle::read($archivePath, 'soul-masters.zip');

$check($bundle['manifest']['format'] === CardEditorGameBundle::FORMAT, 'manifest carries the format id');
$check($bundle['manifest']['gameUuid'] === GAME_UUID, 'manifest carries the game uuid');
$check($bundle['manifest']['gameName'] === 'Soul Masters', 'manifest carries the game name');
$check($bundle['manifest']['exportedAt'] === $exportedAt, 'manifest carries the injected timestamp');
$check(($bundle['manifest']['counts']['ce_cards'] ?? -1) === 1, 'manifest counts rows per table');
$check(count($bundle['tables']) === 12, 'all 12 ce_* tables ride in the bundle');
$check($bundle['assets'] === [], 'a bundle exported without assets carries no files');

// ------------------------------------------------- the bundle is ID-free
// This is the guard the whole design rests on. Export the SAME logical game from two databases
// that numbered it differently; if any local id leaked into the payload the two differ, and an
// import would rebuild a graph pointing at the exporting machine's rows.
$fromMachineA = CardEditorGameBundle::read($writeTemp('.zip', CardEditorGameBundle::export(sampleTables(100), $exportedAt)), 'a.zip');
$fromMachineB = CardEditorGameBundle::read($writeTemp('.zip', CardEditorGameBundle::export(sampleTables(5000), $exportedAt)), 'b.zip');
$check($fromMachineA['tables'] == $fromMachineB['tables'], 'differently-numbered databases export identical payloads');

$cardRow = $bundle['tables']['ce_cards'][0];
$check(!array_key_exists('id', $cardRow), 'row ids are stripped from the bundle');
$check($cardRow['set_id'] === 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'ce_cards.set_id travels as the set uuid');
$check($cardRow['template_id'] === 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'ce_cards.template_id travels as the template uuid');
$check($cardRow['name'] === 'Test Unit', 'non-reference columns survive verbatim');

$elementRow = $bundle['tables']['ce_template_layout_elements'][0];
$check($elementRow['field_id'] === 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'layout element field_id travels as a uuid');
$check($elementRow['asset_id'] === 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'layout element asset_id travels as a uuid');
$check($elementRow['style_json'] === null, 'a null column stays null');

$joinRow = $bundle['tables']['ce_card_tags'][0];
$check($joinRow['card_id'] === '77777777-7777-4777-8777-777777777777', 'the uuid-less join table still resolves its refs');
$check($joinRow['tag_id'] === 'ffffffff-ffff-4fff-8fff-ffffffffffff', 'ce_card_tags.tag_id travels as the tag uuid');

// A nullable reference that is actually null must not become the string "null" or an empty uuid.
$noBackground = sampleTables(100);
$noBackground['ce_templates'][0]['canvas_background_asset_id'] = null;
$nullRefBundle = CardEditorGameBundle::read($writeTemp('.zip', CardEditorGameBundle::export($noBackground, $exportedAt)), 'n.zip');
$check($nullRefBundle['tables']['ce_templates'][0]['canvas_background_asset_id'] === null, 'a null reference stays null');

// ------------------------------------------------------------------ assets
$assetSource = $writeTemp('.webp', 'RIFF');
$withAssets = CardEditorGameBundle::export(sampleTables(100), $exportedAt, [ASSET_PATH => $assetSource]);
$assetBundle = CardEditorGameBundle::read($writeTemp('.zip', $withAssets), 'assets.zip');
$check(($assetBundle['assets'][ASSET_PATH] ?? null) === 'RIFF', 'asset bytes round-trip keyed by relative_path');
$check(($assetBundle['manifest']['counts']['assets'] ?? -1) === 1, 'manifest counts the bundled asset files');

$rejects(
    fn() => CardEditorGameBundle::export(sampleTables(100), $exportedAt, ['Assets/' . GAME_UUID . '/images/stray.webp' => $assetSource]),
    'not listed in ce_assets',
    'export refuses a file with no matching ce_assets row'
);

// An asset entry naming a path outside the game's own folder is how a hostile bundle would drop a
// file into another game's directory — or, with traversal, anywhere on disk.
$stray = new ZipArchive();
$strayPath = sys_get_temp_dir() . '/ce-bundle-test-' . bin2hex(random_bytes(6)) . '.zip';
$tempFiles[] = $strayPath;
$stray->open($strayPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$stray->addFromString(CardEditorGameBundle::MANIFEST_FILE_NAME, json_encode([
    'format' => CardEditorGameBundle::FORMAT, 'gameUuid' => GAME_UUID, 'gameName' => 'Soul Masters',
    'exportedAt' => $exportedAt, 'counts' => [],
]));
$stray->addFromString(CardEditorGameBundle::GAME_FILE_NAME, json_encode($fromMachineA['tables']));
$stray->addFromString(CardEditorGameBundle::ASSET_DIRECTORY . '/../../../evil.php', '<?php');
$stray->close();
$rejects(fn() => CardEditorGameBundle::read($strayPath, 'stray.zip'), 'asset path', 'read refuses a traversing asset entry');

// ------------------------------------------------------------- rejections
$rejects(
    fn() => CardEditorGameBundle::read($archivePath, 'bundle.rar'),
    'Unsupported',
    'read refuses an unsupported extension'
);
$rejects(
    fn() => CardEditorGameBundle::read($writeTemp('.zip', 'not a zip at all'), 'broken.zip'),
    'corrupt',
    'read refuses a corrupt archive'
);

// Builds a zip from raw parts so each structural guard can be probed in isolation.
$forge = function (array $manifest, $tables) use (&$tempFiles): string {
    $path = sys_get_temp_dir() . '/ce-bundle-test-' . bin2hex(random_bytes(6)) . '.zip';
    $tempFiles[] = $path;
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString(CardEditorGameBundle::MANIFEST_FILE_NAME, json_encode($manifest));
    $zip->addFromString(CardEditorGameBundle::GAME_FILE_NAME, is_string($tables) ? $tables : json_encode($tables));
    $zip->close();
    return $path;
};
$goodManifest = [
    'format' => CardEditorGameBundle::FORMAT, 'gameUuid' => GAME_UUID,
    'gameName' => 'Soul Masters', 'exportedAt' => $exportedAt, 'counts' => [],
];

$rejects(
    fn() => CardEditorGameBundle::read($forge(['format' => 'tcgengine-card-data-1'] + $goodManifest, $fromMachineA['tables']), 'x.zip'),
    'not a CardEditor game bundle',
    'read refuses another tool\'s archive format'
);
$rejects(
    fn() => CardEditorGameBundle::read($forge($goodManifest, 'not json'), 'x.zip'),
    'valid JSON',
    'read refuses a malformed payload'
);

$noGame = $fromMachineA['tables'];
$noGame['ce_games'] = [];
$rejects(
    fn() => CardEditorGameBundle::read($forge($goodManifest, $noGame), 'x.zip'),
    'exactly one game',
    'read refuses a bundle with no game row'
);

// A DISTINCT second game, so this probes the one-game rule rather than the duplicate-uuid rule.
$twoGames = $fromMachineA['tables'];
$secondGame = $twoGames['ce_games'][0];
$secondGame['game_uuid'] = '22222222-2222-4222-8222-222222222222';
$secondGame['slug'] = 'other-game';
$twoGames['ce_games'][] = $secondGame;
$rejects(
    fn() => CardEditorGameBundle::read($forge($goodManifest, $twoGames), 'x.zip'),
    'exactly one game',
    'read refuses a bundle carrying two games'
);

$unknownTable = $fromMachineA['tables'];
$unknownTable['users'] = [['id' => 1]];
$rejects(
    fn() => CardEditorGameBundle::read($forge($goodManifest, $unknownTable), 'x.zip'),
    'users',
    'read refuses an unknown table key'
);

$missingTable = $fromMachineA['tables'];
unset($missingTable['ce_cards']);
$rejects(
    fn() => CardEditorGameBundle::read($forge($goodManifest, $missingTable), 'x.zip'),
    'ce_cards',
    'read refuses a bundle missing a table'
);

// A reference to a uuid that is not in the bundle would import as a row pointing at nothing —
// silently, because there are no foreign keys on these tables to catch it.
$dangling = $fromMachineA['tables'];
$dangling['ce_cards'][0]['set_id'] = '00000000-0000-4000-8000-000000000000';
$rejects(
    fn() => CardEditorGameBundle::read($forge($goodManifest, $dangling), 'x.zip'),
    'ce_sets',
    'read refuses a dangling cross-table reference'
);

// A required (NOT NULL) reference cannot be null even though the nullable ones may be.
$nulledRequired = $fromMachineA['tables'];
$nulledRequired['ce_cards'][0]['set_id'] = null;
$rejects(
    fn() => CardEditorGameBundle::read($forge($goodManifest, $nulledRequired), 'x.zip'),
    'set_id',
    'read refuses a null in a required reference'
);

$missingColumn = $fromMachineA['tables'];
unset($missingColumn['ce_cards'][0]['slug']);
$rejects(
    fn() => CardEditorGameBundle::read($forge($goodManifest, $missingColumn), 'x.zip'),
    'slug',
    'read refuses a row missing a column'
);

$duplicateUuid = $fromMachineA['tables'];
$duplicateUuid['ce_sets'][] = $duplicateUuid['ce_sets'][0];
$rejects(
    fn() => CardEditorGameBundle::read($forge($goodManifest, $duplicateUuid), 'x.zip'),
    'duplicate',
    'read refuses two rows sharing a uuid'
);

$manifestMismatch = ['gameUuid' => '00000000-0000-4000-8000-000000000000'] + $goodManifest;
$rejects(
    fn() => CardEditorGameBundle::read($forge($manifestMismatch, $fromMachineA['tables']), 'x.zip'),
    'manifest',
    'read refuses a manifest naming a different game than the payload'
);

// ------------------------------------------------- import-side contract
$order = CardEditorGameBundle::tableOrder();
$check($order[0] === 'ce_games', 'ce_games inserts first');
$check(array_search('ce_assets', $order, true) < array_search('ce_templates', $order, true), 'assets insert before the templates that reference them');
$check(array_search('ce_template_fields', $order, true) < array_search('ce_template_layout_elements', $order, true), 'fields insert before layout elements');
$check(array_search('ce_cards', $order, true) < array_search('ce_card_field_values', $order, true), 'cards insert before their field values');
$check(array_search('ce_game_tags', $order, true) < array_search('ce_card_tags', $order, true), 'tags insert before the card/tag joins');
$check(array_search('ce_game_enums', $order, true) < array_search('ce_game_enum_options', $order, true), 'enums insert before their options');
// Every referenced table must already be inserted when its referrer's turn comes, or the importer
// has no new id to substitute.
$orderIsResolvable = true;
foreach ($order as $position => $table) {
    foreach (CardEditorGameBundle::referencesFor($table) as $column => $targetTable) {
        if (array_search($targetTable, $order, true) >= $position) $orderIsResolvable = false;
    }
}
$check($orderIsResolvable, 'every reference target precedes its referrer in the insert order');

$check(CardEditorGameBundle::uuidColumnFor('ce_cards') === 'card_uuid', 'uuid column is exposed for the id map');
$check(CardEditorGameBundle::uuidColumnFor('ce_card_tags') === null, 'the join table reports no uuid column');
$check(in_array('slug', CardEditorGameBundle::columnsFor('ce_cards'), true), 'insert columns are exposed');
$check(!in_array('id', CardEditorGameBundle::columnsFor('ce_cards'), true), 'the autoincrement id is not an insert column');

foreach ($tempFiles as $path) @unlink($path);

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILURE(S)\n";
exit($fails === 0 ? 0 : 1);
