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

async function inspectGallery() {
    const port = 9755;
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
        let targets = null;
        for (let i = 0; i < 15; i++) {
            await new Promise(r => setTimeout(r, 500));
            try {
                targets = await getJson(`http://127.0.0.1:${port}/json/list`);
                if (targets) break;
            } catch(e) {}
        }
        const pageTarget = targets.find(t => t.type === 'page');
        const ws = new WebSocket(pageTarget.webSocketDebuggerUrl);
        await new Promise(r => ws.onopen = r);

        await sendCommand(ws, 'Page.enable');
        await sendCommand(ws, 'Runtime.enable');

        await sendCommand(ws, 'Page.navigate', { url: 'http://localhost/wordpress/product/doi-gio-hu/' });
        await new Promise(r => setTimeout(r, 6000));

        const res = await sendCommand(ws, 'Runtime.evaluate', {
            expression: `(() => {
                const gallery = document.querySelector('.woocommerce-product-gallery') || document.querySelector('.images');
                if (!gallery) return { error: 'gallery not found' };

                const elements = Array.from(gallery.querySelectorAll('*')).map(el => {
                    const r = el.getBoundingClientRect();
                    const cs = window.getComputedStyle(el);
                    return {
                        tag: el.tagName,
                        class: el.className,
                        id: el.id,
                        x: Math.round(r.x),
                        y: Math.round(r.y),
                        width: Math.round(r.width),
                        height: Math.round(r.height),
                        display: cs.display,
                        position: cs.position,
                        zIndex: cs.zIndex,
                        html: el.outerHTML.substring(0, 100)
                    };
                });

                const gRect = gallery.getBoundingClientRect();

                // Check zoom trigger specifically
                const zoomTrigger = gallery.querySelector('.woocommerce-product-gallery__trigger');

                // Check all elements on the entire single product page that have "Tình trạng"
                const allNodes = [];
                const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
                let node;
                while (node = walker.nextNode()) {
                    if (node.nodeValue && node.nodeValue.toLowerCase().includes('tình trạng')) {
                        allNodes.push({
                            text: node.nodeValue.trim(),
                            tag: node.parentElement.tagName,
                            class: node.parentElement.className,
                            html: node.parentElement.outerHTML.substring(0, 200),
                            rect: { x: Math.round(node.parentElement.getBoundingClientRect().x), y: Math.round(node.parentElement.getBoundingClientRect().y) }
                        });
                    }
                }

                return {
                    galleryRect: { x: Math.round(gRect.x), y: Math.round(gRect.y), width: Math.round(gRect.width), height: Math.round(gRect.height) },
                    hasZoomTrigger: !!zoomTrigger,
                    zoomTriggerHTML: zoomTrigger ? zoomTrigger.outerHTML : null,
                    elements: elements.filter(e => e.width > 0 && e.height > 0),
                    tinhTrangNodes: allNodes
                };
            })()`,
            returnByValue: true
        });

        console.log('Gallery Details:\n', JSON.stringify(res.result.value, null, 2));

        // Take screenshot of gallery
        const gBox = res.result.value.galleryRect;
        const ss = await sendCommand(ws, 'Page.captureScreenshot', {
            clip: {
                x: Math.max(0, gBox.x - 20),
                y: Math.max(0, gBox.y - 20),
                width: gBox.width + 40,
                height: gBox.height + 40,
                scale: 1.5
            }
        });
        const p = 'C:\\Users\\Admin\\.gemini\\antigravity\\brain\\412d0b61-24f6-42ed-89d2-52d5d6ef39a6\\gallery_clip.png';
        fs.writeFileSync(p, Buffer.from(ss.data, 'base64'));
        console.log('Saved gallery screenshot:', p);

        ws.close();
    } catch(e) {
        console.error(e);
    } finally {
        chrome.kill();
    }
}

inspectGallery();
