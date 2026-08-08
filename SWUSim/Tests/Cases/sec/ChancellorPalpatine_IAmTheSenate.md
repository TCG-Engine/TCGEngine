# NoLeaderUnit_NoSpies
#// SEC_082 Chancellor Palpatine — no leader unit controlled → no Spy tokens created.
#// CommonSetup leaves the leader undeployed, so SWUControlsLeaderUnit is false.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_082

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_082
P1NODECISION

---

# WhenPlayed_LeaderUnit_TwoSentinelSpies
#// SEC_082 Chancellor Palpatine (Ground, 2/2, Command/Villainy) — When Played: if you control a leader
#//   unit, create 2 Spy tokens and give those tokens Sentinel for this phase. (Plot keyword is dormant
#//   when played from hand.) P1 controls a deployed leader unit (Luke @0) → SWUControlsLeaderUnit true.
#// Off-aspect (Vigilance/Heroism leader) so SEC_082 costs 3 + 4 = 7.
#// Board after play: leader unit @0, SEC_082 @1, the two Spy tokens @2/@3.

## GIVEN
CommonSetup: ybw/ybw/{
  myLeader:SOR_005:1:1:1;
  myBase:SOR_028;
  theirBase:SOR_028;
  theirLeader:SOR_005:0
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Hand: SEC_082

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:2:CARDID:SEC_T01
P1GROUNDARENAUNIT:2:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:3:HASKEYWORD:Sentinel
P1NODECISION

---

# WhenPlayed_MoffJerjerrod_DoublesOnce
#// SEC_082 Chancellor Palpatine — "create 2 Spy tokens" is ONE create-a-number-of-tokens instruction, so
#//   ASH_094 Moff Jerjerrod's "you may defeat this unit → create twice that number" replacement is offered
#//   exactly ONCE (not per token). Accepting defeats Moff and creates 4 Spy tokens instead of 2. Final
#//   ground: deployed leader + Palpatine + 4 Spy = 6 (Moff gone).

## GIVEN
CommonSetup: ggk/rrk/{myLeader:SOR_010:1:1:1;myResources:3}
P1OnlyActions: true
WithP1GroundArena: ASH_094:1:0
WithP1Hand: SEC_082

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:6

---

# PilotLeaderThatMakesHostALeaderUnit_CountsAsControllingALeaderUnit
#// SEC_082 Chancellor Palpatine — the gate is "if you control a LEADER UNIT". A leader deployed as a PILOT
#// only satisfies it when the pilot's text makes the HOST a leader unit. JTL_001 Asajj Ventress's pilot
#// side reads "Attached unit IS A LEADER UNIT", so P1's piloted Vehicle counts and the 2 Sentinel Spies
#// are created. (Companion negative below uses a pilot leader WITHOUT that clause.)
## GIVEN
CommonSetup: ybw/ybw/{
  myLeader:JTL_001;
  myLeaderDeployedPilot:true;
  myBase:SOR_028;
  theirBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9
WithP1GroundArena: SOR_183:1:0
WithP1Hand: SEC_082
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:2:CARDID:SEC_T01
P1GROUNDARENAUNIT:2:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:3:HASKEYWORD:Sentinel


---

# OrdinaryPilotUpgrade_DoesNotMakeHostALeaderUnit_NoSpies
#// SEC_082 Chancellor Palpatine — the load-bearing negative for the section above. A NON-leader Pilot
#// upgrade (JTL_211 Independent Smuggler) grants keywords but never makes its host a leader unit, so the
#// "if you control a leader unit" gate fails and no Spy tokens are created.
#// ⚠ The obvious negative — a pilot LEADER whose text lacks "attached unit is a leader unit" — does not
#// exist: every leader with a pilot-deploy option (JTL_001/003/006/008/009/011/012/015…) grants it, and
#// leaders without that clause (e.g. JTL_004 Rose Tico) have no pilot-deploy option at all, so seating one
#// as a pilot would be an impossible board state.
## GIVEN
CommonSetup: ybw/ybw/{
  myBase:SOR_028;
  theirBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9
WithP1GroundArena: SOR_183:1:0
WithP1GroundArenaPilot: 0:JTL_211
WithP1Hand: SEC_082
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:NOTLEADERUNIT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_082

---

# PlayedViaPlot_LeaderJustDeployed_TwoSentinelSpies_AndSelfReplaced
#// SEC_082 Chancellor Palpatine — the Plot route. He sits in P1's resources; deploying P1's leader opens
#// the Plot window and P1 plays him from there. Two things have to hold at once: the leader deployed in
#// that same window satisfies his "if you control a leader unit" condition (so the 2 Spies are created
#// and given Sentinel for the phase), and Plot replaces him in the resource row with the top card of the
#// deck — resources stay at 6 and the deck empties.
#// Board after: leader unit @0, Palpatine @1, the two Spy tokens @2/@3.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_082:1,5:SEC_080:1
WithP1Deck: [SHD_029]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:1:CARDID:SEC_082
P1GROUNDARENAUNIT:2:CARDID:SEC_T01
P1GROUNDARENAUNIT:2:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:3:CARDID:SEC_T01
P1GROUNDARENAUNIT:3:HASKEYWORD:Sentinel
P1RESCOUNT:6
P1DECKCOUNT:0
P1NODECISION
