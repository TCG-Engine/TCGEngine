# ASH_004 Grand Admiral Thrawn — the Restore 2 is gated on equal unit counts. P1 controls 1 unit but P2
# controls 2, so no Restore is granted and P1's base stays at 5 damage when SOR_095 attacks.
## GIVEN
P1LeaderBase: ASH_004/SOR_024:5
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:5
P1LEADER:EXHAUSTED
