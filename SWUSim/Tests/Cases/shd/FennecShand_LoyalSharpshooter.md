# OnAttack_DamagePerDistinctDiscardCost
#// SHD_220 Fennec Shand (7-cost 4/6 ground) — Ambush + "On Attack: Deal 1 damage to the defender (if it's a
#// unit) for each DIFFERENT cost among cards in your discard pile." Discard holds SOR_095 (cost 2), SHD_038
#// (cost 2), SHD_178 (cost 1) → 2 DISTINCT costs (not 3 cards), so the On Attack deals 2 to SOR_046; combined
#// with Fennec's 4 combat power, SOR_046 (7 HP) takes 6.
#// COVERAGE: offer=N/A (the On Attack has no target pick — it always hits "the defender", which the
#//           attack declaration already fixed) · decline=N/A (no "you may"; the extra damage is
#//           mandatory) · boundary=OnAttack_EmptyDiscard_NoBonusDamage (0 distinct costs → +0) vs
#//           OnAttack_DamagePerDistinctDiscardCost (3 discard cards but only 2 DISTINCT costs → +2, the
#//           discriminator between counting costs and counting cards); OnAttack_AgainstABase_NoBonusDamage
#//           is the "(if it's a unit)" bound — a base defender gets combat damage only ·
#//           control=N/A (one-shot damage to the current defender; nothing persistent is created that
#//           could change controller) · reqboundary=N/A (the distinct-cost count is recomputed from the
#//           live discard pile at each attack — no marker is stored to survive a serialization round-trip)

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_220:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Discard: [SOR_095 SHD_038 SHD_178]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# OnAttack_EmptyDiscard_NoBonusDamage
#// SHD_220 Fennec Shand — the zero bound of "for each DIFFERENT cost among cards in your discard pile":
#// with an EMPTY discard the count is 0, so the On Attack adds nothing and SOR_046 (3/7) takes only
#// Fennec's 4 combat damage instead of 6. No decision is raised for the zero-damage case. Fennec takes
#// SOR_046's 3 back.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_220:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:CARDID:SHD_220
P1GROUNDARENAUNIT:0:DAMAGE:3
P1DISCARDCOUNT:0
P1NODECISION

---

# OnAttack_AgainstABase_NoBonusDamage
#// SHD_220 Fennec Shand — the "(if it's a unit)" gate. The same 2-distinct-cost discard as the section
#// above, but Fennec attacks the BASE: the On Attack has no unit defender to hit, so the base takes only
#// her 4 combat damage, NOT 4+2. Fennec is undamaged (a base does not deal combat damage back).

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_220:1:0
WithP1Discard: [SOR_095 SHD_038 SHD_178]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:CARDID:SHD_220
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:EXHAUSTED
