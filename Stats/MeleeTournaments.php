<?php
include_once "../SharedUI/MenuBar.php";
include_once "../SharedUI/Header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melee Tournaments - SWU Stats</title>
    <style>
        body {
            font-family: 'Barlow', sans-serif;
            line-height: 1.6;
            color: #000;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }
        h1 {
            color: white;
            margin-bottom: 20px;
        }
        .filters {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filters h3 {
            margin-top: 0;
            color: #333;
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        input, select, button {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            padding: 10px;
            transition: background-color 0.2s ease; /* Subtle, quick transition */
        }
        button:hover {
            background-color: #45a049;
        }
        /* Special styling for filter buttons */
        #filterForm button {
            background-color: #4CAF50;
            transform: none; /* No transform effects */
            box-shadow: none; /* No shadow effects */
        }
        #filterForm button:hover {
            background-color: #3d8b40; /* Slightly darker on hover */
        }
        #filterForm button:active {
            background-color: #367d39; /* Even darker when clicked */
            transform: translateY(1px); /* Very slight push down effect */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            color: white; /* Always use white text */
        }
        th {
            background-color: #2c3e50; /* Darker header background for white text */
            font-weight: 600;
            color: white; /* Ensure header text is also white */
        }
        tr:hover {
            background-color: #3e5267; /* Slightly lighter than header but still dark for contrast */
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .pagination button {
            width: auto;
            padding: 8px 15px;
            margin: 0 5px;
        }
        .loading {
            text-align: center;
            padding: 20px;
            font-size: 18px;
            color: #666;
        }
        .no-results {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #666;
        }
        .tournament-link {
            color: #4db5ff; /* Brighter blue for links on dark background */
            text-decoration: none;
        }
        .tournament-link:hover {
            text-decoration: underline;
            color: #79caff; /* Even brighter on hover */
        }
    </style>
</head>
<body>
    <h1>Melee Tournaments</h1>
    
    <div class="filters">
        <h3>Filter Tournaments</h3>
        <form id="melee-link-search-form" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
            <input type="url" id="melee-link-input" placeholder="Paste melee.gg tournament link here" style="flex: 1 1 400px; min-width: 0; color: #000; font-size: 1rem; padding: 10px 8px; height: 44px; display: block;" required>
            <button type="submit" style="flex: 0 0 auto; width: auto; min-width: 120px; font-size: 1rem; padding: 10px 18px; height: 44px; display: block; margin-top:-10px;">Go to Tournament</button>
        </form>
        <form id="filterForm">
            <div class="filter-row">
            <div class="filter-group">
                <label for="date-from" style="color: #000;">Date From:</label>
                <input type="date" id="date-from" name="date_from" style="color: #000;">
            </div>
            <div class="filter-group">
                <label for="date-to" style="color: #000;">Date To:</label>
                <input type="date" id="date-to" name="date_to" style="color: #000;">
            </div>
            <div class="filter-group">
                <label for="sort-by" style="color: #000;">Sort By:</label>
                <select id="sort-by" name="sort" style="color: #000;">
                <option value="tournamentDate DESC">Date (Newest First)</option>
                <option value="tournamentDate ASC">Date (Oldest First)</option>
                <option value="tournamentName ASC">Name (A-Z)</option>
                <option value="tournamentName DESC">Name (Z-A)</option>
                </select>
            </div>
            </div>
            <div class="filter-row">
                <div class="filter-group">
                    <button type="submit">Apply Filters</button>
                </div>
                <div class="filter-group">
                    <button type="button" id="reset-filters">Reset Filters</button>
                </div>
            </div>
        </form>
    </div>
    
    <div id="loading" class="loading">Loading tournaments...</div>
    
    <div id="tournaments-container" style="display: none;">
        <table id="tournaments-table">
            <thead>
                <tr>
                    <th>Tournament Name</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="tournaments-body">
                <!-- Tournament data will be inserted here via JavaScript -->
            </tbody>
        </table>
        
        <div class="pagination">
            <button id="prev-page" disabled>&laquo; Previous</button>
            <button id="next-page" disabled>Next &raquo;</button>
        </div>
        
        <div id="pagination-info" style="text-align: center; margin-top: 10px; color: #666;"></div>
    </div>
    
    <div id="no-results" class="no-results" style="display: none;">
        No tournaments found matching your criteria.
    </div>

    <script>
        // Configuration
        const API_ENDPOINT = '../APIs/GetMeleeTournaments.php';
        let currentPage = 1;
        const itemsPerPage = 20;
        let totalItems = 0;
        
        // Elements
        const filterForm = document.getElementById('filterForm');
        const resetFiltersButton = document.getElementById('reset-filters');
        const tournamentsContainer = document.getElementById('tournaments-container');
        const tournamentsBody = document.getElementById('tournaments-body');
        const loadingElement = document.getElementById('loading');
        const noResultsElement = document.getElementById('no-results');
        const prevPageButton = document.getElementById('prev-page');
        const nextPageButton = document.getElementById('next-page');
        const paginationInfo = document.getElementById('pagination-info');
        
        // Initial load
        document.addEventListener('DOMContentLoaded', () => {
            loadTournaments();
        });
        
        // Event listeners
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            currentPage = 1;
            loadTournaments();
        });
        
        resetFiltersButton.addEventListener('click', () => {
            filterForm.reset();
            currentPage = 1;
            loadTournaments();
        });
        
        prevPageButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                loadTournaments();
            }
        });
        
        nextPageButton.addEventListener('click', () => {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                loadTournaments();
            }
        });
        
        // Load tournaments with current filters and pagination
        function loadTournaments() {
            showLoading();
            
            // Get form data
            const formData = new FormData(filterForm);
            
            // Calculate offset
            const offset = (currentPage - 1) * itemsPerPage;
            
            // Build query string
            let queryParams = new URLSearchParams();
            formData.forEach((value, key) => {
                if (value) queryParams.append(key, value);
            });
            
            queryParams.append('limit', itemsPerPage);
            queryParams.append('offset', offset);
            
            // Fetch data from API
            fetch(`${API_ENDPOINT}?${queryParams.toString()}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    
                    if (data.success) {
                        displayTournaments(data.tournaments);
                        updatePagination(data.total, offset);
                        totalItems = data.total;
                    } else {
                        showError(data.message || 'Failed to load tournaments');
                    }
                })
                .catch(error => {
                    hideLoading();
                    showError('Error: ' + error.message);
                });
        }
        
        // Display tournaments in the table
        function displayTournaments(tournaments) {
            if (!tournaments || tournaments.length === 0) {
                tournamentsContainer.style.display = 'none';
                noResultsElement.style.display = 'block';
                return;
            }
            
            tournamentsBody.innerHTML = '';
            tournaments.forEach(tournament => {
                const row = document.createElement('tr');
                
                // Format date
                const tournamentDate = new Date(tournament.date);
                const formattedDate = tournamentDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                row.innerHTML = `
                    <td>${escapeHTML(tournament.name)}</td>
                    <td>${formattedDate}</td>
                    <td>
                        <!--<a href="${tournament.melee_url}" target="_blank" class="tournament-link">View on Melee.gg</a>
                        <br>-->
                        <a href="MeleeTournamentResults.php?id=${tournament.id}" class="tournament-link">View Results</a>
                    </td>
                `;
                
                tournamentsBody.appendChild(row);
            });
            
            tournamentsContainer.style.display = 'block';
            noResultsElement.style.display = 'none';
        }
        
        // Update pagination controls
        function updatePagination(total, offset) {
            const totalPages = Math.ceil(total / itemsPerPage);
            const currentOffset = offset + 1;
            const endOffset = Math.min(offset + itemsPerPage, total);
            
            paginationInfo.textContent = `Showing ${currentOffset} to ${endOffset} of ${total} tournaments`;
            
            prevPageButton.disabled = currentPage <= 1;
            nextPageButton.disabled = currentPage >= totalPages;
        }
        
        // UI Helper functions
        function showLoading() {
            loadingElement.style.display = 'block';
            tournamentsContainer.style.display = 'none';
            noResultsElement.style.display = 'none';
        }
        
        function hideLoading() {
            loadingElement.style.display = 'none';
        }
        
        function showError(message) {
            console.error(message);
            noResultsElement.textContent = message;
            noResultsElement.style.display = 'block';
            tournamentsContainer.style.display = 'none';
        }
        
        // Security helper
        function escapeHTML(str) {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Melee.gg link search logic
        const meleeLinkForm = document.getElementById('melee-link-search-form');
        const meleeLinkInput = document.getElementById('melee-link-input');

        meleeLinkForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const link = meleeLinkInput.value.trim();
            if (!link) return;
            meleeLinkInput.disabled = true;
            meleeLinkForm.querySelector('button[type="submit"]').disabled = true;
            showLoading();
            fetch('../APIs/FindOrImportMeleeTournament.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ melee_url: link })
            })
            .then(res => res.json())
            .then(data => {
                meleeLinkInput.disabled = false;
                meleeLinkForm.querySelector('button[type="submit"]').disabled = false;
                hideLoading();
                if (data.success && data.tournament_id) {
                    window.location.href = `MeleeTournamentResults.php?id=${data.tournament_id}`;
                } else {
                    showError(data.message || 'Tournament not found or could not be imported.');
                }
            })
            .catch(err => {
                meleeLinkInput.disabled = false;
                meleeLinkForm.querySelector('button[type="submit"]').disabled = false;
                hideLoading();
                showError('Error: ' + err.message);
            });
        });
    </script>
</body>
</html>