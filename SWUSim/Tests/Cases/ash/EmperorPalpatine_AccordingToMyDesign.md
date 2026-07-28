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

---

# OnlyTargetUnit_ZeroAdvantage
#// ASH_015 Emperor Palpatine — the count is per OTHER friendly unit. With SEC_135 the only friendly unit,
#// there are zero others, so it receives 0 Advantage tokens (Palpatine still exhausts).
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_015}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SEC_135:0:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1LEADER:EXHAUSTED

---

# Leader_OneOtherFriendly
#// ASH_015 Emperor Palpatine (leader action) — the count is per OTHER friendly unit. With the exhausted
#// SEC_135 as the only legal target and a single other friendly unit (SOR_095), it receives 1 Advantage.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_015}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SEC_135:0:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1LEADER:EXHAUSTED

---

# Leader_NoExhaustedTarget_ExhaustsAnyway
#// ASH_015 Emperor Palpatine (leader action) — with NO exhausted friendly units there is no legal target,
#// but the action can still be used: Palpatine exhausts and no Advantage tokens are given.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_015}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:0

---

# Leader_AlreadyExhausted_CannotUse
#// ASH_015 Emperor Palpatine (leader action) — an exhausted Palpatine cannot use the [Exhaust] action; the
#// exhausted SEC_135 receives no Advantage.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_015:0}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SEC_135:0:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_FourAdvantage
#// ASH_015 Emperor Palpatine (deployed) — On Attack counts EVERY other friendly unit, including Palpatine
#// himself and space units. With the exhausted SEC_135 chosen as target, the other friendlies (SOR_095,
#// SEC_080, the space SOR_225, and Palpatine as a unit) = 4, so SEC_135 gets 4 Advantage tokens.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_015:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_135:0:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:0:0
## WHEN
- P1>AttackGroundArena:3:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:4

---

# Deployed_OnAttack_NoExhausted_NoTrigger
#// ASH_015 Emperor Palpatine (deployed) — the On Attack targets an EXHAUSTED friendly unit; with none
#// available (SOR_095 ready, Palpatine ready) the ability does not trigger and no Advantage is given.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_015:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# Deployed_OnAttack_OnlyLeaderIsOther
#// ASH_015 Emperor Palpatine (deployed) — with the exhausted SEC_135 the only other friendly unit besides
#// Palpatine, the count of OTHER friendly units is just 1 (Palpatine himself), so SEC_135 gets 1 Advantage.
## GIVEN
CommonSetup: gyk/brk/{myLeader:ASH_015:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_135:0:0
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
