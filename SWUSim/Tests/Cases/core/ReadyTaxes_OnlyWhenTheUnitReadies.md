# Regroup_AlreadyReadyUnit_IsNotTaxed
#// SSOT #5 (gamelog-updates, 2026-09-12) — the ready taxes had THREE copies: the shared _SWUQueueReadyTaxes
#// (OnReadyCard + the inline readies) and two regroup clones (SWUQueueJTL192RegroupTriggers /
#// SWUQueueASH088RegroupTriggers). The clones taxed EVERY unit carrying the upgrade at the regroup ready step,
#// whether or not it readied. CR 5.1.e: "A ready card can be chosen for a readying effect, but the chosen card
#// does not change orientation and is not considered to have been readied" — and the regroup step readies only
#// EXHAUSTED cards. JTL_192 In Debt to Crimson Dawn: "When attached unit readies: exhaust it unless its
#// controller pays 2 resources." Here SOR_095 is already READY at the regroup: it does not ready, so nothing is
#// asked and nothing is spent. (Fixture from jtl/InDebtToCrimsonDawn.md RegroupDeclineExhaust, host ready.)

## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:JTL_192
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1NODECISION
P1RESAVAILABLE:5

---

# Regroup_UnitThatCantReady_IsNotTaxed
#// The other half: SHD_193 Frozen in Carbonite ("Attached unit can't ready") keeps the exhausted host from
#// readying at the regroup — so it does not ready, and ASH_088 The Conflict Within ("When this unit readies:
#// you may pay 3 resources. If you don't, exhaust this unit.") does not ask.

## GIVEN
CommonSetup: gyk/gyk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 0:SHD_193
WithP1GroundArenaUpgrade: 0:ASH_088
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
P1RESAVAILABLE:5
