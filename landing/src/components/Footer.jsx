import { Cpu, MessageSquare } from 'lucide-react'

export default function Footer() {
  return (
    <footer className="relative bg-[#020817] border-t border-slate-900 pt-20 pb-10 overflow-hidden">
      {/* Decorative background glow */}
      <div className="absolute bottom-0 right-0 w-[300px] h-[300px] bg-cyan-500/5 rounded-full blur-[100px] pointer-events-none z-0"></div>

      <div className="w-[92%] max-w-7xl mx-auto relative z-10">
        
        {/* Main Footer Links & Bio Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12 mb-16">
          
          {/* Brand Bio */}
          <div className="lg:col-span-2 space-y-6">
            <a href="#" className="flex items-center gap-2 group w-fit">
              <div className="p-2 bg-gradient-to-tr from-cyan-500 to-blue-600 rounded-lg text-white shadow-[0_0_15px_rgba(34,211,238,0.3)]">
                <Cpu className="w-5 h-5" />
              </div>
              <span className="font-['Space_Grotesk'] font-bold text-xl tracking-wider bg-gradient-to-r from-white to-cyan-300 bg-clip-text text-transparent">
                AETHER<span className="text-cyan-400 font-light">TECH</span>
              </span>
            </a>
            
            <p className="text-slate-400 text-sm font-light leading-relaxed max-w-sm">
              Crafting premium high-performance quantum computing rigs to accelerate the creative and scientific breakthroughs of tomorrow.
            </p>

            {/* Social Icons */}
            <div className="flex gap-4">
              {/* GitHub Inline SVG */}
              <a href="#" className="p-2.5 text-slate-500 hover:text-cyan-400 hover:bg-slate-900 rounded-lg transition-colors border border-transparent hover:border-slate-800 flex items-center justify-center">
                <svg className="w-5 h-5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                  <path fillRule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clipRule="evenodd" />
                </svg>
              </a>
              {/* Twitter X Inline SVG */}
              <a href="#" className="p-2.5 text-slate-500 hover:text-cyan-400 hover:bg-slate-900 rounded-lg transition-colors border border-transparent hover:border-slate-800 flex items-center justify-center">
                <svg className="w-[18px] h-[18px] fill-current" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                </svg>
              </a>
              <a href="#" className="p-2 text-slate-500 hover:text-cyan-400 hover:bg-slate-900 rounded-lg transition-colors border border-transparent hover:border-slate-800">
                <MessageSquare className="w-5 h-5" />
              </a>
            </div>
          </div>

          {/* Column 1: Products */}
          <div>
            <h4 className="font-['Space_Grotesk'] font-bold text-white text-sm uppercase tracking-wider mb-6">Products</h4>
            <ul className="space-y-3.5 text-sm">
              <li><a href="#showcase" className="text-slate-400 hover:text-cyan-400 transition-colors">Aether Core CPU</a></li>
              <li><a href="#showcase" className="text-slate-400 hover:text-cyan-400 transition-colors">Cryo-Bus Chassis</a></li>
              <li><a href="#showcase" className="text-slate-400 hover:text-cyan-400 transition-colors">Flux Optical RAM</a></li>
              <li><a href="#configurator" className="text-slate-400 hover:text-cyan-400 transition-colors">Custom Configurations</a></li>
            </ul>
          </div>

          {/* Column 2: Tech Innovations */}
          <div>
            <h4 className="font-['Space_Grotesk'] font-bold text-white text-sm uppercase tracking-wider mb-6">Core Tech</h4>
            <ul className="space-y-3.5 text-sm">
              <li><a href="#features" className="text-slate-400 hover:text-cyan-400 transition-colors">Topological Silicon</a></li>
              <li><a href="#features" className="text-slate-400 hover:text-cyan-400 transition-colors">Cryo Liquid Systems</a></li>
              <li><a href="#features" className="text-slate-400 hover:text-cyan-400 transition-colors">Laser Interconnects</a></li>
              <li><a href="#features" className="text-slate-400 hover:text-cyan-400 transition-colors">Synergy Core OS</a></li>
            </ul>
          </div>

          {/* Column 3: Corporate Info */}
          <div>
            <h4 className="font-['Space_Grotesk'] font-bold text-white text-sm uppercase tracking-wider mb-6">Company</h4>
            <ul className="space-y-3.5 text-sm">
              <li><a href="#" className="text-slate-400 hover:text-cyan-400 transition-colors">About Engineering</a></li>
              <li><a href="#" className="text-slate-400 hover:text-cyan-400 transition-colors">Stress Laboratories</a></li>
              <li><a href="#" className="text-slate-400 hover:text-cyan-400 transition-colors">Freight Logistics</a></li>
              <li><a href="#" className="text-slate-400 hover:text-cyan-400 transition-colors">Contact Technical</a></li>
            </ul>
          </div>

        </div>

        {/* Separator line */}
        <div className="w-full h-[1px] bg-slate-900 my-8"></div>

        {/* Bottom Technical Status & Copyright */}
        <div className="flex flex-col md:flex-row items-center justify-between gap-4 text-xs font-mono text-slate-500 uppercase tracking-wider">
          <div>
            © {new Date().getFullYear()} AETHERTECH Systems Inc. All Rights Reserved.
          </div>
          
          {/* Active Status Display Indicator */}
          <div className="flex items-center gap-2.5 bg-slate-950/60 border border-slate-800/80 px-3.5 py-1.5 rounded-full backdrop-blur-sm">
            <span className="flex h-2.5 w-2.5 relative">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-cyan-500"></span>
            </span>
            <span>All Cyro Systems: Operational</span>
            <span className="text-slate-700">|</span>
            <span className="text-cyan-500 font-bold">API latency: 8ms</span>
          </div>
        </div>

      </div>
    </footer>
  )
}
