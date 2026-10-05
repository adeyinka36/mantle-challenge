# Checkout Challenge

Thanks for taking the time to do this. It is a take-home exercise built around a small slice of a legacy Symfony
application from a fictional online shop. We want to see how you improve existing code, not how much you can write from
scratch.

**Suggested time:** 3–4 hours. Please don't spend more. If you run out of time, write down what you would do next.

## Background

The shop is a Symfony 7.4 application using Doctrine ORM and Symfony Mailer. The entities in `src/Entity/` (`Customer`,
`Order`, `OrderItem`) are fine as a starting point. Everything else lives in a single action,
`CheckoutController::checkout()` (`POST /checkout`): request parsing, customer lookup, pricing and discounts, gift and
free-sample handling, persistence, email and logging.

It works in production but the team is struggling with it:

- every new customer type or notification channel means editing the same method,
- nobody can test the pricing rules without booting the kernel, a database and a mailer,
- the warehouse and gifting teams have bolted their features on as extra `if` branches in that method.

Example request:

```bash
curl -X POST localhost:8000/checkout -H 'Content-Type: application/json' -d '{
  "email": "jo@example.com",
  "customerType": "vip",
  "cart": [{"sku": "MUG-01", "price": 12.50, "qty": 2}],
  "gift": true,
  "recipientEmail": "sam@example.com"
}'
```

`customerType` is one of `regular`, `vip`, `staff`, `trade`. `gift` + `recipientEmail` and `sample` are optional.

Your job is to refactor this into code that follows Symfony conventions and SOLID principles, favouring composition over
conditionals and inheritance, and to deliver the four tickets the product team has queued against it. You own all of
them.

## Tickets

### CHK-101: Student discount

Add a `student` customer type that gets **15% off** the cart subtotal.

### CHK-102 (bug): Gift orders are wrong

When a customer places a gift order:

- they are charged the subtotal plus £3.50 gift wrap, but the order record is saved **without** the gift wrap,
- the **recipient** receives an email showing the price, and the purchaser receives nothing.

Expected behaviour:

- the persisted total includes gift wrap,
- the **purchaser** receives the normal priced confirmation,
- the **recipient** receives a message with no prices in it.

### CHK-103: SMS confirmations

Customers can now give a mobile number at checkout (a new optional `mobile` field in the `POST /checkout` payload). If
they do, send them an SMS confirmation **in addition** to the email. Customers without a number get email only.

You don't need a real SMS provider. Define how the code talks to one and fake it in tests.

### CHK-104 (bug): Negative totals

Trade customers placing small orders sometimes end up with a negative total. An order total must never be below £0.00.

## Requirements

- PHP 8.2+, Symfony 7.4, PHPUnit 11.
- The controller should end up thin: parse the request, call one collaborator, return a response. Business rules belong
  in small, single-purpose services composed through constructor injection and autowiring (tagged services, interfaces
  and configuration in `config/` are all fair game).
- You may add Symfony components or bundles (Validator, Serializer, Notifier, Monolog, Doctrine Migrations, ...) where
  they earn their place, but none are required.
- Existing discount rules (`vip`, `staff`, `trade`) must keep the same results, apart from the CHK-104 fix.
- `composer test` must pass, and the tests must not need a database, mail server, network or filesystem (the Symfony
  cache in `var/` is fine). Unit tests should not need to boot the kernel; a small number of `KernelTestCase`/
  `WebTestCase` tests to prove the wiring are welcome.
- You may restructure, rename, add or delete any class in `src/`, including the controller and the entities. If you
  change the HTTP contract or the database schema, tell us what callers would need to change.
- You don't need a real SMS integration. An interface plus a test double is fine.

## What to submit

1. A git repository (zip with the `.git` folder, or a private repo link). **Commit as you go.** We read the history.
2. A `NOTES.md` covering:
    - the main problems you found in the original code and why they mattered,
    - the key design decisions you made and the trade-offs,
    - anything you deliberately left out or would do with more time.

## What we look at

- How easy your design makes the *next* change, e.g. another discount or notification channel.
- Whether the code is honest about its dependencies and easy to test.
- Sensible use of Symfony: the container, autowiring, configuration and environment variables doing the work, without
  framework code leaking into the domain logic.
- SOLID in practice, not in name: single-purpose classes, behaviour added by composing new classes rather than editing
  old ones.
- Test quality: what you chose to test and how readable the tests are.
- Proportion. We value a clear, simple solution over a clever one. Don't build a framework on top of the framework.
- Your reasoning in `NOTES.md` and the commit history.

## Getting started

With Docker (nothing but Docker needed on your machine):

```bash
docker compose up -d --build
docker compose exec app composer test
```

The app is on http://localhost:8000 and every email it sends lands in Mailpit on http://localhost:8025. The database is
a SQLite file in `var/`; the schema is created on start-up. Run any other command the same way, e.g.
`docker compose exec app bin/console debug:router`. Set `APP_PORT` / `MAILPIT_PORT` if the defaults clash.

Without Docker (PHP 8.2+ with the `ctype`, `iconv`, `xml`, `dom`, `mbstring` and `pdo_sqlite` extensions):

```bash
composer install
bin/console doctrine:schema:create
composer test
php -S localhost:8000 -t public
```

Here `MAILER_DSN` defaults to the null transport, so no mail server is needed.

`tests/Unit/` already holds a few unit tests that pin down how checkout behaves today (discounts, persistence, confirmation email, free samples, empty cart). They don't cover gift orders or small trade orders, because those are the bugs in CHK-102 and CHK-104. Use them as a safety net while you refactor. Rewrite or replace them as the code changes shape.

Questions? Reply to the email you got this from. If something is ambiguous, make a sensible assumption and note it in
`NOTES.md`.
