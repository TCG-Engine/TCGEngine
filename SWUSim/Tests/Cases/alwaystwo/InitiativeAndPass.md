#// ALWAYS TWO — shared initiative and the pass rule. docs/superpowers/specs/2026-11-20-swusim-always-two-design.md.
#// • "If one teammate takes the initiative, neither player can take actions for the rest of the round." The claim is stored per
#//   BOARD (card text "you take the initiative" means that board — owner ruling), but the lock is the whole TEAM.
#// • No STRATEGIC pass while the initiative is unclaimed (owner ruling: "no strategic passing until init is claimed"). Once it
#//   is claimed the claiming team is done and the other team keeps taking turns; its pass then ends the action phase — the
#//   pass streak counts TEAM turns (2), not boards (4).
#// P<n>PASSAVAIL reads SWUPassActionAllowed, the same predicate the production Pass button is gated on.
# PassNotOffered_WhileInitiativeUnclaimed
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
## EXPECT
P1PASSAVAIL:0
P3PASSAVAIL:0
P2PASSAVAIL:0

---

# TakingInitiative_LocksTheTeammate
#// Red board 1 takes it. Blue acts; on Red's next turn board 3 is locked too — Red auto-passes and the turn is straight back with
#// Blue (board 4), and board 3's play is refused.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2Resources: 8
WithP3Resources: 8
WithP2Hand: SOR_046
WithP3Hand: SOR_046
## WHEN
- P1>Claim
- P2>PlayHand:0
- P3>PlayHand:0
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
P2GROUNDARENACOUNT:1
P3GROUNDARENACOUNT:0
P3HANDCOUNT:1
TURNPLAYER:4
PHASE:MAIN

---

# OtherTeamMayPassAfterTheClaim_EndsThePhase
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>Claim
## EXPECT
P2PASSAVAIL:1
P4PASSAVAIL:1
PHASE:MAIN

---

# OneBluePassEndsThePhase
#// Red is done (claimed); Blue's single pass ends the action phase — Blue is not handed a second turn to pass again.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>Claim
- P2>Pass
## EXPECT
PHASE:RES

---

# OtherTeamKeepsActingAfterTheClaim
#// Blue takes several actions with either board; Red auto-passes in between.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2Resources: 8
WithP4Resources: 8
WithP2Hand: SOR_046
WithP4Hand: SOR_046
## WHEN
- P1>Claim
- P2>PlayHand:0
- P4>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
P4GROUNDARENACOUNT:1
TURNPLAYER:2
PHASE:MAIN

---

# WhenYouTakeTheInitiative_FiresForTheTakerOnly
#// SEC_168 Ziton Moj ("When you take the initiative: deal 2 to a base") on board 3. Board 1 takes the initiative: "you" is board 1,
#// so board 3's Ziton does nothing (owner ruling: initiative text means YOU, not the team).
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3GroundArena: SEC_168:1:0
## WHEN
- P1>Claim
## EXPECT
P3NODECISION
P1NODECISION

---

# WhenYouTakeTheInitiative_FiresForTheTaker
#// The mirror: Ziton on the taker's own board does fire.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SEC_168:1:0
## WHEN
- P1>Claim
## EXPECT
P1HASDECISION

---

# Control_Premier_PassStillAllowed
## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
## EXPECT
P1PASSAVAIL:1
