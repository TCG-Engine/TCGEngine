<?php
// Localized SWU card art (es / it / fr): the pure half of zzCardI18nImageGenerator.php.
//
// ⚠ Localized API records TRANSLATE relation names (type.name is "Unidad", "Líder", "Ficha de mejora"), so
// the English generator's type-based ID rules (token SET_T## numbering, flip-leader detection) cannot run
// on them. Every localized record carries the same cardUid as its English record, so we join on that to
// the English cache (SWUSim/GeneratedCode/cardArrayCache.json) and reuse the English decisions exactly.
//
// Layout: AppCore/SWU/Images/i18n/<locale>/{WebpImages,concat,crops} + manifest.json. The client
// (Core/SWUCardI18n.js) only rewrites a URL to a localized file the manifest lists.
//
// Design: docs/superpowers/specs/2026-09-17-swusim-card-i18n-images-design.md §1

function SWUI18nLocales(): array
{
    return ['es', 'it', 'fr'];
}

function SWUI18nIsLocale($locale): bool
{
    return is_string($locale) && in_array($locale, SWUI18nLocales(), true);
}

function SWUI18nLocaleRoot(string $locale): string
{
    return __DIR__ . '/Images/i18n/' . $locale;
}

// cardUid => the English decisions this generator must not re-derive.
function SWUI18nEnglishIndex(array $cacheCards): array
{
    $out = [];
    foreach ($cacheCards as $c) {
        if (!is_array($c)) continue;
        $uid = (string)($c['cardUid'] ?? '');
        $id  = (string)($c['id'] ?? '');
        if ($uid === '' || $id === '') continue;
        $type = $c['type'] ?? null;
        $typeName = '';
        if (is_array($type)) {
            $typeName = (string)($type['name'] ?? ($type['data']['attributes']['name'] ?? ''));
        }
        $out[$uid] = ['id' => $id, 'type' => $typeName, 'power' => $c['power'] ?? null, 'hp' => $c['hp'] ?? null];
    }
    return $out;
}

// Strapi v5 flat ({formats:{card:{url}}}) or v4 nested ({data:{attributes:{formats:{card:{url}}}}}).
function SWUI18nArtUrl($art): ?string
{
    if (!is_object($art)) return null;
    if (isset($art->formats->card->url)) return (string)$art->formats->card->url;
    if (isset($art->data->attributes->formats->card->url)) return (string)$art->data->attributes->formats->card->url;
    return null;
}

function SWUI18nPlanRecord($record, array $index): array
{
    if (is_object($record) && isset($record->attributes) && !isset($record->cardUid)) $record = $record->attributes;
    $uid = is_object($record) ? (string)($record->cardUid ?? '') : '';
    if ($uid === '') return ['skip' => 'no_uid'];
    if (!isset($index[$uid])) return ['skip' => 'unknown_uid'];
    $en = $index[$uid];

    $front = SWUI18nArtUrl($record->artFront ?? null);
    if ($front === null) return ['skip' => 'no_art'];

    $back = SWUI18nArtUrl($record->artBack ?? null);
    $backType = null;
    if ($back !== null) {
        // Same rule as zzCardCodeGenerator.php: a Leader with no unit-side stats is a double-leader FLIP
        // card whose back is another leader face (kept horizontal), not a deployed unit side.
        $noStats = ($en['power'] === null || $en['power'] === '') && ($en['hp'] === null || $en['hp'] === '');
        $backType = ($en['type'] === 'Leader' && $noStats) ? 'Leader' : 'LeaderUnit';
    }

    return [
        'cardID'   => $en['id'],
        'type'     => $en['type'],
        'frontUrl' => $front,
        'backID'   => $back !== null ? $en['id'] . '_back' : null,
        'backType' => $backType,
        'backUrl'  => $back,
    ];
}

// Built from the files on disk, never from what a run intended to write: an interrupted or partly failed
// run must not list an image that does not exist, or the client would request a 404 instead of English.
function SWUI18nBuildManifest(string $locale, string $localeRoot, string $generatedAt): array
{
    $out = ['locale' => $locale, 'generated' => $generatedAt];
    $specs = ['WebpImages' => '.webp', 'concat' => '.webp', 'crops' => '_cropped.png'];
    foreach ($specs as $dir => $suffix) {
        $stems = [];
        foreach (glob(rtrim($localeRoot, '/') . '/' . $dir . '/*' . $suffix) ?: [] as $f) {
            $stems[] = substr(basename($f), 0, -strlen($suffix));
        }
        sort($stems, SORT_STRING);
        $out[$dir] = $stems;
    }
    return $out;
}

// English type names whose concat/ and crops/ output is NOT kept for a locale, because the fixed crop
// windows in zzImageConverter.php cut that language's layout badly. The client then falls back to the
// English tile while the full card stays localized. Filled in by the crop check (plan Task 3).
// Crop check 2026-09-17 (DevTools/i18n-crop-compare.php): es/it/fr tiles and crops match English framing for
// Unit, Event, Upgrade, Leader (incl. TWI_017 flip back), LeaderUnit, Base, Token Unit, Token Upgrade; nothing skipped.
function SWUI18nSkipDerivedTypes(string $locale): array
{
    $skip = [];
    return $skip[$locale] ?? [];
}
