# NoForce_Draw1
#// LOF_175 Do or Do Not — without the Force you "do not" use it → draw 1 (no decision, automatic).

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:LOF_175}
P1OnlyActions: true
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1NODECISION

---

# UseForce_Draw2
#// LOF_175 Do or Do Not — "You may use the Force. If you do, draw 2. If you do not, draw 1." With the
#// Force, P1 uses it and draws 2.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:LOF_175}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1HANDCOUNT:2
