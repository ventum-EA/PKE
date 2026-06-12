# Railway Deployment Guide — Chess Platform

How to run the platform on Railway with working **email** (real SMTP) and **WebSockets** (Laravel Reverb), in a single app service + MySQL.

The container is self-contained: Apache serves the app on `$PORT`, Reverb runs inside the same container on `127.0.0.1:8080`, and Apache proxies `wss://<your-domain>/app/*` to it. No second service is needed for websockets.

---

## 1. Services

1. **Create a Railway project** → *Deploy from GitHub repo* (the repo root contains the `Dockerfile`; Railway auto-detects it).
2. **Add MySQL**: *+ New → Database → MySQL*.
3. On the app service: *Settings → Networking → Generate Domain*. Note the domain (e.g. `pke-production.up.railway.app`).

## 2. App service variables

Set these under *app service → Variables*. The `${{MySQL.…}}` references resolve automatically to the MySQL service.

```
# ── Core ──────────────────────────────────────────────
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # REQUIRED — see note below
APP_URL=https://pke-production.up.railway.app

# ── Database (reference the MySQL service) ────────────
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

# ── Session / Sanctum (cookie auth breaks without these) ──
SESSION_DOMAIN=pke-production.up.railway.app
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=pke-production.up.railway.app

# ── Mail (see §3 for provider options) ────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=re_xxxxxxxxxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com

# ── WebSockets (Reverb) ───────────────────────────────
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=chess-platform
REVERB_APP_KEY=<random-string-1>        # generate: openssl rand -hex 16
REVERB_APP_SECRET=<random-string-2>
VITE_REVERB_APP_KEY=<random-string-1>   # MUST equal REVERB_APP_KEY (build-time)
```

**`APP_KEY` is required.** The Dockerfile generates a throwaway key at build time, so without an explicit `APP_KEY` every redeploy would invalidate all sessions and encrypted data. Generate once locally (`php artisan key:generate --show`) and paste it.

**`VITE_REVERB_APP_KEY` is a build-time variable** — it's baked into the JS bundle. Railway passes service variables to Docker build args automatically, but it only takes effect on the **next build**, not a restart. If you change it, redeploy.

Leave `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` unset — the entrypoint defaults them to `127.0.0.1:8080` (Laravel → Reverb inside the container), and the browser side auto-detects the public domain and connects via `wss://<domain>/app` through the Apache proxy.

## 3. Picking a mail provider

Any SMTP provider works — set the five `MAIL_*` values accordingly:

| Provider | Free tier | MAIL_HOST | Notes |
|----------|-----------|-----------|-------|
| **Resend** | 3,000/mo | `smtp.resend.com` | Username is literally `resend`, password is the API key. Easiest setup. |
| **Brevo** | 300/day | `smtp-relay.brevo.com` | Username = account email, password = SMTP key. |
| **Mailgun** | trial | `smtp.mailgun.org` | Also has a native Laravel driver if you prefer API over SMTP. |
| **Postmark** | 100/mo | `smtp.postmarkapp.com` | Best deliverability for transactional mail. |

All of them require **verifying your sending domain** (DNS records) before mail leaves the sandbox — do that in the provider dashboard, then set `MAIL_FROM_ADDRESS` to an address on that domain. Until then, keep `MAIL_MAILER=log` (the entrypoint's safe default): registration and password reset work, and the mails are written to the Laravel log instead of being sent.

**Test it after deploy:** use *Forgot password* with a real address, and check *Railway → Deployments → Logs* for mail errors.

## 4. How the WebSocket path works (for debugging)

```
Browser ── wss://<domain>/app/<key> ──> Railway edge ──> Apache (mod_proxy_wstunnel)
                                                              │
Laravel ── http://127.0.0.1:8080/apps (broadcasts) ──────────>│──> Reverb :8080
```

- The entrypoint starts `php artisan reverb:start --host=0.0.0.0 --port=8080` in the background.
- Apache config `reverb-proxy.conf` (baked in the Dockerfile) proxies `/app` (WebSocket) and `/apps` (server-to-server) to it.
- The frontend (`bootstrap.js`) connects to the **page's own origin** in production builds, so no per-environment rebuild is needed.

Verify after deploy: open the site → DevTools → Network → WS. You should see a `wss://<domain>/app/<key>` connection with status 101, and `window.Echo.connector.pusher.connection.state === "connected"` in the console. The multiplayer page should stop reporting "unavailable".

## 5. Deploy checklist

1. Push the latest code (includes the QA fixes + entrypoint hardening).
2. Set all variables from §2 (at minimum: `APP_KEY`, DB refs, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, `SESSION_SECURE_COOKIE=true`).
3. Trigger a deploy (a **rebuild**, not a restart, so `npm run build` re-runs).
4. Watch logs: migrations run, "Reverb started", "Platform ready".
5. Smoke test: login → register a fresh account (must return 201, no 500) → daily puzzle → multiplayer page shows connected → forgot-password sends a real email.

## 6. Optional hardening

- `QUEUE_CONNECTION=database` + a second Railway service running `php artisan queue:work` makes mail sending non-blocking (currently `sync` = sent inline during the request).
- Add a custom domain in Railway and update `APP_URL`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS` to match.
- Point an uptime monitor at `https://<domain>/up` (Laravel health endpoint).
