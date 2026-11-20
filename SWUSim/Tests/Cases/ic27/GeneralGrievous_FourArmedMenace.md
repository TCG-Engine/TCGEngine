# PlayUpgradeOnHim_OffersAllThree_ChooseDraw
#// IC27_021 General Grievous — Four-Armed Menace (4/5 Ground, Vigilance/Villainy, Separatist·Official, unique)
#// "When you play an upgrade on this unit: Choose one that hasn't been chosen this phase:
#//   • Give an Experience token to him. • Heal 3 damage from your base. • Draw a card."
#// Collected in CollectWhenPlayedAsUpgradeTriggers beside ASH_047 Gar Saxon / SHD_067 Fenn Rau (only when the
#// player who played the upgrade CONTROLS him, and he keeps his abilities); the trigger carries his UniqueID.
#// The reaction offers the modes not yet chosen this phase FOR THIS GRIEVOUS (GlobalEffects
#// SWU_IC27021_<uid>_<mode>, cleared at regroup); one mode left resolves without a prompt; none left -> nothing.
#// Labels: Experience / HealBase / Draw.
#//
#// COVERAGE: offer=PlayUpgradeOnHim_OffersAllThree_ChooseDraw + SecondUpgrade_ChosenModeIsGone ·
#//           decline=N/A (no "you may"; choosing one is mandatory) ·
#//           boundary=LastMode_ResolvesWithoutPrompt_ThenNothing (3 modes -> 1 left auto -> 0 left) ·
#//           control=OpponentPlaysAnUpgradeOnHim_NoTrigger ("you play" — the player who played it must control
#//           him) ·
#//           reqboundary=SecondUpgrade_ChosenModeIsGone (crosses a SimulateRequestBoundary between the two plays) +
#//           ModeAnsweredAcrossRequestBoundary ·
#//           modes=2P only ("you"/"your" — self-only in every format)
#//
#// P1 plays Resilient (+0/+3) on Grievous: all three modes are offered; Draw is chosen.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_021:1:0
WithP1Hand: [SOR_069 SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONHAS:Experience
P1OPTIONHAS:HealBase
P1OPTIONHAS:Draw

---

# ChooseDraw_DrawsOne

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_021:1:0
WithP1Hand: [SOR_069 SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:2
P1GROUNDARENAUNIT:0:HP:8
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# ChooseExperience_GivesItToHim

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_021:1:0
WithP1Hand: [SOR_069 SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Experience

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1HANDCOUNT:1

---

# ChooseHealBase_HealsThree

## GIVEN
CommonSetup: bbk/rrk/{myResources:4;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_021:1:0
WithP1Hand: [SOR_069 SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:HealBase

## EXPECT
P1BASEDMG:2
P1HANDCOUNT:1

---

# SecondUpgrade_ChosenModeIsGone
#// "that hasn't been chosen this phase": after Draw, the second upgrade (a later action, across a request
#// boundary) offers only Experience and HealBase.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_021:1:0
WithP1Hand: [SOR_069 SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Draw
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONNOT:Draw
P1OPTIONHAS:Experience
P1OPTIONHAS:HealBase

---

# LastMode_ResolvesWithoutPrompt_ThenNothing
#// After Draw and HealBase, the third upgrade's only remaining mode (Experience) resolves with no prompt; a
#// fourth upgrade finds nothing left and does nothing.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_021:1:0
WithP1Hand: [SOR_069 SOR_069 SOR_069 SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Draw
- P1>PlayHand:0
- P1>AnswerDecision:HealBase
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:5
P1GROUNDARENAUNIT:0:POWER:5
P1BASEDMG:2
P1DECKCOUNT:2
P1HANDCOUNT:1
P1NODECISION

---

# NextPhase_ModesReset
#// The "this phase" record clears: after the round turns over, Draw is offered again.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_021:1:0
WithP1Hand: [SOR_069 SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Draw
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1OPTIONHAS:Draw

---

# UpgradeOnAnotherUnit_NoTrigger
#// "on this unit" — an upgrade played on P1's other unit does nothing.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_021:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: [SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION
P1HANDCOUNT:0

---

# OpponentPlaysAnUpgradeOnHim_NoTrigger
#// "When YOU play an upgrade": P2 plays its own Resilient on P1's Grievous (an unrestricted upgrade may go on
#// any unit). Grievous's controller did not play it — nothing is offered to either player.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;theirResources:4}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: IC27_021:1:0
WithP2Hand: [SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
P2NODECISION
P1HANDCOUNT:0

---

# Blanked_NoTrigger
#// A Grievous that has lost all abilities (SOR_138 marker) has no reaction.

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_021:1:0:SOR_138
WithP1Hand: [SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
P1HANDCOUNT:0

---

# ModeAnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: bbk/rrk/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_021:1:0
WithP1Hand: [SOR_069 SOR_069]
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Draw

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:2
