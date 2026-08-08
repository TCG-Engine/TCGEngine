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
