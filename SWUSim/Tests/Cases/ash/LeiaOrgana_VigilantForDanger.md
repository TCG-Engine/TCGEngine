# Support_SelfDamageHealBase
#// ASH_059 Leia Organa (Ground, 3/4, Support) — the On Attack "you may deal 1 damage to this unit; if you
#// do, heal 2 damage from your base" is lent to the Support attacker. Leia is played from hand; the
#// friendly SOR_237 (space) is chosen to attack the enemy base. "This unit" in the lent ability is the
#// attacker SOR_237, so it takes the 1 self-damage; P1's base heals 2 (5 → 3). Leia herself is undamaged.
## GIVEN
CommonSetup: bbw/bbk/{myResources:4;myBaseDamage:5;handCardIds:ASH_059}
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:YES
## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:1
P1BASEDMG:3
P2BASEDMG:2
P1GROUNDARENAUNIT:0:CARDID:ASH_059
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# OnAttack_SelfDamageHealBase_Trigger
#// ASH_059 Leia Organa (Ground, 3/4) — On Attack: "you may deal 1 damage to this unit; if you do, heal 2
#// damage from your base." Leia attacks the enemy base; the player triggers the ability. Leia takes 1
#// self-damage and P1's base heals 2 (5 → 3). Leia still deals her 3 combat damage to the enemy base.
## GIVEN
CommonSetup: bbw/bbk/{myBaseDamage:5}
WithP1GroundArena: ASH_059:1:0
WithP2GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_059
P1GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:3
P2BASEDMG:3

---

# OnAttack_SelfDamageHealBase_Pass
#// ASH_059 Leia Organa — the On Attack self-damage/heal is optional. Leia attacks the enemy base but the
#// player passes: Leia takes no self-damage and P1's base is not healed (stays at 5). Leia still deals her
#// 3 combat damage to the enemy base.
## GIVEN
CommonSetup: bbw/bbk/{myBaseDamage:5}
WithP1GroundArena: ASH_059:1:0
WithP2GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_059
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:5
P2BASEDMG:3
