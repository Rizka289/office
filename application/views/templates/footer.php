</div>
                <!-- ============ Konten halaman (view spesifik) selesai ============ -->
            </main>
        </div>
    </div>

    <script>
        // ==== Helper Global CSRF (dipakai oleh SEMUA form AJAX di SEMUA halaman) ====
        // Cukup didefinisikan sekali di sini, tidak perlu ditulis ulang di tiap view.

        // Ambil object {csrf_token_name: hash} untuk request tanpa form (mis. Delete)
        function getCsrfData() {
            var data = {};
            data[$('#csrf_token_name').val()] = $('#csrf_token_hash').val();
            return data;
        }

        // CI3 regenerate CSRF hash baru tiap request selesai.
        // Update semua hidden field csrf di halaman supaya request berikutnya tidak ditolak.
        function refreshCsrf(newHash) {
            if (!newHash) return;
            $('#csrf_token_hash').val(newHash);
            $('.csrf-field').val(newHash);
        }
    </script>

</body>
</html>