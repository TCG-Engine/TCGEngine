# ActionSetPrintedHpTo1
#// LAW_126 Adventurer Sniper Rifle (Upgrade) — grants "Action [Exhaust]: Choose an undamaged non-leader
#// ground unit. Its printed HP is considered to be 1 for this phase." SEC_080 wears the rifle and uses
#// the action targeting the enemy SOR_046 (3/7, undamaged); its HP becomes 1. The host SEC_080 exhausts.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_126
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ActionSetFriendlyHpTo1
#// LAW_126 Adventurer Sniper Rifle — the granted action may target a FRIENDLY undamaged non-leader ground
#// unit too. Host SEC_080 wears the rifle; the action targets the friendly SOR_095 (3/3, undamaged) whose
#// printed HP becomes 1 this phase. The host exhausts.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:LAW_126
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:HP:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ActionSetOwnHpTo1
#// LAW_126 Adventurer Sniper Rifle — the host may target itself. SEC_080 (3/3, undamaged) wears the rifle
#// and uses the action on itself; its printed HP becomes 1 this phase and it exhausts from the cost.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_126
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# AttachNonVehicleOnly
#// LAW_126 Adventurer Sniper Rifle — "Attach to a non-Vehicle unit." A friendly Vehicle (SOR_232 AT-ST)
#// is NOT a legal host; only the non-Vehicle SEC_080 is selectable.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_232:1:0
WithP1Hand: LAW_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# ActionSetFriendlyHpTo1_SurvivesTheRequestBoundary
#// LAW_126 Adventurer Sniper Rifle — in production the [Exhaust] cost is paid in one request and the
#// target answer arrives in a FRESH process, so the host's exhaust and the pending "printed HP is 1 this
#// phase" continuation must both live in the serialized gamestate, not an in-memory global. Mirrors
#// ActionSetFriendlyHpTo1 with a request boundary inserted between the ability use and the answer.
#// The pool is a genuine 3-way choose (myGroundArena-0 & myGroundArena-1 & theirGroundArena-0), so the
#// decision is really pending across the boundary and the section cannot pass vacuously.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:LAW_126
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:HP:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# AttachPool_NonVehicleUnitEitherSide
#// LAW_126 Adventurer Sniper Rifle — "Attach to a non-Vehicle unit." The restriction names no controller,
#// so per CR 2.e it spans BOTH sides: the pool is every non-Vehicle unit in play, friendly or enemy.
#// The board seats a violator for each half of the rule — a friendly Vehicle (SOR_232 AT-ST) and an enemy
#// Vehicle (SOR_039 AT-AT Suppressor) must both be OUT — alongside a friendly non-Vehicle (SEC_080) and an
#// ENEMY non-Vehicle (SOR_095) that must both be IN. The pick is left pending so the pool itself is the
#// assertion; the existing AttachNonVehicleOnly section only reads upgrade counts afterwards and is
#// structurally blind to the enemy half of the pool.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_232:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_039:1:0]
WithP1Hand: LAW_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# ActionPool_UndamagedNonLeaderGroundOnly
#// COVERAGE: offer=AttachPool_NonVehicleUnitEitherSide (attach pool: non-Vehicle, both sides) +
#//           ActionPool_UndamagedNonLeaderGroundOnly (granted action pool: undamaged / non-leader /
#//           ground, both sides) · decline=N/A (neither the attach nor the granted action is a "you may";
#//           both are mandatory chooses) · control=N/A (no control-change text on the upgrade or its
#//           granted action) · boundary=ActionSetPrintedHpTo1 (enemy) vs ActionSetFriendlyHpTo1 (friendly)
#//           vs ActionSetOwnHpTo1 (the host itself), and AttachNonVehicleOnly (Vehicle rejected) ·
#//           reqboundary=ActionSetFriendlyHpTo1_SurvivesTheRequestBoundary.
#// LAW_126 — the granted action reads "Choose an undamaged non-leader ground unit", three restrictions at
#// once, and the board seats one violator for each: the friendly SOR_046 carries 2 damage (not undamaged),
#// the friendly SOR_237 sits in SPACE (not ground), and P2's leader is deployed as a ground unit (not
#// non-leader). The ability is not controller-scoped, so the only legal targets are the undamaged host
#// SEC_080 and the undamaged ENEMY SOR_095. The ISLEADERUNIT assertion pins the deployed leader at
#// theirGroundArena-1 so its absence from the pool is a real exclusion and not an indexing accident.

## GIVEN
CommonSetup: bbw/rrk/{theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:2]
WithP1GroundArenaUpgrade: 0:LAW_126
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HASDECISION
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
