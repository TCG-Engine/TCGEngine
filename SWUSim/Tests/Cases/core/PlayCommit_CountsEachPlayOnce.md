# SmuggledEvent_CountsOnce
#// SSOT #1 (gamelog-updates, 2026-09-11) — SWUCommitPlay. "Cards played this phase" (SWU_CARDS_PLAYED) is
#// read by Vanguard Ace, Lothal Insurgent, Coordinate's "exactly the 2nd card", TS26_36 Tribunal, HMW
#// Talzin's Shuttle. It was bumped at 12 hand-copied commit points, and three play paths got it wrong.
#// A SMUGGLED EVENT counted TWICE: SWUSmuggleResource bumped it, then delegated the event to ActivateCard,
#// which bumped it again (and re-ran the one-shot charge consume). SHD_252 Smuggler's Aid via Smuggle, then
#// SOR_191 Vanguard Ace ("For each other card you played this phase, give an Experience token to this
#// unit") — one other card, one token.

## GIVEN
CommonSetup: gyw/gyw/{myBaseDamage:5}
P1OnlyActions: true
WithP1Resources: 1:SHD_252:1,7:SOR_095:1
WithP1Deck: [SOR_128 SOR_128]
WithP1Hand: SOR_191

## WHEN
- P1>SmuggleResource:0
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
LOGCOUNT:1:played [[SHD_252

---

# PlayFromOpponentsDiscard_CountsOnce
#// A card played from an OPPONENT's discard counted TWICE: SWUPlayFromOpponentDiscard (and the Unit answer
#// of its Unit-vs-Pilot fork) bumped the counter, then handed the card to ActivateCard, which bumped it
#// again. SEC_205 Obi-Wan mills P2's SOR_095 and P1 plays it from P2's discard; then Vanguard Ace — one
#// other card, one token. (Fixture from sec/ObiwanKenobi_FindingWhatDoesntExist.md.)

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 8
WithP1Hand: SOR_191
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1SPACEARENAUNIT:0:CARDID:SOR_191
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
LOGCOUNT:1:played [[SOR_095

---

# PilotFromHand_Counts
#// The opposite failure: a Pilot played from HAND never reached a commit point (the Unit-vs-Pilot fork
#// leaves before ActivateCard), so it did NOT count as a card played at all. JTL_084 Wingman Victor Two as
#// a pilot on SOR_225, then Vanguard Ace — one other card, one token. (The JTL_001 leader's token is space
#// index 1, so Vanguard is index 2.) The pilot's play line is still written once, by the attach step.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_084
WithP1Hand: SOR_191
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:2:CARDID:SOR_191
P1SPACEARENAUNIT:2:UPGRADECOUNT:1
LOGCOUNT:1:played [[JTL_084

---

# PilotOnlyFromHand_Counts
#// The OTHER hand-Pilot entry: when the unit cost can't be paid, SWUBeginPlayCard skips the Unit-vs-Pilot
#// question and goes straight to the Vehicle pick — a second path that never committed. JTL_084 (unit 2 +
#// 2 penalty = 4, pilot 1 + 2 = 3) with exactly 3 resources: it is attached as a pilot with no prompt, and
#// the cards-played counter is set.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: JTL_084
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
P1GLOBALEFFECT:SWU_CARDS_PLAYED
LOGCOUNT:1:played [[JTL_084

---

# OpponentsDiscard_PilotFork_UnitAnswer_CountsOnce
#// The Unit-vs-Pilot fork of a play from an OPPONENT's discard (FOREIGN_PILOT_PLAY_CHOICE) had its own copy of
#// the double count: the Unit answer bumped the counter, then handed the card to ActivateCard, which bumped it
#// again. It lost that bump in the same SWUCommitPlay edit as the direct path, but only the direct path had a
#// test. SEC_205 Obi-Wan mills P2's JTL_142 (Piloting); P1 controls a Vehicle (SOR_141), so the fork is
#// offered; P1 answers Unit, then plays SOR_191 Vanguard Ace — one other card, one token. (Fixture from
#// sec/ObiwanKenobi_FindingWhatDoesntExist.md MilledPilotingCard_UnitBranchPaysTheUnitCost.)

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1SpaceArena: SOR_141:1:0
WithP1Resources: 14
WithP1Hand: SOR_191
WithP2Deck: [JTL_142 JTL_142]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>AnswerDecision:Unit
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:JTL_142
P1SPACEARENAUNIT:1:CARDID:SOR_191
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
LOGCOUNT:1:played [[JTL_142

---

# OpponentsDiscard_PilotFork_PilotAnswer_CountsOnce
#// The other answer of the same fork: JTL_142 attached as a pilot to SOR_141 (SWUCommitPlay 'pilot' before the
#// Vehicle pick). One other card, one token.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1SpaceArena: SOR_141:1:0
WithP1Resources: 14
WithP1Hand: SOR_191
WithP2Deck: [JTL_142 JTL_142]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>AnswerDecision:Pilot
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_142
P1SPACEARENAUNIT:1:CARDID:SOR_191
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
LOGCOUNT:1:played [[JTL_142
