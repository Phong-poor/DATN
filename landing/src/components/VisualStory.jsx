import { ShieldAlert, Award, FileSpreadsheet, Eye } from 'lucide-react'

export default function VisualStory() {
  const steps = [
    {
      phase: 'Phase 01',
      title: 'Topological Silicon Layout',
      desc: 'Our physicists formulated a superconductor pathway matrix to avoid electron collision. By printing circuits on specialized topological insulators, latency decreased to near-zero levels.',
      status: 'Validated',
      icon: Award
    },
    {
      phase: 'Phase 02',
      title: 'Liquid Cryo-Bus Chassis',
      desc: 'Standard fans could not sustain thermal demands. We built an aerodynamic titanium shell, housing a sealed fluorochemical pump to run continuous -180°C cooling cycles.',
      status: 'Perfected',
      icon: Eye
    },
    {
      phase: 'Phase 03',
      title: 'Cognitive Synergy Integration',
      desc: 'A physical rig requires customized software core synergy. We developed Cognitive Synergy OS—an interface system optimized to balance thread metrics dynamically.',
      status: 'Live Beta',
      icon: FileSpreadsheet
    },
    {
      phase: 'Phase 04',
      title: 'Cryo-Thermal Stress Calibration',
      desc: 'Before delivery, each rig spends 48 hours undergoing artificial neural stress testing. The unit undergoes custom benchmark loops until performance metrics stabilize.',
      status: 'Production Ready',
      icon: ShieldAlert
    }
  ]

  return (
    <section id="story" className="relative py-28 bg-[#020817] overflow-hidden border-t border-slate-900">
      {/* Decorative glows in background */}
      <div className="absolute top-1/3 left-10 w-[400px] h-[400px] bg-blue-600/5 rounded-full blur-[100px] pointer-events-none z-0"></div>
      <div className="absolute bottom-1/3 right-10 w-[400px] h-[400px] bg-cyan-500/5 rounded-full blur-[100px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Section Header */}
        <div className="text-center max-w-3xl mx-auto mb-20 reveal-hidden reveal-visible">
          <span className="text-xs font-mono font-bold text-cyan-400 uppercase tracking-widest bg-cyan-950/40 border border-cyan-500/10 px-3 py-1.5 rounded-full">
            Engineering Timeline
          </span>
          <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-4 mb-6 tracking-tight">
            How We Crafted <span className="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">Aether Neural Rig</span>
          </h2>
          <p className="text-slate-400 text-lg font-light leading-relaxed">
            Our timeline represents thousands of engineering hours dedicated to creating the physical peak of creator hardware.
          </p>
        </div>

        {/* Timeline Path Structure */}
        <div className="relative">
          {/* Central Vertical Vector Line */}
          <div className="timeline-line hidden md:block"></div>

          {/* Timeline Nodes */}
          <div className="space-y-12 md:space-y-24">
            {steps.map((step, index) => {
              const Icon = step.icon
              const isEven = index % 2 === 0

              return (
                <div 
                  key={index}
                  className={`flex flex-col md:flex-row items-center justify-between relative z-10 ${
                    isEven ? '' : 'md:flex-row-reverse'
                  }`}
                >
                  {/* Content Card Side */}
                  <div className="w-full md:w-[45%] bg-[#0a1628]/40 border border-white/5 p-8 rounded-3xl backdrop-blur-md hover:border-cyan-500/20 transition-all duration-300">
                    <div className="flex justify-between items-center mb-4">
                      <span className="font-mono text-xs text-cyan-400 font-bold tracking-widest uppercase bg-cyan-950/40 border border-cyan-500/10 px-2.5 py-1 rounded">
                        {step.phase}
                      </span>
                      <span className="text-[10px] font-mono text-slate-500 uppercase tracking-wider bg-slate-900 border border-white/5 px-2 py-0.5 rounded-full">
                        {step.status}
                      </span>
                    </div>

                    <h3 className="font-['Space_Grotesk'] text-2xl font-bold text-white mb-3">
                      {step.title}
                    </h3>
                    <p className="text-slate-300 text-sm font-light leading-relaxed">
                      {step.desc}
                    </p>
                  </div>

                  {/* Central Node Indicator */}
                  <div className="absolute left-1/2 -translate-x-1/2 w-12 h-12 rounded-full bg-slate-950 border-2 border-cyan-500 flex items-center justify-center text-cyan-400 glow-cyan z-20 hidden md:flex">
                    <Icon className="w-5 h-5" />
                  </div>

                  {/* Empty Spacer Side */}
                  <div className="hidden md:block w-[45%]"></div>
                </div>
              )
            })}
          </div>

        </div>

      </div>
    </section>
  )
}
