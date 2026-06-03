import { useState } from 'react'
import { Cpu, Wind, HardDrive, Cpu as MemoryIcon, ChevronRight } from 'lucide-react'
import quantumCoreImg from '../assets/quantum_core.png'
import pcChassisImg from '../assets/pc_chassis.png'
import neuralRamImg from '../assets/neural_ram.png'

export default function ProductShowcase() {
  const [activeTab, setActiveTab] = useState('cpu')

  const products = {
    cpu: {
      name: 'Aether Synapse Core',
      tag: 'SUPERCONDUCTING NEURAL CPU',
      desc: 'The architectural peak of quantum silicon. Built on a proprietary 1.2nm topological insulator lithography, this neural processing unit reaches speeds standard processors cannot duplicate.',
      image: quantumCoreImg,
      specs: [
        { label: 'AI Compute Power', value: '420 TFLOPS FP16' },
        { label: 'Lithography', value: '1.2nm Quantum Topological' },
        { label: 'L3 Cache Architecture', value: '512 MB Superconducting' },
        { label: 'TDP Dynamic Range', value: '35W - 145W Ultra-efficient' },
      ],
      metrics: [
        { name: 'Neural Processing Speed', percentage: 95 },
        { name: 'Thermal Efficiency', percentage: 92 },
      ]
    },
    chassis: {
      name: 'Cryo-Bus Chassis',
      tag: 'LIQUID-COOLED ENCLOSURE',
      desc: 'An aerodynamic marvel engineered for complete thermal neutralization. Uses a proprietary non-conductive dielectric fluid cycle running at near-zero friction flow rates.',
      image: pcChassisImg,
      specs: [
        { label: 'Flow Rate Cycle', value: '4.2 Liters / Min' },
        { label: 'Thermal Capacity', value: 'Up to 950W Neutralization' },
        { label: 'Noise Signature', value: 'Under 12 dBA at full load' },
        { label: 'Material Composition', value: 'Aviation-grade Titanium & Carbon' },
      ],
      metrics: [
        { name: 'Acoustic Suppression', percentage: 98 },
        { name: 'Thermal Dissipation', percentage: 88 },
      ]
    },
    ram: {
      name: 'Neural Flux Memory',
      tag: 'OPTICAL TENSOR STORAGE',
      desc: 'Infinite bandwidth realized. Utilizing carbon fiber heat spreaders and holographic optical laser interconnects to bypass traditional silicon latency caps completely.',
      image: neuralRamImg,
      specs: [
        { label: 'Bus Bandwidth', value: '1,280 GB/s Optical Transfer' },
        { label: 'CAS Latency', value: 'CL 12 Nanosecond response' },
        { label: 'Memory Density', value: 'Up to 256GB Dual Channel stick' },
        { label: 'Error-Correction', value: 'Super-ECC Quantum Parity' },
      ],
      metrics: [
        { name: 'Optical Bus Bandwidth', percentage: 97 },
        { name: 'Data Pipeline Integrity', percentage: 99 },
      ]
    }
  }

  const activeProduct = products[activeTab]

  return (
    <section id="showcase" className="relative py-28 bg-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background visual accents */}
      <div className="absolute top-1/2 right-10 w-[400px] h-[400px] bg-cyan-500/5 rounded-full blur-[100px] pointer-events-none z-0"></div>
      <div className="absolute bottom-10 left-10 w-[300px] h-[300px] bg-blue-600/5 rounded-full blur-[80px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Header Block */}
        <div className="text-center max-w-3xl mx-auto mb-16 reveal-hidden reveal-visible">
          <span className="text-xs font-mono font-bold text-cyan-400 uppercase tracking-widest bg-cyan-950/40 border border-cyan-500/10 px-3 py-1.5 rounded-full">
            Premium Hardware Components
          </span>
          <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-4 mb-6 tracking-tight">
            Designed for <span className="bg-gradient-to-r from-white to-cyan-300 bg-clip-text text-transparent">Extreme Performance</span>
          </h2>
          <p className="text-slate-400 text-lg font-light leading-relaxed">
            Every component is fine-tuned to deliver seamless acceleration. Alternate the tabs below to explore the internal cybernetics.
          </p>
        </div>

        {/* Custom Premium Tabs Control */}
        <div className="flex flex-wrap justify-center gap-4 mb-16 max-w-2xl mx-auto px-4">
          <button 
            onClick={() => setActiveTab('cpu')}
            className={`flex items-center gap-3 px-6 py-4 rounded-xl font-semibold text-sm transition-all duration-300 border ${
              activeTab === 'cpu' 
                ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/25 border-cyan-400 text-white glow-cyan' 
                : 'bg-slate-900/40 border-slate-800 text-slate-400 hover:text-white hover:border-slate-700'
            }`}
          >
            <Cpu className={`w-4 h-4 ${activeTab === 'cpu' ? 'text-cyan-400' : ''}`} />
            Synapse Core
          </button>
          
          <button 
            onClick={() => setActiveTab('chassis')}
            className={`flex items-center gap-3 px-6 py-4 rounded-xl font-semibold text-sm transition-all duration-300 border ${
              activeTab === 'chassis' 
                ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/25 border-cyan-400 text-white glow-cyan' 
                : 'bg-slate-900/40 border-slate-800 text-slate-400 hover:text-white hover:border-slate-700'
            }`}
          >
            <Wind className={`w-4 h-4 ${activeTab === 'chassis' ? 'text-cyan-400' : ''}`} />
            Cryo-Bus Chassis
          </button>

          <button 
            onClick={() => setActiveTab('ram')}
            className={`flex items-center gap-3 px-6 py-4 rounded-xl font-semibold text-sm transition-all duration-300 border ${
              activeTab === 'ram' 
                ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/25 border-cyan-400 text-white glow-cyan' 
                : 'bg-slate-900/40 border-slate-800 text-slate-400 hover:text-white hover:border-slate-700'
            }`}
          >
            <HardDrive className={`w-4 h-4 ${activeTab === 'ram' ? 'text-cyan-400' : ''}`} />
            Flux Memory
          </button>
        </div>

        {/* Interactive Content Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center bg-[#0a1628]/40 border border-white/5 rounded-3xl p-6 md:p-12 backdrop-blur-md">
          
          {/* Visual Column */}
          <div className="lg:col-span-5 flex justify-center relative bg-slate-950/60 border border-white/5 rounded-2xl p-8 h-[340px] md:h-[420px] items-center overflow-hidden">
            {/* Ambient glows behind asset */}
            <div className="absolute w-44 h-44 bg-cyan-400/10 rounded-full blur-2xl animate-pulse"></div>
            
            <img 
              key={activeTab}
              src={activeProduct.image} 
              alt={activeProduct.name} 
              className="w-64 h-64 md:w-80 md:h-80 object-contain drop-shadow-[0_0_25px_rgba(34,211,238,0.2)] animate-float duration-1000"
            />

            {/* Futuristic floating stat */}
            <div className="absolute bottom-4 left-4 glass-dark px-3 py-1.5 border border-white/5 rounded-lg flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-ping"></span>
              <span className="text-[10px] font-mono tracking-wider text-slate-300 uppercase">{activeProduct.tag}</span>
            </div>
          </div>

          {/* Details Column */}
          <div className="lg:col-span-7 flex flex-col justify-between">
            <div>
              <span className="text-xs font-mono font-semibold tracking-wider text-cyan-400 uppercase">{activeProduct.tag}</span>
              <h3 className="font-['Space_Grotesk'] text-3xl md:text-4xl font-bold text-white mt-2 mb-4">
                {activeProduct.name}
              </h3>
              <p className="text-slate-300 font-light text-base leading-relaxed mb-6">
                {activeProduct.desc}
              </p>
            </div>

            {/* Technical Specifications */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
              {activeProduct.specs.map((spec, i) => (
                <div key={i} className="bg-slate-900/60 border border-white/5 p-4 rounded-xl hover:border-cyan-500/20 transition-colors duration-200">
                  <div className="text-xs text-slate-500 font-mono uppercase tracking-wider">{spec.label}</div>
                  <div className="text-sm font-semibold text-white mt-1">{spec.value}</div>
                </div>
              ))}
            </div>

            {/* Graphical Performance Bars */}
            <div className="space-y-4 mb-6">
              {activeProduct.metrics.map((metric, i) => (
                <div key={i}>
                  <div className="flex justify-between text-xs font-semibold mb-1">
                    <span className="text-slate-300">{metric.name}</span>
                    <span className="text-cyan-400 font-mono">{metric.percentage}%</span>
                  </div>
                  <div className="w-full bg-slate-900 rounded-full h-2 overflow-hidden border border-white/5">
                    <div 
                      className="bg-gradient-to-r from-cyan-500 to-blue-500 h-full rounded-full transition-all duration-700 ease-out"
                      style={{ width: `${metric.percentage}%` }}
                    ></div>
                  </div>
                </div>
              ))}
            </div>

            {/* Call to config redirect */}
            <a 
              href="#configurator" 
              className="flex items-center gap-2 text-cyan-400 font-semibold text-sm hover:text-white transition-colors group mt-4 w-fit"
            >
              Add to custom build config
              <ChevronRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </a>
          </div>

        </div>

      </div>
    </section>
  )
}
