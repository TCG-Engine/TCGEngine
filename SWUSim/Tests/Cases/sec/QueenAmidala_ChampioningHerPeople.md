# AttacksBase_NoPreventPrompt
#// SEC_101 Queen Amidala — ATTACKING A BASE: a base deals no counter-damage, so no damage would be dealt
#// to Amidala. Her "if damage would be dealt to this unit, you may defeat a trait-sharing friendly to
#// prevent it" must NOT prompt (previously it wrongly offered the sacrifice for nothing). She hits the
#// enemy base for 5, the trait-sharing friendly (SEC_118) survives, and there is no pending decision.
## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SEC_101:1:0
WithP1GroundArena: SEC_118:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_118
P1NODECISION

---

# IndirectDamageUnpreventable
#// SEC_101 Queen Amidala — INDIRECT damage is unpreventable (it writes Damage directly, never through
#//   SWUDealDamageToUnit / combat), so her prevention does NOT trigger. P1 plays JTL_116 (indirect damage
#//   to a player = its Vehicles) at P2; P2 simply gets the indirect MZSPLITASSIGN (NO prevention offer) and
#//   assigns it onto Amidala. She takes the damage and P2's Official SEC_118 is NOT sacrificed (count stays
#//   2). Confirms indirect ignores Amidala's effect.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;handCardIds:JTL_116}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:2

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:2

---

# PreventsAttackingCounterDamage
#// SEC_101 Queen Amidala — ATTACKING: P1's Amidala (5/3) attacks P2's SEC_080 (3/3), defeats it, and would
#//   take 3 counter (dying). P1 defeats its Official SEC_118 to prevent that counter → Amidala takes 0 and
#//   survives. Proves the prevention covers damage the unit takes while attacking.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SEC_101:1:0
WithP1GroundArena: SEC_118:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_101
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0

---

# PreventsDefendingDamage
#// SEC_101 Queen Amidala (5/3) — "If damage would be dealt to this unit, you may defeat another friendly
#//   unit that shares a trait with this unit (Naboo/Official). If you do, prevent that damage." DEFENDING:
#//   P2's Amidala is attacked by P1's SOR_046 (3 power) — she'd take 3 and die (3 HP). P2 defeats its
#//   Official SEC_118 to prevent → Amidala takes 0 and survives; she counters 5 onto SOR_046.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:5

---

# PreventsOpenFire_SpySacrifice
#// SEC_101 Queen Amidala — EVENT/ability damage (SOR_172 Open Fire, "Deal 4 damage to a unit") with a SPY
#// as the sacrifice. P1 Open-Fires P2's Amidala for 4 (lethal — she has 3 HP). The ability-damage funnel
#// (SWUDealDamageToUnit) defers and offers P2 the prevention; P2 defeats a Spy token (SEC_T01, Official —
#// shares a trait with Amidala) → Amidala takes 0 and survives, the Spy is gone. (Guards the non-combat
#// single-target prevent path with a token sacrifice.)
## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1

---

# PreventsOpponentEventDamage
#// SEC_101 Queen Amidala — OPPONENT EVENT (ability damage). P1 plays Contempt for Culture (SEC_246, "deal
#//   2 to a non-Vehicle unit") targeting P2's Amidala. The ability-damage funnel defers and offers P2 the
#//   prevention; P2 defeats its Official SEC_118 → Amidala takes 0. (Proves the SWUDealDamageToUnit path.)

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:SEC_246}
P1OnlyActions: true
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1

---

# PreventsOpponentWhenPlayedDamage
#// SEC_101 Queen Amidala — OPPONENT WHEN-PLAYED (ability damage). P1 plays Death Trooper (SEC_030, "When
#//   Played: deal 2 to a friendly ground unit and 2 to an enemy ground unit"); it deals 2 to itself, then
#//   2 to P2's Amidala. The enemy-damage funnel defers and offers P2 the prevention; P2 defeats its
#//   Official SEC_118 → Amidala takes 0. Same SWUDealDamageToUnit path as the event case.

## GIVEN
CommonSetup: brk/ggw/{myResources:7;handCardIds:SEC_030}
P1OnlyActions: true
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_118:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1

---

# PreventsSplitDamage_SOR092
#// SEC_101 Queen Amidala — SPLIT/DIVIDED damage prevention (SOR_092 Overwhelming Barrage). P1 buffs its
#// SEC_080 (3/3 → 5/5) and divides its 5 power among P2's units: 1 to Amidala (already at 2 damage, so
#// lethal), 2 to each of two Spy tokens (SEC_T01, 0/2 — lethal). Divided damage is simultaneous (CR 34/35.5),
#// so Amidala's "if damage would be dealt to this unit, you may defeat a trait-sharing friendly to prevent
#// it" fires: P2 defeats a Spy (Official, shares a trait) to prevent the 1 that would have killed her. End
#// state: BOTH Spies defeated (one as the prevent cost, one from its own 2 damage) but Amidala lives at 2.
## GIVEN
CommonSetup: ggk/ggw/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_101:1:2
WithP2GroundArena: SEC_T01:1:0
WithP2GroundArena: SEC_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:1,theirGroundArena-1:2,theirGroundArena-2:2
- P2>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:1

---

# SplitDamage_DeclinePrevent_Dies
#// SEC_101 Queen Amidala — SPLIT damage, DECLINE the prevention. Same 1/2/2 split from SOR_092 as the
#// take-branch test, but P2 declines Amidala's optional prevent (AnswerDecision:-). The declined hit is
#// re-parked and applied with the rest, so Amidala's 1 lands (2 → 3 = lethal) and both Spies take their 2
#// (lethal) — all three P2 units are defeated. Proves the decline path applies the damage simultaneously.
## GIVEN
CommonSetup: ggk/ggw/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_101:1:2
WithP2GroundArena: SEC_T01:1:0
WithP2GroundArena: SEC_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:1,theirGroundArena-1:2,theirGroundArena-2:2
- P2>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
