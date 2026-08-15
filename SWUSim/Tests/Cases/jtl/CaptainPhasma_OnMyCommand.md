# WhenPlayed_BuffFirstOrder
#// JTL_088 Captain Phasma (unit) — When Played: You may give another First Order unit +2/+2 this phase.
#// JTL_081 (a First Order unit, 2/1) becomes 4/3.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_088
WithP1Resources: 5
WithP1SpaceArena: JTL_081:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_081
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:3

---

# OnAttack_BuffFirstOrder
#// JTL_088 Captain Phasma (unit) — On Attack: You may give another First Order unit +2/+2 for this phase.
#// This ports the ON ATTACK trigger (the existing section covers the When Played instance). Phasma (5/6)
#// seated ready attacks the base for 5; her On Attack trigger buffs the friendly First Order TIE Fighter
#// (JTL_081, 2/1) → 4/3.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_088:1:0
WithP1SpaceArena: JTL_081:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_081
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:3
P2BASEDMG:5

---

# WhenPlayed_CanBuffEnemyFirstOrder
#// JTL_088 Captain Phasma — "another First Order unit" has NO "friendly" qualifier, so an ENEMY First Order
#// unit is a legal target and may be buffed +2/+2. P1 plays Phasma
#// with only an enemy First Order Stormtrooper (JTL_132, 2/1) in play and chooses it → it becomes 4/3.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_088
WithP1Resources: 5
WithP2GroundArena: JTL_132:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:JTL_132
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:3

---

# SimulateRequestBoundary_WhenPlayedBuffTarget
#// JTL_088 Captain Phasma (unit) — the optional When Played "+2/+2 this phase" target choice ends the
#// request in production, so the pick arrives in a fresh process. The pending offer, its First Order /
#// "another" filter and the phase-duration buff it writes all have to survive serialization. Mirrors
#// WhenPlayed_BuffFirstOrder with the boundary inserted before the answer: JTL_081 still ends 4/3.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_088
WithP1Resources: 5
WithP1SpaceArena: JTL_081:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_081
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:3

---

# Offer_AnotherFirstOrderEitherSide
#// JTL_088 Captain Phasma — "You may give ANOTHER FIRST ORDER unit +2/+2." Two printed restrictions gate
#// the pool: the FIRST ORDER trait (a non-First-Order friendly unit is excluded) and "another" (Phasma
#// herself is excluded). There is NO "friendly" qualifier, so an ENEMY First Order unit IS in the pool.
#// Board: friendly First Order TIE (JTL_081, space), friendly NON-First-Order SOR_095 (ground), enemy
#// First Order Stormtrooper (JTL_132, ground), plus Phasma herself landing in P1's ground arena.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_010;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_088
WithP1Resources: 5
WithP1SpaceArena: JTL_081:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: JTL_132:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_088
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-0
