# DeployedCreditEngine
#// LAW_018 Lando Calrissian (deployed) — "When Deployed: You may defeat a friendly Credit token. If you
#// do, create 3 Credit tokens." Deploy Lando with 1 existing Credit; defeat it and create 3 → net 3
#// Credits.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Credits: 1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES

## EXPECT
P1CREDITCOUNT:3

---

# FrontAspectMillCredit
#// LAW_018 Lando Calrissian (leader front) — "Action [1 resource, Exhaust]: Choose an aspect, then
#// discard a card from a deck. If it has the chosen aspect, create a Credit token." Choose Vigilance;
#// only P1 has a deck so it auto-discards SOR_046 (Vigilance/Heroism) → it has Vigilance → 1 Credit.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_018;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Deck: SOR_046

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Vigilance

## EXPECT
P1CREDITCOUNT:1
P1DECKCOUNT:0
