# PicksLeia_NestedChain
#// Nested-chain interaction: Rebel Assault (SOR_103) chooses the deployed Leia (SOR_009) as its
#// first attacker. Ordering must be: (1) Leia attacks BUFFED — Rebel Assault +1 AND her own Raid 1
#// → 3+1+1 = 5 to base; (2) her deployed OnAttackEnd nests FIRST → a second Rebel attacks UNbuffed
#// → 3; (3) THEN Rebel Assault continues → a third Rebel attacks BUFFED (+1) → 4. Total 5+3+4 = 12.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_103
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-2
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:12
P1LEADER:DEPLOYED

---

# TwoBuffedAttacks
#// SOR_103 Rebel Assault — Event (cost 1, Command/Heroism): "Attack with a Rebel unit. It gets
#// +1/+0 for this attack. Then, attack with another Rebel unit. It gets +1/+0 for this attack."
#// P1 has two 3-power Rebels; each attacks the base for 3+1=4 → 8 total. The +1 is one-shot per
#// attack (POWER stays 3 on both afterward).

## GIVEN
CommonSetup: ggw/grk/{myResources:1;handCardIds:SOR_103}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:8
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:3
