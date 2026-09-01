# WhenPlayed_Reveal3Vigilance_Gives3Experience
#// SOR_035 Lieutenant Childsen "Death Star Prison Warden" (Unit, cost 4, [Vigilance][Villainy],
#// Imperial/Official, UNIQUE, 2/2 Ground) — "Sentinel / When Played: Reveal up to 4 [Vigilance] cards
#// from your hand. For each card revealed this way, give an Experience token to this unit."
#// COVERAGE: offer=Offer_RevealPoolIsVIGILANCECardsInHandOnly (MZMULTICHOOSE menu asserted on a
#//           PENDING decision — a non-Vigilance hand card is the excluded control, a dual-aspect card
#//           the included one) + RevealsFromHANDOnly_ResourcesAndDiscardAreNotSearched (the ZONE half
#//           of the same restriction: eight Vigilance resources and two in the discard are invisible
#//           to it) · decline=WhenPlayed_DeclineReveal_NoExperience ("up to 4" includes zero) ·
#//           boundary pair=WhenPlayed_RevealFewer_GivesFewerExperience (2 revealed → 2 tokens) vs
#//           WhenPlayed_Reveal3Vigilance_Gives3Experience (3 → 3) vs WhenPlayed_CapsAtFour (5 offered
#//           → 4, the hard cap) vs WhenPlayed_NoVigilanceInHand_NoOp (0) · control
#//           change=ControlChange_SentinelGuardsTheCONTROLLER (the keyword protects the CONTROLLER's
#//           base, and the Experience tokens ride along) + ControlChange_ADefeatedStolenChildsenGoes
#//           ToItsOWNERSDiscard (the card leaves play to its OWNER's zone) · request
#//           boundary=structural in every revealing section — the play is one request and the
#//           MZMULTICHOOSE answer is a SEPARATE one, so the Vigilance pool is rebuilt from serialized
#//           hand state after the just-played Childsen has been cleaned out of it;
#//           Offer_RevealPoolIsVIGILANCECardsInHandOnly reads that rebuilt pool still pending.
#// This section: When Played: reveal up to 4
#//   [Vigilance] cards from hand; give an Experience token to this unit per card revealed. Reveal 3 of 3
#//   → 3 Experience (2/2 + 3 = 5/5); revealed cards STAY in hand (reveal, not discard).

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1HANDCOUNT:3

---

# WhenPlayed_RevealFewer_GivesFewerExperience
#// Reveal only 2 of 4 available Vigilance cards → exactly 2 Experience (count logic distinguishes
#//   "per card revealed" from "one per Vigilance card in hand").

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1HANDCOUNT:4

---

# WhenPlayed_DeclineReveal_NoExperience
#// "up to 4" → the player may reveal NONE (decline). 0 Experience; unit stays base 2/2.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_063
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:2

---

# WhenPlayed_NoVigilanceInHand_NoOp
#// Hand has only non-Vigilance cards (SOR_095 Command/Heroism, SEC_080 Command/Villainy) → no cards to
#//   reveal → no decision, 0 Experience (clean fizzle).

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_095
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# WhenPlayed_CapsAtFour
#// "up to 4" is a hard cap. Five Vigilance cards in hand, answer all five → only 4 Experience (the
#//   resolver validates the count itself; the harness does not enforce the MZMULTICHOOSE max).

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063
WithP1Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1&myHand-2&myHand-3&myHand-4

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1HANDCOUNT:5

---

# Offer_RevealPoolIsVIGILANCECardsInHandOnly
#// THE OFFER CELL, asserted as a MENU on a PENDING MZMULTICHOOSE. "Reveal up to 4 [VIGILANCE] cards
#// FROM YOUR HAND" restricts the pool twice — by aspect and by zone — and both are exercised here.
#// After Childsen is played the remaining hand is
#//   myHand-0 SOR_063 Cloud City Wing Guard ([Vigilance])          → legal
#//   myHand-1 SOR_038 Count Dooku ([Vigilance][Villainy], DUAL)    → legal (a dual-aspect card is a
#//                                                                   [Vigilance] card)
#//   myHand-2 SEC_080 Imperial Dark Trooper ([Command][Villainy])  → EXCLUDED, no Vigilance
#// Two legal entries are what keeps the choice worth asserting; the resolutions live in the sections
#// above and in DualAspectVigilanceCardCounts below.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_063
WithP1Hand: SOR_038
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# DualAspectVigilanceCardCounts
#// VALUE-CLASS VARIANT — "[Vigilance] cards" means cards that HAVE the Vigilance aspect, not cards
#// that are mono-Vigilance. SOR_038 Count Dooku is [Vigilance][Villainy]; revealing it yields one
#// Experience token (2/2 → 3/3), and the [Command][Villainy] card sitting beside it in hand is the
#// control that proves the aspect test is real. Both cards stay in hand — this is a reveal, not a
#// discard.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_035
WithP1Hand: SOR_038
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P1HANDCOUNT:2

---

# RevealsFromHANDOnly_ResourcesAndDiscardAreNotSearched
#// ZONE SCOPE. "Reveal up to 4 [Vigilance] cards FROM YOUR HAND" — nowhere else. P1's hand holds a
#// single non-Vigilance card while EIGHT [Vigilance] cards sit in the resource zone (SOR_063) and two
#// more are in the discard pile. The pool is therefore empty: no decision is raised and Childsen
#// enters at his printed 2/2 with no Experience.
#// Distinct from WhenPlayed_NoVigilanceInHand_NoOp, which has no Vigilance card ANYWHERE — here the
#// cards exist and are deliberately out of zone, which is the only way the zone restriction can be
#// shown to be load-bearing.

## GIVEN
CommonSetup: bbk/rrk/{}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8:SOR_063
WithP1Discard: [SOR_063 SOR_038]
WithP1Hand: SOR_035
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
P1NODECISION

---

# ControlChange_SentinelGuardsTheCONTROLLER
#// OWNER ≠ CONTROLLER, reading (a): WHO THE KEYWORD PROTECTS. Childsen sits in P1's arena but is OWNED
#// by P2 (the end state of a take-control effect). Sentinel's reminder text is "…can't attack YOUR
#// non-Sentinel units or YOUR base", and that "your" is the CONTROLLER's — so P2's attack on P1's base
#// is redirected into the stolen Childsen even though P2 owns the card. Two Experience tokens make him
#// 4/4 so he survives the 3-power attacker and can still be inspected at end state; the tokens also
#// prove upgrades ride along through a control change rather than being re-derived per seat. His 4 power
#// kills the 3/3 attacker on the counter.

## GIVEN
CommonSetup: bbk/rrk/{}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArenaControlled: SOR_035:2
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:0
P2GROUNDARENACOUNT:0

---

# ControlChange_ADefeatedStolenChildsenGoesToItsOWNERSDiscard
#// OWNER ≠ CONTROLLER, reading (b): WHOSE ZONE IT LEAVES TO. Same stolen Childsen, this time at his
#// printed 2/2, so P2's 3-power Battlefield Marine kills him on the redirected attack. A defeated card
#// goes to its OWNER's discard pile, never the controller's — so the card lands in P2's discard and
#// P1's discard stays empty. P1's base still takes 0, which is the Sentinel redirect doing its work on
#// the way there.
#// The pair with ControlChange_SentinelGuardsTheCONTROLLER above is deliberate: one seat resolves the
#// keyword, the OTHER seat receives the card.

## GIVEN
CommonSetup: bbk/rrk/{}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArenaControlled: SOR_035:2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_035
