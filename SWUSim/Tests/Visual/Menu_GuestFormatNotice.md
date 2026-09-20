# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the MAIN MENU (SharedUI/Sites/SWUSim/MainMenu.php), which is not a gamestate.
#
# VISUAL CHECK — the guest format notice (#guest-format-notice), added 2026-09-20
#
#   SWUMenuTreeFor($swuLoggedIn, …) already REMOVES what a logged-out visitor cannot play: every Constructed pool
#   except Open, and the whole Twin Suns branch. So a guest sees a short dropdown with no explanation, and the only
#   other signal is JoinQueue's refusal — which arrives AFTER they have pasted a deck and pressed the button. This
#   note says so up front, next to the buttons it is about.
#
#   Server-rendered inside `<?php if (!$swuLoggedIn) ?>`, so there is no JS toggle and no flash of the wrong state.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit):
    cd DevTools/ui-harness && node swusim-guest-notice-xbrowser.mjs
Then look, by eye, at the menu in each browser you have:
    http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php
    — first LOGGED OUT (use a private window; the harness uses a fresh context)
    — then LOGGED IN as claudebot1 / pass

## WHAT TO LOOK AT

1. **Logged out — the note is there.** Directly under the action-button row (Join Queue / Save Deck / Create Private
   Room), in the same muted 13px style as the invite notice that sits above it. It reads:
   "Playing as a guest — Open lobbies and 1P modes only. Log in to play Premier, Eternal, Twin Suns and more."
2. **Logged in — the note is GONE.** Not hidden with `display:none`, not present in the DOM at all. This is the whole
   discriminator: a note that renders for everyone is the bug.
2b. **Logged out ON AN INVITE — the note is GONE too.** Open the menu with `?privateInvite=<code>` (or `?invite=<code>`)
   in a private window. An invite EXEMPTS the joiner from JoinQueue's login gate
   (`$swuNeedsAccount = … && $privateInviteCode === ''`), so a guest really can play the host's format whatever it is —
   Premier, Twin Suns, anything. Showing "Open lobbies only" there would be a lie on the one page where it matters
   most. The `#private-invite-notice` above it, which names the host's format, is the message that belongs here.
   The code does NOT have to resolve to a real lobby: the gate is arriving with one, not the lookup succeeding.
3. **The link works.** "Log in" is underlined, uses the accent colour, and goes to /TCGEngine/SharedUI/LoginPage.php —
   the same target as the Log In item in the top MenuBar. It must be visibly a link against the muted body text in
   EVERY theme (the accent token changes per theme; a colour that vanishes in one of them is a fail).
4. **Layout, 1400px and 420px.** The note wraps to two or three lines on a phone without pushing anything out of the
   "Create a New Game" card, and does not collide with the Saved Decks panel below (which is itself logged-in only,
   so at 420px logged out the note is the last thing in the card).
5. **It agrees with the dropdowns.** Logged out, Game type offers Constructed and 1P Mode only; Constructed → PvP
   offers only Open. If the note and the dropdowns ever disagree, the note is the thing that is wrong — both derive
   from $swuLoggedIn, but only SWUMenuTreeFor() is enforced by the server.
6. **⚠ DEV BOX READS CONSERVATIVE.** SWUBotPracticeAllowed() short-circuits on SWUIsLocalDevRequest(), so a LOCAL
   logged-out visitor also sees Arenabot — which the note does not mention. That is correct in production (where a
   guest genuinely cannot reach Arenabot) and expected locally. Do NOT "fix" the note to name Arenabot.
