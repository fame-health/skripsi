<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Magang - Dinas Kebudayaan Provinsi Riau</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 15px;
        }
        .certificate {
            width: 1050px;
            height: 720px;
            background: linear-gradient(135deg, #fffef7 0%, #fff9e6 100%);
            border: 6px double #d4a017;
            padding: 30px 40px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        /* Corner Ornaments */
        .corner-ornament {
            position: absolute;
            width: 80px;
            height: 80px;
            border: 2px solid #d4a017;
        }
        .corner-ornament.tl {
            top: 15px;
            left: 15px;
            border-right: none;
            border-bottom: none;
        }
        .corner-ornament.tr {
            top: 15px;
            right: 15px;
            border-left: none;
            border-bottom: none;
        }
        .corner-ornament.bl {
            bottom: 15px;
            left: 15px;
            border-right: none;
            border-top: none;
        }
        .corner-ornament.br {
            bottom: 15px;
            right: 15px;
            border-left: none;
            border-top: none;
        }
        /* Header */
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #15803d;
            margin-bottom: 10px;
        }
        .header img {
            width: 60px;
            height: 60px;
        }
        .header-text {
            text-align: center;
        }
        .header-text h1 {
            font-size: 16pt;
            color: #1e3a8a;
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .header-text h2 {
            font-size: 14pt;
            color: #1e3a8a;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .header-text p {
            font-size: 9pt;
            color: #333;
            line-height: 1.3;
        }
        /* Nomor Surat */
        .nomor-surat {
            text-align: center;
            font-size: 11pt;
            color: #000;
            margin-bottom: 20px;
            font-weight: bold;
        }
        /* Content */
        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            padding: 0 30px;
        }
        .certificate-title {
            font-size: 28pt;
            font-weight: bold;
            color: #15803d;
            text-transform: uppercase;
            margin-bottom: 15px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.1);
            letter-spacing: 2px;
        }
        .intro-text {
            font-size: 11pt;
            margin-bottom: 10px;
            color: #333;
        }
        .student-name {
            font-size: 20pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 10px 0 5px 0;
            text-transform: uppercase;
        }
        .student-nim {
            font-size: 10pt;
            color: #666;
            margin-bottom: 15px;
        }
        .description {
            font-size: 11pt;
            margin-bottom: 15px;
            color: #333;
            line-height: 1.5;
        }
        .details-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 15px 0;
        }
        .detail-item {
            text-align: left;
            font-size: 10pt;
            line-height: 1.6;
        }
        .detail-item strong {
            color: #15803d;
            font-weight: bold;
        }
        /* Signature */
        .signature-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 20px;
            padding: 0 40px;
        }
        .signature-box {
            text-align: center;
            width: 250px;
        }
        .signature-title {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .signature-date {
            font-size: 9pt;
            margin-bottom: 6px;
        }
        .qr-code {
            width: 70px;
            height: 70px;
            margin: 8px auto;
            border: 1px solid #333;
            background-color: #fff;
        }
        .signature-name {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 6px;
            text-decoration: underline;
        }
        .validation-note {
            text-align: center;
            font-size: 8pt;
            color: #666;
            font-style: italic;
            margin-top: 10px;
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .certificate {
                width: 297mm;
                height: 210mm;
                box-shadow: none;
                margin: 0;
            }
            @page {
                size: A4 landscape;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="certificate">
        <!-- Corner Ornaments -->
        <div class="corner-ornament tl"></div>
        <div class="corner-ornament tr"></div>
        <div class="corner-ornament bl"></div>
        <div class="corner-ornament br"></div>

        <!-- Header -->
        <div class="header">
            <img src="{{ public_path('images/logo-disbud.png') }}" alt="Logo Riau">
            <div class="header-text">
                <h1>Pemerintah Provinsi Riau</h1>
                <h2>Dinas Kebudayaan</h2>
                <p>Jl. Jend. Sudirman No. 123, Pekanbaru, Riau</p>
                <p>Telp: (0761) 123456 | Email: dinas.kebudayaan@riau.go.id</p>
            </div>
        </div>

        <!-- Nomor Surat -->
        <div class="nomor-surat">
            Nomor: {{ $nomer_surat ?? '070/DISBUD/'.date('Y').'/---' }}
        </div>

        <!-- Content -->
        <div class="content">
            <div class="certificate-title">SERTIFIKAT MAGANG</div>
            <p class="intro-text">Diberikan kepada:</p>
            <div class="student-name">{{ $mahasiswa_name }}</div>
            <div class="student-nim">NIM: {{ $nim }}</div>
            <p class="description">
                Telah menyelesaikan program magang di Dinas Kebudayaan Provinsi Riau<br>
                dengan rincian sebagai berikut:
            </p>
            <div class="details-container">
                <div class="detail-item">
                    <p><strong>Bidang Peminatan:</strong></p>
                    <p>{{ $bidang_diminati }}</p>
                </div>
                <div class="detail-item">
                    <p><strong>Periode Magang:</strong></p>
                    <p>{{ $tanggal_mulai }} s/d {{ $tanggal_selesai }}</p>
                </div>
                <div class="detail-item">
                    <p><strong>Pembimbing Lapangan:</strong></p>
                    <p>{{ $pembimbing_name }}</p>
                </div>
            </div>
        </div>

        <!-- Signature -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-title">Kepala Dinas Kebudayaan<br>Provinsi Riau</div>
                <div class="qr-code">
                    <img src="{{ $qr_code_path }}" alt="QR Code" style="width: 100%; height: 100%;">
                </div>
                <div class="signature-name">{{ $verified_by }}</div>
            </div>
            <div class="signature-box">
                <div class="signature-date">Pekanbaru, {{ $tanggal_verifikasi }}</div>
            </div>
        </div>

        <div class="validation-note">
            * Sertifikat ini dapat diverifikasi keasliannya dengan memindai QR Code di atas
        </div>
    </div>
</body>
</html>
