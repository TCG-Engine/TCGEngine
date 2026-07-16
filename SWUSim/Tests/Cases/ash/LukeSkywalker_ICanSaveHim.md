# AttackEnd_HealAttacker
#// ASH_005 Luke Skywalker — "When a friendly unit's attack ends: you may exhaust this leader; if you do,
#// heal 1 damage from that unit." SOR_046 attacks SEC_080 and takes 3 counter damage; P1 exhausts Luke to
#// heal 1, leaving SOR_046 at 2 damage.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EXHAUSTED

---

# Decline_NoHeal
#// ASH_005 Luke Skywalker — declining the optional exhaust leaves Luke ready and heals nothing. SOR_046
#// attacks SEC_080, takes 3 counter damage, and P1 declines, so SOR_046 stays at 3 damage and Luke is ready.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:READY

---

# Deployed_AttackEnd_ChooseBaseOverUnit
#// ASH_005 Luke Skywalker (DEPLOYED unit side) — the "that unit OR your base" choice. Friendly X-Wing
#// (2/3) attacks a TIE (2/1) and takes 2 counter damage (→ 2 dmg); P1's base is also pre-damaged (4). Both
#// the attacker and the base are damaged, so Luke's heal-2 presents a real MZCHOOSE; P1 picks the base
#// (4 → 2), leaving the X-Wing untouched at 2 damage.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
  myBaseDamage:4;
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:2
P1SPACEARENAUNIT:0:DAMAGE:2

---

# Deployed_AttackEnd_HealAnotherUnit
#// ASH_005 Luke Skywalker (DEPLOYED unit side) — field observer: fires for ANOTHER friendly unit's attack,
#// not just Luke's own. Friendly X-Wing (SOR_237, 2/3) attacks a TIE (SOR_225, 2/1): X-Wing kills the TIE
#// and takes 2 counter damage. Luke's deployed ability then heals 2 from that unit (base undamaged → the
#// X-Wing is the only valid target → auto-resolves), leaving the X-Wing at 0 damage.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENACOUNT:0
P1BASEDMG:0

---

# Deployed_AttackEnd_HealOwnBase
#// ASH_005 Luke Skywalker (DEPLOYED unit side, 6/7) — "When a friendly unit's attack ends: Heal 2 damage
#// from that unit or from your base." Repro of game 2088: deployed Luke attacks the enemy base, takes no
#// counter (0 damage on him), so the only damaged heal target is P1's own base (7 → 5). Single target
#// auto-resolves — no decision.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
  myBaseDamage:7;
}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:5
P2BASEDMG:6
P1GROUNDARENAUNIT:0:CARDID:ASH_005
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Deployed_AttackEnd_NothingDamaged_NoOp
#// ASH_005 Luke Skywalker (DEPLOYED unit side) — clean fizzle. Deployed Luke attacks the enemy base (no
#// counter → 0 damage on him) with an undamaged P1 base, so neither valid heal source has any damage. The
#// mandatory heal has no beneficial target → no decision is queued (no crash, no dangling prompt).
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1NODECISION
P1BASEDMG:0
P2BASEDMG:6

---

# NoActivatedAbility_NoOp
#// ASH_005 Luke Skywalker (LEADER) — front side is purely REACTIVE ("When a friendly unit's
#// attack ends: you may exhaust this leader..."), so it has NO activated leader action.
#// Clicking the leader (UseLeaderAbility) must be a no-op: the leader stays ready, nothing queued.
#// Regression: SWULeaderActionAffordable used to return true for any zero-cost leader with no
#// $leaderAbilities entry, so ASH_005 glowed and clicking it exhausted (tapped) the leader for free.

## GIVEN
CommonSetup: grw/grw/{
  myLeader:ASH_005;
  myBase:SOR_022;
  theirLeader:ASH_005;
  theirBase:SOR_022
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1NODECISION
