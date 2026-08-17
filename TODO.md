# Lapify Master Fix — TODO

## Section 2: Sellers must not buy/wishlist/cart own listing
- [x] 2.0 includes/functions.php — add `isOwnListing()` helper
- [ ] 2.1 cart.php — server-side ownership reject (toggle/add)
- [ ] 2.2 wishlist.php — server-side ownership reject (toggle)
- [ ] 2.3 laptop-details.php — disable own-listing buttons (cart/buy/wishlist)
- [ ] 2.4 buy.php — disable own-listing card buttons
- [ ] 2.5 index.php — disable own-listing card buttons (renderLaptopCard)
- [ ] 2.6 wishlist.php (cards) — disable own-listing card buttons

## Section 3: Auth pages single-card redesign
- [ ] 3.1 login.php — remove auth-panel-left markup
- [ ] 3.2 register.php — remove auth-panel-left markup
- [ ] 3.3 forgot_password.php — remove auth-panel-left markup
- [ ] 3.4 reset_password.php — remove auth-panel-left markup
- [ ] 3.5 admin/login.php — remove auth-panel-left markup
- [ ] 3.6 assets/css/style.css — single centered card, theme-aware bg, remove unused panel CSS

## Section 4: Output-buffering safety net
- [ ] 4.1 config/config.php — add `ob_start();` as first line
