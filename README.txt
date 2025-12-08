Invoice App - Bintang Kejora 88
=================================

Cara menjalankan:
1. Ekstrak folder `invoice-app` ke folder htdocs (XAMPP) atau root webserver kamu.
2. Buat database MySQL dengan nama: BintangKejoraInvoice
   - Atau ubah DB_NAME di file config.php sesuai keinginan.
3. Import SQL schema yang tersedia di file `schema.sql`.
4. Letakkan library FPDF di `libs/fpdf/fpdf.php`.
   - Download dari: http://www.fpdf.org/
   - Jika tidak, sistem akan menampilkan pesan ketika mencoba generate PDF.
5. Buka di browser: http://localhost/invoice-app/  (atau sesuai BASE_URL)
6. Login dengan:
   - Username: admin
   - Password: password

Catatan:
- Password default disimpan hashed. Silakan ubah password setelah login.
- Jika ingin menambahkan Bootstrap lengkap, ganti file di assets/css/bootstrap.min.css dan assets/js/bootstrap.bundle.min.js
- Nama perusahaan dan alamat tercetak di PDF sesuai permintaan:
  Bintang Kejora 88
  Jalan desa dadap raya no.88 , Banten , Jawa Barat

Files included:
- Full PHP code for routing, auth, CRUD, invoice generation.
- schema.sql -> SQL schema to buat tabel dan seed data.

