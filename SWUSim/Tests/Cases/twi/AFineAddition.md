# ConditionNotMet_Fizzle
#// TWI_040 A Fine Addition — the condition ("If an enemy unit was defeated this phase") is NOT met, so the
#// event fizzles: no upgrade is played, the upgrade stays in hand, the friendly unit stays vanilla.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3

---

# Decline
#// TWI_040 A Fine Addition — the play is a "may": P1 declines (answers "-"), so nothing is played, the
#// upgrade stays in hand, and the unit stays vanilla.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1HANDCOUNT:1

---

# IgnoresAspectPenalty
#// TWI_040 A Fine Addition — "ignoring its aspect penalty": SOR_120 (Command, base cost 2) is fully
#// off-aspect under an Aggression/Villainy board. With only 2 ready resources it is affordable ONLY because
#// the aspect penalty is waived (unignored it would cost 4 and could not be offered → the event would
#// fizzle and POWER would stay 3). It attaches, spending exactly its base cost (2 → 0 resources).
## GIVEN
CommonSetup: brk/bbw/{myResources:2;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1RESAVAILABLE:0

---

# PilotFromHand
#// TWI_040 A Fine Addition — a PILOT can be played this way (user-confirmed ruling: A Fine Addition plays
#// from a known zone, no "search for an upgrade" clause, so pilots qualify — unlike Reforge). JTL_046
#// (Piloting [2], +2/+2) attaches as a Pilot to the only friendly Vehicle (SOR_237 Alliance X-Wing 2/3 →
#// 4/5). Vigilance/Heroism is fully off-aspect here, so it is affordable at cost 2 (from 3) ONLY because
#// the aspect penalty is ignored (unignored it would be 6).
## GIVEN
CommonSetup: brk/bbw/{myResources:3;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: JTL_046
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5
P1RESAVAILABLE:1

---

# UpgradeFromHand
#// TWI_040 A Fine Addition — core: after defeating an enemy this phase (P1's Marine kills the 3/1 Trooper),
#// play a regular Upgrade from hand. SOR_120 Academy Training (+2/+2, cost 2, Command) attaches to the only
#// friendly unit (auto-resolved host). Command is off-aspect under an Aggression/Villainy board, but the
#// aspect penalty is IGNORED, so it costs 2 (6→4 resources), not 4.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9
P1GROUNDARENAUNIT:0:DAMAGE:3
P1RESAVAILABLE:4

---

# UpgradeFromOpponentDiscard
#// TWI_040 A Fine Addition — "from any player's discard pile" includes the OPPONENT's discard. SOR_120 is
#// in P2's discard; P1 plays it from there onto its own unit. (The upgrade is still owned by P2 for later
#// discard routing, but the play + attach are what matter here.)
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040;theirDiscardCardIds:SOR_120}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:theirDiscard-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1RESAVAILABLE:4

---

# UpgradeFromOwnDiscard
#// TWI_040 A Fine Addition — "from any player's discard pile": play an Upgrade out of your OWN discard.
#// SOR_120 sits in P1's discard; after the kill, P1 plays it from discard onto the Marine.
## GIVEN
CommonSetup: brk/bbw/{myResources:6;handCardIds:TWI_040;discardCardIds:SOR_120}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1RESAVAILABLE:4
