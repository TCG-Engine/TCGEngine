# HeroismPenaltyApplies_WithoutKyloRen
#// SHD_046 Rey — without Kylo Ren, the Heroism aspect penalty applies. The base covers Vigilance but not
#// Heroism, so Rey costs 5 + 2 = 7 (7 resources → 0 left). Contrast with the Kylo-Ren waiver test.

## GIVEN
CommonSetup: brk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_046
P1RESAVAILABLE:0

---

# HeroismPenaltyWaived_WithKyloRen
#// SHD_046 Rey (base cost 5, Heroism/Vigilance) — "While playing this unit, ignore her Heroism aspect
#// penalty if you control Kylo Ren." P1's leader is Kylo Ren (SHD_011) and its base covers Vigilance but not
#// Heroism; with Kylo Ren the Heroism pip is waived, so Rey costs the printed 5 (7 resources → 2 left).

## GIVEN
CommonSetup: brk/rrk/{myLeader:SHD_011;myResources:7}
P1OnlyActions: true
WithP1Hand: SHD_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_046
P1RESAVAILABLE:2

---

# OnAttack_HealHeroism_NoShield
#// SHD_046 Rey — the Shield rider only fires for a NON-Heroism target. Healing the friendly SOR_046 (Heroism)
#// clears its 2 damage but grants no Shield.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_046:1:0
WithP1GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# OnAttack_HealNonHeroism_GivesShield
#// SHD_046 Rey (5-cost 4/7 ground) — "On Attack: You may heal 2 damage from a unit. If it's a non-Heroism
#// unit, give a Shield token to it." Rey heals the enemy SEC_080 (Villainy = non-Heroism, 2 damage → 0) and,
#// because it's non-Heroism, gives it a Shield.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_046:1:0
WithP2GroundArena: SEC_080:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
