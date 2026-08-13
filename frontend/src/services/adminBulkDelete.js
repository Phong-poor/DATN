import { computed, ref } from 'vue'
import api from './api'
import swal from './swal'

export function useAdminBulkDelete({
  items,
  filteredItems,
  pageItems,
  getId,
  endpoint,
  entityLabel,
  fetchItems,
  canDelete = () => true,
  cannotDeleteMessage = 'Một số mục không thể xóa.',
}) {
  const selectedIds = ref([])
  const isBulkDeleting = ref(false)

  const visibleItems = computed(() => pageItems?.value || filteredItems.value || items.value || [])
  const deletableVisibleItems = computed(() => visibleItems.value.filter(canDelete))
  const deletableFilteredItems = computed(() => (filteredItems.value || items.value || []).filter(canDelete))

  const allCurrentPageSelected = computed(() =>
    deletableVisibleItems.value.length > 0 &&
    deletableVisibleItems.value.every(item => selectedIds.value.includes(getId(item)))
  )

  const toggleItemSelection = (id) => {
    selectedIds.value = selectedIds.value.includes(id)
      ? selectedIds.value.filter(item => item !== id)
      : [...selectedIds.value, id]
  }

  const toggleCurrentPageSelection = () => {
    const pageIds = deletableVisibleItems.value.map(getId)

    if (allCurrentPageSelected.value) {
      selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id))
      return
    }

    selectedIds.value = Array.from(new Set([...selectedIds.value, ...pageIds]))
  }

  const clearSelection = () => {
    selectedIds.value = []
  }

  const pruneSelection = () => {
    const allIds = new Set((items.value || []).map(getId))
    selectedIds.value = selectedIds.value.filter(id => allIds.has(id))
  }

  const deleteByIds = async (ids) => {
    const uniqueIds = Array.from(new Set(ids))
    const results = await Promise.allSettled(uniqueIds.map(id => api.delete(endpoint(id))))
    const failed = results.filter(result => result.status === 'rejected')

    await fetchItems()
    pruneSelection()

    if (failed.length) {
      swal.warning(
        'Xóa chưa hoàn tất',
        `Đã xóa ${uniqueIds.length - failed.length}/${uniqueIds.length} ${entityLabel}. ${cannotDeleteMessage}`
      )
      return
    }

    swal.success('Thành công', `Đã xóa ${uniqueIds.length} ${entityLabel}.`)
  }

  const removeSelected = async () => {
    if (!selectedIds.value.length || isBulkDeleting.value) return

    const confirmed = await swal.confirm(
      'Xóa mục đã chọn',
      `Bạn có chắc muốn xóa ${selectedIds.value.length} ${entityLabel} đã chọn không?`
    )

    if (!confirmed) return

    isBulkDeleting.value = true
    try {
      await deleteByIds(selectedIds.value)
    } finally {
      isBulkDeleting.value = false
    }
  }

  const removeAllFiltered = async () => {
    const ids = deletableFilteredItems.value.map(getId)
    if (!ids.length || isBulkDeleting.value) return

    const confirmed = await swal.confirm(
      'Xóa toàn bộ',
      `Bạn có chắc muốn xóa toàn bộ ${ids.length} ${entityLabel} trong danh sách hiện tại không?`
    )

    if (!confirmed) return

    isBulkDeleting.value = true
    try {
      await deleteByIds(ids)
    } finally {
      isBulkDeleting.value = false
    }
  }

  return {
    selectedIds,
    isBulkDeleting,
    allCurrentPageSelected,
    toggleItemSelection,
    toggleCurrentPageSelection,
    clearSelection,
    removeSelected,
    removeAllFiltered,
  }
}
