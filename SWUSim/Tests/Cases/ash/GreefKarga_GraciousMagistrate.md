# Decline_NoAdvantage
#// ASH_017 Greef Karga — declining the optional exhaust gives no Advantage and leaves Greef ready. P1 plays
#// SOR_095 and declines.
## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_017
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1LEADER:READY

---

# PlayUnit_Advantage
#// ASH_017 Greef Karga — "When you play or create a unit: you may exhaust this leader; if you do, give an
#// Advantage token to that unit." P1 plays SOR_095 and exhausts Greef to give it an Advantage token.
## GIVEN
CommonSetup: gyw/brk/{
  myLeader:ASH_017
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1LEADER:EXHAUSTED

---

# ExhaustedLeader_NoPromptForNextUnit
#// ASH_017 Greef Karga — using the ability exhausts Greef, so a SECOND unit played the same phase gets no
#// prompt. P1 plays SOR_095 and exhausts Greef (Advantage on it); the next unit SOR_063 enters with no
#// Advantage and no prompt because Greef is already exhausted.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017;handCardIds:SOR_095,SOR_063}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_063
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0
P1LEADER:EXHAUSTED

---

# EnemyPlaysUnit_NoTrigger
#// ASH_017 Greef Karga (front) — the trigger only fires when YOU play/create a unit. When the OPPONENT plays a
#// unit, Greef does not react: he stays ready, P1 gets no decision, and the enemy unit gets no Advantage.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017;theirhandCardIds:SEC_080}
SkipPreGame: true
WithP1Resources: 6
WithP2Resources: 8
## WHEN
- P1>Pass
- P2>PlayHand:0
## EXPECT
P1NODECISION
P1LEADER:READY
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# PlayUpgrade_NoTrigger
#// ASH_017 Greef Karga (front) — playing an UPGRADE is not playing/creating a unit, so Greef does not trigger.
#// P1 plays SOR_069 (Resilient) onto its SOR_095: the upgrade attaches, Greef stays ready and gives no Advantage.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017;handCardIds:SOR_069}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# DeployGreef_NoSelfAdvantage
#// ASH_017 Greef Karga (front) — deploying Greef via his Epic Action is not "playing or creating a unit", so
#// the trigger does not fire on himself: the leader unit enters with no Advantage token.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
## WHEN
- P1>DeployLeader:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_017
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# Deployed_UpgradeNoTrigger
#// ASH_017 Greef Karga (deployed) — playing an UPGRADE does not give an Advantage. P1 plays SOR_069
#// (Resilient) onto its SOR_095: only the upgrade attaches, no Advantage token is given.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017:1:1;handCardIds:SOR_069}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# Deployed_EnemyPlaysUnit_NoTrigger
#// ASH_017 Greef Karga (deployed) — the give only fires for units YOU play. When the opponent plays a unit,
#// Greef does not react and the enemy unit gets no Advantage.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017:1:1;theirhandCardIds:SEC_080}
SkipPreGame: true
WithP1Resources: 6
WithP2Resources: 8
## WHEN
- P1>Pass
- P2>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# Deployed_AutoGiveAdvantage
#// ASH_017 Greef (DEPLOYED leader unit) — playing a unit auto-gives it an Advantage token, with NO exhaust and
#// no prompt. Greef deployed; P1 plays SOR_095 → it enters with an Advantage.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1NODECISION
P1LEADER:DEPLOYED

---

# Deployed_EachSubsequentUnit
#// ASH_017 Greef (deployed) — the auto-give fires for EACH played unit. P1 plays SOR_095 then SOR_108; both
#// enter with an Advantage.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: [SOR_095 SOR_108]
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
P1GROUNDARENAUNIT:2:ADVANTAGECOUNT:1

---

# PlayPilotingUnitAsUpgrade_NoTrigger
#// ASH_017 Greef Karga (front) — playing a Piloting unit AS AN UPGRADE (a pilot) is not "playing or creating a
#// unit", so Greef does not trigger. P1 plays JTL_211 Independent Smuggler as a Pilot onto its LOF_192
#// N-1 Starfighter: the pilot attaches, Greef stays ready and gives no Advantage.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1SpaceArena: LOF_192:1:0
WithP1Hand: JTL_211
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1LEADER:READY
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# TokenCreation_OneAdvantage
#// ASH_017 Greef Karga (front) — the trigger fires on CREATING a unit too. P1 plays JTL_254 Dedicated Wingmen,
#// creating two X-Wing tokens; both trigger simultaneously but Greef can only exhaust once, so exactly one
#// token receives an Advantage token and Greef ends exhausted.
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_254
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1SPACEARENACOUNT:2
P1LEADER:EXHAUSTED

---

# Deployed_TokenCreation_EachAdvantage
#// ASH_017 Greef (deployed) — the auto-give also fires on CREATED tokens. P1 plays JTL_254 Dedicated Wingmen,
#// creating two X-Wing tokens; each receives an Advantage token automatically (no exhaust).
## GIVEN
CommonSetup: gyw/brk/{myLeader:ASH_017:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: JTL_254
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:1
