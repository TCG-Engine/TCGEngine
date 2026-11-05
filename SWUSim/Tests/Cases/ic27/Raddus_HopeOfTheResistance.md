# Exhausted_OpponentsUnitCostsThreeMore_SevenPays
#// IC27_043 Raddus — Hope of the Resistance. Unit (Space), cost 9, [Vigilance][Heroism], 8/9, Resistance/Vehicle/
#// Capital Ship, unique. "Restore 3. While this unit is exhausted, cards your opponents play cost [3] more."
#// SOR_034 Del Meeko's surcharge shape ($playCostFieldModifiers, team-aware SWUIsEnemySeat), gated on Raddus being
#// EXHAUSTED and widened to every card type. It is also charged on the PILOTING path, which the field-modifier
#// loop never reached: a Pilot played as an upgrade is a card being played. Restore 3 is the generated keyword.
#// Fixture: bbw/bbw — both seats Vigilance base + Vigilance/Heroism leader, so SOR_046 costs its printed 4 → 7.
#//
#// COVERAGE: offer=N/A (a passive cost modifier — nothing is chosen) · decline=N/A (a cost increase can't be refused)
#//           boundary=Exhausted_…_SevenPays vs Exhausted_SixIsOneShort · gate=Ready_NoTax ·
#//           scope=YourOwnCards_NotTaxed, Events_AreTaxed, PilotAsUpgrade_IsTaxed + PilotAsUpgrade_FourIsShort ·
#//           blank=Blanked_NoTax · restore+reqboundary=AttacksThenRequestBoundary_RestoreHeals_ThenTaxes ·
#//           control=Control_StolenRaddus_TaxesItsNewControllersOpponent ·
#//           modes=2P, TwinSuns ("your opponents" = every opponent: TwinSuns_FarSeatOpponentIsTaxed +
#//                 TwinSuns_FarSeatFiveIsShort), TeamSuns (a teammate is no opponent: TeamSuns_TeammateNotTaxed)

## GIVEN
CommonSetup: bbw/bbw/{theirResources:7;theirHandCardIds:SOR_046}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: IC27_043:0:0

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2RESAVAILABLE:0

---

# Exhausted_SixIsOneShort
#// The other half of the pair: 6 resources cannot pay 4 + 3, so the play is a silent no-op.

## GIVEN
CommonSetup: bbw/bbw/{theirResources:6;theirHandCardIds:SOR_046}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: IC27_043:0:0

## WHEN
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:0
P2RESAVAILABLE:6

---

# Ready_NoTax
#// "While this unit is exhausted": a READY Raddus taxes nothing — SOR_046 costs 4.

## GIVEN
CommonSetup: bbw/bbw/{theirResources:4;theirHandCardIds:SOR_046}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: IC27_043:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2RESAVAILABLE:0

---

# YourOwnCards_NotTaxed
#// "Your OPPONENTS": Raddus's controller plays SOR_046 at 4 although Raddus is exhausted.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4;handCardIds:SOR_046}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IC27_043:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0

---

# Events_AreTaxed
#// "Cards", not units: P2's IBH_066 event (cost 1) costs 4. It heals 2 from a unit — Raddus is the only one.

## GIVEN
CommonSetup: bbw/bbw/{theirResources:4;theirHandCardIds:IBH_066}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: IC27_043:0:0

## WHEN
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P2RESAVAILABLE:0

---

# PilotAsUpgrade_IsTaxed
#// A Pilot played as an upgrade is still a card being played. P2's JTL_046 Paige Tico (Piloting 2) onto P2's
#// SHD_060 costs 2 + 3 = 5 — all five resources. (At 5, playing her as a unit, 2 + 3, is affordable too, so the
#// Unit/Pilot choice is raised.)

## GIVEN
CommonSetup: bbw/bbw/{theirResources:5;theirHandCardIds:JTL_046}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: IC27_043:0:0
WithP2SpaceArena: SHD_060:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Pilot

## EXPECT
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_046
P2RESAVAILABLE:0

---

# PilotAsUpgrade_FourIsShort
#// The discriminating half: with 4 resources an UNTAXED Piloting play (2) would go through. Taxed, neither mode is
#// affordable and the play is a no-op.

## GIVEN
CommonSetup: bbw/bbw/{theirResources:4;theirHandCardIds:JTL_046}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1SpaceArena: IC27_043:0:0
WithP2SpaceArena: SHD_060:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P2RESAVAILABLE:4
P2NODECISION

---

# Blanked_NoTax
#// P1 blanks his own exhausted Raddus with SOR_138 Force Lightning (1 + 4 off-aspect = 5); then P2's SOR_046 costs 4.

## GIVEN
CommonSetup: bbw/bbw/{myResources:5;handCardIds:SOR_138;theirResources:4;theirHandCardIds:SOR_046}
SkipPreGame: true
WithP1SpaceArena: IC27_043:0:0

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2RESAVAILABLE:0

---

# AttacksThenRequestBoundary_RestoreHeals_ThenTaxes
#// The real path: Raddus attacks (Restore 3 heals P1's base 5 → 2) and is exhausted by it; across a request
#// boundary P2's SOR_046 then costs 7.

## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:5;theirResources:7;theirHandCardIds:SOR_046}
SkipPreGame: true
WithP1SpaceArena: IC27_043:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P2>PlayHand:0

## EXPECT
P1BASEDMG:2
P2BASEDMG:8
P1SPACEARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:1
P2RESAVAILABLE:0

---

# Control_StolenRaddus_TaxesItsNewControllersOpponent
#// "Your opponents" is read from the CONTROLLER. P2 controls a Raddus that P1 owns, attacks with it (exhausting it),
#// and now P1 — P2's opponent — pays 7 for SOR_046.

## GIVEN
CommonSetup: bbw/bbw/{myResources:7;handCardIds:SOR_046}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2SpaceArenaControlled: IC27_043:1

## WHEN
- P2>AttackSpaceArena:0:BASE
- P1>PlayHand:0

## EXPECT
P2SPACEARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0

---

# TwinSuns_FarSeatOpponentIsTaxed
#// Three seats, no teams: seat 3 is an opponent too. Its TWI_057 (Vigilance, 1 + 2 off-aspect on a Command base = 3)
#// costs 6 — all six.

## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP3Resources: 6
WithP3Hand: TWI_057
WithP1SpaceArena: IC27_043:0:0

## WHEN
- P3>PlayHand:0

## EXPECT
SEATCOUNT:3
P3GROUNDARENACOUNT:1
P3RESAVAILABLE:0

---

# TwinSuns_FarSeatFiveIsShort
#// …and 5 is one short — an untaxed seat 3 would have played it with 2 to spare.

## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP3Resources: 5
WithP3Hand: TWI_057
WithP1SpaceArena: IC27_043:0:0

## WHEN
- P3>PlayHand:0

## EXPECT
P3HANDCOUNT:1
P3GROUNDARENACOUNT:0

---

# TeamSuns_TeammateNotTaxed
#// Team Suns: seat 3 is P1's teammate, not an opponent — TWI_057 costs its 3.

## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP4Base: SOR_019:0
WithP3Resources: 3
WithP3Hand: TWI_057
WithP1SpaceArena: IC27_043:0:0

## WHEN
- P3>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:1
P3RESAVAILABLE:0
