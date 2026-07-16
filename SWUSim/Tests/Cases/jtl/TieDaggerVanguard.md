# WhenPlayed_Deal2ToDamaged
#// JTL_239 TIE Dagger Vanguard — When Played: You may deal 2 damage to a damaged unit. SOR_046 (3/7) is
#// already damaged (2) → takes 2 more (total 4).

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_239
WithP1Resources: 3
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# WhenPlayed_Decline
#// JTL_239 TIE Dagger Vanguard — declining the optional damage leaves the damaged unit untouched.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_239
WithP1Resources: 3
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
