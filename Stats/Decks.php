<?php

include_once '../SharedUI/MenuBar.php';
include_once '../SharedUI/Header.php';
include_once '../Core/HTTPLibraries.php';
include_once "../Core/UILibraries.php";
include_once '../Database/ConnectionManager.php';
include_once '../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';

$isMobile = IsMobile();

$forIndividual = false;

$conn = GetLocalMySQLConnection();

?>
<form method="get" action="" style="float: left; padding-left: 25px;">
    <div style="display: flex; flex-direction: column; align-items: flex-start;">
        <div style="margin-bottom: 10px;">
            <label for="leader" style="margin-right: 5px;">Leader:</label>
            <select id="leader" name="leader">
                <option value="">-- All Leaders --</option>
                <?php
                    $leaderQuery = "SELECT DISTINCT keyIndicator1 FROM ownership WHERE assetType=1 AND assetVisibility=2 AND keyIndicator1 IS NOT NULL";
                    $leaderResult = mysqli_query($conn, $leaderQuery);
                    while ($leaderRow = mysqli_fetch_assoc($leaderResult)) {
                        $currentLeader = htmlspecialchars($leaderRow['keyIndicator1']);
                        $selected = (isset($_GET['leader']) && $_GET['leader'] === $leaderRow['keyIndicator1']) ? 'selected' : '';
                        $leaderName = CardTitle($currentLeader);
                        $subtitle = CardSubtitle($currentLeader);
                        $leaderName .= $subtitle != "" ? ", " . $subtitle : "";
                        if($leaderName == "" || $leaderName == "Shield" || $leaderName == "Experience") continue;
                        echo "<option value='{$currentLeader}' {$selected}>{$leaderName}</option>";
                    }
                ?>
            </select>
        </div>
        <div style="margin-bottom: 10px;">
            <label for="base" style="margin-right: 5px;">Base:</label>
            <select id="base" name="base">
                <option value="">-- All Bases --</option>
                <?php
                    $baseQuery = "SELECT DISTINCT keyIndicator2 FROM ownership WHERE assetType=1 AND assetVisibility=2 AND keyIndicator2 IS NOT NULL";
                    $baseResult = mysqli_query($conn, $baseQuery);
                    while ($baseRow = mysqli_fetch_assoc($baseResult)) {
                        $currentBase = htmlspecialchars($baseRow['keyIndicator2']);
                        $selected = (isset($_GET['base']) && $_GET['base'] === $baseRow['keyIndicator2']) ? 'selected' : '';
                        if($currentBase == "") continue;
                        $baseName = CardTitle($currentBase);
                        echo "<option value='{$currentBase}' {$selected}>{$baseName}</option>";
                    }
                ?>
            </select>
        </div>
        <div>
            <input type="submit" value="Filter">
        </div>
    </div>
</form>

<?php
$sql = "SELECT * FROM ownership WHERE assetType=1 AND assetVisibility=2";

if (isset($_GET['leader']) && $_GET['leader'] !== '') {
        $leaderFilter = mysqli_real_escape_string($conn, $_GET['leader']);
        $sql .= " AND keyIndicator1 = '{$leaderFilter}'";
}
if (isset($_GET['base']) && $_GET['base'] !== '') {
        $baseFilter = mysqli_real_escape_string($conn, $_GET['base']);
        $sql .= " AND keyIndicator2 = '{$baseFilter}'";
}
$result = mysqli_query($conn, $sql);

echo "<style>
#sciFiScroll::-webkit-scrollbar {
    width: 12px;
}

/* Ensure the track itself has rounded corners */
#sciFiScroll::-webkit-scrollbar-track {
    background: #000022;
    box-shadow: inset 0 0 5px #000;
    border-radius: 8px; /* Ensure rounded edges */
    overflow: hidden; /* Prevents clipping */
}

/* Modify the scrollbar thumb */
#sciFiScroll::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #000066, #000099);
    border-radius: 12px; /* Increase for more rounded effect */
    box-shadow: inset 0 0 5px #000;
}

/* Smooth animation for hover */
#sciFiScroll::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #000099, #000066);
}

/* Optional: Handle the scrollbar corners */
#sciFiScroll::-webkit-scrollbar-corner {
    background: transparent; /* Prevents awkward edges */
}

</style>";
echo "<div style='position: absolute; top: 180px; left: 50%; transform: translate(-50%, 0%);'>";
echo "  <div id='sciFiScroll' style='max-height: calc(100vh - 220px); overflow: auto;'>";
echo "    <table border='0' cellpadding='5' cellspacing='0'>";
echo "      <tr><th>Leader</th><th>Base</th><th>Asset Name</th><th>Likes</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $assetName = htmlspecialchars($row['assetName']);
    $leader = htmlspecialchars($row['keyIndicator1']);
    $base = htmlspecialchars($row['keyIndicator2']);
    $likes = htmlspecialchars($row['numLikes']);
    if($leader == "" || $base == "") continue;
    echo "<tr onclick=\"window.location='https://swustats.net/TCGEngine/NextTurn.php?gameName=" . $row['assetIdentifier'] . "&playerID=1&folderPath=SWUDeck';\" onmouseover=\"this.style.boxShadow='0 0 10px 5px rgba(51, 204, 255, 0.6)'; this.style.transform='scaleY(1.02)';\" onmouseout=\"this.style.boxShadow='none'; this.style.transform='none';\" style='cursor: pointer; transition: all 0.3s ease-in-out;'>";
    echo "    <td><img style='height:80px' src='../SWUDeck/concat/" . $leader . ".webp' title='" . CardTitle($leader) . "' /></td>";
    echo "    <td><img style='height:80px' src='../SWUDeck/concat/" . $base . ".webp' title='" . CardTitle($base) . "' /></td>";
    echo "    <td>{$assetName}</td>";
    echo "    <td>{$likes}</td>";
    echo "</tr>";
}
echo "    </table>";
echo "  </div>";
echo "</div>";


$conn->close();



include_once '../SharedUI/Disclaimer.php';

?>