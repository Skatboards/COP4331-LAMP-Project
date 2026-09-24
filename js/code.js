const urlBase = (typeof window !== 'undefined' && window.location && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' || window.location.origin.includes('johnaedo')))
  ? '/api/index.php'
  : 'https://lamp.shrimpflip.store/api/index.php';

let userId = 0;
let firstName = '';
let lastName = '';
let authToken = '';
let editingContactId = 0;

function getElement(id) {
  return document.getElementById(id);
}

function showMessage(id, message, type = 'error') {
  const element = getElement(id);
  if (!element) return;
  element.className = `status-message status-${type}`;
  element.textContent = message;
}

function clearMessage(id) {
  const element = getElement(id);
  if (!element) return;
  element.className = 'status-message';
  element.textContent = '';
}

function setBusy(buttonId, busy, busyText) {
  const button = getElement(buttonId);
  if (!button) return;
  if (busy) {
    button.dataset.defaultText = button.textContent.trim();
    button.disabled = true;
    button.textContent = busyText;
  } else {
    button.disabled = false;
    button.textContent = button.dataset.defaultText || button.textContent;
  }
}

function apiRequest(method, path = '', payload = null) {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open(method, urlBase + path, true);
    xhr.setRequestHeader('Content-type', 'application/json; charset=UTF-8');
    if (userId > 0) {
      xhr.setRequestHeader('Authorization', `Bearer ${authToken || userId}`);
      xhr.setRequestHeader('X-User-Id', String(userId));
    }

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      let response = {};
      try {
        response = xhr.responseText ? JSON.parse(xhr.responseText) : {};
      } catch (error) {
        reject(new Error('The server returned an invalid response.'));
        return;
      }
      if (xhr.status === 401) {
        doLogout();
        return;
      }
      if (xhr.status >= 200 && xhr.status < 300) {
        resolve(response);
      } else {
        reject(new Error(response.error || 'The request could not be completed.'));
      }
    };
    xhr.onerror = function () {
      reject(new Error('Unable to connect to the server.'));
    };
    try {
      xhr.send(payload === null ? null : JSON.stringify(payload));
    } catch (error) {
      reject(error);
    }
  });
}

function doLogin() {
  const username = getElement('loginName')?.value.trim() || '';
  const password = getElement('loginPassword')?.value || '';
  clearMessage('authResult');
  if (!username || !password) {
    showMessage('authResult', 'Enter your username and password.');
    return;
  }

  setBusy('loginButton', true, 'Signing in...');
  apiRequest('POST', '', { action: 'login', username, password })
    .then((response) => {
      userId = Number(response.id) || 0;
      if (userId < 1) throw new Error('The username or password is incorrect.');
      firstName = response.firstName || '';
      lastName = response.lastName || '';
      authToken = response.token || '';
      saveCookie();
      window.location.href = 'color.html';
    })
    .catch((error) => showMessage('authResult', error.message))
    .finally(() => setBusy('loginButton', false));
}

function doRegister() {
  const first = getElement('registerFirstName')?.value.trim() || '';
  const last = getElement('registerLastName')?.value.trim() || '';
  const username = getElement('registerUsername')?.value.trim() || '';
  const password = getElement('registerPassword')?.value || '';
  clearMessage('authResult');
  if (!first || !last || !username || !password) {
    showMessage('authResult', 'Complete all registration fields.');
    return;
  }

  setBusy('registerButton', true, 'Creating account...');
  apiRequest('POST', '', {
    action: 'register',
    firstName: first,
    lastName: last,
    username,
    password
  })
    .then(() => {
      showLogin();
      showMessage('authResult', 'Account created. You can sign in now.', 'success');
    })
    .catch((error) => showMessage('authResult', error.message))
    .finally(() => setBusy('registerButton', false));
}

function showLogin() {
  getElement('loginPanel')?.classList.remove('d-none');
  getElement('registerPanel')?.classList.add('d-none');
  getElement('loginName')?.focus();
  clearMessage('authResult');
}

function showRegister() {
  getElement('loginPanel')?.classList.add('d-none');
  getElement('registerPanel')?.classList.remove('d-none');
  getElement('registerFirstName')?.focus();
  clearMessage('authResult');
}

function saveCookie() {
  const expires = new Date(Date.now() + 20 * 60 * 1000).toUTCString();
  document.cookie = `firstName=${encodeURIComponent(firstName)};expires=${expires};path=/;SameSite=Lax`;
  document.cookie = `lastName=${encodeURIComponent(lastName)};expires=${expires};path=/;SameSite=Lax`;
  document.cookie = `userId=${userId};expires=${expires};path=/;SameSite=Lax`;
  document.cookie = `authToken=${encodeURIComponent(authToken)};expires=${expires};path=/;SameSite=Lax`;
}

function readCookie() {
  const cookies = document.cookie.split(';').reduce((result, item) => {
    const [key, ...value] = item.trim().split('=');
    if (key) result[key] = value.join('=');
    return result;
  }, {});
  userId = Number(cookies.userId) || 0;
  firstName = decodeURIComponent(cookies.firstName || '');
  lastName = decodeURIComponent(cookies.lastName || '');
  authToken = decodeURIComponent(cookies.authToken || '');

  if (userId < 1) {
    window.location.href = 'index.html';
    return;
  }
  const userName = getElement('userName');
  if (userName) userName.textContent = `Logged in as ${firstName} ${lastName}`.trim();
  loadContacts();
}

function doLogout() {
  userId = 0;
  firstName = '';
  lastName = '';
  authToken = '';
  ['firstName', 'lastName', 'userId', 'authToken'].forEach((key) => {
    document.cookie = `${key}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
  });
  window.location.href = 'index.html';
}

function contactPayload() {
  return {
    firstName: getElement('contactFirstName')?.value.trim() || '',
    lastName: getElement('contactLastName')?.value.trim() || '',
    email: getElement('contactEmail')?.value.trim() || '',
    phoneNumber: getElement('contactPhone')?.value.trim() || ''
  };
}

function validateContact(contact) {
  if (!contact.firstName && !contact.lastName) return 'Enter a first name or last name.';
  if (contact.firstName.length > 50 || contact.lastName.length > 50) return 'Names must be 50 characters or fewer.';
  if (contact.email.length > 50) return 'Email must be 50 characters or fewer.';
  if (contact.phoneNumber.length > 20) return 'Phone number must be 20 characters or fewer.';
  return '';
}

function loadContacts() {
  searchContacts(getElement('searchText')?.value.trim() || '');
}

function searchContacts(query = '') {
  const results = getElement('contactList');
  if (!results) return;
  results.setAttribute('aria-busy', 'true');
  showMessage('contactStatus', 'Loading contacts...', 'info');
  const path = query ? `?q=${encodeURIComponent(query)}` : '';
  apiRequest('GET', path)
    .then((response) => {
      renderContacts(Array.isArray(response.contacts) ? response.contacts : []);
      showMessage('contactStatus', query ? 'Search results updated.' : 'Contacts updated.', 'info');
    })
    .catch((error) => showMessage('contactStatus', error.message))
    .finally(() => results.setAttribute('aria-busy', 'false'));
}

function createContact() {
  saveContact('POST', 'Contact added.');
}

function updateContact() {
  saveContact('PUT', 'Contact updated.');
}

function saveContact(method, successMessage) {
  const contact = contactPayload();
  const validationError = validateContact(contact);
  if (validationError) {
    showMessage('contactFormStatus', validationError);
    return;
  }
  setBusy('saveContactButton', true, 'Saving...');
  const path = method === 'PUT' ? `?id=${encodeURIComponent(editingContactId)}` : '';
  const payload = method === 'POST' ? { ...contact, action: 'createContact' } : contact;
  apiRequest(method, path, payload)
    .then(() => {
      showMessage('contactFormStatus', successMessage, 'success');
      closeContactForm();
      loadContacts();
    })
    .catch((error) => showMessage('contactFormStatus', error.message))
    .finally(() => setBusy('saveContactButton', false));
}

function openCreateContact() {
  editingContactId = 0;
  getElement('contactForm')?.reset();
  getElement('contactFormTitle').textContent = 'Add contact';
  getElement('saveContactButton').textContent = 'Add contact';
  clearMessage('contactFormStatus');
  getElement('contactFormPanel')?.classList.remove('d-none');
  getElement('contactFirstName')?.focus();
}

function openEditContact(id) {
  apiRequest('GET', `?id=${encodeURIComponent(id)}`)
    .then((response) => {
      const contact = response.contact || (Array.isArray(response.contacts) ? response.contacts[0] : null);
      if (!contact) throw new Error('Contact not found.');
      editingContactId = Number(contact.id || id);
      getElement('contactFirstName').value = contact.firstName || '';
      getElement('contactLastName').value = contact.lastName || '';
      getElement('contactEmail').value = contact.email || '';
      getElement('contactPhone').value = contact.phoneNumber || '';
      getElement('contactFormTitle').textContent = 'Edit contact';
      getElement('saveContactButton').textContent = 'Save changes';
      clearMessage('contactFormStatus');
      getElement('contactFormPanel')?.classList.remove('d-none');
      getElement('contactFirstName')?.focus();
    })
    .catch((error) => showMessage('contactStatus', error.message));
}

function closeContactForm() {
  editingContactId = 0;
  getElement('contactFormPanel')?.classList.add('d-none');
}

function deleteContact(id, name) {
  if (!window.confirm(`Delete ${name || 'this contact'}?`)) return;
  apiRequest('DELETE', `?id=${encodeURIComponent(id)}`)
    .then(() => {
      showMessage('contactStatus', 'Contact deleted.', 'success');
      loadContacts();
    })
    .catch((error) => showMessage('contactStatus', error.message));
}

function renderContacts(contacts) {
  const list = getElement('contactList');
  if (!list) return;
  list.replaceChildren();
  if (contacts.length === 0) {
    const empty = document.createElement('p');
    empty.className = 'empty-state';
    empty.textContent = 'No contacts found. Add your first contact to get started.';
    list.appendChild(empty);
    return;
  }

  contacts.forEach((contact) => {
    const item = document.createElement('article');
    item.className = 'contact-row';
    const details = document.createElement('div');
    details.className = 'contact-details';
    const name = document.createElement('h3');
    name.className = 'contact-name';
    name.textContent = [contact.firstName, contact.lastName].filter(Boolean).join(' ') || 'Unnamed contact';
    details.appendChild(name);

    [['Email', contact.email], ['Phone', contact.phoneNumber]].forEach(([label, value]) => {
      if (!value) return;
      const detail = document.createElement('p');
      detail.className = 'contact-detail';
      detail.textContent = `${label}: ${value}`;
      details.appendChild(detail);
    });

    const actions = document.createElement('div');
    actions.className = 'contact-actions';
    const editButton = document.createElement('button');
    editButton.type = 'button';
    editButton.className = 'btn btn-outline-light btn-sm';
    editButton.textContent = 'Edit';
    editButton.setAttribute('aria-label', `Edit ${name.textContent}`);
    editButton.addEventListener('click', () => openEditContact(contact.id));

    const deleteButton = document.createElement('button');
    deleteButton.type = 'button';
    deleteButton.className = 'btn btn-outline-danger btn-sm';
    deleteButton.textContent = 'Delete';
    deleteButton.setAttribute('aria-label', `Delete ${name.textContent}`);
    deleteButton.addEventListener('click', () => deleteContact(contact.id, name.textContent));

    actions.append(editButton, deleteButton);
    item.append(details, actions);
    list.appendChild(item);
  });
}
