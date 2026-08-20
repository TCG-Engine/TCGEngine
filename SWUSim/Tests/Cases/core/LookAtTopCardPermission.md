# NoSource_NoPermission
#// The "You may look at the top card of your deck at any time" permission — a CONTINUOUS VISIBILITY
#// right shared by two printings, LAW_094 Hondo Ohnaka (granted by the unit, to its controller) and
#// HMW_205 Intelligence Agency (granted to the ATTACHED BASE, so it follows the base's controller).
#// COVERAGE: offer=N/A (no decision of any kind — it is a standing permission) ·
#//           negative=this section + EnemyHondoDoesNotGrantItToYou + HondoThatLostItsAbilities ·
#//           boundary=N/A (boolean, no threshold) · control=StolenHondo_GrantsToTheNewController ·
#//           reqboundary=RequestBoundary_PermissionSurvives · decline=N/A (no cost, nothing to decline)
#// ⚠ THIS FILE ASSERTS THE SERVER-SIDE PERMISSION ONLY. The other half — that the entitled seat's
#// GetNextTurn payload carries the top card and the OPPONENT'S DOES NOT — cannot be seen by the
#// in-process runner, which renders no transport. That half is verified per-viewer against a live
#// GetNextTurn (the only thing that can prove a hidden-information leak; a game-log assertion reads the
#// UNFILTERED log and would pass while leaking).
#// Baseline: neither source in play, so nobody may look.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>Drain

## EXPECT
P1NOTSEESTOPCARD
P2NOTSEESTOPCARD

---

# Hondo_GrantsItToHisController
#// LAW_094 Hondo Ohnaka in play grants the permission to the player who CONTROLS him — and to them only.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>Drain

## EXPECT
P1SEESTOPCARD
P2NOTSEESTOPCARD

---

# EnemyHondoDoesNotGrantItToYou
#// The permission is controller-scoped: the OPPONENT's Hondo lets THEM look at THEIR deck, and gives the
#// other player nothing. Without this a board-wide scan would pass every positive section.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP2GroundArena: LAW_094:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>Drain

## EXPECT
P1NOTSEESTOPCARD
P2SEESTOPCARD

---

# HondoLeavesPlay_PermissionEnds
#// It is a continuous permission, recomputed on every read — so it must END when its source goes away,
#// with no lingering flag. Hondo is 3/7, so he is pre-damaged to 5 and the Security Force's 3-power
#// counter finishes him (8 >= 7) while it survives at 3 of its own 7.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:5
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1NOTSEESTOPCARD

---

# HondoThatLostItsAbilities_GrantsNothing
#// "Loses all abilities" must take this permission with it — the read goes through
#// _SWUCountActiveUnitsWithCardID rather than a raw board scan for exactly that reason.
#// SHD_072 is the lose-all-abilities upgrade, attached to Hondo here.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>Drain

## EXPECT
P1NOTSEESTOPCARD

---

# IntelligenceAgencyOnYourBase_GrantsIt
#// HMW_205 Intelligence Agency grants the SAME clause via the attached base, so the base's controller
#// gets it. Seeded as a base upgrade rather than played, to keep this about the grant and not the
#// Fortify attach path (which its own file covers).

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_205
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>Drain

## EXPECT
P1SEESTOPCARD
P2NOTSEESTOPCARD

---

# IntelligenceAgencyOnTHEIRBase_GrantsItToThem
#// The base half is seat-scoped the same way: the upgrade on the OPPONENT's base grants the permission
#// to them, not to the caster.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP2BaseUpgrade: HMW_205
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>Drain

## EXPECT
P1NOTSEESTOPCARD
P2SEESTOPCARD

---

# StolenHondo_GrantsToTheNewController
#// The control cell. Hondo is OWNED by P1 and CONTROLLED by P2, so the permission follows CONTROL:
#// P2 may look at their own deck, P1 may not. An owner-scoped read gets both backwards.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP2GroundArenaControlled: LAW_094:1
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>Drain

## EXPECT
P1NOTSEESTOPCARD
P2SEESTOPCARD

---

# RequestBoundary_PermissionSurvives
#// The request-boundary cell. The permission is derived from serialized zones (units in play, the base's
#// Subcards) on every read, so it must be identical after a fresh process — anything memoised into a
#// transient global at first read would go stale exactly here.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_094:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>Drain
- P1>SimulateRequestBoundary

## EXPECT
P1SEESTOPCARD
P2NOTSEESTOPCARD
