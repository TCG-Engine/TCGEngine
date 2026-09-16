# KashyyykBase_GainsAmbush
#// COVERAGE: offer=N/A (STRUCTURAL: a constant keyword grant, nothing chosen) · decline=Played_KashyyykBase_AmbushIsOffered
#//           answers the Ambush YES; declining Ambush is the generic keyword's own branch
#//           boundary=N/A (STRUCTURAL: a trait test, not a number)
#//           control=OpponentsKashyyykBase_DoesNotCount ("you control" = the controller's base)
#//           reqboundary=N/A (STRUCTURAL: recomputed on every read)
#//           modes=2P only ("you control" is self-only; no friendly/enemy wording)
#//           Raid 1 = keyword-only half, auto-wired by the generator
#//
#// HMW_131 Soaring Can-Cell — Unit (Ground) 1/4, cost 2, [Command], Creature.
#// "Raid 1. While you control a Kashyyyk base, this unit gains Ambush."
#// HMW_024 Origin Tree is a [Command] Kashyyyk base.

## GIVEN
CommonSetup: ggw/ggw/{myBase:HMW_024}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_131:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:1

---

# NonKashyyykBase_NoAmbush

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_131:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# OpponentsKashyyykBase_DoesNotCount

## GIVEN
CommonSetup: ggw/ggw/{theirBase:HMW_024}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_131:1:0

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# Played_KashyyykBase_AmbushIsOffered
#// Played with a Kashyyyk base, it may Ambush P2's SOR_095 (3/3): 1 power + Raid 1 = 2 damage.

## GIVEN
CommonSetup: ggw/ggw/{myBase:HMW_024;myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_131
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Played_NoKashyyykBase_NoAmbushOffer

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_131
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
