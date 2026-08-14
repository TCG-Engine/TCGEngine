# NoAmbush_NoOtherFriendlyUnit
#// SOR_114 Escort Skiff (4/4, Command) — "While you control another Command unit, this unit gains
#// Ambush." The Skiff is itself a Command unit, but the gate says ANOTHER Command unit — with no
#// other friendly unit in play there is no Ambush prompt at all (P1NODECISION); the Skiff simply
#// enters play exhausted and nothing is attacked.
#//
#// COVERAGE: offer=Ambush_WithAnotherCommandUnit_KillsTarget (two-target Ambush pick) — (historical: the
#//           gate bug note below; the gate-ON scenarios [Ambush YES kill, decline branch, control-
#//           change gate, ambush-target offer] must be added once fixed) · reqboundary=gate-ON
#//           flows span PlayHand→YES→target answers — gate fixed 2026-08-13, Cunning→Command) ·
#//           control=Ambush_ControlledEnemyOwnedCommandUnitCounts ·
#//           boundary pair=NoAmbush_NonCommandFriendlyUnit vs the deferred gate-ON kill section ·
#//           decline=Ambush_Declined_SkiffStaysExhausted
#// Intended: with another friendly COMMAND-aspect unit in play the Skiff is played with Ambush
#// (prompt YES → ready+attack an enemy unit). The current engine keys the gate to the wrong
#// aspect, so only the gate-OFF half is encodable green today.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_114}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SOR_114
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoAmbush_NonCommandFriendlyUnit
#// The gate is aspect-specific: a friendly NON-Command unit (SOR_237 Alliance X-Wing, Heroism) does
#// not satisfy "another Command unit", so the Skiff is played without Ambush — no prompt, enters
#// exhausted, the enemy unit is untouched.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_114}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SOR_114
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoAmbush_EnemyCommandUnitDoesNotCount
#// "While YOU control another Command unit" — the OPPONENT's Command unit (SOR_111 Patrolling
#// V-Wing) does not turn the gate on. No Ambush prompt; the Skiff enters exhausted and P2's units
#// are untouched.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_114}
P1OnlyActions: true
WithP2SpaceArena: SOR_111:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SOR_114
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# StatsAndEntry_Vanilla4Cost
#// Baseline body check while the ability sections are deferred: the Skiff plays for exactly 4
#// resources as a 4/4 ground Vehicle and enters exhausted with no pending decision.

## GIVEN
CommonSetup: ggk/bbk/{myResources:4;handCardIds:SOR_114}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_114
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0

---

# Ambush_WithAnotherCommandUnit_KillsTarget
#// The gate reads COMMAND (was keyed on Cunning since the set was built — fixed 2026-08-13): with a
#// friendly SOR_111 Patrolling V-Wing (Command) in play, the played Skiff gains Ambush. Two enemy
#// ground units seeded so the Ambush target is a real choice; the 4/4 kills the 3/3 marine and takes 3.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1SpaceArena: SOR_111:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: SOR_114

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Ambush_Declined_SkiffStaysExhausted
#// The Ambush is optional: declining leaves the just-played Skiff exhausted and nothing is attacked.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1SpaceArena: SOR_111:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SOR_114

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Ambush_ControlledEnemyOwnedCommandUnitCounts
#// "Another Command unit you CONTROL" — a P2-owned Command unit under P1's control satisfies the gate.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1SpaceArenaControlled: SOR_111:2
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SOR_114

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3
