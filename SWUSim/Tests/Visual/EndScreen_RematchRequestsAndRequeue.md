# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the end-game screen of a finished 1v1 MATCH (GameLayoutShared.php
# SWUEndScreenButtons / SWUShowEndRequestPopup / SWUUpdateEndScreen / SWUEndRequeue), which needs a real match record —
# a Test Schema Editor board has none, so its end screen is the plain local one.
#
# VISUAL CHECK — Persistent lobby: rematch requests, Return to Lobby, Requeue
#
# OWNER REQUIREMENT (2026-10-30, docs/superpowers/specs/2026-10-30-persistent-lobby-rematch-design.md)
#   "clicking Rematch gave better feedback than just text change. perhaps a notification pop up to accept or decline.
#    and appropriate game logs that say a rematch was requested and accepted or declined"
#   "public matches should have a 'Requeue' option or 'Return to Menu'. no return to lobby"
#   "convert to Bo3 should also get a popup"
#
# AUTOMATED: DevTools/ui-harness/swusim-persistent-lobby-xbrowser.mjs covers the room request / decline / accept and the
# public Requeue in Chromium, Firefox and WebKit at 1280x800 and 390x844. This file is for a human look.
#
# SETUP — two browsers (a normal + a private window). For a ROOM match: private lobby → invite → Start, then end the game
# (concede). For a PUBLIC match: both queue Premier Bo1 with any deck; they pair; end the game.
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   ROOM MATCH, both end screens:
#   • Buttons: RETURN TO LOBBY · REMATCH · QUICK REMATCH · [Bo1] · CONVERT TO BEST OF 3 · RETURN TO MAIN MENU · REPORT BUG.
#     The [Bo1] chip sits at the right end of the QUICK REMATCH row (not on a row of its own), desktop and phone.
#   • A presses REMATCH → A's button reads "REMATCH REQUESTED…" (greyed) and the line under the buttons reads
#     "Waiting for <B> to answer…". B gets a centred pop-up "<A> wants a rematch" with ACCEPT AND RETURN TO LOBBY / DECLINE.
#     The game log (both sides) has a grey "<A> requested a rematch."
#   • B presses DECLINE → A's line reads "<B> declined." and the log "<B> declined the rematch."; A's button is REMATCH
#     again and can be pressed straight away.
#   • Ignored: A asks again, B does nothing → nothing happens; after 30s A's button reads REQUEST AGAIN.
#   • B accepts → both pages land in the room, both NOT READY, same chat.
#   • QUICK REMATCH: the [Bo1] chip next to it switches Bo1/Bo3; B's pop-up says "Quick Rematch, Best of 3", ACCEPT /
#     DECLINE. Accept → both boards go straight into a new game (same decks, different opening hands).
#   • CONVERT TO BEST OF 3 (after a Bo1): pop-up "… wants to convert to Best of 3", ACCEPT / DECLINE. Accept → both end
#     screens rebuild for the Bo3 (GO TO NEXT GAME → sideboarding for game 2).
#   • A presses RETURN TO LOBBY alone → A is in the room; B's line reads "<A> returned to the lobby." and B's Rematch /
#     Quick Rematch / Convert buttons are gone (RETURN TO LOBBY is still there).
#   PUBLIC MATCH:
#   • Buttons: REQUEUE · REMATCH · QUICK REMATCH · [Bo1] · CONVERT · RETURN TO MAIN MENU · REPORT BUG — no Return to Lobby.
#   • A presses REQUEUE → A lands on the main menu with "Waiting for opponent…" up (Esc cancels). B's line reads
#     "<A> requeued." and B's request buttons are gone.
#   • A Rematch accepted after a public match opens a NEW private room with both players, A as host, and the match chat.
#   RATED (Meta Premier): only REQUEUE · RETURN TO MAIN MENU · REPORT BUG. If the queue refuses (e.g. the abandon
#   cooldown), the reason shows under the buttons and the page stays.
#   PHONE WIDTH: the buttons stack in one column; the pop-up fits the screen; nothing scrolls sideways.
