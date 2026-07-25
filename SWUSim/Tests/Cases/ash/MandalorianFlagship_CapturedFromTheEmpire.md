# PowerPerMandalorian
#// ASH_113 Mandalorian Flagship (Space, 4/8) — gets +1/+0 for each other friendly Mandalorian unit. With
#// two friendly Mandalorian units (ASH_216, ASH_064), the Flagship is at power 4 + 2 = 6.
## GIVEN
CommonSetup: ggw/ggk
WithP1SpaceArena: ASH_113:1:0
WithP1GroundArena: ASH_216:1:0
WithP1GroundArena: ASH_064:1:0
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_113
P1SPACEARENAUNIT:0:POWER:6

---

# AmbushWithLeaderUnit
#// ASH_113 Mandalorian Flagship — "While you control a leader unit, this unit gains Ambush." With a
#// deployed leader (SHD_013) in play, playing the Flagship lets it immediately attack the enemy SOR_225
#// (TIE/ln, 2/1). Flagship (power 4) defeats it and takes 2 damage back.
## GIVEN
CommonSetup: ggw/ggk/{myResources:7;handCardIds:ASH_113;myLeader:SHD_013:1:1}
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_113
P1SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENACOUNT:0

---

# NoAmbushWithoutLeaderUnit
#// ASH_113 Mandalorian Flagship — without a deployed leader (leader in play but NOT deployed), the
#// Flagship does not gain Ambush, so playing it triggers no attack; the enemy SOR_225 is untouched.
## GIVEN
CommonSetup: ggw/ggk/{myResources:7;handCardIds:ASH_113;myLeader:SHD_013:1:0}
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_113
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENACOUNT:1
P1NODECISION

---

# PowerUpdatesWhenMandalorianLeaves
#// ASH_113 Mandalorian Flagship — its +1/+0-per-other-Mandalorian bonus recalculates live. Starting with
#// two other friendly Mandalorians (ASH_216, ASH_064) it is at power 6; after ASH_216 dies attacking the
#// enemy SOR_232 (AT-ST, 6/7), only one Mandalorian remains and the Flagship drops to power 5.
## GIVEN
CommonSetup: ggw/ggk
WithP1SpaceArena: ASH_113:1:0
WithP1GroundArena: ASH_216:1:0
WithP1GroundArena: ASH_064:1:0
WithP2GroundArena: SOR_232:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:ASH_113
P1SPACEARENAUNIT:0:POWER:5
