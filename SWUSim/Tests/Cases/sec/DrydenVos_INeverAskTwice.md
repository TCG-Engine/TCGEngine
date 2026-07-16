# Deployed_Action_DiscardPlayAmbush
#// SEC_007 Dryden Vos (deployed) — Action [discard a card from your hand]: play a unit from your hand
#// (paying its cost). It gains Ambush this phase. Dryden discards SOR_095, plays SOR_128 (3/1) which
#// gains Ambush.

## GIVEN
CommonSetup: bgk/brk/{
  myLeader:SEC_007:1:1:1;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_128
WithP1Resources: 6

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_128
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# LeaderAction_DiscardPlayAmbush
#// SEC_007 Dryden Vos (leader) — Action [Exhaust, discard a card that costs 6 or more]: Play a unit that
#// costs 5 or less from your hand (paying its cost). It gains Ambush for this phase.
#// P1 discards SOR_049 (cost 6), then plays SEC_080 (cost 2, Command/Villainy — no penalty under the C/V
#// leader) for 2, and SEC_080 gains Ambush this phase. Enemy board empty → Ambush has no target (no attack),
#// the unit just enters.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:SEC_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_049
WithP1Hand: SEC_080

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED
