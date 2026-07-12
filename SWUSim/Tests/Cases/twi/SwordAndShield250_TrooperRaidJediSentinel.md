# TWI_250 Sword and Shield Maneuver (Event, Heroism) — "Give each friendly Trooper unit Raid 1 for this
# phase. Give each friendly Jedi unit Sentinel for this phase." SOR_095 (Trooper) gains Raid; SOR_149 (Jedi)
# gains Sentinel.
## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TWI_250}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_149:1:0]
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
