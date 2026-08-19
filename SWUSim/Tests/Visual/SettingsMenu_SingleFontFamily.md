# VISUAL CHECK — the in-game Settings menu renders in ONE font family
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test Schema
# Editor as SEAT 1 and open the gear menu (top-right of the board header).
#
# WHAT CHANGED, AND WHAT WRONG LOOKS LIKE (2026-08-18, live report: "let's fix the fonts on this in-game
# menu. they should be consistent")
# Nothing on the SWUSim board sets a `font-family` on <body> — every element opts in explicitly via
# --swu-font-ui / --swu-font-label — and .swu-settings-panel never did. Measured before the fix, one
# 640px panel rendered THREE faces at once (identical in Chromium, Firefox and WebKit):
#   • Times           — section titles, every row label, the "Background"/"Card back"/"Playmat" field
#                       labels, the hotkey descriptions, the bookmark rows. These inherited from <body>
#                       and got the UA default, which is a SERIF.
#   • Arial           — the three cosmetic <select>s. Form controls do not inherit font in ANY engine.
#   • Trebuchet MS    — Concede / Return to Main Menu / Report Bug / the close ✕ / the Block Player
#                       widget, via .btn's `font-family: var(--font-display)`.
#   (and the head + key chips were a fourth stack, --swu-font-label, which resolves DIFFERENTLY from
#    --font-display: both are Windows-first, and they fall back to different generics.)
# The panel now declares one family and everything inside inherits it. Hierarchy is carried by size,
# weight, tracking and case, which the titles and key caps already had.
#
# WHAT TO LOOK AT, IN ORDER
#   1. Open the gear menu. Read down the LEFT column: "Settings" / "COSMETICS" / "Show playmats" /
#      "Card motion" / "Background" / the dropdown's own text / "MATCH" / "CONCEDE". Every one of those
#      must be the same typeface. Wrong looks like the row labels having SERIFS while the buttons and
#      dropdown text do not — that is the exact reported state.
#   2. The RIGHT column: "HOTKEYS" and the four descriptions must match the left column, and the key
#      caps (Space / I / U / Esc) must match too. ⚠ The caps are <kbd>, which carries a UA default of
#      `monospace` in all three engines — if they look like a typewriter face, the family opt-in on
#      .swu-hotkey-key has been dropped again. This one is easy to reintroduce and easy to miss.
#   3. The three cosmetic dropdowns: the CLOSED control text is what this covers. (The open native
#      option list is drawn by the OS and will not match — that is expected and not a bug.)
#   4. Concede / Return to Main Menu / Report Bug must be the same SIZE as each other in every browser.
#      .btn sets no font-size, so these took the UA <button> default — 13.33px in Chromium/Firefox but
#      11px in WebKit. They are pinned to 13px now; compare Safari against Chrome side by side.
#   5. Concede (do NOT confirm) so the Block Player widget and, in a rated game, the Gamestate Bookmarks
#      list are on screen — both mount inside this panel AND in the game-over overlay, which also sets
#      no font-family. Check them in BOTH places; the game-over mount is the one that regresses silently.
#
# THE NEGATIVE THAT MATTERS
#   Nothing OUTSIDE the panel may change. The board's badges, pills and counters still use
#   --swu-font-label directly (six rules near the top of GameLayoutShared.php) and must look exactly as
#   before — the fix is scoped to `.swu-settings-panel` descendants plus the two shared sub-widgets.
#
# CROSS-BROWSER: Chromium, Firefox AND Safari per CLAUDE.md — and this one genuinely needs all three,
# because the defect was three engines agreeing on three different fallbacks. Verified 2026-08-18 by
# rendering the panel's real CSS in all three: 3 distinct rendered faces before, 1 after, panel width
# identical (642px) and no hotkey label wrapped. Also check ?swuLayout=mobile, where this same overlay
# is the phone menu and the two columns stack.
#
# BOARD SHAPE (why each element is here)
#   Nothing on the board matters to this check — the menu is an overlay. A minimal legal board is used
#   so the schema loads, and a couple of units are present only so the panel is not floating over an
#   empty playmat while judging contrast.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
WithP1Hand: SOR_063
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN

## EXPECT
# Not run by the regression runner — kept so the fixture can be validated by hand with
# `run-schema-tests.php SWUSim/Tests/Visual/SettingsMenu_SingleFontFamily.md`.
P1HANDCOUNT:1
P1RESCOUNT:5
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
