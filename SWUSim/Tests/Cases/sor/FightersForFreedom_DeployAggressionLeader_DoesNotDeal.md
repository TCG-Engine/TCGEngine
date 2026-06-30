# SOR_143 Fighters for Freedom — "When you play another [Aggression] card: you may deal 1 to a base."
# FFF#1 in play; P1 deploys Sabine (Red Hero leader); not "played" so no trigger

## GIVEN
CommonSetup: rrw/rrk/{myResources:4}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:0
P1NODECISION
