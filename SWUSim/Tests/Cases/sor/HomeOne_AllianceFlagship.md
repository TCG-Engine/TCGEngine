# DiscountThreeLess
#// SOR_102 Home One — the played unit costs 3 LESS. A single Heroism unit (SOR_100 Wedge, cost 5,
#// Command/Heroism) is in discard. P1 has 10 resources: Home One costs 8 (→ 2 left), then Wedge costs
#// 5-3 = 2 (→ 0 left). Wedge enters play and the discard empties. Without the -3, Wedge (cost 5) would
#// be unaffordable with only 2 resources and would NOT be played.

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;discardCardIds:SOR_100}
P1OnlyActions: true
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:0
P1RESAVAILABLE:0

---

# NonHeroismNotPlayable
#// SOR_102 Home One — only a [Heroism] unit can be played from discard. With only a non-Heroism unit
#// (SEC_080, Villainy) in discard, the When Played fizzles: nothing is played and the discard is intact.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;discardCardIds:SEC_080}
P1OnlyActions: true
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# PlayedUnitWhenPlayedFires
#// SOR_102 Home One — a unit played from discard runs its OWN When Played (nested trigger). SOR_096
#// Daring Raid (Command/Heroism, cost 2 → free after -3, "When Played: search top 5 for a Rebel card
#// and draw it") is played from discard; its nested search finds the Rebel SOR_095 in P1's deck and
#// draws it (deck → hand), proving the played unit's entry trigger resolves.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;discardCardIds:SOR_096}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:0

---

# PlaysHeroismUnitFromDiscard
#// SOR_102 Home One (Command/Heroism unit, cost 8, 7/7, Rebel/Capital Ship) — "Restore 2. Each other
#// friendly unit gains Restore 1. When Played: Play a [Heroism] unit from your discard pile. It costs 3
#// less." (Restore/Restore-grant already implemented.) Two Heroism units seeded in discard; choosing
#// SOR_095 (cost 3 → free after -3) plays it into the ground arena, leaving SOR_046 in discard.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;discardCardIds:SOR_095,SOR_046}
P1OnlyActions: true
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1

---

# UnaffordableHeroismFiltered
#// SOR_102 Home One — "When Played: Play a [Heroism] unit from your discard pile. It costs 3 less."
#// Affordability guard: the discount play must still be PAID for. With exactly 8 resources, all are
#// spent deploying Home One (cost 8), leaving 0 ready. SOR_095 (cost 2 -> 0 after -3) is affordable;
#// SOR_046 (cost 4 -> 1 after -3) is NOT. Only the affordable unit may be offered, so the single
#// remaining target auto-resolves and plays — no chance to pick the unplayable one and fizzle.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;discardCardIds:SOR_095,SOR_046}
P1OnlyActions: true
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1

---

# PlaysPilotingUnit_AsUnitNotPilot
#// SOR_102 Home One — "Play a [Heroism] unit from your discard pile." A card with Piloting is still a
#// UNIT card, and this clause plays it as a UNIT: JTL_093 Nien Nunb (cost 1, Command/Heroism,
#// Piloting) is fetched from the discard and lands in the GROUND arena, never offered as an upgrade —
#// even though a friendly Vehicle (SOR_237 Alliance X-Wing) is in play as a legal pilot host. Only
#// Home One's own 8 is paid (Nien Nunb's 1-3 floors at 0), so exactly 2 resources are left of the 10.

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;discardCardIds:JTL_093}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_102
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_093
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:2
P1NODECISION
