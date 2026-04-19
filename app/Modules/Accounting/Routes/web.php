<?php

use App\Modules\Accounting\Http\Controllers\AccountController;
use App\Modules\Accounting\Http\Controllers\BankReconciliationController;
use App\Modules\Accounting\Http\Controllers\EmployeeController;
use App\Modules\Accounting\Http\Controllers\InventoryController;
use App\Modules\Accounting\Http\Controllers\LeaveManagementController;
use App\Modules\Accounting\Http\Controllers\PayrollController;
use App\Modules\Accounting\Http\Controllers\ProductController;
use App\Modules\Accounting\Http\Controllers\FiscalYearController;
use App\Modules\Accounting\Http\Controllers\AssetController;
use App\Modules\Accounting\Http\Controllers\CompanySettingsController;
use App\Modules\Accounting\Http\Controllers\AttachmentController;
use App\Modules\Accounting\Http\Controllers\BalanceSheetController;
use App\Modules\Accounting\Http\Controllers\CreditNoteController;
use App\Modules\Accounting\Http\Controllers\CustomerController;
use App\Modules\Accounting\Http\Controllers\InvoiceController;
use App\Modules\Accounting\Http\Controllers\PaymentController;
use App\Modules\Accounting\Http\Controllers\DashboardController;
use App\Modules\Accounting\Http\Controllers\JournalEntryController;
use App\Modules\Accounting\Http\Controllers\PartnerController;
use App\Modules\Accounting\Http\Controllers\PosController;
use App\Modules\Accounting\Http\Controllers\PurchaseInvoiceController;
use App\Modules\Accounting\Http\Controllers\PurchasePaymentController;
use App\Modules\Accounting\Http\Controllers\ReportController;
use App\Modules\Accounting\Http\Controllers\TransactionController;
use App\Modules\Accounting\Http\Controllers\UserController;
use App\Modules\Accounting\Http\Controllers\BudgetController;
use App\Modules\Accounting\Http\Controllers\RecurringEntryController;
use App\Modules\Accounting\Http\Controllers\VendorController;
use App\Modules\Accounting\Http\Controllers\QuotationController;
use App\Modules\Accounting\Http\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/accounting')
    ->name('accounting.')
    ->middleware(['web', 'auth'])
    ->group(function () {

        // ------------------------------------------------------------------
        // Dashboard
        // ------------------------------------------------------------------
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // ------------------------------------------------------------------
        // Accounts
        // ------------------------------------------------------------------
        Route::resource('accounts', AccountController::class)
            ->except(['show']);

        Route::patch('accounts/{account}/toggle-active', [AccountController::class, 'toggleActive'])
            ->name('accounts.toggle-active');

        // ------------------------------------------------------------------
        // Transactions (business-friendly UI)
        // ------------------------------------------------------------------
        Route::resource('transactions', TransactionController::class)
            ->only(['index', 'create', 'store']);

        // ------------------------------------------------------------------
        // Journal Entries — READ-ONLY audit log (system-generated only)
        // Manual create / post / reverse removed intentionally.
        // ------------------------------------------------------------------
        Route::resource('journal-entries', JournalEntryController::class)
            ->only(['index', 'show']);

        // ------------------------------------------------------------------
        // Partners
        // ------------------------------------------------------------------
        Route::resource('partners', PartnerController::class)
            ->only(['index', 'create', 'store', 'show']);

        Route::post('partners/{partner}/add-capital', [PartnerController::class, 'addCapital'])
            ->name('partners.add-capital');

        Route::post('partners/{partner}/withdraw', [PartnerController::class, 'withdraw'])
            ->name('partners.withdraw');

        // ------------------------------------------------------------------
        // Assets
        // ------------------------------------------------------------------
        Route::resource('assets', AssetController::class)
            ->only(['index', 'create', 'store', 'show']);

        Route::post('assets/{asset}/depreciate', [AssetController::class, 'depreciate'])
            ->name('assets.depreciate');

        // ------------------------------------------------------------------
        // Reports
        // ------------------------------------------------------------------
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('income-expense',          [ReportController::class, 'incomeExpense'])->name('income-expense');
            Route::get('cashier-sales',           [ReportController::class, 'cashierSales'])->name('cashier-sales');
            Route::get('trial-balance',           [ReportController::class, 'trialBalance']) ->name('trial-balance');
            Route::get('account-ledger/{account}',[ReportController::class, 'accountLedger'])->name('account-ledger');
            Route::get('profit-loss',             [ReportController::class, 'profitLoss'])   ->name('profit-loss');
            Route::get('balance-sheet',           [BalanceSheetController::class, 'index'])  ->name('balance-sheet');
            Route::get('ar-aging',                        [ReportController::class, 'arAging'])          ->name('ar-aging');
            Route::get('customer-statement/{customer}',   [ReportController::class, 'customerStatement']) ->name('customer-statement');
            Route::get('ap-aging',                        [ReportController::class, 'apAging'])           ->name('ap-aging');
            Route::get('vendor-statement/{vendor}',       [ReportController::class, 'vendorStatement'])   ->name('vendor-statement');
            Route::get('vat-report',                      [ReportController::class, 'vatReport'])          ->name('vat-report');
        });

        // ------------------------------------------------------------------
        // Customers (AR base)
        // ------------------------------------------------------------------
        Route::resource('customers', CustomerController::class)
            ->only(['index', 'create', 'store', 'show']);

        Route::post('customers/{customer}/payments',  [CustomerController::class, 'storePayment']) ->name('customers.payments.store');
        Route::post('customers/{customer}/settle',    [CustomerController::class, 'settle'])       ->name('customers.settle');

        // ------------------------------------------------------------------
        // Invoices (formal printable invoices with QR)
        // ------------------------------------------------------------------
        Route::resource('invoices', InvoiceController::class)
            ->only(['index', 'create', 'store', 'show']);

        Route::get('pos', [PosController::class, 'create'])->name('pos.create');
        Route::post('pos', [PosController::class, 'store'])->name('pos.store');
        Route::get('pos/drawer', [PosController::class, 'drawer'])->name('pos.drawer');
        Route::get('pos/receipt/{invoice}', [PosController::class, 'receipt'])->name('pos.receipt');

        Route::get ('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
            ->name('invoices.pdf');

        Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'recordPayment'])
            ->name('invoices.pay');

        Route::post('invoices/{invoice}/credit-note', [CreditNoteController::class, 'storeForInvoice'])
            ->name('invoices.credit-note');

        // ------------------------------------------------------------------
        // Credit Notes
        // ------------------------------------------------------------------
        Route::resource('credit-notes', CreditNoteController::class)
            ->only(['index', 'create', 'store', 'show']);

        Route::get('credit-notes/{creditNote}/pdf', [CreditNoteController::class, 'downloadPdf'])
            ->name('credit-notes.pdf');

        // ------------------------------------------------------------------
        // Payments (history + filtering)
        // ------------------------------------------------------------------
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');

        // ------------------------------------------------------------------
        // Vendors (AP base)
        // ------------------------------------------------------------------
        Route::resource('vendors', VendorController::class)
            ->only(['index', 'create', 'store', 'show']);

        Route::post('vendors/{vendor}/payments', [VendorController::class, 'storePayment'])->name('vendors.payments.store');
        Route::post('vendors/{vendor}/settle',   [VendorController::class, 'settle'])      ->name('vendors.settle');

        // ------------------------------------------------------------------
        // Purchase Invoices
        // ------------------------------------------------------------------
        Route::resource('purchase-invoices', PurchaseInvoiceController::class)
            ->only(['index', 'create', 'store', 'show']);

        Route::get ('purchase-invoices/{purchaseInvoice}/pdf', [PurchaseInvoiceController::class, 'downloadPdf'])
            ->name('purchase-invoices.pdf');

        Route::post('purchase-invoices/{purchaseInvoice}/pay', [PurchaseInvoiceController::class, 'recordPayment'])
            ->name('purchase-invoices.pay');

        // ------------------------------------------------------------------
        // Purchase Payments (history + filtering)
        // ------------------------------------------------------------------
        Route::get('purchase-payments', [PurchasePaymentController::class, 'index'])->name('purchase-payments.index');

        // ------------------------------------------------------------------
        // Attachments (secure proxy — enforces company isolation)
        // ------------------------------------------------------------------
        Route::get('attachments/{attachment}',          [AttachmentController::class, 'show'])    ->name('attachments.show');
        Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');

        // ------------------------------------------------------------------
        // Bank Reconciliation
        // ------------------------------------------------------------------
        Route::get ('bank-reconciliation',                              [BankReconciliationController::class, 'index'])   ->name('bank-reconciliation.index');
        Route::get ('bank-reconciliation/create',                       [BankReconciliationController::class, 'create'])  ->name('bank-reconciliation.create');
        Route::post('bank-reconciliation',                              [BankReconciliationController::class, 'store'])   ->name('bank-reconciliation.store');
        Route::get ('bank-reconciliation/{bankStatement}',              [BankReconciliationController::class, 'show'])    ->name('bank-reconciliation.show');
        Route::post('bank-reconciliation/{bankStatement}/match',        [BankReconciliationController::class, 'match'])   ->name('bank-reconciliation.match');
        Route::post('bank-reconciliation/{bankStatement}/unmatch',      [BankReconciliationController::class, 'unmatch']) ->name('bank-reconciliation.unmatch');
        Route::post('bank-reconciliation/{bankStatement}/complete',     [BankReconciliationController::class, 'complete'])->name('bank-reconciliation.complete');

        // ------------------------------------------------------------------
        // Fiscal Year Close
        // ------------------------------------------------------------------
        Route::get ('fiscal-years',                             [FiscalYearController::class, 'index']) ->name('fiscal-years.index');
        Route::post('fiscal-years',                             [FiscalYearController::class, 'store']) ->name('fiscal-years.store');
        Route::post('fiscal-years/{fiscalYear}/close',          [FiscalYearController::class, 'close']) ->name('fiscal-years.close');

        // ------------------------------------------------------------------
        // Company Settings
        // ------------------------------------------------------------------
        Route::get ('settings', [CompanySettingsController::class, 'index']) ->name('settings.index');
        Route::patch('settings', [CompanySettingsController::class, 'update'])->name('settings.update');

        // ------------------------------------------------------------------
        // Employees
        // ------------------------------------------------------------------
        Route::resource('employees', EmployeeController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);

        Route::post('employees/{employee}/toggle', [EmployeeController::class, 'toggle'])
            ->name('employees.toggle');

        Route::post('employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])
            ->name('employees.reset-password');

        // ------------------------------------------------------------------
        // Payroll
        // ------------------------------------------------------------------
        Route::get ('payroll',                                                     [PayrollController::class, 'index'])    ->name('payroll.index');
        Route::get ('payroll/create',                                              [PayrollController::class, 'create'])   ->name('payroll.create');
        Route::post('payroll',                                                     [PayrollController::class, 'store'])    ->name('payroll.store');
        Route::get ('payroll/{payrollRun}',                                        [PayrollController::class, 'show'])     ->name('payroll.show');
        Route::get ('payroll/{payrollRun}/lines/{payrollLine}/edit',               [PayrollController::class, 'editLine']) ->name('payroll.edit-line');
        Route::post('payroll/{payrollRun}/lines/{payrollLine}',                    [PayrollController::class, 'updateLine'])->name('payroll.update-line');
        Route::post('payroll/{payrollRun}/approve',                                [PayrollController::class, 'approve'])  ->name('payroll.approve');

        // ------------------------------------------------------------------
        // Inventory
        // ------------------------------------------------------------------
        Route::get ('inventory',                          [InventoryController::class, 'index'])    ->name('inventory.index');
        Route::get ('inventory/movements',                [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::get ('inventory/{product}',                [InventoryController::class, 'show'])     ->name('inventory.show');
        Route::post('inventory/{product}/adjust',         [InventoryController::class, 'adjust'])   ->name('inventory.adjust');

        // ------------------------------------------------------------------
        // Products & Services Catalog
        // ------------------------------------------------------------------
        // search must be registered before the resource to avoid {product} capture
        Route::get('products/search', [ProductController::class, 'search'])
            ->name('products.search');

        Route::resource('products', ProductController::class)
            ->only(['index', 'create', 'store', 'edit', 'update']);

        Route::post('products/{product}/toggle', [ProductController::class, 'toggle'])
            ->name('products.toggle');

        // ------------------------------------------------------------------
        // Users (admin only — enforced via Gate inside controller)
        // ------------------------------------------------------------------
        Route::resource('users', UserController::class)
            ->except(['show']);

        // ------------------------------------------------------------------
        // Budget Management
        // ------------------------------------------------------------------
        Route::get ('budget',                           [BudgetController::class, 'index'])    ->name('budget.index');
        Route::get ('budget/create',                    [BudgetController::class, 'create'])   ->name('budget.create');
        Route::post('budget',                           [BudgetController::class, 'store'])    ->name('budget.store');
        Route::get ('budget/{budget}',                  [BudgetController::class, 'show'])     ->name('budget.show');
        Route::post('budget/{budget}/lines',            [BudgetController::class, 'addLines']) ->name('budget.add-lines');
        Route::post('budget/{budget}/activate',         [BudgetController::class, 'activate'])->name('budget.activate');
        Route::post('budget/{budget}/close',            [BudgetController::class, 'close'])   ->name('budget.close');

        // ------------------------------------------------------------------
        // Recurring Journal Entries
        // ------------------------------------------------------------------
        Route::get ('recurring',                        [RecurringEntryController::class, 'index'])  ->name('recurring.index');
        Route::get ('recurring/create',                 [RecurringEntryController::class, 'create']) ->name('recurring.create');
        Route::post('recurring',                        [RecurringEntryController::class, 'store'])  ->name('recurring.store');
        Route::post('recurring/run-due',                [RecurringEntryController::class, 'runDue']) ->name('recurring.run-due');
        Route::get ('recurring/{recurringEntry}',       [RecurringEntryController::class, 'show'])   ->name('recurring.show');
        Route::post('recurring/{recurringEntry}/toggle',[RecurringEntryController::class, 'toggle']) ->name('recurring.toggle');

        // ------------------------------------------------------------------
        // Leave Management (admin)
        // ------------------------------------------------------------------
        Route::get ('leaves',                              [LeaveManagementController::class, 'index'])      ->name('leaves.index');
        // Static sub-paths before dynamic {leave} parameter
        Route::get ('leaves/types',                        [LeaveManagementController::class, 'typesIndex']) ->name('leaves.types');
        Route::post('leaves/types',                        [LeaveManagementController::class, 'typesStore']) ->name('leaves.types.store');
        Route::post('leaves/types/{leaveType}/toggle',     [LeaveManagementController::class, 'typesToggle'])->name('leaves.types.toggle');

        Route::get ('leaves/{leave}',                      [LeaveManagementController::class, 'show'])       ->name('leaves.show');
        Route::post('leaves/{leave}/approve',              [LeaveManagementController::class, 'approve'])    ->name('leaves.approve');
        Route::post('leaves/{leave}/reject',               [LeaveManagementController::class, 'reject'])     ->name('leaves.reject');

        // ------------------------------------------------------------------
        // Quotations (عروض الأسعار)
        // ------------------------------------------------------------------
        Route::get ('quotations',                          [QuotationController::class, 'index'])          ->name('quotations.index');
        Route::get ('quotations/create',                   [QuotationController::class, 'create'])         ->name('quotations.create');
        Route::post('quotations',                          [QuotationController::class, 'store'])          ->name('quotations.store');
        Route::get ('quotations/{quotation}',              [QuotationController::class, 'show'])           ->name('quotations.show');
        Route::get ('quotations/{quotation}/edit',         [QuotationController::class, 'edit'])           ->name('quotations.edit');
        Route::put ('quotations/{quotation}',              [QuotationController::class, 'update'])         ->name('quotations.update');
        Route::post('quotations/{quotation}/send',         [QuotationController::class, 'send'])           ->name('quotations.send');
        Route::post('quotations/{quotation}/accept',       [QuotationController::class, 'accept'])         ->name('quotations.accept');
        Route::post('quotations/{quotation}/reject',       [QuotationController::class, 'reject'])         ->name('quotations.reject');
        Route::post('quotations/{quotation}/convert',      [QuotationController::class, 'convertToInvoice'])->name('quotations.convert');

        // ------------------------------------------------------------------
        // Purchase Orders (أوامر الشراء)
        // ------------------------------------------------------------------
        Route::get ('purchase-orders',                     [PurchaseOrderController::class, 'index'])      ->name('purchase-orders.index');
        Route::get ('purchase-orders/create',              [PurchaseOrderController::class, 'create'])     ->name('purchase-orders.create');
        Route::post('purchase-orders',                     [PurchaseOrderController::class, 'store'])      ->name('purchase-orders.store');
        Route::get ('purchase-orders/{purchaseOrder}',     [PurchaseOrderController::class, 'show'])       ->name('purchase-orders.show');
        Route::get ('purchase-orders/{purchaseOrder}/edit',[PurchaseOrderController::class, 'edit'])       ->name('purchase-orders.edit');
        Route::put ('purchase-orders/{purchaseOrder}',     [PurchaseOrderController::class, 'update'])     ->name('purchase-orders.update');
        Route::post('purchase-orders/{purchaseOrder}/send',   [PurchaseOrderController::class, 'send'])   ->name('purchase-orders.send');
        Route::post('purchase-orders/{purchaseOrder}/receive',[PurchaseOrderController::class, 'receive']) ->name('purchase-orders.receive');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])  ->name('purchase-orders.cancel');
        Route::post('purchase-orders/{purchaseOrder}/convert',[PurchaseOrderController::class, 'convertToInvoice'])->name('purchase-orders.convert');
    });
