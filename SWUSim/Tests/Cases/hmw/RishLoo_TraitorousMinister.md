# WhenPlayed_StealsAWeakenedEnemyUnit
#// HMW_200 Rish Loo, Traitorous Minister — Unit (Ground) 3/2, cost 4, [Cunning][Villainy],
#// Separatist/Gungan/Official, unique.
#// "Hidden
#//  When Played: Take control of an enemy non-leader unit with a Weakness token on it. At the start of
#//  the next regroup phase, its owner takes control of it."
#// Hidden needs no code (registry + generic keywords/Hidden.md). The steal is MANDATORY, so a single
#// legal target auto-resolves: the weakened SOR_046 crosses to P1's arena, Weakness still attached.
#// COVERAGE: offer=Offer_OnlyWeakenedEnemyNONLeaderUnits (three-way exclusion, SELECTABLEEXACT)
#//           decline=N/A (mandatory "take control", no may) · boundary=N/A (no numeric threshold; the
#//           Weakness gate's negative lives in the offer section) · control=the CARD IS the control
#//           change; owner-vs-controller pinned by ControlRETURNSToTheOwnerAtNextRegroupStart +
#//           StolenUnitDefeated_GoesToItsOWNERSDiscard · reqboundary=N/A (steal + marker are written in
#//           one handler; the return reads the PERM global at regroup, itself boundary-crossing by design)

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_200
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENACOUNT:0

---

# Offer_OnlyWeakenedEnemyNONLeaderUnits
#// The three exclusions in one offer: enemy SEC_080 (weakened ✓) and SOR_095 (weakened ✓) are legal;
#// the enemy SOR_046 WITHOUT a Weakness is out; the WEAKENED FRIENDLY SOR_128 is out ("enemy"); and the
#// weakened enemy DEPLOYED LEADER (HMW_009, arena index 0) is out ("non-leader"). Three legal-adjacent
#// bodies excluded for three different printed reasons — a filter missing any one of them shows a
#// different offer.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4;theirLeader:HMW_009;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: HMW_200
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArena: [SEC_080:1:0 SOR_095:1:0 SOR_046:1:0]
WithP2GroundArenaUpgrade: 0:HMW_T02
WithP2GroundArenaUpgrade: 1:HMW_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# NoWeakenedEnemy_CleanFizzle
#// No legal target is a clean no-op: Rish Loo still enters play (the steal is not a condition on his own
#// arrival), nothing moves, no decision dangles. The enemy unit is un-weakened on purpose.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_200
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_200
P2GROUNDARENACOUNT:1
P1NODECISION

---

# ControlRETURNSToTheOwnerAtNextRegroupStart
#// The second clause: "At the start of the next regroup phase, its owner takes control of it" — control,
#// not zone (contrast JTL_235 Commandeer, which bounces to hand). After the round ends, the stolen
#// SOR_046 stands in P2's arena again, Weakness intact, NOT in anyone's hand or discard.
#// Decks seeded on both sides so the regroup draws add no base-damage noise.

## GIVEN
CommonSetup: yyk/rrk/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_200
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_200
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2DISCARDCOUNT:0

---

# StolenUnitDefeated_GoesToItsOWNERSDiscard_AndTheReturnNoOps
#// Ownership vs control: P1 controls the stolen SOR_046 but P2 OWNS it, so defeating it (LOF_264 It's
#// Worse, aspectless — the lone non-leader unit beside itself... the STOLEN unit is the only other
#// non-leader unit, and It's Worse may also target Rish, so the answer names the stolen unit) sends it
#// to P2's discard. The regroup return then finds nothing and must no-op cleanly — no crash, no
#// resurrection, no dangling decision.

## GIVEN
CommonSetup: yyk/rrk/{myResources:11}
P1OnlyActions: true
WithP1Hand: [HMW_200 LOF_264]
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02
WithP1Deck: [SOR_095 SOR_046 SOR_128 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SOR_128 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
