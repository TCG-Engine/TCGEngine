# OnAttackEnd_Discard
#// COVERAGE: offer=OnAttackEnd_Leave + OnAttackEnd_Discard + OnAttackEnd_Play (all three branches of
#//           the Play/Discard/Leave prompt) + Piloting_PlayAsUnit/Piloting_PlayAsPilot (Unit-vs-Pilot
#//           mode offer) vs Piloting_NoEligibleVehicle/Piloting_NoVehicles (mode offer withheld) ·
#//           decline=OnAttackEnd_Leave · boundary=OnAttackEnd_EmptyDeck_NoOp (0-card deck) +
#//           DefeatedDuringAttack_NoTrigger (attacker must survive) · reqboundary=every section answers
#//           the prompt in a request after the attack · control=N/A (the trigger rides the attack of
#//           Ezra himself; no scenario changes his controller mid-attack)
#// SOR_192 Ezra Bridger — On Attack End: choosing "Discard" puts the top card into the discard pile
#// (From DECK). Ezra attacks P2's base for 3; the top card SOR_095 is milled (deck 3 → 2, discard
#// 0 → 1).

## GIVEN
CommonSetup: rrw/rrw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Discard

## EXPECT
P2BASEDMG:3
P1DECKCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDUNIT:0:FROM:DECK
P1GROUNDARENACOUNT:1

---

# OnAttackEnd_EmptyDeck_NoOp
#// SOR_192 Ezra Bridger — On Attack End with an empty deck: there is no top card to look at, so the
#// ability fizzles with no decision (no option popup). Ezra still attacks P2's base for 3, and the
#// turn proceeds with no pending decision.

## GIVEN
CommonSetup: rrw/rrw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1NODECISION
P1DECKCOUNT:0
P1DISCARDCOUNT:0
P1GROUNDARENACOUNT:1

---

# OnAttackEnd_Leave
#// SOR_192 Ezra Bridger (Unit 3/4, cost 3, Cunning/Heroism) — When this unit completes an attack:
#// look at the top card; you may play it, discard it, or leave it on top. Ezra (in play, ready)
#// attacks P2's base for 3, then the On Attack End trigger fires; the player chooses "Leave". The
#// deck is untouched at the On Attack End step (top stays SOR_095). P1 then passes, the round rolls
#// to Regroup, and the Draw step draws the top card — confirming the card LEFT ON TOP (SOR_095) is
#// the one actually drawn (hand index 0), not a card from the other end of the deck.

## GIVEN
CommonSetup: rrw/rrw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Leave
- P1>Pass

## EXPECT
P2BASEDMG:3
P1DECKCOUNT:1
P1DISCARDCOUNT:0
P1HANDCOUNT:2
P1GROUNDARENACOUNT:1
#// The card left on top (SOR_095) is the next one drawn in the regroup Draw step → hand index 0.
P1HANDCARD:0:SOR_095

---

# OnAttackEnd_Play
#// SOR_192 Ezra Bridger — On Attack End: choosing "Play" plays the top card from the deck, paying
#// its normal cost. Ezra attacks P2's base for 3; the top card is SOR_157 (cost 1, Aggression, no
#// entry trigger). With matched Aggression aspects and 1 ready resource, it is played to the ground
#// arena (arena 1 → 2, deck 3 → 2, resources 1 → 0).

## GIVEN
CommonSetup: rrw/rrw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1Deck: SOR_157
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_157
P1DECKCOUNT:2
P1RESAVAILABLE:0
P1DISCARDCOUNT:0

---

# DefeatedDuringAttack_NoTrigger
#// SOR_192 Ezra Bridger — "completes an attack" requires the attacker to SURVIVE: an Ezra defeated by
#// the defender's combat damage does not look at the top card. Ezra (3/4) attacks the 4/4 K-2SO: Ezra
#// deals 3 (K-2SO survives at 3 damage) and takes 4 → defeated. No prompt fires, the deck is untouched.

## GIVEN
CommonSetup: rrw/rrw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP2GroundArena: SOR_145:1:0
WithP1Deck: [SOR_095 SOR_128 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_192
P2GROUNDARENAUNIT:0:DAMAGE:3
P1DECKCOUNT:3
P1NODECISION

---

# Piloting_PlayAsUnit
#// SOR_192 Ezra Bridger — a Piloting card on top of the deck can be played EITHER way from Ezra's
#// look; here the Unit mode. Top card JTL_196 Dagger Squadron Pilot (cost 1, Cunning/Heroism) with a
#// friendly pilotless Vehicle (SHD_195 Cartel Turncoat) in space: after "Play" the Unit-vs-Pilot mode
#// choice appears; Unit seats JTL_196 in the ground arena (entering exhausted), Turncoat untouched.

## GIVEN
CommonSetup: byw/byw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1SpaceArena: SHD_195:1:0
WithP1Deck: [JTL_196 SOR_189 SOR_189]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play
- P1>AnswerDecision:Unit

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_196
P1GROUNDARENAUNIT:1:EXHAUSTED
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1DECKCOUNT:2
P1RESAVAILABLE:0

---

# Piloting_PlayAsPilot
#// SOR_192 Ezra Bridger — the SAME top card taken in the Pilot mode: JTL_196 attaches to the friendly
#// pilotless Vehicle SHD_195 as a Piloting upgrade (Piloting cost 1). No ground unit is created.

## GIVEN
CommonSetup: byw/byw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1SpaceArena: SHD_195:1:0
WithP1Deck: [JTL_196 SOR_189 SOR_189]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_196
P1GROUNDARENACOUNT:1
P1DECKCOUNT:2
P1RESAVAILABLE:0

---

# Piloting_NoEligibleVehicle_PlaysAsUnit
#// SOR_192 Ezra Bridger — the Unit-vs-Pilot mode choice is only offered when an ELIGIBLE Vehicle
#// exists. The only friendly Vehicle (SHD_195) already carries a Pilot (JTL_058 Academy Graduate), so
#// "Play" goes straight to the unit play with no mode prompt: JTL_196 lands in the ground arena and
#// the Turncoat keeps exactly its one existing upgrade.

## GIVEN
CommonSetup: byw/byw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1SpaceArena: SHD_195:1:0
WithP1SpaceArenaPilot: 0:JTL_058
WithP1Deck: [JTL_196 SOR_189 SOR_189]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_196
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1DECKCOUNT:2
P1NODECISION
P1RESAVAILABLE:0

---

# Piloting_NoVehicles_PlaysAsUnit
#// SOR_192 Ezra Bridger — with NO friendly Vehicles at all, a Piloting top card is played as a unit
#// with no mode prompt.

## GIVEN
CommonSetup: byw/byw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0
WithP1Deck: [JTL_196 SOR_189 SOR_189]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Play

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_196
P1DECKCOUNT:2
P1NODECISION
P1RESAVAILABLE:0
