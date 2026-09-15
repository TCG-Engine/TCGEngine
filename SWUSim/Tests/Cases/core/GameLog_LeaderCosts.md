# LeaderReactionCost_Wicket_ExhaustIsLogged
#// USER DECISION 2026-09-11 (gamelog-updates leftovers): a leader's "you may exhaust this leader" reaction
#// cost is logged ("P1 exhausted [[Wicket]]"), then its effect. (An Action's own [Exhaust] cost stays covered
#// by "P1 used X's Action".) HMW_014 Wicket (front): "When a friendly unit attacks a unit that costs more
#// than it: You may exhaust this leader. If you do, draw a card." (Fixture from
#// hmw/Wicket_FewGreaterBattlesToFight.md.)

## GIVEN
CommonSetup: rrw/bgw/{
  myLeader:HMW_014;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:EXHAUSTED
LOGCONTAINS:P1 exhausted [[HMW_014|Wicket]]
LOGCOUNT:1:exhausted [[HMW_014

---

# LeaderReactionCost_ViaTheSharedHelper
#// The shared _SWUExhaustUndeployedLeader (JTL_002, LAW_014, LAW_007, JTL_009, TWI_016, TWI_018, HMW_011)
#// logs too. TWI_018 Quinlan Vos (front). (Fixture from twi/QuinlanVos_StickingTheLanding.md.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:4;myLeader:TWI_018:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
LOGCONTAINS:P1 exhausted [[TWI_018|Quinlan Vos]]
