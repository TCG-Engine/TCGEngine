# AnotherSpace_ExhaustUnit
#// JTL_217 Death Space Skirmisher — When Played: If you control another space unit, you may exhaust a
#// unit. With another space unit (SOR_237) in play, P1 exhausts the enemy SOR_095.

## GIVEN
CommonSetup: gyw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_217
WithP1Resources: 3
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# Offer_ExhaustPoolIsEveryUnitNotJustSpaceOrEnemy
#// JTL_217 Death Space Skirmisher — "When Played: If you control another space unit, you may exhaust a
#// unit." The space-unit clause is only the GATE; the exhaust target is any unit at all. The pool must
#// therefore span BOTH arenas and BOTH controllers — the enabling friendly SOR_237 (mySpaceArena-0),
#// the Skirmisher itself (mySpaceArena-1), the enemy ground SOR_095 and the enemy space SOR_225 (seeded
#// already exhausted, still a legal — if wasteful — choice, same as Cat and Mouse's exhausted enemy).
#// A pool narrowed to the space arena, or to enemy units, would be wrong. The decision is left PENDING
#// so the offer itself is asserted.

## GIVEN
CommonSetup: gyw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_217
WithP1Resources: 3
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&mySpaceArena-1&theirGroundArena-0&theirSpaceArena-0
