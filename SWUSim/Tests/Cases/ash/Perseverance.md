# HealAndShield
#// ASH_089 Perseverance (Event, cost 2) — Heal 3 damage from a unit and give a Shield token to it. P1
#// targets the damaged SOR_046 (3 damage): it is healed to 0 and gains a Shield.
## GIVEN
CommonSetup: bbk/bbk/{myResources:2;handCardIds:ASH_089}
WithP1GroundArena: SOR_046:1:3
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# HealAndShield
#// ASH_089 Perseverance — heal 3 damage from a unit and give it a Shield token. SOR_046 (3 damage) is
#// healed to 0 and gains a Shield.
## GIVEN
CommonSetup: bbk/bbk/{myResources:2;handCardIds:ASH_089}
WithP1GroundArena: SOR_046:1:3
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
