# SOR_017 Han Solo (deployed leader unit) — On Attack:
# "Put the top card of your deck into play as a resource and ready it."
# Han is deployed (free, 6 resources), then attacks P2's base. OnAttack puts the top deck
# card into play as a READY resource (mandatory — no "may"). Resources 6 → 7, deck 3 → 2,
# P2 base takes 4 (Han's power). Han is exhausted from attacking.

## GIVEN
P1LeaderBase: SOR_017/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
P1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SOR_017
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:4
P1RESCOUNT:7
P1RESAVAILABLE:7
P1DECKCOUNT:2
