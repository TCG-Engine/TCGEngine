# NoAmbush_NoOtherFriendlyUnit
#// SOR_114 Escort Skiff (4/4, Command) — "While you control another Command unit, this unit gains
#// Ambush." The Skiff is itself a Command unit, but the gate says ANOTHER Command unit — with no
#// other friendly unit in play there is no Ambush prompt at all (P1NODECISION); the Skiff simply
#// enters play exhausted and nothing is attacked.
#//
#// COVERAGE: offer=Ambush_TargetOffer_EnemyGroundUnitsOnly (the exact Ambush target pool, left
#//           pending: two legal enemy ground units plus three decoys excluded for three different
#//           reasons — friendly/right arena, friendly/wrong arena, enemy/wrong arena) ·
#//           reqboundary=Ambush_WithAnotherCommandUnit_KillsTarget (the gate is evaluated at play,
#//           then the YES/NO and the target arrive as separate serialized answers) ·
#//           control=Ambush_ControlledEnemyOwnedCommandUnitCounts (a P2-OWNED Command unit under P1's
#//           control satisfies "another Command unit YOU control") · boundary
#//           pair=Ambush_WithAnotherCommandUnit_KillsTarget (gate ON — a second friendly Command unit)
#//           vs NoAmbush_NoOtherFriendlyUnit (the Skiff is Command but "ANOTHER" fails at zero) +
#//           NoAmbush_NonCommandFriendlyUnit (another unit, wrong aspect) +
#//           NoAmbush_EnemyCommandUnitDoesNotCount (right aspect, wrong controller) ·
#//           decline=Ambush_Declined_SkiffStaysExhausted (NO → the Skiff stays exhausted, nothing
#//           attacked).
#// Intended: with another friendly COMMAND-aspect unit in play the Skiff is played with Ambush
#// (prompt YES → ready+attack an enemy unit). The gate reads COMMAND — it was keyed on the wrong
#// aspect (Cunning) until 2026-08-13, which is why the gate-OFF sections came first.

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

---

# Ambush_TargetOffer_EnemyGroundUnitsOnly
#// SOR_114 Escort Skiff — the Ambush attack's TARGET POOL, asserted rather than answered. The gate is
#// on (friendly SOR_111 Patrolling V-Wing, a Command unit, in space), so the played Skiff gains Ambush
#// and P1 accepts it. Intended: a GROUND unit's Ambush attack reaches only ENEMY units in its OWN
#// arena — exactly P2's two ground units. Three decoys are seeded, each excluded for a DIFFERENT
#// reason, so no single wrong filter passes: P1's own SOR_046 (friendly, right arena), P1's SOR_111
#// (friendly, wrong arena) and P2's SOR_225 TIE (enemy, wrong arena) — plus the Skiff itself, which
#// is the attacker. Two legal targets keep the choice from auto-resolving, and the decision is left
#// PENDING so the offer can be read; Ambush_WithAnotherCommandUnit_KillsTarget resolves the same pick.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_111:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_164:1:0]
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: SOR_114

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
