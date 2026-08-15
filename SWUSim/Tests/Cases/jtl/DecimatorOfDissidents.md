# FullCostNoIndirect
#// JTL_138 Decimator of Dissidents — without having dealt indirect damage this phase, it plays at its
#// full cost of 4 (the -1 discount applies only after indirect damage; that path mirrors SHD_182 Bravado
#// and is exercised by the Phase 21 indirect cards).

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_138
WithP1Resources: 4

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_138
P1RESAVAILABLE:0

---

# Overwhelm_ExcessToBase
#// JTL_138 Decimator of Dissidents has Overwhelm — attacking a weaker enemy unit spills the excess onto the
#// base. Decimator (3 power, seated) attacks P2's SOR_225 (2/1): 1 damage defeats it, the other 2 overwhelm
#// onto P2's base.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_138:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:2

---

# Discount_OwnIndirectThisPhase
#// JTL_138 Decimator of Dissidents — "If you dealt indirect damage this phase, this unit costs 1 less."
#// P1 first plays JTL_181 Planetary Bombardment (8 indirect to P2), THEN plays Decimator in the SAME phase.
#// Having dealt indirect this phase, Decimator is discounted from 4 to 3: with 9 resources (6 for Planetary
#// + 3 for the discounted Decimator) P1 ends at exactly 0 available.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [JTL_181 JTL_138]
WithP1Resources: 9

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_138
P1RESAVAILABLE:0
P2BASEDMG:8

---

# NoDiscount_OpponentDealtIndirect
#// JTL_138 Decimator of Dissidents — the discount checks whether YOU dealt indirect damage; an opponent's
#// indirect does not count. P1 passes, P2 plays JTL_181 Planetary Bombardment dealing 8 indirect to P1's
#// base, then P1 plays Decimator. Because P1 did NOT deal indirect, there is no discount: Decimator costs
#// the full 4 (from 4 resources, P1 ends at exactly 0 available).

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1Hand: JTL_138
WithP1Resources: 4
WithP2Hand: JTL_181
WithP2Resources: 10

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:Opponent
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_138
P1RESAVAILABLE:0
P1BASEDMG:8

---

# SimulateRequestBoundary_DealtIndirectThisPhaseDiscount
#// JTL_138 Decimator of Dissidents — the discount reads "if you dealt indirect damage this phase", and the
#// indirect source (JTL_181) is played as a separate production action from Decimator, with its own target
#// decision in between. So the dealt-indirect-this-phase flag crosses TWO fresh-process boundaries before
#// Determine Cost reads it; if it were a transient global Decimator would silently cost the full 4 in real
#// games. Mirrors Discount_OwnIndirectThisPhase with a boundary before the answer and before the play.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [JTL_181 JTL_138]
WithP1Resources: 9

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Opponent
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_138
P1RESAVAILABLE:0
P2BASEDMG:8
