# WhenDefeated_DealsThreeIndirect
#// JTL_162 Droid Missile Platform (space) — When Defeated: Deal 3 indirect damage to a player. P1 plays
#// Rival's Fall (SHD_079, "Defeat a unit") on P2's Droid Missile Platform. Its When Defeated fires for its
#// controller P2, who directs the 3 indirect at the opponent; P1 (the damaged player) assigns all 3 to
#// their own base.
#// Cross-player reaction: the platform's controller (P2) is the NON-active player, so its When Defeated
#// lands as a static RESOLVE_TRIGGER on P2's queue. In production EngineActionRunner drains both queues
#// (ProcessGoldfishAutomation) after the action; the step-driven harness mirrors that with `P2>Drain`,
#// which runs P2's static trigger and surfaces its "You/Opponent" indirect choice.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_079
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_162:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>Drain
- P2>AnswerDecision:Opponent
- P1>AnswerDecision:myBase-0:3

## EXPECT
P2SPACEARENACOUNT:0
P1BASEDMG:3
P1NODECISION

---

# WhenDefeated_AfterControlChange_DamagesNewControllersOpponent
#// JTL_162 Droid Missile Platform — When Defeated resolves for whoever CONTROLS the unit at defeat time.
#// P1 plays No Glory, Only Results (JTL_043, "Take control of a non-leader unit, then defeat it") on the
#// enemy Droid Missile Platform: P1 gains control, then it is defeated → its When Defeated now belongs to
#// P1, who directs the 3 indirect at P1's opponent (P2), and P2 assigns all 3 to their own base.

## GIVEN
CommonSetup: brk/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: JTL_043
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: JTL_162:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:3

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2BASEDMG:3
P1NODECISION