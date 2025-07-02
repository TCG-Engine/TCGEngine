<?php
/**
 * API to retrieve Melee tournament data
 * 
 * Possible parameters:
 * - id: Specific tournament ID to retrieve
 * - limit: Maximum number of tournaments to return (default: 50)
 * - offset: Number of tournaments to skip (for pagination)
 * - date_from: Filter tournaments after this date (YYYY-MM-DD)
 * - date_to: Filter tournaments before this date (YYYY-MM-DD)
 * - sort: Sort tournaments by field (default: tournamentDate DESC)
 * - format: Response format (json or html, default: json)
 */

// Set headers for CORS and JSON response
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Include database connection
require_once "../Database/ConnectionManager.php";

// Get database connection
$conn = GetLocalMySQLConnection();

// Initialize parameters with defaults
$tournamentID = isset($_GET['id']) ? (int)$_GET['id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
$sort = isset($_GET['sort']) ? $_GET['sort'] : "tournamentDate DESC";
$format = isset($_GET['format']) && $_GET['format'] === 'html' ? 'html' : 'json';

// Validate and sanitize sorting parameter to prevent SQL injection
$validSortFields = ['tournamentID', 'tournamentName', 'tournamentDate', 'tournamentLink'];
$validSortOrders = ['ASC', 'DESC'];

$sortParts = explode(' ', $sort);
$sortField = $sortParts[0];
$sortOrder = isset($sortParts[1]) ? strtoupper($sortParts[1]) : 'DESC';

if (!in_array($sortField, $validSortFields)) {
    $sortField = 'tournamentDate';
}

if (!in_array($sortOrder, $validSortOrders)) {
    $sortOrder = 'DESC';
}

$sort = "$sortField $sortOrder";

// Build SQL query
$sql = "SELECT * FROM meleetournament WHERE 1=1";

// Add filters if specified
if ($tournamentID !== null) {
    $sql .= " AND tournamentID = $tournamentID";
}

if ($dateFrom !== null) {
    $sql .= " AND tournamentDate >= '$dateFrom'";
}

if ($dateTo !== null) {
    $sql .= " AND tournamentDate <= '$dateTo'";
}

// Add sorting
$sql .= " ORDER BY $sort";

// Add limit and offset
$sql .= " LIMIT $limit OFFSET $offset";

// Execute query
$result = mysqli_query($conn, $sql);

// Check for errors
if (!$result) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    mysqli_close($conn);
    exit();
}

// Fetch all tournaments
$tournaments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tournaments[] = [
        'id' => (int)$row['tournamentID'],
        'name' => $row['tournamentName'],
        'date' => $row['tournamentDate'],
        'link' => (int)$row['tournamentLink'],
        'melee_url' => "https://melee.gg/tournament/" . $row['tournamentLink']
    ];
}

// Count total tournaments (for pagination)
$countSql = "SELECT COUNT(*) as total FROM meleetournament WHERE 1=1";

// Add filters to count query if specified
if ($tournamentID !== null) {
    $countSql .= " AND tournamentID = $tournamentID";
}

if ($dateFrom !== null) {
    $countSql .= " AND tournamentDate >= '$dateFrom'";
}

if ($dateTo !== null) {
    $countSql .= " AND tournamentDate <= '$dateTo'";
}

$countResult = mysqli_query($conn, $countSql);
$totalCount = mysqli_fetch_assoc($countResult)['total'];

// Close database connection
mysqli_close($conn);

// Return response based on format
if ($format === 'html') {
    // Return HTML format
    header("Content-Type: text/html; charset=UTF-8");
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Melee Tournaments</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                line-height: 1.6;
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
            }
            th {
                background-color: #f2f2f2;
            }
            tr:hover {
                background-color: #f5f5f5;
            }
            a {
                color: #0066cc;
                text-decoration: none;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <h1>Melee Tournaments</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tournament Name</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($tournaments as $tournament) {
        echo '<tr>
                <td>' . $tournament['id'] . '</td>
                <td>' . htmlspecialchars($tournament['name']) . '</td>
                <td>' . $tournament['date'] . '</td>
                <td>
                    <!--<a href="' . $tournament['melee_url'] . '" target="_blank">View on Melee.gg</a>-->
                </td>
            </tr>';
    }
    
    echo '</tbody>
        </table>
    </body>
    </html>';
} else {
    // Return JSON format
    echo json_encode([
        "success" => true,
        "total" => (int)$totalCount,
        "count" => count($tournaments),
        "offset" => $offset,
        "limit" => $limit,
        "tournaments" => $tournaments
    ]);
}
?>