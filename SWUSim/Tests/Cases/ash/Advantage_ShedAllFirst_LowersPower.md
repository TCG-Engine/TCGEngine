# Advantage shed offers "defeat 1 / defeat all" while another When-Attack-Ends trigger is pending. Same
# Krell setup, but the player resolves the Advantage shed FIRST (EffectStack-1) and chooses "defeat all".
# Krell's power drops to 2 before his ability resolves, so the 3-HP Snowtrooper is no longer a legal
# target (3 is not < 2) and survives. Base still took 4 (power was 4 at combat time; tokens shed after).

## GIVEN
CommonSetup: yrw/grw
WithP1GroundArena: LOF_038:1:0          # Pong Krell (2/9, Grit)
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP1GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArena: IBH_063:2:0          # Snowtrooper (1/3)

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:Defeat_all_Advantage_tokens

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:0:POWER:2
