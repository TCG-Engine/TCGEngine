# Raid2WithAggression
#// SHD_168 Hunting Nexu — Unit, cost 4, 4/4, Ground, [Aggression], trait Creature.
#// "While you control another Aggression unit, this unit gains Raid 2."
#// COVERAGE: offer=N/A (a static conditional keyword grant has no target pick) ·
#//           request boundary=N/A (no decision inside the grant) ·
#//           control=NoOtherAggressionUnit_NoRaid — the enabling Aggression unit sits on the ENEMY side
#//           there, so the section is exactly the "you control" half of the condition ·
#//           boundary pair=Raid2WithAggression (another friendly Aggression unit → Raid, keyword present)
#//           + NoOtherAggressionUnit_NoRaid (none → NOTKEYWORD and 4, not 6, damage to the base — the
#//           "does not count itself" negative) ·
#//           decline=N/A (no "you may").
#// Guard: with another Aggression unit (SHD_138) in play it has Raid.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_168:1:0
WithP1GroundArena: SHD_138:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_168
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# NoOtherAggressionUnit_NoRaid
#// SHD_168 Hunting Nexu — the grant needs ANOTHER Aggression unit that YOU CONTROL. Here the only other
#// Aggression unit (SHD_138 Jango Fett) belongs to P2, and Hunting Nexu does not count itself, so the
#// condition is false: no Raid keyword, and the attack lands its printed 4 power on P2's base instead
#// of 6.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_168:1:0
WithP2GroundArena: SHD_138:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_168
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P2BASEDMG:4
