# Grit
#// SOR_148 Guerilla Attack Pod (4/6) — Grit: +1 power per damage on this unit.
#// With 2 damage, base power 4 + Grit bonus 2 = 6.
#// COVERAGE: offer=N/A — neither clause targets: Grit is a static self-buff and the When Played readies
#//           THIS unit, so there is no candidate pool to inspect. The nearest scope assertion is which
#//           BASES the condition scans, covered by WhenPlayed_Readies (opponent's base) vs
#//           WhenPlayed_YOUROwnBaseAt15_AlsoReadies (own base) · reqboundary=N/A — neither clause raises
#//           a decision, so nothing of this card's crosses a request boundary; the one flow here that
#//           does is the ECL play, whose own choose is exercised in PlayedViaEnergyConversionLab_
#//           AmbushThenReadies · control=N/A — Grit reads the damage on the OBJECT, not a seat, and the
#//           When Played scans every live seat's base rather than a controlled one, so no owner-vs-
#//           controller reading exists; the harness also cannot seat damage on a controlled unit
#//           (WithP{n}GroundArenaControlled takes CARD:owner[:status] with no damage field) ·
#//           boundary=WhenPlayed_Base14Damage_NoReady (14, one short) vs WhenPlayed_Readies (15) is the
#//           N vs N-1 pair on the "15 or more" threshold, and GritNoDamage (0 -> power 4) vs this
#//           section (2 -> power 6) is the per-damage scaling pair · decline=N/A — neither clause is
#//           printed as "you may": Grit is a keyword and the ready is an unconditional consequence of
#//           the If, so there is no branch to decline. The false half of the If is the negative, held
#//           by WhenPlayed_NoReady and WhenPlayed_Base14Damage_NoReady.

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
#// player first picks which resolves — the ready must resolve AFTER the attack, or the attack's
#// exhaust would be the final state.
#// ⚠ EffectStack-1 IS THE AMBUSH, not EffectStack-0. CollectEntryTriggers bags WhenPlayed FIRST and
#// Ambush after it, so EffectStack-0 is the "ready this unit" clause. This answer said EffectStack-0
#// until 2026-08-24, i.e. it resolved the ready BEFORE the attack — the opposite of the order this
#// section is named for. It passed anyway only because Ambush attacks were not exhausting the
#// attacker at all; once that was fixed (CR 6.3.1 step 3, via HMW_018 The Warrior, the first leader
#// with a deployed Ambush) the stale answer produced EXHAUSTED and the wrong order became visible.
#// Ambush does NOT ready the unit — modern reminder text is "it may attack an enemy unit", and CR
#// 5.9.a lets it attack "even if this unit is exhausted". So the Pod attacks WHILE exhausted and the
#// When Played is what leaves it ready, which is the whole point of the interaction.

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
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_148
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# WhenPlayed_Base14Damage_NoReady
#// SOR_148 Guerilla Attack Pod — the N-1 half of the "15 OR MORE damage" threshold, and the section
#// that makes WhenPlayed_Readies mean something. P2's base carries 14 damage: one short. The condition
#// fails and the Pod stays EXHAUSTED, exactly as it does at 0 damage.
#// 14 vs 15 is the only pair that separates a correct `>= 15` from a `> 15`, a `>= 14` or a mere
#// "the base is damaged at all" check — every one of which passes both existing sections.

## GIVEN
CommonSetup: grw/grw/{myResources:6;handCardIds:SOR_148;theirBaseDamage:14}

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:14

---

# WhenPlayed_YOUROwnBaseAt15_AlsoReadies
#// SOR_148 Guerilla Attack Pod — "If A BASE has 15 or more damage on it" names no controller, so the
#// condition scans BOTH bases. WhenPlayed_Readies proves it off the OPPONENT's base; this proves it off
#// P1's OWN, with P2's base clean at 0. The Pod enters exhausted and the When Played readies it.
#// An implementation scoped to "an enemy base" (or to "your base") passes exactly one of the two and
#// looks correct on its own.

## GIVEN
CommonSetup: grw/grw/{myResources:6;handCardIds:SOR_148;myBaseDamage:15}

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1BASEDMG:15
P2BASEDMG:0

---

# Grit_IsPowerOnly_HPIsUnaffected
#// SOR_148 Guerilla Attack Pod (4/6) — Grit is "+1/+0 for each damage on it", NOT +1/+1. With 2 damage
#// the Pod is at power 6 while its HP is still 6: the HP assertion reads CURRENT MAX HP (printed plus
#// modifiers, damage NOT subtracted), so a Grit wired as +1/+1 would report 8 here and would pass every
#// power-only assertion in this file. The HP half is the discriminator; DAMAGE:2 pins the input.

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_148:1:2

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:0:DAMAGE:2
