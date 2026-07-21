# CheaperOnImperial
#// ASH_262 Faith in the Empire (Upgrade, cost 2) — costs 1 resource less to play on an Imperial unit.
#// Played onto the Imperial SEC_080, it costs 1: 2 resources - 1 = 1 left.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_262}
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# ImperialUnit_Discounted
#// ASH_262 Faith in the Empire — "costs 1 resource less to play on an Imperial unit." Played on SEC_080
#// (Imperial), it costs 1 instead of 2, leaving 4 of 5 resources.
## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:ASH_262}
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
