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
