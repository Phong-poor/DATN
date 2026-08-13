<script setup>
import { ref, computed, nextTick } from 'vue'

const props = defineProps({
  currentPage: {
    type: Number,
    required: true,
    default: 1
  },
  totalPages: {
    type: Number,
    required: true,
    default: 1
  },
  totalItems: {
    type: Number,
    default: 0
  },
  pageSize: {
    type: Number,
    default: 10
  },
  itemLabel: {
    type: String,
    default: 'mục'
  },
  showingText: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['changePage', 'update:currentPage'])

// ─── STATE CHO NÚT NHẬP TRANG (JUMP INPUT) ───
const activeEllipsisKey = ref(null) // 'left-ellipsis' | 'right-ellipsis' | null
const jumpInputValue = ref('')
const jumpInputRef = ref(null)

const actualTotalItems = computed(() => {
  const raw = props.totalItems
  if (raw && typeof raw === 'object' && 'value' in raw) {
    return Number(raw.value) || 0
  }
  return Number(raw) || 0
})

const actualPageSize = computed(() => {
  const raw = props.pageSize
  if (raw && typeof raw === 'object' && 'value' in raw) {
    return Number(raw.value) || 10
  }
  return Number(raw) || 10
})

const fromItem = computed(() => {
  if (!actualTotalItems.value || actualTotalItems.value === 0) return 0
  return (props.currentPage - 1) * actualPageSize.value + 1
})

const toItem = computed(() => {
  if (!actualTotalItems.value || actualTotalItems.value === 0) return 0
  return Math.min(props.currentPage * actualPageSize.value, actualTotalItems.value)
})

const computedShowingText = computed(() => {
  if (props.showingText) return props.showingText
  return `Hiển thị ${fromItem.value} – ${toItem.value} của ${actualTotalItems.value} ${props.itemLabel}`
})

// ─── ALGORITHM HIỂN THỊ CÁC TRANG ───
const paginationItems = computed(() => {
  const total = Math.max(1, props.totalPages)
  const current = props.currentPage
  const items = []

  // Nếu tổng số trang <= 7: Hiện toàn bộ 1 .. total
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      items.push({ type: 'page', value: i })
    }
    return items
  }

  // Khi total > 7:
  // 1) Gần đầu (current <= 4): Không cần left-ellipsis
  if (current <= 4) {
    const endWindow = Math.max(3, current + 1)
    for (let i = 1; i <= endWindow; i++) {
      items.push({ type: 'page', value: i })
    }
    items.push({ type: 'ellipsis', key: 'right-ellipsis' })
    for (let i = total - 1; i <= total; i++) {
      items.push({ type: 'page', value: i })
    }
    return items
  }

  // 2) Gần cuối (current >= total - 3): Không cần right-ellipsis
  if (current >= total - 3) {
    for (let i = 1; i <= 2; i++) {
      items.push({ type: 'page', value: i })
    }
    items.push({ type: 'ellipsis', key: 'left-ellipsis' })
    const startWindow = Math.min(total - 2, current - 1)
    for (let i = startWindow; i <= total; i++) {
      items.push({ type: 'page', value: i })
    }
    return items
  }

  // 3) Ở giữa (4 < current < total - 3): Cần cả 2 ellipsis
  // VD: total = 50, current = 25 -> [1, 2, '...', 24, 25, 26, '...', 49, 50]
  for (let i = 1; i <= 2; i++) {
    items.push({ type: 'page', value: i })
  }
  items.push({ type: 'ellipsis', key: 'left-ellipsis' })
  items.push({ type: 'page', value: current - 1 })
  items.push({ type: 'page', value: current })
  items.push({ type: 'page', value: current + 1 })
  items.push({ type: 'ellipsis', key: 'right-ellipsis' })
  for (let i = total - 1; i <= total; i++) {
    items.push({ type: 'page', value: i })
  }

  return items
})

const goToPage = (page) => {
  const target = Math.max(1, Math.min(page, props.totalPages))
  if (target !== props.currentPage) {
    emit('changePage', target)
    emit('update:currentPage', target)
  }
  activeEllipsisKey.value = null
}

const openJumpInput = (key) => {
  activeEllipsisKey.value = key
  jumpInputValue.value = ''
  nextTick(() => {
    if (jumpInputRef.value) {
      const el = Array.isArray(jumpInputRef.value) ? jumpInputRef.value[0] : jumpInputRef.value
      el?.focus()
    }
  })
}

const handleJumpSubmit = () => {
  const parsed = parseInt(jumpInputValue.value, 10)
  if (!isNaN(parsed) && parsed >= 1 && parsed <= props.totalPages) {
    goToPage(parsed)
  } else {
    activeEllipsisKey.value = null
  }
}

const handleJumpKeyDown = (e) => {
  if (e.key === 'Enter') {
    handleJumpSubmit()
  } else if (e.key === 'Escape') {
    activeEllipsisKey.value = null
  }
}
</script>

<template>
  <div class="admin-pagination-container" v-if="totalPages >= 1">
    <div class="admin-pagination-info">
      {{ computedShowingText }}
    </div>

    <div class="admin-pagination-nav">
      <!-- Nút Trước (Previous) -->
      <button
        class="nav-btn prev-btn"
        :disabled="currentPage <= 1"
        @click="goToPage(currentPage - 1)"
        title="Trang trước"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
      </button>

      <!-- Các nút số trang & Nút nhập trang '...' -->
      <template v-for="(item, idx) in paginationItems" :key="idx">
        <!-- Nút Số Trang -->
        <button
          v-if="item.type === 'page'"
          class="page-btn"
          :class="{ active: currentPage === item.value }"
          @click="goToPage(item.value)"
        >
          {{ item.value }}
        </button>

        <!-- Ô Nhập Trang hoặc Nút '...' -->
        <div v-else-if="item.type === 'ellipsis'" class="ellipsis-wrapper">
          <input
            v-if="activeEllipsisKey === item.key"
            ref="jumpInputRef"
            type="number"
            v-model="jumpInputValue"
            class="jump-input"
            placeholder="Số trang"
            :min="1"
            :max="totalPages"
            @blur="handleJumpSubmit"
            @keydown="handleJumpKeyDown"
          />
          <button
            v-else
            class="dots-btn"
            @click="openJumpInput(item.key)"
            title="Nhấp để nhập nhanh số trang..."
          >
            ...
          </button>
        </div>
      </template>

      <!-- Nút Sau (Next) -->
      <button
        class="nav-btn next-btn"
        :disabled="currentPage >= totalPages || totalPages === 0"
        @click="goToPage(currentPage + 1)"
        title="Trang sau"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
.admin-pagination-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 32px;
  background: transparent;
  width: 100%;
  box-sizing: border-box;
  flex-wrap: wrap;
  gap: 12px;
}

.admin-pagination-info {
  font-size: 13.5px;
  font-weight: 500;
  color: #64748b;
  user-select: none;
}

.admin-pagination-nav {
  display: flex;
  align-items: center;
  gap: 6px;
}

.nav-btn,
.page-btn,
.dots-btn {
  min-width: 36px;
  height: 36px;
  padding: 0 10px;
  border-radius: 10px;
  border: 1.5px solid #e2e8f0;
  background: #ffffff;
  color: #334155;
  font-size: 13.5px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
  user-select: none;
  font-family: inherit;
}

.nav-btn svg {
  width: 14px;
  height: 14px;
  stroke-width: 2.2;
}

.nav-btn:hover:not(:disabled),
.page-btn:hover:not(:disabled),
.dots-btn:hover {
  border-color: #2563eb;
  color: #2563eb;
  background: #eff6ff;
  transform: translateY(-1px);
}

.page-btn.active {
  background: #2563eb !important;
  border-color: #2563eb !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3) !important;
  transform: none !important;
}

.nav-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
  border-color: #e2e8f0;
  background: #f8fafc;
  color: #94a3b8;
  box-shadow: none;
  transform: none;
}

.ellipsis-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.dots-btn {
  color: #64748b;
  letter-spacing: 1px;
}

.jump-input {
  width: 58px;
  height: 36px;
  border: 1.5px solid #2563eb;
  border-radius: 10px;
  text-align: center;
  font-size: 13.5px;
  font-weight: 700;
  color: #2563eb;
  outline: none;
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
  font-family: inherit;
  box-sizing: border-box;
  padding: 0 4px;
}

/* Chrome, Safari, Edge, Opera remove number arrows */
.jump-input::-webkit-outer-spin-button,
.jump-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.jump-input[type=number] {
  -moz-appearance: textfield;
}

@media (max-width: 640px) {
  .admin-pagination-container {
    padding: 12px 16px;
    flex-direction: column;
    align-items: center;
  }
}
</style>
