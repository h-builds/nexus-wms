<template>
  <div class="app-shell">
    <header class="topbar">
      <div>
        <p class="eyebrow">NexusWMS</p>
        <h1>Orchestrator Twin</h1>
      </div>

      <div class="status-pill">Tactical Simulation Layer</div>
    </header>

    <main class="layout">
      <section class="scene-panel">
        <div class="scene-placeholder">
          <span>3D / 2.5D Warehouse Scene Placeholder</span>
        </div>
      </section>

      <aside class="insights-panel">
        <article class="card">
          <p class="label">Congestion Risk</p>
          <strong>Low</strong>
        </article>

        <article class="card">
          <p class="label">Slotting Recommendation</p>
          <strong>Pending</strong>
        </article>

        <article class="card">
          <p class="label">Workforce Balance</p>
          <strong>50 / 50</strong>
        </article>

        <article class="card">
          <p class="label">Next Simulation</p>
          <strong>Not configured</strong>
        </article>
      </aside>
    </main>
  </div>
</template>

<style scoped>
:global(*) {
  box-sizing: border-box;
}

:global(body) {
  margin: 0;
  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    sans-serif;
  background: #020617;
  color: #e2e8f0;
}

:global(#app) {
  min-height: 100vh;
}

.app-shell {
  min-height: 100vh;
  padding: 24px;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
}

.eyebrow {
  margin: 0 0 6px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #94a3b8;
}

h1 {
  margin: 0;
  font-size: 32px;
}

.status-pill {
  padding: 10px 14px;
  border: 1px solid #334155;
  border-radius: 999px;
  background: #0f172a;
  color: #cbd5e1;
  font-size: 14px;
}

.layout {
  display: grid;
  grid-template-columns: 1.5fr 380px;
  gap: 24px;
}

.scene-panel,
.card {
  border: 1px solid #1e293b;
  background: #0f172a;
  border-radius: 16px;
}

.scene-panel {
  padding: 20px;
  min-height: 70vh;
}

.scene-placeholder {
  display: grid;
  place-items: center;
  width: 100%;
  min-height: 100%;
  border: 1px dashed #334155;
  border-radius: 12px;
  background: linear-gradient(180deg, rgba(30, 41, 59, 0.35), rgba(15, 23, 42, 0.9));
  color: #94a3b8;
  text-align: center;
  padding: 24px;
}

.insights-panel {
  display: grid;
  gap: 16px;
  align-content: start;
}

.card {
  padding: 18px;
}

.label {
  margin: 0 0 8px;
  font-size: 13px;
  color: #94a3b8;
}

.card strong {
  font-size: 24px;
}

@media (max-width: 1100px) {
  .layout {
    grid-template-columns: 1fr;
  }

  .scene-panel {
    min-height: 50vh;
  }
}

@media (max-width: 640px) {
  .topbar {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
