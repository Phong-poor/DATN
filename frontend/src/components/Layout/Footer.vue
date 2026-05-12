<script setup>
// Footer component
</script>

<template>
  <footer class="footer">
    <div class="footer-glow"></div>
    
    <div class="container">
      <!-- GRID -->
      <div class="grid">

        <!-- LEFT: BRANDING -->
        <div class="col brand">
          <div class="logo">
            <span class="logo-white">NextGen</span>
            <span class="logo-gradient">Laptop</span>
          </div>
          <p class="brand-desc">
            Dẫn đầu kỷ nguyên công nghệ số 2026 với những giải pháp tối ưu và trải nghiệm người dùng đẳng cấp. Chúng tôi cung cấp những dòng Laptop mạnh mẽ nhất thế giới.
          </p>
          <div class="social-tray">
            <a href="#" class="social-icon" title="Facebook">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            </a>
            <a href="#" class="social-icon" title="Instagram">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
            </a>
            <a href="#" class="social-icon" title="Twitter">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
            </a>
          </div>
        </div>

        <!-- CENTER: LINKS -->
        <div class="col">
          <h4 class="col-title">Khám phá</h4>
          <ul class="footer-links">
            <li><router-link to="/products">Cửa hàng trực tuyến</router-link></li>
            <li><router-link to="/products?category=gaming">Laptop Gaming</router-link></li>
            <li><router-link to="/products?category=workstation">Workstation</router-link></li>
            <li><router-link to="/news">Tin tức công nghệ</router-link></li>
          </ul>
        </div>

        <div class="col">
          <h4 class="col-title">Hỗ trợ khách hàng</h4>
          <ul class="footer-links">
            <li><router-link to="/contact">Liên hệ chúng tôi</router-link></li>
            <li><a href="#">Chính sách bảo hành</a></li>
            <li><a href="#">Điều khoản dịch vụ</a></li>
            <li><a href="#">Câu hỏi thường gặp</a></li>
          </ul>
        </div>

        <!-- RIGHT: NEWSLETTER -->
        <div class="col newsletter">
          <h4 class="col-title">Bản tin công nghệ</h4>
          <p class="newsletter-desc">Đăng ký nhận mã giảm giá 10% và cập nhật những dòng laptop mới nhất.</p>
          <div class="subscribe-box">
            <input type="email" placeholder="Địa chỉ Email của bạn" class="subscribe-input" />
            <button class="subscribe-btn">Đăng ký</button>
          </div>
          <div class="contact-info">
            <div class="contact-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
              <span>123 Đường Công Nghệ, Q. Liên Chiểu, Đà Nẵng</span>
            </div>
            <div class="contact-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.08 6.08l.95-.95a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              <span>1800 9999 (Miễn phí)</span>
            </div>
          </div>
        </div>

      </div>

      <!-- BOTTOM BAR -->
      <div class="bottom-bar">
        <div class="bottom-inner">
          <p class="copyright">© 2026 <strong>NextGen Laptop</strong> Premium. Thiết kế bởi Antigravity.</p>
          <div class="bottom-links">
            <a href="#">Chính sách bảo mật</a>
            <span class="dot"></span>
            <a href="#">Cài đặt Cookie</a>
            <span class="dot"></span>
            <a href="#">Sơ đồ trang</a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  background-color: #0f172a;
  color: #f1f5f9;
  padding: 80px 0 30px;
  position: relative;
  overflow: hidden;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.footer-glow {
  position: absolute;
  top: -150px;
  left: 50%;
  transform: translateX(-50%);
  width: 60%;
  height: 200px;
  background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, rgba(37, 99, 235, 0) 70%);
  pointer-events: none;
}

.container {
  max-width: 1300px;
  margin: auto;
  padding: 0 30px;
}

.grid {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr 1.5fr;
  gap: 50px;
}

/* BRANDING */
.logo {
  font-size: 26px;
  font-weight: 800;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.logo-white { color: #ffffff; }

.logo-gradient {
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.brand-desc {
  font-size: 14.5px;
  color: #94a3b8;
  line-height: 1.7;
  margin-bottom: 24px;
}

.social-tray {
  display: flex;
  gap: 12px;
}

.social-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.social-icon:hover {
  background: #3b82f6;
  color: #ffffff;
  transform: translateY(-4px);
  box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.5);
  border-color: #3b82f6;
}

.social-icon svg {
  width: 18px;
  height: 18px;
}

/* TITLES */
.col-title {
  font-size: 15px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #ffffff;
  margin-bottom: 24px;
}

/* LINKS */
.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-links li {
  margin-bottom: 12px;
}

.footer-links a {
  color: #94a3b8;
  text-decoration: none;
  font-size: 14.5px;
  transition: all 0.2s;
  display: inline-block;
}

.footer-links a:hover {
  color: #3b82f6;
  transform: translateX(5px);
}

/* NEWSLETTER */
.newsletter-desc {
  font-size: 14px;
  color: #94a3b8;
  margin-bottom: 20px;
}

.subscribe-box {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  background: rgba(255, 255, 255, 0.03);
  padding: 6px;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: border-color 0.3s;
}

.subscribe-box:focus-within {
  border-color: #3b82f6;
}

.subscribe-input {
  flex: 1;
  background: transparent;
  border: none;
  padding: 10px 15px;
  color: #ffffff;
  font-size: 13.5px;
  outline: none;
}

.subscribe-btn {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0 20px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 13.5px;
  cursor: pointer;
  transition: all 0.2s;
}

.subscribe-btn:hover {
  background: #2563eb;
  transform: scale(1.02);
}

/* CONTACT INFO */
.contact-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.contact-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  color: #94a3b8;
  font-size: 13.5px;
}

.contact-item svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  margin-top: 2px;
  color: #3b82f6;
}

/* BOTTOM BAR */
.bottom-bar {
  margin-top: 60px;
  padding-top: 30px;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.bottom-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 20px;
}

.copyright {
  font-size: 13.5px;
  color: #64748b;
}

.copyright strong {
  color: #94a3b8;
}

.bottom-links {
  display: flex;
  align-items: center;
  gap: 15px;
}

.bottom-links a {
  font-size: 13px;
  color: #64748b;
  text-decoration: none;
  transition: color 0.2s;
}

.bottom-links a:hover {
  color: #3b82f6;
}

.dot {
  width: 4px;
  height: 4px;
  background: #334155;
  border-radius: 50%;
}

@media (max-width: 1024px) {
  .grid {
    grid-template-columns: 1fr 1fr;
    gap: 40px;
  }
}

@media (max-width: 640px) {
  .grid {
    grid-template-columns: 1fr;
  }
  
  .bottom-inner {
    flex-direction: column;
    text-align: center;
  }
  
  .footer {
    padding-top: 50px;
  }
  
  .subscribe-box {
    flex-direction: column;
    background: transparent;
    padding: 0;
    border: none;
  }
  
  .subscribe-input {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    margin-bottom: 10px;
  }
  
  .subscribe-btn {
    height: 44px;
  }
}
</style>