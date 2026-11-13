<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLogin;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController as AdminForgotPassword;
use App\Http\Controllers\Admin\Auth\ResetPasswordController as AdminResetPassword;
use App\Http\Controllers\Admin\Auth\ProfileController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Users\UserController;
use App\Http\Controllers\Admin\Users\AdminUserController;
use App\Http\Controllers\Admin\Product\CategoriesController;
use App\Http\Controllers\Admin\Product\SubcategoryController;
use App\Http\Controllers\Admin\Product\ProductController;
use App\Http\Controllers\Admin\Product\AttributeController;
use App\Http\Controllers\Admin\Product\BulkImportController;
use App\Http\Controllers\Admin\Product\StockMovementController;
use App\Http\Controllers\Admin\Product\BrandController;
use App\Http\Controllers\Admin\Order\OrderController;
use App\Http\Controllers\Admin\Order\OrderCourierController;
use App\Http\Controllers\Admin\Order\RefundController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Frontend\PageController as FrontendPageController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\Settings\SettingController;
use App\Http\Controllers\Frontend\FrontendArticleController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\UserDashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SslcommerzController;
use App\Http\Controllers\Frontend\TrackingController;
Route::redirect('/', '/en');

/**
 * Registers a resource split by permission action. "create" is registered before "view"
 * so that /{uri}/create is not swallowed by the show route.
 */
$adminResource = function (string $uri, string $controller, string $module, array $only = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'], array $names = []) {
    $actions = [
        'create' => ['create', 'store'],
        'view'   => ['index', 'show'],
        'edit'   => ['edit', 'update'],
        'delete' => ['destroy'],
    ];

    foreach ($actions as $action => $methods) {
        $methods = array_values(array_intersect($methods, $only));
        if ($methods) {
            Route::resource($uri, $controller)->only($methods)->names($names)->middleware("permission:{$module}.{$action},admin");
        }
    }
};

$can = fn (string $permission) => "permission:{$permission},admin";

// Admin auth routes
Route::prefix('admin')->group(function () use ($adminResource, $can) {
    Route::get('/login', [AdminLogin::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminLogin::class, 'login'])->name('admin.login.submit');

    Route::get('/forgot-password', [AdminForgotPassword::class, 'showLinkRequestForm'])->name('admin.password.request');
    Route::post('/forgot-password', [AdminForgotPassword::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('admin.password.email');
    Route::get('/reset-password/{token}', [AdminResetPassword::class, 'showResetForm'])->name('admin.password.reset');
    Route::post('/reset-password', [AdminResetPassword::class, 'reset'])->middleware('throttle:5,1')->name('admin.password.update');


    Route::middleware('admin')->group(function () use ($adminResource, $can) {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        // ── Notifications (each admin sees only their own) ──────────────────
        Route::get('notifications', [NotificationController::class, 'index'])->name('admin.notifications.index');
        Route::get('notifications/feed', [NotificationController::class, 'feed'])->name('admin.notifications.feed');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('admin.notifications.readAll');
        Route::get('notifications/{id}/open', [NotificationController::class, 'open'])->name('admin.notifications.open');
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('admin.notifications.destroy');

        // ── Searchable pickers ──────────────────────────────────────────────
        Route::get('lookup/variants', [LookupController::class, 'variants'])->middleware($can('orders.create|products.edit'))->name('admin.lookup.variants');
        Route::get('lookup/customers', [LookupController::class, 'customers'])->middleware($can('orders.create'))->name('admin.lookup.customers');

        // ── Inventory & Products ─────────────────────────────────────────────
        Route::middleware($can('products.view'))->group(function () {
            Route::get('category-subcategory', [CategoriesController::class, 'index'])->name('category_subcategory.index');
            Route::get('/categories/by-type/{type}', [CategoriesController::class, 'getByType'])->name('categories.byType');
            Route::get('products/stock-movements', [StockMovementController::class, 'index'])->name('admin.products.stock_movements.index');
            Route::get('attributes', [AttributeController::class, 'index'])->name('admin.attributes.index');
            Route::get('attributes/by-category/{category_id}', [AttributeController::class, 'getByCategory'])->name('admin.attributes.byCategory');
            Route::get('brands', [BrandController::class, 'index'])->name('admin.brands.index');
        });
        Route::middleware($can('products.create'))->group(function () {
            Route::post('categories', [CategoriesController::class, 'store'])->name('categories.store');
            Route::post('subcategories', [SubcategoryController::class, 'store'])->name('subcategories.store');
            Route::get('products/import', [BulkImportController::class, 'index'])->name('admin.products.import');
            Route::post('products/import', [BulkImportController::class, 'import']);
            Route::get('products/import/template', [BulkImportController::class, 'downloadTemplate'])->name('admin.products.import.template');
            Route::post('attributes', [AttributeController::class, 'store'])->name('admin.attributes.store');
            Route::post('attributes/{attribute}/values', [AttributeController::class, 'storeValue'])->name('admin.attributes.values.store');
            Route::post('brands', [BrandController::class, 'store'])->name('admin.brands.store');
        });
        Route::middleware($can('products.edit'))->group(function () {
            Route::match(['put', 'patch'], 'categories/{category}', [CategoriesController::class, 'update'])->name('categories.update');
            Route::match(['put', 'patch'], 'subcategories/{subcategory}', [SubcategoryController::class, 'update'])->name('subcategories.update');
            Route::post('products/stock-movements', [StockMovementController::class, 'store'])->name('admin.products.stock_movements.store');
            Route::delete('products/image/{image}', [ProductController::class, 'destroyImage'])->name('admin.products.image.destroy');
            Route::patch('products/{id}/restore', [ProductController::class, 'restore'])->whereNumber('id')->name('admin.products.restore');
            Route::put('attributes/{attribute}', [AttributeController::class, 'update'])->name('admin.attributes.update');
            Route::post('attributes/attach-to-category', [AttributeController::class, 'attachToCategory'])->name('admin.attributes.attachToCategory');
            Route::put('brands/{brand}', [BrandController::class, 'update'])->name('admin.brands.update');
        });
        Route::middleware($can('products.delete'))->group(function () {
            Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->name('categories.destroy');
            Route::delete('subcategories/{subcategory}', [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');
            Route::delete('products/comments/{id}', [ProductController::class, 'destroyComment'])->name('admin.products.comment.destroy');
            Route::delete('attributes/{attribute}', [AttributeController::class, 'destroy'])->name('admin.attributes.destroy');
            Route::delete('attributes/values/{value}', [AttributeController::class, 'destroyValue'])->name('admin.attributes.values.destroy');
            Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('admin.brands.destroy');
        });

        // ── Reviews ─────────────────────────────────────────────────────────
        Route::get('reviews', [ReviewController::class, 'index'])->middleware($can('reviews.view'))->name('admin.reviews.index');
        Route::middleware($can('reviews.edit'))->group(function () {
            Route::post('reviews/bulk', [ReviewController::class, 'bulk'])->name('admin.reviews.bulk');
            Route::patch('reviews/{id}/approve', [ReviewController::class, 'approve'])->whereNumber('id')->name('admin.reviews.approve');
            Route::patch('reviews/{id}/reject', [ReviewController::class, 'reject'])->whereNumber('id')->name('admin.reviews.reject');
        });
        Route::delete('reviews/{id}', [ReviewController::class, 'destroy'])->whereNumber('id')->middleware($can('reviews.delete'))->name('admin.reviews.destroy');
        $adminResource('products', ProductController::class, 'products', names: [
            'index' => 'admin.products.index',
            'create' => 'admin.products.create',
            'store' => 'admin.products.store',
            'show' => 'admin.products.show',
            'edit' => 'admin.products.edit',
            'update' => 'admin.products.update',
            'destroy' => 'admin.products.destroy',
        ]);

        // ── Orders & Fulfilment ─────────────────────────────────────────────
        Route::middleware($can('orders.edit'))->group(function () {
            Route::post('orders/bulk-status', [OrderController::class, 'bulkStatus'])->name('orders.bulkStatus');
            Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
            Route::post('orders/{order}/courier', [OrderCourierController::class, 'store'])->name('orders.courier.store');
            Route::post('orders/{order}/courier/refresh', [OrderCourierController::class, 'refresh'])->name('orders.courier.refresh');
            Route::get('orders/courier/pathao/cities', [OrderCourierController::class, 'pathaoCities'])->name('orders.courier.pathao.cities');
            Route::get('orders/courier/pathao/cities/{city}/zones', [OrderCourierController::class, 'pathaoZones'])->whereNumber('city')->name('orders.courier.pathao.zones');
        });
        Route::middleware($can('orders.view'))->group(function () {
            Route::get('/orders/{id}/generate-invoice', [OrderController::class, 'generateInvoice'])->name('admin.orders.invoice.generate');
            Route::get('/orders/{id}/download-invoice', [OrderController::class, 'downloadInvoice'])->name('admin.orders.invoice.download');
        });
        $adminResource('orders', OrderController::class, 'orders');

        // ── Payments & Refunds ──────────────────────────────────────────────
        Route::middleware($can('payments.view'))->group(function () {
            Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/export/{type}', [PaymentController::class, 'export'])->whereIn('type', ['xlsx', 'csv'])->name('payments.export');
            Route::get('/payments/{id}', [PaymentController::class, 'show'])->whereNumber('id')->name('payments.show');
        });
        Route::middleware($can('payments.edit'))->group(function () {
            Route::post('orders/{order}/refunds', [RefundController::class, 'store'])->name('orders.refunds.store');
            Route::post('refunds/{refund}/complete', [RefundController::class, 'complete'])->name('refunds.complete');
            Route::post('refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');
        });

        // ── Reports ─────────────────────────────────────────────────────────
        Route::middleware($can('reports.view'))->group(function () {
            Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index');
            Route::get('/reports/export/{type}', [ReportController::class, 'export'])->whereIn('type', ['xlsx', 'csv'])->name('admin.reports.export');
        });

        // ── Tech Blog ───────────────────────────────────────────────────────
        $adminResource('articles', ArticleController::class, 'articles');
        Route::delete('/articles/comments/{id}', [ArticleController::class, 'destroyComment'])->middleware($can('articles.delete'))->name('articles.comments.destroy');

        // ── Customers ───────────────────────────────────────────────────────
        $adminResource('users', UserController::class, 'users');
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware($can('users.edit'))->name('users.toggleStatus');
        Route::get('users/{user}/orders', [UserController::class, 'orders'])->middleware($can('users.view'))->name('users.orders');

        // ── Access Control ──────────────────────────────────────────────────
        $withoutShow = ['index', 'create', 'store', 'edit', 'update', 'destroy'];
        $adminResource('roles', RoleController::class, 'roles', $withoutShow);
        $adminResource('permissions', PermissionController::class, 'roles', $withoutShow);
        $adminResource('admins', AdminUserController::class, 'roles', $withoutShow);
        Route::get('activity-log', [ActivityLogController::class, 'index'])->middleware($can('activity.view'))->name('admin.activity.index');

        // Profile (Shared administrative route)
        Route::get('/profile', [ProfileController::class, 'index'])->name('admin.profile');
        Route::put('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');

        // ── CMS Pages ───────────────────────────────────────────────────────
        $adminResource('pages', PageController::class, 'pages', ['index', 'create', 'store', 'edit', 'update', 'destroy'], names: [
            'index' => 'admin.pages.index',
            'create' => 'admin.pages.create',
            'store' => 'admin.pages.store',
            'edit' => 'admin.pages.edit',
            'update' => 'admin.pages.update',
            'destroy' => 'admin.pages.destroy',
        ]);

        // ── Global Settings ─────────────────────────────────────────────────
        Route::get('/settings', [SettingController::class, 'index'])->middleware($can('settings.view'))->name('admin.settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->middleware($can('settings.edit'))->name('admin.settings.update');

        // ── Marketing & Coupons ─────────────────────────────────────────────
        Route::get('/marketing', [CouponController::class, 'index'])->middleware($can('marketing.view'))->name('marketing.index');
        Route::middleware($can('marketing.edit'))->group(function () {
            Route::post('/marketing/discount/{product}', [CouponController::class, 'updateProductDiscount'])->name('marketing.updateDiscount');
            Route::post('/marketing/flash-deal', [CouponController::class, 'updateFlashDeal'])->name('marketing.flashDeal');
            Route::patch('coupons/{coupon}/toggle', [CouponController::class, 'toggleStatus'])->name('coupons.toggle');
        });
        $adminResource('coupons', CouponController::class, 'marketing', ['create', 'store', 'edit', 'update', 'destroy']);
        Route::middleware($can('marketing.view'))->group(function () {
            Route::get('subscribers', [SubscriberController::class, 'index'])->name('admin.subscribers.index');
            Route::get('subscribers/export', [SubscriberController::class, 'export'])->name('admin.subscribers.export');
        });
        Route::patch('subscribers/{subscriber}/toggle', [SubscriberController::class, 'toggle'])->middleware($can('marketing.edit'))->name('admin.subscribers.toggle');
        Route::delete('subscribers/{subscriber}', [SubscriberController::class, 'destroy'])->middleware($can('marketing.delete'))->name('admin.subscribers.destroy');

        // ── Banners ─────────────────────────────────────────────────────────
        $adminResource('banners', BannerController::class, 'banners', ['index', 'store', 'update', 'destroy']);
        Route::patch('banners/{banner}/toggle', [BannerController::class, 'toggleStatus'])->middleware($can('banners.edit'))->name('banners.toggle');

        // ── Customer Messages ───────────────────────────────────────────────
        Route::middleware($can('messages.view'))->group(function () {
            Route::get('/messages', [MessageController::class, 'index'])->name('admin.messages.index');
            Route::get('/messages/{identifier}/fetch', [MessageController::class, 'fetchMessages'])->name('admin.messages.fetch');
            Route::post('/messages/{identifier}/mark-read', [MessageController::class, 'markAsRead'])->name('admin.messages.markRead');
        });
        Route::post('/messages/{identifier}/send', [MessageController::class, 'sendMessage'])->middleware($can('messages.create'))->name('admin.messages.send');

        Route::post('/logout', [AdminLogin::class, 'logout'])->name('admin.logout');
    });
});

// Social Authentication Routes
use App\Http\Controllers\Frontend\SocialAuthController;
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

Route::group(['prefix' => '{locale}', 'middleware' => 'set_locale'], function () {
    // User routes
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/products', [HomeController::class, 'products'])->name('products.index');
    Route::get('/search', [\App\Http\Controllers\Frontend\SearchController::class, 'index'])->middleware('throttle:search')->name('search.index');
    Route::get('/product/{id}', [HomeController::class, 'show'])->name('products.show');
    Route::post('/product/{id}/comment', [HomeController::class, 'storeProductComment'])->name('product.comment')->middleware('auth');
    Route::put('/product/blog-comments/{id}', [HomeController::class, 'updateProductComment'])->name('product.comment.update')->middleware('auth');
    Route::delete('/product/blog-comments/{id}', [HomeController::class, 'deleteProductComment'])->name('product.comment.delete')->middleware('auth');
    Route::post('/product/{id}/react', [HomeController::class, 'toggleProductReaction'])->name('product.react')->middleware('auth');
    Route::get('/flash-deals', [HomeController::class, 'flashDeals'])->name('flash-deals');
    Route::get('/load-more-products', [HomeController::class, 'loadMoreProducts'])->name('load.more.products');
    Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
    Route::get('/page/{slug}', [FrontendPageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('pages.show');

    // Public Order Tracking
    Route::get('/track', [TrackingController::class, 'index'])->name('tracking.index');
    Route::post('/track', [TrackingController::class, 'track'])->middleware('throttle:10,1')->name('tracking.track');
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
    Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('cart.coupon.apply');
    Route::post('/cart/coupon/remove', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');


    // Protected checkout routes
    Route::middleware('user')->group(function () {
        Route::get('/checkout', [CheckoutController::class, 'checkoutIndex'])->name('checkout.index');
        Route::post('/place-order', [CheckoutController::class, 'placeOrder'])->name('checkout.placeOrder');
    });

    Route::get('/orders/success/{order}', [CheckoutController::class, 'success'])->name('orders.success');
    Route::get('/orders/{order}/invoice/download', [CheckoutController::class, 'downloadInvoice'])->name('orders.invoice.download')->middleware('user');

    // Accept both GET and POST for success, fail, cancel
    Route::match(['get', 'post'], '/ssl-success', [SslcommerzController::class, 'success'])->name('sslc.success');
    Route::match(['get', 'post'], '/ssl-fail', [SslcommerzController::class, 'fail'])->name('sslc.failure');
    Route::match(['get', 'post'], '/ssl-cancel', [SslcommerzController::class, 'cancel'])->name('sslc.cancel');

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

});
