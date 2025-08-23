// Lightweight tournament simulator stub
// Node.js script. Stubs provided for:
// - GenerateRepresentativeMeta(numParticipants): returns array of decks with leader/base and frequencies
// - GetWinProbability(leaderA, baseA, leaderB, baseB): returns win probability for A vs B
// - simulateTournament(meta, numRounds): runs a simple Swiss-style tournament using random results

const DEFAULT_PARTICIPANTS = 64;
const https = require('https');

function GenerateRepresentativeMeta(numParticipants = DEFAULT_PARTICIPANTS) {
  // Stub: produce a small meta distribution of leader/base archetypes.
  // For now, we create a few archetypes with rough shares.
  const archetypes = [
    { leaderId: 'L_A', leaderName: 'Leader_A', baseId: 'B_X', baseName: 'Base_X', share: 0.30 },
    { leaderId: 'L_B', leaderName: 'Leader_B', baseId: 'B_Y', baseName: 'Base_Y', share: 0.25 },
    { leaderId: 'L_C', leaderName: 'Leader_C', baseId: 'B_Z', baseName: 'Base_Z', share: 0.20 },
    { leaderId: 'L_D', leaderName: 'Leader_D', baseId: 'B_X', baseName: 'Base_X', share: 0.15 },
    { leaderId: 'L_E', leaderName: 'Leader_E', baseId: 'B_Y', baseName: 'Base_Y', share: 0.10 }
  ];

  // Normalize and generate concrete participants list
  let participants = [];
  archetypes.forEach(a => {
    const count = Math.max(1, Math.round(a.share * numParticipants));
    for (let i = 0; i < count; i++) {
      participants.push({ leaderId: a.leaderId, baseId: a.baseId, leaderName: a.leaderName, baseName: a.baseName });
    }
  });

  // If rounding left us off, trim or add copies of the most common archetype
  while (participants.length > numParticipants) participants.pop();
  while (participants.length < numParticipants) participants.push({ leaderId: 'L_A', baseId: 'B_X', leaderName: 'Leader_A', baseName: 'Base_X' });

  return participants;
}

function participantsFromApiDecks(decks) {
  // Heuristic: extract leader/base from deck objects returned by API.
  // Look for explicit fields, otherwise try to parse an archetype string.
  const participants = [];
  for (const d of decks) {
    let leader = null;
    let base = null;
    // API returns leader/base as objects { uuid, name }
    if (d.leader) {
      if (typeof d.leader === 'object' && d.leader.name) leader = d.leader.name;
      else leader = d.leader;
    }
    if (d.base) {
      if (typeof d.base === 'object' && d.base.name) base = d.base.name;
      else base = d.base;
    }
    if (!leader && d.leaderName) leader = d.leaderName;
    if (!base && d.baseName) base = d.baseName;
    if (!leader && d.archetype) {
      // try splitting archetype like "Leader / Base" or "Leader||Base"
      const s = d.archetype;
      if (s.indexOf('||') !== -1) {
        const parts = s.split('||'); leader = parts[0].trim(); base = parts[1] ? parts[1].trim() : null;
      } else if (s.indexOf('/') !== -1) {
        const parts = s.split('/'); leader = parts[0].trim(); base = parts[1] ? parts[1].trim() : null;
      } else if (s.indexOf('-') !== -1) {
        const parts = s.split('-'); leader = parts[0].trim(); base = parts[1] ? parts[1].trim() : null;
      } else {
        leader = s.trim();
      }
    }
  if (!leader && d.deck_name) leader = d.deck_name;
    // final fallbacks
  if (!leader) leader = 'UnknownLeader_' + (d.id || Math.random().toString(36).slice(2,8));
  if (!base) base = 'UnknownBase';
  // coerce to strings to avoid downstream type errors
  leader = (typeof leader === 'string') ? leader : String(leader);
  base = (typeof base === 'string') ? base : String(base);
  participants.push({ leaderId: (d.leader && d.leader.uuid) ? String(d.leader.uuid) : String(d.id || Math.random().toString(36).slice(2,8)), baseId: (d.base && d.base.uuid) ? String(d.base.uuid) : String('base_' + (d.id || Math.random().toString(36).slice(2,8))), leaderName: leader, baseName: base });
  }
  return participants;
}

function fetchMeleeTournament(meleeId) {
  const url = `/TCGEngine/APIs/GetMeleeTournament.php?id=${encodeURIComponent(meleeId)}`;
  // use hostname without scheme; use HTTPS on port 443
  const options = { hostname: 'swustats.net', port: 443, path: url, method: 'GET' };
  return new Promise((resolve, reject) => {
    const req = https.request(options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          const parsed = JSON.parse(data);
          if (!parsed.success || !parsed.decks) return reject(new Error('API returned no decks'));
          const participants = participantsFromApiDecks(parsed.decks);
          resolve(participants);
        } catch (e) {
          reject(e);
        }
      });
    });
    req.on('error', (e) => reject(e));
    req.end();
  });
}

function GetWinProbability(leaderA, baseA, leaderB, baseB) {
  // Coerce inputs to strings so string ops below won't throw
  // Inputs may be ids or names; prefer ids when provided
  leaderA = (leaderA && typeof leaderA === 'object' && leaderA.id) ? String(leaderA.id) : (typeof leaderA === 'string' ? leaderA : String(leaderA || ''));
  baseA = (baseA && typeof baseA === 'object' && baseA.id) ? String(baseA.id) : (typeof baseA === 'string' ? baseA : String(baseA || ''));
  leaderB = (leaderB && typeof leaderB === 'object' && leaderB.id) ? String(leaderB.id) : (typeof leaderB === 'string' ? leaderB : String(leaderB || ''));
  baseB = (baseB && typeof baseB === 'object' && baseB.id) ? String(baseB.id) : (typeof baseB === 'string' ? baseB : String(baseB || ''));

  // Stub: simple deterministic rules plus randomness.
  // For now, use leader name comparisons to create asymmetric matchups.
  if (leaderA === leaderB && baseA === baseB) return 0.5; // mirror

  // Example rules: A beats B if leader letter comes earlier in alphabet (very rough)
  // Derive small deterministic numeric values from ids for comparison
  const hash = s => { let h=0; for(let i=0;i<s.length;i++){h=(h*31 + s.charCodeAt(i))|0;} return Math.abs(h); };
  const la = hash(leaderA) % 1000;
  const lb = hash(leaderB) % 1000;
  let baseFactor = 0;
  if (baseA === baseB) baseFactor = 0;
  else if (baseA === 'Base_X' && baseB === 'Base_Y') baseFactor = 0.05;
  else if (baseA === 'Base_Y' && baseB === 'Base_Z') baseFactor = 0.05;
  else if (baseA === 'Base_Z' && baseB === 'Base_X') baseFactor = 0.05;

  let diff = (lb - la) / 2000; // small normalized range
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
  // initialize players (use id fields)
  const players = meta.map((d, i) => ({ id: i+1, leaderId: String(d.leaderId), baseId: String(d.baseId), leaderName: String(d.leaderName), baseName: String(d.baseName), score: 0 }));

  for (let r = 1; r <= numRounds; r++) {
    const pairs = pairSwiss(players, r);
    for (const [pA, pB] of pairs) {
      if (!pB) { // bye
        pA.score += 3; // 3 points for bye
        continue;
      }
      const p = GetWinProbability(pA.leaderId, pA.baseId, pB.leaderId, pB.baseId);
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
  const archetypeMap = {};
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
      const lid = p.leaderId || p.leader || p.leaderName;
      const bid = p.baseId || p.base || p.baseName;
      const key = `${lid}||${bid}`;
      // record representative names for mapping
      if (!archetypeMap[key]) archetypeMap[key] = { leaderId: String(lid), baseId: String(bid), leaderName: p.leaderName || p.leader || String(lid), baseName: p.baseName || p.base || String(bid) };
      topCounts[key] = (topCounts[key] || 0) + (i < 8 ? 1 : 0);

      // if this player is the target archetype, accumulate rank
  if (targetLeader && targetBase && (p.leaderId === targetLeader || p.leaderName === targetLeader) && (p.baseId === targetBase || p.baseName === targetBase)) {
        targetTotalRank += (i + 1); // ranks are 1-based
      }
    }

    // matches: estimate match wins by comparing against simulated pairings using GetWinProbability
    // naive approach: for each player, simulate matches again vs opponents deterministically
  if (targetLeader && targetBase) {
  // find all players with target archetype in this tournament (match by id or name)
  const targets = standings.filter(p => (p.leaderId === targetLeader || p.leaderName === targetLeader) && (p.baseId === targetBase || p.baseName === targetBase));
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
    const foundInTop = top.some(p => ((p.leaderId === targetLeader || p.leaderName === targetLeader) && (p.baseId === targetBase || p.baseName === targetBase)));
      if (foundInTop) targetTop8Count++;
    }
  }

  // aggregate
  const aggregate = {};
  for (const r of results) {
    aggregate[r.archetype] = (aggregate[r.archetype] || 0) + r.top8;
  }

  const totals = Object.entries(aggregate).map(([k,v]) => {
    const [lid, bid] = k.split('||');
    return ({ archetype: k, leaderId: lid, baseId: bid, top8Appearances: v, top8Rate: v / (numTournaments * 8) });
  });
  totals.sort((a,b) => b.top8Appearances - a.top8Appearances);

  const out = { numTournaments, numParticipants, numRounds, totals, archetypeMap };
  if (targetLeader && targetBase) {
    const avgRank = targetTotalRank / Math.max(1, (numTournaments));
    const matchWinRate = targetMatchTotal > 0 ? (targetMatchWins / targetMatchTotal) : null;
  out.target = { leader: targetLeader, base: targetBase, top8Rate: targetTop8Count / numTournaments, avgRank, matchWinRate };
  }
  return out;
}

// allow running from node
if (require.main === module) {
  (async () => {
    const args = process.argv.slice(2);
    const numT = parseInt(args[0]) || 1000;
    const numP = parseInt(args[1]) || DEFAULT_PARTICIPANTS;
    const numR = parseInt(args[2]) || 6;
    const targetLeader = args[3] || null;
    const targetBase = args[4] || null;
    const meleeId = args[5] || null;

    console.time('sim');
    let out = null;
    try {
      if (meleeId) {
        // fetch participants from the API
        const participants = await fetchMeleeTournament(meleeId);
        const results = [];
        const archetypeMap = {};
        let targetTop8Count = 0, targetTotalRank = 0, targetMatchWins = 0, targetMatchTotal = 0;

        for (let t = 0; t < numT; t++) {
          // initialize players from participants (clone) with ids and names
          const players = participants.map((d, i) => ({ id: i+1, leaderId: d.leaderId, baseId: d.baseId, leaderName: d.leaderName, baseName: d.baseName, score: 0 }));

          for (let r = 1; r <= numR; r++) {
            const pairs = pairSwiss(players, r);
            for (const [pA, pB] of pairs) {
              if (!pB) { pA.score += 3; continue; }
              const p = GetWinProbability(pA.leaderId, pA.baseId, pB.leaderId, pB.baseId);
              const roll = Math.random();
              if (roll < p) pA.score += 3;
              else if (roll < p + (1-p)/2) { pA.score += 1; pB.score += 1; }
              else pB.score += 3;
            }
          }

          players.sort((a,b) => b.score - a.score || a.id - b.id);
          const top = players.slice(0, Math.min(8, players.length));
          const keyCounts = {};
          for (let i = 0; i < players.length; i++) {
            const p = players[i];
            const lid = p.leaderId || p.leaderName;
            const bid = p.baseId || p.baseName;
            const key = `${lid}||${bid}`;
            if (!archetypeMap[key]) archetypeMap[key] = { leaderId: String(lid), baseId: String(bid), leaderName: p.leaderName || String(lid), baseName: p.baseName || String(bid) };
            if (i < 8) keyCounts[key] = (keyCounts[key] || 0) + 1;
            if (targetLeader && targetBase && (p.leaderId === targetLeader || p.leaderName === targetLeader) && (p.baseId === targetBase || p.baseName === targetBase)) targetTotalRank += (i+1);
            if (targetLeader && targetBase && (p.leaderId === targetLeader || p.leaderName === targetLeader) && (p.baseId === targetBase || p.baseName === targetBase)) {
              const wins = Math.round(p.score / 3);
              targetMatchWins += wins; targetMatchTotal += numR;
            }
          }

          for (const k of Object.keys(keyCounts)) results.push({ archetype: k, top8: keyCounts[k] });
          if (targetLeader && targetBase) {
            const foundInTop = top.some(p => ((p.leaderId === targetLeader || p.leaderName === targetLeader) && (p.baseId === targetBase || p.baseName === targetBase)));
            if (foundInTop) targetTop8Count++;
          }
        }

        // aggregate
        const aggregate = {};
        for (const r of results) aggregate[r.archetype] = (aggregate[r.archetype] || 0) + r.top8;
        const totals = Object.entries(aggregate).map(([k,v]) => ({ archetype: k, top8Appearances: v, top8Rate: v / (numT * 8) }));
        totals.sort((a,b) => b.top8Appearances - a.top8Appearances);
        out = { numTournaments: numT, numParticipants: participants.length, numRounds: numR, totals, archetypeMap };
        if (targetLeader && targetBase) {
          out.target = { leader: targetLeader, base: targetBase, top8Rate: targetTop8Count / numT, avgRank: targetTotalRank / Math.max(1, numT), matchWinRate: targetMatchTotal > 0 ? targetMatchWins / targetMatchTotal : null };
        }
      } else {
        out = runManyTournaments(numT, numP, numR, targetLeader, targetBase);
      }
    } catch (e) {
      console.error('error', e && e.stack ? e.stack : e);
      console.timeEnd('sim');
      process.exit(1);
    }
    console.timeEnd('sim');
    console.log(JSON.stringify(out, null, 2));
  })();
}

module.exports = { GenerateRepresentativeMeta, GetWinProbability, simulateSingleTournament, runManyTournaments };
