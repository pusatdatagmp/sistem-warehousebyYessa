
<script setup>
defineProps({
  activeView: String,
  sortedDirectoryEntries: Array,
  sortBy: Function,
  sortKey: String,
  sortDirection: String,
  openDirectory: Function,
  deleteDirectory: Function,
})
</script>

<template>
  <div class="page-heading">
    <div><p class="eyebrow">REFERENSI / {{ activeView.toUpperCase() }}</p><h1>{{ activeView }}</h1><p class="muted">Kelola data referensi untuk transaksi dan katalog.</p></div>
    <button class="primary" @click="openDirectory(activeView)">+ Tambah {{ activeView === 'Data Barang' ? 'barang' : activeView === 'Data Supplier' ? 'supplier' : 'pembeli' }}</button>
  </div>
  <div class="panel table-panel"><table><thead><tr><th @click="sortBy('name')">NAMA {{ sortKey === 'name' ? (sortDirection === 'asc' ? '▲' : '▼') : '' }}</th><th v-if="activeView !== 'Data Barang'" @click="sortBy('phone')">NO. HP</th><th v-if="activeView !== 'Data Barang'" @click="sortBy('address')">ALAMAT</th><th v-if="activeView === 'Data Barang'" @click="sortBy('unit')">SATUAN</th><th v-if="activeView === 'Data Barang'" @click="sortBy('type')">TIPE</th><th>AKSI</th></tr></thead><tbody><tr v-for="entry in sortedDirectoryEntries" :key="entry.id"><td><strong>{{ entry.name }}</strong></td><td v-if="activeView !== 'Data Barang'">{{ entry.phone || '-' }}</td><td v-if="activeView !== 'Data Barang'">{{ entry.address || '-' }}</td><td v-if="activeView === 'Data Barang'">{{ entry.unit }}</td><td v-if="activeView === 'Data Barang'"><span class="type-badge" :class="entry.type">{{ entry.type === 'basah' ? 'Basah' : 'Kering' }}</span></td><td><button class="print" @click="openDirectory(activeView, entry)">Edit</button> <button class="print" @click="deleteDirectory(entry)">Hapus</button></td></tr><tr v-if="!sortedDirectoryEntries.length"><td colspan="6" class="empty">Belum ada data {{ activeView.toLowerCase() }}.</td></tr></tbody></table></div>
</template>
