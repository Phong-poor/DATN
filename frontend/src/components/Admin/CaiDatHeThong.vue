<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import swal from '@/services/swal'

const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const activeMenu = ref('notifications')
const logsLoading = ref(false)
const billingLoading = ref(false)
const logKeyword = ref('')
const billingMonths = ref(6)
const activityLogs = ref([])
const billing = ref({ totals: { revenue: 0, discount: 0, orders: 0 }, series: [] })

const defaultSettings = () => ({
  general: {
    brand_name: 'NextGen',
    slogan: 'Giải pháp công nghệ toàn diện',
    support_email: 'support@nextgen.vn',
    support_phone: '1800 9999',
    business_address: 'TP. Hồ Chí Minh',
    working_hours: '08:00 - 21:00',
  },
  appearance: {
    primary_color: '#2563eb',
    accent_color: '#2563eb',
    theme_mode: 'system',
    font_family: 'Inter',
    border_radius: 12,
    card_shadow: 'medium',
    density: 'comfortable',
    content_width: 'fluid',
    sidebar_style: 'solid',
    animation_level: 'normal',
  },
  notifications: {
    security_alerts: true,
    order_updates: true,
    team_activity: true,
    product_updates: false,
    marketing_promotions: false,
    daily_report: false,
  },
  security: {
    force_logout_on_password_change: true,
    require_2fa_for_admin: false,
    session_timeout_minutes: 120,
    login_alert_email: true,
  },
})

const form = ref(defaultSettings())

const menuItems = [
  { key: 'account', title: 'Tài khoản', sub: 'Hồ sơ & thông tin shop' },
  { key: 'notifications', title: 'Thông báo', sub: 'Cảnh báo & email' },
  { key: 'appearance', title: 'Giao diện', sub: 'Màu sắc, font, bố cục' },
  { key: 'security', title: 'Bảo mật', sub: 'Phiên đăng nhập & 2FA' },
  { key: 'activity', title: 'Nhật ký hoạt động', sub: 'Hoạt động gần đây' },
  { key: 'billing', title: 'Tài chính', sub: 'Doanh thu & đơn hàng' },
]

const notificationRows = [
  {
    key: 'security_alerts',
    title: 'Cảnh báo bảo mật',
    desc: 'Thông báo đăng nhập mới, hoạt động bất thường và thay đổi mật khẩu.',
  },
  {
    key: 'order_updates',
    title: 'Cập nhật đơn hàng',
    desc: 'Nhận thông tin đơn mới, đổi trạng thái vận chuyển và hoàn tất giao hàng.',
  },
  {
    key: 'team_activity',
    title: 'Hoạt động hệ thống',
    desc: 'Theo dõi thay đổi vai trò, thao tác quản trị và tác vụ quan trọng.',
  },
  {
    key: 'product_updates',
    title: 'Cập nhật sản phẩm',
    desc: 'Thông báo khi có sản phẩm, biến thể hoặc tồn kho được cập nhật.',
  },
  {
    key: 'marketing_promotions',
    title: 'Marketing & khuyến mãi',
    desc: 'Nhận gợi ý chiến dịch, voucher, chương trình giảm giá và banner mới.',
  },
  {
    key: 'daily_report',
    title: 'Báo cáo hằng ngày',
    desc: 'Gửi tóm tắt doanh thu, đơn hàng và yêu cầu hỗ trợ mỗi ngày.',
  },
]

const shadowOptions = [
  { value: 'none', label: 'Không đổ bóng' },
  { value: 'soft', label: 'Nhẹ' },
  { value: 'medium', label: 'Vừa' },
  { value: 'strong', label: 'Đậm' },
]

const filteredLogs = computed(() => {
  const keyword = logKeyword.value.trim().toLowerCase()
  if (!keyword) return activityLogs.value
  return activityLogs.value.filter((item) =>
    [item.title, item.description, item.actor, item.type].some((value) =>
      String(value || '').toLowerCase().includes(keyword),
    ),
  )
})

const maxRevenue = computed(() => Math.max(...billing.value.series.map((item) => item.revenue || 0), 1))

function mergeSettings(payload) {
  const defaults = defaultSettings()
  return {
    general: { ...defaults.general, ...(payload?.general || {}) },
    appearance: { ...defaults.appearance, ...(payload?.appearance || {}) },
    notifications: { ...defaults.notifications, ...(payload?.notifications || {}) },
    security: { ...defaults.security, ...(payload?.security || {}) },
  }
}

function money(value) {
  return `${Number(value || 0).toLocaleString('vi-VN')}đ`
}

function formatDate(value) {
  if (!value) return '--'
  return new Date(value).toLocaleString('vi-VN')
}

function applyAppearance() {
  const appearance = form.value.appearance
  const root = document.documentElement
  root.style.setProperty('--admin-primary', appearance.primary_color)
  root.style.setProperty('--admin-accent', appearance.accent_color)
  root.style.setProperty('--admin-radius', `${appearance.border_radius}px`)
  root.dataset.adminTheme = appearance.theme_mode
  root.dataset.adminDensity = appearance.density
  localStorage.setItem('admin-appearance-settings', JSON.stringify(appearance))
  window.dispatchEvent(new CustomEvent('admin-settings-updated', { detail: form.value }))
}

async function fetchSettings() {
  loading.value = true
  try {
    const res = await api.get('/admin/account/settings')
    form.value = mergeSettings(res.data?.data)
    applyAppearance()
  } catch (error) {
    swal.error('Không thể tải cài đặt', error?.response?.data?.message || 'Vui lòng kiểm tra lại kết nối hệ thống.')
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  saving.value = true
  try {
    const res = await api.put('/admin/account/settings', form.value)
    form.value = mergeSettings(res.data?.data)
    applyAppearance()
    await swal.success('Thành công', 'Đã lưu cài đặt hệ thống.')
  } catch (error) {
    swal.error('Lưu thất bại', error?.response?.data?.message || 'Dữ liệu cài đặt chưa hợp lệ.')
  } finally {
    saving.value = false
  }
}

async function fetchActivityLogs() {
  logsLoading.value = true
  try {
    const res = await api.get('/admin/account/activity-log')
    activityLogs.value = res.data?.data || []
  } catch (error) {
    swal.error('Không thể tải nhật ký', error?.response?.data?.message || 'Vui lòng thử lại.')
  } finally {
    logsLoading.value = false
  }
}

async function fetchBilling() {
  billingLoading.value = true
  try {
    const res = await api.get('/admin/account/billing', { params: { months: billingMonths.value } })
    billing.value = res.data?.data || billing.value
  } catch (error) {
    swal.error('Không thể tải tài chính', error?.response?.data?.message || 'Vui lòng thử lại.')
  } finally {
    billingLoading.value = false
  }
}

function onMenuClick(key) {
  if (key === 'activity') {
    router.push('/admin/nhat-ky-hoat-dong')
    return
  }
  activeMenu.value = key
  if (key === 'billing' && !billing.value.series.length) fetchBilling()
}

function resetAppearance() {
  form.value.appearance = defaultSettings().appearance
  applyAppearance()
}

onMounted(async () => {
  await fetchSettings()
})
</script>

<template>
  <div class="settings-v2">
    <aside class="left-card">
      <button
        v-for="item in menuItems"
        :key="item.key"
        class="menu-item"
        :class="{ active: activeMenu === item.key }"
        type="button"
        @click="onMenuClick(item.key)"
      >
        <div>
          <b>{{ item.title }}</b>
          <span>{{ item.sub }}</span>
        </div>
      </button>
    </aside>

    <main class="right-pane">
      <section v-if="activeMenu === 'account'" class="panel">
        <div class="panel-head">
          <div>
            <h3>Thông tin tài khoản & cửa hàng</h3>
            <p>Các thông tin này dùng cho header, liên hệ hỗ trợ và nội dung hệ thống.</p>
          </div>
          <button class="ghost-btn" type="button" @click="router.push('/admin/ho-so-quan-tri')">Mở hồ sơ admin</button>
        </div>

        <div class="form-grid">
          <label><span>Tên thương hiệu</span><input v-model="form.general.brand_name" /></label>
          <label><span>Slogan</span><input v-model="form.general.slogan" /></label>
          <label><span>Email hỗ trợ</span><input v-model="form.general.support_email" type="email" /></label>
          <label><span>Số điện thoại hỗ trợ</span><input v-model="form.general.support_phone" /></label>
          <label><span>Địa chỉ kinh doanh</span><input v-model="form.general.business_address" /></label>
          <label><span>Giờ làm việc</span><input v-model="form.general.working_hours" /></label>
        </div>
      </section>

      <section v-if="activeMenu === 'notifications'" class="panel">
        <div class="panel-head">
          <div>
            <h3>Thông báo qua email</h3>
            <p>Bật tắt các nhóm thông báo hệ thống cần gửi cho quản trị viên.</p>
          </div>
          <button class="save-btn" :disabled="saving || loading" type="button" @click="saveSettings">
            {{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}
          </button>
        </div>

        <div v-if="loading" class="state">Đang tải cài đặt...</div>
        <div v-else class="rows">
          <div class="row" v-for="item in notificationRows" :key="item.key">
            <div class="meta">
              <b>{{ item.title }}</b>
              <p>{{ item.desc }}</p>
            </div>
            <label class="switch">
              <input type="checkbox" v-model="form.notifications[item.key]" />
              <span class="slider"></span>
            </label>
          </div>
        </div>
      </section>

      <section v-if="activeMenu === 'appearance'" class="panel">
        <div class="panel-head">
          <div>
            <h3>Hiển thị & giao diện</h3>
            <p>Tùy chỉnh màu, font, độ rộng và mật độ giao diện admin.</p>
          </div>
          <div class="head-actions">
            <button class="ghost-btn" type="button" @click="resetAppearance">Khôi phục mặc định</button>
            <button class="save-btn" :disabled="saving || loading" type="button" @click="saveSettings">
              {{ saving ? 'Đang lưu...' : 'Lưu giao diện' }}
            </button>
          </div>
        </div>

        <div class="appearance-grid">
          <label><span>Màu chính</span><input v-model="form.appearance.primary_color" type="color" @input="applyAppearance" /></label>
          <label><span>Màu phụ</span><input v-model="form.appearance.accent_color" type="color" @input="applyAppearance" /></label>
          <label><span>Chế độ</span>
            <select v-model="form.appearance.theme_mode" @change="applyAppearance">
              <option value="light">Sáng</option>
              <option value="dark">Tối</option>
              <option value="system">Theo hệ thống</option>
            </select>
          </label>
          <label><span>Phông chữ</span>
            <select v-model="form.appearance.font_family" @change="applyAppearance">
              <option>Inter</option>
              <option>Be Vietnam Pro</option>
              <option>Roboto</option>
              <option>Nunito</option>
            </select>
          </label>
          <label><span>Bo góc: {{ form.appearance.border_radius }}px</span>
            <input v-model.number="form.appearance.border_radius" type="range" min="6" max="24" @input="applyAppearance" />
          </label>
          <label><span>Đổ bóng thẻ</span>
            <select v-model="form.appearance.card_shadow" @change="applyAppearance">
              <option v-for="item in shadowOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
          </label>
          <label><span>Mật độ hiển thị</span>
            <select v-model="form.appearance.density" @change="applyAppearance">
              <option value="compact">Gọn</option>
              <option value="comfortable">Tiêu chuẩn</option>
              <option value="spacious">Rộng</option>
            </select>
          </label>
          <label><span>Độ rộng nội dung</span>
            <select v-model="form.appearance.content_width" @change="applyAppearance">
              <option value="fluid">Toàn màn hình</option>
              <option value="boxed">Giới hạn chiều rộng</option>
            </select>
          </label>
          <label><span>Kiểu sidebar</span>
            <select v-model="form.appearance.sidebar_style" @change="applyAppearance">
              <option value="solid">Đậm</option>
              <option value="glass">Kính mờ</option>
              <option value="gradient">Chuyển sắc</option>
            </select>
          </label>
          <label><span>Hiệu ứng chuyển động</span>
            <select v-model="form.appearance.animation_level" @change="applyAppearance">
              <option value="off">Tắt</option>
              <option value="normal">Vừa phải</option>
              <option value="rich">Nổi bật</option>
            </select>
          </label>
        </div>

        <div class="preview-card">
          <div class="preview-main" :style="{ background: `linear-gradient(135deg, ${form.appearance.primary_color}, ${form.appearance.accent_color})` }">
            <span>Preview</span>
            <b>{{ form.general.brand_name }}</b>
          </div>
          <div class="preview-side">
            <strong>Thẻ giao diện mẫu</strong>
            <p>Font {{ form.appearance.font_family }}, bo góc {{ form.appearance.border_radius }}px, mật độ {{ form.appearance.density }}.</p>
          </div>
        </div>
      </section>

      <section v-if="activeMenu === 'security'" class="panel">
        <div class="panel-head">
          <div>
            <h3>Bảo mật hệ thống</h3>
            <p>Quản lý phiên đăng nhập, cảnh báo bảo mật và yêu cầu xác thực.</p>
          </div>
          <button class="save-btn" :disabled="saving || loading" type="button" @click="saveSettings">
            {{ saving ? 'Đang lưu...' : 'Lưu bảo mật' }}
          </button>
        </div>

        <div class="rows">
          <div class="row">
            <div class="meta"><b>Đăng xuất sau khi đổi mật khẩu</b><p>Buộc các phiên cũ đăng nhập lại sau khi admin đổi mật khẩu.</p></div>
            <label class="switch"><input type="checkbox" v-model="form.security.force_logout_on_password_change" /><span class="slider"></span></label>
          </div>
          <div class="row">
            <div class="meta"><b>Yêu cầu 2FA cho admin</b><p>Bật yêu cầu xác thực hai lớp cho tài khoản quản trị.</p></div>
            <label class="switch"><input type="checkbox" v-model="form.security.require_2fa_for_admin" /><span class="slider"></span></label>
          </div>
          <div class="row">
            <div class="meta"><b>Email cảnh báo đăng nhập</b><p>Gửi email khi phát hiện đăng nhập mới hoặc thiết bị lạ.</p></div>
            <label class="switch"><input type="checkbox" v-model="form.security.login_alert_email" /><span class="slider"></span></label>
          </div>
        </div>
        <div class="form-grid compact">
          <label><span>Thời gian hết phiên (phút)</span><input v-model.number="form.security.session_timeout_minutes" type="number" min="15" max="1440" /></label>
        </div>
      </section>

      <section v-if="activeMenu === 'activity'" class="panel">
        <div class="panel-head">
          <div>
            <h3>Nhật ký hoạt động</h3>
            <p>Theo dõi đơn hàng, tài khoản mới và các tác vụ gần đây.</p>
          </div>
          <button class="ghost-btn" type="button" @click="fetchActivityLogs">Tải lại</button>
        </div>
        <div class="toolbar">
          <input v-model="logKeyword" placeholder="Tìm theo tiêu đề, mô tả, người thao tác..." />
        </div>
        <div v-if="logsLoading" class="state">Đang tải nhật ký...</div>
        <div v-else-if="!filteredLogs.length" class="state">Chưa có dữ liệu nhật ký phù hợp.</div>
        <div v-else class="log-list">
          <div v-for="(item, index) in filteredLogs" :key="index" class="log-row">
            <span class="log-type" :class="item.type">{{ item.type }}</span>
            <div>
              <b>{{ item.title }}</b>
              <p>{{ item.description }}</p>
              <small>{{ item.actor }} · {{ formatDate(item.at) }}</small>
            </div>
          </div>
        </div>
      </section>

      <section v-if="activeMenu === 'billing'" class="panel">
        <div class="panel-head">
          <div>
            <h3>Tài chính & doanh thu</h3>
            <p>Tổng hợp doanh thu, giảm giá và số đơn theo mốc thời gian.</p>
          </div>
          <select v-model.number="billingMonths" class="small-select" @change="fetchBilling">
            <option :value="3">3 tháng</option>
            <option :value="6">6 tháng</option>
            <option :value="12">12 tháng</option>
          </select>
        </div>

        <div class="stats">
          <div class="stat-card blue"><span>Tổng doanh thu</span><b>{{ money(billing.totals.revenue) }}</b></div>
          <div class="stat-card violet"><span>Tổng giảm giá</span><b>{{ money(billing.totals.discount) }}</b></div>
          <div class="stat-card green"><span>Tổng đơn hàng</span><b>{{ billing.totals.orders }}</b></div>
        </div>
        <div v-if="billingLoading" class="state">Đang tải dữ liệu tài chính...</div>
        <div v-else class="chart">
          <div v-for="(item, index) in billing.series" :key="index" class="chart-col">
            <div class="bar-wrap">
              <div class="bar" :style="{ height: `${Math.max(8, (item.revenue / maxRevenue) * 100)}%` }"></div>
            </div>
            <small>{{ item.label }}</small>
            <p>{{ item.orders }} đơn</p>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
.settings-v2{padding:22px 28px 34px;display:grid;grid-template-columns:300px minmax(0,1fr);gap:18px}
.left-card,.panel{background:#fff;border:1px solid #dfe7f2;border-radius:16px;box-shadow:0 12px 28px rgba(15,23,42,.04)}
.left-card{padding:12px;height:fit-content;position:sticky;top:18px}
.menu-item{width:100%;border:0;background:#fff;border-radius:12px;padding:13px 15px;text-align:left;cursor:pointer;transition:.18s}
.menu-item + .menu-item{margin-top:6px}
.menu-item:hover{background:#f8fafc}
.menu-item b{display:block;font-size:18px;color:#0f172a;line-height:1.2}
.menu-item span{display:block;margin-top:3px;font-size:13px;color:#64748b;line-height:1.25}
.menu-item.active{background:#eef2ff}
.menu-item.active b,.menu-item.active span{color:#2563eb}
.right-pane{display:grid;gap:16px;min-width:0}
.panel{overflow:hidden}
.panel-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:18px 20px;border-bottom:1px solid #edf2f7}
.panel-head h3{margin:0;font-size:19px;color:#0f172a;line-height:1.25}
.panel-head p{margin:4px 0 0;color:#64748b;font-size:13px;line-height:1.45}
.head-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.save-btn,.ghost-btn{border-radius:11px;padding:9px 14px;font-size:13px;font-weight:800;cursor:pointer;font-family:inherit;white-space:nowrap}
.save-btn{border:1px solid #2563eb;background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;box-shadow:0 8px 18px rgba(37,99,235,.22)}
.ghost-btn{border:1px solid #dbe2ea;background:#fff;color:#334155}
.save-btn:disabled{opacity:.65;cursor:not-allowed}
.state{padding:22px;color:#64748b;font-size:14px}
.rows{padding:6px 20px}
.row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:15px 0;border-bottom:1px solid #edf2f7}
.row:last-child{border-bottom:0}
.meta b{font-size:14px;color:#0f172a;line-height:1.25}
.meta p{margin:4px 0 0;font-size:13px;color:#64748b;line-height:1.45}
.switch{position:relative;display:inline-block;width:52px;height:30px;flex:0 0 auto}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;inset:0;background:#cbd5e1;border-radius:999px;transition:.2s}
.slider::before{content:'';position:absolute;width:24px;height:24px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 2px 6px rgba(15,23,42,.15)}
.switch input:checked + .slider{background:#3b82f6}
.switch input:checked + .slider::before{transform:translateX(22px)}
.form-grid,.appearance-grid{padding:20px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.form-grid.compact{grid-template-columns:minmax(220px,360px);padding-top:0}
label{display:flex;flex-direction:column;gap:7px}
label span{font-size:13px;color:#475569;font-weight:700}
input,select{border:1px solid #dbe2ea;border-radius:10px;padding:10px 12px;font-size:14px;font-family:inherit;background:#fff;color:#0f172a}
input[type=color]{height:42px;padding:5px}
input[type=range]{padding:0}
input:focus,select:focus{outline:none;border-color:#93c5fd;box-shadow:0 0 0 3px rgba(59,130,246,.15)}
.preview-card{margin:0 20px 20px;border:1px solid #e2e8f0;border-radius:16px;display:grid;grid-template-columns:260px 1fr;overflow:hidden}
.preview-main{padding:22px;color:#fff;display:flex;flex-direction:column;justify-content:center;min-height:120px}
.preview-main span{font-size:12px;text-transform:uppercase;letter-spacing:.08em;opacity:.82}
.preview-main b{font-size:26px;margin-top:8px}
.preview-side{padding:22px;background:#f8fafc}
.preview-side strong{font-size:16px;color:#0f172a}
.preview-side p{font-size:13px;color:#64748b;line-height:1.5}
.toolbar{padding:16px 20px 0}
.toolbar input{width:100%}
.log-list{padding:16px 20px 20px;display:grid;gap:10px}
.log-row{display:flex;gap:12px;padding:13px;border:1px solid #edf2f7;border-radius:13px;background:#fbfdff}
.log-type{height:fit-content;padding:4px 9px;border-radius:999px;font-size:11px;font-weight:800;text-transform:uppercase}
.log-type.order{background:#e0e7ff;color:#1d4ed8}
.log-type.user{background:#dcfce7;color:#166534}
.log-row b{font-size:14px;color:#0f172a}
.log-row p{margin:4px 0;color:#475569;font-size:13px}
.log-row small{color:#64748b;font-size:12px}
.small-select{min-width:130px}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;padding:20px 20px 8px}
.stat-card{border-radius:15px;padding:17px 18px;color:#fff;min-height:100px;display:flex;flex-direction:column;justify-content:center}
.stat-card span{font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;opacity:.82}
.stat-card b{font-size:24px;margin-top:8px}
.stat-card.blue{background:linear-gradient(135deg,#1d4ed8,#3b82f6)}
.stat-card.violet{background:linear-gradient(135deg,#1d4ed8,#3b82f6)}
.stat-card.green{background:linear-gradient(135deg,#1E40AF,#2563eb)}
.chart{padding:12px 20px 24px;height:250px;display:grid;grid-template-columns:repeat(auto-fit,minmax(70px,1fr));gap:10px;align-items:end}
.chart-col{height:210px;display:flex;flex-direction:column;align-items:center;gap:6px}
.bar-wrap{height:150px;width:100%;display:flex;align-items:flex-end;justify-content:center;border-bottom:1px dashed #cbd5e1}
.bar{width:20px;max-height:150px;border-radius:10px 10px 4px 4px;background:linear-gradient(180deg,#3b82f6,#2563eb)}
.chart-col small{font-size:11px;color:#64748b}
.chart-col p{margin:0;color:#334155;font-size:12px}
@media (max-width:1100px){.settings-v2{grid-template-columns:1fr}.left-card{position:static}.form-grid,.appearance-grid,.preview-card,.stats{grid-template-columns:1fr}.panel-head{align-items:flex-start;flex-direction:column}.row{align-items:flex-start}.switch{margin-top:4px}}
</style>
