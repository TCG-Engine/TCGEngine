# 8Indirect
#// JTL_181 Planetary Bombardment — Deal 8 indirect to a player (12 if you control a Capital Ship). Without
#// one, P1 deals 8 indirect to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_181
WithP1Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:8

---

# CapShip12Indirect
#// JTL_181 Planetary Bombardment — Deal 8 indirect to a player (12 if you control a Capital Ship). Without
#// one, P1 deals 8 indirect to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_181
WithP1Resources: 12
WithP1SpaceArena: JTL_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:12

---

# DeployedLeaderCapitalShipCounts

#// "If you control a Capital Ship unit" must read the LIVE trait of each unit in play, not the printed
#// trait of its CardID. HMW_004's deployed side is The Death Star — an Imperial Vehicle CAPITAL SHIP —
#// while the leader row prints Imperial Official, so a bare-CardID HasTrait misses it and deals 8.

## GIVEN
CommonSetup: grw/grw/{
  myLeader:HMW_004;
  myLeaderDeployed:true;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_181
WithP1Resources: 20

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:12

---

# UndeployedLeaderIsNotACapitalShip

#// The scoping guard for the case above: an UNDEPLOYED HMW_004 is not a unit in play at all (and its
#// leader side prints Imperial Official), so the bonus must not apply — 8, not 12.

## GIVEN
CommonSetup: grw/grw/{
  myLeader:HMW_004;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_181
WithP1Resources: 20

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:8
