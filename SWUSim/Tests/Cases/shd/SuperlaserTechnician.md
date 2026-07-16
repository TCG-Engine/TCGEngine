# WhenDefeated_Decline
#// SHD_085 Superlaser Technician — the "You may" is optional. Declining the When Defeated leaves the
#// Technician in the discard; resources stay at 2.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SHD_085:1:0
WithP1Resources: 2
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_085

---

# WhenDefeated_ReadyResource
#// SHD_085 Superlaser Technician (3-cost 2/1) — "When Defeated: You may put this unit into play as a
#// resource and ready it." P1's Technician (2/1) attacks a Wampa (SOR_164, 4/5): deals 2 (Wampa
#// survives), Wampa counters 4 → Technician (1 HP) dies. Its When Defeated resolves (YES) → it leaves
#// the discard and enters as a READY resource. Net: 2 starting resources + the new ready one = 3, all
#// available (attacking spends none).

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1GroundArena: SHD_085:1:0
WithP1Resources: 2
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:3
P1RESAVAILABLE:3
P1DISCARDCOUNT:0
