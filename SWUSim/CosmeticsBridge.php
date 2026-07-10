<?php
// SWUSim/CosmeticsBridge.php — emits `window.SWU_COSMETICS` for the in-game board.
//
// The board JS in Custom/GameLayoutShared.php CONSUMES window.SWU_COSMETICS
// ({background, myCardBack, theirCardBack, myPlaymat, theirPlaymat}) to paint the
// viewer's chosen board background, per-side card backs, and playmats. Nothing ever
// produced that global — cosmetics were resolved server-side (MatchHooks) and consumed
// client-side, but the PHP→JS bridge was missing, so in-game cosmetics never applied.
// This file is that bridge.

require_once __DIR__ . '/MatchFlow.php';                 // SWUReadMatchRef
require_once __DIR__ . '/Match.php';                     // SWUReadMatch
require_once __DIR__ . '/Cosmetics/Catalog.php';         // SWUCosmeticAssetUrl
require_once __DIR__ . '/../Database/functions.inc.php'; // SWUResolveSeatCosmetics

// Board backgrounds ship a <base>.webp / <base>-mobile.webp pair (see SWUBoardBackground()).
// On the phone layout, prefer the -mobile variant when the file exists. Card backs and
// playmats have no such pair, so they use SWUCosmeticAssetUrl() directly.
function _SWUCosBackgroundUrl($asset, bool $mobile): string {
    if (empty($asset)) return '';
    if ($mobile && preg_match('/\.webp$/i', (string)$asset)) {
        $mobileAsset = preg_replace('/\.webp$/i', '-mobile.webp', (string)$asset);
        $rel = preg_replace('#^\./#', '/TCGEngine/', $mobileAsset);
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($docRoot !== '' && @file_exists($docRoot . $rel)) $asset = $mobileAsset;
    }
    return SWUCosmeticAssetUrl($asset);
}

// Per-seat cosmetic overrides for dev/test contexts, keyed by the well-known test authKey the
// zzTestSchemaEditor uses. Lets schema-editor SWUSim games show a representative P2 playmat
// without a real match or login. Shape: ['<seat>' => ['<slot>' => '<choiceId>']].
function SWUCosmeticSeatOverrides($authKey): array {
    if ((string)$authKey === 'testschema') return ['2' => ['playmat' => 'overwhelming-barrage']];
    return [];
}

// Build the viewer-relative cosmetics object. $viewerPerspective is the seat whose
// perspective is rendered (1 or 2); $viewerUserId is the logged-in viewer (for the
// matchless-game fallback). $seatOverrides forces a seat's slot(s) (dev/test). Returns []
// when nothing applies (all-default).
function SWUBuildCosmeticsPayload($gameName, $viewerPerspective, $viewerUserId, bool $mobile, array $seatOverrides = []): array {
    $mySeat    = ((string)$viewerPerspective === '2') ? '2' : '1';
    $theirSeat = ($mySeat === '1') ? '2' : '1';

    $myCos = null; $theirCos = null;

    // Preferred source: the per-seat cosmetics snapshot taken at match creation.
    // Public + private games always create a match; goldfish/hotseat (solo/local) do not.
    $ref = SWUReadMatchRef($gameName);
    if (is_array($ref) && isset($ref['matchId'])) {
        $m = SWUReadMatch($ref['matchId']);
        if (is_array($m)) {
            $myCos    = $m['players'][$mySeat]['cosmetics']    ?? null;
            $theirCos = $m['players'][$theirSeat]['cosmetics'] ?? null;
        }
    }

    // Matchless solo modes (goldfish/hotseat): still honor the viewer's own selections so
    // their board background + card back apply. The opponent (empty/shared seat) stays default.
    if ($myCos === null && $viewerUserId !== null && (string)$viewerUserId !== '') {
        $myCos = SWUResolveSeatCosmetics($viewerUserId);
    }

    // Force any dev/test seat overrides on top (resolved to {id, asset} like real choices).
    $applyOverride = function ($cos, $seat) use ($seatOverrides) {
        foreach ($seatOverrides[$seat] ?? [] as $slot => $choiceId) {
            $cos = is_array($cos) ? $cos : [];
            $cos[$slot] = SWUCosmeticResolve($slot, $choiceId);
        }
        return $cos;
    };
    $myCos    = $applyOverride($myCos, $mySeat);
    $theirCos = $applyOverride($theirCos, $theirSeat);

    return [
        'background'    => _SWUCosBackgroundUrl($myCos['background']['asset'] ?? null, $mobile),
        'myCardBack'    => SWUCosmeticAssetUrl($myCos['cardback']['asset']    ?? null),
        'theirCardBack' => SWUCosmeticAssetUrl($theirCos['cardback']['asset'] ?? null),
        'myPlaymat'     => SWUCosmeticAssetUrl($myCos['playmat']['asset']     ?? null),
        'theirPlaymat'  => SWUCosmeticAssetUrl($theirCos['playmat']['asset']  ?? null),
    ];
}

// The <script> tag to emit into the board page. Placed after the layout include so the
// consumer (which also re-applies on DOMContentLoaded / load / MutationObserver) sees it.
function SWUCosmeticsBridgeScript($gameName, $viewerPerspective, $viewerUserId, bool $mobile, array $seatOverrides = []): string {
    $payload = SWUBuildCosmeticsPayload($gameName, $viewerPerspective, $viewerUserId, $mobile, $seatOverrides);
    return "<script>window.SWU_COSMETICS = " . json_encode($payload) . ";</script>\n";
}

// Patch a single seat's cosmetic in the live match snapshot — ONLY the seat owned by $userId
// (authorization). No-op when the game has no match (solo modes) or the user isn't a seat.
// Returns true iff a seat was patched. Lets the in-game picker propagate a mid-match change to
// the opponent (who reads this snapshot via SWUBuildCosmeticsPayload / CosmeticsLive.php).
function SWUPatchMatchSeatCosmetic($gameName, $userId, string $slot, string $choiceId): bool {
    if ($userId === null || (string)$userId === '' || $slot === '') return false;
    $ref = SWUReadMatchRef($gameName);
    if (!is_array($ref) || !isset($ref['matchId'])) return false;
    $patched = false;
    SWUWithMatchLock($ref['matchId'], function (&$m) use ($userId, $slot, $choiceId, &$patched) {
        foreach (['1', '2'] as $seat) {
            if ((string)($m['players'][$seat]['userId'] ?? '') === (string)$userId) {
                $m['players'][$seat]['cosmetics'][$slot] = SWUCosmeticResolve($slot, $choiceId);
                $patched = true;
            }
        }
    });
    return $patched;
}
