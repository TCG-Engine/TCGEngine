<?php
// Cosmetic asset-path helpers shared by the uploader endpoints (CosmeticsUpload / CosmeticsCommit).
// Single source of truth for where each slot's webp lives, plus a repo-constrained delete.

function SWUCosmeticSlotDir(string $slot): ?string {
    $dirs = [
        'background' => '/Assets/Boards/SWUSim/',
        'cardback'   => '/Assets/CardBacks/SWUSim/',
        'playmat'    => '/Assets/Playmats/SWUSim/',
    ];
    return $dirs[$slot] ?? null;
}

// Root-relative './Assets/.../<id>.webp' for a cosmetic, or null for an unknown slot / empty id.
function SWUCosmeticAssetRel(string $slot, string $id): ?string {
    $dir = SWUCosmeticSlotDir($slot);
    if ($dir === null || $id === '') return null;
    return '.' . $dir . $id . '.webp';
}

// Delete a cosmetic's asset file(s) (+ -mobile.webp for backgrounds), each constrained to the
// repo root via a realpath prefix check. Returns false on an unknown slot or malformed id.
function SWUCosmeticDeleteAsset(string $slot, string $id): bool {
    $dir = SWUCosmeticSlotDir($slot);
    if ($dir === null || !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $id)) return false;
    $repoRoot = realpath(__DIR__ . '/../..');
    if ($repoRoot === false) return false;
    $rels = [$dir . $id . '.webp'];
    if ($slot === 'background') $rels[] = $dir . $id . '-mobile.webp';
    foreach ($rels as $rel) {
        $real = realpath($repoRoot . $rel);
        if ($real && strpos($real, $repoRoot) === 0 && is_file($real)) @unlink($real);
    }
    return true;
}
