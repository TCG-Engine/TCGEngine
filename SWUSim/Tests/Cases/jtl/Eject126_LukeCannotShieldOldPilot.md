# UID-preservation edge (CANNOT shield): JTL_046 Paige is a pilot upgrade that has been in play since an
# earlier round (set up in GIVEN, so NO "played this phase" marker). P2 Ejects her → she becomes a P1
# ground unit, but with no SWU_PLAYED_UNIT marker. P1's Luke (SOR_005) finds no "unit you played this
# phase" to target, so no Shield is given.

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Resources: 4
WithP2Resources: 6
WithP2Hand: JTL_126
WithP2Deck: SOR_237
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_046

## WHEN
- P2>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:JTL_046
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
