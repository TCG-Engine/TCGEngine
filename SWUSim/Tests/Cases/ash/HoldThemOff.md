# SplitPowerInArena
#// ASH_139 Hold Them Off (Event, cost 4) — Choose a friendly unit; it deals damage equal to its power
#// divided among any number of units in its arena. P1 picks SOR_046 (3 power) and assigns all 3 to the
#// enemy SEC_080 (3/3) in the ground arena, defeating it.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:3
## EXPECT
P2GROUNDARENACOUNT:0

---

# SplitAcrossTwoUnits
#// ASH_139 Hold Them Off — the power may be divided among multiple units in the arena. SOR_046 (3 power)
#// puts 2 on SEC_080 and 1 on SOR_128 (3/1), defeating SOR_128 while SEC_080 survives with 2.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
