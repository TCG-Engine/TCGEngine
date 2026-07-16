# HealMode
#// ASH_257 Choose Your Path (Event) — Heal mode: control a Force unit → heal 5 from your base.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myBaseDamage:6;handCardIds:ASH_257}
WithP1GroundArena: SOR_049:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Heal
## EXPECT
P1BASEDMG:1

---

# MandalorianMode
#// ASH_257 Choose Your Path — Mandalorian mode: control a Mandalorian unit → create a Mandalorian token
#// and give it an Advantage token.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:ASH_257}
WithP1GroundArena: ASH_063:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Mandalorian
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
