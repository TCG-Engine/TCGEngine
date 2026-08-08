# PlayUpgrade_UseForce_Draw
#// LOF_229 Kylo Ren (2/3) — Overwhelm + "When you play an upgrade on this unit: may use the Force → draw a
#// card." P1 plays Resilient (SOR_069) onto Kylo; the reaction lets P1 use the Force and draw.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1;handCardIds:SOR_069}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_229:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# PlayUpgrade_NoForce_NoTrigger
#// LOF_229 Kylo Ren — negative: with no Force token, playing an upgrade on Kylo cannot use the Force, so
#// the reaction does nothing and no card is drawn. The upgrade still attaches.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1;handCardIds:SOR_069}
P1OnlyActions: true
WithP1GroundArena: LOF_229:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# PlayUpgrade_DeclineForce_NoDraw_ForceRetained
#// LOF_229 Kylo Ren — "you MAY use the Force" is a real choice distinct from having no Force at all.
#// With a Force token available P1 DECLINES: no card is drawn and the Force token is RETAINED (it was
#// never spent). The upgrade still attaches.
## GIVEN
CommonSetup: bbk/rrk/{myResources:1;handCardIds:SOR_069}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_229:1:0
WithP1Deck: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1HASFORCE
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# UpgradeOnAnotherUnit_NoTrigger
#// LOF_229 Kylo Ren — the reaction is "when you play an upgrade ON THIS UNIT". An upgrade played on a
#// DIFFERENT friendly unit (Battlefield Marine) must not offer it: the Force is untouched, nothing is
#// drawn, and no prompt appears.
## GIVEN
CommonSetup: bbk/rrk/{myResources:1;handCardIds:SOR_069}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_229:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1HASFORCE
P1HANDCOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION
