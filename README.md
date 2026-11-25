# Aurora Framework

## Superadmin sessions

Each superadmin login creates a separate row in `core_superadmin_sessions` with
a session identifier hash and an expiration time. Apply the database migration
before serving the upgraded application (CLI: `php system/bin/console migrate --no-interaction`).
On initial setup with an empty database name, the setup browser uses a PHP session
to configure the database and create the tables.

`SuperAdminAuthTokenExpirationLifetimeDays` sets an absolute lifetime for new
superadmin sessions (default and minimum 1 day). Logout revokes the current session.
After upgrading from a version without this store, superadmins must sign in again,
including API clients. Expired rows are removed on subsequent admin logins.

`StoreAuthTokenInDB` is deprecated and applies only to non-superadmin sessions.
Existing installations retain their configured behavior. `UsedDevices` and
`AuthTokenExpirationLifetimeDays` continue to apply to their existing user flows.

# Development
This repository has a pre-commit hook. To make it work you need to configure git to use the particular hooks folder.

`git config --local core.hooksPath .githooks/`

# Licensing
This package is licensed under AGPLv3 license or Afterlogic Software License if commercial version of the product was purchased.
