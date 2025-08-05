    </div> <!-- End of main content -->
</div> <!-- End of row -->

<!-- Footer -->
<footer class="bg-light text-center text-muted py-3 mt-auto">
    <div class="container">
        <p class="mb-0">
            &copy; {{ date('Y') }} AGROEMSE - Agro Empaques y Servicios. 
            <span class="text-primary">Todos los derechos reservados.</span>
        </p>
        <small>Versión 1.0.0</small>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom Scripts -->
<script>
    // Add any custom JavaScript here
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

@stack('scripts')
</body>
</html>
