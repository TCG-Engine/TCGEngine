# Decline_NoDamage
#// TWI_048 Obi-Wan's Aethersprite — the "may" is optional: declining the choose (AnswerDecision:-) deals
#// no damage to itself or the enemy space unit.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;handCardIds:TWI_048}
P1OnlyActions: true
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_048
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# OnAttack_DamagesSelfAndSpace
#// TWI_048 Obi-Wan's Aethersprite — the On Attack window fires the same "may deal 1 to self / 2 to another
#// space unit" option. It attacks the enemy base (JTL_069 stays available as the "another space unit"
#// target). Ability deals 1 to itself + 2 to the frigate, then combat deals its power 4 to the base.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
P1OnlyActions: true
WithP1SpaceArena: TWI_048:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_048
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:2
P2BASEDMG:4

---

# WhenPlayed_DamagesSelfAndSpace
#// TWI_048 Obi-Wan's Aethersprite (Unit 4/6, Space, cost 5, Vigilance/Heroism) — "When Played/On Attack:
#// You may deal 1 damage to this unit and 2 damage to another space unit." Played into a board with one
#// enemy space unit (JTL_069 Munificent Frigate 4/7); taking the option deals 1 to itself and 2 to the
#// frigate. Base b = Vigilance, leader bw = Heroism → both aspect pips covered, no penalty.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;handCardIds:TWI_048}
P1OnlyActions: true
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_048
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:2
