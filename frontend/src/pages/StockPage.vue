<script setup>
defineProps({
  query: String,
  typeFilter: String,
  products: Array,
  sortedProducts: Array,
  money: { type: Function, required: true },
  sortBy: Function,
  sortKey: String,
  sortDirection: String,
})
const emit = defineEmits(['update:query', 'update:typeFilter'])
</script>

<template>
  <div class="page-heading">
    <div><p class="eyebrow">INVENTARIS / STOK</p><h1>Stok barang</h1><p class="muted">Pantau rekapitulasi stok dan nilai persediaan.</p></div>
  </div>
  <div class="panel table-panel"><div class="master-toolbar"><div class="search">⌕ <input :value="query" @input="emit('update:query', $event.target.value)" placeholder="Cari nama barang..."></div><div class="type-tabs"><button :class="{ selected: typeFilter === '' }" @click="emit('update:typeFilter', '')">Semua</button><button :class="{ selected: typeFilter === 'basah' }" @click="emit('update:typeFilter', 'basah')">Barang basah</button><button :class="{ selected: typeFilter === 'kering' }" @click="emit('update:typeFilter', 'kering')">Barang kering</button></div></div>
    <div class="master-table-wrap"><table><thead><tr><th v-for="column in [{ key: 'name', label: 'NAMA BARANG' }, { key: 'totalMasuk', label: 'MASUK' }, { key: 'totalKeluar', label: 'KELUAR' }, { key: 'sisa', label: 'SISA' }, { key: 'buy', label: 'HARGA SATUAN BELI' }, { key: 'buyTotal', label: 'HARGA BELI (KESELURUHAN)' }, { key: 'sell', label: 'HARGA SATUAN JUAL' }, { key: 'sellTotal', label: 'HARGA JUAL (KESELURUHAN)' }, { key: 'profit', label: 'LABA / KEUNTUNGAN' }]" :key="column.key" @click="sortBy(column.key)">{{ column.label }} {{ sortKey === column.key ? (sortDirection === 'asc' ? '▲' : '▼') : '' }}</th></tr></thead><tbody><tr v-for="product in sortedProducts" :key="product.id"><td><strong>{{ product.name }}</strong><small>{{ product.unit }}</small></td><td>{{ product.totalMasuk }} {{ product.unit }}</td><td>{{ product.totalKeluar }} {{ product.unit }}</td><td><strong>{{ product.sisa }}</strong> {{ product.unit }}</td><td>{{ money(product.buy) }}</td><td>{{ money(product.buyTotal) }}</td><td>{{ money(product.sell) }}</td><td>{{ money(product.sellTotal) }}</td><td class="profit-cell">{{ money(product.profit) }}</td></tr><tr v-if="!sortedProducts.length"><td colspan="9" class="empty">Belum ada data stok barang.</td></tr></tbody></table></div>
  </div>
</template>
