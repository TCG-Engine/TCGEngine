# PlayUnit_Decline
#// TWI_080 Poggle the Lesser — declining (NO) leaves Poggle ready and creates no token.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1GroundArena: TWI_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_080
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENACOUNT:2

---

# PlayUnit_ExhaustCreateDroid
#// TWI_080 Poggle the Lesser (Unit 1/4, Ground, cost 2, Command/Villainy, Separatist/Official) — "When you
#// play another unit: You may exhaust this unit. If you do, create a Battle Droid token." Playing JTL_069
#// triggers Poggle; taking the option exhausts Poggle and creates a Battle Droid (TWI_T01).

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1GroundArena: TWI_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_080
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:2:CARDID:TWI_T01
