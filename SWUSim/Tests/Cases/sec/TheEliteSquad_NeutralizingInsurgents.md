# WhenDamagedInCombat_DealsToUnique
#// SEC_143 The Elite Squad — "When damage is dealt to this unit: you may deal 2 damage to another unique
#//   unit." P1's SEC_143 (6/8) attacks P2's SEC_080 (3/3, non-unique), defeats it, and takes 3 counter
#//   (survives). That counter damage triggers the reaction → P1 deals 2 to another unique unit (LOF_093,
#//   now at index 0 after SEC_080 is defeated). Proves the post-damage combat reaction.

## GIVEN
CommonSetup: rrk/grk
P1OnlyActions: true
WithP1GroundArena: SEC_143:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_093
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenPlayed_DealsToUniqueUnit
#// SEC_143 The Elite Squad — When Played: you may deal 2 damage to another unique unit. P1 plays SEC_143
#//   (unique); the only OTHER unique unit is P2's LOF_093 (2/5), which takes 2. (Non-unique units would not
#//   be offered; SEC_143 itself is excluded as "another".)

## GIVEN
CommonSetup: rrk/grk/{myResources:8;handCardIds:SEC_143}
P1OnlyActions: true
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayed_Decline_NoDamage
#// SEC_143 The Elite Squad — the deal-2 is optional ("you may"). P1 plays SEC_143 and declines → LOF_093
#//   is untouched.

## GIVEN
CommonSetup: rrk/grk/{myResources:8;handCardIds:SEC_143}
P1OnlyActions: true
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
