# ExhaustEnemyUnit
#// SOR_189 Leia Organa — NO: 2 enemy ready units → MZCHOOSE → exhausts chosen one

## GIVEN
CommonSetup: ygw/grw/{myResources:2;theirResources:2;handCardIds:SOR_189}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Exhaust a unit
- P1>ChooseTheirGroundUnit:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY

---

# ExhaustOwnUnit
#// SOR_189 Leia Organa — NO: own unit is valid target; auto-exhausts when it's the only ready unit
#// SOR_095 at myGroundArena-0 is the only other ready unit (Leia enters exhausted).

## GIVEN
CommonSetup: ygw/grw/{myResources:2;theirResources:2;handCardIds:SOR_189}
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Exhaust a unit

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_189

---

# ExhaustUnit_AutoPick
#// SOR_189 Leia Organa — NO: only 1 other ready unit → auto-exhausts (PASSPARAMETER)
#// Leia enters exhausted (Status:0). P2's SOR_095 is the only other ready unit.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;theirResources:2;handCardIds:SOR_189}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Exhaust a unit

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# ReadyResource
#// SOR_189 Leia Organa — YES: auto-readies first exhausted resource (no player choice)
#// Playing Leia (cost 2) exhausts myResources-0 and myResources-1.
#// Choosing YES auto-readies the first one (myResources-0).

## GIVEN
CommonSetup: ygw/grw/{myResources:2;theirResources:2;handCardIds:SOR_189}

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ready a resource

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:CARDID:SOR_189
