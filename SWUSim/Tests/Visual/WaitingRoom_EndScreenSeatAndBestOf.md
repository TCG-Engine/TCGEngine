# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the shared waiting room (SharedUI/Render/WaitingRoom.php) after a 1v1 match,
# which only exists in a browser.
#
# VISUAL CHECK — Persistent lobby: the room after its match
#
# OWNER REQUIREMENT (2026-10-30, docs/superpowers/specs/2026-10-30-persistent-lobby-rematch-design.md)
#   "back to the waiting room where you can chat, swap decks, etc. then start the next Bo1 or Bo3 from there"
#   "it could say 'Player 2 still on end-game screen' or something. and then you can't start the game until
#    1. they come back to lobby and Ready up 2. they return to main menu and you get someone else to join your room"
#
# AUTOMATED: DevTools/ui-harness/swusim-persistent-lobby-xbrowser.mjs covers this in Chromium, Firefox and WebKit at
# 1280x800 and 390x844. This file is for a human look at the same screens.
#
# SETUP — two browsers (or a normal + a private window), so each holds only its own seat:
#   1. Browser A: Main Menu → create a private Premier lobby → copy the invite link.
#   2. Browser B: open the invite link, join with a deck. Both Ready; A presses Start.
#   3. Play (or concede) the game to the end. On A's end screen press Return to Lobby. Leave B on its end screen.
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   • A is back in the SAME room (same invite code, same chat history with "… returned to the lobby." as a grey line),
#     in seat 1, NOT READY.
#   • B's tile reads ON END-GAME SCREEN (a grey tag, in place of READY / NOT READY).
#   • Start Game is greyed out; the status line ends "Guest P2 is still on the end-game screen." (or B's username).
#   • Top right, A (the host) sees MATCH [BEST OF 1 | BEST OF 3] — the menu's options toggle, gold rim on the active one.
#     Click BEST OF 3. B, back in the room, sees "MATCH Best of 3" as plain text (no toggle) and is set to NOT READY.
#   • Both Ready; Start. The game that starts is a Best of 3, and the chat is still the same conversation.
#   • Phone width (390px): the toggle and Leave wrap above the invite; nothing scrolls sideways.
#   • Seat freeing: repeat, but B presses Return to Main Menu instead. B's seat disappears from A's room; a third
#     browser can join through the invite link, Ready, and A can start.
