document.addEventListener('DOMContentLoaded', () => {
  // Initialize date pickers, etc.

    // ====== Airports data (rút gọn Việt Nam + vài hub nội địa) ======
    const AIRPORTS = [
      {code:'HAN', city:'Ha Noi', name:'Noi Bai'},
      {code:'SGN', city:'Ho Chi Minh', name:'Tan Son Nhat'},
      {code:'DAD', city:'Da Nang', name:'Da Nang'},
      {code:'HPH', city:'Hai Phong', name:'Cat Bi'},
      {code:'HUI', city:'Hue', name:'Phu Bai'},
      {code:'CXR', city:'Nha Trang', name:'Cam Ranh'},
      {code:'PQC', city:'Phu Quoc', name:'Phu Quoc'},
      {code:'VII', city:'Vinh', name:'Vinh'},
      {code:'VCA', city:'Can Tho', name:'Can Tho'},
      {code:'VDH', city:'Dong Hoi', name:'Dong Hoi'},
      {code:'THD', city:'Thanh Hoa', name:'Tho Xuan'},
      {code:'VCL', city:'Chu Lai', name:'Chu Lai'}
    ];

    const fromEl = document.getElementById('from');
    const toEl = document.getElementById('to');
    const depEl = document.getElementById('depart');
    const retWrap = document.getElementById('returnWrap');
    const retEl = document.getElementById('return');
    const errBox = document.getElementById('errBox');

    // Fill datalist
    const dl = document.getElementById('airports');
    AIRPORTS.forEach(a=>{
      const o = document.createElement('option');
      o.value = `${a.code} - ${a.city} (${a.name})`;
      dl.appendChild(o);
    });

    // Min date = today
    depEl.min = new Date().toISOString().split('T')[0];

    // Tabs: one-way vs round-trip (UI chỉ bật trường ngày về; BE có thể bổ sung sau)
    const tabOne = document.getElementById('tab-oneway');
    const tabRound = document.getElementById('tab-round');
    function setTrip(type){
      const isRound = (type==='round');
      tabOne.classList.toggle('active', !isRound);
      tabRound.classList.toggle('active', isRound);
      retWrap.hidden = !isRound;
      if (!isRound) retEl.value = '';
    }
    tabOne.addEventListener('click',()=>setTrip('one'));
    tabRound.addEventListener('click',()=>setTrip('round'));

    // Swap From/To
    document.getElementById('swapBtn').addEventListener('click',()=>{
      [fromEl.value, toEl.value] = [toEl.value, fromEl.value];
    });

    // Helper: parse IATA code from input text
    function parseCode(v){
      if(!v) return '';
      const m = v.match(/[A-Z]{3}/); // lấy bộ 3 chữ cái đầu tiên
      if (m) return m[0];
      // thử match theo city
      v = v.toLowerCase();
      const hit = AIRPORTS.find(a=> a.city.toLowerCase()===v || a.name.toLowerCase()===v);
      return hit? hit.code : '';
    }

    // Submit search
    document.getElementById('searchForm').addEventListener('submit', (e)=>{
      e.preventDefault();
      errBox.style.display='none';
      const di = parseCode(fromEl.value.trim());
      const den = parseCode(toEl.value.trim());
      const ngay = depEl.value;
      if(!di || !den){ return showErr('Vui lòng chọn sân bay đi/đến từ danh sách.'); }
      if(di === den){ return showErr('Điểm đi và đến không được trùng nhau.'); }
      if(!ngay){ return showErr('Vui lòng chọn ngày đi.'); }
      // Điều hướng tới trang tìm chuyến của backend PHP
      const qs = new URLSearchParams({p:'book_search', di, den, ngay});
      window.location.href = './index.php?' + qs.toString();
    });

    function showErr(msg){ errBox.textContent = msg; errBox.style.display='block'; }

    // Footer year
    document.getElementById('y').textContent = new Date().getFullYear();
  
});
