# PlayedWithAnotherFriendly_BuffsItAndNotHimself
#// IC27_079 Qui-Gon Jinn (Unwavering Belief) — 5 cost, 5/5, Command+Heroism, Ground,
#//   Republic/Force/Jedi (unique). Sentinel is printed (auto-wired).
#// Text: "When Played: Give another friendly unit +2/+2 for this phase."
#// The Marine (3/3) becomes 5/5. Qui-Gon's own 5/5 is the SELF-EXCLUSION proof: "another" must leave
#// him out. It is also structural — with only one other friendly unit the choose AUTO-RESOLVES, so if
#// he were eligible there would be two targets, a real prompt, and this WHEN would desync.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;myhandCardIds:IC27_079}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:CARDID:IC27_079
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel

---

# NoOtherFriendlyUnit_FizzlesCleanly
#// NO-VALID-TARGET: played into an empty board there is no "another friendly unit", so the ability
#// must no-op without a prompt or a crash — and Qui-Gon must not buff himself as a fallback.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;myhandCardIds:IC27_079}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_079
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1NODECISION

---

# EnemyUnitIsNotAFriendlyTarget
#// THE LOAD-BEARING NEGATIVE on "friendly": an enemy unit on the board must not become eligible, so
#// the ability still fizzles and the enemy keeps its printed 3/3.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;myhandCardIds:IC27_079}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3
P1NODECISION

---

# TwoFriendlies_ChoosesOneAndOnlyThatOneIsBuffed
#// With two eligible units this is a real choice, and exactly one is buffed — the buff is single-target,
#// not an aura over the board.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;myhandCardIds:IC27_079}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:9

---

# BuffExpiresAtEndOfPhase
#// DURATION EDGE: "for this phase" must actually EXPIRE. Passing to regroup runs the phase-effect
#// sweep, and the Marine is back to its printed 3/3 in the next action phase.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;myhandCardIds:IC27_079}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
