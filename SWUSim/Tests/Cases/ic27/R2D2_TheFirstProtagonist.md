# Offer_AllThreeModes
#// IC27_151 R2-D2 — The First Protagonist (1, 1/3 Ground, Cunning/Heroism, Rebel·Droid, unique)
#// "On Attack: Choose one: • Ready a resource. • Deal 1 damage to a ground unit. • Look at the top card of your
#//  deck. You may discard it."
#// PREVIEW card (no official rulings). "Choose one" is a MANDATORY OPTIONCHOOSE; the house modal rule (HMW_221
#// Teeka, IC27_021 Grievous) drops a mode that could only fizzle, and with one mode left resolves it directly.
#//   • Ready a resource = one of YOUR resources — the engine-wide reading of the unqualified wording (IC27_007
#//     Asajj, SOR_015 Boba); an owner ruling on "any player's resource" is OPEN. Viable only with an exhausted one.
#//   • Deal 1 damage to a ground unit = ANY ground unit, either side, R2 himself included; always viable while he
#//     attacks. Mandatory once chosen.
#//   • Look at the top card, you may discard it = SOR_192 Ezra Bridger's shape (@CardID&Discard&Leave); viable
#//     only with a non-empty deck.
#//
#// COVERAGE: offer=Offer_AllThreeModes + Mode_Deal_Offer_EveryGroundUnit_BothSides + Mode_Look_ShowsDiscardOrLeave ·
#//           decline=Mode_Look_Leave (the "you may discard") · no-target=NoExhaustedResource_ReadyModeNotOffered +
#//           EmptyDeck_LookModeNotOffered + OnlyDealViable_NoMenu_StraightToTheTarget ·
#//           quantity=Mode_ReadyAResource (exactly ONE of two exhausted resources) ·
#//           timing=Mode_Deal_PingDefeatsTheDefender_BeforeCombatDamage ·
#//           reqboundary=RequestBoundary_AcrossTheModeChoice + RequestBoundary_AcrossTheLook ·
#//           blank=Blanked_R2D2_NoMenu ·
#//           modes=2P,TwinSuns ("a ground unit" is unqualified = the whole table) — TwinSuns_Deal_FarSeatGroundUnit
#//
#// An exhausted resource and a non-empty deck: all three modes are offered. Left pending.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1OPTIONHAS:Ready_a_resource
P1OPTIONHAS:Deal_1_damage_to_a_ground_unit
P1OPTIONHAS:Look_at_the_top_card

---

# Mode_ReadyAResource
#// Two exhausted resources: ONE is readied. Then R2's 1 lands on the base.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Ready_a_resource

## EXPECT
P1RESAVAILABLE:1
P2BASEDMG:1
P1DECKCOUNT:2

---

# NoExhaustedResource_ReadyModeNotOffered
#// All resources ready: "Ready a resource" could only fizzle, so it is not offered; the other two are.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1Resources: 2:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1OPTIONNOT:Ready_a_resource
P1OPTIONHAS:Deal_1_damage_to_a_ground_unit
P1OPTIONHAS:Look_at_the_top_card

---

# EmptyDeck_LookModeNotOffered
#// An empty deck: "Look at the top card" has nothing to look at, so it is not offered; the other two are.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: []

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1OPTIONNOT:Look_at_the_top_card
P1OPTIONHAS:Ready_a_resource
P1OPTIONHAS:Deal_1_damage_to_a_ground_unit

---

# OnlyDealViable_NoMenu_StraightToTheTarget
#// No exhausted resource AND an empty deck: only the damage mode is left, so no menu — the target pick opens
#// directly (pending), offering every ground unit.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: []

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# Mode_Deal_Offer_EveryGroundUnit_BothSides
#// "a ground unit" names no side: R2 himself, the friendly Marine and the enemy Dark Trooper are offered; space
#// units on both sides are not. Left pending after the mode choice.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: [SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Deal_1_damage_to_a_ground_unit

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# Mode_Deal_HitsTheChosenUnit
#// The damage mode on the enemy Dark Trooper: 1 damage; nothing else changes (resource stays exhausted, deck
#// untouched).

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: [SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Deal_1_damage_to_a_ground_unit
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:0
P1RESAVAILABLE:0
P1DECKCOUNT:1
P2BASEDMG:1

---

# Mode_Deal_PingDefeatsTheDefender_BeforeCombatDamage
#// On Attack resolves before combat damage. R2 (1/3) attacks a Death Star Stormtrooper (3/1) and pings it: it is
#// defeated before it can strike back, so R2 takes no damage — its 3 would otherwise have defeated him.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: [SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Deal_1_damage_to_a_ground_unit
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080

---

# Mode_Look_ShowsDiscardOrLeave
#// The look mode shows the top card to R2's controller and offers Discard or Leave. Left pending.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: [JTL_251 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Look_at_the_top_card

## EXPECT
P1OPTIONHAS:Discard
P1OPTIONHAS:Leave
P1DECKCOUNT:2

---

# Mode_Look_Discard
#// "You may discard it" — taken: the top card goes to the discard from the deck; the next card is now on top.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: [JTL_251 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Look_at_the_top_card
- P1>AnswerDecision:Discard

## EXPECT
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_095
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_251
P1DISCARDUNIT:0:FROM:DECK
P1HANDCOUNT:0

---

# Mode_Look_Leave
#// The DECLINE: leaving it changes nothing — same deck, same top card, no discard, no draw.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: [JTL_251 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Look_at_the_top_card
- P1>AnswerDecision:Leave

## EXPECT
P1DECKCOUNT:2
P1DECKTOPCARD:JTL_251
P1DISCARDCOUNT:0
P1HANDCOUNT:0
P2BASEDMG:1

---

# RequestBoundary_AcrossTheModeChoice
#// The damage mode with a request boundary between the mode choice and the target pick: same result.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: [SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Deal_1_damage_to_a_ground_unit
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# RequestBoundary_AcrossTheLook
#// The discard with a request boundary between the look and the answer: the SAME card (the one shown) is
#// discarded.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0
WithP1Resources: 2:SOR_095:0
WithP1Deck: [JTL_251 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Look_at_the_top_card
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Discard

## EXPECT
P1DISCARDUNIT:0:CARDID:JTL_251
P1DECKTOPCARD:SOR_095

---

# Blanked_R2D2_NoMenu
#// An R2-D2 that has lost all abilities (SOR_138 marker) offers nothing; the attack just lands.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_151:1:0:SOR_138
WithP1Resources: 2:SOR_095:0
WithP1Deck: [SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:1
P1RESAVAILABLE:0

---

# TwinSuns_Deal_FarSeatGroundUnit
#// Twin Suns (3 seats): "a ground unit" is the whole table, so seat 3's unit can take the ping.

## GIVEN
CommonSetup3P: rrk/bbw/bbw
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: IC27_151:1:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP1Deck: []

## WHEN
- P1>AttackGroundArena:0:p2Base-0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
P3GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
