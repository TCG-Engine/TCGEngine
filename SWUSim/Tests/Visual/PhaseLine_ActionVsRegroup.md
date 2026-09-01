# VISUAL CHECK — the sidebar phase line: START → ACTION → REGROUP → … → END
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand, or simply play any two-seat game and watch the sidebar header.
#
# WHY THIS EXISTS — community feedback (2026-08-31):
#   "Most people think of the regroup phase as simply the start of the turn instead of its own phase…
#    if the community started thinking 'start game → action phase → regroup phase → action phase' rather
#    than 'turn 1 → turn 2 → turn 3' I think it would benefit the community as a whole."
# The board showed a Round number and nothing else, so nothing on screen said which phase you were in.
# (A phase track existed in the JS — updatePhaseTrack() + normalizePhase() — but its markup had been
# commented out of GameLayout.php, so it was a consumer with no producer and rendered nothing.)
#
# USER RULING: keep the Round counter; the vocabulary is exactly START → ACTION → REGROUP → ACTION →
# REGROUP → … → END, with NO regroup sub-steps (an earlier cut showed "REGROUP PHASE · RESOURCE" and it
# both clipped and added noise).
#
# ⚠ ONLY A HUMAN CAN CHECK THE LOOK. The schema suite never renders. A Playwright pass has already
# measured the FACTS on Chromium and Firefox — the data-phase-kind attribute, the computed colour,
# scrollWidth <= clientWidth (no clipping), and that .swu-header-right is still fully on screen — so
# what is left here is judgement: does it read well, and does it teach the round structure?
#
# ── WHAT TO DO ────────────────────────────────────────────────────────────────────────────────────
#   1. Start a two-seat game and stop at the mulligan / "choose 2 cards to resource" prompts.
#   2. Finish the pregame on both seats.
#   3. Pass with both players so the round leaves the action phase.
#   4. Resource a card, let the round tick over, and watch the line flip back.
#
# ── WHAT TO LOOK AT ───────────────────────────────────────────────────────────────────────────────
#
# THE LINE ITSELF — its own full-width row directly under the Round number, above "Last Played":
#   • pregame            → a dim white dot + START
#   • action phase       → a WARM GOLD dot + ACTION      (#d8ab34, the board's existing accent)
#   • regroup phase      → a COOL BLUE dot + REGROUP     (#6fb6e8)
#   • after the game ends→ a dim white dot + END
#   • ⚠ IT MUST NOT PUSH THE UNDO / GEAR BUTTONS. That is exactly what the first cut did: the line
#     started life as a third line inside the Round column, and because #swuSidebarHeader is a
#     space-between flex row a nowrap label widened that column and shoved the button cluster clean off
#     the panel. Confirm Undo and the gear are both fully visible in EVERY phase.
#   • ⚠ NOT COLOUR-ONLY. The word is always printed, so the line still reads correctly in greyscale and
#     to a colour-blind player; the colour is reinforcement, not the signal.
#
# WHAT IT SHOULD TEACH — the point of the whole change:
#   • The Round number ticks ONCE per full loop, while the line flips twice. Watching that for two
#     rounds should make "action phase → regroup phase → action phase" the obvious reading, rather than
#     "turn 1 → turn 2".
#   • The regroup phase is brief but real: you should be able to SEE it, because the resource step waits
#     for a decision. If REGROUP flashes past too fast to read on a normal connection, say so — that is
#     a finding, and the answer would be to hold the label briefly rather than to shorten the phase.
#
# BOTH LAYOUTS: the same line exists in GameLayout.php (desktop) and GameLayoutMobile.php, with one
# shared CSS/JS definition in GameLayoutShared.php. Check the mobile drawer too — the sidebar is
# narrower there, so it is the likelier place for a fit problem.
#
# ── ALSO WORTH A LOOK WHILE YOU ARE HERE ──────────────────────────────────────────────────────────
# The Undo button now appears for BOTH seats. It never did for player 2: the undo stack lives in seat
# 1's Versions zone but the client gated on the viewer's own zone, so seat 2 always read an empty stack
# and the button was never drawn. Confirm Undo is present on the player-2 screen as well as player 1's.

## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithP1Resources: 6
WithP2Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN

## EXPECT
