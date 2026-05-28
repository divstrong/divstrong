# Deploy

PowerShell deploy script for pushing the divStrong Laravel app to a Linux VPS over SSH.

## One-time setup

1. Copy the config template:
   ```powershell
   Copy-Item deploy/.deploy.env.example deploy/.deploy.env
   ```
2. Edit `deploy/.deploy.env` with your real SSH host, user, port, remote path, branch.
3. Make sure your SSH key is loaded so `ssh $user@$host` works without a password prompt.
4. Make sure the remote already has the repo cloned at `REMOTE_PATH` and a `.env` configured.

The real `.deploy.env` is gitignored.

## Usage

From the repo root or `server/`:

```powershell
# normal deploy (build + push + remote update)
npm run deploy            # from server/
# or
./deploy/deploy.ps1       # from anywhere

# dry run — show what would happen, change nothing
npm run deploy:dry
./deploy/deploy.ps1 -DryRun

# skip the local Vite build (nothing front-end changed)
./deploy/deploy.ps1 -SkipBuild

# skip the local git push (server is on a branch ahead of origin)
./deploy/deploy.ps1 -SkipPush

# skip the interactive prompt
./deploy/deploy.ps1 -Yes
```

## What it does

1. Pre-flight: warns if working tree is dirty or you're on a non-deploy branch.
2. `git push origin <BRANCH>`.
3. `npm ci && npm run build` in `server/` (local).
4. `scp -r server/public/build/* → remote:$REMOTE_PATH/public/build/` (wiping the remote build dir first).
5. `scp server/public/bug-reporter.js → remote:$REMOTE_PATH/public/bug-reporter.js`.
6. SSH to remote and runs:
   - `git fetch && git reset --hard origin/<BRANCH>`
   - `composer install --no-dev --optimize-autoloader`
   - `php artisan migrate --force`
   - `php artisan storage:link`
   - `php artisan optimize`
   - `php artisan queue:restart`
   - Optional `systemctl reload <FPM_SERVICE>` if configured.

## Requirements on the local machine

- Windows PowerShell (5.1 or PowerShell 7+).
- `ssh` and `scp` on PATH (modern Windows ships OpenSSH client).
- `git`, `npm`.

## Requirements on the remote

- Git, PHP 8.2+, composer.
- An existing checkout of the repo at `REMOTE_PATH` with a `.env`.
- The deploy user has write access to that directory.
- (Optional) Passwordless sudo for `systemctl reload` if you set `FPM_SERVICE` and `USE_SUDO=1`.

## Rolling back

The remote uses `git reset --hard`, so to roll back, SSH in and:

```bash
cd /path/to/app
git reset --hard <previous-good-sha>
composer install --no-dev --optimize-autoloader
php artisan migrate:rollback    # only if the failing deploy added migrations
php artisan optimize
php artisan queue:restart
```
