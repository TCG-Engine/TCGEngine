# TS26_055 Jedi General — with a non-Republic leader (Vader SOR_010), no Republic leader is controlled,
# so no Clone Trooper token is created (only Jedi General enters play).
## GIVEN
CommonSetup: ggk/rrk/{myResources:5;handCardIds:TS26_055;myLeader:SOR_010}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_055
