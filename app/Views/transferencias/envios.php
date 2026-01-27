<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envío de Productos a Sucursal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="p-8">

    <div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow-lg">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Envío de Productos a Sucursal</h1>

        <!-- Formulario de Información General -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label for="responsable" class="block text-sm font-medium text-gray-700">Responsable del Traslado</label>
                <input type="text" id="responsable" name="responsable" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Nombre del responsable">
            </div>
            <div>
                <label for="inventario" class="block text-sm font-medium text-gray-700">Inventario de Origen</label>
                <select id="inventario" name="inventario" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">Seleccione un inventario...</option>
                    <!-- Las opciones se cargarán dinámicamente -->
                </select>
            </div>
        </div>
        
        <!-- Contenedor para la tabla de inventario y búsqueda -->
        <div id="inventario-section" class="mb-8 hidden">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Productos en Inventario</h2>
            <div class="relative mb-4">
                <input type="text" id="search-product" placeholder="Buscar producto por nombre o código..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl shadow-md">
                <table id="inventory-table" class="min-w-full bg-white rounded-xl">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Nombre</th>
                            <th class="px-4 py-2 text-left">Categoría</th>
                            <th class="px-4 py-2 text-left">Descripción</th>
                            <th class="px-4 py-2 text-left">P. Crédito</th>
                            <th class="px-4 py-2 text-left">P. Contado</th>
                            <th class="px-4 py-2 text-left">Stock</th>
                            <th class="px-4 py-2 text-center">Cantidad a Enviar</th>
                            <th class="px-4 py-2 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="inventory-body" class="divide-y divide-gray-200">
                        <!-- Las filas se llenarán con JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabla de Productos a Enviar -->
        <div id="shipping-section" class="mb-6 hidden">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Productos para Enviar</h2>
            <div class="overflow-x-auto rounded-xl shadow-md">
                <table id="shipping-table" class="min-w-full bg-white rounded-xl">
                    <thead class="bg-green-600 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Nombre</th>
                            <th class="px-4 py-2 text-left">Categoría</th>
                            <th class="px-4 py-2 text-left">Descripción</th>
                            <th class="px-4 py-2 text-left">P. Crédito</th>
                            <th class="px-4 py-2 text-left">P. Contado</th>
                            <th class="px-4 py-2 text-center">Cantidad</th>
                            <th class="px-4 py-2 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="shipping-body" class="divide-y divide-gray-200">
                        <!-- Las filas de envío se llenarán con JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Botón de Envío -->
        <div id="submit-button-container" class="mt-8 text-center hidden">
            <button id="submit-shipment" class="bg-indigo-600 text-white px-6 py-3 rounded-lg shadow-lg hover:bg-indigo-700 transition duration-300">
                <i class="fas fa-paper-plane mr-2"></i> Confirmar Envío
            </button>
        </div>
        
        <!-- Modal para mensajes -->
        <div id="message-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <div id="modal-icon" class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <i id="modal-icon-fa" class="fa-solid fa-check text-green-600 text-lg"></i>
                    </div>
                    <h3 id="modal-title" class="text-lg leading-6 font-medium text-gray-900 mt-2"></h3>
                    <div class="mt-2 px-7 py-3">
                        <p id="modal-message" class="text-sm text-gray-500"></p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <button id="modal-ok-btn" class="px-4 py-2 bg-indigo-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">OK</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Datos simulados (reemplaza esto con una llamada a tu API real)
            const mockData = {
                inventarios: [
                    { id: 1, name: 'Almacén Central', code: 'ALM-CEN' },
                    { id: 2, name: 'Inventario de Fábrica', code: 'INV-FAB' }
                ],
                productos: [
                    { id: 101, name: 'Leche Deslactosada', categoria: 'Lácteos', descripcion: 'Leche entera sin lactosa', precio_credito: 2.50, precio_contado: 2.20, stock: 500, inventario_id: 1, unidad: 'Litro' },
                    { id: 102, name: 'Yogur Griego', categoria: 'Yogures', descripcion: 'Yogur natural cremoso', precio_credito: 1.80, precio_contado: 1.60, stock: 350, inventario_id: 1, unidad: 'Unidad' },
                    { id: 103, name: 'Mantequilla sin sal', categoria: 'Mantequillas', descripcion: 'Mantequilla fresca sin sal', precio_credito: 3.20, precio_contado: 2.90, stock: 200, inventario_id: 2, unidad: 'Gramo' },
                    { id: 104, name: 'Queso Mozzarella', categoria: 'Quesos', descripcion: 'Queso para pizzas', precio_credito: 5.00, precio_contado: 4.50, stock: 150, inventario_id: 2, unidad: 'Kilogramo' }
                ],
                categorias: [
                    { id: 1, name: 'Lácteos' },
                    { id: 2, name: 'Yogures' },
                    { id: 3, name: 'Mantequillas' },
                    { id: 4, name: 'Quesos' }
                ],
                unidades: [
                    { id: 1, name: 'Litro' },
                    { id: 2, name: 'Unidad' },
                    { id: 3, name: 'Kilogramo' },
                    { id: 4, name: 'Gramo' }
                ]
            };

            const inventorySelect = document.getElementById('inventario');
            const inventorySection = document.getElementById('inventario-section');
            const inventoryBody = document.getElementById('inventory-body');
            const searchInput = document.getElementById('search-product');
            const shippingSection = document.getElementById('shipping-section');
            const shippingBody = document.getElementById('shipping-body');
            const submitBtnContainer = document.getElementById('submit-button-container');
            const submitBtn = document.getElementById('submit-shipment');
            const responsibleInput = document.getElementById('responsable');

            // Llenar el select de inventarios
            mockData.inventarios.forEach(inv => {
                const option = document.createElement('option');
                option.value = inv.id;
                option.textContent = `${inv.name} (${inv.code})`;
                inventorySelect.appendChild(option);
            });

            // Lógica para mostrar/ocultar secciones
            inventorySelect.addEventListener('change', () => {
                const selectedInventarioId = parseInt(inventorySelect.value);
                if (selectedInventarioId) {
                    inventorySection.classList.remove('hidden');
                    shippingSection.classList.remove('hidden');
                    submitBtnContainer.classList.remove('hidden');
                    renderInventoryTable(selectedInventarioId);
                } else {
                    inventorySection.classList.add('hidden');
                    shippingSection.classList.add('hidden');
                    submitBtnContainer.classList.add('hidden');
                }
            });

            // Función para renderizar la tabla de productos del inventario
            const renderInventoryTable = (inventarioId, searchQuery = '') => {
                inventoryBody.innerHTML = '';
                const filteredProducts = mockData.productos.filter(p => 
                    p.inventario_id === inventarioId && 
                    (p.name.toLowerCase().includes(searchQuery.toLowerCase()) || p.id.toString().includes(searchQuery))
                );

                if (filteredProducts.length === 0) {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td colspan="9" class="text-center py-4 text-gray-500">No se encontraron productos en este inventario.</td>`;
                    inventoryBody.appendChild(row);
                } else {
                    filteredProducts.forEach(product => {
                        const row = document.createElement('tr');
                        row.classList.add('hover:bg-gray-50');
                        row.innerHTML = `
                            <td class="px-4 py-2">${product.id}</td>
                            <td class="px-4 py-2">${product.name}</td>
                            <td class="px-4 py-2">${product.categoria}</td>
                            <td class="px-4 py-2">${product.descripcion}</td>
                            <td class="px-4 py-2 text-right">$${product.precio_credito.toFixed(2)}</td>
                            <td class="px-4 py-2 text-right">$${product.precio_contado.toFixed(2)}</td>
                            <td class="px-4 py-2 text-center">${product.stock}</td>
                            <td class="px-4 py-2">
                                <input type="number" data-id="${product.id}" class="w-20 border border-gray-300 rounded-md p-1 text-center quantity-input" min="0" max="${product.stock}" value="0">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button data-id="${product.id}" class="bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600 add-to-shipping">Añadir</button>
                            </td>
                        `;
                        inventoryBody.appendChild(row);
                    });
                }
            };
            
            // Lógica de búsqueda
            searchInput.addEventListener('input', (e) => {
                const selectedInventarioId = parseInt(inventorySelect.value);
                renderInventoryTable(selectedInventarioId, e.target.value);
            });

            const shippingProducts = {};

            // Añadir producto a la tabla de envío
            inventoryBody.addEventListener('click', (e) => {
                if (e.target.classList.contains('add-to-shipping')) {
                    const productId = parseInt(e.target.dataset.id);
                    const quantityInput = document.querySelector(`.quantity-input[data-id="${productId}"]`);
                    const quantity = parseInt(quantityInput.value);

                    if (quantity <= 0 || quantity > mockData.productos.find(p => p.id === productId).stock) {
                         showMessage('Error', 'La cantidad a enviar debe ser mayor a 0 y menor o igual al stock disponible.', 'red');
                        return;
                    }

                    if (shippingProducts[productId]) {
                        shippingProducts[productId] += quantity;
                    } else {
                        shippingProducts[productId] = quantity;
                    }
                    renderShippingTable();
                    showMessage('Añadido', 'Producto añadido a la lista de envío.', 'green');
                }
            });

            // Eliminar producto de la tabla de envío
            shippingBody.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-from-shipping')) {
                    const productId = parseInt(e.target.dataset.id);
                    delete shippingProducts[productId];
                    renderShippingTable();
                    showMessage('Eliminado', 'Producto eliminado de la lista de envío.', 'red');
                }
            });

            // Función para renderizar la tabla de productos a enviar
            const renderShippingTable = () => {
                shippingBody.innerHTML = '';
                if (Object.keys(shippingProducts).length === 0) {
                    const row = document.createElement('tr');
                    row.innerHTML = `<td colspan="8" class="text-center py-4 text-gray-500">No hay productos en la lista de envío.</td>`;
                    shippingBody.appendChild(row);
                    return;
                }

                for (const productId in shippingProducts) {
                    const product = mockData.productos.find(p => p.id === parseInt(productId));
                    const quantity = shippingProducts[productId];
                    const row = document.createElement('tr');
                    row.classList.add('hover:bg-gray-50');
                    row.innerHTML = `
                        <td class="px-4 py-2">${product.id}</td>
                        <td class="px-4 py-2">${product.name}</td>
                        <td class="px-4 py-2">${product.categoria}</td>
                        <td class="px-4 py-2">${product.descripcion}</td>
                        <td class="px-4 py-2 text-right">$${product.precio_credito.toFixed(2)}</td>
                        <td class="px-4 py-2 text-right">$${product.precio_contado.toFixed(2)}</td>
                        <td class="px-4 py-2 text-center">${quantity}</td>
                        <td class="px-4 py-2 text-center">
                            <button data-id="${product.id}" class="bg-red-500 text-white px-3 py-1 rounded-md text-sm hover:bg-red-600 remove-from-shipping">Quitar</button>
                        </td>
                    `;
                    shippingBody.appendChild(row);
                }
            };
            
            // Función para mostrar mensajes modales
            const showMessage = (title, message, type) => {
                const modal = document.getElementById('message-modal');
                const modalTitle = document.getElementById('modal-title');
                const modalMessage = document.getElementById('modal-message');
                const modalIcon = document.getElementById('modal-icon');
                const modalIconFa = document.getElementById('modal-icon-fa');
                
                modalTitle.textContent = title;
                modalMessage.textContent = message;

                // Restablecer clases de icono y color
                modalIcon.classList.remove('bg-green-100', 'bg-red-100');
                modalIconFa.classList.remove('fa-check', 'fa-times', 'text-green-600', 'text-red-600');
                
                if (type === 'green') {
                    modalIcon.classList.add('bg-green-100');
                    modalIconFa.classList.add('fa-check', 'text-green-600');
                } else if (type === 'red') {
                    modalIcon.classList.add('bg-red-100');
                    modalIconFa.classList.add('fa-times', 'text-red-600');
                }

                modal.classList.remove('hidden');
                document.getElementById('modal-ok-btn').onclick = () => {
                    modal.classList.add('hidden');
                };
            };
            
            // Manejar el envío del formulario
            submitBtn.addEventListener('click', () => {
                const responsable = responsibleInput.value.trim();
                const inventarioId = inventorySelect.value;
                const productosAEnviar = Object.entries(shippingProducts);

                if (!responsable) {
                    showMessage('Error', 'Por favor, ingrese el nombre del responsable del traslado.', 'red');
                    return;
                }

                if (!inventarioId) {
                    showMessage('Error', 'Por favor, seleccione un inventario de origen.', 'red');
                    return;
                }
                
                if (productosAEnviar.length === 0) {
                    showMessage('Error', 'No hay productos en la lista de envío. Añada al menos un producto.', 'red');
                    return;
                }

                // Aquí simularías el envío de datos a tu servidor (CodeIgniter)
                const dataToSend = {
                    responsable: responsable,
                    inventario_origen_id: inventarioId,
                    productos: productosAEnviar.map(([productId, quantity]) => {
                        const product = mockData.productos.find(p => p.id === parseInt(productId));
                        return {
                            id: product.id,
                            nombre: product.name,
                            categoria: product.categoria,
                            unidad: product.unidad,
                            descripcion: product.descripcion,
                            precio_credito: product.precio_credito,
                            precio_contado: product.precio_contado,
                            cantidad_a_enviar: quantity
                        };
                    })
                };
                
                // Simulación de una llamada API exitosa
                console.log("Datos de envío listos:", dataToSend);
                showMessage('Envío Exitoso', 'El envío ha sido registrado correctamente.', 'green');
                
                // Opcional: limpiar el formulario después de un envío exitoso
                responsibleInput.value = '';
                inventorySelect.value = '';
                shippingProducts = {};
                renderShippingTable();
                inventorySection.classList.add('hidden');
                shippingSection.classList.add('hidden');
                submitBtnContainer.classList.add('hidden');
            });
            
            // Inicializar las tablas
            renderInventoryTable(null);
            renderShippingTable();
        });
    </script>
</body>
</html>
