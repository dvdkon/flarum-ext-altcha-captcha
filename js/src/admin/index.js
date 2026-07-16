import app from 'flarum/admin/app';

app.initializers.add('dvdkon-altcha-captcha', () => {
  app.extensionData
    .for('dvdkon-altcha-captcha')
    .registerSetting({
      setting: 'dvdkon-altcha-captcha.hmac_secret',
      type: 'text',
      label: app.translator.trans('dvdkon-altcha-captcha.admin.settings.hmac_secret_label'),
      help: app.translator.trans('dvdkon-altcha-captcha.admin.settings.hmac_secret_help'),
    })
    .registerSetting({
      setting: 'dvdkon-altcha-captcha.cost',
      type: 'number',
      label: app.translator.trans('dvdkon-altcha-captcha.admin.settings.cost_label'),
      help: app.translator.trans('dvdkon-altcha-captcha.admin.settings.cost_help'),
    })
    .registerSetting({
      setting: 'dvdkon-altcha-captcha.expiry',
      type: 'number',
      label: app.translator.trans('dvdkon-altcha-captcha.admin.settings.expiry_label'),
      help: app.translator.trans('dvdkon-altcha-captcha.admin.settings.expiry_help'),
    })
    .registerSetting({
      setting: 'dvdkon-altcha-captcha.protect_registration',
      type: 'boolean',
      label: app.translator.trans('dvdkon-altcha-captcha.admin.settings.protect_registration_label'),
      help: app.translator.trans('dvdkon-altcha-captcha.admin.settings.protect_registration_help'),
    })
    .registerSetting({
      setting: 'dvdkon-altcha-captcha.protect_login',
      type: 'boolean',
      label: app.translator.trans('dvdkon-altcha-captcha.admin.settings.protect_login_label'),
      help: app.translator.trans('dvdkon-altcha-captcha.admin.settings.protect_login_help'),
    });
});
