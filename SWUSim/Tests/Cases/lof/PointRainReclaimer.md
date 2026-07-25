# Jedi_GivesExp
#// LOF_092 Point Rain Reclaimer — When Played: if you control a Jedi unit, may give an Experience token to
#// this unit. P1 controls Plo Koon (Jedi), plays the Reclaimer, and accepts the Experience token.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_092}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# Jedi_DeclineExp
#// LOF_092 Point Rain Reclaimer — the Experience token is a "may". P1 controls Plo Koon (LOF_050, Jedi) so the
#// ability triggers, but P1 declines: no Experience token is placed. Ref: "should be able to be
#// passed".

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_092}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# NoJedi_NoTrigger
#// LOF_092 Point Rain Reclaimer — the ability is gated on controlling a Jedi unit. With only Battlefield
#// Marine (SOR_095, non-Jedi) on board, the ability does not trigger and the Reclaimer enters unupgraded —
#// no Experience is given because no Jedi unit is controlled.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_092}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
