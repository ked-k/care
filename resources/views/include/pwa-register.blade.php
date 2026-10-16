{{--
    Batch 24: registers public/sw.js (a static-asset-only cache — see that
    file's own doc comment) so the manifest link each layout now carries
    actually makes the page installable, not just "has a manifest file
    lying around unused". Shared by layouts.admin-layout, layouts.carer and
    layouts.family rather than duplicated three times.

    Deliberately not inside include/script.blade.php — that file is a
    page-specific @stack target (DataTables, charts, pickers), and this
    needs to run on every page regardless of what any individual page
    pushes there.
--}}
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {
                // Registration failing (e.g. served over plain HTTP on a
                // LAN IP, which browsers don't treat as a secure context)
                // should never break the page — it just means no install
                // prompt / no asset caching, not an app-breaking error.
            });
        });
    }
</script>
