# TS26_069 Remove the Chip (Event, cost 2) — Deal 2 damage to a unit. If it's a Clone, ready it.
# Target the friendly exhausted Clone (TS26_020, 0/4): it takes 2 damage, survives, and — being a
# Clone — is readied. The non-Clone SEC_080 at index 1 is left untouched.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_069}
WithP1GroundArena: [TS26_020:0:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:DAMAGE:0
