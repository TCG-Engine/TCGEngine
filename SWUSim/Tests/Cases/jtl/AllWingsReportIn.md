# Exhaust2_Create2
#// JTL_122 All Wings Report In (event) — Exhaust up to 2 friendly space units; for each, create an X-Wing
#// token. P1 exhausts both space units and gets 2 X-Wings.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_122
WithP1Resources: 1
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0&mySpaceArena-1

## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:1:EXHAUSTED
P1SPACEARENACOUNT:4
P1SPACEARENAUNIT:2:CARDID:JTL_T02
P1SPACEARENAUNIT:3:CARDID:JTL_T02

---

# NoReadyUnits_NoTokens
#// JTL_122 All Wings Report In — "Exhaust up to 2 friendly space units. For each unit exhausted this way,
#// create an X-Wing token." Only READY space units can be exhausted. With both friendly space units already
#// exhausted, there is nothing to exhaust → no X-Wing tokens are created; the arena keeps just the 2 units.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_122
WithP1Resources: 1
WithP1SpaceArena: SOR_237:0:0
WithP1SpaceArena: SOR_225:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:1:EXHAUSTED
P1NODECISION

---

# OneReadyUnit_OneToken
#// JTL_122 All Wings Report In — with only ONE ready friendly space unit, exhausting it makes exactly one
#// X-Wing token. SOR_237 is ready (index 0), SOR_225 already exhausted (index 1); P1 exhausts SOR_237 → 1
#// X-Wing token (JTL_T02), leaving the arena with the 2 originals + 1 token.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_122
WithP1Resources: 1
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArena: SOR_225:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:2:CARDID:JTL_T02
