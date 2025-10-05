<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Penerimaan Magang - Dinas Kebudayaan Provinsi Riau</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }
        .container {
            width: 100%;
            max-width: 21cm; /* A4 width */
            min-height: 29.7cm; /* A4 height */
            margin: 2.54cm auto;
            padding: 0 2.54cm;
            box-sizing: border-box;
            background-color: #fff;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        .header .logo-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .header img.logo-provinsi {
            max-width: 100px;
            position: absolute;
            left: 0;
        }
        .header img.logo-disbud {
            max-width: 100px;
            position: absolute;
            right: 0;
        }
        .header p.title {
            font-size: 14pt;
            font-weight: bold;
            margin: 4px 0;
            text-transform: uppercase;
        }
        .header p.subtitle {
            font-size: 11pt;
            margin: 4px 0;
        }
        .header hr {
            border: 2px solid #000;
            margin: 15px 0;
        }
        .content {
            line-height: 1.8;
            margin-bottom: 30px;
        }
        .content p {
            margin: 12px 0;
            font-size: 12pt;
        }
        .content strong.accepted {
            font-weight: bold;
            text-transform: uppercase;
            color: #006400; /* Dark green for emphasis */
        }
        .content .details {
            margin-left: 25px;
            margin-top: 10px;
        }
        .content table {
            width: 100%;
            max-width: 400px;
            border-collapse: collapse;
            margin-left: 25px;
            margin-top: 10px;
            font-size: 12pt;
        }
        .content table th,
        .content table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .content table th {
            background-color: #f5f5f5;
            font-weight: bold;
            width: 30%;
        }
        .signature {
            margin-top: 60px;
            text-align: right;
            position: relative;
            bottom: 0;
        }
        .signature p {
            margin: 6px 0;
            font-size: 11pt;
        }
        .signature img.qr-signature {
            width: 80px;
            height: 80px;
            border: 1px solid #000;
            margin: 12px 0;
        }
        @media print {
            body { margin: 0; }
            .container { width: 100%; min-height: 29.7cm; margin: 0; padding: 2.54cm; box-sizing: border-box; }
            @page { size: A4; margin: 2.54cm; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="{{ public_path('images/logo-provinsi.png') }}" alt="Logo Provinsi Riau" class="logo-provinsi">
                <img src="{{ public_path('images/logo-disbud.png') }}" alt="Logo Dinas Kebudayaan" class="logo-disbud">
            </div>
            <p class="title">PEMERINTAH PROVINSI RIAU</p>
            <p class="title">DINAS KEBUDAYAAN</p>
            <p class="subtitle">Jl. Jend. Sudirman No. 123, Pekanbaru, Riau</p>
            <p class="subtitle">Telp: (0761) 123456 | Email: dinas.kebudayaan@riau.go.id</p>
            <hr>
        </div>
        <div class="content">
            <p>Pekanbaru, {{ \Carbon\Carbon::parse($template->created_at)->format('d M Y') }}</p>
            <p>Nomor: {{ $template->nomer_surat ?? '-' }}<br>
            Lampiran: -<br>
            Perihal: Penerimaan Magang</p>
            <p>Kepada Yth:<br>
            <strong>{{ $mahasiswa_name }}</strong><br>
            NIM: {{ $nim }}<br>
            Di Tempat</p>
            <p>Dengan hormat,</p>
            <p>Bersama ini kami menyatakan bahwa pengajuan magang Anda telah <strong class="accepted">DITERIMA</strong> untuk mengikuti program magang di Dinas Kebudayaan Provinsi Riau dengan rincian sebagai berikut:</p>
            <div class="details">
                <table>
                    <tr>
                        <th>Bidang</th>
                        <td>{{ $bidang_diminati }}</td>
                    </tr>
                    <tr>
                        <th>Periode</th>
                        <td>{{ $tanggal_mulai }} s/d {{ $tanggal_selesai }}</td>
                    </tr>
                    <tr>
                        <th>Pembimbing</th>
                        <td>{{ $pembimbing_name }}</td>
                    </tr>
                </table>
            </div>
            <p>Surat ini dapat divalidasi dengan memindai QR code pada tanda tangan di bawah ini.</p>
            <p>Demikian surat ini disampaikan, atas perhatian dan kerja samanya kami ucapkan terima kasih.</p>
        </div>
        <div class="signature">
            <p>Kepala Dinas Kebudayaan Provinsi Riau</p>
            <img src="{{ $qr_code_path }}" alt="QR Code Tanda Tangan" class="qr-signature">
            <p>{{ $verified_by }}</p>
        </div>
    </div>
</body>
</html>
