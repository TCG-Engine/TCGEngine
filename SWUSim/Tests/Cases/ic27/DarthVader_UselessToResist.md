# OtherFriendlyGroundUnitGainsAmbush
#// IC27_067 Darth Vader (Useless to Resist) — 8 cost, 8/8, Command+Villainy, Ground, Force/Imperial/Sith.
#// Text: "Ambush / Each other friendly unit gains Ambush."
#// His own Ambush is printed (auto-wired via $Ambush_Cards); the AURA is the implemented half.
#// SEC_080 has no printed Ambush, so any Ambush on it comes from Vader.

## GIVEN
CommonSetup: ggk/ggk/{}
WithP1GroundArena: IC27_067:1:0
WithP1GroundArena: SEC_080:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_067
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush

---

# WithoutVader_OtherUnitHasNoAmbush
#// THE LOAD-BEARING NEGATIVE: the same unit, same board, Vader absent -> no Ambush.
#// Without this, a blanket "every friendly unit has Ambush" bug would pass the positive test.

## GIVEN
CommonSetup: ggk/ggk/{}
WithP1GroundArena: SEC_080:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# EnemyUnitDoesNotGainAmbush
#// "each other FRIENDLY unit" — the aura must not cross to the opponent's board.

## GIVEN
CommonSetup: ggk/ggk/{}
WithP1GroundArena: IC27_067:1:0
WithP2GroundArena: SEC_080:1:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# FriendlySpaceUnitGainsAmbush
#// No arena qualifier in the text, so a friendly SPACE unit is granted it too even though
#// Vader is a Ground unit.

## GIVEN
CommonSetup: ggk/ggk/{}
WithP1GroundArena: IC27_067:1:0
WithP1SpaceArena: SOR_225:1:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:HASKEYWORD:Ambush

---

# TokenUnitGainsAmbush
#// Value-CLASS variant: a Token Unit is still "a friendly unit" (GetUnitsInArena applies no
#// type filter), so the aura reaches it.

## GIVEN
CommonSetup: ggk/ggk/{}
WithP1GroundArena: IC27_067:1:0
WithP1GroundArena: TWI_T01:1:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush

---

# DeployedLeaderUnitGainsAmbush
#// Value-CLASS variant: a deployed leader unit is "a friendly unit" too (no non-leader qualifier).
#// myLeaderDeployed appends the leader unit AFTER the pre-placed Vader, so it lands at index 1.

## GIVEN
CommonSetup: ggk/ggk/{myLeaderDeployed:true}
WithP1GroundArena: IC27_067:1:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush

---

# PlayedUnitEntersAndAmbushAttacks
#// THE DISPATCH-PATH TEST: HASKEYWORD only proves the keyword READ. The granted Ambush must also
#// drive the real entry path (CollectEntryTriggers -> HasKeyword_Ambush -> the Ambush trigger),
#// and it must reach a unit that enters play AFTER Vader (the aura is live, not a snapshot).
#// SEC_080 (Command+Villainy) is on-aspect under ggk, so it costs its printed 2.
#// SEC_080 3/3 ambushes a 1/1 Battle Droid: deals 3 (kills it), takes 1 back and survives.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SEC_080}
WithP1GroundArena: IC27_067:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENACOUNT:1

---

# VaderLeavesPlay_GrantEnds
#// PERSISTENCE across a leave-play transition, and the one case WithoutVader_* cannot catch:
#// an implementation that STAMPED Ambush onto units when Vader entered (rather than reading the
#// aura live) would pass every other section here and still leave the grant behind after he dies.
#// Vader is seeded at 7 damage on 8 HP, so the 1-power Battle Droid's counter finishes him.

## GIVEN
CommonSetup: ggk/ggk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_067:1:7
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
P2GROUNDARENACOUNT:0
