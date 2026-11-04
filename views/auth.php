<main class="auth-shell">
    <section class="auth-story">
        <div class="brand-lockup"><span class="brand-mark" aria-hidden="true">◔</span><span>WhatsApp</span></div>
        <div class="story-copy">
            <p class="eyebrow">RUANG KECIL, KABAR BESAR</p>
            <h1>Semua yang dekat,<br><em>tetap terasa dekat.</em></h1>
            <p class="story-description">Tempat percakapan sehari-hari menemukan jalannya pulang.</p>
        </div>
        <div class="story-visual" aria-hidden="true">
            <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div>
            <div class="visual-note note-one"><span class="note-dot dot-coral"></span> sudah sampai rumah 🌿</div>
            <div class="visual-note note-two">ketemu di sana! <span class="note-time">17.42</span></div>
            <div class="visual-note note-three"><span class="voice-line"></span><span class="voice-line short"></span><span class="voice-line tall"></span><span class="voice-line"></span><span class="voice-time">0:08</span></div>
            <div class="visual-sun"></div>
        </div>
        <div class="story-footer"><span>Pesan dengan tenang.</span><span>01 / 03</span></div>
    </section>
    <section class="auth-panel">
        <div class="auth-panel-top"><span>Selamat datang</span><span class="secure-label"><span class="secure-dot"></span> Koneksi aman</span></div>
        <div class="auth-card">
            <div class="auth-heading"><p class="eyebrow" id="authEyebrow">AKUN WHATSAPP</p><h2 id="authTitle">Masuk kembali</h2><p id="authSubtitle">Lanjutkan percakapan yang berarti.</p></div>
            <form id="loginForm" class="auth-form">
                <label>Email<input type="email" name="email" autocomplete="email" placeholder="nama@email.com" required></label>
                <label>Kata sandi<input type="password" name="password" autocomplete="current-password" placeholder="Minimal 8 karakter" minlength="8" required></label>
                <button class="button button-primary full-button" type="submit">Masuk <span aria-hidden="true">↗</span></button>
            </form>
            <form id="registerForm" class="auth-form hidden">
                <label>Nama lengkap<input type="text" name="name" autocomplete="name" placeholder="Nama kamu" minlength="2" required></label>
                <label>Nomor telepon <span class="optional">opsional</span><input type="tel" name="phone" autocomplete="tel" placeholder="08xx xxxx xxxx"></label>
                <label>Email<input type="email" name="email" autocomplete="email" placeholder="nama@email.com" required></label>
                <label>Kata sandi<input type="password" name="password" autocomplete="new-password" placeholder="Minimal 8 karakter" minlength="8" required></label>
                <button class="button button-primary full-button" type="submit">Buat akun <span aria-hidden="true">↗</span></button>
            </form>
            <form id="verifyForm" class="auth-form hidden">
                <label>Kode verifikasi<input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="6 digit kode" pattern="[0-9]{6}" maxlength="6" required></label>
                <button class="button button-primary full-button" type="submit">Verifikasi akun <span aria-hidden="true">↗</span></button>
                <button class="text-button" id="resendCode" type="button">Kirim kode baru</button>
            </form>
            <div class="auth-switch" id="authSwitch">Belum punya akun? <button class="text-button" id="showRegister" type="button">Daftar</button></div>
            <div class="auth-switch hidden" id="authSwitchLogin">Sudah punya akun? <button class="text-button" id="showLogin" type="button">Masuk</button></div>
            <p class="auth-footnote">Dengan melanjutkan, kamu menyetujui ketentuan penggunaan dan kebijakan privasi.</p>
        </div>
        <div class="auth-panel-bottom"><span>INDONESIA · 2025</span><span>Pribadi, selalu.</span></div>
    </section>
</main>
<div class="modal hidden" id="verifyModal" aria-hidden="true"><div class="modal-backdrop" data-close-modal></div><section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="verifyTitle"><button class="icon-button modal-close" type="button" data-close-modal aria-label="Tutup">×</button><span class="modal-kicker">VERIFIKASI DEMO</span><h2 id="verifyTitle">Kode untuk akunmu</h2><p>Kode verifikasi dibuat lokal untuk demo. Masukkan kode berikut untuk melanjutkan.</p><div class="demo-code" id="demoCode"></div><button class="button button-primary full-button" id="continueVerify" type="button">Lanjutkan</button></section></div>
<div class="toast-region" id="toastRegion" aria-live="polite"></div>