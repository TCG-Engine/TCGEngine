# IBH_042 Han Solo (reprint of IBH_010) — Raid 2 + On Attack defender -2/-0. Confirms the duplicate.

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: IBH_042:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
