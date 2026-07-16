# FrontExpOppCredit
#// LAW_006 Vel Sartha (leader front) — "Action [Exhaust]: Give an Experience token to a unit. An
#// opponent creates a Credit token." SEC_080 (the only unit) auto-gets the Experience token (→ 4/4) and
#// P2 (the opponent) creates 1 Credit.

## GIVEN
CommonSetup: ybw/grw/{
  myLeader:LAW_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P2CREDITCOUNT:1
