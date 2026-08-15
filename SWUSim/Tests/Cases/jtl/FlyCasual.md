# ReadyVehicle_CantAttackBases
#// JTL_206 Fly Casual (event) — Ready a Vehicle unit; it can't attack bases for this phase. SOR_237 is
#// readied, and its subsequent attack on the base is a no-op (stays ready).

## GIVEN
CommonSetup: gyw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_206
WithP1Resources: 1
WithP1SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:READY
P2BASEDMG:0

---

# SimulateRequestBoundary_CantAttackBasesSurvivesRoundTrip
#// JTL_206 Fly Casual — the "can't attack bases for this phase" restriction is written when the event
#// resolves and read by a LATER action. In production every action ends the request, so the attack runs in
#// a fresh process. Mirrors ReadyVehicle_CantAttackBases with the boundary between the play and the attack.

## GIVEN
CommonSetup: gyw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_206
WithP1Resources: 1
WithP1SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:READY
P2BASEDMG:0
