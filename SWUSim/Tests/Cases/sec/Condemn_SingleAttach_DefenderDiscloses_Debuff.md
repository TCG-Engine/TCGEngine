# SEC_038 Condemn (Upgrade, Vigilance/Villainy, no attach restriction) — "While attached unit is
#   attacking, it gains: 'On Attack: the defending player may disclose VigilanceVillainy → this unit
#   gets -6/-0 for this attack' and loses all other abilities."
# P1's SEC_118 (6/5, vanilla) bears 1 Condemn and attacks P2's base. The granted On Attack lets the
# DEFENDING player (P2) disclose; P2 discloses SEC_038 (Vigilance,Villainy → covers VigilanceVillainy),
# so the attacker gets -6/-0 → power max(0, 6-6) = 0 → deals 0 to the base. After the attack the
# attack-duration debuff expires, so the attacker's power is back to 6.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: SEC_118:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
