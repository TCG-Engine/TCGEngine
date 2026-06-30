# SOR_092 — two friendly units; the player CHOOSES which gets the +2/+2 (becoming the dealer).
# P1 picks the 3/3 (→5/5); the unchosen 3/7 friendly stays 3/7 and is itself a valid "other unit"
# split target. Splits the 5 power: 2 onto the unchosen friendly + 3 onto an enemy. Proves the buff
# hits ONLY the chosen unit, the dealer is excluded from targets, and friendly units are legal targets.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_092}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # 3/3 — chosen dealer → 5/5
WithP1GroundArena: SOR_046:1:0    # 3/7 — unchosen friendly; takes 2, NOT buffed
WithP2GroundArena: SOR_046:1:0    # 3/7 — enemy; takes 3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1:2,theirGroundArena-0:3

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
