<script setup>
defineProps({
  money: { type: Function, required: true },
  totalOut: Number,
  totalIn: Number,
  profit: Number,
  products: Array,
  lowStock: Array,
  transactions: Array,
})
</script>

<template>
  <div class="page-heading">
    <div>
      <p class="eyebrow">RINGKASAN OPERASIONAL</p>
      <h1>Selamat pagi, Admin.</h1>
      <p class="muted">Pantau aktivitas warehouse dan performa koperasi hari ini.</p>
    </div>
    <button class="primary" @click="$emit('open-page', 'Barang Masuk')">+ Catat transaksi</button>
  </div>
  <div class="stats-grid">
    <div class="stat-card green"><span class="stat-label">TOTAL PEMASUKAN</span><strong>{{ money(totalOut) }}</strong><small></small><div class="stat-symbol">↗</div></div>
    <div class="stat-card orange"><span class="stat-label">TOTAL PENGELUARAN</span><strong>{{ money(totalIn) }}</strong><small></small><div class="stat-symbol">↘</div></div>
    <div class="stat-card blue"><span class="stat-label">KEUNTUNGAN</span><strong>{{ money(profit) }}</strong><small></small><div class="stat-symbol">◆</div></div>
    <div class="stat-card white"><span class="stat-label">ITEM AKTIF</span><strong>{{ products.length }}</strong><small>{{ lowStock.length }} perlu perhatian</small><div class="stat-symbol">□</div></div>
  </div>
  <div class="dashboard-grid">
    <div class="panel activity">
      <div class="panel-title"><div><h2>Aktivitas terbaru</h2><p class="muted">Transaksi yang baru saja tercatat</p></div><button class="link-button" @click="$emit('open-page', 'Log Transaksi')">Lihat semua →</button></div>
      <div v-for="transaction in transactions.slice(0, 4)" :key="transaction.id" class="activity-row"><div class="type-icon" :class="transaction.type.toLowerCase()">{{ transaction.type === 'IN' ? '↓' : '↑' }}</div><div class="activity-info"><strong>{{ transaction.product }}</strong><span>{{ transaction.party }} · {{ transaction.date }}</span></div><div class="activity-qty" :class="transaction.type.toLowerCase()">{{ transaction.type === 'IN' ? '+' : '-' }}{{ transaction.qty }}</div><strong class="activity-total">{{ money(transaction.total) }}</strong></div>
    </div>
    <div class="panel warning">
      <div class="panel-title"><div><h2>Perlu perhatian</h2><p class="muted">Stok menipis, segera restock</p></div><span class="warning-count">{{ lowStock.length }}</span></div>
      <div v-for="product in lowStock" :key="product.id" class="stock-row"><span class="product-dot"></span><div><strong>{{ product.name }}</strong><small>{{ product.unit }}</small></div><b>{{ product.stock }} <small>tersisa</small></b></div>
      <div v-if="!lowStock.length" class="empty">Semua stok aman</div>
    </div>
  </div>
</template>
