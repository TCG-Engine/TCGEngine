# ArenaOnAttackSelfDamage
#// ASH_186 Treacherous Minefield (Event, cost 2) — Choose an arena. For this phase, each unit in that
#// arena gains "On Attack: deal 2 damage to this unit." P1 plays it choosing Ground; then SOR_046 attacks
#// the enemy base and takes 2 self-damage from the granted On Attack.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:ASH_186}
WithP1GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Ground
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# SpaceArena_SelfDamage
#// ASH_186 Treacherous Minefield — the chosen arena may be Space. Choosing Space grants the self-damage On
#// Attack to space units: SOR_237 (2 power) attacks P2's base for 2 and takes 2 self-damage.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:ASH_186}
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space
- P1>AttackSpaceArena:0:BASE
## EXPECT
P2BASEDMG:2
P1SPACEARENAUNIT:0:DAMAGE:2
