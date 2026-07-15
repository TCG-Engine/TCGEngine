# TS26_006 Rex (leader front) — Action [Exhaust, ready an exhausted enemy unit]: the next event you play
# this phase costs 1 less. Rex readies the exhausted enemy SEC_080, then Urgent Mission (cost 2) plays for
# 1 (only affordable via the discount: 1 resource → 0), dealing 2 to P1's own base.
## GIVEN
CommonSetup: rrw/rrk/{myLeader:TS26_006;myResources:1;handCardIds:TS26_064}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SEC_080:0:0
WithP1Deck: [SEC_080 SOR_095]
## WHEN
- P1>UseLeaderAbility
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:READY
P1BASEDMG:2
P1LEADER:EXHAUSTED
