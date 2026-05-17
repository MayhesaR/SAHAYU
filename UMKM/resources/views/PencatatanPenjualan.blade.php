@extends('layouts.app')
@section('title', 'Kasir POS')
@section('page_title', 'Kasir POS')

@section('content')
<div class="px-4 py-8 sm:px-8 max-w-7xl mx-auto space-y-8" 
     x-data="{ 
        paymentMethod: 'cash', 
        selectedCategory: 'all',
        selectedProductId: '',
        selectedProductPrice: 0,
        selectedProductStock: 0,
        quantity: 1,
        customerId: '',
        isNewCustomerModalOpen: false,
        isSuccessModalOpen: {{ session('print_sale_id') ? 'true' : 'false' }},
        updateProductDetailsById(id, price, stock) {
            this.selectedProductId = id;
            this.selectedProductPrice = parseFloat(price);
            this.selectedProductStock = parseInt(stock);
            this.quantity = 1;
        },
        get totalBill() {
            return this.selectedProductPrice * this.quantity;
        },
        formatRupiah(val) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val);
        }
     }">

    <!-- Alert Notifications -->
    @if(session('success') && !session('print_sale_id'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl space-y-1 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-rose-600">error</span>
                <span class="font-bold text-sm">Terjadi Kesalahan:</span>
            </div>
            <ul class="list-disc list-inside text-xs font-semibold pl-8">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main POS Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- LEFT: Accessible iPad-like Input Panel (7 Columns) -->
        <div class="lg:col-span-7 bg-white p-6 md:p-8 rounded-3xl shadow-xl border border-slate-100 space-y-8">
            
            <!-- Header Section -->
            <div class="flex justify-between items-center pb-6 border-b border-slate-100">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight">Kasir SAHAYU</h2>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mt-1">Registrasi Penjualan & Tagihan</p>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="isNewCustomerModalOpen = true" 
                            type="button" 
                            class="px-4 py-2.5 bg-emerald-50 text-emerald-800 border border-emerald-100 hover:bg-emerald-100 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-sm">
                        <span class="material-symbols-outlined text-[16px]">person_add</span>
                        <span>Pelanggan Baru</span>
                    </button>
                </div>
            </div>

            <!-- Transaction Form -->
            <form action="{{ route('sales.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Hidden Selected Product ID input -->
                <input type="hidden" name="product_id" x-model="selectedProductId" required />

                <!-- 1. Product Highly Visual Grid Cards -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <label class="block text-sm font-black uppercase tracking-widest text-slate-500">Pilih Produk Jadi (Sentuh Kartu)</label>
                        <span x-show="selectedProductId" class="text-[10px] font-black uppercase tracking-wider text-[#005050] bg-teal-50 px-2.5 py-1 rounded-lg border border-teal-100" x-cloak>
                            Terpilih
                        </span>
                    </div>

                    <!-- Horizontal Scrollable Category Chips -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 custom-scrollbar scroll-smooth whitespace-nowrap">
                        <button type="button" 
                                @click="selectedCategory = 'all'"
                                :class="selectedCategory === 'all' ? 'bg-[#005050] text-white border-[#005050] shadow-sm' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-700 border-slate-200'"
                                class="px-4 py-2 rounded-xl text-xs font-black transition-all border select-none">
                            Semua
                        </button>
                        @foreach($categories as $cat)
                            <button type="button" 
                                    @click="selectedCategory = '{{ $cat->id }}'"
                                    :class="selectedCategory === '{{ $cat->id }}' ? 'bg-[#005050] text-white border-[#005050] shadow-sm' : 'bg-slate-50 text-slate-500 hover:bg-slate-100 hover:text-slate-700 border-slate-200'"
                                    class="px-4 py-2 rounded-xl text-xs font-black transition-all border select-none">
                                {{ $cat->name }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Card Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 max-h-[380px] overflow-y-auto pr-2 custom-scrollbar">
                        @foreach ($products as $product)
                            @php
                                $isOutOfStock = $product->stock <= 0;
                            @endphp
                            <div 
                                @if(!$isOutOfStock)
                                    @click="updateProductDetailsById('{{ $product->id }}', '{{ $product->selling_price }}', '{{ $product->stock }}')"
                                @endif
                                x-show="selectedCategory === 'all' || selectedCategory === '{{ $product->category_id }}'"
                                :class="{
                                    'border-[#005050] bg-[#005050]/5 ring-4 ring-[#005050]/10': selectedProductId == '{{ $product->id }}',
                                    'border-slate-100 bg-white hover:border-slate-300 hover:shadow-md': selectedProductId != '{{ $product->id }}' && !{{ $isOutOfStock ? 'true' : 'false' }},
                                    'opacity-50 grayscale bg-slate-100 cursor-not-allowed': {{ $isOutOfStock ? 'true' : 'false' }}
                                }"
                                class="relative rounded-2xl border-2 p-3 flex flex-col justify-between h-44 cursor-pointer transition-all duration-300 overflow-hidden select-none">
                                
                                <!-- Out of stock overlay badge -->
                                @if($isOutOfStock)
                                    <div class="absolute inset-0 bg-slate-900/10 flex items-center justify-center z-10">
                                        <span class="bg-rose-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full shadow-md">HABIS</span>
                                    </div>
                                @endif

                                <!-- Product Image / Placeholder Initials -->
                                <div class="w-full h-24 rounded-xl overflow-hidden bg-slate-50 relative border border-slate-100 flex items-center justify-center">
                                    @if($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" class="w-full h-full object-cover" alt="{{ $product->name }}">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-teal-50 text-teal-700 font-black text-xl">
                                            {{ strtoupper(substr($product->name, 0, 2)) }}
                                        </div>
                                    @endif

                                    <!-- Stock Badge in real time -->
                                    <div class="absolute bottom-2 right-2 bg-slate-900/70 text-white px-2 py-0.5 rounded-lg text-[9px] font-bold flex items-center gap-1 backdrop-blur-sm">
                                        <span>📦 Stok: {{ $product->stock }}</span>
                                    </div>
                                </div>

                                <!-- Title and price -->
                                <div class="mt-2 space-y-0.5">
                                    <p class="text-xs font-black text-slate-800 truncate">{{ $product->name }}</p>
                                    <p class="text-[11px] font-bold text-slate-500">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- 2. Quantity Stepper with Stock Limit Guards -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <label class="block text-sm font-black uppercase tracking-widest text-slate-500">Jumlah Unit (Pcs)</label>
                        <span x-show="selectedProductId && selectedProductStock > 0" class="text-xs font-bold text-slate-400" x-cloak>
                            Sedia: <span class="text-[#005050] font-black" x-text="selectedProductStock + ' Pcs'"></span>
                        </span>
                    </div>
                    <div class="flex items-center gap-4 bg-slate-50 p-2 rounded-2xl border-2 border-slate-100 w-full md:w-3/5">
                        <button type="button" 
                                @click="if(quantity > 1) quantity--" 
                                class="w-12 h-12 bg-white hover:bg-slate-100 text-slate-800 rounded-xl flex items-center justify-center border border-slate-200 shadow-sm font-black text-lg active:scale-95 transition-all">
                            -
                        </button>
                        <input type="number" 
                               name="quantity" 
                               x-model.number="quantity" 
                               min="1" 
                               :max="selectedProductStock"
                               @input="if(quantity > selectedProductStock) quantity = selectedProductStock; if(quantity < 1) quantity = 1;"
                               class="flex-1 text-center bg-transparent border-none text-xl font-black text-slate-800 outline-none w-16"
                               required />
                        <button type="button" 
                                @click="if(quantity < selectedProductStock) quantity++" 
                                class="w-12 h-12 bg-white hover:bg-slate-100 text-slate-800 rounded-xl flex items-center justify-center border border-slate-200 shadow-sm font-black text-lg active:scale-95 transition-all">
                            +
                        </button>
                    </div>
                </div>

                <!-- 3. Mini CRM - Pilih Pelanggan -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <label class="text-sm font-black uppercase tracking-widest text-slate-500">Pilih Pelanggan</label>
                        <span x-show="paymentMethod === 'debt'" 
                              class="text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200"
                              x-cloak>
                            Wajib untuk Piutang / Kasbon
                        </span>
                    </div>
                    <select name="customer_id" 
                            x-model="customerId"
                            :required="paymentMethod === 'debt'"
                            class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl p-4 text-base md:text-lg font-black text-slate-800 focus:bg-white focus:border-emerald-600 focus:ring-4 focus:ring-emerald-500/10 transition-all outline-none">
                        <option value="">-- Umum / Walk-in Customer --</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->name }} ({{ $c->phone ?: 'No HP Kosong' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Massive Accessible Payment Toggle -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <label class="block text-sm font-black uppercase tracking-widest text-slate-500">Pilih Tipe Pembayaran</label>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Tunai (Cash/QRIS/Transfer) -->
                        <button type="button" 
                                @click="paymentMethod = 'cash'"
                                :class="paymentMethod !== 'debt' ? 'border-emerald-600 bg-emerald-50/50 text-emerald-950 ring-4 ring-emerald-500/15' : 'border-slate-100 bg-slate-50 text-slate-500'"
                                class="flex flex-col items-center justify-center p-6 rounded-2xl border-2 cursor-pointer transition-all active:scale-[0.98] duration-300">
                            <span :class="paymentMethod !== 'debt' ? 'text-emerald-700' : 'text-slate-400'" 
                                  class="material-symbols-outlined text-4xl mb-2">payments</span>
                            <span class="text-base font-black tracking-tight">Tunai / Transfer / QRIS</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1">Lunas Langsung</span>
                        </button>
                        <!-- Piutang / Kasbon -->
                        <button type="button" 
                                @click="paymentMethod = 'debt'"
                                :class="paymentMethod === 'debt' ? 'border-amber-600 bg-amber-50/50 text-amber-950 ring-4 ring-amber-500/15' : 'border-slate-100 bg-slate-50 text-slate-500'"
                                class="flex flex-col items-center justify-center p-6 rounded-2xl border-2 cursor-pointer transition-all active:scale-[0.98] duration-300">
                            <span :class="paymentMethod === 'debt' ? 'text-amber-700' : 'text-slate-400'" 
                                  class="material-symbols-outlined text-4xl mb-2">menu_book</span>
                            <span class="text-base font-black tracking-tight">Piutang / Kasbon</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1">Buku Ledger CRM</span>
                        </button>
                    </div>
                </div>

                <!-- Hidden Input For Submitting Payment Method -->
                <div x-show="paymentMethod !== 'debt'" class="space-y-3 pt-2" x-cloak>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Pilih Rincian Tunai</label>
                    <div class="flex gap-3">
                        <label class="flex-1 relative flex items-center justify-center py-2.5 px-4 bg-slate-50 hover:bg-slate-100 rounded-xl cursor-pointer border border-transparent has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/20 text-xs font-black text-slate-700">
                            <input type="radio" name="payment_method" value="cash" checked class="hidden">
                            Cash / Tunai
                        </label>
                        <label class="flex-1 relative flex items-center justify-center py-2.5 px-4 bg-slate-50 hover:bg-slate-100 rounded-xl cursor-pointer border border-transparent has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/20 text-xs font-black text-slate-700">
                            <input type="radio" name="payment_method" value="transfer" class="hidden">
                            Transfer Bank
                        </label>
                        <label class="flex-1 relative flex items-center justify-center py-2.5 px-4 bg-slate-50 hover:bg-slate-100 rounded-xl cursor-pointer border border-transparent has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50/20 text-xs font-black text-slate-700">
                            <input type="radio" name="payment_method" value="qris" class="hidden">
                            QRIS Digital
                        </label>
                    </div>
                </div>

                <!-- Hidden Input For Debt Type -->
                <input type="hidden" name="payment_method" value="debt" x-bind:disabled="paymentMethod !== 'debt'" />

                <!-- Submit Section -->
                <div class="pt-6 border-t border-slate-100">
                    <button type="submit" 
                            style="background-color: #005050;" 
                            class="w-full text-white py-4 md:py-5 rounded-2xl shadow-xl shadow-teal-950/20 text-lg font-black tracking-wide flex items-center justify-center gap-3 hover:opacity-95 active:scale-[0.99] transition-all">
                        <span class="material-symbols-outlined text-2xl">check_circle</span>
                        <span>Konfirmasi & Cetak Nota</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- RIGHT: Luxury Checkout Summary Panel (5 Columns) -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Statistics Banner -->
            <div class="grid grid-cols-2 gap-4">
                <div class="p-6 rounded-3xl text-white relative overflow-hidden group shadow-lg" style="background-color: #005050;">
                    <div class="absolute -right-4 -bottom-4 opacity-10 transform rotate-12 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-8xl">shopping_cart</span>
                    </div>
                    <span class="material-symbols-outlined text-2xl opacity-60 mb-2">shopping_bag</span>
                    <p class="text-[10px] font-bold uppercase tracking-wider opacity-60">Total Terjual Hari Ini</p>
                    <p class="text-2xl font-black font-headline mt-1">{{ number_format($todayUnits, 0, ',', '.') }} Pcs</p>
                </div>
                <div class="bg-amber-500/10 border border-amber-500/20 p-6 rounded-3xl text-amber-950 relative overflow-hidden group shadow-sm">
                    <div class="absolute -right-4 -bottom-4 opacity-10 transform rotate-12 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-8xl">receipt_long</span>
                    </div>
                    <span class="material-symbols-outlined text-2xl text-amber-700 mb-2">assignment</span>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Total Transaksi Hari Ini</p>
                    <p class="text-2xl font-black font-headline mt-1 text-amber-950">{{ number_format($todayTransactions, 0, ',', '.') }} Nota</p>
                </div>
            </div>

            <!-- Real-time Cart Summary Card -->
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="p-6 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-500">Rincian Pembelian</h3>
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-800 text-[10px] font-bold rounded-full">Kalkulator Kasir</span>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Item Line -->
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex gap-3">
                            <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-slate-500 flex-shrink-0">
                                <span class="material-symbols-outlined text-2xl">bakery_dining</span>
                            </div>
                            <div>
                                <p class="text-sm font-black text-slate-800" x-text="selectedProductId ? 'Item Terpilih' : 'Belum Ada Produk'"></p>
                                <p class="text-xs font-bold text-slate-400 mt-0.5" x-text="selectedProductId ? 'Siap Checkout' : 'Sentuh kartu produk di sebelah kiri'"></p>
                            </div>
                        </div>
                        <div class="text-right" x-show="selectedProductId">
                            <p class="text-sm font-black text-slate-800" x-text="formatRupiah(selectedProductPrice)"></p>
                            <p class="text-xs font-bold text-slate-400 mt-0.5" x-text="'x ' + quantity + ' pcs'"></p>
                        </div>
                    </div>

                    <!-- Separator -->
                    <div class="border-t-2 border-dashed border-slate-100"></div>

                    <!-- Bill details -->
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs font-bold text-slate-500">
                            <span>Subtotal</span>
                            <span x-text="formatRupiah(totalBill)"></span>
                        </div>
                        <div class="flex justify-between text-xs font-bold text-slate-500">
                            <span>Diskon / Promo</span>
                            <span>Rp 0</span>
                        </div>
                        <div class="flex justify-between text-xs font-bold text-slate-500">
                            <span>Status Bayar</span>
                            <span :class="paymentMethod === 'debt' ? 'text-amber-600' : 'text-emerald-600'" 
                                  class="font-black uppercase" 
                                  x-text="paymentMethod === 'debt' ? 'Piutang / Belum Lunas' : 'Tunai / Lunas'"></span>
                        </div>
                    </div>

                    <!-- Grand Total Banner -->
                    <div class="p-4 bg-slate-50 rounded-2xl flex justify-between items-center border border-slate-100">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Total Tagihan</p>
                            <p class="text-2xl font-black text-slate-800 mt-1" x-text="formatRupiah(totalBill)"></p>
                        </div>
                        <div class="px-4 py-2 bg-amber-500/10 text-amber-800 text-[10px] font-bold rounded-xl border border-amber-500/10">
                            IDR / Rupiah
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic Marketing Tip / Alert -->
            <div class="relative h-40 rounded-3xl overflow-hidden group shadow-lg">
                <img alt="Point of Sale context" 
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" 
                     src="https://lh3.googleusercontent.com/aida-public/AB6AXuBC4U0wF_xyXdurCsHFHkS0jz3GAIoEFF0MmFILpzeRrsZyscGMMve6QSRYvMcWLd-AOPBjPzZWIWAgSFzebFBueYK6xkL9nbPSKVsY5OzHx8w-Yr1qN1HdUdmvpfIWxAFf3fozonRvwfGAZkqS9J0XBLNWLQ0RWCL8XsWiGtmXPsHz9qaqpXUrtP6JHomWORtHRzHaYcfbHF6wxTAEkvxUiJ7rvL5zOTrA7NzqvteSA9fJ_JWshB7rT2vnQxj-GHhf-l3Lel6qbfI"/>
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent flex flex-col justify-end p-6">
                    <p class="text-xs font-black text-emerald-400 uppercase tracking-widest mb-1">
                        <span class="material-symbols-outlined text-[10px] align-middle">{{ $businessTip['icon'] ?? 'lightbulb' }}</span>
                        Insight: {{ $businessTip['title'] ?? 'Tips Kasir' }}
                    </p>
                    <h4 class="text-white text-xs font-bold leading-relaxed">{{ $businessTip['content'] ?? 'Jaga akurasi pencatatan kasbon agar kas selalu seimbang.' }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- CRM: Add New Customer Modal (AlpineJS) -->
    <div x-show="isNewCustomerModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         x-cloak>
        
        <div class="bg-white p-8 rounded-3xl shadow-2xl max-w-md w-full border border-slate-100 mx-4 relative"
             @click.away="isNewCustomerModalOpen = false">
            
            <!-- Close Button -->
            <button @click="isNewCustomerModalOpen = false" 
                    class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>

            <!-- Header -->
            <div class="mb-6 space-y-1">
                <h3 class="text-xl font-black text-slate-800">Daftarkan Pelanggan Baru</h3>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Layanan CRM Kasbon SAHAYU</p>
            </div>

            <!-- Form -->
            <form action="{{ route('customers.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500">Nama Pelanggan / Toko</label>
                    <input type="text" 
                           name="name" 
                           required 
                           placeholder="Contoh: Toko Berkah Mandiri"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm focus:bg-white focus:border-emerald-600 transition-all outline-none font-bold text-slate-800" />
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500">No. WhatsApp / HP</label>
                    <input type="text" 
                           name="phone" 
                           placeholder="Contoh: 081234567890"
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm focus:bg-white focus:border-emerald-600 transition-all outline-none font-bold text-slate-800" />
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-black uppercase tracking-wider text-slate-500">Alamat Lengkap</label>
                    <textarea name="address" 
                              rows="3" 
                              placeholder="Alamat toko atau rumah pelanggan..."
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-sm focus:bg-white focus:border-emerald-600 transition-all outline-none font-bold text-slate-800"></textarea>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" 
                            @click="isNewCustomerModalOpen = false"
                            class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black rounded-xl transition-all">
                        Batalkan
                    </button>
                    <button type="submit" 
                            style="background-color: #005050;"
                            class="flex-1 py-3 text-white text-xs font-black rounded-xl transition-all shadow-sm">
                        Simpan Pelanggan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CHECKOUT SUCCESS MODAL (AlpineJS) -->
    <div x-show="isSuccessModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         x-cloak>
        
        <div class="bg-white p-8 rounded-3xl shadow-2xl max-w-sm w-full border border-slate-100 mx-4 text-center relative"
             @click.away="isSuccessModalOpen = false">
            
            <!-- Success Icon Animation -->
            <div class="mx-auto w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mb-6 shadow-inner animate-bounce">
                <span class="material-symbols-outlined text-4xl">check_circle</span>
            </div>

            <!-- Header -->
            <div class="mb-6 space-y-2">
                <h3 class="text-xl font-black text-slate-800">Transaksi Berhasil!</h3>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Nota #{{ session('print_sale_id') }} Telah Dicatat</p>
            </div>

            <p class="text-slate-500 text-xs font-medium leading-relaxed mb-8">
                Nota penjualan ini telah berhasil tersimpan dalam sistem ledger keuangan SAHAYU. Siap untuk dicetak ke thermal printer.
            </p>

            <!-- Actions -->
            <div class="flex flex-col gap-3">
                <a href="{{ route('sales.receipt', session('print_sale_id', 0)) }}" 
                   target="_blank"
                   @click="isSuccessModalOpen = false"
                   style="background-color: #005050;"
                   class="w-full py-4 text-white text-sm font-black rounded-2xl transition-all shadow-md flex items-center justify-center gap-2 hover:opacity-95">
                    <span class="material-symbols-outlined text-lg">print</span>
                    <span>Cetak Struk Thermal</span>
                </a>
                <button type="button" 
                        @click="isSuccessModalOpen = false"
                        class="w-full py-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-black rounded-2xl transition-all">
                    Transaksi Baru (Tutup)
                </button>
            </div>
        </div>
    </div>

</div>

<style>
    /* Custom Scrollbar for visual product grid & chips */
    .custom-scrollbar::-webkit-scrollbar {
        height: 4px;
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 20px;
    }
</style>
@endsection
