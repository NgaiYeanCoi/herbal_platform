// 前端AJAX模块：基于XML存储的本草数据交互
// 读取页面内置配置（data-config 或全局 window.HERB_XML_CONFIG）
const root = document.getElementById('herbXmlApp');
const config = (() => {
  const fromData = root?.getAttribute('data-config');
  try { return fromData ? JSON.parse(fromData) : window.HERB_XML_CONFIG || {}; } catch { return window.HERB_XML_CONFIG || {}; }
})();
// 后端API与XSL路径、权限标识
const apiBase = config.apiBase || 'api/herbs.php';
const xslPath = config.xslPath || 'xml/herbs-table.xsl';
const isAdmin = !!config.isAdmin;

const alertPlaceholder = document.getElementById('alertPlaceholder');
const filterForm = document.getElementById('filterForm');
const filterSummary = document.getElementById('filterSummary');
const cards = document.getElementById('herbCards');
const emptyState = document.getElementById('emptyState');
const paginationNav = document.getElementById('paginationNav');
const paginationList = document.getElementById('paginationList');
const xslSortField = document.getElementById('xslSortField');
const xslSortOrder = document.getElementById('xslSortOrder');
const refreshXslBtn = document.getElementById('refreshXslBtn');
const xslTableContainer = document.getElementById('xslTableContainer');

// 模态框与表单元素
const addForm = document.getElementById('addHerbForm');
const updateForm = document.getElementById('updateHerbForm');
const updateId = document.getElementById('updateId');
const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
const deleteHerbName = document.getElementById('deleteHerbName');

// 列表分页与筛选状态
let currentPage = (config.initialFilters?.page) || 1;
let currentPageSize = (config.initialFilters?.pageSize) || 6;
let currentSort = (config.initialFilters?.sort) || 'time_desc';
let currentKeyword = (config.initialFilters?.keyword) || '';
let currentCategory = (config.initialFilters?.category) || '';
let currentDeleteId = '';

// 简易提示条渲染
function showAlert(type, message) {
  if (!alertPlaceholder) return;
  const el = document.createElement('div');
  el.className = `alert alert-${type}`;
  el.innerText = message;
  alertPlaceholder.innerHTML = '';
  alertPlaceholder.appendChild(el);
  setTimeout(() => { if (alertPlaceholder.contains(el)) el.remove(); }, 3000);
}

// 将对象拼接为查询字符串
function qs(obj) {
  return Object.entries(obj).filter(([,v]) => v!==undefined && v!==null && v!=='').map(([k,v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`).join('&');
}

// GET JSON 请求封装
async function getJson(url) {
  const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

// 发送 JSON 请求（POST/PUT），DELETE 无返回体
async function sendJson(method, url, data) {
  const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(data||{}) });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return method==='DELETE' ? null : res.json();
}

// GET 文本（XML/XSL）请求封装
async function getText(url) {
  const res = await fetch(url, { headers: { 'Accept': 'application/xml' } });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.text();
}

// 将前端排序枚举映射为后端字段与顺序
function sortFieldMap(sort) {
  if (sort==='time_desc') return { field: 'created_at', order: 'desc' };
  if (sort==='time_asc') return { field: 'created_at', order: 'asc' };
  if (sort==='name_asc') return { field: 'name', order: 'asc' };
  if (sort==='name_desc') return { field: 'name', order: 'desc' };
  return { field: 'name', order: 'asc' };
}

// 筛选摘要显示
function renderFilterSummary(total) {
  if (!filterSummary) return;
  const cat = currentCategory ? `，类别：${currentCategory}` : '';
  const kw = currentKeyword ? `，关键词：${currentKeyword}` : '';
  filterSummary.innerText = `共 ${total} 条${cat}${kw}。排序：${currentSort}；每页 ${currentPageSize} 条。`;
}

// 列表卡片渲染（保持原样式）
function renderCards(items) {
  cards.innerHTML = '';
  if (!items || items.length===0) {
    emptyState.classList.remove('d-none');
    return;
  }
  emptyState.classList.add('d-none');
  for (const it of items) {
    const col = document.createElement('div');
    col.className = 'col-md-4';
    const card = document.createElement('div');
    card.className = 'card shadow-sm';
    const body = document.createElement('div');
    body.className = 'card-body';
    const h5 = document.createElement('h5');
    h5.className = 'card-title';
    h5.innerText = it.name || '';
    const p = document.createElement('p');
    p.className = 'card-text text-muted';
    p.innerText = (it.alias ? `别名：${it.alias} ` : '') + (it.origin ? `产地：${it.origin}` : '');
    const badge = document.createElement('span');
    badge.className = 'badge bg-success me-2';
    badge.innerText = it.category || '';
    const price = document.createElement('span');
    price.className = 'text-danger fw-bold';
    price.innerText = it.price ? `￥${parseFloat(it.price).toFixed(2)}` : '';
    const actions = document.createElement('div');
    actions.className = 'mt-3';
    if (isAdmin) {
      const editBtn = document.createElement('button');
      editBtn.className = 'btn btn-warning btn-sm me-2';
      editBtn.innerText = '编辑';
      editBtn.onclick = () => openUpdateModal(it);
      const delBtn = document.createElement('button');
      delBtn.className = 'btn btn-danger btn-sm';
      delBtn.innerText = '删除';
      delBtn.onclick = () => openDeleteModal(it);
      actions.appendChild(editBtn);
      actions.appendChild(delBtn);
    }
    body.appendChild(h5);
    body.appendChild(p);
    body.appendChild(badge);
    body.appendChild(price);
    body.appendChild(actions);
    card.appendChild(body);
    col.appendChild(card);
    cards.appendChild(col);
  }
}

// 分页条渲染与跳页绑定
function renderPagination(page, totalPages) {
  paginationList.innerHTML = '';
  if (totalPages<=1) { paginationNav.classList.add('d-none'); return; }
  paginationNav.classList.remove('d-none');
  function addItem(label, target, disabled, active) {
    const li = document.createElement('li');
    li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.innerText = label;
    a.onclick = (e) => { e.preventDefault(); if (!disabled) { currentPage = target; loadPage(); } };
    li.appendChild(a); paginationList.appendChild(li);
  }
  addItem('«', Math.max(1, page-1), page===1, false);
  for (let i=Math.max(1,page-2); i<=Math.min(totalPages,page+2); i++) addItem(String(i), i, false, i===page);
  addItem('»', Math.min(totalPages, page+1), page===totalPages, false);
}

// 加载分页列表（AJAX）
async function loadPage() {
  const map = sortFieldMap(currentSort);
  const params = {
    page: String(currentPage),
    pageSize: String(currentPageSize),
    sortField: map.field,
    sortOrder: map.order,
    keyword: currentKeyword,
    category: currentCategory
  };
  try {
    const data = await getJson(`${apiBase}?${qs(params)}`);
    renderFilterSummary(data.total);
    renderCards(data.items||[]);
    renderPagination(data.page, data.totalPages);
  } catch (e) {
    showAlert('danger', '加载失败');
  }
}

// 加载XML并套用XSL进行表格渲染
async function loadXslTable() {
  try {
    const xml = await getText(`${apiBase}?action=xml`);
    const xsl = await getText(xslPath);
    const parser = new DOMParser();
    const xmlDoc = parser.parseFromString(xml, 'application/xml');
    const xslDoc = parser.parseFromString(xsl, 'application/xml');
    const proc = new XSLTProcessor();
    proc.importStylesheet(xslDoc);
    const field = xslSortField?.value || 'price';
    const order = xslSortOrder?.value || 'ascending';
    const type = (xslSortField?.selectedOptions?.[0]?.dataset?.type) || 'text';
    proc.setParameter(null, 'sortField', field);
    proc.setParameter(null, 'sortOrder', order);
    proc.setParameter(null, 'numericSort', type==='numeric' ? 'true' : 'false');
    const frag = proc.transformToFragment(xmlDoc, document);
    xslTableContainer.innerHTML = '';
    xslTableContainer.appendChild(frag);
  } catch (e) {
    xslTableContainer.innerText = '加载失败';
  }
}

// 表单数据序列化为普通对象
function getFormData(form) {
  const data = {};
  const fd = new FormData(form);
  for (const [k,v] of fd.entries()) data[k] = String(v).trim();
  return data;
}

// 新增本草（管理员权限）
async function handleAdd(e) {
  e.preventDefault();
  if (!isAdmin) return;
  const data = getFormData(addForm);
  try {
    const created = await sendJson('POST', apiBase, data);
    showAlert('success', '新增成功');
    loadPage();
    const modal = bootstrap.Modal.getInstance(document.getElementById('addHerbModal')) || new bootstrap.Modal(document.getElementById('addHerbModal'));
    modal.hide();
    addForm.reset();
  } catch (e) {
    showAlert('danger', '新增失败');
  }
}

// 打开编辑模态框并填充数据
function openUpdateModal(item) {
  updateId.value = item.id || '';
  document.getElementById('updateCode').value = item.code || '';
  document.getElementById('updateName').value = item.name || '';
  document.getElementById('updateAlias').value = item.alias || '';
  document.getElementById('updateCategory').value = item.category || '';
  document.getElementById('updateOrigin').value = item.origin || '';
  document.getElementById('updatePrice').value = item.price || '';
  document.getElementById('updateStock').value = item.stock || '';
  document.getElementById('updateImage').value = item.image_url || '';
  document.getElementById('updateEffect').value = item.effect || '';
  document.getElementById('updateDescription').value = item.description || '';
  const modalEl = document.getElementById('updateHerbModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
}

// 更新本草（管理员权限）
async function handleUpdate(e) {
  e.preventDefault();
  if (!isAdmin) return;
  const data = getFormData(updateForm);
  const id = data.id || updateId.value || '';
  try {
    await sendJson('PUT', `${apiBase}?id=${encodeURIComponent(id)}`, data);
    showAlert('success', '更新成功');
    loadPage();
    const modal = bootstrap.Modal.getInstance(document.getElementById('updateHerbModal')) || new bootstrap.Modal(document.getElementById('updateHerbModal'));
    modal.hide();
  } catch (e) {
    showAlert('danger', '更新失败');
  }
}

// 打开删除确认模态框
function openDeleteModal(item) {
  currentDeleteId = item.id || '';
  deleteHerbName.innerText = item.name || '';
  const modalEl = document.getElementById('confirmDeleteModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
}

// 删除本草（管理员权限）
async function handleDelete() {
  if (!isAdmin) return;
  try {
    await fetch(`${apiBase}?id=${encodeURIComponent(currentDeleteId)}`, { method: 'DELETE' });
    showAlert('success', '删除成功');
    loadPage();
    const modal = bootstrap.Modal.getInstance(document.getElementById('confirmDeleteModal')) || new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    modal.hide();
  } catch (e) {
    showAlert('danger', '删除失败');
  }
}

// XPath 精确/模糊查询（返回XML片段）
async function handleSearch(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  const field = fd.get('field');
  const mode = fd.get('mode');
  const keyword = fd.get('keyword');
  try {
    const xml = await getText(`${apiBase}?action=search&field=${encodeURIComponent(field)}&mode=${encodeURIComponent(mode)}&keyword=${encodeURIComponent(keyword)}`);
    const pre = document.getElementById('searchResult');
    pre.textContent = xml;
  } catch (e) {
    showAlert('danger', '查询失败');
  }
}

// 事件绑定：筛选、查询、XSL刷新、CRUD
function bindEvents() {
  if (filterForm) {
    filterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const fd = new FormData(filterForm);
      currentKeyword = String(fd.get('keyword')||'').trim();
      currentCategory = String(fd.get('category')||'').trim();
      currentSort = String(fd.get('sort')||'name_asc');
      currentPageSize = Number(fd.get('pageSize')||6);
      currentPage = 1;
      loadPage();
    });
  }
  if (document.getElementById('searchForm')) document.getElementById('searchForm').addEventListener('submit', handleSearch);
  if (refreshXslBtn) refreshXslBtn.addEventListener('click', (e) => { e.preventDefault(); loadXslTable(); });
  if (addForm) addForm.addEventListener('submit', handleAdd);
  if (updateForm) updateForm.addEventListener('submit', handleUpdate);
  if (deleteConfirmBtn) deleteConfirmBtn.addEventListener('click', handleDelete);
}

// 初始化：绑定事件与首次加载
function init() {
  bindEvents();
  loadPage();
  loadXslTable();
}

document.addEventListener('DOMContentLoaded', init);
