<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
    <h2 class="mb-4">Dashboard Produk</h2>
    <a href="index.html" class="btn btn-secondary mb-3">← Kembali ke Halaman Utama</a>

    <!-- Form Tambah Produk -->
    <form id="productForm" enctype="multipart/form-data" class="mb-4">
        <input type="hidden" name="id" id="productId">
        <input type="hidden" name="existing_img" id="existingImg">

        <div class="mb-2">
            <label>Nama Produk</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Harga</label>
            <input type="number" name="price" id="price" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Stok</label>
            <input type="number" name="quantity" id="quantity" class="form-control" required min="1">
        </div>
        <div class="mb-2">
            <label>Gambar</label>
            <input type="file" name="img" id="img" class="form-control">
        </div>
        <button class="btn btn-success" type="submit">Simpan</button>
    </form>

    <!-- Tabel Produk -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Gambar</th>
                <th>Nama</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="productTable"></tbody>
    </table>

<script>
function fetchProducts() {
    fetch('server/product_crud.php?action=list')
        .then(res => res.json())
        .then(data => {
            let html = '';
            data.forEach(p => {
                html += `<tr>
                    <td><img src="${p.img}" width="60"></td>
                    <td>${p.title}</td>
                    <td>${p.price}</td>
                    <td>${p.quantity}</td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick="editProduct(${JSON.stringify(p).replace(/"/g, '&quot;')})">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id})">Delete</button>
                    </td>
                </tr>`;
            });
            document.getElementById('productTable').innerHTML = html;
        });
}

document.getElementById('productForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const quantity = parseInt(document.getElementById('quantity').value);
    if (isNaN(quantity) || quantity < 1) {
        alert("Stok harus lebih dari 0.");
        return;
    }

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
            form.set('id', '');
            fetchProducts();
        }
      });
});

function editProduct(p) {
    document.getElementById('productId').value = p.id;
    document.getElementById('title').value = p.title;
    document.getElementById('price').value = p.price;
    document.getElementById('quantity').value = p.quantity;
    document.getElementById('existingImg').value = p.img;
}

function deleteProduct(id) {
    if (confirm('Yakin ingin menghapus produk?')) {
        const form = new FormData();
        form.append('id', id);
        fetch('server/product_crud.php?action=delete', {
            method: 'POST',
            body: form
        }).then(() => fetchProducts());
    }
}

fetchProducts();
</script>
</body>
</html>
