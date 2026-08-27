# LeaderDeployed_OneDottedBoxPerLeader
#// VISUAL CHECK for the deployed-leader placeholder ring (2026-08-26).
#//
#// WHAT TO LOOK FOR — four leader slots, three distinct states:
#//   • YOUR side: BOTH leaders deployed → TWO separate dotted boxes, each with its own "DEPLOYED".
#//     The bug this replaces drew ONE box around both slots, so the words sat side by side inside it
#//     and read as "DEPLOYED DEPLOYED".
#//   • YOUR second leader is also EXHAUSTED — its card span carries UILibraries' inline rotate(9deg),
#//     so this is the tilt case. The ring and the word must stay SQUARE and in step with each other;
#//     a ring that tilts while the label counter-rotates is the failure mode to watch for.
#//   • THEIR side: leader 1 deployed, leader 2 NOT. Only the deployed one gets a ring — the second
#//     leader must still show its full card art, unringed. The old wrapper-level ring covered both,
#//     making a leader that was still on its card look gone.
#//
#// ⚠ Two leaders and a base per seat on purpose: a one-leader board cannot show this bug at all, which
#// is exactly why it survived. Cross-browser: the ring is a native dotted `outline` on a pseudo-element
#// with a border-radius — verify in Chromium, Firefox AND WebKit before signing off.

## GIVEN
CommonSetup: yyk/rrk/{myLeaderDeployed:true;myLeader2:IBH_053:0:1;theirLeaderDeployed:true;theirLeader2:SHD_011:1:0}
SkipPreGame: true
WithP1Resources: 4
WithP2Resources: 4
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN

## EXPECT
#// Nothing asserted — the Test Schema Editor is the evidence. The engine-side state this leans on is
#// already covered by the Cases/ suite; what cannot be asserted here is how it DRAWS.
