# OnAttack_DealDamageEqualToOwnDamage
#// SHD_139 Krrsantan — "On Attack: Choose a ground unit. You may deal 1 damage to it for each damage on this
#// unit." Krrsantan has 3 damage; attacking the base, it deals 3 to the enemy SOR_046 (proves the amount =
#// own damage).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_139:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayed_ReadyIfEnemyBounty
#// SHD_139 Krrsantan (5-cost, Villainy/Aggression) — "When Played: If an enemy unit has a Bounty, you may
#// ready this unit." With the enemy Bounty unit SHD_095 in play, P1 readies Krrsantan (it enters exhausted,
#// then becomes ready).

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_139
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_139
P1GROUNDARENAUNIT:0:READY
