# SixResources_GainsAmbush
#// HMW_118 Ryyk Blademaster (Command/Heroism, Wookiee, 4-cost 5/4 Ground) —
#// "While you control 6 or more resources, this unit gains Ambush and Overwhelm."
#// COVERAGE: offer=N/A (the ability grants keywords; it raises no target pool of its own — the Ambush
#//           prompt it enables is the keyword's, asserted here and in FiveResources_NoAmbush) ·
#//           negative=FiveResources_NoAmbush + FiveResources_NoOverwhelm (the boundary pair partners,
#//           one short of the gate) · boundary pair=6 vs 5 on BOTH keywords ·
#//           control=StolenBlademaster_* (the count is the CONTROLLER's, both directions) ·
#//           reqboundary=SixResources_AmbushSurvivesTheRequestBoundary ·
#//           decline=N/A (no "you may" of its own; the Ambush decline is the keyword's, covered by
#//           keywords/Ambush_No.md) · suppression=LostAbilities_NoKeywordsEvenAtSix
#// "You control 6 or more resources" counts EVERY resource, ready or exhausted (CR: control, not
#// availability) — so paying this unit's own 4-cost out of 6 leaves the gate satisfied. That is exactly
#// what this section proves: 6 resources, 4 spent on the card itself, Ambush still granted.
#// Ambush = "when you play this unit, it may ready and attack an enemy unit." The Blademaster (5/4)
#// kills the 3/3 Marine and takes 3 back.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_118
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_118
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# FiveResources_NoAmbush
#// HMW_118 — the NEGATIVE that makes the gate load-bearing, and the boundary partner of the section
#// above: FIVE resources is one short, so no Ambush is granted and no prompt is raised at all.
#// The card costs 4, so five resources still AFFORD it — the play must succeed and simply grant nothing.
#// P1NODECISION is the load-bearing assertion (the prompt was never raised, not merely declined), and
#// the Marine surviving undamaged proves no attack happened.

## GIVEN
CommonSetup: ggw/bgw/{myResources:5}
P1OnlyActions: true
WithP1Hand: HMW_118
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_118
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# SixResources_GainsOverwhelm
#// HMW_118 — the OTHER granted keyword. Seeded (so no cost is paid and the resource count is exactly
#// the variable under test), 6 resources, attacking a 3/1: Overwhelm sends the 4 excess damage
#// (5 power - 1 remaining HP) to the enemy base.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_118:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# FiveResources_NoOverwhelm
#// HMW_118 — the Overwhelm negative / boundary partner. Identical board at FIVE resources: the same
#// attack kills the same unit, but no excess reaches the base.
#// Asserting P2BASEDMG:0 alongside the kill is what separates "Overwhelm absent" from "the attack
#// didn't happen".

## GIVEN
CommonSetup: ggw/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: HMW_118:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# StolenBlademaster_ReadsTheNEWControllersResources
#// HMW_118 — "you control" is scoped to whoever CONTROLS the Blademaster, not its owner.
#// P1 OWNS it but P2 CONTROLS it (`WithP2GroundArenaControlled: HMW_118:1` — controller = the arena
#// seat, owner = the `:N` argument). P2 holds 6 resources and P1 only 2, so the gate must read P2's
#// count and grant Overwhelm; an owner-scoped read would see P1's 2 and grant nothing.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2;theirResources:6}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArenaControlled: HMW_118:1
WithP1GroundArena: SOR_128:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:4

---

# StolenBlademaster_NewControllerBelowSix_NoOverwhelm
#// HMW_118 — the mirror of the section above, and the half that proves the control scoping is real
#// rather than incidental: the OWNER (P1) now holds 6 resources and the CONTROLLER (P2) only 2.
#// An owner-scoped read would grant Overwhelm here; a correct controller-scoped read grants nothing.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6;theirResources:2}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArenaControlled: HMW_118:1
WithP1GroundArena: SOR_128:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:0

---

# SixResources_AmbushSurvivesTheRequestBoundary
#// HMW_118 — the request-boundary cell. Identical to SixResources_GainsAmbush with one inserted line:
#// production starts a FRESH process on every answer, so the granted keyword must be recomputed from
#// durable state (the resource count) rather than anything held in memory across the Ambush prompt.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_118
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0

---

# LostAbilities_NoKeywordsEvenAtSix
#// HMW_118 — the grant is the unit's OWN ability, so a unit that has lost its abilities does not get it
#// even with the gate satisfied. SHD_072 Imprisoned ("attached unit loses its current abilities and
#// can't gain abilities") is attached; at 6 resources the attack must deal NO excess to the base.
#// Contrast SixResources_GainsOverwhelm, which is the identical board without the upgrade.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: HMW_118:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
