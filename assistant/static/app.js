// Control Room front end: a chat that renders tables, buttons and
// "confirm before doing it" boxes. All text is inserted as plain text.
const csrf = document.querySelector('meta[name="csrf"]').content;
const log = document.getElementById('log');
const input = document.getElementById('input');
const sendBtn = document.getElementById('send');

function el(tag, cls, text) {
  const e = document.createElement(tag);
  if (cls) e.className = cls;
  if (text !== undefined) e.textContent = text;
  return e;
}

async function post(url, body) {
  const r = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
    body: JSON.stringify(body || {}),
  });
  if (r.status === 403) { location.href = '/login'; throw new Error('signed out'); }
  return r.json();
}

function addMsg(who, text) {
  const m = el('div', 'msg ' + who);
  const b = el('div', 'bubble', text);
  m.appendChild(b);
  log.appendChild(m);
  log.scrollTop = log.scrollHeight;
  return b;
}

function cmdButton(btn) {
  const b = el('button', 'chip', btn.label);
  b.onclick = () => ask(btn.command);
  return b;
}

function render(bubble, data) {
  bubble.textContent = data.reply || '';
  if (data.table) {
    const wrap = el('div', 'table-wrap');
    const t = el('table');
    const head = el('tr');
    data.table.columns.forEach(c => head.appendChild(el('th', '', c)));
    if (data.table.rows.some(r => r.buttons)) head.appendChild(el('th', '', ''));
    t.appendChild(head);
    data.table.rows.forEach(r => {
      const tr = el('tr');
      r.cells.forEach(c => tr.appendChild(el('td', '', String(c))));
      if (r.buttons) {
        const td = el('td', 'actions');
        r.buttons.forEach(b => td.appendChild(cmdButton(b)));
        tr.appendChild(td);
      }
      t.appendChild(tr);
    });
    wrap.appendChild(t);
    bubble.appendChild(wrap);
  }
  if (data.buttons) {
    const row = el('div', 'chips');
    data.buttons.forEach(b => row.appendChild(cmdButton(b)));
    bubble.appendChild(row);
  }
  if (data.confirm) bubble.appendChild(confirmBox(data.confirm));
  log.scrollTop = log.scrollHeight;
}

function confirmBox(c) {
  const box = el('div', 'confirm');
  box.appendChild(el('div', 'confirm-title', '⚠️ ' + c.summary));
  let area = null;
  if (c.editable) {
    area = el('textarea');
    area.value = c.text;
    area.rows = Math.min(14, Math.max(5, c.text.split('\n').length + 2));
    box.appendChild(area);
  }
  const row = el('div', 'chips');
  const yes = el('button', 'primary', c.label);
  const no = el('button', 'ghost', 'Cancel');
  yes.onclick = async () => {
    yes.disabled = no.disabled = true;
    const res = await post('/api/confirm', { token: c.token, text: area ? area.value : undefined });
    box.replaceWith(el('div', 'done', res.reply));
  };
  no.onclick = async () => {
    yes.disabled = no.disabled = true;
    const res = await post('/api/cancel', { token: c.token });
    box.replaceWith(el('div', 'done', res.reply));
  };
  row.appendChild(yes);
  row.appendChild(no);
  box.appendChild(row);
  return box;
}

async function ask(text) {
  text = (text || '').trim();
  if (!text) return;
  addMsg('me', text);
  const bubble = addMsg('bot', 'Thinking…');
  bubble.classList.add('thinking');
  sendBtn.disabled = true;
  try {
    const data = await post('/api/chat', { message: text });
    bubble.classList.remove('thinking');
    render(bubble, data);
  } catch (e) {
    bubble.classList.remove('thinking');
    bubble.textContent = '⚠️ Could not reach the control room: ' + e.message;
  } finally {
    sendBtn.disabled = false;
    input.focus();
  }
}

document.getElementById('ask').onsubmit = e => {
  e.preventDefault();
  const t = input.value;
  input.value = '';
  input.style.height = '';
  ask(t);
};
input.addEventListener('keydown', e => {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); document.getElementById('ask').requestSubmit(); }
});
input.addEventListener('input', () => {
  input.style.height = 'auto';
  input.style.height = Math.min(input.scrollHeight, 160) + 'px';
});
document.querySelectorAll('#quick button').forEach(b => b.onclick = () => ask(b.dataset.cmd));
document.getElementById('logout').onclick = async () => { await post('/logout'); location.href = '/login'; };
