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

---

# ForceToken_NoDouble
#// ASH_094 Moff Jerjerrod — a FORCE token is NOT a doubled token type, so Jerjerrod does not fire. P1
#// plays LOF_193 Youngling Padawan (When Played: create your Force token); no doubling offer appears,
#// Jerjerrod survives, and P1 has The Force.
## GIVEN
CommonSetup: ggk/ggk/{myResources:8;handCardIds:LOF_193}
WithActivePlayer: 1
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_094
P1HASFORCE
P1NODECISION

---

# OpponentCreates_NoDouble
#// ASH_094 Moff Jerjerrod — the doubling keys off who CREATES the tokens. When the OPPONENT creates
#// tokens, Jerjerrod (P1's) does not fire. P2 plays JTL_090 Executor (When Played: create 3 TIE Fighters);
#// P2 gets 3 TIEs, Jerjerrod survives and gets no offer.
## GIVEN
CommonSetup: ggk/ggk/{theirResources:11;theirhandCardIds:JTL_090}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: ASH_094:1:0
## WHEN
- P2>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_094
P2SPACEARENACOUNT:4
P1NODECISION

---

# DoubleCredit_Accept
#// ASH_094 Moff Jerjerrod — the doubling applies to CREDIT token creation too (not just unit tokens). P1
#// controls Jerjerrod and plays LAW_248 Windfall (create 3 Credits); accepting the double defeats Jerjerrod
#// and creates 3 MORE Credits (6 total).
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:LAW_248}
WithActivePlayer: 1
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1CREDITCOUNT:6
P1GROUNDARENACOUNT:0

---

# DefeatedByItsOwnAbility_NoSecondOfferThisPhase
#// ASH_094 — the cost of doubling is defeating Jerjerrod HIMSELF, so the ability can only ever fire once:
#// a second token creation in the same phase finds no Jerjerrod and is not offered.
#// P1 plays SEC_097 (When Played: create a Spy token) and ACCEPTS — Jerjerrod dies, two Spies appear. P1
#// then plays a second SEC_097: it creates ONE Spy with no prompt at all.
#// The end state discriminates cleanly: 2 SEC_097 + 3 Spy tokens = 5 units. A second doubling would give
#// 6, and a first doubling that never happened would give 4.
#// This also pins that the offer is gated on Jerjerrod being IN PLAY at creation time, not on a
#// once-per-phase flag — he is simply gone.

## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: [SEC_097 SEC_097]
WithP1GroundArena: ASH_094:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:5
P1NODECISION

---

# TokenUpgrade_PalpatineAdvantage_Accept
#// ★ REPORTED 2026-10-19 (game 1647080): Moff Jerjerrod did not fire when ASH_015 Emperor Palpatine gave
#// Advantage tokens. CR: "When an ability instructs a player to 'give' some number of token upgrades to a
#// unit … the tokens are considered 'created'", so giving N Advantage IS creating N tokens. Owner ruling
#// 2026-10-19: Jerjerrod doubles EVERY token, units and upgrades alike.
#// Palpatine's Action targets the only exhausted friendly unit (SEC_135); the OTHER friendly units are
#// Jerjerrod and SOR_095 → 2 Advantage. Accepting defeats Jerjerrod and gives 2 MORE → 4 on SEC_135.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_015}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SEC_135:0:0
WithP1GroundArena: ASH_094:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_135
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:4
P1GROUNDARENACOUNT:2
P1NODECISION

---

# TokenUpgrade_PalpatineAdvantage_OfferIsMadeOnce
#// The give of N Advantage is ONE "create a number of tokens" instruction: exactly one offer, with the
#// shared Jerjerrod prompt, raised after all N are given (2 already on SEC_135 while it is pending).
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_015}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SEC_135:0:0
WithP1GroundArena: ASH_094:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1DECISIONTOOLTIP:Defeat_Moff_Jerjerrod_to_create_twice_as_many_tokens?
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2

---

# TokenUpgrade_PalpatineAdvantage_Decline
#// The doubling is a "may": declining keeps 2 Advantage and Jerjerrod survives, with no further prompt.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_015}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SEC_135:0:0
WithP1GroundArena: ASH_094:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_094
P1GROUNDARENACOUNT:3
P1NODECISION

---

# TokenUpgrade_SingleExperience_Accept
#// A SINGLE given token is "a number of tokens" too (the token-unit path doubles single tokens as well).
#// SHD_040 Clan Wren Rescuer: "When Played: Give an Experience token to a unit." P1 gives it to SOR_095
#// and accepts: Jerjerrod is defeated and SOR_095 ends with 2 Experience (3/5 from 3/3).
## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: SHD_040
WithP1GroundArena: ASH_094:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENACOUNT:2
P1NODECISION

---

# TokenUpgrade_WeaknessGivenToEnemy_MyJerjerrodDoubles
#// Owner ruling 2026-10-19: the player whose ability GIVES the token creates it, so a Weakness I put on an
#// ENEMY unit is doubled by MY Jerjerrod. HMW_062 Nuvo Vindi: "When Played: You may give a Weakness token
#// to a unit." P1 gives it to P2's SOR_046 and accepts → SOR_046 carries 2 Weakness tokens.
## GIVEN
CommonSetup: bbk/brk/{myResources:8}
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: HMW_062
WithP1GroundArena: ASH_094:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_062
P1NODECISION

---

# TokenUpgrade_OpponentsJerjerrod_NoOffer
#// It keys off who CREATES the tokens: P2's Jerjerrod never fires on P1's give. SHD_040 gives SOR_095 one
#// Experience with no prompt; P2's Jerjerrod survives.
## GIVEN
CommonSetup: ggw/ggw/{myResources:8}
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: SHD_040
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:ASH_094
P1NODECISION
P2NODECISION

---

# TokenUpgrade_TwoTypes_AcceptingFirstDropsSecondOffer
#// LOF_225 Three Lessons gives "an Experience token AND a Shield token" — two creations, so two offers. But
#// accepting the first DEFEATS Jerjerrod, so the second can no longer be paid and must not be asked.
#// SOR_095 ends with exactly 3 upgrades (one kind doubled, the other single — both doubled would be 4), and
#// nothing is left pending. Which kind is offered first follows the card's give order, so it is not pinned.
## GIVEN
CommonSetup: yyk/yyk/{myResources:8}
P1OnlyActions: true
WithActivePlayer: 1
WithP1Hand: [LOF_225 SOR_095]
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1GROUNDARENACOUNT:1
P1NODECISION

---

# OnTheDoorstep_Accept_SixReadyDroids
#// ASH_094 Moff Jerjerrod + TWI_190 On the Doorstep ("Create 3 Battle Droid tokens and ready them."). Owner
#// request 2026-10-19: accepting the doubling must give SIX Battle Droids, and ALL SIX must be ready — the
#// doubled 3 are created "instead" by the same instruction, so they carry its "ready them" too. Tokens enter
#// exhausted by default, so a doubled droid that lost the rider would show up here as EXHAUSTED.
#// Jerjerrod is defeated, so the arena is exactly the 6 droids (indexes 0–5).
## GIVEN
CommonSetup: yyk/grw/{myResources:4;handCardIds:TWI_190}
P1OnlyActions: true
WithP1GroundArena: ASH_094:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:6
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:5:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:3:READY
P1GROUNDARENAUNIT:4:READY
P1GROUNDARENAUNIT:5:READY
P1NODECISION
