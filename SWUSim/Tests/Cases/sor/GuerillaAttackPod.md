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

---

# PlayedViaEnergyConversionLab_AmbushThenReadies
#// SOR_148 Guerilla Attack Pod × SOR_022 Energy Conversion Lab — the Pod is played by ECL's Epic
#// Action, which grants it AMBUSH for the phase. Both of the Pod's entry effects then apply: the
#// granted Ambush attack (it hits SOR_067 Rugged Survivors 3/5 for 4 and takes 3 back) AND the Pod's
#// own When Played "if a base has 15 or more damage on it, ready this unit" — P2's base is at 15, so
#// the Pod ends the action READY despite having attacked. Both entry triggers land together, so the
#// player first picks which resolves (EffectStack-0 = the Ambush) — the ready must resolve AFTER the
#// attack, or the attack's exhaust would be the final state.

## GIVEN
CommonSetup: grw/grw/{theirBaseDamage:15;myBase:SOR_022}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_148
WithP2GroundArena: SOR_067:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_148
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:4
