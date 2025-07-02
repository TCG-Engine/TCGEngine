<?php
if(!isset($skipInitialize) || !$skipInitialize) include_once 'Initialize.php';
echo("<script src='/TCGEngine/SoulMastersDB/Custom/Filters.js'></script>");
echo("<script src='/TCGEngine/SoulMastersDB/Custom/ClientActions.js'></script>");
echo("<div class='flex-container' style='margin:0px; padding:0px; display: flex; flex-direction: column; width: 100%; height: 100%;'>");
echo("<div class='flex-item' style='flex-basis: 20px; background-color: #2a2a2a; color: #fff; padding: 0px; display: flex; align-items: left; justify-content: left;'>");
echo("<button style='background-color: #333; color: #fff; border: none; padding: 3px; margin: 5px; border-radius: 5px; font-size: 14px; cursor: pointer;' onmouseover=\"this.style.backgroundColor='#444';\" onmouseout=\"this.style.backgroundColor='#333';\" onclick=\"window.open('./SharedUI/MainMenu.php', '_self')\">");
echo("<img src='./Assets/Images/blueDiamond.png' style='vertical-align: middle; margin-right: 3px; height:16px; width:16px;'>");
echo("<span style='vertical-align: middle;'>Home</span>");
echo("</button>");
echo("<button style='background-color: #333; color: #fff; border: none; padding: 3px; margin: 5px; border-radius: 5px; font-size: 14px; cursor: pointer;' onmouseover=\"this.style.backgroundColor='#444';\" onmouseout=\"this.style.backgroundColor='#333';\" onclick=\"window.open('/TCGEngine/SoulMastersDB/CreatePDF.php?gameName=$gameName', '_blank')\" target='_blank'>");
echo("<span style='vertical-align: middle;'>Print</span>");
echo("</button>");
echo("<div style='padding: 3px; margin: 5px;' id='AssetVisibility'>");
include_once $_SERVER['DOCUMENT_ROOT'] . '/TCGEngine/AccountFiles/AccountSessionAPI.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/TCGEngine/AccountFiles/AccountDatabaseAPI.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/TCGEngine/Database/ConnectionManager.php';
$assetData = LoadAssetData(1, $gameName);
$visibility = $assetData['assetVisibility'];
$patreonId = GetUserPatreonID();
$userData = LoadUserDataFromId(LoggedInUser());
echo("<select id='assetVisibilityDropdown' style='background-color: #333; color: #fff; border: none; border-radius: 5px; font-size: 14px;' onchange=\"UpdateAssetVisibility(this.value, $gameName, 1)\">");
echo("  <option value='private'" . ($visibility == 0 ? " selected" : "") . ">Private</option>");
if(isset($userData['teamID'])) echo("  <option value='team'" . ($visibility == (1000 + $userData['teamID']) ? " selected" : "") . ">Team</option>");
if($patreonId != "") echo("  <option value='$patreonId'" . ($visibility == $patreonId ? " selected" : "") . ">Patreon</option>");
echo("  <option value='link only'" . ($visibility == 1 ? " selected" : "") . ">Link Only</option>");
echo("  <option value='public'" . ($visibility == 2 ? " selected" : "") . ">Public</option>");
echo("</select>");
echo("</div>");
echo("<div style='padding: 3px; margin: 5px;' id='Versions'>");
echo("<select id='versionDropdown' style='background-color: #333; color: #fff; border: none; border-radius: 5px; font-size: 14px;' onchange=\"OnVersionChanged(this.value)\">");
echo("  <option value='current' selected >Current Version</option>");
$versions = &GetVersions($playerID);
for($i=0; $i<count($versions); ++$i) {
  echo("  <option value='" . $i . "'>Version " . $i . "</option>");
}
echo("  <option value='new'>New Version</option>");
echo("</select>");
echo("</div>");
echo("</div>");
echo("<div class='flex-item' style='flex-grow: 1;'>");
echo("<div class='myStuffWrapper' style='position:relative; z-index:10; left:0; top:0; width:100%; height:100%;'><div class='stuffParent'><div id='myStuff' class='stuff myStuff' style='background-image: url(\"/TCGEngine/Assets/Images/soulMastersBackground.jpg\"); background-size: cover;'></div></div></div>
<div id='theirStuff' style='display:none;' class='theirStuff'></div>");
echo("</div>");
echo("</div>");
?>