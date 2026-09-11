# RampFromHand_HiddenWithSource
#// SSOT #8 (gamelog-updates, 2026-09-11) — one wording for "put a card into play as a resource". The ramp
#// helpers (SWURampResourceExhausted / Ready, ~12 callers) wrote a generic "P1 put a card into play as a
#// (ready) resource" with no source and no origin, while every card with its own line reads "P1 resourced …
#// (Source)". They now write through SWULogResourced, worded by where the card came from: from a HAND it stays
#// hidden ("a card from their hand"), from a DISCARD pile or from play it is named; ", ready" when it enters
#// ready. SOR_017 Han Solo: "Action [Exhaust]: Put a card from your hand into play as a resource and ready it."

## GIVEN
CommonSetup: gyw/grw/{myLeader:SOR_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Resources: 2

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1RESCOUNT:3
LOGCONTAINS:P1 resourced a card from their hand, ready ([[SOR_017|Han Solo]])
LOGCOUNT:0:put a card into play as
P2LOGNOTSEES:[[SOR_095

---

# RampFromDiscard_Named
#// SOR_083 Superlaser Technician: "When Defeated: You may put this unit into play as a resource and ready it."
#// It is defeated into its owner's discard first, so the card comes from a PUBLIC zone — named.
#// (Fixture from sor/SuperlaserTechnician.md WhenDefeated_PutsSelfAsResource.)

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_083:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1RESCOUNT:1
LOGCONTAINS:P1 resourced [[SOR_083|Superlaser Technician]] from their discard pile, ready ([[SOR_083|Superlaser Technician]])
LOGCOUNT:0:put a card into play as

---

# ArquitensSteal_NamedFromTheirDiscard
#// SHD_122 Arquitens Assault Cruiser — "When this unit attacks and defeats a non-leader unit: Put the defeated
#// unit into play as a resource under your control." Its hand-written "P1 put X into play as a resource" line
#// now reads like every other resource line and says whose discard it came from. (Fixture from
#// shd/ArquitensAssaultCruiser.md DefeatBecomesResource.)

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1RESCOUNT:3
LOGCONTAINS:P1 resourced [[SOR_225|
LOGCONTAINS:from P2's discard pile ([[SHD_122|Arquitens Assault Cruiser]])
LOGCOUNT:0:into play as a resource
