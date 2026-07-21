# IndirectPlusOne
#// JTL_165 Hunting Aggressor — Indirect damage you deal to opponents is increased by 1. With it in play,
#// JTL_240's 1 indirect becomes 2 to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_165:1:0
WithP1Hand: JTL_240
WithP1Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:2

---

# NonIndirectNotIncreased
#// JTL_165 Hunting Aggressor only boosts INDIRECT damage. SHD_178 Daring Raid deals 2 (non-indirect)
#// damage to P2's base — with the Aggressor in play it stays 2, NOT increased to 3.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_165:1:0
WithP1Hand: SHD_178
WithP1Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2

---

# TwoCopiesStackPlusTwo
#// Two copies of JTL_165 stack: JTL_181 Planetary Bombardment's 8 indirect becomes 8+2 = 10. With an
#// enemy unit present the damaged player (P2) distributes the 10: 4 to their SOR_164 Wampa (5 HP, survives)
#// + 6 to their base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_165:1:0
WithP1SpaceArena: JTL_165:1:1
WithP1Hand: JTL_181
WithP1Resources: 12
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:4,myBase-0:6

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:6

---

# OpponentIndirectNotIncreased
#// The Aggressor only boosts indirect YOU deal. P1 controls JTL_165, but P2 plays JTL_181 Planetary
#// Bombardment — it deals a plain 8 (not 9) to P1. P1 (the damaged player) distributes: 3 to its own
#// JTL_165 + 5 to its base. Total exactly 8 proves P1's Aggressor did NOT boost the opponent's indirect.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1SpaceArena: JTL_165:1:0
WithP2Hand: JTL_181
WithP2Resources: 12

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Opponent
- P1>AnswerDecision:mySpaceArena-0:3,myBase-0:5

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:3
P1BASEDMG:5

---

# FollowsControlChange
#// The +1 follows control. P1 steals P2's JTL_165 with SOR_224 Change of Heart, then plays JTL_181
#// Planetary Bombardment: 8+1 = 9 (the stolen Aggressor now boosts P1's indirect). P2 distributes the 9:
#// 4 to their SOR_164 Wampa (5 HP, survives) + 5 to their base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_224
WithP1Hand: JTL_181
WithP1Resources: 20
WithP2SpaceArena: JTL_165:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:4,myBase-0:5

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:5

---

# SelfIndirectNotIncreased
#// The Aggressor boosts indirect dealt to OPPONENTS, not to yourself. P1 plays JTL_181 Planetary
#// Bombardment choosing "You": it deals a plain 8 (not 9) to P1. P1 distributes: 4 to its own JTL_165
#// (6 HP, survives) + 4 to its base. Total exactly 8 proves self-indirect is NOT boosted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_165:1:0
WithP1Hand: JTL_181
WithP1Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:mySpaceArena-0:4,myBase-0:4

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:4
P1BASEDMG:4

---

# EachInstanceIncreased
#// EACH separate indirect instance in one attack is boosted by +1. P1 controls JTL_165, leader JTL_009
#// Boba Fett, and JTL_237 TIE Bomber piloted by JTL_139 Dengar. TIE Bomber (power 0, +1 from Dengar = 1)
#// attacks P2's base with no enemy units (all indirect auto-lands on base). Three indirect instances each
#// get +1: (1) TIE Bomber's own On Attack 3->4 (base 4); (2) Boba's non-combat-damage reaction exhausts to
#// deal 1->2 (base 6); (3) Dengar's granted On Attack 2->3, plus the 1 TIE Bomber combat power (base 10).

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:JTL_009;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: JTL_165:1:0
WithP1SpaceArena: JTL_237:1:1
WithP1SpaceArenaUpgrade: 1:JTL_139

## WHEN
- P1>AttackSpaceArena:1:BASE
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:10
P1NODECISION
