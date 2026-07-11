# SHD_059 Embo (3-cost 3/4 ground, Vigilance, Underworld/Bounty Hunter) — "When this unit completes an
# attack: If the defender was defeated, heal up to 2 damage from a unit." Embo (3 power) attacks and
# defeats SOR_128 (3/1), taking 3 counter (survives at 4 HP). Defender defeated → onAttackEnd heals the
# damaged friendly SOR_046 by 2 (2 damage → 0). Both Embo and SOR_046 are damaged, so the pick is explicit.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_059:1:0
WithP1GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SHD_059
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0
