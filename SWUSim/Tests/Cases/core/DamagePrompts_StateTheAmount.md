# DamagePrompt_FixedAmount
#// Every "choose a target to damage" prompt must say HOW MUCH. The audit that fixed 53 of them only
#// proved the source strings; this proves the string the player actually receives.
#//
#// LAW_213 Cutthroat Podracer — "you may deal 2 damage to an exhausted ground unit". The target prompt
#// read "Choose_a_unit". Two exhausted targets so the choice stays interactive (one would auto-resolve).
#// The decision is left UNANSWERED on purpose — a may-choose IS the prompt, so answering it destroys
#// the thing under test.

## GIVEN
CommonSetup: grw/brk/{myResources:12;myhandCardIds:LAW_213}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: [SOR_095:0:0 SOR_046:0:0]

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Deal_2_damage_to_a_unit

---

# DamagePrompt_DynamicAmount
#// The dynamic half: the amount is only known at resolve time, so the prompt has to interpolate it
#// rather than say "Deal_damage_to_a_unit". JTL_153 Rebellious Hammerhead deals damage equal to the
#// cards in your hand, counted AFTER it leaves — 3 here, so the prompt must read "Deal_3_...".

## GIVEN
CommonSetup: grw/brk/{myResources:12}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [JTL_153 SOR_095 SOR_095 SOR_095]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Deal_3_damage_to_a_unit
