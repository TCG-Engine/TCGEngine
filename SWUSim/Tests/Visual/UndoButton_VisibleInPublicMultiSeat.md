# VISUAL CHECK — the Undo button must appear in a PUBLIC game with 3+ seats
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Automated probe:
#   cd DevTools/ui-harness && node swusim-undo-button-xbrowser.mjs
#
# REPORTED 2026-09-26 (game 1310334, a 3-seat public Twin Suns game): the Undo button does not show up —
# first noticed as "not for P3 or P4", then corrected to "it's not showing up for anyone".
#
# MEASURED: the button is IN the DOM with `display: inline-block` — swuUpdateUndoUI's own gate passes,
# UNDO_AVAILABLE is "true" — and it still renders 0x0, for every seat. The collapse is one level up:
#   #swuUndoSplit { display: none; }                       <- the wrapper's BASE state
#   #swuUndoSplit.is-split { display: inline-flex !important; }
# and `.is-split` is toggled from
#   showCaret = showPhase || showBookmark,  showPhase = isPrivate || seats <= 2,  showBookmark = isPrivate
# so in a PUBLIC game with 3+ seats showCaret is false, the class is removed, and the wrapper — Undo
# button and all — is display:none. `.is-split` only ever meant "show the CARET", and #swuUndoMenuBtn
# already has its own independent base `display:none` + `.is-split` reveal, so the wrapper never needed
# to key on it.
#
# ⚠ WHY NO SCHEMA FIXTURE CAN CATCH THIS. GAME_IS_PRIVATE and GAME_SEAT_COUNT are stamped by the real
# game-creation / after-action path; a board built through TestSchemaSetup ships them EMPTY, and the
# client reads `parseInt(GetSWUDQVar('GAME_SEAT_COUNT') || '2', 10)` — which DEFAULTS TO 2 and takes the
# `seats <= 2` branch. A fixture therefore renders the button happily while the live 3-seat game does
# not. Measured: fixture seats="" priv="" -> visible; game 1310334 seats="3" priv="false" -> 0x0.
#
# SO THE PROBE DRIVES THE FUNCTION, NOT A FIXTURE. swuUpdateUndoUI is a pure function of the lobby facts
# it reads, so the probe stubs GetSWUDQVar over a REAL board page (real CSS, real DOM, real engine) and
# sweeps the matrix — the same technique ChooseOpponent_PickerShowsPlayerNames.md uses.
#
# WHAT IT ASSERTS, per engine:
#   • undo available, for every (private|public) x (2|3|4 seats): the button is VISIBLE (offsetWidth > 0).
#     The public/3 and public/4 cells are the reported bug; the rest are the controls that must not move.
#   • undo NOT available: the button is HIDDEN in every cell — the negative control. Without it the fix
#     could simply be "always show the wrapper", which would float an empty control in the header.
#   • the CARET (#swuUndoMenuBtn) still follows its own rule — shown only when private or <=2 seats —
#     so the fix separates the two gates rather than merging them.
#
# THE FIX (SWUSim/Custom/GameLayout.php) — one line:
#   #swuUndoSplit { … display: none; }  +  #swuUndoSplit.is-split { display: inline-flex !important; }
#     becomes
#   #swuUndoSplit { … display: inline-flex; }          (and the .is-split display rule is deleted)
#   ⚠ NO !important on that display. swuUpdateUndoUI hides the control between actions with an inline
#     style.display='none', and an !important stylesheet rule beats a NON-important inline style — the
#     same trap already documented in swuUpdateUndoMenuVisibility. With !important the negative-control
#     cells would flip to a permanently visible empty control.
#   Side benefit measured: the "no undo" cells now report split=none instead of split=flex, so the empty
#   wrapper stops occupying header space in 2-seat/private games too.
#
# AUTOMATED PROBE (2026-09-26): 54 assertions — 6 cells x 3 checks x Chromium/Firefox/WebKit, ALL PASS.
#   BEFORE: exactly two cells red — public/3-seat and public/4-seat, both `split=none is-split=false`
#           while the button itself was already `inline-block`. Every other cell and every negative
#           control was green, which is what pins the diagnosis to the wrapper rather than the gate.
#   MUTATION: restoring the two old rules turns exactly those two cells red again; nothing else moves.
#   LIVE: game 1310334 re-measured after the fix — the Undo button is visible (59px, "Undo") for seats
#         1, 2 AND 3, where before it was 0x0 for all three.

## GIVEN
#// Any 3-seat board; the probe drives the undo UI directly, so the board is only a host for the real
#// stylesheet and header markup.
CommonSetup: rrk/bbw/{myLeader:SHD_014}
WithSeatOrder: 1234
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase

## WHEN

## EXPECT
SEATCOUNT:3
