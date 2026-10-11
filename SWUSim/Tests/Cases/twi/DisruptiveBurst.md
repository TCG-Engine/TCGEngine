# DebuffAllEnemies
#// TWI_075 Disruptive Burst (Event, cost 3, Vigilance) — "Give each enemy unit -1/-1 for this phase."
#// SEC_080 (3/3, ground) → 2/2; SOR_237 (2/3, space) → 1/2.

## GIVEN
CommonSetup: bbw/grw/{myResources:3;handCardIds:TWI_075}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2
P2SPACEARENAUNIT:0:POWER:1
P2SPACEARENAUNIT:0:HP:2

---

# DefeatedUnitFirst_LaterUnitStillDebuffed
#// BUG #1055 family. The section above cannot see this — nothing dies there, so the loop never shifts.
#// -1/-1 is lethal to a 1-HP unit: SOR_128 Death Star Stormtrooper (3/1) is defeated, the arena
#// COMPACTS, and SEC_080 slides from index 1 into index 0 — past the cursor of a loop walking the
#// mzIDs it captured up front. Same defect fixed on SEC_051 / LAW_101 / TS26_48.

## GIVEN
CommonSetup: bbw/grw/{myResources:3;handCardIds:TWI_075}
P1OnlyActions: true
WithP2GroundArena: [SOR_128:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:2
