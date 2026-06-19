<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NodeController;

Route::get('/', function () {
    return redirect()->route('register');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('products/{type}/{id}', [DashboardController::class, 'showProduct'])->name('products.show');
    
    // Nodes Market Routes - Redirected to Dashboard
    Route::get('nodes', function () {
        return redirect()->route('dashboard');
    })->name('nodes.index');
    Route::post('nodes/{id}/rent', [NodeController::class, 'rent'])->name('nodes.rent');
    
    // Real-Time Generation API & UI Routes
    Route::get('generate', [NodeController::class, 'generatePage'])->name('generate.page');
    Route::post('generation/start', [NodeController::class, 'startGeneration'])->name('generation.start');
    Route::post('generation/{id}/claim', [NodeController::class, 'claimProfit'])->name('generation.claim');

    // ARM Vault Routes - Redirected to Dashboard
    Route::get('vaults', function () {
        return redirect()->route('dashboard');
    })->name('vaults.index');
    Route::post('vaults/{id}/invest', [\App\Http\Controllers\VaultController::class, 'invest'])->name('vaults.invest');

    // Wallet Routes
    Route::get('wallet', [\App\Http\Controllers\WalletController::class, 'index'])->name('wallet.index');
    Route::get('recharger', [\App\Http\Controllers\WalletController::class, 'rechargerPage'])->name('wallet.recharger');
    Route::get('retirer', [\App\Http\Controllers\WalletController::class, 'retirerPage'])->name('wallet.retirer');
    Route::get('recharges', [\App\Http\Controllers\WalletController::class, 'rechargesHistoryPage'])->name('wallet.recharges');
    Route::get('retraits', [\App\Http\Controllers\WalletController::class, 'retraitsHistoryPage'])->name('wallet.retraits');
    Route::post('wallet/deposit', [\App\Http\Controllers\WalletController::class, 'deposit'])->name('wallet.deposit');
    Route::post('wallet/withdraw', [\App\Http\Controllers\WalletController::class, 'withdraw'])->name('wallet.withdraw');

    // Team & Share Routes
    Route::get('team', [\App\Http\Controllers\TeamController::class, 'index'])->name('team.index');
    Route::get('share', [\App\Http\Controllers\TeamController::class, 'share'])->name('team.share');

    // VIP Routes
    Route::get('vip', [\App\Http\Controllers\VIPController::class, 'index'])->name('vip.index');

    // AVIP Products Routes
    Route::get('avip-products', function () {
        return redirect()->route('vip.index');
    });
    Route::post('avip-products/{id}/purchase', [\App\Http\Controllers\AVIPProductController::class, 'purchase'])->name('avip-products.purchase');
    Route::post('avip-products/claim-salary', [\App\Http\Controllers\AVIPProductController::class, 'claimSalary'])->name('avip-products.claim-salary');

    // Announcements Routes
    Route::get('announcements', [\App\Http\Controllers\AnnouncementController::class, 'index'])->name('announcements.index');

    // Commandes (Order History)
    Route::get('commandes', [DashboardController::class, 'commandes'])->name('commandes');



    // Gains (Detailed Earnings)
    Route::get('gains', [DashboardController::class, 'gains'])->name('gains');

    // Presentation (Enterprise UK-Cameroon Certificate)
    Route::get('presentation', [DashboardController::class, 'presentation'])->name('presentation');

    // Daily Check-In Route
    Route::post('daily-checkin', [DashboardController::class, 'checkin'])->name('daily-checkin');

    // Lucky Draw (Tirage) Routes
    Route::get('tirage', [DashboardController::class, 'tiragePage'])->name('tirage.page');
    Route::post('tirage/spin', [DashboardController::class, 'spinWheel'])->name('tirage.spin');

    // Gift Claim Route
    Route::post('gift/claim', [\App\Http\Controllers\WalletController::class, 'claimGift'])->name('gift.claim');

    // Admin Routes
    Route::middleware(\App\Http\Middleware\AdminMiddleware::class)->group(function () {
        Route::get('admin', [\App\Http\Controllers\AdminController::class, 'index'])->name('admin.index');
        Route::post('admin/node', [\App\Http\Controllers\AdminController::class, 'createNode'])->name('admin.node.create');
        Route::post('admin/avip-product', [\App\Http\Controllers\AdminController::class, 'createAvipProduct'])->name('admin.avipproduct.create');
        Route::post('admin/transaction/{id}/approve', [\App\Http\Controllers\AdminController::class, 'approveTransaction'])->name('admin.approve');
        Route::post('admin/transaction/{id}/reject', [\App\Http\Controllers\AdminController::class, 'rejectTransaction'])->name('admin.reject');
        Route::post('admin/user/{id}/update', [\App\Http\Controllers\AdminController::class, 'updateUser'])->name('admin.user.update');
        Route::delete('admin/user/{id}/delete', [\App\Http\Controllers\AdminController::class, 'deleteUser'])->name('admin.user.delete');
        Route::post('admin/gift-code', [\App\Http\Controllers\AdminController::class, 'createGiftCode'])->name('admin.giftcode.create');
        Route::delete('admin/gift-code/{id}', [\App\Http\Controllers\AdminController::class, 'deleteGiftCode'])->name('admin.giftcode.delete');
        Route::post('admin/node/{id}/update', [\App\Http\Controllers\AdminController::class, 'updateNode'])->name('admin.node.update');
        Route::delete('admin/node/{id}/delete', [\App\Http\Controllers\AdminController::class, 'deleteNode'])->name('admin.node.delete');
        Route::post('admin/avip-product/{id}/update', [\App\Http\Controllers\AdminController::class, 'updateAvipProduct'])->name('admin.avipproduct.update');
        Route::delete('admin/avip-product/{id}/delete', [\App\Http\Controllers\AdminController::class, 'deleteAvipProduct'])->name('admin.avipproduct.delete');
        
        // Admin Vault Plan CRUD Routes
        Route::post('admin/vault-plan', [\App\Http\Controllers\AdminController::class, 'createVaultPlan'])->name('admin.vaultplan.create');
        Route::post('admin/vault-plan/{id}/update', [\App\Http\Controllers\AdminController::class, 'updateVaultPlan'])->name('admin.vaultplan.update');
        Route::delete('admin/vault-plan/{id}/delete', [\App\Http\Controllers\AdminController::class, 'deleteVaultPlan'])->name('admin.vaultplan.delete');

        // Admin Announcements CRUD Routes
        Route::post('admin/announcement', [\App\Http\Controllers\AdminController::class, 'createAnnouncement'])->name('admin.announcement.create');
        Route::post('admin/announcement/{id}/update', [\App\Http\Controllers\AdminController::class, 'updateAnnouncement'])->name('admin.announcement.update');
        Route::delete('admin/announcement/{id}/delete', [\App\Http\Controllers\AdminController::class, 'deleteAnnouncement'])->name('admin.announcement.delete');

        // Admin Settings Route
        Route::post('admin/settings', [\App\Http\Controllers\AdminController::class, 'updateSettings'])->name('admin.settings.update');
    });

    // Fapshi Custom UI Routes
    Route::get('fapshi/pay/{reference}', [\App\Http\Controllers\WalletController::class, 'fapshiPayPage'])->name('fapshi.pay');
    Route::post('fapshi/check-status/{reference}', [\App\Http\Controllers\WalletController::class, 'checkFapshiStatus'])->name('fapshi.check-status');
});

Route::post('webhook/fapshi', [\App\Http\Controllers\WalletController::class, 'webhook'])->name('webhook.fapshi');

require __DIR__.'/settings.php';
