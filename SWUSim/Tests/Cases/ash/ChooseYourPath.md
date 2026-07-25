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

---

# HealMode_CapsAtBaseDamage
#// ASH_257 Choose Your Path — Heal mode heals up to 5 but is capped at the base's actual damage. With only
#// 2 damage on P1's base, healing 5 removes just those 2 (→ 0).
## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myBaseDamage:2;handCardIds:ASH_257}
WithP1GroundArena: SOR_049:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Heal
## EXPECT
P1BASEDMG:0

---

# ForceMode_NoForceUnit_NoEffect
#// ASH_257 Choose Your Path — both modes are always offered, but the Heal clause is gated on "if you
#// control a Force unit". Controlling only SHD_258 Mandalorian Warrior (not a Force unit), choosing Heal
#// does nothing — P1's base stays at 5 damage.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myBaseDamage:5;handCardIds:ASH_257}
WithP1GroundArena: SHD_258:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Heal
## EXPECT
P1BASEDMG:5
P1GROUNDARENACOUNT:1

---

# MandalorianMode_NoMandoUnit_NoEffect
#// ASH_257 Choose Your Path — the Mandalorian clause is gated on "if you control a Mandalorian unit".
#// Controlling only SOR_049 Obi-Wan Kenobi (a Force unit, not Mandalorian), choosing the Mandalorian mode
#// creates no token and heals nothing (base stays at 10 damage, still just the one unit).
## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myBaseDamage:10;handCardIds:ASH_257}
WithP1GroundArena: SOR_049:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Mandalorian
## EXPECT
P1BASEDMG:10
P1GROUNDARENACOUNT:1

---

# NeitherControlled_NoEffect
#// ASH_257 Choose Your Path — controlling neither a Force nor a Mandalorian unit (only SOR_095 Battlefield
#// Marine), neither clause can do anything; picking the Mandalorian mode creates no token and the base is
#// unchanged.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3;myBaseDamage:5;handCardIds:ASH_257}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Mandalorian
## EXPECT
P1BASEDMG:5
P1GROUNDARENACOUNT:1
