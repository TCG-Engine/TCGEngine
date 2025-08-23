// Lightweight tournament simulator stub
// Node.js script. Stubs provided for:
// - GenerateRepresentativeMeta(numParticipants): returns array of decks with leader/base and frequencies
// - GetWinProbability(leaderA, baseA, leaderB, baseB): returns win probability for A vs B
// - simulateTournament(meta, numRounds): runs a simple Swiss-style tournament using random results

const DEFAULT_PARTICIPANTS = 64;

function GenerateRepresentativeMeta(numParticipants = DEFAULT_PARTICIPANTS) {
  // Stub: produce a small meta distribution of leader/base archetypes.
  // For now, we create a few archetypes with rough shares.
  const archetypes = [
    { leader: 'Leader_A', base: 'Base_X', share: 0.30 },
    { leader: 'Leader_B', base: 'Base_Y', share: 0.25 },
    { leader: 'Leader_C', base: 'Base_Z', share: 0.20 },
    { leader: 'Leader_D', base: 'Base_X', share: 0.15 },
    { leader: 'Leader_E', base: 'Base_Y', share: 0.10 }
  ];

  // Normalize and generate concrete participants list
  let participants = [];
  archetypes.forEach(a => {
    const count = Math.max(1, Math.round(a.share * numParticipants));
    for (let i = 0; i < count; i++) {
      participants.push({ leader: a.leader, base: a.base });
    }
  });

  // If rounding left us off, trim or add copies of the most common archetype
  while (participants.length > numParticipants) participants.pop();
  while (participants.length < numParticipants) participants.push({ leader: 'Leader_A', base: 'Base_X' });

  return participants;
}

function GetWinProbability(leaderA, baseA, leaderB, baseB) {
  // Stub: simple deterministic rules plus randomness.
  // For now, use leader name comparisons to create asymmetric matchups.
  if (leaderA === leaderB && baseA === baseB) return 0.5; // mirror

  // Example rules: A beats B if leader letter comes earlier in alphabet (very rough)
  const la = leaderA.slice(-1).charCodeAt(0);
  const lb = leaderB.slice(-1).charCodeAt(0);
  let baseFactor = 0;
  if (baseA === baseB) baseFactor = 0;
  else if (baseA === 'Base_X' && baseB === 'Base_Y') baseFactor = 0.05;
  else if (baseA === 'Base_Y' && baseB === 'Base_Z') baseFactor = 0.05;
  else if (baseA === 'Base_Z' && baseB === 'Base_X') baseFactor = 0.05;

  let diff = (lb - la) / 52; // small base range
  let p = 0.5 + diff + baseFactor;
  if (p < 0.02) p = 0.02;
  if (p > 0.98) p = 0.98;
  return p;
}

function pairSwiss(participants, round) {
  // Simple pairing: sort by score then pair adjacent; no complex tiebreakers.
  participants.sort((a, b) => b.score - a.score || a.id - b.id);
  const pairs = [];
  for (let i = 0; i < participants.length; i += 2) {
    if (i + 1 < participants.length) pairs.push([participants[i], participants[i+1]]);
    else pairs.push([participants[i], null]); // bye
  }
  return pairs;
}

function simulateSingleTournament(numParticipants = DEFAULT_PARTICIPANTS, numRounds = 6) {
  const meta = GenerateRepresentativeMeta(numParticipants);
  // initialize players
  const players = meta.map((d, i) => ({ id: i+1, leader: d.leader, base: d.base, score: 0 }));

  for (let r = 1; r <= numRounds; r++) {
    const pairs = pairSwiss(players, r);
    for (const [pA, pB] of pairs) {
      if (!pB) { // bye
        pA.score += 3; // 3 points for bye
        continue;
      }
      const p = GetWinProbability(pA.leader, pA.base, pB.leader, pB.base);
      const roll = Math.random();
      if (roll < p) {
        pA.score += 3;
      } else if (roll < p + (1-p)/2) {
        // tie
        pA.score += 1; pB.score += 1;
      } else {
        pB.score += 3;
      }
    }
  }

  // return standings
  players.sort((a,b) => b.score - a.score || a.id - b.id);
  return players;
}

function runManyTournaments(numTournaments = 1000, numParticipants = DEFAULT_PARTICIPANTS, numRounds = 6, targetLeader = null, targetBase = null) {
  const results = [];
  // track metrics for target archetype if provided
  let targetTop8Count = 0;
  let targetTotalRank = 0; // aggregate finishing position
  let targetMatchWins = 0;
  let targetMatchTotal = 0;

  for (let t = 0; t < numTournaments; t++) {
    const standings = simulateSingleTournament(numParticipants, numRounds);
    // record top-8 leader/base counts
    const top = standings.slice(0, Math.min(8, standings.length));
    const topCounts = {};
    for (let i = 0; i < standings.length; i++) {
      const p = standings[i];
      const key = `${p.leader}||${p.base}`;
      topCounts[key] = (topCounts[key] || 0) + (i < 8 ? 1 : 0);

      // if this player is the target archetype, accumulate rank
      if (targetLeader && targetBase && p.leader === targetLeader && p.base === targetBase) {
        targetTotalRank += (i + 1); // ranks are 1-based
      }
    }

    // matches: estimate match wins by comparing against simulated pairings using GetWinProbability
    // naive approach: for each player, simulate matches again vs opponents deterministically
    if (targetLeader && targetBase) {
      // find all players with target archetype in this tournament
      const targets = standings.filter(p => p.leader === targetLeader && p.base === targetBase);
      // for each target, approximate match wins by comparing its final score to average: (score/3) approx wins
      for (const tp of targets) {
        const wins = Math.round(tp.score / 3); // rough
        targetMatchWins += wins;
        targetMatchTotal += numRounds;
      }
    }

    // count top8 occurrences
    for (const k of Object.keys(topCounts)) results.push({ archetype: k, top8: topCounts[k] });
    // also account straightforwardly for top8Count for target
    if (targetLeader && targetBase) {
      const key = `${targetLeader}||${targetBase}`;
      const foundInTop = top.some(p => (p.leader === targetLeader && p.base === targetBase));
      if (foundInTop) targetTop8Count++;
    }
  }

  // aggregate
  const aggregate = {};
  for (const r of results) {
    aggregate[r.archetype] = (aggregate[r.archetype] || 0) + r.top8;
  }

  const totals = Object.entries(aggregate).map(([k,v]) => ({ archetype: k, top8Appearances: v, top8Rate: v / (numTournaments * 8) }));
  totals.sort((a,b) => b.top8Appearances - a.top8Appearances);

  const out = { numTournaments, numParticipants, numRounds, totals };
  if (targetLeader && targetBase) {
    const avgRank = targetTotalRank / Math.max(1, (numTournaments));
    const matchWinRate = targetMatchTotal > 0 ? (targetMatchWins / targetMatchTotal) : null;
    out.target = { leader: targetLeader, base: targetBase, top8Rate: targetTop8Count / numTournaments, avgRank, matchWinRate };
  }
  return out;
}

// allow running from node
if (require.main === module) {
  const args = process.argv.slice(2).map(v => parseInt(v));
  const numT = args[0] || 1000;
  const numP = args[1] || DEFAULT_PARTICIPANTS;
  const numR = args[2] || 6;
  console.time('sim');
  const out = runManyTournaments(numT, numP, numR);
  console.timeEnd('sim');
  console.log(JSON.stringify(out, null, 2));
}

module.exports = { GenerateRepresentativeMeta, GetWinProbability, simulateSingleTournament, runManyTournaments };
