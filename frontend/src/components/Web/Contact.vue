<script setup>
import { ref } from 'vue'
import Header from '../Layout/Header.vue'
import Footer from '../Layout/Footer.vue'

const name = ref('')
const phone = ref('')
const email = ref('')
const message = ref('')
const error = ref('')
const success = ref(false)
const loading = ref(false)

import { onMounted } from 'vue'

onMounted(async () => {
    try {
        const res = await fetch('http://localhost:8000/api/user/profile', {
            headers: {
                Authorization: 'Bearer ' + localStorage.getItem('token')
            }
        })

        const data = await res.json()

        name.value = data.name
        email.value = data.email
        phone.value = data.phone

    } catch (err) {
        console.log('Chưa đăng nhập')
    }
})

async function submitForm() {
    if (loading.value) return

    if (!name.value || !phone.value || !email.value || !message.value) {
        error.value = 'Vui lòng nhập đầy đủ thông tin'
        success.value = false
        return
    }

    try {
        error.value = ''
        success.value = false
        loading.value = true

        const res = await fetch('http://localhost:8000/api/lien-he', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: name.value,
                email: email.value,
                phone: phone.value,
                message: message.value,
            }),
        })

        const data = await res.json()

        if (data.status) {
            success.value = true
            error.value = ''

            // reset form
            name.value = ''
            phone.value = ''
            email.value = ''
            message.value = ''

            setTimeout(() => {
                success.value = false
            }, 4000)
        } else {
            error.value = data.message || 'Gửi thất bại'
        }
    } catch (err) {
        error.value = 'Lỗi kết nối server'
    } finally {
        loading.value = false
    }
}

const mapLink =
    'https://www.google.com/maps?q=123+Đường+Công+Nghệ+Quận+1+TPHCM'

const infos = [
    {
        icon: '📍',
        label: 'Địa chỉ',
        value: 'Tòa nhà VinaTech, 123 Đường Công Nghệ, Quận 1, TP. Hồ Chí Minh',
        color: '#dbeafe',
    },
    {
        icon: '📞',
        label: 'Hotline',
        value: '1900 8888',
        bold: true,
        color: '#dcfce7',
    },
    {
        icon: '✉️',
        label: 'Email',
        value: 'support@vinatech.vn',
        color: '#ede9fe',
    },
    {
        icon: '🕐',
        label: 'Giờ làm việc',
        value: 'T2 – T6: 8:00 – 18:00 | T7: 8:00 – 12:00',
        color: '#fef9c3',
    },
]
</script>

<template>
    <Header />

    <div class="page">
        <!-- HERO -->
        <section class="hero">
            <span class="hero-badge">Liên hệ với chúng tôi</span>
            <h1>
                Chúng tôi luôn sẵn sàng <br />
                <span class="gradient-text">hỗ trợ bạn</span>
            </h1>
            <p>
                Kết nối với đội ngũ chuyên gia VinaTech để nhận tư vấn và giải
                pháp công nghệ tối ưu cho doanh nghiệp và cá nhân.
            </p>
        </section>

        <!-- MAIN -->
        <section class="contact-section">
            <div class="container">
                <div class="contact-grid">
                    <!-- FORM -->
                    <div class="form-card">
                        <form @submit.prevent="submitForm">
                            <div class="form-top">
                                <h3>Gửi tin nhắn cho chúng tôi</h3>
                                <p>Chúng tôi sẽ phản hồi trong vòng 24 giờ</p>
                            </div>

                            <div class="form-row">
                                <div class="input-group">
                                    <label>Họ tên <span class="req">*</span></label>
                                    <input
                                        v-model="name"
                                        placeholder="Nguyễn Văn A"
                                    />
                                </div>

                                <div class="input-group">
                                    <label>Số điện thoại <span class="req">*</span></label>
                                    <input
                                        v-model="phone"
                                        placeholder="090 123 4567"
                                    />
                                </div>
                            </div>

                            <div class="input-group">
                                <label>Email <span class="req">*</span></label>
                                <input
                                    v-model="email"
                                    type="email"
                                    placeholder="example@vinatech.com"
                                />
                            </div>

                            <div class="input-group">
                                <label>Nội dung <span class="req">*</span></label>
                                <textarea
                                    v-model="message"
                                    placeholder="Bạn cần chúng tôi hỗ trợ vấn đề gì?"
                                ></textarea>
                            </div>

                            <p v-if="error" class="msg error">⚠ {{ error }}</p>
                            <p v-if="success" class="msg success">
                                ✓ gửi thành công! chúng tôi sẽ liên hệ trong 24 giờ tới.
                            </p>

                            <button
                                type="submit"
                                class="submit-btn"
                                :disabled="loading"
                            >
                                <span v-if="loading" class="spinner"></span>
                                <span v-else>Gửi yêu cầu →</span>
                            </button>
                        </form>
                    </div>

                    <!-- INFO -->
                    <div class="info-col">
                        <div class="info-card">
                            <h3>Thông tin liên hệ</h3>

                            <div class="info-list">
                                <div
                                    class="info-item"
                                    v-for="item in infos"
                                    :key="item.label"
                                >
                                    <div
                                        class="info-icon"
                                        :style="{ background: item.color }"
                                    >
                                        {{ item.icon }}
                                    </div>

                                    <div>
                                        <p class="info-label">{{ item.label }}</p>
                                        <p
                                            :class="
                                                item.bold
                                                    ? 'info-val bold'
                                                    : 'info-val'
                                            "
                                        >
                                            {{ item.value }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- MAP -->
                        <a :href="mapLink" target="_blank" class="map-card">
                            <iframe
                                src="https://www.google.com/maps?q=123+Đường+Công+Nghệ+Quận+1+TPHCM&output=embed"
                                loading="lazy"
                            ></iframe>

                            <div class="map-overlay">
                                <span>Xem bản đồ chi tiết →</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <Footer />
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

* {
  box-sizing: border-box;
}

.page {
  font-family: 'Inter', sans-serif;
  background: #f8fafc;
}

.hero {
  text-align: center;
  padding: 80px 20px 60px;
  background: linear-gradient(160deg, #eef2ff 0%, #f8fafc 60%);
}

.hero-badge {
  background: #e0e7ff;
  color: #4f46e5;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
}

.hero h1 {
  font-size: 46px;
  font-weight: 800;
  margin: 20px 0;
}

.gradient-text {
  background: linear-gradient(90deg, #4f46e5, #2563eb);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.contact-section {
  padding-bottom: 80px;
}

.container {
  width: min(1100px, 95%);
  margin: auto;
}

.contact-grid {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 24px;
}

.form-card,
.info-card,
.map-card {
  background: white;
  border-radius: 20px;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
}

.form-card,
.info-card {
  padding: 28px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.input-group {
  margin-bottom: 16px;
}

.input-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 6px;
}

.input-group input,
.input-group textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
}

.input-group textarea {
  min-height: 130px;
}

.msg {
  padding: 10px;
  border-radius: 8px;
  margin-bottom: 12px;
}

.error {
  background: #fef2f2;
  color: #dc2626;
}

.success {
  background: #f0fdf4;
  color: #16a34a;
}

.submit-btn {
  width: 100%;
  padding: 14px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #2563eb, #4f46e5);
  color: white;
  font-weight: 700;
  cursor: pointer;
}

.info-col {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-item {
  display: flex;
  gap: 12px;
  margin-bottom: 14px;
}

.info-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bold {
  font-weight: 700;
}

.map-card iframe {
  width: 100%;
  height: 180px;
  border: none;
}

.map-overlay {
  padding: 14px;
  text-align: center;
}

@media (max-width: 768px) {
  .contact-grid,
  .form-row {
    grid-template-columns: 1fr;
  }

  .hero h1 {
    font-size: 30px;
  }
}
</style>