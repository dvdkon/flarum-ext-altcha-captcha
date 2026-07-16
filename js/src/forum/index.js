import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import SignUpModal from 'flarum/forum/components/SignUpModal';
import LogInModal from 'flarum/forum/components/LogInModal';
import 'altcha';

// Work around a bundling bug where [...globalThis.$altcha.plugins] (a Set)
// is transpiled to [].concat(Set), which wraps the Set instead of spreading it.
if (globalThis.$altcha && globalThis.$altcha.plugins instanceof Set) {
  globalThis.$altcha.plugins = Array.from(globalThis.$altcha.plugins);
}

app.initializers.add('dvdkon-altcha-captcha', () => {
  function challengeUrl() {
    return app.forum.attribute('baseUrl') + '/altcha-challenge';
  }

  function setupWidget(modal) {
    const widget = modal.$('altcha-widget')[0];
    if (!widget) return;

    widget.addEventListener('statechange', (e) => {
      if (e.detail && e.detail.state === 'verified') {
        widget.setAttribute('data-altcha-payload', e.detail.payload || '');
      } else if (e.detail && e.detail.state !== 'verified') {
        widget.setAttribute('data-altcha-payload', '');
      }
    });
  }

  function getPayload(modal) {
    const widget = modal.$('altcha-widget')[0];
    if (!widget) return '';

    // Prefer the hidden input the widget creates by default.
    const input = widget.querySelector('input[type="hidden"][name="altcha"]');
    if (input) return input.value || '';

    return widget.getAttribute('data-altcha-payload') || '';
  }

  function showCaptchaError(modal) {
    modal.alertAttrs = {
      type: 'error',
      content: app.translator.trans('dvdkon-altcha-captcha.forum.captcha_required'),
    };
    modal.loading = false;
    m.redraw();
  }

  function altchaField() {
    return (
      <div className="Form-group AltchaCaptcha-container">
        <altcha-widget challenge={challengeUrl()} name="altcha"></altcha-widget>
      </div>
    );
  }

  // --- Sign Up ---

  extend(SignUpModal.prototype, 'fields', function (items) {
    if (!app.forum.attribute('dvdkon-altcha-captcha.protect_registration')) return;

    items.add('altchaCaptcha', altchaField(), -10);
  });

  extend(SignUpModal.prototype, 'oncreate', function () {
    if (app.forum.attribute('dvdkon-altcha-captcha.protect_registration')) {
      setupWidget(this);
    }
  });

  const originalSignUpSubmit = SignUpModal.prototype.onsubmit;
  SignUpModal.prototype.onsubmit = function (e) {
    if (app.forum.attribute('dvdkon-altcha-captcha.protect_registration')) {
      if (!getPayload(this)) {
        e.preventDefault();
        showCaptchaError(this);
        return;
      }
    }
    return originalSignUpSubmit.call(this, e);
  };

  extend(SignUpModal.prototype, 'submitData', function (data) {
    if (!app.forum.attribute('dvdkon-altcha-captcha.protect_registration')) return data;

    data.altcha = getPayload(this);
    return data;
  });

  // --- Log In ---

  extend(LogInModal.prototype, 'fields', function (items) {
    if (!app.forum.attribute('dvdkon-altcha-captcha.protect_login')) return;

    items.add('altchaCaptcha', altchaField(), -10);
  });

  extend(LogInModal.prototype, 'oncreate', function () {
    if (app.forum.attribute('dvdkon-altcha-captcha.protect_login')) {
      setupWidget(this);
    }
  });

  const originalLoginSubmit = LogInModal.prototype.onsubmit;
  LogInModal.prototype.onsubmit = function (e) {
    if (app.forum.attribute('dvdkon-altcha-captcha.protect_login')) {
      if (!getPayload(this)) {
        e.preventDefault();
        showCaptchaError(this);
        return;
      }

      const payload = getPayload(this);

      const originalRequest = app.request.bind(app);
      app.request = (options) => {
        if (options.url && options.url.includes('/login') && options.body) {
          options.body.altcha = payload;
        }
        return originalRequest(options);
      };

      const result = originalLoginSubmit.call(this, e);
      app.request = originalRequest;
      return result;
    }

    return originalLoginSubmit.call(this, e);
  };
});
