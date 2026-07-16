# ShootFirst
#// SHD_234 Incinerator Trooper (2-cost 2/2 ground) — "While attacking, this unit deals combat damage before
#// the defender." Attacking SOR_128 (3/1): Incinerator (2 power) deals 2 first → SOR_128 (1 HP) is defeated
#// and deals NO counter, so Incinerator survives undamaged. Without deal-first, SOR_128's 3 counter would
#// have killed it.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_234:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SHD_234
P1GROUNDARENAUNIT:0:DAMAGE:0
