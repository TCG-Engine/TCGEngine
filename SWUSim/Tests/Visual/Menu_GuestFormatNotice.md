# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the MAIN MENU (SharedUI/Sites/SWUSim/MainMenu.php) and the in-game chat box,
# which are not gamestates.
#
# VISUAL CHECK — guest access: the guest notice (#guest-format-notice) and the in-game chat box
#
#   Since 2026-09-21 (owner: "we can remove the account requirement. we will just not let not-logged in users use the
#   chat feature") a guest gets the SAME menu as a logged-in player — every game type, every pool, Arenabot. The one
#   thing an account adds is in-game chat. The note says so, and the game page swaps the message box for a
#   "Log in to chat" note (#chatGuestNote, NextTurn.php). SubmitChat.php enforces it server-side.
#   (Before 2026-09-21 the note read "Open lobbies and 1P modes only" and was hidden on invite links.)
#
#   Both are server-rendered on the logged-in state, so there is no JS toggle and no flash of the wrong state.
#   Server half: SWUSim/DevTools/tests/guest_access_test.php.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit):
    cd DevTools/ui-harness && node swusim-guest-notice-xbrowser.mjs
Then look, by eye, at the menu in each browser you have:
    http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php
    — first LOGGED OUT (use a private window; the harness uses a fresh context)
    — then LOGGED IN as claudebot1 / pass

## WHAT TO LOOK AT

1. **Logged out — the note is there.** Directly under the action-button row, in the same muted 13px style as the invite
   notice above it. It reads: "Playing as a guest — log in to use in-game chat."
2. **Logged in — the note is GONE.** Not hidden with `display:none`, not present in the DOM at all. This is the whole
   discriminator: a note that renders for everyone is the bug.
2b. **Logged out ON AN INVITE — the note is still there.** An invite does not unlock chat, so the note is true on that
   page too.
3. **The link works.** "log in" is underlined, uses the accent colour, and goes to /TCGEngine/SharedUI/LoginPage.php —
   the same target as the Log In item in the top MenuBar. It must be visibly a link against the muted body text in
   EVERY theme.
4. **Layout, 1400px and 420px.** The note wraps cleanly on a phone without pushing anything out of the
   "Create a New Game" card.
5. **The menu does not change with login.** Logged out, Game type offers Constructed, Twin Suns and 1P Mode, and
   Constructed opens on Arenabot → Premier, exactly as logged in.
6. **In game, logged out.** Start a Goldfish game as a guest. Where the chat message box normally sits, a dark box reads
   "Log in to chat" ("Log in" is a link opening LoginPage.php in a new tab). The chat toggle still opens the log, so a
   guest can READ chat. No whisper row appears in Twin Suns (it attaches to the message box, which is absent).
7. **In game, logged in.** The normal message box and Send button, and no "Log in to chat" note.
