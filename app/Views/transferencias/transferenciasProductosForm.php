<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Listado de Productos' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .product-item {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .product-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body class="bg-gray-100">

    <div class="container mx-auto px-4 py-8">
        <div class="card shadow-lg rounded-xl">
            <div class="card-header bg-primary text-white p-4 rounded-t-xl">
                <h1 class="text-3xl font-bold"><?= $title ?? 'Listado de Productos' ?></h1>
            </div>
          
        </div>
    </div>

    <div class="modal fade" id="addProductsModal" tabindex="-1" aria-labelledby="addProductsModalLabel" aria-modal="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-xl shadow-2xl">
                <div class="modal-header bg-gradient-to-r from-blue-500 to-indigo-600 text-white p-4 rounded-t-xl">
                    <h5 class="modal-title font-bold text-xl" id="addProductsModalLabel">Añadir Productos al Envío</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-6">
                    <form id="productSearchForm" class="row g-3 mb-4">
                        <div class="col-12">
                            <label for="productSearchInput" class="form-label font-medium text-gray-700">Buscar Producto</label>
                            <input type="text" class="form-control rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" id="productSearchInput" placeholder="Escribe el nombre del producto...">
                        </div>
                    </form>

                    <div id="searchResults" class="list-group mb-4 max-h-64 overflow-y-auto border rounded-lg shadow-sm">
                        </div>

                    <hr class="my-6 border-t-2 border-gray-200">

                    <h5 class="text-lg font-semibold text-gray-800 mb-4">Productos en el Carrito</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle rounded-lg shadow-sm" id="itemsTable">
                            <thead class="bg-gray-100">
                                <tr class="text-gray-600 font-semibold">
                                    <th scope="col">Producto</th>
                                    <th scope="col">Cantidad</th>
                                    <th scope="col">Precio Contado</th>
                                    <th scope="col">Precio Crédito</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-gray-50 p-4 rounded-b-xl">
                    <button type="button" class="btn btn-secondary rounded-lg" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary rounded-lg" id="confirmarEnvioBtn">Confirmar Envío</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // Reemplaza los datos estáticos con los datos del backend
            const products = <?= json_encode($productos) ?>;

            const searchInput = document.getElementById('productSearchInput');
            const searchResultsContainer = document.getElementById('searchResults');
            const itemsTableBody = document.getElementById('itemsTableBody');
            const confirmarEnvioBtn = document.getElementById('confirmarEnvioBtn');

            // Función para renderizar los resultados de la búsqueda
            const renderSearchResults = (query = '') => {
                const normalizedQuery = query.toLowerCase().trim();
                const filteredProducts = products.filter(product =>
                    product.nombre.toLowerCase().includes(normalizedQuery)
                );

                searchResultsContainer.innerHTML = '';
                if (filteredProducts.length === 0) {
                    searchResultsContainer.innerHTML = '<div class="p-3 text-center text-gray-500">No se encontraron productos.</div>';
                    return;
                }

                filteredProducts.forEach(product => {
                    const div = document.createElement('div');
                    div.className = 'list-group-item list-group-item-action product-item d-flex justify-content-between items-center py-3 px-4';
                    div.innerHTML = `
                        <div class="flex-grow">
                            <h6 class="mb-1 font-semibold">${product.nombre}</h6>
                            <small class="text-gray-500">Stock: ${product.stock}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-success rounded-full" data-product-id="${product.id}" onclick="event.stopPropagation(); addItemToCart(${product.id});">
                            <i class="ri-add-line align-middle"></i>
                        </button>
                    `;
                    searchResultsContainer.appendChild(div);
                });
            };

            // Listener para el input de búsqueda
            searchInput.addEventListener('input', (e) => {
                renderSearchResults(e.target.value);
            });

            // Función para añadir un producto al carrito
            window.addItemToCart = (productId) => {
                const product = products.find(p => p.id == productId);
                if (!product) return;

                // Verificar si el producto ya está en el carrito
                const existingRow = itemsTableBody.querySelector(`tr[data-product-id="${product.id}"]`);
                if (existingRow) {
                    // Si existe, actualizar la cantidad
                    let quantityCell = existingRow.querySelector('.quantity-cell');
                    let newQuantity = parseInt(quantityCell.textContent) + 1;
                    if (newQuantity > product.stock) {
                         alert('No se puede añadir más stock de este producto.');
                         return;
                    }
                    quantityCell.textContent = newQuantity;
                } else {
                    // Si no, añadir una nueva fila
                    if (product.stock < 1) {
                         alert('No hay stock disponible para este producto.');
                         return;
                    }
                    const row = document.createElement('tr');
                    row.dataset.productId = product.id;
                    row.innerHTML = `
                        <td data-field="name">${product.nombre}</td>
                        <td class="quantity-cell" data-field="cantidad">1</td>
                        <td data-field="precio_contado">${parseFloat(product.precio_contado).toFixed(2)}</td>
                        <td data-field="precio_credito">${parseFloat(product.precio_credito).toFixed(2)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger remove-item-btn rounded-full">
                                <i class="ri-delete-bin-fill"></i>
                            </button>
                        </td>
                    `;
                    itemsTableBody.appendChild(row);
                }
            };

            // Listener para el botón de eliminar del carrito
            itemsTableBody.addEventListener('click', (e) => {
                if (e.target.closest('.remove-item-btn')) {
                    const row = e.target.closest('tr');
                    row.remove();
                }
            });

            // Listener para el botón de confirmar envío
            confirmarEnvioBtn.addEventListener('click', () => {
                const productosParaEnvio = [];
                const rows = itemsTableBody.querySelectorAll('tr');

                rows.forEach(row => {
                    const productId = row.dataset.productId;
                    const cantidad = parseInt(row.querySelector('.quantity-cell').textContent);
                    const precioContado = parseFloat(row.querySelector('[data-field="precio_contado"]').textContent);
                    const precioCredito = parseFloat(row.querySelector('[data-field="precio_credito"]').textContent);

                    productosParaEnvio.push({
                        id: productId,
                        cantidad: cantidad,
                        precio_contado: precioContado,
                        precio_credito: precioCredito
                    });
                });

                // Aquí iría la lógica para enviar los datos al backend (por ejemplo, con fetch)
                console.log('Productos a enviar:', productosParaEnvio);

                // Cierra el modal después de procesar
                const modal = bootstrap.Modal.getInstance(document.getElementById('addProductsModal'));
                modal.hide();
            });

            // Renderizar la lista completa al inicio
            renderSearchResults();
        });
    </script>

</body>

</html>