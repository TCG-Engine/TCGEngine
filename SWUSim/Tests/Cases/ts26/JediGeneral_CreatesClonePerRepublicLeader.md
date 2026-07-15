# TS26_055 Jedi General (Unit 2/3, cost 5) — Ambush. When Played: for each Republic leader you control,
# create a Clone Trooper token and give it an Experience token. With a Republic leader (Yoda TWI_004), one
# Clone Trooper is created and gets 1 Experience (2/2 → 3/3).
## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_055;myLeader:TWI_004}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_T02
P1GROUNDARENAUNIT:1:POWER:3
