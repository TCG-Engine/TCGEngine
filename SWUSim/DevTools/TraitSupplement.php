<?php
// Fills in traits the upstream card API omits, at generation time.
//
// The official API publishes NO traits for bases — all 91 of them come back empty — even though
// every base prints one (JTL_030 Mos Eisley is "Tatooine", SOR_024 Echo Base is "Hoth"). This
// supplement supplies them from tracked source so CardTrait()/TraitContains() see the real data.
//
// FILL-GAPS ONLY: an entry is applied solely when the API gave nothing for that CardID. Official
// data always wins, so if the API ever starts publishing base traits this file goes inert on its
// own rather than masking the upstream values.
//
// Backfill / extend it with: php SWUSim/DevTools/backfill-base-traits.php [--dry]

function SWUSimTraitSupplementPath(): string {
    return __DIR__ . '/../Custom/CardTraitSupplement.php';
}

// SET_NNN => trait string (comma-separated for multiples).
function SWUSimLoadTraitSupplement(string $path = ''): array {
    if ($path === '') $path = SWUSimTraitSupplementPath();
    if (!file_exists($path)) return [];
    $data = require $path;
    return is_array($data) ? $data : [];
}

// Comma-joined, no spaces — matching how the generator stores $traitData ("Force,Imperial,Sith").
function SWUSimNormalizeTraitString($value): string {
    $parts = is_array($value) ? $value : explode(',', (string)$value);
    $out = [];
    foreach ($parts as $p) {
        $p = trim((string)$p);
        if ($p !== '') $out[] = $p;
    }
    return implode(',', $out);
}

// Apply the supplement to a CardID => traitString map, in place. Returns how many entries were
// filled. Only empty/missing values are touched.
function SWUSimApplyTraitSupplement(array &$traitData, string $path = ''): int {
    $supp = SWUSimLoadTraitSupplement($path);
    if (empty($supp)) return 0;
    $filled = 0;
    foreach ($supp as $cardID => $traits) {
        $existing = trim((string)($traitData[$cardID] ?? ''));
        if ($existing !== '') continue;               // official data always wins
        $normalized = SWUSimNormalizeTraitString($traits);
        if ($normalized === '') continue;
        $traitData[$cardID] = $normalized;
        $filled++;
    }
    return $filled;
}
