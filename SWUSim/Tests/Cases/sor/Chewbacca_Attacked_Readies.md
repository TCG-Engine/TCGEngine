# SOR_196 Chewbacca (unit) — "When this unit is attacked: Ready him." (On Defense window per CR 15.c)
# Chewbacca starts EXHAUSTED. P1 attacks him (Sentinel forces the attack onto Chewbacca). His
# On Defense readies him; combat still resolves (both survive). Proves the trigger fires AND readies
# the correct unit (the defender, not the attacker — the OnDefense mzID frame fix).

## GIVEN
CommonSetup: ggw/yyw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_196:0:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_196
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
