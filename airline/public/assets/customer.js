 // footer year
    document.getElementById('y').textContent = new Date().getFullYear();

    // UI: toggle one-way / round-trip
    const onewayBtn = document.getElementById('tab-oneway');
    const roundBtn = document.getElementById('tab-round');
    const returnWrap = document.getElementById('returnWrap');

    function setRound(on) {
      if (on) {
        roundBtn.classList.add('active');
        onewayBtn.classList.remove('active');
        returnWrap.hidden = false;
      } else {
        onewayBtn.classList.add('active');
        roundBtn.classList.remove('active');
        returnWrap.hidden = true;
      }
    }
    if (onewayBtn && roundBtn) {
      onewayBtn.addEventListener('click', ()=> setRound(false));
      roundBtn.addEventListener('click', ()=> setRound(true));
    }

    // swap from/to
    const swapBtn = document.getElementById('swapBtn');
    if (swapBtn) {
      swapBtn.addEventListener('click', function(){
        const a = document.getElementById('from');
        const b = document.getElementById('to');
        const tmp = a.value;
        a.value = b.value;
        b.value = tmp;
        a.focus();
      });
    }

    // basic client validation on submit
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
      searchForm.addEventListener('submit', function(e){
        const from = document.getElementById('from').value.trim();
        const to = document.getElementById('to').value.trim();
        const depart = document.getElementById('depart').value;
        if (!from || !to) {
          e.preventDefault();
          alert('Vui lòng nhập cả điểm đi và điểm đến.');
          return;
        } else if (!depart) {
          e.preventDefault();
          alert('Vui lòng chọn ngày đi.');
          return;
        }
        // submit — form dùng GET để chuyển sang trang kết quả (search_results)
      });
    }
 
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.menu-btn');
    const menu = document.querySelector('.menu-dropdown');
    if (btn) {
      menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
      return;
    }
    // Click ra ngoài thì đóng menu
    if (!e.target.closest('.menu-wrapper')) {
      menu.style.display = 'none';
    }
  });


