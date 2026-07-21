# UseForce_ShieldUnits
#// LOF_072 Priestesses of the Force (6/8) — When Played: may use the Force → give a Shield token to each
#// of up to 5 units. P1 plays it with the Force and shields two friendly units.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;handCardIds:LOF_072}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# NoForce_DoesNothing
#// LOF_072 Priestesses of the Force — the When Played ability requires spending the Force token. Without the
#// Force, the ability does nothing: Priestesses enters with no shields and no other unit is shielded.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;handCardIds:LOF_072}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:LOF_072
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# ShieldEnemyAndSelf
#// LOF_072 Priestesses of the Force — the Shield tokens may go on ANY units, including this unit itself and
#// enemy units. Using the Force, P1 shields Priestesses itself and an enemy Battlefield Marine (SOR_095); the
#// shield targets can include both players' units and the played unit.

## GIVEN
CommonSetup: bbw/rrk/{myResources:7;handCardIds:LOF_072}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0&theirGroundArena-0

## EXPECT
P1NOFORCE
P1GROUNDARENAUNIT:0:CARDID:LOF_072
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
