<script setup>
import { computed } from 'vue'

const props = defineProps({
  activeView: String,
  inForm: Object,
  outForm: Object,
  suppliers: Array,
  customers: Array,
  products: Array,
  itemCatalogs: Array,
  money: { type: Function, required: true },
  incomingTransactions: Array,
  outgoingTransactions: Array,
  selectedProduct: Object,
  activePrice: [Number, String],
  activeQty: [Number, String],
  submitIn: Function,
  submitOut: Function,
  selectIncomingCatalog: Function,
  selectProduct: Function,
  openReceipt: Function,
})

const emit = defineEmits(['update:activePrice', 'update:activeQty'])

const priceModel = computed({
  get: () => props.activePrice,
  set: value => emit('update:activePrice', value),
})

const qtyModel = computed({
  get: () => props.activeQty,
  set: value => emit('update:activeQty', value),
})
</script>

<template>
  <div class="page-heading">
    <div><p class="eyebrow">TRANSAKSI / {{ activeView.toUpperCase() }}</p><h1>{{ activeView }}</h1><p class="muted">Catat pergerakan inventaris dengan detail yang akurat.</p></div>
  </div>
  <div class="form-panel panel"><div class="panel-title"><div><h2>{{ activeView === 'Barang Masuk' ? 'Form barang masuk' : 'Form barang keluar' }}</h2><p class="muted">Field bertanda * wajib diisi</p></div><span class="form-badge">{{ activeView === 'Barang Masuk' ? 'PEMBELIAN' : 'PENJUALAN' }}</span></div>
    <form @submit.prevent="activeView === 'Barang Masuk' ? submitIn() : submitOut()">
      <label v-if="activeView === 'Barang Masuk'">Nama supplier *<select v-model="inForm.supplier"><option value="">Pilih supplier</option><option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.name">{{ supplier.name }}</option></select></label>
      <label v-else>Nama pembeli *<select v-model="outForm.customer"><option value="">Pilih pembeli</option><option v-for="customer in customers" :key="customer.id" :value="customer.name">{{ customer.name }}</option></select></label>
      <label>Nama barang *<select v-if="activeView === 'Barang Keluar'" v-model="outForm.productId" @change="selectProduct"><option value="">Pilih barang dari master</option><option v-for="product in products" :value="product.id" :key="product.id">{{ product.name }} ({{ product.stock }} {{ product.unit }})</option></select><select v-else v-model="inForm.catalogId" @change="selectIncomingCatalog"><option value="">Pilih dari daftar barang</option><option v-for="catalog in itemCatalogs" :value="catalog.id" :key="catalog.id">{{ catalog.name }}</option></select></label>
      <label v-if="activeView === 'Barang Masuk'">Satuan<input :value="inForm.unit || '-'" disabled></label>
      <label v-else>Satuan<input :value="selectedProduct?.unit || '-'" disabled></label>
      <label>Harga satuan *<input v-model="priceModel" type="number" min="0" placeholder="0"></label>
      <label>Jumlah (qty) *<input v-model="qtyModel" type="number" min="0.001" step="0.001" placeholder="0"></label>
      <div class="total-box"><span>Total harga</span><strong>{{ money(Number(priceModel) * Number(qtyModel)) }}</strong></div>
      <p v-if="activeView === 'Barang Keluar' && selectedProduct" class="muted">Stok saat ini: {{ selectedProduct.stock }} {{ selectedProduct.unit }}</p>
      <button class="primary submit" type="submit">{{ activeView === 'Barang Masuk' ? 'Simpan barang masuk' : 'Simpan barang keluar' }}</button>
    </form>
  </div>
  <div class="transaction-list panel"><div class="panel-title"><div><h2>{{ activeView === 'Barang Masuk' ? 'Daftar barang masuk' : 'Daftar barang keluar' }}</h2><p class="muted">{{ activeView === 'Barang Masuk' ? 'Riwayat pembelian dan penerimaan stok' : 'Riwayat penjualan dan pengeluaran stok' }}</p></div><span class="list-count">{{ activeView === 'Barang Masuk' ? incomingTransactions.length : outgoingTransactions.length }} transaksi</span></div>
    <div class="transaction-table-wrap"><table class="transaction-table"><thead><tr><th>TANGGAL</th><th>{{ activeView === 'Barang Masuk' ? 'SUPPLIER' : 'PEMBELI' }}</th><th>NAMA BARANG</th><th>QTY</th><th>TOTAL HARGA</th><th></th></tr></thead><tbody><tr v-for="transaction in (activeView === 'Barang Masuk' ? incomingTransactions : outgoingTransactions)" :key="transaction.id"><td>{{ transaction.date }}</td><td><strong>{{ transaction.party }}</strong></td><td>{{ transaction.product }}</td><td>{{ transaction.qty }}</td><td><strong>{{ money(transaction.total) }}</strong></td><td><button class="print" @click="openReceipt(transaction)">Cetak Nota</button></td></tr><tr v-if="!(activeView === 'Barang Masuk' ? incomingTransactions : outgoingTransactions).length"><td colspan="6" class="empty">Belum ada transaksi {{ activeView.toLowerCase() }}.</td></tr></tbody></table></div>
  </div>
</template>
