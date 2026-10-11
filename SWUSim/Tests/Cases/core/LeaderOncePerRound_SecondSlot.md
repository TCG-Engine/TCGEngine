# CadBane_Slot2_SpendsItsOwnRound
#// "Use this ability only once each round" on a DEPLOYED leader is tracked on THAT leader's NumUses.
#// Seven leaders read and spent it through SWUGetLeader(), i.e. leader SLOT 0, so a once-per-round
#// leader sitting in the SECOND slot spent — and read — the OTHER leader's round.
#// Every section in this file pairs two once-per-round leaders on one seat and uses BOTH in one round:
#//   *_Spends*  — the slot-2 leader goes FIRST; the slot-1 leader must still be offered afterwards.
#//              (pre-fix: slot 2 spent slot 1's round, so the second offer never came)
#//   *_Reads*   — the slot-1 leader goes FIRST; the slot-2 leader must still be offered afterwards.
#//              (pre-fix: slot 2 read slot 1's already-spent round and was refused)
#// Here: SOR_013 Cassian (slot 1, deployed: "When you deal damage to an enemy base: you may draw")
#// + SHD_014 Cad Bane (slot 2, deployed: "When you play an Underworld card: … deal 2 damage").
#// Greedo (Underworld) arms Cad; Cassian's own attack on the base arms Cassian.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_013:1:1; myLeader2:SHD_014:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_013
P1GROUNDARENAUNIT:1:CARDID:SHD_014
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:4
P1HANDCOUNT:1
P1DECKCOUNT:1
P1NODECISION

---

# CadBane_Slot2_ReadsItsOwnRound
#// Same seat as above, opposite order: Cassian (slot 1) draws first, then Cad Bane (slot 2) must still
#// be offered his ping. Pre-fix Cad read slot 1's spent round and made no offer.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SOR_013:1:1; myLeader2:SHD_014:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:4
P1HANDCOUNT:1
P1DECKCOUNT:1
P1NODECISION

---

# Cassian_Slot2_SpendsItsOwnRound
#// Leaders swapped: Cad Bane slot 1, Cassian slot 2. Cassian's draw (combat damage to the base) goes
#// first; Cad must still be offered on the Greedo play. Pre-fix Cassian's YES spent Cad's round.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:SOR_013:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_014
P1GROUNDARENAUNIT:1:CARDID:SOR_013
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:4
P1HANDCOUNT:1
P1DECKCOUNT:1
P1NODECISION

---

# Cassian_Slot2_ReadsItsOwnRound_Combat
#// Cad (slot 1) pings first; Cassian (slot 2) must still be offered his draw when he hits the base.
#// This is the attack-end collection site (CombatLogic, combat damage).

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:SOR_013:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:4
P1HANDCOUNT:1
P1DECKCOUNT:1
P1NODECISION

---

# Cassian_Slot2_ReadsItsOwnRound_NonCombat
#// Cassian's draw is armed from a SECOND site — the base-damage funnel, for non-combat damage. Cad
#// (slot 1) pings first; SHD_178 Daring Raid (not Underworld, so Cad stays out of it) then deals 2 to
#// the enemy base, and Cassian (slot 2) must still be offered his draw.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:SOR_013:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: [SOR_204 SHD_178]
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2
P1HANDCOUNT:1
P1DECKCOUNT:1
P1NODECISION

---

# ShinHati_Slot2_SpendsItsOwnRound
#// Cad Bane slot 1, ASH_016 Shin Hati slot 2 (deployed: "When a friendly unit's attack ends: you may
#// exhaust a unit that costs less than the combat damage dealt to a base. Once per round."). Count Dooku
#// (5) hits the base, Shin exhausts the Consular Security Force (cost 4); then Greedo must still arm Cad.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:ASH_016:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_014
P1GROUNDARENAUNIT:2:CARDID:ASH_016
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:5
P1NODECISION

---

# ShinHati_Slot2_ReadsItsOwnRound
#// Cad (slot 1) pings first; Shin (slot 2) must still be offered the exhaust when Dooku hits the base.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:ASH_016:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:5
P1NODECISION

---

# Poe_Slot2_SpendsItsOwnRound
#// Cad Bane slot 1, JTL_013 Poe Dameron slot 2, attached as a Pilot to the first TIE/ln ("Attach this
#// upgrade to a friendly Vehicle without a Pilot … once each round", 1 resource). Poe hops first; then
#// Greedo must still arm Cad.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:JTL_013}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaUpgrade: 0:JTL_013
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:mySpaceArena-1
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_013
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# Poe_Slot2_ReadsItsOwnRound
#// Cad (slot 1) pings first; Poe (slot 2) must still be able to hop.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:JTL_013}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaUpgrade: 0:JTL_013
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_013
P1NODECISION

---

# Thrawn_Slot2_SpendsItsOwnRound
#// Cad Bane slot 1, JTL_002 Thrawn slot 2 (deployed: free "use that When Defeated ability again", once
#// each round). The pre-damaged TIE Ambush Squadron dies attacking the ARC-170; its "create a TIE
#// Fighter" is reused (2 TIEs). Then Greedo must still arm Cad.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:JTL_002:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_T01
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# Thrawn_Slot2_ReadsItsOwnRound
#// Cad (slot 1) pings first; Thrawn (slot 2) must still offer the reuse.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:JTL_002:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_T01
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P1NODECISION

---

# Enfys_Slot2_SpendsItsOwnRound
#// Cad Bane slot 1, LAW_014 Enfys Nest slot 2 (deployed: free "use that On Attack ability again", once
#// each round). The Rebellion Y-Wing's On Attack (1 to a base) is reused: 1 + 1 + 2 combat = 4. Then
#// Greedo must still arm Cad.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:LAW_014:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1SpaceArena: IBH_006:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# Enfys_Slot2_ReadsItsOwnRound
#// Cad (slot 1) pings first; Enfys (slot 2) must still offer the reuse.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_014:1:1; myLeader2:LAW_014:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SOR_204
WithP1SpaceArena: IBH_006:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:4
P1NODECISION
