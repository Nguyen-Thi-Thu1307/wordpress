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

const sleep = ms => new Promise(r => setTimeout(r, ms));

async function run() {
    fs.writeFileSync('c:\\xampp\\htdocs\\wordpress\\auth_user.php', `<?php
    require_once 'wp-load.php';
    wp_set_current_user(3);
    wp_set_auth_cookie(3, true);
    wp_redirect(home_url('/?add-to-cart=71'));
    exit;
    `);

    const port = 9785;
    const chrome = spawn('C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', [
        '--headless=new',
        `--remote-debugging-port=${port}`,
        '--window-size=1400,1100',
        '--disable-gpu',
        '--no-first-run',
        '--no-default-browser-check',
        `--user-data-dir=C:\\tmp\\chr_${port}`,
        'about:blank'
    ]);

    try {
        await sleep(2500);
        const targets = await getJson(`http://127.0.0.1:${port}/json/list`);
        const pageTarget = targets.find(t => t.type === 'page');
        const ws = new WebSocket(pageTarget.webSocketDebuggerUrl);
        await new Promise(r => ws.onopen = r);

        await sendCommand(ws, 'Page.enable');
        await sendCommand(ws, 'Runtime.enable');

        console.log('1. Login & add item to cart via auth_user.php...');
        await sendCommand(ws, 'Page.navigate', { url: 'http://localhost/wordpress/auth_user.php' });
        await sleep(6000);

        // Add a second book (68 - Hack Não 1500 từ 357,500đ)
        console.log('2. Add second book to cart...');
        await sendCommand(ws, 'Page.navigate', { url: 'http://localhost/wordpress/?add-to-cart=68' });
        await sleep(6000);

        console.log('3. Navigate to checkout...');
        await sendCommand(ws, 'Page.navigate', { url: 'http://localhost/wordpress/thanh-toan/' });
        await sleep(7000);

        // Inspect points card on checkout
        const info = await sendCommand(ws, 'Runtime.evaluate', {
            expression: `(() => {
                const title = document.querySelector('.nb-points-title')?.innerText;
                const desc = document.querySelector('.nb-points-desc')?.innerText;
                const val = document.querySelector('.nb-points-value')?.innerText;
                const chk = document.getElementById('nbUsePointsCheckbox')?.checked;
                const grandTotal = document.getElementById('nbCoGrandTotal')?.innerText;
                return { title, desc, val, chk, grandTotal };
            })()`,
            returnByValue: true
        });

        console.log('Checkout Points info (before toggle):', JSON.stringify(info.result.value, null, 2));

        // Screenshot before toggle
        const ss1 = await sendCommand(ws, 'Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync('C:\\Users\\Admin\\.gemini\\antigravity\\brain\\412d0b61-24f6-42ed-89d2-52d5d6ef39a6\\checkout_points_1xu_100d.png', Buffer.from(ss1.data, 'base64'));
        console.log('Saved checkout_points_1xu_100d.png');

        // Toggle checkbox on
        console.log('Toggling points checkbox ON...');
        await sendCommand(ws, 'Runtime.evaluate', {
            expression: `(() => {
                const chk = document.getElementById('nbUsePointsCheckbox');
                if (chk && !chk.checked) {
                    chk.click();
                }
            })()`
        });

        await sleep(4000);

        const info2 = await sendCommand(ws, 'Runtime.evaluate', {
            expression: `(() => {
                const title = document.querySelector('.nb-points-title')?.innerText;
                const desc = document.querySelector('.nb-points-desc')?.innerText;
                const val = document.querySelector('.nb-points-value')?.innerText;
                const chk = document.getElementById('nbUsePointsCheckbox')?.checked;
                const pointsRow = document.getElementById('nbCoPointsRow')?.innerText;
                const grandTotal = document.getElementById('nbCoGrandTotal')?.innerText;
                return { title, desc, val, chk, pointsRow, grandTotal };
            })()`,
            returnByValue: true
        });

        console.log('Checkout Points info (after toggle):', JSON.stringify(info2.result.value, null, 2));

        // Screenshot after toggle
        const ss2 = await sendCommand(ws, 'Page.captureScreenshot', { format: 'png' });
        fs.writeFileSync('C:\\Users\\Admin\\.gemini\\antigravity\\brain\\412d0b61-24f6-42ed-89d2-52d5d6ef39a6\\checkout_points_toggled_active.png', Buffer.from(ss2.data, 'base64'));
        console.log('Saved checkout_points_toggled_active.png');

        ws.close();
        console.log('Checkout test done!');
    } catch (e) {
        console.error('Test error:', e);
    } finally {
        chrome.kill();
        if (fs.existsSync('c:\\xampp\\htdocs\\wordpress\\auth_user.php')) {
            fs.unlinkSync('c:\\xampp\\htdocs\\wordpress\\auth_user.php');
        }
    }
}

run();
