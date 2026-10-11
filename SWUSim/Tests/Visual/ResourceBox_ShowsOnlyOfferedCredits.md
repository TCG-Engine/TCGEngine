# VISUAL CHECK — the resource box narrows to the resources the decision is actually offering
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression runner). Load it in the Test
# Schema Editor as SEAT 1; the leader Action has already been used, so the token prompt is open.
# Also driven automatically by DevTools/ui-harness/swusim-resource-filter-xbrowser.mjs.
#
# WHAT CHANGED, AND WHAT WRONG LOOKS LIKE
# The resource row lives behind a collapsed badge, and a decision that offers resources auto-opens it
# (refreshResourceSelectionPanel in GameLayoutShared.php). It used to render the WHOLE row, so being
# asked to defeat a Credit meant hunting for the lit card among near-identical resources — the same
# complaint that produced CreditPayment_StagedPickerPopup.md, which solved it for ONE flow by staging
# the Credits into TempZone. The box now shows only the resources in the offer, for every flow, with
# no per-card staging: the pool the server sent IS the filter, so it cannot hide a legal choice.
#
# This board: 7 resources, of which #1 and #3 are Credits (deliberately INTERLEAVED, not bunched at
# the end — that is the reported real-game state). Han Solo LAW_017's Action costs "defeat a friendly
# token"; P1's tokens are those two Credits plus an Experience on the ground unit, so the cost offers
# three things and does not auto-resolve.
#
# WHAT TO LOOK AT
#   1. The resource box is OPEN (the decision opened it) and contains EXACTLY the two Credits. The
#      five ordinary resources are not rendered at all — not dimmed, not present.
#   2. Its header reads "SELECTABLE RESOURCES", not "RESOURCES", so a short box reads as deliberate.
#   3. The Experience token on the ground unit is highlighted on the board at the same time — the
#      narrowing is display-only and does not touch the rest of the offer.
#   4. Pick a Credit: it is defeated, the box closes, and the prompt moves on to "Deal 1 damage".
#   5. NEGATIVE CONTROL — with no decision open, tap the resource badge to open the box by hand: all
#      SEVEN resources render and the header reads "RESOURCES". (Reload the fixture without running
#      the WHEN step, or finish the ability first.)
#
# CROSS-BROWSER: Chromium, Firefox AND WebKit, plus ?swuLayout=mobile — the box is a fixed-position
# panel whose mobile form is a slide-up overlay with its own CSS, and the class that hides a card is
# set by SHARED JS. A desktop-only rule would leave the phone box unfiltered (the recurring
# shared-JS-with-desktop-only-CSS trap), so the rule lives in GameLayoutShared.php.
#
# No further WHEN steps — interaction from here is manual.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028;
  myResources:0
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1,1:LAW_T01:1,1:SOR_095:1,1:SOR_095:1,1:SOR_095:1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
# The cost is pending and un-answered: three tokens are offered (Experience + two Credits), so it does
# NOT auto-resolve — which is what makes the box's contents worth looking at.
P1LEADER:EXHAUSTED
P1DECISIONTOOLTIP:Choose_a_friendly_token_to_defeat
P1CREDITCOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
