/*******************************************************************
 * Smart Chrism Shop - Frontend Logic
 * --------------------------------
 * Handles:
 *  - Product display
 *  - Cart system
 *  - Admin dashboard
 *  - Checkout and M-Pesa payment integration
 *  - Backend integrations for:
 *      -> Products CRUD
 *      -> M-Pesa payments
 *      -> Order management
 *******************************************************************/

// ------------------ Sample product data ------------------
const sampleProducts = [
  { id: 'p1', title: 'Court Classic', description: 'Timeless white sneaker', price: 79.99, stock: 12, image: '' },
  { id: 'p2', title: 'Trail Runner', description: 'Lightweight trail shoe', price: 99.99, stock: 8, image: '' },
  { id: 'p3', title: 'City Slip-On', description: 'Comfortable everyday shoe', price: 59.99, stock: 20, image: '' }
];

// ------------------ Helpers: localStorage ------------------
function loadProducts() {
  const raw = localStorage.getItem('products');
  return raw ? JSON.parse(raw) : sampleProducts.slice();
}
function saveProducts(list) {
  localStorage.setItem('products', JSON.stringify(list));
}
function loadCart() {
  const raw = localStorage.getItem('cart');
  return raw ? JSON.parse(raw) : [];
}
function saveCart(c) {
  localStorage.setItem('cart', JSON.stringify(c));
  updateCartUI();
}

// ------------------ Render products ------------------
function renderProducts() {
  const grid = document.getElementById('productGrid');
  grid.innerHTML = '';
  const products = loadProducts();
  products.forEach(p => {
    const el = document.createElement('div');
    el.className = 'card';
    const editButton = adminLoggedIn
      ? `<button class="btn ghost" data-edit="${p.id}">Edit</button>`
      : '';
    el.innerHTML = `
      <div class="thumb">
        ${p.image ? `<img src="${p.image}" alt="${p.title}" style="max-height:100%;border-radius:8px">` : p.title}
      </div>
      <div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="font-weight:700">${p.title}</div>
          <div class="price">KSH ${(+p.price).toFixed(2)}</div>
        </div>
        <div class="muted" style="font-size:13px;margin-top:6px">${p.description || ''}</div>
      </div>
      <div style="display:flex;gap:8px;margin-top:8px">
        <button class="btn" data-add="${p.id}">Add to cart</button>
        ${editButton}
      </div>
    `;
    grid.appendChild(el);
  });
}

// ------------------ Cart logic ------------------
function updateCartUI() {
  const list = loadCart();
  const cartList = document.getElementById('cartList');
  cartList.innerHTML = '';
  let total = 0;
  list.forEach(item => {
    total += item.price * item.qty;
    const row = document.createElement('div');
    row.className = 'cart-item';
    row.innerHTML = `
      <div style="flex:1">
        ${item.title}
        <div class="muted">${item.qty} × KSH ${item.price.toFixed(2)}</div>
      </div>
      <div>
        <button data-inc="${item.id}" class="btn ghost">+</button>
        <button data-dec="${item.id}" class="btn ghost">−</button>
      </div>
    `;
    cartList.appendChild(row);
  });
  document.getElementById('cartTotal').textContent = `KSH ${total.toFixed(2)}`;
  document.getElementById('cartCount').textContent = list.reduce((s, i) => s + i.qty, 0);
}

function addToCart(id) {
  const products = loadProducts();
  const p = products.find(x => x.id === id);
  if (!p) return;
  let cart = loadCart();
  const existing = cart.find(c => c.id === id);
  if (existing) existing.qty++;
  else cart.push({ id: p.id, title: p.title, price: +p.price, qty: 1 });
  saveCart(cart);
  toast('Added to cart');
}

let adminLoggedIn = false;
let adminEmail = '';

// ------------------ Admin editor ------------------
function openEditor(mode = 'edit', product) {
  const modal = document.getElementById('editorModal');
  modal.style.display = 'flex';
  document.getElementById('modalTitle').textContent =
    mode === 'new' ? 'Add product' : 'Edit product';
  const form = document.getElementById('productForm');
  form.reset();
  form.elements['image'].value = '';
  if (form.elements['imageFile']) {
    form.elements['imageFile'].value = '';
  }
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

// ------------------ Toast message ------------------
function toast(msg, tm = 2000) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.display = 'block';
  setTimeout(() => (t.style.display = 'none'), tm);
}

// ------------------ Payment Instructions ------------------
let currentOrderData = null;

function showPaymentInstructions(orderId, total, customerName, customerPhone, orderItems = []) {
  const modal = document.getElementById('paymentInstructionsModal');
  const paybill = '247247';
  const account = '0705399169';
  const amount = total.toFixed(2);
  
  // Store order data for later use
  currentOrderData = {
    orderId,
    total: amount,
    customerName,
    customerPhone,
    paybill,
    account,
    items: orderItems
  };
  
  // Update modal content
  document.getElementById('paymentOrderId').textContent = '#' + orderId;
  document.getElementById('trackOrderId').textContent = '#' + orderId;
  document.getElementById('paymentTotalAmount').textContent = 'KSh ' + amount;
  document.getElementById('paymentPaybill').textContent = paybill;
  document.getElementById('paymentAccount').textContent = account;
  document.getElementById('paymentAmount').textContent = 'KSh ' + amount;
  
  // Display order items
  const itemsContainer = document.getElementById('paymentOrderItems');
  if (orderItems && orderItems.length > 0) {
    let itemsHtml = '';
    orderItems.forEach(item => {
      const itemName = item.title || item.name || 'Product';
      const itemQty = item.qty || item.quantity || 1;
      const itemPrice = (item.price || 0).toFixed(2);
      const itemTotal = ((item.price || 0) * itemQty).toFixed(2);
      itemsHtml += `<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #dee2e6">
        <span>${itemName} × ${itemQty}</span>
        <span style="font-weight:600">KSh ${itemTotal}</span>
      </div>`;
    });
    itemsHtml += `<div style="display:flex;justify-content:space-between;padding:8px 0;margin-top:8px;border-top:2px solid #25D366;font-weight:700;color:#25D366">
      <span>Total:</span>
      <span>KSh ${amount}</span>
    </div>`;
    itemsContainer.innerHTML = itemsHtml;
  } else {
    itemsContainer.innerHTML = `<div style="color:#6c757d">Total: KSh ${amount}</div>`;
  }
  
  // Create full payment message
  const paymentMessage = `📦 ORDER #${orderId}\n\n💰 PAYMENT DETAILS:\nPaybill: ${paybill}\nAccount: ${account}\nAmount: KSh ${amount}\n\n👤 CUSTOMER:\nName: ${customerName}\nPhone: ${customerPhone}\n\n✅ Please complete payment to confirm your order.`;
  document.getElementById('paymentFullMessage').textContent = paymentMessage;
  
  // Update WhatsApp link with better message
  const whatsappText = encodeURIComponent(`✅ Payment Confirmation\n\nOrder ID: #${orderId}\nAmount: KSh ${amount}\nCustomer: ${customerName}\nPhone: ${customerPhone}\n\nI have completed the M-Pesa payment. Please confirm my order.`);
  document.getElementById('whatsappLink').href = `https://wa.me/254705399169?text=${whatsappText}`;
  
  // Show modal
  modal.style.display = 'flex';
  
  // Save order locally
  saveOrderToLocalStorage(orderId, total, customerName, customerPhone, orderItems);
}

function copyAllPaymentDetails() {
  if (!currentOrderData) return;
  
  const details = `Paybill: ${currentOrderData.paybill}\nAccount: ${currentOrderData.account}\nAmount: KSh ${currentOrderData.total}\nOrder: #${currentOrderData.orderId}`;
  
  navigator.clipboard.writeText(details).then(() => {
    toast('All payment details copied!');
  }).catch(() => {
    const textArea = document.createElement('textarea');
    textArea.value = details;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    toast('All payment details copied!');
  });
}

function printPaymentInstructions() {
  if (!currentOrderData) return;
  
  const printWindow = window.open('', '_blank');
  const printContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Payment Instructions - Order #${currentOrderData.orderId}</title>
      <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 600px; margin: 0 auto; }
        h1 { color: #25D366; }
        .payment-box { border: 2px solid #25D366; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .detail { margin: 10px 0; font-size: 18px; }
        .amount { font-size: 24px; font-weight: bold; color: #856404; }
      </style>
    </head>
    <body>
      <h1>Payment Instructions</h1>
      <p><strong>Order #${currentOrderData.orderId}</strong></p>
      <div class="payment-box">
        <h2>M-Pesa Payment Details</h2>
        <div class="detail"><strong>Paybill:</strong> ${currentOrderData.paybill}</div>
        <div class="detail"><strong>Account:</strong> ${currentOrderData.account}</div>
        <div class="detail amount"><strong>Amount:</strong> KSh ${currentOrderData.total}</div>
      </div>
      <h3>Payment Steps:</h3>
      <ol>
        <li>Open M-Pesa on your phone</li>
        <li>Select "Pay Bill"</li>
        <li>Enter Paybill: ${currentOrderData.paybill}</li>
        <li>Enter Account: ${currentOrderData.account}</li>
        <li>Enter Amount: KSh ${currentOrderData.total}</li>
        <li>Enter your M-Pesa PIN and confirm</li>
      </ol>
      <p><strong>Customer:</strong> ${currentOrderData.customerName}</p>
      <p><strong>Phone:</strong> ${currentOrderData.customerPhone}</p>
      <p style="margin-top: 30px; font-size: 12px; color: #666;">Generated on ${new Date().toLocaleString()}</p>
    </body>
    </html>
  `;
  
  printWindow.document.write(printContent);
  printWindow.document.close();
  printWindow.print();
}

function saveOrderLocally() {
  if (!currentOrderData) return;
  saveOrderToLocalStorage(
    currentOrderData.orderId,
    currentOrderData.total,
    currentOrderData.customerName,
    currentOrderData.customerPhone,
    currentOrderData.items
  );
  toast('Order details saved locally! You can access them anytime.');
}

function saveOrderToLocalStorage(orderId, total, name, phone, items) {
  const orders = JSON.parse(localStorage.getItem('saved_orders') || '[]');
  const orderData = {
    id: orderId,
    total: total,
    name: name,
    phone: phone,
    items: items,
    date: new Date().toISOString(),
    status: 'pending'
  };
  
  // Remove duplicate if exists
  const filtered = orders.filter(o => o.id !== orderId);
  filtered.unshift(orderData); // Add to beginning
  
  // Keep only last 10 orders
  const limited = filtered.slice(0, 10);
  localStorage.setItem('saved_orders', JSON.stringify(limited));
}

function copyPaymentMessage() {
  const messageEl = document.getElementById('paymentFullMessage');
  messageEl.select();
  document.execCommand('copy');
  toast('Payment message copied to clipboard!');
}

// Close payment modal
document.addEventListener('DOMContentLoaded', function() {
  const closeBtn = document.getElementById('closePaymentModal');
  if (closeBtn) {
    closeBtn.addEventListener('click', function() {
      document.getElementById('paymentInstructionsModal').style.display = 'none';
    });
  }
});

// Copy to clipboard helper with enhanced visual feedback
function copyToClipboard(element, label) {
  const text = element.textContent.trim();
  const originalBg = element.style.background || '#fff';
  const originalColor = element.style.color || '';
  const originalTransform = element.style.transform || '';
  const originalBoxShadow = element.style.boxShadow || '';
  
  navigator.clipboard.writeText(text).then(() => {
    // Enhanced visual feedback
    element.style.background = '#d4edda';
    element.style.color = '#155724';
    element.style.transform = 'scale(1.1)';
    element.style.boxShadow = '0 4px 12px rgba(40, 167, 69, 0.4)';
    element.style.transition = 'all 0.3s ease';
    
    // Show checkmark
    const checkmark = document.createElement('span');
    checkmark.textContent = ' ✓';
    checkmark.style.color = '#28a745';
    checkmark.style.fontWeight = 'bold';
    element.appendChild(checkmark);
    
    toast(`✓ ${label} copied: ${text}`, 2000);
    
    setTimeout(() => {
      element.style.background = originalBg;
      element.style.color = originalColor;
      element.style.transform = originalTransform;
      element.style.boxShadow = originalBoxShadow;
      if (checkmark.parentNode) {
        checkmark.parentNode.removeChild(checkmark);
      }
    }, 1500);
  }).catch(() => {
    // Fallback for older browsers
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    
    // Visual feedback for fallback
    element.style.background = '#d4edda';
    element.style.color = '#155724';
    setTimeout(() => {
      element.style.background = originalBg;
      element.style.color = originalColor;
    }, 500);
    
    toast(`✓ ${label} copied: ${text}`, 2000);
  });
}

// ------------------ Backend placeholder calls ------------------
async function callBackendCreateProduct(product) {
  product.id = 'p_' + Math.random().toString(36).slice(2, 8);
  return product;
}
async function callBackendUpdateProduct(id, product) {
  return Object.assign({ id }, product);
}
async function createOrder(orderData) {
  // Create order in database (without payment processing)
  try {
    return await requestJSON('create_order.php', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(orderData),
      credentials: 'include'
    });
  } catch (err) {
    console.error('Create order error:', err);
    throw err;
  }
}

async function callBackendProcessPayment(paymentPayload) {
  // Legacy function - not used for manual payments
  return await requestJSON('mpesa_pay.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(paymentPayload)
  });
}
async function callBackendSendEmail(toOwner, order) {
  return { ok: true };
}

async function requestJSON(url, options = {}) {
  // Check if page is opened via file:// protocol
  if (window.location.protocol === 'file:') {
    throw new Error('Please access this page through XAMPP/Apache. Open: http://localhost/kk/index.html instead of double-clicking the file.');
  }

  // Set default options with credentials
  const defaultOptions = {
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    }
  };
  
  // Merge with provided options
  const finalOptions = {
    ...defaultOptions,
    ...options,
    headers: {
      ...defaultOptions.headers,
      ...(options.headers || {})
    }
  };

  let res;
  try {
    res = await fetch(url, finalOptions);
  } catch (err) {
    if (err.message === 'Failed to fetch' || err.name === 'TypeError') {
      const protocol = window.location.protocol;
      const host = window.location.host;
      if (!host || host === '') {
        throw new Error('Cannot reach the server. Please access this page through XAMPP: http://localhost/kk/index.html');
      }
      throw new Error(`Cannot reach the server at ${window.location.origin}/${url}. Ensure XAMPP Apache is running.`);
    }
    throw err;
  }

  // Handle 405 Method Not Allowed specifically
  if (res.status === 405) {
    throw new Error('Method not allowed (405). The server rejected the request method. Please check the API endpoint.');
  }

  const text = await res.text();
  let data = {};
  if (text) {
    try {
      data = JSON.parse(text);
    } catch (err) {
      const cleaned = text.replace(/<[^>]*>/g, '').trim();
      throw new Error(cleaned || `Unexpected server response (${res.status})`);
    }
  }

  if (!res.ok || data.ok === false) {
    const message = (data && data.error) ? data.error : `Request failed (${res.status})`;
    throw new Error(message);
  }

  return data;
}

// Upload product image to the server and return the hosted URL
async function uploadProductImage(file) {
  const body = new FormData();
  body.append('id_card', file);
  body.append('ajax', '1');
  const res = await fetch('upload.php', {
    method: 'POST',
    body
  });
  if (!res.ok) {
    throw new Error('Upload failed. Please try again.');
  }
  const data = await res.json().catch(() => null);
  if (!data || !data.ok || !data.url) {
    throw new Error(data && data.error ? data.error : 'Upload failed.');
  }
  return data.url;
}

// ------------------ Event listeners ------------------
document.addEventListener('click', e => {
  const add = e.target.dataset.add;
  if (add) addToCart(add);

  const edit = e.target.dataset.edit;
  if (edit) {
    if (!requireAdmin()) return;
    const p = loadProducts().find(x => x.id === edit);
    openEditor('edit', p);
  }

  const inc = e.target.dataset.inc;
  if (inc) {
    let c = loadCart();
    const it = c.find(i => i.id === inc);
    if (it) {
      it.qty++;
      saveCart(c);
    }
  }

  const dec = e.target.dataset.dec;
  if (dec) {
    let c = loadCart();
    const it = c.find(i => i.id === dec);
    if (it) {
      it.qty--;
      if (it.qty <= 0) c = c.filter(x => x.id !== dec);
      saveCart(c);
    }
  }
});

document.getElementById('openCartBtn').addEventListener('click', () => {
  window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
});

document.getElementById('openAddProduct').addEventListener('click', () => {
  if (!requireAdmin()) return;
  openEditor('new', null);
  document.getElementById('productForm').reset();
  document.getElementById('productForm').elements['id'].value = '';
});

document.getElementById('cancelEdit').addEventListener('click', closeEditor);

document.getElementById('productForm').addEventListener('submit', async function(ev) {
  ev.preventDefault();
  if (!requireAdmin()) return;
  const fd = new FormData(this);
  const fileInput = this.elements['imageFile'];
  let currentImage = (fd.get('image') || '').trim();

  if (fileInput && fileInput.files && fileInput.files.length > 0) {
    try {
      const uploadedUrl = await uploadProductImage(fileInput.files[0]);
      currentImage = uploadedUrl;
      this.elements['image'].value = uploadedUrl;
    } catch (err) {
      toast(err.message || 'Image upload failed');
      return;
    }
  }

  if (!currentImage) {
    toast('Please upload an image before saving.');
    return;
  }

  const model = {
    title: fd.get('title'),
    description: fd.get('description'),
    price: parseFloat(fd.get('price') || 0),
    stock: parseInt(fd.get('stock') || 0),
    image: currentImage
  };
  const id = fd.get('id');
  let products = loadProducts();

  if (id) {
    products = products.map(p => (p.id === id ? { ...p, ...model } : p));
    saveProducts(products);
    await callBackendUpdateProduct(id, model);
    toast('Product updated');
  } else {
    const created = await callBackendCreateProduct(model);
    products.push(created);
    saveProducts(products);
    toast('Product created');
  }
  closeEditor();
  renderProducts();
  updateCartUI();
});

document.getElementById('checkoutForm').addEventListener('submit', async function(ev) {
  ev.preventDefault();
  const cart = loadCart();
  if (cart.length === 0) { 
    toast('Cart is empty'); 
    return; 
  }
  
  const submitBtn = this.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.disabled = true;
  submitBtn.textContent = 'Processing...';
  
  const fd = new FormData(this);
  const order = {
    name: fd.get('name'),
    email: fd.get('email'),
    phone: fd.get('phone'),
    address: fd.get('address'),
    payment_method: fd.get('payment_method') || 'mpesa',
    items: cart,
    total: cart.reduce((s, i) => s + i.price * i.qty, 0)
  };

  // Validate Kenyan phone number format
  const phoneDigits = order.phone.replace(/\D+/g, '');
  const phoneStr = order.phone.trim();
  
  // Check if it's a valid Kenyan mobile number format
  // Accepts: 0712345678, +254712345678, 254712345678, 712345678
  let isValid = false;
  if (phoneStr.match(/^(0|\+254|254)?7\d{8}$/)) {
    isValid = true;
  } else if (phoneDigits.length >= 9 && phoneDigits.length <= 12) {
    // Additional check: should start with 7, 07, 2547, or +2547
    if (phoneDigits.startsWith('7') || phoneDigits.startsWith('07') || phoneDigits.startsWith('2547')) {
      isValid = true;
    }
  }
  
  if (!isValid) {
    toast('Please enter a valid Kenyan M-Pesa phone number (e.g. 0712345678)');
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
    return;
  }

  // For manual M-Pesa payments - create order and show payment instructions
  if (order.payment_method === 'mpesa') {
    try {
      // Create order in database
      const orderResponse = await createOrder(order);
      if (orderResponse.ok && orderResponse.order_id) {
        // Clear cart on success
        localStorage.removeItem('cart');
        updateCartUI();
        
        // Show payment instructions modal with order items
        showPaymentInstructions(
          orderResponse.order_id, 
          order.total, 
          order.name, 
          order.phone,
          order.items || cart
        );
        
        // Reset form
        this.reset();
      } else {
        toast(orderResponse.error || 'Failed to create order. Please try again.');
      }
    } catch (err) {
      toast(err.message || 'Order failed. Please try again.');
      console.error('Order Error:', err);
    }
  } else {
    toast('Unsupported payment method.');
  }
  
  submitBtn.disabled = false;
  submitBtn.textContent = originalText;
});

(function init() {
  if (!localStorage.getItem('products')) saveProducts(sampleProducts.slice());
  updateCartUI();
  refreshAdminStatus().then(() => {
    renderProducts();
    updateAdminUI();
  });
})();

// ------------------ Admin auth helpers ------------------
function toggleAdminLoginModal(show) {
  document.getElementById('adminLoginModal').style.display = show ? 'flex' : 'none';
  document.getElementById('adminLoginError').style.display = 'none';
  document.getElementById('adminLoginForm').reset();
}

function requireAdmin() {
  if (!adminLoggedIn) {
    toast('Admin login required');
    toggleAdminLoginModal(true);
    return false;
  }
  return true;
}

function updateAdminUI() {
  const adminPanel = document.getElementById('adminPanel');
  const loginBtn = document.getElementById('adminLoginBtn');
  const logoutBtn = document.getElementById('adminLogoutBtn');
  adminPanel.style.display = adminLoggedIn ? 'block' : 'none';
  loginBtn.style.display = adminLoggedIn ? 'none' : 'inline-block';
  logoutBtn.style.display = adminLoggedIn ? 'inline-block' : 'none';
  document.querySelectorAll('[data-edit]').forEach(btn => {
    btn.style.display = adminLoggedIn ? 'inline-block' : 'none';
  });
}

async function refreshAdminStatus() {
  try {
    const data = await requestJSON('admin_status.php', { credentials: 'include' });
    adminLoggedIn = !!data.loggedIn;
    adminEmail = data.email || '';
  } catch (err) {
    adminLoggedIn = false;
    adminEmail = '';
  }
}

document.getElementById('adminLoginBtn').addEventListener('click', () => toggleAdminLoginModal(true));
document.getElementById('closeAdminLogin').addEventListener('click', () => toggleAdminLoginModal(false));

document.getElementById('adminLogoutBtn').addEventListener('click', async () => {
  try {
    await requestJSON('admin_logout.php', { method: 'POST', credentials: 'include' });
    adminLoggedIn = false;
    adminEmail = '';
    renderProducts();
    updateAdminUI();
    toast('Logged out');
  } catch (err) {
    toast(err.message || 'Logout failed');
  }
});

document.getElementById('adminLoginForm').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = {
    email: fd.get('email'),
    password: fd.get('password')
  };
  try {
    const data = await requestJSON('admin_login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      credentials: 'include'
    });
    adminLoggedIn = true;
    adminEmail = data.email;
    toggleAdminLoginModal(false);
    renderProducts();
    updateAdminUI();
    toast('Admin logged in');
  } catch (err) {
    const errorBox = document.getElementById('adminLoginError');
    errorBox.textContent = err.message || 'Login failed';
    errorBox.style.display = 'block';
  }
});
