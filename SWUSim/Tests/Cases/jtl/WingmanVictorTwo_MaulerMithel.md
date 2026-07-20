# AsUpgrade_CreateTIE
#// JTL_084 Wingman Victor Two — When played as an upgrade: Create a TIE Fighter token. Played as a pilot
#// onto SOR_225, it creates a TIE Fighter (2 space units total).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_084
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENACOUNT:2

---

# AsUnit_NoToken
#// JTL_084 Wingman Victor Two — the "Create a TIE Fighter token" fires only when played AS AN UPGRADE. With
#// no friendly Vehicle to pilot, he plays as a ground UNIT and no token is created.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_084

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_084
P1SPACEARENACOUNT:0
