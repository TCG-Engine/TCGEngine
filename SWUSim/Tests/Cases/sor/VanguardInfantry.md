# WhenDefeatedExp
#// SOR_108 Vanguard Infantry (1/2) — When Defeated: you may give an Experience token to
#// a unit. P1's Vanguard attacks P2's Battlefield Marine (3/3) and dies to the 3 counter-
#// damage. Its When Defeated triggers: YES, then give the token to P1's Consular Security
#// Force (SOR_046, stable at index 0) → power 3 → 4.
#// COVERAGE: offer=WhenDefeated_OfferIsEveryUnitBothSides (exact pool: any unit, both players,
#//           both arenas) · decline=Pass_NoExperienceGiven · control=
#//           NoGloryOnlyResults_NewControllerResolvesIt (the controller at defeat resolves the
#//           trigger and may crown their own unit) · boundary pair=WhenDefeatedExp (combat death)
#//           + DefeatedByAbility_GivesExp (non-combat defeat path) · reqboundary=
#//           DefeatedByAbility_GivesExp (the trigger survives the answered event-target boundary
#//           before the recipient pick)

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0    # Experience recipient — index 0 (stays put)
WithP1GroundArena: SOR_108:1:0    # attacker that dies — index 1
WithP2GroundArena: SOR_095:1:0    # defender (3/3)

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4

---

# WhenDefeated_OfferIsEveryUnitBothSides
#// SOR_108 Vanguard Infantry — "a unit" means ANY unit: the recipient pool spans both players and
#// both arenas. The Vanguard attacks the 3/3 Marine and dies; the may-choose is left PENDING so the
#// exact legal-target set can be inspected: P1's Consular Security Force, P2's surviving Marine, and
#// P2's SPACE-arena Cartel Spacer — the dead Vanguard itself is not offered.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0

---

# DefeatedByAbility_GivesExp
#// SOR_108 Vanguard Infantry — the When Defeated also fires on a NON-combat defeat. P1's own
#// Vanquish (SOR_078) defeats the Vanguard; the trigger then offers the token and P1 gives it to
#// the Consular Security Force → 3/7 becomes 4/7 with one Experience upgrade.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:SOR_078}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Pass_NoExperienceGiven
#// SOR_108 Vanguard Infantry — the When Defeated is "you may": declining it gives no token to
#// anyone. Same combat death as WhenDefeatedExp, but the offer is declined → every surviving unit
#// on both sides stays bare.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# NoGloryOnlyResults_NewControllerResolvesIt
#// SOR_108 Vanguard Infantry — a control-take-then-defeat (JTL_043) means the unit is defeated
#// under the TAKER's control, so the taker resolves the When Defeated and may give the token to
#// their own unit. P1 takes and defeats P2's Vanguard, crowns their own Wampa (4/5 → 5/5 + 1
#// Experience upgrade); the Vanguard still lands in its OWNER P2's discard.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:JTL_043}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENACOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_108
P1NODECISION
