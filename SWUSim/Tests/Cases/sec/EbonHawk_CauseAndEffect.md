# DiscloseHeroism_PlusTwoPower
#// SEC_219 Ebon Hawk (Ground, 3/3, Cunning) — On Attack: you may disclose Heroism and/or Villainy.
#//   Disclosed Heroism → +2/+0 this attack; disclosed Villainy → defender −4/−0 this attack.
#// Disclose SEC_148 (has a Heroism icon, no Villainy) → +2/+0 → base takes 3+2 = 5.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_219:1:0
WithP1Hand: SEC_148

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:5
P1NODECISION

---

# DiscloseNeither_NoEffect
#// SEC_219 Ebon Hawk — reveal nothing → neither bonus applies; base takes the plain 3.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_219:1:0
WithP1Hand: SEC_148

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:3
P1NODECISION

---

# DiscloseVillainy_DefenderDebuff
#// SEC_219 Ebon Hawk — disclose Villainy → defender gets −4/−0 for this attack.
#// Ebon Hawk (3/3) attacks SOR_046 (3/7). Disclose SEC_133 (Villainy, no Heroism) → defender's counter
#// power 3 − 4 = 0, so Ebon Hawk takes 0 and survives; SOR_046 takes Ebon Hawk's 3 (no +2, Heroism not
#// disclosed).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_219:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_133

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# DiscloseBoth_BuffAndDebuffTogether
#// SEC_219 Ebon Hawk — the two clauses are independent, so disclosing Heroism AND Villainy applies both
#// at once. P1 discloses SEC_148 (Heroism) and SEC_080 (Villainy): Ebon Hawk attacks at 3 + 2 = 5 while
#// the defending SOR_046 (3/7) drops to 3 − 4 = 0 power, so it deals no counter-damage. The defender
#// takes 5 and Ebon Hawk comes back undamaged.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_219:1:0
WithP1Hand: SEC_148
WithP1Hand: SEC_080
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# DisclosingAnIrrelevantAspect_GrantsNeitherHalf
#// SEC_219 Ebon Hawk — the buff/debuff are gated on the disclosed cards actually SHOWING Heroism or
#// Villainy. P1's only card is SOR_164 Wampa (Aggression), and revealing it satisfies neither clause:
#// Ebon Hawk attacks at its printed 3, with no +2 and no defender debuff.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_219:1:0
WithP1Hand: SOR_164

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
