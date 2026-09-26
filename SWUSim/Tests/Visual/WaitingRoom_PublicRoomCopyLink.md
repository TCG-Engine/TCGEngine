# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the WAITING ROOM page (SharedUI/Render/WaitingRoom.php),
# which has no gamestate at all — the room is an APCu lobby, not a game.
#
# VISUAL CHECK — "Copy Link" on a PUBLIC Twin Suns waiting room
#
# OWNER FEATURE REQUEST (2026-09-26)
#   "people are erroneously copying the URL and pasting it in a chat to find players. a strict copy
#    link would be nice to have players be able drop into a forming public queue room or invite their
#    friends."
#
# SETUP — two rooms, same format, differing ONLY in public/private. Use TWO accounts: a player holds
# ONE lobby at a time, so creating both from one account abandons the first.
#   PUBLIC (claudebot1):
#     curl -s -c /tmp/j1 -d 'submit=1&userID=claudebot1&password=pass' \
#       http://localhost:3400/TCGEngine/AccountFiles/AttemptPasswordLogin.php >/dev/null
#     curl -s -b /tmp/j1 -d 'rootName=SWUSim&format=teamsuns&queueType=bo1' \
#       --data-urlencode "deckLink@SWUSim/Tests/BotFixtures/twinsuns_deck_a.txt" \
#       http://localhost:3400/TCGEngine/APIs/Lobbies/JoinQueue.php
#   PRIVATE (claudebot2): same, but 'createPrivate=1' and no queueType.
#   Then open, for each:  .../SharedUI/Sites/SWUSim/WaitingRoom.php?lobby=<lobbyID>
#   (the browser needs the seat key: localStorage 'tcg:lobbyAuth:<lobbyID>' = {"authKey":"<authKey>"})
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   • THE PUBLIC ROOM shows a single button reading exactly "Copy Link", and NO code beside it.
#     Until 2026-09-26 it showed nothing at all: inviteCode was minted only in JoinQueue's
#     createPrivate branch, so the button's `if (!d.inviteCode) return;` swallowed it every time.
#   • THE PRIVATE ROOM is UNCHANGED: "Invite: <24 hex chars>  [Copy Invite Link]".
#     ⚠ The difference is deliberate and is the whole design. A public room is reachable through
#     matchmaking by anyone, so its code is an ADDRESS, not a password — printing 24 hex characters
#     next to the button is noise. A private room's code IS the secret, and people read it out to each
#     other, so it stays on screen.
#   • CLICK IT. The button flips to "Copied!" for ~1.2s and a success toast says "Room link copied."
#     (public) or "Invite link copied." (private). Never a native alert — StyledDialog's Toast.
#   • PASTE IT. The link is .../WaitingRoom.php?invite=<code> — NOT the ?lobby=<id> URL in your
#     address bar. Opening it in a second browser lands on the room's deck bar; joining from there
#     puts that player in THIS room.
#   • CLIPBOARD BLOCKED (deny clipboard permission, or Safari without activation): a themed
#     StyledPrompt appears titled "Room Link" / "Invite Link" with the link pre-filled and
#     selectable, so it can still be copied by hand. Not an alert — an alert cannot be copied from.
#
# ⚠ THE BARE ?lobby= URL STILL MISROUTES, AND THAT IS KNOWN AND UNFIXED (owner's call, 2026-09-26).
#   A hand-copied address-bar URL renders the room fine, but its Join button posts an EMPTY
#   privateInviteCode, and JoinQueue then falls through to ordinary matchmaking — the joiner ends up
#   in some other game. That is the original complaint's root cause. The Copy Link button is the
#   supported path; fixing the bare URL was explicitly deferred. Do not "fix" it here by accident.
#
# ── AUTOMATED COVERAGE (both must stay green — this file is for what they cannot see) ───────────
#   • DevTools/tdd-regression/test_swusim_public_room_invite_link.php — the BACKEND: a public room is
#     issued a code, the poll reports it plus isPrivate, and joining by it lands in THAT room.
#     ⚠ Its key assertion is built to avoid a vacuous pass: "second player joins with the code and
#     lands in the same lobby" is meaningless for a public room, because matchmaking would have put
#     them there anyway. It joins a TEAMSUNS room while asking for TWINSUNS, so only the code can
#     explain the outcome, and a control repeats the request without the code to prove they differ.
#   • DevTools/ui-harness/swusim-room-copy-link-xbrowser.mjs — the PAGE, in chromium + firefox +
#     webkit, 42 checks: the button renders, its wording, that the code is printed ONLY on a private
#     room, and what string actually reaches the clipboard (the clipboard is stubbed so all three
#     engines can be measured the same way). It seeds and cleans up its own rooms.
#
# ⚠ WHAT NEITHER CAN SEE, and why this file exists: whether the button is FINDABLE — it sits in
#   #wr-invite above the roster, and on a public room it is now the only control in that strip. If it
#   reads as a stray button rather than "share this room", that is a design finding for the gameboard
#   /lobby review, not a test failure.
