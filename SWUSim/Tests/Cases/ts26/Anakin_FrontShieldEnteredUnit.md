# TS26_002 Anakin Skywalker (leader front) — Action [Exhaust]: if 2+ friendly units entered play this
# phase, give a Shield token to 1 of them. After playing 2 units this phase, shield SEC_080.
## GIVEN
CommonSetup: bbw/rrk/{myLeader:TS26_002;myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SEC_080 SOR_095]
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED
