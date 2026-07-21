# MayGiveTwoToSpaceUnit
#// ASH_238 Attendant Navigator (Ground, 2/3, cost 2) — When Played: you may give 2 Advantage tokens to a
#// space unit. P1 controls a friendly space unit (SOR_225); plays ASH_238 and gives it 2 Advantage tokens.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:ASH_238}
WithP1SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_238
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:2

---

# Decline_NoAdvantage
#// ASH_238 Attendant Navigator — the grant is optional. With a space unit present, P1 declines; SOR_225
#// gains nothing.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:ASH_238}
WithP1SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
