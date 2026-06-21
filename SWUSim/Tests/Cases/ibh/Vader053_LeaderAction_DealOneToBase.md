# IBH_053 Darth Vader — Leader Action [1 resource, Exhaust]: deal 1 damage to a base. P1 chooses the
#   enemy base for 1 damage; Vader exhausts and 1 resource is spent.

## GIVEN
P1LeaderBase: IBH_053/SOR_026
P2LeaderBase: SOR_005/SOR_020
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
