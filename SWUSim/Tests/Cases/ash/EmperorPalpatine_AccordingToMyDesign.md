# AdvantagePerOtherFriendly
#// ASH_015 Emperor Palpatine — Leader Action [Exhaust]: choose an exhausted friendly unit; give it an
#// Advantage token for each OTHER friendly unit. SEC_135 (exhausted, the only valid target) gets 2 Advantage
#// (SOR_095 and SOR_046 are the two other friendly units); Palpatine exhausts.
## GIVEN
CommonSetup: gyk/brk/{
  myLeader:ASH_015
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SEC_135:0:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_AdvantagePerOther
#// ASH_015 Emperor Palpatine (deployed) — On Attack: may choose another exhausted friendly unit;
#// if you do, give it an Advantage token for each OTHER friendly unit. Choosing the exhausted Dark
#// Trooper: other friendly units = Palpatine + the space TIE = 2 → 2 Advantage tokens.

## GIVEN
CommonSetup: gyk/brk/{
  myLeader:ASH_015:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
