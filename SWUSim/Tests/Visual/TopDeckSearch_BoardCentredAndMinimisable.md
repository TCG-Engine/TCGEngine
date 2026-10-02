# VISUAL CHECK — the top-deck SEARCH popup centres on the board and can be minimised out of the way
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test Schema
# Editor, or run the automated probe:
#   cd DevTools/ui-harness && node swusim-topdeck-search-xbrowser.mjs
#
# REPORTED 2026-09-28, from a screenshot of ASH_110 Admiral Ackbar's "SEARCH THE TOP CARDS":
#   • the popup was centred on the VIEWPORT, so the chat/log sidebar pushed it off the board's centre;
#   • it covered the board completely, and the player had no way to review the game state before
#     committing to a pick. Owner: "people want a way to view the board before making a decision."
#
# THE FIX, in two places
#   Core/UILibraries20261001.js — ShowTopDeckSearchPanel gains a minimise/restore control.
#     ⚠ `minimized` lives in the ENCLOSING function, not in render(). render() removes the panel and
#       rebuilds it on every selection change, so a flag held inside would snap back to expanded the
#       moment the player clicked a card.
#     ⚠ Minimising is not just "hide the body": the OVERLAY is what covers the board, so it drops its
#       background AND takes pointer-events:none, with the pill opting back in. A transparent overlay that
#       still eats clicks would look fixed and be useless — which is why the probe hit-tests through it.
#     The minimised panel keeps only its title bar, rendered as a pill (border-radius 999px), matching
#       Core/MZRearrangePopup.js's minimised state.
#   SWUSim/Custom/GameLayout.php — `#topdecksearch-panel > .topdecksearch-box` gets the same sidebar
#     correction as the YES/NO panel: translateX(-1 * var(--swu-sidebar-w) / 2). It keeps its VERTICAL
#     centring; unlike the YES/NO prompt it shows 180px card art and has no room above the hand band.
#
# GLYPHS: "–" to minimise, "+" to restore, on BOTH minimisable panels (owner 2026-09-28). The end-game
# panel's restore button used "□" until then — see EndGameOverlay_MinimizeRestore.md.
#
# WHAT TO LOOK AT (and what the probe asserts — 108 assertions, 3 engines x {mobile, desktop})
#   • Expanded: the box's centre equals the BOARD's centre, i.e. (viewport − sidebar) / 2. At 1600px with
#     a 200px sidebar that is 700, not 800. The sidebar is measured by laying out a ruler element, NEVER
#     by parseFloat on getPropertyValue('--swu-sidebar-w') — a custom property comes back as an
#     unresolved token stream ("clamp(160px, 14vw, 200px)") and parseFloat silently yields 0, which
#     reported a CORRECT layout as broken on the sibling YES/NO probe.
#   • Expanded: both cards render, the overlay dims (alpha ≥ 0.4), and the popup DOES cover the board —
#     the negative control, so "never blocks the board" cannot pass by the popup simply not being there.
#   • Minimised: cards and confirm button gone, overlay background fully transparent, overlay
#     pointer-events:none, pill still clickable, and a real board card's centre hit-tests to something
#     OUTSIDE #topdecksearch-panel.
#     ⚠ Assert "the hit is not in the popup", NOT "the hit is the card". A card's centre legitimately
#       resolves to a board container (#stuffParent on desktop, #swuMobileRoot on mobile) depending on
#       what is painted above the art; the stricter form failed on a feature that works correctly.
#   • Restore: cards and dim both come back, and the state survived render()'s teardown.
#
# NOT COVERED HERE
#   Which cards are offered and whether the cost/count limits are right — that is the schema suite
#   (the ShowTopDeckSearchPanel callers, e.g. ash/AdmiralAckbar_*). The YES/NO prompt's own placement,
#   naming and target ring live in swusim-yesno-prompt-length-xbrowser.mjs.

## GIVEN
#// A board with units in both arenas so the minimised-state hit test has a real card to aim at. The probe
#// drives the popup directly with an ASH_110-shaped Param rather than playing Ackbar, so the fixture only
#// has to provide a board.
CommonSetup: bbk/bbk/{myResources:8}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN

## EXPECT
SEATCOUNT:2
