# BuffThenDefeat
#// LAW_041 Nothing Left to Fear (Vigilance,Command event, cost 5) — "Choose a friendly unit and give it
#// +2/+2 for this phase. Then, you may defeat a non-leader unit with power equal to or less than the
#// chosen unit." Buff SOR_095 (3/3 -> 5/5), then defeat enemy SEC_080 (power 3 <= 5).

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_041

## WHEN
#// Only one friendly unit, so the "choose a friendly unit" step auto-resolves (PASSPARAMETER) and
#// buffs SOR_095; the single AnswerDecision feeds the "you may defeat" MZMAYCHOOSE.
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# DeclineDefeat
#// LAW_041 Nothing Left to Fear — the defeat is a "may"; decline it. Buff still applies; nothing dies.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_041

## WHEN
#// Single friendly unit -> buff auto-applies; decline the optional defeat.
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1DISCARDCOUNT:1
