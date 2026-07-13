# Twin Suns Phase 2: two leaders, using LEADER 1's action exhausts ONLY leader 1 (index-threaded).
# Both are IBH_053 (Darth Vader, "Action [1 resource, Exhaust]: deal 1 to a base") — same CardID proves
# the exhaust targets the clicked INSTANCE (index 1), not "first live".

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:IBH_053;
  myLeader2:IBH_053
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility:1
- P1>AnswerDecision:theirBase-0

## EXPECT
P1LEADERCOUNT:2
P1LEADER0:READY
P1LEADER1:EXHAUSTED
P2BASEDMG:1
