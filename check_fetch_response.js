const { spawn } = require('child_process');
const http = require('http');

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

function waitForEvent(ws, eventName) {
    return new Promise((resolve) => {
        function handler(event) {
            const msg = JSON.parse(event.data);
            if (msg.method === eventName) {
                ws.removeEventListener('message', handler);
                resolve(msg.params);
            }
        }
        ws.addEventListener('message', handler);
    });
}

async function checkFetchResponse() {
    const port = 9889;
    const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        '--headless=new',
        `--remote-debugging-port=${port}`,
        '--window-size=1440,1000',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        `--user-data-dir=C:\\tmp\\chr_${port}`,
        'about:blank'
    ]);

    try {
        let targets = null;
        for (let i = 0; i < 20; i++) {
            await new Promise(r => setTimeout(r, 500));
            try {
                targets = await getJson(`http://127.0.0.1:${port}/json/list`);
                if (targets && targets.length > 0) break;
            } catch(e) {}
        }
        const pageTarget = targets.find(t => t.type === 'page');
        const ws = new WebSocket(pageTarget.webSocketDebuggerUrl);
        await new Promise(r => ws.onopen = r);

        await sendCommand(ws, 'Page.enable');
        await sendCommand(ws, 'Runtime.enable');

        await sendCommand(ws, 'Page.navigate', { url: 'http://localhost/wordpress/auto_login_test.php' });
        await new Promise(r => setTimeout(r, 1000));

        const pLoad = waitForEvent(ws, 'Page.loadEventFired');
        await sendCommand(ws, 'Page.navigate', { url: 'http://localhost/wordpress/' });
        await pLoad;

        const res = await sendCommand(ws, 'Runtime.evaluate', {
            expression: `(async () => {
                const url = window.nbook_ajax.ajax_url + '?action=nbook_notifications&nonce=' + encodeURIComponent(window.nbook_ajax.notifications_nonce);
                const r = await fetch(url);
                const t = await r.text();
                return { status: r.status, url: url, body: t };
            })()`,
            awaitPromise: true,
            returnByValue: true
        });

        console.log('REAL FETCH RESPONSE:', JSON.stringify(res.result.value, null, 2));

        ws.close();
    } catch(e) {
        console.error(e);
    } finally {
        chrome.kill();
    }
}

checkFetchResponse();
