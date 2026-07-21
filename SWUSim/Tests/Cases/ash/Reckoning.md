# DamageEqualToTotal
#// ASH_187 Reckoning (Event, cost 3) — Deal damage to a unit equal to the total amount of damage on all
#// units you control. P1 controls SOR_046 (2 damage) and SOR_095 (1 damage) = 3 total; the chosen enemy
#// SEC_080 (3/3) takes 3 and is defeated.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_187}
WithP1GroundArena: SOR_046:1:2
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0

---

# NoDamageOnUnits_DealsZero
#// ASH_187 Reckoning — the damage equals total damage on your units; with all your units undamaged, it
#// deals 0. The chosen enemy SEC_080 takes nothing.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_187}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
