# DiscountThreeLess
#// SOR_102 Home One — the played unit costs 3 LESS. A single Heroism unit (SOR_100 Wedge, cost 5,
#// Command/Heroism) is in discard. P1 has 10 resources: Home One costs 8 (→ 2 left), then Wedge costs
#// 5-3 = 2 (→ 0 left). Wedge enters play and the discard empties. Without the -3, Wedge (cost 5) would
#// be unaffordable with only 2 resources and would NOT be played.
#// COVERAGE: offer=Offer_DiscardPoolIsHeroismUnitsOnly (pending SELECTABLEEXACT over the discard:
#//           the alignment filter AND the card-type filter, with every candidate affordable so the
#//           pool is not narrowed by cost) · boundary=UnaffordableHeroismFiltered (cost 2-3 payable /
#//           cost 4-3 not, at 0 resources left) + Restore2_HealsYourBaseAndDoesNotBuffItself (heal
#//           exactly 2, not 3 — the "other" exclusion) · control=UnderEnemyControl_TheAuraFollowsThe
#//           Controller ("each other friendly unit" and "your base" both read from the CONTROLLER; the
#//           owner's units gain nothing) · reqboundary=WhenPlayedDiscardPickSurvivesRequestBoundary ·
#//           decline=N/A — the discard is a PUBLIC zone, so the hidden-zone declinability rule does
#//           not reach it, and the printed clause carries no "you may"; the play is mandatory when a
#//           legal target exists and simply fizzles when none does (NonHeroismNotPlayable).

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

---

# Restore2_HealsYourBaseAndDoesNotBuffItself
#// SOR_102 Home One — clause 1: "Restore 2". Home One attacks the enemy base with P1's base sitting at
#// 5 damage; 2 is healed, leaving 3. This is also the NEGATIVE for clause 2's word "other": the
#// Restore-1 aura must NOT apply to Home One itself, so the heal is exactly 2 (a self-inclusive aura
#// would heal 3 and leave the base at 2).

## GIVEN
CommonSetup: ggw/rrk/{myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_102:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:7
P1SPACEARENAUNIT:0:HASKEYWORD:Restore

---

# GrantsRestore1_ToEveryOtherFriendlyUnitInBothArenas
#// SOR_102 Home One — clause 2: "Each OTHER friendly unit gains Restore 1." The grant is not arena
#// scoped: the friendly space X-Wing (SOR_237, 2/3) AND the friendly ground SOR_095 both carry Restore.
#// The X-Wing attacks the enemy base and heals exactly 1 from P1's base (5 → 4), proving the granted
#// value is 1 and not Home One's own 2.

## GIVEN
CommonSetup: ggw/rrk/{myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_102:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:1:BASE

## EXPECT
P1BASEDMG:4
P2BASEDMG:2
P1SPACEARENAUNIT:1:HASKEYWORD:Restore
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore

---

# NoHomeOne_NoRestoreGrant
#// SOR_102 Home One — the control that makes the grant section load-bearing. Identical board WITHOUT
#// Home One: the X-Wing has no Restore keyword and its attack heals nothing, so P1's base stays at 5.

## GIVEN
CommonSetup: ggw/rrk/{myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:5
P2BASEDMG:2
P1SPACEARENAUNIT:0:NOTKEYWORD:Restore
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore

---

# EnemyUnitsNeverGainRestore1
#// SOR_102 Home One — "each other FRIENDLY unit". The scope exclusion: with Home One on P1's board, an
#// ENEMY X-Wing attacking P1's base gains nothing — P2's base stays at its 5 damage and the unit has no
#// Restore keyword. (P1's base takes the 2, and Home One's own aura does not heal it: Restore fires
#// only when the RESTORE unit itself attacks.)

## GIVEN
CommonSetup: ggw/rrk/{theirBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_102:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:5
P1BASEDMG:2
P2SPACEARENAUNIT:0:NOTKEYWORD:Restore

---

# Offer_DiscardPoolIsHeroismUnitsOnly
#// SOR_102 Home One — OFFER axis for clause 3. Four cards seeded in the discard, all affordable at the
#// 4 resources left after Home One's own 8 is paid: two [Heroism] UNITS (SOR_095 cost 2 → free,
#// SOR_046 cost 4 → 1), one non-Heroism unit (SEC_080, Command/Villainy) and one UPGRADE (SOR_120,
#// Command/Heroism-less). The decision is left PENDING so the pool itself is the assertion — exactly
#// the two Heroism units, with the alignment filter and the card-type filter both proven.

## GIVEN
CommonSetup: ggw/rrk/{myResources:12;discardCardIds:SOR_095,SOR_046,SEC_080,SOR_120}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0

---

# UnderEnemyControl_TheAuraFollowsTheController
#// SOR_102 Home One — CONTROL CHANGE. Home One sits in P2's space arena but is OWNED by P1. "Each other
#// friendly unit" and "your base" are read from the CONTROLLER's seat: P2's X-Wing gains Restore 1 and
#// heals P2's base (5 → 4) when it attacks, while P1's own ground unit — the owner's side — gains
#// nothing. (Controlled units seat after the plain ones, so P2's arena is [X-Wing, Home One].)

## GIVEN
CommonSetup: ggw/ggw/{theirBaseDamage:5}
SkipPreGame: true
WithActivePlayer: 2
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaControlled: SOR_102:1
WithP1GroundArena: SOR_095:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:4
P1BASEDMG:2
P2SPACEARENAUNIT:0:HASKEYWORD:Restore
P2SPACEARENAUNIT:1:CARDID:SOR_102
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore

---

# WhenPlayedDiscardPickSurvivesRequestBoundary
#// SOR_102 Home One — REQUEST BOUNDARY. The When Played discount play is written while Home One enters
#// and read in the LATER request that answers the discard pick, so the pending choice and its −3 charge
#// must survive serialization. Two Heroism units keep the pick genuinely pending; SOR_095 (cost 2 → 0)
#// is then fetched, leaving SOR_046 in the discard and 4 of the 12 resources unspent.

## GIVEN
CommonSetup: ggw/rrk/{myResources:12;discardCardIds:SOR_095,SOR_046}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1DISCARDCOUNT:1
P1RESAVAILABLE:4
