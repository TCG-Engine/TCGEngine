# ASH_196 — control: WITHOUT a friendly ASH_196 in play, the same Underworld attacker (SOR_247) is NOT
# unpreventable, so the Shield absorbs the hit normally — SOR_095 takes 0 and the Shield token is consumed.
# Proves the bypass requires ASH_196, not just an Underworld source.
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_247:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
