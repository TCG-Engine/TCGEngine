# ASH_161 Zeb Orrelios — "When a friendly upgrade is defeated: deal 1 damage to a base." With Zeb in play,
# SOR_095 (wearing SOR_120) dies attacking SOR_046; SOR_120 is defeated, so Zeb deals 1 to P2's base.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:3
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:1
