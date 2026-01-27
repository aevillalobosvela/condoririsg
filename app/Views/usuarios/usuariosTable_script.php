<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
    $(document).ready(function() {
        // Función para filtrar la tabla
        function filtrarTabla() {
            var texto = $('#buscadorUsuarios').val().toLowerCase();
            var rol = $('#filtroRol').val().toLowerCase();
            var estado = $('#filtroEstado').val().toLowerCase();
            
            $('#tablaUsuarios tbody tr').each(function() {
                var fila = $(this);
                var textoFila = fila.text().toLowerCase();
                var rolFila = fila.find('td:eq(4)').text().toLowerCase();
                var estadoFila = fila.find('td:eq(7)').text().toLowerCase();
                
                var coincideTexto = texto === '' || textoFila.indexOf(texto) !== -1;
                var coincideRol = rol === '' || rolFila === rol;
                var coincideEstado = estado === '' || 
                                   (estado === 'activo' && estadoFila === 'activo') ||
                                   (estado === 'inactivo' && estadoFila === 'inactivo');
                
                if (coincideTexto && coincideRol && coincideEstado) {
                    fila.show();
                } else {
                    fila.hide();
                }
            });
        }

        // Event listeners para los filtros
        $('#buscadorUsuarios, #filtroRol, #filtroEstado').on('input change', filtrarTabla);

        // Inicializar tooltips de Bootstrap
        $('[title]').tooltip({
            trigger: 'hover',
            placement: 'top'
        });
    });

    // Función para cambiar estado
    function cambiarEstado(id, estado) {
        const accion = estado ? 'activar' : 'desactivar';
        if (confirm(`¿Seguro que quieres ${accion} este usuario?`)) {
            window.location.href = `<?= base_url('usuarios/cambiar-estado/') ?>${id}`;
        }
    }

    // Función para eliminar usuario
    function eliminarUsuario(id) {
        if (confirm("¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer.")) {
            window.location.href = `<?= base_url('usuarios/delete/') ?>${id}`;
        }
    }
</script>