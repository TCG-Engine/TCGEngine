# Damaged_GainsRaid3_BaseTakesSix
#// IC27_103 Grand Inquisitor (How The Mighty Will Fall) — 4-cost 3/6 [Aggression][Villainy] Force/Imperial/Inquisitor.
#// "While this unit is damaged, he gains Raid 3 and Saboteur."
#// One condition, two grants, read by two different engine functions (GetConditionalKeyword_Raid_Value
#// and HasConditionalKeyword_Saboteur), so each grant gets its own positive, negative and blank section.
#// ONE damage is the boundary (damaged vs undamaged is 1 vs 0 — the partner section below).
#// POWER stays 3: Raid is "+3/+0 WHILE ATTACKING", not a stat buff (a Scimitar-style +3/+0 would still
#// deal 6 to the base here, but it would also hit back for 6 while defending — see DefendingWhileDamaged).
#//
#// COVERAGE: offer=Damaged_Saboteur_CanAttackTheNonSentinelUnit + Undamaged_SentinelPresent_NonSentinelUnitIsNotAttackable
#//           (the attack-target pool; the harness VALIDATES an injected target against it)
#//           decline=N/A (no optional clause — both grants are continuous)
#//           boundary=Damaged_GainsRaid3_BaseTakesSix (1 damage) vs Undamaged_NoRaid_BaseTakesThree (0)
#//           control=N/A (self-referential: "this unit is damaged" names no player, zone or controller)
#//           reqboundary=BecomesDamagedOnTheirTurn_GainsBothAcrossTheBoundary
#//           modes=2P only (no player reference, no friendly/enemy wording)
#//           PREVIEW ASSUMPTION: Raid 3 SUMS with any other Raid (user ruling 2026-09-10, CR 7.5.8.b) —
#//           RaidSumsWithAGrantedRaid.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# Undamaged_NoRaid_BaseTakesThree
#// The boundary partner: zero damage, no grant — printed power only.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# Damaged_Saboteur_AttacksBasePastSentinel
#// Saboteur half: an enemy Sentinel no longer forces the attack onto itself. Raid rides along (6).

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:1
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# Undamaged_SentinelForcesTheAttack
#// Saboteur negative: undamaged, the Sentinel is the ONLY legal target, so the attack aimed at the base
#// resolves against the 2/4 Wing Guard instead (3 damage, it survives; 2 back onto the Inquisitor).

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Damaged_Saboteur_CanAttackTheNonSentinelUnit
#// The OFFER cell. declareAttack validates an injected target against the attack-target pool and throws
#// on an out-of-pool pick, so landing the attack on the Battlefield Marine (idx 1) proves Saboteur put a
#// non-Sentinel unit into the pool. Raid 3 makes it 6 → the 3/3 dies; 3 back → Inquisitor on 4 damage.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:1
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:0

---

# Undamaged_SentinelPresent_NonSentinelUnitIsNotAttackable
#// The offer negative on the same board: undamaged, the pool is the Sentinel alone, so the attack aimed
#// at the Marine resolves against the Wing Guard and the Marine is untouched.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# Damaged_Saboteur_DefeatsTheDefendersShield
#// Saboteur's OTHER half (a separate engine read from the Sentinel bypass): the defender's Shield is
#// defeated when the attack begins, so all 6 lands on the 4/7 Industrious Team (it survives on 1).

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:1
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:5

---

# Undamaged_TheShieldAbsorbsTheHit
#// Shield negative: no Saboteur, so the Shield absorbs the whole attack.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# RaidSumsWithAGrantedRaid
#// USER RULING 2026-09-10: Raid stacks (CR 7.5.8.b). A granted Raid 2 plus his Raid 3 is Raid 5 —
#// 3 + 5 = 8 to the base. max() would read 3 (→ 6); the grant alone would read 2 (→ 5).

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:1:RAID-2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:5

---

# DefendingWhileDamaged_NoRaid
#// Raid only applies while ATTACKING. Damaged and defending, he hits back for his printed 3 — the
#// 4/7 Industrious Team ends on 3 damage, not 6. He takes 4 (1 + 4 = 5 of 6) and survives.

## GIVEN
CommonSetup: rrk/bbw
WithActivePlayer: 2
WithP1GroundArena: IC27_103:1:1
WithP2GroundArena: LAW_124:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:5

---

# BecomesDamagedOnTheirTurn_GainsBothAcrossTheBoundary
#// Live recompute, GAINING direction, across the request boundary. He starts undamaged; the opponent's
#// Battlefield Marine attacks him on their turn (3 damage in, the Marine dies to his 3 back). On his own
#// next action — a fresh request — he is damaged, so he attacks the base straight past the Sentinel
#// (Saboteur) for 6 (Raid 3). Nothing about the grant is stored: it is re-read from Damage every time.

## GIVEN
CommonSetup: rrk/bbw
WithActivePlayer: 2
WithP1GroundArena: IC27_103:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:6

---

# HealedToZero_LosesBoth
#// Live recompute, LOSING direction. Too Strong for Blasters (IBH_066, Vigilance, 1 + 2 off-aspect = 3)
#// heals his 2 damage; undamaged again, he has neither keyword, so his attack at the base is pulled
#// onto the Sentinel for his printed 3.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3;myhandCardIds:IBH_066}
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:2
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DISCARDCOUNT:1
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Blanked_NoRaid
#// A unit that has lost all abilities (SOR_138 Force Lightning's marker) gains nothing, damaged or not.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:1:SOR_138

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:0

---

# Blanked_NoSaboteur_SentinelForcesTheAttack
#// The blank reaches the Saboteur half too (a different reader from Raid's), so the Sentinel still
#// pulls the attack.

## GIVEN
CommonSetup: rrk/bbw
P1OnlyActions: true
WithP1GroundArena: IC27_103:1:1:SOR_138
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
