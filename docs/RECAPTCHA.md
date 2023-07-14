# Google reCAPTCHA (v2) Service

**Summary:** This service is utilized for improving security on user access.

## Generate reCAPTCHA Site Key

**Summary:** Sign up for a google developer account and register your site to get the Key.

1. Sign up for a google developer
2. Go to reCAPTCHA admin page to register: https://www.google.com/recaptcha/admin/create
3. Select reCAPTCHA type as v2, input domains for web and owner email address, and then submit.
4. Copy your `SiteKey` as `RECAPTCHA_SITE_KEY` and `SecretKey` as `RECAPTCHA_SECRET_KEY`.
5. Set `RECAPTCHA_DISABLED`=false
