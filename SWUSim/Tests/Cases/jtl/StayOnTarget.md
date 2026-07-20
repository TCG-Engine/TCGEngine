# VehicleAttack_BaseDraw
#// JTL_177 Stay on Target — Attack with a Vehicle; +2/+0 and granted "deals damage to a base: draw a
#// card." SOR_237 (2 power) gets +2 → 4, hits P2's base for 4 and draws a card.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_177
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:1
P1DECKCOUNT:0

---

# AttackUnit_NoDraw
#// JTL_177 Stay on Target — the granted draw triggers only on damage to a BASE. Attacking an enemy UNIT
#// (SOR_237 +2 = 4 power kills SOR_044) deals no base damage, so no card is drawn.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_177
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_128
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:1
P2BASEDMG:0

---

# NonVehicle_NoValidAttacker
#// JTL_177 Stay on Target — the attacker must be a Vehicle. With only a non-Vehicle unit (SOR_046) the
#// event has no legal attacker and fizzles to the discard; nothing is drawn.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_177
WithP1Resources: 5
WithP1Deck: SOR_128
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_177
