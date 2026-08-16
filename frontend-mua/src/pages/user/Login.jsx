import { useState } from 'react'
import { ArrowRightIcon, EyeIcon, EyeSlashIcon, WarningCircleIcon } from '@phosphor-icons/react'
import { Link, useNavigate } from 'react-router-dom'
import { login } from '../../api/bookingApi'
import Navbar from '../../components/Navbar'
import { saveSession } from '../../session'

export default function LoginPage() {
  const navigate = useNavigate()
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState('')
  const [isLoading, setIsLoading] = useState(false)

  const handleSubmit = async (event) => {
    event.preventDefault()
    if (isLoading) return

    setError('')
    setIsLoading(true)

    try {
      const payload = await login({ username, password })
      saveSession({ token: payload.token, user: payload.user, expires_at: payload.expires_at })
      navigate('/admin', { replace: true })
    } catch (err) {
      setError(err?.payload?.message ?? err?.message ?? 'Login gagal. Periksa username dan password.')
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <main className="login-page">
      <Navbar />
      <section className="login-shell" aria-labelledby="login-title">
        <div className="login-card">
          <span className="eyebrow">Ruang internal</span>
          <h1 id="login-title">Masuk ke dashboard.</h1>
          <p>Masukkan kredensial staff untuk mengelola booking dan jadwal.</p>

          {error && <div className="login-error" role="alert"><WarningCircleIcon size={18} aria-hidden="true" /><span>{error}</span></div>}

          <form className="login-form" onSubmit={handleSubmit} noValidate>
            <label className="login-field">
              <span>Username</span>
              <input
                type="text"
                value={username}
                onChange={(event) => setUsername(event.target.value)}
                autoComplete="username"
                placeholder="owner"
                required
                autoFocus
              />
            </label>

            <label className="login-field">
              <span>Password</span>
              <div className="login-password">
                <input
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  autoComplete="current-password"
                  placeholder="••••••••"
                  required
                />
                <button
                  type="button"
                  className="login-toggle"
                  onClick={() => setShowPassword((prev) => !prev)}
                  aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                >
                  {showPassword ? <EyeSlashIcon size={17} /> : <EyeIcon size={17} />}
                </button>
              </div>
            </label>

            <button className="button button-primary login-submit" type="submit" disabled={isLoading}>
              {isLoading ? 'Memproses...' : <>Masuk <ArrowRightIcon size={16} weight="bold" aria-hidden="true" /></>}
            </button>
          </form>

          <p className="login-foot">Klien tanpa akun? <Link to="/booking">Ajukan booking di sini</Link>.</p>
        </div>
      </section>
    </main>
  )
}
