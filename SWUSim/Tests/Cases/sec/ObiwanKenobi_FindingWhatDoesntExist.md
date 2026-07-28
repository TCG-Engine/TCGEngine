# BaseHit_MillAndPlayFromDiscard
#// SEC_205 Obi-Wan Kenobi (Unit, 4/5, cost 4, Cunning/Heroism, Force/Jedi/Republic, Ground)
#//   "When this unit deals combat damage to a base: Discard a card from the defending player's deck. For
#//    this phase, you may play that card from their discard pile, ignoring its aspect penalties."
#// SEC_205 attacks P2's base for 4 → mills the top of P2's deck (SOR_095) into P2's discard with the OTPN
#// modifier (play-from-opp-discard at cost, ignoring aspect penalty). P1 then plays SOR_095 from P2's
#// discard. SOR_095 is Command/Heroism — fully off-aspect for Cunning P1 (penalty would be +4), but OTPN
#// ignores it, so P1 pays exactly its cost (2). P1 has exactly 2 ready resources: the play succeeds and
#// ends at 0 ready — which it could NOT if the +4 penalty applied (proving the aspect-penalty bypass).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 2
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P2DISCARDCOUNT:0
P1RESAVAILABLE:0
P2DECKCOUNT:2

---

# OverwhelmCoordinateHitsBase_MillFires
#// SEC_205 fires on ANY combat damage to a base — including excess Overwhelm damage. P1 controls Wampa
#// (SOR_164), Obi-Wan (SEC_205) and Clone Commander Cody (TWI_114). Cody's Coordinate (3+ units) gives
#// every OTHER friendly unit +1/+1 and Overwhelm, so Obi-Wan is 5/6 with Overwhelm. Obi-Wan attacks
#// SpecForce Soldier (SOR_140, 2/2): kills it and spills 5−2=3 overflow onto P2's base. That base hit
#// fires SEC_205 → mill SOR_232 (AT-ST) from P2's deck into P2's discard. P2's discard then holds both the
#// defeated SpecForce Soldier and the milled AT-ST; the deck drops from 3 to 2.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SEC_205:1:0
WithP1GroundArena: TWI_114:1:0
WithP2GroundArena: SOR_140:1:0
WithP2Deck: [SOR_232 SOR_232 SOR_232]

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P2DISCARDCOUNT:2
P2DECKCOUNT:2

---

# OtherFriendlyHitsBase_NoMill
#// SEC_205's trigger is "When THIS unit deals combat damage to a base" — a different friendly unit hitting
#// the base does nothing. P1's Wampa (SOR_164, 4 power) attacks P2's base for 4 while Obi-Wan sits idle.
#// No card is milled: P2's deck stays full, its discard stays empty, and no play-from-discard is offered.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SEC_205:1:0
WithP2Deck: [SOR_232 SOR_232]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2DECKCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION

---

# ObiwanHitsUnit_NoBaseMill
#// SEC_205 only fires on damage to a BASE. Obi-Wan (4/5) attacks Consular Security Force (SOR_046, 3/7):
#// he deals 4 combat damage to the unit (not lethal) and takes 3 back, but no base is hit — so nothing is
#// milled. P2's deck stays full and its discard stays empty.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: [SOR_232 SOR_232]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P2DECKCOUNT:2
P2DISCARDCOUNT:0

---

# EnemyHitsBase_NoMill
#// SEC_205 belongs to P1's Obi-Wan; an ENEMY unit dealing combat damage to P1's base must not fire it.
#// P1 passes; P2's A-Wing (SEC_213, 1/2 Raid 1 → 2 power attacking a base) hits P1's base for 2. Neither
#// deck is milled — P1's deck and P2's deck both stay full and no discard grows.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_205:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_232 SOR_232]

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:2
P1DECKCOUNT:2
P2DECKCOUNT:2
P2DISCARDCOUNT:0

---

# EmptyDeck_NoDiscardOffered
#// SEC_205 hits the base but the defending player's deck is empty — there is nothing to discard, so the
#// ability fizzles cleanly. Obi-Wan attacks P2's empty-deck base for 4; no card is milled, no play-from-
#// discard offer appears.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP2Deck: []

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2DECKCOUNT:0
P2DISCARDCOUNT:0
P1NODECISION

---

# PlaysOpponentEventFromDiscard_PowerOfDarkSide
#// SEC_205 Obi-Wan — a card milled to the opponent's discard may be PLAYED from there this phase; when it is
#// an EVENT it resolves under the CASTER (P1). P1 mills + plays SOR_041 Power of the Dark Side ("An opponent
#// chooses a unit they control. Defeat that unit.") from P2's discard: "an opponent" is the caster's opponent
#// (P2), who defeats their own only unit (SOR_046).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 3
WithP2Deck: [SOR_041 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4

---

# PlaysOpponentEventFromDiscard_Resupply_ResourceForCaster
#// SEC_205 Obi-Wan — an opponent's EVENT played from their discard resolves under the CASTER. SOR_126
#// Resupply ("Put this event into play as a resource") milled from P2's deck and played by P1 becomes a
#// resource for P1 (the caster), not P2. P1 pays SOR_126's cost (3; OTPN ignores the aspect penalty) and
#// ends with one MORE resource than before.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 3
WithP2Deck: [SOR_126 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P1RESCOUNT:4
P2BASEDMG:4

---

# ForeignEventPlay_AppliesSawGerreraTax
#// Behavior change: a foreign-owned event play (routed through ActivateCard) now applies the same
#// play-time taxes as any event play. P2 controls Saw Gerrera SOR_153 ("as an additional cost for each
#// opponent to play an event, deal 2 to their base") — P1 is P2's opponent, so P1 playing an event pays 2
#// to P1's base. P1's Obi-Wan mills LAW_244 (Unmarked Credits, cost 1) from P2's deck with the OTPN
#// modifier, then P1 plays it: P1's base takes 2 (Saw Gerrera) AND P1 gets a Credit token (event resolved).
#// (Before routing through ActivateCard the bypass path skipped Saw Gerrera → P1BASEDMG was 0.)

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP2GroundArena: SOR_153:1:0
WithP1Resources: 2
WithP2Deck: [LAW_244 LAW_244 LAW_244]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P1BASEDMG:2
P2BASEDMG:4
P1CREDITCOUNT:1
P2DECKCOUNT:2
