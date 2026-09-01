# AttackDefeatsHighHpUnit
#// SOR_085 Rukh (3/6) — "When this unit deals combat damage to a non-leader unit while attacking:
#// Defeat that unit." Rukh attacks a 3/7 that would SURVIVE the 3 combat damage, but Rukh's ability
#// defeats it anyway. Rukh takes 3 counter-damage and survives.
#// COVERAGE: offer=AttackOffer_TheNonLeaderGateSitsOnTheDEFEAT_NotOnWhatRukhMayATTACK (the exact legal
#//           SET of a real pending decision: with the pick routed through SOR_012 IG-88's "attack with a
#//           unit", Rukh's offered targets must be all four bodies — the enemy DEPLOYED LEADER unit
#//           included — because "non-leader" restricts the defeat clause, never target legality)
#//           + TwoEnemyUnits_OnlyTheDefenderIsDefeated (the other half: the defeat itself has no pool —
#//           "that unit" is fixed by the combat; proven with a SECOND legal enemy body on the board and
#//           P1NODECISION, so the auto-resolution is the assertion) — and the entry-trigger ORDER
#//           picker is driven in both ECL_* sections ·
#//           decline=N/A (neither clause is optional — Shielded is a mandatory token grant and the
#//           defeat clause has no "you may") ·
#//           boundary=BoundaryHp3_DefenderDiesToTheDamageItself +
#//           BoundaryHp4_DefenderSurvivesTheDamageAndIsDefeatedAnyway (power 3 vs HP 3 / HP 4) ·
#//           control=UnderEnemyControl_TheControllerResolvesTheDefeat ·
#//           reqboundary=ECL_AmbushAttack_SurvivesRequestBoundary

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_085:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_085
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# ECL_AmbushAttack_Defeats
#// SOR_085 Rukh via SOR_022 Energy Conversion Lab (Epic Action: play a ≤6-cost unit with Ambush).
#// P1 plays Rukh from hand with Ambush; Rukh ambush-attacks the enemy 3/7 and his "deals combat
#// damage to a non-leader unit → defeat it" finishes it off. (Rukh enters with two entry triggers —
#// Shielded + Ambush — so the trigger-order MZCHOOSE is answered first; the shield absorbs the 3
#// counter-damage, so Rukh ends undamaged.)

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  myBase:SOR_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_085
WithP1Resources: 5
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_085
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Shielded_OnPlay_GivesShieldToken
#// SOR_085 Rukh (Command/Villainy, cost 5, 3/6, Imperial) — clause 1: "Shielded (When you play this
#// unit, give a Shield token to it.)" Played on-aspect from hand for exactly 5; he lands with one
#// Shield token on him and nothing else on the board gains one.

## GIVEN
CommonSetup: ggk/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_085
WithP1Resources: 5
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_085
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1RESAVAILABLE:0

---

# AttacksLeaderUnit_NotDefeated
#// SOR_085 Rukh — the NEGATIVE that proves the "non-leader unit" gate is load-bearing. Rukh attacks an
#// enemy DEPLOYED LEADER unit (SOR_014 Sabine Wren, 2/5). The combat damage lands (3 on Sabine) but the
#// defeat trigger must NOT fire: a leader unit is not a "non-leader unit". Sabine survives at 3 damage
#// and deals her 2 back to Rukh.

## GIVEN
CommonSetup: ggk/brw/{
  theirLeaderDeployed:true;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_085:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:CARDID:SOR_085
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# DefendingCombatDamage_NoDefeat
#// SOR_085 Rukh — the NEGATIVE that proves the "while attacking" gate. Here Rukh is the DEFENDER:
#// SOR_046 (3/7) attacks him, Rukh deals his 3 combat damage back to a non-leader unit, but he was not
#// attacking, so nothing is defeated. Both units survive at 3 damage.

## GIVEN
CommonSetup: ggk/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_085:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# ShieldedDefender_NoCombatDamageDealt_NoDefeat
#// SOR_085 Rukh — the trigger's condition is "deals combat damage", not "attacks". The defender
#// (SOR_095, 3/3) carries a Shield token, which is defeated instead of the damage being dealt, so NO
#// combat damage reaches it — the defeat trigger must not fire and the 3/3 walks away undamaged with
#// its shield spent. Rukh still takes the 3 counter-damage.

## GIVEN
CommonSetup: ggk/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_085:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttacksBase_NoUnitToDefeat
#// SOR_085 Rukh — the trigger names a UNIT, so an attack on the enemy BASE deals its 3 and raises no
#// trigger at all: no decision, no crash, Rukh takes no counter-damage.

## GIVEN
CommonSetup: ggk/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_085:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# TwoEnemyUnits_OnlyTheDefenderIsDefeated
#// SOR_085 Rukh — OFFER axis. "Defeat THAT unit" is not a choose-a-unit effect: with TWO legal enemy
#// bodies on the board (a second 3/7 that Rukh never touched), the trigger must resolve on the defender
#// alone with NO pool and NO prompt. The bystander at index 0 after cleanup is undamaged, and
#// P1NODECISION proves no target picker was ever raised — the auto-resolution IS the offer assertion,
#// because the effect's pool is fixed to one unit by construction.

## GIVEN
CommonSetup: ggk/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_085:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_046:1:0]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# BoundaryHp3_DefenderDiesToTheDamageItself
#// SOR_085 Rukh — boundary pair, low side. Rukh's power is 3, so a 3-HP defender (SOR_095, 3/3) is
#// already destroyed by the combat damage before the trigger resolves. The trigger must no-op on a
#// unit that is already gone rather than double-defeating or erroring.

## GIVEN
CommonSetup: ggk/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_085:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# BoundaryHp4_DefenderSurvivesTheDamageAndIsDefeatedAnyway
#// SOR_085 Rukh — boundary pair, high side. One more HP than Rukh's power: SOR_063 (2/4) takes 3 and
#// would survive at 3-of-4, but the ability defeats it regardless. Rukh takes only its 2 back. Together
#// with the 3-HP section this proves the defeat is the ABILITY's, not an artifact of lethal damage.

## GIVEN
CommonSetup: ggk/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_085:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# UnderEnemyControl_TheControllerResolvesTheDefeat
#// SOR_085 Rukh — CONTROL CHANGE. Rukh sits in P1's arena but is OWNED by P2 (the end state after a
#// take-control effect). His trigger belongs to whoever CONTROLS him: P1 attacks with him and the
#// defeat resolves against P2's own 3/7. The defeated card goes to its OWNER's discard (P2's), and
#// Rukh himself stays on P1's board.

## GIVEN
CommonSetup: ggk/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_085:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_085
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# ECL_AmbushAttack_SurvivesRequestBoundary
#// SOR_085 Rukh — REQUEST BOUNDARY. Same flow as ECL_AmbushAttack_Defeats (SOR_022 Energy Conversion
#// Lab grants Ambush; the entry-trigger order is chosen, then the Ambush attack is taken), but every
#// interactive decision is separated by a serialization round-trip. The Shielded token, the granted
#// Ambush and the pending attack must all survive the boundary: the shield still soaks the 3
#// counter-damage and the defeat trigger still finishes off the 3/7.

## GIVEN
CommonSetup: grk/brw/{
  myLeader:SOR_011;
  myBase:SOR_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_085
WithP1Resources: 5
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseBaseAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:EffectStack-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_085
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# AttackOffer_TheNonLeaderGateSitsOnTheDEFEAT_NotOnWhatRukhMayATTACK
#// SOR_085 Rukh — OFFER axis, the pool that DOES exist on this card. Rukh's own defeat clause has no
#// menu ("that unit" is fixed by the combat, pinned in TwoEnemyUnits_OnlyTheDefenderIsDefeated), so the
#// offer worth asserting is the one the word "non-leader" could wrongly narrow: WHAT RUKH MAY ATTACK.
#// The restriction belongs to the defeat, not to target legality, and an implementation that hoisted it
#// into the attack filter would quietly stop Rukh from attacking enemy leader units at all — invisible
#// to every section that only checks what survives. Here P1's leader is SOR_012 IG-88, whose "[Exhaust]:
#// Attack with a unit" routes the target pick through the decision queue, so the pool is a readable
#// pending decision rather than a harness-supplied index. It is left PENDING and asserted exactly: all
#// FOUR bodies — the enemy DEPLOYED LEADER unit at index 2, both non-leader units, and theirBase-0 —
#// must be offered. Rukh is P1's only unit, so the "attack with a unit" step auto-resolves onto him and
#// the pending decision is the target pick.

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_012;
  myBase:SOR_025;
  theirBase:SOR_021;
  theirLeaderDeployed:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_085:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2&theirBase-0
P2GROUNDARENAUNIT:2:ISLEADERUNIT
P2GROUNDARENAUNIT:0:NOTLEADERUNIT
P2GROUNDARENAUNIT:1:NOTLEADERUNIT
P1GROUNDARENAUNIT:0:CARDID:SOR_085
