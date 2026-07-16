# Decline
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
