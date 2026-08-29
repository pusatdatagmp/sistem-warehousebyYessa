<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import api from './api'

const activeView = ref('Dashboard')
const query = ref('')
const toast = ref('')
const showReceipt = ref(false)
const receipt = ref(null)
const mobileOpen = ref(false)
const datePreset = ref('Bulan ini')
const apiMode = ref(false)
const typeFilter = ref('')
const directoryView = ref('')
const editingDirectory = ref(null)
const sortKey = ref('name')
const sortDirection = ref('asc')
const directoryForm = ref({ name: '', phone: '', address: '', unit: 'pcs', type: 'kering' })
const units = ['Pcs', 'Kg', 'Gram', 'Liter', 'Dus', 'Pack', 'Botol', 'Kaleng', 'Karung', 'Roll', 'Meter', 'Ikat', 'Pasang', 'Box', 'Slop']

const products = ref([])
const transactions = ref([])
const suppliers = ref([])
const customers = ref([])
const itemCatalogs = ref([])
const inForm = ref({ supplier: '', product: '', unit: 'pcs', price: 0, qty: 1 })
const outForm = ref({ customer: '', productId: '', price: 0, qty: 1 })

const nav = ['Dashboard', 'Data Barang', 'Data Suplier', 'Data Pembeli', 'Barang Masuk', 'Barang Keluar', 'Stok Barang', 'Log Transaksi']
const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value)
const totalIn = computed(() => transactions.value.filter(t => t.type === 'IN').reduce((sum, t) => sum + t.total, 0))
const totalOut = computed(() => transactions.value.filter(t => t.type === 'OUT').reduce((sum, t) => sum + t.total, 0))
const profit = computed(() => transactions.value.filter(t => t.type === 'OUT').reduce((sum, t) => { const p = products.value.find(item => item.name === t.product); return sum + (p ? (t.price - p.buy) * t.qty : 0) }, 0))
const lowStock = computed(() => products.value.filter(product => product.stock <= 5))
const filteredProducts = computed(() => products.value.filter(product => product.name.toLowerCase().includes(query.value.toLowerCase())))
const sortedProducts = computed(() => sortEntries(filteredProducts.value))
const directoryEntries = computed(() => activeView.value === 'Data Supplier' ? suppliers.value : activeView.value === 'Data Pembeli' ? customers.value : itemCatalogs.value)
const sortedDirectoryEntries = computed(() => sortEntries(directoryEntries.value))
const selectedProduct = computed(() => products.value.find(product => product.id === Number(outForm.value.productId)))
const activePrice = computed({ get: () => activeView.value === 'Barang Masuk' ? inForm.value.price : outForm.value.price, set: value => { if (activeView.value === 'Barang Masuk') inForm.value.price = value; else outForm.value.price = value } })
const activeQty = computed({ get: () => activeView.value === 'Barang Masuk' ? inForm.value.qty : outForm.value.qty, set: value => { if (activeView.value === 'Barang Masuk') inForm.value.qty = value; else outForm.value.qty = value } })
const incomingTransactions = computed(() => transactions.value.filter(transaction => transaction.type === 'IN'))
const outgoingTransactions = computed(() => transactions.value.filter(transaction => transaction.type === 'OUT'))

function notify(message) { toast.value = message; setTimeout(() => { toast.value = '' }, 2800) }
function openReceipt(transaction) { receipt.value = transaction; showReceipt.value = true }
function sortEntries(entries) { return [...entries].sort((left, right) => { const leftValue = left[sortKey.value] ?? ''; const rightValue = right[sortKey.value] ?? ''; const numeric = typeof leftValue === 'number' || typeof rightValue === 'number'; const a = numeric ? Number(leftValue) : String(leftValue).toLowerCase(); const b = numeric ? Number(rightValue) : String(rightValue).toLowerCase(); return (a < b ? -1 : a > b ? 1 : 0) * (sortDirection.value === 'asc' ? 1 : -1) }) }
function sortBy(key) { if (sortKey.value === key) sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'; else { sortKey.value = key; sortDirection.value = 'asc' } }
function openDirectory(view, entry = null) { directoryView.value = view === 'Data Suplier' ? 'Data Supplier' : view; editingDirectory.value = entry; directoryForm.value = entry ? { name: entry.name, phone: entry.phone || '', address: entry.address || '', unit: entry.unit || 'Pcs', type: entry.type || 'kering' } : { name: '', phone: '', address: '', unit: 'Pcs', type: 'kering' } }
function directoryContext() { return directoryView.value || (activeView.value === 'Data Supplier' ? 'Data Supplier' : activeView.value === 'Data Pembeli' ? 'Data Pembeli' : 'Data Barang') }
function directoryEndpoint() { const context = directoryContext(); return context === 'Data Supplier' ? '/suppliers' : context === 'Data Pembeli' ? '/customers' : '/item-catalogs' }
function directoryTitle() { const context = directoryContext(); return context === 'Data Supplier' ? 'Supplier' : context === 'Data Pembeli' ? 'Pembeli' : 'Data barang' }
async function deleteDirectory(entry) { if (!apiMode.value || !window.confirm(`Hapus ${entry.name}?`)) return; try { await api.delete(`${directoryEndpoint()}/${entry.id}`); await loadFromApi(); notify(`${directoryTitle()} berhasil dihapus`) } catch (error) { notify(error.response?.data?.message || 'Gagal menghapus data') } }
function selectIncomingCatalog() { const catalog = itemCatalogs.value.find(item => item.id === Number(inForm.value.catalogId)); if (catalog) { inForm.value.product = catalog.name; inForm.value.unit = catalog.unit } }
function mapProduct(product) { return { id: product.id, name: product.name, unit: product.unit, totalMasuk: Number(product.total_masuk), totalKeluar: Number(product.total_keluar), sisa: Number(product.sisa), buy: Number(product.avg_buy_price), buyTotal: Number(product.harga_beli_keseluruhan), sell: Number(product.avg_sell_price), sellTotal: Number(product.harga_jual_keseluruhan), profit: Number(product.laba_keuntungan), type: product.type, stock: Number(product.stock), status: product.status } }
function mapTransaction(transaction) { return { id: transaction.id, type: transaction.type, product: transaction.product?.name || 'Barang', qty: Number(transaction.qty), price: Number(transaction.type === 'IN' ? transaction.buy_price : transaction.sell_price), total: Number(transaction.total_price), party: transaction.type === 'IN' ? transaction.supplier_name : transaction.customer_name, date: new Date(transaction.created_at).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) } }
async function loadFromApi() {
  try {
    if (!localStorage.getItem('bms_token')) {
      const login = await api.post('/login', { email: import.meta.env.VITE_ADMIN_EMAIL || 'admin@bms-koperasi.test', password: import.meta.env.VITE_ADMIN_PASSWORD || 'password' })
      localStorage.setItem('bms_token', login.data.token)
    }
    const [productsResponse, transactionsResponse, suppliersResponse, customersResponse, catalogsResponse] = await Promise.all([api.get('/products', { params: { search: query.value, type: typeFilter.value || undefined } }), api.get('/warehouse/transactions'), api.get('/suppliers'), api.get('/customers'), api.get('/item-catalogs')])
    products.value = (productsResponse.data.data || []).map(mapProduct)
    transactions.value = (transactionsResponse.data.data?.data || []).map(mapTransaction)
    suppliers.value = suppliersResponse.data.data || suppliersResponse.data || []
    customers.value = customersResponse.data.data || customersResponse.data || []
    itemCatalogs.value = catalogsResponse.data.data || catalogsResponse.data || []
    apiMode.value = true
  } catch (error) {
    apiMode.value = false
    notify(error.response?.data?.message || 'API belum tersedia, menggunakan data lokal')
  }
}
async function refreshProducts() {
  if (!apiMode.value) return
  try {
    const response = await api.get('/products', { params: { search: query.value, type: typeFilter.value || undefined } })
    products.value = (response.data.data || []).map(mapProduct)
  } catch (error) { notify(error.response?.data?.message || 'Gagal memuat master barang') }
}
async function submitDirectory() {
  const form = directoryForm.value
  const title = directoryTitle()
  const isEditing = Boolean(editingDirectory.value)
  if (!form.name || (directoryView.value === 'Data Barang' && !form.unit)) return notify(`Lengkapi data ${directoryTitle().toLowerCase()}`)
  if (!apiMode.value) return notify('API belum tersedia')
  try {
    const payload = directoryView.value === 'Data Barang' ? { name: form.name, unit: form.unit, type: form.type } : { name: form.name, phone: form.phone, address: form.address }
    if (editingDirectory.value) await api.put(`${directoryEndpoint()}/${editingDirectory.value.id}`, payload)
    else await api.post(directoryEndpoint(), payload)
    await loadFromApi(); directoryView.value = ''; editingDirectory.value = null; notify(`${title} berhasil ${isEditing ? 'diperbarui' : 'ditambahkan'}`)
  } catch (error) { notify(error.response?.data?.message || `Gagal menambahkan ${title.toLowerCase()}`) }
}
async function submitIn() {
  if (!inForm.value.supplier || !inForm.value.product || !inForm.value.price || inForm.value.qty < 1) return notify('Lengkapi data barang masuk')
  if (apiMode.value) {
    try {
      await api.post('/warehouse/transactions/in', { supplier_name: inForm.value.supplier, product_name: inForm.value.product, unit: inForm.value.unit, buy_price: Number(inForm.value.price), qty: Number(inForm.value.qty) })
      await loadFromApi(); notify('Barang masuk berhasil dicatat'); inForm.value = { supplier: '', product: '', unit: 'pcs', price: 0, qty: 1 }; return
    } catch (error) { notify(error.response?.data?.message || 'Gagal menyimpan barang masuk'); return }
  }
  let product = products.value.find(item => item.name.toLowerCase() === inForm.value.product.toLowerCase())
  if (product) product.stock += Number(inForm.value.qty)
  else { product = { id: Date.now(), name: inForm.value.product, unit: inForm.value.unit, stock: Number(inForm.value.qty), buy: Number(inForm.value.price), sell: 0 }; products.value.unshift(product) }
  transactions.value.unshift({ id: Date.now(), type: 'IN', product: product.name, qty: Number(inForm.value.qty), price: Number(inForm.value.price), total: Number(inForm.value.price) * Number(inForm.value.qty), party: inForm.value.supplier, date: '22 Agu 2026' })
  notify('Barang masuk berhasil dicatat'); inForm.value = { supplier: '', product: '', unit: 'pcs', price: 0, qty: 1 }
}
async function submitOut() {
  const product = selectedProduct.value
  if (!outForm.value.customer || !product || !outForm.value.qty) return notify('Lengkapi data penjualan')
  if (Number(outForm.value.qty) > product.stock) return notify('Stok tidak cukup')
  if (apiMode.value) {
    try {
      await api.post('/warehouse/transactions/out', { customer_name: outForm.value.customer, product_id: product.id, sell_price: Number(outForm.value.price) || product.sell, qty: Number(outForm.value.qty) })
      await loadFromApi(); notify('Barang keluar berhasil dicatat'); outForm.value = { customer: '', productId: '', price: 0, qty: 1 }; return
    } catch (error) { notify(error.response?.data?.message || 'Gagal menyimpan barang keluar'); return }
  }
  product.stock -= Number(outForm.value.qty)
  const price = Number(outForm.value.price) || product.sell
  transactions.value.unshift({ id: Date.now(), type: 'OUT', product: product.name, qty: Number(outForm.value.qty), price, total: price * Number(outForm.value.qty), party: outForm.value.customer, date: '22 Agu 2026' })
  notify('Barang keluar berhasil dicatat'); outForm.value = { customer: '', productId: '', price: 0, qty: 1 }
}
function selectProduct() { if (selectedProduct.value) outForm.value.price = selectedProduct.value.sell }
onMounted(loadFromApi)
watch(activeView, value => { if (value === 'Data Suplier') activeView.value = 'Data Supplier' })
watch([query, typeFilter], refreshProducts)
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar" :class="{ open: mobileOpen }">
      <div class="brand"><div class="brand-mark">B</div><div><strong>bms<span>-koperasi</span></strong><small>WAREHOUSE SYSTEM</small></div></div>
      <div class="sidebar-label">MAIN MENU</div>
      <nav><button v-for="item in nav" :key="item" :class="{ active: activeView === item || (item === 'Data Suplier' && activeView === 'Data Supplier') }" @click="activeView = item; mobileOpen = false"><span class="nav-icon">{{ ['D','DB','S','P','IN','OUT','M','L'][nav.indexOf(item)] }}</span>{{ item }}<b v-if="item === 'Stok Barang' && lowStock.length">{{ lowStock.length }}</b></button></nav>
      <div class="sidebar-foot"><div class="help-icon">?</div><div><strong>Butuh bantuan?</strong><small>Hubungi administrator</small></div></div>
    </aside>
    <main class="main-content">
      <header class="topbar"><button class="mobile-menu" @click="mobileOpen = !mobileOpen">MENU</button><div class="crumb">Workspace <span>/</span> <strong>{{ activeView }}</strong></div><div class="top-actions"><button class="bell"><i></i></button><div class="avatar">AD</div><div class="admin-name"><strong>Admin Demo</strong><small>Administrator</small></div><button class="logout">Keluar</button></div></header>
      <section class="page-content">
        <template v-if="activeView === 'Dashboard'">
          <div class="page-heading"><div><p class="eyebrow">RINGKASAN OPERASIONAL</p><h1>Selamat pagi, Admin.</h1><p class="muted">Pantau aktivitas warehouse dan performa koperasi hari ini.</p></div><button class="primary" @click="activeView = 'Barang Masuk'">+ Catat transaksi</button></div>
          <div class="stats-grid"><div class="stat-card green"><span class="stat-label">TOTAL PEMASUKAN</span><strong>{{ money(totalOut) }}</strong><small></small><div class="stat-symbol">↗</div></div><div class="stat-card orange"><span class="stat-label">TOTAL PENGELUARAN</span><strong>{{ money(totalIn) }}</strong><small></small><div class="stat-symbol">↘</div></div><div class="stat-card blue"><span class="stat-label">KEUNTUNGAN</span><strong>{{ money(profit) }}</strong><small></small><div class="stat-symbol">◆</div></div><div class="stat-card white"><span class="stat-label">ITEM AKTIF</span><strong>{{ products.length }}</strong><small>{{ lowStock.length }} perlu perhatian</small><div class="stat-symbol">□</div></div></div>
          <div class="dashboard-grid"><div class="panel activity"><div class="panel-title"><div><h2>Aktivitas terbaru</h2><p class="muted">Transaksi yang baru saja tercatat</p></div><button class="link-button" @click="activeView = 'Log Transaksi'">Lihat semua →</button></div><div v-for="transaction in transactions.slice(0, 4)" :key="transaction.id" class="activity-row"><div class="type-icon" :class="transaction.type.toLowerCase()">{{ transaction.type === 'IN' ? '↓' : '↑' }}</div><div class="activity-info"><strong>{{ transaction.product }}</strong><span>{{ transaction.party }} · {{ transaction.date }}</span></div><div class="activity-qty" :class="transaction.type.toLowerCase()">{{ transaction.type === 'IN' ? '+' : '-' }}{{ transaction.qty }}</div><strong class="activity-total">{{ money(transaction.total) }}</strong></div></div><div class="panel warning"><div class="panel-title"><div><h2>Perlu perhatian</h2><p class="muted">Stok menipis, segera restock</p></div><span class="warning-count">{{ lowStock.length }}</span></div><div v-for="product in lowStock" :key="product.id" class="stock-row"><span class="product-dot"></span><div><strong>{{ product.name }}</strong><small>{{ product.unit }}</small></div><b>{{ product.stock }} <small>tersisa</small></b></div><div v-if="!lowStock.length" class="empty">Semua stok aman</div></div></div>
        </template>
        <template v-else-if="activeView === 'Barang Masuk' || activeView === 'Barang Keluar'">
          <div class="page-heading"><div><p class="eyebrow">TRANSAKSI / {{ activeView.toUpperCase() }}</p><h1>{{ activeView }}</h1><p class="muted">Catat pergerakan inventaris dengan detail yang akurat.</p></div></div>
          <div class="form-panel panel"><div class="panel-title"><div><h2>{{ activeView === 'Barang Masuk' ? 'Form barang masuk' : 'Form barang keluar' }}</h2><p class="muted">Field bertanda * wajib diisi</p></div><span class="form-badge">{{ activeView === 'Barang Masuk' ? 'PEMBELIAN' : 'PENJUALAN' }}</span></div><form @submit.prevent="activeView === 'Barang Masuk' ? submitIn() : submitOut()"><label v-if="activeView === 'Barang Masuk'">Nama supplier *<select v-model="inForm.supplier"><option value="">Pilih supplier</option><option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.name">{{ supplier.name }}</option></select></label><label v-else>Nama pembeli *<select v-model="outForm.customer"><option value="">Pilih pembeli</option><option v-for="customer in customers" :key="customer.id" :value="customer.name">{{ customer.name }}</option></select></label><label>Nama barang *<select v-if="activeView === 'Barang Keluar'" v-model="outForm.productId" @change="selectProduct"><option value="">Pilih barang dari master</option><option v-for="product in products" :value="product.id" :key="product.id">{{ product.name }} ({{ product.stock }} {{ product.unit }})</option></select><select v-else v-model="inForm.catalogId" @change="selectIncomingCatalog"><option value="">Pilih dari daftar barang</option><option v-for="catalog in itemCatalogs" :value="catalog.id" :key="catalog.id">{{ catalog.name }}</option></select></label><label v-if="activeView === 'Barang Masuk'">Satuan<input :value="inForm.unit || '-'" disabled></label><label v-else>Satuan<input :value="selectedProduct?.unit || '-'" disabled></label><label>Harga satuan *<input v-model="activePrice" type="number" min="0" placeholder="0"></label><label>Jumlah (qty) *<input v-model="activeQty" type="number" min="0.001" step="0.001" placeholder="0"></label><div class="total-box"><span>Total harga</span><strong>{{ money(Number(activePrice) * Number(activeQty)) }}</strong></div><p v-if="activeView === 'Barang Keluar' && selectedProduct && Number(outForm.qty) > selectedProduct.stock" class="inline-error">Stok tidak cukup. Tersedia {{ selectedProduct.stock }} {{ selectedProduct.unit }}.</p><button class="primary submit" type="submit">Simpan transaksi <span>→</span></button></form></div>
          <div class="transaction-list panel"><div class="panel-title"><div><h2>{{ activeView === 'Barang Masuk' ? 'Daftar barang masuk' : 'Daftar barang keluar' }}</h2><p class="muted">{{ activeView === 'Barang Masuk' ? 'Riwayat pembelian dan penerimaan stok' : 'Riwayat penjualan dan pengeluaran stok' }}</p></div><span class="list-count">{{ activeView === 'Barang Masuk' ? incomingTransactions.length : outgoingTransactions.length }} transaksi</span></div><div class="transaction-table-wrap"><table class="transaction-table"><thead><tr><th>TANGGAL</th><th>{{ activeView === 'Barang Masuk' ? 'SUPPLIER' : 'PEMBELI' }}</th><th>NAMA BARANG</th><th>QTY</th><th>TOTAL HARGA</th><th></th></tr></thead><tbody><tr v-for="transaction in (activeView === 'Barang Masuk' ? incomingTransactions : outgoingTransactions)" :key="transaction.id"><td>{{ transaction.date }}</td><td><strong>{{ transaction.party }}</strong></td><td>{{ transaction.product }}</td><td>{{ transaction.qty }}</td><td><strong>{{ money(transaction.total) }}</strong></td><td><button class="print" @click="openReceipt(transaction)">Cetak Nota</button></td></tr><tr v-if="!(activeView === 'Barang Masuk' ? incomingTransactions : outgoingTransactions).length"><td colspan="6" class="empty">Belum ada transaksi {{ activeView.toLowerCase() }}.</td></tr></tbody></table></div></div>
        </template>
        <template v-else-if="activeView === 'Stok Barang'"><div class="page-heading"><div><p class="eyebrow">INVENTARIS / STOK</p><h1>Stok barang</h1><p class="muted">Pantau rekapitulasi stok dan nilai persediaan.</p></div></div><div class="panel table-panel"><div class="master-toolbar"><div class="search">⌕ <input v-model="query" placeholder="Cari nama barang..."></div><div class="type-tabs"><button :class="{ selected: typeFilter === '' }" @click="typeFilter = ''">Semua</button><button :class="{ selected: typeFilter === 'basah' }" @click="typeFilter = 'basah'">Barang basah</button><button :class="{ selected: typeFilter === 'kering' }" @click="typeFilter = 'kering'">Barang kering</button></div></div><div class="master-table-wrap"><table><thead><tr><th v-for="column in [{ key: 'name', label: 'NAMA BARANG' }, { key: 'totalMasuk', label: 'MASUK' }, { key: 'totalKeluar', label: 'KELUAR' }, { key: 'sisa', label: 'SISA' }, { key: 'buy', label: 'HARGA SATUAN BELI' }, { key: 'buyTotal', label: 'HARGA BELI (KESELURUHAN)' }, { key: 'sell', label: 'HARGA SATUAN JUAL' }, { key: 'sellTotal', label: 'HARGA JUAL (KESELURUHAN)' }, { key: 'profit', label: 'LABA / KEUNTUNGAN' }]" :key="column.key" @click="sortBy(column.key)">{{ column.label }} {{ sortKey === column.key ? (sortDirection === 'asc' ? '▲' : '▼') : '' }}</th></tr></thead><tbody><tr v-for="product in sortedProducts" :key="product.id"><td><strong>{{ product.name }}</strong><small>{{ product.unit }}</small></td><td>{{ product.totalMasuk }} {{ product.unit }}</td><td>{{ product.totalKeluar }} {{ product.unit }}</td><td><strong>{{ product.sisa }}</strong> {{ product.unit }}</td><td>{{ money(product.buy) }}</td><td>{{ money(product.buyTotal) }}</td><td>{{ money(product.sell) }}</td><td>{{ money(product.sellTotal) }}</td><td class="profit-cell">{{ money(product.profit) }}</td></tr><tr v-if="!sortedProducts.length"><td colspan="9" class="empty">Belum ada data stok barang.</td></tr></tbody></table></div></div></template>
        <template v-else-if="['Data Supplier', 'Data Pembeli', 'Data Barang'].includes(activeView)"><div class="page-heading"><div><p class="eyebrow">REFERENSI / {{ activeView.toUpperCase() }}</p><h1>{{ activeView }}</h1><p class="muted">Kelola data referensi untuk transaksi dan katalog.</p></div><button class="primary" @click="openDirectory(activeView)">+ Tambah {{ activeView === 'Data Barang' ? 'barang' : activeView === 'Data Supplier' ? 'supplier' : 'pembeli' }}</button></div><div class="panel table-panel"><table><thead><tr><th @click="sortBy('name')">NAMA {{ sortKey === 'name' ? (sortDirection === 'asc' ? '▲' : '▼') : '' }}</th><th v-if="activeView !== 'Data Barang'" @click="sortBy('phone')">NO. HP</th><th v-if="activeView !== 'Data Barang'" @click="sortBy('address')">ALAMAT</th><th v-if="activeView === 'Data Barang'" @click="sortBy('unit')">SATUAN</th><th v-if="activeView === 'Data Barang'" @click="sortBy('type')">TIPE</th><th>AKSI</th></tr></thead><tbody><tr v-for="entry in sortedDirectoryEntries" :key="entry.id"><td><strong>{{ entry.name }}</strong></td><td v-if="activeView !== 'Data Barang'">{{ entry.phone || '-' }}</td><td v-if="activeView !== 'Data Barang'">{{ entry.address || '-' }}</td><td v-if="activeView === 'Data Barang'">{{ entry.unit }}</td><td v-if="activeView === 'Data Barang'"><span class="type-badge" :class="entry.type">{{ entry.type === 'basah' ? 'Basah' : 'Kering' }}</span></td><td><button class="print" @click="openDirectory(activeView, entry)">Edit</button> <button class="print" @click="deleteDirectory(entry)">Hapus</button></td></tr><tr v-if="!sortedDirectoryEntries.length"><td colspan="6" class="empty">Belum ada data {{ activeView.toLowerCase() }}.</td></tr></tbody></table></div></template>
        <template v-else><div class="page-heading"><div><p class="eyebrow">AUDIT / AKTIVITAS</p><h1>Log transaksi</h1><p class="muted">Riwayat seluruh pergerakan barang di warehouse.</p></div><div class="export-actions"><button class="secondary">↓ PDF</button><button class="primary">↓ Excel</button></div></div><div class="filter-bar panel"><button v-for="preset in ['Hari ini', 'Bulan ini', 'Semua']" :key="preset" :class="{ selected: datePreset === preset }" @click="datePreset = preset">{{ preset }}</button><span class="date-line">22/08/2026 — 22/08/2026</span></div><div class="summary-strip"><div><span>Pemasukan</span><strong class="green-text">{{ money(totalOut) }}</strong></div><div><span>Pengeluaran</span><strong class="orange-text">{{ money(totalIn) }}</strong></div><div><span>Keuntungan</span><strong class="blue-text">{{ money(profit) }}</strong></div></div><div class="panel table-panel"><table><thead><tr><th>WAKTU</th><th>BARANG</th><th>TIPE</th><th>QTY</th><th>HARGA</th><th>TOTAL</th><th>PIHAK TERKAIT</th><th></th></tr></thead><tbody><tr v-for="transaction in transactions" :key="transaction.id"><td>{{ transaction.date }}</td><td><strong>{{ transaction.product }}</strong></td><td><span class="type-pill" :class="transaction.type.toLowerCase()">{{ transaction.type === 'IN' ? 'Masuk' : 'Keluar' }}</span></td><td>{{ transaction.qty }}</td><td>{{ money(transaction.price) }}</td><td><strong>{{ money(transaction.total) }}</strong></td><td>{{ transaction.party }}</td><td><button class="print" @click="openReceipt(transaction)">Cetak</button></td></tr></tbody></table></div></template>
      </section>
    </main>
    <div v-if="toast" class="toast">{{ toast }}</div>
    <div v-if="directoryView" class="modal-backdrop" @click.self="directoryView = ''; editingDirectory = null"><div class="quick-add-modal"><button class="close" @click="directoryView = ''; editingDirectory = null">×</button><p class="eyebrow">REFERENSI</p><h2>{{ editingDirectory ? 'Edit' : 'Tambah' }} {{ directoryTitle() }}</h2><p class="muted">Simpan data untuk digunakan pada transaksi.</p><form @submit.prevent="submitDirectory"><label>Nama *<input v-model="directoryForm.name" :placeholder="`Nama ${directoryTitle().toLowerCase()}`"></label><template v-if="directoryView !== 'Data Barang'"><label>No. HP<input v-model="directoryForm.phone" placeholder="08xxxxxxxxxx"></label><label>Alamat<textarea v-model="directoryForm.address" rows="3" placeholder="Alamat lengkap"></textarea></label></template><template v-else><label>Satuan *<select v-model="directoryForm.unit"><option v-for="unit in units" :key="unit" :value="unit">{{ unit }}</option></select></label><label>Tipe *<select v-model="directoryForm.type"><option value="basah">Basah</option><option value="kering">Kering</option></select></label></template><div class="quick-add-actions"><button class="secondary" type="button" @click="directoryView = ''; editingDirectory = null">Batal</button><button class="primary" type="submit">Simpan</button></div></form></div></div>
    <div v-if="showReceipt" class="modal-backdrop" @click.self="showReceipt = false"><div class="receipt"><button class="close" @click="showReceipt = false">×</button><p class="eyebrow">BMS-KOPERASI</p><h2>{{ receipt.type === 'IN' ? 'Nota Pembelian' : 'Kwitansi Penjualan' }}</h2><p class="muted">22 Agustus 2026 · #TRX-{{ receipt.id }}</p><hr><div class="receipt-line"><span>{{ receipt.type === 'IN' ? 'Supplier' : 'Pembeli' }}</span><strong>{{ receipt.party }}</strong></div><div class="receipt-line"><span>{{ receipt.product }} × {{ receipt.qty }}</span><strong>{{ money(receipt.total) }}</strong></div><button class="primary submit" @click="showReceipt = false">Tutup nota</button></div></div>
  </div>
</template>
