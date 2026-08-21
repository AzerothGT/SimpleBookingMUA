import { useEffect, useState } from 'react'
import { ArrowLeftIcon, ArrowRightIcon } from '@phosphor-icons/react'
import { Link } from 'react-router-dom'

function orderImages(images = []) {
  const validImages = images.filter((image) => image?.image_url)
  const cover = validImages.find((image) => image.is_cover)
  return cover ? [cover, ...validImages.filter((image) => image !== cover)] : validImages
}

export default function ServiceCard({ service }) {
  const [images, setImages] = useState(() => orderImages(service.images))
  const [activeIndex, setActiveIndex] = useState(() => (orderImages(service.images).length > 1 ? 1 : 0))
  const [isTransitioning, setIsTransitioning] = useState(true)
  const [pointerStart, setPointerStart] = useState(null)
  const hasMultipleImages = images.length > 1
  const slides = hasMultipleImages ? [images[images.length - 1], ...images, images[0]] : images
  const currentIndex = hasMultipleImages ? (activeIndex - 1 + images.length) % images.length : 0

  useEffect(() => {
    if (!isTransitioning) {
      const frame = requestAnimationFrame(() => setIsTransitioning(true))
      return () => cancelAnimationFrame(frame)
    }
  }, [isTransitioning])

  const showPrevious = () => setActiveIndex((index) => Math.max(0, index - 1))
  const showNext = () => setActiveIndex((index) => Math.min(images.length + 1, index + 1))
  const selectImage = (index) => setActiveIndex(hasMultipleImages ? index + 1 : index)
  const handleImageError = (failedImage) => {
    const remainingImages = images.filter((item) => item !== failedImage)
    setImages(remainingImages)
    setActiveIndex(remainingImages.length > 1 ? 1 : 0)
  }
  const handleTrackTransitionEnd = () => {
    if (!hasMultipleImages) return
    if (activeIndex === 0) {
      setIsTransitioning(false)
      setActiveIndex(images.length)
    } else if (activeIndex === images.length + 1) {
      setIsTransitioning(false)
      setActiveIndex(1)
    }
  }
  const handlePointerDown = (event) => setPointerStart(event.clientX)
  const handlePointerUp = (event) => {
    if (pointerStart === null) return setPointerStart(null)
    if (hasMultipleImages) {
      const distance = event.clientX - pointerStart
      if (Math.abs(distance) > 40) {
        if (distance < 0) showNext()
        else showPrevious()
      }
    }
    setPointerStart(null)
  }

  return (
    <article className="service-option flex-col items-start gap-4">
      {images.length > 0 && <div className="service-card-slider" onPointerDown={handlePointerDown} onPointerUp={handlePointerUp} onPointerCancel={() => setPointerStart(null)}>
        <div className="service-card-track" onTransitionEnd={handleTrackTransitionEnd} style={{ transform: `translateX(-${activeIndex * 100}%)`, transition: isTransitioning ? undefined : 'none' }}>
          {slides.map((item, index) => <img className="service-card-image" key={`${item.id ?? item.image_url}-${index}`} src={item.image_url} alt={`${service.name} ${index + 1}`} onError={() => handleImageError(item)} draggable="false" />)}
        </div>
        {hasMultipleImages && <>
          <button className="service-card-arrow service-card-arrow-previous" type="button" onClick={showPrevious} aria-label="Gambar sebelumnya"><ArrowLeftIcon size={16} weight="bold" /></button>
          <button className="service-card-arrow service-card-arrow-next" type="button" onClick={showNext} aria-label="Gambar berikutnya"><ArrowRightIcon size={16} weight="bold" /></button>
          <div className="service-card-dots" aria-label="Pilih gambar">{images.map((item, index) => <button key={item.id ?? item.image_url} className={`service-card-dot${index === currentIndex ? ' is-active' : ''}`} type="button" onClick={() => selectImage(index)} aria-label={`Tampilkan gambar ${index + 1}`} aria-current={index === currentIndex ? 'true' : undefined} />)}</div>
        </>}
      </div>}
      <div className="flex w-full items-start justify-between gap-4">
        <div>
          <h2 className="m-0 font-display text-xl font-normal tracking-[-.02em]" style={{ color: 'var(--ink)' }}>{service.name}</h2>
          <p className="mt-2 text-sm leading-6" style={{ color: 'var(--muted)' }}>{service.description}</p>
        </div>
        <strong className="whitespace-nowrap font-mono text-xs" style={{ color: 'var(--green)' }}>{service.priceLabel}</strong>
      </div>
      <Link className="button button-secondary" to="/booking">Pilih layanan <ArrowRightIcon size={16} weight="bold" aria-hidden="true" /></Link>
    </article>
  )
}
