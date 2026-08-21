/** Menentukan status pembayaran dari transaksi terbaru sebuah booking. */
export function getPaymentState(transactions = []) {
  if (!transactions.length) return { key: 'none', transaction: null }

  const latest = [...transactions].sort(
    (a, b) => new Date(b.updated_at ?? b.created_at ?? 0) - new Date(a.updated_at ?? a.created_at ?? 0),
  )[0]
  const status = latest.transaction_status

  if (['capture', 'settlement'].includes(status) && latest.fraud_status === 'accept' && latest.paid_at) {
    return { key: 'paid', transaction: latest }
  }
  if (['refund', 'partial_refund', 'chargeback', 'partial_chargeback'].includes(status)) {
    return { key: 'refunded', transaction: latest }
  }
  if (['deny', 'failure'].includes(status)) return { key: 'failed', transaction: latest }
  if (['cancel', 'expire'].includes(status)) return { key: 'expired', transaction: latest }

  return { key: 'pending', transaction: latest }
}
