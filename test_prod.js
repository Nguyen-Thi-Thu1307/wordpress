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

async function inspectProductPage() {
    // Get a product URL from WP
    fs.writeFileSync('c:\\xampp\\htdocs\\wordpress\\get_prod_url.php', `<?php
    require_once 'wp-load.php';
    $products = wc_get_products(['limit' => 1]);
    if ($products) {
        echo $products[0]->get_permalink();
    }
    `);

    const port = 9740;
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

        // Get product URL
        const { execSync } = require('child_process');
        const prodUrl = execSync('C:\\xampp\\php\\php.exe c:\\xampp\\htdocs\\wordpress\\get_prod_url.php').toString().trim();
        console.log('Navigating to product:', prodUrl);

        await sendCommand(ws, 'Page.navigate', { url: prodUrl });
        await new Promise(r => setTimeout(r, 6000));

        // 1. Search for the big magnifying glass / search icon on top of product image
        const searchIconInfo = await sendCommand(ws, 'Runtime.evaluate', {
            expression: `(() => {
                // Look for gallery trigger or zoom icons
                const gallery = document.querySelector('.woocommerce-product-gallery');
                const triggers = Array.from(document.querySelectorAll('.woocommerce-product-gallery__trigger, .zoomImg, .ast-product-gallery-trigger, [class*="zoom"], [class*="lightbox"], [class*="search"]')).map(el => ({
                    tag: el.tagName,
                    class: el.className,
                    id: el.id,
                    rect: el.getBoundingClientRect(),
                    display: window.getComputedStyle(el).display,
                    html: el.outerHTML.substring(0, 150)
                }));

                // Check text containing "Tình trạng" across the whole document
                const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
                const tinhTrangNodes = [];
                let node;
                while (node = walker.nextNode()) {
                    if (node.nodeValue && node.nodeValue.includes('Tình trạng')) {
                        const parent = node.parentElement;
                        tinhTrangNodes.push({
                            text: node.nodeValue.trim(),
                            parentTag: parent.tagName,
                            parentClass: parent.className,
                            parentHTML: parent.outerHTML.substring(0, 200),
                            rect: parent.getBoundingClientRect()
                        });
                    }
                }

                return {
                    triggers,
                    tinhTrangNodes
                };
            })()`,
            returnByValue: true
        });

        console.log('Product Page Inspection:\n', JSON.stringify(searchIconInfo.result.value, null, 2));

        // Take a screenshot of the top section (image + summary)
        const ss = await sendCommand(ws, 'Page.captureScreenshot', { format: 'png' });
        const p = 'C:\\Users\\Admin\\.gemini\\antigravity\\brain\\412d0b61-24f6-42ed-89d2-52d5d6ef39a6\\product_page_current.png';
        fs.writeFileSync(p, Buffer.from(ss.data, 'base64'));
        console.log('Saved screenshot:', p);

        ws.close();
    } catch(e) {
        console.error(e);
    } finally {
        chrome.kill();
        if (fs.existsSync('c:\\xampp\\htdocs\\wordpress\\get_prod_url.php')) {
            fs.unlinkSync('c:\\xampp\\htdocs\\wordpress\\get_prod_url.php');
        }
    }
}

inspectProductPage();
