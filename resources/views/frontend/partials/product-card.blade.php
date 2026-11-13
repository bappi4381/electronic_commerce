{{-- Kept for existing @include calls (load-more HTML, flash deals); renders the shared component. --}}
<x-store.product-card :product="$product" :wishlist-ids="$wishlistIds ?? []" />
