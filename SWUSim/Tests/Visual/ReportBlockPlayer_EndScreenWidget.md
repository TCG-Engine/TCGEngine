# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# VISUAL CHECK — Report/Block Player (end screen + in-game menu)
#
# Visual-only guide (Tests/Visual/ is not scanned by the regression runner). It needs a REAL match between two
# LOGGED-IN accounts: the widget returns null unless both seats have a username (SWU_SEAT_USERNAMES), so a schema
# board, a goldfish game or a guest opponent shows nothing — that is correct, not a bug.
#
# HOW TO GET THERE
#   Log in as claudebot1 and claudebot2 in two browsers, make a private Premier room, start it. As seat 1:
#     • the gear menu (top-right) → MATCH → "REPORT/BLOCK PLAYER ▸", and
#     • Concede → the game-over overlay → the same widget under the stats table.
#   The scripted version of all of this, in Chromium, Firefox and WebKit at desktop and phone size, is the
#   reportblock harness used when this was built (2026-10-27): it asserts the strings below and screenshots each step.
#
# WHAT RIGHT LOOKS LIKE (docs/superpowers/specs/2026-10-27-saved-chat-and-player-reports-design.md)
#   1. Collapsed header reads "REPORT/BLOCK PLAYER ▸" (▾ when open). It replaced "BLOCK PLAYER".
#   2. Open: the opponent's name, then three buttons in this order — Block (red), Report (amber), Report & Block
#      (amber). Red stays on Block alone: Report is not destructive. In a narrow column "Report & Block" wraps to a
#      second line; that is expected.
#   3. Report / Report & Block open a note box (placeholder "What happened?", 1000 characters max) and a Submit
#      button under it, right-aligned. On a phone the box spans the panel and Submit stays reachable by scrolling.
#   4. Submit → the form is replaced by "Reported. A moderator will review it." A second report in the SAME match
#      reads "Added to your earlier report."; past three follow-ups, "You've already reported this player for this
#      match."
#   5. Report & Block: after the report is accepted, the normal block confirm appears ("Block this player? …").
#      Block alone behaves exactly as before (confirm, then the widget hides).
#
# THE NEGATIVE THAT MATTERS
#   The widget must still be ABSENT for a spectator, a guest viewer, a guest opponent and a bot seat — Report uses
#   exactly the rule Block always had. And the reported player is never told anything.
#
# CROSS-BROWSER: Chromium, Firefox and WebKit, desktop and ?swuLayout=mobile — verified 2026-10-27 (48/48 checks in
# the gear menu, 8/8 on the end screen in Chromium + WebKit; screenshots looked at).
