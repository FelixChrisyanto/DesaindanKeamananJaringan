function vigenereEncrypt(text, key) {
    let result = "";
    key = key.toUpperCase();
    const keyLen = key.length;
    let kIdx = 0;

    for (let i = 0; i < text.length; i++) {
        let char = text[i];
        let kChar = key[kIdx % keyLen];
        let shift = kChar.charCodeAt(0) - 'A'.charCodeAt(0);

        if (/[A-Z]/.test(char)) {
            result += String.fromCharCode(((char.charCodeAt(0) - 'A'.charCodeAt(0) + shift) % 26) + 'A'.charCodeAt(0));
            kIdx++;
        } else if (/[a-z]/.test(char)) {
            result += String.fromCharCode(((char.charCodeAt(0) - 'a'.charCodeAt(0) + shift) % 26) + 'a'.charCodeAt(0));
            kIdx++;
        } else if (/[0-9]/.test(char)) {
            result += String.fromCharCode(((parseInt(char) + shift) % 10) + '0'.charCodeAt(0));
            kIdx++;
        } else {
            result += char;
        }
    }
    return result;
}

// UI Interactions
document.addEventListener('DOMContentLoaded', () => {
    // Live preview for transaction module
    const plaintextInput = document.getElementById('encryption-preview-input');
    const ciphertextDisplay = document.getElementById('encryption-preview-output');
    const activeKey = document.getElementById('active-key-val')?.innerText || "APOTEK";

    if (plaintextInput && ciphertextDisplay) {
        plaintextInput.addEventListener('input', (e) => {
            const val = e.target.value;
            if (val) {
                ciphertextDisplay.innerText = vigenereEncrypt(val, activeKey);
                ciphertextDisplay.classList.remove('hidden');
            } else {
                ciphertextDisplay.innerText = "";
            }
        });
    }

    // Toggle Decryption Visibility
    window.toggleDecryption = (btn, originalValue) => {
        const target = btn.previousElementSibling;
        if (target.dataset.hidden === "true") {
            target.innerText = originalValue;
            target.dataset.hidden = "false";
            btn.innerText = "Sembunyikan";
        } else {
            target.innerText = target.dataset.encrypted;
            target.dataset.hidden = "true";
            btn.innerText = "Lihat";
        }
    };
});
