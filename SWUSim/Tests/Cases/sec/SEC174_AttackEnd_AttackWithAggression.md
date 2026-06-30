# SEC_174 Saw Gerrera's U-Wing (Space, 4/8) — Saboteur + "When this unit completes an attack (and
#   survives): you may attack with another Aggression unit." SEC_174 attacks P2's base (4); on attack-end
#   the Aggression unit SEC_134 attacks the base too (3) → base 7 total.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_174:1:0
WithP1GroundArena: SEC_134:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:7
