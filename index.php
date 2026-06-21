<?php
define('AUTH_SYSTEM_VERSION', '1.0.0167');

function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        return ["envFound" => false, "env" => []];
    }

    $contents = file_get_contents($filePath);

    if ($contents === false) {
        return ["envFound" => false, "env" => []];
    }

    $env = [];

    if (preg_match('/^DEVICE_KEYS=(\{.*\})\s*$/ms', $contents, $matches)) {
        $env['DEVICE_KEYS'] = trim($matches[1]);
    }

    foreach (preg_split("/\r\n|\n|\r/", $contents) as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);

        if ($key === 'DEVICE_KEYS') {
            continue;
        }

        $env[$key] = trim($value, "\"'");
    }

    return ["envFound" => true, "env" => $env];
}

function parseDeviceKeysFallback($deviceKeysText)
{
    $deviceKeys = [];

    preg_match_all(
        '/"([^"]+)"\s*:\s*"(-----BEGIN (?:PUBLIC|PRIVATE) KEY-----.*?-----END (?:PUBLIC|PRIVATE) KEY-----)"/s',
        $deviceKeysText,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $match) {
        $deviceKeys[$match[1]] = $match[2];
    }

    return $deviceKeys;
}

function countDeviceKeys($env)
{
    $deviceKeysText = $env['DEVICE_KEYS'] ?? '{}';
    $deviceKeys = json_decode($deviceKeysText, true);

    if (is_array($deviceKeys)) {
        return count($deviceKeys);
    }

    return count(parseDeviceKeysFallback($deviceKeysText));
}

function countJsonFile($filePath)
{
    if (!file_exists($filePath)) {
        return 0;
    }

    $contents = file_get_contents($filePath);

    if ($contents === false || trim($contents) === '') {
        return 0;
    }

    $data = json_decode($contents, true);

    return is_array($data) ? count($data) : 0;
}

$envPath = __DIR__ . '/fileSystemAccess.env';
$envResult = loadEnv($envPath);
$envFound = $envResult['envFound'];
$env = $envResult['env'];

$hasSecretKey = !empty($env['SECRET_KEY']);
$hasAccessPassword = !empty($env['ACCESS_PASSWORD']);
$approvedDeviceCount = countDeviceKeys($env);
$storedRefreshTokenCount = countJsonFile(__DIR__ . '/json/refreshTokens.json');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>bwAuthSystem Login</title>

    <link rel="icon" type="image/png" href="images/authSystemIcon.png">

    <script src="js/passwordUtils.js?v=<?php echo AUTH_SYSTEM_VERSION; ?>" defer></script>

    <style>
        :root {
            --page-bg: #05070b;
            --panel-bg: rgba(8, 13, 21, 0.92);
            --panel-bg-soft: rgba(13, 20, 31, 0.82);
            --header-bg: rgba(3, 7, 13, 0.96);
            --text: #e8f1ff;
            --muted: #9cadc3;
            --border: rgba(67, 151, 255, 0.34);
            --border-soft: rgba(255, 255, 255, 0.13);
            --blue: #2495ff;
            --blue-soft: rgba(36, 149, 255, 0.12);
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
            font-size: clamp(1.65rem, 5vw, 2.65rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
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
            padding: 10px 0 36px;
        }

        .loginPanel {
            padding: 12px clamp(16px, 4vw, 28px) clamp(16px, 4vw, 28px);
            border-color: var(--border);
        }

        .tagline {
            margin: 0 0 18px;
            padding-left: 13px;
            border-left: 3px solid var(--blue);
            color: var(--muted);
            font-size: 1.02rem;
        }

        .loginPanel,
        details,
        .footerActions {
            border: 1px solid var(--border-soft);
            background: var(--panel-bg);
            box-shadow: var(--shadow);
        }

        .loginPanel h2 {
            margin: 0 0 8px;
            color: var(--blue);
            font-size: 1.45rem;
        }

        .helpText {
            margin: 0 0 18px;
            color: var(--muted);
            line-height: 1.45;
        }

        .loginRule {
            height: 1px;
            margin: 16px 0;
            background: var(--border-soft);
        }

        label {
            display: block;
            margin: 14px 0 7px;
            color: var(--text);
            font-weight: 700;
        }

        input,
        select {
            width: 100%;
            min-height: 46px;
            padding: 10px 12px;
            border: 1px solid rgba(36, 149, 255, 0.45);
            background: rgba(0, 0, 0, 0.28);
            color: var(--text);
            font-size: 1rem;
            outline: none;
        }

        input:focus,
        select:focus {
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

        .signatureButton {
            width: 100%;
            border-color: rgba(36, 149, 255, 0.75);
            background: rgba(36, 149, 255, 0.08);
            color: var(--blue);
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

        .smallNote {
            margin-bottom: 8px;
            padding: 12px 13px;
            border: 1px solid var(--border-soft);
            background: rgba(255, 255, 255, 0.04);
            color: var(--muted);
            line-height: 1.42;
        }

        .dividerText {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 14px 0;
            color: var(--muted);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .dividerText::before,
        .dividerText::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--border-soft);
        }

        .tools {
            display: grid;
            gap: 10px;
            margin-top: 14px;
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

        .statusList {
            list-style: none;
            margin: 0;
            padding: 0;
            border: 1px solid var(--border-soft);
        }

        .statusList li {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 11px;
            border-bottom: 1px solid var(--border-soft);
            background: rgba(0, 0, 0, 0.16);
        }

        .statusList li:last-child {
            border-bottom: 0;
        }

        .ok {
            color: var(--ok);
            font-weight: 700;
        }

        .warn {
            color: var(--warn);
            font-weight: 700;
        }

        .bad {
            color: #ff6470;
            font-weight: 700;
        }

        .docLinks {
            display: grid;
            gap: 8px;
        }

        pre {
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 210px;
            overflow: auto;
            padding: 10px;
            border: 1px solid var(--border-soft);
            background: rgba(0, 0, 0, 0.28) !important;
            color: var(--text) !important;
            font-family: Consolas, Monaco, "Courier New", monospace;
        }

        pre.statusOk {
            border-color: rgba(50, 196, 108, 0.55);
            color: var(--ok) !important;
            background: rgba(50, 196, 108, 0.08) !important;
        }

        pre.statusBad {
            border-color: rgba(255, 100, 112, 0.55);
            color: #ff6470 !important;
            background: rgba(180, 31, 42, 0.1) !important;
        }

        .footerActions {
            margin-top: 14px;
            padding: 14px 16px;
        }

        footer {
            width: min(880px, calc(100% - 28px));
            margin: 0 auto;
            padding: 0 0 28px;
            color: var(--muted);
            text-align: center;
            font-size: 0.9rem;
        }

        .loginButtonRow {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 10px;
        }

        .showPasswordButton {
            width: 100%;
        }

        .loggedInText {
            color: var(--ok);
            font-weight: 700;
        }

        .loggedInInput {
            border-color: rgba(50, 196, 108, 0.85) !important;
            box-shadow: 0 0 0 2px rgba(50, 196, 108, 0.14) !important;
        }

        .loggedInButton {
            opacity: 0.45;
        }

        .authDangerText {
            color: #ff6470;
        }

        .noPrivateKeyButton {
            opacity: 0.45;
            cursor: pointer;
            filter: grayscale(0.35);
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

            .statusList li {
                display: block;
            }

            .statusList li span:last-child {
                display: block;
                margin-top: 4px;
            }
        }
    </style>
</head>

<body>
    <header class="authHeader">
        <div class="headerLine">
            <h1><span>bwAuthSystem</span> Login</h1>
            <div class="versionText">v<?php echo htmlspecialchars(AUTH_SYSTEM_VERSION); ?></div>
        </div>
    </header>

    <main class="authMain">
        <section class="loginPanel">
            <div class="smallNote">
                Login requires site password and private key installed in this browser.
            </div>

            <h2>Admin Login</h2>
            <p id="loginStateText" class="helpText">
                Checking device signature and login status.
            </p>

            <form id="authLoginForm" onsubmit="event.preventDefault(); submitPasswordAndRefreshUi();">
                <label for="deviceName">Device Name</label>
                <input type="text" id="deviceName" name="deviceName" autocomplete="off"
                    placeholder="Example: Brian-Laptop-Chrome">

                <label for="accessPassword">Access Password</label>
                <input type="password" id="accessPassword" name="accessPassword" autocomplete="current-password"
                    placeholder="Enter access password">

                <div class="buttonStack">
                    <div class="loginButtonRow">
                        <button id="passwordSubmit" class="primaryButton" type="submit">Login</button>
                        <button id="showPasswordButton" class="secondaryButton showPasswordButton" type="button"
                            onclick="const p=document.getElementById('accessPassword'); if(p.type==='password'){p.type='text'; this.textContent='Hide';}else{p.type='password'; this.textContent='Show';}">Show</button>
                    </div>
                </div>
            </form>

            <div class="dividerText">or</div>

            <a class="buttonLink signatureButton" href="signatures.php">Open Signatures Page</a>


        </section>

        <section class="tools">
            <details>
                <summary>System Status</summary>
                <div class="detailBody">
                    <ul class="statusList">
                        <li>
                            <span>Environment file</span>
                            <span class="<?php echo $envFound ? 'ok' : 'bad'; ?>">
                                <?php echo $envFound ? 'Found' : 'Missing'; ?>
                            </span>
                        </li>
                        <li>
                            <span>SECRET_KEY</span>
                            <span class="<?php echo $hasSecretKey ? 'ok' : 'bad'; ?>">
                                <?php echo $hasSecretKey ? 'Set' : 'Missing'; ?>
                            </span>
                        </li>
                        <li>
                            <span>Admin password</span>
                            <span class="<?php echo $hasAccessPassword ? 'ok' : 'bad'; ?>">
                                <?php echo $hasAccessPassword ? 'Set' : 'Missing'; ?>
                            </span>
                        </li>
                        <li>
                            <span>Approved devices</span>
                            <span class="<?php echo $approvedDeviceCount > 0 ? 'ok' : 'warn'; ?>">
                                <?php echo $approvedDeviceCount; ?>
                            </span>
                        </li>
                        <li>
                            <span>Stored refresh tokens</span>
                            <span><?php echo $storedRefreshTokenCount; ?></span>
                        </li>
                    </ul>
                </div>
            </details>

            <details>
                <summary>Device Key Data</summary>
                <div class="detailBody">
                    <p>
                        Use this only for troubleshooting. The signatures page is the main place to manage keys.
                    </p>

                    <div class="buttonRow">
                        <button type="button" class="secondaryButton" onclick="showStoredDeviceKeyData()">Show Stored
                            Key Data</button>
                    </div>

                    <h3>Device Name</h3>
                    <pre id="storedDeviceNameOutput">Checking stored device name...</pre>

                    <h3>Public Key</h3>
                    <pre id="storedPublicKeyOutput">Checking stored public key...</pre>

                    <h3>Private Key</h3>
                    <pre id="storedPrivateKeyOutput">Checking stored private key...</pre>
                </div>
            </details>

            <details id="advancedDetails">
                <summary>Advanced</summary>
                <div class="detailBody">
                    <div class="buttonRow">
                        <button type="button" class="secondaryButton" onclick="logoutOnly()">Logout</button>
                        <button type="button" class="dangerButton" onclick="logoutAndWipeAll()">Logout + Wipe Saved
                            Data</button>
                    </div>

                    <p>
                        Logout clears the current token. Wipe saved data removes saved login tokens and cached password
                        data from this browser. Device keys are managed on the signatures page.
                    </p>
                </div>
            </details>
        </section>

    </main>

    <footer>
        bwAuthSystem — secure device-signature MFA for small PHP admin tools.
    </footer>

    <script>
        let authIndexedDbMissingStoreLogged = false;
        async function getAuthIndexedDbValue(key) {
            return new Promise((resolve) => {
                const request = indexedDB.open("BWCSAuthDB", 1);

                request.onupgradeneeded = function (event) {
                    const db = event.target.result;

                    if (!db.objectStoreNames.contains("privateKeys")) {
                        db.createObjectStore("privateKeys");
                    }
                };

                request.onerror = function () {
                    console.warn("Could not open auth IndexedDB.", request.error);
                    resolve("");
                };

                request.onsuccess = function () {
                    const db = request.result;

                    if (!db.objectStoreNames.contains("privateKeys")) {
                        if (!authIndexedDbMissingStoreLogged) {
                            console.log("Auth IndexedDB privateKeys store is missing. User needs to reinstall device key.");
                            authIndexedDbMissingStoreLogged = true;
                        }

                        db.close();
                        resolve("");
                        return;
                    }

                    let transaction;

                    try {
                        transaction = db.transaction(["privateKeys"], "readonly");
                    } catch (error) {
                        console.warn("Could not start auth IndexedDB transaction.", error);
                        db.close();
                        resolve("");
                        return;
                    }

                    const store = transaction.objectStore("privateKeys");
                    const getRequest = store.get(key);

                    getRequest.onsuccess = function () {
                        resolve(getRequest.result || "");
                    };

                    getRequest.onerror = function () {
                        console.warn("Could not read auth IndexedDB value.", getRequest.error);
                        resolve("");
                    };

                    transaction.oncomplete = function () {
                        db.close();
                    };

                    transaction.onerror = function () {
                        console.warn("Auth IndexedDB transaction failed.", transaction.error);
                        db.close();
                        resolve("");
                    };
                };
            });
        }

        function setPreStatus(id, text, state) {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = text;
            el.classList.remove("statusOk", "statusBad");
            if (state) {
                el.classList.add(state);
            }
        }

        function getStoredTokenFromBrowser() {
            return localStorage.getItem("cyberEventToken")
                || sessionStorage.getItem("cyberEventToken")
                || localStorage.getItem("accessToken")
                || sessionStorage.getItem("accessToken")
                || localStorage.getItem("authToken")
                || sessionStorage.getItem("authToken")
                || "";
        }

        async function getAccessTokenForLoginPage() {
            if (typeof getAccessToken === "function") {
                return await getAccessToken();
            }

            return getStoredTokenFromBrowser();
        }

        async function validateTokenForLoginPage(token) {
            if (!token) {
                console.log("No token found.");
                return { valid: false, status: "missing", reason: "no_token", message: "No token found." };
            }

            try {
                const response = await fetch("api/validateToken.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        token: token
                    })
                });

                const rawText = await response.text();
                const data = JSON.parse(rawText);

                console.log("Token validation response:", data);

                return {
                    valid: data.status === "success",
                    status: data.status || "unknown",
                    reason: data.reason || "",
                    message: data.message || ""
                };
            } catch (error) {
                console.log("Token validation failed:", error);
                return { valid: false, status: "error", reason: "validation_request_failed", message: "Token validation request failed." };
            }
        }

        async function prefillDeviceNameFromSignature() {
            const deviceNameInput = document.getElementById("deviceName");
            if (!deviceNameInput) return "";

            let deviceName = "";

            try {
                deviceName = await getAuthIndexedDbValue("privateKeyName") || "";
            } catch {
                deviceName = "";
            }

            if (!deviceName) {
                deviceName = localStorage.getItem("privateKeyName") || "";
            }

            if (deviceName && !deviceNameInput.value.trim()) {
                deviceNameInput.value = deviceName;
            }

            return deviceName;
        }

        async function prefillPasswordIfAvailable() {
            const passwordInput = document.getElementById("accessPassword");
            if (!passwordInput) return;

            try {
                if (typeof getCachedPassword === "function") {
                    const cachedPassword = await getCachedPassword();

                    if (cachedPassword && !passwordInput.value) {
                        passwordInput.value = cachedPassword;
                    }
                }
            } catch {
                // Passive password autofill failed. Do not alert on page load.
            }
        }

        function setLoggedInUi() {
            const loginStateText = document.getElementById("loginStateText");
            const deviceNameInput = document.getElementById("deviceName");
            const passwordInput = document.getElementById("accessPassword");
            const loginButton = document.getElementById("passwordSubmit");
            const advancedDetails = document.getElementById("advancedDetails");

            if (loginStateText) {
                loginStateText.innerHTML = 'You are <span class="loggedInText">logged in.</span>';
            }

            if (deviceNameInput) {
                deviceNameInput.classList.add("loggedInInput");
            }

            if (passwordInput) {
                passwordInput.classList.add("loggedInInput");
            }

            if (loginButton) {
                loginButton.classList.add("loggedInButton");
            }

            if (advancedDetails) {
                advancedDetails.open = true;
            }
        }

        function resetLoggedInUi() {
            const loginStateText = document.getElementById("loginStateText");
            const deviceNameInput = document.getElementById("deviceName");
            const passwordInput = document.getElementById("accessPassword");
            const loginButton = document.getElementById("passwordSubmit");
            const advancedDetails = document.getElementById("advancedDetails");

            if (loginStateText) {
                loginStateText.textContent = "Signature installed. Enter site password to complete login.";
            }

            if (deviceNameInput) {
                deviceNameInput.classList.remove("loggedInInput");
            }

            if (passwordInput) {
                passwordInput.classList.remove("loggedInInput");
            }

            if (loginButton) {
                loginButton.classList.remove("loggedInButton");
                loginButton.classList.remove("noPrivateKeyButton");
            }

            if (advancedDetails) {
                advancedDetails.open = false;
            }
        }

        function setNoPrivateKeyUi() {
            const loginStateText = document.getElementById("loginStateText");
            const loginButton = document.getElementById("passwordSubmit");

            resetLoggedInUi();

            if (loginStateText) {
                loginStateText.innerHTML = '<span class="authDangerText">Device signature is required for login.</span> No private key is installed in this browser. Open the signatures page to create or install a device key.';
            }

            if (loginButton) {
                loginButton.classList.add("noPrivateKeyButton");
            }
        }

        async function refreshLoginPageState() {
            const loginStateText = document.getElementById("loginStateText");
            const deviceName = await prefillDeviceNameFromSignature();

            let privateKey = "";

            try {
                privateKey = await getAuthIndexedDbValue("installedPrivateKey") || "";
            } catch {
                privateKey = "";
            }

            if (!privateKey) {
                const token = await getAccessTokenForLoginPage();

                if (!token) {
                    alert("Admin access only. This page is restricted to approved administrators.");
                }

                setNoPrivateKeyUi();
                console.log("Login state: no private key installed.");
                return;
            }
            await prefillPasswordIfAvailable();
            const token = await getAccessTokenForLoginPage();
            const tokenValidation = await validateTokenForLoginPage(token);

            if (tokenValidation.valid) {
                console.log("Login state: token validated.", tokenValidation);

                setLoggedInUi();

                await prefillPasswordIfAvailable();
                return;
            }

            if (token) {
                loginStateText.textContent = "Token found but could not be validated. Signature installed. Enter site password to complete login.";
                console.log("Login state: token found but could not be validated.", tokenValidation);
                return;
            }

            loginStateText.textContent = deviceName
                ? "Signature installed. Enter site password to complete login."
                : "Device signature detected. Enter a device name and the site password.";

            console.log("Login state: signature installed but no valid token found.", {
                deviceNameExists: !!deviceName,
                tokenExists: !!token,
                tokenValidation: tokenValidation
            });
        }

        async function refreshStoredDeviceKeyStatus() {
            const deviceNameOutput = document.getElementById("storedDeviceNameOutput");
            const publicKeyOutput = document.getElementById("storedPublicKeyOutput");
            const privateKeyOutput = document.getElementById("storedPrivateKeyOutput");

            if (!deviceNameOutput || !publicKeyOutput || !privateKeyOutput) return;

            try {
                const deviceName =
                    await getAuthIndexedDbValue("privateKeyName") ||
                    localStorage.getItem("privateKeyName") ||
                    "";

                const privateKey =
                    await getAuthIndexedDbValue("installedPrivateKey") ||
                    "";

                const publicKey =
                    await getAuthIndexedDbValue("installedPublicKey") ||
                    await getAuthIndexedDbValue("publicKey") ||
                    await getAuthIndexedDbValue("installedPublicKeyPem") ||
                    "";

                setPreStatus(
                    "storedDeviceNameOutput",
                    deviceName ? "Stored device name found. Click Show Stored Key Data to reveal." : "No stored device name found.",
                    deviceName ? "statusOk" : "statusBad"
                );

                setPreStatus(
                    "storedPublicKeyOutput",
                    publicKey ? "Stored public key found. Click Show Stored Key Data to reveal." : "No stored public key found.",
                    publicKey ? "statusOk" : "statusBad"
                );

                setPreStatus(
                    "storedPrivateKeyOutput",
                    privateKey ? "Stored private key found. Click Show Stored Key Data to reveal." : "No stored private key found.",
                    privateKey ? "statusOk" : "statusBad"
                );
            } catch (error) {
                setPreStatus("storedDeviceNameOutput", "Error checking stored device name.", "statusBad");
                setPreStatus("storedPublicKeyOutput", "Error checking stored public key.", "statusBad");
                setPreStatus("storedPrivateKeyOutput", "Error checking stored private key.", "statusBad");
                console.error("Failed to check stored key status:", error);
            }
        }

        async function showStoredDeviceKeyData() {
            const deviceNameOutput = document.getElementById("storedDeviceNameOutput");
            const publicKeyOutput = document.getElementById("storedPublicKeyOutput");
            const privateKeyOutput = document.getElementById("storedPrivateKeyOutput");

            setPreStatus("storedDeviceNameOutput", "Loading...", "");
            setPreStatus("storedPublicKeyOutput", "Loading...", "");
            setPreStatus("storedPrivateKeyOutput", "Loading...", "");

            try {
                const deviceName =
                    await getAuthIndexedDbValue("privateKeyName") ||
                    localStorage.getItem("privateKeyName") ||
                    "";

                const privateKey =
                    await getAuthIndexedDbValue("installedPrivateKey") ||
                    "";

                const publicKey =
                    await getAuthIndexedDbValue("installedPublicKey") ||
                    await getAuthIndexedDbValue("publicKey") ||
                    await getAuthIndexedDbValue("installedPublicKeyPem") ||
                    "";

                setPreStatus("storedDeviceNameOutput", deviceName || "No stored device name found.", deviceName ? "statusOk" : "statusBad");
                setPreStatus("storedPublicKeyOutput", publicKey || "No stored public key found. Use signatures.php to generate or install key data.", publicKey ? "statusOk" : "statusBad");
                setPreStatus("storedPrivateKeyOutput", privateKey || "No stored private key found.", privateKey ? "statusOk" : "statusBad");
            } catch (error) {
                setPreStatus("storedDeviceNameOutput", "Error loading stored key data.", "statusBad");
                setPreStatus("storedPublicKeyOutput", "Error loading stored key data.", "statusBad");
                setPreStatus("storedPrivateKeyOutput", "Error loading stored key data.", "statusBad");
                console.error("Failed to load stored key data:", error);
            }
        }

        function togglePasswordVisibility() {
            console.log("hit toggle password visibility/hidden");
            const passwordInput = document.querySelector("#accessPassword");
            const showButton = document.querySelector("#showPasswordButton");

            if (!passwordInput || !showButton) {
                console.error("Password toggle elements not found.");
                return;
            }

            if (passwordInput.getAttribute("type") === "password") {
                passwordInput.setAttribute("type", "text");
                showButton.textContent = "Hide";
            } else {
                passwordInput.setAttribute("type", "password");
                showButton.textContent = "Show";
            }
        }

        async function submitPasswordAndRefreshUi() {
            try {
                const privateKey = await getAuthIndexedDbValue("installedPrivateKey");

                if (!privateKey) {
                    setNoPrivateKeyUi();
                    alert("No private key is installed. Go to the signatures page to create or install a device key.");
                    return;
                }

                await submitPassword();
            } catch (error) {
                console.error("Login failed before completion.", error);
                alert("Login failed before completion. Check the console for details.");
            }
        }

        async function logoutOnly() {
            if (!confirm("Log out of this device?")) {
                return;
            }

            resetLoggedInUi();

            localStorage.removeItem("cyberEventToken");
            sessionStorage.removeItem("cyberEventToken");

            localStorage.removeItem("cyberEventRefreshToken");
            sessionStorage.removeItem("cyberEventRefreshToken");

            localStorage.removeItem("refreshToken");
            sessionStorage.removeItem("refreshToken");

            localStorage.removeItem("accessToken");
            sessionStorage.removeItem("accessToken");

            localStorage.removeItem("authToken");
            sessionStorage.removeItem("authToken");

            await refreshLoginPageState();

            alert("Logged out.");
        }

        async function logoutAndWipeAll() {
            if (!confirm("Logout and wipe saved login data from this browser? Device keys will not be removed.")) {
                return;
            }

            resetLoggedInUi();

            localStorage.removeItem("cyberEventToken");
            sessionStorage.removeItem("cyberEventToken");

            localStorage.removeItem("cyberEventRefreshToken");
            sessionStorage.removeItem("cyberEventRefreshToken");

            localStorage.removeItem("refreshToken");
            sessionStorage.removeItem("refreshToken");

            localStorage.removeItem("accessToken");
            sessionStorage.removeItem("accessToken");

            localStorage.removeItem("authToken");
            sessionStorage.removeItem("authToken");

            localStorage.removeItem("userRole");
            sessionStorage.removeItem("userRole");

            localStorage.removeItem("cyberEventPassword");
            sessionStorage.removeItem("cyberEventPassword");

            try {
                if (typeof clearEncryptedPasswordCache === "function") {
                    await clearEncryptedPasswordCache();
                }
            } catch (error) {
                console.log("Saved encrypted password cache could not be cleared.");
            }

            await refreshLoginPageState();

            alert("Saved login data was wiped from this browser. The device key was not removed.");
        }

        document.addEventListener("DOMContentLoaded", async () => {
            await refreshLoginPageState();
            await refreshStoredDeviceKeyStatus();

            document.addEventListener("bwcs_login_success", () => {
                setLoggedInUi();
                console.log("Login success event received. Logged-in UI applied.");
            });
        });
    </script>
</body>

</html>