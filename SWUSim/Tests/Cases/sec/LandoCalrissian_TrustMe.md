# EnemyCapturesFriendly_Heal6
#// SEC_068 Lando Calrissian (Ground, 6/8, Vigilance, cost 7) — Grit + When Played: choose an enemy unit
#//   and another friendly non-leader unit → heal 6 from your base and the enemy unit captures the friendly.
#// Enemy SOR_046 captures friendly SOR_095; P1 base heals 6 (6→0).

## GIVEN
CommonSetup: bbk/rrk/{myResources:7;myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_068

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_068
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# WhenPlayed_Optional_Pass
#// SEC_068 Lando Calrissian — the When Played ability is optional ("you may"). P1 has another friendly unit
#// (SEC_213 A-Wing) and P2 has an enemy (JTL_237 TIE Bomber). P1 plays Lando and PASSES: no capture, no
#// base heal (base stays at 6).

## GIVEN
CommonSetup: bbk/rrk/{myResources:7;myBaseDamage:6}
P1OnlyActions: true
WithP1SpaceArena: SEC_213:1:0
WithP2SpaceArena: JTL_237:1:0
WithP1Hand: SEC_068

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1BASEDMG:6
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_213
P2SPACEARENAUNIT:0:CARDID:JTL_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_068
P1NODECISION

---

# WhenPlayed_NoEnemyUnits_Skipped
#// SEC_068 Lando Calrissian — with NO enemy units in play there is nothing to do the capturing, so the
#// ability is skipped entirely: no prompt, no heal (base stays at 6).

## GIVEN
CommonSetup: bbk/rrk/{myResources:7;myBaseDamage:6}
P1OnlyActions: true
WithP1SpaceArena: SEC_213:1:0
WithP1Hand: SEC_068

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:6
P1SPACEARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_068
P1NODECISION

---

# WhenPlayed_LandoOnlyFriendly_NoCapture
#// SEC_068 Lando Calrissian — the captured unit must be ANOTHER friendly non-leader. With Lando as the only
#// friendly unit, choosing the enemy captor (JTL_237) leads nowhere: no friendly to capture, so no heal and
#// no capture occur (base stays at 6).

## GIVEN
CommonSetup: bbk/rrk/{myResources:7;myBaseDamage:6}
P1OnlyActions: true
WithP2SpaceArena: JTL_237:1:0
WithP1Hand: SEC_068

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1BASEDMG:6
P2SPACEARENAUNIT:0:CARDID:JTL_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_068
P1NODECISION

---

# WhenPlayed_IG11_DefeatedInsteadOfCaptured_StillHeals
#// SEC_068 Lando Calrissian — capture-replacement interaction. SHD_170 IG-11 ("If this unit would be
#// captured, defeat him instead") is the only other friendly. P1 chooses the enemy JTL_237 TIE Bomber to
#// capture IG-11; IG-11 is DEFEATED instead of captured (TIE Bomber holds 0 captives), but the heal still
#// happens (base 6 → 0).

## GIVEN
CommonSetup: bbk/rrk/{myResources:7;myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: SHD_170:1:0
WithP2SpaceArena: JTL_237:1:0
WithP1Hand: SEC_068

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1BASEDMG:0
P2SPACEARENAUNIT:0:CARDID:JTL_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
