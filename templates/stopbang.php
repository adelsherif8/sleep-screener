<!-- Honeypot -->
<div class="sbq-hp" aria-hidden="true">
  <label for="sbq-hp-f">Leave this blank</label>
  <input type="text" id="sbq-hp-f" name="website" value="" tabindex="-1" autocomplete="off" />
</div>

<div id="sbq-wrap" role="main">

  <!-- Header -->
  <div class="sbq-header">
    <div class="sbq-header-icon">
      <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="24" cy="24" r="20" fill="rgba(255,255,255,0.15)"/>
        <path d="M14 24c0-5.5 4.5-10 10-10s10 4.5 10 10c0 3-1 5-2 7l-2 3h-12l-2-3c-1-2-2-4-2-7z" fill="white" opacity="0.9"/>
        <path d="M19 30h10M21 34h6" stroke="rgba(30,64,175,0.5)" stroke-width="2" stroke-linecap="round"/>
        <path d="M10 36c0-2 3-3 5-3h18c2 0 5 1 5 3" stroke="white" stroke-width="2.5" stroke-linecap="round" opacity="0.6"/>
        <circle cx="24" cy="16" r="2" fill="white" opacity="0.7"/>
      </svg>
    </div>
    <div>
      <h1 class="sbq-title">Sleep Apnea Screening</h1>
      <p class="sbq-subtitle">STOP-Bang Questionnaire &middot; Takes about 2 minutes</p>
    </div>
  </div>

  <!-- Progress -->
  <div class="sbq-progress-wrap" id="sbq-progress-wrap">
    <div class="sbq-progress-track">
      <div class="sbq-progress-fill" id="sbq-progress-fill"></div>
    </div>
    <p class="sbq-step-label" id="sbq-step-label">Step 1 of 3</p>
  </div>

  <!-- Steps -->
  <div class="sbq-body" id="sbq-body">

    <!-- STEP 1: Contact -->
    <div class="sbq-step active" data-step="1">
      <div class="sbq-step-intro">
        <span class="sbq-step-badge">Contact Info</span>
        <h2>Let's start with your details</h2>
        <p>Your information is private and used only to follow up on your results.</p>
      </div>
      <div class="sbq-fields">
        <div class="sbq-field">
          <label for="sbq-full-name">Full Name <span class="req">*</span></label>
          <input type="text" id="sbq-full-name" name="full_name" placeholder="Jane Smith" autocomplete="name" />
        </div>
        <div class="sbq-field-row">
          <div class="sbq-field">
            <label for="sbq-email">Email Address <span class="req">*</span></label>
            <input type="email" id="sbq-email" name="email" placeholder="jane@email.com" autocomplete="email" />
          </div>
          <div class="sbq-field">
            <label for="sbq-phone">Phone Number <span class="req">*</span></label>
            <input type="tel" id="sbq-phone" name="phone" placeholder="(519) 000-0000" autocomplete="tel" />
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 2: STOP -->
    <div class="sbq-step" data-step="2">
      <div class="sbq-step-intro">
        <span class="sbq-step-badge">STOP Questions</span>
        <h2>About your sleep at night</h2>
        <p>Answer as accurately as possible &mdash; ask a bed partner if you're not sure.</p>
      </div>

      <div class="sbq-questions">

        <div class="sbq-question">
          <div class="sbq-q-header">
            <span class="sbq-letter-badge">S</span>
            <p class="sbq-q-label">Do you <strong>Snore</strong> loudly (loud enough to be heard through closed doors, or your bed-partner elbows you)? <span class="req">*</span></p>
          </div>
          <div class="sbq-options sbq-options--2col" data-name="snoring">
            <label class="sbq-option"><input type="radio" name="snoring" value="yes" /><span>Yes</span></label>
            <label class="sbq-option"><input type="radio" name="snoring" value="no" /><span>No</span></label>
          </div>
        </div>

        <div class="sbq-question">
          <div class="sbq-q-header">
            <span class="sbq-letter-badge">T</span>
            <p class="sbq-q-label">Do you often feel <strong>Tired</strong>, fatigued, or sleepy during the daytime (such as falling asleep while driving or talking)? <span class="req">*</span></p>
          </div>
          <div class="sbq-options sbq-options--2col" data-name="tired">
            <label class="sbq-option"><input type="radio" name="tired" value="yes" /><span>Yes</span></label>
            <label class="sbq-option"><input type="radio" name="tired" value="no" /><span>No</span></label>
          </div>
        </div>

        <div class="sbq-question">
          <div class="sbq-q-header">
            <span class="sbq-letter-badge">O</span>
            <p class="sbq-q-label">Has anyone <strong>Observed</strong> you stop breathing, or choking/gasping during your sleep? <span class="req">*</span></p>
          </div>
          <div class="sbq-options sbq-options--2col" data-name="observed">
            <label class="sbq-option"><input type="radio" name="observed" value="yes" /><span>Yes</span></label>
            <label class="sbq-option"><input type="radio" name="observed" value="no" /><span>No</span></label>
          </div>
        </div>

        <div class="sbq-question">
          <div class="sbq-q-header">
            <span class="sbq-letter-badge">P</span>
            <p class="sbq-q-label">Do you have or are you being treated for high blood <strong>Pressure</strong>? <span class="req">*</span></p>
          </div>
          <div class="sbq-options sbq-options--2col" data-name="pressure">
            <label class="sbq-option"><input type="radio" name="pressure" value="yes" /><span>Yes</span></label>
            <label class="sbq-option"><input type="radio" name="pressure" value="no" /><span>No</span></label>
          </div>
        </div>

      </div>
    </div>

    <!-- STEP 3: BANG -->
    <div class="sbq-step" data-step="3">
      <div class="sbq-step-intro">
        <span class="sbq-step-badge">BANG Questions</span>
        <h2>A few more details</h2>
        <p>Your BMI and age risk are calculated automatically from your inputs.</p>
      </div>

      <div class="sbq-questions">

        <!-- B: BMI Calculator -->
        <div class="sbq-question">
          <div class="sbq-q-header">
            <span class="sbq-letter-badge">B</span>
            <p class="sbq-q-label"><strong>Body Mass Index</strong> &mdash; Enter your height and weight. <span class="req">*</span></p>
          </div>

          <div class="sbq-bmi-calc">
            <div class="sbq-unit-toggle">
              <button type="button" class="sbq-unit-btn active" data-unit="metric">cm / kg</button>
              <button type="button" class="sbq-unit-btn" data-unit="imperial">inches / lb</button>
            </div>

            <div id="sbq-metric-inputs" class="sbq-unit-inputs sbq-field-row">
              <div class="sbq-field">
                <label for="sbq-h-cm">Height (cm)</label>
                <input type="number" id="sbq-h-cm" name="height_cm" min="100" max="250" placeholder="e.g. 178" />
              </div>
              <div class="sbq-field">
                <label for="sbq-w-kg">Weight (kg)</label>
                <input type="number" id="sbq-w-kg" name="weight_kg" min="30" max="300" placeholder="e.g. 80" />
              </div>
            </div>

            <div id="sbq-imperial-inputs" class="sbq-unit-inputs" style="display:none">
              <div class="sbq-field-row">
                <div class="sbq-field">
                  <label>Height</label>
                  <div class="sbq-height-row">
                    <input type="number" id="sbq-h-ft" name="height_ft_imp" min="3" max="8" placeholder="ft" />
                    <input type="number" id="sbq-h-in" name="height_in_imp" min="0" max="11" placeholder="in" />
                  </div>
                </div>
                <div class="sbq-field">
                  <label for="sbq-w-lb">Weight (lb)</label>
                  <input type="number" id="sbq-w-lb" name="weight_lb" min="66" max="660" placeholder="e.g. 176" />
                </div>
              </div>
            </div>

            <div class="sbq-bmi-display">
              <span class="sbq-bmi-label">Your BMI:</span>
              <span class="sbq-bmi-value" id="sbq-bmi-val">&mdash;</span>
              <span id="sbq-bmi-badge" class="sbq-auto-badge" style="display:none"></span>
            </div>
          </div>

          <input type="hidden" name="bmi" id="sbq-bmi-hidden" />
          <input type="hidden" name="height_display" id="sbq-height-display" />
          <input type="hidden" name="weight_display" id="sbq-weight-display" />
        </div>

        <!-- A: Age -->
        <div class="sbq-question">
          <div class="sbq-q-header">
            <span class="sbq-letter-badge">A</span>
            <p class="sbq-q-label"><strong>Age</strong> &mdash; How old are you? <span class="req">*</span></p>
          </div>
          <div class="sbq-field" style="max-width:200px">
            <input type="number" id="sbq-age" name="age" min="18" max="120" placeholder="e.g. 52" />
          </div>
          <span id="sbq-age-badge" class="sbq-auto-badge" style="display:none;margin-top:10px"></span>
        </div>

        <!-- N: Neck -->
        <div class="sbq-question">
          <div class="sbq-q-header">
            <span class="sbq-letter-badge">N</span>
            <p class="sbq-q-label">Is your shirt collar <strong>16&Prime; / 40&nbsp;cm or larger</strong>? (measured around Adam's apple) <span class="req">*</span></p>
          </div>
          <div class="sbq-options sbq-options--2col" data-name="neck_large">
            <label class="sbq-option"><input type="radio" name="neck_large" value="yes" /><span>Yes</span></label>
            <label class="sbq-option"><input type="radio" name="neck_large" value="no" /><span>No</span></label>
          </div>
        </div>

        <!-- G: Gender -->
        <div class="sbq-question">
          <div class="sbq-q-header">
            <span class="sbq-letter-badge">G</span>
            <p class="sbq-q-label"><strong>Gender</strong> <span class="req">*</span></p>
          </div>
          <div class="sbq-options sbq-options--2col" data-name="gender">
            <label class="sbq-option"><input type="radio" name="gender" value="male" /><span>Male</span></label>
            <label class="sbq-option"><input type="radio" name="gender" value="female" /><span>Female</span></label>
          </div>
        </div>

      </div>
    </div>

  </div><!-- /sbq-body -->

  <!-- Error -->
  <div class="sbq-error" id="sbq-error" style="display:none"></div>

  <!-- Navigation -->
  <div class="sbq-nav" id="sbq-nav">
    <button type="button" class="sbq-btn sbq-btn--ghost" id="sbq-prev" style="visibility:hidden">
      <svg viewBox="0 0 20 20" fill="none"><path d="M12 15l-5-5 5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      Back
    </button>
    <button type="button" class="sbq-btn sbq-btn--primary" id="sbq-next">
      Next
      <svg viewBox="0 0 20 20" fill="none"><path d="M8 5l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
  </div>

  <!-- Results -->
  <div class="sbq-results" id="sbq-results" style="display:none">
    <div id="sbq-result-inner"></div>
    <p class="sbq-disclaimer">
      This tool is for informational screening only and does not constitute a medical diagnosis. The STOP-Bang questionnaire is a validated clinical screening tool. Please consult a qualified healthcare provider for a formal evaluation.
    </p>
  </div>

</div><!-- /sbq-wrap -->
