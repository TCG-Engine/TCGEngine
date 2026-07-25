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

---

# ChooseAmongFriendliesAndPowerRestrictedDefeat
#// LAW_041 Nothing Left to Fear — with two friendly units the buff target is chosen (not auto). Buff
#// SOR_095 (3/3 -> 5/5), then the "you may defeat" is restricted to non-leader units with power <= 5:
#// SEC_080 (power 3) can be defeated, but LOF_236 Army of the Dead (power 7) is not a legal target and
#// survives.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LOF_236:1:0
WithP1Hand: LAW_041

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_236
P1DISCARDCOUNT:1

---

# BuffExpiresNextPhase
#// LAW_041 Nothing Left to Fear — the +2/+2 lasts only "this phase". Buff SOR_095 (3/3 -> 5/5), decline
#// the optional defeat, then advance to the next action phase: the bonus is gone (back to 3/3).

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_041
WithP1Deck: SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
