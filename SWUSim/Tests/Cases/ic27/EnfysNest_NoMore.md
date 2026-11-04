# Ambush_IntoACheapUnit_PlusTwoAndOverwhelm
#// IC27_086 Enfys Nest, No More! — Unit (Ground) 4/6, cost 5, [Command][Command], Underworld, unique.
#// Text: Ambush. While attacking a unit that costs 3 or less, this unit gets +2/+0 and gains Overwhelm.
#// (Spelling pass 2026-11-03: Ambush reminder "it may attack" → "she may attack", as printed.)
#// COVERAGE: offer=N/A — a constant combat modifier, nothing is chosen (Ambush's own prompt is generic keyword code) ·
#//           decline=N/A — no "may" in the rider · boundary=this (cost 2) + CostThree_Qualifies + CostFour_NoBonus ·
#//           value-class=TokenUnit_CostZeroQualifies + LeaderUnit_NoBonus · scope=AttackingABase_NoBonus +
#//           Defending_NoBonus · dispatch=this (Ambush attack) + ArenaAttack_IntoACheapUnit (a normal attack) ·
#//           duration=AfterTheAttack_BackToFourNoOverwhelm · blanked=Blanked_NoBonusNoOverwhelm ·
#//           control=N/A — no owner-scoped zone · reqboundary=N/A — evaluated at combat damage, nothing stored ·
#//           modes=2P only (no player reference, no friendly/enemy wording).
#//
#// ⚠ PREVIEW SET — IC27 is absent from card-specific-rulings.md. Modelled on SOR_130 First Legion Snowtrooper ("While
#// attacking a damaged unit, this unit gets +2/+0 and gains Overwhelm"); "costs 3 or less" reads the defender's PRINTED
#// cost (a token unit costs 0).
#//
#// THE POSITIVE. Enfys ambushes Battlefield Marine (cost 2, 3/3): 4 + 2 = 6 damage, 3 excess to P2's base. She takes 3.
## GIVEN
CommonSetup: ggk/ggw/{myResources:5;myhandCardIds:IC27_086}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# CostThree_Qualifies
#// Boundary: Super Battle Droid costs exactly 3 (4/3): 6 damage, 3 excess.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_086:1:0
WithP2GroundArena: TWI_230:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3

---

# CostFour_NoBonus
#// Boundary partner: Coruscant Undercity Police costs 4 (5/4). Enfys deals her plain 4 — exactly lethal, no excess, no
#// Overwhelm. With the rider applied it would be 6 and spill 2.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_086:1:0
WithP2GroundArena: SEC_167:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0

---

# ArenaAttack_IntoACheapUnit
#// A normal attack (not Ambush) into Battlefield Marine: same rider — 3 excess.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_086:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2BASEDMG:3

---

# TokenUnit_CostZeroQualifies
#// A Battle Droid token costs 0: 6 damage into a 1/1, 5 excess.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_086:1:0
WithP2GroundArena: TWI_T01:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:5

---

# LeaderUnit_NoBonus
#// A deployed leader unit costs its printed leader cost (well over 3): Enfys deals her plain 4 and nothing spills.
## GIVEN
CommonSetup: ggk/ggw/{theirLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_086:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:0

---

# AttackingABase_NoBonus
#// "While attacking a UNIT": a base attack is her plain 4.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_086:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:4

---

# Defending_NoBonus
#// Only while ATTACKING: Outer Rim Mystic (cost 3, 2/6) attacks Enfys and takes her plain 4 back — it survives. With the
#// rider wrongly applied it would take 6 and die.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: IC27_086:1:0
WithP2GroundArena: LOF_112:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# AfterTheAttack_BackToFourNoOverwhelm
#// The rider lasts only while attacking: after the attack Enfys reads 4 power and has no Overwhelm.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_086:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm

---

# Blanked_NoBonusNoOverwhelm
#// A blanked Enfys (SOR_138 marker) deals her printed 4 into the Marine: it dies, but nothing spills.
## GIVEN
CommonSetup: ggk/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_086:1:0:SOR_138
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
