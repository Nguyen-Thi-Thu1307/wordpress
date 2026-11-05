/**
 * Nbook Child Theme JavaScript
 * Pure Vanilla JS - Slider, AJAX Search, Mobile Navigation, Toast
 */

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.nb-copy-voucher').forEach(function (button) {
        button.addEventListener('click', function () {
            var code = button.getAttribute('data-code');
            var copied = function () {
                button.textContent = 'Đã sao chép';
                button.classList.add('is-copied');
                window.setTimeout(function () {
                    button.textContent = 'Sao chép';
                    button.classList.remove('is-copied');
                }, 1800);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(copied);
            } else {
                var input = document.createElement('textarea');
                input.value = code;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                input.remove();
                copied();
            }
        });
    });
    // Notifications are refreshed without reloading the page.
    var notificationWrap = document.querySelector('.nb-notification-wrap');
    if (notificationWrap) {
        var notificationTrigger = notificationWrap.querySelector('.nb-notification-trigger');
        var notificationDropdown = notificationWrap.querySelector('.nb-notification-dropdown');
        var notificationItems = notificationWrap.querySelector('.nb-notification-items');
        var notificationCount = notificationWrap.querySelector('.nb-notification-count');
        var notificationNonce = (window.nbook_ajax && window.nbook_ajax.notifications_nonce) ? window.nbook_ajax.notifications_nonce : '';
        var notificationUrl = (window.nbook_ajax && window.nbook_ajax.ajax_url) ? window.nbook_ajax.ajax_url : '/wordpress/wp-admin/admin-ajax.php';

        function escapeHtml(value) {
            var div = document.createElement('div');
            div.textContent = value || '';
            return div.innerHTML;
        }
        function loadNotifications() {
            var url = notificationUrl + '?action=nbook_notifications&nonce=' + encodeURIComponent(notificationNonce);
            fetch(url)
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data || !data.success) return;
                    var count = Number(data.data.unread || 0);
                    if (notificationCount) {
                        notificationCount.textContent = count > 99 ? '99+' : count;
                        notificationCount.hidden = count === 0;
                    }
                    if (!data.data.items || !data.data.items.length) {
                        if (notificationItems) notificationItems.innerHTML = '<p class="nb-notification-empty">Bạn chưa có thông báo nào.</p>';
                        return;
                    }
                    if (notificationItems) {
                        notificationItems.innerHTML = data.data.items.map(function (item) {
                            return '<a class="nb-notification-item ' + (item.is_read ? '' : 'is-unread') + '" href="' + item.url + '" data-notification-id="' + item.id + '">' +
                                '<strong>' + escapeHtml(item.title) + '</strong><span>' + escapeHtml(item.message) + '</span><time>' + escapeHtml(item.date) + '</time></a>';
                        }).join('');
                    }
                })
                .catch(function () {});
        }
        if (notificationTrigger) {
            notificationTrigger.addEventListener('click', function (e) {
                e.stopPropagation();
                var expanded = notificationTrigger.getAttribute('aria-expanded') === 'true';
                notificationTrigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                if (notificationDropdown) notificationDropdown.hidden = expanded;
                if (!expanded) loadNotifications();
            });
        }
        document.addEventListener('click', function (event) {
            if (notificationWrap && !notificationWrap.contains(event.target)) {
                if (notificationTrigger) notificationTrigger.setAttribute('aria-expanded', 'false');
                if (notificationDropdown) notificationDropdown.hidden = true;
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                if (notificationTrigger) notificationTrigger.setAttribute('aria-expanded', 'false');
                if (notificationDropdown) notificationDropdown.hidden = true;
            }
        });
        var markAllBtn = notificationWrap.querySelector('.nb-mark-all-read');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                fetch(notificationUrl, { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=nbook_mark_notifications_read&nonce=' + encodeURIComponent(notificationNonce) })
                    .then(function () { loadNotifications(); });
            });
        }
        if (notificationItems) {
            notificationItems.addEventListener('click', function (event) {
                var item = event.target.closest('[data-notification-id]');
                if (item) {
                    navigator.sendBeacon(notificationUrl, new URLSearchParams({action: 'nbook_mark_notification_read', nonce: notificationNonce, id: item.dataset.notificationId}));
                }
            });
        }
        loadNotifications();
        window.setInterval(loadNotifications, 30000);
    }
    // =========================================================================
    // 1. PURE JS SLIDE BANNER (5-second auto transition, dots, prev/next)
    // =========================================================================
    var track = document.querySelector('.nb-slider-track');
    var slides = document.querySelectorAll('.nb-slide-item');
    var dots = document.querySelectorAll('.nb-dot');
    var prevBtn = document.querySelector('.nb-slider-prev');
    var nextBtn = document.querySelector('.nb-slider-next');
    var sliderContainer = document.querySelector('.nb-slider-container');

    if (track && slides.length > 0) {
        var currentIndex = 0;
        var slideCount = slides.length;
        var slideInterval = null;

        function goToSlide(index) {
            if (index < 0) index = slideCount - 1;
            if (index >= slideCount) index = 0;
            currentIndex = index;
            track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';
            dots.forEach(function (dot, i) {
                dot.classList.toggle('active', i === currentIndex);
            });
        }

        function nextSlide() {
            goToSlide(currentIndex + 1);
        }

        function prevSlide() {
            goToSlide(currentIndex - 1);
        }

        function startAutoPlay() {
            stopAutoPlay();
            slideInterval = setInterval(nextSlide, 5000);
        }

        function stopAutoPlay() {
            if (slideInterval) {
                clearInterval(slideInterval);
                slideInterval = null;
            }
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function (e) {
                e.preventDefault();
                nextSlide();
                startAutoPlay();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function (e) {
                e.preventDefault();
                prevSlide();
                startAutoPlay();
            });
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var targetIdx = parseInt(this.getAttribute('data-index')) || 0;
                goToSlide(targetIdx);
                startAutoPlay();
            });
        });

        if (sliderContainer) {
            sliderContainer.addEventListener('mouseenter', stopAutoPlay);
            sliderContainer.addEventListener('mouseleave', startAutoPlay);

            // Touch support for mobile
            var startX = 0;
            var endX = 0;
            sliderContainer.addEventListener('touchstart', function (e) {
                startX = e.changedTouches[0].screenX;
                stopAutoPlay();
            }, { passive: true });

            sliderContainer.addEventListener('touchend', function (e) {
                endX = e.changedTouches[0].screenX;
                if (startX - endX > 40) {
                    nextSlide();
                } else if (endX - startX > 40) {
                    prevSlide();
                }
                startAutoPlay();
            }, { passive: true });
        }

        startAutoPlay();
    }

    // =========================================================================
    // 2. LIVE AJAX SEARCH (Title, Author, SKU)
    // =========================================================================
    var searchInputs = document.querySelectorAll('.nb-search-input, .nb-big-search-input, .sp-search-input');

    searchInputs.forEach(function (input) {
        var wrapper = input.closest('.nb-header-search-wrap, .nb-big-search-box, .sp-search-container, .sp-search-form');
        if (!wrapper) return;

        var dropdown = wrapper.querySelector('.nb-ajax-dropdown');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'nb-ajax-dropdown';
            wrapper.appendChild(dropdown);
        }

        var debounceTimer = null;

        input.addEventListener('input', function () {
            var term = this.value.trim();
            clearTimeout(debounceTimer);

            if (term.length < 2) {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
                return;
            }

            dropdown.style.display = 'block';
            dropdown.innerHTML = '<div class="nb-ajax-loading">Đang tìm kiếm tựa sách...</div>';

            debounceTimer = setTimeout(function () {
                var ajaxUrl = (window.nbook_ajax && window.nbook_ajax.ajax_url) ? window.nbook_ajax.ajax_url : '/wordpress/wp-admin/admin-ajax.php';
                var nonce = (window.nbook_ajax && window.nbook_ajax.nonce) ? window.nbook_ajax.nonce : '';

                var url = ajaxUrl + '?action=nbook_ajax_search&term=' + encodeURIComponent(term) + '&nonce=' + encodeURIComponent(nonce);

                fetch(url)
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success && data.data && data.data.length > 0) {
                            var html = '';
                            data.data.forEach(function (item) {
                                html += '<a href="' + item.url + '" class="nb-ajax-item">';
                                html += '  <img src="' + item.thumb + '" alt="' + item.title + '" class="nb-ajax-thumb" />';
                                html += '  <div class="nb-ajax-info">';
                                html += '    <h4 class="nb-ajax-title">' + item.title + '</h4>';
                                html += '    <div class="nb-ajax-meta">Tác giả: ' + item.author + (item.sku ? ' | SKU: ' + item.sku : '') + '</div>';
                                html += '  </div>';
                                html += '  <div class="nb-ajax-price">' + item.price + '</div>';
                                html += '</a>';
                            });
                            dropdown.innerHTML = html;
                        } else {
                            dropdown.innerHTML = '<div class="nb-ajax-no-match">Không tìm thấy sách phù hợp với "' + term + '"</div>';
                        }
                    })
                    .catch(function () {
                        dropdown.innerHTML = '<div class="nb-ajax-no-match">Đã xảy ra lỗi khi tìm kiếm.</div>';
                    });
            }, 250);
        });

        // Close on click outside
        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    });

    // =========================================================================
    // 3. MOBILE HAMBURGER MENU
    // =========================================================================
    var hamburgerBtns = document.querySelectorAll('.nb-hamburger-btn, .sp-mobile-toggle');
    var mobileDrawer = document.querySelector('.nb-mobile-drawer');
    var drawerBackdrop = document.querySelector('.nb-drawer-backdrop');
    var drawerCloseBtn = document.querySelector('.nb-drawer-close');

    function openDrawer() {
        if (mobileDrawer) mobileDrawer.classList.add('open');
        if (drawerBackdrop) drawerBackdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        if (mobileDrawer) mobileDrawer.classList.remove('open');
        if (drawerBackdrop) drawerBackdrop.classList.remove('open');
        document.body.style.overflow = '';
    }

    hamburgerBtns.forEach(function (btn) {
        btn.addEventListener('click', openDrawer);
    });
    if (drawerCloseBtn) drawerCloseBtn.addEventListener('click', closeDrawer);
    if (drawerBackdrop) drawerBackdrop.addEventListener('click', closeDrawer);

    // =========================================================================
    // 4. ADD-TO-CART TOAST NOTIFICATION
    // =========================================================================
    var toast = document.querySelector('.nb-toast-box');
    function showToast(msg) {
        if (!toast) return;
        var textEl = toast.querySelector('.nb-toast-text');
        if (textEl && msg) textEl.textContent = msg;
        toast.classList.add('show');
        setTimeout(function () {
            toast.classList.remove('show');
        }, 2600);
    }

    if (window.jQuery) {
        window.jQuery(document.body).on('added_to_cart', function () {
            showToast('Đã thêm sản phẩm vào Giỏ hàng!');
        });
    }

    if (window.location.search.indexOf('add-to-cart=') !== -1) {
        showToast('Đã thêm sản phẩm vào Giỏ hàng!');
    }

    // =========================================================================
    // 5. ACCOUNT DROPDOWN MENU (Hover, Click/Touch, Esc, Keyboard navigation)
    // =========================================================================
    var accWrap = document.getElementById('spAccountDropdownWrap');
    var accTrigger = document.getElementById('spAccTriggerBtn');
    var accMenu = document.getElementById('spAccDropdownMenu');
    var hoverTimeout = null;

    function getMenuItems() {
        if (!accMenu) return [];
        return Array.prototype.slice.call(
            accMenu.querySelectorAll('a, button:not([disabled])')
        );
    }

    function openAccDropdown() {
        if (!accWrap) return;
        clearTimeout(hoverTimeout);
        accWrap.classList.add('is-open');
        if (accTrigger) accTrigger.setAttribute('aria-expanded', 'true');
    }

    function closeAccDropdown() {
        if (!accWrap) return;
        clearTimeout(hoverTimeout);
        accWrap.classList.remove('is-open');
        if (accTrigger) accTrigger.setAttribute('aria-expanded', 'false');
    }

    function toggleAccDropdown() {
        if (!accWrap) return;
        if (accWrap.classList.contains('is-open')) {
            closeAccDropdown();
        } else {
            openAccDropdown();
        }
    }

    if (accWrap) {
        // Desktop Hover (reliable mouseenter/mouseleave with small buffer)
        accWrap.addEventListener('mouseenter', function () {
            openAccDropdown();
        });

        accWrap.addEventListener('mouseleave', function () {
            hoverTimeout = setTimeout(closeAccDropdown, 220);
        });

        // Global Esc key closes dropdown
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                if (accWrap.classList.contains('is-open')) {
                    closeAccDropdown();
                    if (accTrigger) accTrigger.focus();
                }
            }
        });

        // Keyboard Navigation on Trigger Button
        if (accTrigger) {
            accTrigger.addEventListener('keydown', function (e) {
                var items = getMenuItems();
                if (e.key === 'ArrowDown' || e.keyCode === 40) {
                    e.preventDefault();
                    openAccDropdown();
                    if (items.length > 0) items[0].focus();
                } else if (e.key === 'ArrowUp' || e.keyCode === 38) {
                    e.preventDefault();
                    openAccDropdown();
                    if (items.length > 0) items[items.length - 1].focus();
                } else if (e.key === 'Enter' || e.key === ' ' || e.keyCode === 13 || e.keyCode === 32) {
                    e.preventDefault();
                    toggleAccDropdown();
                    if (accWrap.classList.contains('is-open') && items.length > 0) {
                        items[0].focus();
                    }
                }
            });
        }

        // Keyboard Navigation inside Menu
        if (accMenu) {
            accMenu.addEventListener('keydown', function (e) {
                var items = getMenuItems();
                var activeIdx = items.indexOf(document.activeElement);

                if (e.key === 'ArrowDown' || e.keyCode === 40) {
                    e.preventDefault();
                    var nextIdx = (activeIdx + 1) % items.length;
                    if (items[nextIdx]) items[nextIdx].focus();
                } else if (e.key === 'ArrowUp' || e.keyCode === 38) {
                    e.preventDefault();
                    var prevIdx = (activeIdx - 1 + items.length) % items.length;
                    if (items[prevIdx]) items[prevIdx].focus();
                } else if (e.key === 'Home' || e.keyCode === 36) {
                    e.preventDefault();
                    if (items.length > 0) items[0].focus();
                } else if (e.key === 'End' || e.keyCode === 35) {
                    e.preventDefault();
                    if (items.length > 0) items[items.length - 1].focus();
                } else if (e.key === 'Tab') {
                    if (!e.shiftKey && activeIdx === items.length - 1) {
                        closeAccDropdown();
                    } else if (e.shiftKey && (activeIdx === 0 || activeIdx === -1)) {
                        closeAccDropdown();
                    }
                }
            });
        }
    }

    // =========================================================================
    // 6. POPUP AUTHENTICATION MODAL (Tabs, Password Eye, Pure AJAX Auth)
    // =========================================================================
    var authModal = document.getElementById('nbAuthModal');
    var authBackdrop = authModal ? authModal.querySelector('.nb-auth-backdrop') : null;
    var authCloseBtn = document.getElementById('nbAuthCloseBtn');
    var authAlert = document.getElementById('nbAuthAlert');
    var authTabs = document.querySelectorAll('.nb-auth-tab');
    var authPanes = document.querySelectorAll('.nb-auth-pane');

    function showAlert(msg, isSuccess) {
        if (!authAlert) return;
        authAlert.innerHTML = (isSuccess ? '<i class="bi bi-check-circle-fill"></i> ' : '<i class="bi bi-exclamation-triangle-fill"></i> ') + msg;
        authAlert.className = 'nb-auth-alert ' + (isSuccess ? 'success' : 'error');
        authAlert.style.display = 'block';
    }

    function hideAlert() {
        if (!authAlert) return;
        authAlert.style.display = 'none';
        authAlert.innerHTML = '';
        authAlert.className = 'nb-auth-alert';
    }

    function switchAuthTab(targetTab) {
        hideAlert();
        authTabs.forEach(function (tab) {
            var isActive = tab.getAttribute('data-target') === targetTab;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        authPanes.forEach(function (pane) {
            var paneId = pane.id.toLowerCase();
            var isMatch = false;
            if (targetTab === 'login' && paneId === 'panelogin') isMatch = true;
            if (targetTab === 'register' && paneId === 'paneregister') isMatch = true;
            if (targetTab === 'lost-password' && paneId === 'panelostpassword') isMatch = true;
            pane.classList.toggle('active', isMatch);
        });

        var activePane = authModal ? authModal.querySelector('.nb-auth-pane.active') : null;
        if (activePane) {
            var firstInput = activePane.querySelector('input');
            if (firstInput) {
                setTimeout(function () { firstInput.focus(); }, 120);
            }
        }
    }

    function openAuthModal(defaultTab) {
        closeAccDropdown();
        if (!authModal) {
            var homeUrl = (window.nbook_ajax && window.nbook_ajax.home_url) ? window.nbook_ajax.home_url : '/wordpress/';
            window.location.href = homeUrl + 'tai-khoan/';
            return;
        }
        authModal.classList.add('open');
        authModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        switchAuthTab(defaultTab || 'login');
    }

    function closeAuthModal() {
        if (!authModal) return;
        authModal.classList.remove('open');
        authModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        hideAlert();
    }

    // Unified Document Click: Handles trigger click, open modal clicks, and outside click
    document.addEventListener('click', function (e) {
        // 1. Check if trigger button was clicked (touch/mobile/desktop)
        var trigger = e.target.closest('#spAccTriggerBtn');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            toggleAccDropdown();
            return;
        }

        // 2. Check if an auth modal button was clicked
        var modalBtn = e.target.closest('.nb-open-auth-modal');
        if (modalBtn) {
            e.preventDefault();
            e.stopPropagation();
            var tab = modalBtn.getAttribute('data-tab') || 'login';
            openAuthModal(tab);
            return;
        }

        // 3. Click outside closes dropdown
        if (accWrap && !accWrap.contains(e.target)) {
            closeAccDropdown();
        }
    });

    if (authCloseBtn) {
        authCloseBtn.addEventListener('click', closeAuthModal);
    }
    if (authBackdrop) {
        authBackdrop.addEventListener('click', closeAuthModal);
    }

    // Close modal on Esc
    document.addEventListener('keydown', function (e) {
        if ((e.key === 'Escape' || e.keyCode === 27) && authModal && authModal.classList.contains('open')) {
            closeAuthModal();
        }
    });

    // Tab buttons click
    authTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = this.getAttribute('data-target');
            switchAuthTab(target);
        });
    });

    // Internal links (Forgot pass & switch forms)
    var forgotLink = document.getElementById('nbGoForgotLink');
    if (forgotLink) {
        forgotLink.addEventListener('click', function (e) {
            e.preventDefault();
            switchAuthTab('lost-password');
        });
    }

    var switchLinks = document.querySelectorAll('.nb-switch-auth-link');
    switchLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var tab = this.getAttribute('data-tab') || 'login';
            switchAuthTab(tab);
        });
    });

    // Eye toggle password visibility (delegated for robust reliability)
    document.addEventListener('click', function (e) {
        var eyeBtn = e.target.closest('.nb-eye-toggle');
        if (!eyeBtn) return;
        e.preventDefault();
        e.stopPropagation();

        var wrapper = eyeBtn.closest('.nb-input-with-icon, .nb-password-field-wrap, .password-input, .form-row');
        if (!wrapper) return;
        var input = wrapper.querySelector('input');
        var icon = eyeBtn.querySelector('i');
        if (!input) return;

        if (input.type === 'password') {
            input.type = 'text';
            if (icon) {
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            }
        } else {
            input.type = 'password';
            if (icon) {
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    });

    // AJAX Auth Helper
    var ajaxConfig = window.nbook_ajax || {};
    var ajaxEndpoint = ajaxConfig.ajax_url || '/wordpress/wp-admin/admin-ajax.php';
    var authNonce = ajaxConfig.auth_nonce || '';

    function toggleBtnLoading(btn, isLoading, loadingText) {
        if (!btn) return;
        var txt = btn.querySelector('.btn-text');
        var spin = btn.querySelector('.btn-spinner');
        btn.disabled = isLoading;
        if (txt) txt.style.display = isLoading ? 'none' : '';
        if (spin) {
            spin.style.display = isLoading ? 'inline-block' : 'none';
            if (loadingText) spin.textContent = loadingText;
        }
    }

    // Form 1: AJAX Đăng Nhập
    var loginForm = document.getElementById('nbLoginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            hideAlert();

            var submitBtn = document.getElementById('nbLoginSubmitBtn');
            var username = (document.getElementById('nbLoginUser') || {}).value || '';
            var password = (document.getElementById('nbLoginPass') || {}).value || '';
            var remember = (document.getElementById('nbLoginRemember') || {}).checked ? 1 : 0;

            if (!username.trim() || !password) {
                showAlert('Vui lòng điền đầy đủ tên đăng nhập và mật khẩu.', false);
                return;
            }

            toggleBtnLoading(submitBtn, true);

            var formData = new FormData();
            formData.append('action', 'nb_ajax_login');
            formData.append('security', authNonce);
            formData.append('username', username.trim());
            formData.append('password', password);
            formData.append('remember', remember);

            fetch(ajaxEndpoint, {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                toggleBtnLoading(submitBtn, false);
                if (res.success) {
                    showAlert(res.data.message || 'Đăng nhập thành công! Đang tải lại trang...', true);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert(res.data && res.data.message ? res.data.message : 'Đăng nhập không thành công. Vui lòng kiểm tra lại thông tin.', false);
                }
            })
            .catch(function () {
                toggleBtnLoading(submitBtn, false);
                showAlert('Không thể kết nối đến máy chủ. Vui lòng thử lại sau giây lát.', false);
            });
        });
    }

    // Form 2: AJAX Đăng Ký
    var regForm = document.getElementById('nbRegisterForm');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            e.preventDefault();
            hideAlert();

            var submitBtn = document.getElementById('nbRegSubmitBtn');
            var name = (document.getElementById('nbRegName') || {}).value || '';
            var email = (document.getElementById('nbRegEmail') || {}).value || '';
            var password = (document.getElementById('nbRegPass') || {}).value || '';
            var confirmPassword = (document.getElementById('nbRegPassConfirm') || {}).value || '';

            if (password !== confirmPassword) {
                showAlert('Mật khẩu xác nhận không khớp. Vui lòng nhập lại chính xác.', false);
                return;
            }

            if (password.length < 6) {
                showAlert('Mật khẩu quá ngắn (phải có ít nhất 6 ký tự).', false);
                return;
            }

            toggleBtnLoading(submitBtn, true);

            var formData = new FormData();
            formData.append('action', 'nb_ajax_register');
            formData.append('security', authNonce);
            formData.append('name', name.trim());
            formData.append('email', email.trim());
            formData.append('password', password);
            formData.append('confirm_password', confirmPassword);

            fetch(ajaxEndpoint, {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                toggleBtnLoading(submitBtn, false);
                if (res.success) {
                    showAlert(res.data.message || 'Đăng ký thành công! Đang đăng nhập...', true);
                    setTimeout(function () {
                        window.location.reload();
                    }, 1200);
                } else {
                    showAlert(res.data && res.data.message ? res.data.message : 'Đăng ký thất bại. Vui lòng kiểm tra lại thông tin.', false);
                }
            })
            .catch(function () {
                toggleBtnLoading(submitBtn, false);
                showAlert('Lỗi kết nối máy chủ. Vui lòng thử lại.', false);
            });
        });
    }

    // Form 3: AJAX Quên Mật Khẩu
    var lostForm = document.getElementById('nbLostPassForm');
    if (lostForm) {
        lostForm.addEventListener('submit', function (e) {
            e.preventDefault();
            hideAlert();

            var submitBtn = document.getElementById('nbLostSubmitBtn');
            var userInput = (document.getElementById('nbLostUser') || {}).value || '';

            if (!userInput.trim()) {
                showAlert('Vui lòng nhập email hoặc tên tài khoản của bạn.', false);
                return;
            }

            toggleBtnLoading(submitBtn, true);

            var formData = new FormData();
            formData.append('action', 'nb_ajax_lost_password');
            formData.append('security', authNonce);
            formData.append('user_input', userInput.trim());

            fetch(ajaxEndpoint, {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                toggleBtnLoading(submitBtn, false);
                if (res.success) {
                    showAlert(res.data.message || 'Đã gửi liên kết khôi phục mật khẩu tới email của bạn.', true);
                    lostForm.reset();
                } else {
                    showAlert(res.data && res.data.message ? res.data.message : 'Không tìm thấy tài khoản phù hợp với thông tin đã nhập.', false);
                }
            })
            .catch(function () {
                toggleBtnLoading(submitBtn, false);
                showAlert('Lỗi kết nối máy chủ. Vui lòng thử lại sau giây lát.', false);
            });
        });
    }

    // =========================================================================
    // BIBLIO STOCK MANAGEMENT & NOTIFICATION (TÌNH TRẠNG TỒN KHO)
    // =========================================================================

    // 1. Flash Sale Countdown Timer
    var fsHours = document.getElementById('fsHours');
    var fsMinutes = document.getElementById('fsMinutes');
    var fsSeconds = document.getElementById('fsSeconds');

    if (fsHours && fsMinutes && fsSeconds) {
        var targetTime = new Date();
        targetTime.setHours(23, 59, 59, 999);

        function updateCountdown() {
            var diff = Math.max(0, Math.floor((targetTime.getTime() - new Date().getTime()) / 1000));
            var h = Math.floor(diff / 3600);
            var m = Math.floor((diff % 3600) / 60);
            var s = diff % 60;

            fsHours.textContent = h < 10 ? '0' + h : h;
            fsMinutes.textContent = m < 10 ? '0' + m : m;
            fsSeconds.textContent = s < 10 ? '0' + s : s;
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    // 2. Modal "Báo Cho Tôi Khi Có Hàng" (Back-in-stock Modal)
    var stockModal = document.getElementById('nbStockNotifyModal');
    var stockModalClose = document.getElementById('nbStockNotifyCloseBtn');
    var stockForm = document.getElementById('nbStockNotifyForm');
    var stockProductIdInput = document.getElementById('nbNotifyProductId');
    var stockProductTitleSpan = document.getElementById('nbNotifyProductTitle');
    var stockAlertBox = document.getElementById('nbNotifyAlert');
    var stockSubmitBtn = document.getElementById('nbNotifySubmitBtn');

    function openStockModal(productId, productTitle) {
        if (!stockModal) return;
        if (stockProductIdInput) stockProductIdInput.value = productId || '';
        if (stockProductTitleSpan) stockProductTitleSpan.textContent = productTitle || 'Sách này';
        if (stockAlertBox) {
            stockAlertBox.style.display = 'none';
            stockAlertBox.className = 'nb-snb-alert';
            stockAlertBox.textContent = '';
        }
        stockModal.classList.add('active');
        stockModal.setAttribute('aria-hidden', 'false');
        var emailInput = document.getElementById('nbNotifyEmail');
        if (emailInput) setTimeout(function () { emailInput.focus(); }, 150);
    }

    function closeStockModal() {
        if (!stockModal) return;
        stockModal.classList.remove('active');
        stockModal.setAttribute('aria-hidden', 'true');
    }

    // Delegate click for all stock notify buttons across page
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.nb-open-stock-notify-modal, .btn-stock-notify-card, .btn-stock-notify-trigger');
        if (trigger) {
            e.preventDefault();
            var pid = trigger.getAttribute('data-product-id') || '';
            var ptitle = trigger.getAttribute('data-product-title') || '';
            openStockModal(pid, ptitle);
        }

        // Close on backdrop click
        if (e.target.classList.contains('nb-stock-notify-backdrop')) {
            closeStockModal();
        }
    });

    if (stockModalClose) {
        stockModalClose.addEventListener('click', function (e) {
            e.preventDefault();
            closeStockModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && stockModal && stockModal.classList.contains('active')) {
            closeStockModal();
        }
    });

    // Handle AJAX Submission for Modal Form
    if (stockForm) {
        stockForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var pid = (stockProductIdInput || {}).value;
            var emailInput = document.getElementById('nbNotifyEmail');
            var email = emailInput ? emailInput.value.trim() : '';

            if (!pid || !email) {
                if (stockAlertBox) {
                    stockAlertBox.className = 'nb-snb-alert error';
                    stockAlertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>&nbsp;&nbsp;Vui lòng nhập địa chỉ email hợp lệ.';
                    stockAlertBox.style.display = 'flex';
                }
                return;
            }

            // Spinner
            if (stockSubmitBtn) {
                var btnTxt = stockSubmitBtn.querySelector('.btn-text');
                var btnSpin = stockSubmitBtn.querySelector('.btn-spinner');
                if (btnTxt) btnTxt.style.display = 'none';
                if (btnSpin) btnSpin.style.display = 'inline-block';
                stockSubmitBtn.disabled = true;
            }

            var formData = new FormData();
            formData.append('action', 'nb_subscribe_stock_alert');
            formData.append('security', (typeof nbook_ajax !== 'undefined' ? nbook_ajax.nonce : ''));
            formData.append('product_id', pid);
            formData.append('email', email);

            var ajaxUrl = typeof nbook_ajax !== 'undefined' ? nbook_ajax.ajax_url : '/wp-admin/admin-ajax.php';

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (stockSubmitBtn) {
                    var btnTxt = stockSubmitBtn.querySelector('.btn-text');
                    var btnSpin = stockSubmitBtn.querySelector('.btn-spinner');
                    if (btnTxt) btnTxt.style.display = 'inline-block';
                    if (btnSpin) btnSpin.style.display = 'none';
                    stockSubmitBtn.disabled = false;
                }

                if (stockAlertBox) {
                    if (res.success) {
                        stockAlertBox.className = 'nb-snb-alert success';
                        stockAlertBox.innerHTML = '<i class="bi bi-check-circle-fill"></i>&nbsp;&nbsp;' + ((res.data && res.data.message) ? res.data.message : 'Đăng ký thành công! Biblio sẽ gửi thông báo ngay khi có hàng.');
                        stockAlertBox.style.display = 'flex';
                        stockForm.reset();
                        setTimeout(function () {
                            closeStockModal();
                        }, 3000);
                    } else {
                        stockAlertBox.className = 'nb-snb-alert error';
                        stockAlertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>&nbsp;&nbsp;' + ((res.data && res.data.message) ? res.data.message : 'Có lỗi xảy ra, vui lòng thử lại sau.');
                        stockAlertBox.style.display = 'flex';
                    }
                }
            })
            .catch(function () {
                if (stockSubmitBtn) {
                    var btnTxt = stockSubmitBtn.querySelector('.btn-text');
                    var btnSpin = stockSubmitBtn.querySelector('.btn-spinner');
                    if (btnTxt) btnTxt.style.display = 'inline-flex';
                    if (btnSpin) btnSpin.style.display = 'none';
                    stockSubmitBtn.disabled = false;
                }
                if (stockAlertBox) {
                    stockAlertBox.className = 'nb-snb-alert error';
                    stockAlertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>&nbsp;&nbsp;Lỗi kết nối máy chủ, vui lòng thử lại.';
                    stockAlertBox.style.display = 'flex';
                }
            });
        });
    }

    // Handle AJAX Submission for Single Product Inline Form
    var inlineStockForms = document.querySelectorAll('.nb-stock-notify-inline-form');
    inlineStockForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var pid = form.getAttribute('data-product-id');
            var emailInput = form.querySelector('input[name="notify_email"]');
            var email = emailInput ? emailInput.value.trim() : '';
            var feedback = form.parentElement.querySelector('.nb-notify-feedback');
            var submitBtn = form.querySelector('button[type="submit"]');

            if (!email) return;

            if (submitBtn) submitBtn.disabled = true;

            var formData = new FormData();
            formData.append('action', 'nb_subscribe_stock_alert');
            formData.append('security', (typeof nbook_ajax !== 'undefined' ? nbook_ajax.nonce : ''));
            formData.append('product_id', pid);
            formData.append('email', email);

            var ajaxUrl = typeof nbook_ajax !== 'undefined' ? nbook_ajax.ajax_url : '/wp-admin/admin-ajax.php';

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (submitBtn) submitBtn.disabled = false;
                if (feedback) {
                    feedback.style.display = 'block';
                    if (res.success) {
                        feedback.className = 'nb-notify-feedback mt-2 text-success fw-medium';
                        feedback.innerHTML = '<i class="bi bi-check-circle-fill"></i>&nbsp;&nbsp;' + ((res.data && res.data.message) ? res.data.message : 'Đăng ký thành công! Biblio sẽ thông báo ngay khi có hàng.');
                        form.reset();
                    } else {
                        feedback.className = 'nb-notify-feedback mt-2 text-danger fw-medium';
                        feedback.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>&nbsp;&nbsp;' + ((res.data && res.data.message) ? res.data.message : 'Có lỗi xảy ra, vui lòng thử lại.');
                    }
                }
            })
            .catch(function () {
                if (submitBtn) submitBtn.disabled = false;
                if (feedback) {
                    feedback.style.display = 'block';
                    feedback.className = 'nb-notify-feedback mt-2 text-danger fw-medium';
                    feedback.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>&nbsp;&nbsp;Lỗi kết nối, vui lòng thử lại.';
                }
            });
        });
    });

    // =========================================================================
    // 8. REUSABLE CENTERED MODAL COMPONENT (BIBLIO / NEU EVENTS SPEC)
    // Supports: Esc key, backdrop blur, scroll lock, focus trap, role="dialog", aria-modal
    // Usage: BiblioModal.confirm(opts), BiblioModal.toast(opts), BiblioModal.close()
    // =========================================================================
    (function () {
        var activeModal = null;
        var previousActiveElement = null;
        var focusTrapCleanup = null;
        var toastTimer = null;

        // Calculate scrollbar width to prevent layout shift during scroll lock
        function getScrollbarWidth() {
            return window.innerWidth - document.documentElement.clientWidth;
        }

        function lockScroll() {
            var scrollbarWidth = getScrollbarWidth();
            if (scrollbarWidth > 0) {
                document.body.style.paddingRight = scrollbarWidth + 'px';
            }
            document.body.classList.add('nb-modal-open');
        }

        function unlockScroll() {
            document.body.classList.remove('nb-modal-open');
            document.body.style.paddingRight = '';
        }

        // Focus Trap Implementation
        function setupFocusTrap(dialogEl) {
            var focusableSelectors = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
            var focusableElements = Array.prototype.slice.call(dialogEl.querySelectorAll(focusableSelectors));

            if (focusableElements.length === 0) {
                return function () {};
            }

            var firstFocusable = focusableElements[0];
            var lastFocusable = focusableElements[focusableElements.length - 1];

            function handleKeyDown(e) {
                if (e.key !== 'Tab') return;

                if (e.shiftKey) { // Shift + Tab
                    if (document.activeElement === firstFocusable) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else { // Tab
                    if (document.activeElement === lastFocusable) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            }

            dialogEl.addEventListener('keydown', handleKeyDown);

            return function cleanup() {
                dialogEl.removeEventListener('keydown', handleKeyDown);
            };
        }

        var BiblioModal = {
            /**
             * Open a reusable Confirmation Dialog
             * @param {Object} options
             * - title: string
             * - message: string
             * - icon: 'logout' | 'question' | 'warning' | 'danger' | 'success'
             * - confirmText: string (default: 'Xác nhận')
             * - cancelText: string (default: 'Hủy')
             * - confirmClass: string ('nb-global-modal-btn-orange' | 'nb-global-modal-btn-danger')
             * - onConfirm: function(instance)
             * - onCancel: function(instance)
             */
            confirm: function (options) {
                options = options || {};
                BiblioModal.close(false);

                previousActiveElement = document.activeElement;

                var title = options.title || 'Bạn có chắc chắn?';
                var message = options.message || '';
                var iconType = options.icon || 'question';
                var confirmText = options.confirmText || 'Xác nhận';
                var cancelText = options.cancelText || 'Hủy';
                var confirmClass = options.confirmClass || 'nb-global-modal-btn-orange';

                // Determine Icon Markup
                var iconHtml = '';
                if (iconType === 'logout') {
                    iconHtml = '<div class="nb-modal-icon-wrap icon-logout"><i class="bi bi-box-arrow-right"></i></div>';
                } else if (iconType === 'danger') {
                    iconHtml = '<div class="nb-modal-icon-wrap icon-danger"><i class="bi bi-exclamation-triangle-fill"></i></div>';
                } else if (iconType === 'warning') {
                    iconHtml = '<div class="nb-modal-icon-wrap icon-warning"><i class="bi bi-exclamation-circle-fill"></i></div>';
                } else if (iconType === 'success') {
                    iconHtml = '<div class="nb-modal-icon-wrap icon-success"><i class="bi bi-check-circle-fill"></i></div>';
                } else {
                    iconHtml = '<div class="nb-modal-icon-wrap icon-question"><i class="bi bi-question-circle-fill"></i></div>';
                }

                var overlay = document.createElement('div');
                overlay.className = 'nb-global-modal-overlay';
                overlay.setAttribute('role', 'dialog');
                overlay.setAttribute('aria-modal', 'true');
                overlay.setAttribute('aria-labelledby', 'nbGlobalModalTitle');
                if (message) {
                    overlay.setAttribute('aria-describedby', 'nbGlobalModalDesc');
                }

                overlay.innerHTML = [
                    '<div class="nb-global-modal-dialog">',
                    '  <div class="nb-global-modal-content">',
                    '    <button type="button" class="nb-global-modal-close" aria-label="Đóng">&times;</button>',
                    iconHtml,
                    '    <h3 class="nb-global-modal-title" id="nbGlobalModalTitle">' + title + '</h3>',
                    (message ? '    <div class="nb-global-modal-desc" id="nbGlobalModalDesc">' + message + '</div>' : ''),
                    '    <div class="nb-global-modal-actions">',
                    '      <button type="button" class="nb-global-modal-btn nb-global-modal-btn-cancel" id="nbModalCancelBtn">' + cancelText + '</button>',
                    '      <button type="button" class="nb-global-modal-btn ' + confirmClass + '" id="nbModalConfirmBtn">',
                    '        <span class="nb-btn-text">' + confirmText + '</span>',
                    '        <span class="nb-modal-btn-spinner" style="display:none;"><i class="bi bi-arrow-repeat"></i></span>',
                    '      </button>',
                    '    </div>',
                    '  </div>',
                    '</div>'
                ].join('');

                document.body.appendChild(overlay);
                activeModal = overlay;
                lockScroll();

                var dialog = overlay.querySelector('.nb-global-modal-dialog');
                var cancelBtn = overlay.querySelector('#nbModalCancelBtn');
                var confirmBtn = overlay.querySelector('#nbModalConfirmBtn');
                var closeBtn = overlay.querySelector('.nb-global-modal-close');

                // Animate entrance
                requestAnimationFrame(function () {
                    overlay.classList.add('active');
                });

                focusTrapCleanup = setupFocusTrap(dialog);

                // Initial focus on confirm button for prompt action
                setTimeout(function () {
                    if (confirmBtn) confirmBtn.focus();
                }, 100);

                var modalInstance = {
                    element: overlay,
                    close: function () {
                        BiblioModal.close(true);
                    },
                    setLoading: function (loading) {
                        if (confirmBtn) {
                            var textSpan = confirmBtn.querySelector('.nb-btn-text');
                            var spinSpan = confirmBtn.querySelector('.nb-modal-btn-spinner');
                            if (loading) {
                                confirmBtn.disabled = true;
                                if (cancelBtn) cancelBtn.disabled = true;
                                if (closeBtn) closeBtn.style.pointerEvents = 'none';
                                if (textSpan) textSpan.style.display = 'none';
                                if (spinSpan) spinSpan.style.display = 'inline-block';
                            } else {
                                confirmBtn.disabled = false;
                                if (cancelBtn) cancelBtn.disabled = false;
                                if (closeBtn) closeBtn.style.pointerEvents = '';
                                if (textSpan) textSpan.style.display = 'inline-block';
                                if (spinSpan) spinSpan.style.display = 'none';
                            }
                        }
                    }
                };

                // Event Listeners
                if (cancelBtn) {
                    cancelBtn.addEventListener('click', function () {
                        if (typeof options.onCancel === 'function') {
                            options.onCancel(modalInstance);
                        }
                        BiblioModal.close(true);
                    });
                }

                if (closeBtn) {
                    closeBtn.addEventListener('click', function () {
                        if (typeof options.onCancel === 'function') {
                            options.onCancel(modalInstance);
                        }
                        BiblioModal.close(true);
                    });
                }

                if (confirmBtn) {
                    confirmBtn.addEventListener('click', function () {
                        if (typeof options.onConfirm === 'function') {
                            options.onConfirm(modalInstance);
                        } else {
                            BiblioModal.close(true);
                        }
                    });
                }

                // Close on backdrop overlay click
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) {
                        if (typeof options.onCancel === 'function') {
                            options.onCancel(modalInstance);
                        }
                        BiblioModal.close(true);
                    }
                });

                return modalInstance;
            },

            /**
             * Open a centered success/info Toast Modal with countdown timer (Modal 2)
             * @param {Object} options
             * - title: string
             * - message: string
             * - duration: number (default: 2000ms)
             * - onClose: function()
             */
            toast: function (options) {
                options = options || {};
                BiblioModal.close(false);

                var title = options.title || 'Thành công';
                var message = options.message || '';
                var duration = typeof options.duration === 'number' ? options.duration : 2000;

                var overlay = document.createElement('div');
                overlay.className = 'nb-global-modal-overlay nb-global-toast-overlay';
                overlay.setAttribute('role', 'alert');
                overlay.setAttribute('aria-live', 'assertive');

                overlay.innerHTML = [
                    '<div class="nb-global-modal-dialog nb-global-toast-dialog">',
                    '  <div class="nb-global-modal-content nb-global-toast-content">',
                    '    <div class="nb-modal-icon-wrap icon-success">',
                    '      <svg class="nb-checkmark-svg" viewBox="0 0 52 52">',
                    '        <circle class="nb-checkmark-circle" cx="26" cy="26" r="25" fill="none"/>',
                    '        <path class="nb-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>',
                    '      </svg>',
                    '    </div>',
                    '    <h3 class="nb-global-modal-title" style="margin-bottom:6px;">' + title + '</h3>',
                    (message ? '    <div class="nb-global-modal-desc" style="margin-bottom:0; font-size:15px; color:#334155;">' + message + '</div>' : ''),
                    '    <div class="nb-toast-timer-bar"><div class="nb-toast-timer-fill" style="animation-duration:' + (duration / 1000) + 's;"></div></div>',
                    '  </div>',
                    '</div>'
                ].join('');

                document.body.appendChild(overlay);
                activeModal = overlay;
                lockScroll();

                requestAnimationFrame(function () {
                    overlay.classList.add('active');
                });

                if (toastTimer) clearTimeout(toastTimer);
                if (duration > 0) {
                    toastTimer = setTimeout(function () {
                        BiblioModal.close(true);
                        if (typeof options.onClose === 'function') {
                            options.onClose();
                        }
                    }, duration);
                }

                return overlay;
            },

            /**
             * Close currently open modal
             */
            close: function (restoreFocus) {
                if (toastTimer) {
                    clearTimeout(toastTimer);
                    toastTimer = null;
                }

                if (focusTrapCleanup) {
                    focusTrapCleanup();
                    focusTrapCleanup = null;
                }

                if (!activeModal) return;

                var modalToClose = activeModal;
                activeModal = null;

                modalToClose.classList.remove('active');
                unlockScroll();

                setTimeout(function () {
                    if (modalToClose && modalToClose.parentNode) {
                        modalToClose.parentNode.removeChild(modalToClose);
                    }
                }, 250);

                if (restoreFocus && previousActiveElement && typeof previousActiveElement.focus === 'function') {
                    try {
                        previousActiveElement.focus();
                    } catch (err) {}
                    previousActiveElement = null;
                }
            }
        };

        // Global Esc Key Handler
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && activeModal) {
                BiblioModal.close(true);
            }
        });

        // Expose globally for cart deletion, order cancellation, etc.
        window.BiblioModal = BiblioModal;

        // =====================================================================
        // LOGOUT WORKFLOW INTERCEPTION (Centered Confirmation Modal & 2s Toast)
        // =====================================================================
        document.addEventListener('click', function (e) {
            var logoutTrigger = e.target.closest('.sp-acc-logout, .nb-logout-trigger, a[href*="action=logout"], a[href*="customer-logout"]');
            if (!logoutTrigger) return;

            e.preventDefault();

            // Close user account dropdown if open
            var accWrap = document.getElementById('spAccDropdownWrap');
            if (accWrap) {
                accWrap.classList.remove('open');
                var accTrigger = document.getElementById('spAccTriggerBtn');
                if (accTrigger) accTrigger.setAttribute('aria-expanded', 'false');
            }

            // Close mobile nav drawer if open
            var mobileDrawer = document.getElementById('nbMobileDrawer');
            if (mobileDrawer && mobileDrawer.classList.contains('active')) {
                mobileDrawer.classList.remove('active');
            }

            // Extract logout URL & nonce
            var logoutUrl = logoutTrigger.getAttribute('data-logout-url') || logoutTrigger.getAttribute('href') || (typeof nbook_ajax !== 'undefined' ? nbook_ajax.logout_url : '');
            var logoutNonce = logoutTrigger.getAttribute('data-logout-nonce');

            if (!logoutNonce && logoutUrl) {
                var nonceMatch = logoutUrl.match(/_wpnonce=([a-f0-9]+)/i);
                if (nonceMatch) {
                    logoutNonce = nonceMatch[1];
                }
            }
            if (!logoutNonce && typeof nbook_ajax !== 'undefined') {
                logoutNonce = nbook_ajax.logout_nonce || nbook_ajax.auth_nonce;
            }

            // Open Modal 1: Confirmation Dialog
            BiblioModal.confirm({
                title: 'Bạn có chắc muốn đăng xuất?',
                message: 'Phiên làm việc của bạn trên Biblio sẽ kết thúc. Bạn có thể đăng nhập lại bất cứ lúc nào.',
                icon: 'logout',
                confirmText: 'Đăng xuất',
                cancelText: 'Hủy',
                confirmClass: 'nb-global-modal-btn-orange',
                onConfirm: function (modalInstance) {
                    modalInstance.setLoading(true);

                    var ajaxUrl = typeof nbook_ajax !== 'undefined' ? nbook_ajax.ajax_url : '/wp-admin/admin-ajax.php';
                    var homeUrl = typeof nbook_ajax !== 'undefined' ? nbook_ajax.home_url : '/';

                    var formData = new FormData();
                    formData.append('action', 'nbook_ajax_logout');
                    formData.append('_wpnonce', logoutNonce || '');

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        modalInstance.close();

                        // Open Modal 2: Centered Toast / Success Modal (2s auto-close then home)
                        BiblioModal.toast({
                            title: 'Đã đăng xuất thành công.',
                            message: 'Hẹn gặp lại bạn!',
                            duration: 2000,
                            onClose: function () {
                                window.location.href = (data && data.data && data.data.redirect) ? data.data.redirect : homeUrl;
                            }
                        });
                    })
                    .catch(function () {
                        // Fallback: standard WordPress navigation
                        modalInstance.close();
                        window.location.href = logoutUrl || homeUrl;
                    });
                }
            });
        });

        // =====================================================================
        // DATA-ATTRIBUTE DELEGATION FOR OTHER ACTIONS (Cart, Order Cancel, etc.)
        // Usage: <button data-biblio-confirm="Bạn có chắc muốn xóa giỏ hàng?" ...>
        // =====================================================================
        document.addEventListener('click', function (e) {
            var confirmBtn = e.target.closest('[data-biblio-confirm]');
            if (!confirmBtn || confirmBtn.closest('.sp-acc-logout, .nb-logout-trigger')) return;

            e.preventDefault();
            var message = confirmBtn.getAttribute('data-biblio-confirm');
            var title = confirmBtn.getAttribute('data-biblio-title') || 'Xác nhận thao tác';
            var confirmText = confirmBtn.getAttribute('data-biblio-btn') || 'Xác nhận';
            var isDanger = confirmBtn.getAttribute('data-biblio-danger') === 'true';

            BiblioModal.confirm({
                title: title,
                message: message,
                icon: isDanger ? 'danger' : 'question',
                confirmText: confirmText,
                cancelText: 'Hủy',
                confirmClass: isDanger ? 'nb-global-modal-btn-danger' : 'nb-global-modal-btn-orange',
                onConfirm: function (modalInstance) {
                    modalInstance.close();
                    if (confirmBtn.tagName === 'A' && confirmBtn.href) {
                        window.location.href = confirmBtn.href;
                    } else if (confirmBtn.type === 'submit' && confirmBtn.form) {
                        confirmBtn.form.submit();
                    } else {
                        var customEvent = new CustomEvent('biblio:confirmed', { bubbles: true, detail: { trigger: confirmBtn } });
                        confirmBtn.dispatchEvent(customEvent);
                    }
                }
            });
        });
    })();

    // =========================================================================
    // 14. BIBLIO CART MODULE (AJAX Recalculations, Checkbox Selection, Modals)
    // =========================================================================
    (function initBiblioCart() {
        var cartWrap = document.getElementById('nbCartPageWrap');
        if (!cartWrap) return;

        var ajaxUrl = typeof nbook_ajax !== 'undefined' ? nbook_ajax.ajax_url : '/wp-admin/admin-ajax.php';

        function formatVND(num) {
            return new Intl.NumberFormat('vi-VN').format(Math.round(num)) + '₫';
        }

        // 1. Recalculate selected totals based purely on checked rows
        function recalculateSelectedTotals() {
            var itemCheckboxes = cartWrap.querySelectorAll('.nb-item-checkbox');
            var checkAllTop = document.getElementById('nbCheckAllTop');
            var checkAllBottom = document.getElementById('nbCheckAllBottom');
            var selectedCountEl = document.getElementById('nbSelectedCount');
            var selectedTotalEl = document.getElementById('nbSelectedTotalDisplay');
            var savingsRow = document.getElementById('nbSavingsRow');
            var savingsDisplay = document.getElementById('nbSavingsDisplay');
            var proceedBtn = document.getElementById('nbProceedCheckoutBtn');

            var totalSelectedQty = 0;
            var totalSelectedAmount = 0;
            var totalRegularAmount = 0;
            var checkedCount = 0;
            var totalItems = itemCheckboxes.length;

            itemCheckboxes.forEach(function (cb) {
                if (cb.checked) {
                    checkedCount++;
                    var qty = parseInt(cb.getAttribute('data-qty') || '1', 10);
                    var unitPrice = parseFloat(cb.getAttribute('data-unit-price') || '0');
                    var regPrice = parseFloat(cb.getAttribute('data-regular-price') || '0');
                    if (regPrice <= 0) regPrice = unitPrice;

                    totalSelectedQty += qty;
                    totalSelectedAmount += (unitPrice * qty);
                    totalRegularAmount += (regPrice * qty);
                }
            });

            // Update display
            if (selectedCountEl) selectedCountEl.textContent = checkedCount;
            if (selectedTotalEl) selectedTotalEl.textContent = formatVND(totalSelectedAmount);

            var totalSavings = Math.max(0, totalRegularAmount - totalSelectedAmount);
            if (savingsRow && savingsDisplay) {
                if (totalSavings > 0) {
                    savingsDisplay.textContent = formatVND(totalSavings);
                    savingsRow.style.display = 'block';
                } else {
                    savingsRow.style.display = 'none';
                }
            }

            // Sync Check All inputs
            var allChecked = (totalItems > 0 && checkedCount === totalItems);
            if (checkAllTop) checkAllTop.checked = allChecked;
            if (checkAllBottom) checkAllBottom.checked = allChecked;

            // Enable/disable proceed button
            if (proceedBtn) {
                if (checkedCount === 0) {
                    proceedBtn.classList.add('disabled');
                    proceedBtn.style.opacity = '0.6';
                    proceedBtn.style.cursor = 'not-allowed';
                } else {
                    proceedBtn.classList.remove('disabled');
                    proceedBtn.style.opacity = '1';
                    proceedBtn.style.cursor = 'pointer';
                }
            }

            // Update freeship bar dynamically based on selected / cart subtotal
            updateFreeshipBar(totalSelectedAmount);
        }

        function updateFreeshipBar(amount) {
            var freeshipCard = document.getElementById('nbFreeshipCard');
            if (!freeshipCard) return;

            var threshold = 250000;
            var isFreeship = amount >= threshold;
            var remaining = Math.max(0, threshold - amount);
            var percent = Math.min(100, Math.round((amount / threshold) * 100));

            var iconWrap = freeshipCard.querySelector('.nb-fs-icon-wrap');
            var textWrap = freeshipCard.querySelector('.nb-fs-text-wrap');

            if (iconWrap) {
                if (isFreeship) {
                    iconWrap.className = 'nb-fs-icon-wrap success';
                    iconWrap.innerHTML = '<i class="bi bi-patch-check-fill"></i>';
                } else {
                    iconWrap.className = 'nb-fs-icon-wrap';
                    iconWrap.innerHTML = '<i class="bi bi-truck"></i>';
                }
            }

            if (textWrap) {
                if (isFreeship) {
                    textWrap.innerHTML = '<span class="nb-fs-msg success">🎉 Chúc mừng! Đơn hàng của bạn đã đủ điều kiện <strong>MIỄN PHÍ VẬN CHUYỂN</strong> toàn quốc!</span>' +
                        '<div class="nb-fs-track"><div class="nb-fs-fill full" style="width:100%;"></div></div>';
                } else {
                    textWrap.innerHTML = '<span class="nb-fs-msg">Mua thêm <strong class="nb-fs-amount">' + formatVND(remaining) + '</strong> để được <strong>MIỄN PHÍ VẬN CHUYỂN</strong> toàn quốc!</span>' +
                        '<div class="nb-fs-track"><div class="nb-fs-fill" style="width:' + percent + '%;"></div></div>';
                }
            }
        }

        // 2. Event: Check All (Top & Bottom)
        function toggleAllCheckboxes(checked) {
            var itemCheckboxes = cartWrap.querySelectorAll('.nb-item-checkbox');
            itemCheckboxes.forEach(function (cb) {
                cb.checked = checked;
            });
            recalculateSelectedTotals();
        }

        var checkAllTop = document.getElementById('nbCheckAllTop');
        var checkAllBottom = document.getElementById('nbCheckAllBottom');
        if (checkAllTop) {
            checkAllTop.addEventListener('change', function () {
                toggleAllCheckboxes(this.checked);
            });
        }
        if (checkAllBottom) {
            checkAllBottom.addEventListener('change', function () {
                toggleAllCheckboxes(this.checked);
            });
        }

        // 3. Event: Row Checkbox Changed
        cartWrap.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('nb-item-checkbox')) {
                recalculateSelectedTotals();
            }
        });

        // 4. Quantity Increment / Decrement / Input
        function updateCartQtyAjax(cartKey, newQty, rowEl) {
            rowEl.classList.add('nb-updating');
            var formData = new FormData();
            formData.append('action', 'nbook_cart_update_qty');
            formData.append('cart_key', cartKey);
            formData.append('quantity', newQty);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                rowEl.classList.remove('nb-updating');
                if (res.success && res.data) {
                    // Update line total in row
                    var lineTotalEl = document.getElementById('nbLineTotal-' + cartKey);
                    if (lineTotalEl) lineTotalEl.innerHTML = res.data.line_total;

                    // Update input and buttons in row
                    var numInput = rowEl.querySelector('.nb-qty-num-input');
                    var decBtn = rowEl.querySelector('.nb-qty-dec');
                    if (numInput) numInput.value = res.data.quantity;
                    if (decBtn) decBtn.disabled = (res.data.quantity <= 1);

                    // Update checkbox data-qty
                    var cb = rowEl.querySelector('.nb-item-checkbox');
                    if (cb) cb.setAttribute('data-qty', res.data.quantity);

                    // Update header badges
                    var badges = document.querySelectorAll('.nb-cart-badge, .sp-cart-count-badge');
                    badges.forEach(function (b) { b.textContent = res.data.cart_count; });

                    // Recalculate totals
                    recalculateSelectedTotals();
                } else {
                    if (window.BiblioModal) {
                        BiblioModal.toast({ title: 'Lỗi', message: res.data ? res.data.message : 'Không thể cập nhật số lượng', duration: 2000 });
                    }
                }
            })
            .catch(function () {
                rowEl.classList.remove('nb-updating');
            });
        }

        cartWrap.addEventListener('click', function (e) {
            var incBtn = e.target.closest('.nb-qty-inc');
            var decBtn = e.target.closest('.nb-qty-dec');

            if (incBtn) {
                var cartKey = incBtn.getAttribute('data-cart-key');
                var row = document.getElementById('nbCartRow-' + cartKey);
                if (row) {
                    var input = row.querySelector('.nb-qty-num-input');
                    var currentVal = parseInt(input.value || '1', 10);
                    var maxVal = parseInt(input.getAttribute('max') || '99', 10);
                    if (currentVal < maxVal) {
                        updateCartQtyAjax(cartKey, currentVal + 1, row);
                    }
                }
            }

            if (decBtn) {
                var cartKey = decBtn.getAttribute('data-cart-key');
                var row = document.getElementById('nbCartRow-' + cartKey);
                if (row) {
                    var input = row.querySelector('.nb-qty-num-input');
                    var currentVal = parseInt(input.value || '1', 10);
                    if (currentVal > 1) {
                        updateCartQtyAjax(cartKey, currentVal - 1, row);
                    }
                }
            }
        });

        cartWrap.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('nb-qty-num-input')) {
                var input = e.target;
                var cartKey = input.getAttribute('data-cart-key');
                var row = document.getElementById('nbCartRow-' + cartKey);
                if (row) {
                    var val = parseInt(input.value, 10);
                    var min = parseInt(input.getAttribute('min') || '1', 10);
                    var max = parseInt(input.getAttribute('max') || '99', 10);
                    if (isNaN(val) || val < min) val = min;
                    if (val > max) val = max;
                    input.value = val;
                    updateCartQtyAjax(cartKey, val, row);
                }
            }
        });

        // 5. Remove Single Cart Item (Intercepted with BiblioModal)
        cartWrap.addEventListener('click', function (e) {
            var removeBtn = e.target.closest('.nb-item-remove-trigger');
            if (!removeBtn) return;

            var cartKey = removeBtn.getAttribute('data-cart-key');
            var productTitle = removeBtn.getAttribute('data-product-title') || 'cuốn sách này';
            var row = document.getElementById('nbCartRow-' + cartKey);

            if (window.BiblioModal) {
                BiblioModal.confirm({
                    title: 'Xóa sách khỏi giỏ hàng?',
                    message: 'Bạn có chắc chắn muốn bỏ cuốn sách <strong>"' + productTitle + '"</strong> ra khỏi giỏ hàng?',
                    icon: 'danger',
                    confirmText: 'Xóa sách',
                    cancelText: 'Giữ lại',
                    confirmClass: 'nb-global-modal-btn-danger',
                    onConfirm: function (modalInstance) {
                        modalInstance.setLoading(true);

                        var formData = new FormData();
                        formData.append('action', 'nbook_cart_remove_item');
                        formData.append('cart_key', cartKey);

                        fetch(ajaxUrl, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin'
                        })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            modalInstance.close();
                            if (res.success && res.data) {
                                if (row) {
                                    row.style.transition = 'all 0.3s ease';
                                    row.style.opacity = '0';
                                    row.style.transform = 'translateX(-30px)';
                                    setTimeout(function () {
                                        if (row.parentNode) row.parentNode.removeChild(row);
                                        recalculateSelectedTotals();

                                        if (res.data.is_empty) {
                                            window.location.reload();
                                        }
                                    }, 300);
                                }

                                var badges = document.querySelectorAll('.nb-cart-badge, .sp-cart-count-badge');
                                badges.forEach(function (b) { b.textContent = res.data.cart_count; });

                                BiblioModal.toast({
                                    title: 'Đã xóa sản phẩm',
                                    message: 'Sản phẩm đã được xóa khỏi giỏ hàng.',
                                    duration: 1500
                                });
                            }
                        })
                        .catch(function () {
                            modalInstance.close();
                        });
                    }
                });
            }
        });

        // 6. Batch Delete ("Xóa đã chọn")
        var deleteSelectedBtn = document.getElementById('nbDeleteSelectedBtn');
        if (deleteSelectedBtn) {
            deleteSelectedBtn.addEventListener('click', function () {
                var checkedCbs = cartWrap.querySelectorAll('.nb-item-checkbox:checked');
                if (checkedCbs.length === 0) {
                    if (window.BiblioModal) {
                        BiblioModal.toast({
                            title: 'Chưa chọn sản phẩm',
                            message: 'Vui lòng tích chọn ít nhất 1 sản phẩm bạn muốn xóa.',
                            duration: 2000
                        });
                    }
                    return;
                }

                var count = checkedCbs.length;
                var keys = [];
                checkedCbs.forEach(function (cb) {
                    keys.push(cb.getAttribute('data-cart-key'));
                });

                if (window.BiblioModal) {
                    BiblioModal.confirm({
                        title: 'Xóa ' + count + ' sản phẩm đã chọn?',
                        message: 'Tất cả các sản phẩm được chọn sẽ bị gỡ bỏ khỏi giỏ hàng của bạn.',
                        icon: 'danger',
                        confirmText: 'Xóa tất cả',
                        cancelText: 'Hủy',
                        confirmClass: 'nb-global-modal-btn-danger',
                        onConfirm: function (modalInstance) {
                            modalInstance.setLoading(true);

                            var formData = new FormData();
                            formData.append('action', 'nbook_cart_remove_multiple');
                            keys.forEach(function (k) {
                                formData.append('cart_keys[]', k);
                            });

                            fetch(ajaxUrl, {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin'
                            })
                            .then(function (r) { return r.json(); })
                            .then(function (res) {
                                modalInstance.close();
                                if (res.success && res.data) {
                                    keys.forEach(function (k) {
                                        var row = document.getElementById('nbCartRow-' + k);
                                        if (row && row.parentNode) row.parentNode.removeChild(row);
                                    });

                                    recalculateSelectedTotals();

                                    var badges = document.querySelectorAll('.nb-cart-badge, .sp-cart-count-badge');
                                    badges.forEach(function (b) { b.textContent = res.data.cart_count; });

                                    if (res.data.is_empty) {
                                        window.location.reload();
                                    } else {
                                        BiblioModal.toast({
                                            title: 'Đã xóa sản phẩm',
                                            message: 'Đã xóa ' + count + ' sản phẩm khỏi giỏ hàng.',
                                            duration: 1500
                                        });
                                    }
                                }
                            })
                            .catch(function () {
                                modalInstance.close();
                            });
                        }
                    });
                }
            });
        }

        // 7. Voucher Application & Preset Buttons
        function applyCouponAjax(code) {
            var feedback = document.getElementById('nbCouponFeedback');
            var formData = new FormData();
            formData.append('action', 'nbook_cart_apply_coupon');
            formData.append('coupon_code', code);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success && res.data) {
                    if (window.BiblioModal) {
                        BiblioModal.toast({
                            title: 'Thành công',
                            message: res.data.message,
                            duration: 1800,
                            onClose: function () {
                                window.location.reload();
                            }
                        });
                    } else {
                        window.location.reload();
                    }
                } else {
                    if (feedback) {
                        feedback.style.display = 'block';
                        feedback.className = 'nb-coupon-feedback error';
                        feedback.textContent = res.data ? res.data.message : 'Mã giảm giá không hợp lệ.';
                    }
                }
            });
        }

        var applyCouponBtn = document.getElementById('nbApplyCouponBtn');
        var couponCodeInput = document.getElementById('nbCouponCodeInput');
        if (applyCouponBtn && couponCodeInput) {
            applyCouponBtn.addEventListener('click', function () {
                var val = couponCodeInput.value.trim();
                if (val) {
                    applyCouponAjax(val);
                } else {
                    couponCodeInput.focus();
                }
            });
            couponCodeInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var val = couponCodeInput.value.trim();
                    if (val) applyCouponAjax(val);
                }
            });
        }

        // Quick Preset Voucher Cards
        cartWrap.addEventListener('click', function (e) {
            var vpcBtn = e.target.closest('.nb-vpc-apply-btn');
            if (vpcBtn) {
                var code = vpcBtn.getAttribute('data-code');
                if (code) applyCouponAjax(code);
            }

            var removeCouponBtn = e.target.closest('.nb-remove-coupon-btn');
            if (removeCouponBtn) {
                var code = removeCouponBtn.getAttribute('data-code');
                if (code) {
                    var formData = new FormData();
                    formData.append('action', 'nbook_cart_remove_coupon');
                    formData.append('coupon_code', code);

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        window.location.reload();
                    });
                }
            }
        });

        // 8. Proceed to Checkout Button ("Mua Hàng")
        var proceedBtn = document.getElementById('nbProceedCheckoutBtn');
        if (proceedBtn) {
            proceedBtn.addEventListener('click', function () {
                var checkedCbs = cartWrap.querySelectorAll('.nb-item-checkbox:checked');
                if (checkedCbs.length === 0) {
                    if (window.BiblioModal) {
                        BiblioModal.toast({
                            title: 'Chưa chọn sản phẩm',
                            message: 'Vui lòng tích chọn ít nhất 1 sản phẩm để tiến hành mua hàng!',
                            duration: 2500
                        });
                    }
                    return;
                }

                var selectedKeys = [];
                checkedCbs.forEach(function (cb) {
                    selectedKeys.push(cb.getAttribute('data-cart-key'));
                });

                proceedBtn.disabled = true;
                proceedBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang chuyển tiếp...';

                var formData = new FormData();
                formData.append('action', 'nbook_save_checkout_selection');
                selectedKeys.forEach(function (k) {
                    formData.append('selected_keys[]', k);
                });

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success && res.data && res.data.redirect_url) {
                        window.location.href = res.data.redirect_url;
                    } else {
                        window.location.href = '/thanh-toan/';
                    }
                })
                .catch(function () {
                    window.location.href = '/thanh-toan/';
                });
            });
        }

        // Initialize calculations on page load
        recalculateSelectedTotals();
    })();

    // =========================================================================
    // 15. BIBLIO CHECKOUT MODULE (Address Edit, Payment Radios, Shipping, Copy)
    // =========================================================================
    (function initBiblioCheckout() {
        var coWrap = document.getElementById('nbCheckoutPageWrap');
        if (!coWrap) return;

        var ajaxUrl = typeof nbook_ajax !== 'undefined' ? nbook_ajax.ajax_url : '/wp-admin/admin-ajax.php';

        function formatVND(num) {
            return new Intl.NumberFormat('vi-VN').format(Math.round(num)) + '₫';
        }

        // 1. Address Quick Edit Toggle
        var toggleAddressBtn = document.getElementById('nbToggleAddressBtn');
        var displayBox = document.getElementById('nbAddressDisplayBox');
        var editBox = document.getElementById('nbAddressEditBox');
        var addressBtnText = document.getElementById('nbAddressBtnText');
        var saveAddressBtn = document.getElementById('nbSaveAddressBtn');

        if (toggleAddressBtn && displayBox && editBox) {
            toggleAddressBtn.addEventListener('click', function () {
                var isEditing = editBox.style.display !== 'none';
                if (isEditing) {
                    editBox.style.display = 'none';
                    displayBox.style.display = 'block';
                    if (addressBtnText) addressBtnText.textContent = 'Thay đổi';
                } else {
                    editBox.style.display = 'block';
                    displayBox.style.display = 'none';
                    if (addressBtnText) addressBtnText.textContent = 'Thu gọn';
                }
            });
        }

        if (saveAddressBtn && editBox && displayBox) {
            saveAddressBtn.addEventListener('click', function () {
                var fnInput = document.getElementById('billing_first_name');
                var phoneInput = document.getElementById('billing_phone');
                var emailInput = document.getElementById('billing_email');
                var addrInput = document.getElementById('billing_address_1');
                var cityInput = document.getElementById('billing_city');
                var stateInput = document.getElementById('billing_state');

                if (!fnInput.value.trim() || !phoneInput.value.trim() || !addrInput.value.trim()) {
                    if (window.BiblioModal) {
                        BiblioModal.toast({
                            title: 'Thiếu thông tin',
                            message: 'Vui lòng nhập đầy đủ Họ tên, Số điện thoại và Địa chỉ nhận hàng!',
                            duration: 2500
                        });
                    }
                    return;
                }

                // Update display
                var nameEl = document.getElementById('nbDisplayFullName');
                var phoneEl = document.getElementById('nbDisplayPhone');
                var emailEl = document.getElementById('nbDisplayEmail');
                var fullAddrEl = document.getElementById('nbDisplayFullAddress');

                if (nameEl) nameEl.textContent = fnInput.value.trim();
                if (phoneEl) phoneEl.textContent = phoneInput.value.trim();
                if (emailEl) emailEl.textContent = emailInput.value.trim() || 'Chưa cập nhật email';
                if (fullAddrEl) {
                    var fullAddr = addrInput.value.trim();
                    if (stateInput && stateInput.value.trim()) fullAddr += ', ' + stateInput.value.trim();
                    if (cityInput && cityInput.value.trim()) fullAddr += ', ' + cityInput.value.trim();
                    fullAddrEl.textContent = fullAddr;
                }

                editBox.style.display = 'none';
                displayBox.style.display = 'block';
                if (addressBtnText) addressBtnText.textContent = 'Thay đổi';

                if (window.BiblioModal) {
                    BiblioModal.toast({
                        title: 'Đã lưu địa chỉ',
                        message: 'Thông tin nhận hàng đã được cập nhật thành công.',
                        duration: 1500
                    });
                }
            });
        }

        // 2. Shipping Method Selection
        var shippingCards = coWrap.querySelectorAll('.nb-shipping-radio-card');
        var shippingValEl = document.getElementById('nbCoShippingVal');
        var grandTotalEl = document.getElementById('nbCoGrandTotal');

        shippingCards.forEach(function (card) {
            card.addEventListener('click', function () {
                var radio = card.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    shippingCards.forEach(function (c) { c.classList.remove('active'); });
                    card.classList.add('active');

                    var cost = parseFloat(radio.getAttribute('data-cost') || '0');
                    if (shippingValEl) {
                        shippingValEl.innerHTML = (cost === 0) ? '<span class="text-success">0₫ (Miễn phí)</span>' : formatVND(cost);
                    }

                    // Trigger WooCommerce checkout update
                    if (window.jQuery) {
                        jQuery('body').trigger('update_checkout');
                    }
                }
            });
        });

        // 3. Payment Method Radio Cards Selection
        var paymentCards = coWrap.querySelectorAll('.nb-payment-method-card');
        paymentCards.forEach(function (card) {
            card.addEventListener('click', function (e) {
                // If user clicked inside the copy button or info box, avoid resetting
                if (e.target.closest('.nb-btn-copy-sm, .nb-bacs-details-box')) return;

                var radio = card.querySelector('input[name="payment_method"]');
                if (radio) {
                    radio.checked = true;
                    paymentCards.forEach(function (c) {
                        c.classList.remove('selected');
                        var bacsBox = c.querySelector('.nb-bacs-details-box');
                        var simBox = c.querySelector('.nb-simulated-alert-box');
                        if (bacsBox) bacsBox.style.display = 'none';
                        if (simBox) simBox.style.display = 'none';
                    });

                    card.classList.add('selected');

                    var gw = card.getAttribute('data-gateway');
                    if (gw === 'bacs') {
                        var bacsBox = card.querySelector('.nb-bacs-details-box');
                        if (bacsBox) bacsBox.style.display = 'block';
                    } else if (gw === 'biblio_simulated_ewallet') {
                        var simBox = card.querySelector('.nb-simulated-alert-box');
                        if (simBox) simBox.style.display = 'block';
                    }
                }
            });
        });

        // 4. Reward Points Checkbox Toggle
        var pointsCheckbox = document.getElementById('nbUsePointsCheckbox');
        if (pointsCheckbox) {
            pointsCheckbox.addEventListener('change', function () {
                var isChecked = this.checked;
                var formData = new FormData();
                formData.append('action', 'nbook_toggle_reward_points');
                formData.append('enable', isChecked ? 'true' : 'false');

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (window.jQuery) {
                        jQuery('body').trigger('update_checkout');
                    }
                    var pointsRow = document.getElementById('nbCoPointsRow');
                    if (pointsRow) pointsRow.style.display = isChecked ? 'flex' : 'none';
                    if (res.success && res.data) {
                        if (grandTotalEl && res.data.cart_total) {
                            grandTotalEl.innerHTML = res.data.cart_total;
                        }
                        var coPointsVal = document.getElementById('nbCoPointsVal');
                        if (coPointsVal && res.data.discount_vnd) {
                            coPointsVal.innerHTML = '-' + res.data.discount_vnd;
                        }
                    }
                });
            });
        }

        // 5. Checkout Voucher Input & Apply
        var coVoucherBtn = document.getElementById('nbCheckoutApplyCouponBtn');
        var coVoucherInput = document.getElementById('nbCheckoutCouponInput');
        if (coVoucherBtn && coVoucherInput) {
            coVoucherBtn.addEventListener('click', function () {
                var code = coVoucherInput.value.trim();
                if (!code) {
                    coVoucherInput.focus();
                    return;
                }

                var formData = new FormData();
                formData.append('action', 'nbook_cart_apply_coupon');
                formData.append('coupon_code', code);

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        window.location.reload();
                    } else {
                        if (window.BiblioModal) {
                            BiblioModal.toast({
                                title: 'Thông báo',
                                message: res.data ? res.data.message : 'Mã không hợp lệ.',
                                duration: 2500
                            });
                        }
                    }
                });
            });
        }

        // Remove applied voucher chips in checkout
        coWrap.addEventListener('click', function (e) {
            var chipRemove = e.target.closest('.nb-co-chip-remove');
            if (chipRemove) {
                var code = chipRemove.getAttribute('data-code');
                if (code) {
                    var formData = new FormData();
                    formData.append('action', 'nbook_cart_remove_coupon');
                    formData.append('coupon_code', code);

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(function () {
                        window.location.reload();
                    });
                }
            }
        });

        // 6. Global Copy to Clipboard Buttons
        document.addEventListener('click', function (e) {
            var copyBtn = e.target.closest('[data-copy]');
            if (!copyBtn) return;

            e.preventDefault();
            var textToCopy = copyBtn.getAttribute('data-copy');
            if (!textToCopy) return;

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textToCopy).then(function () {
                    showCopySuccess(copyBtn);
                }).catch(function () {
                    fallbackCopy(textToCopy, copyBtn);
                });
            } else {
                fallbackCopy(textToCopy, copyBtn);
            }
        });

        function fallbackCopy(text, btn) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                showCopySuccess(btn);
            } catch (err) {}
            document.body.removeChild(ta);
        }

        function showCopySuccess(btn) {
            var origHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2 text-success"></i> Đã sao chép!';
            btn.classList.add('copied');
            setTimeout(function () {
                btn.innerHTML = origHtml;
                btn.classList.remove('copied');
            }, 2000);
        }

        // 7. Auto-sync Name & Shipping Hidden Fields
        var fnInput = document.getElementById('billing_first_name');
        function syncCheckoutNames() {
            if (!fnInput) return;
            var val = fnInput.value.trim();
            var parts = val.split(' ');
            var lastName = parts.length > 1 ? parts.pop() : val;
            var lnField = document.getElementById('billing_last_name');
            if (lnField) lnField.value = lastName || 'Biblio';
            var sfnField = document.getElementById('shipping_first_name');
            if (sfnField) sfnField.value = val || 'Khách hàng';
            var slnField = document.getElementById('shipping_last_name');
            if (slnField) slnField.value = lastName || 'Biblio';

            var addrInput = document.getElementById('billing_address_1');
            var saddrField = document.getElementById('shipping_address_1');
            if (addrInput && saddrField) saddrField.value = addrInput.value.trim();

            var cityInput = document.getElementById('billing_city');
            var scityField = document.getElementById('shipping_city');
            if (cityInput && scityField) scityField.value = cityInput.value.trim();

            var stateInput = document.getElementById('billing_state');
            var sstateField = document.getElementById('shipping_state');
            if (stateInput && sstateField) sstateField.value = stateInput.value.trim();
        }
        if (fnInput) {
            fnInput.addEventListener('input', syncCheckoutNames);
            fnInput.addEventListener('change', syncCheckoutNames);
            syncCheckoutNames();
        }

        // 8. Place Order Form Submit Interception & Loading
        var form = coWrap.querySelector('form.checkout');
        var placeOrderBtn = document.getElementById('place_order');

        function resetPlaceOrderBtn() {
            if (!placeOrderBtn) return;
            var txtSpan = placeOrderBtn.querySelector('.nb-btn-order-txt');
            var spinSpan = placeOrderBtn.querySelector('.nb-btn-order-spinner');
            if (txtSpan) txtSpan.style.display = '';
            if (spinSpan) spinSpan.style.display = 'none';
            placeOrderBtn.disabled = false;
        }

        if (form && placeOrderBtn) {
            form.addEventListener('submit', function (e) {
                var terms = document.getElementById('nbTermsCheckbox');
                if (terms && !terms.checked) {
                    e.preventDefault();
                    resetPlaceOrderBtn();
                    if (window.BiblioModal) {
                        BiblioModal.toast({
                            title: 'Điều khoản dịch vụ',
                            message: 'Vui lòng tích chọn đồng ý với Điều khoản dịch vụ của Biblio trước khi đặt hàng!',
                            duration: 3000
                        });
                    }
                    return false;
                }

                // Check basic required fields
                var fnVal = fnInput ? fnInput.value.trim() : '';
                var phVal = document.getElementById('billing_phone') ? document.getElementById('billing_phone').value.trim() : '';
                var adVal = document.getElementById('billing_address_1') ? document.getElementById('billing_address_1').value.trim() : '';
                if (!fnVal || !phVal || !adVal) {
                    e.preventDefault();
                    resetPlaceOrderBtn();
                    var editBox = document.getElementById('nbAddressEditBox');
                    var displayBox = document.getElementById('nbAddressDisplayBox');
                    if (editBox && displayBox) {
                        editBox.style.display = 'block';
                        displayBox.style.display = 'none';
                    }
                    if (window.BiblioModal) {
                        BiblioModal.toast({
                            title: 'Thiếu thông tin nhận hàng',
                            message: 'Vui lòng điền đầy đủ Họ tên, Số điện thoại và Địa chỉ nhận hàng!',
                            duration: 3500
                        });
                    }
                    return false;
                }

                syncCheckoutNames();

                // Show spinner on button
                var txtSpan = placeOrderBtn.querySelector('.nb-btn-order-txt');
                var spinSpan = placeOrderBtn.querySelector('.nb-btn-order-spinner');
                if (txtSpan) txtSpan.style.display = 'none';
                if (spinSpan) spinSpan.style.display = 'inline-block';
            });

            // Listen for WooCommerce checkout errors to reset spinner
            if (typeof jQuery !== 'undefined') {
                jQuery(document.body).on('checkout_error', function (event, errorMessage) {
                    resetPlaceOrderBtn();
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = errorMessage || '';
                    var cleanText = tempDiv.textContent.trim() || 'Có lỗi xảy ra khi xử lý đơn hàng, vui lòng thử lại.';
                    if (window.BiblioModal) {
                        BiblioModal.toast({
                            title: 'Lỗi đặt hàng',
                            message: cleanText,
                            duration: 5000
                        });
                    }
                    var noticeBox = document.querySelector('.woocommerce-NoticeGroup-checkout');
                    if (noticeBox) {
                        noticeBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            }
        }
    })();

    // Auto-redirect hash navigation for My Account (#voucher, #diem-thuong)
    (function () {
        if (window.location.pathname.indexOf('/tai-khoan') !== -1) {
            var hash = window.location.hash;
            var cleanPath = window.location.pathname.replace(/\/+$/, '');
            if (hash === '#voucher') {
                window.location.replace(cleanPath + '/voucher/');
            } else if (hash === '#diem-thuong') {
                window.location.replace(cleanPath + '/diem-thuong/');
            }
        }
    })();

    // Shopee-style quantity stepper on single product page & cart
    (function () {
        document.addEventListener('click', function (e) {
            var minusBtn = e.target.closest('.nb-qty-minus');
            var plusBtn = e.target.closest('.nb-qty-plus');
            if (!minusBtn && !plusBtn) return;

            e.preventDefault();
            var wrap = (minusBtn || plusBtn).closest('.nb-qty-wrap');
            if (!wrap) return;
            var input = wrap.querySelector('input.qty, input.nb-qty-input');
            if (!input) return;

            var currentVal = parseFloat(input.value) || 1;
            var min = parseFloat(input.getAttribute('min')) || 1;
            var maxAttr = input.getAttribute('max');
            var max = (maxAttr && maxAttr !== '') ? parseFloat(maxAttr) : Infinity;
            var step = parseFloat(input.getAttribute('step')) || 1;

            if (minusBtn) {
                var newVal = Math.max(min, currentVal - step);
                input.value = newVal;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            } else if (plusBtn) {
                var newVal = (max !== Infinity) ? Math.min(max, currentVal + step) : (currentVal + step);
                input.value = newVal;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    })();

    // Shopee "Mua Ngay" click handler
    (function () {
        document.addEventListener('click', function (e) {
            var buyNowBtn = e.target.closest('.nb-btn-shopee-buy-now');
            if (!buyNowBtn) return;

            var form = buyNowBtn.closest('form.cart');
            if (!form) return;

            var buyNowInput = form.querySelector('input[name="nb_buy_now"]');
            if (!buyNowInput) {
                buyNowInput = document.createElement('input');
                buyNowInput.type = 'hidden';
                buyNowInput.name = 'nb_buy_now';
                buyNowInput.value = '1';
                form.appendChild(buyNowInput);
            } else {
                buyNowInput.value = '1';
            }
        });
    })();

    // Biblio Rewards (Shopee-Style Loyalty Modal Handler)
    (function () {
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('.nb-trigger-tier-modal');
            if (trigger) {
                e.preventDefault();
                var modal = document.getElementById('nbTierModalOverlay');
                if (modal) {
                    modal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
                return;
            }

            var closeBtn = e.target.closest('#nbCloseTierModalBtn, #nbBtnCloseModalBottom');
            if (closeBtn) {
                e.preventDefault();
                var modal = document.getElementById('nbTierModalOverlay');
                if (modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }
                return;
            }

            var overlay = document.getElementById('nbTierModalOverlay');
            if (overlay && e.target === overlay) {
                overlay.style.display = 'none';
                document.body.style.overflow = '';
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var modal = document.getElementById('nbTierModalOverlay');
                if (modal && modal.style.display === 'flex') {
                    modal.style.display = 'none';
                    document.body.style.overflow = '';
                }
            }
        });
    })();

    // =========================================================================
    // CUSTOM AVATAR UPLOAD HANDLER (Trang Hồ Sơ Của Tôi)
    // =========================================================================
    (function initAvatarUpload() {
        var avatarWrap = document.querySelector('.nb-spb-avatar-wrap');
        if (!avatarWrap) return;

        var fileInput = document.getElementById('nbAvatarFileInput');
        var btnChoose = document.getElementById('nbBtnChooseAvatar');
        var btnCamera = document.getElementById('nbBtnCameraUpload');
        var btnRemove = document.getElementById('nbBtnRemoveAvatar');
        var preview = document.getElementById('nbSpbAvatarPreview');
        var statusEl = document.getElementById('nbAvatarUploadStatus');
        var nonce = avatarWrap.getAttribute('data-nonce') || (window.nbook_ajax ? window.nbook_ajax.avatar_nonce : '');
        var ajaxUrl = (window.nbook_ajax && window.nbook_ajax.ajax_url) ? window.nbook_ajax.ajax_url : '/wordpress/wp-admin/admin-ajax.php';

        function showAvatarStatus(type, msg) {
            if (!statusEl) return;
            statusEl.className = 'nb-avatar-status is-' + type;
            var iconHtml = type === 'loading' ? '<i class="bi bi-arrow-repeat spin"></i> ' :
                           (type === 'success' ? '<i class="bi bi-check-circle-fill"></i> ' : '<i class="bi bi-exclamation-triangle-fill"></i> ');
            statusEl.innerHTML = iconHtml + msg;
            statusEl.style.display = 'flex';
            if (type !== 'loading') {
                setTimeout(function() {
                    statusEl.style.display = 'none';
                }, 4000);
            }
        }

        function triggerFileSelect() {
            if (fileInput) fileInput.click();
        }

        if (btnChoose) btnChoose.addEventListener('click', triggerFileSelect);
        if (btnCamera) btnCamera.addEventListener('click', triggerFileSelect);
        if (preview) preview.addEventListener('click', triggerFileSelect);

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                var file = this.files[0];
                if (!file) return;

                // Validate file type
                var validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showAvatarStatus('error', 'Chỉ chấp nhận file ảnh JPG, PNG, WEBP hoặc GIF.');
                    fileInput.value = '';
                    return;
                }

                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showAvatarStatus('error', 'Dung lượng ảnh vượt quá 5MB. Vui lòng chọn ảnh nhỏ hơn.');
                    fileInput.value = '';
                    return;
                }

                // Instant preview
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = preview.querySelector('img');
                    if (!img) {
                        preview.innerHTML = '<img id="nbAvatarImg" src="' + e.target.result + '" alt="Avatar">';
                    } else {
                        img.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);

                showAvatarStatus('loading', 'Đang tải ảnh lên máy chủ...');

                var formData = new FormData();
                formData.append('action', 'nbook_upload_avatar');
                formData.append('nonce', nonce);
                formData.append('avatar_file', file);

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(resp) {
                    if (resp.success) {
                        showAvatarStatus('success', resp.data.message || 'Cập nhật ảnh đại diện thành công!');
                        var img = preview.querySelector('img');
                        if (img && resp.data.avatar_url) {
                            img.src = resp.data.avatar_url;
                        }
                        if (btnRemove) {
                            btnRemove.classList.remove('d-none');
                        }
                        // Đồng bộ sidebar avatar
                        var sideImg = document.querySelector('.nb-sidebar-avatar img');
                        if (sideImg && resp.data.avatar_url) {
                            sideImg.src = resp.data.avatar_url;
                        } else {
                            var sideWrap = document.querySelector('.nb-sidebar-avatar');
                            if (sideWrap && resp.data.avatar_url) {
                                sideWrap.innerHTML = '<img src="' + resp.data.avatar_url + '" alt="Avatar">';
                            }
                        }
                        // Đồng bộ header avatar
                        var headImg = document.querySelector('.sp-user-thumb');
                        if (headImg && resp.data.avatar_url) headImg.src = resp.data.avatar_url;
                        var headDropImg = document.querySelector('.sp-auh-img');
                        if (headDropImg && resp.data.avatar_url) headDropImg.src = resp.data.avatar_url;
                    } else {
                        showAvatarStatus('error', (resp.data && resp.data.message) ? resp.data.message : 'Tải ảnh thất bại.');
                    }
                })
                .catch(function() {
                    showAvatarStatus('error', 'Lỗi kết nối máy chủ khi tải ảnh.');
                })
                .finally(function() {
                    fileInput.value = '';
                });
            });
        }

        if (btnRemove) {
            btnRemove.addEventListener('click', function() {
                if (!confirm('Bạn có chắc chắn muốn xóa ảnh đại diện tùy chỉnh và quay lại ảnh mặc định?')) return;

                showAvatarStatus('loading', 'Đang xóa ảnh...');

                var formData = new FormData();
                formData.append('action', 'nbook_delete_avatar');
                formData.append('nonce', nonce);

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(resp) {
                    if (resp.success) {
                        showAvatarStatus('success', resp.data.message || 'Đã xóa ảnh đại diện.');
                        btnRemove.classList.add('d-none');
                        if (resp.data.has_gravatar && resp.data.default_url) {
                            preview.innerHTML = '<img id="nbAvatarImg" src="' + resp.data.default_url + '" alt="Avatar">';
                        } else {
                            preview.innerHTML = '<span id="nbAvatarInitial">' + (resp.data.initial || 'U') + '</span>';
                        }
                        // Cập nhật sidebar
                        var sideWrap = document.querySelector('.nb-sidebar-avatar');
                        if (sideWrap) {
                            if (resp.data.has_gravatar && resp.data.default_url) {
                                sideWrap.innerHTML = '<img src="' + resp.data.default_url + '" alt="Avatar">';
                            } else {
                                sideWrap.innerHTML = '<span>' + (resp.data.initial || 'U') + '</span>';
                            }
                        }
                    } else {
                        showAvatarStatus('error', (resp.data && resp.data.message) ? resp.data.message : 'Không thể xóa ảnh.');
                    }
                })
                .catch(function() {
                    showAvatarStatus('error', 'Lỗi kết nối khi xóa ảnh.');
                });
            });
        }
    })();

    /* =========================================================================
     * CANCEL ORDER MODAL & AJAX HANDLER
     * ========================================================================= */
    (function initCancelOrder() {
        var modal = document.getElementById('nbCancelOrderModal');
        if (!modal) return;

        var modalOrderNum = document.getElementById('nbCancelModalOrderNum');
        var modalOrderId = document.getElementById('nbCancelModalOrderId');
        var modalError = document.getElementById('nbCancelModalError');
        var modalCloseBtn = document.getElementById('nbCancelModalCloseBtn');
        var modalDismissBtn = document.getElementById('nbCancelModalDismissBtn');
        var modalConfirmBtn = document.getElementById('nbCancelModalConfirmBtn');
        var noteInput = document.getElementById('nbCancelOrderNote');
        var radioItems = modal.querySelectorAll('.nb-cancel-radio-item');

        var ajaxUrl = (window.nbook_ajax && window.nbook_ajax.ajax_url) ? window.nbook_ajax.ajax_url : '/wordpress/wp-admin/admin-ajax.php';
        var fallbackNonce = document.getElementById('nbCancelModalNonce') ? document.getElementById('nbCancelModalNonce').value : '';
        var nonce = (window.nbook_ajax && window.nbook_ajax.cancel_order_nonce) ? window.nbook_ajax.cancel_order_nonce : fallbackNonce;

        // Active trigger button reference
        var activeTriggerBtn = null;

        function openModal(orderId, orderNumber, triggerBtn) {
            activeTriggerBtn = triggerBtn;
            if (modalOrderId) modalOrderId.value = orderId;
            if (modalOrderNum) modalOrderNum.textContent = '#' + orderNumber;
            if (modalError) {
                modalError.style.display = 'none';
                modalError.textContent = '';
            }
            if (noteInput) {
                noteInput.value = '';
            }

            // Reset first radio to checked
            var firstRadio = modal.querySelector('input[name="nb_cancel_reason"]');
            if (firstRadio) {
                firstRadio.checked = true;
            }
            updateRadioSelection();

            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            if (modalConfirmBtn) {
                modalConfirmBtn.disabled = false;
                var btnText = modalConfirmBtn.querySelector('.nb-btn-text');
                var btnSpinner = modalConfirmBtn.querySelector('.nb-btn-spinner');
                if (btnText) btnText.style.display = 'inline-flex';
                if (btnSpinner) btnSpinner.style.display = 'none';
            }
        }

        function updateRadioSelection() {
            radioItems.forEach(function(item) {
                var radio = item.querySelector('input[type="radio"]');
                if (radio && radio.checked) {
                    item.classList.add('is-selected');
                } else {
                    item.classList.remove('is-selected');
                }
            });
        }

        // Delegate radio click highlight
        modal.addEventListener('change', function(e) {
            if (e.target && e.target.name === 'nb_cancel_reason') {
                updateRadioSelection();
            }
        });

        // Event delegation for opening modal from any cancel button on page
        document.addEventListener('click', function(e) {
            var btn = e.target.closest('.nb-trigger-cancel-modal');
            if (btn) {
                e.preventDefault();
                var orderId = btn.getAttribute('data-order-id');
                var orderNumber = btn.getAttribute('data-order-number') || orderId;
                if (orderId) {
                    openModal(orderId, orderNumber, btn);
                }
            }
        });

        if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
        if (modalDismissBtn) modalDismissBtn.addEventListener('click', closeModal);

        // Click outside modal dialog to dismiss
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // ESC key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeModal();
            }
        });

        // Submit cancel request
        if (modalConfirmBtn) {
            modalConfirmBtn.addEventListener('click', function() {
                var orderId = modalOrderId.value;
                if (!orderId) return;

                var selectedRadio = modal.querySelector('input[name="nb_cancel_reason"]:checked');
                var reason = selectedRadio ? selectedRadio.value : 'Khách hàng hủy đơn';
                var note = noteInput ? noteInput.value.trim() : '';

                modalConfirmBtn.disabled = true;
                var btnText = modalConfirmBtn.querySelector('.nb-btn-text');
                var btnSpinner = modalConfirmBtn.querySelector('.nb-btn-spinner');
                if (btnText) btnText.style.display = 'none';
                if (btnSpinner) btnSpinner.style.display = 'inline-flex';
                if (modalError) modalError.style.display = 'none';

                var formData = new FormData();
                formData.append('action', 'nbook_cancel_order');
                formData.append('nonce', nonce);
                formData.append('order_id', orderId);
                formData.append('reason', reason);
                formData.append('note', note);

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(resp) {
                    if (resp.success) {
                        closeModal();

                        // 1. Nếu đang ở trang xem chi tiết View Order
                        var viewOrderWrap = document.querySelector('.nb-view-order-wrap');
                        if (viewOrderWrap) {
                            window.location.reload();
                            return;
                        }

                        // 2. Nếu đang ở trang danh sách đơn hàng Orders
                        var orderCard = activeTriggerBtn ? activeTriggerBtn.closest('.nb-order-card') : document.querySelector('.nb-order-card[data-order-id="' + resp.data.order_number + '"]');
                        if (orderCard) {
                            // Cập nhật badge
                            var oldBadge = orderCard.querySelector('.nb-status-badge');
                            if (oldBadge && resp.data.badge_html) {
                                oldBadge.outerHTML = resp.data.badge_html;
                            }

                            // Xóa nút hủy đơn khỏi thẻ này
                            if (activeTriggerBtn) {
                                activeTriggerBtn.remove();
                            } else {
                                var cBtn = orderCard.querySelector('.nb-trigger-cancel-modal');
                                if (cBtn) cBtn.remove();
                            }

                            // Cập nhật số đếm trên các tabs
                            var processingTabCount = document.querySelector('.nb-order-tab[data-tab-status="processing"] .nb-order-tab-count');
                            if (processingTabCount) {
                                var pCount = parseInt(processingTabCount.textContent, 10) || 0;
                                if (pCount > 1) {
                                    processingTabCount.textContent = pCount - 1;
                                } else {
                                    processingTabCount.remove();
                                }
                            }
                            var cancelledTab = document.querySelector('.nb-order-tab[data-tab-status="cancelled"]');
                            if (cancelledTab) {
                                var cancelledTabCount = cancelledTab.querySelector('.nb-order-tab-count');
                                if (cancelledTabCount) {
                                    var cCount = parseInt(cancelledTabCount.textContent, 10) || 0;
                                    cancelledTabCount.textContent = cCount + 1;
                                } else {
                                    var newBadge = document.createElement('span');
                                    newBadge.className = 'nb-order-tab-count';
                                    newBadge.textContent = '1';
                                    cancelledTab.appendChild(newBadge);
                                }
                            }
                        }

                        // Hiển thị thông báo thành công
                        alert(resp.data.message || 'Hủy đơn hàng thành công!');
                    } else {
                        modalConfirmBtn.disabled = false;
                        if (btnText) btnText.style.display = 'inline-flex';
                        if (btnSpinner) btnSpinner.style.display = 'none';

                        if (modalError) {
                            modalError.textContent = (resp.data && resp.data.message) ? resp.data.message : 'Có lỗi xảy ra, vui lòng thử lại.';
                            modalError.style.display = 'block';
                        }
                    }
                })
                .catch(function() {
                    modalConfirmBtn.disabled = false;
                    if (btnText) btnText.style.display = 'inline-flex';
                    if (btnSpinner) btnSpinner.style.display = 'none';

                    if (modalError) {
                        modalError.textContent = 'Lỗi kết nối máy chủ. Vui lòng kiểm tra đường truyền và thử lại.';
                        modalError.style.display = 'block';
                    }
                });
            });
        }
    })();

});
