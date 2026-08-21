const paymentLabels = {
  none: 'Belum ada pembayaran',
  pending: 'Menunggu pembayaran',
  paid: 'Lunas',
  failed: 'Pembayaran gagal',
  expired: 'Kedaluwarsa',
  refunded: 'Dana dikembalikan',
}

export default function PaymentStatusBadge({ state }) {
  const label = paymentLabels[state] ?? state

  return <span className={`status-badge status-badge--payment-${state}`} aria-label={`Status pembayaran ${label}`}>{label}</span>
}
