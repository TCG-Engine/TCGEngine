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
