# PlayForceUnitShield
#// LOF_076 Soresu Stance — Play a Force unit from your hand (paying its cost) and give a Shield token to it.
#// P1 plays the event, then plays Plo Koon (Force) from hand, who enters with a Shield.

## GIVEN
CommonSetup: bbw/ggk/{myResources:12;handCardIds:LOF_076,LOF_050}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# PlayForcePilotUnitShield
#// LOF_076 Soresu Stance — the "Force unit" it plays may be a Force PILOT card played as a normal unit.
#// JTL_197 Anakin Skywalker (Force,Fringe,Pilot; a Piloting unit) is played from hand as a ground unit and
#// enters with a Shield token. Ref: "allows the player to play a Force pilot unit from their hand
#// and gives it a Shield token."

## GIVEN
CommonSetup: bbw/ggk/{myResources:12;handCardIds:LOF_076,JTL_197}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_197
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
