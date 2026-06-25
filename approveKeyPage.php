<?php
define('APPROVE_KEY_PAGE_VERSION', '1.00.0012');

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

$actionLoggerPath = '';
if ($authFound) {
    if (file_exists($authSystem['fs'] . '/actionLogger.js')) {
        $actionLoggerPath = $authWebPath . 'actionLogger.js';
    } elseif (file_exists($authSystem['fs'] . '/js/actionLogger.js')) {
        $actionLoggerPath = $authWebPath . 'js/actionLogger.js';
    }
}

$keyName = isset($_GET['keyname']) ? preg_replace("/[^a-zA-Z0-9_-]/", "", trim($_GET['keyname'])) : '';
$publicKey = isset($_GET['publickey']) ? trim($_GET['publickey']) : '';

$safeKeyName = htmlspecialchars($keyName, ENT_QUOTES, 'UTF-8');
$safePublicKey = htmlspecialchars($publicKey, ENT_QUOTES, 'UTF-8');
$publicKeyLength = strlen($publicKey);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Device Key</title>
    <link rel="icon" type="image/png" href="images/authSystemIcon.png">

    <?php if ($actionLoggerPath): ?>
        <script src="<?php echo htmlspecialchars($actionLoggerPath); ?>?v=<?php echo APPROVE_KEY_PAGE_VERSION; ?>"
            defer></script>
    <?php endif; ?>

    <?php if ($passwordUtilsPath): ?>
        <script src="<?php echo htmlspecialchars($passwordUtilsPath); ?>?v=<?php echo APPROVE_KEY_PAGE_VERSION; ?>"
            defer></script>
    <?php endif; ?>

    <style>
        :root {
            --page-bg: #070b12;
            --panel-bg: #101724;
            --panel-bg-soft: #121c2b;
            --header-bg: #05070c;
            --text-main: #f5f7fb;
            --text-muted: #9ca8ba;
            --text-soft: #c7d1df;
            --border-main: rgba(255, 255, 255, 0.11);
            --border-strong: rgba(255, 255, 255, 0.18);
            --button-main: #2563eb;
            --button-main-hover: #1d4ed8;
            --button-secondary: #334155;
            --button-secondary-hover: #475569;
            --button-danger: #991b1b;
            --focus-ring: rgba(37, 99, 235, 0.35);
            --shadow-main: 0 22px 70px rgba(0, 0, 0, 0.42);
        }

        * {
            box-sizing: border-box;
        }

        html {
            background: var(--page-bg);
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.16), transparent 34rem),
                linear-gradient(180deg, #070b12 0%, #0a101a 100%);
            color: var(--text-main);
            font-family: Arial, Helvetica, sans-serif;
        }

        a {
            color: inherit;
        }

        .topHeader {
            width: 100%;
            background: rgba(5, 7, 12, 0.96);
            border-bottom: 1px solid var(--border-main);
            box-shadow: 0 16px 44px rgba(0, 0, 0, 0.28);
        }

        .headerInner {
            width: min(840px, calc(100% - 28px));
            margin: 0 auto;
            padding: 22px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .eyebrow {
            margin: 0 0 7px;
            color: var(--text-muted);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: clamp(1.65rem, 4vw, 2.45rem);
            letter-spacing: -0.04em;
            line-height: 1.02;
        }

        .versionText {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .headerLink {
            flex: 0 0 auto;
            min-height: 40px;
            padding: 10px 13px;
            border: 1px solid var(--border-strong);
            border-radius: 10px;
            color: var(--text-soft);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 700;
        }

        .pageShell {
            width: min(840px, calc(100% - 28px));
            margin: 0 auto;
            padding: 24px 0 38px;
        }

        .panel {
            background: linear-gradient(180deg, rgba(16, 23, 36, 0.98), rgba(12, 18, 29, 0.98));
            border: 1px solid var(--border-main);
            border-radius: 16px;
            box-shadow: var(--shadow-main);
            padding: clamp(17px, 3vw, 26px);
            margin-bottom: 16px;
        }

        .primaryPanel {
            border-color: rgba(37, 99, 235, 0.36);
        }

        h2 {
            margin: 0 0 8px;
            font-size: clamp(1.2rem, 2.6vw, 1.55rem);
            letter-spacing: -0.02em;
        }

        h3 {
            margin: 22px 0 8px;
            color: var(--text-soft);
            font-size: 1rem;
        }

        .helpText {
            margin: 0 0 18px;
            color: var(--text-muted);
            line-height: 1.48;
        }

        .metaGrid {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 10px 14px;
            margin-top: 18px;
        }

        .metaLabel {
            color: var(--text-muted);
            font-weight: 700;
        }

        .metaValue {
            color: var(--text-main);
            word-break: break-word;
        }

        pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 310px;
            overflow: auto;
            background: #050910 !important;
            border: 1px solid var(--border-main);
            border-radius: 10px;
            padding: 12px;
            color: #dbeafe !important;
            font-family: Consolas, Monaco, monospace;
            font-size: 0.86rem;
            line-height: 1.42;
            user-select: text;
        }

        .buttonRow {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        button,
        .buttonLink {
            min-height: 42px;
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            background: var(--button-main);
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1.15;
        }

        button:hover,
        .buttonLink:hover {
            background: var(--button-main-hover);
        }

        button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .secondaryButton {
            background: var(--button-secondary);
        }

        .secondaryButton:hover {
            background: var(--button-secondary-hover);
        }

        .notice {
            margin-top: 16px;
            padding: 12px 14px;
            border: 1px solid rgba(36, 149, 255, 0.35);
            border-radius: 8px;
            background: rgba(36, 149, 255, 0.08);
            line-height: 1.45;
            color: var(--text-soft);
        }

        .statusBox {
            display: none;
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 8px;
            line-height: 1.45;
            white-space: pre-wrap;
            user-select: text;
        }

        .statusOk {
            display: block;
            border: 1px solid rgba(34, 197, 94, 0.38);
            background: rgba(34, 197, 94, 0.09);
            color: #bbf7d0;
        }

        .statusBad {
            display: block;
            border: 1px solid rgba(239, 68, 68, 0.38);
            background: rgba(239, 68, 68, 0.09);
            color: #fecaca;
        }

        @media (max-width: 560px) {
            .headerInner {
                display: block;
            }

            .headerLink {
                margin-top: 14px;
                width: 100%;
            }

            .metaGrid {
                grid-template-columns: 1fr;
            }

            button,
            .buttonLink {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <header class="topHeader">
        <div class="headerInner">
            <div>
                <p class="eyebrow">bwAuthSystem</p>
                <h1>Approve Device Key <span
                        class="versionText">v<?php echo htmlspecialchars(APPROVE_KEY_PAGE_VERSION); ?></span></h1>
            </div>
            <a class="headerLink" href="<?php echo htmlspecialchars($authWebPath); ?>index.php">Admin Login</a>
        </div>
    </header>

    <main class="pageShell">
        <section class="panel primaryPanel">
            <h2>Approve Public Device Key</h2>
            <p class="helpText">
                Review the device key below. Clicking approve will send the key name, public key, and current admin
                access token to keyApprover.php.
            </p>

            <div class="metaGrid">
                <div class="metaLabel">Device Name</div>
                <div class="metaValue" id="keyNameDisplay"><?php echo $safeKeyName !== '' ? $safeKeyName : 'Missing'; ?>
                </div>

                <div class="metaLabel">Public Key Length</div>
                <div class="metaValue"><?php echo (int) $publicKeyLength; ?> characters</div>

                <div class="metaLabel">Auth Files</div>
                <div class="metaValue"><?php echo htmlspecialchars($authSystem['label']); ?></div>

                <div class="metaLabel">Token Status</div>
                <div class="metaValue" id="tokenStatusDisplay">Checking browser token...</div>
            </div>

            <h3>Public Key</h3>
            <pre
                id="publicKeyDisplay"><?php echo $safePublicKey !== '' ? $safePublicKey : 'Missing public key.'; ?></pre>

            <div class="notice">
                You must be logged in as admin in this browser. The backend helper still verifies the access token
                before writing to DEVICE_KEYS.
            </div>

            <div class="buttonRow">
                <button type="button" id="approveKeyButton" onclick="approveDeviceKey()">Approve Device Key</button>
                <a class="buttonLink secondaryButton"
                    href="<?php echo htmlspecialchars($authWebPath); ?>signatures.php">Back To Signatures</a>
            </div>

            <div id="statusBox" class="statusBox"></div>
        </section>
    </main>

    <script>
        const AUTH_FOUND = <?php echo $authFound ? 'true' : 'false'; ?>;
        const APPROVE_KEY_NAME = <?php echo json_encode($keyName, JSON_UNESCAPED_SLASHES); ?>;
        const APPROVE_PUBLIC_KEY = <?php echo json_encode($publicKey, JSON_UNESCAPED_SLASHES); ?>;

        async function getApprovalAccessToken() {
            if (typeof getAccessToken === "function") {
                return await getAccessToken();
            }

            return localStorage.getItem("accessToken") ||
                sessionStorage.getItem("accessToken") ||
                localStorage.getItem("authToken") ||
                sessionStorage.getItem("authToken") ||
                localStorage.getItem("jwt") ||
                sessionStorage.getItem("jwt") ||
                "";
        }

        function showStatus(ok, message) {
            const statusBox = document.getElementById("statusBox");
            statusBox.className = ok ? "statusBox statusOk" : "statusBox statusBad";
            statusBox.textContent = message;
        }

        async function updatePageState() {
            const approveButton = document.getElementById("approveKeyButton");

            if (!AUTH_FOUND) {
                document.getElementById("tokenStatusDisplay").textContent = "Auth system files not found.";
                approveButton.disabled = true;
                return;
            }

            if (!APPROVE_KEY_NAME || !APPROVE_PUBLIC_KEY) {
                document.getElementById("tokenStatusDisplay").textContent = "Missing key name or public key.";
                approveButton.disabled = true;
                return;
            }

            const token = await getApprovalAccessToken();

            if (!token) {
                document.getElementById("tokenStatusDisplay").textContent = "No access token found. Log in as admin first.";
                approveButton.disabled = true;
                return;
            }

            document.getElementById("tokenStatusDisplay").textContent = "Access token found. Backend will verify admin role.";
            approveButton.disabled = false;
        }

        async function approvalLogAction(actionName, metadata) {
            if (typeof logAction !== "function") {
                return;
            }

            try {
                let userIP = "Unknown";

                if (typeof getUserIP === "function") {
                    userIP = await getUserIP();
                }

                logAction(actionName, {
                    ...metadata,
                    timestamp: new Date().toISOString(),
                    ip: userIP,
                    browser: navigator.userAgent
                });
            } catch (error) {
                console.log("Approval logging failed:", error);
            }
        }

        async function approveDeviceKey() {
            const token = await getApprovalAccessToken();

            if (!token) {
                showStatus(false, "No access token found. Log in as admin first, then reopen this approval link.");
                return;
            }

            if (!APPROVE_KEY_NAME || !APPROVE_PUBLIC_KEY) {
                showStatus(false, "Missing key name or public key.");
                return;
            }

            document.getElementById("approveKeyButton").disabled = true;
            showStatus(true, "Submitting approval...");

            await approvalLogAction("Key Approval Attempt", {
                keyname: APPROVE_KEY_NAME
            });

            try {
                const response = await fetch("api/keyApprover.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        keyname: APPROVE_KEY_NAME,
                        publickey: APPROVE_PUBLIC_KEY,
                        token: token
                    })
                });

                const rawText = await response.text();
                let result;

                try {
                    result = JSON.parse(rawText);
                } catch {
                    showStatus(response.ok, rawText);
                    if (!response.ok) {
                        document.getElementById("approveKeyButton").disabled = false;
                    }
                    return;
                }

                const ok = result.status === "success";

                showStatus(
                    ok,
                    "Status: " + (result.status || "") +
                    "\nMessage: " + (result.message || "") +
                    (result.key_name ? "\nDevice Name: " + result.key_name : "") +
                    (typeof result.already_existed !== "undefined" ? "\nAlready Existed: " + result.already_existed : "")
                );

                await approvalLogAction(ok ? "Key Approval Success" : "Key Approval Failure", {
                    keyname: APPROVE_KEY_NAME,
                    response: result
                });

                if (!ok) {
                    document.getElementById("approveKeyButton").disabled = false;
                }
            } catch (error) {
                showStatus(false, "Request failed: " + error.message);

                await approvalLogAction("Key Approval Failure", {
                    keyname: APPROVE_KEY_NAME,
                    error: error.message
                });

                document.getElementById("approveKeyButton").disabled = false;
            }
        }

        document.addEventListener("DOMContentLoaded", updatePageState);
    </script>
</body>

</html>