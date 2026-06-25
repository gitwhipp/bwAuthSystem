<?php define('AUTH_SYSTEM_VERSION', '1.0.0167'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>bwAuthSystem Crypto Demo</title>
    <link rel="icon" type="image/png" href="images/authSystemIcon.png">
    <style>
        :root {
            --panel: rgba(8, 13, 21, .92);
            --text: #e8f1ff;
            --muted: #9cadc3;
            --border: rgba(67, 151, 255, .34);
            --blue: #2495ff;
            --ok: #32c46c;
            --bad: #ff6470;
            --shadow: 0 18px 50px rgba(0, 0, 0, .45)
        }

        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            background: radial-gradient(circle at 50% 100%, rgba(28, 96, 160, .22), transparent 42%), linear-gradient(180deg, #05070b 0%, #080d15 52%, #040609 100%)
        }

        .authHeader {
            padding: 10px clamp(16px, 4vw, 32px);
            border-bottom: 1px solid var(--border);
            background: #040a12
        }

        .headerLine {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1180px;
            margin: 0 auto
        }

        h1 {
            margin: 0;
            font-size: clamp(1.15rem, 3vw, 1.65rem);
            letter-spacing: -.035em
        }

        .versionText {
            font-size: .82rem
        }

        h1 span,
        .versionText {
            color: var(--blue)
        }

        main {
            width: min(1180px, calc(100% - 28px));
            margin: 0 auto;
            padding: 12px 0 28px
        }

        .panel {
            padding: 12px clamp(12px, 3vw, 20px);
            border: 1px solid var(--border);
            background: var(--panel);
            box-shadow: var(--shadow)
        }

        .colBox,
        .equationBox {
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, .13);
            background: rgba(0, 0, 0, .14)
        }

        label {
            display: block;
            margin: 8px 0 5px;
            font-weight: 700
        }

        input,
        textarea,
        select {
            width: 100%;
            min-height: 40px;
            padding: 8px 10px;
            border: 1px solid rgba(36, 149, 255, .45);
            background: rgba(0, 0, 0, .28);
            color: var(--text);
            font-size: .95rem
        }

        textarea {
            min-height: 86px;
            font-family: Consolas, Monaco, "Courier New", monospace
        }

        .threeCols {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px
        }

        .equationBox {
            margin-top: 10px
        }

        button {
            margin-top: 12px;
            min-height: 42px;
            padding: 9px 14px;
            border: 0;
            background: linear-gradient(180deg, #2aa3ff, #126fd1);
            color: #fff;
            font-weight: 700;
            cursor: pointer
        }

        pre {
            min-height: 40px;
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, .13);
            background: rgba(0, 0, 0, .28);
            color: var(--text)
        }

        .match {
            margin-top: 10px;
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center
        }

        .good {
            border-color: rgba(50, 196, 108, .7);
            color: var(--ok)
        }

        .bad {
            border-color: rgba(255, 100, 112, .7);
            color: var(--bad)
        }

        .hidden {
            display: none
        }

        button.flashClick {
            animation: flashClick .38s ease;
        }

        @keyframes flashClick {
            0% {
                opacity: 1;
                transform: scale(1);
            }

            45% {
                opacity: .45;
                transform: scale(.98);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @media(max-width:900px) {

            .threeCols,
            .headerLine {
                display: block
            }
        }
    </style>
</head>

<body>
    <header class="authHeader">
        <div class="headerLine">
            <h1><span>bwAuthSystem</span> Crypto Demo</h1>
            <div class="versionText">v
                <?php echo htmlspecialchars(AUTH_SYSTEM_VERSION); ?>
            </div>
        </div>
    </header>

    <main>
        <section class="panel">

            <div class="threeCols">
                <div class="colBox">
                    <label for="challengeInput">Challenge</label>
                    <textarea id="challengeInput"></textarea>
                </div>

                <div class="colBox">
                    <label for="publicKeyInput">Public Key / Public Number</label>
                    <textarea id="publicKeyInput"></textarea>
                </div>

                <div class="colBox">
                    <label for="privateKeyInput">Private Key / Private Number</label>
                    <textarea id="privateKeyInput"></textarea>
                </div>
            </div>

            <div class="equationBox">
                <label for="equationMode">Shared Equation</label>
                <select id="equationMode" onchange="handleEquationChange()">
                    <option value="">Select equation</option>
                    <option value="wheel">Cipher wheel: challenge ^ privateKey, then result ^ publicKey</option>
                    <option value="realRsa">Real RSA: private key signs challenge, public key verifies</option>
                </select>

                <div id="wheelSizeWrap" class="hidden">
                    <label for="wheelSizeInput">Cipher Wheel Size</label>
                    <input id="wheelSizeInput" value="33">
                </div>

                <div id="equationDisplayWrap" class="hidden">
                    <label>Equation Display</label>
                    <pre id="equationOutput"></pre>
                </div>
            </div>

            <button id="runMatchButton" onclick="flashRunButton(); runDemo()">Run Match Test</button>
            <pre id="matchOutput" class="match hidden"></pre>

        </section>
    </main>

    <script>
        function flashRunButton() {
            const btn = document.getElementById("runMatchButton");
            btn.classList.remove("flashClick");
            void btn.offsetWidth;
            btn.classList.add("flashClick");
        }
        function n(v, name) { const x = parseInt(String(v).trim(), 10); if (Number.isNaN(x)) throw new Error(name + " must be a number."); return x }
        function wheel(v, size) { let x = v % size; if (x < 0) x += size; return x }
        function modPow(base, exp, mod) { let r = 1n, b = BigInt(base) % BigInt(mod), e = BigInt(exp), m = BigInt(mod); while (e > 0n) { if (e % 2n === 1n) r = (r * b) % m; b = (b * b) % m; e = e / 2n } return Number(r) }
        function b64ToBuf(b64) { const bin = atob(b64); const bytes = new Uint8Array(bin.length); for (let i = 0; i < bin.length; i++)bytes[i] = bin.charCodeAt(i); return bytes.buffer }
        function pemToBuf(pem) { const clean = pem.replace(/-----BEGIN .*?-----/g, "").replace(/-----END .*?-----/g, "").replace(/\s+/g, ""); return b64ToBuf(clean) }

        function handleEquationChange() {
            wheelSizeWrap.classList.toggle("hidden", equationMode.value !== "wheel");
            equationDisplayWrap.classList.add("hidden");
            matchOutput.classList.add("hidden");
            equationOutput.textContent = "";
        }

        async function runDemo() {
            matchOutput.classList.remove("good", "bad", "hidden");

            try {
                const challenge = challengeInput.value.trim();
                const pub = publicKeyInput.value.trim();
                const priv = privateKeyInput.value.trim();
                const mode = equationMode.value;

                if (!challenge || !pub || !priv) throw new Error("Challenge, public value, and private value are required.");
                if (!mode) throw new Error("Select an equation.");

                let matched = false;

                if (mode === "wheel") {
                    const size = n(wheelSizeInput.value, "Cipher wheel size");
                    if (size < 2) throw new Error("Cipher wheel size must be at least 2.");

                    const c = wheel(n(challenge, "Challenge"), size);
                    const pubKey = n(pub, "Public key");
                    const privKey = n(priv, "Private key");

                    const privateResult = modPow(c, privKey, size);
                    const publicResult = modPow(privateResult, pubKey, size);

                    matched = publicResult === c;

                    equationOutput.textContent =
                        "Private side: " + c + " ^ " + privKey + " mod " + size + " = " + privateResult + "\n" +
                        "Public side: " + privateResult + " ^ " + pubKey + " mod " + size + " = " + publicResult + "\n" +
                        "Match checks whether " + publicResult + " equals original challenge " + c + ".";
                }

                if (mode === "realRsa") {
                    const data = new TextEncoder().encode(challenge);

                    const privateKey = await crypto.subtle.importKey(
                        "pkcs8",
                        pemToBuf(priv),
                        { name: "RSASSA-PKCS1-v1_5", hash: "SHA-256" },
                        false,
                        ["sign"]
                    );

                    const publicKey = await crypto.subtle.importKey(
                        "spki",
                        pemToBuf(pub),
                        { name: "RSASSA-PKCS1-v1_5", hash: "SHA-256" },
                        false,
                        ["verify"]
                    );

                    const sig = await crypto.subtle.sign("RSASSA-PKCS1-v1_5", privateKey, data);
                    matched = await crypto.subtle.verify("RSASSA-PKCS1-v1_5", publicKey, sig, data);

                    equationOutput.textContent =
                        "Private side: challenge × private key = signature\n" +
                        "Public side: challenge × signature × public key = verified / rejected";
                }

                equationDisplayWrap.classList.remove("hidden");
                matchOutput.textContent = matched ? "MATCH" : "NO MATCH";
                matchOutput.classList.add(matched ? "good" : "bad");

            } catch (e) {
                equationDisplayWrap.classList.add("hidden");

                const msg = e.message && e.message.trim()
                    ? e.message
                    : String(e.name || "Key import or verification failed.");

                matchOutput.textContent = "ERROR: " + msg;
                matchOutput.classList.add("bad");
            }
        }
        runMatchButton.addEventListener("animationend", () => {
            runMatchButton.classList.remove("flashClick");
        });
    </script>
</body>

</html>