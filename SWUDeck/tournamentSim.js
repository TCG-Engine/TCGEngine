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

function fetchMetaMatchupStats() {
  const url = '/TCGEngine/APIs/MetaMatchupStatsAPI.php';
  // use hostname without scheme; use HTTPS on port 443
  const options = { hostname: 'swustats.net', port: 443, path: url, method: 'GET' };
  return new Promise((resolve, reject) => {
    const req = https.request(options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          const parsed = JSON.parse(data);
          if (!Array.isArray(parsed)) return reject(new Error('API returned invalid data format'));
          // build pairwise matrix from the meta stats
          const pairwise = buildPairwiseFromMetaStats(parsed);
          // generate representative meta from the stats
          const participants = generateMetaFromStats(parsed);
          resolve({ participants, pairwise });
        } catch (e) {
          reject(e);
        }
      });
    });
    req.on('error', (e) => reject(e));
    req.end();
  });
}

function buildPairwiseFromMetaStats(metaStats, alpha = 1) {
  const counts = {}; // counts[a][b] = { wins, games }
  const probs = {}; // probs[a][b] = { prob, wins, games }
  
  for (const stat of metaStats) {
    const aKey = `${stat.leaderID}||${stat.baseID}`;
    const bKey = `${stat.opponentLeaderID}||${stat.opponentBaseID}`;
    
    const wins = Number(stat.numWins || 0);
    const games = Number(stat.numPlays || 0);
    
    if (games === 0) continue; // skip if no games played
    
    // Initialize if needed
    counts[aKey] = counts[aKey] || {};
    counts[aKey][bKey] = { wins, games };
    
    // Calculate Laplace-smoothed probability
    probs[aKey] = probs[aKey] || {};
    probs[aKey][bKey] = {
      prob: (wins + alpha) / (games + 2 * alpha),
      wins,
      games
    };
  }
  
  return { counts, probs };
}

function generateMetaFromStats(metaStats, numParticipants = DEFAULT_PARTICIPANTS) {
  // Aggregate total games played by each archetype to determine meta share
  const archetypeCounts = {};
  
  for (const stat of metaStats) {
    const aKey = `${stat.leaderID}||${stat.baseID}`;
    const bKey = `${stat.opponentLeaderID}||${stat.opponentBaseID}`;
    
    const games = Number(stat.numPlays || 0);
    archetypeCounts[aKey] = (archetypeCounts[aKey] || 0) + games;
    archetypeCounts[bKey] = (archetypeCounts[bKey] || 0) + games;
  }
  
  // Convert to array and sort by frequency
  const sortedArchetypes = Object.entries(archetypeCounts)
    .sort(([,a], [,b]) => b - a)
    .map(([key, count]) => {
      const [leaderId, baseId] = key.split('||');
      return { leaderId, baseId, count };
    });
  
  // Calculate shares based on total games
  const totalGames = sortedArchetypes.reduce((sum, arch) => sum + arch.count, 0);
  const participants = [];
  
  for (const arch of sortedArchetypes) {
    const share = arch.count / totalGames;
    const count = Math.max(1, Math.round(share * numParticipants));
    
    for (let i = 0; i < count; i++) {
      participants.push({
        leaderId: arch.leaderId,
        baseId: arch.baseId,
        leaderName: arch.leaderId, // Use ID as name for now, could be enhanced with name lookup
        baseName: arch.baseId
      });
    }
    
    if (participants.length >= numParticipants) break;
  }
  
  // Trim to exact number if needed
  participants.length = Math.min(participants.length, numParticipants);
  
  // Fill remaining slots with most popular archetype if needed
  while (participants.length < numParticipants && sortedArchetypes.length > 0) {
    const top = sortedArchetypes[0];
    participants.push({
      leaderId: top.leaderId,
      baseId: top.baseId,
      leaderName: top.leaderId,
      baseName: top.baseId
    });
  }
  
  return participants;
}

function fetchMeleeTournament(meleeId, includeMatchups = false) {
  const url = `/TCGEngine/APIs/GetMeleeTournament.php?id=${encodeURIComponent(meleeId)}` + (includeMatchups ? '&include_matchups=1' : '');
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
          // compute rounds: prefer explicit tournament rounds, otherwise infer from decks' standings
          let rounds = null;
          if (parsed.tournament && parsed.tournament.rounds) rounds = parsed.tournament.rounds;
          else {
            let maxMatches = 0;
            for (const d of parsed.decks) {
              if (d.standings && typeof d.standings.match_wins !== 'undefined') {
                const mw = Number(d.standings.match_wins || 0);
                const ml = Number(d.standings.match_losses || 0);
                const md = Number(d.standings.match_draws || 0);
                const matches = mw + ml + md;
                if (matches > maxMatches) maxMatches = matches;
              } else if (d.standings && d.standings.match_record) {
                const parts = d.standings.match_record.split(/[-:]/).map(x => Number(x));
                const matches = parts.reduce((s,v) => s + (isNaN(v) ? 0 : v), 0);
                if (matches > maxMatches) maxMatches = matches;
              }
            }
            if (maxMatches > 0) rounds = maxMatches;
          }

          const parts = participantsFromApiDecks(parsed.decks);
          if (includeMatchups) {
            const pairwise = buildPairwiseFromMatchups(parsed.decks);
            resolve({ participants: parts, pairwise, rounds });
          } else {
            resolve({ participants: parts, rounds });
          }
        } catch (e) {
          reject(e);
        }
      });
    });
    req.on('error', (e) => reject(e));
    req.end();
  });
}

// Build pairwise win counts/probabilities from API matchups.
// Returns an object { counts, probs } where counts[a][b] = { wins, games }, probs[a][b] = probability A beats B
function buildPairwiseFromMatchups(decks, alpha = 1) {
  // map deck id -> archetype key
  const deckToKey = {};
  for (const d of decks) {
    const lid = (d.leader && d.leader.uuid) ? String(d.leader.uuid) : (d.leaderId || ('L_' + d.id));
    const bid = (d.base && d.base.uuid) ? String(d.base.uuid) : (d.baseId || ('B_' + d.id));
    deckToKey[d.id] = `${lid}||${bid}`;
  }

  const counts = {}; // counts[a][b] = { wins, games }
  for (const d of decks) {
    const aKey = deckToKey[d.id];
    if (!d.matchups || !Array.isArray(d.matchups)) continue;
    for (const mu of d.matchups) {
      const oppId = mu.opponent_id;
      const bKey = deckToKey[oppId];
      if (!bKey) continue; // skip missing opponent
      const wins = Number(mu.wins || 0);
      const losses = Number(mu.losses || 0);
      const draws = Number(mu.draws || 0);
      const games = wins + losses + draws;
      const effectiveWins = wins + 0.5 * draws;
      counts[aKey] = counts[aKey] || {};
      counts[aKey][bKey] = counts[aKey][bKey] || { wins: 0, games: 0 };
      counts[aKey][bKey].wins += effectiveWins;
      counts[aKey][bKey].games += games;
    }
  }

  // convert to probabilities with Laplace smoothing
  const probs = {};
  for (const aKey of Object.keys(counts)) {
    probs[aKey] = {};
    for (const bKey of Object.keys(counts[aKey])) {
      const rec = counts[aKey][bKey];
      const wins = rec.wins;
      const games = rec.games;
      const p = (wins + alpha) / (games + 2 * alpha);
      probs[aKey][bKey] = { prob: p, wins: rec.wins, games: rec.games };
    }
  }
  return { counts, probs };
}

let empiricalMatrix = null;
// Base ID to color mapping for the simulation
const baseIdToColor = {
  // This should be populated when the empirical matrix is set
};

function setEmpiricalMatrix(m) { 
  empiricalMatrix = m;
  // Extract base ID to color mapping from the pairwise data
  if (m && m.probs) {
    for (const rowKey of Object.keys(m.probs)) {
      for (const colKey of Object.keys(m.probs[rowKey])) {
        // colKey format is leaderID||color, extract the color
        const colParts = colKey.split('||');
        if (colParts.length >= 2) {
          const color = colParts[1];
          // Check if this is a known color
          if (['Red', 'Blue', 'Green', 'Yellow'].includes(color)) {
            // Find corresponding base IDs that map to this color by checking rowKey patterns
            // This is a simple heuristic - in practice you'd want a proper mapping
            continue; // Skip for now, we'll handle this in GetWinProbability
          }
        }
      }
    }
  }
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

  // If an empirical matrix was provided, use it when possible.
  const aKey = `${leaderA}||${baseA}`;
  
  if (empiricalMatrix && empiricalMatrix.probs && empiricalMatrix.probs[aKey]) {
    // Try multiple lookup strategies since the pairwise data uses mixed formats
    
    // Strategy 1: Direct lookup with baseB as ID
    const bKeyDirect = `${leaderB}||${baseB}`;
    if (empiricalMatrix.probs[aKey][bKeyDirect]) {
      return empiricalMatrix.probs[aKey][bKeyDirect].prob;
    }
    
    // Strategy 2: Convert baseB to color and try lookup
    // Map base IDs to colors based on common patterns
    const baseToColorMap = {
      '1393827469': 'Red',    // Aggression
      '4028826022': 'Green',  // Command  
      '8327910265': 'Green',  // Command
      '0119018087': 'Yellow', // Cunning
      // Add more mappings as discovered
    };
    
    const colorB = baseToColorMap[baseB];
    if (colorB) {
      const bKeyColor = `${leaderB}||${colorB}`;
      if (empiricalMatrix.probs[aKey][bKeyColor]) {
        return empiricalMatrix.probs[aKey][bKeyColor].prob;
      }
    }
    
    // Strategy 3: Search through all opponents to find a match with the right leader
    for (const opponentKey of Object.keys(empiricalMatrix.probs[aKey])) {
      const opponentParts = opponentKey.split('||');
      if (opponentParts.length >= 2 && opponentParts[0] === leaderB) {
        // Found a matchup against the right leader, use it regardless of base
        return empiricalMatrix.probs[aKey][opponentKey].prob;
      }
    }
  }
  
  // No empirical data for this pair: use flat 50% prior as fallback
  return 0.5;
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
    // per-instance (per player bringing the deck) metrics
    let targetInstanceCount = 0, targetInstanceTop8Count = 0, targetInstanceWins = 0, targetInstanceRankSum = 0, targetInstanceMatchWinsSum = 0, targetInstanceMatchTotal = 0;

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

      // if this player is the target archetype and finished in Top-8, accumulate rank
      if (i < 8 && targetLeader && targetBase && (p.leaderId === targetLeader || p.leaderName === targetLeader) && (p.baseId === targetBase || p.baseName === targetBase)) {
        targetTotalRank += (i + 1); // ranks are 1-based
          // per-instance tracking for the target archetype
          targetInstanceCount += 1;
          if (i < 8) targetInstanceTop8Count += 1;
          if (i === 0) targetInstanceWins += 1;
          targetInstanceRankSum += (i + 1);
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
          targetInstanceMatchWinsSum += wins;
          targetInstanceMatchTotal += numRounds;
      }
    }

    // count top8 occurrences - need to check if target was found in top8
    const foundInTop = targetLeader && targetBase && top.some(p => 
      (p.leaderId === targetLeader || p.leaderName === targetLeader) && 
      (p.baseId === targetBase || p.baseName === targetBase)
    );
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
    // resolve archetype key
    let targetKey = null;
    for (const k of Object.keys(archetypeMap)) {
      const m = archetypeMap[k];
      if (!m) continue;
      const leaderMatches = (m.leaderId === targetLeader || m.leaderName === targetLeader || m.leaderId === String(targetLeader) || m.leaderName === String(targetLeader));
      const baseMatches = (m.baseId === targetBase || m.baseName === targetBase || m.baseId === String(targetBase) || m.baseName === String(targetBase));
      if (leaderMatches && baseMatches) { targetKey = k; break; }
    }
    if (!targetKey) targetKey = `${targetLeader}||${targetBase}`;
    const totalAppearances = aggregate[targetKey] || 0;
    const presenceRate = targetTop8Count / numTournaments;
    const slotShare = totalAppearances / (numTournaments * 8);
    const avgRankPerAppearance = totalAppearances > 0 ? (targetTotalRank / totalAppearances) : null;
    const matchWinRate = targetMatchTotal > 0 ? (targetMatchWins / targetMatchTotal) : null;
    out.target = {
      leader: targetLeader,
      base: targetBase,
      top8PresenceRate: presenceRate,
      top8SlotShare: slotShare,
      avgRankPerAppearance,
      matchWinRate,
      perInstance: {
        totalEntries: targetInstanceCount,
        chanceTop8: targetInstanceCount > 0 ? (targetInstanceTop8Count / targetInstanceCount) : null,
        chanceWin: targetInstanceCount > 0 ? (targetInstanceWins / targetInstanceCount) : null,
        expectedMatchWinRate: targetInstanceMatchTotal > 0 ? (targetInstanceMatchWinsSum / targetInstanceMatchTotal) : null,
        expectedFinishWhenTop8: targetInstanceTop8Count > 0 ? (targetInstanceRankSum / targetInstanceTop8Count) : null
      },
      _resolvedKey: targetKey,
      _totalAppearances: totalAppearances
    };
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
    const dataSource = args[5] || 'meta'; // 'meta' or 'melee'
    const meleeId = args[6] || null;

    console.time('sim');
    let out = null;
    try {
      if (meleeId) {
        // Always fetch participants from the Melee tournament for deck composition
        const fetched = await fetchMeleeTournament(meleeId, dataSource === 'tournament');
        let participants = fetched.participants;
        let pairwise = null;
        
        if (dataSource === 'tournament') {
          // Use tournament-specific matchup data
          pairwise = fetched.pairwise; // { counts, probs }
        } else if (dataSource === 'meta') {
          // Use MetaMatchupStatsAPI for matchup data but keep tournament participants
          try {
            const metaData = await fetchMetaMatchupStats();
            pairwise = metaData.pairwise;
          } catch (e) {
            console.warn('Failed to fetch meta matchup stats, falling back to tournament data:', e.message);
            pairwise = fetched.pairwise; // fallback to tournament data
          }
        }
        
        const fetchedRounds = (typeof fetched.rounds !== 'undefined' && fetched.rounds !== null) ? Number(fetched.rounds) : null;
        if (pairwise) setEmpiricalMatrix(pairwise);
        
        // if rounds were provided by the tournament data, use them but subtract 3 to account for Top-8 elimination rounds
        let roundsToUse;
        if (fetchedRounds !== null) {
          roundsToUse = Math.max(1, fetchedRounds - 3);
        } else {
          roundsToUse = numR;
        }
  const results = [];
  const archetypeMap = {};
  let targetTop8Count = 0, targetTotalRank = 0, targetMatchWins = 0, targetMatchTotal = 0;
  // per-instance counters for individual players bringing the target deck
  let targetInstanceCount = 0, targetInstanceTop8Count = 0, targetInstanceWins = 0, targetInstanceRankSum = 0, targetInstanceMatchWinsSum = 0, targetInstanceMatchTotal = 0;

        for (let t = 0; t < numT; t++) {
          // initialize players from participants (clone) with ids and names
          const players = participants.map((d, i) => ({ id: i+1, leaderId: d.leaderId, baseId: d.baseId, leaderName: d.leaderName, baseName: d.baseName, score: 0 }));

          for (let r = 1; r <= roundsToUse; r++) {
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
            if (targetLeader && targetBase && (p.leaderId === targetLeader || p.leaderName === targetLeader) && (p.baseId === targetBase || p.baseName === targetBase)) {
              // per-tournament aggregate
              if (i < 8) targetTotalRank += (i+1);
              const wins = Math.round(p.score / 3);
              targetMatchWins += wins; targetMatchTotal += roundsToUse;
              // per-instance tracking
              targetInstanceCount += 1;
              if (i < 8) targetInstanceTop8Count += 1;
              if (i === 0) targetInstanceWins += 1;
              targetInstanceRankSum += (i + 1);
              targetInstanceMatchWinsSum += wins;
              targetInstanceMatchTotal += roundsToUse;
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

  // build output and clearer target metrics
  out = { numTournaments: numT, numParticipants: participants.length, numRounds: roundsToUse, totals, archetypeMap };
  if (pairwise) out.pairwise = pairwise;
        if (targetLeader && targetBase) {
          // try to resolve the aggregate key by inspecting archetypeMap (match by id or name)
          let targetKey = null;
          for (const k of Object.keys(archetypeMap)) {
            const m = archetypeMap[k];
            if (!m) continue;
            const leaderMatches = (m.leaderId === targetLeader || m.leaderName === targetLeader || m.leaderId === String(targetLeader) || m.leaderName === String(targetLeader));
            const baseMatches = (m.baseId === targetBase || m.baseName === targetBase || m.baseId === String(targetBase) || m.baseName === String(targetBase));
            if (leaderMatches && baseMatches) { targetKey = k; break; }
          }
          // fallback to naive key if not found
          if (!targetKey) targetKey = `${targetLeader}||${targetBase}`;

          const totalAppearances = aggregate[targetKey] || 0; // total top8 slots occupied by target across all tournaments
          const presenceRate = targetTop8Count / numT; // fraction of tournaments where target had at least one top8
          const slotShare = totalAppearances / (numT * 8); // fraction of all top8 slots occupied by target
          const avgRankPerAppearance = totalAppearances > 0 ? (targetTotalRank / totalAppearances) : null; // average finishing position per appearance
          out.target = {
            leader: targetLeader,
            base: targetBase,
            top8PresenceRate: presenceRate,
            top8SlotShare: slotShare,
            avgRankPerAppearance,
            matchWinRate: targetMatchTotal > 0 ? targetMatchWins / targetMatchTotal : null,
            // per-instance probabilities (per individual copy of the deck)
            perInstance: {
              totalEntries: targetInstanceCount,
              chanceTop8: targetInstanceCount > 0 ? (targetInstanceTop8Count / targetInstanceCount) : null,
              chanceWin: targetInstanceCount > 0 ? (targetInstanceWins / targetInstanceCount) : null,
              expectedMatchWinRate: targetInstanceMatchTotal > 0 ? (targetInstanceMatchWinsSum / targetInstanceMatchTotal) : null,
              expectedFinishWhenTop8: targetInstanceTop8Count > 0 ? (targetInstanceRankSum / targetInstanceTop8Count) : null
            },
            // expose resolved key and totalAppearances for debugging/UI
            _resolvedKey: targetKey,
            _totalAppearances: totalAppearances
          };
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
