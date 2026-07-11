# SHD_171 Covetous Rivals — the same "may deal 2 to a Bounty unit" also fires On Attack (OnAttack-safe
# MZMAYCHOOSE). Covetous Rivals attacks the base; the rider deals 2 to the enemy Bounty unit SHD_095.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_171:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_095
P2GROUNDARENAUNIT:0:DAMAGE:2
