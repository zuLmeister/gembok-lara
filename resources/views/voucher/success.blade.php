<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembelian Berhasil - Voucher Hotspot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-cyan-900 to-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl border border-white/20 p-8 text-center">
            <!-- Success Icon -->
            <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check text-white text-4xl"></i>
            </div>

            <h1 class="text-2xl font-bold text-white mb-2">Pembelian Berhasil!</h1>
            <p class="text-cyan-200 mb-6">Terima kasih atas pembelian Anda</p>

            <!-- Voucher Details -->
            <div class="bg-white/10 rounded-xl p-6 mb-6">
                <div class="mb-4">
                    <p class="text-cyan-300 text-sm">Kode Voucher</p>
                    <p class="text-3xl font-mono font-bold text-white tracking-wider">{{ $voucher->code ?? 'XXXX-XXXX-XXXX' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-cyan-300">Paket</p>
                        <p class="text-white font-medium">{{ $voucher->package->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-cyan-300">Durasi</p>
                        <p class="text-white font-medium">{{ $voucher->package->duration ?? 0 }} Hari</p>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Notice -->
            <div class="bg-green-500/20 border border-green-500/50 rounded-lg p-4 mb-6">
                <div class="flex items-center justify-center text-green-400">
                    <i class="fab fa-whatsapp text-2xl mr-2"></i>
                    <span class="text-sm">Kode voucher juga telah dikirim ke WhatsApp Anda</span>
                </div>
            </div>

            <!-- Instructions -->
            <div class="text-left bg-white/5 rounded-lg p-4 mb-6">
                <h3 class="text-white font-semibold mb-2">Cara Menggunakan:</h3>
                <ol class="text-cyan-200 text-sm space-y-2">
                    <li>1. Hubungkan ke WiFi Hotspot</li>
                    <li>2. Buka browser dan akses halaman login</li>
                    <li>3. Masukkan kode voucher di atas</li>
                    <li>4. Nikmati internet!</li>
                </ol>
            </div>

            <!-- Actions -->
            <div class="space-y-3">
                <a href="{{ route('voucher.buy') }}" class="block w-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white py-3 rounded-lg font-semibold hover:from-cyan-600 hover:to-blue-700 transition">
                    <i class="fas fa-shopping-cart mr-2"></i>Beli Lagi
                </a>
                <a href="/" class="block w-full bg-white/10 text-white py-3 rounded-lg font-semibold hover:bg-white/20 transition">
                    <i class="fas fa-home mr-2"></i>Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</body>
</html>
