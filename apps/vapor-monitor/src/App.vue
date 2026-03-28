<template>
  <div class="app-shell">
    <header class="topbar">
      <div>
        <p class="eyebrow">NexusWMS</p>
        <h1>Vapor Monitor</h1>
      </div>

      <div class="status-pill">Operational Control Center</div>
    </header>

    <main class="content">
      <aside class="sidebar">
        <nav>
          <ul>
            <li>Dashboard</li>
            <li>Inventory</li>
            <li>Incidents</li>
            <li>Occupancy</li>
            <li>Purchasing</li>
          </ul>
        </nav>
      </aside>

      <section class="main-panel">
        <div class="card-grid">
          <article class="card">
            <p class="label">Live Inbound</p>
            <strong>0</strong>
          </article>

          <article class="card">
            <p class="label">Open Incidents</p>
            <strong>0</strong>
          </article>

          <article class="card">
            <p class="label">Blocked Stock</p>
            <strong>0</strong>
          </article>

          <article class="card">
            <p class="label">Occupancy</p>
            <strong>0%</strong>
          </article>
        </div>

        <article class="panel">
          <h2>Realtime Activity Feed</h2>
          <p>No events connected yet.</p>
        </article>
      </section>
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
  background: #0b1020;
  color: #e5e7eb;
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
  background: #111827;
  color: #cbd5e1;
  font-size: 14px;
}

.content {
  display: grid;
  grid-template-columns: 240px 1fr;
  gap: 24px;
}

.sidebar,
.panel,
.card {
  border: 1px solid #1f2937;
  background: #111827;
  border-radius: 16px;
}

.sidebar {
  padding: 18px;
}

.sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 12px;
}

.sidebar li {
  padding: 10px 12px;
  border-radius: 10px;
  background: #0f172a;
  color: #cbd5e1;
}

.main-panel {
  display: grid;
  gap: 24px;
}

.card-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
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
  font-size: 28px;
}

.panel {
  padding: 20px;
}

.panel h2 {
  margin-top: 0;
}

@media (max-width: 960px) {
  .content {
    grid-template-columns: 1fr;
  }

  .card-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .topbar {
    flex-direction: column;
    align-items: flex-start;
  }

  .card-grid {
    grid-template-columns: 1fr;
  }
}
</style>
