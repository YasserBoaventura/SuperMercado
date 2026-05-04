    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.modal').on('shown.bs.modal', function() { $(this).find('input:first').focus(); });
        setTimeout(function() { $('.alert').fadeOut('slow'); }, 3000);
    });
    </script>
</body>
</html>