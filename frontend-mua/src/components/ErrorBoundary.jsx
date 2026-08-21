import { Component } from 'react'

export default class ErrorBoundary extends Component {
  constructor(props) {
    super(props)
    this.state = { hasError: false }
  }

  static getDerivedStateFromError() {
    return { hasError: true }
  }

  componentDidCatch(error, info) {
    if (import.meta.env.DEV) console.error('ErrorBoundary caught an error:', error, info)
  }

  handleReload = () => {
    this.setState({ hasError: false })
    window.location.assign('/')
  }

  render() {
    if (!this.state.hasError) return this.props.children

    return (
      <main className="error-boundary" role="alert">
        <div className="error-boundary-card">
          <span className="eyebrow">Terjadi kesalahan</span>
          <h1>Ada yang tidak beres.</h1>
          <p>Maaf, halaman gagal dimuat. Silakan muat ulang atau kembali ke beranda.</p>
          <button className="button button-primary" type="button" onClick={this.handleReload}>
            Kembali ke beranda
          </button>
        </div>
      </main>
    )
  }
}
