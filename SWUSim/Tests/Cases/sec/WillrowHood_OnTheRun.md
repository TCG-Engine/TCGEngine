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
