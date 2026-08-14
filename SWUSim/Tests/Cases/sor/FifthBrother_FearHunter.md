# UndamagedAttack_NoRaid_BaseTakesPrintedTwo
#// SOR_131 Fifth Brother, Fear Hunter (Ground, 2/4 UQ, Aggression/Villainy) — "This unit gains Raid 1
#//   for each damage on him. On Attack: You may deal 1 damage to this unit and 1 damage to another
#//   ground unit." Undamaged, he has no Raid (keyword absent) and hits the base for his printed 2.
#//   The spare '-' answer passes through harmlessly today and declines the optional On Attack ping
#//   if one is offered.
#// COVERAGE: offer=N/A (no section raises a pending choice; the On Attack target pick is the card's
#//           only offer and no trigger-branch section exists yet — see the decline lines) ·
#//           reqboundary=NoRaidWhenDefending_PowerStaysPrinted (state is re-read across separate
#//           serialized actions) · control=N/A (Raid reads only the unit's own Damage; no per-unit
#//           marker or seat-bound state) · boundary pair=this section vs OneDamage_RaidOne (0 vs 1
#//           damage on him) · decline=the '-' answers here and in the Raid sections (optional
#//           On Attack ping declined; end state identical)

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: [SOR_131:1:0 SOR_164:1:0]
WithP2GroundArena: SOR_239:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OneDamage_RaidOne
#// One damage on him = Raid 1: the 2-power attack hits the base for 3.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: [SOR_131:1:1 SOR_164:1:0]
WithP2GroundArena: SOR_239:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# TwoDamage_RaidTwo_KeywordVisible
#// The Raid grant scales per point of damage: with 2 damage on him the base takes 2+2 = 4, and the
#//   Raid keyword is visible on the damaged unit at end state.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: [SOR_131:1:2 SOR_164:1:0]
WithP2GroundArena: SOR_239:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# NoRaidWhenDefending_PowerStaysPrinted
#// Raid boosts power only WHILE ATTACKING (CR: Raid is an attacking-only modifier). The damaged Fifth
#//   Brother (1 damage) DEFENDS against the enemy Rebel Pathfinder (2/3): the Pathfinder takes only
#//   his printed 2 (Raid 1 would have killed it at 3) and survives; Fifth Brother ends at 3 damage.

## GIVEN
CommonSetup: rrk/ggw
WithActivePlayer: 2
WithP1GroundArena: SOR_131:1:1
WithP2GroundArena: SOR_239:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Raid_AppliesAgainstUnitsToo
#// Raid is not base-only: with 1 damage on him (power 2+1) Fifth Brother attacks the Rebel
#//   Pathfinder (2/3) and kills it exactly (without Raid, 2 power would have left it alive). He
#//   takes 2 back and survives at 3 damage.

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: [SOR_131:1:1 SOR_164:1:0]
WithP2GroundArena: SOR_239:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P2BASEDMG:0

---

# OnAttack_OfferPool_OtherGroundBothSidesSelfExcluded
#// The On Attack ping targets "another ground unit" — BOTH sides, self excluded, space excluded.
#// Offer asserted while pending.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_131:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0

---

# OnAttack_Accept_SelfPingFeedsRaidSameAttack
#// Accepting deals 1 to Fifth Brother and 1 to the chosen enemy — and the fresh self-damage feeds his
#// Raid-per-damage on THIS attack: printed 2 + Raid 1 = 3 to the base.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_131:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:3

---

# OnAttack_Declined_PrintedDamageOnly
#// Declining the optional ping: nobody takes ping damage and the base takes the printed 2.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_131:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:2

---

# OnAttack_NoOtherGroundUnit_NoPrompt
#// With no OTHER ground unit anywhere the compound ping cannot resolve — no prompt (empty pool), the
#// attack just deals the printed 2.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_131:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:2

---

# OnAttack_LethalSelfPing_NoCombatDamage
#// Fifth Brother at 3 damage (1 remaining HP) accepts the ping: the self-damage defeats him BEFORE
#// combat damage, so the base takes nothing and the chosen enemy still takes its 1.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_131:1:3
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:0
