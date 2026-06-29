// authSystem_passwordUtils_clean.js
// Clean standalone auth helper.
// Core: IndexedDB private key storage, challenge signing, login, refresh, token validation, and small UI helpers.
const BW_AUTH_DEFAULT_BASE_PATH = "/auth";

let bwAuthPackageConfig = null;

async function loadBwAuthPackageConfig() {
  if (bwAuthPackageConfig) return bwAuthPackageConfig;

  const defaultConfig = {
    basePath: BW_AUTH_DEFAULT_BASE_PATH,
  };

  try {
    const res = await fetch(
      BW_AUTH_DEFAULT_BASE_PATH + "/config.json?v=" + Date.now(),
    );

    if (res.ok) {
      const config = await res.json();

      bwAuthPackageConfig = {
        basePath: config.basePath || BW_AUTH_DEFAULT_BASE_PATH,
      };

      console.log("Auth config loaded:", bwAuthPackageConfig);
      return bwAuthPackageConfig;
    }

    console.log("Auth config not found. Using default auth config.");
  } catch (e) {
    console.log("Auth config load skipped. Using default auth config.");
  }

  bwAuthPackageConfig = defaultConfig;
  return bwAuthPackageConfig;
}

async function getBwAuthApiUrl(fileName) {
  const config = await loadBwAuthPackageConfig();
  const basePath = String(config.basePath || BW_AUTH_DEFAULT_BASE_PATH).replace(
    /\/$/,
    "",
  );

  return basePath + "/api/" + fileName;
}

console.log("auth utilities loading...");

const BW_AUTH_CONFIG = {
  dbName: "BWCSAuthDB",
  keyStoreName: "privateKeys",
  privateKeyRecordKey: "installedPrivateKey",
  privateKeyNameRecordKey: "privateKeyName",
  accessTokenStorageKey: "cyberEventToken",
  refreshTokenStorageKey: "cyberEventRefreshToken",
  roleStorageKey: "userRole",
  deviceNameStorageKey: "privateKeyName",
  passwordCacheEnabled: true,
  passwordCacheRecordKey: "encryptedCachedPasswordPackage",
  passwordAesKeyRecordKey: "passwordCacheAesKey",
  endpoints: {
    challenge: "getChallenge.php",
    authenticate: "authenticate.php",
    refresh: "refreshToken.php",
    validate: "validateToken.php",
  },
};

window.tokenRecentlyValidated = false;
let refreshInProgress = null;

function authLog(message, details = null) {
  if (details) {
    console.log(message, details);
    return;
  }
  console.log(message);
}

function authWarn(message, details = null) {
  if (details) {
    console.log(message, details);
    return;
  }

  console.log(message);
}

function authError(message, details = null) {
  if (details) {
    console.error(message, details);
    return;
  }
  console.error(message);
}

function openKeyDb() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open(BW_AUTH_CONFIG.dbName, 1);

    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains(BW_AUTH_CONFIG.keyStoreName)) {
        db.createObjectStore(BW_AUTH_CONFIG.keyStoreName);
      }
    };

    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

async function getKeyStoreValue(recordKey) {
  const db = await openKeyDb();

  return new Promise((resolve, reject) => {
    const transaction = db.transaction(
      [BW_AUTH_CONFIG.keyStoreName],
      "readonly",
    );
    const store = transaction.objectStore(BW_AUTH_CONFIG.keyStoreName);
    const request = store.get(recordKey);

    request.onsuccess = () => resolve(request.result || null);
    request.onerror = () => reject(request.error);
  });
}

async function setKeyStoreValue(recordKey, value) {
  const db = await openKeyDb();

  return new Promise((resolve, reject) => {
    const transaction = db.transaction(
      [BW_AUTH_CONFIG.keyStoreName],
      "readwrite",
    );
    const store = transaction.objectStore(BW_AUTH_CONFIG.keyStoreName);

    store.put(value, recordKey);

    transaction.oncomplete = () => resolve(true);
    transaction.onerror = () => reject(transaction.error);
  });
}

async function deleteKeyStoreValue(recordKey) {
  const db = await openKeyDb();

  return new Promise((resolve, reject) => {
    const transaction = db.transaction(
      [BW_AUTH_CONFIG.keyStoreName],
      "readwrite",
    );
    const store = transaction.objectStore(BW_AUTH_CONFIG.keyStoreName);

    store.delete(recordKey);

    transaction.oncomplete = () => resolve(true);
    transaction.onerror = () => reject(transaction.error);
  });
}

async function getPrivateKeyFromDb() {
  authLog("Loading private key record from IndexedDB.");

  const privateKey = await getKeyStoreValue(BW_AUTH_CONFIG.privateKeyRecordKey);
  const keyName = await getKeyStoreValue(
    BW_AUTH_CONFIG.privateKeyNameRecordKey,
  );

  return {
    privateKey: privateKey || null,
    keyName: keyName || null,
  };
}

function arrayBufferToBase64(buffer) {
  const bytes = new Uint8Array(buffer);
  let binary = "";
  bytes.forEach((byte) => {
    binary += String.fromCharCode(byte);
  });
  return btoa(binary);
}

function base64ToArrayBuffer(base64) {
  const binary = atob(base64);
  const bytes = new Uint8Array(binary.length);

  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }

  return bytes.buffer;
}

async function getStoredPasswordCacheKey() {
  return await getKeyStoreValue(BW_AUTH_CONFIG.passwordAesKeyRecordKey);
}

async function savePasswordCacheKey(key) {
  return await setKeyStoreValue(BW_AUTH_CONFIG.passwordAesKeyRecordKey, key);
}

async function getOrCreatePasswordCacheKey() {
  const existingKey = await getStoredPasswordCacheKey();
  if (existingKey) return existingKey;

  const newKey = await window.crypto.subtle.generateKey(
    { name: "AES-GCM", length: 256 },
    false,
    ["encrypt", "decrypt"],
  );

  await savePasswordCacheKey(newKey);
  return newKey;
}

async function encryptPasswordForCache(password) {
  const key = await getOrCreatePasswordCacheKey();
  const iv = window.crypto.getRandomValues(new Uint8Array(12));
  const encodedPassword = new TextEncoder().encode(password);

  const ciphertext = await window.crypto.subtle.encrypt(
    { name: "AES-GCM", iv },
    key,
    encodedPassword,
  );

  return {
    version: 1,
    algorithm: "AES-GCM",
    iv: arrayBufferToBase64(iv.buffer),
    ciphertext: arrayBufferToBase64(ciphertext),
    createdAt: new Date().toISOString(),
  };
}

async function decryptPasswordFromCachePackage(cachePackage) {
  const key = await getOrCreatePasswordCacheKey();
  const iv = new Uint8Array(base64ToArrayBuffer(cachePackage.iv));
  const ciphertext = base64ToArrayBuffer(cachePackage.ciphertext);

  const plaintextBuffer = await window.crypto.subtle.decrypt(
    { name: "AES-GCM", iv },
    key,
    ciphertext,
  );

  return new TextDecoder().decode(plaintextBuffer);
}

async function saveEncryptedPasswordPackage(cachePackage) {
  return await setKeyStoreValue(
    BW_AUTH_CONFIG.passwordCacheRecordKey,
    cachePackage,
  );
}

async function getEncryptedPasswordPackage() {
  return await getKeyStoreValue(BW_AUTH_CONFIG.passwordCacheRecordKey);
}

async function clearEncryptedPasswordCache() {
  return await deleteKeyStoreValue(BW_AUTH_CONFIG.passwordCacheRecordKey);
}

async function cachePassword(password) {
  if (!BW_AUTH_CONFIG.passwordCacheEnabled) return;

  try {
    const encryptedPackage = await encryptPasswordForCache(password);
    await saveEncryptedPasswordPackage(encryptedPackage);
    localStorage.removeItem("cyberEventPassword");
  } catch (error) {
    authError("Failed to encrypt cached password.", error);
  }
}

async function getCachedPassword() {
  if (!BW_AUTH_CONFIG.passwordCacheEnabled) return null;

  try {
    const encryptedPackage = await getEncryptedPasswordPackage();

    if (encryptedPackage) {
      return await decryptPasswordFromCachePackage(encryptedPackage);
    }

    const legacyPassword = localStorage.getItem("cyberEventPassword");

    if (legacyPassword) {
      await cachePassword(legacyPassword);
      localStorage.removeItem("cyberEventPassword");
      return legacyPassword;
    }

    return null;
  } catch (error) {
    authError("Failed to read cached password.", error);
    return null;
  }
}
async function getDeviceName() {
  const storedData = await getPrivateKeyFromDb();
  let deviceName =
    storedData.keyName ||
    localStorage.getItem(BW_AUTH_CONFIG.deviceNameStorageKey) ||
    "";

  if (!deviceName.trim()) {
    deviceName =
      navigator.userAgentData?.platform ||
      navigator.platform ||
      "Device_" + Math.random().toString(36).substring(2, 12);
  }

  localStorage.setItem(BW_AUTH_CONFIG.deviceNameStorageKey, deviceName);
  authLog("Device identifier ready:", {
    exists: !!deviceName,
    length: deviceName.length,
  });
  return deviceName;
}

function getBrowserName() {
  const userAgent = navigator.userAgent;

  if (userAgent.includes("Firefox")) return "Firefox";
  if (userAgent.includes("Chrome") && !userAgent.includes("Chromium"))
    return "Chrome";
  if (userAgent.includes("Chromium")) return "Chromium";
  if (userAgent.includes("Safari") && !userAgent.includes("Chrome"))
    return "Safari";
  if (userAgent.includes("Edge")) return "Edge";
  if (userAgent.includes("Opera") || userAgent.includes("OPR")) return "Opera";

  return "UnknownBrowser";
}

function getUserRole() {
  return localStorage.getItem(BW_AUTH_CONFIG.roleStorageKey) || "Unknown";
}

function getDecodedToken(token) {
  if (!token) return null;

  try {
    const parts = token.split(".");
    if (parts.length !== 3) return null;

    return JSON.parse(atob(parts[1]));
  } catch (error) {
    authError("Failed to decode token.", error);
    return null;
  }
}

function isTokenExpired(token) {
  if (!token) return true;

  try {
    const decodedPayload = getDecodedToken(token);

    if (!decodedPayload || !decodedPayload.exp) {
      localStorage.removeItem(BW_AUTH_CONFIG.accessTokenStorageKey);
      return true;
    }

    return decodedPayload.exp < Math.floor(Date.now() / 1000);
  } catch (error) {
    authWarn("Token decode failed. Clearing stored access token.", error);
    localStorage.removeItem(BW_AUTH_CONFIG.accessTokenStorageKey);
    return true;
  }
}

function extractRoleFromToken(token) {
  try {
    if (!token) {
      authWarn("Role extraction skipped: no token provided.");
      return null;
    }

    const decodedToken = getDecodedToken(token);

    if (decodedToken && decodedToken.role) {
      localStorage.setItem(BW_AUTH_CONFIG.roleStorageKey, decodedToken.role);
      authLog("Role extracted from token:", {
        roleExists: true,
        roleLength: decodedToken.role.length,
      });
      return decodedToken.role;
    }

    authWarn("Role not found in token.");
    return null;
  } catch (error) {
    authError("Role extraction failed.", error);
    return null;
  }
}

async function importPrivateKey(pemKey) {
  const pemHeader = "-----BEGIN PRIVATE KEY-----";
  const pemFooter = "-----END PRIVATE KEY-----";

  authLog("Importing private key:", {
    exists: !!pemKey,
    length: pemKey ? pemKey.length : 0,
  });

  if (!pemKey || !pemKey.includes(pemHeader) || !pemKey.includes(pemFooter)) {
    authError("Invalid PEM private key format.");
    return null;
  }

  const pemContents = pemKey
    .replace(pemHeader, "")
    .replace(pemFooter, "")
    .replace(/\n/g, "")
    .trim();

  try {
    const binaryDer = Uint8Array.from(atob(pemContents), (c) =>
      c.charCodeAt(0),
    );

    return await window.crypto.subtle.importKey(
      "pkcs8",
      binaryDer.buffer,
      {
        name: "RSASSA-PKCS1-v1_5",
        hash: "SHA-256",
      },
      false,
      ["sign"],
    );
  } catch (error) {
    authError("Error importing private key.", error);
    return null;
  }
}

async function fetchChallenge(deviceName) {
  authLog("Fetching server challenge:", {
    deviceExists: !!deviceName,
    deviceLength: deviceName ? deviceName.length : 0,
  });

  try {
    const response = await fetch(
      await getBwAuthApiUrl(BW_AUTH_CONFIG.endpoints.challenge),
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ device: deviceName }),
      },
    );

    const resultText = await response.text();

    authLog("Challenge response received:", {
      ok: response.ok,
      status: response.status,
      bodyExists: !!resultText,
      bodyLength: resultText ? resultText.length : 0,
    });

    let result;
    try {
      result = JSON.parse(resultText);
    } catch (error) {
      authError("Challenge response was not valid JSON.", error);
      return null;
    }

    if (result.status !== "success" || !result.challenge) {
      authError("Failed to get challenge.", {
        messageExists: !!result.message,
      });
      return null;
    }

    return result.challenge;
  } catch (error) {
    authError("Error fetching challenge.", error);
    return null;
  }
}

async function signChallenge(challenge) {
  authLog("Signing challenge:", {
    challengeExists: !!challenge,
    challengeLength: challenge ? challenge.length : 0,
  });

  try {
    const storedData = await getPrivateKeyFromDb();
    const pemKey = storedData.privateKey;

    if (!pemKey) {
      authError("No private key found in IndexedDB.");
      return null;
    }

    const privateKey = await importPrivateKey(pemKey);
    if (!privateKey) {
      authError("Failed to import private key.");
      return null;
    }

    const encodedChallenge = new TextEncoder().encode(challenge);
    const signature = await window.crypto.subtle.sign(
      "RSASSA-PKCS1-v1_5",
      privateKey,
      encodedChallenge,
    );

    const encodedSignature = btoa(
      String.fromCharCode(...new Uint8Array(signature)),
    );

    authLog("Challenge signed:", {
      signatureExists: !!encodedSignature,
      signatureLength: encodedSignature.length,
    });

    return encodedSignature;
  } catch (error) {
    authError("Error signing challenge.", error);
    return null;
  }
}

async function refreshAccessToken() {
  if (refreshInProgress) return await refreshInProgress;

  refreshInProgress = (async () => {
    const deviceName = await getDeviceName();
    const refreshToken = localStorage.getItem(
      BW_AUTH_CONFIG.refreshTokenStorageKey,
    );

    if (!refreshToken) return null;

    const decodedToken = getDecodedToken(refreshToken);
    if (!decodedToken || decodedToken.exp < Math.floor(Date.now() / 1000)) {
      localStorage.removeItem(BW_AUTH_CONFIG.refreshTokenStorageKey);
      return null;
    }

    try {
      const response = await fetch(
        await getBwAuthApiUrl(BW_AUTH_CONFIG.endpoints.refresh),
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ refreshToken, device: deviceName }),
        },
      );

      const rawText = await response.text();
      let result;

      try {
        result = JSON.parse(rawText);
      } catch {
        return null;
      }

      if (!response.ok || result.status !== "success" || !result.accessToken)
        return null;

      localStorage.setItem(
        BW_AUTH_CONFIG.accessTokenStorageKey,
        result.accessToken,
      );

      if (result.refreshToken) {
        const newDecodedToken = getDecodedToken(result.refreshToken);
        if (newDecodedToken && newDecodedToken.exp) {
          localStorage.setItem(
            BW_AUTH_CONFIG.refreshTokenStorageKey,
            result.refreshToken,
          );
        }
      }

      if (result.role)
        localStorage.setItem(BW_AUTH_CONFIG.roleStorageKey, result.role);
      else extractRoleFromToken(result.accessToken);

      return result.accessToken;
    } catch {
      return null;
    } finally {
      refreshInProgress = null;
    }
  })();

  return await refreshInProgress;
}

async function getAccessToken() {
  const accessToken = localStorage.getItem(
    BW_AUTH_CONFIG.accessTokenStorageKey,
  );
  const refreshToken = localStorage.getItem(
    BW_AUTH_CONFIG.refreshTokenStorageKey,
  );

  authLog("Checking access token:", {
    accessTokenExists: !!accessToken,
    accessTokenLength: accessToken ? accessToken.length : 0,
    refreshTokenExists: !!refreshToken,
    refreshTokenLength: refreshToken ? refreshToken.length : 0,
  });

  if (!accessToken) {
    return refreshToken ? await refreshAccessToken() : null;
  }

  if (isTokenExpired(accessToken)) {
    return refreshToken ? await refreshAccessToken() : null;
  }

  return accessToken;
}

async function validateToken(token) {
  try {
    authLog("Validating token:", {
      tokenExists: !!token,
      tokenLength: token ? token.length : 0,
    });

    const response = await fetch(
      await getBwAuthApiUrl(BW_AUTH_CONFIG.endpoints.validate),
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token }),
      },
    );

    const rawText = await response.text();

    authLog("Validate token response received:", {
      ok: response.ok,
      status: response.status,
      bodyExists: !!rawText,
      bodyLength: rawText ? rawText.length : 0,
    });

    let result;

    try {
      result = JSON.parse(rawText);
    } catch (error) {
      authError("Validate token response was not valid JSON.", error);
      return false;
    }

    if (result.status === "success") {
      if (result.role) {
        localStorage.setItem(BW_AUTH_CONFIG.roleStorageKey, result.role);
      } else {
        extractRoleFromToken(token);
      }

      window.tokenRecentlyValidated = true;
      return true;
    }

    authWarn("Token validation failed.", {
      messageExists: !!result?.message,
    });
    return false;
  } catch (error) {
    authError("Token validation request failed.", error);
    return false;
  }
}

async function getPassword() {
  const cachedPassword = await getCachedPassword();
  if (cachedPassword) return cachedPassword;

  const passwordField = document.getElementById("accessPassword");
  const password = passwordField ? passwordField.value.trim() : "";

  if (!password) {
    alert("Please enter your password.");
    throw new Error("No password provided.");
  }

  await cachePassword(password);
  return password;
}

function restoreButton(button) {
  if (button) {
    button.classList.remove("loading-button");
    button.disabled = false;
  }
}

async function submitPassword() {
  authLog("Submitting password for authentication.");

  const submitButton = document.getElementById("passwordSubmit");
  const passwordInput = document.getElementById("accessPassword");
  const password = passwordInput ? passwordInput.value.trim() : "";

  if (!password) {
    restoreButton(submitButton);
    authWarn("No password entered.");
    alert("Please enter a password.");
    return;
  }

  const deviceName = await getDeviceName();
  const challenge = await fetchChallenge(deviceName);

  if (!challenge) {
    restoreButton(submitButton);
    alert("Authentication failed: Unable to fetch server challenge.");
    return;
  }

  const signature = await signChallenge(challenge);

  if (!signature) {
    restoreButton(submitButton);
    const storedData = await getPrivateKeyFromDb();

    alert(
      storedData.privateKey
        ? "Authentication failed: Unable to sign challenge."
        : "Authentication failed: Private key not found. Please install your device key.",
    );
    return;
  }

  const payload = {
    password,
    device: deviceName,
    challenge,
    signature,
  };

  authLog("Authentication payload prepared:", {
    passwordExists: !!password,
    passwordLength: password.length,
    deviceExists: !!deviceName,
    deviceLength: deviceName ? deviceName.length : 0,
    challengeExists: !!challenge,
    challengeLength: challenge.length,
    signatureExists: !!signature,
    signatureLength: signature.length,
  });

  try {
    const response = await fetch(
      await getBwAuthApiUrl(BW_AUTH_CONFIG.endpoints.authenticate),
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      },
    );

    const resultText = (await response.text()).trim();

    authLog("Authentication response received:", {
      ok: response.ok,
      status: response.status,
      bodyExists: !!resultText,
      bodyLength: resultText.length,
    });

    let result;
    try {
      result = JSON.parse(resultText);
    } catch (error) {
      restoreButton(submitButton);
      authError("Authentication response was not valid JSON.", error);
      alert("Authentication error: Server returned malformed JSON.");
      return;
    }

    if (result.status !== "success") {
      restoreButton(submitButton);
      console.log("Authentication failed.", result);

      if (passwordInput) passwordInput.value = "";

      throw new Error(result.message || "Authentication failed.");
    }

    if (!result.accessToken || !result.refreshToken) {
      restoreButton(submitButton);
      authError("Authentication response missing required token data.");
      alert("Authentication error: Missing token data.");
      return;
    }

    await cachePassword(password);

    localStorage.setItem(
      BW_AUTH_CONFIG.accessTokenStorageKey,
      result.accessToken,
    );

    if (result.role) {
      localStorage.setItem(BW_AUTH_CONFIG.roleStorageKey, result.role);
    } else {
      extractRoleFromToken(result.accessToken);
    }

    const decodedRefreshToken = getDecodedToken(result.refreshToken);

    if (decodedRefreshToken && decodedRefreshToken.exp) {
      localStorage.setItem(
        BW_AUTH_CONFIG.refreshTokenStorageKey,
        result.refreshToken,
      );
    } else {
      restoreButton(submitButton);
      authError("Received invalid refresh token.");
      alert("Authentication error: Invalid refresh token.");
      return;
    }

    alert("Authentication successful.");

    await handlePasswordVisibility();

    if (window.location.pathname.includes("fileSystem.php")) {
      initializePage();
    }

    document.dispatchEvent(new CustomEvent("bwcs_login_success"));
  } catch (error) {
    restoreButton(submitButton);
    authError("Authentication request failed.", error);
    alert("An error occurred during authentication.");
  }
}

async function loadValidation() {
  authLog("[loadValidation] Checking authentication on page load.");

  let accessToken = await getAccessToken();

  if (!accessToken) {
    console.log("[loadValidation] No valid token available.");
    await handlePasswordVisibility();
    return false;
  }

  authLog("[loadValidation] Authentication confirmed.");
  return true;
}

async function automateValidationCheck() {
  authLog(
    "[automateValidationCheck] Checking authentication before protected action.",
  );

  const accessToken = await getAccessToken();

  if (!accessToken) {
    authError("Unable to authenticate.");
    await handlePasswordVisibility();
    throw new Error("User is not authenticated.");
  }

  authLog(
    "[automateValidationCheck] User is authenticated. Proceeding with action.",
  );
  return true;
}

async function handlePasswordVisibility() {
  if (document.getElementById("authLoginForm")) {
    await autoFillPassword();
    authLog(
      "/js/passwordUtils.js - Skipping password visibility handling on index login page.",
    );
    return;
  }

  const accessToken = await getAccessToken();

  authLog("Checking password section visibility:", {
    accessTokenExists: !!accessToken,
  });

  const passwordSection = document.getElementById("passwordSection");
  const ids = [
    "passwordLabel",
    "accessPassword",
    "passwordShow",
    "passwordSubmit",
  ];

  if (accessToken && !isTokenExpired(accessToken)) {
    if (passwordSection) passwordSection.style.display = "none";

    ids.forEach((id) => {
      const element = document.getElementById(id);
      if (element) element.style.display = "none";
    });

    return;
  }

  if (passwordSection) passwordSection.style.display = "flex";

  ids.forEach((id) => {
    const element = document.getElementById(id);
    if (element) element.style.display = "flex";
  });

  await autoFillPassword();
}

async function autoFillPassword() {
  const cachedPassword = await getCachedPassword();
  const passwordField = document.getElementById("accessPassword");

  if (cachedPassword && passwordField) {
    passwordField.value = cachedPassword;
  }
}

function togglePasswordVisibility() {
  const passwordField = document.getElementById("accessPassword");
  const toggleButton =
    document.getElementById("passwordShow") ||
    document.getElementById("togglePasswordVisibility");

  if (!passwordField || !toggleButton) return;

  if (passwordField.type === "password") {
    passwordField.type = "text";
    toggleButton.textContent = "Hide";
  } else {
    passwordField.type = "password";
    toggleButton.textContent = "Show";
  }
}

function clearTokens() {
  localStorage.removeItem(BW_AUTH_CONFIG.accessTokenStorageKey);
  localStorage.removeItem(BW_AUTH_CONFIG.refreshTokenStorageKey);
}

function passwordutilslogoutOnly() {
  if (document.getElementById("authLoginForm")) {
    authLog("passwordUtils logoutOnly skipped on index login page.");
    return;
  }

  clearTokens();
  localStorage.removeItem(BW_AUTH_CONFIG.roleStorageKey);
  alert("Logged out.");
  location.reload();
}

async function passwordutilslogoutAndWipeAll() {
  if (document.getElementById("authLoginForm")) {
    authLog("passwordUtils logoutAndWipeAll skipped on index login page.");
    return;
  }
  clearTokens();
  localStorage.removeItem(BW_AUTH_CONFIG.roleStorageKey);
  localStorage.removeItem(BW_AUTH_CONFIG.deviceNameStorageKey);
  localStorage.removeItem("cyberEventPassword");

  await clearEncryptedPasswordCache();
  await deleteKeyStoreValue(BW_AUTH_CONFIG.privateKeyRecordKey);
  await deleteKeyStoreValue(BW_AUTH_CONFIG.privateKeyNameRecordKey);

  alert("Logged out and saved auth data wiped.");
  location.reload();
}

async function clearPasswordCache() {
  if (
    !confirm(
      "Are you sure you want to clear the password cache? This will log you out and remove stored credentials.",
    )
  ) {
    authLog("Cache clear canceled by user.");
    return;
  }

  authLog("Clearing auth cache:", {
    accessTokenExists: !!localStorage.getItem(
      BW_AUTH_CONFIG.accessTokenStorageKey,
    ),
    refreshTokenExists: !!localStorage.getItem(
      BW_AUTH_CONFIG.refreshTokenStorageKey,
    ),
    userRoleExists: !!localStorage.getItem(BW_AUTH_CONFIG.roleStorageKey),
  });

  localStorage.removeItem("cyberEventPassword");
  localStorage.removeItem(BW_AUTH_CONFIG.accessTokenStorageKey);
  localStorage.removeItem(BW_AUTH_CONFIG.refreshTokenStorageKey);
  localStorage.removeItem(BW_AUTH_CONFIG.roleStorageKey);

  await clearEncryptedPasswordCache();

  const passwordSection = document.getElementById("passwordSection");
  if (passwordSection) passwordSection.style.display = "flex";

  ["accessPassword", "passwordLabel", "passwordShow", "passwordSubmit"].forEach(
    (id) => {
      const element = document.getElementById(id);
      if (element) element.style.display = "flex";
    },
  );

  const passwordInput = document.getElementById("accessPassword");
  if (passwordInput) passwordInput.value = "";

  authLog("Auth cache cleared.");
  alert("Password cache is cleared now.");
}

async function checkValidation() {
  const accessToken = localStorage.getItem(
    BW_AUTH_CONFIG.accessTokenStorageKey,
  );
  const refreshToken = localStorage.getItem(
    BW_AUTH_CONFIG.refreshTokenStorageKey,
  );
  const legacyPassword = localStorage.getItem("cyberEventPassword");
  const encryptedPasswordPackage = await getEncryptedPasswordPackage();
  const storedData = await getPrivateKeyFromDb();

  const passwordCacheStatus = encryptedPasswordPackage
    ? "Stored Encrypted"
    : legacyPassword
      ? "Legacy Plaintext Stored"
      : "Not Stored";

  const privateKey = storedData.privateKey;
  const privateKeyName =
    storedData.keyName ||
    localStorage.getItem(BW_AUTH_CONFIG.deviceNameStorageKey) ||
    "None";
  const userRole =
    localStorage.getItem(BW_AUTH_CONFIG.roleStorageKey) || "None";

  const now = Date.now();
  const nowFormatted = new Date(now).toLocaleString();

  let message = `Access Token: ${accessToken ? "Available" : "Not Found"}\n`;
  message += `Refresh Token: ${refreshToken ? "Available" : "Not Found"}\n`;
  message += `Password Cache: ${passwordCacheStatus}\n`;
  message += `Private Key: ${privateKey ? "Stored" : "Not Stored"}\n`;
  message += `Private Key Name: ${privateKeyName}\n`;
  message += `Role: ${userRole}\n\n`;

  message += `Access Token Expiration: ${getTokenExpiration(accessToken)}\n`;
  message += `Refresh Token Expiration: ${getTokenExpiration(refreshToken)}\n`;
  message += `CURRENT TIME: ${nowFormatted}\n`;

  authLog("Auth cache diagnostic:", {
    accessTokenExists: !!accessToken,
    refreshTokenExists: !!refreshToken,
    encryptedPasswordCacheExists: !!encryptedPasswordPackage,
    legacyPasswordExists: !!legacyPassword,
    privateKeyExists: !!privateKey,
    privateKeyNameExists: !!privateKeyName,
    roleExists: !!userRole,
  });

  showMessage(message);
}

function showMessage(message) {
  const modal = document.createElement("div");

  const box = document.createElement("div");
  box.style.position = "fixed";
  box.style.top = "20%";
  box.style.left = "50%";
  box.style.transform = "translate(-50%, -20%)";
  box.style.color = "black";
  box.style.background = "white";
  box.style.padding = "15px";
  box.style.border = "2px solid black";
  box.style.boxShadow = "3px 3px 5px rgba(0, 0, 0, 0.3)";
  box.style.fontFamily = "monospace";
  box.style.zIndex = "10000";
  box.style.width = "700px";
  box.style.maxWidth = "90vw";
  box.style.textAlign = "center";

  const pre = document.createElement("pre");
  pre.style.whiteSpace = "pre-wrap";
  pre.style.userSelect = "text";
  pre.textContent = message;

  const button = document.createElement("button");
  button.style.marginTop = "10px";
  button.textContent = "OK";
  button.onclick = () => modal.remove();

  box.appendChild(pre);
  box.appendChild(button);
  modal.appendChild(box);
  document.body.appendChild(modal);
}

function getTokenExpiration(token) {
  if (!token) return "None";

  const decoded = getDecodedToken(token);
  if (!decoded || !decoded.exp) return "Unknown";

  return new Date(decoded.exp * 1000).toLocaleString();
}

function hidePasswordField() {
  const ids = [
    "passwordSection",
    "passwordLabel",
    "passwordShow",
    "passwordSubmit",
    "accessPassword",
  ];

  ids.forEach((id) => {
    const element = document.getElementById(id);
    if (element) element.style.display = "none";
  });
}

console.log("auth utilities loaded.");
