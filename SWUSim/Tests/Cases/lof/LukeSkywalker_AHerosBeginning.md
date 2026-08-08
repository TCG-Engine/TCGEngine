# PlayUnique_UseForce_ExpShield
#// LOF_249 Luke Skywalker (3/5) — "When you play another unique unit: may use the Force → give an
#// Experience and a Shield token to this unit." P1 plays the unique Owen Lars (LOF_057); the reaction lets
#// P1 use the Force, and Luke gains an Experience + a Shield (2 subcards).

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;handCardIds:LOF_057}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_249:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# NonUnique_NoTrigger
#// LOF_249 Luke Skywalker — the reaction only fires on ANOTHER UNIQUE unit. Playing the non-unique
#// Battlefield Marine (SOR_095) does not trigger: no prompt, Force retained, Luke gains nothing. (Intended: "should
#// not trigger when non-unique unit is played".)

## GIVEN
CommonSetup: bbw/rrk/{myResources:3;handCardIds:SOR_095}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_249:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASFORCE
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# NoForce_NoTrigger
#// LOF_249 Luke Skywalker — with no Force token the "may use the Force" reaction cannot fire even on a unique
#// unit. P1 plays the unique Owen Lars (LOF_057) without the Force; no prompt, Luke gains nothing. (Intended: #// "should not trigger if player does not have the Force".)

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;handCardIds:LOF_057}
P1OnlyActions: true
WithP1GroundArena: LOF_249:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# OpponentUnique_NoTrigger
#// LOF_249 Luke Skywalker — the reaction is "when YOU play another unique unit"; an opponent playing a unique
#// unit does not trigger Luke. P2 plays the unique Owen Lars (LOF_057); Luke (P1) gains nothing and P1 keeps
#// the Force. (Intended: "should not trigger when opponent plays unique unit".)

## GIVEN
CommonSetup: bbw/rrk/{myResources:3;theirResources:6}
SkipPreGame: true
WithP1Force: true
WithP1GroundArena: LOF_249:1:0
WithP2Hand: LOF_057
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true

## WHEN
- P2>PlayHand:0

## EXPECT
P1HASFORCE
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# DeclineForce_NoExpNoShield_ForceRetained
#// LOF_249 Luke Skywalker — "you MAY use the Force" is a real choice, distinct from having none. P1 plays
#// the unique Owen Lars (LOF_057) with a Force token in hand but DECLINES: Luke gets no Experience and no
#// Shield, and the Force token is RETAINED because it was never spent.
## GIVEN
CommonSetup: bbw/rrk/{myResources:1;handCardIds:LOF_057}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_249:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1HASFORCE
P1GROUNDARENAUNIT:0:CARDID:LOF_249
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
