@extends('layouts.app')
@section('title', 'Kasir POS')
@section('page_title', 'Kasir POS')

@section('content')
<div class="w-full space-y-6" 
     x-data="{ 
        paymentMethod: 'cash', 
        selectedCategory: 'all',
        selectedProductId: '',
        selectedProductPrice: 0,
        selectedProductStock: 0,
        quantity: 1,
        customerId: '',
        dueDate: '',
        isNewCustomerModalOpen: false,
        isSuccessModalOpen: {{ session('print_sale_id') ? 'true' : 'false' }},
        searchQuery: '',
        init() {
            const date = new Date();
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            this.dueDate = `${yyyy}-${mm}-${dd}`;
        },
        setDueDateDays(days) {
            const date = new Date();
            date.setDate(date.getDate() + days);
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            this.dueDate = `${yyyy}-${mm}-${dd}`;
        },
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
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 dark:text-emerald-400 rounded-[1.25rem] flex items-center gap-3 shadow-sm animate-fade-in">
            <span class="material-symbols-outlined text-emerald-600">check_circle</span>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-[1.25rem] space-y-1 shadow-sm">
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

    <!-- Top Statistics Dashboard Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Stats Card 1: Omset Hari Ini -->
        <div class="p-4 bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 rounded-[1.25rem] shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">Omset Hari Ini</p>
                <p class="text-lg font-extrabold text-stone-850 dark:text-white">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#0b6e4f] dark:text-emerald-400 flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">payments</span>
            </div>
        </div>
        <!-- Stats Card 2: Total Terjual -->
        <div class="p-4 bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 rounded-[1.25rem] shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">Total Terjual</p>
                <p class="text-lg font-extrabold text-stone-850 dark:text-white">{{ number_format($todayUnits, 0, ',', '.') }} Pcs</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#0b6e4f] dark:text-emerald-400 flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">shopping_bag</span>
            </div>
        </div>
        <!-- Stats Card 3: Total Transaksi -->
        <div class="p-4 bg-white dark:bg-zinc-900 border border-stone-200/60 dark:border-zinc-800/80 rounded-[1.25rem] shadow-sm flex items-center justify-between">
            <div class="space-y-1">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-400 dark:text-zinc-400">Total Transaksi</p>
                <p class="text-lg font-extrabold text-stone-850 dark:text-white">{{ number_format($todayTransactions, 0, ',', '.') }} Nota</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center">
                <span class="material-symbols-outlined text-xl">assignment</span>
            </div>
        </div>
    </div>

    <!-- Main POS Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT: Product list and selection panel (7 Columns) -->
        <div class="lg:col-span-7 bg-white dark:bg-zinc-900 p-5 md:p-6 rounded-[1.5rem] shadow-sm border border-stone-200/60 dark:border-zinc-800/80 space-y-5">
            
            <!-- Header Section -->
            <div class="flex justify-between items-center pb-4 border-b border-stone-200/60 dark:border-zinc-800/80">
                <div>
                    <h2 class="text-lg font-bold text-stone-850 dark:text-white tracking-tight">Kasir SAHAYU</h2>
                    <p class="text-xs font-semibold uppercase tracking-wider text-stone-400 dark:text-zinc-400 mt-0.5">Registrasi Penjualan & Tagihan</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Guided Tour Button -->
                    <button type="button" id="btn-start-tour"
                            class="bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 rounded-xl px-4 py-2 text-xs font-semibold transition-all flex items-center gap-2 border border-emerald-100/30 dark:border-emerald-900/30 shadow-sm hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                        <span class="material-symbols-outlined text-[16px] font-bold">help</span>
                        <span class="hidden sm:inline">Panduan Kasir</span>
                    </button>
                    <button @click="isNewCustomerModalOpen = true" 
                            type="button" 
                            class="px-4 py-2 bg-emerald-50 text-[#0b6e4f] dark:text-emerald-400 border border-emerald-100 hover:bg-[#0b6e4f]/10 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shadow-sm cursor-pointer">
                        <span class="material-symbols-outlined text-[16px]">person_add</span>
                        <span>Pelanggan Baru</span>
                    </button>
                </div>
            </div>

            <!-- Transaction Form -->
            <form id="pos-form" action="{{ route('sales.store') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Hidden Selected Product ID input -->
                <input type="hidden" name="product_id" x-model="selectedProductId" required />

                <!-- 1. Product Grid & Filters -->
                <div id="tour-pos-catalog" class="space-y-3">
                    <div class="flex justify-between items-center">
                        <label class="block text-[10px] font-bold uppercase tracking-widest text-stone-400 dark:text-zinc-400">Pilih Produk Jadi (Sentuh Kartu)</label>
                        <span x-show="selectedProductId" class="text-[9px] font-black uppercase tracking-wider text-[#0b6e4f] dark:text-emerald-400 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100" x-cloak x-transition>
                            Terpilih
                        </span>
                    </div>

                    <!-- Search and category chips placed horizontally to save space -->
                    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
                        <!-- Horizontal Category Chips -->
                        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 max-w-full sm:max-w-[65%] custom-scrollbar scroll-smooth whitespace-nowrap">
                            <button type="button" 
                                    @click="selectedCategory = 'all'"
                                    :class="selectedCategory === 'all' ? 'bg-[#0b6e4f] dark:bg-emerald-600 text-white border-[#0b6e4f] dark:border-emerald-500 shadow-sm' : 'bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 text-stone-500 dark:text-zinc-400 hover:bg-stone-100/50 dark:hover:bg-zinc-800/50 border-stone-200/60 dark:border-zinc-800/80'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all border select-none cursor-pointer">
                                Semua
                            </button>
                            @foreach($categories as $cat)
                                <button type="button" 
                                        @click="selectedCategory = '{{ $cat->id }}'"
                                        :class="selectedCategory === '{{ $cat->id }}' ? 'bg-[#0b6e4f] dark:bg-emerald-600 text-white border-[#0b6e4f] dark:border-emerald-500 shadow-sm' : 'bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 text-stone-500 dark:text-zinc-400 hover:bg-stone-100/50 dark:hover:bg-zinc-800/50 border-stone-200/60 dark:border-zinc-800/80'"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all border select-none cursor-pointer">
                                    {{ $cat->name }}
                                </button>
                            @endforeach
                        </div>
                        
                        <!-- Mini Search Box inside POS -->
                        <div class="relative w-full sm:w-[32%]">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-stone-400 dark:text-zinc-400">
                                <span class="material-symbols-outlined text-[16px]">search</span>
                            </span>
                            <input type="text" 
                                   x-model="searchQuery" 
                                   placeholder="Cari produk..." 
                                   class="w-full pl-9 pr-3 py-1.5 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl text-xs font-semibold focus:bg-white dark:focus:bg-zinc-900 focus:border-[#0b6e4f] dark:focus:border-emerald-500 focus:ring-4 focus:ring-[#0b6e4f]/10 dark:focus:ring-emerald-500/10 transition-all outline-none text-stone-850 dark:text-white placeholder-stone-400" />
                        </div>
                    </div>

                    <!-- Card Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 max-h-[380px] overflow-y-auto pr-1 custom-scrollbar">
                        @forelse ($products as $product)
                            @php
                                $isOutOfStock = $product->stock <= 0;
                            @endphp
                            <div 
                                @if(!$isOutOfStock)
                                    @click="updateProductDetailsById('{{ $product->id }}', '{{ $product->selling_price }}', '{{ $product->stock }}')"
                                @endif
                                x-show="(selectedCategory === 'all' || selectedCategory === '{{ $product->category_id }}') && '{{ strtolower($product->name) }}'.includes(searchQuery.toLowerCase())"
                                :class="{
                                    'border-[#0b6e4f] dark:border-emerald-500 bg-emerald-50/10 ring-2 ring-[#0b6e4f]/20 dark:ring-emerald-500/10': selectedProductId == '{{ $product->id }}',
                                    'border-stone-200/60 dark:border-zinc-800/80 bg-white dark:bg-zinc-900 hover:border-stone-300 hover:shadow-sm': selectedProductId != '{{ $product->id }}' && !{{ $isOutOfStock ? 'true' : 'false' }},
                                    'opacity-40 grayscale bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 cursor-not-allowed': {{ $isOutOfStock ? 'true' : 'false' }}
                                }"
                                class="relative rounded-xl border p-2.5 flex flex-col justify-between h-36 cursor-pointer transition-all duration-300 overflow-hidden select-none">
                                
                                <!-- Selection indicator dot/icon -->
                                <div x-show="selectedProductId == '{{ $product->id }}'" 
                                     class="absolute top-2 left-2 z-20 bg-[#0b6e4f] dark:bg-emerald-600 text-white w-5 h-5 rounded-full flex items-center justify-center shadow-md"
                                     x-cloak x-transition>
                                     <span class="material-symbols-outlined text-[12px] font-bold">check</span>
                                </div>

                                <!-- Out of stock overlay badge -->
                                @if($isOutOfStock)
                                    <div class="absolute inset-0 bg-stone-900/5 flex items-center justify-center z-10">
                                        <span class="bg-rose-600 text-white text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded shadow-sm">HABIS</span>
                                    </div>
                                @endif

                                <!-- Product Image / Placeholder Initials -->
                                <div class="w-full h-20 rounded-lg overflow-hidden bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 relative border border-stone-200/40 dark:border-zinc-800/40 flex items-center justify-center">
                                    @if($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" class="w-full h-full object-cover" alt="{{ $product->name }}">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/40 dark:to-teal-950/40 text-[#0b6e4f] dark:text-emerald-400 font-black text-base">
                                            {{ strtoupper(substr($product->name, 0, 2)) }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Title and price -->
                                <div class="mt-1 space-y-0.5">
                                    <p class="text-[11px] font-bold text-stone-800 dark:text-white truncate">{{ $product->name }}</p>
                                    <div class="flex items-center justify-between gap-1">
                                        <p class="text-[10px] font-bold text-[#0b6e4f] dark:text-emerald-400">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</p>
                                        <span class="text-[9px] font-bold text-stone-400 dark:text-zinc-400">Stok: {{ $product->stock }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full bg-white dark:bg-zinc-900 rounded-2xl p-8 text-stone-500 dark:text-zinc-300 border border-stone-200/60 dark:border-zinc-800/80 shadow-sm text-center">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-stone-400 dark:text-zinc-500 font-light">inventory_2</span>
                                    <p class="font-bold text-stone-700 dark:text-zinc-200">Katalog produk kosong</p>
                                    <p class="text-[10px] text-stone-400 dark:text-zinc-500">Silakan tambahkan produk baru di menu Manajemen Produk terlebih dahulu.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- 2 & 3. Quantity Stepper & Pilih Pelanggan (Side-by-Side to reduce spacing) -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 pt-4 border-t border-stone-100 dark:border-zinc-800/60">
                    
                    <!-- Stepper (5 Columns) -->
                    <div class="md:col-span-5 space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="block text-xs font-bold uppercase tracking-wider text-stone-600 dark:text-zinc-300">Jumlah Unit</label>
                            <span x-show="selectedProductId && selectedProductStock > 0" class="text-[10px] font-semibold text-stone-400 dark:text-zinc-400" x-cloak>
                                Sedia: <span class="text-[#0b6e4f] dark:text-emerald-400 font-bold" x-text="selectedProductStock"></span>
                            </span>
                        </div>
                        <div class="flex items-center gap-3 bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 p-1.5 rounded-xl border border-stone-200/60 dark:border-zinc-800/80 w-full h-[46px]">
                            <button type="button" 
                                    @click="if(quantity > 1) quantity--" 
                                    class="w-10 h-10 bg-white dark:bg-zinc-900 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800 text-stone-850 dark:text-white rounded-lg flex items-center justify-center border border-stone-200/60 dark:border-zinc-800/80 shadow-sm font-black text-sm active:scale-95 transition-all cursor-pointer">
                                -
                            </button>
                            <input type="number" 
                                   name="quantity" 
                                   x-model.number="quantity" 
                                   min="1" 
                                   :max="selectedProductStock"
                                   @input="if(quantity > selectedProductStock) quantity = selectedProductStock; if(quantity < 1) quantity = 1;"
                                   class="flex-1 text-center bg-transparent border-none text-lg font-black text-stone-850 dark:text-white outline-none w-10"
                                   required />
                            <button type="button" 
                                    @click="if(quantity < selectedProductStock) quantity++" 
                                    class="w-10 h-10 bg-white dark:bg-zinc-900 hover:bg-stone-50 dark:hover:bg-zinc-850 dark:hover:bg-zinc-800 text-stone-850 dark:text-white rounded-lg flex items-center justify-center border border-stone-200/60 dark:border-zinc-800/80 shadow-sm font-black text-sm active:scale-95 transition-all cursor-pointer">
                                +
                            </button>
                        </div>
                    </div>

                    <!-- Customer Dropdown (7 Columns) -->
                    <div id="tour-pos-customer" class="md:col-span-7 space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="text-xs font-bold uppercase tracking-wider text-stone-600 dark:text-zinc-300">Pilih Pelanggan</label>
                            <span x-show="paymentMethod === 'debt'" 
                                  class="text-[9px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200"
                                  x-cloak x-transition>
                                Wajib untuk Kasbon
                            </span>
                        </div>
                        <select name="customer_id" 
                                x-model="customerId"
                                :required="paymentMethod === 'debt'"
                                class="w-full bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl px-3 text-xs font-bold text-stone-800 dark:text-white focus:bg-white dark:focus:bg-zinc-900 focus:border-[#0b6e4f] dark:focus:border-emerald-500 focus:ring-4 focus:ring-[#0b6e4f]/10 dark:focus:ring-emerald-500/10 transition-all outline-none h-[46px] cursor-pointer">
                            <option value="">-- Umum / Walk-in Customer --</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->name }} ({{ $c->phone ?: 'No HP Kosong' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- 4. Payment Toggle & Details (Side-by-Side to reduce spacing) -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 pt-4 border-t border-stone-100 dark:border-zinc-800/60">
                    
                    <!-- Left: Tunai vs Piutang (6 Columns) -->
                    <div class="md:col-span-6 space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-600 dark:text-zinc-300">Tipe Pembayaran</label>
                        <div class="flex gap-2">
                            <!-- Tunai -->
                            <button type="button" 
                                    @click="paymentMethod = 'cash'"
                                    :class="paymentMethod !== 'debt' ? 'border-2 border-[#0b6e4f] dark:border-emerald-500 bg-emerald-50/30 text-stone-850 dark:text-white ring-4 ring-[#0b6e4f]/10 dark:ring-emerald-500/10 font-black' : 'border border-stone-200/60 dark:border-zinc-800/80 bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 text-stone-500 dark:text-zinc-400 hover:bg-stone-100/50 dark:hover:bg-zinc-800/50 font-semibold'"
                                    class="flex-1 flex items-center justify-center gap-2 h-[46px] rounded-xl cursor-pointer transition-all duration-300">
                                <span class="material-symbols-outlined text-base">payments</span>
                                <span class="text-xs tracking-tight">Tunai</span>
                            </button>
                            <!-- Piutang -->
                            <button type="button" 
                                    @click="paymentMethod = 'debt'"
                                    :class="paymentMethod === 'debt' ? 'border-2 border-amber-600 bg-amber-50/30 text-stone-850 dark:text-white ring-4 ring-amber-500/10 font-black' : 'border border-stone-200/60 dark:border-zinc-800/80 bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 text-stone-500 dark:text-zinc-400 hover:bg-stone-100/50 dark:hover:bg-zinc-800/50 font-semibold'"
                                    class="flex-1 flex items-center justify-center gap-2 h-[46px] rounded-xl cursor-pointer transition-all duration-300">
                                <span class="material-symbols-outlined text-base">menu_book</span>
                                <span class="text-xs tracking-tight">Piutang</span>
                            </button>
                        </div>
                    </div>

                    <!-- Right: Payment Details (6 Columns) -->
                    <div class="md:col-span-6 space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-stone-600 dark:text-zinc-300">Rincian Pembayaran</label>
                        
                        <!-- When Tunai is selected -->
                        <div x-show="paymentMethod !== 'debt'" class="flex gap-1.5 h-[46px] items-center" x-cloak x-transition>
                            <label class="flex-1 relative flex items-center justify-center h-full px-1.5 bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 hover:bg-stone-100/50 dark:hover:bg-zinc-800/50 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl cursor-pointer has-[:checked]:border-2 has-[:checked]:border-[#0b6e4f] dark:has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/30 has-[:checked]:text-[#0b6e4f] dark:has-[:checked]:text-emerald-400 text-[10px] font-bold text-stone-750 dark:text-zinc-50 transition-all select-none">
                                <input type="radio" name="payment_method" value="cash" checked :disabled="paymentMethod === 'debt'" class="hidden">
                                Cash
                            </label>
                            <label class="flex-1 relative flex items-center justify-center h-full px-1.5 bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 hover:bg-stone-100/50 dark:hover:bg-zinc-800/50 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl cursor-pointer has-[:checked]:border-2 has-[:checked]:border-[#0b6e4f] dark:has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/30 has-[:checked]:text-[#0b6e4f] dark:has-[:checked]:text-emerald-400 text-[10px] font-bold text-stone-750 dark:text-zinc-50 transition-all select-none">
                                <input type="radio" name="payment_method" value="transfer" :disabled="paymentMethod === 'debt'" class="hidden">
                                Transfer
                            </label>
                            <label class="flex-1 relative flex items-center justify-center h-full px-1.5 bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 hover:bg-stone-100/50 dark:hover:bg-zinc-800/50 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl cursor-pointer has-[:checked]:border-2 has-[:checked]:border-[#0b6e4f] dark:has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/30 has-[:checked]:text-[#0b6e4f] dark:has-[:checked]:text-emerald-400 text-[10px] font-bold text-stone-750 dark:text-zinc-50 transition-all select-none">
                                <input type="radio" name="payment_method" value="qris" :disabled="paymentMethod === 'debt'" class="hidden">
                                QRIS
                            </label>
                        </div>
                        
                        <!-- When Piutang is selected (Due Date input & suggestions) -->
                        <div x-show="paymentMethod === 'debt'" class="space-y-2" x-cloak x-transition>
                            <input type="date" 
                                   name="due_date" 
                                   x-model="dueDate"
                                   :required="paymentMethod === 'debt'"
                                   :disabled="paymentMethod !== 'debt'"
                                   min="{{ date('Y-m-d') }}"
                                   class="w-full bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl px-3 text-xs font-bold text-stone-800 dark:text-white focus:bg-white dark:focus:bg-zinc-900 focus:border-[#0b6e4f] dark:focus:border-emerald-500 focus:ring-4 focus:ring-[#0b6e4f]/10 dark:focus:ring-emerald-500/10 transition-all outline-none h-[46px]" />
                            <div class="flex gap-1.5">
                                <button type="button" 
                                        @click="setDueDateDays(7)"
                                        class="flex-1 py-1.5 bg-stone-100 dark:bg-zinc-800 hover:bg-stone-200 dark:hover:bg-zinc-700 text-stone-600 dark:text-zinc-300 rounded-lg text-[10px] font-bold transition-all cursor-pointer">
                                    +7 Hari
                                </button>
                                <button type="button" 
                                        @click="setDueDateDays(14)"
                                        class="flex-1 py-1.5 bg-stone-100 dark:bg-zinc-800 hover:bg-stone-200 dark:hover:bg-zinc-700 text-stone-600 dark:text-zinc-300 rounded-lg text-[10px] font-bold transition-all cursor-pointer">
                                    +14 Hari
                                </button>
                                <button type="button" 
                                        @click="setDueDateDays(30)"
                                        class="flex-1 py-1.5 bg-stone-100 dark:bg-zinc-800 hover:bg-stone-200 dark:hover:bg-zinc-700 text-stone-600 dark:text-zinc-300 rounded-lg text-[10px] font-bold transition-all cursor-pointer">
                                    +30 Hari
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden Input For Debt Type -->
                <input type="hidden" name="payment_method" value="debt" x-bind:disabled="paymentMethod !== 'debt'" />
            </form>
        </div>

        <!-- RIGHT: Checkout Summary (5 Columns) -->
        <div class="lg:col-span-5 space-y-4">
            
            <!-- Real-time Cart Summary Card (Receipt Slip style) -->
            <div id="tour-pos-cart" class="bg-white dark:bg-zinc-900 rounded-[1.5rem] shadow-sm border border-stone-200/60 dark:border-zinc-800/80 overflow-hidden">
                <div class="p-4 bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 border-b border-stone-200/60 dark:border-zinc-800/80 flex justify-between items-center">
                    <h3 class="text-[10px] font-bold uppercase tracking-widest text-stone-500 dark:text-zinc-400">Rincian Pembelian</h3>
                    <span class="px-2.5 py-0.5 bg-emerald-50 text-[#0b6e4f] dark:text-emerald-400 text-[9px] font-bold rounded-full">Struk Pembayaran</span>
                </div>
                
                <div class="p-4 space-y-4">
                    <!-- Item Line -->
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex gap-2.5">
                            <div class="w-10 h-10 bg-stone-50 dark:bg-zinc-850 dark:bg-zinc-800 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl flex items-center justify-center text-stone-400 dark:text-zinc-400 flex-shrink-0">
                                <span class="material-symbols-outlined text-xl">bakery_dining</span>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-stone-850 dark:text-white" x-text="selectedProductId ? 'Item Terpilih' : 'Belum Ada Produk'"></p>
                                <p class="text-[10px] font-semibold text-stone-400 dark:text-zinc-400 mt-0.5" x-text="selectedProductId ? 'Siap Checkout' : 'Sentuh kartu produk di sebelah kiri'"></p>
                            </div>
                        </div>
                        <div class="text-right" x-show="selectedProductId">
                            <p class="text-xs font-bold text-stone-800 dark:text-white" x-text="formatRupiah(selectedProductPrice)"></p>
                            <p class="text-[10px] font-semibold text-stone-400 dark:text-zinc-400 mt-0.5" x-text="'x ' + quantity + ' pcs'"></p>
                        </div>
                    </div>

                    <!-- Separator -->
                    <div class="border-t border-dashed border-stone-200 dark:border-zinc-800"></div>

                    <!-- Bill details -->
                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between font-semibold text-stone-500 dark:text-zinc-400">
                            <span>Subtotal</span>
                            <span x-text="formatRupiah(totalBill)"></span>
                        </div>
                        <div class="flex justify-between font-semibold text-stone-500 dark:text-zinc-400">
                            <span>Diskon / Promo</span>
                            <span>Rp 0</span>
                        </div>
                        <div class="flex justify-between font-semibold text-stone-500 dark:text-zinc-400">
                            <span>Status Bayar</span>
                            <span :class="paymentMethod === 'debt' ? 'text-amber-650 dark:text-amber-400' : 'text-emerald-700 dark:text-emerald-400'" 
                                  class="font-bold uppercase text-[10px]" 
                                  x-text="paymentMethod === 'debt' ? 'Piutang / Kasbon' : 'Lunas'"></span>
                        </div>
                        <div x-show="paymentMethod === 'debt'" class="flex justify-between font-semibold text-stone-500 dark:text-zinc-400" x-cloak x-transition>
                            <span>Jatuh Tempo</span>
                            <span class="font-bold text-amber-700 dark:text-amber-400" x-text="dueDate"></span>
                        </div>
                    </div>

                    <!-- Grand Total Banner -->
                    <div class="p-3 bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 rounded-xl flex justify-between items-center border border-stone-200/60 dark:border-zinc-800/80">
                        <div>
                            <p class="text-[8px] font-black uppercase tracking-wider text-stone-400 dark:text-zinc-400">Total Tagihan</p>
                            <p class="text-xl font-bold text-stone-850 dark:text-white mt-0.5" x-text="formatRupiah(totalBill)"></p>
                        </div>
                        <div class="px-3 py-1.5 bg-amber-500/10 text-amber-800 text-[9px] font-bold rounded-lg border border-amber-500/10">
                            IDR / Rupiah
                        </div>
                    </div>

                    <!-- Checkout Submit Button - Placed at the bottom of the receipt card -->
                    <button type="submit" 
                            form="pos-form"
                            class="w-full text-white py-3.5 rounded-xl shadow-md shadow-[#0b6e4f]/10 dark:shadow-emerald-950/15 bg-[#0b6e4f] dark:bg-emerald-600 hover:bg-[#09523b] active:scale-[0.99] transition-all font-bold text-sm flex items-center justify-center gap-2 cursor-pointer">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                        <span>Konfirmasi & Cetak Nota</span>
                    </button>
                </div>
            </div>

            <!-- Transaksi Terakhir (Hari Ini) -->
            <div class="bg-white dark:bg-zinc-900 rounded-[1.5rem] shadow-sm border border-stone-200/60 dark:border-zinc-800/80 overflow-hidden">
                <div class="p-4 bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 border-b border-stone-200/60 dark:border-zinc-800/80 flex justify-between items-center">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-600 dark:text-zinc-300 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base text-[#0b6e4f] dark:text-emerald-400">history</span>
                        <span>Transaksi Terakhir (Hari Ini)</span>
                    </h3>
                    <span class="px-2 py-0.5 bg-[#0b6e4f]/10 text-[#0b6e4f] dark:text-emerald-400 text-[9px] font-bold rounded-full">5 Terbaru</span>
                </div>
                <div class="divide-y divide-stone-100 dark:divide-zinc-800/80">
                    @forelse($todaySales as $sale)
                        <div class="p-3.5 flex items-center justify-between gap-4 hover:bg-stone-50/50 dark:hover:bg-zinc-850/30 dark:hover:bg-zinc-800/30 transition-colors">
                            <div class="space-y-0.5 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs font-bold text-zinc-800 dark:text-white truncate">#{{ $sale->id }}</span>
                                    <span class="text-[9px] font-semibold text-stone-400 dark:text-zinc-400">{{ $sale->created_at->format('H:i') }}</span>
                                </div>
                                <p class="text-[10px] text-stone-500 dark:text-zinc-350 truncate">
                                    👤 {{ $sale->customer ?: 'Umum / Walk-in' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <div class="text-right">
                                    <p class="text-xs font-bold text-zinc-800 dark:text-white">Rp {{ number_format($sale->total, 0, ',', '.') }}</p>
                                    <span class="inline-block text-[8px] font-black uppercase px-1.5 py-0.5 rounded border
                                        {{ $sale->payment_method !== 'debt' 
                                            ? 'bg-emerald-50 text-[#0b6e4f] border-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/40' 
                                            : 'bg-amber-50 text-amber-850 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/40' }}">
                                        {{ $sale->payment_method !== 'debt' ? 'Lunas' : 'Kasbon' }}
                                    </span>
                                </div>
                                <a href="{{ route('sales.receipt', $sale->id) }}" 
                                   target="_blank" 
                                   class="w-8 h-8 rounded-lg bg-stone-100 dark:bg-zinc-800 hover:bg-stone-200 dark:hover:bg-zinc-700 text-stone-600 dark:text-zinc-300 flex items-center justify-center transition-all cursor-pointer"
                                   title="Cetak Nota">
                                    <span class="material-symbols-outlined text-[16px]">print</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-stone-400 dark:text-zinc-400 space-y-1">
                            <span class="material-symbols-outlined text-2xl opacity-40">receipt_long</span>
                            <p class="text-[10px] font-bold">Belum ada transaksi hari ini</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- CRM: Add New Customer Modal (AlpineJS) -->
    <div x-show="isNewCustomerModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         x-cloak>
        
        <div class="bg-white dark:bg-zinc-900 p-6 sm:p-8 rounded-[1.5rem] shadow-xl border border-stone-200/60 dark:border-zinc-800/80 max-w-md w-full mx-4 relative"
             @click.away="isNewCustomerModalOpen = false">
            
            <!-- Close Button -->
            <button @click="isNewCustomerModalOpen = false" 
                    class="absolute top-4 right-4 text-stone-400 dark:text-white hover:text-stone-600 dark:hover:text-zinc-400 transition-colors cursor-pointer">
                <span class="material-symbols-outlined">close</span>
            </button>

            <!-- Header -->
            <div class="mb-6 space-y-1">
                <h3 class="text-lg font-bold text-stone-850 dark:text-white">Daftarkan Pelanggan Baru</h3>
                <p class="text-[10px] font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-wider">Layanan CRM Kasbon SAHAYU</p>
            </div>

            <!-- Form -->
            <form action="{{ route('customers.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-450 dark:text-zinc-400">Nama Pelanggan / Toko</label>
                    <input type="text" 
                           name="name" 
                           required 
                           placeholder="Contoh: Toko Berkah Mandiri"
                           class="w-full bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl p-3 text-sm focus:bg-white dark:focus:bg-zinc-900 focus:border-[#0b6e4f] dark:focus:border-emerald-500 focus:ring-4 focus:ring-[#0b6e4f]/10 dark:focus:ring-emerald-500/10 transition-all outline-none font-semibold text-stone-800 dark:text-white" />
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-450 dark:text-zinc-400">No. WhatsApp / HP</label>
                    <input type="text" 
                           name="phone" 
                           placeholder="Contoh: 081234567890"
                           class="w-full bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl p-3 text-sm focus:bg-white dark:focus:bg-zinc-900 focus:border-[#0b6e4f] dark:focus:border-emerald-500 focus:ring-4 focus:ring-[#0b6e4f]/10 dark:focus:ring-emerald-500/10 transition-all outline-none font-semibold text-stone-800 dark:text-white" />
                </div>

                <div class="space-y-1">
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-stone-450 dark:text-zinc-400">Alamat Lengkap</label>
                    <textarea name="address" 
                              rows="3" 
                              placeholder="Alamat toko atau rumah pelanggan..."
                              class="w-full bg-stone-50/50 dark:bg-zinc-850/30 dark:bg-zinc-800/30 border border-stone-200/60 dark:border-zinc-800/80 rounded-xl p-3 text-sm focus:bg-white dark:focus:bg-zinc-900 focus:border-[#0b6e4f] dark:focus:border-emerald-500 focus:ring-4 focus:ring-[#0b6e4f]/10 dark:focus:ring-emerald-500/10 transition-all outline-none font-semibold text-stone-800 dark:text-white"></textarea>
                </div>

                <div class="pt-4 flex gap-3">
                    <button type="button" 
                            @click="isNewCustomerModalOpen = false"
                            class="flex-1 py-3 bg-stone-100 hover:bg-stone-200 dark:hover:bg-zinc-800 text-stone-700 dark:text-zinc-50 text-xs font-bold rounded-xl transition-all cursor-pointer">
                        Batalkan
                    </button>
                    <button type="submit" 
                            class="flex-1 py-3 text-white text-xs font-bold rounded-xl transition-all shadow-sm bg-[#0b6e4f] dark:bg-emerald-600 hover:bg-[#09523b] shadow-[#0b6e4f]/10 dark:shadow-emerald-950/15 cursor-pointer">
                        Simpan Pelanggan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CHECKOUT SUCCESS MODAL (AlpineJS) -->
    <div x-show="isSuccessModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         x-cloak>
        
        <div class="bg-white dark:bg-zinc-900 p-6 sm:p-8 rounded-[1.5rem] shadow-xl border border-stone-200/60 dark:border-zinc-800/80 max-w-sm w-full mx-4 text-center relative"
             @click.away="isSuccessModalOpen = false">
            
            <!-- Success Icon Animation -->
            <div class="mx-auto w-16 h-16 bg-emerald-50 text-[#0b6e4f] dark:text-emerald-400 rounded-full flex items-center justify-center mb-6 shadow-inner animate-bounce">
                <span class="material-symbols-outlined text-4xl">check_circle</span>
            </div>

            <!-- Header -->
            <div class="mb-6 space-y-2">
                <h3 class="text-xl font-bold text-stone-850 dark:text-white">Transaksi Berhasil!</h3>
                <p class="text-xs font-bold text-stone-400 dark:text-zinc-400 uppercase tracking-widest">Nota #{{ session('print_sale_id') }} Telah Dicatat</p>
            </div>

            <p class="text-stone-500 dark:text-white text-xs font-semibold leading-relaxed mb-8">
                Nota penjualan ini telah berhasil tersimpan dalam sistem ledger keuangan SAHAYU. Siap untuk dicetak ke thermal printer.
            </p>

            <!-- Actions -->
            <div class="flex flex-col gap-3">
                <a href="{{ route('sales.receipt', session('print_sale_id', 0)) }}" 
                   target="_blank"
                   @click="isSuccessModalOpen = false"
                   class="w-full py-4 text-white text-sm font-bold rounded-2xl transition-all shadow-md flex items-center justify-center gap-2 bg-[#0b6e4f] dark:bg-emerald-600 hover:bg-[#09523b] shadow-[#0b6e4f]/10 dark:shadow-emerald-950/15 cursor-pointer">
                    <span class="material-symbols-outlined text-lg">print</span>
                    <span>Cetak Struk Thermal</span>
                </a>
                <button type="button" 
                        @click="isSuccessModalOpen = false"
                        class="w-full py-4 bg-stone-100 hover:bg-stone-200 dark:hover:bg-zinc-800 text-stone-700 dark:text-zinc-50 text-sm font-bold rounded-2xl transition-all cursor-pointer">
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
        background-color: #e7e5e4;
        border-radius: 20px;
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btnStartTour = document.getElementById('btn-start-tour');
        if (!btnStartTour) return;

        btnStartTour.addEventListener('click', () => {
            const driver = window.driver.js.driver;
            
            const driverObj = driver({
                showProgress: true,
                steps: [
                    {
                        element: '#tour-pos-catalog',
                        popover: {
                            title: 'Katalog Produk & Pencarian',
                            description: 'Klik pada produk di daftar ini untuk menambahkannya ke keranjang. Anda bisa memfilter berdasarkan kategori atau mengetik nama produk untuk pencarian cepat.',
                            side: 'right',
                            align: 'start'
                        }
                    },
                    {
                        element: '#tour-pos-customer',
                        popover: {
                            title: 'Pelanggan Kasbon (Opsional)',
                            description: 'Bila pelanggan Anda ingin bayar tempo, wajib pilih namanya di sini agar sistem bisa melacak sisa utang/kasbon mereka.',
                            side: 'bottom',
                            align: 'start'
                        }
                    },
                    {
                        element: '#tour-pos-cart',
                        popover: {
                            title: 'Cek Keranjang & Checkout',
                            description: 'Rincian pesanan Anda muncul di struk virtual ini. Setelah semuanya benar, klik "Konfirmasi & Cetak Nota" di bagian paling bawah untuk menyelesaikan.',
                            side: 'left',
                            align: 'start'
                        }
                    }
                ]
            });

            driverObj.drive();
        });
    });
</script>
@endsection
