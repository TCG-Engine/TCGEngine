# MandalorianArmor_NoShieldOnNonMandalorian
#// SHD_073 Mandalorian Armor — the Shield is conditional on the host being a Mandalorian. Played onto a
#// non-Mandalorian unit (SOR_046, vanilla) → the upgrade attaches but no Shield is granted.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_073

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# MandalorianArmor_ShieldOnMandalorian
#// SHD_073 Mandalorian Armor (Vigilance upgrade) — "When Played: If attached unit is a Mandalorian,
#// give a Shield token to it." Played onto a native Mandalorian (SOR_142) → it gains a Shield.

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP1Hand: SHD_073

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
