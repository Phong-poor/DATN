<script setup>
import { onMounted, ref } from 'vue'
import api from '@/services/api'
import swal from '@/services/swal'

const loadingXu = ref(false)
const savingXu = ref(false)
const xuForm = ref({
  ti_le_quy_doi: 1,
  ti_le_tich_luy: 1.00,
  phan_tram_giam_toi_da: 50,
  trang_thai: true
})

async function fetchXuSettings() {
  loadingXu.value = true
  try {
    const res = await api.get('/admin/xu/settings')
    if (res.data?.success) {
      xuForm.value = res.data.settings
    }
  } catch (error) {
    console.error('Không thể tải cài đặt xu:', error)
  } finally {
    loadingXu.value = false
  }
}

async function saveXuSettings() {
  savingXu.value = true
  try {
    const res = await api.put('/admin/xu/settings', xuForm.value)
    if (res.data?.success) {
      xuForm.value = res.data.settings
      await swal.success('Thành công', 'Đã lưu cấu hình hệ thống xu.')
    }
  } catch (error) {
    swal.error('Lưu thất bại', error?.response?.data?.message || 'Có lỗi xảy ra khi lưu.')
  } finally {
    savingXu.value = false
  }
}

onMounted(async () => {
  await fetchXuSettings()
})
</script>

<template>
  <div class="xu-config-page">
    <main class="xu-pane">
      <section class="panel">
        <div class="panel-head">
          <div>
            <h3>Cấu hình hệ thống Xu</h3>
            <p>Thiết lập mệnh giá quy đổi giá trị cho Xu sử dụng trong hệ thống.</p>
          </div>
          <button class="save-btn" :disabled="savingXu || loadingXu" type="button" @click="saveXuSettings">
            {{ savingXu ? 'Đang lưu...' : 'Lưu cấu hình xu' }}
          </button>
        </div>

        <div v-if="loadingXu" class="state">Đang tải cài đặt xu...</div>
        <div v-else class="form-grid">
          <label>
            <span>Mệnh giá quy đổi (1 xu = X đ)</span>
            <input v-model.number="xuForm.ti_le_quy_doi" type="number" min="1" />
          </label>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
.xu-config-page {
  padding: 22px 28px 34px;
}
.panel {
  background: #fff;
  border: 1px solid #dfe7f2;
  border-radius: 16px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, .04);
  overflow: hidden;
}
.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 20px;
  border-bottom: 1px solid #edf2f7;
}
.panel-head h3 {
  margin: 0;
  font-size: 19px;
  color: #0f172a;
  line-height: 1.25;
}
.panel-head p {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.45;
}
.save-btn {
  border-radius: 11px;
  padding: 9px 14px;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
  border: 1px solid #2563eb;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #fff;
  box-shadow: 0 8px 18px rgba(37, 99, 235, .22);
  transition: opacity 0.18s;
}
.save-btn:disabled {
  opacity: .65;
  cursor: not-allowed;
}
.state {
  padding: 22px;
  color: #64748b;
  font-size: 14px;
}
.form-grid {
  padding: 20px;
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
}
label {
  display: flex;
  flex-direction: column;
  gap: 7px;
}
label span {
  font-size: 13px;
  color: #475569;
  font-weight: 700;
}
input {
  border: 1px solid #dbe2ea;
  border-radius: 10px;
  padding: 10px 12px;
  font-size: 14px;
  font-family: inherit;
  background: #fff;
  color: #0f172a;
  outline: none;
}
input:focus {
  border-color: #93c5fd;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, .15);
}
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type=number] {
  -moz-appearance: textfield;
}
</style>
