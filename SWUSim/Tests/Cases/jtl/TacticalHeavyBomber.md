# BaseDamaged_Draws
#// JTL_152 Tactical Heavy Bomber — On Attack: deal indirect = power to the defending player; if a base is
#// damaged this way, draw. P1 attacks P2's base with JTL_152 (power 3). P2 controls no units, so the 3
#// indirect auto-resolves onto P2's base → a base is damaged this way → P1 draws a card.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_152:1:0
WithP1Deck: SOR_237

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HANDCOUNT:1

---

# NoBaseDamaged_NoDraw
#// JTL_152 Tactical Heavy Bomber — the draw only happens "if a base is damaged this way" (by the indirect).
#// Attacking an enemy UNIT, the power-3 indirect goes to the defending player P2, who assigns it across
#// their units (not the base) → no base damaged this way → P1 does NOT draw.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1SpaceArena: JTL_152:1:0
WithP1Deck: SOR_237
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P2>AnswerDecision:mySpaceArena-0:1,mySpaceArena-1:1,mySpaceArena-2:1

## EXPECT
P1HANDCOUNT:0

---

# TwinSuns_IndirectGoesToTheACTUALDefendingPlayer
#// The third member of the "the defending player" indirect family (with JTL_149 and JTL_237). JTL_152
#// reads "Deal indirect damage equal to this unit's power to the DEFENDING player. If a base is damaged
#// this way, draw a card." — and resolved it with OtherPlayer($player).
#//
#// Power 3, so attacking seat 4's base is 3 combat + 3 indirect = 6, and the "if a base is damaged this
#// way" rider fires for the draw. The draw is asserted too: routing the indirect to the wrong seat also
#// evaluates the rider against the wrong base, so the two failures are separable here.
#// Seat 1 starts on an empty hand so the drawn card is unambiguous.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_001; theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Hand: -
WithP1GroundArena: JTL_152:1:0

## WHEN
- P1>AttackGroundArena:0:P4B

## EXPECT
SEATCOUNT:4
P4BASEDMG:6
P2BASEDMG:0
P3BASEDMG:0
P1HANDCOUNT:1
