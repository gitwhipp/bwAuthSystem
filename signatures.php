<?php
define('SIGNATURES_VERSION', '1.01.0020');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Signatures</title>

    <script>
        const VERSION = "<?php echo SIGNATURES_VERSION; ?>";
        if (localStorage.getItem("signatures_page_version") !== VERSION) {
            localStorage.setItem("signatures_page_version", VERSION);
        }
    </script>

    <link rel="icon" type="image/png" href="images/authSystemIcon.png">
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
            --button-danger-hover: #7f1d1d;
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

        .brandBlock {
            min-width: 0;
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

        label {
            display: block;
            margin: 0 0 8px;
            color: var(--text-soft);
            font-weight: 700;
        }

        input,
        textarea,
        select {
            width: 100%;
            min-height: 46px;
            padding: 12px 13px;
            border: 1px solid var(--border-strong) !important;
            border-radius: 11px;
            background: #080d15 !important;
            color: var(--text-main) !important;
            font-size: 1rem;
            outline: none;
            box-shadow: none;
        }

        input::placeholder,
        textarea::placeholder {
            color: rgba(156, 168, 186, 0.78) !important;
        }

        input:-webkit-autofill,
        textarea:-webkit-autofill,
        select:-webkit-autofill {
            -webkit-text-fill-color: var(--text-main) !important;
            box-shadow: 0 0 0 1000px #080d15 inset !important;
            caret-color: var(--text-main);
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: rgba(37, 99, 235, 0.82) !important;
            box-shadow: 0 0 0 4px var(--focus-ring) !important;
        }

        textarea {
            min-height: 136px;
            resize: vertical;
            font-family: Consolas, Monaco, monospace;
            font-size: 0.9rem;
        }

        .buttonRow {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
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

        .secondaryButton {
            background: var(--button-secondary);
        }

        .secondaryButton:hover {
            background: var(--button-secondary-hover);
        }

        .dangerButton {
            background: var(--button-danger);
        }

        .dangerButton:hover {
            background: var(--button-danger-hover);
        }

        .keyBlock {
            margin-top: 15px;
            padding: 14px;
            background: rgba(8, 13, 21, 0.7) !important;
            border: 1px solid var(--border-main);
            border-radius: 14px;
        }

        .keyHeader {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .keyHeader h3 {
            margin: 0;
        }

        .smallButton {
            min-height: 34px;
            padding: 8px 11px;
            font-size: 0.86rem;
            border-radius: 9px;
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
        }

        .notice {
            margin-top: 16px;
            padding: 12px 14px;
            border: 1px solid rgba(36, 149, 255, 0.35);
            border-radius: 8px;
            background: rgba(36, 149, 255, 0.08);
            line-height: 1.45;
        }

        details {
            background: var(--panel-bg-soft);
            border: 1px solid var(--border-main);
            border-radius: 14px;
            margin-bottom: 14px;
            overflow: hidden;
        }

        summary {
            cursor: pointer;
            padding: 16px 18px;
            color: var(--text-main);
            font-weight: 800;
        }

        .detailsBody {
            border-top: 1px solid var(--border-main);
            padding: 17px;
        }

        #keyContainer,
        #storedKeyContainer {
            display: none;
        }

        #deleteStoredKeyBtn {
            display: none;
        }

        footer {
            color: var(--text-muted);
            text-align: center;
            font-size: 0.86rem;
            padding: 6px 0 0;
        }

        .versionText {
            margin-top: 8px;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .mailSetupNote {
            margin: 8px 0 18px;
            color: var(--text-muted);
            line-height: 1.48
        }

        .mailSetupNote h3 {
            margin: 12px 0 6px;
            color: var(--text-soft)
        }

        .mailSetupNote ol {
            margin: 8px 0 0 22px;
            padding: 0
        }

        .mailModalOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .72);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 18px
        }

        .mailModal {
            width: min(560px, 100%);
            background: var(--panel-bg);
            border: 1px solid var(--border-strong);
            border-radius: 16px;
            padding: 22px;
            box-shadow: var(--shadow-main)
        }

        .mailModal p {
            color: var(--text-muted);
            line-height: 1.45
        }

        .mailModalActions {
            display: flex;
            gap: 10px;
            margin-top: 14px
        }

        #simpleModalMessage {
            white-space: pre-wrap;
            user-select: text;
        }

        .mailModal input,
        .mailModal input:-webkit-autofill,
        .mailModal input:-webkit-autofill:hover,
        .mailModal input:-webkit-autofill:focus,
        .mailModal input:-webkit-autofill:active {
            background-color: #080d15 !important;
            color: var(--text-main) !important;
            -webkit-text-fill-color: var(--text-main) !important;
            box-shadow: 0 0 0 1000px #080d15 inset !important;
            caret-color: var(--text-main) !important;
        }

        .mailModal input:focus {
            border-color: rgba(37, 99, 235, 0.82) !important;
            box-shadow:
                0 0 0 1000px #080d15 inset,
                0 0 0 4px var(--focus-ring) !important;
        }

        @media (max-width: 560px) {
            .headerInner {
                display: block;
            }

            .headerLink {
                margin-top: 14px;
                width: 100%;
            }

            .buttonRow {
                display: grid;
                grid-template-columns: 1fr;
            }

            button,
            .buttonLink {
                width: 100%;
            }

            .keyHeader {
                display: block;
            }

            .keyHeader button {
                margin-top: 8px;
            }
        }
    </style>
</head>

<body>
    <header class="topHeader">
        <div class="headerInner">
            <div class="brandBlock">
                <p class="eyebrow">bwAuthSystem</p>
                <h1>Device Signatures <span
                        class="versionText">v<?php echo htmlspecialchars(SIGNATURES_VERSION); ?></span></h1>
            </div>
            <a class="headerLink" href="index.php">Admin Login</a>
        </div>
    </header>

    <main class="pageShell">
        <section class="panel primaryPanel">
            <h2>Create Device Key</h2>
            <p class="helpText">
                Name this browser/device, generate a key pair, then copy the public key for server approval.
            </p>

            <label for="keyName">Device Name</label>
            <input type="text" id="keyName" placeholder="Example: Brian-Laptop-Chrome">

            <div class="buttonRow">
                <button type="button" onclick="generateKeys()">Create Key Pair</button>

                <button id="deleteStoredKeyBtn" type="button" class="dangerButton" onclick="deleteStoredKey()">Delete
                    Installed Key</button>
            </div>

            <div class="notice">
                The private key stays in this browser. Only the public key should be added to DEVICE_KEYS.
            </div>
        </section>

        <div class="mailSetupNote">
            <p><strong>*Note:</strong> Device keys must be added to the env file manually unless email is properly set
                up. With email, an authorized user using a browser with their key installed can provision a new user
                with the click of a button.</p>
            <h3>Gmail app password setup</h3>
            <ol>
                <li>Enable 2-Step Verification on the sender Gmail account.</li>
                <li>Open Google Account security settings.</li>
                <li>Create an App Password for mail.</li>
                <li>Use that app password here, not the normal Gmail password.</li>
            </ol>
        </div>

        <section class="panel" id="keyContainer">
            <h2>Generated Key Pair</h2>
            <p class="helpText">
                Copy the public key entry for the server. Copy the private key only if you need a backup.
            </p>

            <div class="keyBlock">
                <div class="keyHeader">
                    <h3>Public Key</h3>
                    <button type="button" class="smallButton secondaryButton" onclick="copyGeneratedPublicKey()">Copy
                        Public Key</button>
                </div>
                <pre id="publicKey"></pre>
            </div>

            <div class="keyBlock">
                <div class="keyHeader">
                    <h3>Private Key</h3>
                    <button type="button" class="smallButton secondaryButton" onclick="copyGeneratedPrivateKey()">Copy
                        Private Key</button>
                </div>
                <pre id="privateKey"></pre>
            </div>

            <div class="buttonRow">
                <button type="button" onclick="copyGeneratedDeviceKeysForEnv()">Copy DEVICE_KEYS Entry</button>
                <button type="button" class="secondaryButton" onclick="sendApprovalRequest()">Send Approval
                    Request</button>
            </div>
        </section>

        <details>
            <summary>Installed Key</summary>
            <div class="detailsBody">
                <p class="helpText">
                    View the key data currently stored in this browser.
                </p>

                <div class="buttonRow">
                    <button type="button" class="secondaryButton" onclick="showStoredKey()">Show Installed Key
                        Data</button>
                </div>

                <div id="storedKeyContainer">
                    <div class="keyBlock">
                        <div class="keyHeader">
                            <h3>Stored Device Name</h3>
                            <button type="button" class="smallButton secondaryButton"
                                onclick="copyStoredDeviceName()">Copy Name</button>
                        </div>
                        <pre id="storedKeyName"></pre>
                    </div>

                    <div class="keyBlock">
                        <div class="keyHeader">
                            <h3>Stored Public Key</h3>
                            <button type="button" class="smallButton secondaryButton"
                                onclick="copyStoredPublicKey()">Copy Public Key</button>
                        </div>
                        <pre id="storedPublicKey"></pre>
                    </div>

                    <div class="keyBlock">
                        <div class="keyHeader">
                            <h3>Stored Private Key</h3>
                            <button type="button" class="smallButton secondaryButton"
                                onclick="copyStoredPrivateKey()">Copy Private Key</button>
                        </div>
                        <pre id="storedPrivateKey"></pre>
                    </div>

                    <div class="buttonRow">
                        <button type="button" onclick="copyStoredDeviceKeysForEnv()">Copy DEVICE_KEYS Entry</button>
                        <button type="button" class="secondaryButton" onclick="sendApprovalRequestFromStoredKey()">Send
                            Approval Request From Installed Key</button>
                    </div>
                </div>
            </div>
        </details>

        <details>
            <summary>Install Existing Key</summary>
            <div class="detailsBody">
                <p class="helpText">
                    Paste an existing private key to install it in this browser. Public key is optional but recommended.
                </p>

                <label for="deviceKey">Private Key</label>
                <textarea id="deviceKey"></textarea>

                <label for="devicePublicKey">Public Key</label>
                <textarea id="devicePublicKey"
                    placeholder="Optional, but needed if you want this page to show/copy the public key later."></textarea>

                <div class="buttonRow">
                    <button type="button" onclick="saveDeviceKey()">Install Key Data</button>
                </div>
            </div>
        </details>

    </main>

    <div id="mailModalOverlay" class="mailModalOverlay">
        <div class="mailModal">
            <h2>Email Approval Setup</h2>
            <p>For Gmail, use a Google App Password, not your normal Gmail password. Enable 2-Step Verification, then
                create an App Password in Google Account security settings.</p>

            <label for="mailRecipient">Your Email</label>
            <input id="mailRecipient" type="email">

            <label for="mailSender">Admin Gmail Address</label>
            <input id="mailSender" type="email">

            <label for="mailPassword">Admin Gmail App Password</label>
            <input id="mailPassword" type="password">

            <div class="mailModalActions">
                <button type="button" onclick="submitMailApprovalModal()">Send Approval Email</button>
                <button type="button" class="secondaryButton" onclick="closeMailApprovalModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div id="simpleModalOverlay" class="mailModalOverlay">
        <div class="mailModal">
            <h2 id="simpleModalTitle"></h2>
            <p id="simpleModalMessage"></p>
            <div class="mailModalActions" id="simpleModalActions"></div>
        </div>
    </div>

    <script>
        let simpleModalConfirmCallback = null;

        function showSimpleModal(title, message) {
            simpleModalConfirmCallback = null;
            document.getElementById("simpleModalTitle").textContent = title;
            document.getElementById("simpleModalMessage").textContent = message;
            document.getElementById("simpleModalActions").innerHTML = '<button type="button" onclick="closeSimpleModal()">OK</button>';
            document.getElementById("simpleModalOverlay").style.display = "flex";
        }

        function showConfirmModal(title, message, callback) {
            simpleModalConfirmCallback = callback;
            document.getElementById("simpleModalTitle").textContent = title;
            document.getElementById("simpleModalMessage").textContent = message;
            document.getElementById("simpleModalActions").innerHTML = '<button type="button" onclick="confirmSimpleModal()">Yes</button><button type="button" class="secondaryButton" onclick="closeSimpleModal()">Cancel</button>';
            document.getElementById("simpleModalOverlay").style.display = "flex";
        }

        function closeSimpleModal() {
            document.getElementById("simpleModalOverlay").style.display = "none";
        }

        async function confirmSimpleModal() {
            closeSimpleModal();
            if (simpleModalConfirmCallback) await simpleModalConfirmCallback();
        }

        const KEY_DB_NAME = "BWCSAuthDB";
        const KEY_STORE_NAME = "privateKeys";

        let authDbRepairLogged = false;

        function openKeyDb() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(KEY_DB_NAME, 1);

                request.onupgradeneeded = function (event) {
                    const db = event.target.result;

                    if (!db.objectStoreNames.contains(KEY_STORE_NAME)) {
                        db.createObjectStore(KEY_STORE_NAME);
                    }
                };

                request.onsuccess = function () {
                    const db = request.result;

                    if (db.objectStoreNames.contains(KEY_STORE_NAME)) {
                        resolve(db);
                        return;
                    }

                    db.close();

                    if (!authDbRepairLogged) {
                        console.log("Auth database was missing the key store. Rebuilding it now.");
                        authDbRepairLogged = true;
                    }

                    const deleteRequest = indexedDB.deleteDatabase(KEY_DB_NAME);

                    deleteRequest.onsuccess = function () {
                        const rebuildRequest = indexedDB.open(KEY_DB_NAME, 1);

                        rebuildRequest.onupgradeneeded = function (event) {
                            const rebuiltDb = event.target.result;

                            if (!rebuiltDb.objectStoreNames.contains(KEY_STORE_NAME)) {
                                rebuiltDb.createObjectStore(KEY_STORE_NAME);
                            }
                        };

                        rebuildRequest.onsuccess = function () {
                            const rebuiltDb = rebuildRequest.result;

                            if (!rebuiltDb.objectStoreNames.contains(KEY_STORE_NAME)) {
                                rebuiltDb.close();
                                reject(new Error("Auth database repair failed. Key store is still missing."));
                                return;
                            }

                            resolve(rebuiltDb);
                        };

                        rebuildRequest.onerror = function () {
                            reject(rebuildRequest.error);
                        };
                    };

                    deleteRequest.onerror = function () {
                        reject(deleteRequest.error);
                    };

                    deleteRequest.onblocked = function () {
                        reject(new Error("Auth database repair was blocked. Close other auth tabs and reload."));
                    };
                };

                request.onerror = function () {
                    reject(request.error);
                };
            });
        }

        async function saveKeyDataToDb(privateKey, keyName, publicKey = "") {
            const db = await openKeyDb();

            return new Promise((resolve, reject) => {
                const transaction = db.transaction([KEY_STORE_NAME], "readwrite");
                const store = transaction.objectStore(KEY_STORE_NAME);

                store.put(privateKey, "installedPrivateKey");
                store.put(keyName, "privateKeyName");

                if (publicKey) {
                    store.put(publicKey, "installedPublicKey");
                }

                transaction.oncomplete = function () {
                    resolve(true);
                };

                transaction.onerror = function () {
                    reject(transaction.error);
                };
            });
        }

        async function getKeyDataFromDb() {
            const db = await openKeyDb();

            return new Promise((resolve, reject) => {
                const transaction = db.transaction([KEY_STORE_NAME], "readonly");
                const store = transaction.objectStore(KEY_STORE_NAME);

                const privateKeyRequest = store.get("installedPrivateKey");
                const publicKeyRequest = store.get("installedPublicKey");
                const nameRequest = store.get("privateKeyName");

                transaction.oncomplete = function () {
                    resolve({
                        privateKey: privateKeyRequest.result || null,
                        publicKey: publicKeyRequest.result || null,
                        keyName: nameRequest.result || null
                    });
                };

                transaction.onerror = function () {
                    reject(transaction.error);
                };
            });
        }

        async function deleteKeyDataFromDb() {
            const db = await openKeyDb();

            return new Promise((resolve, reject) => {
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
        }

        function formatKey(keyBuffer, keyType) {
            const keyBase64 = btoa(String.fromCharCode(...new Uint8Array(keyBuffer)));
            return `-----BEGIN ${keyType}-----\n${keyBase64.match(/.{1,64}/g).join("\n")}\n-----END ${keyType}-----`;
        }

        function publicKeyForEnv(publicKey) {
            return publicKey.trim().replace(/\r\n/g, "\n").replace(/\n/g, "\\n");
        }

        function buildDeviceKeysEntry(keyName, publicKey) {
            const formattedPublicKey = publicKeyForEnv(publicKey);

            return `"${keyName}": "${formattedPublicKey}"`;
        }

        async function copyTextToClipboard(text) {
            if (!text) {
                alert("Nothing to copy.");
                return;
            }

            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return;
            }

            const textarea = document.createElement("textarea");
            textarea.value = text;
            textarea.style.position = "fixed";
            textarea.style.left = "-9999px";
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            document.execCommand("copy");
            textarea.remove();
        }

        async function generateKeys() {
            try {
                const keyName = document.getElementById("keyName").value.trim();

                if (!keyName) {
                    showSimpleModal("Missing Device Name", "Enter a name for this device/key first.");
                    return;
                }

                if (!window.crypto || !window.crypto.subtle) {
                    showSimpleModal("Crypto Not Available", "Web Crypto API is not available. Use HTTPS and a supported browser.");
                    return;
                }

                const keyPair = await window.crypto.subtle.generateKey(
                    {
                        name: "RSASSA-PKCS1-v1_5",
                        modulusLength: 2048,
                        publicExponent: new Uint8Array([1, 0, 1]),
                        hash: "SHA-256"
                    },
                    true,
                    ["sign", "verify"]
                );

                const privateKey = await window.crypto.subtle.exportKey("pkcs8", keyPair.privateKey);
                const publicKey = await window.crypto.subtle.exportKey("spki", keyPair.publicKey);

                const privatePem = formatKey(privateKey, "PRIVATE KEY");
                const publicPem = formatKey(publicKey, "PUBLIC KEY");

                document.getElementById("privateKey").textContent = privatePem;
                document.getElementById("publicKey").textContent = publicPem;
                document.getElementById("keyContainer").style.display = "block";

                showConfirmModal(
                    "Key Generated",
                    "Install this key pair in this browser?",
                    async () => {
                        await installGeneratedKey(privatePem, publicPem, keyName);
                    }
                );
            } catch (error) {
                console.error("generateKeys error:", error);
                showSimpleModal("Key Generation Failed", error.message);
            }
        }

        async function installGeneratedKey(privateKey, publicKey, keyName) {
            const existingData = await getKeyDataFromDb();

            if (existingData.privateKey) {
                showConfirmModal(
                    "Overwrite Installed Key?",
                    "A private key is already installed. Overwrite it?",
                    async () => {
                        await finishInstallGeneratedKey(privateKey, publicKey, keyName);
                    }
                );
                return;
            }

            await finishInstallGeneratedKey(privateKey, publicKey, keyName);
        }

        async function finishInstallGeneratedKey(privateKey, publicKey, keyName) {
            await saveKeyDataToDb(privateKey, keyName, publicKey);
            localStorage.setItem("privateKeyName", keyName);

            await checkStoredKey();

            showConfirmModal(
                "Key Installed",
                `Key pair installed as "${keyName}". Send an approval request for this public key now?`,
                async () => {
                    await sendApprovalRequestFromStoredKey();
                }
            );
        }

        async function saveDeviceKey() {
            const privateKey = document.getElementById("deviceKey").value.trim();
            const publicKey = document.getElementById("devicePublicKey").value.trim();
            const keyNameInput = document.getElementById("keyName").value.trim();

            if (!privateKey) {
                alert("Please enter a private key before installing.");
                return;
            }

            if (!privateKey.startsWith("-----BEGIN PRIVATE KEY-----")) {
                alert("Invalid private key format. Paste the full private key.");
                return;
            }

            if (publicKey && !publicKey.startsWith("-----BEGIN PUBLIC KEY-----")) {
                alert("Invalid public key format. Paste the full public key.");
                return;
            }

            if (!keyNameInput) {
                alert("Please enter a name for this key.");
                return;
            }

            const existingData = await getKeyDataFromDb();

            if (existingData.privateKey && !confirm("A private key is already installed. Overwrite it?")) {
                return;
            }

            await saveKeyDataToDb(privateKey, keyNameInput, publicKey);
            localStorage.setItem("privateKeyName", keyNameInput);

            alert(`Key data installed for "${keyNameInput}".`);
            await checkStoredKey();

            if (publicKey && confirm("Send an approval request for this public key now?")) {
                await sendApprovalRequestFromStoredKey();
            }
        }

        async function checkStoredKey() {
            const storedData = await getKeyDataFromDb();
            const storedKey = storedData.privateKey;
            const privateKeyName = storedData.keyName || localStorage.getItem("privateKeyName");

            if (privateKeyName) {
                document.getElementById("keyName").value = privateKeyName;
            }

            document.getElementById("deleteStoredKeyBtn").style.display = storedKey ? "inline-flex" : "none";

            if (!storedKey) {
                document.getElementById("storedKeyContainer").style.display = "none";
            }
        }

        async function showStoredKey() {
            const storedData = await getKeyDataFromDb();

            if (storedData.privateKey || storedData.publicKey || storedData.keyName) {
                document.getElementById("storedKeyName").textContent =
                    storedData.keyName || localStorage.getItem("privateKeyName") || "No stored device name found.";

                document.getElementById("storedPublicKey").textContent =
                    storedData.publicKey || "No stored public key found. Generate a new key pair or paste the public key in the install form.";

                document.getElementById("storedPrivateKey").textContent =
                    storedData.privateKey || "No stored private key found.";

                document.getElementById("storedKeyContainer").style.display = "block";
            } else {
                alert("No stored key data found.");
            }
        }

        async function deleteStoredKey() {
            if (!confirm("Delete the key data stored in this browser?")) {
                return;
            }

            await deleteKeyDataFromDb();
            localStorage.removeItem("privateKeyName");

            document.getElementById("storedKeyContainer").style.display = "none";
            document.getElementById("storedKeyName").textContent = "";
            document.getElementById("storedPublicKey").textContent = "";
            document.getElementById("storedPrivateKey").textContent = "";

            alert("Stored key data deleted.");
            await checkStoredKey();
        }

        async function sendApprovalRequest() {
            const keyName = document.getElementById("keyName").value.trim();
            const publicKey = document.getElementById("publicKey").textContent.trim();

            if (!keyName || !publicKey) {
                alert("No public key or key name found. Generate a key first.");
                return;
            }

            await sendApprovalRequestWithKey(keyName, publicKey);
        }

        async function sendApprovalRequestFromStoredKey() {
            const storedData = await getKeyDataFromDb();
            const keyName = storedData.keyName || localStorage.getItem("privateKeyName");
            const publicKey = storedData.publicKey;

            if (!keyName || !publicKey) {
                alert("No stored public key or key name found. Generate a new key pair or paste the public key in the install form.");
                return;
            }

            await sendApprovalRequestWithKey(keyName, publicKey);
        }

        let pendingApprovalKeyName = "";
        let pendingApprovalPublicKey = "";

        async function sendApprovalRequestWithKey(keyName, publicKey) {
            pendingApprovalKeyName = keyName;
            pendingApprovalPublicKey = publicKey;
            document.getElementById("mailModalOverlay").style.display = "flex";
        }

        function closeMailApprovalModal() {
            document.getElementById("mailModalOverlay").style.display = "none";
        }

        async function submitMailApprovalModal() {
            const approvalEmail = document.getElementById("mailRecipient").value.trim();
            const smtpUsername = document.getElementById("mailSender").value.trim();
            const smtpPassword = document.getElementById("mailPassword").value.trim();

            const body = new URLSearchParams();
            body.append("key_name", pendingApprovalKeyName);
            body.append("public_key", pendingApprovalPublicKey);
            body.append("approval_email", approvalEmail);
            body.append("smtp_username", smtpUsername);
            body.append("smtp_password", smtpPassword);

            const response = await fetch("api/sendApprovalEmail.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body
            });

            const rawText = await response.text();
            let result;

            try {
                result = JSON.parse(rawText);
            } catch {
                showSimpleModal("Approval Email Failed", rawText);
                return;
            }

            showSimpleModal(
                result.status === "success" ? "Approval Email Sent" : "Approval Email Failed",
                "Status: " + (result.status || "") +
                "\nMessage: " + (result.message || "") +
                "\nDiagnostic: " + (result.diagnostic || "") +
                (result.approval_email ? "\nRecipient: " + result.approval_email : "") +
                (result.smtp_host ? "\nSMTP Host: " + result.smtp_host : "") +
                (result.smtp_port ? "\nSMTP Port: " + result.smtp_port : "")
            );

            if (result.status === "success") {
                closeMailApprovalModal();
            }
        }

        async function copyGeneratedPublicKey() {
            await copyTextToClipboard(document.getElementById("publicKey").textContent.trim());
            alert("Public key copied.");
        }

        async function copyGeneratedPrivateKey() {
            await copyTextToClipboard(document.getElementById("privateKey").textContent.trim());
            alert("Private key copied.");
        }

        async function copyStoredDeviceName() {
            await copyTextToClipboard(document.getElementById("storedKeyName").textContent.trim());
            alert("Stored device name copied.");
        }

        async function copyStoredPublicKey() {
            await copyTextToClipboard(document.getElementById("storedPublicKey").textContent.trim());
            alert("Stored public key copied.");
        }

        async function copyStoredPrivateKey() {
            await copyTextToClipboard(document.getElementById("storedPrivateKey").textContent.trim());
            alert("Stored private key copied.");
        }

        async function copyGeneratedDeviceKeysForEnv() {
            const keyName = document.getElementById("keyName").value.trim();
            const publicKey = document.getElementById("publicKey").textContent.trim();

            if (!keyName || !publicKey) {
                alert("No generated public key found.");
                return;
            }

            await copyTextToClipboard(buildDeviceKeysEntry(keyName, publicKey));
            alert("DEVICE_KEYS entry copied.");
        }

        async function copyStoredDeviceKeysForEnv() {
            const storedData = await getKeyDataFromDb();
            const keyName = storedData.keyName || localStorage.getItem("privateKeyName");
            const publicKey = storedData.publicKey;

            if (!keyName || !publicKey) {
                alert("No stored public key found.");
                return;
            }

            await copyTextToClipboard(buildDeviceKeysEntry(keyName, publicKey));
            alert("DEVICE_KEYS entry copied.");
        }

        document.addEventListener("DOMContentLoaded", async () => {
            try {
                await checkStoredKey();
            } catch (error) {
                console.log("Stored key check could not complete. The key database may need to be rebuilt by creating or installing a key.");
            }
        });
    </script>
</body>

</html>