# AspectWaiver_Mandalorian
#// SHD_126 The Darksaber (Upgrade, cost 4, Command, +4/+3) — "While playing this upgrade on a Mandalorian
#// unit, ignore its aspect penalty." P1's base is off-Command (Aggression), so SHD_126 would normally cost
#// 4 + 2 penalty = 6; attaching to the Mandalorian SOR_142 waives the penalty, so it costs exactly 4 (all
#// of P1's resources) and SOR_142 becomes a 6-power unit wearing the Darksaber.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: SOR_142:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_126
P1GROUNDARENAUNIT:0:POWER:6

---

# NonMandalorian_NoWaiver
#// SHD_126 The Darksaber — the waiver is host-conditional. Attaching to a NON-Mandalorian unit (SOR_046)
#// keeps the +2 off-Command penalty, so the cost is 6; with only 4 resources the play fails and nothing
#// attaches (SOR_046 stays a 3-power unit, resources untouched).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_126
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:0:POWER:3

---

# OnAttack_MandalorianExp
#// SHD_126 The Darksaber — the attached unit gains "On Attack: give an Experience token to each OTHER
#// friendly Mandalorian unit." SHD_034 (Mandalorian, wearing the Darksaber) attacks an enemy unit; the
#// other friendly Mandalorian SOR_142 (2 power) gains an Experience token → 3 power. (The host is excluded.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_034:1:0
WithP1GroundArenaUpgrade: 0:SHD_126
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:1:POWER:3
