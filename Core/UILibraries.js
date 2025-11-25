//Rotate is deprecated
      function Card(cardNumber, folder, maxHeight, action = 0, showHover = 0, overlay = 0, borderColor = 0, counters = 0, actionDataOverride = "", id = "", rotate = 0, lifeCounters = 0, defCounters = 0, atkCounters = 0, controller = 0, restriction = "", isBroken = 0, onChain = 0, isFrozen = 0, gem = 0, landscape = 0, epicActionUsed = 0, heatmapFunction = "", heatmapColorMap = "") {
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
            rv += "<div style='margin: 0px; top: 85%; left:" + left + "; margin-right: -50%; width: " + counterHeight + "px; height: " + counterHeight + "px; border-radius: 50%; border: 3px solid " + PopupBorderColor(darkMode) + "; text-align: center; line-height:" + imgCounterHeight / 1.5 + "px;";
            rv += "transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); position:absolute; z-index: 10; background: radial-gradient(circle, rgba(64,64,64,1) 40%, rgba(142,142,142,1) 100%); font-family: 'Orbitron', sans-serif; font-size:" + (counterHeight - 2) + "px; font-weight:700; color:" + TextCounterColor(darkMode) + "; text-shadow: 0 0 5px " + PopupBorderColor(darkMode) + ", 0 0 10px " + PopupBorderColor(darkMode) + ";'>" + counters + "</div>";
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
        //if (event.keyCode === 117) SubmitInput(10000, ""); //U = undo
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
            var sortProperty = zoneMetadata.Sort != null ? zoneMetadata.Sort.Property : "";
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
            for (var i = 0; i < zoneArr.length; ++i) {
              cardArr = zoneArr[i].split(" ");
              if(filter != "") {
                if(ShouldFilter(cardArr[0], filter)) continue;
              }
              if(filterFunction != null && window.customFilter && filterFunction(cardArr[0])) continue;
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
        if (window.SelectionMode.active && window.SelectionMode.allowedZones.includes(zone)) {
          isSelectable = true;
        }
        var newHTML = "";
        var id = zone + "-" + i;
        var positionStyle = "relative";
        var className = isSelectable ? "selectable-card" : "";
        var styles = " style='position:" + positionStyle + "; margin:1px;'";
        var droppable = " class='draggable " + className + "' draggable='true' ondragstart='dragStart(event)' ondragend='dragEnd(event)'";
        var click = isSelectable
          ? " onclick=\"OnSelectableCardClick('" + zoneName + "', '" + id + "')\""
          : " onclick=\"CardClick(event, '" + zoneName + "', '" + id + "')\"";
        if (id != "-") newHTML += "<span id='" + id + "' " + styles + droppable + click + ">";
        else newHTML += "<span " + styles + droppable + click + ">";

        // Determine overlay parameter for Card()
        var overlay = 0;
        try {
          if (typeof OverlayRules !== 'undefined' && OverlayRules[zoneName]) {
            var cardData = {};
            if (cardArr.length > 2 && cardArr[2] && cardArr[2] !== '-') {
              try { cardData = JSON.parse(cardArr[2]); } catch (e) {}
            }
            OverlayRules[zoneName].forEach(function(rule) {
              if (cardData.hasOwnProperty(rule.field) && String(cardData[rule.field]) === String(rule.value)) {
                overlay = 1;
              }
            });
          }
        } catch (e) {}

        newHTML += Card(cardArr[0], folder, size, 0, 1, overlay, 0, cardArr[1], "", "", 0, 0, 0, 0, 0, "", 0, 0, 0, 0, 0, 0, heatmapFunction, heatmapColorMap);
        try {
          // Append counters HTML generated from CounterRules (if defined for this zone)
          newHTML += CreateCountersHTML(zoneName, cardArr, id);
        } catch (e) {
          if (console && console.error) console.error('CreateCountersHTML error', e);
        }

        var buttons = createWidgetButtons(zoneName, id, cardArr[2]);
        newHTML += "<span class='widget-buttons' style='z-index:1000; display: none; justify-content: center; position:absolute; top:50%; left:50%; transform: translate(-50%, -50%);'>" + buttons.middleButtons + "</span>";
        newHTML += "<div class='widget-buttons' style='display: none; position:absolute; top:0; right:0; z-index:1001;'>" + buttons.topRightButtons + "</div>";
        newHTML += "</span>";
        return newHTML;
      }

        // Create counters HTML for a single card based on CounterRules
        function CreateCountersHTML(zoneName, cardArr, id) {
          try {
            // Prefer CounterRules constant (emitted by generator). Fall back to zone metadata (in case GeneratedUI wasn't included).
            var rules = null;
            if (typeof CounterRules !== 'undefined' && CounterRules[zoneName]) rules = CounterRules[zoneName];
            else {
              try {
                var zoneMeta = GetZoneData(zoneName);
                if (zoneMeta && zoneMeta.Counters) rules = zoneMeta.Counters;
              } catch (e) {
                rules = null;
              }
            }
            if (!rules || !Array.isArray(rules) || rules.length == 0) return "";
            var cardData = {};
            if (cardArr.length > 2 && cardArr[2] && cardArr[2] !== '-') {
              try { cardData = JSON.parse(cardArr[2]); } catch (e) { cardData = {}; }
            }
            var html = "";
            // container is positioned relative via the card wrapper
            for (var r = 0; r < rules.length; ++r) {
              var rule = rules[r];
              var field = rule.field;
              var type = rule.type || "Badge";
              var params = rule.params || {};
              var value = cardData.hasOwnProperty(field) ? cardData[field] : null;
              if (value === null || value === undefined) continue;
              // normalize numeric strings
              if (!isNaN(value)) value = Number(value);
              // Handle ShowZero
              if (params.ShowZero !== undefined && String(params.ShowZero).toLowerCase() === 'false' && Number(value) === 0) continue;
              // Determine visual style
              var sizePx = 22;
              var bg = params.Color ? params.Color : 'red';
              var textColor = '#fff';
              var posStyle = 'top:6px; right:6px;';
              var pos = params.Position ? params.Position.toLowerCase() : 'topright';
              switch(pos) {
                case 'topleft': posStyle = 'top:6px; left:6px;'; break;
                case 'topright': posStyle = 'top:6px; right:6px;'; break;
                case 'bottomleft': posStyle = 'bottom:6px; left:6px;'; break;
                case 'bottomright': posStyle = 'bottom:6px; right:6px;'; break;
                case 'center': posStyle = 'top:50%; left:50%; transform: translate(-50%, -50%);'; break;
                default: posStyle = 'top:6px; right:6px;';
              }
              if (type.toLowerCase() === 'badge') {
                html += "<div data-counter-field='" + field + "' style='position:absolute; z-index:1100; " + posStyle + " width:" + sizePx + "px; height:" + sizePx + "px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + "; background:" + bg + "; box-shadow: 0 0 6px rgba(0,0,0,0.6);'>" + value + "</div>";
              } else if (type.toLowerCase() === 'icon') {
                // If an icon name is provided as positional param, use it as img src fallback
                var icon = params.Icon || (params[0] ? params[0] : null);
                if (icon) {
                  html += "<div data-counter-field='" + field + "' style='position:absolute; z-index:1100; " + posStyle + " display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + ";'>";
                  html += "<img src='./Images/" + icon + ".png' style='width:" + sizePx + "px; height:" + sizePx + "px; object-fit:contain; filter: drop-shadow(0 0 3px rgba(0,0,0,0.6));'/>";
                  html += "<div style='position:absolute; font-size:11px; font-weight:700; color:" + textColor + "; margin-top:12px; text-shadow: 0 0 3px rgba(0,0,0,0.6);'>" + value + "</div>";
                  html += "</div>";
                } else {
                  // fallback to badge
                  html += "<div data-counter-field='" + field + "' style='position:absolute; z-index:1100; " + posStyle + " width:" + sizePx + "px; height:" + sizePx + "px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + "; background:" + bg + "; box-shadow: 0 0 6px rgba(0,0,0,0.6);'>" + value + "</div>";
                }
              } else {
                // unknown type -> render as badge
                html += "<div data-counter-field='" + field + "' style='position:absolute; z-index:1100; " + posStyle + " width:" + sizePx + "px; height:" + sizePx + "px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-family: Orbitron, sans-serif; font-size:12px; color:" + textColor + "; background:" + bg + "; box-shadow: 0 0 6px rgba(0,0,0,0.6);'>" + value + "</div>";
              }
            }
            return html;
          } catch (e) {
            if (console && console.error) console.error('CreateCountersHTML error', e);
            return "";
          }
        }

      // Add this CSS to your stylesheet for the hover effect
      const widgetstyle = document.createElement('style');
      widgetstyle.innerHTML = `
        span.draggable:hover .widget-buttons {
          display: flex !important;
        }
      `;
      document.head.appendChild(widgetstyle);

      function createWidgetButtons(zoneName, cardId, cardJSON="-", currentValue="") {
        const cardData = cardJSON != "-" ? JSON.parse(cardJSON) : {};
        const widgets = GetZoneWidgets(zoneName);
        let buttons = {};
        let buttonsHtml = '';
        let topRightButtons = '';
        for (const widgetType in widgets) {
          widgets[widgetType].forEach(widget => {
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
              widgetContent = widgetIcons(widgetName);
              if(widgetName == "Notes") {
                topRightButtons += `&nbsp;<button class="widget-button" onclick="handleWidgetAction(event, '${cardId}', '${widgetType}', '${widget.Action}')">${widgetContent}</button>`;
              } else {
                if(currentValue != "" && widget.Action == currentValue)
                  buttonsHtml += `&nbsp;<button class="widget-button-selected" onclick="handleWidgetAction(event, '${cardId}', '${widgetType}', '${widget.Action}')">${widgetContent}</button>`;
                else
                  buttonsHtml += `&nbsp;<button class="widget-button" onclick="handleWidgetAction(event, '${cardId}', '${widgetType}', '${widget.Action}')">${widgetContent}</button>`;
              }
            }
          });
        }
        buttons.middleButtons = buttonsHtml;
        buttons.topRightButtons = topRightButtons;
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
          background-color: #333; /* Dark Grey */
          border: none;
          color: white;
          padding: 2px 3px; /* Added padding */
          min-width:14px;
          text-align: center;
          text-decoration: none;
          display: inline-block;
          font-size: 14px;
          margin: 0px 0px;
          transition-duration: 0.4s;
          cursor: pointer;
          border-radius: 3px;
        }

        .widget-button:hover {
          background-color: white;
          color: black;
          border: 2px solid #333;
        }

        .widget-button-selected {
          background-color: #ccc; /* Light Grey */
          border: none;
          color: black;
          padding: 2px 4px; /* Added padding */
          min-width:14px;
          text-align: center;
          text-decoration: none;
          display: inline-block;
          font-size: 14px;
          margin: 0px 0px;
          transition-duration: 0.4s;
          cursor: pointer;
          border-radius: 3px;
        }        .widget-button-selected:hover {
          background-color: white;
          color: black;
          border: 2px solid #ccc;
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
            box-shadow: 0 2px 5px rgba(100, 250, 0, 0.18);
          }

          /* Image-level border and inner glow (so it sits flush with art) */
          .selectable-card img {
            border-radius: 6px;
            display: block;
            position: relative;
            z-index: 1;
            /* tight inset glow that hugs the artwork */
            box-shadow: inset 0 0 14px rgba(220,255,140,1), inset 0 0 6px rgba(200,255,120,0.8);
            /* border lives on the image so it sits flush with the art; make slightly thicker */
            border: 3px solid rgba(100,250,0,0.50);
            transition: box-shadow 140ms ease, border-color 140ms ease, transform 160ms ease;
          }

          /* On hover: hide the border visually and show a compact outer box-shadow */
          .selectable-card:hover img {
            /* keep the inner inset but add a modest outer shadow that hugs the card */
            box-shadow: inset 0 0 20px rgba(245,255,180,0.72), inset 0 0 10px rgba(230,255,160,0.48), 0 10px 22px rgba(200,255,120,0.28);
            /* visually remove the border without causing layout shift */
            border-color: transparent;
          }

          /* Gentle pulsing lime glow when selection mode is active (pulse the image border color) */
          @keyframes selectable-border-pulse {
            0% { border-color: rgba(100,250,0,1); border-width: 2px; }
            50% { border-color: rgba(65, 163, 0, 1); border-width: 2px; }
            100% { border-color: rgba(100,250,0,1); border-width: 2px; }
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
          helpText += `- ${property.Name} (${property.Type})\n`;
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
        var scrollPosition = window[fullName + "ScrollPosition"] || 0;
        var html = "<div style='display: flex; justify-content: center; width:100%; overflow-y: auto;'>";
        html += `<div style='position: relative; display: inline-block;'>`;
        setTimeout(() => {
          document.getElementById(fullName + "Wrapper").scrollTop = scrollPosition;
        }, 0);
        html += `<input type="text" style='height:28px; margin-top:3px;' class='filterBar' id="${fullName}FilterText" onkeydown="PaneFilterKeyDown('${prefix}', '${zoneName}', event);" oninput="PaneFilterCards('${prefix}', '${zoneName}', event, 'textFilter');" placeholder="Filter cards..." ${filterText ? `value="${filterText}"` : ''}></input>`;
        html += `<img src='./Assets/Images/infoicon.png' style='cursor: pointer; position: absolute; top: 2px; right: 2px; height:12px; width:12px;' onclick='ShowFilterBarHelp()' aria-label='Click for filter syntax' />`;
        html += `</div>`;
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
          <label for='customFilterCheckbox' style='margin-left: 5px;'></label>
              </div>`;
        }
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

      function AppendStaticZones(myStatic, theirStatic) {
        var myDiv = document.getElementById("myStuff");
        var theirDiv = document.getElementById("theirStuff");
        myDiv.innerHTML += myStatic;
        theirDiv.innerHTML += theirStatic;
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

      function OnVersionChanged(newVersion) {
        SubmitInput("10003", "&cardID=" + newVersion, true);
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
  prompt.textContent = decision.Param && decision.Param !== '-' ? decision.Param : 'Please choose Yes or No:';
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
        SubmitInput('DECISION', '&decisionIndex=' + i + '&result=' + result);
      });
      break;
    } else if (entry && entry.Type === 'MZCHOOSE' && !entry.removed) {
      // Set up selection mode
      window.SelectionMode.active = true;
      window.SelectionMode.mode = '100';
      window.SelectionMode.allowedZones = (entry.Param || '').split('&').map(z => z.trim()).filter(Boolean);
      window.SelectionMode.decisionIndex = i;
      window.SelectionMode.callback = function(zoneName, cardId, decisionIndex) {
        SubmitInput('DECISION', '&decisionIndex=' + decisionIndex + '&cardID=' + encodeURIComponent(cardId));
      };
      var tooltip = (entry.Tooltip && entry.Tooltip !== '-') ? entry.Tooltip.replace(/_/g, ' ') : 'Select a card from an allowed zone.';
      ShowSelectionMessage(tooltip);
      // Highlight/selectable will be handled in rendering

      // After setting selection mode for MZCHOOSE, force a re-render of all zones
      if (typeof RenderRows === 'function' && typeof window.myRows !== 'undefined' && typeof window.theirRows !== 'undefined') {
        RenderRows(window.myRows, window.theirRows);
      }
          // Add gentle pulsing glow to selectable cards after re-render (DOM needs a moment)
          setTimeout(() => {
            document.querySelectorAll('.selectable-card').forEach(el => el.classList.add('pulse'));
          }, 60);
      break;
    }
  }
};

// --- Selection Mode State ---
window.SelectionMode = {
  active: false,
  mode: '', // e.g., '100' for decision queue
  allowedZones: [],
  callback: null,
  decisionIndex: null
};

function ClearSelectionMode() {
  window.SelectionMode = {
    active: false,
    mode: '',
    allowedZones: [],
    callback: null,
    decisionIndex: null
  };
  // Remove selectable highlight from all cards
  document.querySelectorAll('.selectable-card').forEach(el => {
    el.classList.remove('selectable-card');
    el.classList.remove('pulse');
    el.onclick = null;
  });
  HideSelectionMessage();
}

function ShowSelectionMessage(msg) {
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
    document.body.appendChild(existing);
  }
  existing.textContent = msg;
  existing.style.display = 'block';
}

function HideSelectionMessage() {
  let existing = document.getElementById('selection-message');
  if (existing) existing.style.display = 'none';
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

