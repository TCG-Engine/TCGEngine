# FriendlyDealsPowerToEnemy
#// COVERAGE: offer=FriendlyOfferSpansBothArenas + EnemyOfferSpansBothArenas (both pools asserted
#//           exactly, pending) · decline=N/A (both choices are mandatory — no "you may"; the
#//           no-legal-dealer / no-legal-target no-ops are NoFriendlyUnits_PlayableAsNoOp +
#//           NoEnemyUnits_PlayableAsNoOp) · boundary=DamageEqualsPower_TargetSurvives_NoCounterDamage
#//           (3 dmg on 7 HP survives) vs FriendlyDealsPowerToEnemy (3 dmg on 3 HP is lethal)
#//           · control=N/A (both pools are read live per seat at queue time; the event grants no
#//           persistent effect that could outlive a control change) · reqboundary=the dealer mzID is
#//           carried through the handler param across the two decisions
#//           (DamageEqualsPower_TargetSurvives_NoCounterDamage spans dealer-pick → target-pick)
#// SOR_127 Strike True (Event, cost 3) — "A friendly unit deals damage equal to its
#// power to an enemy unit." P1's only friendly is Consular Security Force (SOR_046,
#// power 3); P2's only unit is Battlefield Marine (SOR_095, 3/3). Both selections
#// auto-resolve (one option each): the dealer's 3 power kills the 3-HP Marine.
#// (The dealer takes no counter-damage and survives.)

## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:SOR_127}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # dealer, power 3
WithP2GroundArena: SOR_095:1:0    # target, 3 HP

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# FriendlyOfferSpansBothArenas
#// SOR_127 Strike True — the dealer choice is "a friendly unit": ALL friendly units, ground AND
#// space, are legal. With two ground friendlies and one space friendly seated, the offer is exactly
#// those three. Asserted with the decision left pending (no pre-condition EXPECT block exists).

## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:SOR_127}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # Consular Security Force 3/7
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine 3/3
WithP1SpaceArena: SOR_237:1:0     # Alliance X-Wing
WithP2GroundArena: SOR_232:1:0    # AT-ST 6/7
WithP2SpaceArena: SOR_225:1:0     # TIE/ln Fighter

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0

---

# EnemyOfferSpansBothArenas
#// SOR_127 Strike True — after the dealer is locked in, the target choice is "an enemy unit":
#// every enemy unit in both arenas is legal (two ground + one space here). Left pending to assert
#// the offer itself.

## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:SOR_127}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # dealer
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine 3/3
WithP2GroundArena: SOR_232:1:0    # AT-ST 6/7
WithP2SpaceArena: SOR_225:1:0     # TIE/ln Fighter 2/1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# DamageEqualsPower_TargetSurvives_NoCounterDamage
#// SOR_127 Strike True — the ability is DAMAGE, not an attack: the dealer's current power (Consular
#// Security Force, 3) hits the chosen AT-ST (6/7), which survives on 3 damage; the dealer takes no
#// damage back. Two candidates on each side keep both prompts interactive.

## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:SOR_127}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # dealer, power 3
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_232:1:0    # AT-ST 6/7 — takes 3, survives
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# NoFriendlyUnits_PlayableAsNoOp
#// SOR_127 Strike True — Intended: the event is playable with NO friendly units; it simply resolves
#// as a no-op (no dealer exists). The card is spent (paid + discarded) and no prompt is raised.

## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:SOR_127}
P1OnlyActions: true
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoEnemyUnits_PlayableAsNoOp
#// SOR_127 Strike True — Intended: the event is playable with NO enemy units; nothing is dealt and
#// the friendly board is untouched. The card is spent (paid + discarded).

## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:SOR_127}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
