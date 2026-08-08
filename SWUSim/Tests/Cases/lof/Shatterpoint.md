# DefeatWeak
#// LOF_079 Shatterpoint — mode A: defeat a non-leader unit with 3 or less remaining HP. P1 picks the
#// DefeatWeak mode and the enemy 3/1 (1 HP) is defeated.

## GIVEN
CommonSetup: bbk/ggw/{myResources:4;handCardIds:LOF_079}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DefeatWeak

## EXPECT
P2GROUNDARENACOUNT:0

---

# ForceDefeat_AnyNonLeader
#// LOF_079 Shatterpoint — mode B: use the Force to defeat ANY non-leader unit (no HP limit). P1 picks
#// ForceDefeat, uses the Force, and defeats the enemy 4/7.

## GIVEN
CommonSetup: bbk/ggw/{myResources:4;handCardIds:LOF_079}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:ForceDefeat

## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:0

---

# ForceMode_NoForce_NoEffect
#// LOF_079 Shatterpoint — picking the "Use the Force. If you do, defeat a non-leader unit" mode WITHOUT a
#// Force token does nothing (the button reads "(No effect)"). P1 chooses ForceDefeat with no Force; the enemy
#// 4/7 (LAW_124) survives and P1 gains no Force. (Intended: "choose the 'use the force' option but without the
#// force nothing happen".)

## GIVEN
CommonSetup: bbk/ggw/{myResources:4;handCardIds:LOF_079}
P1OnlyActions: true
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:ForceDefeat

## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:1
