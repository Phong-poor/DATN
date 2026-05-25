<script setup>
import { onMounted, ref, computed } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'

const loading = ref(true)
const payload = ref({ profiles: [], commissions: [], withdraw_requests: [] })
const statusMap = ['pending', 'approved', 'paid', 'cancelled']
const withdrawStatusMap = ['pending', 'approved', 'rejected', 'paid']

const formatMoney = (value) => Number(value || 0).toLocaleString('vi-VN') + 'đ'

const loadData = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/affiliates')
    payload.value = res.data
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

// Compute statistics for top dashboard overview
const stats = computed(() => {
  const profilesCount = payload.value.profiles?.length || 0
  
  const pendingCommissions = payload.value.commissions?.filter(c => c.status === 'pending') || []
  const pendingCommissionsCount = pendingCommissions.length
  const pendingCommissionsSum = pendingCommissions.reduce((sum, c) => sum + Number(c.amount || 0), 0)

  const pendingWithdraws = payload.value.withdraw_requests?.filter(w => w.status === 'pending') || []
  const pendingWithdrawsCount = pendingWithdraws.length
  const pendingWithdrawsSum = pendingWithdraws.reduce((sum, w) => sum + Number(w.amount || 0), 0)

  return {
    profilesCount,
    pendingCommissionsCount,
    pendingCommissionsSum,
    pendingWithdrawsCount,
    pendingWithdrawsSum
  }
})

const updateStatus = async (row, status) => {
  try {
    await api.put(`/admin/affiliate-commissions/${row.id}/status`, { status })
    swal.success('Thành công', 'Đã cập nhật trạng thái hoa hồng!')
    await loadData()
  } catch (err) {
    swal.error('Lỗi', 'Không thể cập nhật trạng thái hoa hồng!')
  }
}

const updateWithdrawStatus = async (row, status) => {
  try {
    await api.put(`/admin/affiliate-withdraws/${row.id}/status`, { status })
    swal.success('Thành công', 'Đã cập nhật yêu cầu rút tiền!')
    await loadData()
  } catch (err) {
    swal.error('Lỗi', 'Không thể cập nhật yêu cầu rút tiền!')
  }
}

onMounted(loadData)
</script>

<template>
  <div class="affiliate-admin-container">
    <!-- Header Block -->
    <div class="admin-header">
      <div class="header-info">
        <h1>Affiliate Center Admin</h1>
        <p>Quản lý danh sách nhà tiếp thị liên kết, phê duyệt hoa hồng phát sinh và duyệt yêu cầu thanh toán rút tiền.</p>
      </div>
      <button class="btn-refresh" @click="loadData" :disabled="loading">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" class="refresh-icon" :class="{ rotating: loading }">
          <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"></path>
        </svg>
        <span>Tải lại</span>
      </button>
    </div>

    <!-- Loading Placeholder -->
    <div class="loading-overlay" v-if="loading">
      <div class="spinner"></div>
      <p>Đang tải dữ liệu tiếp thị liên kết...</p>
    </div>

    <template v-else>
      <!-- Quick Statistics Widgets -->
      <div class="stats-grid">
        <div class="stat-card blue-gradient">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Tổng số Publishers</span>
            <strong class="stat-value">{{ stats.profilesCount }}</strong>
          </div>
        </div>

        <div class="stat-card orange-gradient">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Hoa hồng chờ duyệt</span>
            <strong class="stat-value">{{ stats.pendingCommissionsCount }} yêu cầu</strong>
            <span class="stat-subtext">Trị giá: {{ formatMoney(stats.pendingCommissionsSum) }}</span>
          </div>
        </div>

        <div class="stat-card teal-gradient">
          <div class="stat-icon">
            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12" y2="18"></line><line x1="2" y1="10" x2="22" y2="10"></line></svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Rút tiền chờ duyệt</span>
            <strong class="stat-value">{{ stats.pendingWithdrawsCount }} yêu cầu</strong>
            <span class="stat-subtext">Tổng tiền: {{ formatMoney(stats.pendingWithdrawsSum) }}</span>
          </div>
        </div>
      </div>

      <!-- Tab 1: Profiles -->
      <div class="table-card">
        <div class="card-header">
          <h3>
            <span class="indicator blue-indicator"></span>
            Danh sách Nhà tiếp thị (Publishers)
          </h3>
          <span class="count-badge">{{ payload.profiles.length }} Publishers</span>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Họ tên User</th>
                <th>Địa chỉ Email</th>
                <th>Mã Affiliate</th>
                <th>Tỉ lệ chia sẻ</th>
                <th>Tổng kiếm được</th>
                <th>Tổng đã thanh toán</th>
                <th>Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in payload.profiles" :key="p.id" class="data-row">
                <td class="user-cell">
                  <div class="avatar">{{ (p.user?.name || 'A').charAt(0).toUpperCase() }}</div>
                  <strong>{{ p.user?.name || '-' }}</strong>
                </td>
                <td>{{ p.user?.email || '-' }}</td>
                <td><code class="code-badge">{{ p.affiliate_code }}</code></td>
                <td><span class="rate-badge">{{ p.commission_rate }}%</span></td>
                <td class="money-text plus">{{ formatMoney(p.total_earned) }}</td>
                <td class="money-text">{{ formatMoney(p.total_paid) }}</td>
                <td>
                  <span class="status-pill" :class="p.status">
                    {{ p.status }}
                  </span>
                </td>
              </tr>
              <tr v-if="payload.profiles.length === 0">
                <td colspan="7" class="empty-cell">Chưa có nhà tiếp thị liên kết nào đăng ký</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 2: Commissions -->
      <div class="table-card">
        <div class="card-header">
          <h3>
            <span class="indicator orange-indicator"></span>
            Lịch sử & Phê duyệt hoa hồng
          </h3>
          <span class="count-badge">{{ payload.commissions.length }} giao dịch</span>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Mã đơn hàng</th>
                <th>Nhà tiếp thị</th>
                <th>Khách hàng mua</th>
                <th>Số tiền hoa hồng</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in payload.commissions" :key="c.id" class="data-row">
                <td><strong>#{{ c.order_id }}</strong></td>
                <td>
                  <div class="name-badge blue-name">{{ c.affiliate_user?.name || '-' }}</div>
                </td>
                <td>{{ c.referred_user?.name || '-' }}</td>
                <td class="money-text plus font-semibold">{{ formatMoney(c.amount) }}</td>
                <td>
                  <span class="status-pill" :class="c.status">
                    {{ c.status }}
                  </span>
                </td>
                <td>
                  <select :value="c.status" @change="updateStatus(c, $event.target.value)" class="status-select" :class="c.status">
                    <option v-for="s in statusMap" :key="s" :value="s">{{ s }}</option>
                  </select>
                </td>
              </tr>
              <tr v-if="payload.commissions.length === 0">
                <td colspan="6" class="empty-cell">Chưa phát sinh giao dịch chia sẻ hoa hồng nào</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab 3: Withdraw Requests -->
      <div class="table-card">
        <div class="card-header">
          <h3>
            <span class="indicator teal-indicator"></span>
            Yêu cầu thanh toán rút tiền
          </h3>
          <span class="count-badge">{{ payload.withdraw_requests.length }} yêu cầu</span>
        </div>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Người yêu cầu</th>
                <th>Số tiền đề xuất</th>
                <th>Ngân hàng</th>
                <th>Số tài khoản</th>
                <th>Trạng thái</th>
                <th>Phê duyệt nhanh</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="w in payload.withdraw_requests" :key="w.id" class="data-row">
                <td class="user-cell">
                  <strong>{{ w.affiliate_user?.name || '-' }}</strong>
                </td>
                <td class="money-text minus font-semibold">{{ formatMoney(w.amount) }}</td>
                <td><span class="bank-tag">{{ w.bank_name }}</span></td>
                <td><code class="code-badge font-semibold">{{ w.bank_account_number }}</code></td>
                <td>
                  <span class="status-pill" :class="w.status">
                    {{ w.status }}
                  </span>
                </td>
                <td>
                  <select :value="w.status" @change="updateWithdrawStatus(w, $event.target.value)" class="status-select" :class="w.status">
                    <option v-for="s in withdrawStatusMap" :key="s" :value="s">{{ s }}</option>
                  </select>
                </td>
              </tr>
              <tr v-if="payload.withdraw_requests.length === 0">
                <td colspan="6" class="empty-cell">Chưa có yêu cầu thanh toán rút tiền nào gửi lên</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

.affiliate-admin-container {
  font-family: 'Plus Jakarta Sans', sans-serif;
  color: #1e293b;
  background: #f8fafc;
  padding: 10px 0;
}

/* Header styling */
.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
}

.header-info h1 {
  font-size: 26px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 6px 0;
  letter-spacing: -0.02em;
}

.header-info p {
  font-size: 14.5px;
  color: #64748b;
  margin: 0;
  line-height: 1.5;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 10px 16px;
  font-size: 14px;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  transition: all 0.2s ease;
}

.btn-refresh:hover:not(:disabled) {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.refresh-icon.rotating {
  animation: spin-anim 1s linear infinite;
}

@keyframes spin-anim {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Loading state overlay */
.loading-overlay {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 60px 20px;
  box-shadow: 0 1px 10px rgba(0,0,0,0.02);
  text-align: center;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 3.5px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin-anim 0.8s linear infinite;
  margin-bottom: 16px;
}

.loading-overlay p {
  font-size: 15px;
  color: #64748b;
  font-weight: 500;
  margin: 0;
}

/* Dashboard Statistics Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 28px;
}

.stat-card {
  border-radius: 16px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 18px;
  color: #ffffff;
  box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06);
  position: relative;
  overflow: hidden;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 30px -5px rgba(0,0,0,0.1);
}

.stat-card::after {
  content: '';
  position: absolute;
  width: 120px;
  height: 120px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 50%;
  top: -30px;
  right: -30px;
}

.blue-gradient {
  background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
}

.orange-gradient {
  background: linear-gradient(135deg, #c2410c 0%, #f97316 100%);
}

.teal-gradient {
  background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
}

.stat-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  background: rgba(255,255,255,0.16);
  display: grid;
  place-items: center;
  flex-shrink: 0;
  backdrop-filter: blur(4px);
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 12.5px;
  font-weight: 600;
  color: rgba(255,255,255,0.85);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  line-height: 1.2;
}

.stat-subtext {
  font-size: 12px;
  color: rgba(255,255,255,0.9);
  margin-top: 2px;
  font-weight: 500;
}

/* Premium Layout Tables */
.table-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.02);
  margin-bottom: 28px;
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #f1f5f9;
  background: #ffffff;
}

.card-header h3 {
  font-size: 16.5px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.blue-indicator { background-color: #3b82f6; box-shadow: 0 0 8px #3b82f6; }
.orange-indicator { background-color: #f97316; box-shadow: 0 0 8px #f97316; }
.teal-indicator { background-color: #0d9488; box-shadow: 0 0 8px #0d9488; }

.count-badge {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  background: #f1f5f9;
  padding: 5px 12px;
  border-radius: 999px;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background: #f8fafc;
  color: #475569;
  font-weight: 600;
  font-size: 12.5px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  padding: 14px 24px;
  border-bottom: 1px solid #f1f5f9;
  text-align: left;
}

td {
  padding: 16px 24px;
  border-bottom: 1px solid #f8fafc;
  font-size: 14px;
  color: #334155;
  transition: background-color 0.15s ease;
}

.data-row {
  transition: all 0.15s ease;
}

.data-row:hover {
  background-color: #f8fafc;
}

.data-row:hover td {
  color: #0f172a;
}

.empty-cell {
  text-align: center;
  padding: 40px;
  color: #94a3b8;
  font-size: 14.5px;
  font-weight: 500;
}

/* Specific elements inside tables */
.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e0f2fe;
  color: #0369a1;
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 13px;
  box-shadow: inset 0 0 0 1px rgba(3, 105, 161, 0.1);
}

.code-badge {
  background: #f1f5f9;
  color: #0f172a;
  border: 1px solid #e2e8f0;
  padding: 4px 8px;
  border-radius: 6px;
  font-family: monospace;
  font-size: 13px;
}

.rate-badge {
  background: #ecfdf5;
  color: #065f46;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 13px;
  border: 1px solid #a7f3d0;
}

.money-text {
  font-variant-numeric: tabular-nums;
  font-weight: 500;
}

.money-text.plus {
  color: #10b981;
}

.money-text.minus {
  color: #ef4444;
}

.font-semibold {
  font-weight: 600;
}

.name-badge {
  display: inline-block;
  font-size: 13px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
}

.blue-name {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}

.bank-tag {
  background: #fdf2f8;
  color: #9d174d;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 13px;
  border: 1px solid #fbcfe8;
}

/* Status Pill classes */
.status-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 999px;
  text-transform: capitalize;
  width: fit-content;
}

/* Pending status */
.status-pill.pending,
.status-pill.unpaid {
  background: #fff7ed;
  color: #c2410c;
  border: 1px solid #ffedd5;
}

/* Approved status */
.status-pill.approved {
  background: #f0fdf4;
  color: #15803d;
  border: 1px solid #dcfce7;
}

/* Paid status */
.status-pill.paid {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #dbeafe;
}

/* Cancelled / Rejected status */
.status-pill.cancelled,
.status-pill.rejected {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fee2e2;
}

/* Dropdown Select elements styling */
.status-select {
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 6px 28px 6px 12px;
  font-size: 13px;
  font-weight: 600;
  background-color: #ffffff;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s ease;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 8px center;
  background-size: 12px;
}

.status-select:hover {
  border-color: #94a3b8;
  background-color: #f8fafc;
}

.status-select:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}

.status-select.pending {
  color: #c2410c;
  border-color: #fed7aa;
}
.status-select.approved {
  color: #15803d;
  border-color: #bbf7d0;
}
.status-select.paid {
  color: #1d4ed8;
  border-color: #bfdbfe;
}
.status-select.cancelled,
.status-select.rejected {
  color: #b91c1c;
  border-color: #fecaca;
}
</style>
