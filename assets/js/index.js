const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const toastRegion = document.getElementById('toastRegion');
const messenger = document.getElementById('messengerShell');
const palette = ['#a77458', '#667b91', '#8d745f', '#728b70', '#9c6e78'];
let chats = [];
let activeChatId = null;
let activeFilter = 'all';
let selectedFile = null;
let pendingEmail = '';

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
	'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[character]));

function showToast(message, type = 'success') {
	if (!toastRegion) return;
	const toast = document.createElement('div');
	toast.className = `toast ${type === 'error' ? 'error' : ''}`;
	toast.textContent = message;
	toastRegion.append(toast);
	window.setTimeout(() => toast.remove(), 3600);
}

async function requestApi(action, options = {}) {
	const headers = new Headers(options.headers || {});
	if (options.method && options.method !== 'GET') headers.set('X-CSRF-Token', csrfToken);
	if (options.body && !(options.body instanceof FormData) && typeof options.body !== 'string') {
		headers.set('Content-Type', 'application/json');
		options.body = JSON.stringify(options.body);
	}
	const response = await fetch(`index.php?api=${encodeURIComponent(action)}`, { ...options, headers });
	const contentType = response.headers.get('content-type') || '';
	const result = contentType.includes('application/json') ? await response.json() : {};
	if (!response.ok) throw new Error(result.error || 'Permintaan belum berhasil.');
	return result;
}

function setAuthMode(mode) {
	const login = document.getElementById('loginForm');
	const register = document.getElementById('registerForm');
	const verify = document.getElementById('verifyForm');
	const switcherRegister = document.getElementById('authSwitch');
	const switcherLogin = document.getElementById('authSwitchLogin');
	const titles = {
		login: ['AKUN WHATSAPP', 'Masuk kembali', 'Lanjutkan percakapan yang berarti.'],
		register: ['MULAI BERSAMA', 'Buat akunmu', 'Satu ruang untuk kabar sehari-hari.'],
		verify: ['LANGKAH TERAKHIR', 'Verifikasi akun', `Masukkan kode yang dikirim untuk ${pendingEmail}.`]
	};
	login?.classList.toggle('hidden', mode !== 'login');
	register?.classList.toggle('hidden', mode !== 'register');
	verify?.classList.toggle('hidden', mode !== 'verify');
	if (switcherRegister) switcherRegister.classList.toggle('hidden', mode !== 'login');
	if (switcherLogin) switcherLogin.classList.toggle('hidden', mode !== 'register');
	document.getElementById('authEyebrow').textContent = titles[mode][0];
	document.getElementById('authTitle').textContent = titles[mode][1];
	document.getElementById('authSubtitle').textContent = titles[mode][2];
}

function openModal(id) {
	const modal = document.getElementById(id);
	if (!modal) return;
	modal.classList.remove('hidden');
	modal.setAttribute('aria-hidden', 'false');
}

function closeModal(modal) {
	modal.classList.add('hidden');
	modal.setAttribute('aria-hidden', 'true');
}

function showDemoCode(code) {
	document.getElementById('demoCode').textContent = code;
	openModal('verifyModal');
}

function setupAuthentication() {
	document.getElementById('showRegister')?.addEventListener('click', () => setAuthMode('register'));
	document.getElementById('showLogin')?.addEventListener('click', () => setAuthMode('login'));
	document.getElementById('loginForm')?.addEventListener('submit', async (event) => {
		event.preventDefault();
		const values = Object.fromEntries(new FormData(event.currentTarget));
		try {
			const result = await requestApi('auth.login', { method: 'POST', body: values });
			if (result.requires_verification) {
				pendingEmail = result.email;
				setAuthMode('verify');
				const resend = await requestApi('auth.resend', { method: 'POST', body: { email: pendingEmail } });
				showDemoCode(resend.demo_code);
				return;
			}
			window.location.reload();
		} catch (error) { showToast(error.message, 'error'); }
	});
	document.getElementById('registerForm')?.addEventListener('submit', async (event) => {
		event.preventDefault();
		const values = Object.fromEntries(new FormData(event.currentTarget));
		try {
			const result = await requestApi('auth.register', { method: 'POST', body: values });
			pendingEmail = result.email;
			showDemoCode(result.demo_code);
		} catch (error) { showToast(error.message, 'error'); }
	});
	document.getElementById('continueVerify')?.addEventListener('click', () => {
		closeModal(document.getElementById('verifyModal'));
		setAuthMode('verify');
		document.querySelector('#verifyForm input[name="code"]')?.focus();
	});
	document.getElementById('resendCode')?.addEventListener('click', async () => {
		try {
			const result = await requestApi('auth.resend', { method: 'POST', body: { email: pendingEmail } });
			showDemoCode(result.demo_code);
		} catch (error) { showToast(error.message, 'error'); }
	});
	document.getElementById('verifyForm')?.addEventListener('submit', async (event) => {
		event.preventDefault();
		const code = new FormData(event.currentTarget).get('code');
		try {
			await requestApi('auth.verify', { method: 'POST', body: { code } });
			window.location.reload();
		} catch (error) { showToast(error.message, 'error'); }
	});
}

function initials(name) {
	return String(name).trim().split(/\s+/).slice(0, 2).map((part) => part[0] || '').join('').toUpperCase();
}

function formatTime(dateString) {
	if (!dateString) return '';
	const date = new Date(String(dateString).replace(' ', 'T'));
	return Number.isNaN(date.valueOf()) ? '' : date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

function unreadKey(chatId) { return `whatsapp-unread-${chatId}`; }
function hasUnread(chatId) { return localStorage.getItem(unreadKey(chatId)) === '1'; }

function renderChats() {
	const list = document.getElementById('chatList');
	if (!list) return;
	const search = document.getElementById('chatSearch').value.trim().toLocaleLowerCase('id');
	const visible = chats.filter((chat) => {
		const matchesSearch = `${chat.contact_name} ${chat.contact_phone} ${chat.last_message || ''}`.toLocaleLowerCase('id').includes(search);
		const isGroup = chat.contact_phone.toLowerCase().includes('anggota');
		return matchesSearch && (activeFilter === 'all' || (activeFilter === 'groups' && isGroup) || (activeFilter === 'unread' && hasUnread(chat.id)));
	});
	const unreadCount = chats.filter((chat) => hasUnread(chat.id)).length;
	document.getElementById('chatCount').textContent = String(chats.length).padStart(2, '0');
	document.getElementById('unreadTotal').textContent = unreadCount;
	document.getElementById('unreadTotal').classList.toggle('hidden', unreadCount === 0);
	if (!visible.length) {
		list.innerHTML = `<div class="empty-list">${search ? 'Tidak ada percakapan yang cocok.' : 'Belum ada percakapan di sini.'}</div>`;
		return;
	}
	list.innerHTML = visible.map((chat) => `
		<article class="chat-row ${Number(chat.id) === Number(activeChatId) ? 'active' : ''}" data-chat-id="${Number(chat.id)}" tabindex="0" role="button" aria-label="Buka percakapan dengan ${escapeHtml(chat.contact_name)}">
			<div class="avatar" style="background:${escapeHtml(chat.avatar_color)}">${escapeHtml(initials(chat.contact_name))}</div>
			<div class="chat-row-copy"><div class="chat-row-top"><strong>${escapeHtml(chat.contact_name)}</strong><time>${escapeHtml(formatTime(chat.updated_at))}</time></div><div class="chat-row-bottom"><span class="chat-preview">${escapeHtml(chat.last_message || 'Mulai percakapan')}</span>${hasUnread(chat.id) ? '<span class="chat-unread" title="Belum dibaca"></span>' : ''}</div></div>
		</article>`).join('');
}

async function loadChats(selectFirst = false) {
	const result = await requestApi('chats');
	chats = result.chats;
	if (chats.length && !localStorage.getItem('whatsapp-unread-seeded')) {
		localStorage.setItem(unreadKey(chats[1]?.id || chats[0].id), '1');
		localStorage.setItem('whatsapp-unread-seeded', '1');
	}
	renderChats();
	if (selectFirst && chats.length) openChat(chats[0].id);
}

function renderAttachment(message) {
	if (!message.attachment_url) return '';
	const image = String(message.attachment_mime || '').startsWith('image/');
	const imagePreview = image ? `<a href="${escapeHtml(message.attachment_url)}" target="_blank" rel="noreferrer"><img class="attachment-image" src="${escapeHtml(message.attachment_url)}" alt="${escapeHtml(message.attachment_name)}"></a>` : '';
	const extension = (message.attachment_name || 'FILE').split('.').pop().slice(0, 4).toUpperCase();
	const size = Number(message.attachment_size) ? `${(Number(message.attachment_size) / 1024 / 1024).toFixed(2)} MB` : 'Lampiran';
	return `${imagePreview}<a class="attachment-card" href="${escapeHtml(message.attachment_url)}" target="_blank" rel="noreferrer" download="${escapeHtml(message.attachment_name)}"><span class="attachment-icon">${escapeHtml(extension)}</span><span class="attachment-copy"><strong>${escapeHtml(message.attachment_name)}</strong><small>${escapeHtml(size)}</small></span></a>`;
}

function renderMessages(messages) {
	const list = document.getElementById('messageList');
	if (!messages.length) {
		list.innerHTML = '<div class="empty-list">Belum ada pesan. Mulai dengan menyapa.</div>';
		return;
	}
	list.innerHTML = messages.map((message) => {
		const mine = message.sender === 'me';
		return `<article class="message ${mine ? 'mine' : ''}" data-message-id="${Number(message.id)}"><div class="message-bubble">${message.attachment_url ? renderAttachment(message) : ''}${escapeHtml(message.body)}</div><div class="message-meta">${message.is_edited ? '<span class="message-edited">diedit</span>' : ''}<time>${escapeHtml(formatTime(message.created_at))}</time>${mine ? '<span>✓✓</span>' : ''}</div>${mine ? `<div class="message-actions"><button type="button" data-message-action="edit">Ubah</button><button type="button" data-message-action="delete">Hapus</button></div>` : ''}</article>`;
	}).join('');
	const scroll = document.getElementById('messageScroll');
	scroll.scrollTop = scroll.scrollHeight;
}

async function openChat(chatId) {
	const chat = chats.find((item) => Number(item.id) === Number(chatId));
	if (!chat) return;
	activeChatId = Number(chat.id);
	localStorage.removeItem(unreadKey(chat.id));
	document.getElementById('welcomeState').classList.add('hidden');
	document.getElementById('conversationHeader').classList.remove('hidden');
	document.getElementById('messageScroll').classList.remove('hidden');
	document.getElementById('messageForm').classList.remove('hidden');
	const avatar = document.getElementById('activeAvatar');
	avatar.textContent = initials(chat.contact_name);
	avatar.style.background = chat.avatar_color;
	document.getElementById('activeName').textContent = chat.contact_name;
	document.getElementById('activeStatus').textContent = chat.contact_phone || 'terakhir terlihat baru saja';
	renderChats();
	messenger.classList.add('chat-open');
	try {
		const result = await requestApi(`messages&chat_id=${encodeURIComponent(chat.id)}`);
		if (Number(chat.id) === activeChatId) renderMessages(result.messages);
	} catch (error) { showToast(error.message, 'error'); }
}

function resetAttachment() {
	selectedFile = null;
	document.getElementById('attachmentInput').value = '';
	document.getElementById('attachmentPreview').classList.add('hidden');
}

function setupMessenger() {
	const user = window.WHATSAPP_USER;
	document.getElementById('profileName').textContent = user.name;
	document.getElementById('profileEmail').textContent = user.email;
	document.getElementById('profileButton').addEventListener('click', () => document.getElementById('profileMenu').classList.toggle('hidden'));
	document.getElementById('logoutButton').addEventListener('click', logout);
	document.getElementById('logoutFooter').addEventListener('click', logout);
	document.getElementById('chatSearch').addEventListener('input', renderChats);
	document.querySelectorAll('.filter-button').forEach((button) => button.addEventListener('click', () => {
		document.querySelectorAll('.filter-button').forEach((item) => item.classList.remove('active'));
		button.classList.add('active');
		activeFilter = button.dataset.filter;
		renderChats();
	}));
	document.getElementById('chatList').addEventListener('click', (event) => {
		const row = event.target.closest('[data-chat-id]');
		if (row) openChat(row.dataset.chatId);
	});
	document.getElementById('chatList').addEventListener('keydown', (event) => {
		if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('[data-chat-id]')) {
			event.preventDefault();
			openChat(event.target.dataset.chatId);
		}
	});
	document.getElementById('newChatButton').addEventListener('click', () => openModal('chatModal'));
	document.getElementById('welcomeNewChat').addEventListener('click', () => openModal('chatModal'));
	document.getElementById('newChatForm').addEventListener('submit', async (event) => {
		event.preventDefault();
		const values = Object.fromEntries(new FormData(event.currentTarget));
		try {
			const result = await requestApi('chats', { method: 'POST', body: values });
			closeModal(document.getElementById('chatModal'));
			event.currentTarget.reset();
			await loadChats();
			openChat(result.chat_id);
			showToast('Percakapan baru siap.');
		} catch (error) { showToast(error.message, 'error'); }
	});
	document.getElementById('renameChatButton').addEventListener('click', async () => {
		const chat = chats.find((item) => Number(item.id) === activeChatId);
		const name = window.prompt('Nama kontak', chat?.contact_name || '');
		if (!name || !name.trim()) return;
		try {
			await requestApi('chats', { method: 'PATCH', body: { id: activeChatId, name: name.trim() } });
			await loadChats();
			document.getElementById('activeName').textContent = name.trim();
			document.getElementById('activeAvatar').textContent = initials(name.trim());
			showToast('Nama kontak diperbarui.');
		} catch (error) { showToast(error.message, 'error'); }
	});
	document.getElementById('archiveChatButton').addEventListener('click', async () => {
		if (!activeChatId || !window.confirm('Arsipkan percakapan ini?')) return;
		try {
			await requestApi('chats', { method: 'PATCH', body: { id: activeChatId, archived: true } });
			activeChatId = null;
			document.getElementById('conversationHeader').classList.add('hidden');
			document.getElementById('messageScroll').classList.add('hidden');
			document.getElementById('messageForm').classList.add('hidden');
			document.getElementById('welcomeState').classList.remove('hidden');
			await loadChats();
			messenger.classList.remove('chat-open');
			showToast('Percakapan diarsipkan.');
		} catch (error) { showToast(error.message, 'error'); }
	});
	document.getElementById('deleteChatButton').addEventListener('click', async () => {
		if (!activeChatId || !window.confirm('Hapus percakapan beserta semua pesannya?')) return;
		try {
			await requestApi('chats', { method: 'DELETE', body: { id: activeChatId } });
			activeChatId = null;
			document.getElementById('conversationHeader').classList.add('hidden');
			document.getElementById('messageScroll').classList.add('hidden');
			document.getElementById('messageForm').classList.add('hidden');
			document.getElementById('welcomeState').classList.remove('hidden');
			await loadChats();
			messenger.classList.remove('chat-open');
			showToast('Percakapan dihapus.');
		} catch (error) { showToast(error.message, 'error'); }
	});
	document.getElementById('backToChats').addEventListener('click', () => messenger.classList.remove('chat-open'));
	document.getElementById('attachButton').addEventListener('click', () => document.getElementById('attachmentInput').click());
	document.getElementById('attachmentInput').addEventListener('change', (event) => {
		selectedFile = event.target.files[0] || null;
		if (!selectedFile) return;
		if (selectedFile.size > 12 * 1024 * 1024) {
			resetAttachment();
			showToast('Ukuran file maksimal 12 MB.', 'error');
			return;
		}
		const preview = document.getElementById('attachmentPreview');
		preview.innerHTML = `${escapeHtml(selectedFile.name)} · ${(selectedFile.size / 1024 / 1024).toFixed(2)} MB <button type="button" aria-label="Batalkan lampiran">×</button>`;
		preview.classList.remove('hidden');
		preview.querySelector('button').addEventListener('click', resetAttachment);
	});
	document.getElementById('messageInput').addEventListener('keydown', (event) => {
		if (event.key === 'Enter' && !event.shiftKey) {
			event.preventDefault();
			document.getElementById('messageForm').requestSubmit();
		}
	});
	document.getElementById('messageInput').addEventListener('input', (event) => {
		event.target.style.height = 'auto';
		event.target.style.height = `${Math.min(event.target.scrollHeight, 130)}px`;
	});
	document.getElementById('messageForm').addEventListener('submit', sendMessage);
	document.getElementById('messageList').addEventListener('click', handleMessageAction);
	document.getElementById('backupButton').addEventListener('click', () => openModal('backupModal'));
	document.getElementById('backupFooter').addEventListener('click', () => openModal('backupModal'));
	document.getElementById('restoreInput').addEventListener('change', restoreBackup);
	document.addEventListener('keydown', (event) => {
		if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
			event.preventDefault();
			document.getElementById('chatSearch').focus();
		}
		if (event.key === 'Escape') {
			document.querySelectorAll('.modal:not(.hidden)').forEach(closeModal);
			document.getElementById('profileMenu').classList.add('hidden');
		}
	});
	loadChats().catch((error) => showToast(error.message, 'error'));
}

async function sendMessage(event) {
	event.preventDefault();
	if (!activeChatId) return;
	const body = document.getElementById('messageInput').value.trim();
	if (!body && !selectedFile) return;
	const formData = new FormData();
	formData.set('chat_id', activeChatId);
	formData.set('body', body);
	if (selectedFile) formData.set('attachment', selectedFile);
	const submit = event.currentTarget.querySelector('.send-button');
	submit.disabled = true;
	try {
		await requestApi('messages', { method: 'POST', body: formData });
		document.getElementById('messageInput').value = '';
		document.getElementById('messageInput').style.height = '';
		resetAttachment();
		const currentChat = activeChatId;
		await loadChats();
		if (currentChat === activeChatId) {
			const result = await requestApi(`messages&chat_id=${encodeURIComponent(currentChat)}`);
			renderMessages(result.messages);
		}
	} catch (error) { showToast(error.message, 'error'); }
	finally { submit.disabled = false; }
}

async function handleMessageAction(event) {
	const button = event.target.closest('[data-message-action]');
	if (!button) return;
	const messageElement = button.closest('[data-message-id]');
	const messageId = Number(messageElement.dataset.messageId);
	try {
		if (button.dataset.messageAction === 'delete') {
			if (!window.confirm('Hapus pesan ini?')) return;
			await requestApi('messages', { method: 'DELETE', body: { id: messageId } });
		} else {
			const currentText = messageElement.querySelector('.message-bubble').innerText.trim();
			const body = window.prompt('Ubah pesan', currentText);
			if (body === null || !body.trim()) return;
			await requestApi('messages', { method: 'PATCH', body: { id: messageId, body: body.trim() } });
		}
		const result = await requestApi(`messages&chat_id=${encodeURIComponent(activeChatId)}`);
		renderMessages(result.messages);
		await loadChats();
		showToast(button.dataset.messageAction === 'delete' ? 'Pesan dihapus.' : 'Pesan diperbarui.');
	} catch (error) { showToast(error.message, 'error'); }
}

async function restoreBackup(event) {
	const file = event.target.files[0];
	if (!file) return;
	if (!window.confirm('Pemulihan akan mengganti semua chat dan pesan saat ini. Lanjutkan?')) {
		event.target.value = '';
		return;
	}
	try {
		const backup = JSON.parse(await file.text());
		await requestApi('backup.restore', { method: 'POST', body: { backup } });
		closeModal(document.getElementById('backupModal'));
		activeChatId = null;
		document.getElementById('conversationHeader').classList.add('hidden');
		document.getElementById('messageScroll').classList.add('hidden');
		document.getElementById('messageForm').classList.add('hidden');
		document.getElementById('welcomeState').classList.remove('hidden');
		await loadChats();
		messenger.classList.remove('chat-open');
		showToast('Cadangan berhasil dipulihkan.');
	} catch (error) { showToast(error instanceof SyntaxError ? 'Berkas JSON tidak dapat dibaca.' : error.message, 'error'); }
	finally { event.target.value = ''; }
}

async function logout() {
	try {
		await requestApi('auth.logout', { method: 'POST', body: {} });
		window.location.reload();
	} catch (error) { showToast(error.message, 'error'); }
}

document.querySelectorAll('[data-close-modal]').forEach((element) => element.addEventListener('click', () => {
	const modal = element.closest('.modal');
	if (modal) closeModal(modal);
}));

if (document.getElementById('loginForm')) setupAuthentication();
if (messenger) setupMessenger();
