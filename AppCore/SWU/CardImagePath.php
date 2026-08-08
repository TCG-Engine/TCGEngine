<?php
// The one place that knows where SWU card art lives and how it is named.
//
// Layout: AppCore/SWU/Images/{WebpImages,concat,crops}, keyed by SET_NNN — the same key as the
// generated dictionaries and the deck-JSON interchange format. Both SWU apps read this corpus.
//
// Naming:
//   SOR_005.webp             front art
//   SOR_005_back.webp        a leader's deployed unit side
//   mock_HMW_004.webp        preview art (the CardID itself is NEVER prefixed — only the file)
//   SOR_005_cropped.png      identity-banner crop
//
// ⚠ DIRECTION MATTERS. Art is SET_NNN-named, so a stored FFG UID (deck files and stats rows keep
// theirs until the identity migration) must map TOWARD SET_NNN via SWUNormalizeDictionaryKey.
// That is the OPPOSITE of the interim shims this replaces — _swuArtKey()/SWUDeckArtKey() mapped
// SET_NNN back to a UUID because the art was still UUID-named. Both are deleted by this change;
// keeping either alongside this seam would translate twice and resolve nothing.
//
// Design: docs/superpowers/specs/2026-08-04-swu-shared-card-universe-design.md §1, §6

if (!defined('SWU_IMAGE_WEB_ROOT')) define('SWU_IMAGE_WEB_ROOT', '/TCGEngine/AppCore/SWU/Images');
if (!defined('SWU_IMAGE_FS_ROOT'))  define('SWU_IMAGE_FS_ROOT', __DIR__ . '/Images');

// CardID -> filename stem. Mirrors resolveCardImageID() in Core/jsInclude.js; keep the two in step.
function SWUCardImageID($cardID)
{
    if ($cardID === null || $cardID === '') return $cardID;
    $id = (string)$cardID;

    // ORDER MATTERS: split "_back" off BEFORE normalising. "2579145458_back" matches neither the
    // SET_NNN pattern nor any lookup key, so normalising first leaves it untouched and yields the
    // filename "2579145458_back" — which does not exist in the SET_NNN corpus. Strip, normalise the
    // base, then re-attach. Core/jsInclude.js's resolveCardImageID does the same; keep them in step.
    $suffix = '';
    if (substr($id, -5) === '_back') { $suffix = '_back'; $id = substr($id, 0, -5); }

    // A stored UUID still resolves while the dictionary façade is in place.
    if (function_exists('SWUNormalizeDictionaryKey')) $id = SWUNormalizeDictionaryKey($id);

    // Preview cards have no UUID and their art is mock_-prefixed. Forgetting this broke the
    // main-menu deck stack once already.
    $mocks = _SWUCardImageMocks();
    if (isset($mocks[$id])) return 'mock_' . $id . $suffix;

    return $id . $suffix;
}

function _SWUCardImageMocks()
{
    static $mocks = null;
    if ($mocks === null) {
        $p = __DIR__ . '/CardMocks.php';
        $mocks = file_exists($p) ? (array)require $p : [];
    }
    return $mocks;
}

function _SWUCardImageParts($kind)
{
    switch ($kind) {
        case 'tile': return ['concat', '.webp'];
        case 'crop': return ['crops', '_cropped.png'];
        case 'card':
        default:     return ['WebpImages', '.webp'];
    }
}

// Web URL for a card's art. $kind: 'card' (full art) | 'tile' (450x450 square) | 'crop' (banner).
function SWUCardImagePath($cardID, $kind = 'card')
{
    list($dir, $ext) = _SWUCardImageParts($kind);
    return SWU_IMAGE_WEB_ROOT . '/' . $dir . '/' . SWUCardImageID($cardID) . $ext;
}

// Absolute filesystem path, for server-side readers (deck-image generation, profile images).
function SWUCardImageFsPath($cardID, $kind = 'card')
{
    list($dir, $ext) = _SWUCardImageParts($kind);
    return SWU_IMAGE_FS_ROOT . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . SWUCardImageID($cardID) . $ext;
}

// Gives client-side code the same seam server code has, as a <script> to echo once per page.
//
// The stats pages build image URLs in JS from FFG UIDs their APIs return, and they deliberately do
// NOT load the generated card dictionary — that file is ~1MB, versus ~50KB for the UUID->SET_NNN
// map alone. So they get the map and a resolver, not the dictionary.
//
// Emits window.swuCardArtUrl(cardID, kind), the JS twin of SWUCardImagePath(). Keep the resolution
// order here identical to SWUCardImageID() and to resolveCardImageID() in Core/jsInclude.js:
// strip "_back" -> normalise toward SET_NNN -> apply the mock_ prefix.
//
// Requires the generated dictionaries to be included server-side (for UUIDLookup/GetAllCardIds).
//
// $withIdMap=false omits the UUID->SET_NNN map (51KB raw / 20KB gzipped) for pages that already
// carry the client card dictionary — a game BOARD loads SWUNormalizeDictionaryKey(), which does the
// same normalisation, so shipping the map there is pure weight. The emitted resolver falls back to
// that function when the map misses, so both callers resolve identically.
function SWUCardArtScript($withIdMap = true)
{
    static $cache = [];
    $key = $withIdMap ? 'full' : 'lite';
    if (isset($cache[$key])) return $cache[$key];

    $map = [];
    if ($withIdMap && function_exists('GetAllCardIds') && function_exists('UUIDLookup')) {
        foreach (GetAllCardIds() as $id) {
            $uuid = UUIDLookup($id);
            if ($uuid !== null && $uuid !== '' && $uuid !== $id) $map[(string)$uuid] = $id;
        }
    }
    $mockIDs = array_fill_keys(array_keys(_SWUCardImageMocks()), 1);

    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    $script = "<script>\n"
        . "window.SWUArtRoot = " . json_encode(SWU_IMAGE_WEB_ROOT, $flags) . ";\n"
        // JSON_FORCE_OBJECT: an empty PHP array encodes as [], so the lite build would hand JS an
        // Array where every consumer expects an object map. Lookups would still miss harmlessly,
        // but the emitted type must not change shape with the build.
        . "window.SWUArtIDMap = " . json_encode($map, $flags | JSON_FORCE_OBJECT) . ";\n"
        . "window.SWUArtMockIDs = " . json_encode($mockIDs, $flags | JSON_FORCE_OBJECT) . ";\n"
        . <<<'JS'
window.swuCardArtID = function (cardID) {
  if (!cardID) return '';
  var id = String(cardID), suffix = '';
  var m = /^(.*)(_back)$/.exec(id);
  if (m) { id = m[1]; suffix = m[2]; }
  if (!/^[A-Z0-9]{2,5}_(T\d{2}|\d{2,3})$/.test(id)) {
    // Map first; when it is absent (the lite build, for pages that carry the client dictionary)
    // fall back to the dictionary's own normaliser so both builds resolve a stored UUID the same.
    if (window.SWUArtIDMap[id]) id = window.SWUArtIDMap[id];
    else if (typeof SWUNormalizeDictionaryKey === 'function') id = SWUNormalizeDictionaryKey(id);
  }
  if (window.SWUArtMockIDs[id]) id = 'mock_' + id;
  return id + suffix;
};
window.swuCardArtUrl = function (cardID, kind) {
  var id = window.swuCardArtID(cardID);
  if (!id) return '';
  if (kind === 'crop') return window.SWUArtRoot + '/crops/' + id + '_cropped.png';
  if (kind === 'tile') return window.SWUArtRoot + '/concat/' + id + '.webp';
  return window.SWUArtRoot + '/WebpImages/' + id + '.webp';
};
JS
        . "\n</script>\n";
    $cache[$key] = $script;
    return $script;
}
