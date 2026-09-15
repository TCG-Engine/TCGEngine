# OnAttack_DiscardTwoKeepers_PlusFifteenAndOverwhelm
#// HMW_041 Keeper of Skara Nal, Awoken — Unit (Ground) 5/8, cost 6, [Aggression][Vigilance],
#// Droid/Vehicle/Walker, unique.
#// Text: Restore 2
#//       On Attack: You may discard 2 cards named Keeper of Skara Nal from your hand. If you do, this
#//       unit gets +15/+0 and gains Overwhelm for this attack.
#// COVERAGE: offer=OnlyOneKeeperInHand_NoOfferAtAll + EmptyHand_NoOfferAtAll +
#//                 OtherCardsInHandDoNotCount (the NAME filter) — the offer is gated three ways and
#//                 each gate has its own section ·
#//           decline=Decline_NoBuffNoDiscard (no discard, no Overwhelm) +
#//                 Decline_TheAttackStillHitsForFive (the POWER half — see that section's note: a
#//                 small defender cannot observe it, so the first decline section alone is not
#//                 enough and a buff-on-decline bug passed it) ·
#//           boundary=ThreeKeepersInHand_DiscardsExactlyTwo (exactly 2, not "all matching") +
#//                 BuffAndOverwhelmExpire_TheNextAttackIsAPlainFive (the DURATION cell — "for this
#//                 attack" proven by re-reading POWER after combat AND by the second attack spilling
#//                 no excess) ·
#//           control=N/A — "your hand" and "this unit" are both self-scoped; the card moves nothing
#//                 between players and has no owner-scoped zone ·
#//           reqboundary=RequestBoundary_OfferSurvivesTheDecision ·
#//           modes=2P ONLY. The text names no player and uses neither "friendly" nor "enemy" — "your
#//                 hand" and "this unit" are self-scoped, so Premier, Twin Suns and Team Suns are the
#//                 same code path here and a far-seat section could never fail.
#//
#// ⚠ PREVIEW SET — HMW is absent from card-specific-rulings.md, so these readings are CR + analogue:
#//   • "cards NAMED Keeper of Skara Nal" matches by TITLE, not CardID — the convention IC27_078 Anakin
#//     uses for "a card named Darth Vader". HMW_041 is unique, so the copy attacking is in play and the
#//     two being discarded are the deck's other copies sitting in hand; that is the whole design.
#//   • "You may discard 2" is an all-or-nothing COST: with fewer than two in hand there is no offer at
#//     all, rather than an offer that discards one and fizzles.
#//   • "If you do" gates BOTH riders — a decline leaves the unit a plain 5/8 with no Overwhelm.
#//
#// THE POSITIVE. Two spare Keepers in hand; the 5/8 attacks a vanilla 2/2 as a 20/8 with Overwhelm, so
#// 2 damage kills the defender and the other 18 spill into the base. Restore 2 also fires (the base is
#// undamaged, so it clamps at 0 and stays there under the 18).
## GIVEN
CommonSetup: rbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP1Hand: [HMW_041 HMW_041]
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P2GROUNDARENACOUNT:0
P2BASEDMG:18
P1GROUNDARENAUNIT:0:CARDID:HMW_041
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# OnAttack_Decline_NoBuffNoDiscard
#// "You MAY discard" — the decline branch. Nothing is discarded, the unit attacks as a plain 5/8, and
#// with no Overwhelm the 3 excess damage over the 2/2 defender is LOST rather than spilling.
## GIVEN
CommonSetup: rbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP1Hand: [HMW_041 HMW_041]
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO
## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:0
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:POWER:5

---

# Decline_TheAttackStillHitsForFive
#// The OTHER half of the decline, and the one the 2/2 board above CANNOT see. A declined attack must
#// deal the unit's PRINTED 5, but power is only observable while the attack is resolving: the
#// attack-duration buff is gone by the time any POWER assertion is read, and with no Overwhelm the
#// excess over a small defender is discarded either way. So a handler that applied +15/+0 on a decline
#// passes every other section in this file — measured, not hypothetical: that mutation came back
#// GREEN and this section is the one written to catch it.
#// SOR_046 Consular Security Force is 3/7, so a plain 5 leaves it alive on 5 damage while a buffed 20
#// would kill it outright.
## GIVEN
CommonSetup: rbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP1Hand: [HMW_041 HMW_041]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
P1HANDCOUNT:2

---

# OnAttack_OnlyOneKeeperInHand_NoOfferAtAll
#// THE COUNT GATE. One copy is one short of the cost, so there is no offer — not an offer that
#// discards the single copy and gives nothing. The attack proceeds as a plain 5/8 and the card stays
#// in hand. Without this section a handler that ignored the "2" would pass every other section here.
## GIVEN
CommonSetup: rbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP1Hand: HMW_041
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P2BASEDMG:0
P2GROUNDARENACOUNT:0

---

# OnAttack_EmptyHand_NoOfferAtAll
#// The other cannot-pay branch: an empty hand is a different code path from "some cards, none of them
#// Keepers", and both have to skip the prompt.
## GIVEN
CommonSetup: rbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P2BASEDMG:0

---

# OnAttack_OtherCardsInHandDoNotCount
#// THE NAME FILTER. A full hand of four cards — one Keeper and three unrelated ones — still cannot pay
#// a cost of two Keepers. A handler that counted "2 cards in hand" instead of "2 cards named Keeper of
#// Skara Nal" offers here and is caught by nothing else in the file.
## GIVEN
CommonSetup: rbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP1Hand: [HMW_041 SOR_095 SOR_046 SHD_110]
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P1HANDCOUNT:4
P1DISCARDCOUNT:0
P2BASEDMG:0

---

# OnAttack_ThreeKeepersInHand_DiscardsExactlyTwo
#// "discard 2" is exactly two, not "all matching". Three in hand leaves one behind.
## GIVEN
CommonSetup: rbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP1Hand: [HMW_041 HMW_041 HMW_041]
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:2
P2BASEDMG:18

---

# BuffAndOverwhelmExpire_TheNextAttackIsAPlainFive
#// THE DURATION CELL, asserted two ways because the outcome alone cannot tell a per-attack buff from a
#// permanent one. After the buffed attack resolves the unit reads its PRINTED power again, and its
#// second attack — against an identical 2/2, with an empty hand so the ability cannot re-arm — spills
#// NO excess, proving the Overwhelm grant expired too.
#// The unit is readied between the two attacks by crossing the round boundary.
## GIVEN
CommonSetup: rbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP1Hand: [HMW_041 HMW_041]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: [SHD_110:1:0 SHD_110:1:0]
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:18
P2GROUNDARENACOUNT:0

---

# RequestBoundary_OfferSurvivesTheDecision
#// The request-boundary cell: the attacker's mzID is written into the continuation before the YESNO is
#// answered and read back after it, so it must be serialized rather than held in memory. Same GIVEN
#// and EXPECT as the positive with one SimulateRequestBoundary inserted before the answer.
## GIVEN
CommonSetup: rbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP1Hand: [HMW_041 HMW_041]
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P2BASEDMG:18
P2GROUNDARENACOUNT:0

---

# Restore2_HealsYourBaseWhenItAttacks
#// The keyword half. Restore 2 is generated (GeneratedKeywordCode carries 'HMW_041' => 2), so this is
#// a verify-only section: a base on 5 damage is healed to 3 when the Keeper attacks. The ability is
#// declined so the heal is observed on its own.
## GIVEN
CommonSetup: rbk/ggw/{myResources:6; myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP1Hand: [HMW_041 HMW_041]
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO
## EXPECT
P1BASEDMG:3

---

# Offer_NothingIsSpentOrBuffedUntilTheChoiceIsCommitted
#// The "start paying, then back out" cell. The cost is one all-or-nothing commit — both copies share
#// the title, so WHICH two are spent is immaterial and there is no per-card picker, hence no half-paid
#// state to strand. Backing out after starting is therefore the plain decline
#// (OnAttack_Decline_NoBuffNoDiscard / Decline_TheAttackStillHitsForFive: both copies stay in hand, no
#// +15, no Overwhelm). What this section adds is the moment BEFORE the answer: with the offer still
#// pending, nothing has been discarded and the attacker is still a plain 5 — so a handler that spent
#// the cost or applied the rider eagerly, and only "refunded" on a decline, is caught here.
## GIVEN
CommonSetup: rbk/ggw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_041:1:0
WithP1Hand: [HMW_041 HMW_041]
WithP2GroundArena: SHD_110:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Discard_2_Keeper_of_Skara_Nal_for_+15/+0_and_Overwhelm?
P1HANDCOUNT:2
P1DISCARDCOUNT:0
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
P2GROUNDARENACOUNT:1
P2BASEDMG:0
