<?php

include_once '../SharedUI/MenuBar.php';
include_once '../SharedUI/Header.php';
include_once '../Core/HTTPLibraries.php';
include_once "../Core/UILibraries.php";
include_once '../Database/ConnectionManager.php';
include_once '../SoulMastersDB/GeneratedCode/GeneratedCardDictionaries.php';

$isMobile = IsMobile();

$forIndividual = false;

$conn = GetLocalMySQLConnection();

?>
<form method="get" action="" style="float: left; padding-left: 25px;">
    <div style="display: flex; flex-direction: column; align-items: flex-start;">
        <div style="margin-bottom: 10px;">
            <label for="commander" style="margin-right: 5px;">Commander:</label>
            <select id="commander" name="commander">
            <option value="">-- All Commanders --</option>
            <?php
                $commanderQuery = "SELECT DISTINCT keyIndicator1 FROM ownership WHERE assetType=1 AND assetVisibility=2 AND keyIndicator1 IS NOT NULL";
                $commanderResult = mysqli_query($conn, $commanderQuery);
                while ($commanderRow = mysqli_fetch_assoc($commanderResult)) {
                $currentCommander = htmlspecialchars($commanderRow['keyIndicator1']);
                $selected = (isset($_GET['commander']) && $_GET['commander'] === $commanderRow['keyIndicator1']) ? 'selected' : '';
                $commanderName = CardName($currentCommander);
                if($commanderName == "") continue;
                echo "<option value='{$currentCommander}' {$selected}>{$commanderName}</option>";
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

if (isset($_GET['commander']) && $_GET['commander'] !== '') {
        $commanderFilter = mysqli_real_escape_string($conn, $_GET['commander']);
        $sql .= " AND keyIndicator1 = '{$commanderFilter}'";
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
echo "      <tr><th>Commander</th><th>Asset Name</th><th>Likes</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $assetName = htmlspecialchars($row['assetName']);
    $commander = htmlspecialchars($row['keyIndicator1']);
    $likes = htmlspecialchars($row['numLikes']);
    if($commander == "") continue;
    echo "<tr onclick=\"window.location='https://soulmastersdb.net/TCGEngine/NextTurn.php?gameName=" . $row['assetIdentifier'] . "&playerID=1&folderPath=SoulMastersDB';\" onmouseover=\"this.style.boxShadow='0 0 10px 5px rgba(51, 204, 255, 0.6)'; this.style.transform='scaleY(1.02)';\" onmouseout=\"this.style.boxShadow='none'; this.style.transform='none';\" style='cursor: pointer; transition: all 0.3s ease-in-out;'>";
    echo "    <td><img style='height:80px' src='../SoulMastersDB/concat/" . $commander . ".webp' title='" . CardName($commander) . "' /></td>";
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