# Clone_Deals2AndReadies
#// TS26_69 Remove the Chip (Event, cost 2) — Deal 2 damage to a unit. If it's a Clone, ready it.
#// Target the friendly exhausted Clone (TS26_20, 0/4): it takes 2 damage, survives, and — being a
#// Clone — is readied. The non-Clone SEC_080 at index 1 is left untouched.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_69}
WithP1GroundArena: [TS26_20:0:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# NonClone_Deals2NoReady
#// TS26_69 Remove the Chip (Event, cost 2) — Deal 2 damage to a unit. If it's a Clone, ready it.
#// Target a friendly exhausted non-Clone (SEC_080 Imperial, 3/3): it takes 2 damage but is NOT a
#// Clone, so it stays exhausted (no ready).
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_69}
WithP1GroundArena: [SEC_080:0:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:EXHAUSTED
