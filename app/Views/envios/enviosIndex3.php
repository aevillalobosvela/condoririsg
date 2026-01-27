
<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
  <div class="container">
    <h1>Gestión de Productos <span class="sub">· Administra tu inventario de manera eficiente</span></h1>

    <div class="top">
      <div class="tabs">
        <div class="tab">Panel de control</div>
        <div class="tab ghost">Gestión de envíos</div>
      </div>
      <button class="btn">Nuevo Producto</button>
    </div>

    <div class="filters">
      <input class="input" placeholder="🔎 Buscar productos…" />
      <select class="select">
        <option>Categoría: Todas</option>
        <option>Lácteos</option>
        <option>Carnes</option>
        <option>Granos</option>
        <option>Bebidas</option>
        <option>Enlatados</option>
      </select>
      <select class="select">
        <option>Estado: Todos</option>
        <option>En stock</option>
        <option>Bajo stock</option>
        <option>Agotado</option>
      </select>
    </div>

    <div class="card">
      <table>
        <thead>
          <tr>
            <th style="width:34%">Producto</th>
            <th>Categoría</th>
            <th>SKU</th>
            <th style="width:20%">Stock</th>
            <th>Precio</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="prod"><div class="img"></div>
                <div>
                  <div class="name">Queso Fresco Artesanal</div>
                  <div class="muted">500g</div>
                </div>
              </div>
            </td>
            <td>Lácteos</td>
            <td>LAC-QF-001</td>
            <td>
              <div class="muted" style="margin-bottom:6px">42</div>
              <div class="bar"><span style="width:42%"></span></div>
            </td>
            <td>Bs. 25.50</td>
            <td><span class="pill ok">En stock</span></td>
            <td class="actions">
              <button class="chip">Ver</button>
              <button class="chip">Editar</button>
              <button class="chip">Borrar</button>
            </td>
          </tr>

          <tr>
            <td>
              <div class="prod"><div class="img"></div>
                <div>
                  <div class="name">Bife de Res Premium</div>
                  <div class="muted">1kg</div>
                </div>
              </div>
            </td>
            <td>Carnes</td>
            <td>CAR-LR-023</td>
            <td>
              <div class="muted" style="margin-bottom:6px">8</div>
              <div class="bar"><span style="width:8%"></span></div>
            </td>
            <td>Bs. 85.00</td>
            <td><span class="pill warn">Bajo stock</span></td>
            <td class="actions">
              <button class="chip">Ver</button>
              <button class="chip">Editar</button>
              <button class="chip">Borrar</button>
            </td>
          </tr>

          <tr>
            <td>
              <div class="prod"><div class="img"></div>
                <div>
                  <div class="name">Quinua Orgánica</div>
                  <div class="muted">500g</div>
                </div>
              </div>
            </td>
            <td>Granos</td>
            <td>GRA-QO-105</td>
            <td>
              <div class="muted" style="margin-bottom:6px">120</div>
              <div class="bar"><span style="width:100%"></span></div>
            </td>
            <td>Bs. 18.75</td>
            <td><span class="pill ok">En stock</span></td>
            <td class="actions">
              <button class="chip">Ver</button>
              <button class="chip">Editar</button>
              <button class="chip">Borrar</button>
            </td>
          </tr>

          <tr>
            <td>
              <div class="prod"><div class="img"></div>
                <div>
                  <div class="name">Chicha Morada Tradicional</div>
                  <div class="muted">1L</div>
                </div>
              </div>
            </td>
            <td>Bebidas</td>
            <td>BEB-CM-078</td>
            <td>
              <div class="muted" style="margin-bottom:6px">0</div>
              <div class="bar"><span style="width:0%"></span></div>
            </td>
            <td>Bs. 12.00</td>
            <td><span class="pill danger">Agotado</span></td>
            <td class="actions">
              <button class="chip">Ver</button>
              <button class="chip">Editar</button>
              <button class="chip">Borrar</button>
            </td>
          </tr>

          <tr>
            <td>
              <div class="prod"><div class="img"></div>
                <div>
                  <div class="name">Palmito en Conserva</div>
                  <div class="muted">400g</div>
                </div>
              </div>
            </td>
            <td>Enlatados</td>
            <td>ENL-PC-056</td>
            <td>
              <div class="muted" style="margin-bottom:6px">35</div>
              <div class="bar"><span style="width:35%"></span></div>
            </td>
            <td>Bs. 22.30</td>
            <td><span class="pill ok">En stock</span></td>
            <td class="actions">
              <button class="chip">Ver</button>
              <button class="chip">Editar</button>
              <button class="chip">Borrar</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div class="table-footer">
        <div class="muted">Mostrando 5 de 42 productos</div>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="pagination">
            <div class="page">«</div>
            <div class="page active">1</div>
            <div class="page">2</div>
            <div class="page">3</div>
            <div class="page">»</div>
          </div>
          <select class="select" style="width:110px;height:36px;padding:6px 10px">
            <option>Mostrar: 5</option>
            <option>10</option>
            <option>20</option>
          </select>
        </div>
      </div>
    </div>

    <div class="grid">
      <div class="card">
        <div class="block">
          <h3>Estadísticas de Inventario</h3>
          <div class="kpis">
            <div class="kpi"><div class="val">42</div><div class="lbl">Total Productos</div></div>
            <div class="kpi"><div class="val">8</div><div class="lbl">Bajo Stock</div></div>
            <div class="kpi"><div class="val">3</div><div class="lbl">Agotados</div></div>
          </div>
          <div style="margin:6px 14px 4px">
            <div class="muted" style="margin:8px 0 6px">Ingresos por categoría</div>
            <div class="hbar"><div class="txt" style="min-width:120px">Lácteos</div><div class="meter" style="flex:1"><span style="width:68%"></span></div></div>
            <div class="hbar"><div class="txt" style="min-width:120px">Granos</div><div class="meter" style="flex:1"><span style="width:52%"></span></div></div>
            <div class="hbar"><div class="txt" style="min-width:120px">Bebidas</div><div class="meter" style="flex:1"><span style="width:34%"></span></div></div>
            <div class="hbar"><div class="txt" style="min-width:120px">Enlatados</div><div class="meter" style="flex:1"><span style="width:78%"></span></div></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="block">
          <h3>Productos Más Vendidos</h3>
          <div class="hbar">
            <div class="thumb"></div>
            <div class="txt">Queso Fresco Artesanal</div>
            <div class="meter" style="flex:1"><span style="width:88%"></span></div>
            <div class="qty">245 unid.</div>
          </div>
          <div class="hbar">
            <div class="thumb"></div>
            <div class="txt">Quinua Orgánica</div>
            <div class="meter" style="flex:1"><span style="width:72%"></span></div>
            <div class="qty">198 unid.</div>
          </div>
          <div class="hbar">
            <div class="thumb"></div>
            <div class="txt">Palmito en Conserva</div>
            <div class="meter" style="flex:1"><span style="width:58%"></span></div>
            <div class="qty">156 unid.</div>
          </div>
          <div class="hbar">
            <div class="thumb"></div>
            <div class="txt">Chicha Morada Tradicional</div>
            <div class="meter" style="flex:1"><span style="width:49%"></span></div>
            <div class="qty">132 unid.</div>
          </div>
        </div>
      </div>
    </div>
  </div>


<?= $this->endSection() ?>

<?= $this->section('scripts') ?><?= $this->endSection() ?>