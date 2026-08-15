# BaseDamage_TaxesOpponent
#// JTL_188 Moff Gideon — When this unit deals combat damage to an opponent's base, each unit that
#// opponent plays this phase costs 1 more. Gideon hits P2's base, then P2's JTL_069 (cost 5) costs 6, so
#// P2's 6 resources are exactly consumed.

## GIVEN
CommonSetup: byk/bbw/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirLeader:JTL_004;
  theirBase:JTL_019
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: JTL_188:1:0
WithP2Hand: JTL_069
WithP2Resources: 6

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2RESAVAILABLE:0
P2BASEDMG:5

---

# GideonTaxAppliesToSmuggledUnit
#// JTL_188 Moff Gideon — after he damages an opponent's base (SWU_GIDEON_TAX), each unit that opponent
#// plays this phase costs 1 more. This passive FIELD-modifier surcharge must also hit a unit that opponent
#// plays via SMUGGLE (regression: the Smuggle payment path bypassed the playCostFieldModifiers registry, so
#// it dodged the tax). P2 controls Gideon; P1 is taxed and smuggles SHD_111 Collections Starhopper
#// (Smuggle [3 Command]; ggw base covers Command → bracket 3). With the +1 tax the smuggle costs 4, so P1
#// with exactly 4 ready resources plays it. Paired with the one-below negative to pin cost == 4.

## GIVEN
CommonSetup: ggw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: JTL_188:1:0
WithP1GlobalEffect: SWU_GIDEON_TAX
WithP1Resources: 1:SHD_111:1,3:SOR_251:1

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111

---

# GideonTaxAppliesToSmuggledUnit_RejectedOneBelow
#// Same as GideonTaxAppliesToSmuggledUnit but P1 has only 3 ready resources. With the +1 Gideon tax the
#// smuggle costs 4 > 3 → REJECTED (space arena empty). Without the tax the bracket cost is 3 ≤ 3 and it
#// would play, so an empty arena here proves the tax raised the cost to 4.

## GIVEN
CommonSetup: ggw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: JTL_188:1:0
WithP1GlobalEffect: SWU_GIDEON_TAX
WithP1Resources: 1:SHD_111:1,2:SOR_251:1

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1SPACEARENACOUNT:0

---

# SimulateRequestBoundary_GideonTaxSurvives
#// JTL_188 Moff Gideon — the "each unit that opponent plays this phase costs 1 more" surcharge is stamped
#// by P1's ATTACK and read when P2 later PLAYS a unit. In production those are two separate requests (and
#// two different seats), so the tax must live in the serialized gamestate. Mirrors BaseDamage_TaxesOpponent
#// with a request boundary between the attack and P2's play.

## GIVEN
CommonSetup: byk/bbw/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirLeader:JTL_004;
  theirBase:JTL_019
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: JTL_188:1:0
WithP2Hand: JTL_069
WithP2Resources: 6

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P2>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2RESAVAILABLE:0
P2BASEDMG:5
