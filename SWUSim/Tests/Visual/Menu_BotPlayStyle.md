# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the MAIN MENU (SharedUI/Sites/SWUSim/MainMenu.php), which is not a gamestate.
#
# VISUAL CHECK — the Arenabot "Bot play style" dropdown (five archetypes, .superpowers/sdd/2026-09-17-swusim-bot-archetypes/task-10-brief.md)
#
#   Constructed → Arenabot reveals a "Bot play style" select alongside the bot deck link field. It now offers the
#   five bot archetypes (SWUSim/Custom/BotArchetypes.php) instead of the old three (Aggro/Normal/Control). Old
#   style names (aggro/normal/control) remain valid on the wire as permanent aliases — JoinQueue.php and saved
#   lobbies still accept them — but the dropdown itself no longer renders them as options.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit):
    cd DevTools/ui-harness && node botpractice-menu-xbrowser.mjs
Screenshots land in /tmp/arenabot-menu-shots/. NOTE: as of 2026-09-18 this harness still asserts the OLD
three-value dropdown ("styles are aggro/normal/control", "Normal is the default") and a later step that
selects the old "control" option by value — all now fail/timeout against the five-option dropdown. That is
expected until the harness is updated with owner approval; it is not a regression in the menu itself. Verify
the dropdown by eye using the steps below in the meantime.

Then look, by eye, at the menu in each browser you have:
    http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php   (logged in as claudebot1 / pass)

## WHAT TO LOOK AT

1. **Reveal.** Constructed → Arenabot → any pool. The "Bot play style" label and select appear alongside
   "Bot deck link:".
2. **Five options, in order.** Hyper Aggro, Soft Aggro, Midrange, Soft Control, Hard Control — left to right /
   top to bottom in that order, nothing missing, nothing duplicated.
3. **Default.** Midrange is preselected on page load (and after switching Arenabot → another game type → back
   to Arenabot).
4. **Layout, 1400px and 420px.** The row still fits inside the "Create a New Game" card at both widths; the
   select does not overflow or get clipped, and none of the five labels are truncated or wrapped mid-word.
5. **Theme.** The select uses the same `swu-queue-select` style as the other dropdowns (Match Type, Card pool)
   in every theme.
6. **Round-trip.** Pick each of the five options in turn and start an Arenabot game (or inspect the POSTed
   `botStyle` field); the value stored should match the option's `value` attribute (`hyperaggro`, `softaggro`,
   `midrange`, `softcontrol`, `hardcontrol`).

## BROWSERS

Chromium, Firefox, WebKit — all three launch locally (DevTools/ui-harness). Check all three; if WebKit will
not launch in a given environment, say so explicitly rather than implying Safari coverage.
