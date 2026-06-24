# ASH_004 Grand Admiral Thrawn — Leader Action [Exhaust]: attack with a unit; it gains Restore 2 for this
# attack if you control the same number of units as the defending player. P1 (1 unit) and P2 (1 unit) are
# equal, so SOR_095's attack heals 2 from P1's base (5 → 3 damage) as it attacks SOR_046.
## GIVEN
P1LeaderBase: ASH_004/SOR_024:5
P2LeaderBase: SOR_010/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:3
P1LEADER:EXHAUSTED
