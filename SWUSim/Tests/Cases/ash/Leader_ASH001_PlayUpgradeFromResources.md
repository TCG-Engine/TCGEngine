# ASH_001 The Armorer — Leader Action [Exhaust]: play an upgrade from your resources on a unit that entered
# play this phase (paying its cost). P1 plays SOR_095 (so it "entered this phase"), then uses The Armorer to
# play SOR_120 (an upgrade in the resource zone) onto it, raising it to 5 power.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SOR_120:1,7:SOR_095:1
WithP1Hand: SOR_095
WithP1Deck: [SOR_063]
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1LEADER:EXHAUSTED
