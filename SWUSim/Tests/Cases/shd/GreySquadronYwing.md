# OnAttack_CasterDeclines
#// SHD_246 Grey Squadron Y-Wing — the damage is a "may": after the opponent chooses its unit, P1 declines
#// (NO), so no damage is dealt to it (only the 1 base combat damage from the attack itself).

## GIVEN
CommonSetup: rrw/rrw
WithActivePlayer: 1
WithP1SpaceArena: SHD_246:1:0
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:NO

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_046
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:1

---

# OnAttack_OpponentChoosesUnit_Deal2
#// SHD_246 Grey Squadron Y-Wing (2-cost 1/3 space) — "On Attack: An opponent chooses a unit or base they
#// control. You may deal 2 damage to it." Grey Squadron attacks P2's base; on attack, P2 chooses its own
#// space unit SOR_046, and P1 opts to deal 2 to it. (Cross-player, so WithActivePlayer:1 — not P1OnlyActions,
#// which would auto-pass P2 and eat its choice.)

## GIVEN
CommonSetup: rrw/rrw
WithActivePlayer: 1
WithP1SpaceArena: SHD_246:1:0
WithP2SpaceArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_046
P2SPACEARENAUNIT:0:DAMAGE:2
P2BASEDMG:1
