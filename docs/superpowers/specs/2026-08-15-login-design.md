# Login Page Design

## Goal

Provide a login page for internal staff (admin, owner, staff) to authenticate against the existing backend and access the admin dashboard at `/admin`.

## Scope

- Login form (username + password) at `/login`.
- Submit to `POST /api/login` via `apiClient`; store token and user in `localStorage`.
- Redirect authenticated users to `/admin`.
- Navbar adapts: shows "Masuk" button when logged out; shows user name + "Keluar" button when logged in.
- Logout clears token and user, returns to home.

Out of scope: registration, password reset, session refresh, role-based route guards beyond redirect-on-existing-token.

## Visual Direction

- Single clear primary CTA: "Masuk".
- Existing theme tokens (`--paper`, `--green`, `--lime`, `--orange`, Manrope, Georgia).
- Square containers (no border-radius), consistent with dashboard theme.
- Loading state on submit, visible error on failed credentials.
- Password show/hide toggle.
- Reduced-motion respected.

## Component Structure

- `frontend-mua/src/pages/user/Login.jsx`: form state, submit handler, redirect.
- `frontend-mua/src/api/bookingApi.js`: add `login({ username, password })`.
- `frontend-mua/src/components/Navbar.jsx`: read auth state, render Masuk/Keluar.
- `frontend-mua/src/App.jsx`: register `/login` route before catch-all; redirect to `/admin` if token exists.

## Data Flow

1. User submits username + password.
2. `apiClient.post('/login', { username, password })` returns `{ token, user: { id, name, role } }`.
3. Store `auth_token` and `auth_user` in `localStorage`.
4. `navigate('/admin')` with `replace: true`.
5. On failure, show error message, keep username, clear password.

## Validation

```bash
npm run lint && npm run build
```

Manual: failed login shows error, successful login stores token + redirects to `/admin`, navbar switches to "Keluar", logout returns to home.
