<template>
  <main class="gaming-dev-page">
    <section class="gaming-dev-panel">
      <span class="gaming-dev-label">Gaming</span>
      <h1>Trang đang được phát triển</h1>
      <p>Giao diện Gaming đang được xây dựng lại.</p>
    </section>
  </main>
</template>

<style scoped>
.gaming-dev-page {
  min-height: calc(100vh - 160px);
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 72px 20px;
}

.gaming-dev-panel {
  width: min(680px, 100%);
  text-align: center;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 56px 32px;
  box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
}

.gaming-dev-label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #2563eb;
  background: #eff6ff;
  border: 1px solid #dbeafe;
  border-radius: 999px;
  padding: 7px 14px;
  font-size: 13px;
  font-weight: 800;
  text-transform: uppercase;
  margin-bottom: 18px;
}

.gaming-dev-panel h1 {
  color: #0f172a;
  font-size: clamp(34px, 5vw, 56px);
  line-height: 1.08;
  margin: 0 0 14px;
}

.gaming-dev-panel p {
  color: #64748b;
  font-size: 17px;
  line-height: 1.6;
  margin: 0;
}
</style>
