import { useEffect, useState } from 'react'
import { useScrollReveal } from './hooks/useAnimations'
import Navbar from './components/Navbar'
import HeroSection from './components/HeroSection'
import ProductShowcase from './components/ProductShowcase'
import FeatureSection from './components/FeatureSection'
import VisualStory from './components/VisualStory'
import SetupGallery from './components/SetupGallery'
import WhyChooseUs from './components/WhyChooseUs'
import BuyingJourney from './components/BuyingJourney'
import Testimonials from './components/Testimonials'
import FinalCTA from './components/FinalCTA'
import Footer from './components/Footer'

export default function App() {
  // Track global scroll progress indicator
  const [scrollProgress, setScrollProgress] = useState(0)
  const [isPreloading, setIsPreloading] = useState(true)

  // Initialize the high-precision scroll reveal IntersectionObserver
  useScrollReveal({ trigger: isPreloading })

  useEffect(() => {
    const handleScroll = () => {
      const totalScroll = document.documentElement.scrollHeight - window.innerHeight
      const currentScroll = window.scrollY
      if (totalScroll > 0) {
        setScrollProgress((currentScroll / totalScroll) * 100)
      }
    }

    // Simulate cyber technical asset loader
    const timer = setTimeout(() => {
      setIsPreloading(false)
    }, 1200)

    window.addEventListener('scroll', handleScroll)
    return () => {
      window.removeEventListener('scroll', handleScroll)
      clearTimeout(timer)
    }
  }, [])

  if (isPreloading) {
    return (
      <div className="fixed inset-0 z-[9999] bg-[#020817] flex flex-col items-center justify-center font-mono">
        <div className="relative flex flex-col items-center gap-6 p-10 max-w-sm w-full text-center">
          {/* Pulsing glow particle */}
          <div className="absolute w-24 h-24 bg-cyan-500/10 rounded-full blur-xl animate-pulse"></div>
          
          <div className="font-bold text-lg tracking-widest text-white">
            AETHER<span className="text-cyan-400 font-light">TECH</span>
          </div>

          <div className="w-full bg-slate-900 border border-white/5 rounded-full h-1.5 overflow-hidden">
            <div className="bg-gradient-to-r from-cyan-400 to-blue-500 h-full w-[80%] rounded-full animate-[shimmer_1.5s_infinite_linear] bg-[length:200%_100%]"></div>
          </div>

          <div className="text-[10px] text-cyan-500 uppercase tracking-widest animate-pulse">
            Đang khởi tạo cấu hình hiệu năng...
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="noise min-h-screen bg-[#020817] relative select-none">
      
      {/* Sleek Top Scroll Indicator Line */}
      <div 
        className="fixed top-0 left-0 h-[2.5px] bg-gradient-to-r from-cyan-400 via-blue-500 to-cyan-400 z-[999] transition-all duration-100 ease-out glow-cyan"
        style={{ width: `${scrollProgress}%` }}
      ></div>

      {/* Primary Landing Page Layout Assemblies */}
      <Navbar />
      <HeroSection />
      
      <div className="reveal-hidden">
        <ProductShowcase />
      </div>

      <div className="reveal-hidden">
        <FeatureSection />
      </div>

      <div className="reveal-hidden">
        <VisualStory />
      </div>

      <div className="reveal-hidden">
        <SetupGallery />
      </div>

      <div className="reveal-hidden">
        <WhyChooseUs />
      </div>

      <div className="reveal-hidden">
        <BuyingJourney />
      </div>

      <div className="reveal-hidden">
        <Testimonials />
      </div>

      <div className="reveal-hidden">
        <FinalCTA />
      </div>

      <Footer />

    </div>
  )
}
