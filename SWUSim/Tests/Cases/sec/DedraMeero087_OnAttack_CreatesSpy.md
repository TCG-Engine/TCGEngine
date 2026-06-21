# SEC_087 Dedra Meero (Ground, 5/5, Command/Villainy) — Ambush (auto) + On Attack: create a Spy token.
# Dedra (idx0) attacks the base (5 power); On Attack auto-creates a Spy token. Ground ends with Dedra + Spy.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_087:1:0

## WHEN
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_T01
P1NODECISION
