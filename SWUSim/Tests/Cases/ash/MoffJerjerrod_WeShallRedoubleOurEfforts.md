# BatchDouble_Accept
#// ASH_094 Moff Jerjerrod — "If you would create a number of tokens, you may defeat this unit. If you do,
#// create twice that number of tokens instead." P1 controls Jerjerrod and plays SEC_191 (When Played:
#// create 2 Spy tokens). The batch creation offers the doubling ONCE; P1 accepts → Jerjerrod is defeated
#// and 2 MORE Spies are created (4 total). Final P1 ground = SEC_191 + 4 Spy = 5 (Jerjerrod gone).
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:SEC_191}
WithActivePlayer: 1
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:5

---

# BatchDouble_Decline
#// ASH_094 Moff Jerjerrod — the doubling is a "may". P1 plays SEC_191 (create 2 Spy) with Jerjerrod in
#// play and DECLINES: only 2 Spies are created and Jerjerrod survives. Final P1 ground = Jerjerrod + SEC_191
#// + 2 Spy = 4.
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:SEC_191}
WithActivePlayer: 1
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:4

---

# NoJerjerrod_NoOffer
#// ASH_094 — control: with no Jerjerrod in play, SEC_191's "create 2 Spy tokens" resolves normally and no
#// doubling offer is made (no dangling decision). Final P1 ground = SEC_191 + 2 Spy = 3.
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:SEC_191}
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:3

---

# ScrambleFighters_Extreme
#// ASH_094 Moff Jerjerrod + JTL_092 Scramble Fighters — extreme case. P1 plays Scramble Fighters (create 8
#// readied TIE Fighters that can't attack bases this phase) with Jerjerrod in play and accepts the doubling:
#// Jerjerrod is defeated and 16 TIEs (1/1) exist — and the DOUBLED 8 also carry the can't-attack-bases
#// marker. P2 has a Mercenary Fleet (LAW_164, 10/10) and a Desperado Freighter (SHD_152, 5/6). The TIEs
#// trade 1-for-death into the ships: 10 TIEs target the 10-HP Fleet first (defeated), then the last 6
#// (all doubled TIEs) are aimed at P2's base but — because they can't attack bases — auto-redirect to the
#// only legal target, the 6-HP Freighter (defeated). End state: no space units on either side, base
#// untouched. (If the doubled TIEs lacked the marker they would hit the base instead — so this guards the
#// doubled-token marker fix.)
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

---

# SingleToken_Accept
#// ASH_094 Moff Jerjerrod — the doubling also fires on a SINGLE-token creation (the SWUCreateUnitToken
#// wrapper, used by the ~84 single-token sites). P1 plays SEC_097 (When Played: create a Spy token) with
#// Jerjerrod in play and accepts: Jerjerrod is defeated and a 2nd Spy is created. Final ground = SEC_097 +
#// 2 Spy = 3, and index 0 is SEC_097 (Jerjerrod was defeated and reindexed away — proving the doubling).
## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SEC_097}
WithActivePlayer: 1
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SEC_097
