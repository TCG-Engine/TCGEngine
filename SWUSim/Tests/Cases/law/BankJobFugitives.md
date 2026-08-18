# WhenPlayed_CreatesCredit
#// LAW_262 Bank Job Fugitives (Unit, cost 6, neutral, 4/6) — When Played: Create a Credit token.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: LAW_262

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_262
P1CREDITCOUNT:1
P1NODECISION

---

# P2Seat_TheCreditGoesToTheUNITSController
#// LAW_262 Bank Job Fugitives — the When Played Credit belongs to whoever played the unit. P2 plays it
#// from its own seat: P2 ends with 1 Credit and P1 with none. The existing section is P1-only and would
#// pass unchanged if the token were handed to a hardcoded seat 1.

## GIVEN
CommonSetup: rrk/yyw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 6
WithP2Hand: LAW_262

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_262
P2CREDITCOUNT:1
P1CREDITCOUNT:0

---

# SeatedWithoutBeingPlayed_NoCredit
#// LAW_262 Bank Job Fugitives — the trigger is WHEN PLAYED, so a copy that reaches the arena without being
#// played creates nothing. Seeded straight into the ground arena, the board shows the unit in play and 0
#// Credits. This is the negative that separates "fires on entering play" from "fires on being played".

## GIVEN
CommonSetup: yyw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_262:1:0

## WHEN

## EXPECT
P1GROUNDARENACOUNT:1
P1CREDITCOUNT:0

---

# TwoCopies_TwoCredits
#// LAW_262 Bank Job Fugitives — each play creates its own Credit; the trigger is not once-per-round or
#// once-per-name. Two copies played in the same action phase leave 2 Credits and 2 units in play.
#// ⚠ The first Credit is available to pay for the second copy, so that play raises a "spend Credits on
#// this cost?" choose; it is declined so both Credits survive to be counted.

## GIVEN
CommonSetup: yyw/rrk/{myResources:12}
P1OnlyActions: true
WithP1Hand: [LAW_262 LAW_262]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1CREDITCOUNT:2
P1HANDCOUNT:0
