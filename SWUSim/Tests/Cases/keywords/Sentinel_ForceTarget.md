# Sentinel forces attacker to target Sentinel unit
# P1 has Syndicate Lackeys (power 5, HP 4) — no Saboteur.
# P2 has Echo Base Defender (Sentinel, power 4, HP 4) — only valid target.
# Exactly 1 valid target → auto-fires at Sentinel.
# Both die simultaneously (power ≥ HP on both sides). Base takes 0 damage.

## GIVEN
CommonSetup: yrw/yrw
WithP1GroundArena: SOR_213:1:0   # Syndicate Lackeys (power 5, HP 4)
WithP2GroundArena: SOR_098:1:0   # Echo Base Defender (Sentinel, power 4, HP 4)

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2BASEDMG:0
