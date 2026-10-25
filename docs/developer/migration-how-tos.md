
A baseline migration is the **starting schema definition** for one app. It creates the tables for a fresh database; later migrations make incremental changes. It should **not** drop existing tables or attempt to recreate an existing database’s schema.

## CLI workflow

The Yii-like migration command is `cmd/migrate.php`. Run it from the repository root:

```powershell
php cmd/migrate.php help
php cmd/migrate.php status all --env=development
php cmd/migrate.php adopt admin --yes --schema-reviewed --env=development
php cmd/migrate.php adopt stitch --yes --schema-reviewed --env=development
php cmd/migrate.php adopt neighborhub --yes --schema-reviewed --env=development
php cmd/migrate.php up all --env=development
php cmd/migrate.php status neighborhub --env=development
php cmd/migrate.php down neighborhub --yes --env=development
php cmd/migrate.php create neighborhub add_delivery_window
```

Supported apps are `admin`, `stitch`, and `neighborhub`. `up all` applies them in dependency order: Admin, Stitch, then NeighborHub. `down` rolls back only the latest applied migration for one app and requires `--yes`. The CLI defaults to production database configuration unless `APP_ENV=development` or `--env=development` is specified. Production settings come from the same `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables as the web application.

`create` writes a timestamped migration skeleton under `html/apps/<app>/migrations/`; it does not connect to the database. Fill in the `up` callback and include `down` only if reversing the change is safe.

`adopt <app>` verifies the baseline's expected table and column names and foreign-key relationships, then records that baseline as applied **without executing its `up` callback**. It requires both `--yes` and `--schema-reviewed`. Before using it, manually compare the live database to the baseline for column types, nullability, defaults, indexes, unique constraints, check constraints, spatial definitions, and table engine; the automatic verifier does not compare those properties. Adopt one app at a time in dependency order: Admin, Stitch, NeighborHub. A mismatch in the automatically checked structure stops adoption without recording the baseline.

Do not use `up` to initialize a populated database. Use `adopt` only after backing up the database and completing the manual schema review; if any schema details differ, do not adopt until the baseline or the live schema has been reconciled.

## Admin migration UI

Administrators can inspect and operate migrations at `/?app=admin&p=migrations`. The page reads status without creating the history table, shows the active environment/database, and reports pending/applied migrations, checksum drift, missing files, recorded timestamps, and existing baseline-table collisions. Adoption and application require an explicit Materialize confirmation, a current-backup acknowledgement, and typing the target database name; adoption also requires the complete-schema-review acknowledgement. The API remains admin-only and checks CSRF on each mutation.

The UI uses the same runner and migration files as the CLI. Existing populated databases must use baseline adoption in dependency order; fresh databases can apply the baseline and subsequent migrations. The UI prominently discloses that the verifier does not compare all schema attributes, so an acknowledgement does not replace the required manual comparison. Never use the UI to operate on production until the backup and schema review are complete. Operation outcomes are logged with the actor and a correlation ID.

## Suggested workflow

1. **Capture each app’s current MySQL schema**—tables, columns, indexes, foreign keys, and constraints—and review it against the live database. The current definitions are in `admin.app.php`, `stitch.app.php`, and `neighborhub.app.php`. Don’t copy the `DROP TABLE` statements into migrations.

2. **Create one migration directory per app** (the CLI creates it on the first `create` command), for example:
   ```text
   html/apps/admin/migrations/
   html/apps/stitch/migrations/
   html/apps/neighborhub/migrations/
   ```
   The current runner reads PHP files directly from the specified directory, not nested subdirectories. Migration filenames must start with a 14-digit timestamp:
   ```text
   20261023170000_baseline.php
   ```

3. **Put the schema creation in the migration’s `up` callback.** The runner expects a PHP file to return an array with a callable `up` function that receives the PDO connection:
   ```php
   <?php

   return [
       'up' => static function (PDO $db): void {
           $db->exec("
               CREATE TABLE example_records (
                   id INT AUTO_INCREMENT PRIMARY KEY,
                   name VARCHAR(255) NOT NULL
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
           ");
       },
   ];
   ```
   For actual baselines, include each app’s reviewed schema and create referenced parent tables before child tables. Don’t put multiple statements in one SQL string: execute each statement separately.

4. **Treat the baseline as immutable once applied.** The runner stores a checksum and will report an error if an applied migration file changes. Make future schema changes in a new, timestamped migration. For a baseline that would remove core data, omit `down`; the runner will then refuse to roll it back.

5. **Test the baseline on a disposable, empty MySQL database.** Check that all tables, indexes, and foreign keys are present, then run migration status and migration application again to verify it reports no pending work. A second run should not recreate or change schema.

## Existing databases need an adoption step

Don’t run the fresh-database baseline against a populated database. The current runner has no “verify this schema and record the baseline as already applied” operation. We need to add that adoption path: compare the existing schema with the expected baseline, stop on mismatches, and record the baseline only after it passes validation. Until then, **do not connect the runner to the current install hooks or run it against production**.

One important caveat: MySQL may implicitly commit DDL, so a multi-table baseline can be only partly applied if a statement fails. A failed attempt needs schema inspection and repair before retrying. The runner foundation is useful, but baseline adoption and MySQL partial-failure handling should be addressed before rollout.