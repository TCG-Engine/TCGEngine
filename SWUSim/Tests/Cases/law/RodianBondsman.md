# WhenDefeatedEachCredit
#// LAW_116 Rodian Bondsman (2/3) — When Defeated: each player creates a Credit token. Attacks SOR_046
#// (3/7) and dies; both players gain a Credit.

## GIVEN
CommonSetup: bbw/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_116:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:1

---

# WhenDefeatedByEvent
#// LAW_116 Rodian Bondsman — the When Defeated ability also fires when defeated by an event, not just in
#// combat. P1 plays Vanquish (SOR_078) on the lone enemy Rodian; both players still gain a Credit.

## GIVEN
CommonSetup: bbw/bgw/{myResources:5}
WithP1Hand: SOR_078
WithP2GroundArena: LAW_116

## WHEN
- P1>PlayHand:0
- P2>Drain

## EXPECT
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:1
P2CREDITCOUNT:1

---

# EnemyBondsman_BOTHPlayersStillGetOne
#// LAW_116 Rodian Bondsman — "EACH PLAYER creates a Credit token" is symmetric, so it does not matter whose
#// Bondsman died. Here the Bondsman belongs to P2 and is defeated by P1's Vanquish: both players still end
#// on exactly 1 Credit. The existing sections both kill a P1-side Bondsman (or one seeded on P2 and killed
#// the same way), so neither separates "each player" from "the controller and their opponent" in the other
#// direction.

## GIVEN
CommonSetup: bbw/bgw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_078
WithP2GroundArena: [LAW_116:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Drain

## EXPECT
P2GROUNDARENACOUNT:1
P1CREDITCOUNT:1
P2CREDITCOUNT:1

---

# TwoBondsmenDefeatedTogether_TwoCreditsEach
#// LAW_116 Rodian Bondsman — the trigger belongs to each copy, so a board wipe that takes two Bondsmen
#// pays each player twice. P1 plays SOR_043 Superlaser Blast to defeat every unit; both Bondsmen fire and
#// both players end on 2 Credits. This is also the simultaneous-defeat shape where an observer that reads
#// the live board mid-loop typically drops the second trigger.

## GIVEN
CommonSetup: bbk/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: LAW_116:1:0
WithP2GroundArena: LAW_116:1:0
WithP1Hand: SOR_043

## WHEN
- P1>PlayHand:0
- P2>Drain

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:2
P2CREDITCOUNT:2
