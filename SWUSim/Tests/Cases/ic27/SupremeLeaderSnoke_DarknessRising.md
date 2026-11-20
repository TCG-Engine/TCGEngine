# FourToEachOtherUnit_BothSidesBothArenas
#// IC27_109 Supreme Leader Snoke — Darkness Rising (8, 8/6 Ground, Aggression/Villainy, Force·First Order·Official,
#// unique, Legendary)
#// "Overwhelm. When Played: Deal 4 damage to each other unit. Draw a card for each friendly unit defeated this way."
#// PREVIEW card (no official rulings). "Each other unit" = the whole table except Snoke (LOF_170 Bendu's pool). The
#// 4s are ONE ability dealing damage simultaneously (official Rancor Keeper ruling, 07/21/2026), so the loop runs
#// inside SWUSimulDefeatBegin/End (HMW_263 Wrecker). "Friendly unit defeated this way" = a unit that was friendly
#// (team-wide) when the 4 was dealt and is gone after it; the draw is ONE instruction of N cards (DoDrawCard), so
#// an empty deck is one deck-out event of 3 x N (user ruling 2026-09-07).
#//
#// COVERAGE: offer=N/A (nothing is chosen) · decline=N/A (no "you may") ·
#//           negative=OnlyEnemiesDefeated_NoDraw + FriendlySurvivor_AndShieldedFriendly_DoNotCount ·
#//           quantity=ThreeFriendliesDefeated_DrawThree (and the zero case above) ·
#//           value-class=FriendlyToken_Counts · scope="other" asserted here (Snoke undamaged) ·
#//           batch=Simultaneous_VictimObserverSeesCoVictims (order-sensitive: the observer dies FIRST) ·
#//           no-card=EmptyDeck_ThreePerUndrawnCard (the single-event vs per-card split is invisible to a
#//           base total — the call shape is one DoDrawCard, and the ruling's guard lives in hmw/AllianceShieldGenerator.md) · keyword=Overwhelm asserted here ·
#//           reqboundary=N/A (no decision is raised and nothing is stored: the snapshot, the damage and the draw
#//           all resolve inline inside the When Played) ·
#//           modes=2P,TeamSuns,TwinSuns ("each other unit" = the whole table; "friendly" = the team) —
#//           TeamSuns_TeammatesDefeatedUnitDraws + TwinSuns_FarSeatUnitsHitButNotFriendly
#//
#// P1: Battlefield Marine (3/3 ground), Alliance X-Wing (2/3 space). P2: Niima Outpost Constables (2/6 ground),
#// TIE/ln Fighter (2/1 space). Both of P1's die (2 draws), the TIE dies, Niima survives on 4; Snoke is untouched.

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:IC27_109}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SHD_062:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_109
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
P1SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:4
P2SPACEARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:1

---

# OnlyEnemiesDefeated_NoDraw
#// The friendly gate: two enemy units die, no friendly one does — no draw.

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:IC27_109}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:3

---

# FriendlySurvivor_AndShieldedFriendly_DoNotCount
#// "defeated this way" measures the outcome: P1's Niima (2/6) survives the 4, and P1's Shielded Marine loses the
#// Shield instead of dying. Neither draws; the enemy Marine's death does not either.

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:IC27_109}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_062:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0

---

# ThreeFriendliesDefeated_DrawThree
#// Quantity: three friendly Marines die → three cards.

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:IC27_109}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0 SOR_095:1:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:3
P1DECKCOUNT:1

---

# FriendlyToken_Counts
#// Value class: a friendly TOKEN unit is a friendly unit — a TIE Fighter token (JTL_T01, 1/1) dying draws a card.

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:IC27_109}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_T01:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1HANDCOUNT:1

---

# EmptyDeck_ThreePerUndrawnCard
#// Two friendly units die with an empty deck: 3 damage per card that can't be drawn, 6 on P1's base. ("Draw a card
#// for each" is ONE instruction of 2 — one event of 6 per the 2026-09-07 ruling — but a base total cannot tell one
#// event of 6 from two of 3; this section pins the amount, not the event split.)

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:IC27_109}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP1Deck: []

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1BASEDMG:6

---

# Simultaneous_VictimObserverSeesCoVictims
#// The batch window, in the ONLY order that discriminates: P1's LOF_130 HK-47 (2/4, "When an enemy unit is defeated:
#// deal 1 damage to its controller's base") is on P1's side, so Snoke's 4 reaches — and defeats — it BEFORE the two
#// enemy Marines. All three die in one simultaneous batch, so HK-47 still sees both enemy defeats: 2 on P2's base.
#// (Reversed, HK-47 would still be alive when they died and the section would pass with the window deleted.)
#// HK-47 is friendly and defeated this way, so P1 draws one.

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:IC27_109}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_130:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1HANDCOUNT:1

---

# TeamSuns_TeammatesDefeatedUnitDraws
#// Team Suns (P1+P3 vs P2+P4): "each other unit" hits all four seats, and teammate P3's Marine is a FRIENDLY unit
#// defeated this way — P1 draws one. The enemy Marines (P2, P4) die without a draw.

## GIVEN
CommonSetup: rrk/bbw/{myResources:12;handCardIds:IC27_109}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P2GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:0
P4GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# TwinSuns_FarSeatUnitsHitButNotFriendly
#// Twin Suns (3 seats, no teams): seat 3's Marine is hit and dies — "each other unit" is the whole table — but it is
#// an opponent's, so no draw.

## GIVEN
CommonSetup3P: rrk/bbw/bbw/{myResources:12;handCardIds:IC27_109}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P3GROUNDARENACOUNT:0
P1HANDCOUNT:0
