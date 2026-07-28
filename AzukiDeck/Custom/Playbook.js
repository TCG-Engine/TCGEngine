(function () {
  'use strict';

  var config = window.AzukiDeckPlaybookConfig;
  if (!config || !config.deckID || document.getElementById('azukiPlaybookOverlay')) return;

  var state = {
    playbook: { schemaVersion: 1, revision: 0, updatedAt: null, lines: [] },
    selectedLineID: '',
    pickerStepID: '',
    pickerQuery: '',
    mode: 'edit',
    saving: false,
    pendingSave: false,
    saveTimer: 0,
    loaded: false
  };

  function makeID(prefix) {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return prefix + '-' + window.crypto.randomUUID().replace(/-/g, '');
    }
    return prefix + '-' + Date.now().toString(36) + Math.random().toString(36).slice(2);
  }

  function cardName(cardID) {
    if (typeof window.Cardname === 'function') {
      var resolved = window.Cardname(cardID);
      if (resolved) return String(resolved);
    }
    return String(cardID || 'Unknown card');
  }

  function cardImageURL(cardID) {
    return String(config.cardImageBase || '/TCGEngine/AzukiSim/WebpImages/')
      + encodeURIComponent(cardID) + '.webp';
  }

  function parseZoneCards(data) {
    if (!data) return [];
    return String(data).split('<|>').map(function (entry) {
      return entry.trim().split(/\s+/)[0] || '';
    }).filter(Boolean);
  }

  function deckCards() {
    var cards = []
      .concat(parseZoneCards(window.myLeaderData))
      .concat(parseZoneCards(window.myGateData))
      .concat(parseZoneCards(window.myMainDeckData))
      .concat(parseZoneCards(window.mySideboardData));
    return cards.filter(function (cardID, index) {
      return cards.indexOf(cardID) === index;
    }).sort(function (left, right) {
      return cardName(left).localeCompare(cardName(right));
    });
  }

  function selectedLine() {
    return state.playbook.lines.find(function (line) {
      return line.id === state.selectedLineID;
    }) || null;
  }

  function selectedStep() {
    var line = selectedLine();
    if (!line) return null;
    return line.steps.find(function (step) {
      return step.id === state.pickerStepID;
    }) || null;
  }

  function scrollEditorTop() {
    var editor = document.getElementById('azukiPlaybookEditor');
    if (editor) editor.scrollTop = 0;
  }

  function button(label, className, onClick, ariaLabel) {
    var element = document.createElement('button');
    element.type = 'button';
    element.className = 'azuki-playbook-button' + (className ? ' ' + className : '');
    element.textContent = label;
    if (ariaLabel) element.setAttribute('aria-label', ariaLabel);
    if (onClick) element.addEventListener('click', onClick);
    return element;
  }

  function fieldLabel(text, input) {
    var label = document.createElement('label');
    label.className = 'azuki-playbook-label';
    label.textContent = text;
    if (input && input.id) label.htmlFor = input.id;
    return label;
  }

  function textInput(className, value, placeholder, onInput) {
    var input = document.createElement('input');
    input.type = 'text';
    input.className = 'azuki-playbook-input' + (className ? ' ' + className : '');
    input.value = value || '';
    input.placeholder = placeholder || '';
    input.addEventListener('input', function () {
      onInput(input.value);
      scheduleSave();
    });
    return input;
  }

  function textArea(value, placeholder, onInput) {
    var input = document.createElement('textarea');
    input.className = 'azuki-playbook-textarea';
    input.value = value || '';
    input.placeholder = placeholder || '';
    input.addEventListener('input', function () {
      onInput(input.value);
      scheduleSave();
    });
    return input;
  }

  function setSaveState(message, isError) {
    var target = document.getElementById('azukiPlaybookSaveState');
    if (!target) return;
    target.textContent = message;
    target.classList.toggle('is-error', !!isError);
  }

  function scheduleSave() {
    if (!state.loaded) return;
    window.clearTimeout(state.saveTimer);
    setSaveState('Unsaved changes', false);
    state.saveTimer = window.setTimeout(save, 650);
  }

  function save() {
    window.clearTimeout(state.saveTimer);
    if (state.saving) {
      state.pendingSave = true;
      return;
    }

    state.saving = true;
    state.pendingSave = false;
    setSaveState('Saving…', false);
    var revision = state.playbook.revision || 0;
    var snapshot = JSON.parse(JSON.stringify(state.playbook));

    fetch(config.endpoint + '?deckID=' + encodeURIComponent(config.deckID), {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ revision: revision, playbook: snapshot })
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (payload) {
        if (!response.ok || !payload.success) {
          var error = new Error(payload.error || 'The playbook could not be saved.');
          error.status = response.status;
          throw error;
        }
        state.playbook.revision = payload.playbook.revision;
        state.playbook.updatedAt = payload.playbook.updatedAt;
        setSaveState('All changes saved', false);
      });
    }).catch(function (error) {
      if (error.status === 409) {
        setSaveState('Changed in another tab — close and reopen Lines', true);
      } else {
        setSaveState(error.message || 'Save failed', true);
      }
    }).finally(function () {
      state.saving = false;
      if (state.pendingSave) save();
    });
  }

  function createLine() {
    var line = {
      id: makeID('line'),
      title: 'Untitled line',
      summary: '',
      notes: '',
      shared: false,
      steps: [],
      values: []
    };
    state.playbook.lines.push(line);
    state.selectedLineID = line.id;
    render();
    scheduleSave();
    var title = document.getElementById('azukiPlaybookTitleInput');
    if (title) {
      title.focus();
      title.select();
    }
  }

  function duplicateLine() {
    var source = selectedLine();
    if (!source) return;
    var copy = JSON.parse(JSON.stringify(source));
    copy.id = makeID('line');
    copy.title += ' — variant';
    copy.steps.forEach(function (step) { step.id = makeID('step'); });
    copy.values.forEach(function (value) { value.id = makeID('value'); });
    state.playbook.lines.push(copy);
    state.selectedLineID = copy.id;
    render();
    scheduleSave();
  }

  function deleteLine() {
    var line = selectedLine();
    if (!line) return;
    StyledConfirm('Delete "' + line.title + '"?', { title: 'Delete line', danger: true, confirmLabel: 'Delete' }).then(function (ok) {
      if (!ok) return;
      state.playbook.lines = state.playbook.lines.filter(function (candidate) {
        return candidate.id !== line.id;
      });
      state.selectedLineID = state.playbook.lines.length ? state.playbook.lines[0].id : '';
      render();
      scheduleSave();
    });
  }

  function createStep() {
    var line = selectedLine();
    if (!line) return;
    line.steps.push({
      id: makeID('step'),
      text: '',
      cards: []
    });
    renderEditor();
    scheduleSave();
    var areas = document.querySelectorAll('.azuki-playbook-step .azuki-playbook-textarea');
    if (areas.length) areas[areas.length - 1].focus();
  }

  function moveStep(index, direction) {
    var line = selectedLine();
    if (!line) return;
    var target = index + direction;
    if (target < 0 || target >= line.steps.length) return;
    var moved = line.steps[index];
    line.steps[index] = line.steps[target];
    line.steps[target] = moved;
    renderEditor();
    scheduleSave();
  }

  function deleteStep(stepID) {
    var line = selectedLine();
    if (!line) return;
    line.steps = line.steps.filter(function (step) { return step.id !== stepID; });
    renderEditor();
    scheduleSave();
  }

  function addValue() {
    var line = selectedLine();
    if (!line) return;
    line.values.push({
      id: makeID('value'),
      label: '',
      value: '',
      unit: ''
    });
    renderEditor();
    scheduleSave();
    var labels = document.querySelectorAll('.azuki-playbook-value .azuki-playbook-input');
    if (labels.length >= 3) labels[labels.length - 3].focus();
  }

  function deleteValue(valueID) {
    var line = selectedLine();
    if (!line) return;
    line.values = line.values.filter(function (value) { return value.id !== valueID; });
    renderEditor();
    scheduleSave();
  }

  function openPicker(stepID) {
    state.pickerStepID = stepID;
    state.pickerQuery = '';
    var search = document.getElementById('azukiPlaybookPickerSearch');
    if (search) search.value = '';
    renderPicker();
    document.getElementById('azukiPlaybookPicker').classList.add('is-open');
    if (search) search.focus();
  }

  function closePicker() {
    state.pickerStepID = '';
    document.getElementById('azukiPlaybookPicker').classList.remove('is-open');
    renderEditor();
  }

  function toggleCard(cardID) {
    var step = selectedStep();
    if (!step) return;
    var index = step.cards.indexOf(cardID);
    if (index >= 0) step.cards.splice(index, 1);
    else step.cards.push(cardID);
    renderPicker();
    scheduleSave();
  }

  function removeCard(step, cardID) {
    step.cards = step.cards.filter(function (candidate) { return candidate !== cardID; });
    renderEditor();
    scheduleSave();
  }

  function renderLineList() {
    var target = document.getElementById('azukiPlaybookLines');
    target.replaceChildren();
    state.playbook.lines.forEach(function (line) {
      var item = document.createElement('button');
      item.type = 'button';
      item.className = 'azuki-playbook-line' + (line.id === state.selectedLineID ? ' is-selected' : '');
      var title = document.createElement('span');
      title.className = 'azuki-playbook-line-title';
      title.textContent = line.title || 'Untitled line';
      var meta = document.createElement('span');
      meta.className = 'azuki-playbook-line-meta';
      meta.textContent = line.steps.length + (line.steps.length === 1 ? ' step' : ' steps')
        + (line.shared ? ' · shared' : ' · private');
      item.appendChild(title);
      item.appendChild(meta);
      item.addEventListener('click', function () {
        state.selectedLineID = line.id;
        render();
        scrollEditorTop();
      });
      target.appendChild(item);
    });
    var toolbarButton = document.getElementById('azukiPlaybookButton');
    if (toolbarButton) toolbarButton.textContent = 'Lines' + (state.playbook.lines.length ? ' (' + state.playbook.lines.length + ')' : '');
    var mobileSelect = document.getElementById('azukiPlaybookMobileSelect');
    if (mobileSelect) {
      mobileSelect.replaceChildren();
      if (!state.playbook.lines.length) {
        var emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'No lines yet';
        mobileSelect.appendChild(emptyOption);
      }
      state.playbook.lines.forEach(function (line) {
        var option = document.createElement('option');
        option.value = line.id;
        option.textContent = line.title || 'Untitled line';
        option.selected = line.id === state.selectedLineID;
        mobileSelect.appendChild(option);
      });
    }
  }

  function renderCardReference(step, cardID) {
    var ref = document.createElement('div');
    ref.className = 'azuki-playbook-card-ref';
    var image = document.createElement('img');
    image.src = cardImageURL(cardID);
    image.alt = cardName(cardID);
    image.loading = 'lazy';
    image.addEventListener('error', function () { image.style.visibility = 'hidden'; });
    if (typeof window.ShowDetail === 'function') {
      image.addEventListener('mouseenter', function (event) {
        window.ShowDetail(event, image.src);
      });
      image.addEventListener('mouseleave', function () {
        if (typeof window.HideCardDetail === 'function') window.HideCardDetail();
      });
    }
    var remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'azuki-playbook-card-remove';
    remove.textContent = '×';
    remove.setAttribute('aria-label', 'Remove ' + cardName(cardID));
    remove.addEventListener('click', function () { removeCard(step, cardID); });
    var name = document.createElement('span');
    name.className = 'azuki-playbook-card-name';
    name.textContent = cardName(cardID);
    ref.appendChild(image);
    ref.appendChild(remove);
    ref.appendChild(name);
    return ref;
  }

  function renderPreviewCard(cardID) {
    var card = document.createElement('figure');
    card.className = 'azuki-playbook-preview-card';
    var image = document.createElement('img');
    image.src = cardImageURL(cardID);
    image.alt = cardName(cardID);
    image.loading = 'lazy';
    image.addEventListener('error', function () { image.style.visibility = 'hidden'; });
    if (typeof window.ShowDetail === 'function') {
      image.addEventListener('mouseenter', function (event) {
        window.ShowDetail(event, image.src);
      });
      image.addEventListener('mouseleave', function () {
        if (typeof window.HideCardDetail === 'function') window.HideCardDetail();
      });
    }
    var caption = document.createElement('figcaption');
    caption.textContent = cardName(cardID);
    card.appendChild(image);
    card.appendChild(caption);
    return card;
  }

  function renderPreview(line) {
    var preview = document.createElement('article');
    preview.className = 'azuki-playbook-preview';

    var hero = document.createElement('header');
    hero.className = 'azuki-playbook-preview-hero';
    var eyebrow = document.createElement('div');
    eyebrow.className = 'azuki-playbook-eyebrow';
    eyebrow.textContent = line.shared ? 'Shared line' : 'Private line';
    var title = document.createElement('h2');
    title.textContent = line.title || 'Untitled line';
    hero.appendChild(eyebrow);
    hero.appendChild(title);
    if (line.summary) {
      var summary = document.createElement('p');
      summary.textContent = line.summary;
      hero.appendChild(summary);
    }
    preview.appendChild(hero);

    var sequence = document.createElement('section');
    sequence.className = 'azuki-playbook-preview-section';
    var sequenceTitle = document.createElement('h3');
    sequenceTitle.textContent = 'Sequence';
    sequence.appendChild(sequenceTitle);
    if (!line.steps.length) {
      var emptySequence = document.createElement('p');
      emptySequence.className = 'azuki-playbook-preview-muted';
      emptySequence.textContent = 'No steps have been added to this line.';
      sequence.appendChild(emptySequence);
    } else {
      var stepList = document.createElement('div');
      stepList.className = 'azuki-playbook-preview-steps';
      line.steps.forEach(function (step, index) {
        var stepRow = document.createElement('section');
        stepRow.className = 'azuki-playbook-preview-step';
        var marker = document.createElement('div');
        marker.className = 'azuki-playbook-preview-marker';
        marker.textContent = String(index + 1);
        var content = document.createElement('div');
        content.className = 'azuki-playbook-preview-step-content';
        var stepText = document.createElement('p');
        stepText.textContent = step.text || 'No instruction entered.';
        content.appendChild(stepText);
        if (step.cards.length) {
          var cards = document.createElement('div');
          cards.className = 'azuki-playbook-preview-cards';
          step.cards.forEach(function (cardID) {
            cards.appendChild(renderPreviewCard(cardID));
          });
          content.appendChild(cards);
        }
        stepRow.appendChild(marker);
        stepRow.appendChild(content);
        stepList.appendChild(stepRow);
      });
      sequence.appendChild(stepList);
    }
    preview.appendChild(sequence);

    if (line.values.length) {
      var valuesSection = document.createElement('section');
      valuesSection.className = 'azuki-playbook-preview-section';
      var valuesTitle = document.createElement('h3');
      valuesTitle.textContent = 'Values';
      var values = document.createElement('dl');
      values.className = 'azuki-playbook-preview-values';
      line.values.forEach(function (field) {
        var item = document.createElement('div');
        var term = document.createElement('dt');
        term.textContent = field.label || 'Untitled value';
        var detail = document.createElement('dd');
        detail.textContent = (field.value || '—') + (field.unit ? ' ' + field.unit : '');
        item.appendChild(term);
        item.appendChild(detail);
        values.appendChild(item);
      });
      valuesSection.appendChild(valuesTitle);
      valuesSection.appendChild(values);
      preview.appendChild(valuesSection);
    }

    if (line.notes) {
      var notesSection = document.createElement('section');
      notesSection.className = 'azuki-playbook-preview-section';
      var notesTitle = document.createElement('h3');
      notesTitle.textContent = 'Notes';
      var notes = document.createElement('p');
      notes.className = 'azuki-playbook-preview-notes';
      notes.textContent = line.notes;
      notesSection.appendChild(notesTitle);
      notesSection.appendChild(notes);
      preview.appendChild(notesSection);
    }

    return preview;
  }

  function renderStep(step, index, count) {
    var article = document.createElement('article');
    article.className = 'azuki-playbook-step';

    var header = document.createElement('div');
    header.className = 'azuki-playbook-step-header';
    var number = document.createElement('span');
    number.className = 'azuki-playbook-step-index';
    number.textContent = String(index + 1);
    var actions = document.createElement('div');
    actions.className = 'azuki-playbook-step-actions';
    var up = button('↑', 'is-ghost azuki-playbook-icon-button', function () { moveStep(index, -1); }, 'Move step up');
    up.disabled = index === 0;
    var down = button('↓', 'is-ghost azuki-playbook-icon-button', function () { moveStep(index, 1); }, 'Move step down');
    down.disabled = index === count - 1;
    var remove = button('×', 'is-ghost is-danger azuki-playbook-icon-button', function () { deleteStep(step.id); }, 'Delete step');
    actions.appendChild(up);
    actions.appendChild(down);
    actions.appendChild(remove);
    header.appendChild(number);
    header.appendChild(actions);

    var input = textArea(step.text, 'Describe an action, decision, or condition…', function (value) {
      step.text = value;
    });
    input.setAttribute('aria-label', 'Step ' + (index + 1));

    var strip = document.createElement('div');
    strip.className = 'azuki-playbook-card-strip';
    step.cards.forEach(function (cardID) {
      strip.appendChild(renderCardReference(step, cardID));
    });
    var addCard = document.createElement('button');
    addCard.type = 'button';
    addCard.className = 'azuki-playbook-card-add';
    addCard.textContent = '+ Add cards';
    addCard.addEventListener('click', function () { openPicker(step.id); });
    strip.appendChild(addCard);

    article.appendChild(header);
    article.appendChild(input);
    article.appendChild(strip);
    return article;
  }

  function renderEditor() {
    var editor = document.getElementById('azukiPlaybookEditor');
    editor.replaceChildren();
    if (!state.loaded) {
      var loading = document.createElement('div');
      loading.className = 'azuki-playbook-empty';
      var loadingHeading = document.createElement('h3');
      loadingHeading.textContent = 'Loading playbook…';
      loading.appendChild(loadingHeading);
      editor.appendChild(loading);
      return;
    }
    var line = selectedLine();
    if (!line) {
      var empty = document.createElement('div');
      empty.className = 'azuki-playbook-empty';
      var heading = document.createElement('h3');
      heading.textContent = 'Build your first line of play';
      var copy = document.createElement('div');
      copy.textContent = 'Add ordered steps, reference cards from this deck, and record whatever values matter to you.';
      empty.appendChild(heading);
      empty.appendChild(copy);
      empty.appendChild(button('+ New line', 'is-primary', createLine));
      editor.appendChild(empty);
      return;
    }

    if (state.mode === 'preview') {
      editor.appendChild(renderPreview(line));
      return;
    }

    var identity = document.createElement('section');
    identity.className = 'azuki-playbook-section';
    var titleInput = textInput('azuki-playbook-title-input', line.title, 'Line title', function (value) {
      line.title = value;
      renderLineList();
    });
    titleInput.id = 'azukiPlaybookTitleInput';
    titleInput.maxLength = 120;
    titleInput.setAttribute('aria-label', 'Line title');
    var summaryInput = textInput('azuki-playbook-summary-input', line.summary, 'What is this line trying to accomplish?', function (value) {
      line.summary = value;
    });
    summaryInput.maxLength = 300;
    summaryInput.setAttribute('aria-label', 'Line summary');
    identity.appendChild(titleInput);
    identity.appendChild(summaryInput);

    var sequence = document.createElement('section');
    sequence.className = 'azuki-playbook-section';
    var sequenceHeading = document.createElement('div');
    sequenceHeading.className = 'azuki-playbook-section-heading';
    var sequenceTitle = document.createElement('h3');
    sequenceTitle.textContent = 'Sequence';
    sequenceHeading.appendChild(sequenceTitle);
    sequenceHeading.appendChild(button('+ Add step', '', createStep));
    var steps = document.createElement('div');
    steps.className = 'azuki-playbook-steps';
    if (!line.steps.length) {
      var noSteps = document.createElement('div');
      noSteps.className = 'azuki-playbook-subtitle';
      noSteps.textContent = 'No steps yet. Start with an action, condition, or opening-hand goal.';
      steps.appendChild(noSteps);
    } else {
      line.steps.forEach(function (step, index) {
        steps.appendChild(renderStep(step, index, line.steps.length));
      });
    }
    sequence.appendChild(sequenceHeading);
    sequence.appendChild(steps);

    var valuesSection = document.createElement('section');
    valuesSection.className = 'azuki-playbook-section';
    var valuesHeading = document.createElement('div');
    valuesHeading.className = 'azuki-playbook-section-heading';
    var valuesTitle = document.createElement('h3');
    valuesTitle.textContent = 'Custom values';
    valuesHeading.appendChild(valuesTitle);
    valuesHeading.appendChild(button('+ Add value', 'is-ghost', addValue));
    var values = document.createElement('div');
    values.className = 'azuki-playbook-values';
    line.values.forEach(function (field) {
      var row = document.createElement('div');
      row.className = 'azuki-playbook-value';
      var label = textInput('', field.label, 'Label', function (value) { field.label = value; });
      label.setAttribute('aria-label', 'Value label');
      var value = textInput('', field.value, 'Value', function (nextValue) { field.value = nextValue; });
      value.setAttribute('aria-label', 'Value');
      var unit = textInput('azuki-playbook-unit', field.unit, 'Optional unit', function (nextUnit) { field.unit = nextUnit; });
      unit.setAttribute('aria-label', 'Optional unit');
      row.appendChild(label);
      row.appendChild(value);
      row.appendChild(unit);
      row.appendChild(button('×', 'is-ghost is-danger azuki-playbook-icon-button', function () {
        deleteValue(field.id);
      }, 'Delete custom value'));
      values.appendChild(row);
    });
    valuesSection.appendChild(valuesHeading);
    valuesSection.appendChild(values);

    var notesSection = document.createElement('section');
    notesSection.className = 'azuki-playbook-section';
    var notes = textArea(line.notes, 'Matchup notes, assumptions, sequencing reminders…', function (value) {
      line.notes = value;
    });
    notes.id = 'azukiPlaybookNotes';
    notesSection.appendChild(fieldLabel('Line notes', notes));
    notesSection.appendChild(notes);

    var sharingSection = document.createElement('section');
    sharingSection.className = 'azuki-playbook-section';
    var share = document.createElement('label');
    share.className = 'azuki-playbook-share';
    var checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.checked = !!line.shared;
    checkbox.addEventListener('change', function () {
      line.shared = checkbox.checked;
      renderLineList();
      scheduleSave();
    });
    var shareText = document.createElement('span');
    shareText.textContent = 'Share this line when playbook sharing is enabled';
    share.appendChild(checkbox);
    share.appendChild(shareText);
    sharingSection.appendChild(share);

    editor.appendChild(identity);
    editor.appendChild(sequence);
    editor.appendChild(valuesSection);
    editor.appendChild(notesSection);
    editor.appendChild(sharingSection);
  }

  function renderPicker() {
    var grid = document.getElementById('azukiPlaybookPickerGrid');
    if (!grid) return;
    grid.replaceChildren();
    var step = selectedStep();
    if (!step) return;
    var query = state.pickerQuery.toLowerCase();
    deckCards().filter(function (cardID) {
      return !query || cardName(cardID).toLowerCase().includes(query) || cardID.toLowerCase().includes(query);
    }).forEach(function (cardID) {
      var card = document.createElement('button');
      card.type = 'button';
      card.className = 'azuki-playbook-picker-card' + (step.cards.includes(cardID) ? ' is-selected' : '');
      card.setAttribute('aria-pressed', step.cards.includes(cardID) ? 'true' : 'false');
      var image = document.createElement('img');
      image.src = cardImageURL(cardID);
      image.alt = '';
      image.loading = 'lazy';
      var name = document.createElement('span');
      name.textContent = cardName(cardID);
      var check = document.createElement('span');
      check.className = 'azuki-playbook-picker-check';
      check.textContent = '✓';
      check.setAttribute('aria-hidden', 'true');
      card.appendChild(image);
      card.appendChild(name);
      card.appendChild(check);
      card.addEventListener('click', function () { toggleCard(cardID); });
      grid.appendChild(card);
    });
  }

  function render() {
    renderLineList();
    renderEditor();
    var previewToggle = document.getElementById('azukiPlaybookPreviewToggle');
    if (previewToggle) {
      previewToggle.textContent = state.mode === 'preview' ? 'Edit' : 'Preview';
      previewToggle.classList.toggle('is-primary', state.mode === 'preview');
    }
    document.getElementById('azukiPlaybookOverlay').classList.toggle('is-preview', state.mode === 'preview');
  }

  function buildUI() {
    var overlay = document.createElement('div');
    overlay.id = 'azukiPlaybookOverlay';
    overlay.setAttribute('aria-hidden', 'true');

    var shell = document.createElement('div');
    shell.className = 'azuki-playbook-shell';
    shell.setAttribute('role', 'dialog');
    shell.setAttribute('aria-modal', 'true');
    shell.setAttribute('aria-label', 'Lines of play');

    var sidebar = document.createElement('aside');
    sidebar.className = 'azuki-playbook-sidebar';
    var sidebarHeader = document.createElement('header');
    sidebarHeader.className = 'azuki-playbook-sidebar-header';
    var eyebrow = document.createElement('div');
    eyebrow.className = 'azuki-playbook-eyebrow';
    eyebrow.textContent = 'Deck playbook';
    var sidebarTitle = document.createElement('h2');
    sidebarTitle.className = 'azuki-playbook-title';
    sidebarTitle.textContent = 'Lines of play';
    var sidebarSubtitle = document.createElement('p');
    sidebarSubtitle.className = 'azuki-playbook-subtitle';
    sidebarSubtitle.textContent = 'Sequences, notes, and outcomes';
    sidebarHeader.appendChild(eyebrow);
    sidebarHeader.appendChild(sidebarTitle);
    sidebarHeader.appendChild(sidebarSubtitle);
    var lines = document.createElement('div');
    lines.id = 'azukiPlaybookLines';
    lines.className = 'azuki-playbook-lines';
    sidebar.appendChild(sidebarHeader);
    sidebar.appendChild(lines);
    var newLine = button('+ New line', 'is-primary azuki-playbook-new-line', createLine);
    sidebar.appendChild(newLine);

    var editorPane = document.createElement('main');
    editorPane.className = 'azuki-playbook-editor';
    var editorHeader = document.createElement('header');
    editorHeader.className = 'azuki-playbook-editor-header';
    var headingRow = document.createElement('div');
    headingRow.className = 'azuki-playbook-heading-row';
    var headingCopy = document.createElement('div');
    var editorEyebrow = document.createElement('div');
    editorEyebrow.className = 'azuki-playbook-eyebrow';
    editorEyebrow.textContent = 'Current deck';
    var editorTitle = document.createElement('h2');
    editorTitle.className = 'azuki-playbook-title';
    editorTitle.textContent = 'Playbook editor';
    headingCopy.appendChild(editorEyebrow);
    headingCopy.appendChild(editorTitle);
    var actions = document.createElement('div');
    actions.className = 'azuki-playbook-header-actions';
    actions.appendChild(button('New', 'is-ghost azuki-playbook-edit-only', createLine));
    actions.appendChild(button('Duplicate', 'is-ghost azuki-playbook-edit-only', duplicateLine));
    actions.appendChild(button('Delete', 'is-ghost is-danger azuki-playbook-edit-only', deleteLine));
    var previewToggle = button('Preview', '', function () {
      state.mode = state.mode === 'preview' ? 'edit' : 'preview';
      render();
      scrollEditorTop();
    });
    previewToggle.id = 'azukiPlaybookPreviewToggle';
    actions.appendChild(previewToggle);
    actions.appendChild(button('Close', '', close));
    headingRow.appendChild(headingCopy);
    headingRow.appendChild(actions);
    editorHeader.appendChild(headingRow);
    var mobileSelect = document.createElement('select');
    mobileSelect.id = 'azukiPlaybookMobileSelect';
    mobileSelect.className = 'azuki-playbook-input azuki-playbook-mobile-select';
    mobileSelect.setAttribute('aria-label', 'Selected line');
    mobileSelect.addEventListener('change', function () {
      state.selectedLineID = mobileSelect.value;
      render();
      scrollEditorTop();
    });
    editorHeader.appendChild(mobileSelect);
    var editor = document.createElement('div');
    editor.id = 'azukiPlaybookEditor';
    editor.className = 'azuki-playbook-content';
    var footer = document.createElement('footer');
    footer.className = 'azuki-playbook-footer';
    var saveState = document.createElement('span');
    saveState.id = 'azukiPlaybookSaveState';
    saveState.className = 'azuki-playbook-save-state';
    saveState.textContent = 'Loading…';
    footer.appendChild(saveState);
    editorPane.appendChild(editorHeader);
    editorPane.appendChild(editor);
    editorPane.appendChild(footer);

    var picker = document.createElement('section');
    picker.id = 'azukiPlaybookPicker';
    picker.className = 'azuki-playbook-picker';
    picker.setAttribute('aria-label', 'Choose cards');
    var pickerHeader = document.createElement('header');
    pickerHeader.className = 'azuki-playbook-picker-header';
    var pickerTitle = document.createElement('h3');
    pickerTitle.textContent = 'Add cards from this deck';
    pickerHeader.appendChild(pickerTitle);
    pickerHeader.appendChild(button('Done', 'is-primary', closePicker));
    var pickerSearchWrap = document.createElement('div');
    pickerSearchWrap.className = 'azuki-playbook-picker-search';
    var pickerSearch = document.createElement('input');
    pickerSearch.id = 'azukiPlaybookPickerSearch';
    pickerSearch.type = 'search';
    pickerSearch.className = 'azuki-playbook-search';
    pickerSearch.placeholder = 'Search this deck';
    pickerSearch.setAttribute('aria-label', 'Search cards in this deck');
    pickerSearch.addEventListener('input', function () {
      state.pickerQuery = pickerSearch.value;
      renderPicker();
    });
    pickerSearchWrap.appendChild(pickerSearch);
    var pickerGrid = document.createElement('div');
    pickerGrid.id = 'azukiPlaybookPickerGrid';
    pickerGrid.className = 'azuki-playbook-picker-grid';
    picker.appendChild(pickerHeader);
    picker.appendChild(pickerSearchWrap);
    picker.appendChild(pickerGrid);

    shell.appendChild(sidebar);
    shell.appendChild(editorPane);
    shell.appendChild(picker);
    overlay.appendChild(shell);
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) close();
    });
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape' || !overlay.classList.contains('is-open')) return;
      if (picker.classList.contains('is-open')) closePicker();
      else close();
    });
    document.body.appendChild(overlay);
  }

  function open() {
    var overlay = document.getElementById('azukiPlaybookOverlay');
    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('azuki-playbook-lock');
    if (!state.loaded) load();
  }

  function close() {
    var overlay = document.getElementById('azukiPlaybookOverlay');
    if (!overlay) return;
    if (state.saveTimer) save();
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('azuki-playbook-lock');
  }

  function installToolbarButton() {
    var toolbar = document.querySelector('.flex-container > .flex-item:first-child');
    if (!toolbar || document.getElementById('azukiPlaybookButton')) return;
    var linesButton = document.createElement('button');
    linesButton.id = 'azukiPlaybookButton';
    linesButton.type = 'button';
    linesButton.textContent = 'Lines';
    linesButton.title = 'Define lines of play';
    linesButton.addEventListener('click', open);
    var visibility = document.getElementById('AssetVisibility');
    toolbar.insertBefore(linesButton, visibility || null);
  }

  function load() {
    setSaveState('Loading…', false);
    fetch(config.endpoint + '?deckID=' + encodeURIComponent(config.deckID), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (payload) {
        if (!response.ok || !payload.success) throw new Error(payload.error || 'The playbook could not be loaded.');
        state.playbook = payload.playbook;
        state.selectedLineID = state.playbook.lines.length ? state.playbook.lines[0].id : '';
        state.loaded = true;
        setSaveState('All changes saved', false);
        render();
      });
    }).catch(function (error) {
      setSaveState(error.message || 'Load failed', true);
    });
  }

  buildUI();
  installToolbarButton();
  render();
})();
