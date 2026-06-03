import { Star, ShieldCheck, Quote } from 'lucide-react'

export default function Testimonials() {
  const reviews = [
    {
      name: 'Dr. Helena Vance',
      role: 'AI Research Lead, Neural Labs',
      quote: 'Running our custom deep learning model loops locally used to require spinning up expensive cloud servers. The Aether neural rig lets us iterate model architectures locally in under 3 minutes.',
      metrics: 'Core Efficiency Approved'
    },
    {
      name: 'Marcus Kaelen',
      role: 'Creative Director, Cyberpunk Cinema',
      quote: 'Virtual volume production is highly sensitive to rendering delays. With the cryo-bus chassis cooling cycles running silent at CL12 memory speeds, our workspace stays zero-noise and latency-free.',
      metrics: 'Unrestricted Workflows'
    },
    {
      name: 'Kenji Tanaka',
      role: 'Chief Mechanical Designer, Apex Robotics',
      quote: 'CAD assembly rendering speeds skyrocketed by a margin of 3.8x. The modular expansion docks made adding hot-swappable subsystem nodes a 5-second process without single toolkit additions.',
      metrics: 'Hardware Integrity Validated'
    }
  ]

  return (
    <section className="relative py-28 bg-[#020817] overflow-hidden border-t border-slate-900">
      {/* Background visual accents */}
      <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-cyan-500/5 rounded-full blur-[120px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Section Header */}
        <div className="text-center max-w-3xl mx-auto mb-20 reveal-hidden reveal-visible">
          <span className="text-xs font-mono font-bold text-cyan-400 uppercase tracking-widest bg-cyan-950/40 border border-cyan-500/10 px-3 py-1.5 rounded-full">
            Verified Endorsements
          </span>
          <h2 className="font-['Space_Grotesk'] text-4xl md:text-5xl font-bold text-white mt-4 mb-6 tracking-tight">
            Approved by <span className="bg-gradient-to-r from-white to-cyan-300 bg-clip-text text-transparent">Industry Pioneers</span>
          </h2>
          <p className="text-slate-400 text-lg font-light leading-relaxed">
            Discover why elite creative professionals and AI researchers rely on our high-contrast premium hardware structures.
          </p>
        </div>

        {/* Reviews Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {reviews.map((review, i) => (
            <div 
              key={i} 
              className="bg-[#0a1628]/40 border border-white/5 p-8 rounded-3xl backdrop-blur-md hover:border-cyan-500/20 transition-all duration-300 flex flex-col justify-between relative group"
            >
              {/* Top Quote Icon Accent */}
              <Quote className="absolute top-6 right-6 w-12 h-12 text-slate-800/40 pointer-events-none group-hover:text-cyan-500/10 transition-colors" />

              <div>
                {/* Rating stars */}
                <div className="flex gap-1 text-cyan-400 mb-6">
                  {[...Array(5)].map((_, idx) => (
                    <Star key={idx} className="w-4 h-4 fill-current" />
                  ))}
                </div>

                <p className="text-slate-300 text-sm font-light leading-relaxed mb-8 relative z-10 italic">
                  "{review.quote}"
                </p>
              </div>

              <div>
                <div className="w-full h-[1px] bg-slate-800/80 mb-6"></div>
                
                <h4 className="font-['Space_Grotesk'] font-bold text-white text-base">
                  {review.name}
                </h4>
                
                <div className="text-xs text-slate-500 mt-0.5">{review.role}</div>
                
                <div className="flex items-center gap-2 mt-4 text-[9px] font-mono text-cyan-400 uppercase tracking-wider bg-cyan-950/40 border border-cyan-500/10 w-fit px-2 py-1 rounded">
                  <ShieldCheck className="w-3 h-3 text-cyan-400" />
                  {review.metrics}
                </div>
              </div>
            </div>
          ))}
        </div>

      </div>
    </section>
  )
}
