# ASH_196 — control: a NON-Underworld attacker (SOR_046 Rebel/Trooper) is preventable even while ASH_196
# is in play, so the Shield absorbs the hit — SOR_095 takes 0 and the Shield is consumed. Proves the
# source must be an Underworld card.
## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: ASH_196:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
