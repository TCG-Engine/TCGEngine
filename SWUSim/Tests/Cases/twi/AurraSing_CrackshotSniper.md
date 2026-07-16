# ReadyOnBaseAttack
#// TWI_166 Aurra Sing (Unit 7/6, Ground, cost 7, Underworld/Bounty Hunter) — Overwhelm + "When an enemy
#// ground unit attacks your base: Ready this unit." Aurra starts exhausted; P2's SOR_095 attacks P1's base,
#// readying her.

## GIVEN
CommonSetup: bbw/rrk/{}
WithActivePlayer: 2
WithP1GroundArena: TWI_166:0:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_166
P1GROUNDARENAUNIT:0:READY
P1BASEDMG:3
