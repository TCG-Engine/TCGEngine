# ASH_094 Moff Jerjerrod + JTL_092 Scramble Fighters — extreme case. P1 plays Scramble Fighters (create 8
# readied TIE Fighters that can't attack bases this phase) with Jerjerrod in play and accepts the doubling:
# Jerjerrod is defeated and 16 TIEs (1/1) exist — and the DOUBLED 8 also carry the can't-attack-bases
# marker. P2 has a Mercenary Fleet (LAW_164, 10/10) and a Desperado Freighter (SHD_152, 5/6). The TIEs
# trade 1-for-death into the ships: 10 TIEs target the 10-HP Fleet first (defeated), then the last 6
# (all doubled TIEs) are aimed at P2's base but — because they can't attack bases — auto-redirect to the
# only legal target, the 6-HP Freighter (defeated). End state: no space units on either side, base
# untouched. (If the doubled TIEs lacked the marker they would hit the base instead — so this guards the
# doubled-token marker fix.)
## GIVEN
CommonSetup: ggk/ggk/{myResources:7;handCardIds:JTL_092}
WithActivePlayer: 1
P1OnlyActions: true
WithP1GroundArena: ASH_094:1:0
WithP2SpaceArena: LAW_164:1:0
WithP2SpaceArena: SHD_152:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:BASE
- P1>AttackSpaceArena:0:BASE
- P1>AttackSpaceArena:0:BASE
- P1>AttackSpaceArena:0:BASE
- P1>AttackSpaceArena:0:BASE
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2BASEDMG:0
