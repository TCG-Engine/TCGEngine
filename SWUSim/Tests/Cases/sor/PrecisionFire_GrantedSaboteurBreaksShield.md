# SOR_168 Precision Fire — the chosen attacker GAINS Saboteur for this attack (it isn't innately a
# Saboteur). SOR_095 (Trooper, 3/3, no innate Saboteur) gets +2/+0 → 5 power and the granted Saboteur
# breaks the defender's Shield before combat, so the shielded LAW_124 (4/7) takes the full 5 (DAMAGE:5,
# shield gone). Without the granted Saboteur the Shield would absorb the hit (DAMAGE:0). The attacker
# dies to the 4-power counter.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP1Hand: SOR_168

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENACOUNT:0
