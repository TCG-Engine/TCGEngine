# CreateCredit
#// LAW_232 Champion's KT9 Podracer (Cunning, cost 3) — When Played: create a Credit token.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
WithP1Hand: LAW_232

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1

---

# P2Seat_TheCreditGoesToTheUNITSController
#// LAW_232 Champion's KT9 Podracer — the When Played Credit belongs to whoever played the unit. P2 plays
#// it and ends with 1 Credit while P1 has none, which is what the existing P1-only section cannot show.

## GIVEN
CommonSetup: rrk/yyk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 3
WithP2Hand: LAW_232

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_232
P2CREDITCOUNT:1
P1CREDITCOUNT:0

---

# SeatedWithoutBeingPlayed_NoCredit
#// LAW_232 Champion's KT9 Podracer — WHEN PLAYED, not "while in play": a copy seeded directly into the
#// arena creates no Credit.

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_232:1:0

## WHEN

## EXPECT
P1GROUNDARENACOUNT:1
P1CREDITCOUNT:0

---

# TwoCopies_TwoCredits
#// LAW_232 Champion's KT9 Podracer — each play creates its own Credit; there is no once-per-round or
#// once-per-name gate. The second play is offered the first Credit as payment and declines it, so both
#// survive to be counted.

## GIVEN
CommonSetup: yyk/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: [LAW_232 LAW_232]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1CREDITCOUNT:2
P1HANDCOUNT:0
