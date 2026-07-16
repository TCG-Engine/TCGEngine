# FrontExhaustAfterToken
#// LAW_016 The Client (leader front) — "Action [Exhaust]: If you created a token this phase, exhaust an
#// enemy unit." Lady Proxima's action first creates a Credit (a token created this phase); then The
#// Client's action exhausts the enemy SEC_080.

## GIVEN
CommonSetup: yyk/grw/{
  myLeader:LAW_016;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_235:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1CREDITCOUNT:1
