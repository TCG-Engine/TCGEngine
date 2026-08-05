// MeleeCharts.js — shared renderers for the melee tournament views.
//
// Included by BOTH Stats/MeleeTournamentResults.php (single tournament) and
// Stats/MeleeTournamentAggregate.php (pooled). The boundary is deliberate:
//   * RENDERERS live here and are shared.
//   * CALCULATORS do not — the single page computes from decks[] client-side, the aggregate
//     page receives equivalent structures from APIs/GetMeleeAggregate.php.
// Both then call identical render functions, which is what stops the two views drifting.
//
// Required DOM containers: #leader-meta-chart, #combo-meta-chart, #leader-performance,
// #archetype-explorer.
//
// Moved verbatim out of MeleeTournamentResults.php — do not edit here without keeping the
// single-tournament page's three-engine harness check green.

// ---- escapeHTML ----------------------------------------------------------------
function escapeHTML(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ---- ARCHETYPE_MIN_MATCHES + archetypeWinRate ----------------------------------
const ARCHETYPE_MIN_MATCHES = 4;
function archetypeWinRate(opponent) {
    if (opponent.matches < ARCHETYPE_MIN_MATCHES) return null;
    const decided = opponent.matchWins + opponent.matchLosses;
    if (decided === 0) return null;
    return (opponent.matchWins / decided) * 100;
}

// ---- renderArchetypeExplorer ---------------------------------------------------
function renderArchetypeExplorer(archetypes) {
    const container = document.getElementById('archetype-explorer');
    const totalDecks = archetypes.reduce((s, a) => s + a.deckCount, 0);

    function cardImg(uuid, alt) {
        if (!uuid) return '';
        return `<img src="../SWUDeck/jpg/concat/${uuid}.jpg" alt="${escapeHTML(alt)}" title="${escapeHTML(alt)}"
                     onerror="this.onerror=null;this.src=swuCardArtUrl('${uuid}', 'tile');">`;
    }

    function renderGallery() {
        container.innerHTML = '';
        if (archetypes.length === 0) {
            container.innerHTML = '<p>No archetype data available.</p>';
            return;
        }
        const grid = document.createElement('div');
        grid.className = 'archetype-grid';
        archetypes.forEach(a => {
            const share = totalDecks ? (a.deckCount / totalDecks * 100).toFixed(1) : '0.0';
            const tile = document.createElement('div');
            tile.className = 'archetype-tile';
            tile.innerHTML =
                `<div class="tile-imgs">${cardImg(a.leaderUuid, a.leaderName)}${cardImg(a.baseUuid, a.baseLabel)}</div>` +
                `<div class="tile-meta">${share}% (${a.deckCount})</div>`;
            tile.title = `${a.leaderName} / ${a.baseLabel}`;
            tile.addEventListener('click', () => renderDetail(a));
            grid.appendChild(tile);
        });
        container.appendChild(grid);
    }

    function renderDetail(a) {
        container.innerHTML = '';

        const head = document.createElement('div');
        head.className = 'archetype-detail-head';
        head.innerHTML =
            `<span class="archetype-back">&larr; All archetypes</span>` +
            `<div class="tile-imgs">${cardImg(a.leaderUuid, a.leaderName)}${cardImg(a.baseUuid, a.baseLabel)}</div>` +
            `<strong>${escapeHTML(a.leaderName)} / ${escapeHTML(a.baseLabel)}</strong>` +
            `<span>${a.deckCount} decks &middot; ${a.totalMatches} matches &middot; ${a.opponents.length} opponents</span>`;
        head.querySelector('.archetype-back').addEventListener('click', renderGallery);
        container.appendChild(head);

        if (a.totalMatches === 0) {
            const p = document.createElement('p');
            p.className = 'archetype-notice';
            p.textContent = 'No recorded matches for this archetype.';
            container.appendChild(p);
            return;
        }

        const strong = a.opponents.filter(o => o.matches >= ARCHETYPE_MIN_MATCHES);
        if (strong.length === 0) {
            const p = document.createElement('p');
            p.className = 'archetype-notice';
            p.textContent = `No opponent reaches ${ARCHETYPE_MIN_MATCHES} matches — not enough games for reliable rates.`;
            container.appendChild(p);
        }

        const table = document.createElement('table');
        table.className = 'archetype-rows';
        table.innerHTML = '<thead><tr><th>Opponent</th><th>Matches</th><th>Win rate</th><th>Record</th></tr></thead>';
        const tbody = document.createElement('tbody');
        let dividerDone = false;

        a.opponents.forEach(o => {
            const rate = archetypeWinRate(o);
            if (rate === null && !dividerDone) {
                dividerDone = true;
                const d = document.createElement('tr');
                d.className = 'archetype-divider';
                d.innerHTML = `<td colspan="4">below ${ARCHETYPE_MIN_MATCHES} matches — win rates not shown</td>`;
                tbody.appendChild(d);
            }
            const tr = document.createElement('tr');
            if (rate === null) tr.classList.add('thin');
            if (o.isMirror) tr.classList.add('mirror');
            // A mirror is 50% by construction — every mirror match is recorded from
            // both sides, contributing one win and one loss to the same archetype — so
            // the figure carries no information and is suppressed. This is deliberately
            // separate from the thin-sample rule: the mirror keeps its sorted position
            // and normal weight rather than being demoted below the divider.
            const showRate = (rate === null || o.isMirror) ? '&mdash;' : rate.toFixed(1) + '%';
            tr.innerHTML =
                `<td><span class="opp-cell">` +
                    `<span class="opp-imgs">${cardImg(o.leaderUuid, o.leaderName)}${cardImg(o.baseUuid, o.baseLabel)}</span>` +
                    `<span>${escapeHTML(o.leaderName)} / ${escapeHTML(o.baseLabel)}</span>` +
                `</span></td>` +
                `<td>${o.matches}</td>` +
                `<td>${showRate}</td>` +
                `<td>${o.matchWins}-${o.matchLosses}-${o.matchDraws}</td>`;
            tbody.appendChild(tr);
        });

        table.appendChild(tbody);
        container.appendChild(table);
    }

    renderGallery();
}

// ---- renderLeaderMetaChart -----------------------------------------------------
function renderLeaderMetaChart(leaderMetaShare) {
    const chartContainer = document.getElementById('leader-meta-chart');
    chartContainer.innerHTML = '';
    
    // Leader art comes from the uuid carried on each entry. This used to be looked up from
    // window.decksData, which coupled a shared renderer to a page-specific global and broke
    // the moment a second page reused it — both producers now supply `uuid` instead.
    const leaderUUIDs = {};
    leaderMetaShare.forEach(l => { if (l.name && l.uuid) leaderUUIDs[l.name] = l.uuid; });

    // Find the maximum count for scaling
    const maxCount = Math.max(...leaderMetaShare.map(leader => leader.count));
    const maxHeight = 150; // Maximum bar height in pixels
    
    // Create a bar for each leader
    leaderMetaShare.forEach(leader => {
        const barHeight = Math.max((leader.count / maxCount) * maxHeight, 20); // Minimum height of 20px
        
        const barContainer = document.createElement('div');
        barContainer.classList.add('meta-bar');
        
        const bar = document.createElement('div');
        bar.classList.add('bar');
        bar.style.height = `${barHeight}px`;
        
        // Generate a distinct color based on leader name
        const hue = Math.abs(leader.name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % 360);
        bar.style.backgroundColor = `hsl(${hue}, 70%, 50%)`;
        
        const barValue = document.createElement('div');
        barValue.classList.add('bar-value');
        barValue.textContent = `${leader.percentage}% (${leader.count})`;
        bar.appendChild(barValue);
        
        // Create label with image if UUID available
        const barLabel = document.createElement('div');
        barLabel.classList.add('bar-label');
        
        const uuid = leaderUUIDs[leader.name];
        if (uuid) {
            // Create image element
            const img = document.createElement('img');
            img.classList.add('leader-img');
            img.style.marginBottom = '5px'; // Add spacing below image
            
            // Try to use JPG version first (faster loading)
            img.src = `../SWUDeck/jpg/concat/${uuid}.jpg`;
            img.alt = leader.name;
            img.title = leader.name;
            
            // If JPG fails, fall back to WebP version
            img.onerror = function() {
                this.src = swuCardArtUrl(uuid, 'tile');
            };
            
            barLabel.appendChild(img);
        } else {
            // Fall back to text if no UUID is available
            barLabel.textContent = leader.name;
        }
        
        barContainer.appendChild(bar);
        barContainer.appendChild(barLabel);
        chartContainer.appendChild(barContainer);
    });
    
    // Show message if no data available
    if (leaderMetaShare.length === 0) {
        chartContainer.innerHTML = '<p>No leader data available.</p>';
    }
}

// ---- renderLeaderComboChart ----------------------------------------------------
function renderLeaderComboChart(comboMetaShare) {
    const chartContainer = document.getElementById('combo-meta-chart');
    chartContainer.innerHTML = '';
    
    // Find the maximum count for scaling
    const maxCount = Math.max(...comboMetaShare.map(combo => combo.count));
    const maxHeight = 150; // Maximum bar height in pixels
    
    // Create a tooltip for hover effects
    const tooltip = document.createElement('div');
    tooltip.classList.add('leader-tooltip');
    document.body.appendChild(tooltip);
    
    // Create a bar for each combo
    comboMetaShare.forEach(combo => {
        const barHeight = Math.max((combo.count / maxCount) * maxHeight, 20); // Minimum height of 20px
        
        const barContainer = document.createElement('div');
        barContainer.classList.add('meta-bar');
        
        const bar = document.createElement('div');
        bar.classList.add('bar');
        bar.style.height = `${barHeight}px`;
        
        // Generate a distinct color based on combo name
        const hue = Math.abs(combo.name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % 360);
        bar.style.backgroundColor = `hsl(${hue}, 70%, 50%)`;
        
        const barValue = document.createElement('div');
        barValue.classList.add('bar-value');
        barValue.textContent = `${combo.percentage}% (${combo.count})`;
        bar.appendChild(barValue);
        
        // Create label with combo images if UUIDs available
        const barLabel = document.createElement('div');
        barLabel.classList.add('bar-label');
        barLabel.style.display = 'flex';
        barLabel.style.flexDirection = 'column';
        barLabel.style.alignItems = 'center';
        
        // Leader and base come straight off the combo object — no string parsing.
        const leaderName = combo.leaderName;
        const baseName = combo.baseLabel;

        // Create leader image
        const leaderUUID = combo.leaderUuid;
        if (leaderUUID) {
            const leaderImg = document.createElement('img');
            leaderImg.classList.add('leader-img');
            leaderImg.style.marginBottom = '5px';
            
            // Try to use JPG version first (faster loading)
            leaderImg.src = `../SWUDeck/jpg/concat/${leaderUUID}.jpg`;
            leaderImg.alt = leaderName;
            leaderImg.title = leaderName;
            
            // If JPG fails, fall back to WebP version
            leaderImg.onerror = function() {
                this.src = swuCardArtUrl(leaderUUID, 'tile');
            };
            
            // Add hover for tooltip
            leaderImg.addEventListener('mouseenter', (e) => {
                tooltip.innerHTML = `
                    <img src="../SWUDeck/jpg/concat/${leaderUUID}.jpg" onerror="this.src='${swuCardArtUrl(leaderUUID, 'tile')}';" alt="${leaderName}">
                    <h4>${leaderName}</h4>
                    <p>Leader</p>
                `;
                tooltip.style.display = 'block';
                updateTooltipPosition(e);
            });
            
            leaderImg.addEventListener('mousemove', updateTooltipPosition);
            
            leaderImg.addEventListener('mouseleave', () => {
                tooltip.style.display = 'none';
            });
            
            barLabel.appendChild(leaderImg);
        } else {
            const leaderText = document.createElement('div');
            leaderText.textContent = leaderName;
            leaderText.style.marginBottom = '5px';
            barLabel.appendChild(leaderText);
        }
        
        // Create base image
        const baseUUID = combo.baseUuid;
        if (baseUUID) {
            const baseImg = document.createElement('img');
            baseImg.classList.add('leader-img');
            
            // Try to use JPG version first
            baseImg.src = `../SWUDeck/jpg/concat/${baseUUID}.jpg`;
            baseImg.alt = baseName;
            baseImg.title = baseName;
            
            // Fall back to WebP if JPG fails
            baseImg.onerror = function() {
                this.src = swuCardArtUrl(baseUUID, 'tile');
            };
            
            // Add hover for tooltip
            baseImg.addEventListener('mouseenter', (e) => {
                tooltip.innerHTML = `
                    <img src="../SWUDeck/jpg/concat/${baseUUID}.jpg" onerror="this.src='${swuCardArtUrl(baseUUID, 'tile')}';" alt="${baseName}">
                    <h4>${baseName}</h4>
                    <p>Base</p>
                `;
                tooltip.style.display = 'block';
                updateTooltipPosition(e);
            });
            
            baseImg.addEventListener('mousemove', updateTooltipPosition);
            
            baseImg.addEventListener('mouseleave', () => {
                tooltip.style.display = 'none';
            });
            
            barLabel.appendChild(baseImg);
        } else {
            const baseText = document.createElement('div');
            baseText.textContent = baseName;
            barLabel.appendChild(baseText);
        }
        
        barContainer.appendChild(bar);
        barContainer.appendChild(barLabel);
        chartContainer.appendChild(barContainer);
    });
    
    // Show message if no data available
    if (comboMetaShare.length === 0) {
        chartContainer.innerHTML = '<p>No leader/base combo data available.</p>';
    }
    
    // Helper function to update tooltip position
    function updateTooltipPosition(e) {
        // Position tooltip relative to mouse cursor
        const x = e.pageX;
        const y = e.pageY;
        
        // Get viewport dimensions
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        
        // Get tooltip dimensions
        const tooltipWidth = tooltip.offsetWidth;
        const tooltipHeight = tooltip.offsetHeight;
        
        // Default position
        let posX = x + 15;
        let posY = y + 15;
        
        // Check if tooltip would go off-screen to the right
        if (posX + tooltipWidth > viewportWidth) {
            posX = x - tooltipWidth - 15;
        }
        
        // Check if tooltip would go off-screen at the bottom
        if (posY + tooltipHeight > viewportHeight) {
            posY = y - tooltipHeight - 15;
        }
        
        // Ensure tooltip doesn't go off-screen to the left or top
        posX = Math.max(10, posX);
        posY = Math.max(10, posY);
        
        // Apply the position
        tooltip.style.left = `${posX}px`;
        tooltip.style.top = `${posY}px`;
    }
}

// ---- renderLeaderPerformanceCards ----------------------------------------------
function renderLeaderPerformanceCards(leaderPerformance) {
    const container = document.getElementById('leader-performance');
    container.innerHTML = '';
    
    // Create a card for each leader, limit to top 8
    leaderPerformance.slice(0, 8).forEach(leader => {
        const card = document.createElement('div');
        card.classList.add('leader-card');
        
        // Calculate color based on win rate (green for high, red for low)
        const winRate = parseFloat(leader.matchWinRate);
        let color;
        if (winRate >= 60) {
            color = 'var(--success)'; // Strong green
        } else if (winRate >= 50) {
            color = 'var(--success)'; // Light green
        } else if (winRate >= 40) {
            color = 'var(--accent-gold)'; // Orange
        } else {
            color = 'var(--danger)'; // Red
        }
        
        card.style.borderLeft = `4px solid ${color}`;
        
        card.innerHTML = `
            <div class="leader-card-name">${escapeHTML(leader.name)}</div>
            <div class="leader-card-stats">
                <div>Win rate: <strong>${leader.matchWinRate}%</strong></div>
                <div>Count: <strong>${leader.count}</strong></div>
                <div>Record: <strong>${leader.matchWins}-${leader.matchLosses}</strong></div>
                <div>Top cut: <strong>${leader.topCut}/${leader.count}</strong></div>
            </div>
        `;
        
        container.appendChild(card);
    });
    
    // Show message if no data available
    if (leaderPerformance.length === 0) {
        container.innerHTML = '<p>No leader performance data available.</p>';
    }
}
