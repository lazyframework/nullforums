(() => {
  const catSel   = document.getElementById('catSelect');
  const forumSel = document.getElementById('forumSelect');
  if (!catSel || !forumSel) return;

  const esc = s => String(s).replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));

  const setOption = text => {
    forumSel.innerHTML = `<option value="">${esc(text)}</option>`;
  };

  catSel.addEventListener('change', async () => {
    const cat = catSel.value;

    if (!cat) {
      setOption('— Pilih kategori dulu —');
      return;
    }

    setOption('Memuat data...');

    try {
      const res  = await fetch('/api/forums?cat=' + encodeURIComponent(cat) + '&_t=' + Date.now(), {
        cache: 'no-store'
      });
      const data = await res.json();

      if (!data.ok || data.forums.length === 0) {
        setOption('— Tidak ada forum di kategori ini —');
        return;
      }

      forumSel.innerHTML = '<option value="">— Pilih forum —</option>'
        + data.forums.map(f => `<option value="${esc(f.slug)}">${esc(f.name)}</option>`).join('');
    } catch (err) {
      setOption('— Gagal memuat forum —');
    }
  });
})();
