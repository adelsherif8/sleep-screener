/* STOP-Bang Questionnaire — Frontend Logic */
(function () {
  'use strict';

  var TOTAL_STEPS = 3;
  var currentStep = 1;
  var bmiUnit     = 'metric';
  var sbqLoadedAt = Date.now();

  /* ── DOM refs ── */
  var wrap         = document.getElementById('sbq-wrap');
  var progressFill = document.getElementById('sbq-progress-fill');
  var stepLabel    = document.getElementById('sbq-step-label');
  var prevBtn      = document.getElementById('sbq-prev');
  var nextBtn      = document.getElementById('sbq-next');
  var errorEl      = document.getElementById('sbq-error');
  var resultsEl    = document.getElementById('sbq-results');
  var resultInner  = document.getElementById('sbq-result-inner');
  var bodyEl       = document.getElementById('sbq-body');
  var navEl        = document.getElementById('sbq-nav');
  var progressWrap = document.getElementById('sbq-progress-wrap');

  if (!wrap) return;

  /* ── Radio selection feedback ── */
  wrap.addEventListener('change', function (e) {
    if (e.target.type !== 'radio') return;
    var group = e.target.closest('.sbq-options');
    if (!group) return;
    group.querySelectorAll('.sbq-option input').forEach(function (inp) {
      inp.closest('.sbq-option').classList.toggle('selected', inp.checked);
    });
    hideError();
  });

  /* ── Unit toggle ── */
  wrap.addEventListener('click', function (e) {
    var btn = e.target.closest('.sbq-unit-btn');
    if (!btn) return;
    bmiUnit = btn.dataset.unit;
    wrap.querySelectorAll('.sbq-unit-btn').forEach(function (b) {
      b.classList.toggle('active', b === btn);
    });
    document.getElementById('sbq-metric-inputs').style.display   = bmiUnit === 'metric'   ? '' : 'none';
    document.getElementById('sbq-imperial-inputs').style.display = bmiUnit === 'imperial' ? '' : 'none';
    calcBMI();
  });

  /* ── BMI Calculator ── */
  function calcBMI() {
    var bmi = 0;
    var hDisplay = '', wDisplay = '';

    if (bmiUnit === 'metric') {
      var hcm = parseFloat(document.getElementById('sbq-h-cm').value) || 0;
      var wkg = parseFloat(document.getElementById('sbq-w-kg').value) || 0;
      if (hcm > 0 && wkg > 0) {
        var hm = hcm / 100;
        bmi = wkg / (hm * hm);
        hDisplay = hcm + ' cm';
        wDisplay = wkg + ' kg';
      }
    } else {
      var hft = parseFloat(document.getElementById('sbq-h-ft').value) || 0;
      var hin = parseFloat(document.getElementById('sbq-h-in').value) || 0;
      var wlb = parseFloat(document.getElementById('sbq-w-lb').value) || 0;
      var totalIn = hft * 12 + hin;
      if (totalIn > 0 && wlb > 0) {
        bmi = (wlb * 703) / (totalIn * totalIn);
        hDisplay = hft + '\'' + hin + '"';
        wDisplay = wlb + ' lb';
      }
    }

    bmi = Math.round(bmi * 10) / 10;

    var bmiVal   = document.getElementById('sbq-bmi-val');
    var bmiHid   = document.getElementById('sbq-bmi-hidden');
    var bmiBadge = document.getElementById('sbq-bmi-badge');
    var hHid     = document.getElementById('sbq-height-display');
    var wHid     = document.getElementById('sbq-weight-display');

    if (bmiVal)  bmiVal.textContent = bmi > 0 ? bmi : '—';
    if (bmiHid)  bmiHid.value       = bmi > 0 ? String(bmi) : '';
    if (hHid)    hHid.value         = hDisplay;
    if (wHid)    wHid.value         = wDisplay;

    if (bmiBadge) {
      if (bmi <= 0) {
        bmiBadge.style.display = 'none';
      } else {
        bmiBadge.style.display = '';
        if (bmi > 35) {
          bmiBadge.className   = 'sbq-auto-badge sbq-auto-badge--high';
          bmiBadge.textContent = 'Above 35 — Risk Factor';
        } else {
          bmiBadge.className   = 'sbq-auto-badge sbq-auto-badge--ok';
          bmiBadge.textContent = 'Below 35 — Within Range';
        }
      }
    }
    return bmi;
  }

  /* ── Age badge ── */
  function updateAgeBadge() {
    var ageEl  = document.getElementById('sbq-age');
    var badge  = document.getElementById('sbq-age-badge');
    var age    = parseInt(ageEl ? ageEl.value : 0) || 0;
    if (!badge) return;
    if (age <= 0) { badge.style.display = 'none'; return; }
    badge.style.display = '';
    if (age > 50) {
      badge.className   = 'sbq-auto-badge sbq-auto-badge--high';
      badge.textContent = 'Over 50 — Risk Factor';
    } else {
      badge.className   = 'sbq-auto-badge sbq-auto-badge--ok';
      badge.textContent = '50 or Under';
    }
  }

  /* Live BMI + age updates */
  ['sbq-h-cm','sbq-w-kg','sbq-h-ft','sbq-h-in','sbq-w-lb'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', calcBMI);
  });

  var ageEl = document.getElementById('sbq-age');
  if (ageEl) ageEl.addEventListener('input', updateAgeBadge);

  /* ── Progress ── */
  function updateProgress() {
    var pct = (currentStep / TOTAL_STEPS) * 100;
    progressFill.style.width = pct + '%';
    stepLabel.textContent = 'Step ' + currentStep + ' of ' + TOTAL_STEPS;
    prevBtn.style.visibility = currentStep > 1 ? 'visible' : 'hidden';
    nextBtn.innerHTML = currentStep === TOTAL_STEPS
      ? 'See My Results <svg viewBox="0 0 20 20" fill="none"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
      : 'Next <svg viewBox="0 0 20 20" fill="none"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
  }

  /* ── Validation ── */
  function validateStep(step) {
    var errors = [];

    if (step === 1) {
      var name  = wrap.querySelector('[name="full_name"]');
      var email = wrap.querySelector('[name="email"]');
      var phone = wrap.querySelector('[name="phone"]');
      if (!name.value.trim())                            { markInvalid(name);  errors.push('Full name is required.'); }
      if (!email.value.trim() || !email.value.includes('@')) { markInvalid(email); errors.push('A valid email address is required.'); }
      if (!phone.value.trim())                           { markInvalid(phone); errors.push('Phone number is required.'); }
    }

    if (step === 2) {
      ['snoring','tired','observed','pressure'].forEach(function (n) {
        if (!wrap.querySelector('[name="' + n + '"]:checked')) errors.push('Please answer all questions on this step.');
      });
    }

    if (step === 3) {
      var bmiHid = document.getElementById('sbq-bmi-hidden');
      if (!bmiHid || !bmiHid.value) errors.push('Please enter your height and weight to calculate BMI.');

      var ageInput = document.getElementById('sbq-age');
      if (!ageInput || !ageInput.value || parseInt(ageInput.value) < 18) errors.push('Please enter a valid age (18+).');

      if (!wrap.querySelector('[name="neck_large"]:checked')) errors.push('Please answer the neck size question.');
      if (!wrap.querySelector('[name="gender"]:checked'))     errors.push('Please select your gender.');
    }

    return errors;
  }

  function markInvalid(el) {
    el.classList.add('sbq-invalid');
    el.addEventListener('input', function () { el.classList.remove('sbq-invalid'); }, { once: true });
  }

  function showError(msg) {
    errorEl.textContent = msg;
    errorEl.style.display = 'block';
    errorEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function hideError() { errorEl.style.display = 'none'; }

  /* ── Navigation ── */
  function goToStep(step) {
    wrap.querySelectorAll('.sbq-step').forEach(function (el) {
      el.classList.toggle('active', parseInt(el.dataset.step) === step);
    });
    currentStep = step;
    updateProgress();
    hideError();
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  nextBtn.addEventListener('click', function () {
    var errs = validateStep(currentStep);
    if (errs.length) { showError(errs[0]); return; }
    if (currentStep < TOTAL_STEPS) { goToStep(currentStep + 1); } else { submitForm(); }
  });

  prevBtn.addEventListener('click', function () {
    if (currentStep > 1) goToStep(currentStep - 1);
  });

  /* ── Collect data ── */
  function collectData() {
    var data = {};
    wrap.querySelectorAll('input[type="text"],input[type="email"],input[type="tel"],input[type="number"],input[type="hidden"]').forEach(function (el) {
      if (el.name) data[el.name] = el.value.trim();
    });
    wrap.querySelectorAll('input[type="radio"]:checked').forEach(function (el) {
      if (el.name) data[el.name] = el.value;
    });
    return data;
  }

  /* ── Score (mirrors PHP) ── */
  function scoreData(d) {
    var s = d.snoring  === 'yes' ? 1 : 0;
    var t = d.tired    === 'yes' ? 1 : 0;
    var o = d.observed === 'yes' ? 1 : 0;
    var p = d.pressure === 'yes' ? 1 : 0;

    var bmi = parseFloat(d.bmi) || 0;
    var b   = bmi > 35 ? 1 : 0;
    var age = parseInt(d.age) || 0;
    var a   = age > 50 ? 1 : 0;
    var n   = d.neck_large === 'yes' ? 1 : 0;
    var g   = d.gender    === 'male' ? 1 : 0;

    var stopScore = s + t + o + p;
    var bangScore = b + a + n + g;
    var total     = stopScore + bangScore;

    var risk;
    if (total >= 5 || (stopScore >= 2 && (g || b || a))) {
      risk = 'High';
    } else if (total >= 3) {
      risk = 'Intermediate';
    } else {
      risk = 'Low';
    }

    return { s:s, t:t, o:o, p:p, b:b, a:a, n:n, g:g,
             bmi: Math.round(bmi * 10) / 10, age: age,
             stopScore: stopScore, bangScore: bangScore, total: total, risk: risk };
  }

  /* ── Render results ── */
  function renderResults(score) {
    var bookingUrl = (typeof SBQ !== 'undefined' && SBQ.booking_url) ? SBQ.booking_url : '/thank-you';

    var badgeCls = { High: 'sbq-risk-badge--high', Intermediate: 'sbq-risk-badge--int', Low: 'sbq-risk-badge--low' };
    var headlines = {
      High:         'High risk of obstructive sleep apnea detected.',
      Intermediate: 'Intermediate risk of sleep apnea detected.',
      Low:          'Lower risk of sleep apnea detected.'
    };
    var subtexts = {
      High:         'You scored <strong>' + score.total + ' out of 8</strong> on the STOP-Bang scale. We strongly recommend a formal sleep evaluation — sleep apnea is treatable when caught early.',
      Intermediate: 'You scored <strong>' + score.total + ' out of 8</strong>. A consultation with a sleep health specialist is recommended to rule out sleep apnea.',
      Low:          'You scored <strong>' + score.total + ' out of 8</strong>. Your risk appears low, but feel free to discuss your sleep health at your next visit.'
    };

    var letters = [
      { l:'S', name:'Snoring',  val:score.s },
      { l:'T', name:'Tired',    val:score.t },
      { l:'O', name:'Observed', val:score.o },
      { l:'P', name:'Pressure', val:score.p },
      { l:'B', name:'BMI',      val:score.b },
      { l:'A', name:'Age',      val:score.a },
      { l:'N', name:'Neck',     val:score.n },
      { l:'G', name:'Gender',   val:score.g }
    ];

    var gridHtml = letters.map(function (item) {
      return '<div class="sbq-letter-result ' + (item.val ? 'sbq-letter-result--yes' : '') + '">'
        + '<div class="sbq-letter-circle">' + item.l + '</div>'
        + '<div class="sbq-letter-name">'   + item.name + '</div>'
        + '<div class="sbq-letter-tick">'   + (item.val ? '✓' : '—') + '</div>'
        + '</div>';
    }).join('');

    var ctaBtn = score.risk === 'High'
      ? '<a href="' + bookingUrl + '" class="sbq-cta-btn sbq-cta-btn--urgent">Book a Consultation <svg viewBox="0 0 20 20" fill="none" style="width:16px;height:16px"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a>'
        + '<p class="sbq-cta-note">Our team will review your results and discuss next steps.</p>'
      : '<a href="' + bookingUrl + '" class="sbq-cta-btn">Schedule a Visit <svg viewBox="0 0 20 20" fill="none" style="width:16px;height:16px"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a>'
        + '<p class="sbq-cta-note">Mention your screening results when you call.</p>';

    resultInner.innerHTML =
      '<div class="sbq-risk-badge ' + (badgeCls[score.risk] || 'sbq-risk-badge--low') + '">' + score.risk + ' Risk</div>'
      + '<h2 class="sbq-result-headline">' + headlines[score.risk] + '</h2>'
      + '<p class="sbq-result-sub">' + subtexts[score.risk] + '</p>'
      + '<div class="sbq-score-row">'
        + '<div><div class="sbq-score-num">' + score.total + '</div><div class="sbq-score-denom">out of 8</div></div>'
        + '<div class="sbq-score-label">STOP: ' + score.stopScore + '/4 &nbsp;&nbsp; BANG: ' + score.bangScore + '/4</div>'
      + '</div>'
      + '<div class="sbq-letter-grid">' + gridHtml + '</div>'
      + '<div class="sbq-result-cta">' + ctaBtn + '</div>';
  }

  /* ── Submit ── */
  function submitForm() {
    var data  = collectData();
    var score = scoreData(data);

    bodyEl.style.display        = 'none';
    navEl.style.display         = 'none';
    progressWrap.style.display  = 'none';
    renderResults(score);
    resultsEl.style.display = 'block';
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });

    if (typeof SBQ === 'undefined' || !SBQ.ajax_url) return;

    var formData = new FormData();
    formData.append('action', 'sbq_submit');
    formData.append('nonce',  SBQ.nonce);
    formData.append('data[_elapsed]', String(Math.floor((Date.now() - sbqLoadedAt) / 1000)));
    var hp = document.querySelector('[name="website"]');
    formData.append('data[_hp]', hp ? hp.value : '');
    Object.keys(data).forEach(function (k) { formData.append('data[' + k + ']', data[k]); });

    fetch(SBQ.ajax_url, { method: 'POST', body: formData }).catch(function () {});
  }

  /* ── Init ── */
  updateProgress();

})();
