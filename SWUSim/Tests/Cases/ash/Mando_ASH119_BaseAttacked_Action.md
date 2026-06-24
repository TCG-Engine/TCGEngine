# ASH_119 Greef Karga (Ground, 2/3) — Action [1 resource, Exhaust]: if your base was attacked this phase,
# create a Mandalorian token. P2's Dark Trooper attacks P1's base (sets the flag); P1 then uses Greef's
# action → a Mandalorian token is created.
## GIVEN
CommonSetup: ggw/rrk/{myResources:2}
WithP1GroundArena: ASH_119:1:0
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 2
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
