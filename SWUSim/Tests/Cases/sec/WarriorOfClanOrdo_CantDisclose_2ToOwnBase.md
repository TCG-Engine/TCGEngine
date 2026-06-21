# SEC_164 Warrior of Clan Ordo — no Aggression card in hand → can't disclose → deal 2 to your own
#   base automatically (no decision offered).

## GIVEN
CommonSetup: rrw/grw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_164:1:0

## WHEN
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:3
P1BASEDMG:2
P1NODECISION
