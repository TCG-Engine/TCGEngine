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

// ── Staged replacement ────────────────────────────────────────────────────────────────────────────
// Replacing a cosmetic's art reuses the SAME id and the SAME asset path, so writing the new webp
// straight over the old one would make the uploader's Cancel button a lie — the art would already be
// gone. The upload therefore lands on a staged sibling (`<id>.staged.webp`), the preview shows THAT,
// and only Confirm moves it into place. Cancel just deletes it.
// ⚠ The staged file sits in the same asset dir, so it must never look like a cosmetic id: ids are
// kebab-case ([a-z0-9-]+) and cannot contain a dot, so `<id>.staged.webp` can never collide with one.
function SWUCosmeticStagedAbs(string $slot, string $id, bool $mobile = false): ?string {
    $dir = SWUCosmeticSlotDir($slot);
    if ($dir === null || !preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $id)) return null;
    $repoRoot = realpath(__DIR__ . '/../..');
    if ($repoRoot === false) return null;
    return $repoRoot . $dir . $id . ($mobile ? '-mobile' : '') . '.staged.webp';
}

// Move the staged webp(s) over the live asset(s). Returns false if nothing was staged.
function SWUCosmeticCommitStaged(string $slot, string $id): bool {
    $live = SWUCosmeticAssetRel($slot, $id);
    $staged = SWUCosmeticStagedAbs($slot, $id);
    if ($live === null || $staged === null || !is_file($staged)) return false;
    $repoRoot = realpath(__DIR__ . '/../..');
    if ($repoRoot === false) return false;
    if (!rename($staged, $repoRoot . substr($live, 1))) return false;
    if ($slot === 'background') {
        $sm = SWUCosmeticStagedAbs($slot, $id, true);
        if ($sm !== null && is_file($sm)) {
            @rename($sm, $repoRoot . SWUCosmeticSlotDir($slot) . $id . '-mobile.webp');
        }
    }
    return true;
}

// Drop the staged webp(s) without touching the live asset.
function SWUCosmeticDiscardStaged(string $slot, string $id): void {
    foreach ([false, true] as $mobile) {
        $p = SWUCosmeticStagedAbs($slot, $id, $mobile);
        if ($p !== null && is_file($p)) @unlink($p);
    }
}
