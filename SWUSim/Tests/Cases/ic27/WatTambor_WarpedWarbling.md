# WhenPlayed_AttacksWithAReadyUnit_AndTheTurnPassesOnce
#// IC27_143 Wat Tambor (Ground 4/2, cost 3, Sentinel) — "When Played: You may attack with a unit."
#// P1 plays Wat Tambor and attacks P2's base with the ready Battlefield Marine (3 power). The attack is
#// nested inside the play, so the play is P1's single action: the turn passes to P2 exactly once
#// (the SEC_172 bug #926 family). Initiative unclaimed and no P1OnlyActions so a double swap would show.
#// COVERAGE: offer=Offer_OnlyFriendlyUnitsThatCanAttackNow (pending SELECTABLEEXACT: ready ground + space
#//           + a deployed leader unit; exhausted, "can't attack", enemy and Wat Tambor himself excluded) ·
#//           decline=WhenPlayed_Decline_NoAttack_TurnPasses · boundary=N/A (no number in the text) ·
#//           control=N/A (no owner-scoped zone; "attack with a unit" is whoever controls it now) ·
#//           reqboundary=WhenPlayed_AttackerAnsweredAcrossRequestBoundary
#//           modes=2P only (no player reference; attacking is controller-only by rule, so "friendly"
#//           never enters — a Team Suns teammate's unit cannot be the attacker)

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_143

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:IC27_143
TURNPLAYER:2

---

# Offer_OnlyFriendlyUnitsThatCanAttackNow
#// The pool is the units P1 could actually attack with right now: the ready Battlefield Marine (ground 0),
#// the ready deployed leader unit (ground 3) and the ready TIE/ln Fighter in SPACE. Excluded: the
#// EXHAUSTED Consular Security Force (ground 1), the ready Loth-Wolf (ground 2, "This unit can't
#// attack"), P2's ready Death Star Stormtrooper (an enemy unit), and Wat Tambor himself (ground 4 —
#// he entered play exhausted). The pick is left PENDING.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:0:0
WithP1GroundArena: LOF_044:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: IC27_143

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-3&mySpaceArena-0
P1GROUNDARENAUNIT:3:ISLEADERUNIT
P1GROUNDARENAUNIT:4:CARDID:IC27_143
P1GROUNDARENAUNIT:4:EXHAUSTED

---

# WhenPlayed_AttacksAnEnemyUnit
#// The granted attack is an ordinary attack: with an enemy unit in the arena the attacker picks its
#// target. P1's Consular Security Force (3/7) attacks P2's Death Star Stormtrooper (3/1) and defeats it,
#// taking 3 counter damage; P2's base is untouched.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: IC27_143

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# WhenPlayed_Decline_NoAttack_TurnPasses
#// "You may" — declining makes no attack: the Marine stays ready, P2's base is untouched, and the play
#// still ends P1's action.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_143

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:READY
P1NODECISION
TURNPLAYER:2

---

# WhenPlayed_NoUnitCanAttack_NoPrompt
#// No unit P1 controls can attack: the Security Force is exhausted, the Loth-Wolf can't attack, and Wat
#// Tambor enters exhausted. No prompt is raised and the turn passes.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:0:0
WithP1GroundArena: LOF_044:1:0
WithP1Hand: IC27_143

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2BASEDMG:0
P1GROUNDARENACOUNT:3
TURNPLAYER:2

---

# WhenPlayed_AttackerAnsweredAcrossRequestBoundary
#// The happy path with the attacker answered in a FRESH process: the offer and the after-action ownership
#// must both survive serialization (one swap, not zero or two).

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Hand: IC27_143

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
TURNPLAYER:2
