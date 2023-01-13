# Sync Channels across PJE sites

We have a helper controller that can be run in dev mode to keep content models programmatically in sync.

Run the command locally and save any project config yaml updates to deploy on staging or production.

- `ddev craft pje-shared/sync/notifications` Runs `services/Notifications.php`
- `ddev craft pje-shared/sync/external-links` Runs `services/ExternalLinksSectionSync.php`
