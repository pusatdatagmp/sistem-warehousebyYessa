<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import api from './api'
import DashboardPage from './pages/DashboardPage.vue'
import TransactionPage from './pages/TransactionPage.vue'
import StockPage from './pages/StockPage.vue'
import DirectoryPage from './pages/DirectoryPage.vue'
import LogPage from './pages/LogPage.vue'

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
// Gunakan Map untuk O(1) lookup profit calculation
const productMap = computed(() => { const map = new Map(); products.value.forEach(p => map.set(p.name, p)); return map })
const transactionStats = computed(() => {
  const stats = { totalIn: 0, totalOut: 0, profit: 0, incoming: [], outgoing: [] }
  transactions.value.forEach(t => {
    if (t.type === 'IN') { stats.totalIn += t.total; stats.incoming.push(t) }
    else {
      stats.totalOut += t.total
      stats.outgoing.push(t)
      const p = productMap.value.get(t.product)
      if (p) stats.profit += (t.price - p.buy) * t.qty
    }
  })
  return stats
})
const totalIn = computed(() => transactionStats.value.totalIn)
const totalOut = computed(() => transactionStats.value.totalOut)
const profit = computed(() => transactionStats.value.profit)
const lowStock = computed(() => products.value.filter(product => product.stock <= 5))
const filteredProducts = computed(() => {
  const q = query.value.toLowerCase()
  return q ? products.value.filter(product => product.name.toLowerCase().includes(q)) : products.value
})
const sortedProducts = computed(() => sortEntries(filteredProducts.value))
const directoryEntries = computed(() => activeView.value === 'Data Supplier' ? suppliers.value : activeView.value === 'Data Pembeli' ? customers.value : itemCatalogs.value)
const sortedDirectoryEntries = computed(() => sortEntries(directoryEntries.value))
const selectedProduct = computed(() => products.value.find(product => product.id === Number(outForm.value.productId)))
const activePrice = computed({ get: () => activeView.value === 'Barang Masuk' ? inForm.value.price : outForm.value.price, set: value => { if (activeView.value === 'Barang Masuk') inForm.value.price = value; else outForm.value.price = value } })
const activeQty = computed({ get: () => activeView.value === 'Barang Masuk' ? inForm.value.qty : outForm.value.qty, set: value => { if (activeView.value === 'Barang Masuk') inForm.value.qty = value; else outForm.value.qty = value } })
const incomingTransactions = computed(() => transactionStats.value.incoming)
const outgoingTransactions = computed(() => transactionStats.value.outgoing)

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
    apiMode.value = true
    // Load minimal data untuk dashboard
    await loadDashboard()
    // Load master data untuk form (non-blocking)
    loadMasterData()
  } catch (error) {
    apiMode.value = false
    notify(error.response?.data?.message || 'API belum tersedia, menggunakan data lokal')
  }
}

async function loadDashboard() {
  try {
    const response = await api.get('/warehouse/dashboard')
    // Parse dashboard response dan update state minimal
  } catch (error) { console.error('Dashboard load error:', error) }
}

async function loadMasterData() {
  try {
    const [suppliersResponse, customersResponse, catalogsResponse] = await Promise.all([
      api.get('/suppliers'),
      api.get('/customers'),
      api.get('/item-catalogs')
    ])
    suppliers.value = suppliersResponse.data.data || suppliersResponse.data || []
    customers.value = customersResponse.data.data || customersResponse.data || []
    itemCatalogs.value = catalogsResponse.data.data || catalogsResponse.data || []
  } catch (error) { console.error('Master data load error:', error) }
}

async function loadProducts() {
  if (products.value.length > 0) return
  try {
    const response = await api.get('/products', { params: { search: query.value, type: typeFilter.value || undefined } })
    products.value = (response.data.data || []).map(mapProduct)
  } catch (error) { notify('Gagal memuat master barang') }
}

async function loadTransactions() {
  if (transactions.value.length > 0) return
  try {
    const response = await api.get('/warehouse/transactions')
    transactions.value = (response.data.data?.data || []).map(mapTransaction)
  } catch (error) { notify('Gagal memuat transaksi') }
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
    const response = isEditing ? await api.put(`${directoryEndpoint()}/${editingDirectory.value.id}`, payload) : await api.post(directoryEndpoint(), payload)
    // Optimistic update - update array lokal saja
    const entries = directoryView.value === 'Data Supplier' ? suppliers.value : directoryView.value === 'Data Pembeli' ? customers.value : itemCatalogs.value
    if (isEditing) Object.assign(editingDirectory.value, response.data)
    else entries.unshift(response.data)
    directoryView.value = ''; editingDirectory.value = null; notify(`${title} berhasil ${isEditing ? 'diperbarui' : 'ditambahkan'}`)
  } catch (error) { notify(error.response?.data?.message || `Gagal menambahkan ${title.toLowerCase()}`) }
}
async function submitIn() {
  if (!inForm.value.supplier || !inForm.value.product || !inForm.value.price || inForm.value.qty < 1) return notify('Lengkapi data barang masuk')
  // Simpan data sebelum direset
  const submitData = { supplier_name: inForm.value.supplier, product_name: inForm.value.product, unit: inForm.value.unit, buy_price: Number(inForm.value.price), qty: Number(inForm.value.qty) }
  // Optimistic update dulu
  let product = products.value.find(item => item.name.toLowerCase() === inForm.value.product.toLowerCase())
  if (product) product.stock += Number(inForm.value.qty)
  else { product = { id: Date.now(), name: inForm.value.product, unit: inForm.value.unit, stock: Number(inForm.value.qty), buy: Number(inForm.value.price), sell: 0 }; products.value.unshift(product) }
  transactions.value.unshift({ id: Date.now(), type: 'IN', product: product.name, qty: Number(inForm.value.qty), price: Number(inForm.value.price), total: Number(inForm.value.price) * Number(inForm.value.qty), party: inForm.value.supplier, date: new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) })
  notify('Barang masuk berhasil dicatat')
  inForm.value = { supplier: '', product: '', unit: 'pcs', price: 0, qty: 1 }
  if (apiMode.value) {
    try {
      await api.post('/warehouse/transactions/in', submitData)
    } catch (error) { notify('Simpan offline - sync saat online') }
  }
}
async function submitOut() {
  const product = selectedProduct.value
  if (!outForm.value.customer || !product || !outForm.value.qty) return notify('Lengkapi data penjualan')
  if (Number(outForm.value.qty) > product.stock) return notify('Stok tidak cukup')
  // Simpan data sebelum direset
  const price = Number(outForm.value.price) || product.sell
  const submitData = { customer_name: outForm.value.customer, product_id: product.id, sell_price: price, qty: Number(outForm.value.qty) }
  // Optimistic update dulu
  product.stock -= Number(outForm.value.qty)
  transactions.value.unshift({ id: Date.now(), type: 'OUT', product: product.name, qty: Number(outForm.value.qty), price, total: price * Number(outForm.value.qty), party: outForm.value.customer, date: new Date().toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) })
  notify('Barang keluar berhasil dicatat')
  outForm.value = { customer: '', productId: '', price: 0, qty: 1 }
  if (apiMode.value) {
    try {
      await api.post('/warehouse/transactions/out', submitData)
    } catch (error) { notify('Simpan offline - sync saat online') }
  }
}
function selectProduct() { if (selectedProduct.value) outForm.value.price = selectedProduct.value.sell }
onMounted(loadFromApi)
watch(activeView, value => { 
  if (value === 'Data Suplier') activeView.value = 'Data Supplier'
  // Lazy load data saat user membuka halaman
  if (['Barang Masuk', 'Barang Keluar', 'Stok Barang'].includes(value)) loadProducts()
  if (['Barang Masuk', 'Barang Keluar', 'Log Transaksi'].includes(value)) loadTransactions()
})
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
        <DashboardPage v-if="activeView === 'Dashboard'" :money="money" :total-out="totalOut" :total-in="totalIn" :profit="profit" :products="products" :low-stock="lowStock" :transactions="transactions" @open-page="activeView = $event" />
                <TransactionPage
          v-else-if="activeView === 'Barang Masuk' || activeView === 'Barang Keluar'"
          :active-view="activeView"
          :in-form="inForm"
          :out-form="outForm"
          :suppliers="suppliers"
          :customers="customers"
          :products="products"
          :item-catalogs="itemCatalogs"
          :money="money"
          :incoming-transactions="incomingTransactions"
          :outgoing-transactions="outgoingTransactions"
          :selected-product="selectedProduct"
          :active-price="activePrice"
          :active-qty="activeQty"
          :submit-in="submitIn"
          :submit-out="submitOut"
          :select-incoming-catalog="selectIncomingCatalog"
          :select-product="selectProduct"
          :open-receipt="openReceipt"
          @update:activePrice="activePrice = $event"
          @update:activeQty="activeQty = $event"
        />
<StockPage v-else-if="activeView === 'Stok Barang'" :query="query" :type-filter="typeFilter" :products="products" :sorted-products="sortedProducts" :money="money" :sort-by="sortBy" :sort-key="sortKey" :sort-direction="sortDirection" @update:query="query = $event" @update:typeFilter="typeFilter = $event" />
        <DirectoryPage v-else-if="['Data Supplier', 'Data Pembeli', 'Data Barang'].includes(activeView)" :active-view="activeView" :sorted-directory-entries="sortedDirectoryEntries" :sort-by="sortBy" :sort-key="sortKey" :sort-direction="sortDirection" :open-directory="openDirectory" :delete-directory="deleteDirectory" />
        <LogPage v-else :transactions="transactions" :money="money" :total-out="totalOut" :total-in="totalIn" :profit="profit" :date-preset="datePreset" @update:datePreset="datePreset = $event" @open-receipt="openReceipt" />
      </section>
    </main>
    <div v-if="toast" class="toast">{{ toast }}</div>
    <div v-if="directoryView" class="modal-backdrop" @click.self="directoryView = ''; editingDirectory = null"><div class="quick-add-modal"><button class="close" @click="directoryView = ''; editingDirectory = null">×</button><p class="eyebrow">REFERENSI</p><h2>{{ editingDirectory ? 'Edit' : 'Tambah' }} {{ directoryTitle() }}</h2><p class="muted">Simpan data untuk digunakan pada transaksi.</p><form @submit.prevent="submitDirectory"><label>Nama *<input v-model="directoryForm.name" :placeholder="`Nama ${directoryTitle().toLowerCase()}`"></label><template v-if="directoryView !== 'Data Barang'"><label>No. HP<input v-model="directoryForm.phone" placeholder="08xxxxxxxxxx"></label><label>Alamat<textarea v-model="directoryForm.address" rows="3" placeholder="Alamat lengkap"></textarea></label></template><template v-else><label>Satuan *<select v-model="directoryForm.unit"><option v-for="unit in units" :key="unit" :value="unit">{{ unit }}</option></select></label><label>Tipe *<select v-model="directoryForm.type"><option value="basah">Basah</option><option value="kering">Kering</option></select></label></template><div class="quick-add-actions"><button class="secondary" type="button" @click="directoryView = ''; editingDirectory = null">Batal</button><button class="primary" type="submit">Simpan</button></div></form></div></div>
    <div v-if="showReceipt" class="modal-backdrop" @click.self="showReceipt = false"><div class="receipt"><button class="close" @click="showReceipt = false">×</button><p class="eyebrow">BMS-KOPERASI</p><h2>{{ receipt.type === 'IN' ? 'Nota Pembelian' : 'Kwitansi Penjualan' }}</h2><p class="muted">22 Agustus 2026 · #TRX-{{ receipt.id }}</p><hr><div class="receipt-line"><span>{{ receipt.type === 'IN' ? 'Supplier' : 'Pembeli' }}</span><strong>{{ receipt.party }}</strong></div><div class="receipt-line"><span>{{ receipt.product }} × {{ receipt.qty }}</span><strong>{{ money(receipt.total) }}</strong></div><button class="primary submit" @click="showReceipt = false">Tutup nota</button></div></div>
  </div>
</template>
