# bwAuthSystem

Browser-based multi-factor authentication for small PHP admin tools.

Recommended install directory:

README.md

```text
/auth
```

bwAuthSystem uses a normal password plus an approved browser/device private key. The private key stays in the browser. The server stores the matching public key. During login, the server sends a random challenge, the browser signs it, and the server verifies the signature before issuing tokens.

OWASP recognizes cryptographic device-key authentication as a strong MFA factor when combined with another factor.

## What this protects

bwAuthSystem is designed for private admin panels, package tools, and small PHP utilities where you want stronger protection than a shared password.

Examples:

- admin dashboards
- private file tools
- Paginator admin actions
- small personal web tools
- low-user-count private systems

## Core idea

1. Browser creates a public/private key pair.
2. Public key is submitted for approval.
3. Private key stays in the browser.
4. Admin approves the public key.
5. User logs in with password.
6. Server sends challenge.
7. Browser signs challenge.
8. Server verifies signature.
9. Server issues access and refresh tokens.

## Recommended structure

README.md

```text
/auth
    index.php
    signatures.php
    approveUser.php
    fileSystemAccess.env
    /api
        authenticate.php
        refreshToken.php
        validateToken.php
        keyApprover.php
        sendApprovalEmail.php
    /css
        baseline.css
        modern.css
        darkmode.css
    /js
        authSystem.js
        passwordUtils.js
        actionLogger.js
    /images
        authSystemIcon.png
```

## Configure environment file

Edit:

README.md

```text
/auth/fileSystemAccess.env
```

Starter file:

README.md

```env
SECRET_KEY=CHANGE_THIS_TO_A_LONG_RANDOM_SECRET
ACCESS_PASSWORD=CHANGE_THIS_ADMIN_PASSWORD
EDITOR_PASSWORD=
VIEWER_PASSWORD=

DEVICE_KEYS={}
REFRESH_TOKENS={}
CHALLENGES={}
```

## Generate a 64-character secret

Bash:

README.md

```bash
openssl rand -hex 32
```

PowerShell:

README.md

```powershell
-join ((48..57)+(65..90)+(97..122) | Get-Random -Count 64 | ForEach-Object {[char]$_})
```

Use the generated value for:

README.md

```env
SECRET_KEY=PASTE_GENERATED_SECRET_HERE
```

## Default token policy

Recommended GitHub release defaults:

- Access token: 15 minutes
- Refresh token: 7 days
- Refresh tokens rotate
- Old refresh tokens are rejected after replacement
- Device revocation blocks future refreshes

To change refresh token lifetime, edit:

README.md

```text
/api/refreshToken.php
```

Change:

README.md

```php
$refreshTokenDays = 7;
```

Also keep the matching value in:

README.md

```text
/api/authenticate.php
```

## Breach response

If one device is compromised:

1. Remove the device from `DEVICE_KEYS`.
2. Remove its refresh token from `REFRESH_TOKENS`.
3. Have the user generate a new key.
4. Reapprove the new public key.

For immediate global lockout, rotate:

README.md

```env
SECRET_KEY=NEW_RANDOM_SECRET_HERE
```

That invalidates all existing access and refresh tokens.

## First admin setup

The first approved public key should be added manually to `DEVICE_KEYS`.

After the first trusted device is approved, future devices can use the approval flow.

## Main pages

- `index.php` — admin login, status, help
- `signatures.php` — generate/install browser private key
- `approveUser.php` — approve submitted public keys

## Backend endpoints

- `api/authenticate.php` — password + signature login
- `api/refreshToken.php` — token refresh and rotation
- `api/validateToken.php` — validate access token
- `api/keyApprover.php` — add approved public key
- `api/sendApprovalEmail.php` — send key approval request

## Notes

Keep `/auth` behind HTTPS.

Do not share private keys.

Do not commit real passwords or production secrets to public repositories.
