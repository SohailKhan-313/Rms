/**
 * RMS Modern POS Terminal Script
 * Comprehensive state management, calculations, sound effects, hold orders, checkout & thermal printing
 */

(function () {
  'use strict';

  // --- AUDIO SYNTHESIS HELPER (Web Audio API) ---
  let audioCtx = null;
  function getAudioContext() {
    if (!audioCtx && (window.AudioContext || window.webkitAudioContext)) {
      audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
    return audioCtx;
  }

  function playSound(type) {
    if (!posState.soundEnabled) return;
    try {
      const ctx = getAudioContext();
      if (!ctx) return;
      if (ctx.state === 'suspended') ctx.resume();

      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);

      const now = ctx.currentTime;
      if (type === 'add') {
        osc.frequency.setValueAtTime(880, now);
        osc.frequency.exponentialRampToValueAtTime(1320, now + 0.08);
        gain.gain.setValueAtTime(0.12, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
        osc.start(now);
        osc.stop(now + 0.08);
      } else if (type === 'remove') {
        osc.frequency.setValueAtTime(440, now);
        osc.frequency.exponentialRampToValueAtTime(220, now + 0.06);
        gain.gain.setValueAtTime(0.1, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.06);
        osc.start(now);
        osc.stop(now + 0.06);
      } else if (type === 'checkout') {
        // Two-tone pleasant victory chime
        osc.frequency.setValueAtTime(523.25, now); // C5
        osc.frequency.setValueAtTime(659.25, now + 0.1); // E5
        osc.frequency.setValueAtTime(783.99, now + 0.2); // G5
        gain.gain.setValueAtTime(0.15, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc.start(now);
        osc.stop(now + 0.35);
      }
    } catch (e) {
      // Audio not permitted or supported
    }
  }

  // --- STATE ---
  window.posState = {
    cart: [],
    orderType: 'Dine-In',
    floorName: 'Ground Floor',
    tableNo: 'Table 01',
    customerName: 'Walk-in Customer',
    customerPhone: '',
    discountPercent: 0,
    discountFixed: 0,
    discountMode: 'percent', // 'percent' or 'fixed'
    heldOrders: [],
    currentHeldId: null,
    soundEnabled: true,
    lastCompletedOrder: null,
    printerAttached: true,
    printerRule: 'preview_if_attached' // 'preview_if_attached' (preview if attached, direct print if not) or 'direct_if_attached'
  };

  // Load held orders, sound preference & printer settings from LocalStorage
  try {
    const savedHeld = localStorage.getItem('rms_held_orders');
    if (savedHeld) posState.heldOrders = JSON.parse(savedHeld);

    const savedSound = localStorage.getItem('rms_pos_sound');
    if (savedSound !== null) posState.soundEnabled = savedSound === 'true';

    const savedPrinter = localStorage.getItem('rms_pos_printer_attached');
    if (savedPrinter !== null) posState.printerAttached = (savedPrinter === 'true');

    const savedPrinterRule = localStorage.getItem('rms_pos_printer_rule');
    if (savedPrinterRule) posState.printerRule = savedPrinterRule;

    const savedActiveCart = localStorage.getItem('rms_active_cart');
    if (savedActiveCart) {
      const parsed = JSON.parse(savedActiveCart);
      if (Array.isArray(parsed) && parsed.length > 0) posState.cart = parsed;
    }
  } catch (e) {}

  // --- DOM ELEMENTS ---
  let dom = {};

  document.addEventListener('DOMContentLoaded', function () {
    dom = {
      productList: document.querySelectorAll('.pos-product-card'),
      categoryPills: document.querySelectorAll('.pos-category-pill'),
      searchInput: document.getElementById('posSearchInput'),
      clearSearchBtn: document.getElementById('posClearSearch'),
      cartItemsList: document.getElementById('posCartItemsList'),
      mobileCartItemsList: document.getElementById('posMobileCartItemsList'),
      emptyCartMsg: document.getElementById('posEmptyCartMsg'),
      orderTypeBtns: document.querySelectorAll('.pos-type-btn'),
      dineInLocationWrap: document.getElementById('posDineInLocationWrapper'),
      floorSelectWrap: document.getElementById('posFloorSelectWrapper'),
      floorSelect: document.getElementById('posFloorSelect'),
      tableSelectorWrap: document.getElementById('posTableSelectWrapper'),
      tableSelect: document.getElementById('posTableSelect'),
      customerSelect: document.getElementById('posCustomerSelect'),
      customerInput: document.getElementById('posCustomerNameInput'),
      // Calculation outputs
      subtotalEl: document.querySelectorAll('.pos-calc-subtotal'),
      taxEl: document.querySelectorAll('.pos-calc-tax'),
      discountEl: document.querySelectorAll('.pos-calc-discount'),
      grandTotalEl: document.querySelectorAll('.pos-calc-grand-total'),
      // Buttons
      holdOrderBtn: document.getElementById('posHoldOrderBtn'),
      heldOrdersListBtn: document.getElementById('posHeldOrdersListBtn'),
      heldCountBadge: document.getElementById('posHeldBadge'),
      clearCartBtn: document.getElementById('posClearCartBtn'),
      checkoutBtn: document.querySelectorAll('.btn-pos-checkout'),
      kotBtn: document.getElementById('posKotBtn'),
      // Mobile elements
      mobileCartBar: document.getElementById('posMobileCartBar'),
      mobileCartCount: document.getElementById('posMobileCartCount'),
      mobileCartTotal: document.getElementById('posMobileCartTotal'),
      // Modals
      paymentModalEl: document.getElementById('posPaymentModal'),
      receiptModalEl: document.getElementById('posReceiptModal'),
      heldOrdersModalEl: document.getElementById('posHeldOrdersModal'),
      kotModalEl: document.getElementById('posKotModal'),
      recentOrdersModalEl: document.getElementById('posRecentOrdersModal'),
      recentOrdersList: document.getElementById('posRecentOrdersList'),
      recentOrdersBtn: document.getElementById('posRecentOrdersBtn'),
      // Printer controls
      printerStatusBtn: document.getElementById('posPrinterStatusBtn'),
      printerToggle: document.getElementById('posPrinterAttachedToggle'),
      printerStatusText: document.getElementById('posPrinterStatusText'),
      printerIcon: document.getElementById('posPrinterIcon'),
      printerDot: document.getElementById('posPrinterDot'),
      printerModeBadge: document.getElementById('posPrinterModeBadge'),
      printerStatePill: document.getElementById('posPrinterStatePill'),
      printerExplainer: document.getElementById('posPrinterExplainer'),
      testPrintSlipBtn: document.getElementById('posTestPrintSlipBtn'),
      ruleRadios: document.querySelectorAll('input[name="printerRule"]'),
      directPrintFrame: document.getElementById('posDirectPrintFrame')
    };

    initEventListeners();
    setupFloorAndTableSync();
    renderCart();
    updateHeldCount();
    setupLiveClock();
    initPrinterControls();
    initRecentOrdersModal();
  });

  // --- FLOOR & TABLE DYNAMIC SYNC ---
  function updateTablesForFloor(selectedFloorName, preselectTable) {
    if (!dom.tableSelect) return;
    const allTables = (window.posInitialData && window.posInitialData.tables) || [];
    dom.tableSelect.innerHTML = '';

    const filtered = allTables.filter(t => !selectedFloorName || t.floor_name === selectedFloorName);
    if (filtered.length === 0) {
      dom.tableSelect.innerHTML = '<option value="">No tables on this floor</option>';
      posState.tableNo = '';
      return;
    }

    filtered.forEach((t, idx) => {
      const opt = document.createElement('option');
      opt.value = t.table_number;
      opt.textContent = `${t.table_number} (${t.capacity}p) - ${t.status}`;
      opt.dataset.floorName = t.floor_name;
      opt.dataset.status = t.status;
      if (preselectTable && t.table_number === preselectTable) {
        opt.selected = true;
      } else if (!preselectTable && idx === 0) {
        opt.selected = true;
      }
      dom.tableSelect.appendChild(opt);
    });

    posState.tableNo = dom.tableSelect.value;
  }

  function setupFloorAndTableSync() {
    if (dom.floorSelect) {
      if (window.posInitialData && window.posInitialData.initialFloor) {
        dom.floorSelect.value = window.posInitialData.initialFloor;
      }
      posState.floorName = dom.floorSelect.value;
    }
    if (dom.tableSelect) {
      const initTable = (window.posInitialData && window.posInitialData.initialTable) || '';
      updateTablesForFloor(posState.floorName, initTable);
      if (initTable) {
        dom.tableSelect.value = initTable;
      }
      posState.tableNo = dom.tableSelect.value;
    }
  }

  // --- LIVE CLOCK ---
  function setupLiveClock() {
    const clockEl = document.getElementById('posLiveClock');
    if (!clockEl) return;
    function update() {
      const now = new Date();
      clockEl.textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    update();
    setInterval(update, 1000);
  }

  // --- EVENT LISTENERS ---
  function initEventListeners() {
    // 1. Product card click to add
    if (dom.productList) {
      dom.productList.forEach(card => {
        card.addEventListener('click', function () {
          const item = {
            id: this.dataset.id || ('ITEM-' + Math.random().toString(36).substr(2, 5)),
            code: this.dataset.code || '',
            name: this.dataset.name || 'Menu Item',
            price: parseFloat(this.dataset.price) || 0,
            gst: parseFloat(this.dataset.gst) || 0,
            image: this.dataset.image || ''
          };
          addToCart(item);
        });
      });
    }

    // 2. Category filtering
    if (dom.categoryPills) {
      dom.categoryPills.forEach(pill => {
        pill.addEventListener('click', function () {
          dom.categoryPills.forEach(p => p.classList.remove('active'));
          this.classList.add('active');
          const category = this.dataset.category || 'all';
          filterCatalog(category, dom.searchInput ? dom.searchInput.value : '');
        });
      });
    }

    // 3. Search input & barcode listener
    if (dom.searchInput) {
      dom.searchInput.addEventListener('input', function () {
        const query = this.value.trim();
        if (dom.clearSearchBtn) dom.clearSearchBtn.style.display = query ? 'block' : 'none';
        const activePill = document.querySelector('.pos-category-pill.active');
        const activeCategory = activePill ? activePill.dataset.category : 'all';
        filterCatalog(activeCategory, query);
      });

      dom.searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          const query = this.value.trim().toLowerCase();
          // Check if matches an exact item code or single match
          let matched = null;
          let visibleCount = 0;
          dom.productList.forEach(card => {
            if (card.style.display !== 'none') {
              visibleCount++;
              if (card.dataset.code && card.dataset.code.toLowerCase() === query) {
                matched = card;
              }
            }
          });

          if (matched || visibleCount === 1) {
            const cardToClick = matched || document.querySelector('.pos-product-card:not([style*="display: none"])');
            if (cardToClick) {
              cardToClick.click();
              this.value = '';
              if (dom.clearSearchBtn) dom.clearSearchBtn.style.display = 'none';
              filterCatalog('all', '');
            }
          }
        }
      });
    }

    if (dom.clearSearchBtn) {
      dom.clearSearchBtn.addEventListener('click', function () {
        if (dom.searchInput) {
          dom.searchInput.value = '';
          dom.searchInput.focus();
        }
        this.style.display = 'none';
        const activePill = document.querySelector('.pos-category-pill.active');
        filterCatalog(activePill ? activePill.dataset.category : 'all', '');
      });
    }

    // 4. Order type pills (Dine-in, Takeaway, Delivery)
    if (dom.orderTypeBtns) {
      dom.orderTypeBtns.forEach(btn => {
        btn.addEventListener('click', function () {
          dom.orderTypeBtns.forEach(b => b.classList.remove('active'));
          this.classList.add('active');
          posState.orderType = this.dataset.type || 'Dine-In';

          if (dom.dineInLocationWrap) {
            dom.dineInLocationWrap.style.display = posState.orderType === 'Dine-In' ? 'flex' : 'none';
          }
          if (window.rmsToast) window.rmsToast(`Switched to ${posState.orderType}`, 'info');
        });
      });
    }

    // 5. Floor & Table Selection
    if (dom.floorSelect) {
      dom.floorSelect.addEventListener('change', function () {
        posState.floorName = this.value;
        updateTablesForFloor(this.value);
        if (window.rmsToast) window.rmsToast(`Selected floor: ${this.value}`, 'info');
      });
    }

    if (dom.tableSelect) {
      dom.tableSelect.addEventListener('change', function () {
        posState.tableNo = this.value;
      });
    }

    // 6. Customer selection
    if (dom.customerSelect) {
      dom.customerSelect.addEventListener('change', function () {
        if (this.value === 'new') {
          const modal = new bootstrap.Modal(document.getElementById('posAddCustomerModal'));
          modal.show();
        } else {
          const selOpt = this.options[this.selectedIndex];
          posState.customerName = selOpt.dataset.name || selOpt.text || 'Walk-in Customer';
          posState.customerPhone = selOpt.dataset.phone || '';
          const disc = parseFloat(selOpt.dataset.discount) || 0;
          if (disc > 0) {
            posState.discountPercent = disc;
            if (dom.discountPresetBtns) {
              dom.discountPresetBtns.forEach(btn => {
                btn.classList.toggle('active', parseFloat(btn.dataset.percent) === disc);
              });
            }
            if (window.rmsToast) window.rmsToast(`Applied ${disc}% VIP discount for ${posState.customerName}`, 'info');
          } else if (this.value === 'walk-in') {
            posState.discountPercent = 0;
            if (dom.discountPresetBtns) {
              dom.discountPresetBtns.forEach(btn => {
                btn.classList.toggle('active', parseFloat(btn.dataset.percent) === 0);
              });
            }
          }
          renderCart();
        }
      });
    }

    // 7. Hold Order action
    if (dom.holdOrderBtn) {
      dom.holdOrderBtn.addEventListener('click', holdCurrentOrder);
    }
    if (dom.heldOrdersListBtn) {
      dom.heldOrdersListBtn.addEventListener('click', openHeldOrdersModal);
    }

    // 8. Clear Cart
    if (dom.clearCartBtn) {
      dom.clearCartBtn.addEventListener('click', function () {
        if (posState.cart.length === 0) return;
        if (confirm('Are you sure you want to clear the current order?')) {
          posState.cart = [];
          posState.currentHeldId = null;
          saveCartLocally();
          renderCart();
          playSound('remove');
          if (window.rmsToast) window.rmsToast('Order cleared', 'warning');
        }
      });
    }

    // 9. Checkout & Pay Now Buttons
    if (dom.checkoutBtn) {
      dom.checkoutBtn.forEach(btn => {
        btn.addEventListener('click', openPaymentModal);
      });
    }

    // 10. KOT Kitchen Ticket direct print
    if (dom.kotBtn) {
      dom.kotBtn.addEventListener('click', directPrintKot);
    }

    // 11. Discount quick preset buttons
    document.querySelectorAll('.btn-discount-preset').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.btn-discount-preset').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const pct = parseFloat(this.dataset.percent) || 0;
        posState.discountMode = 'percent';
        posState.discountPercent = pct;
        renderCart();
      });
    });

    // 12. Sound toggle
    const soundToggleBtn = document.getElementById('posSoundToggle');
    if (soundToggleBtn) {
      soundToggleBtn.addEventListener('click', function () {
        posState.soundEnabled = !posState.soundEnabled;
        localStorage.setItem('rms_pos_sound', posState.soundEnabled);
        this.innerHTML = posState.soundEnabled
          ? '<i class="bi bi-volume-up-fill text-primary"></i>'
          : '<i class="bi bi-volume-mute-fill text-muted"></i>';
        if (window.rmsToast) window.rmsToast(posState.soundEnabled ? 'Sound enabled' : 'Sound muted', 'info');
      });
    }

    // 13. Fullscreen toggle
    const fullscreenBtn = document.getElementById('posFullscreenToggle');
    if (fullscreenBtn) {
      fullscreenBtn.addEventListener('click', function () {
        if (!document.fullscreenElement) {
          document.documentElement.requestFullscreen().catch(() => {});
          this.innerHTML = '<i class="bi bi-fullscreen-exit"></i>';
        } else {
          document.exitFullscreen().catch(() => {});
          this.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
        }
      });
    }

    // 14. Global Keyboard Shortcuts
    document.addEventListener('keydown', function (e) {
      // F2 -> focus search
      if (e.key === 'F2') {
        e.preventDefault();
        if (dom.searchInput) dom.searchInput.focus();
      }
      // F4 -> open checkout
      if (e.key === 'F4') {
        e.preventDefault();
        if (posState.cart.length > 0) openPaymentModal();
      }
      // F8 -> hold order
      if (e.key === 'F8') {
        e.preventDefault();
        holdCurrentOrder();
      }
      // F9 -> direct print KOT
      if (e.key === 'F9') {
        e.preventDefault();
        directPrintKot();
      }
    });

    // 15. Payment Modal Tender & Method Selection
    initPaymentModalEvents();
  }

  // --- CATALOG FILTERING ---
  function filterCatalog(category, searchQuery) {
    if (!dom.productList) return;
    const query = searchQuery ? searchQuery.toLowerCase().trim() : '';

    let matchCount = 0;
    dom.productList.forEach(card => {
      const cardCat = (card.dataset.category || '').toLowerCase();
      const cardName = (card.dataset.name || '').toLowerCase();
      const cardCode = (card.dataset.code || '').toLowerCase();

      const catMatches = (category === 'all' || cardCat === category.toLowerCase());
      const queryMatches = (!query || cardName.includes(query) || cardCode.includes(query));

      if (catMatches && queryMatches) {
        card.style.display = 'flex';
        matchCount++;
      } else {
        card.style.display = 'none';
      }
    });

    const emptyCatalog = document.getElementById('posEmptyCatalog');
    if (emptyCatalog) {
      emptyCatalog.style.display = matchCount === 0 ? 'block' : 'none';
    }
  }

  // --- CART OPERATIONS ---
  function addToCart(product) {
    const existingIndex = posState.cart.findIndex(item => item.id == product.id || (product.code && item.code == product.code));
    if (existingIndex > -1) {
      posState.cart[existingIndex].qty += 1;
      posState.cart[existingIndex].total = posState.cart[existingIndex].qty * posState.cart[existingIndex].price;
    } else {
      posState.cart.push({
        id: product.id,
        code: product.code,
        name: product.name,
        price: product.price,
        gst: product.gst,
        qty: 1,
        total: product.price,
        notes: '',
        image: product.image
      });
    }

    saveCartLocally();
    renderCart();
    playSound('add');

    // Visual feedback micro-animation on clicked card
    const activeCard = Array.from(dom.productList).find(c => c.dataset.id == product.id || c.dataset.code == product.code);
    if (activeCard) {
      activeCard.classList.add('pulse-active');
      setTimeout(() => activeCard.classList.remove('pulse-active'), 300);
    }
  }

  function updateQty(index, delta) {
    if (!posState.cart[index]) return;
    posState.cart[index].qty += delta;

    if (posState.cart[index].qty <= 0) {
      posState.cart.splice(index, 1);
      playSound('remove');
    } else {
      posState.cart[index].total = posState.cart[index].qty * posState.cart[index].price;
      playSound('add');
    }

    saveCartLocally();
    renderCart();
  }

  function setExactQty(index, val) {
    const qty = parseInt(val) || 0;
    if (qty <= 0) {
      posState.cart.splice(index, 1);
      playSound('remove');
    } else {
      posState.cart[index].qty = qty;
      posState.cart[index].total = posState.cart[index].qty * posState.cart[index].price;
    }
    saveCartLocally();
    renderCart();
  }

  function removeItem(index) {
    if (!posState.cart[index]) return;
    const itemName = posState.cart[index].name || 'Item';
    posState.cart.splice(index, 1);
    saveCartLocally();
    renderCart();
    playSound('remove');
    if (window.rmsToast) window.rmsToast(`"${itemName}" removed from order.`, 'info');
  }

  function editItemNotes(index) {
    const item = posState.cart[index];
    if (!item) return;
    const currentNotes = item.notes || '';
    const newNotes = prompt(`Special instructions for ${item.name}:`, currentNotes);
    if (newNotes !== null) {
      item.notes = newNotes.trim();
      saveCartLocally();
      renderCart();
    }
  }

  function saveCartLocally() {
    try {
      localStorage.setItem('rms_active_cart', JSON.stringify(posState.cart));
    } catch (e) {}
  }

  // --- CALCULATIONS & RENDERING ---
  function calculateTotals() {
    let subtotal = 0;
    let totalTax = 0;

    posState.cart.forEach(item => {
      const lineTotal = item.price * item.qty;
      subtotal += lineTotal;
      if (item.gst > 0) {
        totalTax += (lineTotal * item.gst / 100);
      }
    });

    let discountAmount = 0;
    if (posState.discountMode === 'percent') {
      discountAmount = (subtotal * posState.discountPercent / 100);
    } else {
      discountAmount = posState.discountFixed;
    }
    if (discountAmount > subtotal) discountAmount = subtotal;

    const grandTotal = Math.max(0, subtotal + totalTax - discountAmount);

    return {
      subtotal,
      totalTax,
      discountAmount,
      grandTotal,
      totalItems: posState.cart.reduce((sum, item) => sum + item.qty, 0)
    };
  }

  function renderCart() {
    const totals = calculateTotals();
    const hasItems = posState.cart.length > 0;

    // Render Cart HTML helper
    const buildItemsHtml = (isMobile = false) => {
      return posState.cart.map((item, idx) => `
        <div class="pos-cart-item">
          <div class="pos-cart-item-header">
            <div>
              <div class="pos-cart-item-title">${escapeHtml(item.name)}</div>
              <div class="pos-cart-item-unitprice">
                ${item.code ? `<span class="badge bg-light text-dark me-1">${escapeHtml(item.code)}</span>` : ''}
                Rs. ${item.price.toFixed(2)} each ${item.gst > 0 ? `<small class="text-muted">(+${item.gst}% tax)</small>` : ''}
              </div>
              ${item.notes ? `<div class="pos-item-note-badge"><i class="bi bi-chat-left-dots me-1"></i>${escapeHtml(item.notes)}</div>` : ''}
            </div>
            <button class="pos-cart-item-remove" data-action="remove" data-index="${idx}" title="Remove item">
              <i class="bi bi-x-circle-fill"></i>
            </button>
          </div>
          <div class="pos-cart-item-controls">
            <div class="d-flex align-items-center gap-2">
              <div class="pos-qty-stepper">
                <button type="button" class="pos-qty-btn" data-action="decrease" data-index="${idx}">-</button>
                <input type="number" class="pos-qty-input" data-action="qty-input" data-index="${idx}" value="${item.qty}" min="1">
                <button type="button" class="pos-qty-btn" data-action="increase" data-index="${idx}">+</button>
              </div>
              <button class="btn btn-sm btn-light border py-0 px-2 text-muted" data-action="note" data-index="${idx}" title="Add special instructions">
                <i class="bi bi-pencil-square"></i>
              </button>
            </div>
            <div class="pos-cart-item-total">Rs. ${(item.price * item.qty).toFixed(2)}</div>
          </div>
        </div>
      `).join('');
    };

    // Desktop Cart list
    if (dom.cartItemsList) {
      dom.cartItemsList.innerHTML = hasItems ? buildItemsHtml(false) : '';
    }
    // Mobile Drawer Cart list
    if (dom.mobileCartItemsList) {
      dom.mobileCartItemsList.innerHTML = hasItems ? buildItemsHtml(true) : '<div class="text-center py-5 text-muted"><i class="bi bi-cart-x fs-1"></i><p class="mt-2">Cart is empty</p></div>';
    }

    // Attach event delegation for stepper buttons
    const bindCartActions = (container) => {
      if (!container) return;
      container.onclick = function (e) {
        const target = e.target.closest('[data-action]');
        if (!target) return;
        const action = target.dataset.action;
        const index = parseInt(target.dataset.index);

        if (action === 'increase') updateQty(index, 1);
        if (action === 'decrease') updateQty(index, -1);
        if (action === 'remove') removeItem(index);
        if (action === 'note') editItemNotes(index);
      };
      container.onchange = function (e) {
        if (e.target.dataset.action === 'qty-input') {
          const index = parseInt(e.target.dataset.index);
          setExactQty(index, e.target.value);
        }
      };
    };

    bindCartActions(dom.cartItemsList);
    bindCartActions(dom.mobileCartItemsList);

    // Empty state display
    if (dom.emptyCartMsg) {
      dom.emptyCartMsg.style.display = hasItems ? 'none' : 'flex';
    }

    // Update calculation text
    dom.subtotalEl.forEach(el => el.textContent = `Rs. ${totals.subtotal.toFixed(2)}`);
    dom.taxEl.forEach(el => el.textContent = `Rs. ${totals.totalTax.toFixed(2)}`);
    dom.discountEl.forEach(el => el.textContent = totals.discountAmount > 0 ? `-Rs. ${totals.discountAmount.toFixed(2)}` : 'Rs. 0.00');
    dom.grandTotalEl.forEach(el => el.textContent = `Rs. ${totals.grandTotal.toFixed(2)}`);

    // Enable/disable checkout
    dom.checkoutBtn.forEach(btn => {
      btn.disabled = !hasItems;
      const amountSpan = btn.querySelector('.checkout-btn-amount');
      if (amountSpan) amountSpan.textContent = `Rs. ${totals.grandTotal.toFixed(2)}`;
    });

    // Mobile floating bar updates
    if (dom.mobileCartCount) dom.mobileCartCount.textContent = `${totals.totalItems} ${totals.totalItems === 1 ? 'item' : 'items'}`;
    if (dom.mobileCartTotal) dom.mobileCartTotal.textContent = `Rs. ${totals.grandTotal.toFixed(2)}`;
    if (dom.mobileCartBar) {
      dom.mobileCartBar.style.display = hasItems ? 'flex' : 'none';
    }
  }

  // --- HOLD / PARK ORDERS ---
  function holdCurrentOrder() {
    if (posState.cart.length === 0) {
      if (window.rmsToast) window.rmsToast('Cannot hold an empty order!', 'warning');
      return;
    }

    const totals = calculateTotals();
    const heldItem = {
      id: 'HELD-' + Date.now(),
      orderType: posState.orderType,
      floorName: posState.floorName,
      tableNo: posState.tableNo,
      customerName: posState.customerName,
      customerPhone: posState.customerPhone,
      items: JSON.parse(JSON.stringify(posState.cart)),
      totals: totals,
      heldAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    };

    posState.heldOrders.push(heldItem);
    try {
      localStorage.setItem('rms_held_orders', JSON.stringify(posState.heldOrders));
    } catch (e) {}

    posState.cart = [];
    posState.currentHeldId = null;
    saveCartLocally();
    renderCart();
    updateHeldCount();
    playSound('remove');

    if (window.rmsToast) window.rmsToast(`Order held (${posState.heldOrders.length} parked)`, 'success');
  }

  function updateHeldCount() {
    const count = posState.heldOrders.length;
    if (dom.heldCountBadge) {
      dom.heldCountBadge.textContent = count;
      dom.heldCountBadge.style.display = count > 0 ? 'inline-block' : 'none';
    }
  }

  function openHeldOrdersModal() {
    const listContainer = document.getElementById('posHeldOrdersList');
    if (!listContainer) return;

    if (posState.heldOrders.length === 0) {
      listContainer.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1"></i><p class="mt-2">No held orders right now.</p></div>';
    } else {
      listContainer.innerHTML = posState.heldOrders.map((order, idx) => `
        <div class="card mb-3 border shadow-sm">
          <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <div class="fw-bold fs-6">
                <span class="badge bg-primary me-2">${escapeHtml(order.orderType)}</span>
                ${order.orderType === 'Dine-In' ? `<span class="badge bg-secondary me-2">${escapeHtml((order.floorName ? order.floorName + ' • ' : '') + order.tableNo)}</span>` : ''}
                ${escapeHtml(order.customerName)}
              </div>
              <small class="text-muted">Parked at ${order.heldAt} • ${order.items.length} items</small>
              <div class="fw-bold text-success mt-1">Rs. ${order.totals.grandTotal.toFixed(2)}</div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-primary resume-held-btn" data-index="${idx}">
                <i class="bi bi-play-circle me-1"></i> Resume
              </button>
              <button class="btn btn-sm btn-outline-danger delete-held-btn" data-index="${idx}">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      `).join('');

      // Bind resume / delete handlers
      listContainer.querySelectorAll('.resume-held-btn').forEach(btn => {
        btn.onclick = function () {
          const idx = parseInt(this.dataset.index);
          resumeHeldOrder(idx);
        };
      });

      listContainer.querySelectorAll('.delete-held-btn').forEach(btn => {
        btn.onclick = function () {
          const idx = parseInt(this.dataset.index);
          deleteHeldOrder(idx);
        };
      });
    }

    const modal = new bootstrap.Modal(dom.heldOrdersModalEl);
    modal.show();
  }

  function resumeHeldOrder(index) {
    const order = posState.heldOrders[index];
    if (!order) return;

    if (posState.cart.length > 0) {
      if (!confirm('You already have an active order. Resume this parked order and replace current cart?')) return;
    }

    posState.cart = JSON.parse(JSON.stringify(order.items));
    posState.orderType = order.orderType;
    posState.floorName = order.floorName || 'Ground Floor';
    posState.tableNo = order.tableNo;
    posState.customerName = order.customerName;
    posState.customerPhone = order.customerPhone || '';
    posState.currentHeldId = order.id;

    if (dom.floorSelect) dom.floorSelect.value = posState.floorName;
    updateTablesForFloor(posState.floorName, posState.tableNo);

    // Remove from held orders
    posState.heldOrders.splice(index, 1);
    try {
      localStorage.setItem('rms_held_orders', JSON.stringify(posState.heldOrders));
    } catch (e) {}

    saveCartLocally();
    renderCart();
    updateHeldCount();

    // Close modal
    const modalInstance = bootstrap.Modal.getInstance(dom.heldOrdersModalEl);
    if (modalInstance) modalInstance.hide();

    if (window.rmsToast) window.rmsToast('Order resumed successfully!', 'success');
  }

  function deleteHeldOrder(index) {
    if (!confirm('Are you sure you want to discard this held order?')) return;
    posState.heldOrders.splice(index, 1);
    try {
      localStorage.setItem('rms_held_orders', JSON.stringify(posState.heldOrders));
    } catch (e) {}
    updateHeldCount();
    openHeldOrdersModal(); // re-render
    if (window.rmsToast) window.rmsToast('Held order deleted', 'info');
  }

  // --- PAYMENT MODAL & CHECKOUT ---
  let selectedPaymentMethod = 'Cash';

  function initPaymentModalEvents() {
    // Payment method switch buttons
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.payment-method-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        selectedPaymentMethod = this.dataset.method || 'Cash';

        const cashDetails = document.getElementById('cashPaymentDetails');
        if (cashDetails) {
          cashDetails.style.display = selectedPaymentMethod === 'Cash' ? 'block' : 'none';
        }
      });
    });

    // Cash tender input
    const cashInput = document.getElementById('posCashTendered');
    if (cashInput) {
      cashInput.addEventListener('input', calculateChange);
    }

    // Quick cash denomination buttons ($5, $10, $20, $50, $100, Exact)
    document.querySelectorAll('.btn-quick-cash').forEach(btn => {
      btn.addEventListener('click', function () {
        const val = this.dataset.amount;
        const totals = calculateTotals();
        if (cashInput) {
          if (val === 'exact') {
            cashInput.value = totals.grandTotal.toFixed(2);
          } else {
            cashInput.value = parseFloat(val).toFixed(2);
          }
          calculateChange();
        }
      });
    });

    // Confirm Payment & Place Order button
    const confirmPayBtn = document.getElementById('posConfirmPaymentBtn');
    if (confirmPayBtn) {
      confirmPayBtn.addEventListener('click', processCheckout);
    }
  }

  function openPaymentModal() {
    if (posState.cart.length === 0) return;
    const totals = calculateTotals();

    // Close view order modal or offcanvas if open
    const viewOrderModalEl = document.getElementById('posViewOrderModal');
    if (viewOrderModalEl) {
      const viewModalInst = bootstrap.Modal.getInstance(viewOrderModalEl);
      if (viewModalInst) viewModalInst.hide();
    }
    const offcanvasEl = document.getElementById('posMobileCartOffcanvas');
    if (offcanvasEl) {
      const ocInst = bootstrap.Offcanvas.getInstance(offcanvasEl);
      if (ocInst) ocInst.hide();
    }

    const dueAmountEl = document.getElementById('posPayModalDueAmount');
    if (dueAmountEl) dueAmountEl.textContent = `Rs. ${totals.grandTotal.toFixed(2)}`;

    const cashInput = document.getElementById('posCashTendered');
    if (cashInput) {
      cashInput.value = totals.grandTotal.toFixed(2); // default to exact
      calculateChange();
    }

    const modal = new bootstrap.Modal(dom.paymentModalEl);
    modal.show();
  }

  function calculateChange() {
    const totals = calculateTotals();
    const cashInput = document.getElementById('posCashTendered');
    const changeDueEl = document.getElementById('posChangeDueAmount');
    const confirmBtn = document.getElementById('posConfirmPaymentBtn');

    const tendered = parseFloat(cashInput ? cashInput.value : 0) || 0;
    const change = tendered - totals.grandTotal;

    if (changeDueEl) {
      changeDueEl.textContent = change >= 0 ? `Rs. ${change.toFixed(2)}` : `Short by Rs. ${Math.abs(change).toFixed(2)}`;
      changeDueEl.className = change >= 0 ? 'change-due-box text-success' : 'change-due-box text-danger bg-danger-subtle';
    }

    if (confirmBtn) {
      confirmBtn.disabled = selectedPaymentMethod === 'Cash' && change < -0.01;
    }
  }

  function processCheckout() {
    const totals = calculateTotals();
    const cashInput = document.getElementById('posCashTendered');
    const tendered = selectedPaymentMethod === 'Cash' ? (parseFloat(cashInput ? cashInput.value : 0) || totals.grandTotal) : totals.grandTotal;
    const change = Math.max(0, tendered - totals.grandTotal);

    const orderPayload = {
      action: 'save_order',
      order_type: posState.orderType,
      floor_name: posState.floorName,
      table_no: posState.tableNo,
      customer_name: posState.customerName,
      customer_phone: posState.customerPhone,
      subtotal: totals.subtotal,
      tax: totals.totalTax,
      discount: totals.discountAmount,
      grand_total: totals.grandTotal,
      paid_amount: tendered,
      change_amount: change,
      payment_method: selectedPaymentMethod,
      items: posState.cart
    };

    const confirmBtn = document.getElementById('posConfirmPaymentBtn');
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    }

    fetch('/RMS/app/controller/order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(orderPayload)
    })
      .then(res => res.json())
      .then(data => {
        if (data && data.success) {
          playSound('checkout');

          // Close payment modal
          const modalInstance = bootstrap.Modal.getInstance(dom.paymentModalEl);
          if (modalInstance) modalInstance.hide();

          // Prepare order details for receipt
          posState.lastCompletedOrder = {
            id: data.order_id || 0,
            orderNumber: data.order_number || ('ORD-' + Date.now()),
            createdAt: new Date().toLocaleString(),
            orderType: posState.orderType,
            floorName: posState.floorName,
            tableNo: posState.tableNo,
            customerName: posState.customerName,
            paymentMethod: selectedPaymentMethod,
            items: JSON.parse(JSON.stringify(posState.cart)),
            totals: totals,
            paidAmount: tendered,
            changeAmount: change
          };

          // Clear active cart & storage
          posState.cart = [];
          posState.currentHeldId = null;
          saveCartLocally();
          renderCart();

          // Conditional receipt dispatch: preview if printer attached, otherwise directly go to print
          handleOrderReceiptDispatch(posState.lastCompletedOrder);

          if (window.rmsToast) window.rmsToast('Order placed successfully!', 'success');
        } else {
          if (window.rmsToast) {
            window.rmsToast('Failed to place order: ' + (data.message || 'Unknown error'), 'danger');
          } else {
            alert('Failed to place order: ' + (data.message || 'Unknown error'));
          }
        }
      })
      .catch(err => {
        // Fallback simulation if offline
        console.warn('Backend unavailable, simulating offline receipt:', err);
        playSound('checkout');
        const modalInstance = bootstrap.Modal.getInstance(dom.paymentModalEl);
        if (modalInstance) modalInstance.hide();

        posState.lastCompletedOrder = {
          orderNumber: 'ORD-' + Math.floor(100000 + Math.random() * 900000),
          createdAt: new Date().toLocaleString(),
          orderType: posState.orderType,
          floorName: posState.floorName,
          tableNo: posState.tableNo,
          customerName: posState.customerName,
          paymentMethod: selectedPaymentMethod,
          items: JSON.parse(JSON.stringify(posState.cart)),
          totals: totals,
          paidAmount: tendered,
          changeAmount: change
        };

        posState.cart = [];
        saveCartLocally();
        renderCart();
        handleOrderReceiptDispatch(posState.lastCompletedOrder);
      })
      .finally(() => {
        if (confirmBtn) {
          confirmBtn.disabled = false;
          confirmBtn.innerHTML = '<i class="bi bi-printer me-2"></i>Complete & Print Receipt';
        }
      });
  }

  // --- DIRECT PRINT DISPATCH (NO SEPARATE PREVIEWS) ---
  function handleOrderReceiptDispatch(order) {
    if (!order) return;
    directPrintReceipt(order, 'thermal');
    if (window.rmsToast) {
      window.rmsToast(`Direct print triggered for ${order.orderNumber || 'Order'}`, 'success');
    }
  }

  function directPrintReceipt(order, format = 'thermal') {
    if (!order) return;
    const orderId = order.id || 0;
    let url = `/RMS/views/order/print_slip.php?order_id=${orderId}&format=${format}&autoprint=1`;

    if (order.items && order.items.length > 0) {
      const itemsPayload = encodeURIComponent(JSON.stringify(order.items.map(i => ({
        name: i.name || i.item_name,
        item_name: i.name || i.item_name,
        quantity: i.qty || i.quantity || 1,
        qty: i.qty || i.quantity || 1,
        price: i.price || 0,
        notes: i.notes || ''
      }))));
      const cleanTable = formatCleanTableNumber(order.tableNo);
      url += `&order_number=${encodeURIComponent(order.orderNumber || '')}` +
             `&order_type=${encodeURIComponent(order.orderType || 'Dine-In')}` +
             `&floor=${encodeURIComponent(order.floorName || '')}` +
             `&table=${encodeURIComponent(cleanTable || '')}` +
             `&customer_name=${encodeURIComponent(order.customerName || 'Walk-in Customer')}` +
             `&payment_method=${encodeURIComponent(order.paymentMethod || 'Cash')}` +
             `&subtotal=${order.totals?.subtotal || 0}` +
             `&tax=${order.totals?.totalTax || 0}` +
             `&discount=${order.totals?.discountAmount || 0}` +
             `&grand_total=${order.totals?.grandTotal || 0}` +
             `&paid_amount=${order.paidAmount || 0}` +
             `&change_amount=${order.changeAmount || 0}` +
             `&items=${itemsPayload}`;
    }

    directPrintViaIframe(url);
  }

  function directPrintKot() {
    if (posState.cart.length === 0) {
      if (window.rmsToast) window.rmsToast('No items in order to send to kitchen!', 'warning');
      return;
    }

    const cleanTable = formatCleanTableNumber(posState.tableNo);
    const itemsPayload = encodeURIComponent(JSON.stringify(posState.cart.map(i => ({
      name: i.name,
      item_name: i.name,
      quantity: i.qty,
      notes: i.notes || ''
    }))));

    const kotUrl = `/RMS/views/order/print_slip.php?format=kot&autoprint=1&order_type=${encodeURIComponent(posState.orderType)}&floor=${encodeURIComponent(posState.floorName)}&table=${encodeURIComponent(cleanTable)}&items=${itemsPayload}`;

    directPrintViaIframe(kotUrl);

    if (window.rmsToast) {
      window.rmsToast('Kitchen ticket sent directly to printer!', 'success');
    }
  }

  function directPrintViaIframe(url) {
    if (!url) return;
    if (typeof window.rmsDirectPrint === 'function') {
      window.rmsDirectPrint(url);
      return;
    }
    let iframe = document.getElementById('posDirectPrintFrame');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.id = 'posDirectPrintFrame';
      iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;pointer-events:none;';
      document.body.appendChild(iframe);
    }
    try {
      const u = new URL(url, window.location.origin);
      u.searchParams.set('autoprint', '1');
      u.searchParams.set('_t', Date.now().toString());

      iframe.onload = function () {
        setTimeout(() => {
          try {
            if (iframe.contentWindow && !iframe.contentWindow._rmsPrintExecuted) {
              iframe.contentWindow._rmsPrintExecuted = true;
              iframe.contentWindow.focus();
              iframe.contentWindow.print();
            }
          } catch (e) {
            console.warn('Iframe print error:', e);
          }
        }, 120);
      };

      iframe.src = u.toString();
    } catch (e) {
      console.warn('Invalid URL for print iframe:', e);
    }
  }

  function updatePrinterStatusUI() {
    const isAttached = posState.printerAttached;
    const rule = posState.printerRule || 'preview_if_attached';

    if (dom.printerToggle) {
      dom.printerToggle.checked = isAttached;
    }

    if (dom.ruleRadios) {
      dom.ruleRadios.forEach(radio => {
        radio.checked = (radio.value === rule);
      });
    }

    if (dom.printerStatusBtn) {
      if (isAttached) {
        dom.printerStatusBtn.className = 'btn btn-sm btn-outline-success dropdown-toggle d-flex align-items-center gap-1 fw-semibold printer-attached';
        if (dom.printerDot) dom.printerDot.className = 'pos-printer-dot online';
        if (dom.printerIcon) dom.printerIcon.className = 'bi bi-printer-fill text-success';
        if (dom.printerStatusText) dom.printerStatusText.textContent = 'Printer: Connected';
      } else {
        dom.printerStatusBtn.className = 'btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1 fw-semibold printer-detached';
        if (dom.printerDot) dom.printerDot.className = 'pos-printer-dot offline';
        if (dom.printerIcon) dom.printerIcon.className = 'bi bi-printer text-muted';
        if (dom.printerStatusText) dom.printerStatusText.textContent = 'Printer: Not Attached';
      }
    }

    if (dom.printerModeBadge) {
      dom.printerModeBadge.textContent = 'Direct Print';
      dom.printerModeBadge.className = 'badge bg-success-subtle text-success border border-success-subtle ms-1';
    }

    if (dom.printerStatePill) {
      dom.printerStatePill.textContent = isAttached ? 'Attached' : 'Not Attached';
      dom.printerStatePill.className = isAttached ? 'badge bg-success' : 'badge bg-secondary';
    }

    if (dom.printerExplainer) {
      dom.printerExplainer.textContent = 'Direct print active: Instantly prints receipt directly without separate preview.';
    }
  }

  function initPrinterControls() {
    updatePrinterStatusUI();

    if (dom.printerToggle) {
      dom.printerToggle.addEventListener('change', function () {
        posState.printerAttached = this.checked;
        try {
          localStorage.setItem('rms_pos_printer_attached', posState.printerAttached ? 'true' : 'false');
        } catch (e) {}
        updatePrinterStatusUI();
        if (window.rmsToast) {
          window.rmsToast(posState.printerAttached ? 'Printer: Attached' : 'Printer: Not Attached', 'info');
        }
      });
    }

    if (dom.ruleRadios) {
      dom.ruleRadios.forEach(radio => {
        radio.addEventListener('change', function () {
          if (this.checked) {
            posState.printerRule = this.value;
            try {
              localStorage.setItem('rms_pos_printer_rule', posState.printerRule);
            } catch (e) {}
            updatePrinterStatusUI();
            if (window.rmsToast) {
              window.rmsToast('Print rule saved', 'info');
            }
          }
        });
      });
    }

    if (dom.testPrintSlipBtn) {
      dom.testPrintSlipBtn.addEventListener('click', function () {
        const testOrder = posState.lastCompletedOrder || {
          id: 0,
          orderNumber: 'TEST-' + Math.floor(1000 + Math.random() * 9000),
          createdAt: new Date().toLocaleString(),
          orderType: posState.orderType,
          floorName: posState.floorName,
          tableNo: posState.tableNo,
          customerName: 'Test Customer',
          paymentMethod: 'Cash',
          items: [
            { name: 'POS Printer Test Slip', qty: 1, price: 5.00, notes: 'Thermal Printer Diagnostic Slip' }
          ],
          totals: { subtotal: 5.00, totalTax: 0.25, discountAmount: 0, grandTotal: 5.25 },
          paidAmount: 5.25,
          changeAmount: 0.00
        };
        handleOrderReceiptDispatch(testOrder);
      });
    }
  }

  function initRecentOrdersModal() {
    if (dom.recentOrdersBtn) {
      dom.recentOrdersBtn.addEventListener('click', openRecentOrdersModal);
    }
  }

  function openRecentOrdersModal() {
    const container = dom.recentOrdersList;
    if (!container) return;

    container.innerHTML = '<div class="text-center py-4"><span class="spinner-border text-primary me-2"></span>Loading recent orders...</div>';

    const modal = new bootstrap.Modal(dom.recentOrdersModalEl);
    modal.show();

    fetch('/RMS/app/controller/order.php?action=get_orders')
      .then(r => r.json())
      .then(data => {
        if (data && data.success && Array.isArray(data.orders) && data.orders.length > 0) {
          container.innerHTML = `
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Order #</th>
                    <th>Type / Table</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Time</th>
                    <th class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  ${data.orders.map(o => `
                    <tr>
                      <td><strong class="text-primary">${escapeHtml(o.order_number || ('ORD-' + o.id))}</strong></td>
                      <td>
                        <span class="badge bg-secondary">${escapeHtml(o.order_type)}</span>
                        ${o.order_type === 'Dine-In' && o.table_no ? `<small class="text-muted ms-1">${escapeHtml(o.table_no)}</small>` : ''}
                      </td>
                      <td>${escapeHtml(o.customer_name || 'Walk-in')}</td>
                      <td><strong class="text-success">Rs. ${parseFloat(o.grand_total || o.total || 0).toFixed(2)}</strong></td>
                      <td><small class="text-muted">${escapeHtml(o.created_at || '')}</small></td>
                      <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary py-0 px-2 btn-preview-recent" data-id="${o.id}" title="Preview Receipt">
                          <i class="bi bi-eye me-1"></i>Preview
                        </button>
                        <button class="btn btn-sm btn-outline-dark py-0 px-2 btn-print-recent ms-1" data-id="${o.id}" title="Direct Print Receipt">
                          <i class="bi bi-printer me-1"></i>Print
                        </button>
                      </td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          `;

          container.querySelectorAll('.btn-preview-recent').forEach(btn => {
            btn.addEventListener('click', function () {
              const id = parseInt(this.dataset.id);
              fetchOrderAndShowReceipt(id, true);
            });
          });

          container.querySelectorAll('.btn-print-recent').forEach(btn => {
            btn.addEventListener('click', function () {
              const id = parseInt(this.dataset.id);
              directPrintReceipt({ id: id }, 'thermal');
            });
          });
        } else {
          container.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1"></i><p class="mt-2">No completed orders found.</p></div>';
        }
      })
      .catch(err => {
        container.innerHTML = '<div class="alert alert-danger mb-0">Error loading recent orders: ' + escapeHtml(err.message) + '</div>';
      });
  }

  function fetchOrderAndShowReceipt(orderId, forcePreview = false) {
    fetch(`/RMS/app/controller/order.php?action=get_order_details&order_id=${orderId}`)
      .then(r => r.json())
      .then(data => {
        if (data && data.success && data.order) {
          const o = data.order;
          const mappedOrder = {
            id: o.id,
            orderNumber: o.order_number,
            createdAt: o.created_at,
            orderType: o.order_type,
            floorName: o.floor_name || '',
            tableNo: o.table_no || '',
            customerName: o.customer_name || 'Walk-in Customer',
            paymentMethod: o.payment_method || 'Cash',
            items: (o.items || []).map(i => ({
              name: i.item_name,
              qty: parseInt(i.quantity || 1),
              price: parseFloat(i.price || 0),
              notes: i.notes || ''
            })),
            totals: {
              subtotal: parseFloat(o.subtotal || 0),
              totalTax: parseFloat(o.tax || 0),
              discountAmount: parseFloat(o.discount || 0),
              grandTotal: parseFloat(o.grand_total || 0)
            },
            paidAmount: parseFloat(o.paid_amount || o.grand_total || 0),
            changeAmount: parseFloat(o.change_amount || 0)
          };
          if (forcePreview) {
            showReceiptModal(mappedOrder);
          } else {
            handleOrderReceiptDispatch(mappedOrder);
          }
        }
      })
      .catch(console.error);
  }

  // --- THERMAL RECEIPT MODAL ---
  function showReceiptModal(order) {
    const container = document.getElementById('thermalReceiptPreviewContainer');
    if (!container || !order) return;

    container.innerHTML = `
      <div class="thermal-receipt-preview" id="thermalReceiptPrintSection">
        <div class="thermal-receipt-header">
          <h4>RMS RESTAURANT</h4>
          <div>123 Gourmet Blvd, Suite 100</div>
          <div>Tel: +1 (555) 890-1234</div>
          <div class="thermal-divider"></div>
          <div><strong>Order: ${escapeHtml(order.orderNumber)}</strong></div>
          <div>Date: ${escapeHtml(order.createdAt)}</div>
          <div>Type: ${escapeHtml(order.orderType)} ${order.orderType === 'Dine-In' ? ` | ${order.floorName ? escapeHtml(order.floorName) + ' | ' : ''}${escapeHtml(formatCleanTableNumber(order.tableNo))}` : ''}</div>
          <div>Customer: ${escapeHtml(order.customerName)}</div>
        </div>

        <div class="thermal-divider"></div>

        <table class="thermal-table">
          <thead>
            <tr>
              <th style="width: 50%;">Item</th>
              <th style="width: 15%; text-align: center;">Qty</th>
              <th style="width: 35%; text-align: right;">Total</th>
            </tr>
          </thead>
          <tbody>
            ${order.items.map(item => `
              <tr>
                <td>
                  ${escapeHtml(item.name)}
                  ${item.notes ? `<br><small>(${escapeHtml(item.notes)})</small>` : ''}
                </td>
                <td class="thermal-center">${item.qty}</td>
                <td class="thermal-right">Rs. ${(item.price * item.qty).toFixed(2)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>

        <div class="thermal-divider"></div>

        <table class="thermal-table">
          <tr>
            <td>Subtotal:</td>
            <td class="thermal-right">Rs. ${order.totals.subtotal.toFixed(2)}</td>
          </tr>
          ${order.totals.totalTax > 0 ? `
            <tr>
              <td>Tax / GST:</td>
              <td class="thermal-right">Rs. ${order.totals.totalTax.toFixed(2)}</td>
            </tr>
          ` : ''}
          ${order.totals.discountAmount > 0 ? `
            <tr>
              <td>Discount:</td>
              <td class="thermal-right">-Rs. ${order.totals.discountAmount.toFixed(2)}</td>
            </tr>
          ` : ''}
          <tr style="font-weight: bold; font-size: 16px;">
            <td>TOTAL:</td>
            <td class="thermal-right">Rs. ${order.totals.grandTotal.toFixed(2)}</td>
          </tr>
          <tr>
            <td>Paid (${escapeHtml(order.paymentMethod)}):</td>
            <td class="thermal-right">Rs. ${order.paidAmount.toFixed(2)}</td>
          </tr>
          <tr>
            <td>Change Due:</td>
            <td class="thermal-right">Rs. ${order.changeAmount.toFixed(2)}</td>
          </tr>
        </table>

        <div class="thermal-divider"></div>
        <div class="thermal-center mt-2">
          <strong>Thank you for dining with us!</strong><br>
          <small>Please visit again</small>
        </div>
      </div>
    `;

    const pdfBtn = document.getElementById('posDownloadPdfBtn');
    if (pdfBtn && order) {
      const orderIdParam = order.id ? `order_id=${order.id}` : `order_id=0`;
      pdfBtn.href = `/RMS/views/order/print_slip.php?${orderIdParam}&format=thermal&autoprint=1`;
      pdfBtn.onclick = function (e) {
        e.preventDefault();
        directPrintReceipt(order, 'thermal');
      };
    }

    const receiptKotBtn = document.getElementById('posReceiptKotBtn');
    if (receiptKotBtn && order) {
      const orderIdParam = order.id ? `order_id=${order.id}` : `order_id=0`;
      receiptKotBtn.href = `/RMS/views/order/print_slip.php?${orderIdParam}&format=kot&autoprint=1`;
      receiptKotBtn.onclick = function (e) {
        e.preventDefault();
        directPrintReceipt(order, 'kot');
      };
    }

    const printBtn = document.getElementById('posPrintReceiptActionBtn');
    if (printBtn) {
      printBtn.onclick = function (e) {
        e.preventDefault();
        directPrintReceipt(order, 'thermal');
      };
    }

    const modal = new bootstrap.Modal(dom.receiptModalEl);
    modal.show();
  }

  // --- KITCHEN ORDER TICKET (KOT) MODAL ---
  function openKotModal() {
    if (posState.cart.length === 0) {
      if (window.rmsToast) window.rmsToast('No items in order to send to kitchen!', 'warning');
      return;
    }

    const container = document.getElementById('posKotContent');
    if (!container) return;

    const cleanTable = formatCleanTableNumber(posState.tableNo);
    const locHeader = posState.orderType + (posState.orderType === 'Dine-In' ? (posState.floorName ? ' | ' + posState.floorName : '') + (cleanTable ? ' | ' + cleanTable : '') : '');

    container.innerHTML = `
      <div class="thermal-receipt-preview border p-3">
        <div class="thermal-center mb-2">
          <h5 class="fw-bold mb-0" style="font-size: 1.25rem;">*** KITCHEN ORDER TICKET ***</h5>
          <div class="fw-bold text-primary fs-5 mt-1">${escapeHtml(locHeader)}</div>
          <small class="fw-semibold">${new Date().toLocaleTimeString()} • Server: Staff</small>
        </div>
        <div class="thermal-divider"></div>
        <table class="thermal-table">
          <thead>
            <tr>
              <th style="width: 44px;">Qty</th>
              <th>Item & Instructions</th>
            </tr>
          </thead>
          <tbody>
            ${posState.cart.map(item => `
              <tr>
                <td style="font-size: 18px; font-weight: bold; vertical-align: top; width: 44px;">${item.qty}x</td>
                <td>
                  <strong style="font-size: 16px;">${escapeHtml(item.name)}</strong>
                  ${item.notes ? `<div class="text-danger fw-bold">> NOTE: ${escapeHtml(item.notes)}</div>` : ''}
                </td>
              </tr>
            `).join('')}
          </tbody>
        </table>
        <div class="thermal-divider"></div>
        <div class="thermal-center text-muted small fw-bold mt-2">RMS Kitchen Management System</div>
      </div>
    `;

    const kotPdfBtn = document.getElementById('posKotPdfBtn');
    if (kotPdfBtn) {
      kotPdfBtn.onclick = function (e) {
        e.preventDefault();
        directPrintKot();
        try {
          const m = bootstrap.Modal.getInstance(dom.kotModalEl);
          if (m) m.hide();
        } catch (err) {}
      };
    }

    const modal = new bootstrap.Modal(dom.kotModalEl);
    modal.show();
  }

  // Clean table numbers: strip bullets, parentheses, extra symbols, duplicate words
  function formatCleanTableNumber(raw) {
    if (!raw) return '';
    let c = String(raw).replace(/\s*\([^)]*\).*/, '').replace(/\s*-\s*(Available|Occupied|Reserved).*/i, '');
    c = c.replace(/[•Ââ€¢#:]/g, '').trim();
    if (!/^table\s*/i.test(c) && c) {
      c = 'Table ' + c;
    }
    return c;
  }
  // --- UTILITY ---
  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  window.posTerminal = {
    addToCart,
    calculateTotals,
    holdCurrentOrder,
    resumeHeldOrder
  };
})();
