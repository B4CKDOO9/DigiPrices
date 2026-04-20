let currentEditDisplayId = null;
let cachedDisplays = [];
let cachedProducts = [];
// ============================================================
//  DigiPrices — app.js
//  Frontend logic. Replace fetch() URLs with your PHP endpoints.
// ============================================================

const API = {
    login: 'api/login.php',
    displays: 'api/displays.php',
    products: 'api/products.php',
    logs: 'api/logs.php',
};

// ============================================================
//  AUTH HELPERS
// ============================================================

function getSession() {
    const s = sessionStorage.getItem('digiprices_user');
    return s ? JSON.parse(s) : null;
}

function requireAuth() {
    if (!getSession()) {
        window.location.href = 'index.html';
    }
}

function logout() {
    sessionStorage.removeItem('digiprices_user');
    window.location.href = 'index.html';
}

// ============================================================
//  LOGIN PAGE
// ============================================================

const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const errorEl = document.getElementById('loginError');

        const res = await fetch(API.login, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        const data = await res.json();
        if (data.success) {
            sessionStorage.setItem('digiprices_user', JSON.stringify(data.admin));
            window.location.href = 'dashboard.html';
        } else {
            errorEl.style.display = 'block';
        }
    });
}

// ============================================================
//  DASHBOARD PAGE
// ============================================================

if (document.getElementById('devicesGrid')) {
    requireAuth();
    loadDashboard();
}

async function loadDashboard() {
    const user = getSession();
    if (document.getElementById('navUsername')) {
        document.getElementById('navUsername').textContent = user?.name || user?.username || 'Admin';
    }

    const res = await fetch(API.displays);
    const displays = await res.json();

    // PLACEHOLDER DATA — remove when PHP is ready
    //const displays = [
    //    { id_display: 1, section: 'Aisle 1', mac: 'AA:BB:CC:DD:EE:01', ip: '192.168.1.101', product_id: 3, fw_version: 'v1.2.0' },
    //    { id_display: 2, section: 'Aisle 2', mac: 'AA:BB:CC:DD:EE:02', ip: '192.168.1.102', product_id: 5, fw_version: 'v1.2.0' },
    //    { id_display: 3, section: 'Checkout', mac: 'AA:BB:CC:DD:EE:03', ip: '192.168.1.103', product_id: null, fw_version: 'v1.1.8' },
    // ];

    renderDashboard(displays);
}

function renderDashboard(displays) {
    const grid = document.getElementById('devicesGrid');
    const online = displays.filter(d => d.ip).length; // placeholder logic
    const offline = displays.length - online;

    document.getElementById('statTotal').textContent = displays.length;
    document.getElementById('statOnline').textContent = online;
    document.getElementById('statOffline').textContent = offline;
    document.getElementById('statProducts').textContent = displays.filter(d => d.product_id).length;
    document.getElementById('deviceCount').textContent = `${displays.length} devices`;

    grid.innerHTML = displays.map(d => `
    <div class="device-card">
      <div class="device-card-header">
        <span class="device-name">${d.section}</span>
        <div class="status-dot ${d.ip ? '' : 'offline'}"></div>
      </div>
      <div class="device-info">
        <div class="device-info-row"><span>IP</span><span>${d.ip || '—'}</span></div>
        <div class="device-info-row"><span>MAC</span><span>${d.mac}</span></div>
        <div class="device-info-row"><span>Product ID</span><span>${d.product_id ?? '—'}</span></div>
        <div class="device-info-row"><span>Firmware</span><span>${d.fw_version}</span></div>
      </div>
    </div>
  `).join('');
}

// ============================================================
//  ADMIN PAGE
// ============================================================

if (document.getElementById('displaysTable')) {
    requireAuth();
    loadAdmin();
}

async function loadAdmin() {
    const user = getSession();
    if (document.getElementById('navUsername')) {
        document.getElementById('navUsername').textContent = user?.name || user?.username || 'Admin';
    }

    const [dRes, pRes] = await Promise.all([fetch(API.displays), fetch(API.products)]);
    const displays = await dRes.json();
    const products = await pRes.json();
    cachedDisplays = displays;  // save for later
    cachedProducts = products;  // save for later

    // PLACEHOLDER DATA
    //const displays = [
    //    { id_display: 1, section: 'Aisle 1', mac: 'AA:BB:CC:DD:EE:01', ip: '192.168.1.101', product_id: 3, fw_version: 'v1.2.0' },
    //    { id_display: 2, section: 'Aisle 2', mac: 'AA:BB:CC:DD:EE:02', ip: '192.168.1.102', product_id: 5, fw_version: 'v1.2.0' },
    //];
    //const products = [
    //    { id_product: 3, displaying_name: 'Milk 1L', name: 'Full Fat Milk', price: 1.29, currency_code: 'EUR', barcode: '123456789', last_price_change: '2025-01-10 12:00:00' },
    //    { id_product: 5, displaying_name: 'Bread 500g', name: 'White Bread', price: 0.99, currency_code: 'EUR', barcode: '987654321', last_price_change: '2025-01-12 09:30:00' },
    //];

    renderDisplaysTable(displays);
    renderProductsTable(products);
}

function renderDisplaysTable(displays) {
    const tbody = document.getElementById('displaysBody');
    tbody.innerHTML = displays.map(d => `
    <tr>
      <td>#${d.id_display}</td>
      <td>${d.section}</td>
      <td style="font-family:var(--font-mono);font-size:0.78rem;">${d.mac}</td>
      <td style="font-family:var(--font-mono);font-size:0.78rem;">${d.ip}</td>
      <td>${d.product_id ?? '—'}</td>
      <td>${d.fw_version}</td>
      <td>
        <button class="btn-edit" onclick="editDisplay(${d.id_display})">Edit</button>
        <button class="btn-delete" onclick="deleteDisplay(${d.id_display})">Delete</button>
      </td>
    </tr>
  `).join('');
}

function renderProductsTable(products) {
    const tbody = document.getElementById('productsBody');
    tbody.innerHTML = products.map(p => `
    <tr>
      <td>#${p.id_product}</td>
      <td>${p.displaying_name}</td>
      <td>${p.name}</td>
      <td style="font-family:var(--font-mono);">${p.price} ${p.currency_code}</td>
      <td>${p.currency_code}</td>
      <td style="font-family:var(--font-mono);font-size:0.78rem;">${p.barcode}</td>
      <td style="font-size:0.8rem;color:var(--text-muted);">${p.last_price_change}</td>
      <td>
        <button class="btn-edit" onclick="editProduct(${p.id_product})">Edit</button>
        <button class="btn-delete" onclick="deleteProduct(${p.id_product})">Delete</button>
      </td>
    </tr>
  `).join('');
}

// Stubs — wire to PHP later
async function addDisplay() {
    const section = document.getElementById('newSection').value;
    const mac = document.getElementById('newMac').value;
    const ip = document.getElementById('newIp').value;
    const fw_version = document.getElementById('newFwVersion').value;

    const res = await fetch(API.displays, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ section, mac, ip, fw_version, admin_id: getSession().id_admin })
    });
    const data = await res.json();
    if (data.success) {
        closeModal('displayModal');
        loadAdmin(); // refresh the table
    } else {
        alert('Error: ' + data.message);
    }
}

function editDisplay(id) {
    currentEditDisplayId = id;
    const d = cachedDisplays.find(x => x.id_display == id);
    document.getElementById('editSection').value = d.section;
    document.getElementById('editMac').value = d.mac;
    document.getElementById('editIp').value = d.ip;
    document.getElementById('editFwVersion').value = d.fw_version;

    const select = document.getElementById('editProductId');
    select.innerHTML = '<option value="">-- None --</option>';
    cachedProducts.forEach(p => { select.innerHTML += `<option value="${p.id_product}">${p.displaying_name}</option>`; });
    select.value = d.product_id ?? '';

    openModal('editDisplayModal');
}


async function saveEditDisplay() {
    const section = document.getElementById('editSection').value;
    const mac = document.getElementById('editMac').value;
    const ip = document.getElementById('editIp').value;
    const fw_version = document.getElementById('editFwVersion').value;
    const product_id = document.getElementById('editProductId').value;

    const res = await fetch(API.displays, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_display: currentEditDisplayId, section, mac, ip, fw_version, product_id, admin_id: getSession().id_admin })
    });
    const data = await res.json();
    if (data.success) {
        closeModal('editDisplayModal');
        loadAdmin();
    } else {
        alert('Error: ' + data.message);
    }
}

async function deleteDisplay(id) {
    if (!confirm(`Delete display #${id}?`)) return;
    const res = await fetch(API.displays, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_display: id, admin_id: getSession().id_admin})
    });
    const data = await res.json();
    if (data.success) {
        loadAdmin();
    } else {
        alert('Error: ' + data.message);
    }
}

let currentEditProductId = null;

async function addProduct() {
    const res = await fetch(API.products, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            displaying_name: document.getElementById('newDisplayingName').value,
            name: document.getElementById('newName').value,
            descr: document.getElementById('newDescr').value,
            price: document.getElementById('newPrice').value,
            price_per_kg: document.getElementById('newPricePerKg').value,
            currency_code: document.getElementById('newCurrencyCode').value,
            barcode: document.getElementById('newBarcode').value,
            admin_id: getSession().id_admin,
        })
    });
    const data = await res.json();
    if (data.success) {
        closeModal('productModal');
        loadAdmin();
    } else {
        alert('Error: ' + data.message);
    }
}

function editProduct(id) {
    fetch(API.products).then(r => r.json()).then(data => {
        const p = data.find(x => x.id_product == id);
        currentEditProductId = id;
        document.getElementById('editDisplayingName').value = p.displaying_name;
        document.getElementById('editName').value = p.name;
        document.getElementById('editDescr').value = p.descr || '';
        document.getElementById('editPrice').value = p.price;
        document.getElementById('editPricePerKg').value = p.price_per_kg || '';
        document.getElementById('editCurrencyCode').value = p.currency_code;
        document.getElementById('editBarcode').value = p.barcode;
        openModal('editProductModal');
    });
}

async function saveEditProduct() {
    const res = await fetch(API.products, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_product: currentEditProductId,
            displaying_name: document.getElementById('editDisplayingName').value,
            name: document.getElementById('editName').value,
            descr: document.getElementById('editDescr').value,
            price: document.getElementById('editPrice').value,
            price_per_kg: document.getElementById('editPricePerKg').value,
            currency_code: document.getElementById('editCurrencyCode').value,
            barcode: document.getElementById('editBarcode').value,
            admin_id: getSession().id_admin
        })
    });
    const data = await res.json();
    if (data.success) {
        closeModal('editProductModal');
        loadAdmin();
    } else {
        alert('Error: ' + data.message);
    }
}

async function deleteProduct(id) {
    if (!confirm(`Delete product #${id}?`)) return;
    const res = await fetch(API.products, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id_product: id,
            admin_id: getSession().id_admin,
        })
    });
    const data = await res.json();
    if (data.success) {
        loadAdmin();
    } else {
        alert('Error: ' + data.message);
    }
}

// ============================================================
//  LOGS PAGE
// ============================================================

let allLogs = [];

if (document.getElementById('logsTable')) {
    requireAuth();
    loadLogs();
}

async function loadLogs() {
    const user = getSession();
    if (document.getElementById('navUsername')) {
        document.getElementById('navUsername').textContent = user?.name || user?.username || 'Admin';
    }

    const res = await fetch(API.logs);
    allLogs = await res.json();

    // PLACEHOLDER DATA
    //allLogs = [
    //   { id_log: 1, admin_id: 1, display_id: 1, product_id: 3, what_changed: 'price updated to 1.49', changed_at: '2025-01-15 10:22:00' },
    //    { id_log: 2, admin_id: 1, display_id: 2, product_id: 5, what_changed: 'display assigned to Aisle 2', changed_at: '2025-01-14 08:10:00' },
    //   { id_log: 3, admin_id: 1, display_id: 3, product_id: null, what_changed: 'display added', changed_at: '2025-01-13 14:55:00' },
    //];

    renderLogs(allLogs);
}

function renderLogs(logs) {
    const tbody = document.getElementById('logsBody');
    if (!logs.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="table-empty">No logs found.</td></tr>';
        return;
    }
    tbody.innerHTML = logs.map(l => `
    <tr>
      <td>#${l.id_log}</td>
      <td>Admin #${l.admin_id}</td>
      <td>Display #${l.display_id}</td>
      <td>${l.product_id ? `#${l.product_id}` : '—'}</td>
      <td>${l.what_changed}</td>
      <td style="font-size:0.8rem;color:var(--text-muted);font-family:var(--font-mono);">${l.changed_at}</td>
    </tr>
  `).join('');
}

function filterLogs() {
    const q = document.getElementById('logSearch').value.toLowerCase();
    renderLogs(allLogs.filter(l =>
        l.what_changed.toLowerCase().includes(q) ||
        String(l.display_id).includes(q) ||
        String(l.admin_id).includes(q)
    ));
}

// ============================================================
//  MODAL HELPERS
// ============================================================

function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.style.display = 'none';
    });
});