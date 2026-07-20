# OppMoreSpace_ReadyAllSpace
#// JTL_209 It's a Trap (event) — If an opponent controls more space units than you, ready each space unit
#// you control. P2 has 2 space units, P1 has 1 exhausted space unit, so it readies.

## GIVEN
CommonSetup: gyw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_209
WithP1Resources: 3
WithP1SpaceArena: SOR_237:0:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:READY

---

# NotMoreSpace_PlayAnyway
#// JTL_209 It's a Trap — readies your space units only "If an opponent controls MORE space units than you".
#// P1 controls 3 space units and P2 only 1, so the condition fails: no unit readies, played anyway.

## GIVEN
CommonSetup: gyw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_209
WithP1Resources: 3
WithP1SpaceArena: SOR_237:0:0
WithP1SpaceArena: SOR_225:0:0
WithP1SpaceArena: JTL_069:0:0
WithP2SpaceArena: SOR_144:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:1:EXHAUSTED
P1SPACEARENAUNIT:2:EXHAUSTED
