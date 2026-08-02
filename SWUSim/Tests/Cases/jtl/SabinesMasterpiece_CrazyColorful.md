# OnAttack_CommandCunning
#// JTL_250 Sabine's Masterpiece — On Attack: Command + Cunning branches.
#// P1 controls a Command unit (SOR_095) and a Cunning unit (SOR_213), no Vigilance/Aggression unit.
#// Command → give an Experience token to a unit (SOR_095 → 3/3 becomes 4/4). Cunning → exhaust or
#// ready a resource; P1 chooses Exhaust (3 ready → 2 available). Only these two effects fire, in order.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1SpaceArena: JTL_250:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_213:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:You

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1RESAVAILABLE:2

---

# OnAttack_VigilanceAggression
#// JTL_250 Sabine's Masterpiece — On Attack: for each controlled aspect, its effect.
#// P1 controls a Vigilance unit (SOR_046) and an Aggression unit (LAW_180), but no Command/Cunning
#// unit. So only the Vigilance (heal 2 from a base) and Aggression (1 to a unit/base) effects fire,
#// in printed order. No extra prompts for the absent aspects.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_250:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: LAW_180:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# OnAttack_NoMatchingAspect
#// JTL_250 Sabine's Masterpiece — the four branches key on Vigilance/Command/Aggression/Cunning units you
#// control. Sabine is Heroism and is alone, so NONE of the four fire: it just attacks the base for 3 with no
#// menu or prompt.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_250:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:3

---

# OnAttack_Cunning_ReadyOwnResource
#// JTL_250 Sabine's Masterpiece — Cunning branch "ready a resource". SWUSim previously only exercised the
#// Exhaust choice; this drives the Ready choice. P1 controls a lone Cunning unit (SOR_213) and has 3
#// exhausted + 2 ready resources (RESAVAILABLE 2). The Cunning branch offers Exhaust-or-Ready; P1 picks
#// Ready, which readies one of the controller's own resources: RESAVAILABLE 2 -> 3.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3:SOR_095:0,2:SOR_095:1
WithP1SpaceArena: JTL_250:1:0
WithP1GroundArena: SOR_213:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Ready
- P1>AnswerDecision:You

## EXPECT
P1RESAVAILABLE:3
P2BASEDMG:3

---

# OnAttack_Cunning_BiColorLawUnit
#// JTL_250 Sabine's Masterpiece — Cunning aspect detection on a multi-aspect LAW unit. LAW_089 Kanan Jarrus
#// is Cunning+Vigilance+Heroism. With no base damage, the Vigilance "heal a base" branch has nothing to heal
#// and silently no-ops, so only the Cunning branch fires. P1 picks Exhaust, exhausting one of their own
#// resources (RESAVAILABLE 3 -> 2), confirming Cunning is detected on the bi-color/multi-aspect unit.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1SpaceArena: JTL_250:1:0
WithP1GroundArena: LAW_089:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:You

## EXPECT
P1RESAVAILABLE:2
P2BASEDMG:3

---

# OnAttack_Cunning_ExhaustOpponentResource
#// JTL_250 Sabine's Masterpiece — the Cunning "exhaust or ready a resource" has NO "your", so the controller
#// chooses WHICH player's resource. P1 controls a lone Cunning unit (SOR_213). The Cunning branch offers
#// Exhaust/Ready, then a You/Opponent player pick; P1 picks Exhaust → Opponent, exhausting one of P2's ready
#// resources (P2 RESAVAILABLE 3 -> 2) while P1's own resources are untouched.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP2Resources: 3
WithP1SpaceArena: JTL_250:1:0
WithP1GroundArena: SOR_213:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:Opponent

## EXPECT
P1RESAVAILABLE:2
P2RESAVAILABLE:2
P2BASEDMG:3

---

# OnAttack_AllFourAspects_AllBranchesFire
#// JTL_250 Sabine's Masterpiece — the four clauses are INDEPENDENT: each fires on its own "if you control
#// a <aspect> unit" check, so controlling one unit of every aspect fires ALL FOUR in printed order
#// (Vigilance → Command → Aggression → Cunning). P1 controls TWI_057 (Vigilance), SHD_110 (Command),
#// LOF_168 (Aggression) and SOR_210 (Cunning), and its base starts at 3 damage.
#//   Vigilance  → heal 2 from a base (P1's own base 3 → 1)
#//   Command    → give an Experience token to a unit (SHD_110 2/2 → 3/3)
#//   Aggression → deal 1 damage to a unit or base (the enemy base)
#//   Cunning    → exhaust or ready a resource (Exhaust: 5 ready → 4)
#// Plus Sabine's own attack damage on the enemy base. Complements the paired-aspect sections above,
#// which each prove that the NON-controlled aspects stay silent.
#// ⚠ The Vigilance heal AUTO-RESOLVES here: P1's base is the only DAMAGED base, so it is the single
#// legal target and no prompt is raised. Feeding it an answer shifts every later answer by one.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: JTL_250:1:0
WithP1GroundArena: TWI_057:1:0
WithP1GroundArena: SHD_110:1:0
WithP1GroundArena: LOF_168:1:0
WithP1GroundArena: SOR_210:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:Exhaust
- P1>AnswerDecision:You

## EXPECT
P1BASEDMG:1
P1GROUNDARENAUNIT:1:CARDID:SHD_110
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
P1RESAVAILABLE:4
