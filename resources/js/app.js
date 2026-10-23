import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import './bootstrap';

window.$ = $;
window.jQuery = $;
window.bootstrap = bootstrap;
document.documentElement.classList.add('js', 'js-ready');

const revealSections = [...document.querySelectorAll('.ps-main > section, #home-products > section')];
revealSections.forEach(section => section.classList.add('ps-section-reveal'));
if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const revealObserver = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('is-visible'); revealObserver.unobserve(entry.target); } }), { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
    revealSections.forEach(section => revealObserver.observe(section));
} else revealSections.forEach(section => section.classList.add('is-visible'));

const chatWidget = document.querySelector('[data-chat-widget]');
if (chatWidget) {
    const panel = chatWidget.querySelector('[data-chat-panel]'); const toggle = chatWidget.querySelector('[data-chat-toggle]'); const close = chatWidget.querySelector('[data-chat-close]'); const form = chatWidget.querySelector('[data-chat-form]'); const input = form.querySelector('input'); const messages = chatWidget.querySelector('[data-chat-messages]');
    const addMessage = (text, type) => { const node = document.createElement('div'); node.className = `ps-chat-message ${type}`; node.textContent = text; messages.append(node); messages.scrollTop = messages.scrollHeight; };
    const open = () => { panel.hidden = false; toggle.setAttribute('aria-expanded', 'true'); input.focus(); }; const hide = () => { panel.hidden = true; toggle.setAttribute('aria-expanded', 'false'); };
    toggle.addEventListener('click', () => panel.hidden ? open() : hide()); close.addEventListener('click', hide);
    chatWidget.querySelectorAll('[data-chat-suggestions] button').forEach(button => button.addEventListener('click', () => { input.value = button.textContent; input.focus(); }));
    form.addEventListener('submit', event => { event.preventDefault(); const message = input.value.trim(); if (!message) return; addMessage(message, 'user'); input.value = ''; const loading = document.createElement('div'); loading.className = 'ps-chat-message ai'; loading.textContent = 'Đang tư vấn…'; messages.append(loading); messages.scrollTop = messages.scrollHeight; form.querySelector('button').disabled = true;
        fetch(form.closest('[data-chat-widget]').dataset.chatUrl || '/chat', { method: 'POST', headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}, body: JSON.stringify({message}) }).then(async response => { const data = await response.json(); if (!response.ok) throw new Error(data.message || 'Lỗi kết nối'); return data; }).then(data => { loading.remove(); addMessage(data.message, 'ai'); (data.products || []).forEach(product => { const card = document.createElement('a'); card.className = 'ps-chat-product'; card.href = product.url; const image = document.createElement('img'); image.src = product.image; image.alt = product.name; const details = document.createElement('span'); details.textContent = `${product.name} · ${money(product.price)}${product.in_stock ? '' : ' · Hết hàng'}`; card.append(image, details); messages.append(card); }); messages.scrollTop = messages.scrollHeight; }).catch(error => { loading.remove(); addMessage(error.message || 'Không thể kết nối. Vui lòng thử lại.', 'ai'); }).finally(() => { form.querySelector('button').disabled = false; }); });
}

const money = value => new Intl.NumberFormat('vi-VN').format(value) + 'đ';

const wishlistToast = message => { let node = document.getElementById('wishlist-toast'); if (!node) { node = document.createElement('div'); node.id = 'wishlist-toast'; node.className = 'toast-container position-fixed bottom-0 end-0 p-3'; document.body.append(node); } node.innerHTML = `<div class="toast show" role="status"><div class="toast-body">${escapeHtml(message)}</div></div>`; setTimeout(() => node.innerHTML = '', 2600); };
const updateWishlistCount = count => document.querySelectorAll('[data-wishlist-count]').forEach(node => { node.textContent = count; node.hidden = !count; });
document.addEventListener('click', event => { const button = event.target.closest('[data-wishlist-product]'); if (!button) return; event.preventDefault(); fetch('/wishlist/toggle', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }, body: JSON.stringify({ product_id: button.dataset.wishlistProduct }) }).then(async response => { if (response.status === 401 || response.redirected || !response.headers.get('content-type')?.includes('application/json')) { window.location.href = '/login?redirect=' + encodeURIComponent(location.href); return; } const data = await response.json(); if (!response.ok) throw new Error(data.message || 'Không thể cập nhật yêu thích.'); button.classList.toggle('text-danger', data.added); button.querySelector('i').className = `bi ${data.added ? 'bi-heart-fill' : 'bi-heart'}`; button.setAttribute('aria-label', data.added ? 'Xóa khỏi yêu thích' : 'Thêm vào yêu thích'); updateWishlistCount(data.count); wishlistToast(data.message); }).catch(error => wishlistToast(error.message)); });

const compareBar = document.getElementById('compare-bar');
if (compareBar) {
    let ids = JSON.parse(localStorage.getItem('phonestore_compare') || '[]').map(Number).filter(Number.isInteger).slice(0, 3);
    const renderCompare = () => { compareBar.hidden = !ids.length; compareBar.innerHTML = ''; const title = document.createElement('strong'); title.textContent = `Đang chọn ${ids.length}/3 sản phẩm`; const link = document.createElement('a'); link.className = 'btn btn-light btn-sm'; link.textContent = 'So sánh ngay'; link.href = '/compare?ids=' + ids.join(','); link.setAttribute('aria-disabled', ids.length < 2); if (ids.length < 2) link.classList.add('disabled'); const clear = document.createElement('button'); clear.className = 'btn btn-outline-light btn-sm'; clear.textContent = 'Xóa tất cả'; clear.onclick = () => { ids = []; save(); }; compareBar.append(title, link, clear); document.querySelectorAll('[data-compare-add]').forEach(button => { button.textContent = ids.includes(Number(button.dataset.compareAdd)) ? 'Đã chọn' : 'So sánh'; button.disabled = ids.includes(Number(button.dataset.compareAdd)); }); };
    const save = () => { localStorage.setItem('phonestore_compare', JSON.stringify(ids)); renderCompare(); };
    document.addEventListener('click', event => { const button = event.target.closest('[data-compare-add]'); if (!button) return; const id = Number(button.dataset.compareAdd); if (!ids.includes(id) && ids.length < 3) { ids.push(id); save(); } }); renderCompare();
}
const differentToggle = document.getElementById('compare-different');
if (differentToggle) differentToggle.addEventListener('change', () => document.querySelectorAll('[data-compare-row]').forEach(row => { const values = [...row.querySelectorAll('td')].map(cell => cell.textContent.trim().toLocaleLowerCase().replace(/\s+/g, ' ')); row.hidden = differentToggle.checked && new Set(values).size < 2; }));
const compareSearch = document.getElementById('compare-search');
const compareResults = document.getElementById('compare-search-results');
if (compareSearch && compareResults) {
    let timer;
    compareSearch.addEventListener('input', () => { clearTimeout(timer); const term = compareSearch.value.trim(); if (term.length < 2) { compareResults.hidden = true; return; } timer = setTimeout(() => fetch(`${compareSearch.dataset.suggestUrl}?q=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } }).then(response => response.json()).then(data => { compareResults.innerHTML = ''; const current = JSON.parse(localStorage.getItem('phonestore_compare') || '[]').map(Number); data.data.filter(item => !current.includes(Number(item.id))).forEach(item => { const button = document.createElement('button'); button.type = 'button'; button.className = 'list-group-item list-group-item-action'; button.textContent = `${item.name} · ${money(item.price)}`; button.onclick = () => { if (current.length >= 3) return; current.push(Number(item.id)); localStorage.setItem('phonestore_compare', JSON.stringify(current)); location.href = '/compare?ids=' + current.join(','); }; compareResults.append(button); }); compareResults.hidden = !compareResults.children.length; }).catch(() => { compareResults.hidden = true; }), 250); });
}
document.querySelectorAll('[data-compare-remove]').forEach(button => button.addEventListener('click', () => { const ids = JSON.parse(localStorage.getItem('phonestore_compare') || '[]').map(Number).filter(id => id !== Number(button.dataset.compareRemove)); localStorage.setItem('phonestore_compare', JSON.stringify(ids)); location.href = ids.length ? '/compare?ids=' + ids.join(',') : '/compare'; }));
document.querySelectorAll('[data-compare-variant]').forEach(select => select.addEventListener('change', () => { const params = new URLSearchParams(location.search); params.set(`variants[${select.dataset.compareVariant}]`, select.value); location.href = '/compare?' + params.toString(); }));
const escapeHtml = value => $('<div>').text(value ?? '').html();

const bannerCarousel = document.querySelector('[data-banner-carousel]');
if (bannerCarousel) {
    const slides = [...bannerCarousel.querySelectorAll('[data-banner-slide]')];
    const dots = [...bannerCarousel.querySelectorAll('[data-banner-dot]')];
    if (slides.length > 1) {
        let current = 0; let timer;
        const show = index => { current = (index + slides.length) % slides.length; slides.forEach((slide, i) => slide.classList.toggle('is-active', i === current)); dots.forEach((dot, i) => dot.classList.toggle('active', i === current)); };
        const stop = () => clearInterval(timer);
        const start = () => { stop(); if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) timer = setInterval(() => show(current + 1), 5000); };
        bannerCarousel.querySelector('[data-banner-prev]')?.addEventListener('click', () => { show(current - 1); start(); });
        bannerCarousel.querySelector('[data-banner-next]')?.addEventListener('click', () => { show(current + 1); start(); });
        dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); start(); }));
        bannerCarousel.addEventListener('mouseenter', stop); bannerCarousel.addEventListener('mouseleave', start); bannerCarousel.addEventListener('focusin', stop); bannerCarousel.addEventListener('focusout', event => { if (!bannerCarousel.contains(event.relatedTarget)) start(); });
        let touchX = 0; bannerCarousel.addEventListener('touchstart', event => { touchX = event.changedTouches[0].screenX; }, { passive: true }); bannerCarousel.addEventListener('touchend', event => { const delta = event.changedTouches[0].screenX - touchX; if (Math.abs(delta) > 40) { show(current + (delta < 0 ? 1 : -1)); start(); } }, { passive: true });
        start();
    }
}

const homeProducts = document.querySelector('#home-products');
const brandFilter = document.querySelector('[data-brand-filter]');
if (homeProducts && brandFilter) {
    let request;
    const loadBrand = (link, updateHistory = true) => {
        request?.abort();
        request = new AbortController();
        const brandId = link.dataset.brandId || '';
        homeProducts.classList.add('is-loading');
        homeProducts.setAttribute('aria-busy', 'true');
        fetch(`${homeProducts.dataset.filterUrl}${brandId ? `?brand_id=${encodeURIComponent(brandId)}` : ''}`, { headers: { Accept: 'text/html' }, signal: request.signal })
            .then(response => { if (!response.ok) throw new Error('network'); return response.text(); })
            .then(html => { homeProducts.innerHTML = html; homeProducts.classList.remove('is-loading'); homeProducts.removeAttribute('aria-busy'); brandFilter.querySelectorAll('[data-brand-id]').forEach(item => item.classList.toggle('active', item === link)); if (updateHistory) history.pushState({ brandId }, '', brandId ? `/?brand_id=${encodeURIComponent(brandId)}` : '/'); })
            .catch(error => { if (error.name !== 'AbortError') { homeProducts.classList.remove('is-loading'); homeProducts.removeAttribute('aria-busy'); homeProducts.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger mx-3" role="alert">Không thể tải sản phẩm. Vui lòng thử lại.</div>'); } });
    };
    brandFilter.addEventListener('click', event => { const link = event.target.closest('[data-brand-id]'); if (!link) return; event.preventDefault(); loadBrand(link); });
    window.addEventListener('popstate', () => { const id = new URLSearchParams(location.search).get('brand_id') || ''; const link = brandFilter.querySelector(`[data-brand-id="${CSS.escape(id)}"]`) || brandFilter.querySelector('[data-brand-id=""]'); loadBrand(link, false); });
}

$('.ps-live-search').each(function () {
    const form = this;
    const input = form.querySelector('input[name="q"]');
    const results = form.querySelector('.ps-search-results');
    let timer;
    let items = [];
    let active = -1;

    const close = () => { results.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; };
    const render = (data) => {
        items = data;
        results.innerHTML = data.length ? data.map((item, index) => `<a class="ps-search-result" role="option" id="ps-result-${index}" href="${escapeHtml(item.url)}"><img src="${escapeHtml(item.image)}" alt=""><span><strong>${escapeHtml(item.name)}</strong><small>${escapeHtml(item.brand)} · <b>${money(item.price)}</b>${item.original_price ? ` <del>${money(item.original_price)}</del>` : ''}</small></span></a>`).join('') : '<div class="ps-search-empty" role="status">Không tìm thấy sản phẩm phù hợp.</div>';
        results.hidden = false; input.setAttribute('aria-expanded', 'true');
    };
    const load = () => {
        const q = input.value.trim();
        if (q.length < 2) return close();
        results.innerHTML = '<div class="ps-search-empty" role="status">Đang tìm kiếm…</div>'; results.hidden = false;
        fetch(`${form.dataset.suggestUrl}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } }).then(response => response.json()).then(json => render(json.data || [])).catch(() => render([]));
    };
    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 300); });
    input.addEventListener('keydown', event => {
        if (event.key === 'Escape') return close();
        if (!items.length || results.hidden) return;
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') { event.preventDefault(); active = (active + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length; [...results.querySelectorAll('.ps-search-result')].forEach((el, i) => el.classList.toggle('active', i === active)); input.setAttribute('aria-activedescendant', `ps-result-${active}`); }
        if (event.key === 'Enter' && active >= 0) { event.preventDefault(); window.location.href = items[active].url; }
    });
    document.addEventListener('click', event => { if (!form.contains(event.target)) close(); });
});
