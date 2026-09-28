const verificationApiUrl = new URL('api/index.php', window.location.href).toString();

const verificationForm = document.getElementById('verificationForm');
const verificationButton = document.getElementById('verifyButton');
const verificationStatus = document.getElementById('verificationStatus');

function setVerificationStatus(message, type = 'error') {
  verificationStatus.className = `status-message status-${type}`;
  verificationStatus.textContent = message;
}

function getVerificationToken() {
  return new URLSearchParams(window.location.hash.slice(1)).get('token') || '';
}

if (!getVerificationToken()) {
  verificationButton.disabled = true;
  setVerificationStatus('This verification link is missing its token.');
}

verificationForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  const token = getVerificationToken();
  if (!token) {
    setVerificationStatus('This verification link is missing its token.');
    return;
  }

  verificationButton.disabled = true;
  verificationButton.textContent = 'Verifying...';

  try {
    const response = await fetch(verificationApiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json; charset=UTF-8' },
      body: JSON.stringify({ action: 'consumeVerification', token })
    });

    const result = await response.json();
    if (!response.ok) {
      throw new Error(result.error || 'Unable to verify email.');
    }

    history.replaceState(null, '', window.location.pathname);
    setVerificationStatus('Email verified. You can now sign in.', 'success');
    verificationButton.textContent = 'Email verified';
  
  } catch (error) {

    verificationButton.disabled = false;
    verificationButton.textContent = 'Verify email';
    setVerificationStatus(error.message || 'Unable to verify email.');
  }
});
