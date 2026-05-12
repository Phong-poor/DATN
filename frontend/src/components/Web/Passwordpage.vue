<script setup>
import { ref, computed } from 'vue'

const form = ref({ current: '', newPass: '', confirm: '' })
const showCurrent = ref(false)
const showNew = ref(false)
const showConfirm = ref(false)
const saving = ref(false)
const saved = ref(false)
const errors = ref({})

const strength = computed(() => {
    const p = form.value.newPass
    if (!p) return 0
    let score = 0
    if (p.length >= 8) score++
    if (/[A-Z]/.test(p)) score++
    if (/[0-9]/.test(p)) score++
    if (/[^A-Za-z0-9]/.test(p)) score++
    return score
})

const strengthLabel = computed(() => ['', 'Yếu', 'Trung bình', 'Mạnh', 'Rất mạnh'][strength.value])
const strengthColor = computed(() => ['', '#ef4444', '#f59e0b', '#2563eb', '#16a34a'][strength.value])

const requirements = computed(() => [
    { label: 'Tối thiểu 8 ký tự', ok: form.value.newPass.length >= 8 },
    { label: 'Có chữ hoa (A-Z)', ok: /[A-Z]/.test(form.value.newPass) },
    { label: 'Có số (0-9)', ok: /[0-9]/.test(form.value.newPass) },
    { label: 'Có ký tự đặc biệt', ok: /[^A-Za-z0-9]/.test(form.value.newPass) },
])

const validate = () => {
    errors.value = {}
    if (!form.value.current) errors.value.current = 'Vui lòng nhập mật khẩu hiện tại'
    if (!form.value.newPass) errors.value.newPass = 'Vui lòng nhập mật khẩu mới'
    else if (strength.value < 2) errors.value.newPass = 'Mật khẩu quá yếu'
    if (form.value.newPass !== form.value.confirm) errors.value.confirm = 'Mật khẩu không khớp'
    return Object.keys(errors.value).length === 0
}

const save = async () => {
    if (!validate()) return
    saving.value = true
    await new Promise(r => setTimeout(r, 1000))
    saving.value = false
    saved.value = true
    form.value = { current: '', newPass: '', confirm: '' }
    setTimeout(() => saved.value = false, 3000)
}
</script>

<template>
    <div class="page">
        <transition name="toast">
            <div class="toast" v-if="saved">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                </svg>
                Đổi mật khẩu thành công!
            </div>
        </transition>

        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Đổi mật khẩu</h1>
                <p class="page-sub">Cập nhật mật khẩu để bảo mật tài khoản</p>
            </div>

            <div class="two-col">
                <!-- Form -->
                <div class="card">
                    <form @submit.prevent="save" class="form">
                        <!-- Current password -->
                        <div class="form-group" :class="{ error: errors.current }">
                            <label>Mật khẩu hiện tại</label>
                            <div class="input-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="11" width="18" height="11" rx="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                                <input :type="showCurrent ? 'text' : 'password'" v-model="form.current"
                                    placeholder="••••••••" />
                                <button type="button" class="eye-btn" @click="showCurrent = !showCurrent">
                                    <svg v-if="!showCurrent" viewBox="0 0 24 24" fill="none">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg v-else viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                        <line x1="1" y1="1" x2="23" y2="23" />
                                    </svg>
                                </button>
                            </div>
                            <span class="err-msg" v-if="errors.current">{{ errors.current }}</span>
                        </div>

                        <!-- New password -->
                        <div class="form-group" :class="{ error: errors.newPass }">
                            <label>Mật khẩu mới</label>
                            <div class="input-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none">
                                    <path
                                        d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4" />
                                </svg>
                                <input :type="showNew ? 'text' : 'password'" v-model="form.newPass"
                                    placeholder="••••••••" />
                                <button type="button" class="eye-btn" @click="showNew = !showNew">
                                    <svg v-if="!showNew" viewBox="0 0 24 24" fill="none">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg v-else viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                        <line x1="1" y1="1" x2="23" y2="23" />
                                    </svg>
                                </button>
                            </div>
                            <!-- Strength bar -->
                            <div class="strength-bar" v-if="form.newPass">
                                <div class="strength-track">
                                    <div class="strength-fill"
                                        :style="{ width: (strength / 4 * 100) + '%', background: strengthColor }"></div>
                                </div>
                                <span class="strength-label" :style="{ color: strengthColor }">{{ strengthLabel
                                    }}</span>
                            </div>
                            <span class="err-msg" v-if="errors.newPass">{{ errors.newPass }}</span>
                        </div>

                        <!-- Confirm password -->
                        <div class="form-group" :class="{ error: errors.confirm }">
                            <label>Xác nhận mật khẩu mới</label>
                            <div class="input-wrap">
                                <svg class="input-icon" viewBox="0 0 24 24" fill="none">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <polyline points="22 4 12 14.01 9 11.01" />
                                </svg>
                                <input :type="showConfirm ? 'text' : 'password'" v-model="form.confirm"
                                    placeholder="••••••••" />
                                <button type="button" class="eye-btn" @click="showConfirm = !showConfirm">
                                    <svg v-if="!showConfirm" viewBox="0 0 24 24" fill="none">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg v-else viewBox="0 0 24 24" fill="none">
                                        <path
                                            d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                        <line x1="1" y1="1" x2="23" y2="23" />
                                    </svg>
                                </button>
                            </div>
                            <span class="err-msg" v-if="errors.confirm">{{ errors.confirm }}</span>
                        </div>

                        <button type="submit" class="btn-save" :disabled="saving">
                            <svg v-if="saving" class="spin" viewBox="0 0 24 24" fill="none">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                            </svg>
                            {{ saving ? 'Đang cập nhật...' : 'Cập nhật mật khẩu' }}
                        </button>
                    </form>
                </div>

                <!-- Requirements + Tips -->
                <div class="side-info">
                    <div class="req-card">
                        <h3 class="req-title">Yêu cầu mật khẩu</h3>
                        <ul class="req-list">
                            <li v-for="req in requirements" :key="req.label" :class="{ ok: req.ok }">
                                <svg v-if="req.ok" viewBox="0 0 24 24" fill="none">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <svg v-else viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" />
                                </svg>
                                {{ req.label }}
                            </li>
                        </ul>
                    </div>

                    <div class="tip-card">
                        <div class="tip-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="tip-title">Mẹo bảo mật</h4>
                            <ul class="tip-list">
                                <li>Không dùng thông tin cá nhân</li>
                                <li>Dùng mật khẩu khác nhau cho mỗi trang</li>
                                <li>Thay đổi mật khẩu định kỳ 3–6 tháng</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page {
    min-height: 100vh;
    background: #f8fafc;
    padding: 32px 30px;
    font-family: system-ui, sans-serif;
}

.container {
    max-width: 900px;
    margin: auto;
}

.page-header {
    margin-bottom: 24px;
}

.page-title {
    font-size: 22px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px;
}

.page-sub {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

.two-col {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 20px;
    align-items: start;
}

.card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #e5e7eb;
    padding: 28px 32px;
}

/* FORM */
.form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
}

.input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 14px;
    width: 16px;
    height: 16px;
    stroke: #94a3b8;
    stroke-width: 1.8;
    fill: none;
    pointer-events: none;
}

.input-wrap input {
    width: 100%;
    padding: 11px 44px 11px 40px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    color: #1e293b;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

.input-wrap input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-group.error .input-wrap input {
    border-color: #ef4444;
}

.eye-btn {
    position: absolute;
    right: 12px;
    width: 28px;
    height: 28px;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.eye-btn svg {
    width: 16px;
    height: 16px;
    stroke: #94a3b8;
    stroke-width: 1.8;
    fill: none;
}

.strength-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 4px;
}

.strength-track {
    flex: 1;
    height: 5px;
    background: #e2e8f0;
    border-radius: 99px;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    border-radius: 99px;
    transition: width 0.3s, background 0.3s;
}

.strength-label {
    font-size: 12px;
    font-weight: 600;
    min-width: 72px;
    text-align: right;
}

.err-msg {
    font-size: 12px;
    color: #ef4444;
    font-weight: 500;
}

.btn-save {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    border-radius: 12px;
    background: #2563eb;
    border: none;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    margin-top: 4px;
}

.btn-save:hover {
    background: #1d4ed8;
}

.btn-save:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.spin {
    width: 16px;
    height: 16px;
    stroke: #fff;
    stroke-width: 2.5;
    fill: none;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* SIDE INFO */
.req-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 20px;
    margin-bottom: 14px;
}

.req-title {
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 14px;
}

.req-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.req-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    color: #94a3b8;
    font-weight: 500;
    transition: color 0.2s;
}

.req-list li svg {
    width: 16px;
    height: 16px;
    stroke: #cbd5e1;
    stroke-width: 2.5;
    fill: none;
    flex-shrink: 0;
    transition: stroke 0.2s;
}

.req-list li.ok {
    color: #16a34a;
}

.req-list li.ok svg {
    stroke: #16a34a;
}

.tip-card {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 16px;
    padding: 18px 20px;
    display: flex;
    gap: 14px;
}

.tip-icon {
    flex-shrink: 0;
}

.tip-icon svg {
    width: 20px;
    height: 20px;
    stroke: #2563eb;
    stroke-width: 1.8;
    fill: none;
    margin-top: 2px;
}

.tip-title {
    font-size: 13px;
    font-weight: 700;
    color: #1e40af;
    margin: 0 0 8px;
}

.tip-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.tip-list li {
    font-size: 12px;
    color: #3b82f6;
    padding-left: 12px;
    position: relative;
}

.tip-list li::before {
    content: '•';
    position: absolute;
    left: 0;
}

/* TOAST */
.toast {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 9999;
    background: #0f172a;
    color: #fff;
    padding: 12px 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.toast svg {
    width: 18px;
    height: 18px;
    stroke: #4ade80;
    stroke-width: 2.5;
    fill: none;
}

.toast-enter-active {
    transition: all 0.3s cubic-bezier(0.34, 1.4, 0.64, 1);
}

.toast-leave-active {
    transition: all 0.2s ease;
}

.toast-enter-from {
    opacity: 0;
    transform: translateY(-12px);
}

.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>