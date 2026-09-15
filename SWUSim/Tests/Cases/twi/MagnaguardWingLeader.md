# Action_TwoDroidAttacks
#// TWI_082 MagnaGuard Wing Leader (Unit, Space, Command/Villainy) — "Action: Attack with a Droid unit.
#// Then, attack with another Droid unit. Use this ability only once each round." Two Battle Droids (TWI_T01,
#// 1/1) each attack the enemy base for 1 (total 2).

## GIVEN
CommonSetup: ggk/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: TWI_082:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:2

---

# OnlyOnceEachRound_TheSameCopyCannotGoAgain
#// "Use this ability only once each round." Four ready Battle Droids: the first activation attacks with
#// two of them (P2's base 2); a second activation of the SAME copy the same round is a no-op, so the other
#// two never attack and the base stays on 2.
## GIVEN
CommonSetup: ggk/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: TWI_082:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>UseUnitAbility:mySpaceArena-0
## EXPECT
P2BASEDMG:2
P1NODECISION

---

# TwoCopies_EachWingLeaderHasItsOwnRound
#// The limit belongs to each COPY (CR 8.32.3; USER RULING 2026-09-07). MagnaGuard Wing Leader is not
#// unique: with two in play, each gets its own activation this round — 4 Droid attacks, P2's base on 4.
#// A per-player flag spent the second copy's round with the first (base on 2).
## GIVEN
CommonSetup: ggk/rrk/{}
P1OnlyActions: true
WithP1SpaceArena: TWI_082:1:0
WithP1SpaceArena: TWI_082:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
- P1>UseUnitAbility:mySpaceArena-1
- P1>AnswerDecision:myGroundArena-2
- P1>AnswerDecision:myGroundArena-3
## EXPECT
P2BASEDMG:4
P1NODECISION

---

# Ruling_UsableWithNoReadyDroid_AndThatSpendsTheRound
#// Official ruling (03/27/2026): "MagnaGuard Wing Leader's action ability can be used as an action even
#// if you can't attack with a Droid unit, since making the ability no longer usable changes the game
#// state." The only Droid is exhausted, yet the Action is offered: using it takes P1's action, so the
#// turn passes to P2 (no P1OnlyActions — a refused click would leave it on P1).
## GIVEN
CommonSetup: ggk/rrk/{}
SkipPreGame: true
WithActivePlayer: 1
WithP1SpaceArena: TWI_082:1:0
WithP1GroundArena: TWI_T01:0:0
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
## EXPECT
TURNPLAYER:2
P2BASEDMG:0

---

# ControlChange_TheNewControllerGetsAFreshRound
#// The per-controller reading, as already pinned for Sebulba's Podracer
#// (law/SebulbasPodracer_TakingTheLead.md::NewControllerGetsAFreshUsePerRound): CR 8.32.3 words the limit
#// on the PLAYER ("they may not choose to resolve that text again that round"). P1 uses the Wing Leader,
#// then P2 takes control of it with Change of Heart (SOR_224) and uses it the same round: P2's two Droids
#// attack P1's base. ⚠ OPEN QUESTION (2026-09-12) — flip to P1BASEDMG:0 if the user rules the round stays
#// spent across a control change (SWUTakeControlOfUnit would then carry NumUses).
## GIVEN
CommonSetup: ggk/ggk/{theirResources:10}
SkipPreGame: true
WithActivePlayer: 1
WithP1SpaceArena: TWI_082:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP2Hand: SOR_224
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T01:1:0
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Pass
- P2>UseUnitAbility:mySpaceArena-0
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:theirBase-0
- P2>AnswerDecision:theirBase-0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:TWI_082
P2BASEDMG:2
P1BASEDMG:2
