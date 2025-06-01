// Variabel global untuk menyimpan data produk seluruhnya
let allProducts = [];

// Fungsi mengambil produk dari server dan menyimpan ke allProducts lalu render ke tabel
function fetchProducts() {
  fetch('server/product_crud.php?action=list')
    .then(res => res.json())
    .then(data => {
      allProducts = data;
      renderProducts(allProducts);
    })
    .catch(err => {
      console.error('Gagal memuat produk:', err);
    });
}

// Fungsi untuk render array produk ke dalam tabel HTML
function renderProducts(products) {
  let html = '';
  products.forEach(p => {
    html += `<tr>
      <td><img src="${p.img}" width="60" class="img-thumbnail"></td>
      <td>${p.title}</td>
      <td>Rp ${parseInt(p.price).toLocaleString()}</td>
      <td>${p.quantity} <br></td>
      <td>
        <button class="btn btn-warning btn-sm me-1" onclick="editProduct(${JSON.stringify(p).replace(/"/g, '&quot;')})">✏️ Edit</button>
        <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id})">🗑️ Hapus</button>
      </td>
    </tr>`;
  });
  document.getElementById('productTable').innerHTML = html;
}

// Fungsi filter produk berdasarkan awalan nama yang diinputkan user di search input
function filterProductsByStart() {
  const keyword = document.getElementById('searchInput').value.trim().toLowerCase();

  const filtered = allProducts.filter(p =>
    p.title.toLowerCase().startsWith(keyword)
  );

  renderProducts(filtered);
}

// Huruf Kapital
function capitalizeFirstLetter(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}


// Event listener form submit tambah/edit produk
document.getElementById('productForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const quantity = parseInt(document.getElementById('quantity').value);
  if (isNaN(quantity) || quantity < 1) {
    alert("Stok harus lebih dari 0.");
    return;
  }

  // Kapitalisasi huruf pertama nama produk
  const titleInput = document.getElementById('title');
  titleInput.value = capitalizeFirstLetter(titleInput.value.trim());

  const form = new FormData(this);
  const isUpdate = form.get('id') !== '';

  fetch(`server/product_crud.php?action=${isUpdate ? 'update' : 'add'}`, {
    method: 'POST',
    body: form
  }).then(res => res.json())
    .then(data => {
      if (data.status === 'error') {
        alert(data.message);
      } else {
        this.reset();
        document.getElementById('productId').value = '';
        document.getElementById('existingImg').value = '';
        fetchProducts();
      }
    })
    .catch(err => {
      console.error('Gagal menyimpan produk:', err);
    });
});


// Fungsi mengisi form untuk edit produk
function editProduct(p) {
  document.getElementById('productId').value = p.id;
  document.getElementById('title').value = p.title;
  document.getElementById('price').value = p.price;
  document.getElementById('quantity').value = p.quantity;
  document.getElementById('existingImg').value = p.img;
}

// Fungsi hapus produk
function deleteProduct(id) {
  const product = allProducts.find(p => p.id == id);

  Swal.fire({
    title: `Hapus produk "${product.title}"?`,
    text: "Data tidak bisa dikembalikan setelah dihapus.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '🗑️ Ya, Hapus',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      const form = new FormData();
      form.append('id', id);
      fetch('server/product_crud.php?action=delete', {
        method: 'POST',
        body: form
      })
      .then(() => {
        fetchProducts();
        Swal.fire({
          icon: 'success',
          title: 'Produk Dihapus',
          text: `"${product.title}" telah berhasil dihapus.`,
          timer: 1500,
          showConfirmButton: false
        });
      })
      .catch(err => {
        console.error('Gagal menghapus produk:', err);
        Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: 'Terjadi kesalahan saat menghapus produk.'
        });
      });
    }
  });
}


// Event listener untuk input search realtime
document.getElementById('searchInput').addEventListener('input', filterProductsByStart);

// Panggil awal untuk fetch data produk
fetchProducts();
