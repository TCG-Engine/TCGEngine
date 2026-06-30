# ASH_036 Rukh (Ground, 1/5, Support) — When Attack Ends: if the defending unit was defeated, you may
# give 3 Advantage tokens to a unit. Rukh attacks SOR_128 (3/1) and kills it (deals 1); takes 3 counter
# (survives, 5 HP), and since the defender was defeated, gives 3 Advantage tokens to itself.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_036:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:ASH_036
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
