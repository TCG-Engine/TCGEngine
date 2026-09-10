# OpponentsUnit_Defeated_TheyCreateABeast
#// HMW_058 Mysterious Disappearance (Event, cost 2, [Cunning][Vigilance], Trick) — "A player chooses a
#// non-leader unit they control. You may defeat that unit. If you do, that player creates a Beast token."
#//
#// COVERAGE: offer=PlayerOffer_OnlyPlayersWithANonLeaderUnit + TwinSuns_PlayerOfferSpansEveryEligibleSeat
#//           + TeamSuns_ATeammateIsAPlayer (the player menu) and OpponentPicksWhichUnit_ThenYouDecide
#//           (the defeat prompt left pending, AFTER the opponent's pick) ·
#//           decline=Decline_NoDefeatNoBeast · boundary=N/A (structural: no numeric threshold; the one
#//           count — "a" unit, one Beast — is pinned by every positive) ·
#//           control=StolenUnit_OwnerGetsTheCardControllerGetsTheBeast ·
#//           reqboundary=RequestBoundary_BeforeTheDefeatAnswer ·
#//           modes=2P,TwinSuns,TeamSuns — "A PLAYER" is a player choice (includes you, spans every live
#//           seat) and a teammate is a player (TeamSuns: "a player", not "an opponent").
#//
#// Reading: the CASTER chooses a player (yourself included); THAT player picks one of their own non-leader
#// units; the caster MAY defeat it; only if the defeat actually happens does that player create a Beast
#// (HMW_T03, a 3/3 token). ELIGIBILITY = WHO ACTS: the chosen player acts on their own board, so a player
#// with no non-leader unit is not on the menu, and a lone eligible player is picked silently.
#//
#// This section: P1 has no units, so P2 is the only eligible player (no prompt) and their only unit is
#// picked automatically. YES → the Dark Trooper is defeated and P2 gets a Beast. Run WITHOUT
#// P1OnlyActions so the single action close is observable.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_T03
P2DISCARDUNIT:0:CARDID:SEC_080
P1GROUNDARENACOUNT:0
P1NODECISION
TURNPLAYER:2
NOEXTRAACTION

---

# PlayerOffer_OnlyPlayersWithANonLeaderUnit
#// HMW_058 — the PLAYER menu. Both players control a non-leader unit, so "a player" is a real choice and
#// it includes the caster.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1OPTIONHAS:P1
P1OPTIONHAS:P2

---

# ALeaderUnitIsNotEligible
#// HMW_058 — "NON-LEADER unit". P2's only unit is its deployed leader, so P2 is NOT eligible and the lone
#// eligible player (P1, with a Marine) is picked silently — the next prompt is already the defeat
#// question about P1's own Marine.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2;theirLeaderDeployed:true}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P1DECISIONTOOLTIP:Defeat_Battlefield_Marine?_If_you_do,_its_controller_creates_a_Beast_token.

---

# ChooseYourself_DefeatYourOwnUnit_YouGetTheBeast
#// HMW_058 — "a player" includes YOU. P1 picks P1, then picks their own Dark Trooper (two units, so the
#// pick is real), defeats it, and P1 creates the Beast. Discard: the event + the Dark Trooper.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P1
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1DISCARDCOUNT:2
P2GROUNDARENACOUNT:1
TURNPLAYER:2
NOEXTRAACTION

---

# OpponentPicksWhichUnit_ThenYouDecide
#// ⚠ HMW_058 — ORDER across seats. P2 has two units, so P2 makes a real pick (on P2's queue); only THEN
#// does the caster's "defeat it?" appear — and the action must NOT have closed in between (TURNPLAYER is
#// still P1). The defeat prompt is left pending and names the unit P2 picked.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
TURNPLAYER:1
P2NODECISION
P1DECISIONTOOLTIP:Defeat_Battlefield_Marine?_If_you_do,_its_controller_creates_a_Beast_token.

---

# OpponentPicksWhichUnit_Resolved
#// HMW_058 — the same board carried through: P2 picked the Marine, P1 defeats it → the Marine is gone, the
#// Dark Trooper untouched, P2 has a Beast, and the action closed exactly once.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:1:CARDID:HMW_T03
P2DISCARDUNIT:0:CARDID:SOR_095
TURNPLAYER:2
NOEXTRAACTION

---

# AnEventPlayReactionWaitsForTheDefeatDecision
#// ⚠ HMW_058 — ORDER, the reaction tail. P1's SOR_182 Bossk reacts to "when you play an event" — a
#// TRIGGERED ability, so it resolves only after this event's ability has finished. After P2 picks, the
#// pending prompt must be the caster's DEFEAT question, not Bossk's. (Bossk also makes P1 an eligible
#// player, so P1 picks P2 from a real menu.)
#// ⚠ Measured 2026-09-10: this order holds by QUEUE ORDER, not by a hop-back — P2's continuation queues
#// the defeat prompt on P1's queue before the reaction collector runs, so Bossk's lands behind it. Giving
#// the collector the actor hop-back too changed nothing and was reverted. Kept as a guard on the order.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P2
- P2>AnswerDecision:myGroundArena-1

## EXPECT
TURNPLAYER:1
P1DECISIONTOOLTIP:Defeat_Battlefield_Marine?_If_you_do,_its_controller_creates_a_Beast_token.

---

# AnEventPlayReaction_ResolvesAfterTheEvent
#// HMW_058 — the same board carried one step further: after YES, the Marine is defeated and P2 has its
#// Beast, and only NOW is Bossk's reaction offered.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P2
- P2>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:HMW_T03
P2DISCARDUNIT:0:CARDID:SOR_095
P1DECISIONTOOLTIP:Deal_2_damage_to_a_unit

---

# Decline_NoDefeatNoBeast
#// HMW_058 — the DECLINE ("you may"): the unit survives, no Beast is created, the action still closes.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2DISCARDCOUNT:0
TURNPLAYER:2

---

# IfYouDo_TheDefeatIsRefused_NoBeast
#// ⚠ HMW_058 — "IF YOU DO" measures the OUTCOME. P2's only unit is SHD_187 Lurking TIE Phantom, which
#// "can't be … defeated … by enemy card abilities": P1 says YES, the defeat is refused, the Phantom stays,
#// and P2 gets NO Beast. (An implementation that creates the Beast on the YES alone fails here.)

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2GROUNDARENACOUNT:0
TURNPLAYER:2

---

# IfYouDo_YourOwnPhantomIsNotProtectedFromYou
#// HMW_058 — the control for the section above: the Phantom's protection is against ENEMY abilities, so
#// the caster CAN defeat their OWN Phantom — it is defeated and P1 gets the Beast. P2 also has a unit, so
#// P1 picks themselves from a real menu.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP1SpaceArena: SHD_187:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P1
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P2GROUNDARENACOUNT:1

---

# ATokenUnitIsANonLeaderUnit
#// HMW_058 — VALUE CLASS: a token unit is a non-leader unit. P2's only unit is a Battle Droid token; it is
#// defeated (tokens cease, nothing reaches the discard) and P2 still creates the Beast.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_T03
P2DISCARDCOUNT:0

---

# NoNonLeaderUnitAnywhere_NothingHappens
#// HMW_058 — NO VALID TARGET: nobody controls a non-leader unit, so no player can be chosen and the event
#// resolves to nothing — no prompt, no Beast, one action close.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
TURNPLAYER:2

---

# StolenUnit_OwnerGetsTheCardControllerGetsTheBeast
#// HMW_058 — CONTROL. P2 controls a Marine that P1 OWNS. "A non-leader unit THEY CONTROL" makes P2
#// eligible; the defeated card goes to its OWNER's discard (P1: the event + the Marine), while "THAT
#// PLAYER" — P2, who chose — creates the Beast.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP2GroundArenaControlled: SOR_095:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_T03
P1DISCARDCOUNT:2
P2DISCARDCOUNT:0
P1GROUNDARENACOUNT:0

---

# RequestBoundary_BeforeTheDefeatAnswer
#// HMW_058 — the REQUEST-BOUNDARY cell: the chosen unit and "that player" must survive into the request
#// that answers the defeat prompt (they ride the continuation's Param, never a global).

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: HMW_058
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:1:CARDID:HMW_T03
P2DISCARDUNIT:0:CARDID:SOR_095
TURNPLAYER:2

---

# TwinSuns_PlayerOfferSpansEveryEligibleSeat
#// ⚠ HMW_058 — "A PLAYER" at four seats. Seats 2 and 3 control a non-leader unit; seat 4 controls none and
#// the caster (seat 1) controls none — so the menu is exactly P2 and P3. Cannot pass at two seats.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_058
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONNOT:P4
P1OPTIONNOT:P1

---

# TwinSuns_TheFarSeatPicksAndGetsTheBeast
#// ⚠ HMW_058 — the same board resolved on seat 3: P1 picks P3, P3's only unit is picked automatically,
#// P1 defeats it and P3 — not seat 2 — creates the Beast.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_058
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P1>AnswerDecision:YES

## EXPECT
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:HMW_T03
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080

---

# TeamSuns_ATeammateIsAPlayer
#// ⚠ HMW_058 — "a PLAYER", not "an opponent": in a 2v2 the caster's teammate (seat 3) is a legal pick.
#// An "an opponent" picker would drop them from the menu.

## GIVEN
CommonSetup: ybw/rrk/{myResources:2}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_058
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1OPTIONHAS:P2
P1OPTIONHAS:P3
