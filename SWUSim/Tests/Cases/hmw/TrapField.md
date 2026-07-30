# EnemyUnitEnters_MayDefeatAndDealThree
#// HMW_171 Trap Field (Fortify) — "When a non-leader ground unit enters play (including token units): You
#// may defeat this upgrade. If you do, deal 3 damage to that unit." The intended use: the OPPONENT's base
#// carries it and reacts (cross-player) to a ground unit the active player plays. SOR_046 is a 3/7 wall so
#// it survives the 3 and shows DAMAGE:3, proving the deal-3 landed.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP2BaseUpgrade: HMW_171
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES
- P2>AnswerDecision:YES

## EXPECT
P2BASE:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# EnemyUnitEnters_Decline_UpgradeStaysNoDamage
#// The "you may" decline: the base owner passes, so Trap Field stays attached and no damage is dealt. The
#// first P2 answer drains the RESOLVE_NEXT_TRIGGER orchestration (a cross-player single reaction, like
#// TWI_210); the second (`-`) is the actual decline of the YESNO.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP2BaseUpgrade: HMW_171
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES
- P2>AnswerDecision:-

## EXPECT
P2BASE:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# DealThreeDefeatsASmallUnit
#// The 3 damage can be lethal: a 3/3 entering the board is defeated by it (SWUCheckShrinkDefeats /
#// SWUDealDamageToUnit → defeat), so it never sticks in the arena.

## GIVEN
CommonSetup: bbw/ggw/{theirResources:3}
WithActivePlayer: 2
WithP1BaseUpgrade: HMW_171
WithP2Hand: SOR_095

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1BASE:UPGRADECOUNT:0
P2GROUNDARENACOUNT:0

---

# OwnUnitEnters_TrapFieldIsUnrestricted
#// The text carries no friendly/enemy qualifier, so your OWN Trap Field reacts to your OWN ground unit too
#// (same-player, active). Confirms the observer is unrestricted rather than enemy-only.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_171
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1BASE:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# SpaceUnitEnters_DoesNotTrigger
#// "non-leader GROUND unit" — a unit entering the SPACE arena raises no reaction and does not consume the
#// upgrade. SOR_237 Alliance X-Wing is a 2/3 space Fighter.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_171
WithP1Hand: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1BASE:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0

---

# TokenUnitEnters_AlsoTriggers
#// "including token units" — a CREATED ground token (not a played card) must also trigger. Droid Deployment
#// (event) creates 2 Battle Droid tokens (1/1 ground); the first arms Trap Field, and taking it defeats the
#// upgrade + the 1/1 token. The upgrade is then gone, so the second token is not hit and survives.

## GIVEN
CommonSetup: rrk/ggw/{myResources:2}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_171
WithP1Hand: TWI_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1BASE:UPGRADECOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
