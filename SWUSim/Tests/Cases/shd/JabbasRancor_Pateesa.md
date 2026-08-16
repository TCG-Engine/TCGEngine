# CostReduction_WithJabba
#// COVERAGE: offer=Offer_AnotherFriendlyGroundExcludesTheRancorItself (pending SELECTABLEEXACT — the
#//           Rancor itself, the friendly SPACE unit and the enemy ground unit are all out of the friendly
#//           pool) · boundary=the discount on/off pair — CostReduction_WithJabba (Jabba as LEADER, 7) and
#//           CostReduction_JabbaAsUnit (Jabba as UNIT, 7) vs WhenPlayed_DamageBothSides_FullCost (no Jabba,
#//           8); plus the partial-resolution pair WhenPlayed_FriendlyHalfLandsWhenEnemyHasNoGroundUnit vs
#//           CostReduction_WithJabba (neither half has a target) · decline=N/A — the printed text has no
#//           "may" on either damage clause · control=N/A — "another friendly"/"an enemy" are read live from
#//           the resolving controller's seat and the card leaves no per-unit marker behind ·
#//           reqboundary=N/A — both clauses and the cost check resolve inside a single action, so nothing
#//           is written by one request and read by a later one
#// SHD_091 Jabba's Rancor — "If you control Jabba the Hutt (as a leader or unit), this unit costs 1 less."
#// With Jabba (SHD_006) as P1's leader, the 8-cost Rancor costs 7 → 1 resource left of 8. Played with no
#// other units, the When Played damage has no valid targets and fizzles (no decision).

## GIVEN
CommonSetup: grk/grk/{myLeader:SHD_006;myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_091

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_091
P1NODECISION

---

# OnAttack_DamageBothSides
#// SHD_091 Jabba's Rancor — the same "deal 3 to another friendly ground + 3 to an enemy ground" also fires
#// On Attack. Proves the OnAttack-safe MZMAYCHOOSE path: Rancor attacks the base, the OnAttack rider damages
#// SOR_046 (friendly) and SEC_080 (enemy) by 3 each.

## GIVEN
CommonSetup: grk/grk
P1OnlyActions: true
WithP1GroundArena: SHD_091:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayed_DamageBothSides_FullCost
#// SHD_091 Jabba's Rancor (8-cost 9/9 ground, Command/Villainy) — When Played: Deal 3 to another friendly
#// ground unit AND 3 to an enemy ground unit. Without Jabba the cost is the full 8 (grk leader/base cover
#// Command+Villainy, no penalty → 8 spent, 0 left). Friendly damage lands on SOR_046 (7 HP); enemy on SEC_080.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_091
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SHD_091
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# CostReduction_JabbaAsUnit
#// SHD_091 Jabba's Rancor — "If you control Jabba the Hutt (AS A LEADER OR UNIT), this unit costs 1
#// resource less." The leader form is covered by CostReduction_WithJabba; this is the UNIT form. P1's
#// leader here is NOT a Jabba (Command/Villainy generic pair); the only Jabba on the board is the
#// SOR_181 Jabba the Hutt UNIT in the ground arena, and the 8-cost Rancor still costs 7 (1 of 8 left).
#// The When Played damage then lands: 3 on SOR_181 (2/8, survives) and 3 on the enemy SOR_046 (3/7, survives).

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_091
WithP1GroundArena: SOR_181:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:CARDID:SOR_181
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SHD_091
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Offer_AnotherFriendlyGroundExcludesTheRancorItself
#// SHD_091 Jabba's Rancor — "Deal 3 damage to ANOTHER friendly ground unit". The friendly pick is left
#// pending here so the offer itself can be asserted: only the two pre-seated friendly GROUND units are in
#// the pool. The Rancor is excluded ("another"), the friendly SPACE unit is excluded (ground only), and the
#// enemy ground unit is not in this pool either (it belongs to the second, separate pick).

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_091
WithP1GroundArena: [SOR_046:1:0 SEC_080:1:0]
WithP1SpaceArena: JTL_069:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# WhenPlayed_FriendlyHalfLandsWhenEnemyHasNoGroundUnit
#// SHD_091 Jabba's Rancor — the two halves of "deal 3 damage to another friendly ground unit AND 3 damage
#// to an enemy ground unit" resolve independently. Here the enemy controls only a SPACE unit, so the enemy
#// half has no legal target and does nothing, while the friendly half still lands its 3 on SOR_046.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_091
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:DAMAGE:0
P1NODECISION


---

# WhenPlayed_EnemyHalfLandsWhenNoOtherFriendlyGroundUnit
#// SHD_091 — the two halves of "deal 3 to another friendly ground unit AND 3 to an enemy ground unit" are
#// INDEPENDENT. Here P1 controls no OTHER friendly ground unit (only a friendly SPACE unit, which the
#// "ground" restriction excludes), so the friendly half has no legal target — and the enemy half must
#// still deal its 3 to the lone enemy ground unit. Mirror of the section that drops the enemy half.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_091
WithP1SpaceArena: JTL_069:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_091
P1RESAVAILABLE:0
P1SPACEARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
