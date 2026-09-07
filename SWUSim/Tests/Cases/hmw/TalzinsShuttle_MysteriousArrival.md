# WhenPlayed_OpponentPlayedTwo_GivesTwoWeaknessTokens
#// HMW_040 Talzin's Shuttle, Mysterious Arrival — Unit (Space) 2/4, cost 3, [Vigilance][Aggression],
#// Night/Vehicle/Transport, unique.
#// Text: Raid 1 (This unit gets +1/+0 while attacking.)
#//       When Played: If an opponent played 2 or more cards this phase, you may give 2 Weakness
#//       tokens to a unit.
#// COVERAGE: offer=FriendlyUnitIsALegalTarget (unqualified "a unit" spans BOTH sides) ·
#//           decline=Decline_NoTokens ·
#//           boundary=OpponentPlayedOnlyOne_NoOfferAtAll (the NEGATIVE that makes the threshold
#//                 load-bearing) + OpponentPlayedThree_StillQualifies (">= 2", not "== 2") +
#//                 TwoTokensNotOne_ShrinkDefeatsATwoHpUnit (the AMOUNT — a one-token bug leaves the
#//                 2/2 alive at 1/1 and every UPGRADECOUNT assertion still reads plausibly) ·
#//           control=N/A — the tokens are given by the SHUTTLE'S controller to any unit on the table;
#//                 there is no owner-scoped zone, and the card moves nothing between players ·
#//           reqboundary=RequestBoundary_OfferSurvivesTheDecision ·
#//           modes=Twin Suns (TwinSuns_AFarSeatOpponentsPlaysCount) + Team Suns
#//                 (TeamSuns_ATeammatesPlaysDoNotCount) — "an OPPONENT played" is a team-relative
#//                 player reference, so both axes discriminate: the far-seat one cannot pass at two
#//                 seats, and the team one fails if the scan is "every other seat".
#//
#// ⚠ PREVIEW SET — HMW is absent from card-specific-rulings.md, so the readings below come from the CR
#// plus released analogues, not from a ruling:
#//   • "an opponent played 2 or more cards" is a CONDITION, not a prompt — it asks whether ANY single
#//     opponent has reached 2, so it is an existential over OpponentsOf(), never a sum across them and
#//     never OtherPlayer(). Two opponents on 1 card each do NOT satisfy it (asserted below).
#//   • "this phase" = the action phase. SWU_CARDS_PLAYED is cleared in RegroupPhaseStart, which is the
#//     same window.
#//   • "a unit" is unqualified — friendly and enemy, ground and space, per the SWUAllUnits reading
#//     HMW_207 Maim and HMW_062 Nuvo Vindi both use.
#//   • the condition is read when the When Played RESOLVES, so the Shuttle's own play does not count
#//     toward it (it is the caster's card, not an opponent's) — no self-interference either way.
#//
#// This first section drives the counter through REAL PLAYS rather than a seeded flag, so the bump
#// sites are exercised at least once; the combinatorial sections below seed SWU_CARDS_PLAYED directly.
#// P2 plays two Battlefield Marines (P1 passes between them so the turn comes back round), then P1
#// plays the Shuttle and weakens P2's Consular Security Force: 3/7 -> 1/5 with two HMW_T02 attached.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; theirResources:8; myhandCardIds:HMW_040; theirhandCardIds:SOR_095,SOR_095}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P1>Pass
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:UPGRADE:1:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5

---

# WhenPlayed_OpponentPlayedOnlyOne_NoOfferAtAll
#// THE NEGATIVE. One card short of the threshold, the When Played does nothing at all — not an offer
#// the player must decline, no offer. Without this section a handler that ignored the condition
#// entirely would pass every other section in the file.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; theirResources:8; myhandCardIds:HMW_040; theirhandCardIds:SOR_095,SOR_095}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:CARDID:HMW_040

---

# WhenPlayed_Decline_NoTokens
#// "you MAY give" — the decline branch. MZMAYCHOOSE declines with `-`. The Shuttle still arrives.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myhandCardIds:HMW_040}
SkipPreGame: true
P1OnlyActions: true
WithP2GlobalEffect: [SWU_CARDS_PLAYED SWU_CARDS_PLAYED]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:CARDID:HMW_040

---

# WhenPlayed_FriendlyUnitIsALegalTarget
#// "a unit" names no controller, so a FRIENDLY unit is a legal target — including the Shuttle itself,
#// which is already in play when its own When Played resolves. Weakening your own board is printed
#// behaviour, not an oversight. The Shuttle is 2/4; two Weakness tokens make it 0/2.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myhandCardIds:HMW_040}
SkipPreGame: true
P1OnlyActions: true
WithP2GlobalEffect: [SWU_CARDS_PLAYED SWU_CARDS_PLAYED]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_040
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:POWER:0
P1SPACEARENAUNIT:0:HP:2

---

# TwoTokensNotOne_ShrinkDefeatsATwoHpUnit
#// THE AMOUNT CELL, and the reason it is not asserted with UPGRADECOUNT alone. The shared
#// GIVE_WEAKNESS continuation attaches ONE token; "amount" is silently ignored by it (it is not in
#// SWUOfferUnitTarget's $amountTaking list), so a card that just passes amount=>2 gives one token and
#// every count/stat assertion still reads plausibly on a big body.
#// SHD_110 Warzone Lieutenant is a vanilla 2/2: two Weakness tokens take it to 0/0 and the shrink
#// sweep defeats it, while ONE leaves it standing at 1/1. Only the death discriminates.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myhandCardIds:HMW_040}
SkipPreGame: true
P1OnlyActions: true
WithP2GlobalEffect: [SWU_CARDS_PLAYED SWU_CARDS_PLAYED]
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# WhenPlayed_OpponentPlayedThree_StillQualifies
#// "2 or MORE" — the threshold is >=, not ==. Three plays still arms the offer.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myhandCardIds:HMW_040}
SkipPreGame: true
P1OnlyActions: true
WithP2GlobalEffect: [SWU_CARDS_PLAYED SWU_CARDS_PLAYED SWU_CARDS_PLAYED]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:1

---

# TwoOpponentsOnOneCardEach_DoesNotQualify
#// "AN opponent played 2 or more" is per-opponent, not a table total. Two opponents holding one play
#// each sum to 2 but no single opponent reached the threshold, so the ability does nothing. This is
#// the section a sum-across-opponents implementation cannot pass, and it is invisible at two seats.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myhandCardIds:HMW_040}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP2GlobalEffect: SWU_CARDS_PLAYED
WithP3GlobalEffect: SWU_CARDS_PLAYED
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# TwinSuns_AFarSeatOpponentsPlaysCount
#// FOUR SEATS. The only opponent over the threshold is seat 3; the adjacent seat 2 has played nothing.
#// OtherPlayer() answers 2 for seat 1, so a two-seat implementation sees 0 and never offers — this
#// section cannot pass at two seats. The ACTOR is seat 1 (CommonSetup dresses seats 1-2 only; seats
#// 3/4 carry boards and flags, never the acting role).
#// ⚠ Above two seats the offer is minted SEAT-TAGGED (`p2GroundArena-0`), not `theirGroundArena-0` —
#// "their" only names a single opponent while there are only two seats.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myhandCardIds:HMW_040}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithP3GlobalEffect: [SWU_CARDS_PLAYED SWU_CARDS_PLAYED]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5

---

# TeamSuns_ATeammatesPlaysDoNotCount
#// TEAM SUNS (seats 1+3 vs 2+4). P1's TEAMMATE at seat 3 has played two cards; both actual opponents
#// have played none. "An opponent" excludes a teammate, so the ability does nothing. A scan written as
#// "every other seat" passes every other section in this file and fails only here.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myhandCardIds:HMW_040}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3GlobalEffect: [SWU_CARDS_PLAYED SWU_CARDS_PLAYED]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# RequestBoundary_OfferSurvivesTheDecision
#// The request-boundary cell: the condition is evaluated before the target choose is answered, so the
#// pending offer must survive a fresh request. Same GIVEN and EXPECT as the positive, with one
#// SimulateRequestBoundary inserted before the answer.
## GIVEN
CommonSetup: brk/ggw/{myResources:6; myhandCardIds:HMW_040}
SkipPreGame: true
P1OnlyActions: true
WithP2GlobalEffect: [SWU_CARDS_PLAYED SWU_CARDS_PLAYED]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5

---

# Raid1_PlusOnePowerWhileAttacking
#// The keyword half. Raid 1 is generated (GeneratedKeywordCode has HMW_040 => 1), so this is a
#// verify-only section: the 2/4 Shuttle hits an undefended base for 3, not 2.
## GIVEN
CommonSetup: brk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_040:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:3
