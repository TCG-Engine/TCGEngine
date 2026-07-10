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

    // Atomic write: temp file + rename, so a partial write never corrupts Catalog.php.
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $new, LOCK_EX) === false) return false;
    if (!rename($tmp, $path)) { @unlink($tmp); return false; }
    return true;
}
