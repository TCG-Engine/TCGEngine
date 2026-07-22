# ExhaustTwoGiveAdvantage
#// ASH_231 Diplomatic Pageantry (Event, cost 1) — Exhaust a friendly unit and an enemy unit. If you do,
#// give 2 Advantage tokens to that friendly unit. P1 chooses friendly SOR_095 (g0) and enemy SEC_080
#// (theirGround-0); both are exhausted, and SOR_095 gains 2 Advantage tokens. The other units (SOR_046,
#// SOR_225) are unaffected and stay ready.
## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:ASH_231}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:READY

---

# NoEnemyUnit_NoEffect
#// ASH_231 Diplomatic Pageantry — it must exhaust a friendly AND an enemy unit. With no enemy unit in play,
#// the effect can't complete: nobody is exhausted and no Advantage is given.
## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:ASH_231}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
