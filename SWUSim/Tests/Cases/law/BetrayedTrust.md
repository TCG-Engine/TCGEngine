# NoCombatDamage
#// LAW_130 Betrayed Trust (Vigilance event, cost 2) — "Choose an enemy unit. For this phase, that unit
#// can't deal combat damage." Mark P2's SOR_046, then P1's SOR_095 attacks it: SOR_046 takes 3 but deals
#// NO counter-damage, so SOR_095 ends undamaged.
#// COVERAGE: offer=OfferIncludesDeployedLeaderUnit (exact target set incl. a deployed enemy leader unit)
#//           · decline=N/A ("choose an enemy unit" is mandatory; the no-target play is NoEnemyUnitsFizzle)
#//           · reqboundary=BuffedByOpponentTrick_StillNoCombatDamage + ExpiresNextActionPhase (the marker
#//           must survive opponent actions and phase machinery across many round-trips) ·
#//           boundary=ExpiresNextActionPhase (0 dmg in the marked phase vs full 6 the next) ·
#//           control=N/A (no scenario moves the marked unit between players)

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_130

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# CantDealCombatToBase
#// LAW_130 Betrayed Trust — the marked enemy unit can't deal combat damage even while ATTACKING our base.
#// P1 marks P2's AT-ST (SOR_232, 6 power); P2 then attacks P1's base with it (AT-ST exhausts, proving the
#// attack happened) but deals 0. (P1's own attack afterward is only there to resolve the exchange so the
#// board can be read mid-phase.)

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
WithP1Hand: LAW_130
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
PHASE:MAIN
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:EXHAUSTED
P1BASEDMG:0

---

# EnemyCantDamageDefendingUnit
#// LAW_130 Betrayed Trust — marked enemy deals no combat damage when it ATTACKS a friendly unit either.
#// P1 marks P2's AT-ST (SOR_232, 6 power); P2 attacks P1's Consular Security Force (SOR_046, 3/7): the
#// Consular takes 0, yet still deals its 3 back to AT-ST.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
WithP1Hand: LAW_130
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0
- P1>AttackGroundArena:0:BASE

## EXPECT
PHASE:MAIN
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# PreventsCombatToSpaceUnit
#// LAW_130 Betrayed Trust — works in the space arena. P1 marks P2's Desperado Freighter (SHD_152, 5/6),
#// which attacks P1's HWK-290 Freighter (SHD_060, 2/5): the HWK takes 0, the freighter takes 2 counter.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
WithP1Hand: LAW_130
WithP1SpaceArena: SHD_060:1:0
WithP2SpaceArena: SHD_152:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackSpaceArena:0:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
PHASE:MAIN
P1SPACEARENAUNIT:0:CARDID:SHD_060
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:2

---

# DoesNotPreventNonCombatDamage
#// LAW_130 Betrayed Trust only stops COMBAT damage — a marked unit's ability damage still lands. P1 marks
#// P2's Bendu (LOF_170, 10/10, "On Attack: Deal 3 damage to each other unit"). Bendu attacks P1's base:
#// the combat hit is prevented (base = 0), but its On Attack ability still deals 3 to P1's Consular.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
WithP1Hand: LAW_130
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_170:1:0

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
PHASE:MAIN
P1BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# NoEnemyUnitsFizzle
#// LAW_130 Betrayed Trust — with no enemy units to choose, it simply resolves with no effect (it still
#// leaves hand for the discard pile; no crash / no hang).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1Hand: LAW_130

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# MultiDefender_MarkedDealsNoCounter
#// LAW_130 Betrayed Trust — the "can't deal combat damage" marker also works in a MULTI-defender attack.
#// P1 marks one of two enemy Battlefield Marines, then Darth Maul (TWI_135, 5 power) attacks BOTH. He deals
#// 5 to each (both defeated); only the UNMARKED marine deals its 3 counter, so Maul takes 3 (not 6).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP1Hand: LAW_130
WithP1Deck: SOR_046
WithP2Deck: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0

---

# BuffedByOpponentTrick_StillNoCombatDamage
#// LAW_130 Betrayed Trust — a unit marked "deals no combat damage this phase" still deals none even after
#// the opponent pumps it. P1 plays Betrayed Trust on the enemy AT-ST (SOR_232). The turn passes to P2, who
#// plays Surprise Strike (SOR_220, "+3/+0 for this attack") on the AT-ST and attacks P1's base — 0 damage.
#// (Demonstrates an OPPONENT playing a card mid-phase: P1 event → turn swaps → P2 plays + attacks.)

## GIVEN
CommonSetup: bbw/yyk/{myResources:2}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP2Resources: 2
WithP1Hand: LAW_130
WithP2Hand: SOR_220
WithP2GroundArena: SOR_232

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:theirBase-0

## EXPECT
P1BASEDMG:0

---

# MarkedMultiAttacker_NoDamageToEitherDefender
#// LAW_130 Betrayed Trust — a MARKED multi-attacker deals no combat damage to EITHER defender. P1 marks
#// P2's Darth Maul (TWI_135, 5/6, attacks 2 units); P2 attacks P1's Consular (3/7) AND Marine (3/3) with
#// him: both take 0, while both still deal their combat damage back (3+3=6), defeating Maul.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP2GroundArena: TWI_135:1:0
WithP1Hand: LAW_130

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0
- P2>AnswerDecision:Units
- P2>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENACOUNT:0

---

# BothDefendersMarked_NoCounterAtAll
#// LAW_130 Betrayed Trust — when BOTH defenders of a 2-unit attack are marked, neither deals its combat
#// damage back. P1 plays two copies, one on each of P2's Consular (3/7) and Marine (3/3), then Darth Maul
#// (TWI_135, 5 power) attacks both: he deals 5 to each (Marine defeated, Consular at 5) and takes 0.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: TWI_135:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP1Hand: [LAW_130 LAW_130]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Units
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_135
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# PowerUnchangedForPowerCaringEffects
#// LAW_130 Betrayed Trust removes the ability to deal COMBAT damage — it does NOT change the unit's
#// power. P1 marks P2's AT-ST (SOR_232, 6 power); P2 then plays Strike True (SOR_127, "a friendly unit
#// deals damage equal to its power to an enemy unit"): the AT-ST still deals its full 6 (ability damage,
#// power intact) to P1's Consular.

## GIVEN
CommonSetup: bbw/ggw/{myResources:2; theirResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_232:1:0
WithP1Hand: LAW_130
WithP2Hand: SOR_127

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:6

---

# RemainingHPAttack_StillNoCombatDamage
#// LAW_130 Betrayed Trust — "deals damage equal to its remaining HP instead of its power" is still
#// COMBAT damage, so a marked unit deals none of it. P1 marks P2's Super Battle Droid (TWI_230, 4/3);
#// P2 uses Babu Frik (LOF_206, "Action [Exhaust]: You may attack with a friendly Droid unit. For this
#// attack, it deals damage equal to its remaining HP instead of its power.") to attack P1's base with
#// the marked Droid — 0 damage (would be 3 unmarked).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP2GroundArena: [LOF_206:1:0 TWI_230:1:0]
WithP1Hand: LAW_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P2>UseUnitAbility:myGroundArena-0
- P2>AnswerDecision:myGroundArena-1
- P2>AnswerDecision:theirBase-0

## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# VigilAmplifierDoesNotTurnZeroIntoOne
#// LAW_130 Betrayed Trust × Vigil (SEC_050: "If damage would be dealt to this unit by another card,
#// deal that much damage plus 1 instead."). A marked attacker deals NO combat damage at all — there is
#// no damage event for Vigil's +1 replacement to amplify, so Vigil takes 0 (not 1). P1 marks P2's
#// Desperado Freighter (5/6), which attacks Vigil: Vigil at 0, freighter takes Vigil's 5 counter.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1SpaceArena: SEC_050:1:0
WithP2SpaceArena: SHD_152:1:0
WithP1Hand: LAW_130

## WHEN
- P1>PlayHand:0
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_050
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:5

---

# ExpiresNextActionPhase
#// LAW_130 Betrayed Trust — "for this phase" only. P1 marks P2's AT-ST (SOR_232, 6 power); P2 attacks
#// P1's base with it for 0. The round ends (both decks seeded so the regroup draw causes no empty-deck
#// base damage), the AT-ST readies, and in the NEXT action phase its attack deals the full 6.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP2GroundArena: SOR_232:1:0
WithP1Hand: LAW_130
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2Deck: SOR_095
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:BASE
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
PHASE:MAIN
P1BASEDMG:6

---

# OfferIncludesDeployedLeaderUnit
#// LAW_130 Betrayed Trust — "choose an enemy unit" can choose a deployed enemy LEADER unit too. P2 has
#// a deployed leader and an AT-ST; the section ends on the pending target picker and asserts BOTH are
#// offered (and nothing else — P1's own unit and the bases are not legal targets).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2; theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_232:1:0
WithP1Hand: LAW_130

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
