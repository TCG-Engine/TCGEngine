# UID-preservation edge (CAN shield): JTL_046 Paige (Heroism Pilot) is PLAYED as a pilot upgrade THIS
# phase, so she counts as "a unit you played this phase". P2 Ejects her → she becomes a P1 ground unit
# with her UniqueID (and SWU_PLAYED_UNIT marker) preserved. P1's Luke (SOR_005) can then shield her.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 8
WithP2Resources: 6
WithP1Hand: JTL_046
WithP2Hand: JTL_126
WithP2Deck: SOR_237
WithP1GroundArena: SEC_214:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P2>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:JTL_046
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
