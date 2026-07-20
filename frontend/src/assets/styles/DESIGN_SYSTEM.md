# TechNova Design System

Design system này nằm ở `frontend/src/assets/styles/design-system.css` và được import trong `frontend/src/main.js`.

## Nguyên tắc giao diện

- Giao diện khách hàng ưu tiên cảm giác công nghệ, sạch, dễ quét thông tin sản phẩm.
- Bo góc vừa phải, phần lớn dùng `8px` đến `14px`; card lớn có thể dùng `18px`.
- Màu chủ đạo là xanh `#2563eb`, nền sáng `#f8fafc`, chữ chính `#0f172a`.
- Không dùng chữ quá lớn trong card nhỏ; title hero mới dùng cỡ lớn.
- Mọi control tương tác nên có focus state rõ ràng.

## Token chính

- Màu: `--ds-primary`, `--ds-bg`, `--ds-surface`, `--ds-border`, `--ds-text`, `--ds-text-muted`.
- Spacing: `--ds-space-1` đến `--ds-space-20`.
- Radius: `--ds-radius-xs`, `--ds-radius-sm`, `--ds-radius-md`, `--ds-radius-lg`, `--ds-radius-xl`.
- Shadow: `--ds-shadow-sm`, `--ds-shadow-md`, `--ds-shadow-lg`.

## Layout

```html
<section class="ds-section">
  <div class="ds-container ds-stack" style="--ds-stack-gap: var(--ds-space-8)">
    ...
  </div>
</section>
```

- `ds-container`: giới hạn chiều rộng trang.
- `ds-section`: padding dọc responsive.
- `ds-grid`: grid responsive, chỉnh min bằng `--ds-grid-min`.
- `ds-stack`: xếp dọc có gap.
- `ds-cluster`: xếp ngang và tự xuống dòng.

## Component primitives

### Button

```html
<button class="ds-btn ds-btn--primary">Mua ngay</button>
<button class="ds-btn ds-btn--secondary">Xem chi tiết</button>
<button class="ds-btn ds-btn--ghost">Hủy</button>
```

### Card

```html
<article class="ds-card ds-card--interactive">
  <div class="ds-card__body ds-stack">
    <span class="ds-badge">Hot</span>
    <h3 class="ds-heading-sm">Laptop Gaming RTX</h3>
    <p class="ds-text-muted ds-line-clamp-2">Mô tả ngắn của sản phẩm.</p>
    <strong class="ds-price">29.990.000đ</strong>
  </div>
</article>
```

### Form

```html
<label class="ds-field">
  <span class="ds-label">Email</span>
  <input class="ds-input" placeholder="email@example.com" />
</label>
```

### Badge

```html
<span class="ds-badge">New</span>
<span class="ds-badge ds-badge--success">Còn hàng</span>
<span class="ds-badge ds-badge--warning">Sắp hết</span>
<span class="ds-badge ds-badge--danger">Sale</span>
```

## Áp dụng dần

1. Trang mới nên dùng `ds-*` ngay từ đầu.
2. Trang cũ có thể thay từng phần: section, card, button, form.
3. Khi sửa component lớn như `TrangChu.vue`, ưu tiên gom style lặp lại về `ds-*` trước khi tinh chỉnh riêng.
