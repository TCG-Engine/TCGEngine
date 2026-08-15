# DrawAttackBase_SelfDefeat
#// COVERAGE: offer=AttackerOffer_ReadyUnitsBothArenas_ExhaustedExcluded (pending SELECTABLEEXACT;
#//           both arenas, exhausted out) · decline=N/A (the attack instruction is mandatory once a
#//           legal attacker exists; the no-attacker soft branch is NoReadyUnit_DrawOnly)
#//           · boundary=ShieldedDefender_NoCombatDamage_NoSelfDefeat + AbilityDamageIsNotCombat
#//           Damage_NoSelfDefeat (combat-damage gate, both directions) + SurvivesCounter_
#//           StillSelfDefeats (defeat fires even on survival) · control=N/A (grant rides the
#//           chosen friendly attacker for one attack; no control-change window) · reqboundary=
#//           AbilityDamageIsNotCombatDamage_NoSelfDefeat (target, flamethrower YES and split
#//           resolve across separate serialized answers)
#// SOR_150 Heroic Sacrifice (Aggression/Heroism event, cost 1, Tactic) — "Draw a card, then attack with
#// a unit. For this attack, it gets +2/+0 and gains: 'When this unit deals combat damage: Defeat it.'"
#// P1 draws (deck 1 → 0, hand → 1), the attacker (SOR_095, 3/3) gets +2/+0 → deals 5 to the enemy base,
#// then is defeated by its granted self-defeat trigger (even though the base dealt no counter-damage).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P1HANDCOUNT:1
P2BASEDMG:5
P1GROUNDARENACOUNT:0

---

# NoReadyUnit_DrawOnly
#// SOR_150 Heroic Sacrifice — with no unit able to attack (only an EXHAUSTED unit present), the draw
#// still happens but there is no attack and no self-defeat. The exhausted unit survives.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P2BASEDMG:0
P1NODECISION

---

# SurvivesCounter_StillSelfDefeats
#// SOR_150 Heroic Sacrifice — the self-defeat fires on dealing combat damage to a UNIT too, and it
#// defeats the attacker even when it survives the counter. SOR_046 (3/7) gets +2/+0 → 5 power and must
#// attack the Sentinel SOR_063 (2/4): it kills the Sentinel (5 ≥ 4) and survives the 2-power counter
#// (7 HP), but the granted "when it deals combat damage: defeat it" still defeats it.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_063:1:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECKCOUNT:0
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0

---

# AttackerOffer_ReadyUnitsBothArenas_ExhaustedExcluded
#// SOR_150 Heroic Sacrifice — "attack with a unit": the attacker pool is every friendly unit that
#// can attack — the ready ground unit and the ready SPACE unit — and excludes the exhausted one.
#// The pick is left PENDING to pin the offer (the draw has already resolved: deck 1 → 0).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:0:0
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0
P1DECKCOUNT:0
P1HANDCOUNT:1

---

# ShieldedDefender_NoCombatDamage_NoSelfDefeat
#// SOR_150 Heroic Sacrifice — the granted defeat reads "when this unit DEALS COMBAT DAMAGE".
#// Attacking a Shielded defender, the Shield prevents all of the attacker's combat damage, so no
#// combat damage was dealt and the attacker is NOT defeated. SOR_046 (3/7, +2/+0 → 5 power)
#// attacks the shielded SEC_080: the Shield pops (0 damage through), the counter deals 3, and the
#// attacker survives in play at 3 damage.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# AbilityDamageIsNotCombatDamage_NoSelfDefeat
#// SOR_150 Heroic Sacrifice — non-combat damage dealt during the attack does NOT trip the granted
#// defeat. SOR_046 wearing SHD_177 (4/8; +2/+0 → 6 power) attacks the SHIELDED SEC_080: the
#// granted On Attack deals its 3 ability damage to the BYSTANDER wampa, the Shield then prevents
#// all combat damage to the defender, so the attacker dealt only ability damage this attack and
#// survives (counter 3). Nobody self-defeats; the wampa sits at 3.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_164:1:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-1:3

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:1:DAMAGE:3

---

# SimulateRequestBoundary_GrantedAttackModifiersSurviveFreshProcess
#// SOR_150 Heroic Sacrifice — in production the attacker's target pick, the flamethrower YES/NO and the
#// split-damage assignment each end the request, so every answer arrives in a FRESH process with all
#// transient globals empty. The +2/+0 "for this attack" buff, the granted "when this unit deals combat
#// damage: defeat it" and the combat-vs-ability damage bookkeeping must all live in the serialized
#// gamestate. Mirrors AbilityDamageIsNotCombatDamage_NoSelfDefeat with a boundary before every answer.

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_177
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_164:1:0
WithP1Deck: SOR_237
WithP1Hand: SOR_150

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1:3

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:1:DAMAGE:3
