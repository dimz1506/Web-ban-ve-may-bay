document.addEventListener('DOMContentLoaded', () => {
  // ====== Airports data (rút gọn Việt Nam + hub) ======
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

  // Elements
  const fromEl = document.getElementById('from');
  const toEl = document.getElementById('to');
  const depEl = document.getElementById('depart');
  const retWrap = document.getElementById('returnWrap');
  const retEl = document.getElementById('return');
  const errBox = document.getElementById('errBox');
  const dl = document.getElementById('airports');
  const swapBtn = document.getElementById('swapBtn');
  const tabOne = document.getElementById('tab-oneway');
  const tabRound = document.getElementById('tab-round');
  const paxEl = document.getElementById('pax') || { value: 1 };
  const cabinEl = document.getElementById('cabin') || { value: 'ECON' };

  // Fill datalist
  dl.innerHTML = '';
  AIRPORTS.forEach(a=>{
    const o = document.createElement('option');
    o.value = `${a.code} - ${a.city} (${a.name})`;
    dl.appendChild(o);
  });

  // Min date = today
  const today = new Date().toISOString().split('T')[0];
  depEl.min = today;
  retEl.min = today;

  // Tabs: one-way vs round-trip
  function setTrip(type){
    const isRound = (type === 'round');
    tabOne.classList.toggle('active', !isRound);
    tabRound.classList.toggle('active', isRound);
    retWrap.hidden = !isRound;
    retEl.required = isRound;
    // if switching to one-way, clear return date
    if (!isRound) retEl.value = '';
  }
  tabOne && tabOne.addEventListener('click', ()=>setTrip('one'));
  tabRound && tabRound.addEventListener('click', ()=>setTrip('round'));

  // Swap From/To
  swapBtn && swapBtn.addEventListener('click', ()=>{
    const a = fromEl.value;
    fromEl.value = toEl.value;
    toEl.value = a;
    fromEl.focus();
  });

  // Helper: parse IATA code from input text (robust)
  function parseCode(v){
    if(!v) return '';
    v = v.trim();
    // match first 3 uppercase letters
    let m = v.match(/([A-Z]{3})/);
    if(m) return m[1];
    // try uppercase the first token
    const t = v.split(/\s*[-–—,()]\s*/)[0].trim().toUpperCase();
    if (t.length === 3) return t;
    // fallback: try match by city or name (case-insensitive)
    const lower = v.toLowerCase();
    const hit = AIRPORTS.find(a => a.city.toLowerCase() === lower || a.name.toLowerCase() === lower || `${a.code} - ${a.city} (${a.name})`.toLowerCase() === lower);
    return hit ? hit.code : '';
  }

  // show error (accessible)
  function showErr(msg){
    errBox.textContent = msg;
    errBox.hidden = false;
    errBox.setAttribute('aria-hidden', 'false');
    errBox.focus && errBox.focus();
  }
  function clearErr(){
    errBox.textContent = '';
    errBox.hidden = true;
    errBox.setAttribute('aria-hidden', 'true');
  }

  // Keep return.min in sync with depart
  depEl.addEventListener('change', () => {
    if (depEl.value) {
      // ensure return cannot be before depart
      retEl.min = depEl.value;
      if (retEl.value && (new Date(retEl.value) < new Date(depEl.value))) {
        retEl.value = '';
      }
    } else {
      retEl.min = today;
    }
  });

  // Submit search
  document.getElementById('searchForm').addEventListener('submit', (e)=>{
    e.preventDefault();
    clearErr();

    const fromRaw = (fromEl.value || '').trim();
    const toRaw = (toEl.value || '').trim();
    const fromCode = parseCode(fromRaw);
    const toCode = parseCode(toRaw);
    const depart = depEl.value;
    const ret = retEl.value;
    const pax = parseInt(paxEl.value, 10) || 1;
    const cabin = (cabinEl.value || 'ECON');

    // Validations
    if (!fromRaw || !toRaw) return showErr('Vui lòng chọn sân bay đi và đến.');
    if (!fromCode || !toCode) return showErr('Vui lòng chọn sân bay hợp lệ từ danh sách gợi ý.');
    if (fromCode === toCode) return showErr('Điểm đi và điểm đến không thể giống nhau.');
    if (!depart) return showErr('Vui lòng chọn ngày đi.');
    // If round trip visible => require return and check order
    const isRound = !retWrap.hidden;
    if (isRound) {
      if (!ret) return showErr('Vui lòng chọn ngày về.');
      if (new Date(ret) < new Date(depart)) return showErr('Ngày về phải bằng hoặc sau ngày đi.');
    }

    if (pax < 1 || pax > 9) return showErr('Số khách phải trong khoảng 1–9.');

    // Build query with names the server expects
    const params = new URLSearchParams();
    params.set('p', 'search_results');
    params.set('from', fromCode);   // server can accept either code or full string; decide in backend
    params.set('to', toCode);
    params.set('depart', depart);
    if (isRound) params.set('return', ret);
    params.set('pax', pax.toString());
    params.set('cabin', cabin);
    params.set('trip_type', isRound ? 'round' : 'oneway');

    // go to search results
    window.location.href = './index.php?' + params.toString();
  });

  // init UI state (if you want to persist state from query string, you can read URL params here)
  clearErr();
  // default trip type = oneway
  setTrip('one');
});
