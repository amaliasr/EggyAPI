<?php

use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AppSettingController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CorrectionRequestController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DailyProductionController;
use App\Http\Controllers\Api\V1\EggCategoryController;
use App\Http\Controllers\Api\V1\EggPickupController;
use App\Http\Controllers\Api\V1\EggReturnController;
use App\Http\Controllers\Api\V1\EmployeeController;
use App\Http\Controllers\Api\V1\EmployeeIncentiveController;
use App\Http\Controllers\Api\V1\FarmSourceController;
use App\Http\Controllers\Api\V1\IncentiveProgramController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PriceController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SalesTargetController;
use App\Http\Controllers\Api\V1\StockController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — v1
|--------------------------------------------------------------------------
| 13 modul sesuai §8 dokumen rancangan Tahap 1: auth, karyawan, master data,
| harga telur, produksi harian, pengambilan telur, retur, penjualan, stok,
| insentif, target penjualan, laporan, pengumuman+notifikasi+koreksi+setting.
| Route publik hanya /auth/login — semua yang lain wajib token Sanctum.
*/

Route::prefix('v1')->group(function () {

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/fcm-token', [AuthController::class, 'updateFcmToken']);

        // Karyawan (atasan saja)
        Route::middleware('role:atasan')->group(function () {
            Route::apiResource('employees', EmployeeController::class)->except(['destroy']);
            Route::patch('/employees/{employee}/status', [EmployeeController::class, 'setStatus']);
        });

        // Master data — lihat: semua role; ubah: atasan
        Route::get('/egg-categories', [EggCategoryController::class, 'index']);
        Route::get('/egg-categories/{eggCategory}', [EggCategoryController::class, 'show']);
        Route::get('/farm-sources', [FarmSourceController::class, 'index']);
        Route::get('/farm-sources/{farmSource}', [FarmSourceController::class, 'show']);

        Route::middleware('role:atasan')->group(function () {
            Route::post('/egg-categories', [EggCategoryController::class, 'store']);
            Route::put('/egg-categories/{eggCategory}', [EggCategoryController::class, 'update']);
            Route::patch('/egg-categories/{eggCategory}/status', [EggCategoryController::class, 'setStatus']);
            Route::post('/farm-sources', [FarmSourceController::class, 'store']);
            Route::put('/farm-sources/{farmSource}', [FarmSourceController::class, 'update']);
            Route::patch('/farm-sources/{farmSource}/status', [FarmSourceController::class, 'setStatus']);
        });

        // Harga Telur — lihat: semua role; kelola: atasan
        Route::get('/prices', [PriceController::class, 'index']);
        Route::get('/prices/active', [PriceController::class, 'active']);
        Route::get('/prices/{price}', [PriceController::class, 'show']);

        Route::middleware('role:atasan')->group(function () {
            Route::post('/prices', [PriceController::class, 'store']);
            Route::put('/prices/{price}', [PriceController::class, 'update']);
            Route::delete('/prices/{price}', [PriceController::class, 'destroy']);
            Route::post('/prices/activate-due', [PriceController::class, 'activateDue']);
        });

        // Produksi harian — karyawan catat & lihat milik sendiri, atasan lihat semua
        Route::get('/daily-productions', [DailyProductionController::class, 'index']);
        Route::post('/daily-productions', [DailyProductionController::class, 'store']);
        Route::get('/daily-productions/{dailyProduction}', [DailyProductionController::class, 'show']);
        Route::put('/daily-productions/{dailyProduction}', [DailyProductionController::class, 'update']);
        Route::patch('/daily-productions/{dailyProduction}/lock', [DailyProductionController::class, 'setLocked'])
            ->middleware('role:atasan');

        // Pengambilan telur
        Route::get('/egg-pickups', [EggPickupController::class, 'index']);
        Route::post('/egg-pickups', [EggPickupController::class, 'store']);
        Route::get('/egg-pickups/{eggPickup}', [EggPickupController::class, 'show']);

        // Retur
        Route::get('/egg-returns', [EggReturnController::class, 'index']);
        Route::post('/egg-returns', [EggReturnController::class, 'store']);
        Route::get('/egg-returns/{eggReturn}', [EggReturnController::class, 'show']);

        // Pelanggan — dipakai autocomplete/quick-add di form Penjualan & Retur. Semua role
        // boleh cari & tambah (karyawan perlu menambahkan pelanggan baru langsung di lapangan).
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update']);

        // Penjualan
        Route::get('/sales', [SaleController::class, 'index']);
        Route::post('/sales', [SaleController::class, 'store']);
        Route::get('/sales/{sale}', [SaleController::class, 'show']);
        Route::post('/sales/{sale}/payments', [SaleController::class, 'addPayment']);

        // Stok
        Route::get('/stock/balances', [StockController::class, 'balances']);
        Route::get('/stock/movements', [StockController::class, 'movements']);
        Route::post('/stock/adjustments', [StockController::class, 'adjust'])->middleware('role:atasan');

        // Insentif — kelola & lihat semua: atasan; karyawan hanya lihat miliknya sendiri
        Route::get('/my-incentives', [EmployeeIncentiveController::class, 'mine']);

        Route::middleware('role:atasan')->group(function () {
            Route::apiResource('incentive-programs', IncentiveProgramController::class)->except(['destroy']);
            Route::patch('/incentive-programs/{incentiveProgram}/status', [IncentiveProgramController::class, 'setStatus']);
            Route::post('/incentive-programs/{incentiveProgram}/calculate', [IncentiveProgramController::class, 'calculate']);
            Route::post('/employee-incentives/{employeeIncentive}/approve', [EmployeeIncentiveController::class, 'approve']);
            Route::post('/employee-incentives/{employeeIncentive}/pay', [EmployeeIncentiveController::class, 'markPaid']);
        });

        // Target penjualan — kelola: atasan; lihat: sesuai kepemilikan (dicek di controller)
        Route::get('/sales-targets', [SalesTargetController::class, 'index']);
        Route::get('/sales-targets/{salesTarget}', [SalesTargetController::class, 'show']);

        Route::middleware('role:atasan')->group(function () {
            Route::post('/sales-targets', [SalesTargetController::class, 'store']);
            Route::put('/sales-targets/{salesTarget}', [SalesTargetController::class, 'update']);
            Route::delete('/sales-targets/{salesTarget}', [SalesTargetController::class, 'destroy']);
        });

        // Laporan (read-only)
        Route::get('/reports/production', [ReportController::class, 'production']);
        Route::get('/reports/sales', [ReportController::class, 'sales']);
        Route::get('/reports/stock', [ReportController::class, 'stock']);
        Route::get('/reports/incentive-programs/{incentiveProgram}', [ReportController::class, 'incentives'])
            ->middleware('role:atasan');

        // Pengumuman — lihat: semua role; kelola: atasan
        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show']);

        Route::middleware('role:atasan')->group(function () {
            Route::post('/announcements', [AnnouncementController::class, 'store']);
            Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
            Route::patch('/announcements/{announcement}/status', [AnnouncementController::class, 'setStatus']);
        });

        // Notifikasi in-app (milik sendiri)
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);

        // Koreksi — ajukan: semua role; tinjau: atasan
        Route::get('/correction-requests', [CorrectionRequestController::class, 'index']);
        Route::post('/correction-requests', [CorrectionRequestController::class, 'store']);
        Route::get('/correction-requests/{correctionRequest}', [CorrectionRequestController::class, 'show']);
        Route::post('/correction-requests/{correctionRequest}/review', [CorrectionRequestController::class, 'review'])
            ->middleware('role:atasan');

        // Pengaturan aplikasi & audit log — atasan saja
        Route::middleware('role:atasan')->group(function () {
            Route::get('/settings', [AppSettingController::class, 'index']);
            Route::get('/settings/{key}', [AppSettingController::class, 'show']);
            Route::put('/settings/{key}', [AppSettingController::class, 'update']);
            Route::get('/audit-logs', [AuditLogController::class, 'index']);
        });
    });
});
