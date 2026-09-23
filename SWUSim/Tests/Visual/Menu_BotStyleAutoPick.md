# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the MAIN MENU (SharedUI/Sites/SWUSim/MainMenu.php), which is not a gamestate.
#
# VISUAL CHECK — Arenabot's "Bot play style" AUTO-PICK (owner, 2026-09-22)
#
#   When a deck is entered for the Arenabot game, the menu reads the deck the BOT will play and preselects its
#   archetype in the "Bot play style" dropdown. The player can still change it; the NEXT deck load picks again
#   (owner's choice: "2 — always auto-pick on deck change").
#     Deck read:  the bot deck link, or the host's own deck when that field is empty (the bot then plays the host's list).
#     Endpoint:   APIs/SWUBotDeckStyle.php (read-only: no lobby, no game, no row).
#     Classifier: SWUSim/Custom/BotDeckStyle.php — a deck sharing >= 75% of its cards with one of the owner's 23
#                 labelled fixtures takes that label; otherwise the deck's shape decides (curve, removal, wipes,
#                 draw, big drops, burn, space, base).
#     Spec:       docs/superpowers/specs/2026-09-22-swusim-deck-style-classifier-design.md
#   Nothing about the reasoning is shown on screen. The dropdown simply changes.

## HOW TO RUN

Automated (Chromium / Firefox / WebKit):
    cd DevTools/ui-harness && node swusim-botstyle-autopick-xbrowser.mjs
The classifier and its accuracy (70% exact, 100% within one step, leave-one-out over the 23 labelled decks):
    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/bot_deckstyle_test.php
The endpoint, end to end:
    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off DevTools/tdd-regression/test_swusim_deckstyle_endpoint.php

By hand: open the menu, choose Game type "Arenabot", then paste a list into "Bot deck link:".

## WHAT TO LOOK AT

1. **The dropdown changes by itself.** Paste the Vader Yellow list (`SWUSim/Tests/BotFixtures/meta-2026-09/
   vader_yellow.txt`) into the bot deck field and click away: "Bot play style" switches to **Hyper Aggro**.
   Paste `krennic_splash.txt` instead: it switches to **Soft Control**.
2. **Your change sticks until the next deck.** Set the dropdown to Hard Control by hand: it stays Hard Control while
   you do anything else. Paste a different deck: it re-picks from that deck.
3. **A bad link changes nothing.** Type `not a deck` into the bot deck field: the dropdown keeps whatever it had, and
   no error appears — this field is validated when you start the game, not here.
4. **Nothing else moved.** No new text, no spinner, no layout shift; the field keeps its label ("Bot play style:") and
   its place between the bot deck link and the Game type row.
5. **Only Arenabot.** Switch the game type to anything else: the play-style field hides as before, and entering decks
   does nothing to it.
6. **An empty bot deck field still works.** Leave it empty and paste a list into your own deck box: the style is read
   from YOUR list, because that is the deck the bot will play.

## KNOWN LIMITS

- The pick is right about 70% of the time and within one step every time, measured against the owner's 23 labelled
  decks. A deck by an unlabelled leader leans on its shape alone, so check it before starting a game that matters.
- The two ends of the scale are the weak spots: Hyper Aggro and Hard Control lists often read one step softer, because
  only 2 and 3 of the labelled decks sit there.
