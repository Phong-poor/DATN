<template>
  <div class="scene" v-if="visible">
    <canvas ref="confettiCanvas" class="confetti-canvas"></canvas>
    <div class="backdrop" @click.self="dismiss"></div>

    <div class="modal" :class="{ 'modal--entered': entered }">
      <div class="glow-ring"></div>

      <button class="close-btn" @click="dismiss">✕</button>

      <div class="gift-badge">
        <div class="gift-icon-wrap">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
               stroke="white" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 12 20 22 4 22 4 12"></polyline>
            <rect x="2" y="7" width="20" height="5"></rect>
            <line x1="12" y1="22" x2="12" y2="7"></line>
            <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path>
            <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path>
          </svg>
        </div>
        <div class="gift-pulse"></div>
        <div class="gift-pulse gift-pulse--2"></div>
      </div>

      <div class="modal__content">
        <p class="label-top">🎉 VinaTech Elite 2026</p>

        <h1 class="title">Chúc Mừng Bạn!</h1>

        <!-- FIX lỗi ở đây -->
        <p class="subtitle">
          Phần quà đặc quyền dành riêng cho thành viên
        </p>

        <div class="cards">
          <div
            v-for="(promo, index) in promos"
            :key="promo.id"
            class="card"
            :class="[
              index === 0 ? 'card--purple' : 'card--pink',
              { 'card--visible': cardsVisible }
            ]"
          >
            <div class="card__shimmer"></div>

            <div class="card__header">
              <span class="card__tag"
                    :class="{ 'card__tag--pink': index === 1 }">

                <template v-if="promo.category === 'freeship'">
                  🚚
                </template>
                <template v-else>
                  %
                </template>

              </span>

              <span class="card__label"
                    :class="{ 'card__label--pink': index === 1 }">
                {{ getDiscountLabel(promo) }}
              </span>
            </div>

            <h2 class="card__code">{{ promo.code }}</h2>

            <p class="card__desc">
              <template v-if="promo.category === 'freeship' && promo.dieu_kien > 0">
                🛒 Đơn tối thiểu {{ formatPrice(promo.dieu_kien) }}<br/>
              </template>
              {{ promo.mota || 'Không có mô tả' }}
            </p>

            <button
              class="card__copy"
              :class="{ 'card__copy--pink': index === 1 }"
              @click="handleCopy(promo, index + 1)"
            >
              {{ (index === 0 ? copied1 : copied2)
                ? '✓ Đã sao chép'
                : 'Sao chép mã' }}
            </button>
          </div>
        </div>

        <!-- FIX: bỏ button thừa ở đây -->

        <div class="actions">
          <button class="btn-primary" @click="accept">
            <span>Nhận ngay</span>
            <span class="btn-primary__shine"></span>
          </button>
                    <button class="btn-secondary" @click="dismiss">
                        Bỏ qua
                    </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import api from '../../services/api'
import { getToken } from '@/services/auth'


export default {
    name: 'GiftPopup',
    props: {
        delay: { type: Number, default: 3000 },
        promosData: { type: Array, default: () => [] }
    },
    data() {
        return {
            visible: false,
            entered: false,
            cardsVisible: false,
            copied1: false,
            copied2: false,
            animFrame: null,
            showTimer: null,
            promos: [], // [0]: product, [1]: freeship
            loading: true
        }
    },
    async mounted() {
        // Chỉ hiện cho thành viên
        const token = getToken()
        if (!token) return

        if (this.promosData && this.promosData.length > 0) {
            const all = this.promosData
            const productPromo = all.find(p => p.category === 'product')
            const freeshipPromo = all.find(p => p.category === 'freeship')
            
            if (productPromo) this.promos.push(productPromo)
            if (freeshipPromo) this.promos.push(freeshipPromo)
        } else {
            // Đợi tải quà tặng về trước khi quyết định hiện popup
            await this.fetchGifts()
        }

        if (this.promos.length === 0) {
            return 
        }

        this.showTimer = setTimeout(() => {
            this.visible = true
            this.$nextTick(() => {
                setTimeout(() => { this.entered = true }, 30)
                setTimeout(() => { this.cardsVisible = true }, 350)
                setTimeout(() => { this.launchConfetti() }, 250)
            })
        }, this.delay)
    },
    beforeUnmount() {
        cancelAnimationFrame(this.animFrame)
        clearTimeout(this.showTimer)
    },
    methods: {
        launchConfetti() {
            const canvas = this.$refs.confettiCanvas
            if (!canvas) return
            const ctx = canvas.getContext('2d')
            canvas.width = window.innerWidth
            canvas.height = window.innerHeight
            const colors = ['#a855f7', '#ec4899', '#6366f1', '#f59e0b', '#10b981', '#3b82f6', '#f43f5e', '#facc15', '#c084fc', '#fb7185', '#818cf8', '#34d399']
            const shapes = ['circle', 'rect', 'triangle', 'star']
            const particles = []

            for (let i = 0; i < 160; i++) {
                particles.push({ x: Math.random() * canvas.width, y: -20 - Math.random() * 150, vx: (Math.random() - 0.5) * 5, vy: 2 + Math.random() * 5, color: colors[Math.floor(Math.random() * colors.length)], shape: shapes[Math.floor(Math.random() * shapes.length)], size: 6 + Math.random() * 9, rotation: Math.random() * 360, rotationSpeed: (Math.random() - 0.5) * 8, gravity: 0.09, drift: (Math.random() - 0.5) * 0.3, life: 1, decay: 0.004 + Math.random() * 0.003 })
            }
            setTimeout(() => {
                const cx = canvas.width / 2
                for (let i = 0; i < 100; i++) {
                    particles.push({ x: cx + (Math.random() - 0.5) * 80, y: canvas.height * 0.35, vx: (Math.random() - 0.5) * 13, vy: -7 - Math.random() * 8, color: colors[Math.floor(Math.random() * colors.length)], shape: shapes[Math.floor(Math.random() * shapes.length)], size: 7 + Math.random() * 9, rotation: Math.random() * 360, rotationSpeed: (Math.random() - 0.5) * 12, gravity: 0.2, drift: (Math.random() - 0.5) * 0.3, life: 1, decay: 0.006 + Math.random() * 0.004 })
                }
            }, 500)

            const draw = () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height)
                let alive = false
                for (const p of particles) {
                    if (p.life <= 0) continue
                    alive = true
                    p.vy += p.gravity; p.vx += p.drift; p.x += p.vx; p.y += p.vy; p.rotation += p.rotationSpeed; p.life -= p.decay
                    ctx.save(); ctx.globalAlpha = Math.max(0, p.life); ctx.fillStyle = p.color; ctx.translate(p.x, p.y); ctx.rotate(p.rotation * Math.PI / 180)
                    if (p.shape === 'circle') { ctx.beginPath(); ctx.arc(0, 0, p.size / 2, 0, Math.PI * 2); ctx.fill() }
                    else if (p.shape === 'rect') { ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2) }
                    else if (p.shape === 'triangle') { ctx.beginPath(); ctx.moveTo(0, -p.size / 2); ctx.lineTo(p.size / 2, p.size / 2); ctx.lineTo(-p.size / 2, p.size / 2); ctx.closePath(); ctx.fill() }
                    else { const r1 = p.size / 2, r2 = p.size / 4; ctx.beginPath(); for (let j = 0; j < 10; j++) { const a = j * Math.PI / 5, r = j % 2 === 0 ? r1 : r2; j === 0 ? ctx.moveTo(Math.cos(a) * r, Math.sin(a) * r) : ctx.lineTo(Math.cos(a) * r, Math.sin(a) * r) } ctx.closePath(); ctx.fill() }
                    ctx.restore()
                }
                if (alive) this.animFrame = requestAnimationFrame(draw)
            }
            draw()
        },
        async fetchGifts() {
            try {
                // Gọi API lấy các mã mà User chưa nhận
                const res = await api.get('/user/vouchers/available')
                const all = res.data
                
                const productPromo = all.find(p => p.category === 'product')
                const freeshipPromo = all.find(p => p.category === 'freeship')
                
                if (productPromo) this.promos.push(productPromo)
                if (freeshipPromo) this.promos.push(freeshipPromo)
            } catch (err) {
                console.error('Lỗi lấy quà tặng:', err)
            } finally {
                this.loading = false
            }
        },
        async claim(promoId) {
            try {
                await api.post('/user/vouchers/claim', { id_promotion: promoId })
                // Chúng ta không quan tâm nếu báo lỗi (ví dụ đã nhận rồi) 
                // vì mục tiêu là đảm bảo nó được lưu vào hồ sơ.
            } catch (err) {
                console.warn('Lưu voucher thất bại hoặc đã nhận:', err.response?.data?.message)
            }
        },
        formatPrice(price) {
            return new Intl.NumberFormat('vi-VN').format(price) + 'đ'
        },
        getDiscountLabel(p) {
            if (p.category === 'freeship') return 'MIỄN PHÍ VẬN CHUYỂN'
            if (p.type === 'percent') return `GIẢM GIÁ ${p.value}%`
            return `GIẢM ${this.formatPrice(p.value)}`
        },
        async handleCopy(promo, which) {
            this.copyCode(promo.code, which)
            // Tự động lưu vào hồ sơ
            await this.claim(promo.id)
        },
        copyCode(code, which) {
            navigator.clipboard?.writeText(code)
            if (which === 1) { this.copied1 = true; setTimeout(() => { this.copied1 = false }, 2000) }
            else { this.copied2 = true; setTimeout(() => { this.copied2 = false }, 2000) }
        },
        async accept() { 
            this.launchConfetti(); 
            // Lưu tất cả các mã đang hiện vào hồ sơ
            for (const p of this.promos) {
                await this.claim(p.id)
            }
            setTimeout(() => this.close(), 1600) 
        },
        dismiss() { this.close() },
        close() { this.entered = false; setTimeout(() => { this.visible = false }, 450) }
    }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap');

*,
*::before,
*::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0
}

.scene {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    font-family: 'DM Sans', sans-serif
}

.confetti-canvas {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 10000
}

.backdrop {
    position: fixed;
    inset: 0;
    background: radial-gradient(ellipse at center, rgba(30, 10, 60, 0.7) 0%, rgba(10, 5, 30, 0.84) 100%);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px)
}

.modal {
    position: relative;
    z-index: 100;
    width: min(460px, 92vw);
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.10) 0%, rgba(140, 100, 255, 0.09) 50%, rgba(255, 100, 180, 0.06) 100%);
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 24px;
    padding: 32px 30px 26px;
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.04), 0 24px 70px rgba(0, 0, 0, 0.5), 0 0 50px rgba(150, 80, 255, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.13);
    backdrop-filter: blur(30px);
    -webkit-backdrop-filter: blur(30px);
    transform: scale(0.72) translateY(36px);
    opacity: 0;
    transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.35s ease;
    overflow: hidden
}

.modal--entered {
    transform: scale(1) translateY(0);
    opacity: 1
}

.glow-ring {
    position: absolute;
    inset: -2px;
    border-radius: 26px;
    background: conic-gradient(from 0deg, #a855f7, #ec4899, #6366f1, #a855f7, #ec4899, #6366f1, #a855f7);
    opacity: 0.28;
    z-index: -1;
    animation: ring-spin 6s linear infinite;
    filter: blur(3px)
}

@keyframes ring-spin {
    to {
        transform: rotate(360deg)
    }
}

.close-btn {
    position: absolute;
    top: 13px;
    right: 14px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    width: 26px;
    height: 26px;
    font-size: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff
}

.gift-badge {
    position: relative;
    width: 54px;
    height: 54px;
    margin: 0 auto 16px
}

.gift-icon-wrap {
    position: relative;
    z-index: 2;
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: linear-gradient(135deg, #7c3aed, #a855f7, #ec4899);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 22px rgba(168, 85, 247, 0.5);
    animation: badge-float 3s ease-in-out infinite
}

@keyframes badge-float {

    0%,
    100% {
        transform: translateY(0)
    }

    50% {
        transform: translateY(-5px)
    }
}

.gift-pulse {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 2px solid rgba(168, 85, 247, 0.55);
    animation: pulse-out 2s ease-out infinite
}

.gift-pulse--2 {
    animation-delay: 0.7s;
    border-color: rgba(236, 72, 153, 0.35)
}

@keyframes pulse-out {
    0% {
        transform: scale(1);
        opacity: 0.8
    }

    100% {
        transform: scale(2.2);
        opacity: 0
    }
}

.modal__content {
    text-align: center
}

.label-top {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.08em;
    color: rgba(200, 160, 255, 0.9);
    text-transform: uppercase;
    margin-bottom: 7px
}

.title {
    font-family: 'Syne', sans-serif;
    font-size: clamp(24px, 5vw, 34px);
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
    letter-spacing: -0.01em;
    margin-bottom: 9px;
    background: linear-gradient(120deg, #fff 40%, #d8b4fe 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text
}

.subtitle {
    font-size: 12.5px;
    line-height: 1.6;
    color: rgba(210, 200, 230, 0.72);
    margin-bottom: 18px;
    font-weight: 300
}

.cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 11px;
    margin-bottom: 20px
}

.card {
    position: relative;
    border-radius: 14px;
    padding: 13px 13px 11px;
    text-align: left;
    border: 1px solid rgba(255, 255, 255, 0.12);
    overflow: hidden;
    transform: translateY(14px);
    opacity: 0;
    transition: transform 0.45s cubic-bezier(0.34, 1.4, 0.64, 1), opacity 0.35s ease
}

.card--visible {
    transform: translateY(0);
    opacity: 1
}

.card:hover {
    transform: translateY(-2px) scale(1.02);
    box-shadow: 0 10px 28px rgba(0, 0, 0, 0.3)
}

.card--purple {
    background: linear-gradient(145deg, rgba(109, 40, 217, 0.4), rgba(76, 29, 149, 0.28))
}

.card--pink {
    background: linear-gradient(145deg, rgba(219, 39, 119, 0.35), rgba(157, 23, 77, 0.22));
    transition-delay: 0.08s
}

.card__shimmer {
    position: absolute;
    inset: 0;
    background: linear-gradient(105deg, transparent 30%, rgba(255, 255, 255, 0.06) 50%, transparent 70%);
    transform: translateX(-100%);
    animation: shimmer 3s ease-in-out infinite
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%)
    }

    50%,
    100% {
        transform: translateX(200%)
    }
}

.card__header {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 7px
}

.card__tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 7px;
    flex-shrink: 0;
    background: rgba(139, 92, 246, 0.35);
    border: 1px solid rgba(167, 139, 250, 0.3);
    color: #e879f9;
    font-size: 12px;
    font-weight: 700
}

.card__tag--pink {
    background: rgba(236, 72, 153, 0.3);
    border-color: rgba(249, 168, 212, 0.3);
    color: #f9a8d4
}

.card__label {
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 0.1em;
    color: rgba(196, 181, 253, 0.75);
    text-transform: uppercase
}

.card__label--pink {
    color: rgba(251, 207, 232, 0.75)
}

.card__code {
    font-family: 'Syne', sans-serif;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.04em;
    margin-bottom: 5px
}

.card__desc {
    font-size: 10px;
    line-height: 1.5;
    color: rgba(210, 200, 230, 0.55);
    margin-bottom: 9px;
    font-weight: 300
}

.card__copy {
    width: 100%;
    padding: 6px 0;
    border-radius: 7px;
    border: 1px solid rgba(167, 139, 250, 0.4);
    background: rgba(139, 92, 246, 0.15);
    color: #c4b5fd;
    font-size: 10.5px;
    font-weight: 500;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    transition: all 0.2s ease
}

.card__copy:hover {
    background: rgba(139, 92, 246, 0.3);
    border-color: rgba(167, 139, 250, 0.7);
    color: #fff
}

.card__copy--pink {
    border-color: rgba(249, 168, 212, 0.4);
    background: rgba(236, 72, 153, 0.15);
    color: #f9a8d4
}

.card__copy--pink:hover {
    background: rgba(236, 72, 153, 0.3);
    border-color: rgba(249, 168, 212, 0.7);
    color: #fff
}

.actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px
}

.btn-primary {
    position: relative;
    padding: 11px 28px;
    border-radius: 50px;
    border: none;
    background: linear-gradient(135deg, #7c3aed, #a855f7, #ec4899);
    background-size: 200% 200%;
    animation: grad-shift 3s ease infinite;
    color: #fff;
    font-family: 'DM Sans', sans-serif;
    font-size: 13.5px;
    font-weight: 500;
    cursor: pointer;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(168, 85, 247, 0.42);
    transition: transform 0.2s ease, box-shadow 0.2s ease
}

.btn-primary:hover {
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 8px 26px rgba(168, 85, 247, 0.58)
}

.btn-primary:active {
    transform: scale(0.97)
}

.btn-secondary {
    padding: 11px 28px;
    border-radius: 50px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.8);
    font-family: 'DM Sans', sans-serif;
    font-size: 13.5px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.5);
    color: #fff;
    transform: translateY(-2px)
}

.btn-secondary:active {
    transform: scale(0.97)
}

@keyframes grad-shift {
    0% {
        background-position: 0% 50%
    }

    50% {
        background-position: 100% 50%
    }

    100% {
        background-position: 0% 50%
    }
}

.btn-primary__shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 60%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.22), transparent);
    animation: shine 2.5s ease-in-out infinite
}

@keyframes shine {
    0% {
        left: -100%
    }

    50%,
    100% {
        left: 200%
    }
}

.btn-ghost {
    background: none;
    border: none;
    color: rgba(200, 190, 220, 0.6);
    font-family: 'DM Sans', sans-serif;
    font-size: 12.5px;
    font-weight: 400;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 7px;
    transition: color 0.2s ease, background 0.2s ease
}

.btn-ghost:hover {
    color: rgba(200, 190, 220, 0.95);
    background: rgba(255, 255, 255, 0.06)
}

@media (max-width: 576px) {
    .modal {
        width: 94vw;
        padding: 22px 16px 16px;
        border-radius: 20px;
    }
    
    .gift-badge {
        width: 42px;
        height: 42px;
        margin: 0 auto 10px;
    }
    
    .gift-icon-wrap {
        width: 42px;
        height: 42px;
    }
    
    .gift-icon-wrap svg {
        width: 18px;
        height: 18px;
    }
    
    .title {
        font-size: 20px;
        margin-bottom: 4px;
    }
    
    .label-top {
        font-size: 10px;
        margin-bottom: 4px;
    }
    
    .subtitle {
        font-size: 11.5px;
        margin-bottom: 12px;
    }
    
    .cards {
        grid-template-columns: 1fr;
        gap: 8px;
        max-height: 175px;
        overflow-y: auto;
        padding-right: 4px;
        margin-bottom: 14px;
    }
    
    .cards::-webkit-scrollbar {
        width: 4px;
    }
    .cards::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
    }
    .cards::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 10px;
    }
    
    .card {
        padding: 10px 12px 8px;
    }
    
    .card__header {
        margin-bottom: 4px;
    }
    
    .card__tag {
        width: 20px;
        height: 20px;
        font-size: 10px;
        border-radius: 5px;
    }
    
    .card__code {
        font-size: 11px;
        margin-bottom: 3px;
    }
    
    .card__desc {
        font-size: 9px;
        margin-bottom: 6px;
    }
    
    .card__copy {
        padding: 4px 0;
        font-size: 9.5px;
    }
    
    .actions {
        gap: 10px;
    }
    
    .btn-primary {
        padding: 9px 24px;
        font-size: 12.5px;
        box-shadow: 0 4px 14px rgba(168, 85, 247, 0.35);
    }
    
    .btn-ghost {
        font-size: 11.5px;
        padding: 5px 8px;
    }
}
</style>
