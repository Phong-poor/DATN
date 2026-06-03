import { useTilt } from '../hooks/useAnimations'
import { Cpu, Shield, Activity, RefreshCw, Zap, Server } from 'lucide-react'

// Individually wrapped tilt cards so each card has its own tilt ref/state
function FeatureCard({ icon: Icon, title, desc, tag }) {
  const tiltRef = useTilt(12)

  return (
    <div 
      ref={tiltRef}
      className="tilt-card p-[1px] bg-slate-800/40 hover:bg-gradient-to-br hover:from-cyan-400/20 hover:to-blue-500/20 rounded-2xl border border-white/5 transition-all duration-300 hover:shadow-[0_0_30px_rgba(6,182,212,0.15)] group"
    >
      <div className="bg-[#0a1628]/80 hover:bg-slate-950/40 rounded-[15px] p-6 h-full flex flex-col justify-between backdrop-blur-sm">
        <div>
          {/* Icon frame */}
          <div className="w-12 h-12 bg-cyan-950/50 border border-cyan-500/10 rounded-xl flex items-center justify-center text-cyan-400 group-hover:scale-110 group-hover:bg-cyan-500/10 group-hover:text-white transition-all duration-300 mb-6">
            <Icon className="w-6 h-6" />
          </div>
          
          <span className="font-mono text-[10px] text-cyan-500 font-bold uppercase tracking-wider">{tag}</span>
          <h3 className="font-['Space_Grotesk'] text-xl font-bold text-white mt-2 mb-3">
            {title}
          </h3>
          <p className="text-slate-400 text-sm font-light leading-relaxed">
            {desc}
          </p>
        </div>

        {/* Small decorative visual line */}
        <div className="w-6 h-[2px] bg-slate-800 group-hover:w-full group-hover:bg-cyan-500/50 transition-all duration-300 mt-6"></div>
      </div>
    </div>
  )
}

export default function FeatureSection() {
  const features = [
    {
      icon: Cpu,
      tag: 'Superconducting Matrix',
      title: 'Topological Quantum Silicon',
      desc: 'Superconducting circuits bypass electron scattering, enabling zero-resistance signal routes for instantaneous operations.'
    },
    {
      icon: Server,
      tag: 'Zero-Thermal Enclosure',
      desc: 'Liquid bus chambers filled with low-viscosity fluorochemicals maintain absolute stability under heavy processing cycles.',
      title: 'Cryogenic Cycle Loop'
    },
    {
      icon: Zap,
      tag: 'Laser Interconnect',
      title: 'Optical Neural Storage',
      desc: 'Holographic pipelines transport storage parameters at the speed of light, resolving latency caps seen in regular architectures.'
    },
    {
      icon: Activity,
      tag: 'Self-Calibrating Engine',
      title: 'Cognitive Synergy OS',
      desc: 'Underlying hardware metrics are optimized in real-time by an intelligent, embedded neural calibrator.'
    },
    {
      icon: Shield,
      tag: 'Military-Grade Isolation',
      title: 'Quantum Parity Shields',
      desc: 'Isolated core pipelines feature electrostatic defense shells, preserving structural memory integrity against ambient decay.'
    },
    {
      icon: RefreshCw,
      tag: 'Unrestricted Upgrades',
      title: 'Modular Expansion Node',
      desc: 'Designed with tool-less magnetic interconnect rails, making hot-swappable subsystem upgrades simple and effortless.'
    }
  ]

  return (
    <section id="features" className="relative py-28 bg-gradient-to-b from-[#020817] via-[#071120] to-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background vector accents */}
      <div className="absolute top-10 left-1/2 -translate-x-1/2 w-full max-w-7xl h-full pointer-events-none opacity-20">
        <div className="absolute w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-[120px] top-1/4 -left-20"></div>
        <div className="absolute w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-[120px] bottom-1/4 -right-20"></div>
      </div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Section Title */}
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-16 reveal-hidden reveal-visible">
          <div className="max-w-2xl">
            <span className="text-xs font-mono font-bold text-cyan-400 uppercase tracking-widest bg-cyan-950/40 border border-cyan-500/10 px-3 py-1.5 rounded-full">
              System Core Innovations
            </span>
            <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-4 mb-4 tracking-tight">
              Built to Empower <br/>
              <span className="bg-gradient-to-r from-cyan-400 via-blue-400 to-white bg-clip-text text-transparent">Next-Gen Creators</span>
            </h2>
          </div>
          <p className="text-slate-400 font-light text-base max-w-sm mt-4 md:mt-0 md:text-right">
            Our engineering team designed each system around extreme workloads, ensuring fluid stability under continuous pressure.
          </p>
        </div>

        {/* 3D Tilt Cards Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {features.map((feature, index) => (
            <FeatureCard 
              key={index}
              icon={feature.icon}
              tag={feature.tag}
              title={feature.title}
              desc={feature.desc}
            />
          ))}
        </div>

      </div>
    </section>
  )
}
