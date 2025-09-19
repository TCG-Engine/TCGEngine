Tournament simulator (stub)

This folder contains small utilities to extract the current deck's leader/base from the PHP gamestate and a lightweight Node.js tournament simulator stub for running many simulated tournaments locally.

Files added:
- `getDeckLeaderBase.php` - PHP endpoint that returns JSON { leaderID, leaderName, baseID, baseName } for the currently parsed deck gamestate.
- `tournamentSim.js` - Node.js script with stubs:
  - `GenerateRepresentativeMeta(numParticipants)` - returns a mocked meta distribution
  - `GetWinProbability(leaderA, baseA, leaderB, baseB)` - stubbed pairwise win probability
  - `simulateSingleTournament(numParticipants, numRounds)` - runs a simple Swiss pairing and scoring
  - `runManyTournaments(numTournaments, numParticipants, numRounds)` - aggregates results across many tournaments

Quick start (Node.js required):

1. Run 1000 tournaments with 64 players, 6 rounds:

```powershell
node tournamentSim.js 1000 64 6
```

2. Call the PHP endpoint from within the web app (when a gamestate is loaded) to get the leader/base for the current deck. Example URL:

http://localhost/TCGEngine/SWUDeck/getDeckLeaderBase.php

Notes & next steps:
- The simulator uses very simple stubs. Replace `GenerateRepresentativeMeta` with a function that samples from your real meta distribution (e.g., from tournament logs).
- Replace `GetWinProbability` with empirical head-to-head rates (leader/base vs leader/base). You can store a table mapping archetype pairs to win rates and load it in the JS code.
- The pairing logic is a minimal Swiss pairing. For accurate tournament simulation, add proper tiebreakers, avoid repeat pairings, and support byes and odd player counts more accurately.

If you want, I can:
- Wire `getDeckLeaderBase.php` to return archetype keys used by the simulator.
- Implement a small CSV loader to feed empirical matchup probabilities into `tournamentSim.js`.
