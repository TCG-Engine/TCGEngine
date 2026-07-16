# 140_WhenPlayed_Deal1ToThree
#// JTL_140 IG-2000 — Overwhelm + When Played: Deal 1 damage to each of up to 3 units. P1 picks all three
#// enemy units (SOR_095, SEC_080, SOR_237), each taking 1. Also confirms Overwhelm is auto-wired.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_140
WithP1Resources: 4
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:CARDID:JTL_140
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm

---

# 140_WhenPlayed_PartialOneTarget
#// JTL_140 IG-2000 — "up to 3" allows fewer: choosing only one unit deals 1 to it and leaves the others
#// untouched.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_140
WithP1Resources: 4
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
