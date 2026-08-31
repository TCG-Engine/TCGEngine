# DeployedOnAttack
#// LOF_010 Third Sister (deployed) — On Attack: the next unit you play this phase gains Hidden. She attacks
#// the base, then P1 plays Plo Koon, who enters with Hidden.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 12
WithP1Hand: LOF_050

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_050
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden

---

# PlayHiddenUnit
#// LOF_010 Third Sister — Action [Exhaust]: Play a unit from your hand. It gains Hidden for this phase. Plo
#// Koon enters with Hidden.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LOF_050
WithP1Resources: 10

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden

---

# DeployedOnAttack_PilotingSkipped
#// LOF_010 Third Sister (deployed) — On Attack: "the next unit you play this phase gains Hidden." A unit
#// played WITH PILOTING (as an upgrade, not entering as a unit) does NOT consume the pending Hidden grant and
#// does not itself become Hidden. Third Sister attacks the base; P1 first plays Paige Tico (JTL_046) as a
#// Pilot onto the friendly TIE Advanced — no Hidden — then plays Battlefield Marine (SOR_095) as a unit,
#// which is the one that gains Hidden. (Intended: "should not give hidden to units played with piloting".)

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 12
WithP1Hand: JTL_046
WithP1Hand: SOR_095
WithP1SpaceArena: SOR_231:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_231
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:NOTKEYWORD:Hidden
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden

---

# Front_UnaffordableUnit_NotSelectable
#// LOF_010 Third Sister front — "Action [Exhaust]: Play a unit from your hand." The unit is still PAID FOR
#// normally, so a unit P1 cannot afford must not be offered. With 3 resources the two cost-2 units
#// (IBH_057 Snowtrooper Vanguard, SEC_028 Trayus Acolyte) are both selectable but SOR_039 AT-AT Suppressor
#// (cost 8) is not. All three are on-aspect under a Vigilance base + Aggression/Villainy leader, so cost —
#// not an aspect penalty — is what excludes it. (Two affordable units on purpose: with only one the choice
#// auto-resolves and there is no offer left to inspect.)
## GIVEN
CommonSetup: brk/bbk/{myLeader:LOF_010;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IBH_057
WithP1Hand: SEC_028
WithP1Hand: SOR_039
WithP1Resources: 3
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# DeployedOnAttack_OnlyTheNextUnit_SecondGetsNothing
#// LOF_010 Third Sister (deployed) — On Attack grants Hidden to "the NEXT unit you play this phase",
#// singular. Two units played after the attack: only the FIRST gains Hidden; the second does not. Without
#// this the grant could be a blanket "every unit this phase" and every existing section would still pass.
## GIVEN
CommonSetup: brk/bbk/{myLeader:LOF_010;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 12
WithP1Hand: IBH_057
WithP1Hand: SEC_028
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:IBH_057
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
P1GROUNDARENAUNIT:2:CARDID:SEC_028
P1GROUNDARENAUNIT:2:NOTKEYWORD:Hidden

---

# DeployedOnAttack_ArmExpiresNextPhase
#// LOF_010 Third Sister (deployed) — the grant is "this phase", so an UNSPENT arm must not carry over.
#// She attacks the base to arm it, both players pass through regroup into the next action phase without
#// playing anything, then P1 plays IBH_057 — which must enter WITHOUT Hidden.
## GIVEN
CommonSetup: brk/bbk/{myLeader:LOF_010;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 12
WithP1Hand: IBH_057
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:IBH_057
P1GROUNDARENAUNIT:1:NOTKEYWORD:Hidden

---

# Deployed_ThisPhase_SheIsACTUALLYUnattackable
#// THE GAP THIS FILE HAD, and the card that states the rule most plainly. LOF_010's deployed side prints
#// "Hidden (This unit can't be attacked if she was DEPLOYED this phase.)" — the reminder text says
#// DEPLOYED, not "played", which is the wording most Hidden cards use and the wording that produced bug
#// reports #1025/#1026. Third Sister was broken by that same bug: `_SWUHiddenBlocksAttack` tested
#// SWU_PLAYED_UNIT_, set only by ActivateCard, so a leader that DEPLOYED never carried it and her own
#// printed Hidden did nothing at all.
#// ⚠ EVERY OTHER SECTION IN THIS FILE ASSERTS `HASKEYWORD:Hidden`, which only says the keyword is
#// PRESENT — ObjectHasHidden is 1 whenever a unit has Hidden, attackable or not. Nothing here tested
#// that Hidden BLOCKS anything, which is exactly how the defect survived. ATTACKTARGETS does.
## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>DeployLeader
## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
#// P1's base only — Third Sister is out of the pool.
ATTACKTARGETS:2:G:0:1

---

# Deployed_PreviousPhase_SheIsAttackableAgain
#// THE CONTROL for the section above. Hidden lapses with the phase: the round turns over, the
#// entered-play marker clears in RegroupPhaseStart, and she is a legal target again — 2 targets, not 1.
#// Without this, the section above would pass on a rule that made every deployed leader permanently
#// unattackable.
## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046
## WHEN
- P1>DeployLeader
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
ATTACKTARGETS:2:G:0:2

---

# Front_PlayHiddenUnit_TheGrantACTUALLYBlocks
#// The front Action's grant, tested for EFFECT rather than for the keyword being present. PlayHiddenUnit
#// above already proves Plo Koon arrives with the keyword; this proves an opponent cannot attack him.
#// The played unit qualifies on both counts — it was played (so it entered play this phase) AND it holds
#// the granted Hidden — so the block is real, not merely a badge.
## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 12
WithP1Hand: LOF_050
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden
ATTACKTARGETS:2:G:0:1

---

# Front_GrantedHidden_LapsesNextPhase
#// The grant is "for this phase" and Hidden's own condition is "entered play this phase" — both expire
#// together at the round turn, so Plo Koon becomes attackable. This is the unhappy-path twin of the
#// section above and the thing that stops a permanent-Hidden bug hiding behind it.
## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 12
WithP1Hand: LOF_050
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
ATTACKTARGETS:2:G:0:2

---

# DeployedOnAttack_GrantedHidden_ACTUALLYBlocks_HerOwnHiddenLapsed
#// The On Attack grant, isolated from her OWN Hidden — which is the only way to read it cleanly.
#// ⚠ IF SHE DEPLOYS THIS PHASE, BOTH BLOCKS APPLY AT ONCE and the pool collapses to the base for two
#// different reasons, so the section could not tell them apart. She therefore deploys in round 1 and
#// does nothing; by round 2 her own Hidden has lapsed and she is attackable again. Then she attacks,
#// arming "the next unit you play this phase gains Hidden", and P1 plays Plo Koon.
#// Expected pool for P2: Third Sister + P1's base = 2. Plo Koon is excluded, she is NOT.
#// A pool of 3 means the grant never blocked; a pool of 1 means her lapsed Hidden is still blocking.
## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 12
WithP1Hand: LOF_050
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046
## WHEN
- P1>DeployLeader
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AttackGroundArena:0:BASE
- P2>Pass
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LOF_050
P1GROUNDARENAUNIT:1:HASKEYWORD:Hidden
ATTACKTARGETS:2:G:0:2


---

# GrantedHidden_OnASentinel_TheGrantStillApplies
#// HALF ONE OF THE SENTINEL PAIR. Before claiming Sentinel OVERRIDES the grant, prove the grant actually
#// happened — otherwise the override section below would pass just as well on a unit that never received
#// Hidden at all, which is the classic way a rules-override test measures nothing.
## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: SHD_029
WithP2GroundArena: SOR_046:1:0
## WHEN
#// ⚠ NO `AnswerDecision:myHand-0`. One unit in hand means the offer AUTO-RESOLVES, and a spare answer is
#// not harmless — it gets eaten by the NEXT decision. A first draft included it and reported an EMPTY
#// arena, which reads like the play failing when the answer had simply landed somewhere else.
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_029
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden

---

# GrantedHidden_OnASentinel_IsStillAttackable
#// HALF TWO, and the rules claim. CR 18.b — "If a unit has both Hidden and Sentinel, it can be attacked,
#// as abilities can't prevent units with Sentinel from being attacked." Sentinel WINS: the Pyke Sentinel
#// carries the granted Hidden (proved directly above) and is attacked anyway. SOR_046's 3 power clears
#// its HP, so the arena empties.
#// ⚠ THE ASSERTION IS THE DEFEAT, NOT A TARGET COUNT, and not damage on a survivor. A Sentinel restricts
#// the pool to itself, so "Sentinel only" and "Sentinel wrongly hidden, base only" are BOTH a count of 1
#// and ATTACKTARGETS cannot separate them. A landed attack can: refused → the unit is still there.
## GIVEN
CommonSetup: brk/bbk/{
  myLeader:LOF_010;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 14
WithP1Hand: SHD_029
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P2>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
