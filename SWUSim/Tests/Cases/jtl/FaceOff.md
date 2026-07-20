# NoInitiative_ReadyEnemyAndFriendly
#// JTL_178 Face Off (event) — If no player has taken the initiative this phase, you may ready an enemy
#// unit; if you do, ready a friendly unit in the same arena. With no initiative taken, P1 readies the
#// exhausted enemy SOR_237, then readies the friendly SOR_225 (same arena).

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_178
WithP1Resources: 3
WithP1SpaceArena: SOR_225:0:0
WithP2SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:READY
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:READY

---

# DoesNotReadyFriendlyInDifferentArena
#// JTL_178 Face Off — after readying an enemy unit, it readies a friendly unit "in the same arena". P1
#// readies an enemy GROUND unit, but its only friendly unit is in SPACE, so no friendly unit readies.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_178
WithP1Resources: 3
WithP1SpaceArena: SOR_225:0:0
WithP2GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1SPACEARENAUNIT:0:EXHAUSTED

---

# InitiativeTaken_DoesNothing
#// JTL_178 Face Off — the ready only happens "if no player has taken the initiative this phase". With
#// initiative already claimed (P1OnlyActions), Face Off does nothing: both units stay exhausted.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_178
WithP1Resources: 3
WithP1SpaceArena: SOR_225:0:0
WithP2SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:EXHAUSTED
