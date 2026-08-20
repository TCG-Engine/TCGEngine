# VISUAL CHECK — "You may look at the top card of your deck at any time"
#                (LAW_094 Hondo Ohnaka / HMW_205 Intelligence Agency)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, or build it headlessly:
#   curl -s -X POST .../SWUSim/TestSchemaSetup.php --data-urlencode "schema@<this file>"
#   open  http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=N&playerID=1
#   ⚠ folderPath=SWUSim is REQUIRED — without it NextTurn.php prints "Invalid folder path."
#     and returns a 200 with a ~2KB stub, which looks like a blank board rather than an error.
#
# WHY THIS EXISTS
# This is the engine's FIRST continuous hidden-information permission. Everything about it lives in
# the transport and the client, so the in-process regression is structurally blind to it:
#   • the permission itself is a derived predicate, _SWUCanSeeOwnTopCard() (GameLogic.php) — that half
#     IS covered, by Tests/Cases/core/LookAtTopCardPermission.md via the new P#SEESTOPCARD assertion;
#   • the other half — the top card reaching the ENTITLED SEAT ONLY — cannot be seen by the schema
#     runner, which renders no transport. A LOGCONTAINS assertion would read the UNFILTERED log and
#     pass while leaking. Only a per-viewer GetNextTurn payload can prove it.
#
# ⚠ The deck's rendered CardID stays "CardBack"; the top card travels in cardJSON.TopCardPeek. That is
# deliberate — the Deck renders in Mode=Single, so emitting the real CardID would make every existing
# render path show it face up. If you ever see the deck rendering as its top card, that inversion is
# the regression.
#
# WHAT TO LOOK AT (P1 controls Hondo; P1's deck top is SOR_231 TIE Advanced)
#   • P1's own deck wears a warm AMBER RING plus a small eye badge in its top-right corner. The ring is
#     standing, not hover-only — the permission is continuous, and amber (not the green selectable
#     highlight) because "you may look" is not "this is selectable".
#   • HOVER the badge, or anywhere on the deck: the full card opens (readable rules text), not the back.
#     Move away and it closes. The badge also takes a CLICK, so the affordance survives touch.
#   • The deck still LOOKS like a deck — card back art, unchanged count bubble.
#   • Open the SAME game as playerID=2: NO ring, NO badge, and SOR_231 appears nowhere in the payload.
#     Same for a spectator (playerID=3). This is the leak check and it is the point of the test.
#   • Swap Hondo out (or move HMW_205 to the other base) and the ring must disappear.
#
# EMPTY DECK (same file, second state — load the variant GIVEN at the bottom)
#   • A deck with ZERO cards must render the EMPTY PILE CONTAINER — the bordered rounded rect with the
#     small "DECK" label, identical to how the DISCARD pile looks before anything is discarded — and
#     NOT a card back. A face-down back is a lie about a pile that holds nothing, and deck-out is a
#     reachable state (CR 6.1).
#   • This is true for BOTH the owner's own deck and the opponent's view of it.
#   • The Deck is the only Private zone in the schema, and the Private branch emitted a CardBack
#     unconditionally, ignoring the count — so the deck could never reach the zero state. The layout
#     had ALREADY been written for it: GameLayout.php:631 hides the empty-zone placeholder text for
#     #myDeck/#theirDeck exactly like #myDiscard, and `.swu-pile:has(img)` restores the frame when the
#     pile empties. The generator now suppresses the emission when count == 0.
#   • ⚠ The counter is left alone: the emit gate is `count(zone) > 0`, i.e. exactly "the counter reads
#     0", so what is shown can never disagree with what is counted.
#   • The empty frame reads "EMPTY", not "Deck" — the .swu-pile-label is only ever visible when the pile
#     holds nothing (an occupied pile's art covers it), so naming the state beats naming the pile.
#     ⚠ The DISCARD pile still says "Discard" in its empty state — left as-is deliberately, not missed.
#
# PILE ALIGNMENT — the empty deck is what made this visible
#   • The empty frame and the neighbouring discard card must be the SAME SIZE and share a top edge.
#   • Two stale sizing rules made the frame smaller than the art it holds, both invisible while every
#     pile held a card (`.swu-pile:has(img)` blanks the frame when one is present):
#       – `--swu-pile-w: min(88px, calc(var(--swu-cardsize,80px) * 1.1))` — the 88px cap froze the frame
#         at 90px outer while --swu-cardsize is 100px and the art renders 102px outer. Now
#         `var(--swu-cardsize)`, so frame outer == art outer at EVERY card size.
#       – the slot's `min-height: min(112px, calc(var(--swu-cardsize,80px) * 1.4))` — a PORTRAIT 1.4
#         ratio on a pile whose art is square, making the slot 112 tall inside a 100-tall pile and
#         flex-centring the discard's card 3px above its own frame. Now `var(--swu-pile-h)`.
#   • Measured after the fix: discard art coincides with its frame EXACTLY (0px, 102x102 both), and the
#     empty deck frame shares its top edge. The deck's own art lands within 1px of the discard's (it
#     goes through the absolutely-pinned Stacked wrapper rather than flex centring) — that residual is
#     known and left alone.
#   • ⚠ --swu-pile-w feeds --swu-pile-zone-w -> --swu-hand-w and two right-edge offsets, so the pile
#     zone grew 24px and the hand band narrowed. Verified: no overlap (hand ends 1090, piles start
#     1100) and no horizontal document overflow at 1600px in either engine. The mobile layout does not
#     use .swu-pile at all, so it is unaffected.
#
# ⚠ The wrapper span is display:inline by default and measures 0x0, which made the ring invisible and
# threw the badge off the card's left edge. createCardHTML now forces inline-block for the peek case
# only. If the badge ever drifts off the card again, re-measure that box first.
#
# VERIFIED 2026-08-19: Chromium 1600x1250 and Firefox 1600x1250, both seats — ring + badge present for
# seat 1 only, hover popup resolves to AppCore/SWU/Images/WebpImages/SOR_231.webp at natural 450x628 in
# both engines, and seat 2's payload is byte-identical to a no-permission game ("CardBack 2 -").
# Empty deck verified in both engines and from both seats: zone piece empty on the wire, no <img> in the
# slot, and .swu-pile back to border rgba(255,255,255,0.1) / bg rgba(255,255,255,0.04) — the same frame
# the untouched discard pile shows.
# WebKit was NOT covered: it does not launch on this machine (hard-timed out at 150s in playwright
# 1.62.1), the same standing limitation noted for the SWUDeck cross-browser passes.
#
# The GIVEN state is the whole check — there are no WHEN steps.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0
WithP1Deck: [SOR_231 SOR_046]
WithP2Deck: [SOR_128 SOR_095]

## WHEN

## EXPECT
P1SEESTOPCARD
P2NOTSEESTOPCARD

# ── VARIANT: EMPTY DECK ────────────────────────────────────────────────────────────────────────────
# Swap the GIVEN above for this one to check the zero state. P1's deck is empty; P2's is not, so the
# opponent's stocked deck is the in-frame control for "a deck that DOES hold cards still shows a back".
#
# ## GIVEN
# CommonSetup: bbw/rrk/{}
# P1OnlyActions: true
# WithP1Deck: []
# WithP2Deck: [SOR_128 SOR_095]
#
# ## WHEN
