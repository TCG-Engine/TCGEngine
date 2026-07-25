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

---

# NoTokenOnMove
#// JTL_084 Wingman Victor Two — the "Create a TIE Fighter token" fires only when he is PLAYED as an upgrade,
#// not when the pilot is later MOVED to a different vehicle. Played as a pilot onto SOR_225 he makes 1 TIE
#// token (space = host + TIE = 2). Corvus (JTL_038) then enters and its When Played relocates the Victor Two
#// pilot upgrade onto itself. No second TIE is created — space stays at 3 units (host, the one TIE, Corvus),
#// with Victor Two now on Corvus and SOR_225 pilot-less.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 20
WithP1Hand: [JTL_084 JTL_038]
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P1SPACEARENAUNIT:2:CARDID:JTL_038
P1SPACEARENAUNIT:2:UPGRADECOUNT:1

---

# ReRegisterOnReEntry
#// JTL_084 Wingman Victor Two — the "Create a TIE Fighter token" trigger correctly un-registers on leaving
#// the arena and re-registers (firing once, not zero or twice) on re-entry. Played as a pilot onto SOR_225
#// he makes 1 TIE. Bamboozle (SOR_199) returns the Victor Two pilot upgrade to hand (host SOR_225 stays,
#// pilot-less; the TIE remains). Replaying him as a pilot makes exactly one MORE TIE — 2 total. Space ends
#// at 3 units: SOR_225 (re-piloted) + two TIE tokens.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 20
WithP1Hand: [JTL_084 SOR_199]
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P1SPACEARENAUNIT:2:CARDID:JTL_T01
P1HANDCOUNT:0
