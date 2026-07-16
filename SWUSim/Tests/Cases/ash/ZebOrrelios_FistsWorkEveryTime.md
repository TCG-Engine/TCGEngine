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
