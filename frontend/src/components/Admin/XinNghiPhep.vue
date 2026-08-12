<script setup>
import LeaveRequestPanel from '@/components/Admin/LeaveRequestPanel.vue'
</script>

<template>
  <div class="employee-leave-page">
    <LeaveRequestPanel />
  </div>
</template>

<style scoped>
.employee-leave-page { padding: 0 0 24px; }
@media (max-width: 760px) { .employee-leave-page { padding-bottom: 18px; } }
</style>
