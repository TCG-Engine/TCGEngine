# AmidalaCanSpendASpyKilledByTheSameDamage
#// Bug Report, game 1157594: the bot played ASH_112 Luke Skywalker "Answering the Call" ("When Played: If
#// you control at least 4 units, deal 3 damage to each enemy unit") into a board holding SEC_101 Queen
#// Amidala (Naboo, Official) and two SEC_T01 Spy tokens (Official). THE GAME FROZE: P1 was left holding an
#// MZMAYCHOOSE with an EMPTY option list in front of the AMIDALA_PREVENT_ABILITY continuation, so there was
#// nothing to click and nothing to pass.
#//
#// ROOT CAUSE is the ORDERING. LukeSkywalker_AnsweringTheCall.php loops SWUDealDamageToUnit() once per
#// enemy unit and defeats resolve inline, so by the time Amidala's damage is processed the Spies — the only
#// units on the board that share a trait with her — have already been defeated by the SAME effect.
#//
#// OWNER RULING (2026-09-23): "since all damage is dealt simultaneously, i should be able to use Amidala's
#// ability to defeat a Spy even though it took 3 damage. the damage cleanup/resolution is not until after
#// the 'when this unit is damaged' reactions." A Spy that is damaged-but-not-yet-defeated is still on the
#// board, so it is still a legal cost for Amidala's replacement effect.
#//
#// ⚠ TWO Spies on purpose. With one the MZMAYCHOOSE has a single option; this is a "may", so it still
#// prompts, but two keeps the pool assertion honest against an auto-resolve.
#//
#// ⚠ AMIDALA IS LAST ON PURPOSE. The defect is ORDER-DEPENDENT: with Amidala at index 0 her damage is
#// processed while the Spies are still untouched and the prevention works fine (the control section
#// below). Only when the Spies are dealt with FIRST — as they were in the reported game, where the
#// snapshot left Amidala at the end of the arena — do they die before her replacement effect looks for
#// them. An earlier version of this section put her first and PASSED while the bug was live.
## GIVEN
CommonSetup: ggw/ggw/{theirResources:9;theirHandCardIds:ASH_112}
WithInitiativePlayer: 2
WithP1GroundArena: SEC_T01:1:0
WithP1GroundArena: SEC_T01:1:0
WithP1GroundArena: SEC_101:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_101
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# AmidalaFirstInArena_PreventionAlreadyWorked
#// CONTROL — the SAME board with Amidala at index 0 instead of last. Her damage is processed before the
#// Spies are touched, so this arrangement worked even with the bug live. It pins the fix as an ordering
#// fix: it must not regress the case that already worked, and if BOTH sections fail together the cause is
#// the fixture, not the ordering.
## GIVEN
CommonSetup: ggw/ggw/{theirResources:9;theirHandCardIds:ASH_112}
WithInitiativePlayer: 2
WithP1GroundArena: SEC_101:1:0
WithP1GroundArena: SEC_T01:1:0
WithP1GroundArena: SEC_T01:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_101
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
