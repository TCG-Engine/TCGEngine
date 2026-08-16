# FrontAttackBuffSelfDefeat
#// LAW_001 Saw Gerrera (leader front) — "Action [Exhaust]: Attack with a unit. It gets +2/+0 and gains
#// Overwhelm for this attack. After completing this attack, defeat it." SEC_080 (3/3) is the only ready
#// unit and P2 has no units, so it auto-attacks the base for 3+2 = 5, then is defeated.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:0

---

# FrontAttackEnemyOverwhelmDefeat
#// LAW_001 Saw Gerrera (leader front) — Action: attack with a unit granting +2/+0 and Overwhelm, then
#// defeat it. SEC_080 (3/3, auto-selected as the lone ready unit) attacks the enemy SHD_110 (2/2): it
#// hits for 5, defeats SHD_110, and Overwhelm carries the 3 excess to the base. Afterward SEC_080 is
#// defeated by Saw's ability; the leader is exhausted.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P2BASEDMG:3
P1LEADER:EXHAUSTED

---

# FrontUseWithNoLegalAttacker
#// LAW_001 Saw Gerrera (leader front) — with only an exhausted unit (which cannot attack) there is no legal
#// attacker, but the ability may still be used to no effect: the leader exhausts, the exhausted unit stays
#// in play, and the enemy is untouched.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# DeployedTriggerSecondAttack
#// LAW_001 Saw Gerrera (deployed) — When Saw's attack ends (and he survives), another friendly unit may
#// attack, gaining +2/+0 and Overwhelm and then being defeated. Saw (4/7) attacks the base for 4, then
#// SEC_080 attacks SHD_110 (2/2): 5 power defeats it and Overwhelm carries 3 to the base (total 7). SEC_080
#// is then defeated, leaving only Saw in the arena.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001:1:1:1;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:7
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# DeployedTriggerPassed
#// LAW_001 Saw Gerrera (deployed) — the follow-up attack is optional. Saw attacks the base for 4 and the
#// player declines the trigger: no second unit attacks, so SEC_080 and the enemy SHD_110 are untouched.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001:1:1:1;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:2

---

# DeployedNoTriggerIfSawDies
#// LAW_001 Saw Gerrera (deployed) — the follow-up requires Saw to survive his attack. Saw (4/7) attacks the
#// SHD_172 Krayt Dragon (10/10): Saw deals 4 but takes 10 and is defeated, so the "attack with another unit"
#// trigger never fires — SEC_080 is untouched and no decision is offered.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001:1:1:1;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_172:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENACOUNT:1
P1NODECISION

---

# FrontOffer_ReadyUnitsOnly
#// COVERAGE: offer=FrontOffer_ReadyUnitsOnly (pending P1SELECTABLEEXACT: only ready units are offered;
#//           the exhausted unit is excluded) · decline=DeployedTriggerPassed (deployed side; the front's
#//           attack pick is mandatory, no decline exists — FrontUseWithNoLegalAttacker covers the
#//           no-target "use anyway" path) · boundary=FrontAttackerDiesInCombat_DefeatAfterAttack +
#//           FrontAttackEnemyOverwhelmDefeat (dies-in-combat vs survives-then-defeated pair) ·
#//           control=N/A (the ability targets friendly units by controller at resolve time; no
#//           control-change interaction in the text) · reqboundary=each section crosses the
#//           leader-action/attack-target decision boundary with serialized state (multi-request WHEN)
#// LAW_001 Saw Gerrera (leader front) — "Attack with a unit" offers exactly the friendly units able to
#// attack: SOR_095 and SHD_110 are ready (offered), SEC_080 is exhausted (excluded). The decision is left
#// pending so the offer itself is the assertion.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SEC_080:0:0 SHD_110:1:0]
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2

---

# FrontAttackerDiesInCombat_DefeatAfterAttack
#// LAW_001 Saw Gerrera (leader front) — the "defeat it" rider must not break when the unit already died
#// during the attack. SOR_095 Battlefield Marine (3/3, +2 = 5 power) attacks SOR_128 Death Star
#// Stormtrooper (3/1): the trooper is defeated and Overwhelm carries 4 excess to the base, while the
#// trooper's 3 damage kills the marine in combat. Saw's after-attack defeat then finds the marine already
#// in the discard and resolves cleanly — the leader still ends exhausted and no decision is stranded.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# FrontMercenaryDiesInCombat_WhenDefeatedResources
#// LAW_001 Saw Gerrera (leader front) + LAW_159 Expendable Mercenary (3/3) — the attacked unit's own
#// When Defeated still works when it dies during Saw's granted attack. The mercenary (+2 = 5 power)
#// attacks SOR_128 (3/1): trooper dies, Overwhelm sends 4 to the base, and the trooper's 3 damage kills
#// the mercenary. Its When Defeated (auto-resolving ramp family) resources it from the discard, so P1
#// ends with 3 resources and an empty discard; Saw's after-attack defeat is a clean no-op.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001;
  myBase:SOR_025;
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_159:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
P1RESCOUNT:3
P1DISCARDCOUNT:0
P1LEADER:EXHAUSTED

---

# DeployedMercenaryDiesInCombat_WhenDefeatedResources
#// LAW_001 Saw Gerrera (deployed) + LAW_159 Expendable Mercenary — same interaction through the deployed
#// trigger. Saw (4/7) attacks the base for 4, then the follow-up attack sends the mercenary (+2 = 5)
#// into SOR_128 (3/1): trooper dies, Overwhelm adds 4 more to the base (total 8), and the trooper's 3
#// damage kills the mercenary, whose When Defeated resources it (2 -> 3 resources). Only Saw remains on
#// the ground.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001:1:1:1;
  myBase:SOR_025;
  myResources:2
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_159:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:8
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1RESCOUNT:3
P1DISCARDCOUNT:0

---

# DeployedTriggerSecondAttack_SurvivesTheRequestBoundary
#// LAW_001 Saw Gerrera — request-boundary guard. Identical to DeployedTriggerSecondAttack except the game
#// round-trips through serialization (SimulateRequestBoundary) AFTER the follow-up attacker has been
#// chosen (SEC_080) and while its attack-target pick is still pending. This is the valuable insertion
#// point: by then the trigger has already recorded three things — which unit is making the granted
#// attack, the +2/+0-and-Overwhelm-for-this-attack grant on it, and the "defeat it afterwards" rider —
#// all of which are read only after this answer, which in a real game arrives in a fresh process. The
#// result must be unchanged: 5 power defeats SHD_110, Overwhelm carries 3 to the base (4 + 3 = 7),
#// SEC_080 is then defeated and only Saw remains.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001:1:1:1;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_110:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:7
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
