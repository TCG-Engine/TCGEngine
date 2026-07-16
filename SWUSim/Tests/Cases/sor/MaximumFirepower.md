# TwoImperialsHitSameUnit
#// SOR_234 Maximum Firepower (Event, cost 4) — a friendly Imperial deals its power to
#// a unit, then another friendly Imperial deals its power to the same unit. P1 has
#// Death Trooper (SOR_033, power 3) and First Legion Snowtrooper (SOR_130, power 2);
#// target is P2's Consular Security Force (SOR_046, 3/7). Pick Death Trooper first and
#// SOR_046 as target → 3 damage; the remaining Imperial (Snowtrooper) auto-adds 2 →
#// total 5 damage on SOR_046 (survives at 5).

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
