<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const customTitle = ref('')

const handleCustomTitle = (e) => {
  customTitle.value = e.detail
}

onMounted(() => {
  window.addEventListener('page-title-updated', handleCustomTitle)
})

onUnmounted(() => {
  window.removeEventListener('page-title-updated', handleCustomTitle)
})

// Reset custom title on route changes to avoid showing incorrect product title
watch(() => route.path, () => {
  customTitle.value = ''
})

const crumbs = computed(() => {
  const list = []
  
  // Always include Trang chủ link
  list.push({
    name: 'Trang chủ',
    path: '/'
  })
  
  if (route.path.startsWith('/san-pham/') && route.params.id) {
    list.push({
      name: 'Sản phẩm',
      path: '/san-pham'
    })
    list.push({
      name: customTitle.value || route.meta.title || 'Chi tiết sản phẩm',
      path: '',
      active: true
    })
  } else {
    if (route.meta && route.meta.title) {
      list.push({
        name: route.meta.title,
        path: '',
        active: true
      })
    }
  }
  
  return list
})
</script>

<template>
  <div class="breadcrumbs-bar">
    <div class="breadcrumbs-container">
      <nav class="breadcrumbs-nav" aria-label="Breadcrumb">
        <ol class="breadcrumbs-list">
          <li v-for="(crumb, index) in crumbs" :key="index" class="breadcrumbs-item">
            <!-- Separator Chevron -->
            <svg v-if="index > 0" class="chevron-separator" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <polyline points="9 18 15 12 9 6" />
            </svg>
            
            <router-link v-if="crumb.path" :to="crumb.path" class="breadcrumb-link">
              <span v-if="crumb.name === 'Trang chủ'" class="home-wrap">
                <svg class="home-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                  <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
                {{ crumb.name }}
              </span>
              <span v-else>{{ crumb.name }}</span>
            </router-link>
            
            <span v-else class="breadcrumb-current" :title="crumb.name">
              {{ crumb.name }}
            </span>
          </li>
        </ol>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.breadcrumbs-bar {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  padding: 6px 0;
  position: relative;
  z-index: 2;
  transition: all 0.3s ease;
}

.breadcrumbs-container {
  max-width: 1300px;
  width: 100%;
  margin: 0 auto;
  padding: 0 24px;
  box-sizing: border-box;
}

.breadcrumbs-list {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  list-style: none;
  margin: 0;
  padding: 0;
  gap: 4px;
}

.breadcrumbs-item {
  display: flex;
  align-items: center;
  font-size: 12.5px;
  font-weight: 500;
  color: #64748b;
  min-width: 0;
}

.chevron-separator {
  width: 12px;
  height: 12px;
  stroke-width: 2.5;
  color: #94a3b8;
  margin: 0 6px;
  flex-shrink: 0;
}

.breadcrumb-link {
  color: #64748b;
  text-decoration: none;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  display: flex;
  align-items: center;
  white-space: nowrap;
}

.breadcrumb-link:hover {
  color: #2563eb;
  transform: translateY(-1px);
}

.home-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
}

.home-icon {
  width: 12px;
  height: 12px;
  stroke-width: 2.2;
}

.breadcrumb-current {
  color: #1e293b;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 320px;
}

/* ===================== RESPONSIVE ===================== */
@media (max-width: 1024px) {
  .breadcrumbs-bar {
    padding: 5px 0;
  }
  .breadcrumbs-container {
    padding: 0 20px;
  }
}

@media (max-width: 576px) {
  .breadcrumbs-bar {
    padding: 4px 0;
  }
  .breadcrumbs-container {
    padding: 0 12px;
  }
  .breadcrumbs-item {
    font-size: 12px;
  }
  .breadcrumb-current {
    max-width: 130px; /* Clamps dynamic title nicely to fit small mobile screens */
  }
  .chevron-separator {
    margin: 0 4px;
    width: 12px;
    height: 12px;
  }
}
</style>
