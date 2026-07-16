# NoPalpatine_NoHP
#// SOR_082 Emperor's Royal Guard (3/4) — P1's leader is NOT Palpatine, so the
#// +0/+1 does not apply. Reads its printed 3/4.
#// (Absence guard — passes pre-implementation; stays meaningful once the buff exists.)

## GIVEN
CommonSetup: grw/grw
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4

---

# PalpatineLeader_GetsHP
#// SOR_082 Emperor's Royal Guard (3/4) — "While you control Emperor Palpatine
#// (as a leader or unit), this unit gets +0/+1." P1's leader is Palpatine (SOR_006),
#// so the Guard reads 3/5. (The separate "Official → Sentinel" clause is already
#// implemented; no Official unit here, so only the +0/+1 is active.)

## GIVEN
CommonSetup: ggk/grw/{
  myLeader:SOR_006
}
SkipPreGame: true
WithP1GroundArena: SOR_082:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5
