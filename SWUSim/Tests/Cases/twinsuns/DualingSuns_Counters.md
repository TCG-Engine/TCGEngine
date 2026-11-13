# DualingSuns_RoundOffersInitiativeAndPlanOnly
#// Dualing Suns (docs/superpowers/specs/2026-11-12-swusim-dualing-suns-design.md): a 1v1 Twin Suns game whose round has two
#// counters, Initiative and Plan. There is no Blast, and no Pass while a counter is still there to take.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
## WHEN
## EXPECT
SEATCOUNT:2
P1PLANAVAIL:1
P1BLASTAVAIL:0
P1PASSAVAIL:0

---

# DualingSuns_TakingPlanEndsYourRound
#// Plan draws 1 and bottoms 1, then the seat is done for the round. The opponent still may not pass: the Initiative is there.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>TakeCounter:plan
- P1>AnswerDecision:myHand-0
## EXPECT
PLANCOUNTER:P1
TURNPLAYER:2
P2PASSAVAIL:0
P2PLANAVAIL:0
PHASE:MAIN

---

# DualingSuns_KeepThenOpponentMustPlan
#// The holder keeps the initiative; the opponent's only way to end the round is the Plan counter, which ends the action phase.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP2Hand: SOR_251
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Claim
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
TURNPLAYER:2
P2PLANAVAIL:1
P2PASSAVAIL:0
PHASE:MAIN

---

# DualingSuns_OpponentPlanEndsTheActionPhase
#// After the keep, the opponent's Plan is the last counter: the action phase ends.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP2Hand: SOR_251
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>Claim
- P2>TakeCounter:plan
- P2>AnswerDecision:myHand-0
## EXPECT
PLANCOUNTER:P2
PHASEISNOT:MAIN

---

# DualingSuns_ForgedBlastIsRefused
#// There is no Blast counter in Dualing Suns. A forged take (an old client button, a crafted request) does nothing: no damage,
#// the counter stays where it was, and the turn stays with the same player.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
## WHEN
- P1>TakeCounter:blast
## EXPECT
BLASTCOUNTER:AVAILABLE
TURNPLAYER:1
P2BASEDMG:0
P1PASSAVAIL:0

---

# DualingSuns_PremierUnchanged
#// Without the dualingsuns pool, a two-seat game is plain Premier: Pass is always there and there are no Blast / Plan counters.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
## WHEN
## EXPECT
P1PASSAVAIL:1
P1PLANAVAIL:0
P1BLASTAVAIL:0
