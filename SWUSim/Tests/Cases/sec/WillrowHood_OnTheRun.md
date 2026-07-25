# FriendlyDefeatNotBlocked
#// SEC_061 Willrow Hood — only ENEMY card abilities are blocked. The controller's OWN abilities can still
#//   defeat the upgrade. P2 controls Willrow + 1 upgrade and plays its OWN Confiscate on it → the upgrade
#//   is defeated normally (actor == controller, so the protection does not apply).

## GIVEN
CommonSetup: grw/grw/{theirResources:1;theirHandCardIds:SOR_251}
WithActivePlayer: 2
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:2

---

# OneUpgrade_EnemyCantDefeat
#// SEC_061 Willrow Hood — "While this unit has exactly 1 friendly upgrade on it, that upgrade can't be
#//   defeated or returned to hand by enemy card abilities." P2's Willrow bears exactly 1 friendly upgrade
#//   (SOR_120). P1 plays Confiscate ("Defeat an upgrade") targeting it — but the enemy defeat is blocked,
#//   so the upgrade survives (Confiscate is spent for nothing).

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:0

---

# OneUpgrade_EnemyCantReturn
#// SEC_061 Willrow Hood — the lone friendly upgrade also can't be RETURNED to hand by an enemy ability.
#//   P2's Willrow bears 1 friendly upgrade (SOR_120, cost 2 ≤3). P1 plays SEC_200 Junior Senator ("may
#//   return an upgrade that costs 3 or less to its owner's hand") and picks Willrow as the host — but the
#//   enemy return is blocked, so SOR_120 stays attached.

## GIVEN
CommonSetup: yyw/grw/{myResources:2;handCardIds:SEC_200}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120

---

# TwoUpgrades_EnemyCanDefeat
#// SEC_061 Willrow Hood — the protection is ONLY while he has EXACTLY 1 friendly upgrade. With 2 friendly
#//   upgrades (SOR_120 + SOR_069) the protection is off, so P1's Confiscate defeats the chosen one. Proves
#//   the "exactly 1" boundary.

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1

---

# WillrowDefeated_UpgradeStillDefeated
#// SEC_061 Willrow Hood — the protection is only against enemy ABILITIES defeating the upgrade; it does not
#// keep the upgrade alive when Willrow himself dies. A near-dead Willrow (1 HP) wearing SOR_120 attacks
#// SOR_046 and dies to the counter, so both Willrow and his upgrade go to the discard (count 2).
## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_061:1:4
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# ProtectionScopedToWillrowsOwnUpgrade
#// SEC_061 Willrow Hood — the protection covers only the upgrade on Willrow, not upgrades on other units.
#// P2 has Willrow (wearing SOR_120, protected) AND SOR_046 (wearing SOR_120). P1's Confiscate targeting
#// SOR_046 defeats its upgrade normally, while Willrow's own upgrade is untouched.
## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 1:SOR_120
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_061
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# OwnerReturnsOwnUpgradeNotBlocked
#// SEC_061 Willrow Hood — the protection is only against ENEMY card abilities. The controller's OWN
#//   return effect still works. P2 controls Willrow with exactly 1 friendly upgrade (SOR_120, cost 2 <=3)
#//   and plays its OWN Junior Senator (SEC_200, "may return an upgrade that costs 3 or less to its owner's
#//   hand") targeting Willrow. Actor == controller, so the protection does not apply and SOR_120 returns
#//   to P2's hand.

## GIVEN
CommonSetup: grw/yyw/{theirResources:2;theirHandCardIds:SEC_200}
WithActivePlayer: 2
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_061
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENACOUNT:2

---

# EnemyReturnsWillrowHimself_UpgradeDefeated
#// SEC_061 Willrow Hood — the protection covers the UPGRADE, not Willrow himself. An enemy Waylay
#//   (SOR_222, "Return a non-leader unit to its owner's hand") returns Willrow to P2's hand. Willrow
#//   leaving play is a state-based consequence, so his lone friendly upgrade (SOR_120, a non-token
#//   upgrade) is defeated to P2's discard. Willrow himself returns to hand (not the discard).

## GIVEN
CommonSetup: yyw/grw/{myResources:3;handCardIds:SOR_222}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# TokenShieldUpgrade_EnemyCantDefeat
#// SEC_061 Willrow Hood — the lone friendly upgrade may be a TOKEN upgrade. P2's Willrow bears exactly
#//   1 friendly Shield token (SOR_T02). P1 plays Confiscate ("Defeat an upgrade") targeting it, but the
#//   enemy defeat is blocked, so the Shield survives (Confiscate is spent for nothing).

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1
P2DISCARDCOUNT:0

---

# SystemShock_ProtectedUpgrade_NoDamage
#// SEC_061 Willrow Hood — JTL_175 System Shock is "Defeat a non-leader upgrade attached to a unit. If you
#// do, deal 1 damage to that unit." P2's Willrow bears exactly 1 friendly upgrade (SOR_120), so the enemy
#// defeat is blocked; because the defeat is prevented, the "if you do" 1 damage must NOT fire. Willrow
#// keeps the upgrade AND takes 0 damage.

## GIVEN
CommonSetup: rrw/grw/{myResources:1;handCardIds:JTL_175}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
