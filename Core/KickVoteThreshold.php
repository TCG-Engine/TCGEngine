<?php
// Shared vote-to-remove-a-seat threshold — the ONE formula behind both the in-game kick vote
// (SWUSim/Custom/InactivityClock.php's SWUVotesNeededFor) and the lobby's Kick Host vote
// (APIs/Lobbies/Classes/HostVote.php). Pulled out so the two can never independently drift, per
// the project rule that "one function advertises and another resolves, and their disagreement IS
// the bug class."
//
// $unanimous = true requires every voter (Team Suns kicking an opposing player: a 1-vote kick
// would let one player unilaterally hand their team the win). Otherwise the threshold is capped
// at 2 — a majority, not a full house, once there are more than two voters: 2 of 2 at three total
// participants, 2 of 3 at four.
function KickVoteThreshold(int $voterCount, bool $unanimous = false): int {
    if ($voterCount <= 0) return 0;
    return $unanimous ? $voterCount : min(2, $voterCount);
}
