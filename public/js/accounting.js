/* ==========================================================================
   Sinbad Accounting Module — JavaScript
   All interactivity lives here. No JS logic inside Blade files.
   ========================================================================== */

'use strict';

/* --------------------------------------------------------------------------
   Boot
   -------------------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
    Sidebar.init();
    UI.init();
    TrialBalance.init();
    AccountCards.init();
    Accounts.init();
    JournalEntryForm.init();
    TransactionForm.init();
    ProgressBars.init();
    RolePicker.init();
    AttachmentUpload.init();
    PaymentMethodPicker.init();
    Modal.init();
    BalanceSheetTree.init();
    BankReconciliation.init();
});

/* --------------------------------------------------------------------------
   Sidebar — desktop collapse + mobile overlay drawer
   -------------------------------------------------------------------------- */
const Sidebar = {
    STORAGE_KEY: 'ac.sidebar.collapsed',
    MOBILE_QUERY: '(max-width: 900px)',

    init() {
        this.body = document.body;
        this.sidebar = document.getElementById('accounting-sidebar');
        this.toggleBtn = document.querySelector('[data-sidebar-toggle]');
        this.closeBtn = document.querySelector('[data-sidebar-close]');
        this.overlay = document.querySelector('[data-sidebar-overlay]');
        this.media = window.matchMedia(this.MOBILE_QUERY);

        if (!this.sidebar || !this.toggleBtn) return;

        this.prepareLabels();
        this.applyStoredState();
        this.bindEvents();
        this.syncMode();
    },

    prepareLabels() {
        this.sidebar.querySelectorAll('.ac-sidebar__link').forEach((link) => {
            const label = link.textContent.trim().replace(/\s+/g, ' ');
            link.dataset.sidebarLabel = label;
            link.setAttribute('title', label);
            link.setAttribute('aria-label', label);
        });
    },

    bindEvents() {
        this.toggleBtn.addEventListener('click', () => this.toggle());
        this.closeBtn?.addEventListener('click', () => this.closeMobile());
        this.overlay?.addEventListener('click', () => this.closeMobile());

        this.sidebar.querySelectorAll('.ac-sidebar__link').forEach((link) => {
            link.addEventListener('click', () => {
                if (this.isMobile()) {
                    this.closeMobile();
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.closeMobile();
            }
        });

        if (this.media.addEventListener) {
            this.media.addEventListener('change', () => this.syncMode());
        } else if (this.media.addListener) {
            this.media.addListener(() => this.syncMode());
        }
    },

    toggle() {
        if (this.isMobile()) {
            this.body.classList.toggle('ac-sidebar-mobile-open');
            this.body.classList.toggle('ac-no-scroll', this.body.classList.contains('ac-sidebar-mobile-open'));
            this.updateAria();
            return;
        }

        const collapsed = this.body.classList.toggle('ac-sidebar-collapsed');
        this.saveCollapsedState(collapsed);
        this.updateAria();
    },

    closeMobile() {
        this.body.classList.remove('ac-sidebar-mobile-open', 'ac-no-scroll');
        this.updateAria();
    },

    applyStoredState() {
        if (this.isMobile()) return;

        const collapsed = this.getCollapsedState();
        this.body.classList.toggle('ac-sidebar-collapsed', collapsed);
        this.updateAria();
    },

    syncMode() {
        if (this.isMobile()) {
            this.body.classList.remove('ac-no-scroll');
            this.body.classList.remove('ac-sidebar-mobile-open');
        } else {
            this.applyStoredState();
        }

        this.updateAria();
    },

    updateAria() {
        const expanded = this.isMobile()
            ? this.body.classList.contains('ac-sidebar-mobile-open')
            : !this.body.classList.contains('ac-sidebar-collapsed');

        this.toggleBtn?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    },

    isMobile() {
        return this.media?.matches ?? window.innerWidth <= 900;
    },

    getCollapsedState() {
        try {
            return window.localStorage.getItem(this.STORAGE_KEY) === '1';
        } catch (error) {
            return false;
        }
    },

    saveCollapsedState(collapsed) {
        try {
            window.localStorage.setItem(this.STORAGE_KEY, collapsed ? '1' : '0');
        } catch (error) {
            // Keeping the UI functional is more important than persistence.
        }
    },
};

/* --------------------------------------------------------------------------
   Progress Bars — read data-pct attribute and set width
   -------------------------------------------------------------------------- */
const ProgressBars = {
    init() {
        document.querySelectorAll('.ac-progress-fill[data-pct]').forEach((el) => {
            const pct = Math.min(100, Math.max(0, parseFloat(el.dataset.pct) || 0));
            el.style.width = pct + '%';
        });
    },
};

/* --------------------------------------------------------------------------
   UI — alerts, flash dismiss
   -------------------------------------------------------------------------- */
const UI = {
    init() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-dismiss="alert"]')) {
                e.target.closest('.ac-alert')?.remove();
            }
        });
    },
};

/* --------------------------------------------------------------------------
   Accounts — auto-fill normal_balance when type changes
   -------------------------------------------------------------------------- */
const Accounts = {
    DEBIT_TYPES: ['asset', 'expense'],

    init() {
        const typeSelect = document.getElementById('account-type');
        if (!typeSelect) return;
        typeSelect.addEventListener('change', () => this.syncNormalBalance(typeSelect.value));
    },

    syncNormalBalance(type) {
        const field = document.getElementById('account-normal-balance');
        if (!field) return;
        field.value = this.DEBIT_TYPES.includes(type) ? 'debit' : 'credit';
    },
};

/* --------------------------------------------------------------------------
   Trial Balance — collapse / expand account type groups
   -------------------------------------------------------------------------- */
const TrialBalance = {
    init() {
        document.querySelectorAll('.ac-tb-collapse-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                btn.closest('.ac-tb-group')?.classList.toggle('ac-tb-group--collapsed');
            });
        });
    },
};

/* --------------------------------------------------------------------------
   Account Cards — collapse / expand + delete confirmation
   -------------------------------------------------------------------------- */
const AccountCards = {
    init() {
        // Collapse / expand
        document.querySelectorAll('.ac-accounts-card__collapse-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const card = btn.closest('.ac-accounts-card');
                if (card) card.classList.toggle('ac-accounts-card--collapsed');
            });
        });

        // Delete confirmation — forms with data-confirm attribute
        document.addEventListener('submit', (e) => {
            const form = e.target.closest('form[data-confirm]');
            if (!form) return;
            if (!window.confirm(form.dataset.confirm)) {
                e.preventDefault();
            }
        });
    },
};

/* --------------------------------------------------------------------------
   Transaction Form — type card picker + per-type field panels
   -------------------------------------------------------------------------- */
const TransactionForm = {
    TYPES: ['expense', 'income', 'transfer', 'capital_addition', 'withdrawal'],

    init() {
        const grid = document.getElementById('txn-type-grid');
        if (!grid) return;

        // On first load: disable ALL panel inputs until a type is chosen,
        // so no hidden field can accidentally submit an empty value.
        this.TYPES.forEach((t) => {
            const panel = document.getElementById(`fields-${t}`);
            panel?.querySelectorAll('select, input').forEach((el) => { el.disabled = true; });
        });

        // Click on any type card
        grid.addEventListener('click', (e) => {
            const card = e.target.closest('.ac-type-card');
            if (!card) return;
            const type = card.dataset.type;
            card.querySelector('input[type="radio"]').checked = true;
            this.activate(type);
        });

        // Restore state after validation error (old input repopulated by Laravel)
        const checked = grid.querySelector('input[type="radio"]:checked');
        if (checked) this.activate(checked.value);
    },

    activate(type) {
        // Highlight selected card
        document.querySelectorAll('.ac-type-card').forEach((c) => {
            c.classList.toggle('ac-type-card--active', c.dataset.type === type);
        });

        // Show active panel + DISABLE inputs in hidden panels so they are
        // excluded from form submission (prevents duplicate field names
        // from overwriting the active panel's values).
        this.TYPES.forEach((t) => {
            const panel = document.getElementById(`fields-${t}`);
            if (!panel) return;
            const isActive = t === type;
            panel.style.display = isActive ? '' : 'none';
            panel.querySelectorAll('select, input').forEach((el) => {
                el.disabled = !isActive;
            });
        });

        // Show the shared fields wrapper
        const wrapper = document.getElementById('txn-fields');
        if (wrapper) wrapper.style.display = '';
    },
};

/* --------------------------------------------------------------------------
   Journal Entry Form — dynamic lines + balance check
   -------------------------------------------------------------------------- */
const JournalEntryForm = {
    MIN_LINES: 2,

    init() {
        const form = document.getElementById('je-form');
        if (!form) return;

        document.getElementById('je-add-line')
            ?.addEventListener('click', () => this.addLine());

        form.addEventListener('submit', (e) => this.onSubmit(e));

        // Delegate remove clicks and amount inputs to the container
        const container = document.getElementById('je-lines');
        if (!container) return;

        container.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="remove-line"]')) {
                this.removeLine(e.target.closest('[data-journal-line]'));
            }
        });

        container.addEventListener('input', (e) => {
            if (e.target.matches('[data-debit], [data-credit]')) {
                this.checkBalance();
            }
        });

        this.checkBalance();
    },

    addLine() {
        const container = document.getElementById('je-lines');
        const template  = document.getElementById('je-line-template');
        if (!container || !template) return;

        const index = container.querySelectorAll('[data-journal-line]').length;
        const clone = template.content.cloneNode(true);

        clone.querySelectorAll('[name]').forEach((el) => {
            el.name = el.name.replace(/__INDEX__/g, index);
        });

        container.appendChild(clone);
        this.checkBalance();
    },

    removeLine(row) {
        if (!row) return;

        const container = document.getElementById('je-lines');
        const lines     = container?.querySelectorAll('[data-journal-line]') ?? [];

        if (lines.length <= this.MIN_LINES) {
            alert(`A journal entry requires at least ${this.MIN_LINES} lines.`);
            return;
        }

        row.remove();
        this.reindexLines();
        this.checkBalance();
    },

    // Keep name indices contiguous after a removal
    reindexLines() {
        const container = document.getElementById('je-lines');
        if (!container) return;

        container.querySelectorAll('[data-journal-line]').forEach((row, i) => {
            row.querySelectorAll('[name]').forEach((el) => {
                el.name = el.name.replace(/lines\[\d+\]/, `lines[${i}]`);
            });
        });
    },

    checkBalance() {
        let totalDebit  = 0;
        let totalCredit = 0;

        document.querySelectorAll('[data-journal-line]').forEach((row) => {
            totalDebit  += parseFloat(row.querySelector('[data-debit]')?.value)  || 0;
            totalCredit += parseFloat(row.querySelector('[data-credit]')?.value) || 0;
        });

        const balanced = Math.abs(totalDebit - totalCredit) < 0.005;

        this.updateBalanceBar(balanced, totalDebit, totalCredit);
        this.updateSubmitButton(balanced);
    },

    updateBalanceBar(balanced, totalDebit, totalCredit) {
        const bar = document.getElementById('je-balance-bar');
        if (!bar) return;

        const fmt = (n) => n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        bar.className = 'ac-balance-bar ' + (balanced ? 'ac-balance-bar--ok' : 'ac-balance-bar--error');

        const status  = bar.querySelector('.ac-balance-bar__status');
        const amounts = bar.querySelector('.ac-balance-bar__amounts');

        if (status)  status.textContent  = balanced ? 'Balanced ✓' : 'Unbalanced ✗';
        if (amounts) amounts.textContent = `DR ${fmt(totalDebit)}   CR ${fmt(totalCredit)}`;
    },

    updateSubmitButton(balanced) {
        const btn = document.getElementById('je-submit');
        if (!btn) return;
        btn.disabled = !balanced;
    },

    onSubmit(e) {
        this.checkBalance();

        const btn = document.getElementById('je-submit');
        if (btn?.disabled) {
            e.preventDefault();
            alert('Journal entry is not balanced. Total debits must equal total credits.');
        }
    },
};

/* --------------------------------------------------------------------------
   Role Picker — highlight selected card when radio changes
   -------------------------------------------------------------------------- */
const RolePicker = {
    init() {
        const picker = document.querySelector('.ac-role-picker');
        if (!picker) return;

        // Initial state — mark the checked option
        picker.querySelectorAll('.ac-role-option').forEach((label) => {
            const radio = label.querySelector('input[type="radio"]');
            if (radio?.checked) label.classList.add('ac-role-option--selected');
        });

        // On change
        picker.addEventListener('change', (e) => {
            if (!e.target.matches('input[type="radio"]')) return;
            picker.querySelectorAll('.ac-role-option').forEach((label) => {
                const radio = label.querySelector('input[type="radio"]');
                label.classList.toggle('ac-role-option--selected', !!radio?.checked);
            });
        });
    },
};

/* --------------------------------------------------------------------------
   Attachment Upload — multi-file picker + drag-drop + live list with remove
   -------------------------------------------------------------------------- */
const AttachmentUpload = {
    /** Internal file list (File objects). Mirrors what will be submitted. */
    files: [],

    init() {
        const zone  = document.getElementById('upload-zone');
        if (!zone) return;

        this.files  = [];
        this.input  = zone.querySelector('.ac-upload-zone__input');
        this.list   = document.getElementById('upload-list');

        // File chosen via native picker
        this.input.addEventListener('change', () => {
            this.addFiles(Array.from(this.input.files));
            // Reset input value so the same file can be re-added after removal
            this.input.value = '';
        });

        // Drag over / leave
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('ac-upload-zone--drag');
        });
        ['dragleave', 'drop'].forEach((ev) =>
            zone.addEventListener(ev, () => zone.classList.remove('ac-upload-zone--drag'))
        );

        // Drop files
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            const dropped = Array.from(e.dataTransfer?.files ?? []);
            if (dropped.length) this.addFiles(dropped);
        });
    },

    addFiles(incoming) {
        const MAX = 10;
        incoming.forEach((file) => {
            if (this.files.length >= MAX) return;
            // Deduplicate by name + size
            const dup = this.files.some((f) => f.name === file.name && f.size === file.size);
            if (!dup) this.files.push(file);
        });
        this.syncInput();
        this.renderList();
    },

    removeFile(index) {
        this.files.splice(index, 1);
        this.syncInput();
        this.renderList();
    },

    /** Push current this.files back into the <input> via DataTransfer. */
    syncInput() {
        const dt = new DataTransfer();
        this.files.forEach((f) => dt.items.add(f));
        this.input.files = dt.files;
    },

    renderList() {
        if (!this.list) return;

        if (this.files.length === 0) {
            this.list.hidden = true;
            this.list.innerHTML = '';
            return;
        }

        this.list.hidden = false;
        this.list.innerHTML = '';

        this.files.forEach((file, idx) => {
            const li = document.createElement('li');
            li.className = 'ac-upload-item';
            li.dataset.idx = idx;

            const isImage = file.type.startsWith('image/');

            if (isImage) {
                const img = document.createElement('img');
                img.className = 'ac-upload-item__thumb';
                img.alt = file.name;
                const reader = new FileReader();
                reader.onload = (ev) => { img.src = ev.target.result; };
                reader.readAsDataURL(file);
                li.appendChild(img);
            } else {
                const icon = document.createElement('div');
                icon.className = 'ac-upload-item__icon';
                icon.textContent = file.type === 'application/pdf' ? '📄' : '📊';
                li.appendChild(icon);
            }

            const meta = document.createElement('div');
            meta.className = 'ac-upload-item__meta';
            meta.innerHTML = `
                <span class="ac-upload-item__name">${this.escHtml(file.name)}</span>
                <span class="ac-upload-item__size">${this.formatSize(file.size)}</span>`;
            li.appendChild(meta);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ac-upload-item__remove';
            btn.setAttribute('aria-label', 'إزالة');
            btn.textContent = '✕';
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.removeFile(idx);
            });
            li.appendChild(btn);

            this.list.appendChild(li);
        });
    },

    formatSize(bytes) {
        if (bytes < 1024)        return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    },

    escHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },
};

/* --------------------------------------------------------------------------
   Modal — lightweight accessible modal manager
   Usage:
     Open : <button data-modal="my-modal">
     Close: <button data-modal-close="my-modal">  OR  click backdrop  OR  Escape
     HTML : <div id="my-modal" class="ac-modal" hidden>
              <div class="ac-modal__backdrop" data-modal-close="my-modal"></div>
              <div class="ac-modal__dialog"> ... </div>
            </div>
   -------------------------------------------------------------------------- */
const Modal = {
    init() {
        // Open triggers
        document.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-modal]');
            if (trigger) this.open(trigger.dataset.modal);
        });

        // Close triggers (backdrop + close buttons)
        document.addEventListener('click', (e) => {
            const closer = e.target.closest('[data-modal-close]');
            if (closer) this.close(closer.dataset.modalClose);
        });

        // ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.ac-modal:not([hidden])').forEach((m) => {
                    this.close(m.id);
                });
            }
        });
    },

    open(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.hidden = false;
        document.body.classList.add('ac-modal-open');
        // Focus the first focusable element inside the dialog
        const focusable = modal.querySelector(
            'button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        focusable?.focus();
    },

    close(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.hidden = true;
        if (!document.querySelector('.ac-modal:not([hidden])')) {
            document.body.classList.remove('ac-modal-open');
        }
    },
};

/* --------------------------------------------------------------------------
   Payment Method Picker — highlight selected card when radio changes
   -------------------------------------------------------------------------- */
const PaymentMethodPicker = {
    init() {
        const grids = document.querySelectorAll('.ac-pay-method-grid');
        grids.forEach((grid) => {
            // Mark initially checked card
            grid.querySelectorAll('.ac-pay-method-card').forEach((card) => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio?.checked) card.classList.add('ac-pay-method-card--active');
            });
            // On change
            grid.addEventListener('change', (e) => {
                if (!e.target.matches('input[type="radio"]')) return;
                grid.querySelectorAll('.ac-pay-method-card').forEach((card) => {
                    const radio = card.querySelector('input[type="radio"]');
                    card.classList.toggle('ac-pay-method-card--active', !!radio?.checked);
                });
            });
        });
    },
};

/* --------------------------------------------------------------------------
   Balance Sheet Tree — expand / collapse account hierarchy
   --------------------------------------------------------------------------
   Markup contract:
     Parent row : data-bs-node="account-{id}"   + .ac-bs-toggle button
     Child row  : data-bs-node="account-{id}"   + data-bs-parent="account-{parentId}"
   Global buttons: #bs-expand-all  /  #bs-collapse-all
   -------------------------------------------------------------------------- */
const BalanceSheetTree = {
    init() {
        // Per-row toggle buttons
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.ac-bs-toggle');
            if (!btn) return;
            const row = btn.closest('[data-bs-node]');
            if (!row) return;
            this.toggle(row);
        });

        // Global expand / collapse
        document.getElementById('bs-expand-all')
            ?.addEventListener('click', () => this.setAll(false));
        document.getElementById('bs-collapse-all')
            ?.addEventListener('click', () => this.setAll(true));
    },

    /** Toggle a single parent row between expanded and collapsed. */
    toggle(row) {
        const collapsed = row.classList.toggle('ac-bs-node--collapsed');
        const btn       = row.querySelector('.ac-bs-toggle');
        if (btn) btn.classList.toggle('ac-bs-toggle--collapsed', collapsed);

        const nodeId = row.dataset.bsNode;
        if (collapsed) {
            this._hideDescendants(nodeId);
        } else {
            this._showDirectChildren(nodeId);
        }
    },

    /** Expand or collapse every parent row in the sheet. */
    setAll(collapse) {
        document.querySelectorAll('[data-bs-node]').forEach((row) => {
            if (!row.querySelector('.ac-bs-toggle')) return; // leaf — skip

            row.classList.toggle('ac-bs-node--collapsed', collapse);
            row.querySelector('.ac-bs-toggle')
               ?.classList.toggle('ac-bs-toggle--collapsed', collapse);
        });

        // Hide or show ALL child rows
        document.querySelectorAll('[data-bs-parent]').forEach((child) => {
            child.hidden = collapse;
        });
    },

    /** Recursively hide every descendant of nodeId. */
    _hideDescendants(nodeId) {
        document.querySelectorAll(`[data-bs-parent="${nodeId}"]`).forEach((child) => {
            child.hidden = true;
            const childId = child.dataset.bsNode;
            if (childId) this._hideDescendants(childId);
        });
    },

    /** Show only direct children; grandchildren keep their own collapse state. */
    _showDirectChildren(nodeId) {
        document.querySelectorAll(`[data-bs-parent="${nodeId}"]`).forEach((child) => {
            child.hidden = false;
        });
    },
};

/* --------------------------------------------------------------------------
   Bank Reconciliation — interactive matching on show page
   -------------------------------------------------------------------------- */
const BankReconciliation = {
    selectedBankLineId: null,
    selectedBankRowEl:  null,

    init() {
        if (! document.getElementById('br-page')) return;

        // Click selectable bank line rows
        document.querySelectorAll('.ac-br-bank-row--selectable').forEach((row) => {
            row.addEventListener('click', () => this.selectBankLine(row));
        });

        // "طابق" button on journal lines
        document.querySelectorAll('.ac-br-match-btn').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (! this.selectedBankLineId) {
                    this.setHint('يرجى تحديد سطر من كشف البنك أولاً (العمود الأيسر).', true);
                    return;
                }
                this.doMatch(this.selectedBankLineId, btn.dataset.matchJournal);
            });
        });

        // "إلغاء المطابقة" button on matched bank lines
        document.querySelectorAll('.ac-br-unmatch-btn').forEach((btn) => {
            btn.addEventListener('click', () => this.doUnmatch(btn.dataset.unmatchLine));
        });
    },

    selectBankLine(row) {
        // Deselect previous
        document.querySelectorAll('.ac-br-bank-row--selected').forEach((r) => {
            r.classList.remove('ac-br-bank-row--selected');
        });
        row.classList.add('ac-br-bank-row--selected');
        this.selectedBankLineId = row.dataset.lineId;
        this.selectedBankRowEl  = row;

        this.setHint('تم تحديد السطر — اضغط <strong>طابق</strong> بجانب القيد المقابل في العمود الأيمن.');
    },

    setHint(msg, isError = false) {
        const hint     = document.getElementById('br-hint');
        const hintText = document.getElementById('br-hint-text');
        if (! hint || ! hintText) return;
        hintText.innerHTML = msg;
        hint.classList.toggle('ac-br-hint--error', isError);
    },

    async doMatch(statementLineId, journalLineId) {
        const page = document.getElementById('br-page');
        const result = await this._post(page.dataset.matchUrl, {
            statement_line_id: statementLineId,
            journal_line_id:   journalLineId,
        });

        if (result.ok) {
            this.selectedBankLineId = null;
            this.selectedBankRowEl  = null;
            location.reload();
        } else {
            this.setHint(result.message || 'حدث خطأ في المطابقة.', true);
        }
    },

    async doUnmatch(statementLineId) {
        const page   = document.getElementById('br-page');
        const result = await this._post(page.dataset.unmatchUrl, {
            statement_line_id: statementLineId,
        });

        if (result.ok) {
            location.reload();
        } else {
            alert(result.message || 'حدث خطأ في إلغاء المطابقة.');
        }
    },

    async _post(url, data) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        try {
            const resp = await fetch(url, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept':       'application/json',
                },
                body: JSON.stringify(data),
            });
            return resp.json();
        } catch {
            return { ok: false, message: 'خطأ في الاتصال بالخادم.' };
        }
    },
};
