# Raffaly Platform

A white-label online raffle and competition platform with integrated payment processing, automated drawing, and comprehensive prize management.

## Overview

Raffaly enables users to host and participate in online raffles with three distinct formats:

- **Traditional Raffles**: Ticket-based competitions with scheduled or automatic draws
- **Access Raffles**: Direct purchase access to prizes with time-limited payment links
- **Daily Drops**: Free daily prize draws with one entry per user

## Tech Stack

- **Framework**: Laravel 12.36.1
- **PHP**: 8.2+
- **Database**: MySQL
- **Queue**: Redis + Horizon
- **Frontend**: Vue 3, Tailwind CSS, Livewire
- **Payments**: TrustPayments (Axcess), PayPal, Revolut
- **Email**: Postmark
- **Storage**: AWS S3

## Features

- Multi-tenant architecture with custom domains
- Automated competition drawing system
- Integrated payment processing (multiple providers)
- Email verification and phone verification
- Commission-based earnings system
- Affiliate program
- Discount system (per-ticket, batch, checkout-total)
- Free ticket promotions
- Newsletter integration (Mailchimp/MailerLite)
- SMS notifications (Twilio)
- Review and rating system
- Zapier webhook integration
- Admin approval workflow
- Automated prize acceptance reminders

## Local Development Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL
- Redis

### Installation

```bash
# Clone repository
git clone <repository-url>
cd raffaly

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Build assets
npm run dev

# Start services
php artisan horizon    # Queue worker
php artisan serve      # Development server
```

### Environment Variables

Key environment variables to configure:

```env
# App
APP_KEY=
APP_URL=http://localhost
JWT_SECRET=

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=raffaly
DB_USERNAME=root
DB_PASSWORD=

# TrustPayments
TRUST_PAYMENTS_TEST_USERNAME=
TRUST_PAYMENTS_TEST_PASSWORD=
TRUST_PAYMENTS_SITE_REFERENCE=

# Axcess API
RAFFALY_AXCESS_API_URL=https://gateway.cashflows.com/
RAFFALY_AXCESS_CONFIG_ID=
RAFFALY_AXCESS_API_KEY=

# PayPal
PAYPAL_MODE=sandbox
PAYPAL_SANDBOX_CLIENT_ID=
PAYPAL_SANDBOX_CLIENT_SECRET=

# Revolut
REVOLUT_WEBHOOK_SECRET=

# Email
POSTMARK_TOKEN=

# SMS
TWILIO_SID=
TWILIO_AUTH_TOKEN=
TWILIO_VERIFY_SID=

# Newsletter
MAILERLITE_API_KEY=
MAILERLITE_GROUP_ID=

# Storage
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
```

## Development Workflow

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

### Code Formatting

```bash
# Format all code
./vendor/bin/pint

# Check without formatting
./vendor/bin/pint --test
```

### Queue Management

```bash
# Start Horizon
php artisan horizon

# Restart workers after code changes
php artisan horizon:terminate
```

### Database

```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh database (WARNING: deletes all data)
php artisan migrate:fresh --seed
```

## Deployment

### Pre-Deployment Checklist

1. Run tests: `php artisan test`
2. Format code: `./vendor/bin/pint`
3. Update dependencies: `composer update --no-dev`
4. Build assets: `npm run production`
5. Clear config cache: `php artisan config:clear`

### Migrations to Run

When deploying this branch (`upgrade-laravel-8-to-11`):

```bash
php artisan migrate
```

**New migrations:**
1. `rename_cashflows_to_axcess_in_tables` - Renames payment provider columns
2. `cleanup_prospect_and_brevo_tables` - Removes deprecated Prospect and Brevo tables

### Post-Deployment

```bash
# Clear all caches
php artisan optimize:clear

# Restart queue workers
php artisan horizon:terminate

# Restart server (if using php artisan serve)
# Or restart PHP-FPM/supervisor
```

## Project Structure

### Routes Organization

Routes are organized by feature:
- `routes/web.php` - Main entry point with includes
- `routes/drops.php` - Daily drops feature
- `routes/access-links.php` - Access raffle links
- `routes/webhooks.php` - Payment provider webhooks
- `routes/shared/*.php` - Grouped route files

### Key Services

- `CheckoutService` - Checkout calculations, totals, discounts
- `TicketService` - Ticket assignment, competition draw
- `CompetitionService` - Competition lifecycle management
- `AxcessService` - TrustPayments API integration
- `PayPalService` - PayPal order creation and refunds
- `RevolutService` - Revolut payment processing
- `LocationService` - Timezone and country detection
- `AccountService` - User account statistics

## Common Tasks

### Creating a Competition

1. Multi-step form (6 steps)
2. Step 1: Basic details (title, description)
3. Step 2: Prizes
4. Step 3: Tickets (price, quantity)
5. Step 4: Charity selection
6. Step 5: Extra settings (free tickets, delivery)
7. Step 6: Affiliates
8. Submit for review → admin approval

### Processing Payments

Handled by webhooks and checkout store:
- TrustPayments: iframe integration, webhook callback
- PayPal: redirect flow, capture on return
- Revolut: webhook-based status updates

### Drawing Winners

Automated via scheduled command:
```bash
php artisan competition:draw  # Runs every 15 minutes
php artisan competition:drop-draw  # Runs daily at 00:01
```

## Troubleshooting

### Common Issues

**Checkout expiry errors:**
- Check Redis connection
- Verify timezone settings in LocationService

**Payment failures:**
- Check provider credentials in .env
- Verify webhook URLs are accessible
- Check Horizon for failed jobs

**Email not sending:**
- Verify Postmark token
- Check `emails` table for queued emails
- Check Horizon queue

**Assets not loading:**
- Run `npm run dev` or `npm run production`
- Check Mix manifest exists: `public/mix-manifest.json`

## Support

For issues or questions, contact: liam@raffaly.com

## License

Proprietary - All rights reserved

