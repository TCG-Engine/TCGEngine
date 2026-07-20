# OnAttack_MillDraw
#// JTL_048 Cassian Andor (pilot) — Attached gains "On Attack: discard the top card of the defending
#// player's deck; if it costs 3 or less, draw a card." P2's top card (SOR_128, cost 1) is milled, so P1 draws.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_048
WithP1Deck: SOR_063
WithP2Deck: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P2DECKCOUNT:0

---

# MilledCostlyCard_NoDraw
#// JTL_048 Cassian Andor — the draw only follows if the milled card costs 3 or less. P2's top card is
#// SOR_046 (cost 4); it's discarded from their deck but P1 does NOT draw.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_048
WithP1Deck: SOR_063
WithP2Deck: SOR_046

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P2DECKCOUNT:0

---

# EmptyDeck_NoMill
#// JTL_048 Cassian Andor — with the defending player's deck empty there is nothing to discard, so no draw
#// happens either. The attack still deals its base damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_048
WithP1Deck: SOR_063

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P2BASEDMG:3
