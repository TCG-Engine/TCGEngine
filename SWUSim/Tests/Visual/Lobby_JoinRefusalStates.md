# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. There is no board here at all: this pins the WAITING ROOM page.
#
# VISUAL CHECK — why you could not join a Twin Suns room
#
# Visual-only (Tests/Visual/ is not scanned by the regression endpoint).
#
# OWNER REQUEST (2026-09-26)
#   "we need better error messages to the user that explains better what happened. why they couldn't
#   join." Plus the ruling on what a blocked visitor gets: "perhaps add them to the live waiting room.
#   but make a note that they will be a spectator until a seat opens up. if they get into this state,
#   give them an option to requeue."
#
# WHAT WAS WRONG
#   Following a PUBLIC room's Copy Link produced ONE message for every possible cause — "That invite is
#   invalid or has expired." — including for a live room with a free seat. PollLobbyUpdates.php
#   resolved an invite code only when `!empty($lobby->isPrivate)`, and a public Twin Suns room is the
#   only kind that HAS a Copy Link.
#
# SETUP (no fixture script — these are real rooms)
#   1. Sign in as claudebot1 and queue a Twin Suns game from the main menu. You land in the room.
#   2. Press Copy Link. That URL is what every check below opens
#      (…/SharedUI/Sites/SWUSim/WaitingRoom.php?invite=<code>).
#   3. Fill the room to 4/4 with claudebot2, claudebot3, claudebot4 — one account per seat, they hold
#      one lobby each.
#   4. Open the copied link in a PRIVATE window (a link recipient need not have an account).
#
# WHAT TO LOOK AT (desktop, 1280x900, and repeat in Firefox and Safari)
#
#   A. FULL ROOM — the link resolves; it does not dead-end.
#      • The four seat tiles render with their decks. This page used to be a red error line.
#      • Status bar, bottom: the people icon reads 4/4, and the sentence beside it is
#        "This room is full. Waiting for a seat to open — you will be able to join as soon as one does."
#        ⚠ THE SENTENCE MUST NOT REPEAT THE COUNT. The icon owns the number; "4/4  This room is full
#        (4/4)" is the noise the blockers line already avoids for the same reason. The first cut said
#        it twice and only the screenshot caught it.
#      • "Join with this deck" is present but DISABLED — a disabled control with a reason beats an
#        absent one.
#      • Above it: "You are not seated. If the game starts before a seat opens, you will watch as a
#        spectator." and a FIND ANOTHER ROOM button.
#
#   B. A SEAT OPENS while that page is still open — the promise in (A) being kept.
#      • Have claudebot2 press Leave. Do NOT reload the visitor's page.
#      • Within ~2 seconds: the freed tile becomes "Waiting…", the count drops to 3/4, the sentence
#        becomes "Pick a deck to take a seat.", and the Join button ENABLES itself.
#      • Paste a legal Twin Suns deck and join. You take the free seat.
#
#   C. THE GAME STARTS while you are waiting, unseated — you become a spectator, not an error.
#      • Refill to 4/4, open the link as a fifth person, then have the host press Start.
#      • The visitor's page reads "The game started without you — you are watching as a spectator."
#        and then loads the BOARD as a spectator (…NextTurn.php?…&playerID=S).
#      • ⚠ IT MUST BE playerID=S, NOT playerID=0. The URL is worth reading in the address bar. Before
#        this, an unseated viewer fell back to myPlayerID (0), NormalizeViewerIdentity rejected it, and
#        the board came up broken rather than spectating.
#      • Chat: the spectator gets NO composer and no notice — see Spectator_SeatPickerAndNoChat.md.
#
#   D. A DEAD LINK — the two causes read differently.
#      • Truncate the code in the URL (drop the last 4 characters) and open it:
#        "That room link isn't valid. Check you copied the whole link, including the code at the end."
#      • Have every seat Leave, then open the ORIGINAL link:
#        "That room has closed — everyone left before the game started."
#        ⚠ Only within ~10 minutes of closing. The `invite:` index is what distinguishes the two, and it
#        carries the lobby's TTL — after that a closed room correctly degrades to the "isn't valid"
#        wording. It never degrades the other way.
#      • BOTH states keep the deck box up, show "Pick a deck and search for another room." UNDER the
#        deck box, and offer BACK TO MENU plus FIND ANOTHER ROOM.
#        ⚠ The guidance is in #wr-deck-msg, NOT #wr-hint — #wr-hint lives inside the status bar, which
#        this state hides, so a string written there renders nowhere. That was the first cut's bug and
#        the screenshot is what caught it.
#
#   E. FIRST PERSON IN AN EMPTY QUEUE — the status line says ONE thing.
#      • Queue Twin Suns with nobody else waiting. The status line reads exactly
#        "Need at least 3 players to start." — the seat icon beside it reads 1/4.
#      • ⚠ NOTHING IS APPENDED TO IT. A "— share the room link to fill it faster" nudge was tried and
#        cut (owner, 2026-09-27): the Copy Link button is at the top of this same page, so it pointed
#        at a control already on screen, in the voice of a growth prompt. If that sentence ever comes
#        back, it was not asked for.
#
# AUTOMATED COVERAGE, AND WHAT IT CANNOT SEE
#   • SWUSim/DevTools/tests/lobby_invite_messages_test.php — which reason the SERVER returns for each
#     situation (web SAPI; it needs APCu).
#   • DevTools/ui-harness/swusim-room-join-refusals-xbrowser.mjs — 57 checks in chromium + firefox +
#     webkit, including the live re-enable in (B).
#   • Neither reads the page as a person does: whether the sentence and the seat-count icon say the
#     same thing twice, and whether the spectator line is where the eye lands. That is why (A) and (D)
#     are here.
