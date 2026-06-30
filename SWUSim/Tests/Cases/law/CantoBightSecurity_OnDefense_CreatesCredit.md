# LAW_121 Canto Bight Security (Unit, cost 5, Vigilance, 3/5) — Sentinel + On Defense: Create a Credit token.
#   P2's SEC_080 (3/3) is forced by Sentinel to attack LAW_121. The On Defense fires → P1 gets a Credit.
#   LAW_121 (3/5) survives the 3 damage; SEC_080 (3/3) dies to the 3 counter. Turn alternates normally.

## GIVEN
CommonSetup: bbk/grk/{}
WithActivePlayer: 2
WithP1GroundArena: LAW_121:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1CREDITCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_121
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
