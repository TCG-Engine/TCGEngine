# LOF_022 Mystic Monastery — "Action: The Force is with you (create your Force token). Use this ability
# no more than 3 times each game." Unlike the other common Force bases this is a repeatable base Action
# (NOT an Epic Action, NOT a When-attacks trigger). One use creates P1's Force token and consumes one of
# the three per-game uses.

## GIVEN
CommonSetup: gbk/bbk/{
  myBase:LOF_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HASFORCE
P1BASEACTIONUSES:2
P1BASE:EPICAVAILABLE
