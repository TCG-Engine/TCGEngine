# WhenDefeated_Draw
#// LOF_059 Nightsister Warrior (2/2) — When Defeated: draw a card. She attacks a 4/7, dies to the counter,
#// and P1 draws.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_059:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0

---

# WhenDefeated_Draw_UnderNoGlory
#// LOF_059 Nightsister Warrior — No Glory, Only Results (JTL_043) takes control of the enemy Nightsister
#// FIRST, then defeats it. The When Defeated "draw a card" now belongs to P1 (the controller at defeat
#// time), so P1 draws — the ability follows the controller, not the original owner.

## GIVEN
CommonSetup: bbw/rrk/{myResources:13;handCardIds:JTL_043}
P1OnlyActions: true
WithP2GroundArena: LOF_059:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENACOUNT:0
