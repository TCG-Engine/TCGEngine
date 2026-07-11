# SHD_004 Rey (front Action [1 resource, Exhaust]) — "Give an Experience token to a unit with 2 or less
# power." SHD_095 (power 2) is the lone eligible target → gets an Experience token (2/3 → 3/4). Rey
# exhausts and 1 resource is spent.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_004}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SHD_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
