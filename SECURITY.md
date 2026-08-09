# Security Policy

## Supported versions

| Version | Supported |
|---------|-----------|
| 0.1.x   | Yes       |

## Reporting a vulnerability

Please report security issues privately to **support@leefipay.com**.

Do not open public GitHub issues for vulnerabilities that could expose merchant funds, API tokens, or customer data.

Include:

- Package version
- Laravel / PHP versions
- Steps to reproduce
- Impact assessment

We will acknowledge receipt within a few business days and coordinate a fix and disclosure timeline.

## Safe usage

- Never commit `.env` files or live API tokens
- Never log Bearer tokens, webhook secrets, or raw STK payloads containing secrets
- Validate webhook signatures before trusting event payloads
- Use HTTPS in production
