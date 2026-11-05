const { spawn } = require('child_process');
const http = require('http');
const fs = require('fs');

function getJson(url) {
    return new Promise((resolve, reject) => {
        http.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => resolve(JSON.parse(data)));
        }).on('error', reject);
    });
}

function sendCommand(ws, method, params = {}) {
    return new Promise((resolve, reject) => {
        const id = Math.floor(Math.random() * 100000);
        function handler(event) {
            const msg = JSON.parse(event.data);
            if (msg.id === id) {
                ws.removeEventListener('message', handler);
                if (msg.error) reject(msg.error);
                else resolve(msg.result);
            }
        }
        ws.addEventListener('message', handler);
        ws.send(JSON.stringify({ id, method, params }));
    });
}

async function inspectEditAccount() {
    fs.writeFileSync('c:\\xampp\\htdocs\\wordpress\\auth_edit_acc.php', `<?php
    require_once 'wp-load.php';
    wp_set_current_user(3);
    wp_set_auth_cookie(3, true);
    wp_redirect(wc_get_endpoint_url('edit-account', '', wc_get_page_permalink('myaccount')));
    exit;
    `);

    const port = 9690;
    const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        '--headless=new',
        `--remote-debugging-port=${port}`,
        '--window-size=1440,1200',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        `--user-data-dir=C:\\tmp\\chr_${port}`,
        'about:blank'
    ]);

    try {
        await new Promise(r => setTimeout(r, 2500));
        const targets = await getJson(`http://127.0.0.1:${port}/json/list`);
        const pageTarget = targets.find(t => t.type === 'page');
        const ws = new WebSocket(pageTarget.webSocketDebuggerUrl);
        await new Promise(r => ws.onopen = r);

        await sendCommand(ws, 'Page.enable');
        await sendCommand(ws, 'Runtime.enable');

        await sendCommand(ws, 'Page.navigate', { url: 'http://localhost/wordpress/auth_edit_acc.php' });
        await new Promise(r => setTimeout(r, 6000));

        // Inspect DOM structure of password fields and eye buttons
        const info = await sendCommand(ws, 'Runtime.evaluate', {
            expression: `(() => {
                const fnP = document.getElementById('account_first_name')?.closest('p');
                const lnP = document.getElementById('account_last_name')?.closest('p');
                const fnRow = fnP?.parentElement;

                const pw1 = document.getElementById('password_1');
                const pw1Wrap = pw1?.parentElement;
                const pw1Buttons = pw1Wrap ? Array.from(pw1Wrap.querySelectorAll('button, span, i')).map(el => ({
                    tag: el.tagName,
                    class: el.className,
                    text: el.innerText
                })) : [];

                const pwCardHeader = document.querySelector('.nb-password-card .nb-fc-header');
                const pwCardBody = document.querySelector('.nb-password-card .nb-fc-body');

                return {
                    url: window.location.href,
                    firstNameLabel: fnP?.querySelector('label')?.innerText,
                    lastNameLabel: lnP?.querySelector('label')?.innerText,
                    fnRowClasses: fnRow?.className,
                    pw1Buttons,
                    pwCurrentParent: document.getElementById('password_current')?.parentElement?.className
                };
            })()`,
            returnByValue: true
        });

        console.log('Edit Account Form Info:\n', JSON.stringify(info.result.value, null, 2));

        // Take full screenshot of form
        const ss = await sendCommand(ws, 'Page.captureScreenshot', { format: 'png' });
        const p = 'C:\\Users\\Admin\\.gemini\\antigravity\\brain\\412d0b61-24f6-42ed-89d2-52d5d6ef39a6\\edit_account_current.png';
        fs.writeFileSync(p, Buffer.from(ss.data, 'base64'));
        console.log('Saved screenshot:', p);

        ws.close();
    } catch(e) {
        console.error(e);
    } finally {
        chrome.kill();
        if (fs.existsSync('c:\\xampp\\htdocs\\wordpress\\auth_edit_acc.php')) {
            fs.unlinkSync('c:\\xampp\\htdocs\\wordpress\\auth_edit_acc.php');
        }
    }
}

inspectEditAccount();
