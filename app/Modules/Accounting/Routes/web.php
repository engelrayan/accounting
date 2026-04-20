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
use App\Modules\Accounting\Http\Controllers\ModuleSettingsController;
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
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('module:dashboard')
            ->name('dashboard');

        // ------------------------------------------------------------------
        // Accounts
        // ------------------------------------------------------------------
        Route::resource('accounts', AccountController::class)
            ->except(['show'])
            ->middleware('module:accounts');

        Route::patch('accounts/{account}/toggle-active', [AccountController::class, 'toggleActive'])
            ->middleware('module:accounts')
            ->name('accounts.toggle-active');

        // ------------------------------------------------------------------
        // Transactions (business-friendly UI)
        // ------------------------------------------------------------------
        Route::resource('transactions', TransactionController::class)
            ->only(['index', 'create', 'store'])
            ->middleware('module:transactions');

        // ------------------------------------------------------------------
        // Journal Entries — READ-ONLY audit log (system-generated only)
        // Manual create / post / reverse removed intentionally.
        // ------------------------------------------------------------------
        Route::resource('journal-entries', JournalEntryController::class)
            ->only(['index', 'show'])
            ->middleware('module:journal_entries');

        // ------------------------------------------------------------------
        // Partners
        // ------------------------------------------------------------------
        Route::resource('partners', PartnerController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware('module:partners');

        Route::post('partners/{partner}/add-capital', [PartnerController::class, 'addCapital'])
            ->middleware('module:partners')
            ->name('partners.add-capital');

        Route::post('partners/{partner}/withdraw', [PartnerController::class, 'withdraw'])
            ->middleware('module:partners')
            ->name('partners.withdraw');

        // ------------------------------------------------------------------
        // Assets
        // ------------------------------------------------------------------
        Route::resource('assets', AssetController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware('module:assets');

        Route::post('assets/{asset}/depreciate', [AssetController::class, 'depreciate'])
            ->middleware('module:assets')
            ->name('assets.depreciate');

        // ------------------------------------------------------------------
        // Reports
        // ------------------------------------------------------------------
        Route::prefix('reports')->name('reports.')->middleware('module:reports')->group(function () {
            Route::get('income-expense',          [ReportController::class, 'incomeExpense'])->middleware('module:report_income_expense')->name('income-expense');
            Route::get('cashier-sales',           [ReportController::class, 'cashierSales'])->middleware('module:report_cashier_sales')->name('cashier-sales');
            Route::get('trial-balance',           [ReportController::class, 'trialBalance'])->middleware('module:report_trial_balance')->name('trial-balance');
            Route::get('account-ledger/{account}',[ReportController::class, 'accountLedger'])->name('account-ledger');
            Route::get('profit-loss',             [ReportController::class, 'profitLoss'])->middleware('module:report_profit_loss')->name('profit-loss');
            Route::get('balance-sheet',           [BalanceSheetController::class, 'index'])->middleware('module:report_balance_sheet')->name('balance-sheet');
            Route::get('ar-aging',                        [ReportController::class, 'arAging'])->middleware('module:report_ar_aging')->name('ar-aging');
            Route::get('customer-statement/{customer}',   [ReportController::class, 'customerStatement']) ->name('customer-statement');
            Route::get('ap-aging',                        [ReportController::class, 'apAging'])->middleware('module:report_ap_aging')->name('ap-aging');
            Route::get('vendor-statement/{vendor}',       [ReportController::class, 'vendorStatement'])   ->name('vendor-statement');
            Route::get('vat-report',                      [ReportController::class, 'vatReport'])->middleware('module:report_vat')->name('vat-report');
        });

        // ------------------------------------------------------------------
        // Customers (AR base)
        // ------------------------------------------------------------------
        Route::resource('customers', CustomerController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware('module:customers');

        Route::post('customers/{customer}/payments',  [CustomerController::class, 'storePayment'])
            ->middleware('module:customers')
            ->name('customers.payments.store');

        Route::post('customers/{customer}/settle',    [CustomerController::class, 'settle'])
            ->middleware('module:customers')
            ->name('customers.settle');

        // ------------------------------------------------------------------
        // Invoices (formal printable invoices with QR)
        // ------------------------------------------------------------------
        Route::resource('invoices', InvoiceController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware('module:invoices');

        Route::get('pos', [PosController::class, 'create'])->middleware('module:pos')->name('pos.create');
        Route::post('pos', [PosController::class, 'store'])->middleware('module:pos')->name('pos.store');
        Route::get('pos/drawer', [PosController::class, 'drawer'])->middleware('module:pos')->name('pos.drawer');
        Route::get('pos/receipt/{invoice}', [PosController::class, 'receipt'])->middleware('module:pos')->name('pos.receipt');

        Route::get ('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
            ->middleware('module:invoices')
            ->name('invoices.pdf');

        Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'recordPayment'])
            ->middleware('module:invoices')
            ->name('invoices.pay');

        Route::post('invoices/{invoice}/credit-note', [CreditNoteController::class, 'storeForInvoice'])
            ->middleware('module:invoices')
            ->name('invoices.credit-note');

        // ------------------------------------------------------------------
        // Credit Notes
        // ------------------------------------------------------------------
        Route::resource('credit-notes', CreditNoteController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware('module:invoices');

        Route::get('credit-notes/{creditNote}/pdf', [CreditNoteController::class, 'downloadPdf'])
            ->middleware('module:invoices')
            ->name('credit-notes.pdf');

        // ------------------------------------------------------------------
        // Payments (history + filtering)
        // ------------------------------------------------------------------
        Route::get('payments', [PaymentController::class, 'index'])
            ->middleware('module:payments')
            ->name('payments.index');

        // ------------------------------------------------------------------
        // Vendors (AP base)
        // ------------------------------------------------------------------
        Route::resource('vendors', VendorController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware('module:vendors');

        Route::post('vendors/{vendor}/payments', [VendorController::class, 'storePayment'])
            ->middleware('module:vendors')
            ->name('vendors.payments.store');

        Route::post('vendors/{vendor}/settle',   [VendorController::class, 'settle'])
            ->middleware('module:vendors')
            ->name('vendors.settle');

        // ------------------------------------------------------------------
        // Purchase Invoices
        // ------------------------------------------------------------------
        Route::resource('purchase-invoices', PurchaseInvoiceController::class)
            ->only(['index', 'create', 'store', 'show'])
            ->middleware('module:purchase_invoices');

        Route::get ('purchase-invoices/{purchaseInvoice}/pdf', [PurchaseInvoiceController::class, 'downloadPdf'])
            ->middleware('module:purchase_invoices')
            ->name('purchase-invoices.pdf');

        Route::post('purchase-invoices/{purchaseInvoice}/pay', [PurchaseInvoiceController::class, 'recordPayment'])
            ->middleware('module:purchase_invoices')
            ->name('purchase-invoices.pay');

        // ------------------------------------------------------------------
        // Purchase Payments (history + filtering)
        // ------------------------------------------------------------------
        Route::get('purchase-payments', [PurchasePaymentController::class, 'index'])
            ->middleware('module:purchase_payments')
            ->name('purchase-payments.index');

        // ------------------------------------------------------------------
        // Attachments (secure proxy — enforces company isolation)
        // ------------------------------------------------------------------
        Route::get('attachments/{attachment}',          [AttachmentController::class, 'show'])    ->name('attachments.show');
        Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');

        // ------------------------------------------------------------------
        // Bank Reconciliation
        // ------------------------------------------------------------------
        Route::get ('bank-reconciliation',                              [BankReconciliationController::class, 'index'])   ->middleware('module:bank_reconciliation')->name('bank-reconciliation.index');
        Route::get ('bank-reconciliation/create',                       [BankReconciliationController::class, 'create'])  ->middleware('module:bank_reconciliation')->name('bank-reconciliation.create');
        Route::post('bank-reconciliation',                              [BankReconciliationController::class, 'store'])   ->middleware('module:bank_reconciliation')->name('bank-reconciliation.store');
        Route::get ('bank-reconciliation/{bankStatement}',              [BankReconciliationController::class, 'show'])    ->middleware('module:bank_reconciliation')->name('bank-reconciliation.show');
        Route::post('bank-reconciliation/{bankStatement}/match',        [BankReconciliationController::class, 'match'])   ->middleware('module:bank_reconciliation')->name('bank-reconciliation.match');
        Route::post('bank-reconciliation/{bankStatement}/unmatch',      [BankReconciliationController::class, 'unmatch']) ->middleware('module:bank_reconciliation')->name('bank-reconciliation.unmatch');
        Route::post('bank-reconciliation/{bankStatement}/complete',     [BankReconciliationController::class, 'complete'])->middleware('module:bank_reconciliation')->name('bank-reconciliation.complete');

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
            ->only(['index', 'create', 'store', 'edit', 'update'])
            ->middleware('module:employees');

        Route::post('employees/{employee}/toggle', [EmployeeController::class, 'toggle'])
            ->middleware('module:employees')
            ->name('employees.toggle');

        Route::post('employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])
            ->middleware('module:employees')
            ->name('employees.reset-password');

        // ------------------------------------------------------------------
        // Payroll
        // ------------------------------------------------------------------
        Route::get ('payroll',                                                     [PayrollController::class, 'index'])    ->middleware('module:payroll')->name('payroll.index');
        Route::get ('payroll/create',                                              [PayrollController::class, 'create'])   ->middleware('module:payroll')->name('payroll.create');
        Route::post('payroll',                                                     [PayrollController::class, 'store'])    ->middleware('module:payroll')->name('payroll.store');
        Route::get ('payroll/{payrollRun}',                                        [PayrollController::class, 'show'])     ->middleware('module:payroll')->name('payroll.show');
        Route::get ('payroll/{payrollRun}/lines/{payrollLine}/edit',               [PayrollController::class, 'editLine']) ->middleware('module:payroll')->name('payroll.edit-line');
        Route::post('payroll/{payrollRun}/lines/{payrollLine}',                    [PayrollController::class, 'updateLine'])->middleware('module:payroll')->name('payroll.update-line');
        Route::post('payroll/{payrollRun}/approve',                                [PayrollController::class, 'approve'])  ->middleware('module:payroll')->name('payroll.approve');

        // ------------------------------------------------------------------
        // Inventory
        // ------------------------------------------------------------------
        Route::get ('inventory',                          [InventoryController::class, 'index'])    ->middleware('module:inventory')->name('inventory.index');
        Route::get ('inventory/movements',                [InventoryController::class, 'movements'])->middleware('module:inventory')->name('inventory.movements');
        Route::get ('inventory/{product}',                [InventoryController::class, 'show'])     ->middleware('module:inventory')->name('inventory.show');
        Route::post('inventory/{product}/adjust',         [InventoryController::class, 'adjust'])   ->middleware('module:inventory')->name('inventory.adjust');

        // ------------------------------------------------------------------
        // Products & Services Catalog
        // ------------------------------------------------------------------
        // search must be registered before the resource to avoid {product} capture
        Route::get('products/search', [ProductController::class, 'search'])
            ->middleware('module:products')
            ->name('products.search');

        Route::resource('products', ProductController::class)
            ->only(['index', 'create', 'store', 'edit', 'update'])
            ->middleware('module:products');

        Route::post('products/{product}/toggle', [ProductController::class, 'toggle'])
            ->middleware('module:products')
            ->name('products.toggle');

        // ------------------------------------------------------------------
        // Users (admin only — enforced via Gate inside controller)
        // ------------------------------------------------------------------
        Route::resource('users', UserController::class)
            ->except(['show']);

        // ------------------------------------------------------------------
        // Budget Management
        // ------------------------------------------------------------------
        Route::get ('budget',                           [BudgetController::class, 'index'])    ->middleware('module:budget')->name('budget.index');
        Route::get ('budget/create',                    [BudgetController::class, 'create'])   ->middleware('module:budget')->name('budget.create');
        Route::post('budget',                           [BudgetController::class, 'store'])    ->middleware('module:budget')->name('budget.store');
        Route::get ('budget/{budget}',                  [BudgetController::class, 'show'])     ->middleware('module:budget')->name('budget.show');
        Route::post('budget/{budget}/lines',            [BudgetController::class, 'addLines']) ->middleware('module:budget')->name('budget.add-lines');
        Route::post('budget/{budget}/activate',         [BudgetController::class, 'activate'])->middleware('module:budget')->name('budget.activate');
        Route::post('budget/{budget}/close',            [BudgetController::class, 'close'])   ->middleware('module:budget')->name('budget.close');

        // ------------------------------------------------------------------
        // Recurring Journal Entries
        // ------------------------------------------------------------------
        Route::get ('recurring',                        [RecurringEntryController::class, 'index'])  ->middleware('module:recurring')->name('recurring.index');
        Route::get ('recurring/create',                 [RecurringEntryController::class, 'create']) ->middleware('module:recurring')->name('recurring.create');
        Route::post('recurring',                        [RecurringEntryController::class, 'store'])  ->middleware('module:recurring')->name('recurring.store');
        Route::post('recurring/run-due',                [RecurringEntryController::class, 'runDue']) ->middleware('module:recurring')->name('recurring.run-due');
        Route::get ('recurring/{recurringEntry}',       [RecurringEntryController::class, 'show'])   ->middleware('module:recurring')->name('recurring.show');
        Route::post('recurring/{recurringEntry}/toggle',[RecurringEntryController::class, 'toggle']) ->middleware('module:recurring')->name('recurring.toggle');

        // ------------------------------------------------------------------
        // Leave Management (admin)
        // ------------------------------------------------------------------
        Route::get ('leaves',                              [LeaveManagementController::class, 'index'])      ->middleware('module:leaves')->name('leaves.index');
        // Static sub-paths before dynamic {leave} parameter
        Route::get ('leaves/types',                        [LeaveManagementController::class, 'typesIndex']) ->middleware('module:leaves')->name('leaves.types');
        Route::post('leaves/types',                        [LeaveManagementController::class, 'typesStore']) ->middleware('module:leaves')->name('leaves.types.store');
        Route::post('leaves/types/{leaveType}/toggle',     [LeaveManagementController::class, 'typesToggle'])->middleware('module:leaves')->name('leaves.types.toggle');

        Route::get ('leaves/{leave}',                      [LeaveManagementController::class, 'show'])       ->middleware('module:leaves')->name('leaves.show');
        Route::post('leaves/{leave}/approve',              [LeaveManagementController::class, 'approve'])    ->middleware('module:leaves')->name('leaves.approve');
        Route::post('leaves/{leave}/reject',               [LeaveManagementController::class, 'reject'])     ->middleware('module:leaves')->name('leaves.reject');

        // ------------------------------------------------------------------
        // Quotations (عروض الأسعار)
        // ------------------------------------------------------------------
        Route::get ('quotations',                          [QuotationController::class, 'index'])          ->middleware('module:quotations')->name('quotations.index');
        Route::get ('quotations/create',                   [QuotationController::class, 'create'])         ->middleware('module:quotations')->name('quotations.create');
        Route::post('quotations',                          [QuotationController::class, 'store'])          ->middleware('module:quotations')->name('quotations.store');
        Route::get ('quotations/{quotation}',              [QuotationController::class, 'show'])           ->middleware('module:quotations')->name('quotations.show');
        Route::get ('quotations/{quotation}/edit',         [QuotationController::class, 'edit'])           ->middleware('module:quotations')->name('quotations.edit');
        Route::put ('quotations/{quotation}',              [QuotationController::class, 'update'])         ->middleware('module:quotations')->name('quotations.update');
        Route::post('quotations/{quotation}/send',         [QuotationController::class, 'send'])           ->middleware('module:quotations')->name('quotations.send');
        Route::post('quotations/{quotation}/accept',       [QuotationController::class, 'accept'])         ->middleware('module:quotations')->name('quotations.accept');
        Route::post('quotations/{quotation}/reject',       [QuotationController::class, 'reject'])         ->middleware('module:quotations')->name('quotations.reject');
        Route::post('quotations/{quotation}/convert',      [QuotationController::class, 'convertToInvoice'])->middleware('module:quotations')->name('quotations.convert');

        // ------------------------------------------------------------------
        // Purchase Orders (أوامر الشراء)
        // ------------------------------------------------------------------
        Route::get ('purchase-orders',                     [PurchaseOrderController::class, 'index'])      ->middleware('module:purchase_orders')->name('purchase-orders.index');
        Route::get ('purchase-orders/create',              [PurchaseOrderController::class, 'create'])     ->middleware('module:purchase_orders')->name('purchase-orders.create');
        Route::post('purchase-orders',                     [PurchaseOrderController::class, 'store'])      ->middleware('module:purchase_orders')->name('purchase-orders.store');
        Route::get ('purchase-orders/{purchaseOrder}',     [PurchaseOrderController::class, 'show'])       ->middleware('module:purchase_orders')->name('purchase-orders.show');
        Route::get ('purchase-orders/{purchaseOrder}/edit',[PurchaseOrderController::class, 'edit'])       ->middleware('module:purchase_orders')->name('purchase-orders.edit');
        Route::put ('purchase-orders/{purchaseOrder}',     [PurchaseOrderController::class, 'update'])     ->middleware('module:purchase_orders')->name('purchase-orders.update');
        Route::post('purchase-orders/{purchaseOrder}/send',   [PurchaseOrderController::class, 'send'])   ->middleware('module:purchase_orders')->name('purchase-orders.send');
        Route::post('purchase-orders/{purchaseOrder}/receive',[PurchaseOrderController::class, 'receive']) ->middleware('module:purchase_orders')->name('purchase-orders.receive');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])  ->middleware('module:purchase_orders')->name('purchase-orders.cancel');
        Route::post('purchase-orders/{purchaseOrder}/convert',[PurchaseOrderController::class, 'convertToInvoice'])->middleware('module:purchase_orders')->name('purchase-orders.convert');
    });

Route::prefix('admin/settings/modules')
    ->name('accounting.settings.modules.')
    ->middleware(['web', 'auth'])
    ->group(function () {
        Route::get('/', [ModuleSettingsController::class, 'index'])->name('index');
        Route::patch('/', [ModuleSettingsController::class, 'update'])->name('update');
        Route::delete('/', [ModuleSettingsController::class, 'reset'])->name('reset');
    });
