# SOR_061 Guardian of the Whills — the discount applies only to upgrades that actually land ON the
# Guardian. With a Guardian (idx 0) and a non-Guardian unit (SOR_095, idx 1) both in play, P1 plays
# SOR_069 (cost 1) onto SOR_095. The affordability gate showed -1 (a Guardian is in play), but ATTACH
# reconciles: the upgrade went elsewhere, so the 1 is clawed back → net full cost (3 → 2). The
# Guardian's charge stays UNUSED. (If the reconcile leaked the discount, RESAVAILABLE would be 3.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:2
