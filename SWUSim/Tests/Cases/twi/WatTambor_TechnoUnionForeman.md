# Front_FriendlyDefeated_Buff
#// TWI_006 Wat Tambor (Leader, front) — "Action [Exhaust]: If a friendly unit was defeated this phase, give
#// a unit +2/+2 for this phase." SOR_128 dies attacking (1 friendly defeat this phase); then Wat Tambor's
#// action buffs SOR_095 to 5/5.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:TWI_006}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
