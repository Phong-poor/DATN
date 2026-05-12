<script setup>
import { ref } from 'vue'

const activeTab = ref('Cài đặt chung')
const brandName = ref('VinaTech')
const slogan = ref('Tiên phong giải pháp công nghệ')
const font = ref('Be Vietnam Pro (Default)')
const darkMode = ref('Tối')
const footerText = ref('')
const facebook = ref('facebook.com/vinatech')
const linkedin = ref('linkedin.com/company/vinatech')
const twitter = ref('twitter.com/vinatech')
const primaryColor = ref('#0052CC')
const accentColor = ref('#7B2FBE')

const logoRef = ref(null)
const faviconRef = ref(null)
const logoPreview = ref('')
const faviconPreview = ref('')

const tabs = [
    { label: 'Cài đặt chung', icon: '⚙️' },
    { label: 'Giao diện', icon: '🎨' },
    { label: 'Thông báo', icon: '🔔' },
    { label: 'Bảo mật', icon: '🛡️' },
]

const onLogoChange = (e) => {
    const f = e.target.files[0]
    if (!f) return
    const r = new FileReader()
    r.onload = ev => logoPreview.value = ev.target.result
    r.readAsDataURL(f)
}

const onFaviconChange = (e) => {
    const f = e.target.files[0]
    if (!f) return
    const r = new FileReader()
    r.onload = ev => faviconPreview.value = ev.target.result
    r.readAsDataURL(f)
}

const saved = ref(false)
const save = () => {
    saved.value = true
    setTimeout(() => saved.value = false, 2000)
}
</script>

<template>
    <div class="page">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input placeholder="Tìm kiếm cài đặt..." />
            </div>
            <div class="topbar-right">
                <button class="icon-btn">🔔</button>
                <button class="icon-btn">🌙</button>
                <div class="admin-info">
                    <div class="admin-text">
                        <b>Admin VinaTech</b>
                        <span>QUẢN TRỊ VIÊN</span>
                    </div>
                    <div class="admin-avatar">AV</div>
                </div>
            </div>
        </div>

        <!-- HEADER -->
        <div class="page-header">
            <div>
                <h1>Cài đặt hệ thống</h1>
                <p>Tuỳ chỉnh nhận diện thương hiệu và cấu hình vận hành VinaTech.</p>
            </div>
            <button class="btn-save" @click="save">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                {{ saved ? 'Đã lưu!' : 'Lưu thay đổi' }}
            </button>
        </div>

        <!-- TABS -->
        <div class="tabs">
            <button
                v-for="t in tabs" :key="t.label"
                class="tab" :class="{ active: activeTab === t.label }"
                @click="activeTab = t.label"
            >
                <span>{{ t.icon }}</span> {{ t.label }}
            </button>
        </div>

        <!-- CONTENT -->
        <div class="content" v-if="activeTab === 'Cài đặt chung'">

            <!-- ROW 1 -->
            <div class="grid-2">

                <!-- WEBSITE INFO -->
                <div class="card">
                    <div class="card-title">
                        <span class="bar blue"></span> Thông tin website
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>TÊN THƯƠNG HIỆU</label>
                            <input v-model="brandName" />
                        </div>
                        <div class="form-group">
                            <label>SLOGAN THƯƠNG HIỆU</label>
                            <input v-model="slogan" />
                        </div>
                    </div>

                    <div class="form-row" style="margin-top:16px">
                        <!-- LOGO -->
                        <div class="form-group">
                            <label>LOGO WEBSITE</label>
                            <input ref="logoRef" type="file" accept="image/*" style="display:none" @change="onLogoChange" />
                            <div class="upload-zone" @click="logoRef.click()">
                                <template v-if="!logoPreview">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                    <p>Tải lên Logo (PNG/SVG)</p>
                                    <small>Khuyến khích 200×60px</small>
                                </template>
                                <img v-else :src="logoPreview" class="upload-preview" />
                            </div>
                        </div>

                        <!-- FAVICON -->
                        <div class="form-group">
                            <label>FAVICON</label>
                            <input ref="faviconRef" type="file" accept="image/*" style="display:none" @change="onFaviconChange" />
                            <div class="upload-zone" @click="faviconRef.click()">
                                <template v-if="!faviconPreview">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                        <path d="M3 9h18M9 21V9"/>
                                    </svg>
                                    <p>Tải lên Favicon</p>
                                    <small>Kích thước 32×32px</small>
                                </template>
                                <img v-else :src="faviconPreview" class="upload-preview favicon-preview" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VISUAL IDENTITY -->
                <div class="side-col">

                    <div class="card">
                        <div class="card-title">
                            <span class="bar purple"></span> Nhận diện thị giác
                        </div>

                        <label class="small-label">Màu sắc chủ đạo</label>

                        <div class="color-row">
                            <div class="color-item">
                                <div class="color-swatch" :style="{ background: primaryColor }"></div>
                                <div class="color-info">
                                    <span>Primary Tech Blue</span>
                                    <small>{{ primaryColor }}</small>
                                </div>
                                <label class="edit-color-btn" title="Đổi màu">
                                    ✏️
                                    <input type="color" v-model="primaryColor" style="opacity:0;position:absolute;width:0;height:0" />
                                </label>
                            </div>
                            <div class="color-item">
                                <div class="color-swatch" :style="{ background: accentColor }"></div>
                                <div class="color-info">
                                    <span>Tertiary Accent</span>
                                    <small>{{ accentColor }}</small>
                                </div>
                                <label class="edit-color-btn" title="Đổi màu">
                                    ✏️
                                    <input type="color" v-model="accentColor" style="opacity:0;position:absolute;width:0;height:0" />
                                </label>
                            </div>
                        </div>

                        <label class="small-label" style="margin-top:16px">Phông chữ hệ thống</label>
                        <select v-model="font" class="font-select">
                            <option>Be Vietnam Pro (Default)</option>
                            <option>Inter</option>
                            <option>Roboto</option>
                            <option>Nunito</option>
                        </select>
                    </div>

                    <!-- DARK MODE -->
                    <div class="card dark-card">
                        <div class="dark-header">
                            <b>Chế độ hiển thị</b>
                            <p>Áp dụng giao diện tối/sáng tự động dựa trên thiết bị người dùng.</p>
                        </div>
                        <div class="mode-btns">
                            <button
                                v-for="m in ['Sáng','Tối','Hệ thống']" :key="m"
                                class="mode-btn" :class="{ 'mode-active': darkMode === m }"
                                @click="darkMode = m"
                            >{{ m }}</button>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ROW 2: SOCIAL + FOOTER -->
            <div class="card" style="margin-top:20px">
                <div class="card-title">
                    <span class="bar blue"></span> Cấu hình Mạng xã hội &amp; Chân trang
                </div>

                <div class="form-row" style="align-items:flex-start">

                    <!-- SOCIAL -->
                    <div class="form-group">
                        <label>LIÊN KẾT XÃ HỘI</label>
                        <div class="social-list">
                            <div class="social-item">
                                <span class="social-icon fb">f</span>
                                <input v-model="facebook" />
                            </div>
                            <div class="social-item">
                                <span class="social-icon li">in</span>
                                <input v-model="linkedin" />
                            </div>
                            <div class="social-item">
                                <span class="social-icon tw">𝕏</span>
                                <input v-model="twitter" />
                            </div>
                        </div>
                    </div>

                    <!-- FOOTER TEXT -->
                    <div class="form-group">
                        <label>THÔNG TIN FOOTER BẢN QUYỀN</label>
                        <textarea v-model="footerText" placeholder="Nhập nội dung hiển thị ở cuối trang..." rows="5"></textarea>

                        <div class="tip-box">
                            <span class="tip-icon">💡</span>
                            <div>
                                <b>Mẹo thiết kế:</b>
                                <p>Sử dụng các thẻ {year} để tự động cập nhật năm hiện tại và {brand} cho tên thương hiệu của bạn.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- OTHER TABS placeholder -->
        <div v-else class="content">
            <div class="card empty-tab">
                <p>Nội dung tab <b>{{ activeTab }}</b> đang được phát triển.</p>
            </div>
        </div>

    </div>
</template>

<style scoped>
* { box-sizing: border-box; }

.page {
    background: #f5f7fb;
    min-height: 100vh;
    font-family: sans-serif;
    padding-bottom: 60px;
}

/* TOPBAR */
.topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 32px; background: white; border-bottom: 1px solid #f1f5f9;
}
.search-box {
    position: relative; width: 220px;
}
.search-box svg {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    width: 14px; height: 14px; color: #94a3b8; pointer-events: none;
}
.search-box input {
    width: 100%; padding: 8px 12px 8px 32px; border-radius: 8px;
    border: 1px solid #e2e8f0; font-size: 13px; color: #0f172a; outline: none;
    background: #f8fafc;
}
.topbar-right { display: flex; align-items: center; gap: 10px; }
.icon-btn { background: none; border: none; font-size: 16px; cursor: pointer; padding: 6px; border-radius: 8px; }
.icon-btn:hover { background: #f1f5f9; }
.admin-info { display: flex; align-items: center; gap: 10px; }
.admin-text { text-align: right; }
.admin-text b { display: block; font-size: 13px; color: #0f172a; }
.admin-text span { font-size: 10px; color: #94a3b8; letter-spacing: 0.05em; }
.admin-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg,#2563eb,#4f46e5);
    color: white; font-size: 12px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}

/* PAGE HEADER */
.page-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    padding: 28px 32px 20px;
}
.page-header h1 { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0 0 4px; }
.page-header p  { font-size: 13px; color: #64748b; margin: 0; }

.btn-save {
    display: flex; align-items: center; gap: 7px;
    padding: 10px 20px; border-radius: 10px; border: none;
    background: linear-gradient(135deg,#2563eb,#4f46e5);
    color: white; font-size: 13px; font-weight: 600; cursor: pointer;
    transition: opacity 0.2s, transform 0.2s; white-space: nowrap;
}
.btn-save svg { width: 15px; height: 15px; }
.btn-save:hover { opacity: 0.9; transform: translateY(-1px); }

/* TABS */
.tabs {
    display: flex; gap: 4px; padding: 0 32px; margin-bottom: 24px;
    border-bottom: 1px solid #e2e8f0;
}
.tab {
    display: flex; align-items: center; gap: 6px;
    padding: 10px 16px; background: none; border: none;
    font-size: 13px; font-weight: 500; color: #64748b; cursor: pointer;
    border-bottom: 2px solid transparent; margin-bottom: -1px; transition: all 0.2s;
}
.tab:hover { color: #334155; }
.tab.active { color: #2563eb; border-bottom-color: #2563eb; font-weight: 600; }

/* CONTENT */
.content { padding: 0 32px; }

.grid-2 { display: grid; grid-template-columns: 1fr 340px; gap: 20px; }

.side-col { display: flex; flex-direction: column; gap: 16px; }

/* CARD */
.card {
    background: white; border-radius: 14px; border: 1px solid #f1f5f9; padding: 22px;
}
.card-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 20px;
}
.bar { width: 4px; height: 18px; border-radius: 2px; flex-shrink: 0; }
.bar.blue   { background: #2563eb; }
.bar.purple { background: #7c3aed; }

/* FORM */
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
label { font-size: 10px; font-weight: 700; color: #94a3b8; letter-spacing: 0.08em; }

.form-group input,
.form-group select,
.form-group textarea {
    padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0;
    font-size: 13px; color: #0f172a; outline: none; transition: border-color 0.2s;
    background: #f8fafc; font-family: sans-serif;
}
.form-group textarea { resize: vertical; }
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { border-color: #2563eb; background: white; box-shadow: 0 0 0 3px rgba(37,99,235,0.08); }

/* UPLOAD */
.upload-zone {
    border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 22px 16px;
    text-align: center; cursor: pointer; transition: all 0.2s;
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    background: #f8fafc; min-height: 110px; justify-content: center;
}
.upload-zone:hover { border-color: #2563eb; background: #f0f6ff; }
.upload-zone svg { width: 28px; height: 28px; color: #94a3b8; }
.upload-zone p { font-size: 12px; font-weight: 600; color: #475569; margin: 0; }
.upload-zone small { font-size: 11px; color: #94a3b8; }
.upload-preview { max-width: 100%; max-height: 80px; object-fit: contain; border-radius: 6px; }
.favicon-preview { max-height: 48px; }

/* COLORS */
.small-label { font-size: 10px; font-weight: 700; color: #94a3b8; letter-spacing: 0.08em; display: block; margin-bottom: 10px; }
.color-row { display: flex; flex-direction: column; gap: 10px; }
.color-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 10px; background: #f8fafc; border: 1px solid #f1f5f9;
}
.color-swatch { width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0; border: 2px solid rgba(0,0,0,0.06); }
.color-info { flex: 1; }
.color-info span { display: block; font-size: 12px; font-weight: 600; color: #0f172a; }
.color-info small { font-size: 11px; color: #94a3b8; }
.edit-color-btn { cursor: pointer; font-size: 13px; position: relative; }

.font-select {
    width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0;
    font-size: 13px; color: #0f172a; outline: none; background: #f8fafc; cursor: pointer;
}
.font-select:focus { border-color: #2563eb; background: white; }

/* DARK MODE CARD */
.dark-card { background: linear-gradient(135deg, #1e3a5f, #1a1060); color: white; }
.dark-header b { display: block; font-size: 14px; font-weight: 700; color: white; margin-bottom: 6px; }
.dark-header p { font-size: 12px; color: rgba(255,255,255,0.6); line-height: 1.5; margin: 0 0 16px; }
.mode-btns { display: flex; gap: 6px; }
.mode-btn {
    flex: 1; padding: 8px 6px; border-radius: 8px; border: none;
    background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.7);
    font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.mode-btn:hover { background: rgba(255,255,255,0.2); color: white; }
.mode-btn.mode-active { background: white; color: #1e3a5f; }

/* SOCIAL */
.social-list { display: flex; flex-direction: column; gap: 8px; }
.social-item { display: flex; align-items: center; gap: 8px; }
.social-icon {
    width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: white;
}
.fb { background: #1877f2; }
.li { background: #0a66c2; }
.tw { background: #000; }
.social-item input {
    flex: 1; padding: 9px 12px; border-radius: 8px; border: 1px solid #e2e8f0;
    font-size: 13px; color: #0f172a; outline: none; background: #f8fafc;
}
.social-item input:focus { border-color: #2563eb; background: white; box-shadow: 0 0 0 3px rgba(37,99,235,0.08); }

/* TIP */
.tip-box {
    display: flex; gap: 10px; padding: 12px 14px;
    background: #fefce8; border: 1px solid #fef08a; border-radius: 10px; margin-top: 12px;
}
.tip-icon { font-size: 16px; flex-shrink: 0; }
.tip-box b { display: block; font-size: 12px; font-weight: 700; color: #854d0e; margin-bottom: 3px; }
.tip-box p { font-size: 12px; color: #92400e; line-height: 1.5; margin: 0; }

/* EMPTY TAB */
.empty-tab { text-align: center; padding: 60px; color: #94a3b8; font-size: 14px; }
.empty-tab b { color: #334155; }

/* RESPONSIVE */
@media (max-width: 900px) {
    .grid-2 { grid-template-columns: 1fr; }
    .side-col { flex-direction: row; flex-wrap: wrap; }
    .side-col .card { flex: 1; min-width: 260px; }
}
@media (max-width: 600px) {
    .page-header { flex-direction: column; gap: 16px; }
    .form-row { grid-template-columns: 1fr; }
    .tabs { overflow-x: auto; }
    .content { padding: 0 16px; }
    .topbar { padding: 12px 16px; }
    .page-header { padding: 20px 16px 16px; }
}
</style>