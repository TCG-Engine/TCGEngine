# BothAspectsInDiscard_DoubleDebuff
#// LOF_070 Anakin Skywalker — two When-Played windows: a Heroism card AND a Villainy card are in P1's
#// discard, so both -3/-3 effects fire. P1 debuffs the enemy 3/7 twice → power 0, remaining HP 1.

## GIVEN
CommonSetup: bbk/ggw/{myResources:6;handCardIds:LOF_070;discardCardIds:SOR_095,SEC_080}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:HP:4
P2GROUNDARENAUNIT:1:HP:4

---

# NeitherAspect_NoAbility
#// LOF_070 Anakin Skywalker — with neither a Heroism nor a Villainy card in the discard pile (only the
#// neutral Vigilance card LAW_124), both When-Played windows fail their gate, so no debuff prompt appears and
#// the enemy LAW_124 (4/7) is untouched.

## GIVEN
CommonSetup: bbk/ggw/{myResources:6;handCardIds:LOF_070;discardCardIds:LAW_124}
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:7

---

# HeroismOnly_SingleDebuff
#// LOF_070 Anakin Skywalker — only a Heroism card (SOR_095) is in the discard, so just the first When-Played
#// window fires; the enemy LAW_124 (4/7) gets -3/-3 → 1/4.

## GIVEN
CommonSetup: bbk/ggw/{myResources:6;handCardIds:LOF_070;discardCardIds:SOR_095}
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:4

---

# VillainyOnly_SingleDebuff
#// LOF_070 Anakin Skywalker — only a Villainy card (SEC_080) is in the discard, so just the second When-Played
#// window fires; the enemy LAW_124 (4/7) gets -3/-3 → 1/4.

## GIVEN
CommonSetup: bbk/ggw/{myResources:6;handCardIds:LOF_070;discardCardIds:SEC_080}
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:4
