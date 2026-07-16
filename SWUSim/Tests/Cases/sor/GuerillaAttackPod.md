# Grit
#// SOR_148 Guerilla Attack Pod (4/6) — Grit: +1 power per damage on this unit.
#// With 2 damage, base power 4 + Grit bonus 2 = 6.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_148:1:2

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6

---

# GritDamagesBase
#// SOR_148 Guerilla Attack Pod (4/6) — Grit bonus applies to base attack damage.
#// GAP is ready with 2 damage: Grit gives +2 power (4 + 2 = 6).
#// Attacking P2's base should deal 6 damage, not 4.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_148:1:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6

---

# GritNoDamage
#// SOR_148 Guerilla Attack Pod (4/6) — Grit baseline: 0 damage means no Grit bonus.
#// Power equals base 4.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1GroundArena: SOR_148:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4

---

# WhenPlayed_NoReady
#// SOR_148 Guerilla Attack Pod — When Played: no base at 15+ damage → stays exhausted.
#// Both bases have 0 damage. WhenPlayed condition fails; unit enters and stays exhausted.

## GIVEN
CommonSetup: grw/grw/{myResources:6;handCardIds:SOR_148}

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# WhenPlayed_Readies
#// SOR_148 Guerilla Attack Pod — When Played: a base has 15+ damage → ready this unit.
#// P2's base has 15 damage. GAP enters play exhausted, then WhenPlayed readies it.

## GIVEN
CommonSetup: grw/grw/{myResources:6;handCardIds:SOR_148;theirBaseDamage:15}

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
