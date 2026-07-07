<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// 1. Live player/user counter simulator (WOW factor for tech sites)
const liveUserCount = ref(1842)
let counterInterval = null

// 2. Back to top with scroll progress ring logic
const showScrollTop = ref(false)
const scrollProgress = ref(0)

const handleScroll = () => {
  const scrollTop = window.scrollY
  const docHeight = document.documentElement.scrollHeight - window.innerHeight
  if (docHeight > 0) {
    scrollProgress.value = (scrollTop / docHeight) * 100
  }
  showScrollTop.value = scrollTop > 300
}

const scrollToTop = () => {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  })
}

onMounted(() => {
  // Live user counter fluctuation
  counterInterval = setInterval(() => {
    const delta = Math.floor(Math.random() * 5) - 2 // random between -2 and +2
    const newValue = liveUserCount.value + delta
    if (newValue >= 1810 && newValue <= 1890) {
      liveUserCount.value = newValue
    }
  }, 4000)

  // Scroll listener
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  if (counterInterval) clearInterval(counterInterval)
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <footer class="footer">
    <!-- Neon glowing background orbs -->
    <div class="glow-orb glow-cyan"></div>
    <div class="glow-orb glow-purple"></div>
    <div class="cyber-grid-overlay"></div>
    
    <div class="container">
      <!-- ================= 2. CORE DIRECTORY GRID (5 COLUMNS) ================= -->
      <div class="directory-grid">
        
        <!-- COLUMN 1: BRAND HUB & LIVE STATUS -->
        <div class="dir-col brand-hub">
          <div class="footer-brand">
            <img src="/nextgen_logo.svg" alt="NextGen Logo" class="footer-logo" />
            <span class="cyber-badge-glow">TECH</span>
          </div>
          <p class="brand-slogan">
            Đại lý công nghệ tiên phong. Chúng tôi cung cấp những cỗ máy hiệu năng mạnh mẽ, tối ưu cho gaming, sáng tạo nội dung và làm việc chuyên nghiệp.
          </p>
          
          <!-- Live server dashboard widget -->
          <div class="live-status-widget">
            <div class="widget-header">
              <span class="live-pulse"></span>
              <span class="live-label">CORE SERVER: <span class="green-text">ONLINE</span></span>
            </div>
            <div class="widget-details">
              <span class="stat-item">Gamers trực tuyến: <strong>{{ liveUserCount }}</strong></span>
              <span class="stat-divider">|</span>
              <span class="stat-item">Ping: <strong class="cyan-text">14ms</strong></span>
            </div>
          </div>

          <!-- Social media tray with high-tech glow on hover -->
          <div class="social-tray">
            <a href="#" class="social-btn facebook" title="Facebook">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c4.56-.93 8-4.96 8-9.75z"/></svg>
            </a>
            <a href="#" class="social-btn instagram" title="Instagram">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
            </a>
            <a href="#" class="social-btn youtube" title="YouTube">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.163a3.003 3.003 0 0 0-2.11-2.107C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.388.511a3.002 3.002 0 0 0-2.11 2.107C0 8.053 0 12 0 12s0 3.947.502 5.837a3.003 3.003 0 0 0 2.11 2.107C4.495 20.455 12 20.455 12 20.455s7.505 0 9.388-.511a3.003 3.003 0 0 0 2.11-2.107C24 15.947 24 12 24 12s0-3.947-.502-5.837zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
            </a>
            <a href="#" class="social-btn tiktok" title="TikTok">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.03 2.61-.01 3.91-.02.08 1.53.63 3.02 1.59 4.23.95.8 2.07 1.37 3.29 1.63v3.83c-1.84-.04-3.61-.75-5-2.02-.02 2.58.01 5.16-.01 7.74-.06 2.37-1.12 4.65-2.99 6.11-2.03 1.63-4.83 2.19-7.33 1.48-2.61-.75-4.75-2.79-5.46-5.44-.75-2.73-.01-5.78 1.95-7.79 1.83-1.92 4.61-2.63 7.15-1.83v4.03c-1.39-.46-2.97-.13-4.01.87-1.13 1.05-1.39 2.82-.57 4.13.73 1.25 2.19 1.93 3.61 1.7 1.48-.19 2.64-1.39 2.81-2.88.08-3.07.03-6.15.05-9.22z"/></svg>
            </a>
            <a href="#" class="social-btn discord" title="Discord">
              <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.873-.894.077.077 0 0 1-.008-.128c.126-.093.252-.19.372-.287a.075.075 0 0 1 .077-.011c3.92 1.793 8.18 1.793 12.061 0a.073.073 0 0 1 .078.009c.12.099.246.195.373.289a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.894.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.156-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.156 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.156-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.156 2.418z"/></svg>
            </a>
          </div>
        </div>

        <!-- COLUMN 2: CUA HANG CONG NGHE -->
        <div class="dir-col">
          <h4 class="col-title">Cửa Hàng Công Nghệ</h4>
          <ul class="footer-links">
            <li><router-link to="/san-pham?category=gaming" class="link-item">Laptop Gaming AI (RTX 50-Series)</router-link></li>
            <li><router-link to="/macbook" class="link-item">MacBook & Ultrabook Siêu Mỏng</router-link></li>
            <li><router-link to="/san-pham?category=workstation" class="link-item">Workstation Đồ Họa & Render</router-link></li>
            <li><router-link to="/san-pham?category=gear" class="link-item">Gaming Gear & Phụ Kiện High-End</router-link></li>
            <li><router-link to="/san-pham?category=office" class="link-item">Laptop Văn Phòng AI Intel Ultra</router-link></li>
          </ul>
        </div>

        <!-- COLUMN 3: HE SINH THAI VINATECH -->
        <div class="dir-col">
          <h4 class="col-title">Hệ Sinh Thái</h4>
          <ul class="footer-links">
            <li><router-link to="/phong-thi-nghiem-tuong-tac" class="link-item">NextGen Interactive Labs</router-link></li>
            <li><router-link to="/phong-thi-nghiem-tuong-tac" class="link-item">Đấu Trường Hiệu Năng VinaTech</router-link></li>
            <li><router-link to="/tin-tuc" class="link-item">Tin Tức Công Nghệ AI & Review</router-link></li>
            <li><router-link to="/san-pham" class="link-item">Góc Setup VIP Cộng Đồng</router-link></li>
            <li><router-link to="/phong-thi-nghiem-tuong-tac" class="link-item">VIP Club & Esports Tournaments</router-link></li>
          </ul>
        </div>

        <!-- COLUMN 4: HO TRO KY THUAT & HAU MAI -->
        <div class="dir-col">
          <h4 class="col-title">Hỗ Trợ Kỹ Thuật</h4>
          <ul class="footer-links">
            <li><router-link to="/lien-he" class="link-item">Liên Hệ & Gửi Yêu Cầu Hỗ Trợ</router-link></li>
            <li><a href="#" class="link-item">Chính Sách Bảo Hành Ultimate Care</a></li>
            <li><a href="#" class="link-item">Tra Cứu Bảo Hành Điện Tử VIP</a></li>
            <li><a href="#" class="link-item">Tải Driver & Firmware Mới Nhất</a></li>
            <li><a href="#" class="link-item">Chính Sách Đổi Trả 1-Đổi-1 30 Ngày</a></li>
          </ul>
        </div>

        <!-- COLUMN 5: NEWSLETTER & NEWS SOUNDWAVE HOTLINE -->
        <div class="dir-col newsletter-col">
          <h4 class="col-title">Bản Tin Cyber-Tech</h4>
          <p class="news-desc">Đăng ký ngay để nhận sớm voucher giảm giá 10% và cập nhật công nghệ AI mới nhất.</p>
          
          <div class="subscribe-box-glass">
            <span class="sub-mail-icon">@</span>
            <input type="email" placeholder="Địa chỉ email của bạn..." class="subscribe-input" />
            <button class="subscribe-btn">
              <span>Đăng Ký</span>
              <svg class="arrow-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
          </div>

          <!-- Hotline Soundwave card -->
          <div class="hotline-card">
            <div class="hotline-icon-box">
              <svg class="phone-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.91a16 16 0 0 0 6.08 6.08l.95-.95a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div class="hotline-body">
              <div class="hotline-number">
                <span>1800 9999</span>
                <!-- Soundwave dynamic animation bars -->
                <div class="soundwave-container" title="Tổng đài trực tuyến hoạt động">
                  <div class="soundwave-bar"></div>
                  <div class="soundwave-bar"></div>
                  <div class="soundwave-bar"></div>
                  <div class="soundwave-bar"></div>
                  <div class="soundwave-bar"></div>
                </div>
              </div>
              <p class="hotline-note">Hỗ trợ kỹ thuật chuyên sâu (miễn phí cuộc gọi)</p>
            </div>
          </div>

          <div class="location-item">
            <svg class="loc-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span>123 Đường Công Nghệ, Q. Liên Chiểu, Đà Nẵng</span>
          </div>
        </div>

      </div>

      <!-- ================= 3. BOTTOM FOOTER BAR (COMPLIANCE & PAYMENTS) ================= -->
      <div class="bottom-bar">
        <div class="bottom-inner">
          <div class="b-left">
            <p class="copyright">
              © 2026 <strong>VinaTech NextGen</strong>. Thiết kế & phát triển với công nghệ tối ưu.
            </p>
            <div class="bottom-links">
              <a href="#">Chính Sách Bảo Mật</a>
              <span class="dot-separator"></span>
              <a href="#">Điều Khoản Sử Dụng</a>
              <span class="dot-separator"></span>
              <a href="#">Sơ Đồ Trang Website</a>
            </div>
          </div>

          <div class="b-right">
            <!-- Payment gateway logos (Vivid glowing SVGs on hover) -->
            <div class="payment-suite" aria-label="Cổng thanh toán hỗ trợ">
              <!-- VISA -->
              <div class="pay-logo visa" title="Visa Card">
                <svg viewBox="0 0 24 24" fill="currentColor">
                  <path d="M13.9 15.2h2.2L18.4 8h-2.2l-2.3 7.2zm4.1-7.2h-2.1L12.5 15h2.1l1.4-4.5h4.1l-2.1 4.7zm-10.7 7.2L9.4 8H7.2L5.1 12.8 4.8 11.2l-.3-1.6c-.4-1.3-1.5-2.2-2.8-2.3v.3l2 4 2.2 4h2.2z"/>
                </svg>
              </div>
              <!-- MASTERCARD -->
              <div class="pay-logo mastercard" title="Mastercard">
                <svg viewBox="0 0 24 24">
                  <circle cx="9" cy="12" r="6" fill="#eb001b" opacity="0.95"/>
                  <circle cx="15" cy="12" r="6" fill="#ff5f00" opacity="0.95"/>
                </svg>
              </div>
              <!-- JCB -->
              <div class="pay-logo jcb" title="JCB Payment">
                <span class="jcb-text">JCB</span>
              </div>
              <!-- MOMO -->
              <div class="pay-logo momo" title="Ví MoMo">
                <span class="momo-text">MoMo</span>
              </div>
              <!-- VNPAY -->
              <div class="pay-logo vnpay" title="VNPAY Cổng thanh toán">
                <span class="vnpay-text">VNPAY</span>
              </div>
            </div>
            
            <!-- Government compliance logo & Trust certificates -->
            <div class="compliance-suite">
              <!-- Norton Secured / SSL Encryption -->
              <div class="trust-badge security-ssl" title="Mã hóa SSL 256-bit bảo mật cao">
                <svg viewBox="0 0 24 24" class="lock-svg" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>SSL SECURED</span>
              </div>

              <!-- Ministry of Industry and Trade Badge -->
              <a href="#" class="gov-bct-badge" title="Website đã thông báo với Bộ Công Thương Việt Nam">
                <div class="gov-content">
                  <span class="gov-small">ĐÃ THÔNG BÁO</span>
                  <span class="gov-bold">BỘ CÔNG THƯƠNG</span>
                </div>
                <div class="gov-check-icon">
                  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Duplicate back to top widget removed, handled globally by MainLayout.vue -->

  </footer>
</template>

<style scoped>
/* ================= GLOBAL CUSTOM DESIGN TOKENS ================= */
.footer {
  --neon-cyan: #3b82f6;
  --neon-purple: #2563eb;
  --neon-green: #2563eb;
  --cyber-dark-bg: #070a13;
  --cyber-card-bg: rgba(11, 16, 29, 0.65);
  --text-primary: #ffffff;
  --text-muted: #94a3b8;
  --border-light: rgba(255, 255, 255, 0.05);

  background-color: var(--cyber-dark-bg);
  color: #e2e8f0;
  padding: 80px 0 32px;
  position: relative;
  overflow: hidden;
  border-top: 1px solid rgba(37, 99, 235, 0.22);
  font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif;
}

/* Cybernetic digital matrix mesh background */
.cyber-grid-overlay {
  position: absolute;
  inset: 0;
  background-image: 
    radial-gradient(circle at 1px 1px, rgba(37, 99, 235, 0.04) 1px, transparent 0),
    linear-gradient(rgba(255, 255, 255, 0.002) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.002) 1px, transparent 1px);
  background-size: 32px 32px;
  pointer-events: none;
  z-index: 1;
  opacity: 0.95;
}

/* Ambient neon background glows */
.glow-orb {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  pointer-events: none;
  filter: blur(120px);
  z-index: 1;
  opacity: 0.12;
}
.glow-cyan {
  top: -150px;
  left: 15%;
  background: radial-gradient(circle, var(--neon-cyan) 0%, transparent 70%);
}
.glow-purple {
  bottom: -200px;
  right: 10%;
  background: radial-gradient(circle, var(--neon-purple) 0%, transparent 70%);
}

.container {
  max-width: 1440px;
  margin: 0 auto;
  padding: 0 28px;
  position: relative;
  z-index: 2;
}

/* ================= 2. CORE DIRECTORY GRID ================= */
.directory-grid {
  display: grid;
  grid-template-columns: 1.35fr 1fr 1fr 1fr 1.35fr;
  gap: 40px;
  padding-bottom: 56px;
}

/* COLUMN 1: BRAND HUB */
.brand-hub {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.footer-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 22px;
}
.footer-logo {
  height: 38px;
  object-fit: contain;
  filter: drop-shadow(0 0 12px rgba(37, 99, 235, 0.38));
  animation: logoBreath 4s ease-in-out infinite alternate;
}
@keyframes logoBreath {
  0% { filter: drop-shadow(0 0 8px rgba(37, 99, 235, 0.3)); transform: translateY(0); }
  100% { filter: drop-shadow(0 0 16px rgba(37, 99, 235, 0.58)); transform: translateY(-2px); }
}
.cyber-badge-glow {
  font-size: 9px;
  font-weight: 800;
  background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple));
  color: white;
  padding: 2px 7px;
  border-radius: 5px;
  letter-spacing: 0.05em;
  box-shadow: 0 0 10px rgba(37, 99, 235, 0.25);
  font-family: monospace;
}
.brand-slogan {
  font-size: 13.5px;
  line-height: 1.7;
  color: var(--text-muted);
  margin: 0 0 24px;
}

/* Live status widget styling */
.live-status-widget {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 14px;
  padding: 12px 16px;
  margin-bottom: 24px;
  width: 100%;
  max-width: 280px;
  backdrop-filter: blur(8px);
}
.widget-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}
.live-pulse {
  width: 8px;
  height: 8px;
  background-color: var(--neon-green);
  border-radius: 50%;
  box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7);
  animation: pulseDot 1.8s infinite;
}
@keyframes pulseDot {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(37, 99, 235, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
}
.live-label {
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.05em;
  color: var(--text-primary);
  font-family: monospace;
}
.green-text {
  color: var(--neon-green);
  text-shadow: 0 0 6px rgba(37, 99, 235, 0.4);
}
.widget-details {
  display: flex;
  font-size: 12px;
  color: var(--text-muted);
  font-family: monospace;
}
.stat-divider {
  margin: 0 8px;
  color: rgba(255, 255, 255, 0.1);
}
.cyan-text {
  color: var(--neon-cyan);
}

/* Social Tray */
.social-tray {
  display: flex;
  gap: 10px;
}
.social-btn {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.social-btn svg {
  width: 17px;
  height: 17px;
}
.social-btn.facebook {
  background: #3b5998;
  color: #ffffff;
  border: 1px solid #3b5998;
  box-shadow: 0 4px 10px rgba(59, 89, 152, 0.25);
}
.social-btn.instagram {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: #ffffff;
  border: none;
  box-shadow: 0 4px 10px rgba(220, 39, 67, 0.25);
}
.social-btn.youtube {
  background: #ff0000;
  color: #ffffff;
  border: 1px solid #ff0000;
  box-shadow: 0 4px 10px rgba(255, 0, 0, 0.25);
}
.social-btn.tiktok {
  background: #000000;
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.35);
}
.social-btn.discord {
  background: #5865f2;
  color: #ffffff;
  border: 1px solid #5865f2;
  box-shadow: 0 4px 10px rgba(88, 101, 242, 0.25);
}

.social-btn:hover {
  transform: translateY(-5px) scale(1.08);
  filter: brightness(1.15);
}
.social-btn.facebook:hover { box-shadow: 0 8px 24px rgba(59, 89, 152, 0.45); }
.social-btn.instagram:hover { box-shadow: 0 8px 24px rgba(220, 39, 67, 0.45); }
.social-btn.youtube:hover { box-shadow: 0 8px 24px rgba(37, 99, 235, 0.42); }
.social-btn.tiktok:hover { box-shadow: 0 8px 24px rgba(255, 255, 255, 0.2); }
.social-btn.discord:hover { box-shadow: 0 8px 24px rgba(88, 101, 242, 0.45); }

/* LINK DIRECTORIES STYLE */
.col-title {
  font-size: 13.5px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-primary);
  margin-bottom: 24px;
  position: relative;
}
.col-title::after {
  content: "";
  position: absolute;
  bottom: -6px;
  left: 0;
  width: 28px;
  height: 2px;
  background: var(--neon-cyan);
  border-radius: 1px;
  box-shadow: 0 0 8px var(--neon-cyan);
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}
.footer-links li {
  margin-bottom: 14px;
}
.footer-links .link-item {
  color: var(--text-muted);
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 500;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  display: inline-block;
  position: relative;
}
.footer-links .link-item::before {
  content: "::";
  position: absolute;
  left: 0;
  opacity: 0;
  color: var(--neon-cyan);
  font-family: monospace;
  font-weight: 700;
  transition: all 0.3s ease;
  transform: translateX(-10px);
}
.footer-links .link-item:hover {
  color: var(--neon-cyan);
  padding-left: 16px;
  text-shadow: 0 0 8px rgba(37, 99, 235, 0.3);
}
.footer-links .link-item:hover::before {
  opacity: 1;
  transform: translateX(0);
}

/* NEWSLETTER COLUMN */
.news-desc {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 18px;
  line-height: 1.5;
}
.subscribe-box-glass {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.02);
  padding: 5px 6px 5px 16px;
  border-radius: 16px;
  border: 1.5px solid rgba(255, 255, 255, 0.05);
  transition: all 0.3s;
  margin-bottom: 24px;
  backdrop-filter: blur(8px);
}
.subscribe-box-glass:focus-within {
  border-color: var(--neon-cyan);
  background: rgba(37, 99, 235, 0.04);
  box-shadow: 0 0 15px rgba(37, 99, 235, 0.12);
}
.sub-mail-icon {
  font-size: 16px;
  color: #dbeafe;
}
.subscribe-input {
  flex: 1;
  background: transparent;
  border: none;
  padding: 8px 0;
  color: #f8fafc;
  font-size: 13px;
  font-weight: 700;
  outline: none;
}
.subscribe-input::placeholder {
  color: #cbd5e1;
  opacity: 0.95;
}
.subscribe-btn {
  background: linear-gradient(135deg, var(--neon-cyan), #1d4ed8);
  color: #ffffff;
  border: none;
  padding: 9px 16px;
  border-radius: 12px;
  font-weight: 800;
  font-size: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
  text-shadow: 0 1px 8px rgba(2, 6, 23, 0.28);
}
.arrow-svg {
  width: 14px;
  height: 14px;
  transition: transform 0.3s;
}
.subscribe-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(37, 99, 235, 0.36);
  filter: brightness(1.08);
}
.subscribe-btn:hover .arrow-svg {
  transform: translateX(3px);
}

/* Hotline Card style */
.hotline-card {
  display: flex;
  align-items: center;
  gap: 14px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px dashed rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  padding: 14px 16px;
  margin-bottom: 18px;
  transition: all 0.3s;
}
.hotline-card:hover {
  background: rgba(255, 255, 255, 0.03);
  border-color: rgba(37, 99, 235, 0.2);
}
.hotline-icon-box {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  border: 1px solid rgba(37, 99, 235, 0.18);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--neon-cyan);
  flex-shrink: 0;
  box-shadow: 0 0 10px rgba(37, 99, 235, 0.1);
}
.phone-svg {
  width: 18px;
  height: 18px;
}
.hotline-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.hotline-number {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  font-family: monospace;
  display: flex;
  align-items: center;
  gap: 10px;
}
.hotline-note {
  margin: 0;
  font-size: 11px;
  color: var(--text-muted);
}

/* Soundwave Equalizer animation */
.soundwave-container {
  display: flex;
  align-items: flex-end;
  gap: 2px;
  height: 12px;
}
.soundwave-bar {
  width: 2px;
  height: 100%;
  background-color: var(--neon-cyan);
  border-radius: 1px;
  animation: soundwaveBeat 0.8s ease-in-out infinite alternate;
  transform-origin: bottom;
}
.soundwave-bar:nth-child(1) { animation-delay: 0.1s; height: 35%; }
.soundwave-bar:nth-child(2) { animation-delay: 0.3s; height: 75%; }
.soundwave-bar:nth-child(3) { animation-delay: 0.5s; height: 100%; }
.soundwave-bar:nth-child(4) { animation-delay: 0.2s; height: 60%; }
.soundwave-bar:nth-child(5) { animation-delay: 0.4s; height: 40%; }

@keyframes soundwaveBeat {
  0% { transform: scaleY(0.25); }
  100% { transform: scaleY(1); }
}

.location-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  color: var(--text-muted);
  font-size: 12.5px;
  line-height: 1.5;
}
.loc-svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
  color: var(--neon-cyan);
  margin-top: 2px;
  filter: drop-shadow(0 0 6px rgba(37, 99, 235, 0.36));
}

/* ================= 3. BOTTOM FOOTER BAR ================= */
.bottom-bar {
  margin-top: 56px;
  padding-top: 32px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}
.bottom-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 32px;
}

.b-left {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.copyright {
  font-size: 13px;
  color: #64748b;
  margin: 0;
}
.copyright strong {
  color: #94a3b8;
}

.bottom-links {
  display: flex;
  align-items: center;
  gap: 12px;
}
.bottom-links a {
  font-size: 12.5px;
  color: #64748b;
  text-decoration: none;
  transition: color 0.2s;
  font-weight: 500;
}
.bottom-links a:hover {
  color: var(--neon-cyan);
  text-shadow: 0 0 5px rgba(37, 99, 235, 0.2);
}
.dot-separator {
  width: 4px;
  height: 4px;
  background: #334155;
  border-radius: 50%;
}

.b-right {
  display: flex;
  align-items: center;
  gap: 28px;
  flex-wrap: wrap;
}

/* Payment badges styling */
.payment-suite {
  display: flex;
  align-items: center;
  gap: 8px;
}
.pay-logo {
  border-radius: 8px;
  width: 48px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
  user-select: none;
  cursor: default;
}
.pay-logo svg {
  width: 26px;
  height: 26px;
}

.pay-logo.visa {
  background: #ffffff;
  color: #1a1f71;
  border: 1px solid #1a1f71;
}
.pay-logo.mastercard {
  background: #ffffff;
  border: 1px solid #e2e8f0;
}
.pay-logo.jcb {
  background: #002b5e;
  color: #ffffff;
  border: 1px solid #d11c29;
  font-size: 8px;
  font-weight: 800;
  letter-spacing: 0.5px;
}
.pay-logo.momo {
  background: #a50064;
  color: #ffffff;
  border: 1px solid #a50064;
  font-size: 8px;
  font-weight: 800;
  letter-spacing: -0.5px;
}
.pay-logo.vnpay {
  background: #ffffff;
  color: #005aab;
  border: 1px solid #f7941d;
  font-size: 7px;
  font-weight: 800;
}

/* Hover payment states glowing brand neon */
.pay-logo:hover {
  transform: translateY(-3px) scale(1.05);
  filter: brightness(1.1);
}
.pay-logo.visa:hover {
  box-shadow: 0 6px 15px rgba(26, 31, 113, 0.3);
}
.pay-logo.mastercard:hover {
  box-shadow: 0 6px 15px rgba(255, 95, 0, 0.2);
}
.pay-logo.jcb:hover {
  box-shadow: 0 6px 15px rgba(209, 28, 41, 0.3);
}
.pay-logo.momo:hover {
  box-shadow: 0 6px 15px rgba(165, 0, 100, 0.4);
}
.pay-logo.vnpay:hover {
  box-shadow: 0 6px 15px rgba(0, 90, 171, 0.3);
}

/* Compliance Certificates */
.compliance-suite {
  display: flex;
  align-items: center;
  gap: 12px;
}
.trust-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  background: rgba(255,255,255,0.01);
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 10px;
  font-weight: 700;
  color: #64748b;
  font-family: monospace;
}
.trust-badge.security-ssl {
  border-color: rgba(37, 99, 235, 0.3);
  background: rgba(37, 99, 235, 0.06);
  color: var(--neon-cyan);
  text-shadow: 0 0 5px rgba(37, 99, 235, 0.2);
}
.lock-svg {
  width: 10px;
  height: 10px;
  color: var(--neon-cyan);
}

/* Premium BCT Badge */
.gov-bct-badge {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  border-radius: 10px;
  padding: 5px 12px 5px 14px;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(225, 29, 72, 0.25);
  transition: all 0.3s;
  border: 1px solid rgba(225, 29, 72, 0.3);
}
.gov-bct-badge:hover {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 6px 20px rgba(225, 29, 72, 0.45);
  border-color: #fda4af;
}
.gov-content {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.gov-small {
  font-size: 7.5px;
  font-weight: 800;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.85);
  letter-spacing: 0.04em;
}
.gov-bold {
  font-size: 8.5px;
  font-weight: 900;
  color: #ffedd5;
  letter-spacing: 0.02em;
}
.gov-check-icon {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #ffffff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 6px rgba(255, 255, 255, 0.4);
}
.gov-check-icon svg {
  width: 9px;
  height: 9px;
}

/* ================= 4. BACK-TO-TOP WIDGET ================= */
.back-to-top-widget {
  position: fixed;
  bottom: 40px;
  right: 40px;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(11, 16, 29, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.05);
  cursor: pointer;
  z-index: 99;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  backdrop-filter: blur(8px);
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.progress-ring {
  position: absolute;
  transform: rotate(-90deg);
  pointer-events: none;
}
.progress-ring-indicator {
  transition: stroke-dashoffset 0.1s;
}
.arrow-container {
  color: var(--neon-cyan);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2;
  transition: transform 0.3s;
}
.up-arrow {
  width: 18px;
  height: 18px;
}
.widget-glow {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: var(--neon-cyan);
  filter: blur(14px);
  opacity: 0;
  transition: opacity 0.3s;
  z-index: 1;
}

.back-to-top-widget:hover {
  transform: translateY(-5px);
  border-color: var(--neon-cyan);
  box-shadow: 0 15px 35px rgba(37, 99, 235, 0.24);
}
.back-to-top-widget:hover .arrow-container {
  transform: translateY(-2px);
  color: #ffffff;
}
.back-to-top-widget:hover .widget-glow {
  opacity: 0.15;
}

/* Animations transitions */
.fade-in-scale-enter-active,
.fade-in-scale-leave-active {
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.fade-in-scale-enter-from,
.fade-in-scale-leave-to {
  transform: scale(0.7) translateY(20px);
  opacity: 0;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 1200px) {
  .directory-grid {
    grid-template-columns: 1.2fr 1fr 1fr 1.2fr;
    gap: 32px;
  }
  .newsletter-col {
    grid-column: span 2;
  }
}

@media (max-width: 992px) {
  .trust-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
  }
  .directory-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .brand-hub {
    grid-column: span 2;
  }
  .newsletter-col {
    grid-column: span 2;
  }
}

@media (max-width: 768px) {
  .bottom-inner {
    flex-direction: column;
    align-items: flex-start;
    gap: 24px;
  }
  .b-right {
    width: 100%;
    justify-content: space-between;
    gap: 20px;
  }
  .back-to-top-widget {
    bottom: 24px;
    right: 24px;
  }
}

@media (max-width: 576px) {
  .footer {
    padding-top: 60px;
  }
  .trust-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  .directory-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  .brand-hub, .newsletter-col {
    grid-column: span 1;
  }
  .subscribe-box-glass {
    flex-direction: column;
    background: transparent;
    border: none;
    padding: 0;
    margin-bottom: 20px;
  }
  .subscribe-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.02);
    border: 1.5px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 8px;
  }
  .subscribe-btn {
    width: 100%;
    justify-content: center;
    height: 44px;
  }
  .b-right {
    flex-direction: column;
    align-items: flex-start;
    gap: 20px;
  }
  .payment-suite {
    flex-wrap: wrap;
  }
  .compliance-suite {
    width: 100%;
    justify-content: space-between;
  }
}
</style>
