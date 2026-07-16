# OnAttack_BuffNonVillainy_GivesExp
#// SHD_141 Kylo Ren (6-cost 5/? ground) — "On Attack: Give a unit +2/+0 for this phase. If it's a non-Villainy
#// unit, also give an Experience token to it." Kylo attacks the base and buffs the friendly non-Villainy
#// SOR_046: +2/+0 (phase) AND +1/+1 (Experience) → 6/8, with 1 Experience subcard.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_141:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:8
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# OnAttack_BuffVillainy_NoExp
#// SHD_141 Kylo Ren — buffing a Villainy unit (SEC_080) grants only the +2/+0 phase buff, no Experience
#// (→ 5 power, no subcard).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_141:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# VillainyPenaltyApplies_WithoutRey
#// SHD_141 Kylo Ren — without Rey, the Villainy aspect penalty applies. The base covers Aggression but not
#// Villainy, so Kylo costs 6 + 2 = 8 (8 resources → 0 left). Contrast with the Rey-waiver test.

## GIVEN
CommonSetup: rrw/rrw/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_141

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_141
P1RESAVAILABLE:0

---

# VillainyPenaltyWaived_WithRey
#// SHD_141 Kylo Ren (base cost 6, Villainy/Aggression) — "While playing this unit, ignore his Villainy aspect
#// penalty if you control Rey." P1's leader is Rey (SHD_004) and its base covers Aggression but not Villainy;
#// with Rey the Villainy pip is waived, so Kylo costs the printed 6 (6 resources → 0 left).

## GIVEN
CommonSetup: rrw/rrw/{myLeader:SHD_004;myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_141

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_141
P1RESAVAILABLE:0
