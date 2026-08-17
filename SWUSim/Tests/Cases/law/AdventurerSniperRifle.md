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

---

# AttachPool_DeployedLeadersAreLegalHosts
#// COVERAGE (supersedes the ledger in ActionPool_UndamagedNonLeaderGroundOnly, which is a pre-existing
#//           section and therefore not editable here):
#//           offer=AttachPool_NonVehicleUnitEitherSide (attach pool: non-Vehicle, both sides) +
#//           AttachPool_DeployedLeadersAreLegalHosts (attach pool: a DEPLOYED LEADER unit is non-Vehicle
#//           and so is a legal host on either side) + ActionPool_UndamagedNonLeaderGroundOnly (granted
#//           action pool: undamaged / non-leader / ground, both sides) · decline=N/A (neither the attach
#//           nor the granted action is a "you may"; both are mandatory chooses) · control=N/A (no
#//           control-change text on the upgrade or its granted action) · boundary=ActionSetPrintedHpTo1
#//           (enemy) vs ActionSetFriendlyHpTo1 (friendly) vs ActionSetOwnHpTo1 (the host itself), and
#//           AttachNonVehicleOnly (Vehicle rejected) · duration=HpOverrideExpiresAtEndOfPhase (the
#//           override is "for this phase" and must be gone in the next one) · scope=
#//           ActionSetsHpOnly_PowerAndBystandersUnchanged (only HP, only the chosen unit) ·
#//           reqboundary=ActionSetFriendlyHpTo1_SurvivesTheRequestBoundary.
#//
#// LAW_126 Adventurer Sniper Rifle — "Attach to a non-Vehicle unit." A DEPLOYED LEADER is a unit, and no
#// leader carries the Vehicle trait on its deployed side, so per the printed restriction a deployed leader
#// is a legal host — and since the restriction names no controller (CR 2.e) that holds on BOTH sides.
#// AttachPool_NonVehicleUnitEitherSide proves the friendly/enemy split but seats no leaders at all, so a
#// blanket "hosts must be non-leader units" implementation would pass it unnoticed. Here both leaders are
#// deployed (SOR_005 Luke for P1, SOR_010 Vader for P2) and both must appear in the pool alongside the
#// friendly SEC_080 and the enemy SOR_095, while the friendly SOR_232 AT-ST and the enemy SOR_039 AT-AT
#// Suppressor stay out as Vehicles. Seeded arena units are placed before the leader deploy, so each
#// leader lands at the highest index of its arena; the ISLEADERUNIT assertions pin those indices so their
#// PRESENCE in the pool is a real inclusion and not an indexing accident.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;myLeaderDeployed:true;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_232:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_039:1:0]
WithP1Hand: LAW_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1GROUNDARENAUNIT:2:ISLEADERUNIT
P2GROUNDARENAUNIT:2:ISLEADERUNIT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2&theirGroundArena-0&theirGroundArena-2

---

# ActionSetsHpOnly_PowerAndBystandersUnchanged
#// LAW_126 Adventurer Sniper Rifle — "Its printed HP is considered to be 1 for this phase" changes exactly
#// one number on exactly one unit. The three ActionSet*HpTo1 sections each read only the chosen unit's HP,
#// so an implementation that also flattened POWER, or that stamped the override onto every unit in the
#// arena instead of the chosen one, would pass all three. This section reads the whole board after the
#// enemy SOR_095 is chosen: SOR_095's power stays at its printed 3 while its HP reads 1, and every other
#// object keeps its printed line — the host SEC_080 (3/3, exhausted by the cost but not shrunk), the
#// friendly bystander SOR_046 (3/7, itself a legal but unchosen target), the friendly SOR_237 in space
#// (2/3) and the enemy SOR_225 in space (2/1).

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:0]
WithP1GroundArenaUpgrade: 0:LAW_126
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:HP:1
P2GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:7
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:3
P2SPACEARENAUNIT:0:POWER:2
P2SPACEARENAUNIT:0:HP:1

---

# HpOverrideExpiresAtEndOfPhase
#// LAW_126 Adventurer Sniper Rifle — the override lasts "for this phase" and no longer. Nothing in the
#// file currently advances the clock, so a permanent printed-HP rewrite would satisfy every other section.
#// The host SEC_080 sets the enemy SOR_046's printed HP to 1; the game is then driven out of the action
#// phase, through regroup, and into the NEXT action phase, where SOR_046 must read its printed 7 again.
#// Decks are seeded so the regroup draw decks no one. The mid-phase value in this exact fixture is proven
#// separately by ActionSetPrintedHpTo1, which uses the same board and reads HP:1 before any pass — so a
#// no-op ability could not sneak this section through.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_126
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass

## EXPECT
PHASE:MAIN
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:HP:7
