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

---

# FrontSide_NoExhaustIfAttackerDies
#// ASH_005 Luke Skywalker (LEADER front) — "you may exhaust Luke; if you do, heal 1 from that unit." If the
#// attacking unit dies during the attack there is no unit left to heal, so the ability offers nothing and
#// Luke is not exhausted. SOR_095 (3/3) attacks SEC_080 (3/3): both deal 3 and both die. Luke stays ready.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:READY
P1NODECISION

---

# Deployed_HealSpaceAttacker
#// ASH_005 Luke Skywalker (DEPLOYED) — the heal works for a SPACE attacker. Deployed Luke (ground) with a
#// pre-damaged SOR_237 X-Wing (2 damage) in space and a damaged base (5). The X-Wing attacks the enemy base;
#// P1 heals 2 from the X-Wing (2 → 0), leaving the base at 5.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
  myBaseDamage:5;
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:2
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P1BASEDMG:5

---

# Deployed_HealBase_MultipleAttacks
#// ASH_005 Luke Skywalker (DEPLOYED) — the attack-end heal fires on EVERY friendly attack this phase.
#// Deployed Luke + AT-ST (SOR_232) + Marine (SOR_095), base damaged 5. Three different units attack the
#// enemy base in turn; each time P1 heals 2 from its own base: 5 → 3 → 1 → 0.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
  myBaseDamage:5;
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myBase-0
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myBase-0
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:0

---

# FrontSide_OpponentAttack_NoTrigger
#// ASH_005 Luke Skywalker (LEADER front) — the reactive heal fires only when a FRIENDLY unit's attack ends.
#// An enemy unit attacking does not trigger it. P2's SEC_080 attacks P1's base; Luke offers no heal and stays
#// ready with no decision queued.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P1LEADER:READY
P1NODECISION

---

# Deployed_AttackerDies_StillHealsYOURBASE
#// ASH_005 (DEPLOYED) — "When a friendly unit's attack ends: Heal 2 damage from that unit OR from your
#// base." The attacker dying removes ONE of the two sources; the base is still there, so the ability must
#// still resolve. Per CR 16.c a When-Attack-Ends ability fires on a dead attacker by DEFAULT — survival is
#// a per-card opt-in, and ASH_005 is deliberately NOT in the must-survive roster.
#// SOR_095 (3/3) attacks SEC_080 (3/3): both deal 3, both die. P1's base is seeded at 5 damage and must be
#// healed to 3. The heal auto-resolves onto the base because it is the only surviving source.
#// ⚠ Contrast FrontSide_NoExhaustIfAttackerDies above: the FRONT side heals only "that unit", so with the
#// attacker gone it has no legal target and correctly offers nothing. The two sides differ because their
#// target lists differ, not because one fires on death and the other does not.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
  myBaseDamage:5
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_063 SOR_063]
WithP2Deck: [SOR_063 SOR_063]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:3
P1NODECISION

---

# Deployed_AttackerDies_AndBaseUndamaged_FizzlesCleanly
#// ASH_005 (DEPLOYED) — the boundary partner. With the attacker dead AND the base undamaged there is no
#// legal heal source at all, so the ability fizzles with no prompt rather than raising an empty choice.
#// Together with the section above this pins that the trigger DOES fire on a dead attacker (it just finds
#// nothing here), rather than being skipped wholesale.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_063 SOR_063]
WithP2Deck: [SOR_063 SOR_063]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:0
P1NODECISION
