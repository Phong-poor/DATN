import { Monitor, Cpu, Sparkles, LayoutGrid } from 'lucide-react'
import setupImg from '../assets/setup_gallery.png'

export default function SetupGallery() {
  return (
    <section id="gallery" className="relative py-28 bg-[#071120]/40 overflow-hidden border-t border-slate-900">
      {/* Glow Effects in Background */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-cyan-500/5 rounded-full blur-[120px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Section Header */}
        <div className="max-w-3xl mb-16 reveal-hidden reveal-visible">
          <span className="text-xs font-mono font-bold text-cyan-400 uppercase tracking-widest bg-cyan-950/40 border border-cyan-500/10 px-3 py-1.5 rounded-full">
            Premium Workspaces
          </span>
          <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-4 mb-6 tracking-tight">
            The Digital Sanctum of <br/>
            <span className="bg-gradient-to-r from-cyan-400 via-blue-400 to-white bg-clip-text text-transparent">Elite Creators</span>
          </h2>
          <p className="text-slate-400 text-lg font-light leading-relaxed">
            See how top-tier neural developers, virtual production studios, and mechanical engineers deploy the Aether neural rig to optimize their workflows.
          </p>
        </div>

        {/* Dynamic Interactive Showroom Display */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
          
          {/* Main Visual Display Block (Left/Center) */}
          <div className="lg:col-span-8 gallery-item rounded-3xl border border-white/5 shadow-2xl relative overflow-hidden group min-h-[380px] sm:min-h-[480px]">
            {/* Holographic scan line */}
            <div className="absolute inset-0 bg-gradient-to-b from-cyan-400/5 to-transparent h-1/2 pointer-events-none rounded-3xl z-10 animate-scan-line"></div>
            
            <img 
              src={setupImg} 
              alt="Elite Creative Setup" 
              className="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
            />
            
            {/* Custom Overlay */}
            <div className="gallery-overlay absolute inset-0 z-20 flex flex-col justify-end p-6 sm:p-10">
              <div className="bg-slate-950/80 border border-white/5 rounded-2xl p-6 backdrop-blur-md max-w-lg transition-transform duration-300 group-hover:translate-y-0">
                <div className="flex items-center gap-2 mb-2">
                  <Sparkles className="w-4 h-4 text-cyan-400 animate-pulse" />
                  <span className="font-mono text-[10px] text-cyan-400 font-bold uppercase tracking-widest">Featured Workspace #42</span>
                </div>
                <h3 className="font-['Space_Grotesk'] text-xl sm:text-2xl font-bold text-white mb-2">
                  VFX & Neural Simulation Deck
                </h3>
                <p className="text-slate-300 text-xs sm:text-sm font-light leading-relaxed">
                  Deployed at Synthetix Labs, this custom Aether workstation integrates 4x Neural Cores and cryo-liquid thermal cycles to run real-time fluid solvers without frame dropping.
                </p>
              </div>
            </div>
          </div>

          {/* Configuration Stats Panel (Right) */}
          <div className="lg:col-span-4 flex flex-col justify-between gap-6">
            
            {/* Card 1 */}
            <div className="bg-[#0a1628]/60 border border-white/5 p-6 rounded-3xl backdrop-blur-md flex-1 flex flex-col justify-between hover:border-cyan-500/20 transition-all duration-300">
              <div>
                <div className="w-10 h-10 bg-cyan-950/50 border border-cyan-500/10 rounded-xl flex items-center justify-center text-cyan-400 mb-4">
                  <Monitor className="w-5 h-5" />
                </div>
                <h4 className="font-['Space_Grotesk'] text-lg font-bold text-white mb-2">Dual Ultra-Link Interface</h4>
                <p className="text-slate-400 text-xs sm:text-sm font-light leading-relaxed">
                  Support up to three 8K optical-link display terminals running at a synchronized 144Hz latency-free pipeline rate.
                </p>
              </div>
              <div className="font-mono text-[10px] text-slate-500 uppercase mt-4">Optical Interconnect Ready</div>
            </div>

            {/* Card 2 */}
            <div className="bg-[#0a1628]/60 border border-white/5 p-6 rounded-3xl backdrop-blur-md flex-1 flex flex-col justify-between hover:border-cyan-500/20 transition-all duration-300">
              <div>
                <div className="w-10 h-10 bg-blue-950/50 border border-blue-500/10 rounded-xl flex items-center justify-center text-blue-400 mb-4">
                  <Cpu className="w-5 h-5" />
                </div>
                <h4 className="font-['Space_Grotesk'] text-lg font-bold text-white mb-2">Cryogenic Thermal Grid</h4>
                <p className="text-slate-400 text-xs sm:text-sm font-light leading-relaxed">
                  Fully integrated custom loop structures run through modular deck ports, cooling high-speed chips in silent equilibrium.
                </p>
              </div>
              <div className="font-mono text-[10px] text-slate-500 uppercase mt-4">Fluorochemical Cycle Integration</div>
            </div>

          </div>

        </div>

      </div>
    </section>
  )
}
