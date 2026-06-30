# Advantage shed "defeat 1" path + re-queue. Krell (2/9, Grit) has 3 Advantage tokens → power 5; attacks
# the base for 5. At attack end, player resolves the Advantage shed first (EffectStack-1) and chooses
# "defeat 1" (power 5→4, 2 tokens remain) — the shed re-enters the bag. Player then resolves Krell
# (EffectStack-0) at power 4 and defeats the 3-HP Snowtrooper (3 < 4). The re-queued shed finally
# auto-resolves the last 2 tokens. (Had all 3 shed first, Krell's power would be 2 and the Snowtrooper
# would survive — so its defeat proves exactly one token was shed and the re-queue ordered correctly.)

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: LOF_038:1:0          # Pong Krell (2/9, Grit)
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: IBH_063:2:0          # Snowtrooper (1/3)

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:Defeat_1_Advantage_token
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:5
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:POWER:2
