# 049_CombatDefeatReplaced_AttachToVehicle
#// JTL_049 L3-37 — the "if would be defeated" replacement also covers COMBAT defeats. L3-37 (3/3) attacks
#// SOR_046 (3/7): she takes 3 lethal combat damage, but instead of being defeated her controller (P1)
#// attaches her as a pilot upgrade onto the friendly Vehicle SEC_214. SOR_046 survives (7 HP).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_049:1:0
WithP1GroundArena: SEC_214:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_049
P1DISCARDCOUNT:0

---

# 049_DefeatDeclined_GoesToDiscard
#// JTL_049 L3-37 — the replacement is a "may". P2 Takedowns L3-37; her controller (P1) DECLINES the
#// replacement, so she is defeated normally and goes to P1's discard. SEC_214 gains nothing.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 6
WithP2Hand: SOR_077
WithP1GroundArena: JTL_049:1:0
WithP1GroundArena: SEC_214:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1

---

# 049_DefeatReplaced_AttachToVehicle
#// JTL_049 L3-37 — If this unit would be defeated, you may instead attach her as an upgrade to a friendly
#// Vehicle without a Pilot. P2 plays SOR_077 Takedown to defeat L3-37 (3/3, ≤5 HP). Instead of being
#// defeated, L3-37's controller (P1) chooses to attach her as a pilot upgrade on the friendly Vehicle
#// SEC_214 — so she leaves the unit slot, is NOT discarded, and SEC_214 gains her as an upgrade.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 6
WithP2Hand: SOR_077
WithP1GroundArena: JTL_049:1:0
WithP1GroundArena: SEC_214:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_049
P1DISCARDCOUNT:0

---

# 049_NoFriendlyVehicle_DefeatedNormally
#// JTL_049 L3-37 — the replacement needs a friendly Vehicle WITHOUT a Pilot to attach to. With no such
#// Vehicle in play, there is no legal replacement: P2 Takedowns L3-37 and she is defeated normally, going
#// to P1's discard.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 6
WithP2Hand: SOR_077
WithP1GroundArena: JTL_049:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_049

---

# 049_AttachClearsUpgradesAndDamage
#// JTL_049 L3-37 — on the replacement attach, "defeat all upgrades on her and remove all damage from her."
#// L3-37 has 2 damage and a normal upgrade (SOR_120). P2 Takedowns her; P1 replaces the defeat by attaching
#// her onto SEC_214, so SOR_120 is defeated to P1's discard and she attaches clean (as a pilot upgrade).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 6
WithP2Hand: SOR_077
WithP1GroundArena: JTL_049:1:2
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: SEC_214:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_049
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_120
