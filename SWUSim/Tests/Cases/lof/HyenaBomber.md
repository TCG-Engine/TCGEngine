# AggressionUnit_Deal2
#// LOF_158 Hyena Bomber — When Played: if you control another Aggression unit, may deal 2 damage to a
#// ground unit. P1 controls the Aggression Acolyte, so playing the Bomber lets P1 deal 2 to the enemy 3/7.

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_158}
P1OnlyActions: true
WithP1GroundArena: LOF_129:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# AggressionUnit_DealFriendly
#// LOF_158 Hyena Bomber — the "deal 2 to a ground unit" can hit a FRIENDLY ground unit too. P1 controls the
#// Aggression Acolyte (LOF_129), so playing the Bomber lets P1 deal 2 to its own LOF_129. Intended: "should deal 2
#// damage to friendly unit because we control another aggression unit."

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_158}
P1OnlyActions: true
WithP1GroundArena: LOF_129:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# NoOtherAggression_NoEffect
#// LOF_158 Hyena Bomber — with no OTHER Aggression unit under P1's control, the When Played ability does not
#// trigger: no damage is dealt and it becomes P2's turn. P1's only other unit here (SOR_095, Command) is not
#// Aggression. Intended: "should not deal 2 damage to unit because we do not control another ... unit."

## GIVEN
CommonSetup: rrk/ggw/{myResources:3;handCardIds:LOF_158}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:0
P1SPACEARENACOUNT:1
