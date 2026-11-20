# AFriendlyUnitAttacksABase_YouDraw
#// IC27_122 The Ghost (Space 7/7, cost 7, [Aggression][Heroism]) — "Overwhelm. When a friendly unit deals damage to an
#// enemy base: If you have 7 or fewer cards in your hand, draw a card." The Battlefield Marine attacks P2's base;
#// P1 (0 cards) draws 1.
#// Fixture: CommonSetup rrw = Aggression base + Aggression/Heroism leader.
#// COVERAGE: offer=N/A (no choice — the draw is automatic) · decline=N/A (no "you may") ·
#//           boundary=HandOfSeven_Draws + HandOfEight_NoDraw (the "7 or fewer" pair) ·
#//           control=StolenGhost_ItsControllerDraws (the CONTROLLER's friendly units and hand) ·
#//           reqboundary=N/A (the reaction resolves synchronously inside the damage event; nothing crosses a request)
#//           modes=2P,TwinSuns (any enemy base — TwinSuns_AFarSeatBaseIsAnEnemyBase),
#//                 TeamSuns ("friendly unit" = the team — TeamSuns_YourTeammatesGhostDraws)

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
P1DECKCOUNT:2

---

# TheGhostAttacks_OverwhelmSpill_DrawsOnce
#// The Ghost is a friendly unit too. It attacks P2's TIE/ln Fighter (2/1) with Overwhelm: 6 excess reaches P2's base —
#// ONE instance of damage to a base, so ONE card.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:6
P1HANDCOUNT:1

---

# AUnitAttacksAUnit_NoBaseDamage_NoDraw
#// Combat damage to a unit is not damage to a base: the Marine attacks P2's Consular Security Force (3/7, survives,
#// no Overwhelm) and nothing is drawn.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:0
P1HANDCOUNT:0

---

# HandOfSeven_Draws
#// Exactly 7 cards in hand is "7 or fewer": P1 draws to 8.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
WithP1Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:8
P1DECKCOUNT:2

---

# HandOfEight_NoDraw
#// 8 cards is more than 7: nothing is drawn.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
WithP1Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:8
P1DECKCOUNT:3

---

# TwoAttacksOnABase_TwoDraws
#// Each instance draws: the Marine and then The Ghost attack P2's base — two cards.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:10
P1HANDCOUNT:2

---

# AUnitsAbilityDamage_EnemyBaseOnly
#// "deals damage" is not only combat: SHD_160 Reckless Gunslinger's When Played deals 1 damage to EACH base. Its hit
#// on P2's base is a friendly unit damaging an enemy base (one draw); its hit on P1's OWN base is not (no second draw).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1;myhandCardIds:SHD_160}
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:1
P1BASEDMG:1
P1HANDCOUNT:1

---

# AnEventsDamage_IsNotAUnits_NoDraw
#// IBH_059 Target the Main Generator is an event, not a unit: its 2 damage to P2's base draws nothing.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;myhandCardIds:IBH_059}
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1HANDCOUNT:0
P1DECKCOUNT:3

---

# IndirectDamageFromAUnit_Draws
#// JTL_143 Devastator's When Played deals 4 indirect damage to each opponent; P2 has no units, so it all lands on
#// P2's base — a unit's damage to an enemy base. P1 draws 1. (Devastator is Villainy: 8 + 2 off-aspect.)

## GIVEN
CommonSetup: rrw/rrk/{myResources:10;myhandCardIds:JTL_143}
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:4
P1HANDCOUNT:1

---

# AnEnemyDamagesYourBase_NoDraw
#// P2's Marine attacks P1's base: P1's base is not an enemy base for P1, and the dealer is not P1's friendly unit.

## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 2
WithP1SpaceArena: IC27_122:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P1HANDCOUNT:0

---

# PreventedDamage_IsNotDealt_NoDraw
#// JTL_074 Close the Shield Gate is armed on P2's base: the Marine's attack is prevented, no damage is dealt, nothing
#// is drawn.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GlobalEffect: SWU_SHIELD_GATE
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P1HANDCOUNT:0

---

# BlankedGhost_NoDraw
#// The Ghost under SOR_138 Force Lightning ("loses all abilities this phase"): the Marine's base hit draws nothing.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: IC27_122:1:0:SOR_138
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:0

---

# StolenGhost_ItsControllerDraws
#// The Ghost is OWNED by P2 and controlled by P1: P1's Marine is P1's friendly unit, P2's base is P1's enemy base —
#// P1 (the controller) draws. P2, the owner, does not.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArenaControlled: IC27_122:2
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HANDCOUNT:1
P2HANDCOUNT:0

---

# TwinSuns_AFarSeatBaseIsAnEnemyBase
#// Three seats, no teams: the Marine attacks seat 3's base — an enemy base. P1 draws.

## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1SpaceArena: IC27_122:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:p3Base-0

## EXPECT
SEATCOUNT:3
P1HANDCOUNT:1

---

# TeamSuns_YourTeammatesGhostDraws
#// Team game: The Ghost is controlled by P1's TEAMMATE (seat 3). P1's Marine is the teammate's friendly unit and
#// seat 2's base is an enemy base for the teammate, so seat 3 draws; P1 (no Ghost) does not.

## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP3SpaceArena: IC27_122:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]
WithP3Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:p2Base-0

## EXPECT
SEATCOUNT:4
P3HANDCOUNT:1
P1HANDCOUNT:0

---

# TwoDefenderOverwhelm_CombinedExcess_DrawsOnce
#// TWI_135 Darth Maul's two-defender attack with Overwhelm (TWI_119 Nameless Valor → 7/8) deals the combined excess
#// (4 + 4) to P2's base in one instance: The Ghost's controller draws one card.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP1GroundArenaUpgrade: 0:TWI_119
WithP1SpaceArena: IC27_122:1:0
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP1Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2BASEDMG:8
P1HANDCOUNT:1

---

# AttackerWithAnOnAttack_StillOneDrawPerHit
#// IC27_113 Ezra Bridger's On Attack leaves a unit-ability damage source set for the rest of the attack; his combat hit
#// on P2's base is still ONE instance (1 + Raid 2 = 3) and draws ONE card, not two. (His grant auto-resolves onto The
#// Ghost, the only other friendly Heroism unit.)

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: IC27_113:1:0
WithP1SpaceArena: IC27_122:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1

---

# TwinSuns_AnOpponentDamagingAnotherOpponentsBase_NoDraw
#// Three seats, no teams: seat 2's Marine attacks seat 3's base. That base is P1's enemy, but the dealer is not P1's
#// friendly unit — nothing is drawn.

## GIVEN
CommonSetup: rrw/rrk
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1SpaceArena: IC27_122:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P2>AttackGroundArena:0:p3Base-0

## EXPECT
SEATCOUNT:3
P3BASEDMG:3
P1HANDCOUNT:0
