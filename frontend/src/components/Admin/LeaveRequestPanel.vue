<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'
import { storageUrl } from '@/services/urls'

const props = defineProps({ admin: { type: Boolean, default: false } })

const requests = ref([])
const adminRequests = ref([])
const historyRequests = ref([])
const historyPage = ref(1)
const historyPagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
const submitting = ref(false)
const reviewingId = ref(null)
const evidence = ref(null)
const feedback = ref({})
const showForm = ref(false)
const editingRequestId = ref(null)
const detailRequest = ref(null)
const today = new Date().toISOString().slice(0, 10)
const form = ref({
  loai_nghi: 'annual', thoi_luong: 'full_day', tu_ngay: today, den_ngay: today,
  ly_do: '', nguoi_ban_giao: '', ghi_chu_ban_giao: ''
})

const leaveTypes = {
  annual: 'Nghỉ phép năm', sick: 'Nghỉ ốm', personal: 'Việc cá nhân', unpaid: 'Nghỉ không lương',
  maternity: 'Thai sản', bereavement: 'Hiếu / hỷ', late: 'Xin đi trễ', early_leave: 'Xin về sớm',
  remote: 'Làm việc từ xa', other: 'Lý do khác'
}
const durationLabels = { full_day: 'Cả ngày', morning: 'Buổi sáng', afternoon: 'Buổi chiều', hours: 'Theo giờ' }
const statusLabels = { pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Từ chối', needs_info: 'Cần bổ sung', cancelled: 'Đã hủy' }
const displayedAdminRequests = computed(() => adminRequests.value)
const detailEvidenceUrl = computed(() => storageUrl(detailRequest.value?.minh_chung || ''))
const detailEvidenceIsPdf = computed(() => /\.pdf(?:$|[?#])/i.test(detailEvidenceUrl.value))

function openDetail(item) {
  detailRequest.value = item
}

function closeDetail() {
  detailRequest.value = null
}

function resetForm() {
  form.value = { loai_nghi: 'annual', thoi_luong: 'full_day', tu_ngay: today, den_ngay: today, ly_do: '', nguoi_ban_giao: '', ghi_chu_ban_giao: '' }
  evidence.value = null
  editingRequestId.value = null
}

function closeForm() {
  showForm.value = false
  resetForm()
}

function openCreateForm() {
  if (showForm.value && !editingRequestId.value) return closeForm()
  resetForm()
  showForm.value = true
}

function openSupplementForm(item) {
  editingRequestId.value = item.id
  form.value = {
    loai_nghi: item.loai_nghi,
    thoi_luong: item.thoi_luong,
    tu_ngay: String(item.tu_ngay || '').slice(0, 10),
    den_ngay: String(item.den_ngay || '').slice(0, 10),
    ly_do: item.ly_do || '',
    nguoi_ban_giao: item.nguoi_ban_giao || '',
    ghi_chu_ban_giao: item.ghi_chu_ban_giao || ''
  }
  evidence.value = null
  showForm.value = true
  requestAnimationFrame(() => document.querySelector('.leave-form')?.scrollIntoView({ behavior: 'smooth', block: 'center' }))
}

function formatDate(value) {
  return value ? new Date(`${String(value).slice(0, 10)}T00:00:00`).toLocaleDateString('vi-VN') : ''
}
function validationMessage(error) {
  const errors = error.response?.data?.errors
  return errors ? Object.values(errors).flat()[0] : error.response?.data?.message || 'Vui lòng thử lại.'
}

async function fetchMine() {
  try {
    const response = await api.get('/cham-cong/don-xin-nghi', { skipGlobalLoader: true })
    requests.value = response.data.data?.data || []
  } catch (error) {
    console.error('Không thể tải đơn nghỉ cá nhân:', error)
  }
}
async function fetchAdmin() {
  if (!props.admin) return
  loading.value = true
  try {
    const [approvalResponse, historyResponse] = await Promise.all([
      api.get('/admin/cham-cong/don-xin-nghi', { params: { status: 'actionable', per_page: 50 }, skipGlobalLoader: true }),
      api.get('/admin/cham-cong/don-xin-nghi', { params: { status: 'history', per_page: 10, page: historyPage.value }, skipGlobalLoader: true })
    ])
    adminRequests.value = approvalResponse.data.data?.data || []
    historyRequests.value = historyResponse.data.data?.data || []
    historyPagination.value = {
      current_page: historyResponse.data.data?.current_page || 1,
      last_page: historyResponse.data.data?.last_page || 1,
      total: historyResponse.data.data?.total || 0
    }
  } catch (error) {
    await swal.error('Không tải được đơn nghỉ', validationMessage(error))
  } finally { loading.value = false }
}
async function changeHistoryPage(page) {
  if (loading.value || page < 1 || page > historyPagination.value.last_page) return
  historyPage.value = page
  await fetchAdmin()
}
async function submitRequest() {
  if (submitting.value) return
  if (form.value.ly_do.trim().length < 10) {
    await swal.warning('Lý do chưa đầy đủ', 'Vui lòng nhập lý do có ít nhất 10 ký tự.')
    return
  }
  submitting.value = true
  try {
    if (evidence.value && !(evidence.value instanceof File)) {
      await swal.warning('Tệp minh chứng không hợp lệ', 'Vui lòng chọn lại ảnh hoặc tệp PDF cần gửi.')
      return
    }
    const payload = new FormData()
    Object.entries(form.value).forEach(([key, value]) => payload.append(key, value))
    if (evidence.value) payload.append('minh_chung', evidence.value)
    const isSupplement = Boolean(editingRequestId.value)
    const endpoint = isSupplement
      ? `/cham-cong/don-xin-nghi/${editingRequestId.value}/bo-sung`
      : '/cham-cong/don-xin-nghi'
    const response = await api.post(endpoint, payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
      bypassOffline: true
    })
    closeForm()
    await Promise.all([fetchMine(), fetchAdmin()])
    await swal.success('Đã gửi đơn nghỉ', response.data.message)
  } catch (error) {
    await swal.error('Không thể gửi đơn', validationMessage(error))
  } finally { submitting.value = false }
}
async function cancelRequest(item) {
  const confirmed = await swal.confirm('Hủy đơn nghỉ?', 'Đơn đang chờ duyệt sẽ được chuyển sang trạng thái đã hủy.')
  if (!confirmed) return
  try {
    const response = await api.patch(`/cham-cong/don-xin-nghi/${item.id}/huy`)
    await fetchMine()
    await swal.success('Đã hủy đơn', response.data.message)
  } catch (error) { await swal.error('Không thể hủy', validationMessage(error)) }
}
async function review(item, action) {
  if (reviewingId.value) return
  const note = String(feedback.value[item.id] || '').trim()
  if (action !== 'approve' && !note) {
    await swal.warning('Thiếu phản hồi', 'Vui lòng nhập lý do từ chối hoặc nội dung cần nhân viên bổ sung.')
    return
  }
  reviewingId.value = item.id
  try {
    const response = await api.patch(`/admin/cham-cong/don-xin-nghi/${item.id}/xu-ly`, { action, feedback: note || null })
    delete feedback.value[item.id]
    await Promise.all([fetchAdmin(), fetchMine()])
    await swal.success('Đã xử lý đơn', response.data.message)
  } catch (error) { await swal.error('Không thể xử lý', validationMessage(error)) }
  finally { reviewingId.value = null }
}

onMounted(() => props.admin ? fetchAdmin() : fetchMine())
</script>

<template>
  <section class="leave-panel">
    <div v-if="!admin" class="leave-heading">
      <div><span class="eyebrow">NGHỈ PHÉP & VẮNG MẶT</span><h3>Đơn xin nghỉ phép</h3><p>Đơn được duyệt sẽ tự đồng bộ với lịch làm và bảng chấm công.</p></div>
      <button type="button" class="primary-btn" :disabled="submitting" @click="openCreateForm">{{ showForm && !editingRequestId ? 'Đóng biểu mẫu' : '+ Tạo đơn xin nghỉ' }}</button>
    </div>

    <form v-if="!admin && showForm" class="leave-form" @submit.prevent="submitRequest">
      <div v-if="editingRequestId" class="supplement-notice wide"><strong>Cập nhật thông tin bổ sung</strong><span>Chỉnh sửa theo phản hồi của quản lý rồi gửi lại để phê duyệt.</span></div>
      <label><span>Loại nghỉ *</span><select v-model="form.loai_nghi"><option v-for="(label, key) in leaveTypes" :key="key" :value="key">{{ label }}</option></select></label>
      <label><span>Thời lượng *</span><select v-model="form.thoi_luong"><option v-for="(label, key) in durationLabels" :key="key" :value="key">{{ label }}</option></select></label>
      <label><span>Từ ngày *</span><input v-model="form.tu_ngay" type="date" :min="today" required /></label>
      <label><span>Đến ngày *</span><input v-model="form.den_ngay" type="date" :min="form.tu_ngay" required /></label>
      <label class="wide"><span>Lý do *</span><textarea v-model.trim="form.ly_do" rows="3" maxlength="1000" placeholder="Mô tả lý do nghỉ rõ ràng (ít nhất 10 ký tự)..." required></textarea><small>{{ form.ly_do.length }}/1000</small></label>
      <label><span>Người nhận bàn giao</span><input v-model.trim="form.nguoi_ban_giao" maxlength="150" placeholder="Họ tên người nhận bàn giao" /></label>
      <label><span>Minh chứng</span><input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="evidence = $event.target.files?.[0] || null" /><small>JPG, PNG hoặc PDF · tối đa 5MB</small></label>
      <label class="wide"><span>Ghi chú bàn giao</span><textarea v-model.trim="form.ghi_chu_ban_giao" rows="2" maxlength="1000" placeholder="Công việc cần bàn giao trước khi nghỉ..."></textarea></label>
      <div class="form-actions wide"><button type="button" class="secondary-btn" :disabled="submitting" @click="closeForm">Hủy</button><button class="primary-btn" :disabled="submitting">{{ submitting ? 'Đang gửi...' : (editingRequestId ? 'Gửi lại để duyệt' : 'Gửi đơn xin nghỉ') }}</button></div>
    </form>

    <div v-if="!admin && !showForm" class="request-list mine-list">
      <div class="list-title"><strong>Đơn của tôi</strong><span>{{ requests.length }} đơn gần nhất</span></div>
      <div v-if="!requests.length" class="empty">Bạn chưa có đơn xin nghỉ.</div>
      <article v-for="item in requests" :key="item.id" class="request-card">
        <div><strong>{{ leaveTypes[item.loai_nghi] || item.loai_nghi }}</strong><p>{{ formatDate(item.tu_ngay) }} → {{ formatDate(item.den_ngay) }} · {{ durationLabels[item.thoi_luong] }}</p><small>{{ item.ly_do }}</small></div>
        <div class="request-side"><span class="leave-status" :class="item.trang_thai">{{ statusLabels[item.trang_thai] || item.trang_thai }}</span><button v-if="item.trang_thai === 'needs_info'" type="button" class="supplement-btn" @click="openSupplementForm(item)">Cập nhật bổ sung</button><button v-if="['pending','needs_info'].includes(item.trang_thai)" class="danger-link" @click="cancelRequest(item)">Hủy đơn</button></div>
        <p v-if="item.phan_hoi_quan_ly" class="manager-note">Phản hồi quản lý: {{ item.phan_hoi_quan_ly }}</p>
      </article>
    </div>

    <div v-if="admin" class="admin-review">
      <div class="list-title"><div><span class="eyebrow">XỬ LÝ ĐƠN</span><strong>Đơn cần phê duyệt</strong></div><span>{{ adminRequests.length }} đơn cần xử lý</span></div>
      <div v-if="loading" class="empty">Đang tải đơn nghỉ...</div><div v-else-if="!displayedAdminRequests.length" class="empty">Không có đơn phù hợp.</div>
      <article v-for="item in displayedAdminRequests" :key="`admin-${item.id}`" class="review-card">
        <div class="employee"><strong>{{ item.nhan_vien?.ten }}</strong><small>{{ item.nhan_vien?.email }}</small></div>
        <div><strong>{{ leaveTypes[item.loai_nghi] }}</strong><p>{{ formatDate(item.tu_ngay) }} → {{ formatDate(item.den_ngay) }} · {{ durationLabels[item.thoi_luong] }}</p><small>{{ item.ly_do }}</small></div>
        <div class="review-side"><span class="leave-status" :class="item.trang_thai">{{ statusLabels[item.trang_thai] }}</span><button type="button" class="view-btn" @click="openDetail(item)">Xem</button></div>
        <div v-if="item.trang_thai === 'pending'" class="review-actions"><input v-model="feedback[item.id]" placeholder="Nhập phản hồi khi từ chối hoặc yêu cầu bổ sung..." maxlength="1000" /><button class="info-btn" :disabled="reviewingId" @click="review(item,'needs_info')">Yêu cầu bổ sung</button><button class="danger-btn" :disabled="reviewingId" @click="review(item,'reject')">Từ chối</button><button class="approve-btn" :disabled="reviewingId" @click="review(item,'approve')">Duyệt đơn</button></div>
      </article>
      <div class="leave-history">
        <div class="list-title"><div><span class="eyebrow">LỊCH SỬ</span><strong>Lịch sử nhân viên xin nghỉ</strong></div><span>{{ historyPagination.total }} đơn</span></div>
        <div v-if="loading" class="empty">Đang tải lịch sử...</div>
        <div v-else-if="!historyRequests.length" class="empty">Chưa có lịch sử đơn nghỉ.</div>
        <article v-for="item in historyRequests" :key="`history-${item.id}`" class="review-card history-card">
          <div class="employee"><strong>{{ item.nhan_vien?.ten }}</strong><small>{{ item.nhan_vien?.email }}</small></div>
          <div><strong>{{ leaveTypes[item.loai_nghi] || item.loai_nghi }}</strong><p>{{ formatDate(item.tu_ngay) }} → {{ formatDate(item.den_ngay) }} · {{ durationLabels[item.thoi_luong] }}</p><small>{{ item.ly_do }}</small></div>
          <span class="leave-status" :class="item.trang_thai">{{ statusLabels[item.trang_thai] || item.trang_thai }}</span>
          <p v-if="item.nguoi_xu_ly || item.phan_hoi_quan_ly" class="manager-note history-note"><strong>Người xử lý:</strong> {{ item.nguoi_xu_ly?.ten || 'Hệ thống' }}<template v-if="item.phan_hoi_quan_ly"> · {{ item.phan_hoi_quan_ly }}</template></p>
        </article>
        <div v-if="historyPagination.total > 0" class="history-pagination">
          <button type="button" :disabled="historyPagination.current_page <= 1 || loading" @click="changeHistoryPage(historyPagination.current_page - 1)">← Trước</button>
          <span>Trang <strong>{{ historyPagination.current_page }}</strong> / {{ historyPagination.last_page }}</span>
          <button type="button" :disabled="historyPagination.current_page >= historyPagination.last_page || loading" @click="changeHistoryPage(historyPagination.current_page + 1)">Sau →</button>
        </div>
      </div>
    </div>

    <Teleport to="body">
      <div v-if="detailRequest" class="leave-detail-backdrop" @click.self="closeDetail">
        <section class="leave-detail-modal" role="dialog" aria-modal="true" aria-labelledby="leave-detail-title">
          <header><div><span class="eyebrow">CHI TIẾT ĐƠN XIN NGHỈ</span><h3 id="leave-detail-title">{{ detailRequest.nhan_vien?.ten || 'Nhân viên' }}</h3><p>{{ detailRequest.nhan_vien?.email }}</p></div><button type="button" class="detail-close" aria-label="Đóng" @click="closeDetail">×</button></header>
          <div class="detail-grid">
            <div><span>Loại nghỉ</span><strong>{{ leaveTypes[detailRequest.loai_nghi] || detailRequest.loai_nghi }}</strong></div>
            <div><span>Thời lượng</span><strong>{{ durationLabels[detailRequest.thoi_luong] || detailRequest.thoi_luong }}</strong></div>
            <div><span>Từ ngày</span><strong>{{ formatDate(detailRequest.tu_ngay) }}</strong></div>
            <div><span>Đến ngày</span><strong>{{ formatDate(detailRequest.den_ngay) }}</strong></div>
          </div>
          <div class="detail-block"><span>Lý do xin nghỉ</span><p>{{ detailRequest.ly_do || 'Không có nội dung.' }}</p></div>
          <div class="detail-grid handover-grid">
            <div><span>Người nhận bàn giao</span><strong>{{ detailRequest.nguoi_ban_giao || 'Không có' }}</strong></div>
            <div><span>Ghi chú bàn giao</span><strong>{{ detailRequest.ghi_chu_ban_giao || 'Không có' }}</strong></div>
          </div>
          <div class="detail-block evidence-block"><div class="evidence-title"><span>Minh chứng đính kèm</span><a v-if="detailEvidenceUrl" :href="detailEvidenceUrl" target="_blank" rel="noopener">Mở tệp ↗</a></div><div v-if="!detailEvidenceUrl" class="no-evidence">Nhân viên không đính kèm minh chứng.</div><iframe v-else-if="detailEvidenceIsPdf" :src="detailEvidenceUrl" title="Minh chứng PDF"></iframe><img v-else :src="detailEvidenceUrl" alt="Minh chứng đơn xin nghỉ" /></div>
          <footer><button type="button" class="secondary-btn" @click="closeDetail">Đóng</button></footer>
        </section>
      </div>
    </Teleport>
  </section>
</template>

<style scoped>
.leave-panel{margin:18px 0;padding:22px;border:1px solid #dbe3ef;border-radius:20px;background:#fff;box-shadow:0 10px 30px rgba(15,23,42,.05);color:#172033}.leave-heading,.list-title{display:flex;justify-content:space-between;align-items:center;gap:16px}.leave-heading h3{margin:4px 0;font-size:21px}.leave-heading p{margin:0;color:#64748b}.eyebrow{font-size:11px;font-weight:800;color:#2563eb;letter-spacing:.08em}.primary-btn,.secondary-btn,.approve-btn,.danger-btn,.info-btn{border:0;border-radius:10px;padding:11px 16px;font-weight:750;cursor:pointer}.primary-btn{background:#2563eb;color:#fff}.secondary-btn{background:#e2e8f0;color:#334155}.approve-btn{background:#059669;color:#fff}.danger-btn{background:#dc2626;color:#fff}.info-btn{background:#d97706;color:#fff}button:disabled{opacity:.55;cursor:not-allowed}.leave-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:18px;padding:18px;border-radius:15px;background:#f8fafc}.leave-form label{display:flex;flex-direction:column;gap:7px;font-weight:700;font-size:13px}.leave-form input,.leave-form select,.leave-form textarea,.review-actions input,.list-title select{border:1px solid #cbd5e1;border-radius:10px;padding:11px;background:#fff;color:#172033}.leave-form small{color:#64748b;font-weight:500}.wide{grid-column:1/-1}.form-actions{display:flex;justify-content:flex-end;gap:10px;background:transparent !important;padding:0 !important;border:none !important;margin-top:12px}.request-list,.admin-review{margin-top:20px}.list-title{padding-bottom:10px;border-bottom:1px solid #e2e8f0}.list-title span{font-size:12px;color:#64748b}.request-card,.review-card{position:relative;display:grid;grid-template-columns:1fr auto;gap:10px;margin-top:10px;padding:15px;border:1px solid #e2e8f0;border-radius:13px;background:#fff}.request-card p,.review-card p{margin:5px 0;color:#475569}.request-card small,.review-card small{color:#64748b}.request-side{display:flex;flex-direction:column;align-items:flex-end;gap:9px}.status{display:inline-flex;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:800;background:#fef3c7;color:#92400e}.status.approved{background:#d1fae5;color:#065f46}.status.rejected,.status.cancelled{background:#fee2e2;color:#991b1b}.status.needs_info{background:#ffedd5;color:#9a3412}.danger-link{border:0;background:none;color:#dc2626;font-weight:700;cursor:pointer}.manager-note{grid-column:1/-1;padding:9px 11px;border-radius:9px;background:#f1f5f9}.review-card{grid-template-columns:180px minmax(250px,1fr) auto}.employee{display:flex;flex-direction:column}.review-actions{grid-column:1/-1;display:flex;gap:8px}.review-actions input{flex:1}.empty{padding:24px;text-align:center;color:#64748b}.admin-review{padding-top:18px;border-top:2px solid #e2e8f0}
:global(.admin-layout.theme-dark) .leave-panel,:global(html[data-admin-theme='dark']) .leave-panel,:global(.admin-layout.theme-dark) .request-card,:global(.admin-layout.theme-dark) .review-card{background:#111827;border-color:#374151;color:#f8fafc}:global(.admin-layout.theme-dark) .leave-form{background:#0b1220}:global(.admin-layout.theme-dark) .leave-form input,:global(.admin-layout.theme-dark) .leave-form select,:global(.admin-layout.theme-dark) .leave-form textarea,:global(.admin-layout.theme-dark) .review-actions input,:global(.admin-layout.theme-dark) .list-title select{background:#1f2937;border-color:#4b5563;color:#fff}
@media(max-width:850px){.leave-form{grid-template-columns:1fr}.wide{grid-column:auto}.review-card{grid-template-columns:1fr}.review-actions{grid-column:auto;flex-wrap:wrap}.review-actions input{flex-basis:100%}.leave-heading{align-items:flex-start;flex-direction:column}.request-card{grid-template-columns:1fr}.request-side{align-items:flex-start}}
</style>

<style scoped>
.leave-panel { margin: 0; padding: 18px 20px; }
.supplement-notice { display:flex; flex-direction:column; gap:4px; padding:12px 14px; border:1px solid #fdba74; border-radius:11px; background:#fff7ed; color:#9a3412; }
.supplement-notice span { font-size:13px; color:#c2410c; }
.supplement-btn { border:0; border-radius:9px; padding:9px 13px; background:#d97706; color:#fff; font-weight:800; cursor:pointer; white-space:nowrap; }
.supplement-btn:hover { background:#b45309; }
.review-card:not(.history-card) { grid-template-columns:180px minmax(250px,1fr) minmax(145px,max-content); }
.review-side { display:flex; flex-direction:row !important; flex-wrap:nowrap !important; align-items:center; justify-content:flex-end; gap:6px; width:max-content; min-width:145px; justify-self:end; align-self:start; }
.review-side .leave-status { flex:0 0 auto; align-self:center; }
.review-side .view-btn { flex:0 0 auto; }
.view-btn { min-height:30px; border:1px solid #93c5fd; border-radius:8px; padding:5px 9px; background:#eff6ff; color:#1d4ed8; font-size:12px; font-weight:800; line-height:1; cursor:pointer; white-space:nowrap; }
.view-btn:hover { background:#dbeafe; }
.leave-detail-backdrop { position:fixed; inset:0; z-index:10050; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(2,6,23,.72); backdrop-filter:blur(5px); }
.leave-detail-modal { width:min(640px,100%); max-height:86vh; overflow:auto; padding:17px; border:1px solid #dbe3ef; border-radius:17px; background:#fff; color:#172033; box-shadow:0 28px 80px rgba(2,6,23,.35); }
.leave-detail-modal header { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding-bottom:11px; border-bottom:1px solid #e2e8f0; }
.leave-detail-modal header h3 { margin:3px 0 1px; font-size:21px; }
.leave-detail-modal header p { margin:0; color:#64748b; }
.detail-close { width:34px; height:34px; border:1px solid #fecaca; border-radius:9px; background:#fff1f2; color:#dc2626; font-size:23px; line-height:1; cursor:pointer; }
.detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:9px; margin-top:11px; }
.detail-grid > div,.detail-block { padding:10px 12px; border:1px solid #e2e8f0; border-radius:10px; background:#f8fafc; }
.detail-grid span,.detail-block > span,.evidence-title > span { display:block; margin-bottom:5px; color:#64748b; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; }
.detail-grid strong { display:block; overflow-wrap:anywhere; }
.detail-block { margin-top:9px; }
.detail-block p { margin:0; white-space:pre-wrap; overflow-wrap:anywhere; }
.evidence-title { display:flex; justify-content:space-between; align-items:center; gap:12px; }
.evidence-title a { color:#2563eb; font-weight:800; text-decoration:none; }
.evidence-block img,.evidence-block iframe { display:block; width:100%; max-height:280px; margin-top:8px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; object-fit:contain; }
.evidence-block iframe { height:280px; }
.no-evidence { margin-top:8px; padding:22px; border-radius:9px; background:#eef2f7; color:#64748b; text-align:center; }
.leave-detail-modal footer { display:flex; justify-content:flex-end; margin-top:11px; }
:global(.admin-layout.theme-dark) .supplement-notice,
:global(html[data-admin-theme='dark']) .supplement-notice { background:#431407; border-color:#c2410c; color:#ffedd5; }
:global(.admin-layout.theme-dark) .supplement-notice span,
:global(html[data-admin-theme='dark']) .supplement-notice span { color:#fed7aa; }
:global(html[data-admin-theme='dark']) .leave-detail-modal { background:#111827; border-color:#374151; color:#f8fafc; }
:global(html[data-admin-theme='dark']) .leave-detail-modal header { border-color:#374151; }
:global(html[data-admin-theme='dark']) .leave-detail-modal header p,
:global(html[data-admin-theme='dark']) .detail-grid span,
:global(html[data-admin-theme='dark']) .detail-block > span,
:global(html[data-admin-theme='dark']) .evidence-title > span { color:#cbd5e1; }
:global(html[data-admin-theme='dark']) .detail-grid > div,
:global(html[data-admin-theme='dark']) .detail-block { background:#1f2937; border-color:#4b5563; }
:global(html[data-admin-theme='dark']) .no-evidence { background:#0f172a; color:#cbd5e1; }
@media(max-width:650px){.detail-grid{grid-template-columns:1fr}.leave-detail-modal{padding:16px}.handover-grid{grid-template-columns:1fr}.evidence-block iframe{height:320px}}
.leave-heading { gap: 12px; }
.leave-heading h3 { margin: 3px 0; }
.request-list { margin-top: 16px; }
.list-title { padding-bottom: 8px; }
.request-card,
.review-card,
.empty { margin-top: 14px; }
.request-card,
.review-card { padding: 13px 15px; gap: 8px 16px; }
.request-card p,
.review-card p { margin: 3px 0; }
.manager-note { padding: 8px 10px; }
.admin-review { margin-top: 0; padding-top: 0; border-top: 0; }
.admin-review > .list-title > div,
.leave-history > .list-title > div { display: flex; flex-direction: column; gap: 4px; }
.leave-history { margin-top: 18px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
.history-note { margin: 2px 0 0 !important; }
.history-card { grid-template-columns: 180px minmax(250px, 1fr) auto; }
.history-pagination { display:flex; justify-content:center; align-items:center; gap:12px; margin-top:18px; }
.history-pagination button { min-width:92px; padding:9px 13px; border:1px solid #cbd5e1; border-radius:9px; background:#fff; color:#1e40af; font-weight:750; cursor:pointer; }
.history-pagination button:disabled { color:#94a3b8; background:#f1f5f9; }
:global(.admin-layout.theme-dark) .history-pagination button { background:#1f2937; border-color:#4b5563; color:#dbeafe; }
.leave-status {
  justify-self: end;
  align-self: start;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: auto !important;
  min-width: 86px;
  height: 34px !important;
  min-height: 34px;
  padding: 0 13px;
  border-radius: 999px;
  background: #fef3c7;
  color: #92400e;
  font-size: 12px;
  font-weight: 800;
  line-height: 1;
  white-space: nowrap;
}
.leave-status.approved { background: #d1fae5; color: #065f46; }
.leave-status.rejected,
.leave-status.cancelled { background: #fee2e2; color: #991b1b; }
.leave-status.needs_info { background: #ffedd5; color: #9a3412; }
.review-card { padding: 13px 15px; gap: 8px 16px; }
.review-actions {
  align-items: center;
  padding-top: 14px;
  border-top: 1px solid #e2e8f0;
}
.review-actions input { min-height: 46px; }
.review-actions button {
  min-height: 38px;
  padding: 8px 13px;
  border-radius: 9px;
  font-size: 13px;
  transition: transform .16s ease, filter .16s ease, box-shadow .16s ease;
  white-space: nowrap;
}
.review-actions button:hover:not(:disabled) {
  transform: translateY(-1px);
  filter: brightness(1.04);
  box-shadow: 0 7px 16px rgba(15, 23, 42, .13);
}
:global(.admin-layout.theme-dark) .review-actions,
:global(html[data-admin-theme='dark']) .review-actions { border-color: #374151; }
@media (max-width: 1050px) {
  .review-card:not(.history-card) { grid-template-columns: 1fr minmax(145px,max-content); }
  .employee { grid-column: 1 / -1; }
  .review-actions { flex-wrap: wrap; }
  .review-actions input { flex-basis: 100%; }
}
@media (max-width: 650px) {
  .leave-panel { padding: 15px; border-radius: 15px; }
  .review-card { grid-template-columns: 1fr; }
  .review-side { justify-self:start; }
  .leave-status { justify-self: start; }
  .review-actions button { flex: 1 1 100%; }
}
</style>

<style>
.admin-layout.theme-dark .leave-panel,
html[data-admin-theme='dark'] .leave-panel {
  background: #111827;
  border-color: #374151;
  color: #f8fafc;
}
.admin-layout.theme-dark .leave-panel .request-card,
.admin-layout.theme-dark .leave-panel .review-card,
html[data-admin-theme='dark'] .leave-panel .request-card,
html[data-admin-theme='dark'] .leave-panel .review-card {
  background: #151e2d;
  border-color: #374151;
  color: #f8fafc;
}
.admin-layout.theme-dark .leave-panel .leave-form,
html[data-admin-theme='dark'] .leave-panel .leave-form {
  background: #0b1220;
}
.admin-layout.theme-dark .leave-panel .form-actions,
html[data-admin-theme='dark'] .leave-panel .form-actions {
  background: transparent !important;
  border: none !important;
  box-shadow: none !important;
  padding: 0 !important;
}
.admin-layout.theme-dark .leave-panel p,
.admin-layout.theme-dark .leave-panel small,
html[data-admin-theme='dark'] .leave-panel p,
html[data-admin-theme='dark'] .leave-panel small {
  color: #b8c4d6;
}
.admin-layout.theme-dark .leave-panel input,
.admin-layout.theme-dark .leave-panel select,
.admin-layout.theme-dark .leave-panel textarea,
html[data-admin-theme='dark'] .leave-panel input,
html[data-admin-theme='dark'] .leave-panel select,
html[data-admin-theme='dark'] .leave-panel textarea {
  background: #1f2937;
  border-color: #4b5563;
  color: #f8fafc;
}
.admin-layout.theme-dark .leave-panel .manager-note,
html[data-admin-theme='dark'] .leave-panel .manager-note {
  background: #202c3d;
}
</style>
