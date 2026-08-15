# AsUpgrade_GiveExp
#// JTL_086 Wingman Victor Three — When played as an upgrade: You may give an Experience token to another
#// unit. Played onto SOR_225, it gives the token to SOR_046 (3 power → 4).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_086
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4

---

# AsUnit_NoExp
#// JTL_086 Wingman Victor Three — the "give an Experience token" fires only when played AS AN UPGRADE (a
#// pilot). With no friendly Vehicle to pilot, he plays as a ground UNIT: no target decision is offered, no
#// Experience token is given to the friendly SOR_046 (stays power 3), and he himself gains none (power 4).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_086
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:CARDID:JTL_086
P1GROUNDARENAUNIT:1:POWER:4

---

# NoExpOnMove
#// JTL_086 Wingman Victor Three — the "give an Experience token" fires only when he is PLAYED as an upgrade,
#// not when the pilot is later MOVED to another vehicle. Played as a pilot onto SOR_225 he gives one
#// Experience to SOR_046 (power 3 → 4). Corvus (JTL_038) then enters and its When Played relocates the
#// Victor Three pilot upgrade onto itself; no SECOND Experience is granted (SOR_046 stays power 4) and no
#// dangling decision is left.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 20
WithP1Hand: [JTL_086 JTL_038]
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:4
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:JTL_038
P1SPACEARENAUNIT:1:UPGRADECOUNT:1


---

# Offer_AnotherUnit_ExcludesOnlyTheHost
#// JTL_086 Wingman Victor Three — "When played as an upgrade: You may give an Experience token to ANOTHER
#// unit." The only exclusion is the unit he is attached to; every other unit on the board qualifies,
#// including P1's second space Vehicle (proving the pool is not narrowed to the non-host ARENA) and the
#// ENEMY ground unit (proving it is not narrowed to friendly units). The decision is left PENDING so the
#// offer itself is asserted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_086
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-1&myGroundArena-0&theirGroundArena-0
