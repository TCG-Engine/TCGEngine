# HealAndAdvantage
#// ASH_044 Barriss Offee (Ground, 3/4) — When Played: heal up to 2 from a unit; give it an Advantage token
#// per damage healed. A friendly unit with 2 damage is healed to 0 and gains 2 Advantage tokens.
## GIVEN
CommonSetup: byk/rrk/{myResources:6;handCardIds:ASH_044}
WithP1GroundArena: SEC_080:1:2
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2

---

# UndamagedUnit_ZeroAdvantage
#// ASH_044 Barriss Offee — the Advantage is one per damage HEALED. Choosing an undamaged unit heals 0, so
#// it gains no Advantage tokens.
## GIVEN
CommonSetup: byk/rrk/{myResources:6;handCardIds:ASH_044}
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
