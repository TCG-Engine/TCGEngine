# VISUAL CHECK — a hand card you have SELECTED stays lifted, not just outlined
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test Schema
# Editor as SEAT 1. The board opens on the initial resource step, which is the reported moment.
#
# WHAT CHANGED, AND WHAT WRONG LOOKS LIKE (2026-08-18, live report: "keep hand card popped up during
# initial resource")
# CR 5.2.1.f "resource two cards" is ONE multi-select over the hand — you mark two cards and confirm —
# and the hand routes through the INLINE selection path (it is a Visibility=Self zone belonging to the
# viewer, so it is never a modal). The inline path marks a chosen card with `.selected-inline`, which was
# purely a gold border. The only vertical motion a hand card had was `#myHandWrapper .selectable-card:hover`,
# so the moment the pointer moved to the second card the first one dropped flush back into the row.
# Against six same-sized cards that reads as "did my click register?".
# A selected card now holds the hover pose (translateY(-12px)) until it is deselected.
#
# WHAT TO LOOK AT, IN ORDER
#   1. Before touching anything: all 6 hand cards sit flush along the bottom band, none lifted.
#   2. Hover one. It rises ~12px, in place, with no zoom — that is the pre-existing hover pose and the
#      pose a selected card must match exactly.
#   3. Click it to select it for resourcing. It gets the gold border AND stays risen.
#   4. MOVE THE POINTER AWAY, onto the board. This is the whole check: the selected card must STAY UP.
#      Wrong looks like it dropping back level with its neighbours the instant the pointer leaves.
#   5. Select the second card. Now TWO cards stand proud of the row and the counter reads 0 of 2 left.
#      You should be able to see your pair at a glance without re-reading the borders.
#   6. Deselect one (click it again). It drops back flush immediately.
#   7. Confirm. Both selected cards leave the hand as resources; nothing stays stuck in the lifted pose.
#
# THE NEGATIVE THAT MATTERS
#   • Nothing may be CLIPPED. #myHandWrapper reserves --swu-hand-lift (22px) of headroom against a 12px
#     rise, and `overflow-y` there is hidden while `overflow-x` is auto — so if the lift ever exceeds the
#     padding the card's top is sliced off rather than overflowing. Look at the top edge of a lifted card:
#     full art, no cut. Measured 2026-08-18 in Chromium, Firefox and WebKit: card top sits 10px inside the
#     wrapper, no clipping, no vertical scrollbar appears.
#   • The OPPONENT's hand must not move at all. #theirHandWrapper suppresses the lift on purpose (it is
#     top-anchored with no headroom), and their cards are backs you cannot select.
#   • The same lift applies to EVERY hand multi-select, not only the opening one — check one mid-game
#     ("discard 2 cards") and confirm it behaves identically rather than only the resource step.
#
# CROSS-BROWSER: Chromium, Firefox AND Safari per CLAUDE.md. This is a transform inside a clipping
# scroller, which is exactly where engines diverge, so all three matter. Also check ?swuLayout=mobile —
# the mobile layout has NO hand hover rule and therefore no lift, by design (touch has no hover and the
# mobile hand has no reserved headroom); the gold border is the whole feedback there, which is a
# deliberate gap rather than a regression.
#
# BOARD SHAPE (why each element is here)
#   P1 hand — 6 cards, the real opening hand size, and visibly DIFFERENT cards so "which two did I pick"
#             is a question the art alone cannot answer; the lift is what answers it.
#   No resources on either side — that is what leaves the initial resource step outstanding, which is the
#   exact prompt the report is about.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
WithP1Hand: [SOR_095 SOR_128 SOR_063 SOR_046 SOR_236 SOR_238]

## WHEN

## EXPECT
# Not run by the regression runner — kept so the fixture can be validated by hand with
# `run-schema-tests.php SWUSim/Tests/Visual/HandMultiSelect_SelectedCardStaysLifted.md`.
P1HANDCOUNT:6
P1RESCOUNT:0
