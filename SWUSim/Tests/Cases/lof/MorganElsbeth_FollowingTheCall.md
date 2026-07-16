# DeployedOnAttack
#// LOF_005 Morgan Elsbeth (deployed) — On Attack: the next unit you play this phase costs 1 less if it shares
#// a keyword with a friendly unit. She attacks the base (arming the discount); P1 then plays LOF_132 (Raid),
#// which shares Raid with the friendly LOF_131 — so it costs 3+2−1 = 4 instead of 5.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:LOF_005;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6
WithP1SpaceArena: LOF_131:1:0
WithP1Hand: LOF_132

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_132
P1RESAVAILABLE:2

---

# SharedKeywordPlay
#// LOF_005 Morgan Elsbeth — Action [Exhaust]: Choose a friendly unit that attacked this phase; play a unit
#// from your hand that shares a keyword with it, for 1 less. LOF_132 (Raid) attacks the base; then Morgan
#// plays LOF_131 (also Raid; cost 2 + 2 off-aspect − 1 discount = 3) from hand — affordable only with the
#// discount.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:LOF_005;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_132:1:0
WithP1Hand: LOF_131
WithP1Resources: 3

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1RESAVAILABLE:0
