//Rotate is deprecated
      function Card(cardNumber, folder, maxHeight, action = 0, showHover = 0, overlay = 0, borderColor = 0, counters = 0, actionDataOverride = "", id = "", rotate = 0, lifeCounters = 0, defCounters = 0, atkCounters = 0, controller = 0, restriction = "", isBroken = 0, onChain = 0, isFrozen = 0, gem = 0, landscape = 0, epicActionUsed = 0, heatmapFunction = "", heatmapColorMap = "", mzId = "") {
        if (folder == "crops") {
          cardNumber += "_cropped";
        }
        fileExt = ".png";
        folderPath = folder;
        // Check if asset reflection path function exists to handle file paths
        var folderPath = folder;
        if (typeof AssetReflectionPath === 'function' && AssetReflectionPath() != null) {
          var reflectionPath = AssetReflectionPath();
          // Replace the first part of the path with reflection path
          var parts = folderPath.split('/');
          // Remove the first element from the parts array if it exists
          if (parts.length > 0) {
            parts.shift();
          }
          if (parts.length > 0) {
            parts[0] = reflectionPath;
            folderPath = parts.join('/');
          } else {
            folderPath = reflectionPath;
          }
        }
        folderPath = "./" + folderPath;
        var pathArr = folder.split("/");
        folder = pathArr[pathArr.length - 1];

        if (cardNumber == "ENDSTEP" || cardNumber == "ENDTURN" || cardNumber == "RESUMETURN" || cardNumber == "PHANTASM" || cardNumber == "FINALIZECHAINLINK" || cardNumber == "DEFENDSTEP") {
          showHover = 0;
          borderColor = 0;
        } else if (folder == "concat") {
          fileExt = ".webp";
        } else if (folder == "WebpImages") {
          fileExt = ".webp";
        }
        var actionData = actionDataOverride != "" ? actionDataOverride : cardNumber;
        //Enforce 375x523 aspect ratio as exported (.71)
        margin = "margin:0px;";
        border = "";
        if (borderColor != -1) margin = borderColor > 0 ? "margin:0px;" : "margin:1px;";
        if (folder == "crops") margin = "0px;";

        var rv = "<a style='" + margin + " position:relative; display:inline-block;" + (action > 0 ? "cursor:pointer;" : "") + "'" + (showHover > 0 ? " onmouseover='ShowCardDetail(event, this)' onmouseout='HideCardDetail()'" : "") + (action > 0 ? " onclick='SubmitInput(\"" + action + "\", \"&cardID=" + actionData + "\");'" : "") + ">";

        if (borderColor > 0) {
          border = "border-radius:8px; border:2px solid " + BorderColorMap(borderColor) + ";";
        } else if (folder == "concat") {
          border = "border-radius:8px; border:1px solid transparent;";
        } else {
          border = "border: 1px solid transparent;";
        }

        var orientation = landscape == 1 ? "data-orientation='landscape'" : "";
        if(rotate == 1 || landscape == 1) {
          height = (maxHeight);
          width = (maxHeight * 1.29);
        }
        else if (folder == "crops") {
          height = maxHeight;
          width = (height * 1.29);
        } else if (folder == "concat") {
          height = maxHeight;
          width = maxHeight;
        } else {
          height = maxHeight;
          width = (maxHeight * .71);
        }        //var altText = " alt='" + CardTitle(cardNumber) + "' ";//TODO:Fix screenreader mode
        var altText = " alt='Card' ";
        rv += "<img " + (id != "" ? "id='" + id + "-img' " : "") + altText + orientation + "loading='lazy' style='" + border + " height:" + height + "; width:" + width + "px; position:relative;' src='" + folderPath + "/" + cardNumber + fileExt + "' />";

        if(heatmapFunction != "") {
            var heatmapValue = window[heatmapFunction](cardNumber);
            var overlayColor = "rgba(0, 0, 0, .7)"; // Initialize to gray color
            if (heatmapColorMap == "HigherIsBetter") {
              overlayColor = heatmapValue == -1 ? "rgba(0, 0, 0, .7)" : getOverlayColorHigherIsBetter(heatmapValue);
            } else if (heatmapColorMap == "LowerIsBetter") {
              overlayColor = heatmapValue == -1 ? "rgba(0, 0, 0, .7)" : getOverlayColorLowerIsBetter(heatmapValue);
            }
            var gradientOverlay = heatmapValue == -1 ? "rgba(0, 0, 0, 0.5)" : `linear-gradient(to top, ${overlayColor}, rgba(255, 255, 255, 0))`;
            rv += "<div " + (id != "" ? "id='" + id + "-ovr' " : "") + "style='visibility:visible; width:calc(100% - 2px); height:calc(100% - 2px); top:1px; left:1px; border-radius:6px; position:absolute; background: " + gradientOverlay + "; z-index: 1; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; color: white; text-shadow: 2px 2px 4px black;'>" + (heatmapValue == -1 ? "No Data" : (heatmapValue * 100).toFixed(2) + "%") + "</div>";
        } else {
          rv += "<div " + (id != "" ? "id='" + id + "-ovr' " : "") + "style='visibility:" + (overlay == 1 ? "visible" : "hidden") + "; width:calc(100% - 4px); height:calc(100% - 4px); top:2px; left:2px; border-radius:10px; position:absolute; background: rgba(0, 0, 0, 0.5); z-index: 1;'></div>";
        }

        var darkMode = false;
        counterHeight = 28;
        imgCounterHeight = 42;
        //Attacker Label Style
        if (counters == "Attacker" || counters == "Arsenal") {
          rv += "<div style='margin: 0px; top: 80%; left: 50%; margin-right: -50%; border-radius: 7px; width: fit-content; text-align: center; line-height: 16px; height: 16px; padding: 5px; border: 3px solid " + PopupBorderColor(darkMode) + ";";
          rv += "transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); position:absolute; z-index: 10; background:" + BackgroundColor(darkMode) + "; font-size:20px; font-weight:800; color:" + PopupBorderColor(darkMode) + ";'>" + counters + "</div>";
        }
        //Equipments, Hero and default counters style
        else if (counters != 0) {
          //var left = "72%";
          //if (lifeCounters == 0 && defCounters == 0 && atkCounters == 0) {
          //  left = "50%";
          //
          //rv += "<div style='margin: 0px; top: 50%; left:" + left + "; margin-right: -50%; border-radius: 50%; width:" + counterHeight + "px; height:" + counterHeight + "px; padding: 5px; border: 3px solid " + PopupBorderColor(darkMode) + "; text-align: center; line-height:" + imgCounterHeight / 1.5 + "px;";
          //rv += "transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); position:absolute; z-index: 10; background:" + BackgroundColor(darkMode) + "; font-family: Helvetica; font-size:" + (counterHeight - 2) + "px; font-weight:550; color:" + TextCounterColor(darkMode) + "; text-shadow: 2px 0 0 " + PopupBorderColor(darkMode) + ", 0 -2px 0 " + PopupBorderColor(darkMode) + ", 0 2px 0 " + PopupBorderColor(darkMode) + ", -2px 0 0 " + PopupBorderColor(darkMode) + ";'>" + counters + "</div>";
            left = "50%";
            rv += "<div class='counter-bubble' style='margin: 0px; top: 85%; left:" + left + "; margin-right: -50%; width: " + counterHeight + "px; height: " + counterHeight + "px; border-radius: 50%; border: 3px solid " + PopupBorderColor(darkMode) + "; text-align: center; line-height:" + imgCounterHeight / 1.5 + "px; cursor: pointer;";
            rv += "transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); position:absolute; z-index: 10; background: radial-gradient(circle, rgba(64,64,64,1) 40%, rgba(142,142,142,1) 100%); font-family: 'Orbitron', sans-serif; font-size:" + (counterHeight - 2) + "px; font-weight:700; color:" + TextCounterColor(darkMode) + "; text-shadow: 0 0 5px " + PopupBorderColor(darkMode) + ", 0 0 10px " + PopupBorderColor(darkMode) + ";' onclick='event.stopPropagation(); ShowZonePopup(\"" + mzId + "\");'>" + counters + "</div>";
        }
        //-1 Defense & Endurance Counters style
        if (defCounters != 0 && isBroken != 1) {
          var left = "-42%";
          if (lifeCounters == 0 && counters == 0) {
            left = "0px";
          }
          rv += "<div style=' position:absolute; margin: auto; top: 0; left:" + left + "; right: 0; bottom: 0;width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; display: flex;justify-content: center; z-index: 5; text-align: center; vertical-align: middle; line-height:" + imgCounterHeight + "px;";
          rv += "font-size:" + (imgCounterHeight - 17) + "px; font-weight: 600;  color: #EEE; text-shadow: 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000, -2px 0 0 #000;'>" + defCounters + "<img style='position:absolute; top: -2px; width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; opacity: 0.9; z-index:-1;' src='./Images/Defense.png'></div>";
        }

        //Health Counters style
        if (lifeCounters != 0) {
          var left = "45%";
          if (defCounters == 0 && atkCounters == 0) {
            left = "0px";
          }
          rv += "<div style=' position:absolute; margin: auto; top: 0; left:" + left + "; right: 0; bottom: 0;width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; display: flex; justify-content: center; z-index: 5; text-align: center; vertical-align: middle; line-height:" + imgCounterHeight + "px;";
          rv += "font-size:" + (imgCounterHeight - 17) + "+px; font-weight: 600;  color: #EEE; text-shadow: 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000, -2px 0 0 #000;'>" + lifeCounters + "<img style='position:absolute; top: -2px; width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; opacity: 0.9; z-index:-1;' src='./Images/Life.png'></div>";
        }

        //Attack Counters style
        if (atkCounters != 0) {
          var left = "-45%";
          if (lifeCounters == 0 && counters == 0) {
            left = "0px";
          }
          rv += "<div style=' position:absolute; margin: auto; top: 0; left:" + left + "; right: 0; bottom: 0;width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; display: flex; justify-content: center; z-index: 5; text-align: center; vertical-align: middle; line-height:" + imgCounterHeight + "px;";
          rv += "font-size:" + (imgCounterHeight - 17) + "px; font-weight: 600;  color: #EEE; text-shadow: 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000, -2px 0 0 #000;'>" + atkCounters + "<img style='position:absolute; top: -2px; width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; opacity: 0.9; z-index:-1;' src='./Images/AttackIcon.png'></div>";
        }

        if (restriction != "") {
          //$restrictionName = CardName($restriction);
          rv += "<img title='Restricted by: " + restriction + "' style='position:absolute; z-index:100; top:26px; left:26px;' src='./Images/restricted.png' />";
        }
        if (epicActionUsed == 1) rv += "<img title='Epic Action Used' style='position:absolute; z-index:100; border-radius:5px; top: -3px; right: -2px; height:26px; width:26px; filter:drop-shadow(1px 1px 1px rgba(0, 0, 0, 0.50));' src='./Images/ExhaustToken.png' />";
        rv += "</a>";
        /*
        if (gem != 0) {
          var playerID = <?php echo ($playerID); ?>;
           //Note: 96 = Card Size
          var cardWidth = 96;
          gemImg = (gem == 1 ? "hexagonRedGem.png" : "hexagonGrayGem.png");
          if (gem == 1) rv += "<img " + ProcessInputLink(playerID, 102, actionDataOverride) + " title='Effect Active' style='position:absolute; z-index:1001; bottom:3px; left:" + (cardWidth / 2 - 18) + "px; width:40px; height:40px; cursor:pointer;' src='./Images/" + gemImg + "' />";
          else if (gem == 2) rv += "<img " + ProcessInputLink(playerID, 102, actionDataOverride) + " title='Effect Inactive' style='position:absolute; z-index:1001; bottom:3px; left:" + (cardWidth / 2 - 18) + "px; width:40px; height:40px; cursor:pointer;' src='./Images/" + gemImg + "' />";
        }
          */
        return rv;
      }

      function getOverlayColorHigherIsBetter(value) {
        if (value < 0.2) return "rgba(255, 0, 0, .7)"; // Very red
        if (value > 0.8) return "rgba(0, 255, 0, .7)"; // Very green
        var red = 255 - Math.round((value - 0.2) * 255 / 0.6);
        var green = Math.round((value - 0.2) * 255 / 0.6);
        return `rgba(${red}, ${green}, 0, .7)`;
      }

      function getOverlayColorLowerIsBetter(value) {
        if (value < 0.2) return "rgba(0, 255, 0, .7)"; // Very green
        if (value > 0.8) return "rgba(255, 0, 0, .7)"; // Very red
        var green = 255 - Math.round((value - 0.2) * 255 / 0.6);
        var red = Math.round((value - 0.2) * 255 / 0.6);
        return `rgba(${red}, ${green}, 0, .7)`;
      }


      function Hotkeys(event) {
        //if (event.keyCode === 32) { if(document.getElementById("passConfirm").innerText == "false" || confirm("Do you want to skip arsenal?")) SubmitInput(99, ""); } //Space = pass
        if(window.rootPath == './RBSim' || window.rootPath == './GudnakSim' || window.rootPath == './GrandArchiveSim') {
          if (event.keyCode === 83) SubmitInput(10005, ""); //S = Save snapshot
          if (event.keyCode === 85) SubmitInput(10004, ""); //U = Undo
        }
        //if (event.keyCode === 104) SubmitInput(3, "&cardID=0"); //H = hero ability
        //if (event.keyCode === 109) TogglePopup("menuPopup"); //M = open menu
        //TODO: Add schema file to define hotkeys
      }

      function ProcessInputLink(player, mode, input, event = 'onmousedown', fullRefresh = false) {
        return " " + event + "='SubmitInput(\"" + mode + "\", \"&buttonInput=" + input + "\", " + fullRefresh + ");'";
      }

      function BackgroundColor(darkMode) {
        if (darkMode) return "rgba(74, 74, 74, 0.9)";
        else return "rgba(235, 235, 235, 0.9)";
      }

      function PopupBorderColor(darkMode) {
        if (darkMode) return "#DDD";
        else return "#1a1a1a";
      }

      function TextCounterColor(darkMode) {
        if (darkMode) return "#1a1a1a";
        else return "#EDEDED";
      }

      // Function to handle drag start event
      function dragStart(e) {
          // Set the drag's data and styling
          var id = e.target.id;
          var element = e.target;
          var tries = 0;
          while(id == "" && tries < 20) {
            element = element.parentNode;
            id = element.id;
            ++tries;
          }
          e.dataTransfer.setData("text/plain", id);
          e.target.style.opacity = "0.4";
          HideCardDetail();
          //Now show the droppable areas
          generatedDragStart();
      }

      // Function to handle drag end event
      function dragEnd(e) {
          // Reset the element's opacity after drag
          e.target.style.opacity = "1";
          //Now hide the droppable areas
          generatedDragEnd();
      }

      // Function to handle drag over event
      function dragOver(e) {
          e.preventDefault(); // Allow drop
      }

      // Function to handle drop event
      function drop(e) {
          e.preventDefault(); // Prevent default action (open as link for some elements)
          var el = e.target;
          var destination = el.id;
          var tries = 0;
          while((destination == "" || destination.includes("-")) && tries < 20) {
            el = el.parentNode;
            destination = el.id;
            ++tries;
          }

          // Get the card being dragged
          var draggedCard = e.dataTransfer.getData("text/plain");

          // Send the action input to the server
          SubmitInput("10014", "&cardID=" + draggedCard + "!" + destination);
      }

      function BorderColorMap(code) {
        code = parseInt(code);
        switch (code) {
          case 1:
            return "DeepSkyBlue";
          case 2:
            return "red";
          case 3:
            return "yellow";
          case 4:
            return "Gray";
          case 5:
            return "Tan";
          case 6:
            return "#00FF66";
          case 7:
            return "Orchid";
          default:
            return "Black";
        }
      }

      // Split a filter string on " or " at the top level (not inside parentheses)
      function _splitOnTopLevelOr(filter) {
        var parts = [];
        var depth = 0;
        var start = 0;
        for(var i = 0; i < filter.length; i++) {
          if(filter[i] === '(') depth++;
          else if(filter[i] === ')') depth--;
          else if(depth === 0 && filter.slice(i, i+4) === ' or ') {
            parts.push(filter.slice(start, i).trim());
            start = i + 4;
            i += 3;
          }
        }
        parts.push(filter.slice(start).trim());
        return parts;
      }

      // Split a filter string on spaces at the top level (not inside parentheses or double quotes)
      function _splitOnTopLevelSpaces(filter) {
        var tokens = [];
        var depth = 0;
        var inQuote = false;
        var start = 0;
        for(var i = 0; i < filter.length; i++) {
          if(filter[i] === '"') { inQuote = !inQuote; }
          else if(!inQuote && filter[i] === '(') depth++;
          else if(!inQuote && filter[i] === ')') depth--;
          else if(!inQuote && depth === 0 && filter[i] === ' ') {
            var tok = filter.slice(start, i).trim();
            if(tok.length > 0) tokens.push(tok);
            start = i + 1;
          }
        }
        var tok = filter.slice(start).trim();
        if(tok.length > 0) tokens.push(tok);
        return tokens;
      }

      // Returns true if the card should be filtered OUT (hidden) given a full filter string
      // Supports: space = AND, " or " = OR, (parens) for grouping
      function ShouldFilterWithOr(cardID, filter) {
        filter = filter.trim();
        if(filter === '') return false;
        var orGroups = _splitOnTopLevelOr(filter);
        for(var g = 0; g < orGroups.length; g++) {
          var group = orGroups[g].trim();
          if(group === '') continue;
          // Evaluate this AND-group: all tokens must pass (not filter the card)
          var tokens = _splitOnTopLevelSpaces(group);
          var groupFails = false;
          for(var t = 0; t < tokens.length; t++) {
            var tok = tokens[t].trim();
            if(tok === '') continue;
            var filtered;
            if(tok[0] === '(' && tok[tok.length-1] === ')') {
              // Parenthesised sub-expression — recurse
              filtered = ShouldFilterWithOr(cardID, tok.slice(1, tok.length-1));
            } else {
              filtered = ShouldFilter(cardID, tok);
            }
            if(filtered) { groupFails = true; break; }
          }
          if(!groupFails) return false; // At least one OR-group matched: show the card
        }
        return true; // All OR-groups filtered the card: hide it
      }

      //Note: 96 = Card Size
      function PopulateZone(zone, zoneData, size = 96, folder = "concat", row = 1, mode = 'All', filter="") {
          // Skip rendering if zone visibility is None
          var zoneMeta = GetZoneData(zone);
          if(zoneMeta && zoneMeta.Display && zoneMeta.Display.toLowerCase() === 'none') {
            return "";
          }
          zoneData = zoneData.trim();
          var dragProps = mode != "Panel" ? "ondragover='dragOver(event)' ondrop='drop(event)' " : "";
          var newHTML = "<span id='" + zone + "' " + dragProps + "style='display: flex; flex-wrap: wrap; justify-content: center;'>";
          var zoneArr = (zoneData.length == 0 ? [] : zoneData.split("<|>"));
          var zoneName = zone.replace("my", "").replace("their", "");

          // Handle Single display mode - only render one card (first or last based on Reverse)
          if(mode == 'Single' && zoneArr.length > 0) {
            var zoneMetadata = GetZoneData(zoneName);
            var useReverse = zoneMetadata.Sort && zoneMetadata.Sort.Reverse;
            var displayIndex = useReverse ? (zoneArr.length - 1) : 0;
            var cardArr = zoneArr[displayIndex].split(" ");

            // Override counter to show total zone count (cardArr[1] is normally counter data)
            // This replicates the count bubble that was previously shown
            if(zoneArr.length > 1) {
              cardArr[1] = String(zoneArr.length);
            }

            var heatmapFunction = "";
            var heatmapColorMap = "";
            // Apply heatmaps if defined
            var heatmaps = zoneMetadata.Heatmaps;
            if(heatmaps != null) {
              for(var i = 0; i < heatmaps.length; ++i) {
                var heatmapProperty = window[heatmaps[i].Property + "Data"];
                var heatmapFunctionMap = JSON.parse(heatmaps[i].FunctionMap);
                heatmapFunction = heatmapProperty && heatmapFunctionMap[heatmapProperty] ? heatmapFunctionMap[heatmapProperty].Function : "";
                heatmapColorMap = heatmapProperty && heatmapFunctionMap[heatmapProperty] ? heatmapFunctionMap[heatmapProperty].ColorMap : "";
              }
            }
            newHTML += createCardHTML(zone, zoneName, folder, size, cardArr, displayIndex, heatmapFunction, heatmapColorMap);
            newHTML += "</span>";
            return newHTML;
          }

          if(zoneArr.length == 0 && mode != "Calculate") {
            newHTML += "<span style='margin: 1px;'>" + zoneName + "</span>";
          } else if(mode == 'Count') {
            var id = zone + "-0";
            var buttons = createWidgetButtons(zoneName, id);
            newHTML += "<span style='margin: 1px;'>" + zoneName + " Count: " + zoneData + " " + buttons.middleButtons + "</span>";
          } else if(mode == 'Value') {
            newHTML += "<span style='margin: 1px; display: flex; align-items: center; padding-right: 5px;'>" + zoneName + ": " + zoneData + "</span>";
            var id = zone + "-0";
            var buttons = createWidgetButtons(zoneName, id);
            newHTML += "<div style='display: flex; justify-content: center; align-items: center; padding-left: 5px;'>" + buttons.middleButtons + "</div>";
          } else if(mode == 'Radio') {
            newHTML += "<span style='margin: 1px; display: flex; align-items: center; padding-right: 5px;'>" + zoneName + ":</span>";
            var id = zone + "-0";
            var buttons = createWidgetButtons(zoneName, id, "-", zoneData);
            newHTML += "<div style='display: flex; justify-content: center; align-items: center; padding-left: 5px;flex-wrap: wrap; gap:0.5rem 0;'>" + buttons.middleButtons + "</div>";
          } else if(mode == 'Panel') {
            var id = zone;
            newHTML += "div id='" + id + "' style='display: flex; flex-wrap: wrap; justify-content: center;'></div>";
          } else if(mode == "Calculate") {
            var zoneData = GetZoneData(zoneName);
            var functionName = zoneData.DisplayParameters.length > 0 ? zoneData.DisplayParameters[0] : "";
            var id = zone + "-0";
            var buttons = createWidgetButtons(zoneName, id);
            if (typeof window[functionName] === 'function') {
              newHTML += window[functionName]();
            }
            newHTML += buttons.middleButtons;
          } else {
            var tiledCardArr = [];
            var zoneMetadata = GetZoneData(zoneName);
            var filters = zoneMetadata.Filters;
            var heatmaps = zoneMetadata.Heatmaps;
            var sortProperty = (zoneMetadata.Sort && zoneMetadata.Sort.Property) ? zoneMetadata.Sort.Property : "";
            var sortFunction = sortProperty != "" ? "Card" + window[sortProperty + "Data"] : null;
            if(sortFunction != null && typeof window[sortFunction] !== 'function') {
              var sortFunction = sortProperty != "" ? "Card" + window[sortProperty + "Data"].toLowerCase() : null;
            }
            var heatmapFunction = "";
            var heatmapColorMap = "";
            if(heatmaps != null) {
              for(var i = 0; i < heatmaps.length; ++i) {
                var heatmapProperty = window[heatmaps[i].Property + "Data"];
                var heatmapFunctionMap = JSON.parse(heatmaps[i].FunctionMap);
                heatmapFunction = heatmapProperty && heatmapFunctionMap[heatmapProperty] ? heatmapFunctionMap[heatmapProperty].Function : "";
                heatmapColorMap = heatmapProperty && heatmapFunctionMap[heatmapProperty] ? heatmapFunctionMap[heatmapProperty].ColorMap : "";
              }
            }
            var filterFunction = null;
            if (!!filters && filters.length > 0) filterFunction = window[filters[0]];
            // Reverse the zone array if Sort.Reverse is true (for all modes except Tile, which reverses after sorting)
            if(mode != "Tile" && zoneMetadata.Sort && zoneMetadata.Sort.Reverse) {
              zoneArr.reverse();
            }
            // Pre-collect CardIDs that appear as Subcards of other cards in this zone.
            // Such cards are rendered inline as subcard thumbnails and should be hidden
            // from standalone rendering (e.g. Ally Link Phantasia cards linked to an ally).
            // Safety: only skip if the CardID is also a standalone entry in this same zone
            // (prevents hiding cards whose CardID just happens to match a lineage history entry).
            var linkedSubcardCardIDs = {};
            var standaloneCardIDs = {};
            for (var _si = 0; _si < zoneArr.length; ++_si) {
              var _tempArr = zoneArr[_si].split(" ");
              standaloneCardIDs[_tempArr[0]] = true;
            }
            for (var _si = 0; _si < zoneArr.length; ++_si) {
              var _tempArr = zoneArr[_si].split(" ");
              if (_tempArr.length > 2 && _tempArr[2] && _tempArr[2] !== '-') {
                try {
                  var _tempData = JSON.parse(_tempArr[2]);
                  if (_tempData.Subcards && Array.isArray(_tempData.Subcards)) {
                    _tempData.Subcards.forEach(function(sid) {
                      if (sid && typeof sid === 'string' && standaloneCardIDs[sid]) {
                        linkedSubcardCardIDs[sid] = true;
                      }
                    });
                  }
                } catch (e) {}
              }
            }
            for (var i = 0; i < zoneArr.length; ++i) {
              cardArr = zoneArr[i].split(" ");
              if(filter != "") {
                if(ShouldFilterWithOr(cardArr[0], filter)) continue;
              }
              if(filterFunction != null && window.customFilter && filterFunction(cardArr[0])) continue;
              if(filterFunction != null && typeof window.InLegalFilter === 'function' && window.legalFilter && window.InLegalFilter(cardArr[0])) continue;
              if(linkedSubcardCardIDs[cardArr[0]]) continue; // skip cards rendered inline as subcards
              if(mode == "Tile") {
                var cardObject = {
                  id: cardArr[0],
                  numCounters: cardArr[1],
                  cardJson: cardArr[2],
                  quantity: 1,
                  index: i
                };
                var existingCard = tiledCardArr.find(card => card.id === cardObject.id);
                if(existingCard) {
                  existingCard.quantity += 1;
                } else {
                  tiledCardArr.push(cardObject);
                }
              } else {
                newHTML += createCardHTML(zone, zoneName, folder, size, cardArr, i, heatmapFunction, heatmapColorMap);
              }
            }
            if(mode == "Tile") {
                if(sortFunction != null) {
                  if (typeof window[sortFunction] === 'function') {
                    tiledCardArr.sort((a, b) => {
                    const idA = a.id;
                    const idB = b.id;
                    const valueA = window[sortFunction](idA);
                    const valueB = window[sortFunction](idB);
                    if (typeof valueA === 'string' && typeof valueB === 'string') {
                      return valueA.localeCompare(valueB);
                    }
                    return valueA - valueB;
                    });
                  }
                }
                if(zoneMetadata.Sort && zoneMetadata.Sort.Reverse) {
                  tiledCardArr.reverse();
                }
              tiledCardArr.forEach((cardObject) => {
                newHTML += createCardHTML(zone, zoneName, folder, size, [cardObject.id, cardObject.quantity > 1 ? cardObject.quantity : 0, cardObject.cardJson], cardObject.index, heatmapFunction, heatmapColorMap);
              });
            }
          }
          newHTML += "</span>";
          return newHTML;
      }

      function CardClick(event, zoneName, cardId) {
        event.stopPropagation(); // Prevent the click event from bubbling up
        var clickActions = GetZoneClickActions(zoneName);
        if(clickActions.length == 1) {
            SubmitInput(10002, "&cardID=" + cardId + "!" + clickActions[0].Action + "!" + clickActions[0].Parameters.join(","));
        }
      }

      // Handler for selectable cards. Inline markup calls this when a card is marked selectable.
      // If selection mode is active, invoke the configured callback. Otherwise delegate to CardClick.
      function OnSelectableCardClick(zoneName, cardId) {
        try {
          // If selection mode is active, call the selection callback (used for decision queue selections)
          if (window.SelectionMode && window.SelectionMode.active) {
            if (typeof window.SelectionMode.callback === 'function') {
              window.SelectionMode.callback(zoneName, cardId, window.SelectionMode.decisionIndex);
            }
            // Clear selection UI/state after making a selection
            ClearSelectionMode();
            return;
          }

          // Not in selection mode: delegate to CardClick. CardClick expects an event object, so provide a minimal stub.
          var fakeEvent = { stopPropagation: function() {} };
          CardClick(fakeEvent, zoneName, cardId);
        } catch (e) {
          // Swallow errors to avoid breaking UI; log to console for debugging
          if (console && console.error) console.error('OnSelectableCardClick error', e);
        }
      }

      function createCardHTML(zone, zoneName, folder, size, cardArr, i, heatmapFunction = "", heatmapColorMap = "") {
        let isSelectable = false;
        if (window.SelectionMode.active && typeof IsSelectableCard === 'function') {
          isSelectable = IsSelectableCard(zone, cardArr, i);
        }
        var sharedCardData = {};
        if (cardArr.length > 2 && cardArr[2] && cardArr[2] !== '-') {
          try { sharedCardData = JSON.parse(cardArr[2]); } catch (e) {}
        }

        // Check if this card should be highlighted based on HighlightRules
        // Only apply highlighting for the turn player
        let highlightMetadata = null;
        try {
          // Check if viewer is the turn player
          const turnVal = typeof window.TurnPlayerData !== 'undefined' ? parseInt(window.TurnPlayerData) : NaN;
          const viewerVal = (document.getElementById('playerID') && document.getElementById('playerID').value) ? parseInt(document.getElementById('playerID').value) : NaN;
          const viewerIsTurnPlayer = !isNaN(turnVal) && !isNaN(viewerVal) && viewerVal === turnVal;

          if (viewerIsTurnPlayer && typeof HighlightRules !== 'undefined' && HighlightRules[zoneName]) {
            const highlightProperty = HighlightRules[zoneName];
            var cardData = sharedCardData;
            // Check if the card has the highlight property and it's truthy
            if (cardData.hasOwnProperty(highlightProperty) && cardData[highlightProperty]) {
              let rawValue = cardData[highlightProperty];
              // If the value is a string (nested JSON), parse it
              if (typeof rawValue === 'string') {
                try {
                  rawValue = JSON.parse(rawValue);
                } catch (e) {
                  // If parsing fails, skip highlighting
                  rawValue = null;
                }
              }

              // Check if we have valid highlight metadata with a color property
              if (rawValue && typeof rawValue === 'object' && rawValue.color) {
                highlightMetadata = rawValue;
                isSelectable = true;
              }
            }
          }
        } catch (e) {
          if (console && console.error) console.error('Highlight check error', e);
        }

        var newHTML = "";
        var id = zone + "-" + i;
        var positionStyle = "relative";
        var className = isSelectable ? "selectable-card" : "";
        var combatIndicatorClass = (zoneName === "Field" && sharedCardData.CombatTargetIndicator) ? " combat-targeted-card" : "";
        var combatIndicatorText = (zoneName === "Field" && sharedCardData.CombatTargetIndicator) ? sharedCardData.CombatTargetIndicator : "";

        // Build inline styles - combine position and custom color variable
        var inlineStyles = "position:" + positionStyle + "; margin:1px;";
        if (isSelectable) {
          // If selectable, always set the highlight color (custom or default)
          if (highlightMetadata && highlightMetadata.color) {
            // Clean up the color string - replace underscores with spaces (serialization artifact)
            var cleanColor = highlightMetadata.color.replace(/_/g, ' ');
            // Add custom CSS variable for the custom color
            inlineStyles += " --highlight-color: " + cleanColor + ";";
          } else {
            // Set default green color for selectable cards without custom color
            inlineStyles += " --highlight-color: rgba(100,250,0,0.50);";
          }
        }

        var styles = " style='" + inlineStyles + "'";
        var droppable = " class='draggable " + className + combatIndicatorClass + "' draggable='true' ondragstart='dragStart(event)' ondragend='dragEnd(event)'";
        var click = isSelectable
          ? " onclick=\"OnSelectableCardClick('" + zoneName + "', '" + id + "')\""
          : " onclick=\"CardClick(event, '" + zoneName + "', '" + id + "')\"";
        if (id != "-") newHTML += "<span id='" + id + "' " + styles + droppable + click + ">";
        else newHTML += "<span " + styles + droppable + click + ">";

        // Determine overlay parameter for Card()
        var overlay = 0;
        try {
          if (typeof OverlayRules !== 'undefined' && OverlayRules[zoneName]) {
            var cardData = sharedCardData;
            OverlayRules[zoneName].forEach(function(rule) {
              if (cardData.hasOwnProperty(rule.field) && String(cardData[rule.field]) === String(rule.value)) {
                overlay = 1;
              }
            });
          }
        } catch (e) {}

        newHTML += Card(cardArr[0], folder, size, 0, 1, overlay, 0, cardArr[1], "", "", 0, 0, 0, 0, 0, "", 0, 0, 0, 0, 0, 0, heatmapFunction, heatmapColorMap, id);

        try {
          if (combatIndicatorText) {
            newHTML += "<div class='combat-target-indicator' aria-label='Attack target'>" + combatIndicatorText + "</div>";
          }
        } catch (e) {
          if (console && console.error) console.error('Combat target indicator render error', e);
        }

        // Render subcards (lineage) as offset images behind the card
        try {
          var cardDataSub = sharedCardData;
          if (cardDataSub.Subcards && Array.isArray(cardDataSub.Subcards) && cardDataSub.Subcards.length > 0) {
            var subcards = cardDataSub.Subcards;
            for (var si = subcards.length - 1; si >= 0; si--) {
              var offsetTop = (si + 1) * 10;
              var offsetLeft = (si + 1) * 3;
              var subFolder = folder;
              if (typeof AssetReflectionPath === 'function' && AssetReflectionPath()) {
                subFolder = AssetReflectionPath();
              }
              var subSrc = "./" + subFolder + "/concat/" + subcards[si] + ".webp";
              newHTML += "<img data-subcard-id='" + subcards[si] + "' onmouseover='ShowSubcardDetail(event, this)' onmouseout='HideCardDetail()' "
                + "loading='lazy' class='lineage-subcard' style='position:absolute; top:-" + offsetTop + "px; left:" + offsetLeft + "px; height:" + size + "px; width:" + size + "px; "
                + "border:1px solid transparent; opacity:0.85; z-index:-" + (si + 1) + "; pointer-events:auto;' "
                + "src='" + subSrc + "' alt='Lineage card' />";
            }
          }
        } catch (e) {
          if (console && console.error) console.error('Subcards render error', e);
        }

        try {
          // Append counters HTML generated from CounterRules (if defined for this zone)
          newHTML += CreateCountersHTML(zoneName, cardArr, id);
        } catch (e) {
          if (console && console.error) console.error('CreateCountersHTML error', e);
        }

        var buttons = createWidgetButtons(zoneName, id, cardArr[2]);
        newHTML += "<span class='widget-buttons' style='z-index:1000; visibility:hidden; pointer-events:none; display:flex; justify-content: center; position:absolute; top:50%; left:50%; transform: translate(-50%, -50%);'>" + buttons.middleButtons + "</span>";
        newHTML += "<div class='widget-buttons' style='visibility:hidden; pointer-events:none; position:absolute; top:0; right:0; z-index:1001;'>" + buttons.topRightButtons + "</div>";
        if (buttons.topLeftButtons) newHTML += "<div class='widget-buttons' style='visibility:hidden; pointer-events:none; position:absolute; top:0; left:0; z-index:1001;'>" + buttons.topLeftButtons + "</div>";
        if (buttons.bottomLeftButtons) newHTML += "<div class='widget-buttons' style='visibility:hidden; pointer-events:none; position:absolute; bottom:0; left:0; z-index:1001;'>" + buttons.bottomLeftButtons + "</div>";
        if (buttons.bottomRightButtons) newHTML += "<div class='widget-buttons' style='visibility:hidden; pointer-events:none; position:absolute; bottom:0; right:0; z-index:1001;'>" + buttons.bottomRightButtons + "</div>";
        newHTML += "</span>";
        return newHTML;
      }

      // Add this CSS to your stylesheet for the hover effect
      const widgetstyle = document.createElement('style');
      widgetstyle.innerHTML = `
        span.draggable:hover .widget-buttons {
          visibility: visible !important;
          pointer-events: auto !important;
        }

        .combat-target-indicator {
          position: absolute;
          top: 6px;
          left: 50%;
          transform: translateX(-50%);
          z-index: 12;
          padding: 3px 8px;
          border: 1px solid rgba(255, 255, 255, 0.9);
          border-radius: 999px;
          background: rgba(170, 22, 22, 0.9);
          box-shadow: 0 0 0 2px rgba(255, 210, 210, 0.2);
          color: #fff;
          font-family: 'Orbitron', sans-serif;
          font-size: 11px;
          font-weight: 700;
          letter-spacing: 0.08em;
          pointer-events: none;
          text-shadow: 0 1px 2px rgba(0, 0, 0, 0.7);
        }

        span.draggable.combat-targeted-card > a > img {
          box-shadow: 0 0 0 2px rgba(255, 84, 84, 0.95), 0 0 18px rgba(255, 84, 84, 0.45);
        }
      `;
      document.head.appendChild(widgetstyle);

      function createWidgetButtons(zoneName, cardId, cardJSON="-", currentValue="") {
        const escapeHtmlAttr = (value) => String(value ?? '')
          .replace(/&/g, '&amp;')
          .replace(/"/g, '&quot;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;');
        const formatTooltipText = (value) => String(value ?? '').replace(/_/g, ' ');
        let cardData = {};
        if (cardJSON && cardJSON !== "-" && cardJSON.trim() !== "") {
          try {
            cardData = JSON.parse(cardJSON);
          } catch (e) {
            // If JSON parsing fails, log the error and use empty object
            if (console && console.error) console.error('createWidgetButtons: Failed to parse cardJSON for', cardId, ':', cardJSON, e);
            cardData = {};
          }
        }
        const widgets = GetZoneWidgets(zoneName);
        let buttons = {};
        let buttonsHtml = '';
        let topRightButtons = '';
        let bottomLeftButtons = '';
        let bottomRightButtons = '';
        let topLeftButtons = '';

        for (const widgetType in widgets) {
          const widgetGroup = widgets[widgetType];
          const widgetActions = Array.isArray(widgetGroup) ? widgetGroup : widgetGroup.actions || [];
          const position = (!Array.isArray(widgetGroup) && widgetGroup.position) ? widgetGroup.position.toLowerCase() : 'center';
          const condition = (!Array.isArray(widgetGroup) && widgetGroup.condition) ? widgetGroup.condition : null;

          // Check if condition is met (if one exists)
          let conditionMet = true;
          if (condition && condition.field && condition.value !== undefined) {
            const fieldValue = cardData.hasOwnProperty(condition.field) ? cardData[condition.field] : null;
            conditionMet = fieldValue !== null && String(fieldValue) === String(condition.value);
          }

          // Skip rendering if condition is not met
          if (!conditionMet) continue;

          widgetActions.forEach(widget => {
            if(widget.Action == "Display" && cardData.hasOwnProperty(widgetType)) {
              var widgetContent = typeof cardData[widgetType] === 'string' ? cardData[widgetType].replace(/_/g, ' ') : cardData[widgetType];
              if(widgetContent == "Notes") {
                topRightButtons += `<span style="font-weight: bold; color: black; background-color: rgba(255, 255, 255, 0.8); margin-left: 5px; padding: 2px 4px; border-radius: 3px;">${widgetContent}</span>`;
              } else {
                widgetContent = widgetIcons(widgetContent);
                buttonsHtml += `&nbsp;<span style="font-weight: bold; color: black; background-color: rgba(255, 255, 255, 0.8); margin-left: 5px; padding: 2px 4px; border-radius: 3px;">${widgetContent}</span>`;
              }
            }
            else {
              var widgetName = widget.Action.replace(/_/g, ' ');

              // Special handling for Activate button - show ability names
              if (widget.Action === 'Activate' && cardData.CardID && typeof CardActivateAbilityCount === 'function') {
                const abilityCount = CardActivateAbilityCount(cardData.CardID);
                // Read server-computed dynamic abilities (generic — no game-specific logic here)
                let dynamicAbilities = [];
                let activateAbilityStates = [];
                if (cardData.DynamicAbilities && cardData.DynamicAbilities !== '' && cardData.DynamicAbilities !== '[]') {
                  try { dynamicAbilities = JSON.parse(cardData.DynamicAbilities); } catch(e) {}
                }
                if (cardData.ActivateAbilityButtonStates && cardData.ActivateAbilityButtonStates !== '' && cardData.ActivateAbilityButtonStates !== '[]') {
                  try { activateAbilityStates = JSON.parse(cardData.ActivateAbilityButtonStates); } catch(e) {}
                }
                const staticAbilityStates = new Map(
                  activateAbilityStates.map((entry) => [Number(entry.index), entry])
                );
                if (abilityCount >= 1 || dynamicAbilities.length > 0) {
                  // Generate button(s) for each static ability
                  const abilityNames = typeof CardActivateAbilityCountNames === 'function'
                    ? CardActivateAbilityCountNames(cardData.CardID)
                    : [];
                  for (let i = 0; i < abilityCount; i++) {
                    const abilityName = abilityNames[i] || `Ability ${i + 1}`;
                    const actionWithIndex = `Activate:${i}`;
                    const abilityState = staticAbilityStates.get(i);
                    const isEnabled = !abilityState || abilityState.enabled !== false;
                    const buttonTitle = escapeHtmlAttr(formatTooltipText((abilityState && abilityState.tooltip) ? abilityState.tooltip : abilityName));
                    const buttonClass = isEnabled ? 'widget-button' : 'widget-button widget-button-disabled';
                    const buttonAction = isEnabled
                      ? `handleWidgetAction(event, '${cardId}', '${widgetType}', '${actionWithIndex}')`
                      : 'event.preventDefault(); event.stopPropagation(); return false;';
                    const buttonHtml = `&nbsp;<button class="${buttonClass}" onclick="${buttonAction}" title="${buttonTitle}" aria-disabled="${isEnabled ? 'false' : 'true'}">${abilityName}</button>`;
                    switch(position) {
                      case 'topright': topRightButtons += buttonHtml; break;
                      case 'topleft': topLeftButtons += buttonHtml; break;
                      case 'bottomleft': bottomLeftButtons += buttonHtml; break;
                      case 'bottomright': bottomRightButtons += buttonHtml; break;
                      default: buttonsHtml += buttonHtml;
                    }
                  }
                  // Generate button(s) for each server-provided dynamic ability
                  for (const dynAbility of dynamicAbilities) {
                    const dynAction = `Activate:${dynAbility.index}`;
                    const dynButtonHtml = `&nbsp;<button class="widget-button" onclick="handleWidgetAction(event, '${cardId}', '${widgetType}', '${dynAction}')" title="${escapeHtmlAttr(formatTooltipText(dynAbility.name))}">${dynAbility.name}</button>`;
                    switch(position) {
                      case 'topright': topRightButtons += dynButtonHtml; break;
                      case 'topleft': topLeftButtons += dynButtonHtml; break;
                      case 'bottomleft': bottomLeftButtons += dynButtonHtml; break;
                      case 'bottomright': bottomRightButtons += dynButtonHtml; break;
                      default: buttonsHtml += dynButtonHtml;
                    }
                  }
                  return; // Skip the default button generation
                }
              }

              widgetContent = widgetIcons(widgetName);
              const buttonHtml = `&nbsp;<button class="widget-button${currentValue != "" && widget.Action == currentValue ? '-selected' : ''}" onclick="handleWidgetAction(event, '${cardId}', '${widgetType}', '${widget.Action}')">${widgetContent}</button>`;

              if(widgetName == "Notes") {
                topRightButtons += buttonHtml;
              } else {
                switch(position) {
                  case 'topright':
                    topRightButtons += buttonHtml;
                    break;
                  case 'topleft':
                    topLeftButtons += buttonHtml;
                    break;
                  case 'bottomleft':
                    bottomLeftButtons += buttonHtml;
                    break;
                  case 'bottomright':
                    bottomRightButtons += buttonHtml;
                    break;
                  default: // center
                    buttonsHtml += buttonHtml;
                }
              }
            }
          });
        }
        buttons.middleButtons = buttonsHtml;
        buttons.topRightButtons = topRightButtons;
        buttons.bottomLeftButtons = bottomLeftButtons;
        buttons.bottomRightButtons = bottomRightButtons;
        buttons.topLeftButtons = topLeftButtons;
        return buttons;
      }

      function widgetIcons(content) {
        if(content == ">>>") {
          return `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-chevron-double-right" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M3.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L9.293 8 3.646 2.354a.5.5 0 0 1 0-.708"/>
      <path fill-rule="evenodd" d="M7.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L13.293 8 7.646 2.354a.5.5 0 0 1 0-.708"/>
    </svg>`;
        } else if(content == "<<<") {
          return `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-chevron-double-left" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M8.354 1.646a.5.5 0 0 1 0 .708L2.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
      <path fill-rule="evenodd" d="M12.354 1.646a.5.5 0 0 1 0 .708L6.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
    </svg>`;
        } else if(content == "<") {
          return `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
    </svg>`;
        } else if(content == ">") {
          return `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
    </svg>`;
        } else if(content == "V") {
          return `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-chevron-down" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708"/>
    </svg>`;
        } else if(content == "^") {
          return `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-chevron-up" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708z"/>
    </svg>`;
        } else if(content == "+") {
          return `<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
      <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
    </svg>`;
        } else if(content == "Notes") {
          return `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
  <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
  <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z"/>
</svg>`;
        }
        return content;
      }
      // Add this CSS to your stylesheet for the modern button style
      const style = document.createElement('style');
      style.innerHTML = `
        .widget-button {
          background: linear-gradient(180deg, rgba(58, 58, 58, 0.96) 0%, rgba(28, 28, 28, 0.98) 100%);
          border: 1px solid rgba(255, 255, 255, 0.14);
          color: #f6f3eb;
          padding: 4px 8px;
          min-width: 20px;
          text-align: center;
          text-decoration: none;
          display: inline-block;
          font-size: 12px;
          font-weight: 600;
          letter-spacing: 0.02em;
          margin: 0px 0px;
          transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease, background 0.16s ease, color 0.16s ease;
          cursor: pointer;
          border-radius: 999px;
          box-shadow: 0 2px 7px rgba(0, 0, 0, 0.28);
        }

        .widget-button:hover {
          background: linear-gradient(180deg, rgba(255, 248, 236, 0.98) 0%, rgba(230, 224, 212, 0.98) 100%);
          color: #171717;
          border-color: rgba(255, 255, 255, 0.9);
          box-shadow: 0 0 0 1px rgba(255, 248, 236, 0.55), 0 8px 18px rgba(0, 0, 0, 0.35);
          transform: translateY(-1px);
        }

        .widget-button-disabled {
          background: linear-gradient(180deg, rgba(74, 44, 44, 0.95) 0%, rgba(52, 22, 22, 0.98) 100%);
          border-color: rgba(255, 110, 110, 0.22);
          color: rgba(255, 232, 232, 0.78);
          cursor: not-allowed;
          opacity: 0.92;
          box-shadow: 0 2px 8px rgba(24, 0, 0, 0.32);
        }

        .widget-button-disabled:hover {
          background: linear-gradient(180deg, rgba(100, 34, 34, 0.98) 0%, rgba(74, 18, 18, 1) 100%);
          color: #fff0f0;
          border-color: rgba(255, 120, 120, 0.9);
          box-shadow: 0 0 0 1px rgba(255, 120, 120, 0.38), 0 0 12px rgba(255, 72, 72, 0.42), 0 0 24px rgba(255, 42, 42, 0.22);
          transform: translateY(-1px);
        }

        .widget-button-selected {
          background: linear-gradient(180deg, rgba(214, 205, 192, 0.98) 0%, rgba(182, 170, 154, 0.98) 100%);
          border: 1px solid rgba(255, 255, 255, 0.3);
          color: #141414;
          padding: 4px 8px;
          min-width: 20px;
          text-align: center;
          text-decoration: none;
          display: inline-block;
          font-size: 12px;
          font-weight: 600;
          letter-spacing: 0.02em;
          margin: 0px 0px;
          transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease, background 0.16s ease;
          cursor: pointer;
          border-radius: 999px;
          box-shadow: 0 2px 8px rgba(0, 0, 0, 0.24);
        }        .widget-button-selected:hover {
          background: linear-gradient(180deg, rgba(255, 247, 233, 0.98) 0%, rgba(227, 220, 208, 0.98) 100%);
          color: #101010;
          border-color: rgba(255, 255, 255, 0.9);
          box-shadow: 0 0 0 1px rgba(255, 248, 236, 0.5), 0 8px 18px rgba(0, 0, 0, 0.3);
          transform: translateY(-1px);
        }
      `;
      document.head.appendChild(style);

      // Add Flash Message styles
      const flashStyle = document.createElement('style');
      flashStyle.innerHTML = `
        @keyframes flash-pulse {
          0% { box-shadow: 0 0 15px rgba(0, 123, 255, 0.5); }
          50% { box-shadow: 0 0 20px rgba(0, 123, 255, 0.8); }
          100% { box-shadow: 0 0 15px rgba(0, 123, 255, 0.5); }
        }
        #flash-message {
          animation: flash-pulse 2s infinite;
        }
        .flash-content {
          margin-right: 20px;
        }
      `;
      document.head.appendChild(flashStyle);

      // Add selectable-card green glow styles (brighter, lighter, less transparent)
      const selectableStyle = document.createElement('style');
        selectableStyle.innerHTML = `
          /* Light lime glow for selectable cards */

          /* Any span containing a lineage subcard must not clip its overflow */
          span:has(.lineage-subcard) {
            position: relative;
            z-index: 1;
            margin-top: 12px;
            overflow: visible !important;
          }

          /* Lineage subcard images must not inherit the selectable highlight */
          .selectable-card .lineage-subcard {
            border: 1px solid transparent !important;
            box-shadow: none !important;
            transform: none !important;
          }

          .selectable-card {
            display: inline-block; /* ensure box-shadow applies nicely */
            position: relative;
            overflow: visible;
            border-radius: 8px;
            transition: transform 160ms cubic-bezier(.2,.9,.2,1), box-shadow 200ms ease, outline-color 200ms ease;
            /* no wrapper border — border lives on the image so it hugs art exactly */
            box-shadow: none;
            border: none;
            will-change: transform, box-shadow;
          }

          /* Slight lift on hover for tactile feedback */
          .selectable-card:hover {
            transform: translateY(-4px) scale(1.04);
          }

          /* Image-level border - use custom color variable */
          .selectable-card img {
            border-radius: 6px;
            display: block;
            position: relative;
            z-index: 1;
            /* Always show border with custom color */
            border: 1px solid var(--highlight-color);
            /* Subtle outer glow even at rest */
            box-shadow: 0 0 4px var(--highlight-color);
            transition: box-shadow 140ms ease, border-color 140ms ease, border-width 140ms ease, transform 160ms ease;
          }

          /* On hover: slightly thicker border and enhanced glow */
          .selectable-card:hover img {
            /* Slightly thicker border */
            border: 2px solid var(--highlight-color);
            /* Enhanced but still subtle glow on hover */
            box-shadow:
              0 0 12px var(--highlight-color),
              0 0 6px var(--highlight-color);
          }

          /* Gentle pulsing for active selection mode */
          @keyframes selectable-border-pulse {
            0% {
              border-color: rgba(100,250,0,0.50);
              box-shadow: 0 0 8px rgba(100,250,0,0.3);
            }
            50% {
              border-color: rgba(100,250,0,0.80);
              box-shadow: 0 0 12px rgba(100,250,0,0.5);
            }
            100% {
              border-color: rgba(100,250,0,0.50);
              box-shadow: 0 0 8px rgba(100,250,0,0.3);
            }
          }

          /* Pulse the image border when the wrapper has the pulse class */
          .selectable-card.pulse img {
            animation: selectable-border-pulse 1.5s infinite;
          }
        `;
      document.head.appendChild(selectableStyle);

      // Temporarily set nearest scrollable ancestor overflow to visible while hovering a selectable card
      function findOverflowingAncestor(el) {
        while (el && el !== document.documentElement) {
          el = el.parentElement;
          if (!el) break;
          const cs = window.getComputedStyle(el);
          // consider it overflowing if overflow or overflow-y is not 'visible'
          if (cs.overflow !== 'visible' || cs.overflowY !== 'visible' || cs.overflowX !== 'visible') {
            // only return if it actually can clip (auto/hidden/scroll)
            if (['hidden','auto','scroll'].includes(cs.overflow) || ['hidden','auto','scroll'].includes(cs.overflowY) || ['hidden','auto','scroll'].includes(cs.overflowX)) {
              return el;
            }
          }
        }
        return null;
      }

      // Manage hover delegation to avoid adding listeners to every card
      document.addEventListener('mouseover', function(e) {
        const card = e.target.closest && e.target.closest('.selectable-card');
        if (!card) return;
        const anc = findOverflowingAncestor(card);
        if (!anc) return;
        // initialize counter if needed
        if (!anc.dataset._overflowCount) anc.dataset._overflowCount = '0';
        if (!anc.dataset.prevOverflow) {
          anc.dataset.prevOverflow = anc.style.overflow || '';
        }
        // increment
        anc.dataset._overflowCount = String(parseInt(anc.dataset._overflowCount || '0') + 1);
        anc.style.overflow = 'visible';
      });

      document.addEventListener('mouseout', function(e) {
        const card = e.target.closest && e.target.closest('.selectable-card');
        if (!card) return;
        // if moving to an element still inside the card, do nothing
        const to = e.relatedTarget;
        if (to && card.contains(to)) return;
        const anc = findOverflowingAncestor(card);
        if (!anc) return;
        const count = Math.max(0, parseInt(anc.dataset._overflowCount || '0') - 1);
        anc.dataset._overflowCount = String(count);
        if (count <= 0) {
          // restore previous overflow
          anc.style.overflow = anc.dataset.prevOverflow || '';
          delete anc.dataset.prevOverflow;
          delete anc.dataset._overflowCount;
        }
      });

      function handleWidgetAction(event, cardId, widgetType, action) {
        event.stopPropagation(); // Prevent the click event from bubbling up
        // Implement the action handling logic here
        if(action == "Notes") {
          DisplayTextPopup(cardId, widgetType, action);
        } else if (typeof ClientWidgetActions === 'function' && ClientWidgetActions(action)) {

        } else {
          SubmitInput("10001", "&cardID=" + encodeURIComponent(cardId + "!" + widgetType + "!" + action));
        }
      }

      function DisplayTextPopup(cardId, widgetType, action) {
        let modalOverlay = document.createElement('div');
        modalOverlay.style.position = 'fixed';
        modalOverlay.style.top = 0;
        modalOverlay.style.left = 0;
        modalOverlay.style.width = '100%';
        modalOverlay.style.height = '100%';
        modalOverlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        modalOverlay.style.display = 'flex';
        modalOverlay.style.alignItems = 'center';
        modalOverlay.style.justifyContent = 'center';
        modalOverlay.style.zIndex = '2000';

        let modalContainer = document.createElement('div');
        modalContainer.style.backgroundColor = '#0D1B2A'; // Deep space dark blue background
        modalContainer.style.padding = '20px';
        modalContainer.style.borderRadius = '8px';
        modalContainer.style.position = 'relative';
        modalContainer.style.boxShadow = '0 0 15px 5px rgba(0, 123, 255, 0.7)';

        let caption = document.createElement('div');
        caption.textContent = 'Notes';
        caption.style.fontSize = '18px';
        caption.style.fontWeight = 'bold';
        caption.style.marginBottom = '10px';
        caption.style.color = '#FFFFFF';
        caption.style.fontFamily = "'Orbitron', sans-serif";
        caption.style.letterSpacing = '2px';

        let closeButton = document.createElement('button');
        closeButton.textContent = 'X';
        closeButton.style.position = 'absolute';
        closeButton.style.top = '5px';
        closeButton.style.right = '5px';
        closeButton.style.background = 'transparent';
        closeButton.style.border = 'none';
        closeButton.style.fontSize = '16px';
        closeButton.style.cursor = 'pointer';
        closeButton.style.fontFamily = "'Orbitron', sans-serif";
        closeButton.style.color = '#FFFFFF';

        var fullData = GetZoneCard(cardId);
        let textEntry = document.createElement('textarea');
        //textEntry.value = fullData["Notes"].replace(/_/g, " ");
        textEntry.value = GetCardNotes(fullData.CardID);
        textEntry.style.width = '300px';
        textEntry.style.height = '150px';
        textEntry.style.backgroundColor = '#333'; // Dark background for dark mode
        textEntry.style.color = '#EEE'; // Light text color for readability
        textEntry.style.border = '1px solid #555'; // Subtle border

        // Function to close the modal and submit the note text
        function closeModal() {
          var noteText = textEntry.value;
          if(modalOverlay.parentNode) {
        document.body.removeChild(modalOverlay);
          }
          document.removeEventListener('keydown', escClose);
          SubmitInput("10001", "&cardID=" + encodeURIComponent(cardId + "!text!" + action + "!" + noteText));
        }

        // Close if click occurs outside the modal container
        modalOverlay.addEventListener('click', function(e) {
          if (e.target === modalOverlay) {
        closeModal();
          }
        });

        // Close if Escape key is pressed
        function escClose(e) {
          if (e.key === "Escape") {
        closeModal();
          }
        }
        document.addEventListener('keydown', escClose);

        closeButton.addEventListener('click', function() {
          closeModal();
        });

        modalContainer.appendChild(closeButton);
        modalContainer.appendChild(caption);
        modalContainer.appendChild(textEntry);
        modalOverlay.appendChild(modalContainer);
        document.body.appendChild(modalOverlay);
        textEntry.focus();
      }

      function GetCardNotes(cardID) {
        var notesData = (typeof window["myCardNotesData"] !== "undefined") ? window["myCardNotesData"] : "";
        if(notesData == "") return "";
        notesData = notesData.split("<|>");
        for(var i = 0; i < notesData.length; ++i) {
          var cardData = notesData[i].split(" ");
          if(cardData[0] == cardID) {
            var cardJson = JSON.parse(cardData[2]);
            return cardJson["Notes"].replace(/_/g, " ");
          }
        }
        return "";
      }

      function GetZoneCard(cardId) {
        var cardArr = cardId.split("-");
        var zoneName = cardArr[0];
        var zoneData = GetZoneData(zoneName);
        var fullData = [];
        if(zoneData.DisplayMode != "Pane") {
          var cardData = window[zoneName + "Data"].split("<|>");
          fullData = JSON.parse(cardData[cardArr[1]].split(" ")[2]);
        } else {
          var prefix = zoneName.replace(zoneData.Name, "");
          var activePaneVar = `_${prefix}_${zoneData.DisplayParameters[0]}_activePane`;
          var paneName = prefix + zoneData.DisplayParameters[0] + "Panes";
          var panes = window[paneName];
          var cardData = panes[window[activePaneVar]].split("<|>");
          fullData = JSON.parse(cardData[cardArr[1]].split(" ")[2]);
        }
        return fullData;
      }

      function GetZoneCards(zoneName) {
        var zoneData = GetZoneData(zoneName);
        var cards = [];
        if(zoneData.DisplayMode != "Pane") {
          var cardData = window[zoneName + "Data"].split("<|>");
          for (let i = 0; i < cardData.length; i++) {
            const cardArr = cardData[i].split(" ");
            if(cardArr.length < 3) continue;
            const cardObj = JSON.parse(cardArr[2]);
            cards.push(cardObj["CardID"]);
          }
          return cards;
        } else {
          showFlashMessage("Get zone cards for panes not implemented yet");
          /*
          var prefix = zoneName.replace(zoneData.Name, "");
          var activePaneVar = `_${prefix}_${zoneData.DisplayParameters[0]}_activePane`;
          var paneName = prefix + zoneData.DisplayParameters[0] + "Panes";
          var panes = window[paneName];
          var cardData = panes[window[activePaneVar]].split("<|>");
          fullData = JSON.parse(cardData[cardArr[1]].split(" ")[2]);
          */
        }
      }

      function RenderRows(myRows, theirRows) {
        var theirStuff = "";
        var myStuff = "";
        theirRows.forEach(element => {
          theirStuff += element + "<br>";
        });
        myRows.forEach(element => {
          myStuff += element + "<br>";
        });

        var theirDiv = document.getElementById("theirStuff");
        var myDiv = document.getElementById("myStuff");
        theirDiv.innerHTML = theirStuff;
        myDiv.innerHTML = myStuff;
      }

      function RenderPanes(zoneName, myPanes, theirPanes) {
        RenderPane("my", zoneName, myPanes);
        RenderPane("their", zoneName, theirPanes);
      }

      function ShowFilterBarHelp() {
        let helpText = "Use the filter bar to quickly find cards. Text properties can use ':' or '=' to search for cards where that property contains that text. Numeric properties can use '=', '<', '>', '>=', or '<='. You can filter by the following properties:\n\n";
        propertyLookup.forEach(property => {
          helpText += `- "${property.Name}" (${property.Type})${property.Alias ? ` | alias: "${property.Alias}"` : ''}\n`;
        });
        let modalOverlay = document.createElement('div');
        modalOverlay.style.position = 'fixed';
        modalOverlay.style.top = 0;
        modalOverlay.style.left = 0;
        modalOverlay.style.width = '100%';
        modalOverlay.style.height = '100%';
        modalOverlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        modalOverlay.style.display = 'flex';
        modalOverlay.style.alignItems = 'center';
        modalOverlay.style.justifyContent = 'center';
        modalOverlay.style.zIndex = '2000';

        let modalContainer = document.createElement('div');
        modalContainer.style.backgroundColor = '#0D1B2A'; // Deep space dark blue background
        modalContainer.style.padding = '20px';
        modalContainer.style.borderRadius = '8px';
        modalContainer.style.position = 'relative';
        modalContainer.style.boxShadow = '0 0 15px 5px rgba(0, 123, 255, 0.7)'; // Blue glow
        modalContainer.style.maxWidth = '80%';
        modalContainer.style.maxHeight = '80%';
        modalContainer.style.overflowY = 'auto';
        modalContainer.style.color = 'white'; // Set text color to white
        modalContainer.style.fontFamily = 'Arial, sans-serif'; // Change font to Arial
        modalContainer.style.lineHeight = '1.6'; // Increase line height for better readability

        let closeButton = document.createElement('button');
        closeButton.textContent = 'X';
        closeButton.style.position = 'absolute';
        closeButton.style.top = '10px';
        closeButton.style.right = '10px';
        closeButton.style.background = 'transparent';
        closeButton.style.border = 'none';
        closeButton.style.fontSize = '16px';
        closeButton.style.cursor = 'pointer';

        let helpContent = document.createElement('div');
        helpContent.textContent = helpText;
        helpContent.style.whiteSpace = 'pre-wrap';

        function closeModal() {
          if (modalOverlay.parentNode) {
            document.body.removeChild(modalOverlay);
          }
          document.removeEventListener('keydown', escClose);
        }

        function escClose(e) {
          if (e.key === 'Escape') {
            closeModal();
          }
        }

        modalOverlay.addEventListener('click', function(e) {
          if (e.target === modalOverlay) {
            closeModal();
          }
        });

        closeButton.addEventListener('click', closeModal);
        document.addEventListener('keydown', escClose);

        modalContainer.appendChild(closeButton);
        modalContainer.appendChild(helpContent);
        modalOverlay.appendChild(modalContainer);
        document.body.appendChild(modalOverlay);
      }

      function ZoneScrollHandler(fullName) {
        var container = document.getElementById(fullName + "Wrapper");
        if (container) {
          window[fullName + "ScrollPosition"] = container.scrollTop;
        }
      }

      // Add a help icon next to the filter bar
      function RenderPane(prefix, zoneName, panes) {
        var fullName = prefix + zoneName;
        var filterText = window.filterText ? window.filterText : "";//TODO: Separate filter text for each zone
        var customFilterStatus = window.customFilter ? window.customFilter : false;
        var storedLegalFilter = localStorage.getItem('swuLegalFilter');
        var legalFilterStatus = storedLegalFilter !== null ? storedLegalFilter === 'true' : true;
        window.legalFilter = legalFilterStatus;
        var scrollPosition = window[fullName + "ScrollPosition"] || 0;
        var html = "<div style='display: flex; flex-direction: column; width:100%; overflow-y: auto;'>";
        html += `<div style='position: relative; width: 100%; box-sizing: border-box;'>`;
        setTimeout(() => {
          document.getElementById(fullName + "Wrapper").scrollTop = scrollPosition;
        }, 0);
        html += `<input type="text" style='height:28px; margin-top:3px; width:100%; box-sizing:border-box; padding-right:20px;' class='filterBar' id="${fullName}FilterText" onkeydown="PaneFilterKeyDown('${prefix}', '${zoneName}', event);" oninput="PaneFilterCards('${prefix}', '${zoneName}', event, 'textFilter');" placeholder="Filter cards..." ${filterText ? `value="${filterText.replace(/"/g, '&quot;')}"` : ''}></input>`;
        html += `<img src='./Assets/Images/infoicon.png' style='cursor: pointer; position: absolute; top: 2px; right: 2px; height:12px; width:12px;' onclick='ShowFilterBarHelp()' aria-label='Click for filter syntax' />`;
        html += `</div>`;
        html += `<div style='display: flex; align-items: center; flex-wrap: wrap; margin-top: 4px;'>`;
        var paneHTML = "<span id='" + prefix + "_" + zoneName + "_content'>";
        var panelNames = GetPaneData(zoneName);
        var activePaneVar = `_${prefix}_${zoneName}_activePane`;
        var index = 0;
        var hasCustomFilter = false;
        panelNames.forEach(panelName => {
          html += `<div style='font-size: 14px; cursor: pointer;' class='panelTab' onclick='PaneTabClick("${prefix}", "${zoneName}", "${index}")'>${panelName}</div>`;
          if(index == window[activePaneVar]) {
            paneHTML += PopulateZone(prefix + panelName, panes[window[activePaneVar]], window.cardSize, window.rootPath + "/concat",1,"All", filterText);
            var zoneData = GetZoneData(prefix + panelName);
            if(zoneData.Filters.length > 0) hasCustomFilter = true;
          }
          ++index;
        });
        if(hasCustomFilter) {
          html += `<div style='display: flex; align-items: center; margin-left: 10px;'>
          <input type='checkbox' id='customFilterCheckbox' onchange='PaneFilterCards("${prefix}", "${zoneName}", event, "check");' ${customFilterStatus ? 'checked' : ''}>
          <label for='customFilterCheckbox' style='margin-left: 5px; font-size: 13px; cursor: pointer;'>Filter Aspect</label>
              </div>`;
        }
        if(typeof window.InLegalFilter === 'function') {
          html += `<div style='display: flex; align-items: center; margin-left: 10px;'>
          <input type='checkbox' id='legalFilterCheckbox' onchange='PaneFilterCards("${prefix}", "${zoneName}", event, "check");' ${legalFilterStatus ? 'checked' : ''}>
          <label for='legalFilterCheckbox' style='margin-left: 5px; font-size: 13px; cursor: pointer;'>Filter Legal</label>
              </div>`;
        }
        html += `</div>`;
        paneHTML += "</span>";
        html += "</div>";
        document.getElementById(fullName).innerHTML = html + paneHTML;
      }
      function PaneTabClick(prefix, zoneName, index) {
        var activePaneVar = `_${prefix}_${zoneName}_activePane`;
        window[activePaneVar] = index;
        var paneVar = `${prefix}${zoneName}Panes`;
        RenderPane(prefix, zoneName, window[paneVar]);
      }

      function PaneFilterCards(prefix, zoneName, event, source) {
        event.stopPropagation();
        var fullName = prefix + zoneName;
        var paneVar = `${prefix}${zoneName}Panes`;
        const filterTextElement = document.getElementById(`${fullName}FilterText`);
        const cursorPosition = filterTextElement.selectionStart; // Save the cursor position
        window.filterText = filterTextElement.value; // TODO: Separate filter text for each zone
        const customFilterCheckbox = document.getElementById('customFilterCheckbox');
        window.customFilter = customFilterCheckbox ? customFilterCheckbox.checked : false;
        const legalFilterCheckbox = document.getElementById('legalFilterCheckbox');
        window.legalFilter = legalFilterCheckbox ? legalFilterCheckbox.checked : false;
        localStorage.setItem('swuLegalFilter', window.legalFilter);
        RenderPane(prefix, zoneName, window[paneVar]);
        if(source == "textFilter") {
          const filterTextElement2 = document.getElementById(`${fullName}FilterText`);
          filterTextElement2.setSelectionRange(cursorPosition, cursorPosition); // Restore the cursor position
          filterTextElement2.focus();
        }
      }

      function PaneFilterKeyDown(prefix, zoneName, event) {
        event.stopPropagation();
        if(event.key === 'Enter') {
            var panelNames = GetPaneData(zoneName);
            var activePaneVar = `_${prefix}_${zoneName}_activePane`;
            var contentDiv = document.getElementById(prefix + panelNames[window[activePaneVar]]);
            if(contentDiv.innerHTML.length === 0) {
              //Search returned no content, try conversational search
              const filterTextElement = document.getElementById(`${prefix}${zoneName}FilterText`);
              var filterText = filterTextElement.value;
              var xhr = new XMLHttpRequest();
              xhr.open("GET", "/TCGEngine/AIEndpoints/FullElasticSearch.php?request=" + encodeURIComponent(filterText), true);
              xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.error) {
                  showFlashMessage(response.error);
                } else {
                  window.filterText = response.message;//TODO: Separate filter text for each zone
                  var paneVar = `${prefix}${zoneName}Panes`;
                  RenderPane(prefix, zoneName, window[paneVar]);
                }
              }
              };
              xhr.send();
            }
        }
      }

      function AppendStaticZones(myStatic, theirStatic, globalStatic) {
        var myDiv = document.getElementById("myStuff");
        var theirDiv = document.getElementById("theirStuff");
        myDiv.innerHTML += myStatic;
        theirDiv.innerHTML += theirStatic;
        if (globalStatic) {
          var globalDiv = document.getElementById("globalStuff");
          if (globalDiv) {
            globalDiv.innerHTML = globalStatic;
            // Re-enable pointer events on child elements since the container has pointer-events:none
            var children = globalDiv.querySelectorAll('[id$="Wrapper"]');
            for (var i = 0; i < children.length; i++) {
              children[i].style.pointerEvents = 'auto';
            }
          }
        }
        // Reorganize layout for mobile deck editor
        if (window.innerWidth <= 1000 && typeof MobileDeckEditorLayout === 'function') {
          MobileDeckEditorLayout();
        }
      }

      function chkSubmit(mode, count) {
        var input = "";
        input += "&gameName=" + document.getElementById("gameName").value;
        input += "&playerID=" + document.getElementById("playerID").value;
        input += "&chkCount=" + count;
        for (var i = 0; i < count; ++i) {
          var el = document.getElementById("chk" + i);
          if (el.checked) input += "&chk" + i + "=" + el.value;
        }
        SubmitInput(mode, input);
      }

      function textSubmit(mode) {
        var input = "";
        input += "&gameName=" + document.getElementById("gameName").value;
        input += "&playerID=" + document.getElementById("playerID").value;
        input += "&inputText=" + document.getElementById("inputText").value;
        SubmitInput(mode, input);
      }

      function suppressEventPropagation(e)
      {
        e.stopPropagation();
      }

      function UpdateAssetVisibility(newVisibility, gameName, assetType) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', './APIs/UpdateAssetVisibility.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('visibility=' + newVisibility + '&gameName=' + gameName + '&assetType=' + assetType);
      }

      function showFlashMessage(message, duration = 5000) {
        // Remove any existing flash messages
        const existingFlash = document.getElementById('flash-message');
        if (existingFlash) {
          document.body.removeChild(existingFlash);
        }

        // Create flash message container
        const flashMessage = document.createElement('div');
        flashMessage.id = 'flash-message';
        flashMessage.style.position = 'fixed';
        flashMessage.style.top = '0';
        flashMessage.style.left = '50%';
        flashMessage.style.transform = 'translateX(-50%) translateY(-100%)';
        flashMessage.style.zIndex = '3000';
        flashMessage.style.backgroundColor = '#0D1B2A';
        flashMessage.style.color = 'white';
        flashMessage.style.padding = '15px 20px';
        flashMessage.style.borderRadius = '0 0 8px 8px';
        flashMessage.style.boxShadow = '0 5px 15px rgba(0, 123, 255, 0.5)';
        flashMessage.style.transition = 'transform 0.4s ease-in-out';
        flashMessage.style.maxWidth = '80%';
        flashMessage.style.minWidth = '300px';
        flashMessage.style.textAlign = 'center';
        flashMessage.style.fontFamily = "'Orbitron', sans-serif";
        flashMessage.style.borderLeft = '4px solid #007bff';
        flashMessage.style.borderRight = '4px solid #007bff';
        flashMessage.style.borderBottom = '4px solid #007bff';

        // Add content container (to handle HTML)
        const contentDiv = document.createElement('div');
        contentDiv.className = 'flash-content';
        contentDiv.style.maxHeight = '200px';
        contentDiv.style.overflowY = 'auto';
        contentDiv.innerHTML = message;

        // Add close button
        const closeButton = document.createElement('button');
        closeButton.textContent = '×';
        closeButton.style.position = 'absolute';
        closeButton.style.right = '10px';
        closeButton.style.top = '5px';
        closeButton.style.background = 'transparent';
        closeButton.style.border = 'none';
        closeButton.style.color = 'white';
        closeButton.style.fontSize = '20px';
        closeButton.style.cursor = 'pointer';
        closeButton.style.fontFamily = "'Orbitron', sans-serif";
        closeButton.addEventListener('click', () => {
          hideFlashMessage(flashMessage);
        });

        // Add elements to DOM
        flashMessage.appendChild(contentDiv);
        flashMessage.appendChild(closeButton);
        document.body.appendChild(flashMessage);

        // Show the flash message
        setTimeout(() => {
          flashMessage.style.transform = 'translateX(-50%) translateY(0)';
        }, 100);

        // Auto-hide after duration
        setTimeout(() => {
          hideFlashMessage(flashMessage);
        }, duration);
      }

      function hideFlashMessage(flashMessage) {
        flashMessage.style.transform = 'translateX(-50%) translateY(-100%)';
        setTimeout(() => {
          if (flashMessage.parentNode) {
            flashMessage.parentNode.removeChild(flashMessage);
          }
        }, 400);
      }

      function OnVersionChanged(newVersion, versionName) {
        var extra = (versionName ? '&versionName=' + encodeURIComponent(versionName) : '');
        SubmitInput("10003", "&cardID=" + newVersion + extra);
      }

      function showNewVersionPrompt() {
        closeVersionDropdown();
        // Compute placeholder: max DisplayNumber across current versions + 1
        var nextNum = 0;
        var menu = document.getElementById('versionDropdownMenu');
        if (menu) {
          var rows = menu.querySelectorAll('[data-vnum]');
          rows.forEach(function(r) {
            var n = parseInt(r.getAttribute('data-vnum'), 10);
            if (!isNaN(n) && n >= nextNum) nextNum = n + 1;
          });
        }
        var placeholder = 'Version ' + nextNum;

        // Build modal overlay
        var overlay = document.createElement('div');
        overlay.id = 'newVersionOverlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:10000;display:flex;align-items:center;justify-content:center;';

        var box = document.createElement('div');
        box.style.cssText = 'background:#2a2a2a;border:1px solid #555;border-radius:8px;padding:20px 24px;min-width:280px;color:#fff;font-family:inherit;';

        var title = document.createElement('div');
        title.textContent = 'Save New Version';
        title.style.cssText = 'font-size:15px;font-weight:bold;margin-bottom:12px;';
        box.appendChild(title);

        var input = document.createElement('input');
        input.type = 'text';
        input.placeholder = placeholder;
        input.maxLength = 60;
        input.style.cssText = 'width:100%;box-sizing:border-box;padding:6px 8px;background:#1a1a1a;border:1px solid #666;border-radius:4px;color:#fff;font-size:13px;margin-bottom:14px;';
        box.appendChild(input);

        var btnRow = document.createElement('div');
        btnRow.style.cssText = 'display:flex;gap:8px;justify-content:flex-end;';

        var cancelBtn = document.createElement('button');
        cancelBtn.textContent = 'Cancel';
        cancelBtn.style.cssText = 'background:#444;color:#fff;border:none;padding:5px 14px;border-radius:4px;cursor:pointer;font-size:13px;';
        cancelBtn.onclick = function() { document.body.removeChild(overlay); };

        var okBtn = document.createElement('button');
        okBtn.textContent = 'Save';
        okBtn.style.cssText = 'background:#1a73e8;color:#fff;border:none;padding:5px 14px;border-radius:4px;cursor:pointer;font-size:13px;';
        okBtn.onclick = function() {
          var name = input.value.trim() || placeholder;
          document.body.removeChild(overlay);
          setVersionDisplay('current', 'Current Version');
          OnVersionChanged('new', name);
        };

        btnRow.appendChild(cancelBtn);
        btnRow.appendChild(okBtn);
        box.appendChild(btnRow);
        overlay.appendChild(box);
        document.body.appendChild(overlay);

        // Close on backdrop click
        overlay.addEventListener('click', function(e) { if (e.target === overlay) { document.body.removeChild(overlay); } });
        // Submit on Enter
        input.addEventListener('keydown', function(e) { if (e.key === 'Enter') okBtn.click(); });
        setTimeout(function() { input.focus(); }, 50);
      }

      function toggleVersionDropdown() {
        var menu = document.getElementById('versionDropdownMenu');
        if (!menu) return;
        closeVisibilityDropdown();
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
      }

      function closeVersionDropdown() {
        var menu = document.getElementById('versionDropdownMenu');
        if (menu) menu.style.display = 'none';
      }

      var _visdropListenerAdded = false;
      function toggleVisibilityDropdown() {
        var menu = document.getElementById('visibilityDropdownMenu');
        if (!menu) return;
        closeVersionDropdown();
        if (!_visdropListenerAdded) {
          _visdropListenerAdded = true;
          document.addEventListener('click', function(e) {
            var w = document.getElementById('visibilityDropdownWrapper');
            if (w && !w.contains(e.target)) closeVisibilityDropdown();
          });
        }
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
      }

      function closeVisibilityDropdown() {
        var menu = document.getElementById('visibilityDropdownMenu');
        if (menu) menu.style.display = 'none';
      }

      function selectVisibility(value, label, gameName) {
        closeVisibilityDropdown();
        var lbl = document.getElementById('visibilityDropdownLabel');
        if (lbl) lbl.textContent = label;
        UpdateAssetVisibility(value, gameName || '', 1);
      }

      function setVersionDisplay(value, label) {
        var labelEl = document.getElementById('versionDropdownLabel');
        if (labelEl) labelEl.textContent = label;
        var trigger = document.getElementById('versionDropdownTrigger');
        if (trigger) trigger.setAttribute('data-label', label);
      }

      function selectVersion(value, label) {
        closeVersionDropdown();
        setVersionDisplay(value, label);
        OnVersionChanged(value);
      }

      function showDeleteVersionConfirm(arrayIndex, displayNum) {
        var existing = document.getElementById('vdrop-delete-modal');
        if (existing) existing.remove();

        var overlay = document.createElement('div');
        overlay.id = 'vdrop-delete-modal';
        overlay.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.6);z-index:10000;display:flex;align-items:center;justify-content:center;';

        var modal = document.createElement('div');
        modal.style.cssText = 'background:#1a1a2e;border:1px solid #555;border-radius:10px;padding:28px 24px;text-align:center;max-width:340px;width:90%;box-shadow:0 0 24px rgba(0,0,0,0.8);font-family:inherit;';

        var msg = document.createElement('div');
        msg.style.cssText = 'color:#fff;font-size:15px;margin-bottom:20px;line-height:1.5;';
        msg.textContent = 'Delete Version ' + displayNum + '? This cannot be undone.';
        modal.appendChild(msg);

        var btnRow = document.createElement('div');
        btnRow.style.cssText = 'display:flex;gap:12px;justify-content:center;';

        var btnYes = document.createElement('button');
        btnYes.textContent = 'Delete';
        btnYes.style.cssText = 'background:#c0392b;color:#fff;border:none;padding:8px 22px;border-radius:6px;font-size:14px;cursor:pointer;';
        btnYes.onclick = function() {
          overlay.remove();
          var gameName = document.getElementById('gameName') ? document.getElementById('gameName').value : '';
          var folderPath = document.getElementById('folderPath') ? document.getElementById('folderPath').value : 'SWUDeck';
          var xhr = new XMLHttpRequest();
          xhr.open('GET', '/TCGEngine/' + folderPath + '/DeleteVersion.php?deckID=' + encodeURIComponent(gameName) + '&playerID=1&versionIndex=' + arrayIndex, true);
          xhr.send();
        };

        var btnNo = document.createElement('button');
        btnNo.textContent = 'Cancel';
        btnNo.style.cssText = 'background:#444;color:#fff;border:none;padding:8px 22px;border-radius:6px;font-size:14px;cursor:pointer;';
        btnNo.onclick = function() { overlay.remove(); };

        btnRow.appendChild(btnYes);
        btnRow.appendChild(btnNo);
        modal.appendChild(btnRow);
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
      }

      var _vdropListenerAdded = false;

      function UpdateVersionDropdown(versionsData) {
        var wrapper = document.getElementById('versionDropdownWrapper');
        if (!wrapper) return;

        if (!_vdropListenerAdded) {
          _vdropListenerAdded = true;
          document.addEventListener('click', function(e) {
            var w = document.getElementById('versionDropdownWrapper');
            if (w && !w.contains(e.target)) closeVersionDropdown();
          });
        }

        var trimmed = versionsData ? versionsData.trim() : '';
        var entries = trimmed.length === 0 ? [] : trimmed.split('<|>');

        // Parse display numbers and names from each entry JSON
        var versions = [];
        for (var i = 0; i < entries.length; i++) {
          var entry = entries[i];
          var displayNum = i;
          var versionName = '';
          var s1 = entry.indexOf(' ');
          var s2 = s1 >= 0 ? entry.indexOf(' ', s1 + 1) : -1;
          if (s2 >= 0) {
            try {
              var obj = JSON.parse(entry.substring(s2 + 1));
              if (obj && typeof obj.DisplayNumber !== 'undefined') displayNum = obj.DisplayNumber;
              if (obj && typeof obj.VersionName === 'string' && obj.VersionName.length > 0) versionName = obj.VersionName.replace(/_/g, ' ');
            } catch(e) {}
          }
          versions.push({ arrayIndex: i, displayNum: displayNum, versionName: versionName });
        }

        var savedLabel = document.getElementById('versionDropdownLabel') ? document.getElementById('versionDropdownLabel').textContent : 'Current Version';

        var menu = document.getElementById('versionDropdownMenu');
        if (!menu) return;
        menu.innerHTML = '';

        var optStyle = 'padding:7px 12px;cursor:pointer;font-size:13px;color:#fff;white-space:nowrap;';

        var divCurrent = document.createElement('div');
        divCurrent.style.cssText = optStyle;
        divCurrent.textContent = 'Current Version';
        divCurrent.onmouseover = function() { this.style.background = '#3a3a3a'; };
        divCurrent.onmouseout = function() { this.style.background = ''; };
        divCurrent.onclick = function() { selectVersion('current', 'Current Version'); };
        menu.appendChild(divCurrent);

        for (var j = 0; j < versions.length; j++) {
          (function(v) {
            var row = document.createElement('div');
            row.style.cssText = optStyle + 'display:flex;justify-content:space-between;align-items:center;';
            row.setAttribute('data-vnum', String(v.displayNum));
            row.onmouseover = function() { this.style.background = '#3a3a3a'; };
            row.onmouseout = function() { this.style.background = ''; };

            var label = v.versionName || ('Version ' + v.displayNum);
            var lbl = document.createElement('span');
            lbl.style.cssText = 'flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;';
            lbl.textContent = label;
            row.appendChild(lbl);

            var btnWrap = document.createElement('span');
            btnWrap.style.cssText = 'display:inline-flex;align-items:center;gap:16px;flex-shrink:0;margin-left:8px;';

            var loadBadge = document.createElement('span');
            loadBadge.title = 'Load ' + label;
            loadBadge.style.cssText = 'padding:1px 5px;border-radius:3px;background:#1a73e8;color:#fff;font-size:9px;cursor:pointer;line-height:14px;white-space:nowrap;';
            loadBadge.textContent = 'Load';
            loadBadge.onclick = function(e) {
              e.stopPropagation();
              selectVersion(String(v.arrayIndex), label);
            };

            var delBadge = document.createElement('span');
            delBadge.title = 'Delete Version ' + v.displayNum;
            delBadge.style.cssText = 'width:15px;height:15px;border-radius:50%;background:#c0392b;color:#fff;font-size:9px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;line-height:1;';
            delBadge.textContent = '\u2715';
            delBadge.onclick = function(e) {
              e.stopPropagation();
              closeVersionDropdown();
              showDeleteVersionConfirm(v.arrayIndex, v.displayNum);
            };

            btnWrap.appendChild(loadBadge);
            btnWrap.appendChild(delBadge);
            row.appendChild(btnWrap);
            menu.appendChild(row);
          })(versions[j]);
        }

        var divNew = document.createElement('div');
        divNew.style.cssText = optStyle + 'color:#aaf;';
        divNew.textContent = '+ New Version';
        divNew.onmouseover = function() { this.style.background = '#3a3a3a'; };
        divNew.onmouseout = function() { this.style.background = ''; };
        divNew.onclick = function() { showNewVersionPrompt(); };
        menu.appendChild(divNew);

        // If the currently displayed version was deleted, reset to Current
        if (savedLabel !== 'Current Version' && savedLabel !== '+ New Version') {
          var stillExists = versions.some(function(v) { return (v.versionName || ('Version ' + v.displayNum)) === savedLabel; });
          if (!stillExists) setVersionDisplay('current', 'Current Version');
        }
      }

// Show a YES/NO popup for a decision queue entry
function ShowYesNoDecisionPopup(decision, onSubmit) {
  // Remove any existing modal
  let existing = document.getElementById('yesno-decision-modal');
  if (existing) existing.remove();

  let overlay = document.createElement('div');
  overlay.id = 'yesno-decision-modal';
  overlay.style.position = 'fixed';
  overlay.style.top = '0';
  overlay.style.left = '0';
  overlay.style.width = '100vw';
  overlay.style.height = '100vh';
  overlay.style.background = 'rgba(0,0,0,0.5)';
  overlay.style.zIndex = '5000';
  overlay.style.display = 'flex';
  overlay.style.alignItems = 'center';
  overlay.style.justifyContent = 'center';

  let modal = document.createElement('div');
  modal.style.background = '#0D1B2A';
  modal.style.padding = '32px 24px';
  modal.style.borderRadius = '10px';
  modal.style.boxShadow = '0 0 20px #0008';
  modal.style.textAlign = 'center';
  modal.style.minWidth = '300px';
  modal.style.fontFamily = "'Orbitron', sans-serif";

  let prompt = document.createElement('div');
  prompt.style.fontSize = '20px';
  prompt.style.color = '#fff';
  prompt.style.marginBottom = '24px';
  prompt.textContent = decision.Tooltip && decision.Tooltip !== '-' ? decision.Tooltip.replace(/_/g, ' ') : 'Please choose Yes or No:';
  modal.appendChild(prompt);

  let yesBtn = document.createElement('button');
  yesBtn.textContent = 'Yes';
  yesBtn.style.margin = '0 16px 0 0';
  yesBtn.style.padding = '8px 24px';
  yesBtn.style.fontSize = '18px';
  yesBtn.style.background = '#28a745';
  yesBtn.style.color = '#fff';
  yesBtn.style.border = 'none';
  yesBtn.style.borderRadius = '5px';
  yesBtn.style.cursor = 'pointer';
  yesBtn.onclick = function() {
    overlay.remove();
    if (onSubmit) onSubmit('YES');
  };
  modal.appendChild(yesBtn);

  let noBtn = document.createElement('button');
  noBtn.textContent = 'No';
  noBtn.style.padding = '8px 24px';
  noBtn.style.fontSize = '18px';
  noBtn.style.background = '#dc3545';
  noBtn.style.color = '#fff';
  noBtn.style.border = 'none';
  noBtn.style.borderRadius = '5px';
  noBtn.style.cursor = 'pointer';
  noBtn.onclick = function() {
    overlay.remove();
    if (onSubmit) onSubmit('NO');
  };
  modal.appendChild(noBtn);

  overlay.appendChild(modal);
  document.body.appendChild(overlay);
}

// Pre-processes a raw decision queue string into an array of objects
function ParseDecisionQueue(raw) {
  if (!raw || typeof raw !== 'string') return [];
  // Split on <|> and filter out empty segments
  const segments = raw.split('<|>').map(s => s.trim()).filter(Boolean);
  const result = [];
  for (const seg of segments) {
    // Each segment is like: '- 0 {"Type":"YESNO",...}'
    const jsonStart = seg.indexOf('{');
    if (jsonStart !== -1) {
      try {
        const obj = JSON.parse(seg.slice(jsonStart));
        result.push(obj);
      } catch (e) {
        // Ignore parse errors
      }
    }
  }
  return result;
}

// Call this after game state update to check for pending YESNO decisions
function CheckAndShowDecisionQueue(decisionQueue) {
  // Accept raw string or array
  if (typeof decisionQueue === 'string') {
    decisionQueue = ParseDecisionQueue(decisionQueue);
  }
  if (!decisionQueue || !Array.isArray(decisionQueue)) return;
  for (let i = 0; i < decisionQueue.length; ++i) {
    let entry = decisionQueue[i];
    if (entry && entry.Type === 'YESNO' && !entry.removed) {
      ShowYesNoDecisionPopup(entry, function(result) {
        SubmitInput('DECISION', '&decisionIndex=' + i + '&cardID=' + result);
      });
      break;
    } else if (entry && (entry.Type === 'MZCHOOSE' || entry.Type === 'MZMAYCHOOSE') && !entry.removed) {
      // Set up selection mode
      window.SelectionMode.active = true;
      window.SelectionMode.mode = '100';
      window.SelectionMode.mayPass = (entry.Type === 'MZMAYCHOOSE');

      // Parse allowed zones/cards into objects
      // Supports:
      //   - Zone selection: "myHand" or "BG1" (selects any card in the zone)
      //   - Specific card selection: "myHand-0" or "BG1-2" (selects a specific card by index)
      //   - Filters: "myHand:CardType=Spell" (zone with filter)
      const parsedSpecs = (entry.Param || '').split('&').map(s => s.trim()).filter(Boolean).map(spec => {
        const parts = spec.split(':');
        const zoneOrCard = parts[0].trim();
        const filters = [];
        if (parts.length > 1) {
          const filtString = parts.slice(1).join(':');
          const clauses = filtString.split(',').map(f => f.trim()).filter(Boolean);
          clauses.forEach(cl => {
            const m = cl.match(/^(\w+)(==|!=|<=|>=|=|<|>)(.*)$/);
            if (m) {
              filters.push({ field: m[1], op: m[2], value: m[3] });
            } else {
              filters.push({ field: cl, op: '=', value: 'true' });
            }
          });
        }

        // Check if this is a specific card reference (zoneName-index)
        const cardMatch = zoneOrCard.match(/^(.+)-(\d+)$/);
        if (cardMatch) {
          // Specific card reference
          return {
            zone: cardMatch[1],
            specificIndex: parseInt(cardMatch[2], 10),
            filters: filters,
            isSpecificCard: true,
            originalSpec: zoneOrCard
          };
        } else {
          // Zone reference (any card in zone)
          return { zone: zoneOrCard, filters: filters, isSpecificCard: false };
        }
      });

      window.SelectionMode.allowedZones = parsedSpecs;
      window.SelectionMode.decisionIndex = i;
      window.SelectionMode.callback = function(zoneName, cardId, decisionIndex) {
        SubmitInput('DECISION', '&decisionIndex=' + decisionIndex + '&cardID=' + encodeURIComponent(cardId));
      };
      var tooltip = (entry.Tooltip && entry.Tooltip !== '-') ? entry.Tooltip.replace(/_/g, ' ') : 'Select a card from an allowed zone.';

      const categorizedSpecs = CategorizeMZChooseSpecs(parsedSpecs);
      const inlineSpecs = categorizedSpecs.inlineSpecs;
      const popupCards = categorizedSpecs.popupCards;


function CategorizeMZChooseSpecs(parsedSpecs) {
  const inlineSpecs = [];
  const popupCards = [];

  for (const spec of parsedSpecs) {
    const zoneData = GetZoneData(spec.zone);
    const displayMode = zoneData && zoneData.DisplayMode ? zoneData.DisplayMode : 'All';

    if (spec.isSpecificCard && (displayMode === 'Single' || displayMode === 'None')) {
      popupCards.push(spec);
    } else {
      inlineSpecs.push(spec);
    }
  }

  if (popupCards.length > 0) {
    const remainingInlineSpecs = [];
    for (const spec of inlineSpecs) {
      if (spec.isSpecificCard) {
        popupCards.push(spec);
      } else {
        remainingInlineSpecs.push(spec);
      }
    }
    return {
      inlineSpecs: remainingInlineSpecs,
      popupCards: popupCards,
    };
  }

  return {
    inlineSpecs: inlineSpecs,
    popupCards: popupCards,
  };
}
      // Store categorized specs for rendering
      window.SelectionMode.inlineSpecs = inlineSpecs;
      window.SelectionMode.popupCards = popupCards;

      // Only show selection message banner if there are inline selectable options
      // If only popup cards, the popup handles the UI
      if (inlineSpecs.length > 0) {
        ShowSelectionMessage(tooltip, window.SelectionMode.mayPass, i);
      }

      // Show popup for Single mode zone cards if any
      if (popupCards.length > 0) {
        ShowMZChoosePopup(popupCards, tooltip, window.SelectionMode.mayPass, i);
      }

      // Highlight/selectable will be handled in rendering for inline specs

      // After setting selection mode for MZCHOOSE, force a re-render of all zones
      if (typeof RenderRows === 'function' && typeof window.myRows !== 'undefined' && typeof window.theirRows !== 'undefined') {
        RenderRows(window.myRows, window.theirRows);
      }
          // Add gentle pulsing glow to selectable cards after re-render (DOM needs a moment)
          setTimeout(() => {
            document.querySelectorAll('.selectable-card').forEach(el => el.classList.add('pulse'));
          }, 0);
      break;
    } else if (entry && entry.Type === 'MZREARRANGE' && !entry.removed) {
      // MZREARRANGE: Allow player to rearrange cards between piles
      // Param format: "PileName1=card1,card2;PileName2=card3,card4"
      var tooltip = (entry.Tooltip && entry.Tooltip !== '-') ? entry.Tooltip.replace(/_/g, ' ') : 'Arrange the cards';

      if (typeof ShowMZRearrangePopup === 'function') {
        ShowMZRearrangePopup(entry.Param, tooltip, i, function(serializedResult, decisionIndex) {
          SubmitInput('DECISION', '&decisionIndex=' + decisionIndex + '&cardID=' + encodeURIComponent(serializedResult));
        });
      } else {
        console.error('MZRearrangePopup.js not loaded - ShowMZRearrangePopup function not found');
      }
      break;
    } else if (entry && entry.Type === 'MZMODAL' && !entry.removed) {
      // MZMODAL: Choose N of M labeled options
      // Param format: "min|max|label1&label2&label3"
      var tooltip = (entry.Tooltip && entry.Tooltip !== '-') ? entry.Tooltip.replace(/_/g, ' ') : 'Choose options';

      if (typeof ShowMZModalUI === 'function') {
        ShowMZModalUI(entry.Param, tooltip, i, function(serializedResult, decisionIndex) {
          SubmitInput('DECISION', '&decisionIndex=' + decisionIndex + '&cardID=' + encodeURIComponent(serializedResult));
        });
      } else {
        console.error('MZModalUI.js not loaded - ShowMZModalUI function not found');
      }
      break;
    } else if (entry && entry.Type === 'MZSPLITASSIGN' && !entry.removed) {
      // MZSPLITASSIGN: Split-assign a numeric pool across multiple target cards
      // Param format: "amount|mzID1&mzID2&mzID3"
      var tooltip = (entry.Tooltip && entry.Tooltip !== '-') ? entry.Tooltip.replace(/_/g, ' ') : 'Assign points';

      if (typeof ShowMZSplitAssignUI === 'function') {
        ShowMZSplitAssignUI(entry.Param, tooltip, i, function(serializedResult, decisionIndex) {
          SubmitInput('DECISION', '&decisionIndex=' + decisionIndex + '&cardID=' + encodeURIComponent(serializedResult));
        });
      } else {
        console.error('MZSplitAssignUI.js not loaded - ShowMZSplitAssignUI function not found');
      }
      break;
    } else if (entry && entry.Type === 'NUMBERCHOOSE' && !entry.removed) {
      // NUMBERCHOOSE: Numeric stepper/slider choice
      // Param format: "min|max"
      var tooltip = (entry.Tooltip && entry.Tooltip !== '-') ? entry.Tooltip.replace(/_/g, ' ') : 'Choose a number';

      if (typeof ShowNumberChooseUI === 'function') {
        ShowNumberChooseUI(entry.Param, tooltip, i, function(selectedNumber, decisionIndex) {
          SubmitInput('DECISION', '&decisionIndex=' + decisionIndex + '&cardID=' + encodeURIComponent(selectedNumber));
        });
      } else {
        console.error('NumberChooseUI.js not loaded - ShowNumberChooseUI function not found');
      }
      break;
    } else if (entry && entry.Type === 'ICONCHOICE' && !entry.removed) {
      // ICONCHOICE: Compass-rose directional choice (Shifting Currents)
      // Param format: "OPT1&OPT2|CURRENT|CARDID"
      var tooltip = (entry.Tooltip && entry.Tooltip !== '-') ? entry.Tooltip.replace(/_/g, ' ') : 'Choose a direction';

      if (typeof ShowIconChoiceUI === 'function') {
        ShowIconChoiceUI(entry.Param, tooltip, i, function(selectedOption, decisionIndex) {
          SubmitInput('DECISION', '&decisionIndex=' + decisionIndex + '&cardID=' + encodeURIComponent(selectedOption));
        });
      } else {
        console.error('IconChoiceUI.js not loaded - ShowIconChoiceUI function not found');
      }
      break;
    }
  }
};

// --- Selection Mode State ---
window.SelectionMode = {
  active: false,
  mode: '', // e.g., '100' for decision queue
  allowedZones: [],
  inlineSpecs: [],    // Specs for inline selection (All mode zones/cards)
  popupCards: [],     // Specs for popup selection (Single mode zone specific cards)
  callback: null,
  decisionIndex: null,
  mayPass: false
};

function ClearSelectionMode() {
  window.SelectionMode = {
    active: false,
    mode: '',
    allowedZones: [],
    inlineSpecs: [],
    popupCards: [],
    callback: null,
    decisionIndex: null,
    mayPass: false
  };
  // Remove selectable highlight from all cards
  document.querySelectorAll('.selectable-card').forEach(el => {
    el.classList.remove('selectable-card');
    el.classList.remove('pulse');
    el.onclick = null;
  });
  HideSelectionMessage();
  // Also hide the MZChoose popup if it exists
  HideMZChoosePopup();
  // Also hide the MZRearrange popup if it exists
  if (typeof HideMZRearrangePopup === 'function') {
    HideMZRearrangePopup();
  }
  // Also hide the MZSplitAssign UI if it exists
  if (typeof HideMZSplitAssignUI === 'function') {
    HideMZSplitAssignUI();
  }
  // Also hide the MZModal UI if it exists
  if (typeof HideMZModalUI === 'function') {
    HideMZModalUI();
  }
  // Also hide the NumberChoose UI if it exists
  if (typeof HideNumberChooseUI === 'function') {
    HideNumberChooseUI();
  }
  // Also hide YES/NO and icon choice modals if they exist
  let yesNoModal = document.getElementById('yesno-decision-modal');
  if (yesNoModal) yesNoModal.remove();
  let iconChoiceModal = document.getElementById('iconchoice-modal');
  if (iconChoiceModal) iconChoiceModal.remove();
}

function ShowSelectionMessage(msg, showPassButton, decisionIndex) {
  // Use flash message or unobtrusive banner
  let existing = document.getElementById('selection-message');
  if (!existing) {
    existing = document.createElement('div');
    existing.id = 'selection-message';
    existing.style.position = 'fixed';
    existing.style.bottom = '20px';
    existing.style.left = '50%';
    existing.style.transform = 'translateX(-50%)';
    existing.style.background = '#0D1B2A';
    existing.style.color = '#fff';
    existing.style.padding = '10px 24px';
    existing.style.borderRadius = '8px';
    existing.style.boxShadow = '0 0 10px #0008';
    existing.style.fontFamily = "'Orbitron', sans-serif";
    existing.style.zIndex = '9999';
    existing.style.display = 'flex';
    existing.style.alignItems = 'center';
    existing.style.gap = '16px';
    document.body.appendChild(existing);
  }

  // Clear previous content
  existing.innerHTML = '';

  // Add message text
  let msgSpan = document.createElement('span');
  msgSpan.textContent = msg;
  existing.appendChild(msgSpan);

  // Add Pass button if allowed
  if (showPassButton) {
    let passBtn = document.createElement('button');
    passBtn.textContent = 'Pass';
    passBtn.style.padding = '6px 16px';
    passBtn.style.fontSize = '14px';
    passBtn.style.background = '#6c757d';
    passBtn.style.color = '#fff';
    passBtn.style.border = 'none';
    passBtn.style.borderRadius = '5px';
    passBtn.style.cursor = 'pointer';
    passBtn.style.marginLeft = '8px';
    passBtn.onmouseover = function() { passBtn.style.background = '#5a6268'; };
    passBtn.onmouseout = function() { passBtn.style.background = '#6c757d'; };
    passBtn.onclick = function() {
      // Submit PASS as a DECISION action (action code 100)
      SubmitInput('DECISION', '&decisionIndex=' + decisionIndex + '&cardID=PASS');
      ClearSelectionMode();
    };
    existing.appendChild(passBtn);
  }

  existing.style.display = 'flex';
}

function HideSelectionMessage() {
  let existing = document.getElementById('selection-message');
  if (existing) existing.style.display = 'none';
}

// Determine if a card element (in a given zone) should be selectable based on
// the current SelectionMode definitions. Supports:
// - Zone selection: "myHand" (any card in zone)
// - Specific card selection: "myHand-0" (only card at index 0)
// - Filters: "myBase:index=0" or "myBattlefield:CardID=ABC"
// Returns true if the zone matches and all filters pass.
// For inline selection, only checks inlineSpecs (not popup cards).
function IsSelectableCard(zone, cardArr, index) {
  try {
    if (!window.SelectionMode || !window.SelectionMode.active) return false;

    // Use inlineSpecs if available, otherwise fall back to allowedZones for compatibility
    const specs = window.SelectionMode.inlineSpecs || window.SelectionMode.allowedZones || [];

    for (let si = 0; si < specs.length; ++si) {
      const spec = specs[si];
      if (!spec || !spec.zone) continue;
      if (spec.zone !== zone) continue;

      // If this is a specific card reference, check the index matches exactly
      if (spec.isSpecificCard) {
        if (spec.specificIndex !== index) continue;
      }

      const filters = spec.filters || [];
      if (filters.length === 0) return true;

      // parse card JSON if present
      let cardData = {};
      if (cardArr && cardArr.length > 2 && cardArr[2] && cardArr[2] !== '-') {
        try { cardData = JSON.parse(cardArr[2]); } catch (e) { cardData = {}; }
      }
      let allOk = true;
      for (let fi = 0; fi < filters.length; ++fi) {
        const f = filters[fi];
        const field = f.field;
        const op = f.op;
        const target = f.value;
        let actual = null;
        if (field.toLowerCase() === 'index' || field.toLowerCase() === 'i') {
          actual = Number(index);
        } else {
          actual = cardData.hasOwnProperty(field) ? cardData[field] : null;
        }
        if (actual === null || actual === undefined) { allOk = false; break; }
        const numActual = Number(actual);
        const numTarget = Number(target);
        const numericCompare = !isNaN(numActual) && !isNaN(numTarget);
        switch(op) {
          case '=': case '==':
            if (numericCompare) { if (!(numActual == numTarget)) allOk = false; }
            else { if (String(actual) !== String(target)) allOk = false; }
            break;
          case '!=':
            if (numericCompare) { if (!(numActual != numTarget)) allOk = false; }
            else { if (String(actual) === String(target)) allOk = false; }
            break;
          case '<':
            if (!numericCompare || !(numActual < numTarget)) allOk = false;
            break;
          case '>':
            if (!numericCompare || !(numActual > numTarget)) allOk = false;
            break;
          case '<=':
            if (!numericCompare || !(numActual <= numTarget)) allOk = false;
            break;
          case '>=':
            if (!numericCompare || !(numActual >= numTarget)) allOk = false;
            break;
          default:
            allOk = false;
        }
        if (!allOk) break;
      }
      if (allOk) return true;
    }
    return false;
  } catch (e) {
    if (console && console.error) console.error('IsSelectableCard error', e);
    return false;
  }
}

// Hide the MZChoose popup
function HideMZChoosePopup() {
  let existing = document.getElementById('mzchoose-popup');
  if (existing) existing.remove();
  if (typeof HideCardDetail === 'function') HideCardDetail();
}

// Show a popup for selecting cards from Single mode zones
// popupCards: array of specs with { zone, specificIndex, originalSpec, ... }
// Each card will display with a label showing the zone name
function ShowMZChoosePopup(popupCards, tooltip, showPassButton, decisionIndex) {
  // Remove any existing popup
  HideMZChoosePopup();

  if (!popupCards || popupCards.length === 0) return;

  // Create overlay
  let overlay = document.createElement('div');
  overlay.id = 'mzchoose-popup';
  overlay.style.position = 'fixed';
  overlay.style.top = '0';
  overlay.style.left = '0';
  overlay.style.width = '100vw';
  overlay.style.height = '100vh';
  overlay.style.background = 'rgba(0,0,0,0.7)';
  overlay.style.zIndex = '5000';
  overlay.style.display = 'flex';
  overlay.style.flexDirection = 'column';
  overlay.style.alignItems = 'center';
  overlay.style.justifyContent = 'center';
  overlay.style.fontFamily = "'Orbitron', sans-serif";

  // Create modal container
  let modal = document.createElement('div');
  modal.style.background = '#0D1B2A';
  modal.style.padding = '24px';
  modal.style.borderRadius = '12px';
  modal.style.boxShadow = '0 0 30px rgba(0,0,0,0.8)';
  modal.style.maxWidth = '90vw';
  modal.style.maxHeight = '80vh';
  modal.style.overflow = 'auto';

  // Title/tooltip
  let title = document.createElement('div');
  title.style.fontSize = '18px';
  title.style.color = '#fff';
  title.style.marginBottom = '20px';
  title.style.textAlign = 'center';
  title.textContent = tooltip;
  modal.appendChild(title);

  // Cards container - horizontal wrap
  let cardsContainer = document.createElement('div');
  cardsContainer.style.display = 'flex';
  cardsContainer.style.flexWrap = 'wrap';
  cardsContainer.style.justifyContent = 'center';
  cardsContainer.style.gap = '16px';
  cardsContainer.style.marginBottom = '20px';

  // Get card size from window or use default
  const cardSize = window.cardSize || 96;
  const rootPath = window.rootPath || '.';

  // For each popup card spec, find and display the card
  for (const spec of popupCards) {
    // Get card data from the zone's window data variable
    const zoneDataVar = spec.zone + 'Data';
    const zoneDataStr = window[zoneDataVar];
    if (!zoneDataStr || typeof zoneDataStr !== 'string') continue;

    // Parse the zone data to get the card at the specific index
    const zoneCards = zoneDataStr.split('<|>').filter(s => s.trim());
    if (spec.specificIndex >= zoneCards.length) continue;

    const cardEntry = zoneCards[spec.specificIndex];
    const cardArr = cardEntry.split(' ');
    // cardArr[0] = card number (image filename)
    // cardArr[1] = counter data
    // cardArr[2] = JSON data
    const cardNumber = cardArr[0];
    const counters = cardArr.length > 1 ? cardArr[1] : '0';

    // Create card wrapper
    let cardWrapper = document.createElement('div');
    cardWrapper.style.position = 'relative';
    cardWrapper.style.cursor = 'pointer';
    cardWrapper.style.transition = 'transform 0.2s, box-shadow 0.2s';
    cardWrapper.style.borderRadius = '8px';

    // Add hover effect
    cardWrapper.onmouseenter = function(e) {
      cardWrapper.style.transform = 'scale(1.05)';
      cardWrapper.style.boxShadow = '0 0 20px rgba(100,250,0,0.6)';
      if (typeof ShowCardDetail === 'function') ShowCardDetail(e, cardWrapper);
    };
    cardWrapper.onmouseleave = function() {
      cardWrapper.style.transform = 'scale(1)';
      cardWrapper.style.boxShadow = 'none';
      if (typeof HideCardDetail === 'function') HideCardDetail();
    };

    // Create card image container using the Card() function
    let cardImgContainer = document.createElement('div');
    cardImgContainer.style.position = 'relative';

    // Use the Card() function to generate the card HTML
    // Card(cardNumber, folder, maxHeight, action, showHover, overlay, borderColor, counters, ...)
    const folder = rootPath + '/concat';
    const cardHTML = Card(cardNumber, folder, cardSize, 0, 0, 0, 0, counters);
    cardImgContainer.innerHTML = cardHTML;

    // Style the generated image
    const imgEl = cardImgContainer.querySelector('img');
    if (imgEl) {
      imgEl.style.width = cardSize + 'px';
      imgEl.style.height = 'auto';
      imgEl.style.borderRadius = '6px';
    }

    cardWrapper.appendChild(cardImgContainer);

    // Zone label at bottom of card
    let zoneLabel = document.createElement('div');
    zoneLabel.style.position = 'absolute';
    zoneLabel.style.bottom = '0';
    zoneLabel.style.left = '0';
    zoneLabel.style.right = '0';
    zoneLabel.style.background = 'rgba(0,0,0,0.8)';
    zoneLabel.style.color = '#fff';
    zoneLabel.style.fontSize = '11px';
    zoneLabel.style.padding = '4px 6px';
    zoneLabel.style.textAlign = 'center';
    zoneLabel.style.borderRadius = '0 0 6px 6px';
    // Extract readable zone name (remove my/their prefix for cleaner display)
    let displayZoneName = spec.zone.replace(/^(my|their)/, '');
    zoneLabel.textContent = displayZoneName;
    cardWrapper.appendChild(zoneLabel);

    // Click handler - select this card
    const cardIdToSubmit = spec.originalSpec; // e.g., "myHand-0" or "BG1-2"
    const zoneNameForCallback = spec.zone;
    cardWrapper.onclick = function() {
      if (window.SelectionMode && window.SelectionMode.callback) {
        window.SelectionMode.callback(zoneNameForCallback, cardIdToSubmit, decisionIndex);
      }
      ClearSelectionMode();
    };

    cardsContainer.appendChild(cardWrapper);
  }

  modal.appendChild(cardsContainer);

  // Buttons container
  let buttonsContainer = document.createElement('div');
  buttonsContainer.style.display = 'flex';
  buttonsContainer.style.justifyContent = 'center';
  buttonsContainer.style.gap = '16px';

  // Pass button (if allowed)
  if (showPassButton) {
    let passBtn = document.createElement('button');
    passBtn.textContent = 'Pass';
    passBtn.style.padding = '10px 24px';
    passBtn.style.fontSize = '16px';
    passBtn.style.background = '#6c757d';
    passBtn.style.color = '#fff';
    passBtn.style.border = 'none';
    passBtn.style.borderRadius = '6px';
    passBtn.style.cursor = 'pointer';
    passBtn.style.fontFamily = "'Orbitron', sans-serif";
    passBtn.onmouseover = function() { passBtn.style.background = '#5a6268'; };
    passBtn.onmouseout = function() { passBtn.style.background = '#6c757d'; };
    passBtn.onclick = function() {
      SubmitInput('DECISION', '&decisionIndex=' + decisionIndex + '&cardID=PASS');
      ClearSelectionMode();
    };
    buttonsContainer.appendChild(passBtn);
  }

  modal.appendChild(buttonsContainer);
  overlay.appendChild(modal);
  document.body.appendChild(overlay);
}

function _ensureTurnMiasmaOverlay() {
  let el = document.getElementById('turn-miasma-overlay');
  if (!el) {
    el = document.createElement('div');
    el.id = 'turn-miasma-overlay';
    document.body.appendChild(el);
  }
  return el;
}

function UpdateTurnPlayerMiasma() {
  try {
    const overlay = _ensureTurnMiasmaOverlay();
    const turnVal = typeof window.TurnPlayerData !== 'undefined' ? parseInt(window.TurnPlayerData) : NaN;
    const viewerVal = (document.getElementById('playerID') && document.getElementById('playerID').value) ? parseInt(document.getElementById('playerID').value) : NaN;

    // If we don't have a valid turn value, hide the overlay
    if (isNaN(turnVal)) {
      overlay.style.display = 'none';
      return;
    }

    // If viewer value available, only show miasma when viewer is NOT the turn player
    if (!isNaN(viewerVal)) {
      const viewerIsTurn = viewerVal === turnVal;
      overlay.style.display = viewerIsTurn ? 'none' : 'block';
      return;
    }

    // For spectators (no viewerVal) show overlay by default when turnVal exists
    overlay.style.display = 'block';
  } catch (e) {
    if (console && console.error) console.error('UpdateTurnPlayerMiasma error', e);
  }
}
function GetCookieValue(cookieName) {
  var nameEQ = cookieName + '=';
  var cookies = document.cookie.split(';');
  for (var i = 0; i < cookies.length; ++i) {
    var c = cookies[i];
    while (c.charAt(0) === ' ') c = c.substring(1, c.length);
    if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
  }
  return null;
}

function SetCookieValue(cookieName, cookieValue, maxAgeDays) {
  var maxAgeSeconds = Math.max(1, parseInt(maxAgeDays, 10) || 1) * 24 * 60 * 60;
  document.cookie = cookieName + '=' + encodeURIComponent(cookieValue) + '; max-age=' + maxAgeSeconds + '; path=/; SameSite=Lax';
}

// ---- Mobile Deck Editor Layout ----
// Reorganizes #myStuff into a vertical layout for screens ≤1000px:
//   - Leader + Base side-by-side at the top (scrollable)
//   - Deck count / Stats / Sort controls row
//   - Main deck and Sideboard tiles
//   - Card search/browse pane fixed at the bottom
// Called automatically from AppendStaticZones on every render update.
function MobileDeckEditorLayout() {
  if (window.innerWidth > 1000) return;
  var myStuff = document.getElementById('myStuff');
  var cardPaneWrapper = document.getElementById('myCardPaneWrapper');
  if (!myStuff || !cardPaneWrapper) return;

  var leaderWrapper   = document.getElementById('myLeaderWrapper');
  var baseWrapper     = document.getElementById('myBaseWrapper');
  var mainDeckWrapper = document.getElementById('myMainDeckWrapper');
  var sideboardWrapper= document.getElementById('mySideboardWrapper');
  var deckWrapper     = document.getElementById('myDeckWrapper');
  var statsWrapper    = document.getElementById('myStatsWrapper');
  var sortWrapper     = document.getElementById('mySortWrapper');

  // Scrollable top section
  var topArea = document.createElement('div');
  topArea.id = 'mobileTopArea';
  topArea.style.cssText = 'flex:1;overflow-y:auto;overflow-x:hidden;min-height:0;width:100%;box-sizing:border-box;padding-bottom:80px;';

  // Leader + Base side-by-side row
  var leaderBaseRow = document.createElement('div');
  leaderBaseRow.style.cssText = 'display:flex;flex-direction:row;justify-content:center;align-items:flex-start;padding:8px 4px;gap:8px;flex-wrap:wrap;';
  var relStyle = 'position:relative;left:0;top:0;bottom:auto;right:auto;width:auto;';
  if (leaderWrapper)  { leaderWrapper.style.cssText  = relStyle; leaderBaseRow.appendChild(leaderWrapper); }
  if (baseWrapper)    { baseWrapper.style.cssText    = relStyle; leaderBaseRow.appendChild(baseWrapper); }
  topArea.appendChild(leaderBaseRow);

  // Controls row: deck count + stats + sort
  var controlsRow = document.createElement('div');
  controlsRow.style.cssText = 'display:flex;flex-direction:row;flex-wrap:wrap;align-items:flex-start;padding:2px 8px 4px;gap:4px;';
  if (deckWrapper)  { deckWrapper.style.cssText  = relStyle; controlsRow.appendChild(deckWrapper); }
  if (statsWrapper) { statsWrapper.style.cssText = relStyle; controlsRow.appendChild(statsWrapper); }
  if (sortWrapper)  { sortWrapper.style.cssText  = relStyle; controlsRow.appendChild(sortWrapper); }
  topArea.appendChild(controlsRow);

  // Main deck + Sideboard (full width) with sticky section title headers
  var wideStyle = 'position:relative;left:0;top:0;bottom:auto;right:auto;width:100%;box-sizing:border-box;';
  var sectionTitleStyle = 'position:sticky;top:0;z-index:10;background:#1a1a2e;color:#ccc;font-size:0.75rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;padding:3px 8px;border-bottom:1px solid #444;box-sizing:border-box;';
  if (mainDeckWrapper) {
    var mainDeckTitle = document.createElement('div');
    mainDeckTitle.id = 'mobileMainDeckTitle';
    mainDeckTitle.textContent = 'Main Deck';
    mainDeckTitle.style.cssText = sectionTitleStyle;
    topArea.appendChild(mainDeckTitle);
    mainDeckWrapper.style.cssText = wideStyle;
    topArea.appendChild(mainDeckWrapper);
  }
  if (sideboardWrapper) {
    var sideboardTitle = document.createElement('div');
    sideboardTitle.id = 'mobileSideboardTitle';
    sideboardTitle.textContent = 'Sideboard';
    sideboardTitle.style.cssText = sectionTitleStyle;
    topArea.appendChild(sideboardTitle);
    sideboardWrapper.style.cssText = wideStyle;
    topArea.appendChild(sideboardWrapper);
  }

  // Card pane: fixed height at bottom
  cardPaneWrapper.style.cssText = 'position:relative;left:0;top:0;bottom:0;right:0;width:100%;height:50vh;min-height:250px;flex-shrink:0;border-top:2px solid #444;box-sizing:border-box;overflow-y:auto;';

  var mobileCardBrowserCookieName = 'swu_mobile_card_browser_hidden';
  // Keep user preference across rerenders while toggling the card browser pane.
  if (typeof window.mobileCardBrowserHidden === 'undefined') {
    var cookieVal = GetCookieValue(mobileCardBrowserCookieName);
    window.mobileCardBrowserHidden = cookieVal === '1';
  }

  // Detach card pane from myStuff (all other wrappers already moved to topArea)
  if (cardPaneWrapper.parentNode === myStuff) myStuff.removeChild(cardPaneWrapper);

  // Clear any stragglers and apply flex column layout
  myStuff.innerHTML = '';
  myStuff.style.display        = 'flex';
  myStuff.style.flexDirection  = 'column';
  myStuff.style.overflow       = 'hidden';

  var toggleRow = document.createElement('div');
  toggleRow.id = 'mobileDeckToggleRow';
  toggleRow.style.cssText = 'position:fixed;left:8px;right:8px;bottom:10px;z-index:1200;display:flex;justify-content:flex-end;align-items:center;padding:6px 8px;gap:6px;border:1px solid #3f3f3f;border-radius:8px;background:rgba(18,18,18,0.92);backdrop-filter:blur(2px);box-sizing:border-box;opacity:1;transform:translateY(0);transition:opacity 0.25s ease, transform 0.25s ease;';

  var toggleButton = document.createElement('button');
  toggleButton.id = 'mobileDeckToggleButton';
  toggleButton.style.cssText = 'padding:6px 10px;font-size:12px;font-weight:600;letter-spacing:0.02em;background:#1d4ed8;color:#ffffff;border:1px solid #60a5fa;border-radius:6px;cursor:pointer;';
  toggleButton.onmouseover = function() { toggleButton.style.background = '#1e40af'; };
  toggleButton.onmouseout = function() { toggleButton.style.background = '#1d4ed8'; };

  var applyMobileCardBrowserVisibility = function() {
    var isHidden = window.mobileCardBrowserHidden === true;
    toggleButton.textContent = isHidden ? 'Show Card Browser' : 'Hide Card Browser';
    toggleButton.setAttribute('aria-expanded', isHidden ? 'false' : 'true');

    if (isHidden) {
      cardPaneWrapper.style.display = 'none';
      topArea.style.flex = '1 1 auto';
    }
    else {
      cardPaneWrapper.style.display = 'block';
      cardPaneWrapper.style.height = '50vh';
      cardPaneWrapper.style.minHeight = '250px';
      cardPaneWrapper.style.flex = '0 0 auto';
      topArea.style.flex = '1 1 auto';
    }
  };

  var showMobileToggleRow = function() {
    toggleRow.style.opacity = '1';
    toggleRow.style.transform = 'translateY(0)';
    toggleRow.style.pointerEvents = 'auto';
    if (window.mobileDeckToggleAutoHideTimer) clearTimeout(window.mobileDeckToggleAutoHideTimer);
    window.mobileDeckToggleAutoHideTimer = setTimeout(function() {
      toggleRow.style.opacity = '0';
      toggleRow.style.transform = 'translateY(10px)';
      toggleRow.style.pointerEvents = 'none';
    }, 1500);
  };

  var registerMobileToggleActivityEvents = function() {
    var activityHandler = function() { showMobileToggleRow(); };

    if (topArea._mobileToggleActivityHandler) {
      topArea.removeEventListener('scroll', topArea._mobileToggleActivityHandler);
      topArea.removeEventListener('touchmove', topArea._mobileToggleActivityHandler);
    }
    topArea._mobileToggleActivityHandler = activityHandler;
    topArea.addEventListener('scroll', activityHandler, { passive: true });
    topArea.addEventListener('touchmove', activityHandler, { passive: true });

    if (cardPaneWrapper._mobileToggleActivityHandler) {
      cardPaneWrapper.removeEventListener('scroll', cardPaneWrapper._mobileToggleActivityHandler);
      cardPaneWrapper.removeEventListener('touchmove', cardPaneWrapper._mobileToggleActivityHandler);
    }
    cardPaneWrapper._mobileToggleActivityHandler = activityHandler;
    cardPaneWrapper.addEventListener('scroll', activityHandler, { passive: true });
    cardPaneWrapper.addEventListener('touchmove', activityHandler, { passive: true });

    if (window._mobileToggleWindowActivityHandler) {
      window.removeEventListener('scroll', window._mobileToggleWindowActivityHandler);
      window.removeEventListener('touchmove', window._mobileToggleWindowActivityHandler);
    }
    window._mobileToggleWindowActivityHandler = activityHandler;
    window.addEventListener('scroll', activityHandler, { passive: true });
    window.addEventListener('touchmove', activityHandler, { passive: true });
  };

  toggleButton.onclick = function() {
    window.mobileCardBrowserHidden = !window.mobileCardBrowserHidden;
    SetCookieValue(mobileCardBrowserCookieName, window.mobileCardBrowserHidden ? '1' : '0', 365);
    applyMobileCardBrowserVisibility();
    showMobileToggleRow();
  };

  toggleRow.onmouseenter = function() { showMobileToggleRow(); };
  toggleRow.ontouchstart = function() { showMobileToggleRow(); };

  applyMobileCardBrowserVisibility();
  toggleRow.appendChild(toggleButton);
  registerMobileToggleActivityEvents();
  showMobileToggleRow();

  myStuff.appendChild(topArea);
  myStuff.appendChild(toggleRow);
  myStuff.appendChild(cardPaneWrapper);
}

