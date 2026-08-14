# TwoImperialsHitSameUnit
#// SOR_234 Maximum Firepower (Event, cost 4) — a friendly Imperial deals its power to
#// a unit, then another friendly Imperial deals its power to the same unit. P1 has
#// Death Trooper (SOR_033, power 3) and First Legion Snowtrooper (SOR_130, power 2);
#// target is P2's Consular Security Force (SOR_046, 3/7). Pick Death Trooper first and
#// SOR_046 as target → 3 damage; the remaining Imperial (Snowtrooper) auto-adds 2 →
#// total 5 damage on SOR_046 (survives at 5).
#// COVERAGE: offer=FirstImperialOffer_OnlyFriendlyImperials + TargetOffer_AllUnitsBothSides +
#//           SecondImperialOffer_ExcludesFirst (all three picks asserted as pending
#//           SELECTABLEEXACT pools) · reqboundary=TwoImperialsHitSameUnit (each pick arrives
#//           in its own request) · control=TargetOffer_AllUnitsBothSides (the damage target
#//           pool crosses the seat line; TwoImperialsHitSameUnit resolves onto an enemy) ·
#//           boundary pair=TwoImperialsHitSameUnit (target survives both hits) +
#//           TargetDefeatedStopsSecondHit (first hit lethal → no second hit) · decline=
#//           NoImperials_EventNoOps (no legal first Imperial → the event resolves as a no-op;
#//           the ability itself is mandatory, no "you may")

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_234}
P1OnlyActions: true
WithP1GroundArena: SOR_033:1:0    # Death Trooper (Imperial, power 3) — index 0
WithP1GroundArena: SOR_130:1:0    # First Legion Snowtrooper (Imperial, power 2) — index 1
WithP2GroundArena: SOR_046:1:0    # target (3/7)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# FirstImperialOffer_OnlyFriendlyImperials
#// Intended: the first pick is a FRIENDLY IMPERIAL unit — the pool spans both of P1's arenas
#// but excludes P1's non-Imperial (Battlefield Marine) and every enemy unit. Decision left
#// pending so the exact pool can be inspected.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_234}
P1OnlyActions: true
WithP1GroundArena: SOR_033:1:0    # Death Trooper (Imperial) — idx 0
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (NOT Imperial) — idx 1, excluded
WithP1SpaceArena: SOR_225:1:0     # TIE/ln Fighter (Imperial, space) — included
WithP2GroundArena: SOR_046:1:0    # enemy — excluded

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# TargetOffer_AllUnitsBothSides
#// Intended: the damage target is "a unit" — ANY unit, both players, both arenas, including
#// the first Imperial itself and friendly non-Imperials. Decision left pending after the
#// first Imperial (Death Trooper) is chosen.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_234}
P1OnlyActions: true
WithP1GroundArena: SOR_033:1:0    # Death Trooper (Imperial) — the chosen first Imperial
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine — a legal TARGET even though not Imperial
WithP1SpaceArena: SOR_225:1:0     # TIE/ln Fighter
WithP2GroundArena: SOR_046:1:0    # enemy unit

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0&theirGroundArena-0

---

# SecondImperialOffer_ExcludesFirst
#// Intended: "ANOTHER friendly Imperial unit" — after Death Trooper (3 power) hits the
#// Consular Security Force (3/7 → 3 damage, survives), the second pick offers the two
#// remaining Imperials and NOT Death Trooper. Decision left pending.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_234}
P1OnlyActions: true
WithP1GroundArena: SOR_033:1:0    # Death Trooper (Imperial, power 3) — first Imperial
WithP1GroundArena: SOR_130:1:0    # First Legion Snowtrooper (Imperial) — idx 1
WithP1SpaceArena: SOR_225:1:0     # TIE/ln Fighter (Imperial)
WithP2GroundArena: SOR_046:1:0    # target (3/7)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1SELECTABLEEXACT:myGroundArena-1&mySpaceArena-0

---

# TargetDefeatedStopsSecondHit
#// Intended: "the same unit" — if the first hit defeats the target, the second hit has
#// nothing to damage and no further damage lands anywhere. Death Trooper (power 3) kills the
#// enemy TIE/ln Fighter (2/1); the sole remaining Imperial (Snowtrooper) resolves without a
#// pick and its damage fizzles — every survivor ends on 0 damage.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_234}
P1OnlyActions: true
WithP1GroundArena: SOR_033:1:0    # Death Trooper (power 3) — first Imperial
WithP1GroundArena: SOR_130:1:0    # First Legion Snowtrooper — the only other Imperial
WithP2SpaceArena: SOR_225:1:0     # target (2/1) — dies to the first hit
WithP2GroundArena: SOR_046:1:0    # bystander — must NOT be hit instead

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:1
P1NODECISION
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoImperials_EventNoOps
#// Intended: with no friendly Imperial unit in play the event resolves as a no-op — it is
#// still played (paid and discarded) but no prompt is raised and nothing takes damage.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:SOR_234}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine — NOT Imperial
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# TargetDefeated_TwoImperialsRemain_NoSecondPrompt
#// Pointless-prompt doctrine: the first Imperial's hit defeats the target, so even with TWO other
#// friendly Imperials in play the "choose another Imperial" prompt is skipped — the second clause has
#// no legal effect.

## GIVEN
CommonSetup: bbk/ggw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_033:1:0
WithP1GroundArena: SOR_130:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: SOR_234

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1NODECISION
P2SPACEARENACOUNT:0
P1GROUNDARENAUNIT:1:DAMAGE:0
