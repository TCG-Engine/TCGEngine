# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the in-game deck/discard piles (SWUSim/Custom/GameLayout*.php).
#
# VISUAL CHECK — deck count badge + no deck/discard overlap (player report after a Twin Suns game, 2026-09-21:
#   "can't see own deck size/count; and the stacked image starts overlapping the discard on the right")
#
#   1. The deck is a Stacked single zone; Core's renderer zeroes the card's count bubble in that mode (the stack
#      depth stands in for it), so NO deck showed a number. SWUSim now adds its own badge (.swu-pile-count,
#      updateDeckCount in GameLayoutShared.php) from the zone data ("CardBack <n> -"), on both deck piles.
#   2. The stack's offset layers spill past the deck's box by up to 8 x 2px + borders (~18px) by design, but the
#      deck→discard gap was 6px, so a big deck's layers sat on the discard (a 74-card Twin Suns deck: 11px over).
#      The gap is now --swu-pile-gap: 18px, and the hand's reserved pile zone grew with it.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit; 2P Goldfish at 1728 / 1280 / 390 and a 3-seat Twin Suns game):
    cd DevTools/ui-harness && node swusim-deck-pile-xbrowser.mjs
Screenshots: /tmp/deck-pile-<engine>-2p-<width>.png, /tmp/deck-pile-<engine>-twinsuns.png.
⚠ Playwright's desktop browsers get the DESKTOP layout even at 390px (SWUSim picks the mobile layout by device, not
width) — check the mobile piles (.swu-m-pile) on a real phone or with ?swuLayout=mobile.

## WHAT TO LOOK AT

1. **Your deck** (bottom right) shows a small dark pill with the card count at the bottom-centre of the deck art —
   e.g. 44 in a fresh Premier game, 74 in Twin Suns. It updates on every draw, mill and shuffle-in, and reads 1 when
   one card is left (the stack draws no layers then). An empty deck shows the pile's "Empty" frame, no pill.
2. **The opponent's deck** (top right, 2-player) shows its count the same way.
3. **Deck vs discard**: the deck's stacked edge layers end in the gap — the Discard frame is fully visible, never
   covered — at every board size, most visibly with a 70+ card Twin Suns deck.
4. **Hand**: the hand strip still ends before the piles (it scrolls inside its own panel).
5. **Clicking the deck** still opens its zone popup (the pill ignores clicks).
