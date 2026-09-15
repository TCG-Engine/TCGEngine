# Accept_TakesThree_AndGetsAShield
#// HMW_079 Radiant VII, Negotiating For Naboo — Unit (Space) 5/7, cost 6, [Vigilance][Heroism],
#// Republic/Vehicle/Transport, unique.
#// "When Played: You may deal 3 damage to this unit. If you do, give a Shield token to it."
#//
#// COVERAGE: offer=Offer_TheYesNoIsPending (the prompt is asserted pending; there is no target pool —
#//                 "this unit" is the only object the ability can touch)
#//           decline=Decline_NoDamage_NoShield
#//           boundary=N/A (structural — no threshold or count; the 3 and the one Shield are fixed)
#//           control=N/A (structural — a When Played resolves once, on entry, for whoever played it; no
#//                   owner- or controller-scoped zone is named)
#//           reqboundary=Accept_AcrossARequestBoundary (the unit rides the continuation by UniqueID)
#//           modes=2P only (no player reference, no friendly/enemy wording)
#//
#// ★ "IF YOU DO" IS THE CHOICE, NOT THE DAMAGE LANDING — CR 9.2: "If a replacement effect replaces the
#// resolution of the text before 'If you do' … the controlling player is still considered to have
#// resolved that text." A Shield's prevention is a replacement effect (CR 843), and the Malakili ruling
#// (07/14/2025) says the same of prevented self-damage. So a prevented 3 still earns the Shield —
#// AlreadyShielded_ThePreventedDamageStillEarnsTheShield pins it. HMW is a preview set (no ruling of its
#// own); this is the CR reading.
#//
#// P1 plays it for 6 and accepts: 3 damage on a 7-HP unit (it survives on 4 remaining) and a Shield.
#// Its stats are untouched by either (a Shield is +0/+0).

## GIVEN
CommonSetup: byw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_079

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_079
P1SPACEARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:7
P1RESAVAILABLE:0
P1NODECISION

---

# Offer_TheYesNoIsPending
#// The printed "You may" — the offer is a real prompt, left pending here so its existence is the
#// assertion (answering it proves only the branch). Nothing has happened to the unit yet.

## GIVEN
CommonSetup: byw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_079

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Deal_3_damage_to_Radiant_VII_to_give_it_a_Shield?
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:SHIELDCOUNT:0

---

# Decline_NoDamage_NoShield
#// Declining deals nothing — and, because the Shield hangs off "If you do", gives nothing either.

## GIVEN
CommonSetup: byw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_079

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_079
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# AlreadyShielded_ThePreventedDamageStillEarnsTheShield
#// ★ CR 9.2 — THE DISCRIMINATING SECTION. LOF_225 Three Lessons plays it with a Shield (and an
#// Experience token) already on it, so the 3 it deals itself is PREVENTED and that Shield is spent. The
#// "If you do" still resolves: a NEW Shield arrives. End state: no damage, one Shield, the Experience.
#// An implementation that gave the Shield only when damage actually landed ends with NO Shield.
#// ⚠ It is GREEN against an unimplemented card too (the Three Lessons Shield is simply never spent), so
#// it does not prove the ability fires — the positives do. What it pins is the CR 9.2 reading, and it
#// reds (with the Mandalorian section) when the Shield is gated on the damage landing. Mutation-verified.
#// 2 for Three Lessons + 6 = 8 resources.

## GIVEN
CommonSetup: byw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: [LOF_225 HMW_079]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_079
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:POWER:6
P1SPACEARENAUNIT:0:HP:8

---

# Accept_AcrossARequestBoundary
#// The YESNO ends the request; the answer arrives in a fresh process. The continuation carries the unit
#// by UniqueID in its own param, so nothing is held in memory across the boundary.

## GIVEN
CommonSetup: byw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_079

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_079
P1SPACEARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# TheShieldIsPermanent_ItSurvivesTheRegroup
#// A Shield token is not a "for this phase" grant. After a full regroup it is still on the unit, and so
#// is the damage (damage does not heal at regroup either). Both decks are seeded so the regroup draw is
#// not a deck-out.

## GIVEN
CommonSetup: byw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_079
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_079
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:0:READY

---

# PlayedByNightbrother_FromTheDiscard_StillOffersAndEntersReady
#// Dispatch path: played by ANOTHER card's ability. HMW_204 Nightbrother plays it from the discard at 3
#// less and "enters play ready". Its own When Played still fires, the Shield still arrives, and it is
#// ready. Aspects: a Cunning/Heroism leader on a Vigilance base leaves only Nightbrother's Villainy pip
#// uncovered (+2), so Nightbrother costs 9 and Radiant VII 6 - 3 = 3: 12 resources, 0 left.
#// Nightbrother sits at space 0, Radiant VII at space 1.

## GIVEN
CommonSetup: byw/rrk/{myResources:12}
P1OnlyActions: true
WithP1Hand: HMW_204
WithP1Discard: HMW_079

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:HMW_079
P1SPACEARENAUNIT:1:READY
P1SPACEARENAUNIT:1:DAMAGE:3
P1SPACEARENAUNIT:1:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_204
P1SPACEARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0

---

# MandalorianPreventsTheThree_TheShieldStillArrives
#// CR 9.2 through a DIFFERENT replacement: ASH_062 The Mandalorian ("If damage would be dealt to another
#// friendly unit, you may defeat a Shield token on this unit. If you do, prevent that damage"). P1 spends
#// Mando's Shield to prevent the 3 — and Radiant VII still gets its own Shield.

## GIVEN
CommonSetup: byw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_079
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_079
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_062
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# MandalorianOfferDeclined_TheShieldArrivesAFTERTheThree
#// ⚠ THE ORDERING SECTION. The Mandalorian's offer DEFERS the 3 behind a prompt. P1 declines it, so the 3
#// lands — and only then does Radiant VII get its Shield, which is why the Shield is a separate step
#// queued behind the damage. Given inline, the new Shield would already be on the unit when the deferred
#// 3 resolved and would eat it: 0 damage and no Shield. Mando keeps his own Shield.

## GIVEN
CommonSetup: byw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_079
WithP1GroundArena: ASH_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_079
P1SPACEARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# Accept_TurnPassesExactlyOnce
#// No P1OnlyActions: the turn really alternates. The YESNO and the queued Shield step both sit inside the
#// play's own action, so one play hands the turn over exactly once.

## GIVEN
CommonSetup: byw/rrk/{myResources:6}
WithActivePlayer: 1
WithP1Hand: HMW_079

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:DAMAGE:3
TURNPLAYER:2
