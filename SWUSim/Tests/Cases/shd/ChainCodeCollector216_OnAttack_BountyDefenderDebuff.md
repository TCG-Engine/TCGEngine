# SHD_216 Chain Code Collector (4-cost 4/2 ground) — Ambush + "On Attack: If the defender has a Bounty, it
# gets -4/-0 for this attack." Attacking the Bounty unit SHD_095 (2/3), the defender's counter-power drops
# to 0, so the Collector takes no damage (and its 4 power defeats SHD_095).

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_216:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SHD_216
P1GROUNDARENAUNIT:0:DAMAGE:0
