# WhenPlayed_Heal4WithVigilance
#// SHD_066 Cargo Juggernaut (6-cost, Vigilance) — Shielded + "When Played: If you control another Vigilance
#// unit, heal 4 damage from your base." With another Vigilance unit (SHD_057) in play and 5 base damage, the
#// base is healed to 1.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SHD_066
WithP1GroundArena: SHD_057:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:1

---

# WhenPlayed_NoVigilance_NoHeal
#// SHD_066 Cargo Juggernaut — without another Vigilance unit, the base is not healed (stays at 5 damage).

## GIVEN
CommonSetup: bbw/bbw/{myResources:6;myBaseDamage:5}
P1OnlyActions: true
WithP1Hand: SHD_066
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:5
