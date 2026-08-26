<template>
  <div class="bulk-toolbar">
    <div class="bulk-summary">
      <span class="bulk-icon">✓</span>
      <div>
        <b>{{ selectedCount }} {{ label }} đã chọn</b>
        <p>Dùng checkbox ở đầu bảng để chọn nhanh các mục trên trang hiện tại.</p>
      </div>
    </div>
    <div class="bulk-actions">
      <slot name="tools" />
      <button class="bulk-btn ghost" :disabled="!selectedCount || loading" @click="$emit('clear')">
        Bỏ chọn
      </button>
      <button class="bulk-btn danger" :disabled="!selectedCount || loading" @click="$emit('delete-selected')">
        {{ loading ? 'Đang xóa...' : `Xóa đã chọn (${selectedCount})` }}
      </button>
      <button class="bulk-btn danger-outline" :disabled="!totalCount || loading" @click="$emit('delete-all')">
        Xóa toàn bộ
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  selectedCount: { type: Number, default: 0 },
  totalCount: { type: Number, default: 0 },
  label: { type: String, default: 'mục' },
  loading: { type: Boolean, default: false },
})

defineEmits(['clear', 'delete-selected', 'delete-all'])
</script>

<style scoped>
.bulk-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  background: #fff;
  border: 1px solid #e8edf5;
  border-bottom: none !important;
  border-radius: 14px 14px 0 0 !important;
  margin-bottom: 0 !important;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
}

.bulk-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.bulk-icon {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  color: #2563eb;
  font-weight: 800;
  flex-shrink: 0;
}

.bulk-summary b {
  display: block;
  font-size: 13px;
  color: #0f172a;
  margin-bottom: 2px;
}

.bulk-summary p {
  margin: 0;
  font-size: 12px;
  color: #64748b;
}

.bulk-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  flex-wrap: wrap;
}

.bulk-btn {
  min-height: 34px;
  padding: 8px 12px;
  border-radius: 9px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all .18s;
  border: 1px solid transparent;
  font-family: inherit;
  white-space: nowrap;
}

.bulk-btn:disabled {
  opacity: .48;
  cursor: not-allowed;
  transform: none !important;
}

.bulk-btn.ghost {
  background: #f8fafc;
  color: #334155;
  border-color: #e2e8f0;
}

.bulk-btn.ghost:hover:not(:disabled) {
  background: #eff6ff;
  color: #2563eb;
  border-color: #bfdbfe;
}

.bulk-btn.danger {
  background: #ef4444;
  color: #fff;
  border-color: #ef4444;
}

.bulk-btn.danger:hover:not(:disabled) {
  background: #dc2626;
  border-color: #dc2626;
  transform: translateY(-1px);
}

.bulk-btn.danger-outline {
  background: #fff1f2;
  color: #dc2626;
  border-color: #fecaca;
}

.bulk-btn.danger-outline:hover:not(:disabled) {
  background: #ffe4e6;
  border-color: #fca5a5;
}

@media (max-width: 768px) {
  .bulk-toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .bulk-actions {
    justify-content: flex-start;
  }
}
</style>
