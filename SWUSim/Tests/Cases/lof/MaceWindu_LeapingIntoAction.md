# UseForce_Deal4
#// LOF_149 Mace Windu (6/6) — Overwhelm + When Played: may use the Force → deal 4 damage to a unit. P1
#// uses the Force and deals 4 to the enemy 3/7.

## GIVEN
CommonSetup: rrw/rrk/{myResources:6;handCardIds:LOF_149}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# UseForce_Decline_KeepsForce
#// LOF_149 Mace Windu — the When Played ability is a "may": with the Force token available, P1 declines to
#// spend it, so no damage is dealt and the Force token is kept. Ref: "should deal 4 damage ... but decides not
#// to use it" (clickPrompt 'Pass' → hasTheForce stays true).

## GIVEN
CommonSetup: rrw/rrk/{myResources:6;handCardIds:LOF_149}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1HASFORCE
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoForceToken_NoDamage
#// LOF_149 Mace Windu — the ability requires spending the Force token; with no Force token in the pool it
#// cannot be used, so playing Mace deals no damage. Ref: "should not deal damage when played and not use the
#// Force" (hasForceToken: false).

## GIVEN
CommonSetup: rrw/rrk/{myResources:6;handCardIds:LOF_149}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:DAMAGE:0
