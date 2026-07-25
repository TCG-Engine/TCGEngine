# LeaderUnitGainsRaidOverwhelm
#// SEC_099 Naboo Royal Starship (Space, 2/5) — "Each friendly leader unit gains Raid 2 and Overwhelm."
#//   P1 deploys its leader (becomes a leader unit); with SEC_099 in play it has Raid and Overwhelm.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8}
P1OnlyActions: true
WithP1SpaceArena: SEC_099:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# GrantedRaid2AndOverwhelm_SpillsToBase
#// SEC_099 Naboo Royal Starship — the granted Raid 2 and Overwhelm actually FUNCTION. P1's deployed
#//   leader SOR_009 Leia (power 4) gains Raid 2 (→ power 6) and Overwhelm. She attacks the enemy SOR_095
#//   (3 HP): 3 defeats the marine, and Overwhelm carries the 3 excess damage to the enemy base (3 damage).

## GIVEN
CommonSetup: ggw/rrk/{myResources:8}
P1OnlyActions: true
WithP1SpaceArena: SEC_099:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3

---

# PlayViaPlot_StillGrantsRaid2Overwhelm
#// SEC_099 Naboo Royal Starship — when played from resources via PLOT on a leader deploy, it still grants
#//   Raid 2 + Overwhelm to friendly leader units. It sits in P1's resources; deploying the leader offers
#//   the Plot play; then SOR_009 Leia (power 4 → 6 with Raid 2) attacks SOR_095 (3 HP) and Overwhelm
#//   spills the 3 excess to the enemy base.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_099:1,7:SOR_095:1
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
