<?php

use Illuminate\Support\Facades\Route;
// use App\Models\Subcategory;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLogin;
use App\Http\Controllers\Admin\Auth\ProfileController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\users\UserController;
use App\Http\Controllers\Admin\Product\CategoriesController;
use App\Http\Controllers\Admin\Product\SubcategoryController;
use App\Http\Controllers\Admin\Product\ProductController;
use App\Http\Controllers\Admin\Order\OrderController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Frontend\FrontendArticleController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SslcommerzController;

// User routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [HomeController::class, 'products'])->name('products.index');
Route::get('/product/{id}', [HomeController::class, 'show'])->name('products.show');
Route::post('/product/{id}/comment', [HomeController::class, 'storeProductComment'])->name('product.comment')->middleware('auth');
Route::put('/product/blog-comments/{id}', [HomeController::class, 'updateProductComment'])->name('product.comment.update')->middleware('auth');
Route::delete('/product/blog-comments/{id}', [HomeController::class, 'deleteProductComment'])->name('product.comment.delete')->middleware('auth');
Route::post('/product/{id}/react', [HomeController::class, 'toggleProductReaction'])->name('product.react')->middleware('auth');
Route::get('/flash-deals', [HomeController::class, 'flashDeals'])->name('flash-deals');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/newsletter/subscribe', [\App\Http\Controllers\Frontend\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');

// Global Chat Routes
Route::get('/chat/fetch', [HomeController::class, 'fetchChat'])->name('chat.fetch');
Route::post('/chat/send', [HomeController::class, 'sendChat'])->name('chat.send');
Route::post('/chat/mark-read', [HomeController::class, 'markChatAsRead'])->name('chat.markRead');

Route::get('/articles', [FrontendArticleController::class, 'index'])->name('frontend.articles');
Route::get('/articles/{slug}', [FrontendArticleController::class, 'show'])->name('frontend.articles.show');
Route::post('/articles/{id}/comment', [FrontendArticleController::class, 'storeComment'])->name('frontend.articles.comment')->middleware('auth');
Route::put('/articles/blog-comments/{id}', [FrontendArticleController::class, 'updateComment'])->name('frontend.articles.comment.update')->middleware('auth');
Route::delete('/articles/blog-comments/{id}', [FrontendArticleController::class, 'deleteComment'])->name('frontend.articles.comment.delete')->middleware('auth');
Route::post('/articles/{id}/react', [FrontendArticleController::class, 'toggleReaction'])->name('frontend.articles.react')->middleware('auth');


Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');


// Protected checkout routes
Route::middleware('user')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'checkoutIndex'])->name('checkout.index');
    Route::post('/place-order', [CheckoutController::class, 'placeOrder'])->name('checkout.placeOrder');
});

Route::get('/orders/success/{order}', [CheckoutController::class, 'success'])->name('orders.success');
Route::get('/orders/{order}/invoice/download', [CheckoutController::class, 'downloadInvoice'])->name('orders.invoice.download')->middleware('user');

// Accept both GET and POST for success, fail, cancel
Route::match(['get','post'], '/ssl-success', [SslcommerzController::class, 'success'])->name('sslc.success');
Route::match(['get','post'], '/ssl-fail', [SslcommerzController::class, 'fail'])->name('sslc.failure');
Route::match(['get','post'], '/ssl-cancel', [SslcommerzController::class, 'cancel'])->name('sslc.cancel');

// IPN is always POST
Route::post('/ssl-ipn', [SslcommerzController::class, 'ipn'])->name('sslc.ipn');

// Pay route
Route::get('/ssl-pay/{orderId}', [SslcommerzController::class, 'pay'])->name('sslc.pay');

Route::get('/login', [AuthController::class, 'showAccountPage'])->name('user.auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('user.auth.register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');

// Password Recovery
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
Route::prefix('user')->middleware('user')->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'userDashboard'])->name('user.dashboard');
    Route::get('/profile', [UserDashboardController::class, 'profileIndex'])->name('user.profile');
    Route::put('/profile', [UserDashboardController::class, 'updateProfile'])->name('profile.update');
    //order routes
    Route::get('/orders', [UserDashboardController::class, 'userOrders'])->name('user.orders.index');
    Route::get('/orders/{order}', [UserDashboardController::class, 'userOrderDetails'])->name('user.orders.details');
    Route::patch('/orders/{order}/cancel', [UserDashboardController::class, 'cancelOrder'])->name('user.orders.cancel');
    
    //track order
    Route::get('/track-order', [UserDashboardController::class, 'trackOrderForm'])->name('user.orders.track');
    Route::post('/track-order', [UserDashboardController::class, 'trackOrder'])->name('user.trackOrder.submit');
    //user.messages.index
    Route::get('/messages', [UserDashboardController::class, 'userMessages'])->name('user.messages.index');
    Route::get('/messages/fetch', [UserDashboardController::class, 'fetchMessages'])->name('user.messages.fetch');
    Route::post('/messages/send', [UserDashboardController::class, 'sendMessage'])->name('user.messages.send');
    Route::post('/messages/mark-read', [UserDashboardController::class, 'markAsRead'])->name('user.messages.markRead');

    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
});

// Public/Shared Wishlist Routes
Route::get('/wishlist', [\App\Http\Controllers\WishlistController::class, 'index'])->name('wishlist.index');
Route::get('/wishlist/fetch', [\App\Http\Controllers\WishlistController::class, 'fetchWishlist'])->name('wishlist.fetch');
Route::post('/wishlist/toggle/{product}', [\App\Http\Controllers\WishlistController::class, 'toggle'])->name('wishlist.toggle');
Route::delete('/wishlist/remove/{id}', [\App\Http\Controllers\WishlistController::class, 'remove'])->name('wishlist.remove');

// Admin auth routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminLogin::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminLogin::class, 'login'])->name('admin.login.submit');
    

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('category-subcategory', [CategoriesController::class, 'index'])->name('category_subcategory.index');
        Route::resource('categories', CategoriesController::class)->only(['store', 'destroy']);
        Route::resource('subcategories', SubcategoryController::class)->only(['store', 'destroy']);

        // Add route for getting subcategories (e.g., for AJAX dependent dropdowns)
        Route::get('/categories/by-type/{type}', [CategoriesController::class, 'getByType'])->name('categories.byType');
        Route::get('get-subcategories', [SubcategoryController::class, 'getSubcategories'])->name('get.subcategories');
        
        Route::resource('products', ProductController::class)->names([
            'index'   => 'admin.products.index',
            'create'  => 'admin.products.create',
            'store'   => 'admin.products.store',
            'show'    => 'admin.products.show',
            'edit'    => 'admin.products.edit',
            'update'  => 'admin.products.update',
            'destroy' => 'admin.products.destroy',
        ]);
        Route::delete('products/comments/{id}', [ProductController::class, 'destroyComment'])->name('admin.products.comment.destroy');

        Route::resource('orders', OrderController::class);
        Route::patch('orders/{order}/status/{status}', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::get('/orders/{id}/generate-invoice', [OrderController::class, 'generateInvoice'])->name('admin.orders.invoice.generate');
        Route::get('/orders/{id}/download-invoice', [OrderController::class, 'downloadInvoice'])->name('admin.orders.invoice.download');

        Route::resource('/articles', ArticleController::class);
        Route::delete('/articles/comments/{id}', [ArticleController::class, 'destroyComment'])->name('articles.comments.destroy');
        

        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
        Route::get('users/{user}/orders', [UserController::class, 'orders'])->name('users.orders');

        Route::get('/profile', [ProfileController::class, 'index'])->name('admin.profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');

        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
        Route::get('/payments/export/{type}', [PaymentController::class, 'export'])->name('payments.export');

        Route::get('/settings', [\App\Http\Controllers\Admin\Settings\SettingController::class, 'index'])->name('admin.settings.index');
        Route::post('/settings', [\App\Http\Controllers\Admin\Settings\SettingController::class, 'update'])->name('admin.settings.update');

        // Marketing & Coupons
        Route::get('/marketing', [CouponController::class, 'index'])->name('marketing.index');
        Route::post('/marketing/discount/{product}', [CouponController::class, 'updateProductDiscount'])->name('marketing.updateDiscount');
        Route::resource('coupons', CouponController::class)->except(['index', 'create', 'show', 'edit']);
        Route::patch('coupons/{coupon}/toggle', [CouponController::class, 'toggleStatus'])->name('coupons.toggle');

        // Banners
        Route::resource('banners', BannerController::class)->except(['create', 'show', 'edit']);
        Route::patch('banners/{banner}/toggle', [BannerController::class, 'toggleStatus'])->name('banners.toggle');

        // Messages
        Route::get('/messages', [\App\Http\Controllers\Admin\MessageController::class, 'index'])->name('admin.messages.index');
        Route::get('/messages/{identifier}/fetch', [\App\Http\Controllers\Admin\MessageController::class, 'fetchMessages'])->name('admin.messages.fetch');
        Route::post('/messages/{identifier}/send', [\App\Http\Controllers\Admin\MessageController::class, 'sendMessage'])->name('admin.messages.send');
        Route::post('/messages/{identifier}/mark-read', [\App\Http\Controllers\Admin\MessageController::class, 'markAsRead'])->name('admin.messages.markRead');

        Route::post('/logout', [AdminLogin::class, 'logout'])->name('admin.logout');
    });
});