<?php
define('AUTH_EXAMPLE_VERSION', '1.00.0004');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

function bwFindAuthSystem()
{
    $candidates = [
        ['label' => 'Auth files in this directory', 'fs' => __DIR__, 'web' => ''],
        ['label' => 'Auth files in /auth', 'fs' => __DIR__ . '/auth', 'web' => 'auth/'],
        ['label' => 'Auth files one level up', 'fs' => dirname(__DIR__), 'web' => '../']
    ];

    foreach ($candidates as $candidate) {
        $score = 0;

        foreach (['authenticate.php', 'validate.php', 'index.php', 'signatures.php'] as $file) {
            if (file_exists($candidate['fs'] . '/' . $file)) {
                $score++;
            }
        }

        if (
            file_exists($candidate['fs'] . '/passwordUtils.js') ||
            file_exists($candidate['fs'] . '/js/passwordUtils.js')
        ) {
            $score++;
        }

        if ($score >= 3) {
            $candidate['score'] = $score;
            return $candidate;
        }
    }

    return ['label' => 'Auth system not found', 'fs' => '', 'web' => '', 'score' => 0];
}

$authSystem = bwFindAuthSystem();
$authFound = $authSystem['score'] >= 3;
$authWebPath = $authSystem['web'];

$passwordUtilsPath = '';
if ($authFound) {
    if (file_exists($authSystem['fs'] . '/passwordUtils.js')) {
        $passwordUtilsPath = $authWebPath . 'passwordUtils.js';
    } elseif (file_exists($authSystem['fs'] . '/js/passwordUtils.js')) {
        $passwordUtilsPath = $authWebPath . 'js/passwordUtils.js';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auth Protected Page Example</title>

    <?php if ($passwordUtilsPath): ?>
        <script src="<?php echo htmlspecialchars($passwordUtilsPath); ?>?v=<?php echo AUTH_EXAMPLE_VERSION; ?>"
            defer></script>
    <?php endif; ?>

    <style>
        :root {
            --page-bg: #05070b;
            --panel-bg: rgba(8, 13, 21, 0.92);
            --panel-bg-soft: rgba(13, 20, 31, 0.82);
            --text: #e8f1ff;
            --muted: #9cadc3;
            --border: rgba(67, 151, 255, 0.34);
            --border-soft: rgba(255, 255, 255, 0.13);
            --blue: #2495ff;
            --danger: #b41f2a;
            --ok: #32c46c;
            --warn: #d39b28;
            --shadow: 0 18px 50px rgba(0, 0, 0, 0.45);
        }

        * {
            box-sizing: border-box;
        }

        html {
            min-height: 100%;
            background: var(--page-bg);
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at 50% 100%, rgba(28, 96, 160, 0.22), transparent 42%),
                radial-gradient(circle at 15% 10%, rgba(36, 149, 255, 0.16), transparent 30%),
                linear-gradient(180deg, #05070b 0%, #080d15 52%, #040609 100%);
        }

        a {
            color: var(--blue);
        }

        .authHeader {
            width: 100%;
            margin: 0;
            padding: 22px clamp(16px, 4vw, 52px);
            border-bottom: 1px solid var(--border);
            background:
                linear-gradient(90deg, rgba(4, 10, 18, 0.98), rgba(8, 22, 38, 0.96)),
                radial-gradient(circle at 80% 0%, rgba(36, 149, 255, 0.22), transparent 34%);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.32);
        }

        .headerLine {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            max-width: 880px;
            margin: 0 auto;
        }

        .authHeader h1 {
            margin: 0;
            font-size: clamp(1.45rem, 4vw, 2.15rem);
            line-height: 1.05;
            letter-spacing: -0.035em;
            color: var(--text);
        }

        .authHeader h1 span {
            color: var(--blue);
        }

        .versionText {
            color: var(--blue);
            font-size: 0.95rem;
            white-space: nowrap;
        }

        .authMain {
            width: min(880px, calc(100% - 28px));
            margin: 0 auto;
            padding: clamp(18px, 4vw, 38px) 0 36px;
        }

        .tagline {
            margin: 0 0 18px;
            padding-left: 13px;
            border-left: 3px solid var(--blue);
            color: var(--muted);
            font-size: 1.02rem;
        }

        .panel,
        details {
            border: 1px solid var(--border-soft);
            background: var(--panel-bg);
            box-shadow: var(--shadow);
            margin-bottom: 14px;
        }

        .panel {
            padding: clamp(16px, 4vw, 28px);
            border-color: var(--border);
        }

        .panel h2 {
            margin: 0 0 8px;
            color: var(--blue);
            font-size: 1.35rem;
        }

        .helpText {
            margin: 0 0 18px;
            color: var(--muted);
            line-height: 1.45;
        }

        .steps {
            display: grid;
            gap: 10px;
            margin-bottom: 14px;
        }

        .step {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid var(--border-soft);
            background: rgba(0, 0, 0, 0.18);
            padding: 10px 11px;
        }

        .stepName {
            color: var(--text);
            font-weight: 700;
        }

        .stepSub {
            display: block;
            margin-top: 3px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .pill {
            white-space: nowrap;
            font-weight: 800;
            color: var(--warn);
        }

        .pill.ok {
            color: var(--ok);
        }

        .pill.bad {
            color: #ff6470;
        }

        label {
            display: block;
            margin: 14px 0 7px;
            color: var(--text);
            font-weight: 700;
        }

        input {
            width: 100%;
            min-height: 46px;
            padding: 10px 12px;
            border: 1px solid rgba(36, 149, 255, 0.45);
            background: rgba(0, 0, 0, 0.28);
            color: var(--text);
            font-size: 1rem;
            outline: none;
        }

        input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 2px rgba(36, 149, 255, 0.18);
        }

        .buttonStack {
            display: grid;
            gap: 10px;
            margin-top: 18px;
        }

        .buttonRow {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        button,
        .buttonLink {
            min-height: 44px;
            border: 1px solid transparent;
            padding: 10px 14px;
            background: var(--blue);
            color: #ffffff;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.98rem;
            font-weight: 700;
        }

        .primaryButton {
            width: 100%;
            background: linear-gradient(180deg, #2aa3ff, #126fd1);
        }

        .secondaryButton {
            border-color: rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
        }

        .dangerButton {
            border-color: rgba(255, 64, 84, 0.65);
            background: rgba(180, 31, 42, 0.16);
            color: #ff5d69;
        }

        details {
            background: var(--panel-bg-soft);
        }

        summary {
            cursor: pointer;
            padding: 14px 16px;
            color: var(--blue);
            font-size: 1.05rem;
            font-weight: 700;
            list-style: none;
        }

        summary::-webkit-details-marker {
            display: none;
        }

        summary::after {
            content: "+";
            float: right;
            color: var(--muted);
        }

        details[open] summary::after {
            content: "–";
        }

        .detailBody {
            padding: 0 16px 16px;
            color: var(--muted);
            line-height: 1.45;
        }

        pre {
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 210px;
            overflow: auto;
            padding: 10px;
            border: 1px solid var(--border-soft);
            background: rgba(0, 0, 0, 0.28);
            color: var(--text);
        }

        footer {
            width: min(880px, calc(100% - 28px));
            margin: 0 auto;
            padding: 0 0 28px;
            color: var(--muted);
            text-align: center;
            font-size: 0.9rem;
        }

        @media (max-width: 560px) {
            .headerLine {
                display: block;
            }

            .versionText {
                display: block;
                margin-top: 8px;
            }

            .buttonRow {
                display: grid;
            }

            button,
            .buttonLink {
                width: 100%;
            }

            .step {
                display: block;
            }

            .pill {
                display: block;
                margin-top: 5px;
            }
        }
    </style>
</head>

<body>
    <header class="authHeader">
        <div class="headerLine">
            <h1><span>bwAuthSystem</span> Protected Page Example</h1>
            <div class="versionText">v
                <?php echo htmlspecialchars(AUTH_EXAMPLE_VERSION); ?>
            </div>
        </div>
    </header>

    <main class="authMain">
        <p class="tagline">Example admin page using device-signature MFA.</p>

        <section class="panel">
            <h2 id="mainStatusTitle">Checking Auth Status</h2>
            <p id="mainStatusText" class="helpText">
                Checking for auth files, browser device signature, and access token.
            </p>

            <div class="steps">
                <div class="step">
                    <div>
                        <span class="stepName">Auth files</span>
                        <span class="stepSub">
                            <?php echo htmlspecialchars($authSystem['label']); ?>
                        </span>
                    </div>
                    <span class="pill <?php echo $authFound ? 'ok' : 'bad'; ?>">
                        <?php echo $authFound ? 'Found' : 'Missing'; ?>
                    </span>
                </div>

                <div class="step">
                    <div>
                        <span class="stepName">Device signature</span>
                        <span class="stepSub">Private key installed in this browser.</span>
                    </div>
                    <span id="deviceSignaturePill" class="pill">Checking</span>
                </div>

                <div class="step">
                    <div>
                        <span class="stepName">Access token</span>
                        <span class="stepSub">Token created after login.</span>
                    </div>
                    <span id="tokenPill" class="pill">Checking</span>
                </div>
            </div>

            <div class="buttonRow">
                <a class="buttonLink secondaryButton" href="<?php echo htmlspecialchars($authWebPath); ?>index.php">Open
                    Auth Login</a>
                <a class="buttonLink secondaryButton"
                    href="<?php echo htmlspecialchars($authWebPath); ?>signatures.php">Open Signatures</a>
                <button type="button" class="secondaryButton" onclick="refreshAuthStatus()">Refresh</button>
            </div>
        </section>

        <section id="loginPanel" class="panel">
            <h2>Login</h2>
            <p class="helpText">Use this form after the device signature is installed.</p>

            <form id="authLoginForm" onsubmit="event.preventDefault(); exampleSubmitPassword();">
                <label for="accessPassword">Access Password</label>
                <input type="password" id="accessPassword" autocomplete="current-password"
                    placeholder="Enter access password">

                <div class="buttonStack">
                    <button id="passwordSubmit" class="primaryButton" type="submit">Login</button>
                    <button id="passwordShow" class="secondaryButton" type="button"
                        onclick="togglePasswordVisibility()">Show Password</button>
                </div>
            </form>
        </section>

        <section id="protectedPanel" class="panel" style="display:none;">
            <h2>Logged In With Token</h2>
            <p class="helpText">
                This section is visible because the browser has a valid access token.
            </p>

            <pre id="tokenPreview">Token preview unavailable.</pre>

            <div class="buttonRow">
                <button type="button" class="secondaryButton" onclick="copyAccessTokenExample()">Copy Token</button>
                <button type="button" class="secondaryButton" onclick="clearAccessTokenExample()">Clear Token</button>
                <button type="button" class="dangerButton" onclick="deleteDeviceSignatureExample()">Delete Device
                    Signature</button>
            </div>
        </section>

        <details>
            <summary>Example Code Pattern</summary>
            <div class="detailBody">
                <pre>const token = await getAccessToken();

fetch("protectedEndpoint.php", {
    method: "GET",
    headers: {
        "Authorization": `Bearer ${token}`
    }
});</pre>
            </div>
        </details>
    </main>

    <footer>
        bwAuthSystem — secure device-signature MFA for small PHP admin tools.
    </footer>

    <script>
        const AUTH_BASE = "<?php echo addslashes($authWebPath); ?>";
        const AUTH_FOUND = <?php echo $authFound ? 'true' : 'false'; ?>;
        const KEY_DB_NAME = "BWCSAuthDB";
        const KEY_STORE_NAME = "privateKeys";

        function setPill(id, text, state) {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = text;
            el.className = "pill" + (state ? " " + state : "");
        }

        function setMainStatus(title, text) {
            document.getElementById("mainStatusTitle").textContent = title;
            document.getElementById("mainStatusText").textContent = text;
        }

        function openKeyDbExample() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(KEY_DB_NAME, 1);

                request.onupgradeneeded = function (event) {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains(KEY_STORE_NAME)) {
                        db.createObjectStore(KEY_STORE_NAME);
                    }
                };

                request.onsuccess = function () {
                    resolve(request.result);
                };

                request.onerror = function () {
                    reject(request.error);
                };
            });
        }

        async function getDeviceSignatureExample() {
            const db = await openKeyDbExample();

            return new Promise((resolve, reject) => {
                const transaction = db.transaction([KEY_STORE_NAME], "readonly");
                const store = transaction.objectStore(KEY_STORE_NAME);
                const privateKeyRequest = store.get("installedPrivateKey");

                transaction.oncomplete = function () {
                    resolve(!!privateKeyRequest.result);
                };

                transaction.onerror = function () {
                    reject(transaction.error);
                };
            });
        }

        async function getTokenExample() {
            if (typeof getAccessToken === "function") {
                return await getAccessToken();
            }

            return localStorage.getItem("accessToken") ||
                sessionStorage.getItem("accessToken") ||
                localStorage.getItem("authToken") ||
                sessionStorage.getItem("authToken") ||
                "";
        }

        async function validateTokenExample(token) {
            if (!token || !AUTH_FOUND) return false;

            try {
                const response = await fetch(AUTH_BASE + "validate.php", {
                    method: "GET",
                    headers: {
                        "Authorization": `Bearer ${token}`
                    }
                });

                const rawText = await response.text();

                try {
                    const data = JSON.parse(rawText);
                    return data.status === "success" ||
                        data.valid === true ||
                        data.authenticated === true ||
                        data.loggedIn === true;
                } catch {
                    return response.ok;
                }
            } catch {
                return false;
            }
        }

        async function refreshAuthStatus() {
            const loginPanel = document.getElementById("loginPanel");
            const protectedPanel = document.getElementById("protectedPanel");
            const tokenPreview = document.getElementById("tokenPreview");

            let hasSignature = false;

            try {
                hasSignature = await getDeviceSignatureExample();
            } catch {
                hasSignature = false;
            }

            const token = await getTokenExample();
            const tokenValid = await validateTokenExample(token);

            setPill("deviceSignaturePill", hasSignature ? "Installed" : "Missing", hasSignature ? "ok" : "bad");
            setPill("tokenPill", tokenValid ? "Logged In" : "No Valid Token", tokenValid ? "ok" : "bad");

            if (!AUTH_FOUND) {
                setMainStatus("Auth System Missing", "This page could not find the auth system files.");
                loginPanel.style.display = "none";
                protectedPanel.style.display = "none";
                return;
            }

            if (!hasSignature) {
                setMainStatus("Device Signature Missing", "Open the signatures page and install a device signature first.");
                loginPanel.style.display = "none";
                protectedPanel.style.display = "none";
                return;
            }

            if (!tokenValid) {
                setMainStatus("Device Signature Installed", "Login with the site password to create an access token.");
                loginPanel.style.display = "block";
                protectedPanel.style.display = "none";
                return;
            }

            setMainStatus("Logged In With Token", "Admin verified. Protected content is visible.");
            loginPanel.style.display = "none";
            protectedPanel.style.display = "block";

            if (tokenPreview) {
                tokenPreview.textContent = token
                    ? token.substring(0, 32) + "..." + token.substring(Math.max(token.length - 20, 0))
                    : "No token found.";
            }
        }

        async function exampleSubmitPassword() {
            if (typeof submitPassword === "function") {
                await submitPassword();
                setTimeout(refreshAuthStatus, 400);
                return;
            }

            alert("submitPassword() was not found. Check that passwordUtils.js loaded.");
        }

        function togglePasswordVisibility() {
            const input = document.getElementById("accessPassword");
            const button = document.getElementById("passwordShow");

            if (input.type === "password") {
                input.type = "text";
                button.textContent = "Hide Password";
            } else {
                input.type = "password";
                button.textContent = "Show Password";
            }
        }

        async function clearAccessTokenExample() {
            if (typeof clearPasswordCache === "function") {
                clearPasswordCache();
            }

            localStorage.removeItem("accessToken");
            sessionStorage.removeItem("accessToken");
            localStorage.removeItem("authToken");
            sessionStorage.removeItem("authToken");

            await refreshAuthStatus();
        }

        async function copyAccessTokenExample() {
            const token = await getTokenExample();

            if (!token) {
                alert("No token found.");
                return;
            }

            await navigator.clipboard.writeText(token);
            alert("Token copied.");
        }

        async function deleteDeviceSignatureExample() {
            if (!confirm("Delete the device signature from this browser?")) {
                return;
            }

            const db = await openKeyDbExample();

            await new Promise((resolve, reject) => {
                const transaction = db.transaction([KEY_STORE_NAME], "readwrite");
                const store = transaction.objectStore(KEY_STORE_NAME);

                store.delete("installedPrivateKey");
                store.delete("installedPublicKey");
                store.delete("privateKeyName");

                transaction.oncomplete = function () {
                    resolve(true);
                };

                transaction.onerror = function () {
                    reject(transaction.error);
                };
            });

            localStorage.removeItem("privateKeyName");
            await clearAccessTokenExample();
        }

        document.addEventListener("DOMContentLoaded", refreshAuthStatus);
    </script>
</body>

</html>