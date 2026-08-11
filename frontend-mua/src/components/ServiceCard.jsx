import { ArrowRight } from '@phosphor-icons/react'
import { Link } from 'react-router-dom'

export default function ServiceCard({ service }) {
  return (
    <article className="service-option flex-col items-start gap-4">
      <div className="flex w-full items-start justify-between gap-4">
        <div>
          <h2 className="m-0 text-lg font-bold" style={{ color: 'var(--ink)' }}>{service.name}</h2>
          <p className="mt-2 text-sm leading-6" style={{ color: 'var(--muted)' }}>{service.description}</p>
        </div>
        <strong className="whitespace-nowrap font-mono text-xs" style={{ color: 'var(--green)' }}>{service.priceLabel}</strong>
      </div>
      <Link className="button button-secondary" to="/booking">Pilih layanan <ArrowRight size={16} weight="bold" aria-hidden="true" /></Link>
    </article>
  )
}
