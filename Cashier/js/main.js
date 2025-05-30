function cartApp() {
    return {
        open: false,
        cart: [],
        total: 0,
        products: [],
        keyword: '',

        init() {
            this.loadProducts();
            this.loadCart();
            this.updateTotal();
        },

        loadProducts() {
            fetch('server/product_crud.php?action=list')
                .then(res => res.json())
                .then(data => {
                    this.products = data;
                })
                .catch(err => {
                    console.error("Gagal memuat produk:", err);
                });
        },

        loadCart() {
            const savedCart = localStorage.getItem('cart');
            if (savedCart) {
                this.cart = JSON.parse(savedCart);
            }
        },

        saveCart() {
            localStorage.setItem('cart', JSON.stringify(this.cart));
        },

        formatRupiah(amount) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR'
            }).format(amount);
        },

        addToCart(product) {
            const existing = this.cart.find(item => item.id == product.id);

            if (existing) {
                if (existing.quantity + 1 > product.quantity) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Stok Tidak Cukup',
                        text: `Stok untuk ${product.title} hanya tersedia ${product.quantity}.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    return;
                }

                existing.quantity += 1;

                Swal.fire({
                    icon: 'success',
                    title: 'Jumlah Diperbarui',
                    text: `Jumlah ${product.title} diperbarui di Cart.`,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                if (product.quantity < 1) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Stok Habis',
                        text: `${product.title} sedang tidak tersedia.`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    return;
                }

                this.cart.push({ ...product, quantity: 1 });

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: `${product.title} ditambahkan ke Cart.`,
                    timer: 1500,
                    showConfirmButton: false
                });
            }

            this.saveCart();
            this.updateTotal();
            this.open = true;
        },

        removeItem(index) {
            const removed = this.cart[index];
            this.cart.splice(index, 1);
            this.saveCart();
            this.updateTotal();

            Swal.fire({
                icon: 'info',
                title: 'Dihapus',
                text: `${removed.title} telah dihapus dari Cart.`,
                timer: 1500,
                showConfirmButton: false
            });
        },

        updateTotal() {
            this.total = this.cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
        },

        bayar() {
            if (this.cart.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cart Kosong',
                    text: 'Silakan tambahkan barang terlebih dahulu.'
                });
                return;
            }

            // Validasi stok sebelum bayar
            for (let item of this.cart) {
                const currentProduct = this.products.find(p => p.id == item.id);
                if (!currentProduct) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Produk Tidak Ditemukan',
                        text: `Produk dengan ID ${item.id} tidak tersedia.`
                    });
                    return;
                }

                if (item.quantity > currentProduct.quantity) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Stok Tidak Cukup',
                        text: `Stok untuk "${item.title}" hanya ${currentProduct.quantity}, tetapi Anda membeli ${item.quantity}.`,
                    });
                    return;
                }
            }

            // Jika validasi lolos, lanjutkan proses pembayaran
            fetch('server/transaction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cart: this.cart,
                    total: this.total
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.token) {
                    window.snap.pay(data.token, {
                        onSuccess: () => {
                            Swal.fire({
                                icon: 'success',
                                title: 'Pembayaran Berhasil!',
                                text: 'Terima kasih atas pembeliannya.'
                            });
                            this.cart = [];
                            this.saveCart();
                            this.updateTotal();
                        },
                        onPending: () => {
                            Swal.fire({
                                icon: 'info',
                                title: 'Menunggu Pembayaran',
                                text: 'Silakan selesaikan pembayaran Anda.'
                            });
                        },
                        onError: () => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Pembayaran Gagal',
                                text: 'Terjadi kesalahan saat pembayaran.'
                            });
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Transaksi Gagal',
                        text: 'Token pembayaran tidak tersedia.'
                    });
                    console.error(data);
                }
            })
            .catch(err => {
                console.error("Payment error:", err);
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Sistem',
                    text: 'Gagal memproses pembayaran.'
                });
            });
        }

    }
}
