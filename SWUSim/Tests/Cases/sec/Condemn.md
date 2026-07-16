# DoubleAttach_NormalPower_NoDisclose
#// SEC_038 Condemn — the multi-copy interaction. P1's SOR_141 (1/3, Raid 2) bears TWO Condemns and
#//   attacks P2's base. Each Condemn grants its own On Attack AND "loses all OTHER abilities" — so each
#//   Condemn's granted On Attack is itself suppressed by the other Condemn. Result: NO disclose is offered
#//   (P2NODECISION), and the unit's Raid 2 is also suppressed, so it attacks for its normal power 1.

## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 0:SEC_038
WithP1SpaceArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:1
P2NODECISION
P1SPACEARENAUNIT:0:UPGRADECOUNT:2

---

# NotAttacking_KeepsSentinel
#// SEC_038 Condemn — the suppression is attack-scoped ("WHILE attached unit is attacking"). A Condemn-
#//   bearing Sentinel unit that is NOT attacking keeps all its abilities: SOR_063 (2/4 Sentinel) with a
#//   Condemn still HAS Sentinel while idle. Guard against the lose-abilities applying continuously.

## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# SentinelUnit_LosesSentinelMidAttack
#// SEC_038 Condemn — Sentinel loss is immediate at attack declaration; the -6/-0 is NOT (it only lands
#//   if/after the defender discloses). P1's SOR_063 (2/4 Sentinel) bears 1 Condemn and attacks P2's base.
#//   The granted On Attack queues P2's disclose, which pauses combat. Mid-attack (disclose still pending):
#//     - the attacker has LOST Sentinel (lose-all-other-abilities is active from declaration), and
#//     - its power is STILL 2 (the -6/-0 only applies once the disclose resolves, not yet).
#//   P2 still has the pending disclose decision.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:POWER:2
P2HASDECISION

---

# SingleAttach_DefenderDeclines_NoDebuff
#// SEC_038 Condemn — the granted disclose is "may". P2 (defending player) declines (AnswerDecision:-),
#//   so no -6/-0 is applied: SEC_118 deals its full 6 to the base. Proves the decline path no-ops.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: SEC_118:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:-

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:6

---

# SingleAttach_DefenderDiscloses_Debuff
#// SEC_038 Condemn (Upgrade, Vigilance/Villainy, no attach restriction) — "While attached unit is
#//   attacking, it gains: 'On Attack: the defending player may disclose VigilanceVillainy → this unit
#//   gets -6/-0 for this attack' and loses all other abilities."
#// P1's SEC_118 (6/5, vanilla) bears 1 Condemn and attacks P2's base. The granted On Attack lets the
#// DEFENDING player (P2) disclose; P2 discloses SEC_038 (Vigilance,Villainy → covers VigilanceVillainy),
#// so the attacker gets -6/-0 → power max(0, 6-6) = 0 → deals 0 to the base. After the attack the
#// attack-duration debuff expires, so the attacker's power is back to 6.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: SEC_118:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# SingleAttach_SuppressesOwnRaid
#// SEC_038 Condemn — "loses all other abilities" while attacking. P1's SOR_141 (1/3, innate Raid 2)
#//   bears 1 Condemn and attacks P2's base from space. P2 declines the granted disclose (so no -6/-0),
#//   but the unit's OWN Raid 2 is suppressed by Condemn, so it deals just its base power 1 (not 1+2=3).
#//   Proves the lose-all-other-abilities suppresses the host's own keywords.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:-

## EXPECT
P2BASEDMG:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
