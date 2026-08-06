# Coffers

Coffers is a companion app for tabletop D&D campaigns. It gives the DM a place to manage campaigns, shops, NPCs, scenes, and journal entries, and gives players a place to manage their characters, inventory, and in-game purchases — all shared live within a campaign.

## Core concepts

- **Campaigns** — a DM owns a campaign and invites players (friends) into it. Each campaign has its own shops, scenes, NPCs, and journal.
- **Characters** — each player has a character per campaign, with a character sheet (level, health, AC, statuses) and an inventory.
- **Shops** — the DM stocks shops with items (price, quantity); players browse and buy. Shop open/close state updates live for anyone viewing it.
- **Journal** — DM notes that can be kept private or revealed to players.
- **Scenes & NPCs** — DM-side tools for tracking what's happening in a session.
- **Friends** — the social layer connecting users so they can be invited into each other's campaigns.

Access to DM-only pages (`/characters`, `/scenes`) and player-only pages (`/character`, `/inventory`) is gated by which role a user has selected for the current campaign, not a fixed account role — the same user can DM one campaign and play in another.

## Tech stack

- **Laravel 13** (PHP 8.4)
- **Filament v5** — admin panel for managing campaigns, shops, items, characters, friends, and users
- **Livewire v4** + **Flux UI v2** for interactive frontend components
- **Fortify** — authentication, including email verification, two-factor auth, and passkeys
- **Reverb** — real-time broadcasting for live shop open/close updates
- **Horizon** — queue dashboard (queue driver defaults to the database, no Redis required locally)
- **Nightwatch** — application observability
- **Tailwind CSS v4** + Vite
- **Pest v4** for testing, **Larastan** for static analysis, **Pint** for formatting

Image uploads (campaign, character, shop, scene, and journal images) are stored on S3 via `intervention/image` and `league/flysystem-aws-s3-v3`; everything else stays on the local disk. An observer cleans up the old S3 object whenever an image is replaced or its owning model is deleted.

## Getting started

Requires PHP 8.4, Composer, and Node.

```bash
composer setup
```

This copies `.env.example` to `.env`, generates an app key, runs migrations (SQLite by default), installs npm dependencies, and builds assets.

Then start the app:

```bash
composer dev
```

This runs the dev server, queue worker, Vite, and log tailing concurrently via `php artisan dev`.

### Optional environment setup

- **Real-time shop updates**: set `BROADCAST_CONNECTION=reverb` and fill in the `REVERB_*`/`VITE_REVERB_*` values, then run `php artisan reverb:start`. Without this, broadcasting falls back to the log driver and shop status won't update live.
- **Image uploads**: fill in `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, and `AWS_BUCKET` to enable uploads for campaigns, characters, shops, scenes, and journal entries.

## Testing & quality checks

```bash
composer test        # config:clear, Pint check, Larastan, then Pest
composer lint         # fix formatting with Pint
composer types:check  # Larastan static analysis
php artisan test --compact --filter=SomeTest   # run a single test
```
