<?php

//TODO: Fix all of this to be more general
//TODO: Change counters to be an array of enum:count
//TODO: Overlays should also be an array of enum:count
//TODO: type and stype as implemented here will not work generically (although most games have this concept, there's no way to map it)

//0 Card number = card ID
//1 Counters = number of counters
function ClientRenderedCard($cardNumber, $counters = 0, $cardJSON = "-")
{
  $cardJSON = str_replace(' ', '_', $cardJSON);
  $rv = $cardNumber . " " . $counters . " " . $cardJSON;
  return $rv;
}

function CreatePopup($id, $fromArr, $canClose, $defaultState = 0, $title = "", $arrElements = 1, $customInput = "", $path = "./", $big = false, $overCombatChain = false, $additionalComments = "", $size = 0)
{
  global $darkMode, $cardSize, $playerID;
  $style = "";
  $overCC = 1000;
  $darkMode = IsDarkMode($playerID);
  $top = "40%";
  $left = "calc(25% - 129px)";
  $width = "50%";
  $height = "30%";
  if ($size == 2) {
    $top = "10%";
    $left = "calc(25% - 129px)";
    $width = "50%";
    $height = "80%";
    $overCC = 1001;
  }
  if ($big) {
    $top = "5%";
    $left = "5%";
    $width = "80%";
    $height = "90%";
    $overCC = 1001;
  }
  if ($overCombatChain) {
    $top = "160px";
    $left = "calc(25% - 129px)";
    $width = "auto";
    $height = "auto";
    $overCC = 100;
  }

  // Modals
  $rv = "<div id='" . $id . "' style='overflow-y: auto; background-color:rgba(0, 0, 0, 0.8); backdrop-filter: blur(20px); border-radius: 10px; padding: 10px; font-weight: 500; scrollbar-color: #888888 rgba(0, 0, 0, 0); scrollbar-width: thin; z-index:" . $overCC . "; position: absolute; top:" . $top . "; left:" . $left . "; width:" . $width . "; height:" . $height . ";" . ($defaultState == 0 ? " display:none;" : "") . "'>";

  if ($title != "")
    $rv .= "<h" . ($big ? "1" : "3") . " style=' font-weight: 500; margin-left: 10px; margin-top: 5px; margin-bottom: 15px; text-align: center; user-select: none;'>" . $title . "</h" . ($big ? "1" : "3") . ">";
  if ($canClose == 1)
    $rv .= "<div style='position:absolute; top:0px; right:54px;'><div title='Click to close' style='position: fixed; cursor:pointer; padding: 17px;' onclick='(function(){ document.getElementById(\"" . $id . "\").style.display = \"none\";})();'><img style='width: 20px; height: 20px;' src='./Images/close.png'></div></div>";
  if ($additionalComments != "")
    $rv .= "<h" . ($big ? "3" : "4") . " style='font-weight: 500; margin-left: 10px; margin-top: 5px; margin-bottom: 10px; text-align: center;'>" . $additionalComments . "</h" . ($big ? "3" : "4") . ">";
  for ($i = 0; $i < count($fromArr); $i += $arrElements) {
    $rv .= Card($fromArr[$i], $path . "concat", $cardSize, 0, 1);
  }
  $style = "font-size: 18px; font-weight: 500; margin-left: 10px; line-height: 22px; align-items: center;";
  $rv .= "<div style='" . $style . "'>" . $customInput . "</div>";
  $rv .= "</div>";
  return $rv;
}

function ProcessInputLink($player, $mode, $input, $event = 'onmousedown', $fullRefresh = false, $prompt = "")
{
  global $gameName;

  $jsCode = "SubmitInput(\"" . $mode . "\", \"&buttonInput=" . $input . "\", " . $fullRefresh . ");";
  // If a prompt is given, surround the code with a "confirm()" call
  if ($prompt != "")
    $jsCode = "if (confirm(\"" . $prompt . "\")) { " . $jsCode . " }";

  return " " . $event . "='" . $jsCode . "'";
}

?>