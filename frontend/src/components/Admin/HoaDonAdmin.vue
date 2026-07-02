<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '@/services/api'

const months = ref(6)
const loading = ref(false)
const payload = ref({ totals: { revenue: 0, discount: 0, orders: 0 }, series: [] })

const maxRevenue = computed(() => Math.max(...payload.value.series.map((s) => s.revenue || 0), 1))

function money(v) {
  return `${Number(v || 0).toLocaleString('vi-VN')}đ`
}

async function fetchBilling() {
  loading.value = true
  try {
    const res = await api.get('/admin/account/billing', { params: { months: months.value } })
    payload.value = res.data?.data || payload.value
  } finally {
    loading.value = false
  }
}

onMounted(fetchBilling)
</script>

<template>
  <div class="card">
    <div class="head">
      <h3>Billing</h3>
      <div class="actions">
        <select v-model.number="months" @change="fetchBilling">
          <option :value="3">3 tháng</option>
          <option :value="6">6 tháng</option>
          <option :value="12">12 tháng</option>
        </select>
      </div>
    </div>

    <div class="stats">
      <div class="box"><span>Tổng doanh thu</span><b>{{ money(payload.totals.revenue) }}</b></div>
      <div class="box"><span>Tổng giảm giá</span><b>{{ money(payload.totals.discount) }}</b></div>
      <div class="box"><span>Tổng đơn hàng</span><b>{{ payload.totals.orders }}</b></div>
    </div>

    <div v-if="loading" class="state">Đang tải dữ liệu billing...</div>
    <div v-else class="chart">
      <div v-for="(s, i) in payload.series" :key="i" class="col">
        <div class="bar-wrap">
          <div class="bar" :style="{ height: `${Math.max(6, (s.revenue / maxRevenue) * 100)}%` }"></div>
        </div>
        <small>{{ s.label }}</small>
        <p>{{ s.orders }} đơn</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px}
.head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
h3{margin:0;font-size:22px}
select{border:1px solid #dbe2ea;border-radius:10px;padding:8px 10px}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.box{border:1px solid #edf2f7;border-radius:12px;padding:12px;background:#f8fafc}
.box span{display:block;color:#64748b;font-size:12px}
.box b{font-size:18px;color:#0f172a}
.state{padding:16px;color:#64748b}
.chart{display:grid;grid-template-columns:repeat(auto-fit,minmax(70px,1fr));gap:8px;align-items:end;height:240px}
.col{display:flex;flex-direction:column;align-items:center;gap:6px}
.bar-wrap{height:160px;width:100%;display:flex;align-items:flex-end;justify-content:center}
.bar{width:18px;max-height:160px;border-radius:8px 8px 4px 4px;background:linear-gradient(180deg,#6366f1,#4f46e5)}
small{color:#64748b;font-size:11px}
p{margin:0;color:#334155;font-size:12px}
@media (max-width:900px){.stats{grid-template-columns:1fr}}
</style>

