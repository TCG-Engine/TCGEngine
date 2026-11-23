<?php
require_once __DIR__ . '/GameLayoutDevice.php';   // SWUSimIsMobileRequest — phones keep the Home panels
// The multiplayer TABLE — every board of the game on one page, in the 1v1 Always Two table's look
// (docs/superpowers/specs/2026-11-21-swusim-multiplayer-table-design.md). This file decides WHICH games get it and WHO SITS WHERE;
// the layouts (GameLayout.php / GameLayoutMobile.php / GameLayoutShared.php) draw it.
//
// ── OWNER'S REVERT SWITCH ── (a PLAYER can also choose the Home panels for themselves: SWUTableHomePanelsPreferred below)───────────────────────────────────────────────────────────────────────────────────────────────
// The table REPLACES the Home panels (home view, its tiles, Zoom In, the carousel) for the modes below. The Home panels code is
// untouched and still in place: set a family to false to bring them back for it (owner, 2026-11-21: "replace them. but keep the
// other layout commented just in case i want to revert it").
//   table4  — every 4-seat game: Twin Suns 4P, Team Suns, 4P Always Two
//   table3  — 3-seat Twin Suns (Phase 2: opponents across the top, you across the bottom)
//   a2-1v1  — 1v1 Always Two on this page instead of SWUSim/AlwaysTwoTable.php (Phase 3, not built yet)
function SWUTableLayoutSwitch(): array {
    $switch = ['table4' => true, 'table3' => true, 'a2-1v1' => false];
    // Test hook only (table_seating_test.php): never set by the product.
    if (isset($GLOBALS['SWU_TABLE_SWITCH_OVERRIDE']) && is_array($GLOBALS['SWU_TABLE_SWITCH_OVERRIDE'])) {
        $switch = array_merge($switch, $GLOBALS['SWU_TABLE_SWITCH_OVERRIDE']);
    }
    return $switch;
}

// 'table4' when the LOADED game is drawn as the 4-place table, else '' (the ordinary board / Home panels).
// Decided by the game and the DEVICE: a phone (the same user-agent test that picks the phone layout, GameLayoutDevice.php — tablets
// count as desktop) keeps the Home panel tiles (owner, 2026-11-22: "on mobile, let's revert to the home panel tiles layout we had
// before. desktop looks great though"). A narrow DESKTOP window still gets the table, its columns stacked. A game that SEATED four
// keeps the table after a seat is defeated (seating never changes mid-game). Harnesses that pin the Home panels pass swuTable=off —
// honoured on dev only.
function SWUTableLayoutKind(): string {
    if (($_GET['swuTable'] ?? '') === 'off' && function_exists('SimGameIsDevelopmentEnvironment') && SimGameIsDevelopmentEnvironment()) {
        return '';
    }
    if (SWUSimIsMobileRequest()) return '';
    $seats = count(GetSeatOrderArray());
    if ($seats === 4) {
        if (SWUAlwaysTwoSharedSeat()) return '';   // 1v1 Always Two: its own table until Phase 3
        $kind = 'table4';
    } elseif ($seats === 3) {
        $kind = 'table3';
    } else {
        return '';
    }
    if (empty(SWUTableLayoutSwitch()[$kind])) return '';   // the owner's switch first: a player can never turn the table ON
    return SWUTableHomePanelsPreferred() ? '' : $kind;
}

// The player's "Home Panels Layout" choice (owner 2026-11-22: "so people can switch on the previous layout if they so choose").
// Read before the page is built, so the browser layer is a COOKIE (swuHomePanels = '1' Home panels / '0' the table; set by
// swuSetHomePanels in GameLayoutShared.php) — the other per-browser settings live in localStorage, which the server cannot see. The
// browser's choice wins when it has made one; else the account's (setting 4, SWUSim/PlayerSettings.php); else the table.
function SWUTableHomePanelsPreferred(): bool {
    $c = $_COOKIE['swuHomePanels'] ?? null;
    if ($c === '1') return true;
    if ($c === '0') return false;
    $uid = intval($_SESSION['userid'] ?? 0);
    if ($uid <= 0) return false;
    if (!function_exists('SWUSimAccountHomePanels')) require_once __DIR__ . '/../PlayerSettings.php';
    return SWUSimAccountHomePanels($uid) === true;
}

// Who sits where, for one viewer. Each column is one pairing: the bottom board faces the board above it.
//   Free-for-all: clockwise from the viewer — bottom-left, up the left, across the top, down the right.
//     P1: [P2][P3] / [P1][P4] (owner, 2026-11-21: "to preserve the clockwise pattern").
//   Teams (Team Suns, 4P Always Two): you and your teammate along the bottom, each facing an enemy, paired as the 1v1 Always Two
//     table pairs them (1<->4, 3<->2). P1: [P4][P2] / [P1][P3] (owner: "put the teammates together at the bottom").
function SWUTableSeating(int $viewerSeat, array $seatOrder, bool $teams): array {
    $order = array_values(array_map('intval', $seatOrder));
    $at = array_search($viewerSeat, $order, true);
    if ($at === false) $at = 0;
    $rot = array_merge(array_slice($order, $at), array_slice($order, 0, $at));   // [me, next clockwise, across, last]
    if (count($rot) === 3) {
        // 3P (owner 2026-11-22: "[them1][them2] / [Me]"): the two opponents clockwise across the top, you across the whole bottom.
        // The right column holds them 2 ALONE — bottom seat 0, which the generated SWURenderSeatPair skips.
        return ['left' => ['bottom' => $rot[0], 'top' => $rot[1]], 'right' => ['bottom' => 0, 'top' => $rot[2]]];
    }
    if ($teams) {
        $face = [1 => 4, 3 => 2, 2 => 3, 4 => 1];
        // Teams are by seat parity (SWUTeamOf: 1,3 Red / 2,4 Blue).
        $mate = $rot[2];
        foreach ($order as $s) if ($s !== $rot[0] && $s % 2 === $rot[0] % 2) { $mate = $s; break; }
        return ['left'  => ['bottom' => $rot[0], 'top' => $face[$rot[0]]],
                'right' => ['bottom' => $mate,   'top' => $face[$mate]]];
    }
    return ['left'  => ['bottom' => $rot[0], 'top' => $rot[1]],
            'right' => ['bottom' => $rot[3], 'top' => $rot[2]]];
}

// The wide table's log/chat column, in px: only as wide as the whisper row ("Whisper: [P2] [P3] [P4]") on one line, its last chip
// nearly touching the screen's right edge (owner, 2026-11-22) — the boards get the rest. ONE number: the table CSS
// (GameLayoutShared.php) and the card-size formula (NextTurn.php) both read it from window.SWUTableLayout.sidebarW.
// Measured 2026-11-22: the row is 196px in Chromium, Firefox and WebKit; + 12px left / 6px right padding + 4px slack = 218.
function SWUTableSidebarWidth(): int { return 218; }

// What the page needs: echoed by the layout as window.SWUTableLayout.
function SWUTableClientConfig(int $viewerSeat): array {
    $kind = SWUTableLayoutKind();
    if ($kind === '') return ['kind' => ''];
    return ['kind' => $kind, 'seating' => SWUTableSeating($viewerSeat, GetSeatOrderArray(), SWUIsTeamGame()),
            'sidebarW' => SWUTableSidebarWidth()];
}
