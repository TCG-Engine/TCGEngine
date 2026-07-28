# OnAttackAspectDebuff
#// LAW_101 Lawbringer (7/7, space) — When Played/On Attack: choose an aspect; give each enemy unit with
#// that aspect -2/-2 for this phase. Attacks the base; choose Heroism -> SOR_046 (Vigilance,Heroism) 3/7
#// -> 1/5.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_101:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Heroism

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5

---

# WhenPlayedAspectDebuff
#// LAW_101 Lawbringer — the same debuff fires When Played. Play Lawbringer (Vigilance/Villainy, cost 8)
#// and choose Villainy: enemy AT-ST (SOR_232, Villainy 6/7) -> 4/5 and Pyke Sentinel (SHD_029, Villainy
#// 2/3) -> 0/1, while Battlefield Marine (SOR_095, Command/Heroism 3/3) is untouched.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: LAW_101
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Villainy

## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3
P2GROUNDARENAUNIT:1:POWER:4
P2GROUNDARENAUNIT:1:HP:5
P2GROUNDARENAUNIT:2:POWER:0
P2GROUNDARENAUNIT:2:HP:1

---

# DoesNotAffectFriendlyUnits
#// LAW_101 Lawbringer — the debuff only hits ENEMY units. Choose Command: enemy Battlefield Marine
#// (SOR_095, Command 3/3) -> 1/1, but friendly Phoenix Squadron A-Wing (JTL_095, Command 3/2) is
#// unaffected, and the enemy Consular Security Force (SOR_046, Vigilance/Heroism — not Command) is untouched.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: LAW_101
WithP1SpaceArena: JTL_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Command

## EXPECT
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:HP:2
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:1
P2GROUNDARENAUNIT:1:POWER:3
P2GROUNDARENAUNIT:1:HP:7

---

# DefeatsUnitsReducedToZeroHp
#// LAW_101 Lawbringer — a unit reduced to 0 HP is defeated. Choose Villainy: enemy TIE/ln Fighter
#// (SOR_225, Villainy 2/1 space) drops to 0/-1 and is defeated, while Battlefield Marine (Command) survives.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: LAW_101
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Villainy

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3

---

# ChooseAspectToNoEffect
#// LAW_101 Lawbringer — choosing an aspect that no enemy unit has is legal and simply does nothing.
#// Choose Aggression: enemy AT-ST (Villainy) and Battlefield Marine (Command) both remain at full stats.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: LAW_101
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Aggression

## EXPECT
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:7
P2GROUNDARENAUNIT:1:POWER:3
P2GROUNDARENAUNIT:1:HP:3
