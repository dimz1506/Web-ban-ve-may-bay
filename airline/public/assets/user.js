 
    // Footer year
    document.getElementById('y').textContent = new Date().getFullYear();

    // Keyboard shortcut: N => focus email field
    document.addEventListener('keydown', (e)=> {
      if (e.key.toLowerCase() === 'n' && !e.ctrlKey && !e.metaKey) {
        const el = document.querySelector('#form-create input[name="email"]');
        if (el) { el.focus(); e.preventDefault(); }
      }
    });

    // Action menu toggles
    document.querySelectorAll('.action-menu').forEach(menuWrap => {
      const btn = menuWrap.querySelector('.action-btn');
      const menu = menuWrap.querySelector('.menu');
      btn.addEventListener('click', (ev) => {
        ev.stopPropagation();
        // close other menus
        document.querySelectorAll('.menu').forEach(m => { if (m !== menu) m.style.display = 'none'; });
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
      });
    });

    // Close menus when clicking outside
    document.addEventListener('click', ()=> {
      document.querySelectorAll('.menu').forEach(m => m.style.display = 'none');
    });

    // Modal logic
    const modal = document.getElementById('confirmModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalForm = document.getElementById('modalForm');
    const modalId = document.getElementById('modalId');
    const modalAction = document.getElementById('modalAction');
    const modalCancel = document.getElementById('modalCancel');

    // Attach handlers for menu buttons
    document.querySelectorAll('.menu .menu-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const formWrap = btn.closest('.menu-form');
        const action = formWrap.getAttribute('data-action'); // resetpw/toggle/delete
        const id = btn.getAttribute('data-id');
        let title = 'Xác nhận';
        let body = 'Bạn có chắc muốn thực hiện hành động này?';
        if (action === 'resetpw') { title = 'Reset mật khẩu'; body = 'Reset mật khẩu người dùng và tạo mật khẩu tạm thời. Bạn sẽ nhận được mật khẩu mới hiển thị sau khi xác nhận.'; }
        if (action === 'toggle') { title = 'Khoá / Mở tài khoản'; body = 'Hành động này sẽ thay đổi trạng thái tài khoản. Bạn có muốn tiếp tục?'; }
        if (action === 'delete') { title = 'Xoá tài khoản'; body = 'Xoá tài khoản là hành động không thể hoàn tác. Bạn có chắc chắn?'; }

        modalTitle.textContent = title;
        modalBody.textContent = body;
        modalId.value = id;
        modalAction.value = action;
        modal.style.display = 'flex';

      });
    });

    modalCancel.addEventListener('click', ()=> { modal.style.display = 'none'; });

    // close modal on outside click
    modal.addEventListener('click', (e)=> { if (e.target === modal) modal.style.display = 'none'; });

    // Accessibility: close modal with ESC
    document.addEventListener('keydown', (e)=> { if (e.key === 'Escape') modal.style.display = 'none'; });
