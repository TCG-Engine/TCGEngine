# WhenPlayed_HealBaseWithRepublic
#// TWI_109 501st Liberator (Unit 3/3, Ground, cost 3, Command, Republic/Clone/Trooper) — "When Played: If
#// you control another Republic unit, you may heal 3 damage from a base." With another Republic unit
#// (TWI_065) in play and P1's base at 5 damage, healing P1's base brings it to 2. Base g + leader gw.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myBaseDamage:5;handCardIds:TWI_109}
P1OnlyActions: true
WithP1GroundArena: TWI_065:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P1GROUNDARENAUNIT:1:CARDID:TWI_109

---

# WhenPlayed_NoRepublic_NoHeal
#// TWI_109 501st Liberator — condition guard: with no OTHER Republic unit (the Liberator itself doesn't
#// count), no heal is offered and P1's base stays at 5 damage.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myBaseDamage:5;handCardIds:TWI_109}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1BASEDMG:5
P1GROUNDARENAUNIT:0:CARDID:TWI_109
