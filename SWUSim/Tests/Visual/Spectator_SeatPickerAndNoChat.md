# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. The board comes from the CLI fixture below, which pins a FIXED game id.
#
# VISUAL CHECK — the spectator's seat picker, and a spectator's missing chat composer
#
# Visual-only (Tests/Visual/ is not scanned by the regression endpoint).
#
# SETUP
#   docker exec -w /var/www/html/TCGEngine <container> \
#     php SWUSim/DevTools/make-fourseat-fixture.php --game=9932777
#   then open as a SPECTATOR (no seat, no authKey):
#     http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=9932777&playerID=S
#   Remove it afterwards with --remove.
#
# WHAT THIS PINS (owner, 2026-09-26)
#   1. "Spectators of a Twin Suns game cannot see as P3 or P4. this is due to the legacy view picker."
#   2. "Spectators are allowed to send chats. disable this feature. blank the input and send button.
#      and no message as to why."
#
# WHAT TO LOOK AT (desktop, 1440x1000)
#   • Top-left: a "Spectator View" box with FOUR buttons — P1 P2 P3 P4 — on ONE row, no wrap, no
#     clipping. The seat you are currently watching from is the GOLD one; the rest are navy.
#   • Click P4. The page reloads watching from seat 4: P4's board is now the near side, and P4's
#     button is the gold one. Clicking P3 does the same. Both were unreachable before.
#   • In a TWO-seat game the same box shows exactly two buttons — the picker is built from the live
#     seat list, not a fixed pair.
#   • Bottom-left chat widget: the log and the toggle are there, and the composer row is EMPTY —
#     no text input, no Send button, and NO notice explaining the absence. A spectator is simply not
#     offered chat.
#   • Compare with a seated viewer who is not logged in: they DO get "Log in to chat." That contrast
#     is the point — logging in is an action a guest can take, so it is worth saying; there is
#     nothing a spectator could do, so saying anything would be noise.
#
# ⚠ THE PICKER OVERLAPS THE BOARD, AND THAT IS PRE-EXISTING — FOR THE DESIGN REVIEW
#   It is `position:fixed; top:16px; left:16px`, so it sits ON TOP of the viewer's own home-strip
#   stats. Measured at 1440x1000: the box is 203x75 at (16,16) and covers that strip's Hand / Deck /
#   Discard readouts at y=78. Two buttons covered them too — four made the box ~124px wider, which is
#   why the labels are "P3" and not "As P3" (that clawed ~77px back, 280 → 203).
#   WHERE THIS OVERLAY BELONGS is a gameboard-layout question and is deliberately NOT decided here.
#   Raise it in the gameboard design review; do not quietly relocate it.
#
# AUTOMATED COVERAGE (both must stay green — this file is for the things they cannot see)
#   • DevTools/tdd-regression/test_swusim_spectator_view_and_chat.php — the MARKUP and the SERVER
#     gate: four perspective handlers, no composer for a LOGGED-IN spectator, SubmitChat refuses a
#     spectator, and a seated guest still gets the login note.
#     ⚠ CLI only. It fetches from the server that would be serving it, so under the web SAPI every
#     fetch returns empty and the "no composer" checks pass against a blank page.
#   • DevTools/ui-harness/swusim-spectator-picker-xbrowser.mjs — GEOMETRY in chromium + firefox +
#     webkit: every button on screen, none overlapping, and clicking P4 really lands on
#     viewerPerspective=4. 42 checks, 3 engines.
#
# ⚠ WHY BOTH. The HTTP test proves the buttons are in the markup; it cannot see a row that wraps out
#   of its box. The browser gate proves they are usable; it cannot see the server refusing a POST.
#   Neither can tell you whether the thing looks right — that is what this file is for, so OPEN THE
#   PAGE rather than trusting two green suites.
