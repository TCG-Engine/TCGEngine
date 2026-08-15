# WhenPlayed_DefeatUpgrade
#// SEC_163 Outer Rim Constable (Unit, Aggression, cost 2) — When Played: you may defeat an upgrade.
#//   The enemy SOR_095 bears SOR_120 → defeat it.
#//
#// ⚠ ADDRESSING: "defeat an upgrade" is ONE decision that offers the UPGRADES themselves as SUBCARD
#// mzIDs ("<hostMz>.u<subIdx>", the raw Subcards key), highlighted in place on their hosts. It used to
#// be TWO decisions — pick a host unit, then pick from a TempZone popup of bare card art — which made
#// the player choose a unit before they could see what was on it.
#//
#// COVERAGE: offer asserted -> Offer_EveryUpgradeOnBoardAcrossArenas; request boundary -> N/A (no
#// this-phase/this-round duration); control change -> N/A (a When Played rider does not re-fire on a
#// later control change, and the ability reads no owner-scoped zone); boundary pair -> N/A (no numeric
#// threshold); decline branch -> Decline_NoUpgradeDefeated.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP1Hand: SEC_163

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Offer_EveryUpgradeOnBoardAcrossArenas
#// The pool is every upgrade in play, each addressed on its own host, spanning BOTH arenas and both
#// sides — not the host units, and not a flat popup. This is the board shape from live game 3329: an
#// ASH_086 on an enemy GROUND unit and a Shield token on an enemy SPACE unit, plus a friendly upgraded
#// unit so the pool is provably not enemy-only. Asserting the mzIDs is what proves the host association
#// reaches the offer — the retired TempZone staging produced myTempZone-N, which names no unit at all.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: HMW_107:1:0
WithP2GroundArenaUpgrade: 0:ASH_086
WithP2SpaceArena: JTL_242:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02
WithP1Hand: SEC_163

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0.u0&theirGroundArena-0.u0&theirSpaceArena-0.u0

## WHEN
- P1>PlayHand:0

---

# DefeatShieldTokenInTheOtherArena
#// A Shield token IS an upgrade and is defeatable by this ability. Picking it by its subcard mzID
#// defeats that Shield and leaves the enemy GROUND upgrade untouched — which is the pair of assertions
#// that distinguishes "defeated the one I picked" from "defeated something".

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: HMW_107:1:0
WithP2GroundArenaUpgrade: 0:ASH_086
WithP2SpaceArena: JTL_242:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02
WithP1Hand: SEC_163

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0.u0

## EXPECT
P2SPACEARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# TwoUpgradesOnOneHost_ThePickedOneDies
#// Two upgrades on a SINGLE host are distinct addresses (.u0 / .u1) — under the old host-first flow the
#// player picked the unit and only then saw a popup of both. Picking .u1 must defeat exactly that one
#// and leave .u0 in place.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: HMW_107:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:ASH_086
WithP1Hand: SEC_163

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1NODECISION

---

# Decline_NoUpgradeDefeated
#// "You may" — declining leaves every upgrade in play.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP2GroundArena: HMW_107:1:0
WithP2GroundArenaUpgrade: 0:ASH_086
WithP2SpaceArena: JTL_242:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T02
WithP1Hand: SEC_163

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:SHIELDCOUNT:1
P1NODECISION
