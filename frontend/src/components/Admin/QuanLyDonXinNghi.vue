<script setup>
import LeaveRequestPanel from '@/components/Admin/LeaveRequestPanel.vue'
</script>

<template>
  <div class="leave-management-page">
    <LeaveRequestPanel admin />
  </div>
</template>

<style scoped>
.leave-management-page { padding: 0 0 24px; }
@media (max-width: 760px) { .leave-management-page { padding-bottom: 18px; } }
</style>
