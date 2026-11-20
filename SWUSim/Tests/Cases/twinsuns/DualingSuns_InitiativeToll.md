# DualingSuns_KeepIsFree
#// Dualing Suns (docs/superpowers/specs/2026-11-12-swusim-dualing-suns-design.md): taking the initiative you already hold
#// ("keeping" it) costs nothing.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
## WHEN
- P1>Claim
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
P1BASEDMG:0
P2BASEDMG:0

---

# DualingSuns_StealCostsOne
#// Taking the initiative while the opponent holds it ("stealing" it) deals 1 damage to your own base — the Blast a third
#// player would have hit you with.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: false
## WHEN
- P1>Claim
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
P1BASEDMG:1
P2BASEDMG:0
LOGCONTAINS:P1 stole the initiative (1 damage to their own base)

---

# DualingSuns_HolderPlansOpponentPaysToll
#// The holder takes Plan first, so the opponent has to take the initiative — and pays the toll. Allowed: a strategy in itself.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>TakeCounter:plan
- P1>AnswerDecision:myHand-0
- P2>Claim
## EXPECT
INITIATIVECOUNTER:P2_CLAIMED
P2BASEDMG:1
P1BASEDMG:0

---

# DualingSuns_StealAtOneHp_FixtureIsAtOneHp
#// Guards the next section's fixture: SOR_026 Catacombs of Cadera is 30 HP, so 29 damage leaves exactly 1.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; myBaseDamage:29}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: false
## WHEN
## EXPECT
P1BASEREMAINING:1
NOWINNER

---

# DualingSuns_StealAtOneHp_StealerLoses
#// The toll is ordinary base damage and can be lethal: stealing at 1 remaining HP loses the game (not a draw).
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; myBaseDamage:29}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: false
## WHEN
- P1>Claim
## EXPECT
P2WIN

---

# DualingSuns_StealIsPreventable
#// Like the Blast, the toll can be prevented: JTL_074 Close the Shield Gate on P1's base stops it.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: false
WithP1GlobalEffect: SWU_SHIELD_GATE
## WHEN
- P1>Claim
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
P1BASEDMG:0
LOGCONTAINS:was prevented ([[JTL_074|Close the Shield Gate]])

---

# DualingSuns_KeepFiresWhenYouTakeTheInitiative
#// Keeping counts as "taking the initiative" (the SWU rules: it means claiming it for yourself), so HMW_168 Ezra Bridger's
#// "When you take the initiative" offer fires on a keep — with no toll.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1GroundArena: HMW_168:1:0
## WHEN
- P1>Claim
## EXPECT
P1DECISIONTOOLTIP:Deal_3_to_your_base_to_create_a_Beast_token?
P1BASEDMG:0

---

# DualingSuns_PremierStealIsFree
#// Without the dualingsuns pool, claiming the initiative from the other seat is free, exactly as before.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: false
## WHEN
- P1>Claim
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
P1BASEDMG:0

---

# DualingSuns_StealAtOneHp_AfterOpponentPlanned_StealerLoses
#// The routine Dualing Suns endgame: the holder takes Plan, so the opponent at 1 HP must take the initiative and pay the
#// toll. The toll is lethal AND it is the round's last counter. The game must end on the toll — the round-ending pass that
#// follows used to run the phase loop on a decided game and never return (final review 2026-11-13, C1).
#// ⚠ The failure mode is a HANG: run this file under `timeout`.
## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; theirBaseDamage:29}
SkipPreGame: true
WithCardPool: dualingsuns
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1Hand: SOR_251
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>TakeCounter:plan
- P1>AnswerDecision:myHand-0
- P2>Claim
## EXPECT
P1WIN
