import { ChevronRight, ShieldCheck, Sparkles, Truck } from 'lucide-react'
import { useMagneticButton } from '../hooks/useAnimations'

export default function FinalCTA() {
  const ctaRef = useMagneticButton()

  return (
    <section className="relative py-28 bg-gradient-to-b from-[#020817] via-[#071120] to-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background glow vector lights */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-cyan-500/10 rounded-full blur-[140px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-5xl mx-auto relative z-10">
        
        {/* Main High-Contrast Panel Card */}
        <div className="bg-slate-950/80 border border-cyan-500/25 rounded-3xl p-8 sm:p-16 text-center backdrop-blur-md glow-cyan relative overflow-hidden">
          {/* Neon corner decorative lights */}
          <div className="absolute top-0 inset-x-0 h-[2px] bg-gradient-to-r from-transparent via-cyan-400 to-transparent"></div>
          <div className="absolute inset-0 bg-radial-[circle_at_center,_transparent_40%,_#020817_90%] z-0 pointer-events-none"></div>

          <div className="relative z-10">
            {/* Cyber Badge */}
            <div className="inline-flex items-center gap-2 px-3 py-1.5 bg-cyan-500/10 border border-cyan-400/20 rounded-full w-fit mb-6 animate-pulse">
              <Sparkles className="w-3.5 h-3.5 text-cyan-400" />
              <span className="text-xs font-mono font-semibold text-cyan-400 uppercase tracking-widest">
                Immediate Allocation Slots Open
              </span>
            </div>

            {/* Headline */}
            <h2 className="font-['Space_Grotesk'] text-4xl sm:text-6xl font-extrabold text-white mb-6 tracking-tight leading-[1.1]">
              Build Your <br/>
              <span className="gradient-text bg-gradient-to-r from-cyan-400 via-blue-400 to-white">Cognitive Masterpiece</span>
            </h2>

            {/* Description */}
            <p className="text-slate-300 text-base sm:text-lg font-light leading-relaxed max-w-xl mx-auto mb-10">
              Each unit is assembled by expert hardware technicians under cryogenic insulation clean rooms. Secure your place in the future of AI computation.
            </p>

            {/* Magnetic Giant Primary Button */}
            <a 
              ref={ctaRef}
              href="#configurator" 
              className="magnetic-btn inline-flex items-center gap-2 px-10 py-5 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-2xl font-bold text-white shadow-[0_0_30px_rgba(6,182,212,0.35)] hover:shadow-[0_0_40px_rgba(6,182,212,0.6)] transition-all duration-300 border border-cyan-400/20 group text-lg"
            >
              Enter Configurator Now
              <ChevronRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
            </a>

            {/* Footer Trust Markers */}
            <div className="flex flex-wrap items-center justify-center gap-8 mt-12 pt-10 border-t border-slate-900 text-xs text-slate-500 font-mono uppercase tracking-wider">
              <div className="flex items-center gap-2">
                <Truck className="w-4 h-4 text-cyan-400" />
                Worldwide Priority Freight Shipping
              </div>
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 text-cyan-400" />
                3-Year Cryo-Insulation Warranty
              </div>
            </div>

          </div>

        </div>

      </div>
    </section>
  )
}
