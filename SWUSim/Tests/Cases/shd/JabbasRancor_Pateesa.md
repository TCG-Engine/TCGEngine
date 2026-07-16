# CostReduction_WithJabba
#// SHD_091 Jabba's Rancor — "If you control Jabba the Hutt (as a leader or unit), this unit costs 1 less."
#// With Jabba (SHD_006) as P1's leader, the 8-cost Rancor costs 7 → 1 resource left of 8. Played with no
#// other units, the When Played damage has no valid targets and fizzles (no decision).

## GIVEN
CommonSetup: grk/grk/{myLeader:SHD_006;myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_091

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_091
P1NODECISION

---

# OnAttack_DamageBothSides
#// SHD_091 Jabba's Rancor — the same "deal 3 to another friendly ground + 3 to an enemy ground" also fires
#// On Attack. Proves the OnAttack-safe MZMAYCHOOSE path: Rancor attacks the base, the OnAttack rider damages
#// SOR_046 (friendly) and SEC_080 (enemy) by 3 each.

## GIVEN
CommonSetup: grk/grk
P1OnlyActions: true
WithP1GroundArena: SHD_091:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayed_DamageBothSides_FullCost
#// SHD_091 Jabba's Rancor (8-cost 9/9 ground, Command/Villainy) — When Played: Deal 3 to another friendly
#// ground unit AND 3 to an enemy ground unit. Without Jabba the cost is the full 8 (grk leader/base cover
#// Command+Villainy, no penalty → 8 spent, 0 left). Friendly damage lands on SOR_046 (7 HP); enemy on SEC_080.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_091
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SHD_091
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
