# ExhaustedDefeat_NoBlast
#// LAW_201 Thermal Detonator — guard: if the host was EXHAUSTED when defeated, the granted When Defeated
#// does NOT fire. P1's host (SEC_080 + detonator, EXHAUSTED) is killed by SOR_039; no enemy damage, so
#// both P2 ground units survive.

## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SEC_080:0:0
WithP1GroundArenaUpgrade: 0:LAW_201
WithP2GroundArena: SOR_039:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2

---

# ReadyDefeatBlastsEnemies
#// LAW_201 Thermal Detonator (Upgrade, +1/+1) — grants "When Defeated: If this unit was ready, deal 2
#// damage to each enemy ground unit." P1's host (SEC_080 + detonator = 4/4, READY) is attacked and killed
#// by P2's SOR_039 (8/8) while still ready (a defender). Its When Defeated deals 2 to each P2 ground unit:
#// SOR_039 (8/8) survives, SOR_128 (3/1) dies → P2 keeps only SOR_039.

## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_201
WithP2GroundArena: SOR_039:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_039

---

# UpgradeDefeatedDoesNotTrigger
#// LAW_201 Thermal Detonator — the granted When Defeated fires only when the HOST UNIT is defeated, NOT
#// when the upgrade itself is removed. P2 plays Disabling Fang Fighter (SOR_162), which defeats the
#// detonator upgrade on P1's ready host (SEC_080). The host survives, no blast, so P2's ground unit
#// (SOR_095, 3/3) takes 0 damage.

## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_201
WithP2GroundArena: SOR_095:1:0
WithP2Hand: SOR_162
WithP2Resources: 3

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# StolenReadyHost_BlastHitsOriginalOwner
#// LAW_201 Thermal Detonator — the granted When Defeated resolves for the unit's CONTROLLER at
#// defeat, so after a control-change defeat "each enemy ground unit" points back at the original
#// owner's side. P2 plays JTL_043 No Glory, Only Results on P1's READY host (SEC_080 + detonator):
#// P2 takes control, then defeats it. The host was ready, so the blast deals 2 to each of P2's
#// enemies' ground units — i.e. P1's SOR_095 (3/3 -> 2 damage) — while P2's own SOR_046 takes 0.
#//
#// COVERAGE: offer=N/A (the grant is a mandatory, target-less blast — no picker exists; the NGOR
#//           take-control pick is asserted here with a 3-unit pool) · decline=N/A (no "you may") ·
#//           control=this section + StolenExhaustedHost_NoBlast · boundary pair=ready-vs-exhausted
#//           (ReadyDefeatBlastsEnemies vs ExhaustedDefeat_NoBlast, and the same pair under NGOR
#//           below) · reqboundary=the ready-state check crosses the play's control-change +
#//           defeat chain inside one resolution (state pinned before the defeat resolves).

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:LAW_201
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:2

---

# StolenExhaustedHost_NoBlast
#// LAW_201 Thermal Detonator — the "if this unit was ready" guard also holds through a
#// control-change defeat. Same JTL_043 No Glory, Only Results flow as above, but the host
#// (SEC_080 + detonator) is EXHAUSTED when stolen and defeated: no blast, so P1's SOR_095 and
#// P2's SOR_046 both end with 0 damage.

## GIVEN
CommonSetup: rrk/rrk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: [SEC_080:0:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:LAW_201
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:2

---

# AttachPool_NonVehicleUnitEitherSide
#// COVERAGE (corrects the ledger in StolenReadyHost_BlastHitsOriginalOwner, which recorded offer=N/A on
#//           the strength of the GRANTED ability being target-less; the upgrade's OWN attach step is a
#//           real offer and is now asserted here): offer=AttachPool_NonVehicleUnitEitherSide (attach pool
#//           exactly: non-Vehicle only, both sides) + the NGOR take-control pick in StolenReadyHost_*.
#//           The other axes are unchanged: decline=N/A · control=StolenReadyHost_BlastHitsOriginalOwner +
#//           StolenExhaustedHost_NoBlast · boundary=ReadyDefeatBlastsEnemies vs ExhaustedDefeat_NoBlast
#//           (and the same pair under NGOR) · reqboundary=the ready-state check crosses the play's
#//           control-change + defeat chain inside one resolution.
#// LAW_201 Thermal Detonator — "Attach to a non-Vehicle unit." The restriction names no controller, so per
#// CR 2.e it spans BOTH sides; a detonator may legally be strapped to an ENEMY unit. The board seats a
#// violator for each half — friendly Vehicle SOR_232 AT-ST and enemy Vehicle SOR_039 AT-AT Suppressor must
#// both be OUT — and a witness for each half that must be IN: the friendly non-Vehicle SEC_080 and the
#// ENEMY non-Vehicle SOR_095. Every pre-existing section seats the detonator with WithP1GroundArenaUpgrade
#// and therefore never exercises the attach filter at all.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_232:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_039:1:0]
WithP1Hand: LAW_201

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
