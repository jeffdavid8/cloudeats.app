# NeighborHub Customer Registration

The NeighborHub header signup form creates inactive customer accounts. Users can sign in only after verifying the email address; authentication already rejects users whose `users.active` flag is false.

## Configuration

Set these environment variables for each deployment:

- `APP_BASE_URL`: public application origin, including scheme, used in verification links and reCAPTCHA hostname validation.
- `RECAPTCHA_SITE_KEY` and `RECAPTCHA_SECRET_KEY`: Google reCAPTCHA v3 keys registered for that hostname.
- `RECAPTCHA_MIN_SCORE`: optional score threshold; defaults to `0.5`.
- `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_USER`, and `MAIL_PASS`: SMTP settings (`587` and `tls` are the defaults).
- `MAIL_FROM_EMAIL`: optional verified sender address; defaults to `MAIL_USER`.

The reCAPTCHA v3 script is loaded in the shared document head whenever a site key is configured. PHPMailer is a Composer dependency. The registration API creates its verification-token and signup-attempt tables if they do not already exist.

## Flow

The `register_customer` API action validates the form, CSRF token, reCAPTCHA action/score/hostname, and a limit of ten signup attempts per client IP per hour; it creates the user and customer profile in one transaction and sends a 24-hour verification link. The `verify_registration` action consumes the one-time token and activates the user. Registration responses avoid disclosing whether an email address already has an account.
