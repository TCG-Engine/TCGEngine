# RaidDoubled
#// LOF_186 Marchion Ro — Each friendly unit's Raid is doubled. LOF_136 (Raid 3, power 3) attacks the base:
#// its Raid is doubled to 6, so the base takes 3 + 6 = 9.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_136:1:0
WithP1GroundArena: LOF_186:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:9

---

# EnemyRaidNotDoubled
#// LOF_186 Marchion Ro — "Each FRIENDLY unit's Raid is doubled" does NOT affect enemy units. P2's Green
#// Squadron A-Wing (SOR_141, power 1, Raid 2) attacks P1's base: Raid stays 2, so the base takes 1 + 2 = 3,
#// not 1 + 4. Intended: "does not double the raid value of enemy units."

## GIVEN
CommonSetup: rrk/ggw
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_186:1:0
WithP2SpaceArena: SOR_141:1:0

## WHEN
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:3

---

# OwnRaidDoubled
#// LOF_186 Marchion Ro — the doubling applies to Marchion himself. Clone Cohort (TWI_169) is attached, giving
#// him Raid 2; Marchion's ability doubles it to Raid 4. Marchion (power 6) attacks the base: 6 + 4 = 10.
#// Intended: "doubles Marchion Ro's own raid value."

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_186:1:0
WithP1GroundArenaUpgrade: 0:TWI_169

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:10

---

# RestoreNotDoubled
#// LOF_186 Marchion Ro — only Raid is doubled, not other numeric keywords. Darth Sidious (LOF_039, power 6,
#// Restore 2) attacks P2's base: the base takes 6, and P1's base heals only 2 (not 4), so its damage drops
#// 5 → 3. Intended: "does not double other numeric keywords."

## GIVEN
CommonSetup: rrk/ggw/{myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: LOF_186:1:0
WithP1GroundArena: LOF_039:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:6
P1BASEDMG:3

---

# StackedRaidDoubledAfterSumming
#// LOF_186 Marchion Ro — the multiplier applies AFTER all stacked Raid effects are summed. Fifth Brother
#// (SOR_131, base power 2) has 2 damage → Raid 2 from his own ability, plus Clone Cohort (TWI_169, Raid 2)
#// and Constructed Lightsaber (LOF_261, Villainy → Raid 2, +2/+3 stats). Total Raid 6, doubled to 12; power
#// 2 + 2 = 4. Attacking the base deals 4 + 12 = 16. Fifth Brother's optional On-Attack damage is declined.
#// Intended: "applies the multiplier after all stacked raid effects."

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_186:1:0
WithP1GroundArena: SOR_131:1:2
WithP1GroundArenaUpgrade: 1:TWI_169
WithP1GroundArenaUpgrade: 1:LOF_261

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2BASEDMG:16
