<?php

// Moves ONE authored CardEditor game — its 12 ce_* tables plus, optionally, its uploaded asset
// files — between machines.
//
// Why this is not a SQL dump like CardAbilitySqlTransfer: card_abilities is a flat table keyed by
// (root_name, card_id), so its rows can be deleted and re-inserted verbatim. The ce_* tables are
// not. Every one of them is keyed by an AUTOINCREMENT id and joined to the others by that id, so a
// dump carrying local ids would either collide with the receiving database's numbering or, worse,
// silently attach a card to whatever set happened to hold that id there.
//
// So the bundle is ID-FREE. On the way out, every row's own `id` is dropped and every cross-table
// reference column is rewritten to the target row's UUID (game_uuid, set_uuid, template_uuid, …).
// On the way in, the importer walks tableOrder(), inserts each table, and records uuid => new id as
// it goes, so a reference is always resolvable by the time its referrer is written. Two databases
// that numbered the same game differently therefore produce byte-identical payloads.
//
// The game_uuid itself is deliberately PRESERVED rather than reminted: ce_assets.relative_path is
// "Assets/<game_uuid>/images/<asset_uuid>.<ext>", so keeping the uuid is what keeps every asset row
// pointing at the file that travelled with it.
//
// SECURITY: asset entries are never extracted under their own names by this class. read() returns
// them in memory keyed by the relative_path that a bundled ce_assets row already claims, and the
// path is checked to sit inside that game's own Assets/<game_uuid>/ folder, so a hostile entry name
// ("assets/../../evil.php") is rejected here rather than being trusted by the caller.
final class CardEditorGameBundle
{
    public const FORMAT = 'tcgengine-cardeditor-game-1';
    public const MANIFEST_FILE_NAME = 'manifest.json';
    public const GAME_FILE_NAME = 'game.json';
    public const ASSET_DIRECTORY = 'assets';

    // Decompression-bomb ceilings, not targets. A large authored game's rows are a few MB.
    public const MAX_GAME_BYTES = 64 * 1024 * 1024;
    public const MAX_ASSET_BYTES = 32 * 1024 * 1024;

    // Whole-archive ceiling, applied on BOTH sides so an export can always be re-imported. A game
    // whose assets exceed this can still ship the rows-only bundle.
    public const MAX_ARCHIVE_BYTES = 64 * 1024 * 1024;

    // The tables in INSERT order: a table may only reference tables above it. tableOrder() is this
    // key order, and the test asserts the property rather than the literal list, so adding a table
    // here is safe as long as it is placed after everything it points at.
    //
    //   uuid    the row's stable identity, and what other tables' references are rewritten to.
    //           null means nothing references this table, so it needs no identity of its own.
    //   columns the insert columns, in order. `id` is never among them — it is assigned locally.
    //   refs    column => [table, nullable]. Rewritten to a uuid on export, resolved back to a
    //           local id on import.
    private const TABLE_SPEC = [
        'ce_games' => [
            'uuid' => 'game_uuid',
            'columns' => ['game_uuid', 'name', 'slug', 'description', 'created_at', 'updated_at'],
            'refs' => [],
        ],
        'ce_assets' => [
            'uuid' => 'asset_uuid',
            'columns' => ['asset_uuid', 'game_id', 'asset_kind', 'original_filename', 'mime_type', 'extension', 'relative_path', 'width', 'height', 'file_size', 'created_at'],
            'refs' => ['game_id' => ['ce_games', false]],
        ],
        'ce_sets' => [
            'uuid' => 'set_uuid',
            'columns' => ['set_uuid', 'game_id', 'name', 'slug', 'description', 'created_at', 'updated_at'],
            'refs' => ['game_id' => ['ce_games', false]],
        ],
        'ce_templates' => [
            'uuid' => 'template_uuid',
            'columns' => ['template_uuid', 'game_id', 'name', 'slug', 'description', 'canvas_width', 'canvas_height', 'canvas_background_color', 'canvas_background_asset_id', 'safe_area_padding', 'created_at', 'updated_at'],
            'refs' => ['game_id' => ['ce_games', false], 'canvas_background_asset_id' => ['ce_assets', true]],
        ],
        'ce_template_fields' => [
            'uuid' => 'field_uuid',
            'columns' => ['field_uuid', 'template_id', 'field_key', 'label', 'field_type', 'help_text', 'default_value', 'sort_order', 'settings_json', 'created_at', 'updated_at'],
            'refs' => ['template_id' => ['ce_templates', false]],
        ],
        'ce_template_layout_elements' => [
            'uuid' => 'element_uuid',
            'columns' => ['element_uuid', 'template_id', 'element_type', 'field_id', 'asset_id', 'x', 'y', 'width', 'height', 'z_index', 'rotation', 'is_visible', 'style_json', 'created_at', 'updated_at'],
            'refs' => ['template_id' => ['ce_templates', false], 'field_id' => ['ce_template_fields', true], 'asset_id' => ['ce_assets', true]],
        ],
        'ce_game_tags' => [
            'uuid' => 'tag_uuid',
            'columns' => ['tag_uuid', 'game_id', 'name', 'slug', 'created_at', 'updated_at'],
            'refs' => ['game_id' => ['ce_games', false]],
        ],
        'ce_game_enums' => [
            'uuid' => 'enum_uuid',
            'columns' => ['enum_uuid', 'game_id', 'name', 'slug', 'created_at', 'updated_at'],
            'refs' => ['game_id' => ['ce_games', false]],
        ],
        'ce_game_enum_options' => [
            'uuid' => 'option_uuid',
            'columns' => ['option_uuid', 'enum_id', 'label', 'value', 'asset_id', 'sort_order', 'created_at', 'updated_at'],
            'refs' => ['enum_id' => ['ce_game_enums', false], 'asset_id' => ['ce_assets', true]],
        ],
        'ce_cards' => [
            'uuid' => 'card_uuid',
            'columns' => ['card_uuid', 'game_id', 'set_id', 'template_id', 'name', 'slug', 'created_at', 'updated_at'],
            'refs' => ['game_id' => ['ce_games', false], 'set_id' => ['ce_sets', false], 'template_id' => ['ce_templates', false]],
        ],
        'ce_card_field_values' => [
            'uuid' => null,
            'columns' => ['card_id', 'field_id', 'value_text', 'value_number', 'value_boolean', 'value_json', 'updated_at'],
            'refs' => ['card_id' => ['ce_cards', false], 'field_id' => ['ce_template_fields', false]],
        ],
        'ce_card_tags' => [
            'uuid' => null,
            'columns' => ['card_id', 'tag_id', 'created_at'],
            'refs' => ['card_id' => ['ce_cards', false], 'tag_id' => ['ce_game_tags', false]],
        ],
    ];

    // ------------------------------------------------------------------ spec accessors

    // Insert order. Every table's reference targets are guaranteed to appear before it.
    public static function tableOrder(): array
    {
        return array_keys(self::TABLE_SPEC);
    }

    public static function columnsFor(string $table): array
    {
        self::assertKnownTable($table);
        return self::TABLE_SPEC[$table]['columns'];
    }

    // column => target table, for the importer's id substitution.
    public static function referencesFor(string $table): array
    {
        self::assertKnownTable($table);
        $flattened = [];
        foreach (self::TABLE_SPEC[$table]['refs'] as $column => $reference) {
            $flattened[$column] = $reference[0];
        }
        return $flattened;
    }

    // The column other tables reference this one by, or null when nothing references it.
    public static function uuidColumnFor(string $table): ?string
    {
        self::assertKnownTable($table);
        return self::TABLE_SPEC[$table]['uuid'];
    }

    // ------------------------------------------------------------------------- export

    // Builds the downloadable zip from rows read straight out of MySQL — local `id` present,
    // reference columns holding local ids. $exportedAt is injected rather than read from the clock
    // so the output is reproducible under test. $assetFiles is [relative_path => absolute path];
    // when empty the export is the small rows-only bundle.
    public static function export(array $rowsByTable, string $exportedAt, array $assetFiles = []): string
    {
        $identity = self::buildIdentityMaps($rowsByTable);

        $payload = [];
        $counts = [];
        foreach (self::TABLE_SPEC as $table => $spec) {
            if (!array_key_exists($table, $rowsByTable)) {
                throw new InvalidArgumentException('Export is missing the ' . $table . ' rows');
            }
            $payload[$table] = [];
            foreach ($rowsByTable[$table] as $row) {
                $payload[$table][] = self::portableRow($table, $spec, (array)$row, $identity);
            }
            $counts[$table] = count($payload[$table]);
        }

        if (count($payload['ce_games']) !== 1) {
            throw new InvalidArgumentException('A bundle must carry exactly one game');
        }
        $game = $payload['ce_games'][0];

        // Every bundled file has to belong to a row that travels with it, or the import would write
        // an image no ce_assets row can ever point at.
        $knownPaths = [];
        foreach ($payload['ce_assets'] as $asset) $knownPaths[(string)$asset['relative_path']] = true;
        foreach ($assetFiles as $relativePath => $sourcePath) {
            if (!isset($knownPaths[(string)$relativePath])) {
                throw new InvalidArgumentException('Asset file ' . $relativePath . ' is not listed in ce_assets');
            }
        }
        $counts['assets'] = count($assetFiles);

        $manifest = [
            'format' => self::FORMAT,
            'gameUuid' => (string)$game['game_uuid'],
            'gameName' => (string)$game['name'],
            'gameSlug' => (string)$game['slug'],
            'exportedAt' => $exportedAt,
            'counts' => $counts,
        ];

        $zipPath = self::temporaryPath();
        try {
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create the export bundle');
            }
            $zip->addFromString(self::MANIFEST_FILE_NAME, (string)json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            $zip->addFromString(self::GAME_FILE_NAME, (string)json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            foreach ($assetFiles as $relativePath => $sourcePath) {
                // addFile streams from disk, so a large asset set never lands in memory all at once.
                $zip->addFile((string)$sourcePath, self::ASSET_DIRECTORY . '/' . (string)$relativePath);
            }
            if (!$zip->close()) throw new RuntimeException('Could not finalize the export bundle');

            $bytes = file_get_contents($zipPath);
            if ($bytes === false) throw new RuntimeException('Could not read the export bundle');
            return $bytes;
        } finally {
            @unlink($zipPath);
        }
    }

    // id => uuid per table, for rewriting reference columns. Only tables something references need
    // one; ce_card_field_values and ce_card_tags are pure leaves.
    private static function buildIdentityMaps(array $rowsByTable): array
    {
        $identity = [];
        foreach (self::TABLE_SPEC as $table => $spec) {
            if ($spec['uuid'] === null) continue;
            $identity[$table] = [];
            foreach ($rowsByTable[$table] ?? [] as $row) {
                $row = (array)$row;
                $localId = (string)($row['id'] ?? '');
                $uuid = trim((string)($row[$spec['uuid']] ?? ''));
                if ($localId === '' || $uuid === '') {
                    throw new InvalidArgumentException('A ' . $table . ' row is missing its id or ' . $spec['uuid']);
                }
                $identity[$table][$localId] = $uuid;
            }
        }
        return $identity;
    }

    // One row, stripped of its local id and with every reference rewritten to a uuid.
    private static function portableRow(string $table, array $spec, array $row, array $identity): array
    {
        $portable = [];
        foreach ($spec['columns'] as $column) {
            if (!array_key_exists($column, $row)) {
                throw new InvalidArgumentException($table . ' row is missing the ' . $column . ' column');
            }
            $value = $row[$column];

            if (isset($spec['refs'][$column])) {
                [$targetTable, $nullable] = $spec['refs'][$column];
                if ($value === null || $value === '') {
                    if (!$nullable) {
                        throw new InvalidArgumentException($table . '.' . $column . ' is required but empty');
                    }
                    $portable[$column] = null;
                    continue;
                }
                $localId = (string)$value;
                if (!isset($identity[$targetTable][$localId])) {
                    throw new InvalidArgumentException(
                        $table . '.' . $column . ' points at a ' . $targetTable . ' row outside this game'
                    );
                }
                $portable[$column] = $identity[$targetTable][$localId];
                continue;
            }

            $portable[$column] = $value === null ? null : (string)$value;
        }
        return $portable;
    }

    // --------------------------------------------------------------------------- read

    // Reads an uploaded bundle and returns ['manifest' => [...], 'tables' => [...], 'assets' =>
    // [relative_path => bytes]], validated hard enough that the importer can insert rows without
    // re-checking them. $fileNameHint is the ORIGINAL upload name: a PHP upload tmp file has no
    // extension, so the hint is what tells us it claims to be a zip at all.
    public static function read(string $archivePath, string $fileNameHint): array
    {
        if (strtolower(substr(trim($fileNameHint), -4)) !== '.zip') {
            throw new InvalidArgumentException('Unsupported bundle format. Choose a .zip exported by the CardEditor game panel.');
        }

        $zip = new ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new InvalidArgumentException('Could not read the uploaded bundle — it may be corrupt or not a zip file');
        }

        try {
            $manifest = self::decodeEntry($zip, self::MANIFEST_FILE_NAME, self::MAX_GAME_BYTES);
            if (!is_array($manifest) || (string)($manifest['format'] ?? '') !== self::FORMAT) {
                throw new InvalidArgumentException('This file is not a CardEditor game bundle');
            }

            $tables = self::decodeEntry($zip, self::GAME_FILE_NAME, self::MAX_GAME_BYTES);
            if (!is_array($tables)) {
                throw new InvalidArgumentException(self::GAME_FILE_NAME . ' is not valid JSON');
            }
            $tables = self::validateTables($tables);

            $gameUuid = (string)$tables['ce_games'][0]['game_uuid'];
            if ((string)($manifest['gameUuid'] ?? '') !== $gameUuid) {
                throw new InvalidArgumentException('The bundle manifest names a different game than its payload');
            }

            $assets = self::readAssets($zip, $tables['ce_assets'], $gameUuid);
        } finally {
            $zip->close();
        }

        return ['manifest' => $manifest, 'tables' => $tables, 'assets' => $assets];
    }

    private static function decodeEntry(ZipArchive $zip, string $entryName, int $maxBytes)
    {
        $stat = $zip->statName($entryName);
        if (!$stat) throw new InvalidArgumentException('The bundle has no ' . $entryName);
        if ((int)$stat['size'] > $maxBytes) {
            throw new InvalidArgumentException($entryName . ' exceeds the size limit');
        }
        $contents = $zip->getFromName($entryName, $maxBytes);
        if ($contents === false) throw new InvalidArgumentException('Could not read ' . $entryName . ' from the bundle');
        return json_decode($contents, true);
    }

    // Structural validation. These tables carry no foreign keys, so nothing downstream would catch a
    // reference to a row that is not here — it has to be caught now, before any INSERT runs.
    private static function validateTables(array $tables): array
    {
        foreach (array_keys($tables) as $table) {
            if (!isset(self::TABLE_SPEC[$table])) {
                throw new InvalidArgumentException('The bundle carries an unexpected table: ' . $table);
            }
        }

        $validated = [];
        $known = [];
        foreach (self::TABLE_SPEC as $table => $spec) {
            if (!isset($tables[$table]) || !is_array($tables[$table])) {
                throw new InvalidArgumentException('The bundle is missing the ' . $table . ' rows');
            }
            $validated[$table] = array_values($tables[$table]);

            if ($spec['uuid'] === null) continue;
            $known[$table] = [];
            foreach ($validated[$table] as $row) {
                if (!is_array($row) || !array_key_exists($spec['uuid'], $row)) {
                    throw new InvalidArgumentException('A ' . $table . ' row is missing its ' . $spec['uuid']);
                }
                $uuid = (string)$row[$spec['uuid']];
                if (isset($known[$table][$uuid])) {
                    throw new InvalidArgumentException('The bundle has a duplicate ' . $table . ' ' . $spec['uuid'] . ': ' . $uuid);
                }
                $known[$table][$uuid] = true;
            }
        }

        if (count($validated['ce_games']) !== 1) {
            throw new InvalidArgumentException('A bundle must carry exactly one game, found ' . count($validated['ce_games']));
        }

        foreach (self::TABLE_SPEC as $table => $spec) {
            foreach ($validated[$table] as $row) {
                if (!is_array($row)) throw new InvalidArgumentException('A ' . $table . ' entry is not a row');
                foreach ($spec['columns'] as $column) {
                    if (!array_key_exists($column, $row)) {
                        throw new InvalidArgumentException($table . ' row is missing the ' . $column . ' column');
                    }
                }
                foreach ($spec['refs'] as $column => [$targetTable, $nullable]) {
                    $value = $row[$column];
                    if ($value === null || $value === '') {
                        if (!$nullable) {
                            throw new InvalidArgumentException($table . '.' . $column . ' is required but empty');
                        }
                        continue;
                    }
                    if (!isset($known[$targetTable][(string)$value])) {
                        throw new InvalidArgumentException(
                            $table . '.' . $column . ' references a ' . $targetTable . ' row that is not in the bundle: ' . $value
                        );
                    }
                }
            }
        }

        return $validated;
    }

    // Asset bytes keyed by the relative_path a bundled ce_assets row claims. An entry that does not
    // resolve to one of those paths never reaches the caller, so it can never be written to disk.
    private static function readAssets(ZipArchive $zip, array $assetRows, string $gameUuid): array
    {
        $expected = [];
        foreach ($assetRows as $row) $expected[(string)$row['relative_path']] = true;

        $prefix = self::ASSET_DIRECTORY . '/';
        $gameFolder = 'Assets/' . $gameUuid . '/';
        $assets = [];

        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $stat = $zip->statIndex($i);
            if (!$stat) continue;
            $entryName = str_replace('\\', '/', (string)$stat['name']);
            if (substr($entryName, -1) === '/') continue;
            if (strpos($entryName, $prefix) !== 0) continue;

            $relativePath = substr($entryName, strlen($prefix));
            // Three gates, all required: no traversal, inside THIS game's folder, and claimed by a
            // ce_assets row that travelled in the same bundle.
            if (strpos($relativePath, '..') !== false || strpos($relativePath, $gameFolder) !== 0 || !isset($expected[$relativePath])) {
                throw new InvalidArgumentException('The bundle contains an asset path outside this game: ' . $relativePath);
            }
            if ((int)$stat['size'] > self::MAX_ASSET_BYTES) {
                throw new InvalidArgumentException('Asset ' . $relativePath . ' exceeds the size limit');
            }
            $contents = $zip->getFromIndex($i, self::MAX_ASSET_BYTES);
            if ($contents === false) {
                throw new InvalidArgumentException('Could not read asset ' . $relativePath . ' from the bundle');
            }
            $assets[$relativePath] = $contents;
        }

        return $assets;
    }

    private static function assertKnownTable(string $table): void
    {
        if (!isset(self::TABLE_SPEC[$table])) {
            throw new InvalidArgumentException('Unknown CardEditor table: ' . $table);
        }
    }

    private static function temporaryPath(): string
    {
        return sys_get_temp_dir() . '/tcgengine-ce-game-' . bin2hex(random_bytes(8)) . '.zip';
    }
}
