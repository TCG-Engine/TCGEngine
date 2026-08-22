# WhenPlayed_TheOPPONENTGetsTheBeast_NotYou
#// HMW_152 Babwa Venomor - Burning Kashyyyk (Aggression/Villainy, cost 2, 4/4 Ground, Imperial, unique)
#// Text: Overwhelm
#//       When Played: An opponent creates a Beast token.
#//
#// The whole card is a DRAWBACK attached to an efficient body: a 2-cost 4/4 with Overwhelm that hands
#// the other side a free 3/3 Creature (HMW_T03). So the load-bearing thing to prove is WHO ends up with
#// the token — an implementation that created it for the caster would look "working" in a screenshot
#// and be the exact inverse of the card.
#//
#// COVERAGE: offer=TwinSuns_OffersEXACTLYTheThreeOpponents (P1OPTIONHAS/NOT on the picker) ·
#//           negative=this section (caster's arena holds ONLY Babwa) + PlayedByP2_ThenP1GetsTheBeast ·
#//           boundary=N/A (no threshold — exactly one token, no count or cost gate) ·
#//           control=N/A (the token is created at play time under the seat that PLAYED Babwa; a later
#//             control change of Babwa cannot re-fire a When Played, and the token is a normal unit
#//             from then on) · reqboundary=TwinSuns_AcrossTheRequestBoundary ·
#//           decline=N/A (printed "creates", not "may create" — mandatory, and the opponent picker is
#//             a mandatory OPTIONCHOOSE with no pass)
#//
#// P1 plays Babwa. P1's ground holds Babwa and NOTHING else; P2's holds exactly one Beast.
#// ⚠ Assert BOTH arenas. "P2 has a Beast" alone passes against an implementation that gives one to
#//   everybody, and "P1 has only Babwa" alone passes against one that creates nothing at all.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
WithActivePlayer: 1
WithP1Hand: HMW_152
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_152
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_T03
P2GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# WhenPlayed_PlayedByP2_ThenP1GetsTheBeast
#// The MIRROR, and the section that proves "an opponent" is resolved relative to whoever played Babwa
#// rather than hardcoded to seat 2. A `OtherPlayer(2)` bug is invisible from P1's side alone.
#// ⚠ No P1OnlyActions — P1 must genuinely pass so the turn reaches P2 and P2's play is a real action.

## GIVEN
CommonSetup: bbw/rrk/{theirResources:4}
WithActivePlayer: 1
WithP2Hand: HMW_152
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_152
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03

---

# BeastIsCreatedInTHEIRFrame_TheirEntersReadyAuraApplies
#// ⚠ THE SHARPEST SECTION IN THE FILE. "An opponent CREATES a Beast" means the opponent is the CREATOR,
#// not merely the recipient — so every replacement and aura that keys off "tokens YOU create" is read
#// off THEIR board, not the caster's.
#// TWI_203 Chancellor Palpatine (2/6 Ground, Cunning): "Each token unit you create enters play ready."
#// P2 controls one, so P2's Beast enters READY. An implementation that created the token under the
#// CASTER and then handed it over produces an EXHAUSTED Beast here and passes every other section in
#// this file — this is the only cell that separates the two.
#// Its control is the first section, where P2 has no aura and the Beast is EXHAUSTED.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
WithActivePlayer: 1
WithP1Hand: HMW_152
WithP2GroundArena: TWI_203:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:HMW_T03
P2GROUNDARENAUNIT:1:READY

---

# TwinSuns_OffersEXACTLYTheThreeOpponents
#// ⚠ THE OFFER CELL — the most-skipped of the matrix, and here it is the entire Twin Suns half of the
#// card. With three opponents "AN opponent" is a real CHOICE, made by Babwa's controller.
#// The decision is left PENDING and the pool is read off the prompt itself; answering a seat proves the
#// branch works but says nothing about which seats were on the menu.
#// ⚠ P1OPTIONNOT:P1 is the load-bearing half: "an opponent" can never include yourself, and a picker
#//   built from GetLiveSeatsArray() instead of OpponentsOf() offers the caster their own seat.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_152
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1

---

# TwinSuns_ChoosingSeatThree_ONLYP3GetsIt
#// Answer the picker with P3. Exactly one Beast exists and it is P3's — the other two opponents get
#// nothing, which is what separates "an opponent" from "each opponent".
#// ⚠ Seat 3 is also unreachable for any two-seat implementation, so this is the Twin Suns hardcode cell.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_152
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:HMW_T03
P2GROUNDARENACOUNT:0
P4GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_152

---

# TwinSuns_ChoosingSeatFour_ONLYP4GetsIt
#// The second Twin Suns seat, and not redundant with P3: a picker that ignores the answer and always
#// takes the FIRST opponent passes the P3 section by coincidence whenever P3 happens to be first in
#// the list. Two different answered seats is the cheapest way to prove the answer is actually read.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_152
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P4

## EXPECT
P4GROUNDARENACOUNT:1
P4GROUNDARENAUNIT:0:CARDID:HMW_T03
P2GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:0

---

# TwinSuns_AcrossTheRequestBoundary
#// ⚠ THE REQUEST-BOUNDARY CELL. The opponent picker ENDS the request: the seat is chosen in one HTTP
#// request and the token is created by a handler that resumes in a FRESH process. Anything the ability
#// held in an in-memory global between the two is empty by then and the card silently does nothing —
#// the failure mode that a single-process test cannot see by construction.
#// Identical to ChoosingSeatThree plus one SimulateRequestBoundary before the answer.

## GIVEN
CommonSetup: rrk/bbw/{myResources:4}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: HMW_152
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:P3

## EXPECT
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:HMW_T03
P2GROUNDARENACOUNT:0
P4GROUNDARENACOUNT:0

---

# Overwhelm_ExcessDamageHitsTheBase
#// The keyword half. Babwa is in $Overwhelm_Cards (generated from the card text), so there is no code
#// to write — but membership is a LITERAL, and a wrong-literal keyword gate is exactly the shape that
#// stays green for a whole set because every fixture was seeded from the handler.
#// Babwa (4 power) attacks a Battlefield Marine already carrying 2 damage (3/3 → 1 HP left): 1 finishes
#// it and the other 3 overflow to the base.
#// ⚠ Placed READY in the arena rather than played: a unit played this turn is exhausted and cannot
#//   attack, and this section is about the keyword, not the When Played.

## GIVEN
CommonSetup: rrk/bbw/{}
WithActivePlayer: 1
WithP1GroundArena: HMW_152:1:0
WithP2GroundArena: SOR_095:1:2
WithP1Deck: [SOR_095 SOR_046 SEC_080]
WithP2Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
