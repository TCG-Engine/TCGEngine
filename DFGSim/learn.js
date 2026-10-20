/* Guidance only: app.js owns all board rendering and gameplay inputs. */
window.DFGTutorial = (() => {
  let guide = null, choosingLesson = false, lastStep = null, targetKey = null, queued = false;
  const panel = document.getElementById('tutorialGuide');
  const spotlight = document.getElementById('tutorialSpotlight');
  document.body.classList.add('dfg-guided-game');
  function findTarget() {
    if (choosingLesson || guide?.complete) return null;
    if (guide?.target) return document.getElementById(guide.target);
    const action = guide?.action;
    if (!action) return null;
    const get = id => document.getElementById(id);
    if (action.type === 'commit') {
      if (browseColor !== state.players[0].color) return get(`your-orb-${state.players[0].color}`);
      if (selectedMain !== action.main) return get(`hand-${action.main}`);
      const bonus = action.bonuses?.find(id => !selectedBonuses.has(id));
      if (bonus) return get(`hand-${bonus}`);
      const color = action.advantages?.find(color => !selectedAdvantages.has(color));
      if (color) {
        const picker = get('advantagePicker');
        return picker?.open ? picker.querySelector(`[data-advantage="${color}"]`) : picker?.querySelector('summary');
      }
      return get('commit');
    }
    if (action.type === 'react') return browseColor !== state.players[0].color ? get(`your-orb-${state.players[0].color}`) : get(`hand-${action.card}`);
    if (action.type === 'pass') return get('pass');
    if (action.type === 'switch') return document.querySelector(`[data-switch="${action.color}"]`);
    return null;
  }
  function position() {
    queued = false;
    if (!guide) return;
    const target = findTarget();
    document.body.classList.toggle('dfg-tutorial-target', !!target);
    const gap = 18, edge = 14, width = innerWidth, height = innerHeight;
    panel.style.maxHeight = `${Math.max(160, target ? Math.min(height * 0.55, height - 2 * edge) : height - 2 * edge)}px`;
    panel.classList.toggle('is-centered', !target);
    spotlight.hidden = !target;
    document.getElementById('tutorialBackdrop').hidden = !!target;
    if (!target) {
      panel.style.left = ''; panel.style.top = ''; panel.style.transform = '';
      targetKey = null;
      return;
    }
    const key = `${guide.lesson}:${guide.step}:${panel.offsetHeight}:${height}:${target.id || target.outerHTML.slice(0,100)}`;
    const rect = target.getBoundingClientRect();
    const pw = panel.offsetWidth, ph = panel.offsetHeight;
    const clamp = (v, max) => Math.max(edge, Math.min(max - edge, v));
    const candidates = [
      [clamp(rect.left, width-pw), rect.top-ph-gap],
      [clamp(rect.left, width-pw), rect.bottom+gap],
      [rect.right+gap, clamp(rect.top, height-ph)],
      [rect.left-pw-gap, clamp(rect.top, height-ph)]
    ];
    const fits = ([x,y]) => x>=edge && y>=edge && x+pw<=width-edge && y+ph<=height-edge;
    const spot = candidates.find(fits);
    let [left, top] = spot || [width-pw-edge, height-ph-edge];
    // Keep the current real control above the popup on compact screens. Extra bottom
    // scroll space lets the last hand row move clear of the floating explanation.
    if (!spot || rect.top < edge || rect.bottom > height-edge) {
      if (targetKey !== key) {
        targetKey = key;
        const desiredTop = Math.max(edge, (top - Math.min(rect.height, top-edge)) / 2);
        window.scrollBy({top:rect.top-desiredTop, behavior:'instant'});
        schedulePosition();
      }
    } else targetKey = key;
    panel.style.setProperty('--tutorial-left', `${left}px`); panel.style.setProperty('--tutorial-top', `${top}px`);
    panel.style.left = `${left}px`; panel.style.top = `${top}px`; panel.style.transform = 'none';
    const current = target.getBoundingClientRect();
    spotlight.style.left = `${current.left-6}px`; spotlight.style.top = `${current.top-6}px`;
    spotlight.style.width = `${current.width+12}px`; spotlight.style.height = `${current.height+12}px`;
  }
  function schedulePosition() {
    if (queued) return;
    queued = true;
    requestAnimationFrame(position);
  }
  window.addEventListener('resize', schedulePosition);
  window.addEventListener('scroll', schedulePosition, {passive:true});
  document.addEventListener('toggle', schedulePosition, true);
  new ResizeObserver(schedulePosition).observe(panel);
  async function navigate(command, extra = {}) {
    if (busy) return;
    busy = true;
    showError('');
    render();
    let accepted = false;
    try {
      const data = await request({command, ...extra, matchId: state.matchId, revision: state.revision});
      busy = false;
      selectedMain = null; selectedBonuses.clear(); selectedAdvantages.clear(); browseColor = null;
      accept(data);
      accepted = true;
      document.getElementById('stepTitle').focus({preventScroll:true});
    } catch (error) {
      // A failed response may still have advanced the server. Read the saved
      // checkpoint before allowing another input; never replay a POST blindly.
      if (error.status === 409 || !error.status) {
        try { accept(await request()); } catch {}
      }
      showError(error.message);
    } finally { busy = false; if (!accepted) render(); }
  }
  document.addEventListener('click', event => {
    const button = event.target.closest('#tutorialGuide button');
    if (!button || button.disabled || !guide) return;
    if (button.dataset.lesson !== undefined) { choosingLesson = false; navigate('lesson', {lesson:Number(button.dataset.lesson)}); }
    if (button.id === 'tutorialRestart') navigate('lesson', {lesson:guide.lesson});
    if (button.id === 'tutorialPrevious') {
      if (choosingLesson) { choosingLesson = false; render(); }
      else navigate('lesson', {lesson:guide.lesson, step:Math.max(0,guide.step-1)});
    }
    if (button.id === 'tutorialLessons') { choosingLesson = true; render(); document.getElementById('stepTitle').focus({preventScroll:true}); }
    if (button.id === 'tutorialContinue') navigate('continue');
  });
  return {
    accept(data) { guide = data; },
    render() {
      if (!guide) return;
      const get = id => document.getElementById(id);
      get('lessonTabs').innerHTML = guide.names.map((name,i) => `<button data-lesson="${i}" ${i===guide.lesson?'aria-current="step"':''} ${busy?'disabled':''}>${i+1}. ${esc(name)}</button>`).join('');
      panel.hidden = false;
      panel.setAttribute('aria-busy', String(busy));
      get('tutorialLoading').hidden = !busy;
      get('tutorialProgress').textContent = choosingLesson ? 'Learn to play · Lessons' : `${guide.names[guide.lesson]} · ${guide.step+1}/${guide.steps}`;
      const stepKey = `${guide.lesson}:${guide.step}:${guide.complete}`;
      if(lastStep !== stepKey) { lastStep = stepKey; targetKey = null; }
      get('stepTitle').tabIndex = -1;
      get('stepTitle').textContent = choosingLesson ? 'What would you like to try?' : guide.complete ? 'You’re ready.' : guide.title;
      get('tutorialText').textContent = guide.complete ? 'That’s the basics. Pick a hand, read their move, and decide what you’re willing to spend. The rest will come with a few fights.' : guide.text;
      get('tutorialTask').textContent = guide.complete ? 'Play against the bot, or use Exit to return to your saved match.' : guide.task;
      get('tutorialContinue').hidden = choosingLesson || guide.complete || !!guide.action;
      get('tutorialContinue').disabled = busy;
      get('tutorialContinue').textContent = guide.button;
      get('tutorialPrevious').disabled = busy || (!choosingLesson && guide.step===0);
      get('tutorialPrevious').textContent = choosingLesson ? 'Back to lesson' : 'Back';
      get('tutorialRestart').hidden = choosingLesson;
      get('tutorialLessons').hidden = choosingLesson;
      get('lessonTabs').hidden = !choosingLesson;
      get('tutorialText').hidden = choosingLesson;
      get('tutorialTask').hidden = choosingLesson || !get('tutorialTask').textContent;
      get('tutorialRestart').disabled = busy;
      get('tutorialLessons').disabled = busy;
      get('tutorialPlay').hidden = choosingLesson || !guide.complete;
      ['newMatch','playAgain','resign'].forEach(id => { if(get(id)) get(id).hidden=true; });
      schedulePosition();
    }
  };
})();
