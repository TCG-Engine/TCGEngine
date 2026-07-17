# OppDeclines
#// TS26_33 Kouhun Assassination — if the opponent declines to discard ("may"), the rider does not happen:
#// no debuff, the opponent keeps their card and unit.
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33;theirhandCardIds:SOR_095}
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:-
## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:1

---

# OppDiscardsThenDebuff
#// TS26_33 Kouhun Assassination (Event, cost 3) — An opponent may discard a card from their hand. If they
#// do, give a non-Vehicle unit -8/-8 for this phase. The opponent discards their card, then the caster
#// debuffs a non-Vehicle enemy unit (SEC_080, 3/3) to death.
## GIVEN
CommonSetup: byk/rrk/{myResources:3;handCardIds:TS26_33;theirhandCardIds:SOR_095}
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2HANDCOUNT:0
P2GROUNDARENACOUNT:0
