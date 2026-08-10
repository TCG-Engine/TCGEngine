# WhenDefeatedResourceSelf
#// LAW_159 Expendable Mercenary (3/3) — When Defeated: you may resource this unit from its owner's
#// discard pile. Attacks SOR_046 (3/7) and dies; it returns as a resource (exhausted). P1 started with 0
#// resources -> 1 (exhausted).

## GIVEN
CommonSetup: ggw/bgw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LAW_159:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1RESCOUNT:1
P1RESAVAILABLE:0
P1DISCARDCOUNT:0

---

# DecliningLeavesItInTheDiscard
#// "You MAY resource this unit" — the choice is real, and declining leaves the card in the discard with
#// the resource count unchanged. Worth having: a player can want it in the discard (recursion, discard
#// counters) or want to stay BELOW an opponent's resource count (SEC_151 Kazuda's "+2/+0 while you
#// control fewer resources").

## GIVEN
CommonSetup: ggw/bgw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LAW_159:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1RESCOUNT:0
P1DISCARDCOUNT:1

---

# DefeatedUnderEnemyControl_ResourcedByThatController
#// "…from its OWNER'S discard pile" — when the unit is defeated while an OPPONENT controls it, the card
#// goes to its OWNER's discard but the When Defeated belongs to whoever controlled it at the moment of
#// defeat. P2 plays JTL_043 No Glory, Only Results (take control of a non-leader unit, then defeat it):
#// P2 gets the prompt and the mercenary is resourced into P2's row, not P1's. P1 ends with 0 resources
#// and an empty discard; P2's only discard is the event itself.
#// (The take-control target auto-resolves — the mercenary is the only non-leader unit in play.)

## GIVEN
CommonSetup: ggw/bbk/{myResources:0; theirResources:10}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1GroundArena: LAW_159:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P1RESCOUNT:0
P2RESCOUNT:11
P1DISCARDCOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:0

---

# AnotherEffectResourcesItFirst_NoDoubleResource
#// The ability resources the card FROM the discard, so it can only fire if the card is still there.
#// P2 plays SHD_230 Swoop Down to let SHD_122 Arquitens Assault Cruiser (7/8) attack the ground and
#// defeat the mercenary; Arquitens' own "put the defeated unit into play as a resource under YOUR
#// control" moves it to P2 first. P1's prompt still appears but finds nothing to move, so the card is
#// resourced exactly ONCE, to P2 — P1 gains nothing and its discard stays empty.

## GIVEN
CommonSetup: ggw/yyk/{myResources:0; theirResources:10}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithP1GroundArena: LAW_159:1:0
WithP2SpaceArena: SHD_122:1:0
WithP2Hand: SHD_230

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1RESCOUNT:0
P2RESCOUNT:11
P1DISCARDCOUNT:0
P1NODECISION

---

# UsedByChimaeraWhileStillInPlay_DoesNothing
#// JTL_039 Chimaera, Reinforcing the Center ("When Played: you may use a 'When Defeated' ability on
#// another friendly unit") can point at the mercenary, but the ability resources the card FROM THE
#// DISCARD and the mercenary is still on the board — so it is a clean no-op: nobody gains a resource
#// and the mercenary stays in the ground arena.

## GIVEN
CommonSetup: bbk/bgw/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: LAW_159:1:0
WithP1Hand: JTL_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESCOUNT:8
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_159
P1SPACEARENACOUNT:1
