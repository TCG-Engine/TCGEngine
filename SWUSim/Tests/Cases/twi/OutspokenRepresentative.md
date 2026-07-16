# Sentinel_WhileRepublic
#// TWI_043 Outspoken Representative — "While you control another Republic unit, this unit gains
#// Sentinel." Guard: with a friendly Clone Trooper (Republic) alongside, TWI_043 reports HASKEYWORD
#// Sentinel.

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_043:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenDefeated_CreatesClone
#// TWI_043 Outspoken Representative (Unit 0/3, Ground) — "When Defeated: Create a Clone Trooper token."
#// TWI_043 (power 0) attacks SOR_046 (3/7) and dies to the 3-power counter (its HP is 3) → When Defeated
#// creates a Clone Trooper. (SOR_046 takes 0 damage.)

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_043:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P2GROUNDARENAUNIT:0:DAMAGE:0
