<script setup>
defineProps({
  transactions: Array,
  money: { type: Function, required: true },
  totalOut: Number,
  totalIn: Number,
  profit: Number,
  datePreset: String,
})
const emit = defineEmits(['update:datePreset', 'open-receipt'])
</script>

<template>
  <div class="page-heading">
    <div><p class="eyebrow">AUDIT / AKTIVITAS</p><h1>Log transaksi</h1><p class="muted">Riwayat seluruh pergerakan barang di warehouse.</p></div>
    <div class="export-actions"><button class="secondary">↓ PDF</button><button class="primary">↓ Excel</button></div>
  </div>
  <div class="filter-bar panel"><button v-for="preset in ['Hari ini', 'Bulan ini', 'Semua']" :key="preset" :class="{ selected: datePreset === preset }" @click="emit('update:datePreset', preset)">{{ preset }}</button><span class="date-line">22/08/2026 — 22/08/2026</span></div>
  <div class="summary-strip"><div><span>Pemasukan</span><strong class="green-text">{{ money(totalOut) }}</strong></div><div><span>Pengeluaran</span><strong class="orange-text">{{ money(totalIn) }}</strong></div><div><span>Keuntungan</span><strong class="blue-text">{{ money(profit) }}</strong></div></div>
  <div class="panel table-panel"><table><thead><tr><th>WAKTU</th><th>BARANG</th><th>TIPE</th><th>QTY</th><th>HARGA</th><th>TOTAL</th><th>PIHAK TERKAIT</th><th></th></tr></thead><tbody><tr v-for="transaction in transactions" :key="transaction.id"><td>{{ transaction.date }}</td><td><strong>{{ transaction.product }}</strong></td><td><span class="type-pill" :class="transaction.type.toLowerCase()">{{ transaction.type === 'IN' ? 'Masuk' : 'Keluar' }}</span></td><td>{{ transaction.qty }}</td><td>{{ money(transaction.price) }}</td><td><strong>{{ money(transaction.total) }}</strong></td><td>{{ transaction.party }}</td><td><button class="print" @click="emit('open-receipt', transaction)">Cetak</button></td></tr></tbody></table></div>
</template>
