document.addEventListener('DOMContentLoaded', () => {
  const page = document.querySelector('.ac-pos-page');
  if (!page) return;

  const productCards = Array.from(document.querySelectorAll('.ac-pos-product'));
  const cartRows = document.getElementById('pos-cart-rows');
  const cartCount = document.getElementById('pos-cart-count');
  const subtotalEl = document.getElementById('pos-subtotal');
  const taxEl = document.getElementById('pos-tax');
  const totalEl = document.getElementById('pos-total');
  const inputsWrap = document.getElementById('pos-items-inputs');
  const submitBtn = document.getElementById('pos-submit-btn');
  const searchInput = document.getElementById('pos-search');
  const barcodeInput = document.getElementById('pos-barcode');
  const typeButtons = Array.from(document.querySelectorAll('.ac-pos-filter-tabs__btn'));
  const saleModeInputs = Array.from(document.querySelectorAll('input[name="sale_mode"]'));
  const methodInputs = Array.from(document.querySelectorAll('input[name="payment_method"]'));
  const dueDateWrap = document.getElementById('pos-due-date-wrap');
  const paymentMethodsWrap = document.getElementById('pos-payment-methods');
  const grossTotalEl = document.getElementById('pos-gross-total');
  const discountEl = document.getElementById('pos-discount');
  const discountInput = document.getElementById('pos-discount-input');
  const discountButtons = Array.from(document.querySelectorAll('.ac-pos-discount-btn'));
  const taxEnabled = page.dataset.taxEnabled === '1';
  const defaultTaxRate = parseFloat(page.dataset.defaultTaxRate || '0');
  const initialItemsNode = document.getElementById('pos-initial-items');

  const productIndex = new Map();
  const cart = new Map();
  let activeType = '';

  productCards.forEach((card) => {
    const item = {
      id: Number(card.dataset.id),
      name: card.dataset.name,
      code: card.dataset.code,
      barcode: card.dataset.barcode,
      type: card.dataset.type,
      price: parseFloat(card.dataset.price || '0'),
      stock: parseFloat(card.dataset.stock || '0'),
      unit: card.dataset.unit || '',
    };

    productIndex.set(item.id, item);

    card.addEventListener('click', () => {
      if (card.disabled) return;
      addToCart(item);
    });
  });

  function addToCart(item) {
    const existing = cart.get(item.id);

    if (existing) {
      existing.quantity = roundQty(existing.quantity + 1);
    } else {
      cart.set(item.id, {
        ...item,
        quantity: 1,
        unit_price: item.price,
      });
    }

    renderCart();
  }

  function roundQty(value) {
    return Math.round(value * 1000) / 1000;
  }

  function formatMoney(value) {
    return Number(value || 0).toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function selectedSaleMode() {
    return saleModeInputs.find((input) => input.checked)?.value || 'paid';
  }

  function updateModeVisibility() {
    const isPending = selectedSaleMode() === 'pending';
    dueDateWrap.classList.toggle('is-hidden', !isPending);
    paymentMethodsWrap.classList.toggle('is-disabled', isPending);

    document.querySelectorAll('.ac-pos-choice').forEach((choice) => {
      const input = choice.querySelector('input');
      choice.classList.toggle('is-selected', input.checked);
    });
  }

  function updateMethodSelection() {
    document.querySelectorAll('.ac-pos-method').forEach((choice) => {
      const input = choice.querySelector('input');
      choice.classList.toggle('is-selected', input.checked);
    });
  }

  function renderCart() {
    const items = Array.from(cart.values());

    if (items.length === 0) {
      cartRows.innerHTML = '<div class="ac-pos-cart__empty">أضف عناصر من القائمة لبدء عملية البيع.</div>';
    } else {
      cartRows.innerHTML = items.map((item) => {
        const isProduct = item.type === 'product';
        const stockText = isProduct ? `<span class="ac-pos-cart__stock">المتاح: ${stripTrailingZeros(item.stock)}</span>` : '';
        return `
          <div class="ac-pos-cart-item" data-id="${item.id}">
            <div class="ac-pos-cart-item__head">
              <div>
                <div class="ac-pos-cart-item__name">${escapeHtml(item.name)}</div>
                <div class="ac-pos-cart-item__meta">
                  <span>${escapeHtml(item.code || 'بدون رمز')}</span>
                  ${stockText}
                </div>
              </div>
              <button type="button" class="ac-pos-cart-item__remove" data-remove="${item.id}">حذف</button>
            </div>

            <div class="ac-pos-cart-item__controls">
              <label>
                <span>الكمية</span>
                <input type="number"
                       min="0.001"
                       step="0.001"
                       value="${item.quantity}"
                       data-qty="${item.id}">
              </label>

              <label>
                <span>السعر</span>
                <input type="number"
                       min="0"
                       step="0.01"
                       value="${item.unit_price}"
                       data-price="${item.id}">
              </label>

              <div class="ac-pos-cart-item__total">
                <span>الإجمالي</span>
                <strong>${formatMoney(item.quantity * item.unit_price)}</strong>
              </div>
            </div>
          </div>
        `;
      }).join('');
    }

    bindCartEvents();
    syncHiddenInputs();
    updateSummary();
  }

  function bindCartEvents() {
    cartRows.querySelectorAll('[data-remove]').forEach((button) => {
      button.addEventListener('click', () => {
        cart.delete(Number(button.dataset.remove));
        renderCart();
      });
    });

    cartRows.querySelectorAll('[data-qty]').forEach((input) => {
      input.addEventListener('input', () => {
        const id = Number(input.dataset.qty);
        const item = cart.get(id);
        if (!item) return;

        const quantity = Math.max(0.001, roundQty(parseFloat(input.value || '0')));
        item.quantity = quantity;
        renderCart();
      });
    });

    cartRows.querySelectorAll('[data-price]').forEach((input) => {
      input.addEventListener('input', () => {
        const id = Number(input.dataset.price);
        const item = cart.get(id);
        if (!item) return;

        item.unit_price = Math.max(0, Math.round((parseFloat(input.value || '0')) * 100) / 100);
        renderCart();
      });
    });
  }

  function stripTrailingZeros(value) {
    return String(Number(value || 0));
  }

  function updateSummary() {
    const items = Array.from(cart.values());
    const gross = items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
    const discountValue = Math.max(0, Math.min(gross, parseFloat(discountInput?.value || '0')));
    if (discountInput) discountInput.value = discountValue.toFixed(2);
    const subtotal = Math.max(0, gross - discountValue);
    const tax = taxEnabled ? (subtotal * defaultTaxRate / 100) : 0;
    const total = subtotal + tax;

    if (grossTotalEl) grossTotalEl.textContent = formatMoney(gross);
    if (discountEl) discountEl.textContent = formatMoney(discountValue);
    subtotalEl.textContent = formatMoney(subtotal);
    taxEl.textContent = formatMoney(tax);
    totalEl.textContent = formatMoney(total);
    cartCount.textContent = `${items.length} عنصر`;
    submitBtn.disabled = items.length === 0;
  }

  function syncHiddenInputs() {
    inputsWrap.innerHTML = '';

    Array.from(cart.values()).forEach((item, index) => {
      inputsWrap.insertAdjacentHTML('beforeend', `
        <input type="hidden" name="items[${index}][product_id]" value="${item.id}">
        <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
        <input type="hidden" name="items[${index}][unit_price]" value="${item.unit_price}">
      `);
    });
  }

  function filterProducts() {
    const term = (searchInput.value || '').trim().toLowerCase();

    productCards.forEach((card) => {
      const matchesType = !activeType || card.dataset.type === activeType;
      const haystack = `${card.dataset.name} ${card.dataset.code} ${card.dataset.barcode || ''}`.toLowerCase();
      const matchesSearch = !term || haystack.includes(term);
      card.classList.toggle('is-hidden', !(matchesType && matchesSearch));
    });
  }

  function findByBarcode(term) {
    const cleaned = String(term || '').trim();
    if (!cleaned) return null;

    return Array.from(productIndex.values()).find((item) => {
      const barcode = String(item.barcode || '').trim();
      const code = String(item.code || '').trim();
      return barcode === cleaned || code === cleaned;
    }) || null;
  }

  function hydrateInitialItems() {
    if (!initialItemsNode) return;

    let initialItems = [];
    try {
      initialItems = JSON.parse(initialItemsNode.textContent || '[]');
    } catch (error) {
      initialItems = [];
    }

    initialItems.forEach((item) => {
      const product = productIndex.get(Number(item.product_id));
      if (!product) return;

      cart.set(product.id, {
        ...product,
        quantity: roundQty(parseFloat(item.quantity || '1')),
        unit_price: Math.max(0, Math.round((parseFloat(item.unit_price || product.price)) * 100) / 100),
      });
    });
  }

  function escapeHtml(value) {
    return String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  searchInput.addEventListener('input', filterProducts);

  if (barcodeInput) {
    barcodeInput.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') return;
      event.preventDefault();

      const product = findByBarcode(barcodeInput.value);
      if (product) {
        addToCart(product);
        barcodeInput.value = '';
        barcodeInput.focus();
      } else {
        barcodeInput.select();
      }
    });
  }

  if (discountInput) {
    discountInput.addEventListener('input', updateSummary);
  }

  discountButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const pct = parseFloat(button.dataset.discountPct || '0');
      const gross = Array.from(cart.values()).reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
      if (discountInput) {
        discountInput.value = pct > 0 ? ((gross * pct) / 100).toFixed(2) : '0.00';
      }
      updateSummary();
    });
  });

  typeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      activeType = button.dataset.type || '';
      typeButtons.forEach((item) => item.classList.toggle('is-active', item === button));
      filterProducts();
    });
  });

  saleModeInputs.forEach((input) => {
    input.addEventListener('change', updateModeVisibility);
  });

  methodInputs.forEach((input) => {
    input.addEventListener('change', updateMethodSelection);
  });

  hydrateInitialItems();
  const subtotalRow = subtotalEl?.closest('.ac-pos-summary__row');
  if (subtotalRow) {
    const spans = subtotalRow.querySelectorAll('span');
    if (spans.length > 1) {
      spans.forEach((span, index) => {
        if (index === spans.length - 1) {
          span.textContent = 'الصافي قبل الضريبة';
        } else {
          span.remove();
        }
      });
    }
  }
  updateModeVisibility();
  updateMethodSelection();
  renderCart();
  filterProducts();
});
