# Advantage shed is an ORDERED When-Attack-Ends trigger. LOF_038 Pong Krell (2/9, Grit) has 2 Advantage
# tokens → power 4. Krell attacks P2's base (4 damage). At attack end there are two triggers: Krell's
# "defeat a unit with less HP than this unit's power" and the Advantage shed. Resolving Krell FIRST
# (EffectStack-0) keeps the tokens on, so his power is still 4 and he can defeat the 3-HP Snowtrooper.
# The Advantage shed then auto-resolves (nothing else pending) → tokens gone, power back to 2.

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: LOF_038:1:0          # Pong Krell (2/9, Grit)
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: IBH_063:2:0          # Snowtrooper (1/3)

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:POWER:2
