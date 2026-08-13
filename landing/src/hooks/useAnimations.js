import { useEffect, useRef } from 'react'

export function useScrollReveal(options = {}) {
  const { threshold = 0.1, rootMargin = '0px 0px -60px 0px', trigger } = options

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const el = entry.target
            if (el.classList.contains('reveal-hidden')) {
              el.classList.add('reveal-visible')
            }
            if (el.classList.contains('reveal-left') || el.classList.contains('reveal-right')) {
              el.classList.add('reveal-visible-x')
            }
            // Stagger children
            el.querySelectorAll('[data-stagger]').forEach((child, i) => {
              setTimeout(() => {
                child.style.opacity = '1'
                child.style.transform = 'translateY(0)'
              }, i * 120)
            })
          }
        })
      },
      { threshold, rootMargin }
    )

    document.querySelectorAll('.reveal-hidden, .reveal-left, .reveal-right, [data-reveal-container]').forEach((el) => {
      observer.observe(el)
    })

    return () => observer.disconnect()
  }, [threshold, rootMargin, trigger])
}

export function useTilt(sensitivity = 15) {
  const ref = useRef(null)

  useEffect(() => {
    const el = ref.current
    if (!el) return

    const handleMouseMove = (e) => {
      const rect = el.getBoundingClientRect()
      const x = (e.clientX - rect.left) / rect.width - 0.5
      const y = (e.clientY - rect.top) / rect.height - 0.5
      el.style.transform = `perspective(1000px) rotateX(${-y * sensitivity}deg) rotateY(${x * sensitivity}deg) translateZ(10px)`
    }
    const handleMouseLeave = () => {
      el.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0)'
    }

    el.addEventListener('mousemove', handleMouseMove)
    el.addEventListener('mouseleave', handleMouseLeave)
    return () => {
      el.removeEventListener('mousemove', handleMouseMove)
      el.removeEventListener('mouseleave', handleMouseLeave)
    }
  }, [sensitivity])

  return ref
}

export function useMagneticButton() {
  const ref = useRef(null)

  useEffect(() => {
    const el = ref.current
    if (!el) return

    const handleMouseMove = (e) => {
      const rect = el.getBoundingClientRect()
      const x = e.clientX - rect.left - rect.width / 2
      const y = e.clientY - rect.top - rect.height / 2
      el.style.transform = `translate(${x * 0.25}px, ${y * 0.25}px)`
    }
    const handleMouseLeave = () => {
      el.style.transform = 'translate(0, 0)'
    }

    el.addEventListener('mousemove', handleMouseMove)
    el.addEventListener('mouseleave', handleMouseLeave)
    return () => {
      el.removeEventListener('mousemove', handleMouseMove)
      el.removeEventListener('mouseleave', handleMouseLeave)
    }
  }, [])

  return ref
}
