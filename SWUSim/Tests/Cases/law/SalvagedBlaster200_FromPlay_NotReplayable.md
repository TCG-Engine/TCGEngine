# LAW_200 — the replay only applies if discarded "from your hand or deck this phase." When the Blaster
# goes to the discard FROM PLAY (its host is defeated), it is NOT stamped TPP, so it can't be played from
# discard — even with a valid non-Vehicle host available. SEC_080 (5/3 with the Blaster) attacks SOR_046
# (3/7) and dies to the counter; the Blaster lands in P1's discard (From PLAY). P1's attempt to replay it
# onto SOR_095 is a no-op: the Blaster stays in the discard and SOR_095 gets no upgrade. (P1's discard
# holds the defeated SEC_080 AND the Blaster = 2 cards; if the Blaster had been replayable it would be 1.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_200
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayFromDiscard:0

## EXPECT
P1DISCARDCOUNT:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
