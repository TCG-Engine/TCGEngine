<?php

  use Patreon\API;
  use Patreon\OAuth;

  // Ensure required files are included
  require_once __DIR__ . '/API.php';
  require_once __DIR__ . '/OAuth.php';

  function GetUserPatreonID()
  {
    if(!IsUserLoggedIn()) return "";
    $userName = LoggedInUserName();
    foreach (PatreonCampaign::cases() as $campaign) {
      if($campaign->IsTeamMember($userName)) return $campaign->value;
    }
    return "";
  }

  function IsPatron($campaignID)
  {
    if(!IsUserLoggedIn()) return false;
    $userName = LoggedInUserName();
    $campaign = PatreonCampaign::tryFrom($campaignID);
    if($campaign == null) return false;
    if($campaign->IsTeamMember($userName)) return true;
    if(isset($_SESSION[$campaign->SessionID()])) return true;
    return false;
  }

  function PatreonLoginByUserId($userId)
  {
    require_once __DIR__ . '/../../../Database/ConnectionManager.php';
    
    $conn = GetLocalMySQLConnection();
    $query = $conn->prepare("SELECT patreonAccessToken FROM users WHERE usersId = ?");
    $query->bind_param("i", $userId);
    $query->execute();
    $result = $query->get_result();
    
    if ($result && $result->num_rows > 0) {
      $userRecord = $result->fetch_assoc();
      $patreonAccessToken = $userRecord['patreonAccessToken'];
      
      if ($patreonAccessToken) {
        try {
          PatreonLogin($patreonAccessToken, true, false);
        } catch (\Exception $e) {
          // If patreon validation fails, they're not a patron
        }
      }
    }
    $query->close();
    $conn->close();
  }

function PatreonLogin($access_token, $silent=true, $debugMode=false)
{
  $output = new stdClass();
  $output->patreonCampaigns = [];
  if($access_token == "") return $output;
  if($access_token == "PERMANENT")
  {
    $_SESSION["isPatron"] = true;
    return $output;
  }

  $api_client = new API($access_token);
	$api_client->api_return_format = 'object';

	$patron_response = $api_client->fetch_patron_campaigns();

  if(is_string($patron_response))
  {
    if(!$silent) echo($patron_response);
    return $output;
  }

	$patron = $patron_response->data;

	$relationships = $patron->relationships;
	if(isset($relationships)) $memberships = $relationships->memberships;

  $yourPatronages = [];
  $activeStatus = [];
  $tiers = [];

  if(isset($patron_response->included)) {
    for($i=0; $i<count($patron_response->included); ++$i)
    {
      $_SESSION["patreonAuthenticated"] = true;
      $include = $patron_response->included[$i];
      if($debugMode)
      {
        echo($include->id . " ");
        if($include->attributes && isset($include->attributes->patron_status)) echo($include->attributes->patron_status . " " . $include->relationships->campaign->data->id);
        else if(isset($include->attributes->creation_name)) echo($include->attributes->creation_name);
        echo("<BR>");
      }
      if($include->attributes && isset($include->attributes->patron_status) && isset($include->relationships) && isset($include->relationships->campaign))
      {
        $activeStatus[$include->relationships->campaign->data->id] = $include->attributes->patron_status;
      }
      if($include->attributes && isset($include->relationships) &&  isset($include->relationships->currently_entitled_tiers))
      {
        $tier = $include->relationships->currently_entitled_tiers;
        if(isset($include->relationships->campaign)) {
          $tiers[$include->relationships->campaign->data->id] = $tier;
          if($include->relationships->campaign->data->id == "11987758")
          {
            if($tier == "22632435" || $tier == "22890110") $_SESSION["isWokling"] = true;
          }
        }
      }
      if($include->type == "campaign" && (!isset($activeStatus[$include->id]) || $activeStatus[$include->id] == "former_patron")) continue;
  
      if($include->type == "campaign")
      {
        $campaign = PatreonCampaign::tryFrom($include->id);
        if($campaign != null)
        {
          $_SESSION[$campaign->SessionID()] = true;
          $campaignName = $campaign->CampaignName();
          $yourPatronages[] = $campaignName;
          $output->patreonCampaigns[] = $campaignName;
        }
      }
    }
  } else {
    if (isset($patron_response->data->relationships->memberships->data)) {
      $_SESSION["patreonAuthenticated"] = true;
      foreach ($patron_response->data->relationships->memberships->data as $membership) {
        $campaignID = $membership->relationships->campaign->data->id;
        $patronStatus = $membership->attributes->patron_status;
        $tier = $membership->relationships->currently_entitled_tiers;

        if ($debugMode) {
          echo($campaignID . " ");
          if (isset($patronStatus)) echo($patronStatus . " " . $campaignID);
          echo("<BR>");
        }

        if (isset($patronStatus)) {
          $activeStatus[$campaignID] = $patronStatus;
        }

        if (isset($tier)) {
          $tiers[$campaignID] = $tier;
          if ($campaignID == "11987758") {
            if ($tier == "22632435" || $tier == "22890110") $_SESSION["isWokling"] = true;
          }
        }

        if (!isset($activeStatus[$campaignID]) || $activeStatus[$campaignID] == "former_patron") continue;

        $campaign = PatreonCampaign::tryFrom($campaignID);
        if ($campaign != null) {
          $_SESSION[$campaign->SessionID()] = true;
          $campaignName = $campaign->CampaignName();
          $yourPatronages[] = $campaignName;
          $output->patreonCampaigns[] = $campaignName;
        }
      }
    }
  }
	

  if(!$silent)
  {
    echo("<h1>Your patronages:</h1>");
    for($i=0; $i<count($yourPatronages); ++$i)
    {
      echo("<h2>" . $yourPatronages[$i] . "</h2>");
      if($yourPatronages[$i] == "KTOD" && isset($_SESSION["isWokling"])) {
        echo("<h3>Wokling Tier</h3>");
      }
    }
    echo("<h4>Not seeing something you expect to see?</h4>");
    echo("<h4>1. Check your patreon page to make sure it's listed in your currently supported campaigns</h4>");
    echo("<h4>2. Reach out on our discord server!</h4>");
  }
  return $output;
}

?>
