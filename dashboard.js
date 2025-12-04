// dashboard.js (client)
async function getJSON(url) {
  const r = await fetch(url, { credentials: 'include' });
  return r.json();
}
async function postJSON(url, body) {
  const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(body)});
  return r.json();
}
async function putJSON(url, body) {
  const r = await fetch(url, { method:'PUT', headers:{'Content-Type':'application/json'}, credentials:'include', body: JSON.stringify(body)});
  return r.json();
}
async function delJSON(url) {
  const r = await fetch(url, { method:'DELETE', credentials:'include' });
  return r.json();
}

// Ensure logged in
(async function checkAuth() {
  const me = await getJSON('/api/me');
  if (!me.user) {
    window.location.href = 'login.html';
    return;
  } else {
    document.title = 'Dashboard — ' + me.user.email;
  }
})();

async function loadProducts() {
  const res = await getJSON('/api/products');
  return res.products || [];
}

async function renderProducts() {
  const list = document.getElementById('adminProductList');
  const products = await loadProducts();
  list.innerHTML = '';
  products.forEach(p => {
    const card = document.createElement('div');
    card.className = 'card';
    card.style.marginBottom = '8px';
    card.innerHTML = `
      <div><strong>${p.title}</strong></div>
      <div style="margin-top:6px">${p.description || ''}</div>
      <div style="margin-top:6px">Price: KSH ${Number(p.price).toFixed(2)}</div>
      <div>Stock: ${p.stock}</div>
      <div style="margin-top:8px">
        <button class="btn ghost" data-edit="${p.id}">Edit</button>
        <button class="btn ghost" data-del="${p.id}">Delete</button>
      </div>
    `;
    list.appendChild(card);
  });
}

function openEditor(product) {
  const modal = document.getElementById('editorModal');
  modal.style.display = 'flex';
  const form = document.getElementById('productForm');
  form.reset();
  if (product) {
    form.elements['id'].value = product.id;
    form.elements['title'].value = product.title;
    form.elements['description'].value = product.description;
    form.elements['price'].value = product.price;
    form.elements['stock'].value = product.stock;
    form.elements['image'].value = product.image || '';
  }
}
function closeEditor() {
  document.getElementById('editorModal').style.display = 'none';
}

document.getElementById('logoutBtn').addEventListener('click', async () => {
  await postJSON('/api/logout', {});
  window.location.href = 'login.html';
});

document.getElementById('addProductBtn').addEventListener('click', () => openEditor());

document.getElementById('cancelEdit').addEventListener('click', closeEditor);

document.getElementById('adminProductList').addEventListener('click', async (e) => {
  const id = e.target.dataset.edit;
  if (id) {
    const products = await loadProducts();
    openEditor(products.find(p => p.id === id));
    return;
  }
  const del = e.target.dataset.del;
  if (del) {
    await delJSON('/api/products/' + del);
    await renderProducts();
  }
});

document.getElementById('productForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const model = {
    title: fd.get('title'),
    description: fd.get('description'),
    price: parseFloat(fd.get('price') || 0),
    stock: parseInt(fd.get('stock') || 0),
    image: fd.get('image')
  };
  const id = fd.get('id');
  if (id) {
    await putJSON('/api/products/' + id, model);
  } else {
    await postJSON('/api/products', model);
  }
  closeEditor();
  await renderProducts();
});

async function loadOrders() {
  const res = await getJSON('/api/orders');
  return res.orders || [];
}
async function renderOrders() {
  const el = document.getElementById('orderList');
  const orders = await loadOrders();
  if (!orders.length) { el.textContent = 'No orders yet'; return; }
  el.innerHTML = '';
  orders.forEach(o => {
    const d = document.createElement('div');
    d.style.marginBottom = '10px';
    d.innerHTML = `<strong>${o.user_email || 'guest'}</strong><div class="muted">${new Date(o.created_at).toLocaleString()}</div><div>Total: KSH ${o.total}</div>`;
    el.appendChild(d);
  });
}

(async function init() {
  await renderProducts();
  await renderOrders();
})();
