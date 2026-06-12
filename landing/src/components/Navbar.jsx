import { useState } from 'react'
import { Cpu, Menu, X, ShoppingCart } from 'lucide-react'
import { useMagneticButton } from '../hooks/useAnimations'

export default function Navbar() {
  const [isOpen, setIsOpen] = useState(false)
  const magneticCtaRef = useMagneticButton()

  return (
    <nav className="fixed top-4 left-1/2 -translate-x-1/2 w-[92%] max-w-7xl z-50 glass-dark rounded-2xl px-6 py-4 transition-all duration-300 hover:border-cyan-500/20">
      <div className="flex items-center justify-between">
        {/* Logo */}
        <a href="#" className="flex items-center gap-2 group">
          <div className="p-2 bg-gradient-to-tr from-cyan-500 to-blue-600 rounded-lg text-white shadow-[0_0_15px_rgba(34,211,238,0.3)] group-hover:shadow-[0_0_25px_rgba(34,211,238,0.5)] transition-all duration-300">
            <Cpu className="w-5 h-5 animate-pulse" />
          </div>
          <span className="font-['Space_Grotesk'] font-bold text-xl tracking-wider bg-gradient-to-r from-white via-cyan-200 to-blue-400 bg-clip-text text-transparent">
            AETHER<span className="text-cyan-400 font-light">TECH</span>
          </span>
        </a>

        {/* Desktop Navigation Links */}
        <div className="hidden md:flex items-center gap-8 font-medium text-sm text-slate-300">
          <a href="#showcase" className="hover:text-cyan-400 transition-colors duration-200 relative group py-1">
            Specs
            <span className="absolute bottom-0 left-0 w-0 h-[2px] bg-cyan-400 transition-all duration-300 group-hover:w-full"></span>
          </a>
          <a href="#features" className="hover:text-cyan-400 transition-colors duration-200 relative group py-1">
            Core Tech
            <span className="absolute bottom-0 left-0 w-0 h-[2px] bg-cyan-400 transition-all duration-300 group-hover:w-full"></span>
          </a>
          <a href="#story" className="hover:text-cyan-400 transition-colors duration-200 relative group py-1">
            Engineering
            <span className="absolute bottom-0 left-0 w-0 h-[2px] bg-cyan-400 transition-all duration-300 group-hover:w-full"></span>
          </a>
          <a href="#gallery" className="hover:text-cyan-400 transition-colors duration-200 relative group py-1">
            Setups
            <span className="absolute bottom-0 left-0 w-0 h-[2px] bg-cyan-400 transition-all duration-300 group-hover:w-full"></span>
          </a>
          <a href="#configurator" className="hover:text-cyan-400 transition-colors duration-200 relative group py-1 text-cyan-400">
            Configure
            <span className="absolute bottom-0 left-0 w-full h-[2px] bg-cyan-400/50 scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></span>
          </a>
        </div>

        {/* Action Buttons */}
        <div className="hidden md:flex items-center gap-4">
          <button className="p-2.5 text-slate-300 hover:text-cyan-400 hover:bg-white/5 rounded-xl transition-all duration-200 relative group">
            <ShoppingCart className="w-5 h-5" />
            <span className="absolute top-1 right-1 w-2 h-2 bg-cyan-400 rounded-full animate-ping"></span>
          </button>
          
          <a 
            ref={magneticCtaRef}
            href="#configurator" 
            className="magnetic-btn px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-xl font-semibold text-sm text-white shadow-[0_0_20px_rgba(6,182,212,0.2)] hover:shadow-[0_0_30px_rgba(6,182,212,0.4)] hover:scale-[1.02] transition-all duration-300 border border-cyan-400/20"
          >
            Configure Rig
          </a>
        </div>

        {/* Mobile menu button */}
        <div className="md:hidden flex items-center gap-4">
          <button className="p-2 text-slate-300 hover:text-cyan-400 hover:bg-white/5 rounded-lg transition-colors">
            <ShoppingCart className="w-5 h-5" />
          </button>
          <button 
            onClick={() => setIsOpen(!isOpen)}
            className="p-2 text-slate-300 hover:text-cyan-400 hover:bg-white/5 rounded-lg transition-colors"
          >
            {isOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>
      </div>

      {/* Mobile Menu Dropdown */}
      {isOpen && (
        <div className="md:hidden mt-4 pt-4 border-t border-white/5 flex flex-col gap-4 animate-fade-in">
          <a 
            href="#showcase" 
            onClick={() => setIsOpen(false)}
            className="px-4 py-2 hover:bg-cyan-500/10 rounded-lg text-slate-300 hover:text-cyan-400 transition-colors"
          >
            Specs
          </a>
          <a 
            href="#features" 
            onClick={() => setIsOpen(false)}
            className="px-4 py-2 hover:bg-cyan-500/10 rounded-lg text-slate-300 hover:text-cyan-400 transition-colors"
          >
            Core Tech
          </a>
          <a 
            href="#story" 
            onClick={() => setIsOpen(false)}
            className="px-4 py-2 hover:bg-cyan-500/10 rounded-lg text-slate-300 hover:text-cyan-400 transition-colors"
          >
            Engineering
          </a>
          <a 
            href="#gallery" 
            onClick={() => setIsOpen(false)}
            className="px-4 py-2 hover:bg-cyan-500/10 rounded-lg text-slate-300 hover:text-cyan-400 transition-colors"
          >
            Setups
          </a>
          <a 
            href="#configurator" 
            onClick={() => setIsOpen(false)}
            className="px-4 py-2 bg-cyan-500/10 rounded-lg text-cyan-400 font-medium transition-colors"
          >
            Configure Now
          </a>
          <a 
            href="#configurator" 
            onClick={() => setIsOpen(false)}
            className="mx-4 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-xl font-semibold text-center text-white shadow-lg"
          >
            Configure Rig
          </a>
        </div>
      )}
    </nav>
  )
}
