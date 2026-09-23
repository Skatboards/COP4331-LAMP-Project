const urlBase = (typeof window !== 'undefined' && window.location && (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' || window.location.origin.includes('johnaedo')))
  ? '/api/index.php'
  : 'https://lamp.shrimpflip.store/api/index.php';

let userId = 0;
let firstName = '';
let lastName = '';
let authToken = '';
let userRole = 'User';
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
    const authenticatedRequest = Boolean(authToken);
    xhr.open(method, urlBase + path, true);
    xhr.setRequestHeader('Content-type', 'application/json; charset=UTF-8');
    if (authToken) {
      xhr.setRequestHeader('Authorization', `Bearer ${authToken}`);
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
        if (authenticatedRequest) {
          clearAuthState();
          window.location.href = 'index.html';
        } else {
          reject(new Error(response.error || 'Invalid username or password.'));
        }
        return;
      }
      if (xhr.status === 403) {
        reject(new Error(response.error || 'You do not have permission to perform this action.'));
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
      userRole = response.role || 'User';
      if (!authToken) throw new Error('The server did not return an authentication token.');
      saveCookie();
      window.location.href = 'contacts.html';
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
  document.cookie = `userRole=${encodeURIComponent(userRole)};expires=${expires};path=/;SameSite=Lax`;
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
  userRole = decodeURIComponent(cookies.userRole || 'User');

  if (userId < 1 || !authToken) {
    window.location.href = 'index.html';
    return;
  }
  const userName = getElement('userName');
  if (userName) userName.textContent = `Logged in as ${firstName} ${lastName}`.trim();
  const adminLink = getElement('adminLink');
  if (adminLink && userRole === 'Admin') adminLink.classList.remove('d-none');
  loadContacts();
  loadDirectory();
}

function doLogout() {
  const token = authToken;
  clearAuthState();

  if (!token) {
    window.location.href = 'index.html';
    return;
  }

  authToken = token;
  apiRequest('POST', '', { action: 'logout' })
    .catch(() => {})
    .finally(() => {
      clearAuthState();
      window.location.href = 'index.html';
    });
}

function clearAuthState() {
  userId = 0;
  firstName = '';
  lastName = '';
  authToken = '';
  userRole = 'User';
  ['firstName', 'lastName', 'userId', 'authToken', 'userRole'].forEach((key) => {
    document.cookie = `${key}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/`;
  });
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
    if (contact.username) {
      const username = document.createElement('p');
      username.className = 'contact-detail';
      username.textContent = `Username: ${contact.username}`;
      details.appendChild(username);
    }

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

function loadDirectory(query = '') {
  const list = getElement('directoryList');
  if (!list) return;
  list.setAttribute('aria-busy', 'true');
  showMessage('directoryStatus', 'Loading directory...', 'info');
  const path = `?action=directory${query ? `&q=${encodeURIComponent(query)}` : ''}`;
  apiRequest('GET', path)
    .then((response) => {
      renderDirectory(Array.isArray(response.contacts) ? response.contacts : []);
      showMessage('directoryStatus', 'Directory updated.', 'info');
    })
    .catch((error) => showMessage('directoryStatus', error.message))
    .finally(() => list.setAttribute('aria-busy', 'false'));
}

function renderDirectory(contacts) {
  const list = getElement('directoryList');
  if (!list) return;
  list.replaceChildren();
  if (contacts.length === 0) {
    const empty = document.createElement('p');
    empty.className = 'empty-state';
    empty.textContent = 'No directory contacts found.';
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

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'btn btn-primary btn-sm';
    addButton.textContent = 'Add to my contacts';
    addButton.setAttribute('aria-label', `Add ${name.textContent} to my contacts`);
    addButton.addEventListener('click', () => addDirectoryContact(contact.directoryType, contact.sourceId));

    const actions = document.createElement('div');
    actions.className = 'contact-actions';
    actions.appendChild(addButton);
    item.append(details, actions);
    list.appendChild(item);
  });
}

function addDirectoryContact(sourceType, sourceId) {
  apiRequest('POST', '', { action: 'addDirectoryContact', sourceType, sourceId })
    .then(() => {
      showMessage('directoryStatus', 'Contact added to your list.', 'success');
      loadContacts();
    })
    .catch((error) => showMessage('directoryStatus', error.message));
}

function initAdminPage() {
  readCookie();
  if (userRole !== 'Admin') {
    window.location.href = 'contacts.html';
    return;
  }
  loadAdminUsers();
  loadAllContacts();
}

function loadAdminUsers(query = '') {
  const path = `?action=users${query ? `&q=${encodeURIComponent(query)}` : ''}`;
  apiRequest('GET', path)
    .then((response) => renderUsers(Array.isArray(response.users) ? response.users : []))
    .catch((error) => showMessage('adminStatus', error.message));
}

function renderUsers(users) {
  const list = getElement('userList');
  if (!list) return;
  list.replaceChildren();
  if (users.length === 0) {
    const empty = document.createElement('p');
    empty.className = 'empty-state';
    empty.textContent = 'No users found.';
    list.appendChild(empty);
    return;
  }

  users.forEach((user) => {
    const row = document.createElement('article');
    row.className = 'contact-row';
    const details = document.createElement('div');
    details.className = 'contact-details';
    const name = document.createElement('h3');
    name.className = 'contact-name';
    name.textContent = `${user.firstName} ${user.lastName}`.trim() || 'Unnamed user';
    const account = document.createElement('p');
    account.className = 'contact-detail';
    account.textContent = `${user.username} · ${user.role}${Number(user.isDisabled) ? ' · Disabled' : ''}`;
    details.append(name, account);

    const actions = document.createElement('div');
    actions.className = 'contact-actions';
    if (Number(user.id) !== userId) {
      const toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'btn btn-outline-danger btn-sm';
      toggle.textContent = Number(user.isDisabled) ? 'Enable' : 'Disable';
      toggle.addEventListener('click', () => setUserDisabled(user.id, !Number(user.isDisabled)));
      actions.appendChild(toggle);
    }
    const password = document.createElement('button');
    password.type = 'button';
    password.className = 'btn btn-outline-light btn-sm';
    password.textContent = 'Change password';
    password.addEventListener('click', () => changeUserPassword(user.id, name.textContent));
    actions.appendChild(password);
    row.append(details, actions);
    list.appendChild(row);
  });
}

function setUserDisabled(id, disabled) {
  apiRequest('PUT', `?action=disableUser&id=${encodeURIComponent(id)}`, { disabled })
    .then((response) => {
      showMessage('adminStatus', response.message || 'User status updated.', 'success');
      loadAdminUsers(getElement('userSearch')?.value.trim() || '');
    })
    .catch((error) => showMessage('adminStatus', error.message));
}

function changeUserPassword(id, name) {
  const password = window.prompt(`New password for ${name}:`);
  if (!password) return;
  apiRequest('PUT', `?action=changePassword&id=${encodeURIComponent(id)}`, { password })
    .then((response) => showMessage('adminStatus', response.message || 'Password updated.', 'success'))
    .catch((error) => showMessage('adminStatus', error.message));
}

function createAdmin() {
  const payload = {
    action: 'createAdmin',
    firstName: getElement('adminFirstName')?.value.trim() || '',
    lastName: getElement('adminLastName')?.value.trim() || '',
    username: getElement('adminUsername')?.value.trim() || '',
    password: getElement('adminPassword')?.value || ''
  };
  if (!payload.firstName || !payload.lastName || !payload.username || !payload.password) {
    showMessage('adminFormStatus', 'Complete every Admin account field.');
    return;
  }
  apiRequest('POST', '', payload)
    .then(() => {
      getElement('adminForm')?.reset();
      showMessage('adminFormStatus', 'Admin account created.', 'success');
      loadAdminUsers();
    })
    .catch((error) => showMessage('adminFormStatus', error.message));
}

function loadAllContacts(query = '') {
  const path = `?action=allContacts${query ? `&q=${encodeURIComponent(query)}` : ''}`;
  apiRequest('GET', path)
    .then((response) => renderAdminContacts(Array.isArray(response.contacts) ? response.contacts : []))
    .catch((error) => showMessage('adminStatus', error.message));
}

function renderAdminContacts(contacts) {
  const list = getElement('adminContactList');
  if (!list) return;
  list.replaceChildren();
  if (contacts.length === 0) {
    const empty = document.createElement('p');
    empty.className = 'empty-state';
    empty.textContent = 'No contacts found.';
    list.appendChild(empty);
    return;
  }
  contacts.forEach((contact) => {
    const row = document.createElement('article');
    row.className = 'contact-row';
    const details = document.createElement('div');
    details.className = 'contact-details';
    const name = document.createElement('h3');
    name.className = 'contact-name';
    name.textContent = [contact.firstName, contact.lastName].filter(Boolean).join(' ') || 'Unnamed contact';
    const owner = document.createElement('p');
    owner.className = 'contact-detail';
    owner.textContent = `Owner: ${contact.username}`;
    const contactInfo = document.createElement('p');
    contactInfo.className = 'contact-detail';
    contactInfo.textContent = [contact.email, contact.phoneNumber].filter(Boolean).join(' · ');
    details.append(name, owner, contactInfo);
    row.appendChild(details);
    list.appendChild(row);
  });
}
