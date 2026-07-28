(function(global) {
  'use strict';

  function activateWithKeyboard(element, callback) {
    element.setAttribute('role', 'button');
    element.setAttribute('tabindex', '0');
    element.addEventListener('click', callback);
    element.addEventListener('keydown', function(event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        callback(event);
      }
    });
  }

  function confirmDelete(versionNumber) {
    var promptText = 'Delete Version ' + versionNumber
      + '? Its children will be reparented and its aggregate stats will be deleted.';
    return typeof global.StyledConfirm === 'function'
      ? global.StyledConfirm(promptText, {
          title: 'Delete version',
          danger: true,
          confirmLabel: 'Delete'
        })
      : Promise.resolve(global.confirm(promptText));
  }

  function request(config, action, versionID, fields) {
    var params = new URLSearchParams({
      folderPath: config.folderPath,
      assetID: String(config.assetID),
      action: action
    });
    var options = { credentials: 'same-origin' };
    var url = '/TCGEngine/APIs/AssetVersions.php';
    if (action === 'list') {
      url += '?' + params.toString();
    } else {
      params.set('versionID', String(versionID));
      Object.keys(fields || {}).forEach(function(key) {
        params.set(key, String(fields[key]));
      });
      options.method = 'POST';
      options.headers = { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' };
      options.body = params.toString();
    }
    return fetch(url, options).then(function(response) {
      return response.json().catch(function() { return {}; }).then(function(payload) {
        if (!response.ok || !payload.success) {
          throw new Error(payload.error || 'Version history is unavailable.');
        }
        return payload;
      });
    });
  }

  function createAction(label, className, callback) {
    var action = document.createElement('span');
    action.className = className;
    action.textContent = label;
    activateWithKeyboard(action, function(event) {
      event.stopPropagation();
      callback();
    });
    return action;
  }

  function startRename(config, version, nameElement) {
    var row = nameElement.closest('.asset-version-row');
    if (!row || row.querySelector('.asset-version-rename-editor')) return;

    var originalName = version.versionName || ('Version ' + version.versionNumber);
    var editor = document.createElement('span');
    editor.className = 'asset-version-rename-editor';
    var input = document.createElement('input');
    input.className = 'asset-version-rename-input';
    input.type = 'text';
    input.maxLength = 255;
    input.value = originalName;
    input.setAttribute('aria-label', 'Version name');
    var save = createAction('Save', 'asset-version-rename-save', submitRename);
    var cancel = createAction('Cancel', 'asset-version-rename-cancel', cancelRename);
    editor.appendChild(input);
    editor.appendChild(save);
    editor.appendChild(cancel);
    nameElement.replaceWith(editor);

    function restoreName(nextName) {
      var depth = Math.max(0, Number(version.depth) || 0);
      nameElement.textContent = (depth > 0 ? '\u21b3 ' : '') + nextName;
      editor.replaceWith(nameElement);
    }

    function cancelRename() {
      restoreName(originalName);
    }

    function submitRename() {
      var nextName = input.value.trim();
      if (!nextName) {
        showError(new Error('Enter a version name.'));
        input.focus();
        return;
      }
      input.disabled = true;
      request(config, 'rename', version.versionID, { versionName: nextName })
        .then(function(payload) {
          var savedName = payload.versionName || nextName;
          version.versionName = savedName;
          restoreName(savedName);
          var currentLabel = document.getElementById('versionDropdownLabel');
          if (currentLabel && currentLabel.textContent === originalName) {
            currentLabel.textContent = savedName;
          }
        })
        .catch(function(error) {
          input.disabled = false;
          showError(error);
          input.focus();
        });
    }

    input.addEventListener('click', function(event) {
      event.stopPropagation();
    });
    input.addEventListener('keydown', function(event) {
      event.stopPropagation();
      if (event.key === 'Enter') {
        event.preventDefault();
        submitRename();
      } else if (event.key === 'Escape') {
        event.preventDefault();
        cancelRename();
      }
    });
    input.focus();
    input.select();
  }

  function renderDelta(delta, version) {
    var segments = Array.isArray(version.deltaSegments) ? version.deltaSegments : [];
    if (!segments.length) {
      delta.textContent = version.deltaText || '';
      return;
    }

    segments.forEach(function(segment) {
      var text = document.createElement('span');
      text.textContent = segment && segment.text ? String(segment.text) : '';
      if (segment && segment.itemID && segment.previewURL) {
        text.className = 'asset-version-card-link';
        var previewImage = document.createElement('img');
        previewImage.className = 'asset-version-preview-image';
        previewImage.src = String(segment.previewURL);
        previewImage.alt = '';
        text.appendChild(previewImage);
        text.addEventListener('mouseenter', function(event) {
          if (typeof ShowCardDetail === 'function') {
            ShowCardDetail(event, text);
          }
        });
        text.addEventListener('mouseleave', function() {
          if (typeof HideCardDetail === 'function') HideCardDetail();
        });
      }
      delta.appendChild(text);
    });
  }

  function render(config, versions) {
    var menu = document.getElementById('versionDropdownMenu');
    if (!menu) return;
    menu.innerHTML = '';

    var current = document.createElement('div');
    current.className = 'asset-version-current';
    current.textContent = 'Current Version';
    activateWithKeyboard(current, function() {
      if (typeof global.selectVersion === 'function') {
        global.selectVersion('current', 'Current Version');
      }
    });
    menu.appendChild(current);

    if (!versions.length) {
      var empty = document.createElement('div');
      empty.className = 'asset-version-empty';
      empty.textContent = 'The first version will be created when this asset records a completed game.';
      menu.appendChild(empty);
      return;
    }

    versions.forEach(function(version) {
      var label = version.versionName || ('Version ' + version.versionNumber);
      var depth = Math.max(0, Number(version.depth) || 0);
      var row = document.createElement('div');
      row.className = 'asset-version-row';
      row.style.paddingLeft = (12 + depth * 18) + 'px';
      row.setAttribute('data-version-id', String(version.versionID));

      var copy = document.createElement('span');
      copy.className = 'asset-version-copy';
      var heading = document.createElement('span');
      heading.className = 'asset-version-heading';
      var name = document.createElement('strong');
      name.className = 'asset-version-name';
      name.textContent = (depth > 0 ? '\u21b3 ' : '') + label;
      var record = document.createElement('span');
      record.className = 'asset-version-record';
      record.textContent = version.wins + ' W \u00b7 ' + version.losses + ' L';
      heading.appendChild(name);
      heading.appendChild(record);

      var delta = document.createElement('span');
      delta.className = 'asset-version-delta';
      renderDelta(delta, version);
      copy.appendChild(heading);
      copy.appendChild(delta);

      var actions = document.createElement('span');
      actions.className = 'asset-version-actions';
      actions.appendChild(createAction('Rename', 'asset-version-rename', function() {
        startRename(config, version, name);
      }));
      actions.appendChild(createAction('Load', 'asset-version-load', function() {
        var currentLabel = version.versionName || ('Version ' + version.versionNumber);
        if (typeof global.selectVersion === 'function') {
          global.selectVersion('auto:' + version.versionID, currentLabel);
        }
      }));
      actions.appendChild(createAction('\u2715', 'asset-version-delete', function() {
        if (typeof global.closeVersionDropdown === 'function') global.closeVersionDropdown();
        confirmDelete(version.versionNumber).then(function(confirmed) {
          if (!confirmed) return;
          request(config, 'delete', version.versionID)
            .then(function() { global.location.reload(); })
            .catch(showError);
        });
      }));

      row.appendChild(copy);
      row.appendChild(actions);
      menu.appendChild(row);
    });
  }

  function showError(error) {
    if (typeof global.showFlashMessage === 'function') global.showFlashMessage(error.message, 6000);
    else global.alert(error.message);
  }

  function mount(options) {
    var config = Object.assign({
      container: '#versionDropdownWrapper',
      folderPath: ''
    }, options || {});
    var wrapper = document.querySelector(config.container);
    var gameNameInput = document.getElementById('gameName');
    if (!wrapper || !config.folderPath) return;
    config.assetID = Number(config.assetID || (gameNameInput ? gameNameInput.value : 0));
    if (!config.assetID) return;
    wrapper.setAttribute('data-auto-versioning', '1');

    var menu = document.getElementById('versionDropdownMenu');
    if (menu) {
      menu.innerHTML = '';
      var loading = document.createElement('div');
      loading.className = 'asset-version-empty';
      loading.textContent = 'Loading version history\u2026';
      menu.appendChild(loading);
    }

    request(config, 'list')
      .then(function(payload) { render(config, payload.versions || []); })
      .catch(function(error) {
        if (!menu) return;
        menu.innerHTML = '';
        var message = document.createElement('div');
        message.className = 'asset-version-empty';
        message.textContent = error.message;
        menu.appendChild(message);
      });
  }

  global.AssetVersioningUI = { mount: mount };
})(window);
