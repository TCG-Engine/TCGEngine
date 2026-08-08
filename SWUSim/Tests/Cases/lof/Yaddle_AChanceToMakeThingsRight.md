# OnAttack_GrantRestoreToJedi
#// LOF_045 Yaddle — On Attack: each other friendly Jedi unit gains Restore 1 for this phase. Yaddle
#// attacks the base; her fellow Jedi (Plo Koon) gains Restore.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_045:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Restore

---

# RestoreExpiresNextPhase
#// LOF_045 Yaddle — the granted Restore lasts only "for this phase". Yaddle attacks, granting the other
#// friendly Jedi (LOF_050) Restore 1; both players then pass so the action phase ends and regroup runs
#// turn-effect expiry. Next phase LOF_050 no longer has the granted Restore, while Yaddle keeps her own
#// innate Restore 1. The granted Restore lasts only for the current phase.

## GIVEN
CommonSetup: bbw/rrk
WithActivePlayer: 1
WithP1GroundArena: LOF_045:1:0
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:NOTKEYWORD:Restore
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore

---

# GrantScope_OtherFriendlyJediOnly
#// LOF_045 Yaddle — the three scope limits of "each OTHER FRIENDLY JEDI unit", all in one board.
#// SOR_095 Battlefield Marine (friendly, Rebel/Trooper — NOT a Jedi) gets nothing; the ENEMY Jedi
#// Plo Koon (LOF_050) gets nothing; and Yaddle herself is excluded by "other" — she keeps only her own
#// printed Restore 1, so a wrong self-grant to Restore 2 would show up here.
## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_045:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LOF_050:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:NOTKEYWORD:Restore
P2GROUNDARENAUNIT:0:CARDID:LOF_050
P2GROUNDARENAUNIT:0:NOTKEYWORD:Restore
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
