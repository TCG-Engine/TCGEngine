# IBH_053 Darth Vader — with no ready resource, the Action can't pay: full no-op (Vader stays ready,
#   no base damage).

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:IBH_053
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:0
P1LEADER:READY
