<?php
// authProtectedPageExample.php
define('AUTH_EXAMPLE_VERSION', '1.00.0034');

function bw_find_auth_system()
{
    $candidates = [
        ['label' => 'Same directory', 'fs' => __DIR__, 'web' => ''],
        ['label' => '/auth directory', 'fs' => __DIR__ . '/auth', 'web' => 'auth/'],
        ['label' => 'One level up', 'fs' => dirname(__DIR__), 'web' => '../']
    ];

    foreach ($candidates as $candidate) {
        $score = 0;
        $requiredFiles = [
            'index.php',
            'signatures.php',
            'api/authenticate.php',
            'api/validateToken.php'
        ];

        foreach ($requiredFiles as $file) {
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

    return ['label' => 'Not found', 'fs' => '', 'web' => '', 'score' => 0];
}

$authSystem = bw_find_auth_system();
$authFound = $authSystem['score'] >= 3;
$authWebPath = $authSystem['web'];

$passwordUtilsPath = '';
$faviconPath = '';

if ($authFound) {
    if (file_exists($authSystem['fs'] . '/passwordUtils.js')) {
        $passwordUtilsPath = $authWebPath . 'passwordUtils.js';
    }

    if (file_exists($authSystem['fs'] . '/js/passwordUtils.js')) {
        $passwordUtilsPath = $authWebPath . 'js/passwordUtils.js';
    }

    if (file_exists($authSystem['fs'] . '/images/authSystemIcon.png')) {
        $faviconPath = $authWebPath . 'images/authSystemIcon.png';
    }
}
$sigInstalledImage = '';
$sigNotInstalledImage = '';

function bw_find_image_file($fileName)
{
    $candidates = [
        [
            'fs' => __DIR__ . '/images/' . $fileName,
            'web' => 'images/' . $fileName
        ],
        [
            'fs' => __DIR__ . '/auth/images/' . $fileName,
            'web' => 'auth/images/' . $fileName
        ],
        [
            'fs' => dirname(__DIR__) . '/auth/images/' . $fileName,
            'web' => '../auth/images/' . $fileName
        ]
    ];

    foreach ($candidates as $candidate) {
        if (file_exists($candidate['fs'])) {
            return $candidate['web'];
        }
    }

    return '';
}

$sigInstalledImage = bw_find_image_file('sigInstalled.png');
$sigNotInstalledImage = bw_find_image_file('sigNotInstalled.png');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>bwAuthSystem Example</title>
    <?php if ($faviconPath): ?>
        <link rel="icon" type="image/png"
            href="<?php echo htmlspecialchars($faviconPath); ?>?v=<?php echo AUTH_EXAMPLE_VERSION; ?>">
    <?php endif; ?>
    <?php if ($passwordUtilsPath): ?>
        <script src="<?php echo htmlspecialchars($passwordUtilsPath); ?>?v=<?php echo AUTH_EXAMPLE_VERSION; ?>"
            defer></script>
    <?php endif; ?>
    <style>
        :root {
            --page-bg: #05070b;
            --panel-bg: rgba(8, 13, 21, .92);
            --panel-bg-soft: rgba(13, 20, 31, .82);
            --text: #e8f1ff;
            --muted: #9cadc3;
            --border: rgba(67, 151, 255, .34);
            --border-soft: rgba(255, 255, 255, .13);
            --blue: #2495ff;
            --danger: #b41f2a;
            --ok: #32c46c;
            --warn: #d39b28;
            --shadow: 0 18px 50px rgba(0, 0, 0, .45)
        }

        * {
            box-sizing: border-box
        }

        html {
            min-height: 100%;
            background: var(--page-bg)
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            background: radial-gradient(circle at 50% 100%, rgba(28, 96, 160, .22), transparent 42%), radial-gradient(circle at 15% 10%, rgba(36, 149, 255, .16), transparent 30%), linear-gradient(180deg, #05070b 0%, #080d15 52%, #040609 100%)
        }

        a {
            color: var(--blue)
        }

        .authHeader {
            width: 100%;
            margin: 0;
            padding: 16px clamp(16px, 4vw, 52px);
            border-bottom: 1px solid var(--border);
            background: linear-gradient(90deg, rgba(4, 10, 18, .98), rgba(8, 22, 38, .96)), radial-gradient(circle at 80% 0%, rgba(36, 149, 255, .22), transparent 34%);
            box-shadow: 0 10px 28px rgba(0, 0, 0, .32)
        }

        .headerLine {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 16px;
            max-width: 880px;
            margin: 0 auto
        }

        .authHeader h1 {
            margin: 0;
            font-size: clamp(1.2rem, 3vw, 1.75rem);
            line-height: 1.05;
            letter-spacing: -.04em;
            color: var(--text)
        }

        .authHeader h1 span {
            color: var(--blue)
        }

        .versionText {
            color: var(--blue);
            font-size: .95rem;
            white-space: nowrap
        }

        .authMain {
            width: min(880px, calc(100% - 28px));
            margin: 0 auto;
            padding: 8px 0 36px;
        }

        .tagline {
            margin: 0 0 8px;
            padding-left: 10px;
            border-left: 3px solid var(--blue);
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.35
        }

        .loginPanel,
        details {
            border: 1px solid var(--border-soft);
            background: var(--panel-bg);
            box-shadow: var(--shadow)
        }

        .loginPanel {
            padding: clamp(12px, 2.5vw, 18px);
            border-color: var(--border);
            margin-bottom: 10px
        }

        .loginPanel h2 {
            margin: 0 0 6px;
            color: var(--blue);
            font-size: 1.22rem
        }

        .helpText {
            margin: 0 0 12px;
            color: var(--muted);
            line-height: 1.4;
            font-size: 0.9rem
        }

        .introText {
            margin-bottom: 10px;
        }

        .introText p {
            margin: 0 0 8px;
            color: var(--muted);
            line-height: 1.38;
            font-size: 0.9rem;
        }

        .introText p:last-child {
            margin-bottom: 0;
        }


        .statusList {
            list-style: none;
            margin: 0;
            padding: 0;
            border: 1px solid var(--border-soft)
        }

        .statusList li {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 8px 10px;
            border-bottom: 1px solid var(--border-soft);
            background: rgba(0, 0, 0, .16);
            font-size: 0.9rem
        }

        .statusList li:last-child {
            border-bottom: 0
        }

        .ok {
            color: var(--ok);
            font-weight: 700
        }

        .warn {
            color: var(--warn);
            font-weight: 700
        }

        .bad {
            color: #ff6470;
            font-weight: 700
        }

        label {
            display: block;
            margin: 10px 0 6px;
            color: var(--text);
            font-weight: 700;
            font-size: 0.9rem
        }

        input {
            width: 100%;
            min-height: 46px;
            padding: 10px 12px;
            border: 1px solid rgba(36, 149, 255, .45);
            background: rgba(0, 0, 0, .28);
            color: var(--text);
            font-size: 1rem;
            outline: none
        }

        input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 2px rgba(36, 149, 255, .18)
        }

        .buttonStack {
            display: grid;
            gap: 8px;
            margin-top: 12px
        }

        .buttonRow {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px
        }

        button,
        .buttonLink {
            min-height: 44px;
            border: 1px solid transparent;
            padding: 10px 14px;
            background: var(--blue);
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .98rem;
            font-weight: 700
        }

        .primaryButton {
            width: 100%;
            background: linear-gradient(180deg, #2aa3ff, #126fd1)
        }

        .secondaryButton {
            border-color: rgba(255, 255, 255, .18);
            background: rgba(255, 255, 255, .1);
            color: var(--text)
        }

        .dangerButton {
            border-color: rgba(255, 64, 84, .65);
            background: rgba(180, 31, 42, .16);
            color: #ff5d69
        }

        details {
            background: var(--panel-bg-soft);
            margin-bottom: 10px
        }

        summary {
            cursor: pointer;
            padding: 14px 16px;
            color: var(--blue);
            font-size: 1.05rem;
            font-weight: 700;
            list-style: none
        }

        summary::-webkit-details-marker {
            display: none
        }

        summary::after {
            content: '+';
            float: right;
            color: var(--muted)
        }

        details[open] summary::after {
            content: '–'
        }

        .detailBody {
            padding: 0 16px 16px;
            color: var(--muted);
            line-height: 1.45
        }

        pre {
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 210px;
            overflow: auto;
            padding: 10px;
            border: 1px solid var(--border-soft);
            background: rgba(0, 0, 0, .28);
            color: var(--text)
        }

        footer {
            width: min(880px, calc(100% - 28px));
            margin: 0 auto;
            padding: 0 0 28px;
            color: var(--muted);
            text-align: center;
            font-size: .9rem
        }

        .signatureImageBox {
            position: relative;
            width: 100%;
            height: 300px;
            margin: 0 0 10px;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .signatureImageBox img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            display: none;
        }

        #sigInstalledImage {
            filter:
                drop-shadow(0 0 4px rgba(255, 255, 255, 0.85)) drop-shadow(0 0 14px rgba(36, 149, 255, 0.9)) drop-shadow(0 0 30px rgba(0, 255, 220, 0.45));
        }

        #sigNotInstalledImage {
            filter:
                drop-shadow(0 0 4px rgba(255, 255, 255, 0.65)) drop-shadow(0 0 18px rgba(165, 90, 255, 0.65)) drop-shadow(0 0 32px rgba(120, 40, 255, 0.45));
        }


        .signatureStatusStage {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 230px;
            align-items: stretch;
            gap: 14px;
            margin: 0 0 10px;
            padding: 0;
        }

        .signatureStatusStage .signatureImageBox {
            margin: 0;
        }

        .signatureStatusLabel {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 5;
            padding: 5px 8px;
            color: var(--blue);

            font-size: 0.84rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            line-height: 1;
            pointer-events: none;
        }


        .sessionMeter {
            height: 300px;
            padding: 10px;
            border: 1px solid var(--border-soft);
            background: rgba(0, 0, 0, .16);
            box-shadow: 0 0 24px rgba(0, 0, 0, .22);
            display: flex;
            flex-direction: column;
        }

        .sessionMeterTitle {
            margin: 0 0 8px;
            color: var(--blue);
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .meterRows {
            display: grid;
            gap: 8px;
            flex: 1;
        }

        .meterRow {
            position: relative;
            min-height: 52px;
            padding: 9px 10px 9px 30px;
            border: 1px solid rgba(255, 255, 255, .1);
            color: rgba(232, 241, 255, .72);
            font-size: 0.84rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .meterRow::before {
            content: "";
            position: absolute;
            left: 8px;
            top: 50%;
            width: 10px;
            height: 10px;
            transform: translateY(-50%);
            border-radius: 50%;
            background: rgba(255, 255, 255, .28);
        }

        .meterRow::after {
            content: "";
            position: absolute;
            inset: 0;
            opacity: .22;
            pointer-events: none;
        }

        .meterValid::after {
            background: linear-gradient(180deg, rgba(50, 196, 108, .75), rgba(8, 80, 38, .75));
        }

        .meterRefresh::after {
            background: linear-gradient(180deg, rgba(36, 149, 255, .75), rgba(6, 62, 125, .75));
        }

        .meterInvalid::after {
            background: linear-gradient(180deg, rgba(211, 155, 40, .8), rgba(94, 58, 8, .75));
        }

        .meterNone::after {
            background: linear-gradient(180deg, rgba(255, 64, 84, .78), rgba(92, 11, 21, .78));
        }

        .meterRow span {
            position: relative;
            z-index: 2;
        }

        .meterRow.active {
            color: #ffffff;
            border: 3px solid rgba(255, 255, 255, .9);
            box-shadow:
                0 0 0 1px rgba(36, 149, 255, .55),
                0 0 22px rgba(36, 149, 255, .55);
        }

        .meterRow.active::before {
            background: #ffffff;
            width: 13px;
            height: 13px;
            box-shadow:
                0 0 0 3px rgba(255, 255, 255, .28),
                0 0 14px rgba(255, 255, 255, 1),
                0 0 24px rgba(36, 149, 255, .9);
        }

        .meterRow.active::after {
            opacity: .82;
        }

        .meterRow.refreshing {
            animation: meterPulse 900ms ease-in-out infinite alternate;
        }

        @keyframes meterPulse {
            from {
                box-shadow: 0 0 10px rgba(36, 149, 255, .3);
            }

            to {
                box-shadow: 0 0 28px rgba(36, 149, 255, .9);
            }
        }

        @media(max-width:700px) {
            .signatureStatusStage {
                grid-template-columns: 1fr;
            }

            .sessionMeter {
                height: auto;
                min-height: auto;
            }
        }

        @media(max-width:560px) {
            .headerLine {
                display: block
            }

            .versionText {
                display: block;
                margin-top: 8px
            }

            .buttonRow {
                display: grid
            }

            button,
            .buttonLink {
                width: 100%
            }

            .statusList li {
                display: block
            }

            .statusList li span:last-child {
                display: block;
                margin-top: 4px
            }
        }
    </style>
</head>

<body>
    <header class="authHeader">
        <div class="headerLine">
            <h1><span>Auth System</span> Example Page</h1>
            <div class="versionText">v<?php echo htmlspecialchars(AUTH_EXAMPLE_VERSION); ?></div>
        </div>
    </header>
    <main class="authMain">
        <p class="tagline">This page is an example of device-signature MFA (multi-factor authentication).</p>
        <?php if ($sigInstalledImage || $sigNotInstalledImage): ?>
            <div class="signatureStatusStage">
                <div class="signatureImageBox">
                    <div class="signatureStatusLabel">Signature Status:</div>
                    <?php if ($sigInstalledImage): ?>
                        <img id="sigInstalledImage"
                            src="<?php echo htmlspecialchars($sigInstalledImage); ?>?v=<?php echo AUTH_EXAMPLE_VERSION; ?>"
                            alt="Device signature installed">
                    <?php endif; ?>

                    <?php if ($sigNotInstalledImage): ?>
                        <img id="sigNotInstalledImage"
                            src="<?php echo htmlspecialchars($sigNotInstalledImage); ?>?v=<?php echo AUTH_EXAMPLE_VERSION; ?>"
                            alt="Device signature not installed">
                    <?php endif; ?>
                </div>

                <aside class="sessionMeter" aria-label="Session status meter">
                    <div class="sessionMeterTitle">Session Status:</div>
                    <div class="meterRows">
                        <div id="meterValid" class="meterRow meterValid"><span>Valid access token</span></div>
                        <div id="meterRefresh" class="meterRow meterRefresh"><span>Refreshing token</span></div>
                        <div id="meterInvalid" class="meterRow meterInvalid"><span>Token invalid or expired</span></div>
                        <div id="meterNone" class="meterRow meterNone"><span>No token</span></div>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
        <section class="loginPanel introText">
            <h2>This Page Features:</h2>
            <p>
                This is an example of what a PHP page using the auth system can look like.
            </p>

            <p>
                On this page, you can verify that the <u>auth system was found</u>, that your
                <u>device signature is installed in this browser</u>, and that you are
                <u>logged in with a valid access token</u>.
            </p>

            <p>
                The example file also contains code showing how a page can use the auth system to
                <u>show public content</u>, <u>hide protected admin content</u>, and
                <u>send the access token to protected server endpoints</u>.
            </p>
        </section>

        <section class="loginPanel">
            <h2 id="mainStatusTitle">Checking Auth Status</h2>
            <p id="mainStatusText" class="helpText">Checking auth files, device signature, and access token.</p>
            <ul class="statusList">
                <li><span>Auth files</span><span
                        class="<?php echo $authFound ? 'ok' : 'bad'; ?>"><?php echo $authFound ? 'Found' : 'Missing'; ?></span>
                </li>
                <li><span>Detected location</span><span><?php echo htmlspecialchars($authSystem['label']); ?></span>
                </li>
                <li><span>Device signature</span><span id="deviceSignatureStatus" class="warn">Checking</span></li>
                <li><span>Access token</span><span id="tokenStatus" class="warn">Checking</span></li>
            </ul>
            <div class="buttonRow"><a class="buttonLink secondaryButton"
                    href="<?php echo htmlspecialchars($authWebPath); ?>index.php">Open Auth Login</a><a
                    class="buttonLink secondaryButton"
                    href="<?php echo htmlspecialchars($authWebPath); ?>signatures.php">Open Signatures</a><button
                    type="button" class="secondaryButton" onclick="refreshAuthStatus()">Refresh</button></div>
        </section>
        <section id="loginPanel" class="loginPanel">
            <h2>Login</h2>
            <p class="helpText">Use this form after the device signature is installed.</p>
            <form id="authLoginForm" onsubmit="event.preventDefault(); exampleSubmitPassword();"><label
                    for="accessPassword">Access Password</label><input type="password" id="accessPassword"
                    autocomplete="current-password" placeholder="Enter access password">
                <div class="buttonStack"><button id="passwordSubmit" class="primaryButton"
                        type="submit">Login</button><button id="passwordShow" class="secondaryButton" type="button"
                        onclick="togglePasswordVisibility()">Show Password</button></div>
            </form>
        </section>
        <section id="protectedPanel" class="loginPanel" style="display:none;">
            <h2>Logged In With Token</h2>
            <p class="helpText">This section is visible because this browser has a valid access token.</p>
            <pre id="tokenPreview">Token preview unavailable.</pre>
            <div class="buttonRow"><button type="button" class="secondaryButton" onclick="copyAccessTokenExample()">Copy
                    Token</button><button type="button" class="secondaryButton"
                    onclick="clearAccessTokenExample()">Clear Token</button><button type="button" class="dangerButton"
                    onclick="deleteDeviceSignatureExample()">Delete Device Signature</button></div>
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
    <footer>bwAuthSystem — secure device-signature MFA for small PHP admin tools.</footer>
    <script>
        const AUTH_BASE = "<?php echo addslashes($authWebPath); ?>"; const AUTH_FOUND = <?php echo $authFound ? 'true' : 'false'; ?>; const KEY_DB_NAME = "BWCSAuthDB"; const KEY_STORE_NAME = "privateKeys";
        function setLine(id, text, state) { const el = document.getElementById(id); if (!el) return; el.textContent = text; el.className = state } function setMainStatus(title, text) { document.getElementById("mainStatusTitle").textContent = title; document.getElementById("mainStatusText").textContent = text }
        function setSessionMeterState(state) {
            const meterRows = {
                valid: document.getElementById("meterValid"),
                refresh: document.getElementById("meterRefresh"),
                invalid: document.getElementById("meterInvalid"),
                none: document.getElementById("meterNone")
            };

            Object.keys(meterRows).forEach(key => {
                if (!meterRows[key]) return;
                meterRows[key].classList.remove("active", "refreshing");
            });

            if (!meterRows[state]) return;

            meterRows[state].classList.add("active");

            if (state === "refresh") {
                meterRows[state].classList.add("refreshing");
            }
        }
        function openKeyDbExample() { return new Promise((resolve, reject) => { const request = indexedDB.open(KEY_DB_NAME, 1); request.onupgradeneeded = e => { const db = e.target.result; if (!db.objectStoreNames.contains(KEY_STORE_NAME)) db.createObjectStore(KEY_STORE_NAME) }; request.onsuccess = () => resolve(request.result); request.onerror = () => reject(request.error) }) }
        async function getDeviceSignatureExample() { const db = await openKeyDbExample(); return new Promise((resolve, reject) => { const transaction = db.transaction([KEY_STORE_NAME], "readonly"); const store = transaction.objectStore(KEY_STORE_NAME); const privateKeyRequest = store.get("installedPrivateKey"); transaction.oncomplete = () => resolve(!!privateKeyRequest.result); transaction.onerror = () => reject(transaction.error) }) }
        function getStoredTokenExample() {
            return localStorage.getItem("accessToken")
                || sessionStorage.getItem("accessToken")
                || localStorage.getItem("authToken")
                || sessionStorage.getItem("authToken")
                || "";
        }

        async function getTokenExample() { if (typeof getAccessToken === "function") return await getAccessToken(); return getStoredTokenExample() }

        async function validateTokenExample(token) {
            if (!token || !AUTH_FOUND) {
                return false;
            }

            try {
                const response = await fetch(AUTH_BASE + "api/validateToken.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        token: token
                    })
                });

                const rawText = await response.text();

                let data = null;

                try {
                    data = JSON.parse(rawText);
                } catch {
                    console.log("Token validation returned non-JSON response.", rawText);
                    return false;
                }

                console.log("Protected page token validation response:", data);

                return data.status === "success" || data.valid === true || data.authenticated === true || data.loggedIn === true;
            } catch (error) {
                console.log("Protected page token validation failed.", error);
                return false;
            }
        }

        async function refreshAuthStatus() {
            const loginPanel = document.getElementById("loginPanel");
            const protectedPanel = document.getElementById("protectedPanel");
            const tokenPreview = document.getElementById("tokenPreview");

            const installedImage = document.getElementById("sigInstalledImage");
            const notInstalledImage = document.getElementById("sigNotInstalledImage");

            let hasSignature = false;

            try {
                hasSignature = await getDeviceSignatureExample();
            } catch {
                hasSignature = false;
            }

            if (installedImage) {
                installedImage.style.display = hasSignature ? "block" : "none";
            }

            if (notInstalledImage) {
                notInstalledImage.style.display = hasSignature ? "none" : "block";
            }

            const storedTokenBeforeRefresh = getStoredTokenExample();

            setLine("deviceSignatureStatus", hasSignature ? "Installed" : "Missing", hasSignature ? "ok" : "bad");

            if (!AUTH_FOUND) {
                setSessionMeterState("none");
                setLine("tokenStatus", "Auth files missing", "bad");
                setMainStatus("Authorization Status", "The auth system files could not be found. Check where this example page is installed.");
                loginPanel.style.display = "none";
                protectedPanel.style.display = "none";
                return;
            }

            if (!hasSignature) {
                setSessionMeterState("none");
                setLine("tokenStatus", "No token check", "bad");
                setMainStatus("Authorization Status", "Your device signature is not installed. Open the signatures page and install a device signature first.");
                loginPanel.style.display = "none";
                protectedPanel.style.display = "none";
                return;
            }

            setSessionMeterState("refresh");

            const token = await getTokenExample();
            const tokenValid = await validateTokenExample(token);

            if (tokenValid) {
                setSessionMeterState("valid");
                setLine("tokenStatus", "Valid access token", "ok");
                setMainStatus("Authorization Status", "Your device signature is detected and your access token is valid. Protected content is visible.");

                loginPanel.style.display = "none";
                protectedPanel.style.display = "block";

                if (tokenPreview) {
                    tokenPreview.textContent = token
                        ? token.substring(0, 32) + "..." + token.substring(Math.max(token.length - 20, 0))
                        : "No token found.";
                }

                return;
            }

            protectedPanel.style.display = "none";
            loginPanel.style.display = "block";

            if (token || storedTokenBeforeRefresh) {
                setSessionMeterState("invalid");
                setLine("tokenStatus", "Token invalid or expired", "warn");
                setMainStatus("Authorization Status", "Your device signature is detected. Token is stored but is invalid and can not be refreshed. Login with the site password to create an access token.");
                return;
            }

            setSessionMeterState("none");
            setLine("tokenStatus", "No token", "bad");
            setMainStatus("Authorization Status", "Your device signature is detected. No access token is stored. Login with the site password to create an access token.");
        }
        async function exampleSubmitPassword() { if (typeof submitPassword === "function") { await submitPassword(); setTimeout(refreshAuthStatus, 400); return } alert("submitPassword() was not found. Check that passwordUtils.js loaded.") }
        function togglePasswordVisibility() { const input = document.getElementById("accessPassword"); const button = document.getElementById("passwordShow"); if (input.type === "password") { input.type = "text"; button.textContent = "Hide Password" } else { input.type = "password"; button.textContent = "Show Password" } }
        async function clearAccessTokenExample() { if (typeof clearPasswordCache === "function") clearPasswordCache(); localStorage.removeItem("accessToken"); sessionStorage.removeItem("accessToken"); localStorage.removeItem("authToken"); sessionStorage.removeItem("authToken"); await refreshAuthStatus() }
        async function copyAccessTokenExample() { const token = await getTokenExample(); if (!token) { alert("No token found."); return } await navigator.clipboard.writeText(token); alert("Token copied.") }
        async function deleteDeviceSignatureExample() { if (!confirm("Delete the device signature from this browser?")) return; const db = await openKeyDbExample(); await new Promise((resolve, reject) => { const transaction = db.transaction([KEY_STORE_NAME], "readwrite"); const store = transaction.objectStore(KEY_STORE_NAME); store.delete("installedPrivateKey"); store.delete("installedPublicKey"); store.delete("privateKeyName"); transaction.oncomplete = () => resolve(true); transaction.onerror = () => reject(transaction.error) }); localStorage.removeItem("privateKeyName"); await clearAccessTokenExample() }
        document.addEventListener("DOMContentLoaded", refreshAuthStatus);
    </script>
</body>

</html>