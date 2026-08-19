# VISUAL CHECK — the Credit-payment prompt shows the CREDITS, not the whole resource row
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test Schema
# Editor as SEAT 1 and play Cloud City Wing Guard (SOR_063) from hand.
#
# WHAT CHANGED, AND WHAT WRONG LOOKS LIKE (2026-08-18, live report)
# Reported as: "when asked to use Credits to pay for things, they are not always the 'last resources' in
# the resource pop-up."
# The report is accurate and the cause is two separate things stacking:
#   1. SWUOfferAltPayment offered the Credits by their `myResources-N` mzIDs. Resources is declared
#      `Visibility=Self, Mode=All`, and a Self zone belonging to the VIEWER is routed INLINE — so this was
#      never a "pop-up" at all. It highlighted the Credits in place, along the player's whole resource row.
#   2. `SWUKeepCreditTokensLast()` exists and DoResourceCard calls it, but Credits are appended when they
#      are CREATED and several effects add resources on other paths, so the tokens are genuinely not
#      always at the end. This fixture reproduces that state directly: real / CREDIT / real / CREDIT / real.
# Together: the player was asked to "pick your Credits" while looking at five near-identical cards with two
# of them lit somewhere in the middle.
#
# The fix stages the Credits into TempZone (`Display: Visibility=Self, Mode=None`) and offers those, so the
# choice routes to the MZMULTICHOOSE card modal and that modal contains the Credits and nothing else.
# No schema change and no client change — the routing falls out of the zone's declared Mode.
#
# WHAT TO LOOK AT, IN ORDER
#   1. BEFORE playing anything: P1's resource row reads real / CREDIT / real / CREDIT / real, left to right.
#      The Credits are visibly INTERLEAVED, not bunched at the end. That is the reported board state and it
#      is what makes the rest of this check meaningful — if they render bunched, the fixture didn't load.
#   2. Play SOR_063 (cost 3) from hand. The Credit-payment prompt opens.
#   3. THE WHOLE POINT: it must open as its own MODAL containing exactly TWO cards — the two Credit tokens.
#      Wrong looks like: no modal at all, and instead two cards lit up in place in the resource row with the
#      three real resources sitting between and around them. That is the pre-fix behaviour.
#   4. Both cards in the modal are selectable, and the confirm control reflects "up to 2" (the cost is 3, so
#      the cap is 2 = the number of Credits, not the cost).
#   5. Select ONE and confirm. Exactly one Credit leaves the resource row; the row closes up to
#      real / CREDIT / real. Two of the three real resources exhaust (cost 3 paid as 2), one stays ready.
#      ⚠ Watch WHICH token disappears — picking the SECOND card in the modal must remove the SECOND Credit
#      (the one at row position 4), not the first. That is the index-map bug this staging introduces, and it
#      is covered mechanically by Cases/core/CreditPaymentPickerStaging.md — this step is the eyeball twin.
#   6. After the prompt resolves, the modal must be GONE and no stray card may remain anywhere on the board.
#      TempZone has no board slot, so a leaked staged card surfaces as phantom cards in the NEXT popup —
#      play a second card that raises any card-image choice and confirm the popup shows only its own cards.
#
# THE NEGATIVE THAT MATTERS
#   Decline the prompt instead (close / cancel). Both Credits must still be in the row, all three real
#   resources exhaust, and — same as step 6 — nothing may be left staged. The refusal path is the one that
#   historically forgets to drain.
#
# ALSO WORTH ONE LOOK (same code path, different entry point): a leader/unit ACTION cost, a Smuggle cost,
# and the Millennium Falcon's regroup keep-tax all route through SWUOfferAltPayment and get the same modal.
#
# CROSS-BROWSER: Chromium, Firefox AND Safari per CLAUDE.md. The MZMULTICHOOSE modal is a flex/grid card
# strip, so this one IS layout-sensitive — check that two cards centre correctly (a 2-item grid is the case
# most likely to stretch), and check ?swuLayout=mobile, where the modal is width-constrained.
#
# BOARD SHAPE (why each element is here)
#   P1 resources — 1:SOR_095 / 1:LAW_T01 / 1:SOR_095 / 1:LAW_T01 / 1:SOR_095, all ready. The interleave is
#                  the whole point; a flat `WithP1Credits: 2` would append them at the end and hide the bug.
#                  Three REAL resources exactly covers SOR_063's cost 3, so the payment is affordable with
#                  or without the Credits and the prompt is a genuine choice.
#   P1 hand      — SOR_063 Cloud City Wing Guard, cost 3, Vigilance only, so the `bbw` leader means no
#                  off-aspect penalty inflates the cost and changes the cap.
#   P2 ground    — one unit so the board is not empty behind the modal.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0}
WithP1Resources: 1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1
WithP1Hand: SOR_063
WithP2GroundArena: SOR_046:1:0

## WHEN

## EXPECT
# Not run by the regression runner — kept so the fixture can be validated by hand with
# `run-schema-tests.php SWUSim/Tests/Visual/CreditPayment_StagedPickerPopup.md`.
# A wrong board makes the visual check meaningless.
P1HANDCOUNT:1
P1RESCOUNT:3
P1CREDITCOUNT:2
P1RESAVAILABLE:3
P1TEMPZONECOUNT:0
P2GROUNDARENACOUNT:1
