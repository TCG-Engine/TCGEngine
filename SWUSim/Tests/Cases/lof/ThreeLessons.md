# PlayHiddenExpShield
#// LOF_225 Three Lessons — Play a unit from your hand; it gains Hidden for this phase and gets an Experience
#// token and a Shield token. Plo Koon enters as 7/9 (one Experience) with Hidden and a Shield.

## GIVEN
CommonSetup: yyw/ggk/{myResources:10;handCardIds:LOF_225,LOF_050}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:9
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden

---

# PlaySpaceUnit_HiddenExpShield
#// LOF_225 — the played unit may be a SPACE unit. Alliance X-Wing (2/3) enters the space arena with an
#// Experience token (→3/4), a Shield token, and Hidden for the phase.
## GIVEN
CommonSetup: yyw/ggk/{myResources:10;handCardIds:LOF_225,SOR_237}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:4
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:HASKEYWORD:Hidden

---

# PlayAlreadyHidden_ExpShield
#// LOF_225 — a unit that already has Hidden (Vulptex 3/2) still receives an Experience token (→4/3) and a
#// Shield token; it keeps Hidden. Ref: "play a unit that already has Hidden".
## GIVEN
CommonSetup: yyw/ggk/{myResources:10;handCardIds:LOF_225,LOF_245}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_245
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden

---

# ChooseNothing_NoOp
#// LOF_225 — the play is a "you may": declining to play a unit leaves the hand unit unplayed and no unit
#// enters. Only the event's own cost (2) is paid. Ref: "should do nothing when choosing nothing from hand".
## GIVEN
CommonSetup: yyw/ggk/{myResources:10;handCardIds:LOF_225,SOR_237,SOR_164}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:2
P1RESAVAILABLE:8

---

# Unaffordable_NotSelectable
#// LOF_225 — targets that can't be afforded are not selectable. With 3 resources, after paying the event's
#// cost (2) only 1 remains, so Wampa (cost 4) cannot be chosen; the only selectable option is to decline.
#// Ref: "should not allow selecting targets that can't be afforded". With Wampa the only (unaffordable)
#// unit, no legal play-target exists, so the event resolves with nothing played: Wampa stays in hand and
#// only the event's cost (2) is spent.
## GIVEN
CommonSetup: yyw/ggk/{myResources:3;handCardIds:LOF_225,SOR_164}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:1

---

# PilotPlayedAsUnit
#// LOF_225 — a Pilot (Luke Skywalker, JTL_094) is played as a UNIT, never attached as an upgrade, even with
#// a friendly Vehicle (Wing Leader) available to pilot. Luke enters the ground arena as 3/2, gains an
#// Experience token (→4/3), a Shield token, and Hidden; the Wing Leader stays unpiloted. Ref: "should only
#// play pilots as units".
## GIVEN
CommonSetup: yyw/ggk/{myResources:10;handCardIds:LOF_225,JTL_094}
P1OnlyActions: true
WithP1SpaceArena: SOR_241:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_094
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
