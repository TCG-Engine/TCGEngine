# WhenPlayed_GivesHerselfTwoExperience
#// IC27_045 Qi'ra — Survivor (1/1 Ground, Vigilance/Vigilance, Underworld, unique)
#// "When Played: Give 2 Experience tokens to this unit.
#//  When Defeated: Choose a unit. You may give an Experience token to it for each Experience token that was on
#//  this unit."
#// When Played: two DoGiveExperienceToken calls on herself. When Defeated: her Experience count is snapshotted
#// at the defeat collection point (subcards still attached — the JTL_104 Raddus window) and persisted; the
#// closure offers an optional pick over every unit on the table (MZMAYCHOOSE — "you may") with
#// GIVE_EXPERIENCE|N. With N = 0 nothing is offered (a fizzle-only optional).
#//
#// COVERAGE: offer=WhenDefeated_Offer_EveryUnitOnTheTable ·
#//           decline=WhenDefeated_Decline_NoTokens ·
#//           boundary=WhenDefeated_ThreeExperience_GivesThree (count read, not a fixed 2) +
#//           WhenDefeated_NoExperience_NoPrompt (0) ·
#//           control=N/A (no owner-scoped zone; the resolver is her controller at defeat — the generic WD rule) ·
#//           reqboundary=WhenDefeated_AnsweredAcrossRequestBoundary ·
#//           modes=2P only (no player reference, no friendly/enemy wording — "a unit" is the whole table)

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_045

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_045
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1NODECISION

---

# WhenDefeated_TwoExperience_GivesTwo
#// Qi'ra (3/3 with 2 Experience) attacks Industrious Team (4/7) and dies. P1 gives 2 Experience to P1's
#// Battlefield Marine.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenDefeated_Offer_EveryUnitOnTheTable
#// "Choose a unit" — any unit: P1's Marine and P2's Industrious Team (and its X-Wing in space). Left pending.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirSpaceArena-0

---

# WhenDefeated_EnemyUnit_CanTakeThem
#// The tokens may go to an ENEMY unit.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# WhenDefeated_ThreeExperience_GivesThree
#// Quantity: the count is "each Experience token that was on this unit" — 3 here (a Shield token on her is
#// NOT counted). Qi'ra 4/4 dies to a 4/7.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3

---

# WhenDefeated_ShieldIsNotExperience
#// A Shield token is not counted: 1 Experience + 1 Shield on her -> 1 token given. She is defeated by P1's own
#// Vanquish (a defeat, so the Shield does not matter).

## GIVEN
CommonSetup: bbw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_078
WithP1GroundArena: IC27_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# WhenDefeated_Decline_NoTokens

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# WhenDefeated_NoExperience_NoPrompt
#// No Experience on her when she dies: zero tokens to give, so nothing is offered.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_045:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# WhenDefeated_AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_045:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
