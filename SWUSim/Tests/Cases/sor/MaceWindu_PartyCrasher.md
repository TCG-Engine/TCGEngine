# AttackDefeats_ReadiesSelf
#// SOR_149 Mace Windu (5/7) — "When this unit attacks and defeats a unit: Ready him." Mace attacks
#// a 3/3, defeats it, and is readied (so he ends READY despite having attacked). He takes 3
#// counter-damage.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_149:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_149
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackNoDefeat_StaysExhausted
#// SOR_149 Mace Windu — the ready only triggers on a DEFEAT. Mace attacks a 3/7 that survives his
#// 5 damage, so he is NOT readied and stays exhausted.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_149:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:EXHAUSTED
