# UseAnotherUnitsWhenDefeated
#// JTL_039 Chimaera — When Played: may use a "When Defeated" ability on another friendly unit.
#// P1 plays Chimaera; chooses JTL_087 (alive, "When Defeated: create a TIE") to use its ability.
#// JTL_087 stays in play; a TIE token is created. Arena ends with JTL_087 + Chimaera + TIE = 3.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_039
WithP1SpaceArena: JTL_087:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:3

---

# WhenDefeated_CreatesTwoTies
#// JTL_039 Chimaera — "When Defeated: Create 2 TIE Fighter tokens." Chimaera (5/6, pre-damaged to 1 HP)
#// attacks a small enemy space unit and dies to the counter; its When Defeated then makes 2 TIE tokens
#// (JTL_T01) for its controller. (Active player attacks into a lethal counter — the combat-WhenDefeated
#// pattern that doesn't stall the harness.)

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_039:1:5
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_T01
P1SPACEARENAUNIT:1:CARDID:JTL_T01

---

# CannotSelectItself
#// JTL_039 Chimaera — "use a When Defeated ability on ANOTHER friendly unit." With no other friendly unit

## GIVEN
CommonSetup: ggk/bbk/{myLeader:JTL_005;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_039

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_039
P1NODECISION

---

# CannotTriggerEnemyWhenDefeated
#// JTL_039 Chimaera targets only FRIENDLY units. With the only When Defeated unit being an enemy (JTL_087,
#// "When Defeated: create a TIE"), Chimaera's When Played finds no valid target — no TIE is created and the
#// enemy unit is untouched.

## GIVEN
CommonSetup: ggk/bbk/{myLeader:JTL_005;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_039
WithP2SpaceArena: JTL_087:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_039
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_087
P1NODECISION

---

# DoesNotDefeatSelectedUnit
#// JTL_039 Chimaera's When Played USES another friendly unit's "When Defeated" ability without
#// defeating it. TWI_032 Wartime Trade Official ("When Defeated: Create a Battle Droid token.") is
#// selected: a Battle Droid (TWI_T01) appears and TWI_032 is still in the ground arena afterwards.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1GroundArena: TWI_032:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_032
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_039

---

# OffersNoOpWhenDefeated_DocumentedGap
## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1GroundArena: TWI_164:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_164
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_039
P1HASDECISION

---

# UseUpgradeGrantedWhenDefeated
#// JTL_039 Chimaera can use a "When Defeated" ability the target GAINED FROM AN UPGRADE (TWI_218
#// Droid Cohort, "Attached unit gains, 'When Defeated: Create a Battle Droid token.'"). SOR_164 Wampa
#// has no innate When Defeated — the upgrade is the only source. Selecting Wampa creates a Battle
#// Droid; Wampa survives with its upgrade still attached.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:TWI_218

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1SPACEARENACOUNT:1

---

# UseUnitGrantedWhenDefeated
#// JTL_039 Chimaera can use a "When Defeated" ability the target GAINED FROM ANOTHER UNIT (SOR_105
#// General Krell, "Each OTHER friendly unit gains: 'When Defeated: You may draw a card.'"). Krell
#// grants it to SOR_095 Battlefield Marine — and to Chimaera itself, but Chimaera may only target
#// ANOTHER friendly unit, and Krell (which does not grant to itself) has no When Defeated, so the
#// Marine is the only legal target. Using it draws a card; both units stay in play.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1HANDCOUNT:1
P1SPACEARENACOUNT:1

---

# UseEventGrantedWhenDefeated
#// JTL_039 Chimaera can use a "When Defeated" ability the target GAINED FROM AN EVENT (TWI_129 In
#// Defense of Kamino, "For this phase, each friendly Republic unit gains Restore 2 and: 'When
#// Defeated: Create a Clone Trooper token.'"). TWI_058 Padawan Starfighter is Republic and gains it;
#// Chimaera (Imperial) does not. Selecting the Padawan creates a Clone Trooper token (TWI_T02) in the
#// GROUND arena while the Padawan itself stays in the space arena.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: TWI_129
WithP1Hand: JTL_039
WithP1SpaceArena: TWI_058:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:TWI_058
P1SPACEARENAUNIT:1:CARDID:JTL_039
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T02

---

# ChooseBetweenTwoWhenDefeatedOnSameUnit
#// JTL_039 Chimaera — when the selected unit holds MORE THAN ONE "When Defeated" ability, its
#// controller picks which one to use. SHD_164 Rhokai Gunship has an innate "Deal 1 damage to a unit or
#// base" AND carries TWI_218 Droid Cohort granting "Create a Battle Droid token." Chimaera offers both
#// (labelled by the CardID supplying each: the host's own SHD_164 for the innate one, TWI_218 for the
#// granted one); choosing TWI_218 creates a Battle Droid and deals NO damage anywhere.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1SpaceArena: SHD_164:1:0
WithP1SpaceArenaUpgrade: 0:TWI_218

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:TWI_218

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SHD_164
P1SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# ChooseInnateWhenDefeatedOverGranted
#// JTL_039 Chimaera — the other side of the same choice: picking the host's INNATE ability instead of
#// the upgrade-granted one. SHD_164 Rhokai Gunship's own "Deal 1 damage to a unit or base" is selected
#// (label = its own CardID) and aimed at P2's base; no Battle Droid is created.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1SpaceArena: SHD_164:1:0
WithP1SpaceArenaUpgrade: 0:TWI_218

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:SHD_164
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:2

---

# CannotSelectWhenDefeatedOnUpgradeItself
#// JTL_039 Chimaera may use a When Defeated ability ON A UNIT — not one that belongs to an UPGRADE
#// rather than to its host. TWI_069 Roger Roger's "When Defeated: Attach this upgrade to a friendly
#// Battle Droid token" is the upgrade's OWN ability (it re-attaches itself); it is not granted to the
#// host, so SOR_164 Wampa gains no When Defeated from carrying it and is not a legal target. With no
#// other candidate, Chimaera's When Played finds nothing and offers no decision.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:TWI_069

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENACOUNT:1
P1NODECISION

---

# DoesNotTriggerBounty
#// JTL_039 Chimaera USES a unit's When Defeated ability without defeating it, so the unit's BOUNTY
#// never fires — a bounty is collected by whoever DEFEATS the unit, and no defeat happens here.
#// SHD_058 Val has "Bounty — Deal 3 damage to a unit" and "When Defeated: Give 2 Experience tokens to
#// a friendly unit". Chimaera uses Val's When Defeated, putting 2 Experience on Val herself; Val stays
#// in play undamaged and P2 (who would collect the bounty) is never offered anything.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1GroundArena: SHD_058:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_058
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P2NODECISION

---

# CountDookuGrantedWhenDefeatedNoError
#// JTL_039 Chimaera evaluating a unit whose ONLY When Defeated is granted by an upgrade, on a card
#// with a complex When Played of its own (TWI_138 Count Dooku — Exploit 2 / Overwhelm / a When Played
#// that references exploited units). Dooku has no innate When Defeated; SEC_039 Creditor's Claim grants
#// "When Defeated: You may defeat a unit with 3 or less remaining HP." Chimaera selects Dooku, the
#// granted ability resolves (single option, so no ability picker), and it defeats P2's SOR_095
#// Battlefield Marine (3 HP). Regression against errors while scanning a card like Dooku for abilities.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1GroundArena: TWI_138:1:0
WithP1GroundArenaUpgrade: 0:SEC_039
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_138

---

# UseMultiStepWhenDefeated_ShuttleSt149
#// JTL_039 Chimaera activating a MULTI-STEP When Defeated (JTL_242 Shuttle ST-149, "When Played/When
#// Defeated: You may take control of a token upgrade on a unit and attach it to a different eligible
#// unit") — the ability asks for the token first, then the destination. The Shuttle carries a Shield
#// token (SOR_T02); Chimaera activates the Shuttle's ability while it is still alive, the Shield is
#// selected, and the only other eligible destination is Chimaera itself, so the Shield ends up on
#// Chimaera and the Shuttle is left with no upgrades.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1SpaceArena: JTL_242:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_242
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:JTL_039
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:SOR_T02

---

# UseStatReferencingWhenDefeated
#// JTL_039 Chimaera activating a When Defeated whose EFFECT AMOUNT reads the source unit's own stats
#// (JTL_104 Raddus, "When Defeated: Deal damage equal to this unit's power to an enemy unit"). Unlike
#// a real defeat, the source is still ALIVE and fully buffed here, so the amount must use its CURRENT
#// power: Raddus is 8 printed + 1 from an Experience token (SOR_T01) = 9, dealing 9 to Krayt Dragon
#// (10 HP, so it survives). SHD_072 Imprisoned is attached to Krayt Dragon to strip its "When an
#// opponent plays a card" reaction, which would otherwise fire when Chimaera is played.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1SpaceArena: JTL_104:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SHD_172:1:0
WithP2GroundArenaUpgrade: 0:SHD_072

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_172
P2GROUNDARENAUNIT:0:DAMAGE:9
P1SPACEARENACOUNT:2

---

# UseWhenDefeatedThatMakesOpponentChoose
#// JTL_039 Chimaera activating a When Defeated that hands a choice to the OPPONENT (JTL_183 Zygerrian
#// Starhopper, "When Defeated: Deal 2 indirect damage to a player" — the controller picks who takes
#// it, then THAT player assigns the 2 unpreventable damage among their own base and units). P1 sends
#// it to the opponent; P2 puts both points on their own base. The Starhopper is never defeated.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1SpaceArena: JTL_183:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:2

## EXPECT
P2BASEDMG:2
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_183

---

# UseClonedWhenDefeated
#// JTL_039 Chimaera can activate a "When Defeated" a unit has by being a COPY of another unit
#// (TWI_116 Clone, "You may have this unit enter play as a copy of a non-leader, non-Vehicle unit in
#// play"). P1's Clone enters as a copy of P2's TWI_032 Wartime Trade Official, so the Clone — a
#// FRIENDLY unit — carries the copied "When Defeated: Create a Battle Droid token." Chimaera uses it
#// for exactly ONE Battle Droid: the enemy original is still not a legal target for Chimaera, so
#// copying an enemy's ability does not also make the enemy's own copy of it usable.

## GIVEN
CommonSetup: bbk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 20
WithP1Hand: TWI_116
WithP1Hand: JTL_039
WithP2GroundArena: TWI_032:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_032
P1SPACEARENACOUNT:1
