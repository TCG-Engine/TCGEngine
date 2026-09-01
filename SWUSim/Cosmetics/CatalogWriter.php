<?php
// SWUSim/Cosmetics/CatalogWriter.php — appends a cosmetic entry into Catalog.php, above the
// `//new <slot>s above this line` marker. Keeps Catalog.php as read-only data; all mutation is
// isolated here. Caller (CosmeticsUpload.php) guarantees id uniqueness before calling.

// Escape a value for embedding inside a single-quoted PHP string literal.
function _SWUCatalogEsc(string $v): string {
    return str_replace(['\\', "'"], ['\\\\', "\\'"], $v);
}

function SWUCatalogAppendEntry(string $slot, string $id, string $label, string $asset, ?string $catalogPath = null): bool {
    if (!in_array($slot, ['background', 'cardback', 'playmat'], true)) return false;
    if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $id)) return false;

    $path = $catalogPath ?? (__DIR__ . '/Catalog.php');
    if (!is_file($path) || !is_writable($path)) return false;
    $src = file_get_contents($path);
    if ($src === false) return false;

    // Capture the marker line (with its leading indentation) and prepend the new entry above it.
    $pattern = '/([ \t]*\/\/new ' . preg_quote($slot, '/') . 's above this line)/';
    $line = "            '" . $id . "' => ['label'=>'" . _SWUCatalogEsc($label)
          . "', 'asset'=>'" . _SWUCatalogEsc($asset) . "', 'isDefault'=>false],\n";

    $count = 0;
    $new = preg_replace_callback($pattern, function ($m) use ($line) {
        return $line . $m[1];
    }, $src, 1, $count);
    if ($count !== 1 || $new === null) return false;

    return _SWUCatalogWrite($path, $new);
}

// ── Locating an existing entry ────────────────────────────────────────────────────────────────────
// Everything below is SLOT-SCOPED, and that is not defensive padding: ids are only unique WITHIN a
// slot (CosmeticsUpload.php checks `SWUCosmeticCatalog()[$slot]`), so a background and a playmat may
// legitimately share an id. A file-wide search for "'death-star' =>" would happily rewrite or delete
// the wrong slot's line. Each slot's block runs from its `'<slot>' => [` opener to its
// `//new <slot>s above this line` marker, which is the same anchor SWUCatalogAppendEntry writes above.
//
// Returns [startOffset, endOffset) of the slot's block body, or null if either anchor is missing.
function _SWUCatalogSlotBounds(string $src, string $slot): ?array {
    if (!preg_match("/'" . preg_quote($slot, '/') . "'\s*=>\s*\[/", $src, $m, PREG_OFFSET_CAPTURE)) return null;
    $start = $m[0][1] + strlen($m[0][0]);
    $marker = strpos($src, "//new {$slot}s above this line", $start);
    if ($marker === false) return null;
    return [$start, $marker];
}

// Byte range [lineStart, lineEnd) of the whole entry LINE for $id inside a slot block, or null.
// Matches the id key with tolerant spacing: the built-in entries are column-aligned
// ('against-the-galaxy'             => [...]) while appended ones are not.
function _SWUCatalogEntryLineRange(string $src, string $slot, string $id): ?array {
    $b = _SWUCatalogSlotBounds($src, $slot);
    if ($b === null) return null;
    [$from, $to] = $b;
    $block = substr($src, $from, $to - $from);
    $re = "/^[ \t]*'" . preg_quote($id, '/') . "'\s*=>\s*\[[^\n]*\],[ \t]*\r?\n/m";
    if (!preg_match($re, $block, $m, PREG_OFFSET_CAPTURE)) return null;
    $lineStart = $from + $m[0][1];
    return [$lineStart, $lineStart + strlen($m[0][0])];
}

// Atomic write: temp file + rename, so a partial write never corrupts Catalog.php.
function _SWUCatalogWrite(string $path, string $contents): bool {
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $contents, LOCK_EX) === false) return false;
    if (!rename($tmp, $path)) { @unlink($tmp); return false; }
    return true;
}

// True iff $slot/$id exists in the catalog FILE (not the in-memory catalog).
function SWUCatalogHasEntry(string $slot, string $id, ?string $catalogPath = null): bool {
    if (!in_array($slot, ['background', 'cardback', 'playmat'], true)) return false;
    $path = $catalogPath ?? (__DIR__ . '/Catalog.php');
    if (!is_file($path)) return false;
    $src = file_get_contents($path);
    return $src !== false && _SWUCatalogEntryLineRange($src, $slot, $id) !== null;
}

// Rename an entry: replaces ONLY the 'label'=>'…' value, leaving the id, the asset path, the
// isDefault flag and the line's own alignment untouched. The id and asset deliberately never change —
// they are what every saved user selection points at, so renaming is safe for players who already
// chose this cosmetic (they keep it and just see the new name).
function SWUCatalogUpdateEntryLabel(string $slot, string $id, string $label, ?string $catalogPath = null): bool {
    if (!in_array($slot, ['background', 'cardback', 'playmat'], true)) return false;
    if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $id)) return false;
    if (trim($label) === '') return false;

    $path = $catalogPath ?? (__DIR__ . '/Catalog.php');
    if (!is_file($path) || !is_writable($path)) return false;
    $src = file_get_contents($path);
    if ($src === false) return false;

    $range = _SWUCatalogEntryLineRange($src, $slot, $id);
    if ($range === null) return false;
    [$ls, $le] = $range;
    $line = substr($src, $ls, $le - $ls);

    $count = 0;
    $newLine = preg_replace(
        // ⚠ SINGLE-quoted: in a double-quoted PHP string the backslashes collapse and `[^'\\]`
        // arrives as `[^'\]`, which escapes the closing bracket — PCRE then fails to compile.
        '/(\'label\'\\s*=>\\s*\')(?:[^\'\\\\]|\\\\.)*(\')/',
        '${1}' . str_replace('$', '\$', _SWUCatalogEsc($label)) . '${2}',
        $line, 1, $count
    );
    if ($count !== 1 || $newLine === null) return false;

    return _SWUCatalogWrite($path, substr($src, 0, $ls) . $newLine . substr($src, $le));
}

// Delete an entry line outright. The CALLER is responsible for the asset files
// (SWUCosmeticDeleteAsset) and for refusing to delete a slot's default — see CosmeticsCommit.php.
function SWUCatalogDeleteEntry(string $slot, string $id, ?string $catalogPath = null): bool {
    if (!in_array($slot, ['background', 'cardback', 'playmat'], true)) return false;
    if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $id)) return false;

    $path = $catalogPath ?? (__DIR__ . '/Catalog.php');
    if (!is_file($path) || !is_writable($path)) return false;
    $src = file_get_contents($path);
    if ($src === false) return false;

    $range = _SWUCatalogEntryLineRange($src, $slot, $id);
    if ($range === null) return false;
    [$ls, $le] = $range;
    return _SWUCatalogWrite($path, substr($src, 0, $ls) . substr($src, $le));
}
