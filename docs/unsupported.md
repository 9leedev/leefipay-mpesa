# Unsupported Open API features

Based on the LeefiPay application Open API (`/api/v1`), the following are **not** available to third-party integrators and are therefore omitted from this package:

| Feature | Status |
|---------|--------|
| B2C payouts | Not exposed |
| C2B register / validation URLs | Not exposed |
| Account balance enquiry | Not exposed |
| Transaction reversal | Not exposed |
| Direct Daraja OAuth / consumer key/secret | Platform-internal only |
| REST create webhook endpoint | Dashboard only |

If LeefiPay adds these endpoints later, this package can gain matching methods in a minor/major release as appropriate.
