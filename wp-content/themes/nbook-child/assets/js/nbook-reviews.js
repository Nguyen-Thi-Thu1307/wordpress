(function () {
    'use strict';

    function request(data, files) {
        var body = files ? new FormData() : new URLSearchParams();
        Object.keys(data).forEach(function (key) {
            if (files && key === 'images') {
                Array.prototype.forEach.call(data[key], function (file) { body.append('images[]', file); });
            } else {
                body.append(key, data[key]);
            }
        });
        return fetch(nbook_reviews.ajax_url, { method: 'POST', body: body }).then(function (response) { return response.json(); });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.querySelector('.nb-reviews');
        if (!root) return;
        var list = root.querySelector('.nb-review-list');
        var filter = 'all';
        var page = 1;
        var more = root.querySelector('.nb-review-pagination button');

        function load(reset) {
            if (reset) page = 1;
            request({ action: 'nbook_load_reviews', nonce: nbook_reviews.nonce, product_id: root.dataset.productId, filter: filter, page: page })
                .then(function (result) {
                    if (!result.success) return;
                    if (reset) list.innerHTML = result.data.html; else list.insertAdjacentHTML('beforeend', result.data.html);
                    more.hidden = !result.data.has_more;
                });
        }
        root.querySelectorAll('.nb-review-filters button').forEach(function (button) {
            button.addEventListener('click', function () {
                root.querySelector('.nb-review-filters .is-active').classList.remove('is-active');
                button.classList.add('is-active');
                filter = button.dataset.filter;
                load(true);
            });
        });
        more.addEventListener('click', function () { page++; load(false); });
        root.addEventListener('click', function (event) {
            var button = event.target.closest('.nb-helpful');
            if (!button) return;
            request({ action: 'nbook_helpful_review', nonce: nbook_reviews.nonce, review_id: button.dataset.reviewId }).then(function (result) {
                if (result.success) {
                    button.querySelector('span').textContent = result.data.count;
                    button.disabled = true;
                }
            });
        });
        var form = root.querySelector('.nb-review-form');
        if (form) form.addEventListener('submit', function (event) {
            event.preventDefault();
            var files = form.querySelector('input[type="file"]').files;
            var status = form.querySelector('.nb-review-form-status');
            if (files.length > 5) { status.textContent = 'Chỉ được tải tối đa 5 ảnh.'; return; }
            for (var i = 0; i < files.length; i++) {
                if (files[i].size > 5 * 1024 * 1024) { status.textContent = 'Mỗi ảnh không được vượt quá 5MB.'; return; }
            }
            var data = {
                action: 'nbook_submit_review', nonce: nbook_reviews.nonce,
                product_id: form.dataset.productId, order_id: form.dataset.orderId,
                rating: form.querySelector('[name="rating"]').value,
                content: form.querySelector('[name="content"]').value,
                anonymous: form.querySelector('[name="anonymous"]').checked ? '1' : '0',
                images: files
            };
            status.textContent = 'Đang gửi...';
            request(data, true).then(function (result) {
                status.textContent = result.success ? result.data.message : (result.data && result.data.message ? result.data.message : 'Không thể gửi đánh giá.');
                if (result.success) form.reset();
            });
        });
    });
}());
