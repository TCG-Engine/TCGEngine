# CreditDefeatedByAnEffect_HasALine
#// Game-log follow-up (2026-09-11). SWUDefeatCreditToken never logged, so an effect that defeated a Credit
#// token (any player's) was silent — only the interactive payment picker wrote a line. (Fixture from
#// law/ArvelSkeen_WinAndWalkAway.md.) LAW_191 Arvel Skeen: "When Played/On Attack: You may defeat a Credit
#// token (belonging to any player). If you do, deal 1 damage to a unit or base."

## GIVEN
CommonSetup: rrw/bgw/{theirResources:0}
P1OnlyActions: true
WithP2Credits: 1
WithP1GroundArena: LAW_191:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirResources-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2CREDITCOUNT:0
LOGCONTAINS:P1's [[LAW_191|Arvel Skeen]] defeated P2's Credit token
LOGCOUNT:0:to pay

---

# UpgradeReturnedToHand_HasALine
#// SWUReturnUpgradeToHand (9 callers) never logged. SHD_209 Criminal Muscle: "When Played: You may return a
#// non-unique upgrade to its owner's hand." (Fixture from shd/CriminalMuscle.md.)

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_209
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1's [[SHD_209|Criminal Muscle]] returned [[SOR_120|
LOGCONTAINS:on P1's [[SEC_080|Imperial Dark Trooper]] to its owner's hand

---

# TokenUpgradeReturned_LeavesPlay
#// A TOKEN upgrade never reaches a hand — it ceases to exist. The line says so.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_209
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
LOGCONTAINS:P1's [[SHD_209|Criminal Muscle]] returned an Experience token on P2's [[SOR_095|Battlefield Marine]] (it left play)
LOGCOUNT:0:to its owner's hand

---

# Bounty_Collected_HasALine_AndIsTheSource
#// SWUCollectBounty wrote no line, and its handler name is not card-shaped, so a reward's effects were
#// credited to whatever resolved before it. SHD_027 Hylobon Enforcer: "Bounty — Draw a card." (Fixture
#// from shd/HylobonEnforcer.md.)

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_027:1:0
WithP2GroundArena: SOR_164:1:0
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P2HANDCOUNT:1
LOGCONTAINS:P2 collected the Bounty ([[SHD_027|Hylobon Enforcer]])
LOGCONTAINS:P2 drew 1 card ([[SHD_027|Hylobon Enforcer]])

---

# DeckOut_SaysWhy_AndTheDamageIsTheRules
#// A draw from an empty deck deals 3 to the drawer's base per card (CR 6.1). The damage is the GAME's, not
#// the card's that asked for the draw — "P2's Hylobon Enforcer dealt 3 damage to P2's base" was the
#// first-draft line. Now: why (with the card that asked), then a sourceless damage line.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_027:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
LOGCONTAINS:P2 couldn't draw 1 card — their deck is empty ([[SHD_027|Hylobon Enforcer]])
LOGCONTAINS:P2's base took 3 damage
LOGCOUNT:0:dealt 3 damage
