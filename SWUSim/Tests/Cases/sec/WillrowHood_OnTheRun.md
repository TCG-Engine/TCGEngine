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

---

# TwoUpgrades_EnemyCanReturn
#// SEC_061 Willrow Hood — the RETURN-side mirror of the "exactly 1" boundary (only the defeat side was
#//   covered). With 2 friendly upgrades the protection is off, so P1's SEC_200 Junior Senator ("may return
#//   an upgrade that costs 3 or less to its owner's hand") DOES return one — Willrow drops to 1 upgrade
#//   and the returned card lands in P2's hand.
## GIVEN
CommonSetup: yyw/grw/{myResources:2;handCardIds:SEC_200}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_069
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:1

---

# TokenShieldUpgrade_EnemyCantReturn
#// SEC_061 Willrow Hood — the RETURN-side mirror for a TOKEN upgrade (only the token DEFEAT side was
#//   covered). P2's Willrow bears exactly 1 friendly Shield token. P1 plays SEC_200 Junior Senator and
#//   picks Willrow as the host, but the enemy return is blocked, so the Shield stays attached.
#//   (A returned token would CEASE rather than go to hand, so "blocked" and "returned" are easy to tell
#//   apart: the token is still on Willrow.)
## GIVEN
CommonSetup: yyw/grw/{myResources:2;handCardIds:SEC_200}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# OwnerDefeatsOwnUpgradeNotBlocked
#// SEC_061 Willrow Hood — the owner-side DEFEAT mirror (only owner-RETURN was covered). The protection is
#//   against ENEMY card abilities only, so P2 defeating its own lone upgrade with its own Confiscate
#//   works — this is the same actor==controller rule as FriendlyDefeatNotBlocked, stated on the owner axis.
## GIVEN
CommonSetup: grw/grw/{theirResources:1;theirHandCardIds:SOR_251}
WithActivePlayer: 2
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P2>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# FriendlyReturnNotBlocked
#// SEC_061 Willrow Hood — the RETURN-side mirror of FriendlyDefeatNotBlocked. Only ENEMY card abilities
#//   are blocked, so Willrow's own controller returning his lone upgrade with their OWN SEC_200 Junior
#//   Senator works: the upgrade leaves Willrow and goes back to P2's hand.
## GIVEN
CommonSetup: grw/yyw/{theirResources:2;theirHandCardIds:SEC_200}
WithActivePlayer: 2
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# EnemyOwnedUpgradeOnWillrow_DoesNotBreakTheExactlyOneGate
#// SEC_061 Willrow Hood — "exactly 1 FRIENDLY upgrade" counts by CONTROLLER, and per CR 2.e/3.5 a player
#//   who plays an upgrade onto an ENEMY unit REMAINS its controller. So an upgrade P1 plays onto P2's
#//   Willrow is P1's, not P2's — Willrow still has exactly ONE friendly upgrade and the protection holds.
#//   P1 attaches Ambition's Reward (SEC_175, enemy-attachable per CR 2.e) onto P2's Willrow, which already
#//   bears the P2-owned SOR_120, then Confiscates the FRIENDLY one → blocked, both upgrades remain.
#//   ⚠ Confiscate's target OFFER lists both upgrades; the protection applies at RESOLUTION. Read the
#//   outcome, not the offer — the offer alone looks exactly like "protection is off".
## GIVEN
CommonSetup: rrw/grw/{myResources:6;handCardIds:SEC_175,SOR_251}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120

---

# EnemyOwnedUpgradeOnWillrow_IsItselfUnprotected
#// SEC_061 Willrow Hood — the complementary half: only FRIENDLY upgrades are protected, so the upgrade
#//   P1 controls on P2's Willrow is fair game for P1's own Confiscate. Same board as the section above,
#//   but P1 targets its OWN SEC_175 → it IS defeated, leaving only P2's SOR_120 attached. Together the two
#//   sections prove the controller split is real in both directions.
## GIVEN
CommonSetup: rrw/grw/{myResources:6;handCardIds:SEC_175,SOR_251}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-1
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
