# UpgradeDefeated_DealBase
#// ASH_161 Zeb Orrelios — "When a friendly upgrade is defeated: deal 1 damage to a base." With Zeb in play,
#// SOR_095 (wearing SOR_120) dies attacking SOR_046; SOR_120 is defeated, so Zeb deals 1 to P2's base.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:3
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:1

---

# WhenPlayed_ThreeAdvantage
#// ASH_161 Zeb Orrelios (Ground, 5/7, cost 7) — When Played: give 3 Advantage tokens to another unit. Zeb
#// enters and piles 3 Advantage onto SOR_095 (the only other unit, auto-resolved).
## GIVEN
CommonSetup: rrw/rrk/{myResources:7;handCardIds:ASH_161}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3

---

# WhenPlayed_ThreeAdvantage_ToEnemy
#// ASH_161 Zeb — "another unit" may be an ENEMY unit. With a friendly and an enemy unit present, Zeb
#// prompts; P1 chooses the enemy SOR_046 and piles all 3 Advantage there.
## GIVEN
CommonSetup: rrw/rrk/{myResources:7;handCardIds:ASH_161}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# UpgradeDefeated_DealOwnBase
#// ASH_161 Zeb — the "deal 1 to a base" target is the controller's choice; P1 may point it at their OWN
#// base. Same defeat as above, but P1 chooses myBase.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:3
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:1
P2BASEDMG:0

---

# FriendlyUnitNoUpgradeDefeated_NoTrigger
#// ASH_161 Zeb — triggers only on a friendly UPGRADE being defeated, not on a bare friendly unit dying.
#// SOR_095 (no upgrade) dies attacking SOR_046; Zeb does nothing — no base damage, no prompt.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1NODECISION
P1BASEDMG:0
P2BASEDMG:0
P1GROUNDARENACOUNT:1

---

# EnemyUpgradeDefeated_NoTrigger
#// ASH_161 Zeb — an ENEMY-controlled upgrade being defeated is not "friendly," so Zeb does not trigger.
#// P1's SOR_046 kills a pre-damaged enemy SOR_095 wearing the enemy's SOR_120; that upgrade is defeated
#// but belongs to P2, so no base damage and no prompt for P1.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:0:2
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1NODECISION
P1BASEDMG:0
P2BASEDMG:0
P2GROUNDARENACOUNT:0
