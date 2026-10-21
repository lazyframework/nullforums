/* ============================================================
   1. MODAL LOGIN
   ============================================================ */
const loginModal = document.getElementById('loginModal');
const btnMasuk = document.getElementById('btnMasuk');
const btnDaftar = document.getElementById('btnDaftar');
const closeModalBtn = document.getElementById('closeModal');
const btnSubmitLogin = document.getElementById('btnSubmitLogin');

if (loginModal) {
  const openLogin = () => loginModal.classList.add('show');
  const closeLogin = () => loginModal.classList.remove('show');

  if (btnMasuk) btnMasuk.addEventListener('click', openLogin);
  if (btnDaftar) btnDaftar.addEventListener('click', openLogin);
  if (closeModalBtn) closeModalBtn.addEventListener('click', closeLogin);
  if (btnSubmitLogin) btnSubmitLogin.addEventListener('click', closeLogin);

  loginModal.addEventListener('click', e => {
    if (e.target === loginModal) closeLogin();
  });
}

/* ============================================================
   2. PENCARIAN DI HEADER (NAVBAR)
   ============================================================ */
const searchInputNav = document.getElementById('searchInputNav');
if (searchInputNav) {
  searchInputNav.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const keyword = searchInputNav.value.trim();
      if (keyword.length >= 3) {
        window.location.href = '/search?q=' + encodeURIComponent(keyword);
      } else if (keyword.length > 0) {
        alert('Kata kunci minimal 3 karakter.');
      }
    }
  });
}

/* ============================================================
   3. TAB: SCROLL HORIZONTAL + PANAH
   ============================================================ */
const tabsEl = document.getElementById('tabs');
const arrowL = document.getElementById('arrowLeft');
const arrowR = document.getElementById('arrowRight');

if (tabsEl && arrowL && arrowR) {
  const updateArrows = () => {
    const max = tabsEl.scrollWidth - tabsEl.clientWidth;
    if (max <= 1) {
      arrowL.classList.remove('show');
      arrowR.classList.remove('show');
      return;
    }
    arrowL.classList.toggle('show', tabsEl.scrollLeft > 4);
    arrowR.classList.toggle('show', tabsEl.scrollLeft < max - 4);
  };

  const centerActiveTab = () => {
    const active = tabsEl.querySelector('.tab.active');
    if (!active) return;
    const offset = active.offsetLeft - tabsEl.offsetLeft;
    tabsEl.scrollLeft = offset - (tabsEl.clientWidth - active.offsetWidth) / 2;
  };

  tabsEl.addEventListener('scroll', updateArrows);
  window.addEventListener('resize', updateArrows);
  window.addEventListener('load', () => { centerActiveTab(); updateArrows(); });
  centerActiveTab();
  updateArrows();

  arrowL.addEventListener('click', () => tabsEl.scrollBy({ left: -180, behavior: 'smooth' }));
  arrowR.addEventListener('click', () => tabsEl.scrollBy({ left: 180, behavior: 'smooth' }));
}

/* ============================================================
   4. DRAWER MENU (MOBILE)
   ============================================================ */
const drawer = document.getElementById('drawer');
const drawerBackdrop = document.getElementById('drawerBackdrop');
const menuToggle = document.getElementById('menuToggle');
const drawerClose = document.getElementById('drawerClose');

if (drawer && drawerBackdrop && menuToggle && drawerClose) {
  const openDrawer = () => {
    drawer.classList.add('open');
    drawerBackdrop.classList.add('show');
    menuToggle.classList.add('open');
    menuToggle.setAttribute('aria-expanded', 'true');
    drawer.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };

  const closeDrawer = () => {
    drawer.classList.remove('open');
    drawerBackdrop.classList.remove('show');
    menuToggle.classList.remove('open');
    menuToggle.setAttribute('aria-expanded', 'false');
    drawer.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  };

  menuToggle.addEventListener('click', () => {
    drawer.classList.contains('open') ? closeDrawer() : openDrawer();
  });
  drawerClose.addEventListener('click', closeDrawer);
  drawerBackdrop.addEventListener('click', closeDrawer);

  drawer.querySelectorAll('.drawer-nav a').forEach(a => {
    a.addEventListener('click', closeDrawer);
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 900) closeDrawer();
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeDrawer();
  });
}

/* ============================================================
   5. SUB-NAVIGASI (PIL-PIL)
   ============================================================ */
const subnavLinks = document.querySelectorAll('.subnav a');
if (subnavLinks.length > 0) {
  subnavLinks.forEach(link => {
    link.addEventListener('click', e => {
      if (link.getAttribute('href') === '#') {
        e.preventDefault(); 
        const parentNav = link.closest('.subnav');
        parentNav.querySelectorAll('a').forEach(a => a.classList.remove('active'));
        link.classList.add('active');
      }
    });
  });
}

/* ============================================================
   6. SLIDER THREAD (HANYA JALAN JIKA ADA TOMBOL SLIDER)
   ============================================================ */
const sliders = document.querySelectorAll('.slider');

sliders.forEach(track => {
  const container = track.closest('.cat');
  if (!container) return;
  
  const prevBtn = container.querySelector('.slider-prev') || container.querySelector('#slidePrev');
  const nextBtn = container.querySelector('.slider-next') || container.querySelector('#slideNext');
  const countEl = container.querySelector('.slider-count') || container.querySelector('#slideCount');
  
  if (!prevBtn || !nextBtn) return; 

  const total = track.children.length;
  const currentIndex = () => Math.round(track.scrollLeft / track.clientWidth);
  
  const goTo = (index) => {
    const i = Math.max(0, Math.min(total - 1, index));
    track.scrollTo({ left: i * track.clientWidth, behavior: 'smooth' });
  };
  
  const updateControls = () => {
    const i = currentIndex();
    prevBtn.disabled = i <= 0;
    nextBtn.disabled = i >= total - 1;
    if (countEl) countEl.textContent = (i + 1) + ' / ' + total;
  };
  
  prevBtn.addEventListener('click', () => goTo(currentIndex() - 1));
  nextBtn.addEventListener('click', () => goTo(currentIndex() + 1));
  track.addEventListener('scroll', updateControls);
  window.addEventListener('resize', updateControls);
  updateControls();
});

/* ============================================================
   7. HOME PAGE AJAX PAGINATION
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  const catBoxes = document.querySelectorAll('.cat.boxed[data-cat-slug]');

  catBoxes.forEach(box => {
    const slug = box.getAttribute('data-cat-slug');
    const limit = box.getAttribute('data-limit');
    const listContainer = box.querySelector('.thread-list');
    const btnPrev = box.querySelector('.btn-prev');
    const btnNext = box.querySelector('.btn-next');
    const countText = box.querySelector('.slider-count');
    
    if (!listContainer || !btnPrev || !btnNext) return;

    let currentPage = 1;
    let totalPages = parseInt(countText.textContent.split('/')[1].trim()) || 1;

    const loadPage = async (page) => {
      if (page < 1 || page > totalPages) return;

      listContainer.style.opacity = '0.5';
      listContainer.style.pointerEvents = 'none';

      try {
        const res = await fetch(`/api/home_threads?cat=${slug}&page=${page}&limit=${limit}`);
        const data = await res.json();

        if (data.ok) {
          currentPage = page;
          
          if (data.threads.length === 0) {
            listContainer.innerHTML = '<div class="empty-state">Belum ada thread di kategori ini.</div>';
          } else {
            listContainer.innerHTML = data.threads.map(t => `
              <div class="forum-row">
                <div class="f-icon">${t.icon}</div>
                <div>
                  <div class="f-name">
                    <a href="/t/${t.slug}">${t.title}</a>
                  </div>
                  <div class="f-desc">${t.forum_name} &middot; oleh ${t.username}</div>
                </div>
                <div class="f-stat"><div class="n">${t.reply_count}</div><div class="l">Balasan</div></div>
                <div class="f-stat"><div class="n">${t.views}</div><div class="l">Dilihat</div></div>
                <div class="last-post">
                  <b>${t.relative_time}</b>
                </div>
              </div>
            `).join('');
          }

          countText.textContent = `${currentPage} / ${totalPages}`;

          if (currentPage <= 1) {
            btnPrev.disabled = true;
            btnPrev.style.opacity = '.35';
            btnPrev.style.cursor = 'default';
          } else {
            btnPrev.disabled = false;
            btnPrev.style.opacity = '1';
            btnPrev.style.cursor = 'pointer';
          }

          if (currentPage >= totalPages) {
            btnNext.disabled = true;
            btnNext.style.opacity = '.35';
            btnNext.style.cursor = 'default';
          } else {
            btnNext.disabled = false;
            btnNext.style.opacity = '1';
            btnNext.style.cursor = 'pointer';
          }
        }
      } catch (err) {
        console.error('Gagal memuat thread:', err);
        listContainer.innerHTML = `
          <div class="empty-state" style="color:var(--red)">
            ⚠️ Gagal memuat data. Periksa koneksi internet Anda.
          </div>
        `;
      } finally {
        listContainer.style.opacity = '1';
        listContainer.style.pointerEvents = 'auto';
      }
    };

    btnPrev.addEventListener('click', () => loadPage(currentPage - 1));
    btnNext.addEventListener('click', () => loadPage(currentPage + 1));
  });
});

/* ============================================================
   8. AUTO HIDE FLASH BANNER
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  const flashAlert = document.querySelector('.alert');
  if (flashAlert) {
    setTimeout(() => {
      flashAlert.style.transition = 'opacity 0.5s ease';
      flashAlert.style.opacity = '0';
      setTimeout(() => flashAlert.remove(), 500);
    }, 4000); // Hilang setelah 4 detik
  }
});

/* ============================================================
   9. HAPUS THREAD DENGAN MODAL KUSTOM (APP DIALOG)
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  const appDialog = document.getElementById('appDialog');
  const dialogTitle = document.getElementById('dialogTitle');
  const dialogMsg = document.getElementById('dialogMsg');
  const btnCancel = document.getElementById('dialogCancel');
  const btnOk = document.getElementById('dialogOk');

  if (!appDialog) return;

  let currentThreadId = null;

  // Event saat tombol hapus (tong sampah) diklik
  const deleteButtons = document.querySelectorAll('.btn-delete-thread');
  deleteButtons.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      currentThreadId = this.getAttribute('data-id');
      
      // Siapkan teks modal
      dialogTitle.textContent = 'Delete Thread?';
      dialogMsg.textContent = 'This action cannot be undone. The thread will be permanently removed.';
      btnOk.textContent = 'Yes, Delete';
      btnOk.className = 'btn btn-primary'; // Pastikan tombol OK berwarna merah
      
      // Tampilkan modal
      appDialog.classList.add('show');
      appDialog.setAttribute('aria-hidden', 'false');
    });
  });

  // Tombol Batal
  if (btnCancel) {
    btnCancel.addEventListener('click', () => {
      appDialog.classList.remove('show');
      appDialog.setAttribute('aria-hidden', 'true');
      currentThreadId = null;
    });
  }

  // Tutup modal jika klik area gelap di luar
  appDialog.addEventListener('click', (e) => {
    if (e.target === appDialog) {
      appDialog.classList.remove('show');
      appDialog.setAttribute('aria-hidden', 'true');
      currentThreadId = null;
    }
  });

  // Tombol Konfirmasi Hapus
  if (btnOk) {
    btnOk.addEventListener('click', async () => {
      if (!currentThreadId) return;
      
      // Ambil CSRF token dari meta tag
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (!csrfToken) {
        alert('CSRF token not found. Please refresh the page.');
        return;
      }

      const formData = new FormData();
      formData.append('thread_id', currentThreadId);
      formData.append('_csrf', csrfToken);

      const originalText = btnOk.textContent;
      btnOk.textContent = 'Deleting...';
      btnOk.disabled = true;

      try {
        const res = await fetch('/api/thread/delete', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();

        if (data.ok) {
          appDialog.classList.remove('show');
          appDialog.setAttribute('aria-hidden', 'true');
          
          // Animasi hapus elemen dari layar
          const wrapper = document.getElementById(`thread-${currentThreadId}`);
          if (wrapper) {
            wrapper.style.transition = 'all 0.3s';
            wrapper.style.opacity = '0';
            wrapper.style.transform = 'scale(0.95)';
            setTimeout(() => wrapper.remove(), 300);
          }
        } else {
          alert('Failed to delete: ' + data.msg);
        }
      } catch (err) {
        alert('Network error occurred.');
      } finally {
        btnOk.textContent = originalText;
        btnOk.disabled = false;
        currentThreadId = null;
      }
    });
  }
});


/* ============================================================
   10. BERANDA: PAGINATION PER KATEGORI
   ============================================================ */
(() => {
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));

  const iconHtml = ic => {
    ic = String(ic ?? '').trim();
    if (!ic) return '';
    return ic.startsWith('ti ') ? `<i class="${esc(ic)}"></i>` : esc(ic);
  };

  // Harus sama dengan markup baris thread di home.php
  const rowHtml = t => `
    <div class="profile-thread-wrapper" id="thread-${t.id}">
      <div class="forum-row">
        <div class="f-icon">${iconHtml(t.icon)}</div>
        <div>
          <div class="f-name"><a href="/t/${esc(t.slug)}">${esc(t.title)}</a></div>
          <div class="f-desc">${esc(t.forum_name)} &middot; by ${esc(t.username)}</div>
        </div>
        <div class="f-stat"><div class="n">${t.reply_count}</div><div class="l">Replies</div></div>
        <div class="f-stat"><div class="n">${t.views}</div><div class="l">Views</div></div>
        <div class="last-post"><b>${esc(t.time)}</b></div>
      </div>
      ${t.can_delete
        ? `<button type="button" class="btn-delete-thread" data-id="${t.id}" title="Delete Thread" aria-label="Delete Thread"><i class="ti ti-trash"></i></button>`
        : ''}
    </div>`;

  document.querySelectorAll('.cat[data-cat]').forEach(box => {
    const list  = box.querySelector('.thread-list');
    const prev  = box.querySelector('.btn-prev');
    const next  = box.querySelector('.btn-next');
    const count = box.querySelector('.slider-count');
    const limit = box.dataset.limit;

    let page       = Number(box.dataset.page) || 1;
    let totalPages = Number(box.dataset.totalPages) || 1;
    let busy       = false;

    const updateControls = () => {
      count.textContent = `${page} / ${totalPages}`;
      prev.disabled = page <= 1;
      next.disabled = page >= totalPages;
    };

    const load = async target => {
      if (busy) return;
      busy = true;
      list.style.opacity = '.5';
      try {
        const url = `/api/home_threads?cat=${encodeURIComponent(box.dataset.cat)}&page=${target}&limit=${limit}`;
        const res  = await fetch(url, { cache: 'no-store' });
        const data = await res.json();
        if (!data.ok) return;

        page       = data.page;
        totalPages = data.totalPages;
        list.innerHTML = data.threads.length
          ? data.threads.map(rowHtml).join('')
          : '<div class="empty-state">No threads yet.</div>';
        updateControls();
      } catch (err) {
        // Gagal memuat: halaman sebelumnya tetap tampil
      } finally {
        list.style.opacity = '';
        busy = false;
      }
    };

    prev.addEventListener('click', () => { if (page > 1) load(page - 1); });
    next.addEventListener('click', () => { if (page < totalPages) load(page + 1); });

    updateControls();
  });
})();
