<?php
// RUN VIA CLI:
//   docker exec -w /var/www/html/TCGEngine otmtcge-swustats-web-server-1 \
//     php -d xdebug.mode=off DevTools/tdd-regression/test_swudeck_profile_layout.php
//
// The profile page's panes are laid out by `.core-wrapper`, which menuStyles.css defines as
// `display:flex; flex-direction:row` with NO wrap. RenderProfile() opens that div and — before
// 2026-08-08 — never closed it, carrying a comment that this "matches the original Profile.php
// structure". PageEntry.php emits RenderTemplate('Disclaimer') immediately afterwards, so the page
// FOOTER became a fourth flex item in the row.
//
// Measured consequence at 1440px: disclaimer 759px, oauth 313px, team 226px, and the first pane
// squeezed to 42px against 204px of content — which its own `overflow:hidden` then clipped, so
// "Change Your Password" rendered as an unreadable 3-character column.
//
// Balanced markup is the invariant that prevents it: if RenderProfile closes what it opens, nothing
// emitted after it can land inside the flex row.
//
// READ-ONLY: renders to a string. No database writes, no POST.
header('Content-Type: text/plain');
$root = dirname(__DIR__, 2);
$_SESSION = $_SESSION ?? [];            // Profile.php reads $_SESSION for the OAuth panel
require_once $root . '/SharedUI/Render/SiteDef.php';
require_once $root . '/SharedUI/Render/Profile.php';

$checks = [];

$def = LoadSiteDef('SWUDeck');
$ctx = ['username' => 'TestUser', 'loggedIn' => true];
$userData = ['teamID' => null, 'teamInvites' => [], 'userID' => 'TestUser', 'usersUid' => 'TestUser'];

$html = @RenderProfile($def, $ctx, $userData);

$open  = substr_count($html, '<div');
$close = substr_count($html, '</div>');

// ── The bug ─────────────────────────────────────────────────────────────────
// An unclosed div here does not merely produce invalid markup — the browser hoists every
// subsequent sibling INTO the flex row.
$checks['RenderProfile emits balanced divs'] = ($open - $close) === 0;

// ── core-wrapper specifically ───────────────────────────────────────────────
$checks['core-wrapper is opened'] = strpos($html, 'class="core-wrapper"') !== false;
// It must be closed INSIDE RenderProfile's own output, not left to the browser or a later template.
$checks['core-wrapper is closed by RenderProfile'] =
    ($open - $close) === 0 && strpos($html, 'class="core-wrapper"') !== false;

// ── The panes are all present ───────────────────────────────────────────────
// SWUDeck's SiteDef declares sections ['welcome+changePassword', 'team', 'developerOptions'].
$checks['welcome+changePassword pane rendered'] = strpos($html, 'profile-pane') !== false;
$checks['team panel rendered']                  = strpos($html, 'team-management') !== false;
$checks['developer options panel rendered']     = strpos($html, 'oauth-management') !== false;

// ── The footer is emitted AFTER, by the caller ──────────────────────────────
// Pinning this makes the balance check above meaningful: it is precisely because PageEntry appends
// the disclaimer as a following sibling that an unclosed wrapper swallows it.
$checks['RenderProfile does not itself emit the disclaimer'] = stripos($html, 'class="disclaimer"') === false;
$entry = @file_get_contents($root . '/SharedUI/Render/PageEntry.php');
$checks['PageEntry emits Disclaimer after RenderProfile'] =
    $entry !== false
    && ($pp = strpos($entry, 'RenderProfile(')) !== false
    && ($dp = strpos($entry, "RenderTemplate('Disclaimer'", $pp)) !== false
    && $dp > $pp;

// ── The row must be able to wrap ────────────────────────────────────────────
// SECOND, independent bug (2026-08-08): mobile-responsive.css carries
// `@media (max-width:768px){ .core-wrapper { flex-direction: column } }` but that stylesheet is NOT
// loaded on the profile page — it links only menuStyles, tokens, components, hud.tokens,
// swudeck-overrides and menuStyles2. So `.core-wrapper` stayed `flex-direction:row; nowrap` at EVERY
// width, and three panes squeezed into a 390px phone: pane 1 at 42px against 204px of content,
// panes overflowing the right edge, and the document scrolling sideways. Reproduced in Chromium,
// Firefox AND WebKit, so it is not an engine quirk.
//
// Only pane 1 collapsed because `.profile-pane` sets `overflow:hidden`, which zeroes a flex item's
// AUTOMATIC MINIMUM SIZE; the other two are `overflow:visible` and held at their content width.
//
// The fix is wrapping + a sane flex-basis rather than a breakpoint, so the panes reflow at any
// width instead of only below one magic number.
$style = _ProfilePaneStyle();
$checks['profile style lets the row wrap']   = preg_match('/\.core-wrapper[^}]*flex-wrap\s*:\s*wrap/', $style) === 1;
$checks['panes have a wrapping flex-basis']  = preg_match('/flex\s*:\s*1\s+1\s+\d+px/', $style) === 1;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
if ($fails) {
    echo "FAIL (" . count($fails) . "/" . count($checks) . "):\n";
    foreach ($fails as $f) echo "  - $f\n";
    echo "  <div=$open  </div>=$close  balance=" . ($open - $close) . "\n";
} else {
    echo "PASS (" . count($checks) . " checks)\n";
}
