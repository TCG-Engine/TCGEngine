# FirstCreatureDiscount
#// LOF_108 Malakili — The first Creature unit you play each phase costs 1 resource less. With Malakili in
#// play, P1 plays LOF_063 (a Creature, cost 3) for 2, leaving 1 resource.

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_063}
P1OnlyActions: true
WithP1GroundArena: LOF_108:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:1

---

# PreventsFriendlyCreatureDamage
#// LOF_108 Malakili — "If a friendly Creature unit would deal damage to a friendly unit, prevent that
#// damage." Bendu (LOF_170, a Creature) attacks the P2 base; its On Attack deals 3 to each other unit.
#// With Malakili in play, P1's own units (Malakili + SOR_046) take 0 — prevented — while the enemy
#// SOR_046 takes the full 3.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_170:1:0
WithP1GroundArena: LOF_108:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:10
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:2:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# SecondCreatureNoDiscount
#// LOF_108 Malakili — the discount is only for the FIRST Creature played each phase. With Malakili in play,
#// P1 plays Wampa (SOR_164, Creature, cost 4 → 3) then Tuk'ata (LOF_161, Creature, cost 3 → full 3). Total 6
#// with 6 resources leaves 0; if the second were wrongly discounted, 1 would remain. Intended: "the second unit
#// we play must not have a discount".

## GIVEN
CommonSetup: rrk/ggk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: LOF_108:1:0
WithP1Hand: SOR_164 LOF_161

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0

---

# NonCreatureDiscountDoesNotApply
#// LOF_108 Malakili — the discount applies only to Creature units. P1 plays Cantina Braggart (SOR_157, an
#// Underworld non-Creature, cost 1). With exactly 1 resource it is played at full cost, leaving 0; a wrong
#// discount would make it free and leave 1. Intended: "discount work only for Creature".

## GIVEN
CommonSetup: rrk/ggk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: LOF_108:1:0
WithP1Hand: SOR_157

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0

---

# AttackDamageNotPrevented
#// LOF_108 Malakili — the "prevent that damage" clause only covers a friendly Creature damaging a friendly
#// unit; it does not touch combat/attack damage against enemies. A friendly Wampa (SOR_164, Creature)
#// attacks the enemy 3/7 Consular Security Force: Wampa deals 4 to the enemy and takes 3 counter, both
#// applied normally. Intended: "should not prevent attack damage".

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_108:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:1:DAMAGE:3

---

# PreventsDividedDamageFromFriendlyCreature
#// LOF_108 Malakili — "If a friendly Creature unit would deal damage to a friendly unit, prevent that
#// damage." CR 9.12: "If a unit's ability deals damage, that unit is considered to have dealt that
#// damage" — so DIVIDED damage from an ability that names a dealer unit is dealt BY that unit, and
#// Malakili must prevent the share assigned to a friendly.
#// P1 plays ASH_139 Hold Them Off choosing LOF_063 Oggdo Bogdo (a Creature, power 5) as the dealer and
#// assigns 2 to the friendly SOR_046 and 3 to the enemy SOR_046. The friendly share is prevented; the
#// enemy share lands in full. The enemy assertion is the control: it proves the prevention is scoped to
#// friendly targets and that the split itself resolved.

## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
P1OnlyActions: true
WithP1GroundArena: LOF_063:1:0    # Oggdo Bogdo — Creature, 5 power (the dealer)
WithP1GroundArena: LOF_108:1:0    # Malakili
WithP1GroundArena: SOR_046:1:0    # friendly 3/7 — takes 2, prevented to 0
WithP2GroundArena: SOR_046:1:0    # enemy 3/7 — takes the full 3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-2:2,theirGroundArena-0:3

## EXPECT
P1GROUNDARENAUNIT:2:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3
