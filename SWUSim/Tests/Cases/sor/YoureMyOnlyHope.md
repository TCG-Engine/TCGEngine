# Decline
#// COVERAGE: offer=Unaffordable_NoPlayOption (Play withheld when cost−5 is unpayable — the prompt
#//           itself is the offer, no target pool) + TopPiloting_* (Unit-vs-Pilot mode offer) ·
#//           decline=Decline (Leave) · boundary=PlayFree_LowBase (exactly 5 remaining HP → free) +
#//           PlayDiscount (healthy base → −5) + EmptyDeck_NoOp (0 cards) · reqboundary=every section
#//           answers the Play/Leave prompt in a request after the play (state crosses the boundary) ·
#//           control=CrossPlayer_ReadsTheCASTERSOwnDeckAndOwnBaseHp (supersedes the earlier N/A: both
#//           "your"s are proven seat-bound to the CASTER — P2 casts it and gets P2's top card, and
#//           the free-vs-−5 branch is decided by P2's own full base even though P1's base sits at 5
#//           remaining HP; P1's deck is untouched)
#// SOR_246 You're My Only Hope — decline: "you MAY play it". P1 looks at the top card (SOR_049
#// Obi-Wan) and chooses Leave → nothing played, the card stays on top. P1 still paid 3 for the event
#// (→ 0), and the event is in the discard.

## GIVEN
CommonSetup: byw/byw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_246
WithP1Deck: SOR_049
WithP1Deck: SOR_189
WithP1Deck: SOR_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Leave

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_049
P1RESAVAILABLE:0
P1DISCARDCOUNT:1

---

# FreePlaysUpgradeFromDeck_NoCharge
#// SOR_246 You're My Only Hope — free-play upgrade: when base has 5 or less remaining HP the top
#// card is played for FREE (ignoreCost=true). Top card is SOR_069 Resilient (cost 1, Upgrade).
#// P1 has exactly 3 resources — just enough to pay for the event itself — leaving 0 after the event.
#// A cost-1 upgrade is unaffordable on 0 resources (SWUPayCost would fail), so if ATTACH_UPGRADE
#// incorrectly calls SWUPayCost the upgrade stays in deck. The only way the upgrade attaches is if
#// ATTACH_UPGRADE skips payment entirely when ignoreCost=1 (Bug 2 fix). Sole friendly unit is
#// SOR_095 Battlefield Marine (auto-selected as the target — no MZCHOOSE needed).

## GIVEN
CommonSetup: byw/byw/{myResources:3;myBaseDamage:25}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_246
WithP1Deck: SOR_069
WithP1Deck: SOR_189
WithP1Deck: SOR_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_069
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1RESAVAILABLE:0

---

# PlayDiscount
#// SOR_246 You're My Only Hope (Event, cost 3, Heroism) — Look at the top card; you may play it for
#// 5 resources less (free if your base has ≤5 remaining HP). Base is healthy here → the −5 discount
#// applies. Vigilance/Heroism deck (byw): top card SOR_049 Obi-Wan Kenobi (cost 6, Vigilance/Heroism,
#// Sentinel — no entry trigger). P1 has 4 resources: pays 3 for the event → 1 left, then plays
#// Obi-Wan for 6 − 5 = 1 → 0 left. The −5 is what makes it playable (a normal cost-6 unit is
#// unaffordable on 1 resource), so Obi-Wan in the arena proves the reduction. Deck 3→2.

## GIVEN
CommonSetup: byw/byw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_246
WithP1Deck: SOR_049
WithP1Deck: SOR_189
WithP1Deck: SOR_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_049
P1DECKCOUNT:2
P1RESAVAILABLE:0
P1DISCARDCOUNT:1

---

# PlayFree_LowBase
#// SOR_246 You're My Only Hope — free-play branch: when your base has 5 or less remaining HP you may
#// play the top card for FREE instead of −5. P1's base (SOR_024, 30 HP) has 25 damage → 5 remaining
#// → the free branch is taken. Vigilance/Heroism deck (byw): top card SOR_056 Bendu (cost 6,
#// Sentinel — no entry trigger). P1 pays 3 for the event → 0 resources, then plays Bendu for free.
#// A cost-6 unit on 0 resources can ONLY come down via the free branch (the −5 discount would still
#// leave a cost > 0), so Bendu in the arena proves the free play. Deck 3→2.

## GIVEN
CommonSetup: byw/byw/{myResources:3;myBaseDamage:25}
P1OnlyActions: true
WithP1Hand: SOR_246
WithP1Deck: SOR_056
WithP1Deck: SOR_189
WithP1Deck: SOR_189

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_056
P1DECKCOUNT:2
P1RESAVAILABLE:0
P1DISCARDCOUNT:1

---

# Unaffordable_NoPlayOption
#// SOR_246 You're My Only Hope — "Look at the top card. You may play it (5 less; free if your base has
#// ≤5 remaining HP)." With a healthy base the discount is only −5, so "Play" must be gated on affordability:
#// if the player can't pay cost−5, only "Leave" applies (no prompt / no Play option).
#//
#// SOR_246 costs 3 (Heroism, covered) → after playing it P1 has 0 ready resources. Base is full (30 HP > 5)
#// so the free branch does NOT apply. Top card SOR_049 Obi-Wan (cost 6) → 6 − 5 = 1 net > 0 → UNaffordable.
#// (Companions: YoureMyOnlyHope_PlayDiscount covers the affordable −5 case, YoureMyOnlyHope_PlayFree_LowBase
#// the free branch — both must still offer Play.)

## GIVEN
CommonSetup: byw/byw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_246
WithP1Deck: SOR_049
WithP1Deck: SOR_189

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION

---

# EmptyDeck_NoOp
#// SOR_246 You're My Only Hope — with an EMPTY deck there is no top card to look at: the event still
#// resolves (cost paid, event to discard) but nothing is shown and nothing is played — no pending
#// decision, arenas untouched.

## GIVEN
CommonSetup: byw/byw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_246

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:0

---

# TopPiloting_PlayAsUnit
#// SOR_246 You're My Only Hope — a Piloting card on top of the deck can be played EITHER way; here P1
#// takes the Unit mode. Top card JTL_196 Dagger Squadron Pilot (cost 1, Cunning/Heroism, Piloting)
#// with a friendly pilotless Vehicle (SHD_195 Cartel Turncoat) on the board: after "Play" the
#// Unit-vs-Pilot mode choice appears; choosing Unit seats JTL_196 in the ground arena (1 − 5 → free)
#// and the Turncoat stays upgrade-free. (P1 keeps 1 ready resource: the mode offer's host-affordability
#// gate reads the UNdiscounted Piloting cost, so a broke player is never shown the choice.)

## GIVEN
CommonSetup: byw/byw/{myResources:4}
P1OnlyActions: true
WithP1SpaceArena: SHD_195:1:0
WithP1Hand: SOR_246
WithP1Deck: [JTL_196 SOR_189 SOR_189]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play
- P1>AnswerDecision:Unit

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_196
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1RESAVAILABLE:1

---

# TopPiloting_PlayAsPilot
#// SOR_246 You're My Only Hope — the SAME top card taken in the Pilot mode: choosing Pilot attaches
#// JTL_196 to the friendly pilotless Vehicle SHD_195 as a Piloting upgrade (the −5 also covers the
#// Piloting cost, 1 − 5 → free; the 4th resource stays ready). No ground unit is created.

## GIVEN
CommonSetup: byw/byw/{myResources:4}
P1OnlyActions: true
WithP1SpaceArena: SHD_195:1:0
WithP1Hand: SOR_246
WithP1Deck: [JTL_196 SOR_189 SOR_189]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_196
P1GROUNDARENACOUNT:0
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1RESAVAILABLE:1

---

# NamedCard_PlayFizzles
#// SOR_246 You're My Only Hope — the "you may play it" respects play-restrictions. P2's Regional
#// Governor (SOR_062) names "Battlefield Marine", which is the top of P1's deck (SOR_095, cost 2 —
#// otherwise free after the −5). The look still prompts, but choosing Play fizzles against the
#// restriction: the top card stays on the deck and nothing is played.

## GIVEN
CommonSetup: byw/bbw/{myResources:3;theirResources:2}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Hand: SOR_062
WithP1Hand: SOR_246
WithP1Deck: [SOR_095 SOR_189 SOR_189]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Battlefield Marine
- P1>PlayHand:0
- P1>AnswerDecision:Play

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_062
P1NODECISION
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_095
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:0

---

# TopPiloting_ZeroReadyAfterEvent_PilotModeStillOffered
#// The -5 makes the top Piloting card's pilot mode FREE, so the Unit-vs-Pilot choice must be offered
#// even with 0 ready resources after paying the event (the gate prices the REAL discounted cost).
#// Exactly 3 resources: the event consumes all of them; the pilot play then costs max(0, 2-5) = 0.

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Hand: SOR_246
WithP1Deck: [JTL_108 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Play
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:0
P1DECKCOUNT:1

---

# CrossPlayer_ReadsTheCASTERSOwnDeckAndOwnBaseHp
#// Intended: every "your" on this card belongs to the player who PLAYED the event — "look at the top
#// card of YOUR deck" and "if YOUR BASE has 5 or less remaining HP". P2 casts it here, and the board
#// is built so that a seat-1-framed read of either half is visible:
#//   • decks differ — P1's top is SOR_056 Bendu, P2's is SOR_049 Obi-Wan. The unit that comes down
#//     must be Obi-Wan, and P1's deck must still be 3.
#//   • bases differ — P1's base carries 25 damage (5 remaining, the free branch) while P2's is FULL,
#//     so P2 must pay the −5 price, not play free. P2 has 4 resources: 3 for the event, then 6 − 5 = 1
#//     for Obi-Wan → 0 ready. A base read off seat 1 would grant the free branch and leave P2 with 1
#//     ready resource, so RESAVAILABLE:0 is the discriminator between the two branches.
#// (Companion in this file: PlayFree_LowBase covers the caster's OWN base being at 5.)

## GIVEN
CommonSetup: byw/byw/{myBaseDamage:25;theirResources:4}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Hand: SOR_246
WithP2Deck: [SOR_049 SOR_189 SOR_189]
WithP1Deck: [SOR_056 SOR_189 SOR_189]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Play

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_049
P2DECKCOUNT:2
P2RESAVAILABLE:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:0
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_056
