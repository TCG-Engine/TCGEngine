# Uniqueness rule (CR 8.19.1.b / 29.3): a player may control only ONE copy of a given unique
# card (same name + subtitle) in play at a time. If they ever control two, they must IMMEDIATELY
# choose and defeat copies until one remains. This is NOT a triggered ability — it happens as a
# game rule the instant the second copy resolves into play.
#
# SOR_034 Del Meeko: Providing Overwatch is UNIQUE (cost 3, Vigilance/Villainy, no When-Played
# ability — so nothing but the uniqueness rule fires here). P1 already controls one copy in the
# ground arena (ready) and plays a second copy from hand. Because both copies are now in play
# under P1's control, the engine must ASK P1 which one to defeat (the player chooses — CR 29.3),
# then defeat it. P1 defeats the freshly-played copy, leaving the original ready copy in play.
#
# Iden Versio (bk = Vigilance+Villainy) covers both of SOR_034's aspects, so no aspect penalty:
# cost is exactly 3, matching the 3 resources.
#
# NOTE: uniqueness enforcement is not yet implemented — this test is expected to be RED until the
# rule is wired into the "unit enters play" path. Today PlayHand leaves TWO copies in play with no
# prompt, so the AnswerDecision below has nothing to answer and the counts stay at 2 / 0.

## GIVEN
CommonSetup: ybk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_034
WithP1GroundArena: SOR_034:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:1

## EXPECT
# Exactly one copy remains in play — the original, still ready and undamaged.
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_034
P1GROUNDARENAUNIT:0:READY
# The chosen (freshly-played) copy is defeated to the owner's discard, from play.
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_034
P1DISCARDUNIT:0:FROM:PLAY
# The uniqueness prompt has fully resolved — nothing left pending.
P1NODECISION
