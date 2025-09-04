# Symfony Crypto rates

Web application on Symfony and PHP 8.x that provides an API for cryptocurrency exchange
rates from EUR to BTC, ETH, and LTC.

## Getting Started

1. If not already done, install Docker Compose
2. Create .env file from the provided .env.example template
3. Run `docker compose build --pull --no-cache` to build fresh images
4. Run `docker compose up --wait` to set up and start a fresh Symfony project
5. Initialize the database:
   `docker compose exec php php bin/console doctrine:migrations:migrate`
6. Start the scheduler worker (required for periodic updates):
   `docker compose exec php php bin/console messenger:consume scheduler_default`
7. To run tests use `docker compose exec php php bin/phpunit`

## Functionality:
* Data source: [Binance API](https://developers.binance.com/docs/binance-spot-api-docs/rest-api/market-data-endpoints#symbol-price-ticker).
* Periodic update: Store rates (EUR/BTC, EUR/ETH, EUR/LTC) in MySQL every 5 minutes using Symfony
  Scheduler. 
* API endpoints (JSON responses for charts):
  - a. /api/rates/last-24h?pair=EUR/BTC — Rates for the last 24 hours (every 5 minutes).
  - b. /api/rates/day?pair=EUR/BTC&date=YYYY-MM-DD — Rates for the specified day (every 5 minutes).
* Storage: MySQL

## Examples

### Last 24h
```bash
curl --location 'http://localhost/api/rates/last-24h?pair=BTC%2FEUR'
```

### Day
```bash
curl --location 'http://localhost/api/rates/day?pair=BTC%2FEUR&date=2025-09-03'
```
