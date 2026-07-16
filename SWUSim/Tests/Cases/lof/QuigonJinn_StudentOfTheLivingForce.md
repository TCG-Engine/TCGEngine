# Deployed_AttackEnd_ReturnAndPlayFree
#// LOF_016 Qui-Gon Jinn (deployed) — When this unit completes an attack (and survives): you may return
#// a friendly non-leader unit to its owner's hand, then play a non-Villainy unit costing less than the
#// returned unit for free. Qui-Gon attacks the base (survives), returns the SOR_046 wall (cost 4), and
#// plays the X-Wing (SOR_237, cost 2 < 4, Heroism) from hand for free.

## GIVEN
CommonSetup: gyw/brk/{
  myLeader:LOF_016:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_237

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1

---

# ReturnPlayCheaper
#// LOF_016 Qui-Gon Jinn — Action [Exhaust, use the Force]: Return a friendly non-leader unit to hand, then
#// play a non-Villainy unit that costs less from your hand for free. P1 returns SOR_046 (cost 4) and plays
#// SOR_059 (cost 1, Vigilance) for free.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_016;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: SOR_059
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_059
P1HANDCOUNT:1
P1NOFORCE
