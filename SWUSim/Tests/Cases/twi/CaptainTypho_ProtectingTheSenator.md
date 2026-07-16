# WhenPlayed_GiveSentinel
#// TWI_046 Captain Typho (Unit, Ground, Vigilance/Heroism) — "When Played/On Attack: Give a unit Sentinel
#// for this phase."
## GIVEN
CommonSetup: bbw/rrk/{myResources:3;handCardIds:TWI_046}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
