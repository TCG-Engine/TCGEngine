# TS26_069 Remove the Chip (Event, cost 2) — Deal 2 damage to a unit. If it's a Clone, ready it.
# Target a friendly exhausted non-Clone (SEC_080 Imperial, 3/3): it takes 2 damage but is NOT a
# Clone, so it stays exhausted (no ready).
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_069}
WithP1GroundArena: [SEC_080:0:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:EXHAUSTED
