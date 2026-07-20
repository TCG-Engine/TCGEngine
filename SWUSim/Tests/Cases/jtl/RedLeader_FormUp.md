# PilotAttach_CreateXWing
#// JTL_101 Red Leader — When a Pilot upgrade attaches to this unit: Create an X-Wing token. Playing the
#// pilot JTL_034 onto Red Leader creates an X-Wing (2 space units).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9
WithP1Hand: JTL_034
WithP1SpaceArena: JTL_101:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:2

---

# CostReductionPerPilot
#// JTL_101 Red Leader — "costs 1 resource less for each friendly Pilot unit and upgrade." With two friendly
#// Pilot units in play (JTL_034, JTL_035), Red Leader (printed cost 4) costs 4 − 2 = 2 — with exactly 2
#// resources it plays and leaves 0.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_101
WithP1Resources: 2
WithP1GroundArena: JTL_034:1:0
WithP1GroundArena: JTL_035:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_101
P1RESAVAILABLE:0
