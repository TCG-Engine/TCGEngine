# TS26_001 Count Dooku (leader front) — Action [Exhaust]: choose 2 players; they each heal 1 from their
# base and create a Battle Droid token. In 2-player, both bases heal 1 (3 → 2) and both players get a
# Battle Droid.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_001;myBaseDamage:3;theirBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1BASEDMG:2
P2BASEDMG:2
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED
