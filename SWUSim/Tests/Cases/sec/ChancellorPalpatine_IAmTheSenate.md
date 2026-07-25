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
