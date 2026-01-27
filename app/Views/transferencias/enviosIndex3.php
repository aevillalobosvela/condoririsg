<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestión de Productos</title>
  <style>
    :root{
      --bg:#f5f7fb;--card:#ffffff;--muted:#6b7280;--text:#111827;--brand:#4f46e5;--brand-600:#5046e5;--brand-700:#4338ca;--ok:#16a34a;--warn:#f59e0b;--danger:#ef4444;--ring:rgba(79,70,229,.25);
      --radius:14px;--shadow:0 10px 30px rgba(17,24,39,.08);
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,sans-serif;background:var(--bg);color:var(--text);}
    .container{max-width:1100px;margin:28px auto;padding:0 18px}
    h1{font-size:28px;margin:0 0 18px;display:flex;gap:10px;align-items:center}
    .sub{color:var(--muted);font-weight:500}

    /* Top bar */
    .top{display:flex;gap:14px;align-items:center;justify-content:space-between;margin-bottom:18px}
    .tabs{display:flex;gap:8px;flex-wrap:wrap}
    .tab{padding:9px 14px;border-radius:999px;background:#eceffe;color:#4338ca;border:1px solid #dfe3ff;font-weight:600}
    .tab.ghost{background:transparent;border-color:#e5e7eb;color:#374151}
    .btn{padding:10px 14px;border-radius:12px;border:none;background:linear-gradient(180deg,#6366f1,#4f46e5);color:#fff;font-weight:700;box-shadow:0 10px 20px rgba(79,70,229,.25);cursor:pointer}
    .btn:active{transform:translateY(1px)}

    /* Filters */
    .filters{display:grid;grid-template-columns:1fr 160px 160px;gap:12px;margin:14px 0}
    .input,.select{background:var(--card);border:1px solid #e5e7eb;border-radius:12px;padding:12px 12px;box-shadow:var(--shadow);outline:none}
    .input:focus,.select:focus{border-color:var(--brand);box-shadow:0 0 0 4px var(--ring)}
    .input::placeholder{color:#9ca3af}

    /* Table */
    .card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
    table{width:100%;border-collapse:separate;border-spacing:0}
    thead th{background:#fafbff;color:#6b7280;font-size:12px;letter-spacing:.02em;text-transform:uppercase;padding:14px;text-align:left;border-bottom:1px solid #eef2f7}
    tbody td{padding:14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
    tbody tr:hover{background:#fafbff}

    .prod{display:flex;gap:12px;align-items:center}
    .img{width:46px;height:46px;border-radius:12px;flex:0 0 46px;background:linear-gradient(135deg,#e5e7eb,#cbd5e1);overflow:hidden}
    .img::after{content:"";display:block;width:100%;height:100%;background:radial-gradient(circle at 30% 30%,rgba(0,0,0,.08),transparent 55%)}
    .name{font-weight:700}
    .muted{font-size:12px;color:#6b7280}

    .pill{padding:6px 10px;border-radius:999px;font-weight:700;font-size:12px;display:inline-flex;align-items:center;gap:6px}
    .pill.ok{background:#ecfdf5;color:#047857}
    .pill.warn{background:#fffbeb;color:#b45309}
    .pill.danger{background:#fef2f2;color:#b91c1c}

    .actions{display:flex;gap:6px}
    .chip{border:1px solid #e5e7eb;background:#fff;border-radius:9px;padding:6px 10px;font-weight:700;font-size:12px;cursor:pointer}
    .chip:hover{border-color:#c7cbe8;background:#f8faff}

    /* Progress */
    .bar{height:8px;background:#eef2ff;border-radius:999px;position:relative;overflow:hidden}
    .bar>span{position:absolute;left:0;top:0;height:100%;background:linear-gradient(90deg,#60a5fa,#34d399);border-radius:999px}

    /* Footer / pagination */
    .table-footer{display:flex;align-items:center;justify-content:space-between;padding:12px;border-top:1px solid #eef2f7;background:#fafbff}
    .pagination{display:flex;gap:8px}
    .page{min-width:36px;height:36px;border-radius:9px;border:1px solid #e5e7eb;background:#fff;display:grid;place-items:center;font-weight:700;cursor:pointer}
    .page.active{background:#eef2ff;border-color:#c7cbe8;color:#4338ca}

    /* Bottom grid */
    .grid{display:grid;grid-template-columns:1.1fr .9fr;gap:16px;margin-top:16px}
    .kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:14px}
    .kpi{background:#f8fafc;border:1px solid #eef2f7;border-radius:12px;padding:12px}
    .kpi .val{font-size:22px;font-weight:800}
    .kpi .lbl{font-size:12px;color:var(--muted)}

    .block{padding:16px}
    .block h3{margin:0 0 12px}

    .hbar{display:flex;align-items:center;gap:10px;margin:10px 0}
    .hbar .thumb{width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,#c7d2fe,#e5e7eb);flex:0 0 28px}
    .hbar .txt{flex:1}
    .hbar .meter{height:10px;background:#eef2ff;border-radius:999px;overflow:hidden}
    .hbar .meter>span{display:block;height:100%;background:linear-gradient(90deg,#60a5fa,#4ade80)}
    .hbar .qty{width:68px;text-align:right;font-weight:700}

    /* Responsive */
    @media (max-width: 920px){
      .filters{grid-template-columns:1fr 1fr;}
      .grid{grid-template-columns:1fr}
      .kpis{grid-template-columns:repeat(3,minmax(0,1fr))}
    }
    @media (max-width: 620px){
      thead{display:none}
      table,tbody,tr,td{display:block;width:100%}
      tbody tr{border-bottom:1px solid #eef2f7}
      tbody td{padding:10px 14px}
      .table-footer{flex-direction:column;gap:10px}
      .filters{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
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
</body>
</html>
