# SOR_011 Grand Inquisitor — Leader Action targets "a friendly unit with 3 or less power".
# The only friendly is a 4-power unit (ineligible), so the action fizzles: the leader still pays
# its [Exhaust] cost but no unit is damaged and no decision is queued.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION
