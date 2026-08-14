export default function MetricCard({ label, value, caption, icon: Icon }) {
  return (
    <article className="metric-card">
      <div className="metric-card-icon" aria-hidden="true"><Icon size={20} weight="bold" /></div>
      <span className="eyebrow">{label}</span>
      <strong className="metric-value">{value}</strong>
      <p>{caption}</p>
    </article>
  )
}
